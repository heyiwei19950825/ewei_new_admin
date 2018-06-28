<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/2/1
 * Time: 2:19
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\GoodsCollective as GoodsCollectiveModel;
use app\api\model\Shop;
use app\api\model\User;
use app\api\model\UserAddress;
use app\api\model\Goods as GoodsModel;
use app\api\model\Region;
use app\api\model\Express;
use app\api\model\Order;
use app\api\model\UserCollective;
use app\api\service\Token;
use app\api\service\Pay;

class Collective extends BaseController
{
    public $uid = '';

    public function _initialize()
    {
        parent::_initialize();
        if( $_SERVER['PATH_INFO'] != '/api/v1/collective/check' && $_SERVER['PATH_INFO'] != '/api/v1/collective/detail'){
            $this->uid = Token::getCurrentUid();
        }
    }

    /**
     * 获取所有在线的开团商品
     */
    public function getList(){
        $page = $this->request->param('page');
        $size = $this->request->param('size');
        $collectiveList = GoodsCollectiveModel::getList($page,$size);
        //商品相册
        foreach ( $collectiveList['data'] as &$item) {
//            $item['photo'] = unserialize($item['photo']);
//            if($item['photo']){
//                foreach ( $item['photo']as &$pItem) {
//                    $pItem =  config('setting.domain').$pItem;
//                }
//            }
        }
        $collectiveList['data'] = self::prefixDomainToArray('thumb',$collectiveList['data']);

        //配置参数
        $data = [
            'count' => $collectiveList['total'],//总数
            'last_page' => $collectiveList['last_page'],//下一页页码
            'currentPage' => $collectiveList['current_page'],//当前页码
            'pagesize'  => $size,//每页长度
            'totalPages' => 1, //总页数
            'data' => $collectiveList['data'],
            'banner' => 'http://wq.mskfkj.com/public/1520405232.jpg'
        ];

        $row = [
            'errno' => 0,
            'errmsg' => '',
            'data' => $data
        ];

        return $row;
    }

    /**
     * 获取详细信息
     * @return array
     */
    public function getOne(){
        $id = $this->request->param('id');
        $collective = GoodsCollectiveModel::getOne($id);
        $collective['thumb'] = self::prefixDomain($collective['thumb']);
        //处理轮播图片信息
        preg_match_all ('/\"\/uploads(.*?)\"/', $collective['photo'], $m);
        if(empty($m[1])){
            $photo = explode(',',$collective['photo']);
            if( !empty($photo)){
                foreach ( $photo as $k=>$v){
                    $collective['gallery'][] = self::prefixDomain($v);
                }
            }
        }
        foreach ( $m[1] as $k=>$v){
            $collective['gallery'][] = self::prefixDomain('/uploads'.$v);
        }
        $row = [
            'errno' => 0,
            'errmsg' => '',
            'data' => [
                'info' => $collective
                ]
        ];
        return $row;
    }

    /**
     * 团购购物车
     * @return array
     */
    public function Cart(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $goodsId = $this->request->param('goodsId',0);
        $num = $this->request->param('num',0);
        $addressId = $this->request->param('addressId',0);

        //收货地址
        $userAddressModel = new UserAddress();
        if( $addressId != 0 ){
          $addresssMap = ['id'=>$addressId,'user_id'=>$this->uid];
        }else{
          $addresssMap = ['is_default'=>1,'user_id'=>$this->uid];
        }
        $address = $userAddressModel->where($addresssMap)->find();

        if( empty($address) ){
            $address = 0;
        }

        if( !empty($address) ){
            $address['province_name'] = Region::getRegionName($address['province_id']);
            $address['city_name'] = Region::getRegionName($address['city_id']);
            $address['district_name'] = Region::getRegionName($address['district_id']);
            $address['full_region'] = $address['province_name'] . $address['city_name'] . $address['district_name'];
        }else{
            $address = ['id'=>0];
        }

        $field = '';
        $product = GoodsModel::getProductDetail($goodsId,$field);

        $product['thumb'] = self::prefixDomain($product['thumb']);
        $product['collective'] = GoodsCollectiveModel::getCollectiveByGid($product['id']);

        //判断是否是团购
        if($product['is_collective'] == 0 ){
            $row['errmsg'] = '非法请求';
            $row['errno']  = 10001;

            return  $row;
        }

        //运费
        $express = Express::getDetail($product['eid'],'cost,company_name,id');
        $goodsPrice = $product['collective']['goods_price'] * $num;
        //最终价格   商品价格 + 运费
        $actualPrice = $goodsPrice + ($express['cost']+0);

        $row['data'] = [
            'checkedAddress' => $address,//收货地址
            'goodsPrice' => $goodsPrice,//收货地址
            'freightPrice'   => $express['cost'],//运费
            'actualPrice'    => $actualPrice,//最后的价格
            'product'        => $product,    //商品信息
            'shop'           => Shop::getShopInfoById($product['s_id'],'shop_name')    //商品信息
        ];
        return $row;
    }

    /**
     * 团购订单
     * @return array
     */
    public function orderList(){
        $param['order_type'] = 1;
        $param['type'] = $this->request->param('types','9999');
        //查询订单信息
        $data = Order::getSummaryByUser($param,$this->uid);
        //查询订单对应的团购信息
        foreach ($data as &$v){
            $v['collective'] = UserCollective::getInfo(['order_id'=>$v['id']]);

            $v['collective']['order_status_text'] = config('collective.status')[$v['collective']['status']];
        }
        if( empty($data) ){
            $row = [
                'errno' => 1,
                'errmsg' => '暂无数据',
                'data' => []
            ];
        }else{
            $row = [
                'errno' => 0,
                'errmsg' => '查询成功',
                'data' => $data
            ];
        }

        return $row;
    }

    /**
     * 获取用拼团信息
     * @return array
     */
    public function detailByID(){
        //查询当前用户的开团信息
        $param['collective_id'] = $this->request->param('id');
        $collectiveInfo = UserCollective::getInfo($param);

        //查询当前用户开团信息中的商品信息
        $oParam['p.order_id']= $collectiveInfo['order_id'];
        $goodsInfo = Order::getGoodsInfoByOrderId($oParam);
        //查询开团规则
        $collectiveRule = GoodsCollectiveModel::getCollectiveByGid($goodsInfo['goods_id']);
        //多少人开团
        $goodsInfo['user_number'] = $collectiveRule['user_number'];

        if( empty($collectiveRule) || $collectiveRule['status'] == 0){
            $row = [
                'errno' => 1,
                'errmsg' => '开团活动已结束',
                'data' => []
            ];
        }

        //查询当前开团订单的信息
        $cParam['collective_no'] = $collectiveInfo['collective_no'];
        $cParam['status'] = ['<>','3'];
        $cField = 'u_name,u_portrait';
        $cOrderBy = 'identity desc';
        $collectiveList = UserCollective::getList($cParam,$cField,$cOrderBy);

        //团购到期时间
        $limitTime = $collectiveInfo['add_time']+$collectiveInfo['limit_time'];
        $limit_time_ms  = explode('-',date('Y-m-d-H-i-s',$limitTime));

        //剩余多少人
        $surplus = $collectiveRule['user_number'] - count($collectiveList);
        for ($i=0;$i<$surplus;$i++){
            array_push($collectiveList,['u_portrait' => 0]);
        }

        //是否是团长
        $in_group = $collectiveInfo['identity']==1 && $collectiveInfo['uid'] == $this->uid?true:false;

        //更多团购信息
        $goodsList  = GoodsCollectiveModel::getList(1,10);
        $goodsList['data'] = self::prefixDomainToArray('thumb',$goodsList['data']);

        $row = [
            'errno' => 0,
            'errmsg' => '查询成功',
            'data'=>[
                'surplus' => $surplus,
                'goodsList' => $goodsList,
                'inGroup' => $in_group,
                'goodsInfo' => $goodsInfo,
                'collectiveRule' => $collectiveRule,
                'collectiveInfo' => $collectiveInfo,
                'collectiveList' => $collectiveList,
                'limit_time_ms' => $limit_time_ms,
                'cid' => $param['collective_id'],
            ],
        ];

        return $row;
    }

    /**
     * 定时任务检测拼团情况
     */
    public function checkCollectiveStatus(){
        $orderList = UserCollective::getNoOnLine();
        foreach ($orderList as $item){
            $openid = User::getInfoById($item['buyer_id'])['openid'];
            $pay= new Pay($item['id'],'xWeChat');
            $pay->refund($openid);
        }
    }
}