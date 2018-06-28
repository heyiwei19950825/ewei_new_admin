<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/4
 * Time: 13:59
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Goods;
use app\api\service\Token;
use app\api\validate\ShopEnter;
use think\Db;
class Shop extends BaseController
{
    protected $uid;
    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
    }


    /**
     * 商家入驻
     */
    public function Enter(){
        $shopRow = Db::name('shop_enter')->where(['u_id'=>$this->uid])->field('shop_name,user_name,enter_time,status,description,reason,status')->find();
        if($this->request->isPost()){
            if($shopRow){
                $this->error('您已申请过啦');
            }
            //商家分类
            $shopGroup = Db::name('shop_group')->field('id,group_name')->order('group_sort desc')->select();
            (new ShopEnter())->goCheck();
            $params = $this->request->param();
            if($params['category'] == 0 ){
                $this->error('请选择店铺分组');
            }
            $params['c_id'] = $shopGroup[ $params['category'] - 1 ]['id'];
            $params['enter_time'] = time();
            $params['u_id'] = $this->uid;
            unset($params['category']);
            unset($params['version']);

            $row = Db::name('shop_enter')->insert($params);
            if( $row ){
                $this->success('申请成功');
            }else{
                $this->error('申请失败');
            }
        }else{
            if($shopRow){
                $shopNote =['正在审核','申请不通过','恭喜您成为入驻商家，联系客服获取您的账号密码'];
                $shopRow['enter_time'] = date('Y-m-d H:i:s',$shopRow['enter_time']);
                $shopRow['status'] = $shopNote[$shopRow['status']];
            }else{
                $shopRow = [];
            }
            return [
                'errno' => 1,
                'data' => $shopRow,
                'msg'  => '查询成功'
            ];
        }
    }

    /**
     * 获取商城帮助信息
     */
    public function helper(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
        $site_config = unserialize($site_config['value']);
        $row['data'] = $site_config['helper'];

        return $row;
    }

    /**
     * 获取商城列表
     */
    public function getList(){

        $page           = $this->request->param('page',1);
        $keyword        = $this->request->param('keyword','');
        $latitude       = $this->request->param('latitude',113.14952);
        $longitude      = $this->request->param('longitude',40.00543);


        $page_size  = 10;
        $start_row  = $page_size * ($page - 1);
        $map['shop_status'] = ['=',1];

        if( !empty($keyword) ){
            $map['shop_name'] = ['like','%'.$keyword.'%'];
        }

        $count = Db::name('shop')->where($map)->count();

        $list = Db::name('shop')->where($map)
            ->field('id,shop_type,shop_recommend,shop_address,shop_phone,shop_name,latitude_longitude,shop_logo')
            ->order('shop_sort desc')
            ->limit($start_row . "," . $page_size)
            ->select()->toArray();
        $list = $this->prefixDomainToArray('shop_logo',$list);
        if( !empty($list) ){
            foreach ($list as $index => &$v){
                if(!empty($v['latitude_longitude'])){
                    $latitude_longitude  = explode(',', $v['latitude_longitude']);
                    $v['longitude'] = $latitude_longitude[0];
                    $v['latitude']  = $latitude_longitude[1];
                }else{
                    $v['longitude'] = 0;
                    $v['latitude']  = 0;
                }

                unset($v['latitude_longitude']);
                $list[$index]['distance'] = -1;
                if ($v['longitude'] && $latitude) {
                    $from = [$latitude, $longitude];
                    $to = [$v['longitude'], $v['latitude']];
                    $list[$index]['distance'] = $this->get_distance($from, $to, false, 2);
                }
                $distance[] = $list[$index]['distance'];
            }
            array_multisort($distance, SORT_ASC, $list);
            foreach ($list as $k=> &$item){
                $item['distance'] =  $this->distance($item['distance']);
            }
        }

        $data['list'] = $list;
        if ($count % $page_size == 0) {
            $data['page_count'] = $count / $page_size;
            $data['row_count'] = $count;
        } else {
            $data['page_count'] = (int) ($count / $page_size) + 1;
            $data['row_count'] = $count;
        }
        return [
            'code' => 0,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * 获取商铺信息
     */
    public function shopInfo(){
        //商家信息
        $id = $this->request->param('id',0);
        if($id == 0 ){
            return [
                'code' => 1,
                'msg' => '',
            ];
        }
        $info = Db::name('shop')->where(['u_id'=>$id,'shop_status'=>1])->field('u_id,shop_name,shop_logo,shop_address,brief,shop_type,shop_phone,content,live_store_address')->find();
        $info['book'] =Db::name('book_config')->field('switch')->where([
            's_id' => $id
        ])->find();
        $info['shop_logo'] = $this->prefixDomain($info['shop_logo']);
        $banner = Db::name('slide')
            ->field('image,link,description')
            ->where([
                's_id'=>$id
            ])->order('sort desc')->select();
        $info['shop_banner']  = self::prefixDomainToArray('image',$banner);

        Db::name('statistics')->where(['s_id'=>$id])->setInc('visit');
        return [
            'code' => 0,
            'msg' => '',
            'data' => $info
        ];
    }

    /***
     * 获取商铺分类
     * @return array
     */
    public function getShopCategory(){
        $id = $this->request->param('id',0);
        //获取店铺分类
        $category = Db::name('category')->where([
            's_id'=>$id,
            'is_hide' =>0,
            'pid'=>0
        ])->field('id,name,icon')->order('sort desc')->select()->toArray();

        return [
            'code' => 0,
            'msg' => '',
            'data' => $category
        ];
    }
    /**
     * 根据分类获取商品列表
     */
    public function getGoodsByCategory(){
        $id = $this->request->param('id',0);
        //获取店铺分类
        $category = Db::name('category')->where([
            's_id'=>$id,
            'is_hide' =>0,
            'pid'=>0
        ])->field('id,name,icon')->order('sort desc')->select()->toArray();
        //获取分类下的所有商品
        foreach( $category as $k => $v){
            $goodList = Goods::getProductsByCategoryID($v['id'],false,'id,name,sp_price,sp_market,is_recommend,is_hot,is_collective,is_integral,is_collective,thumb,prefix_title','','sort','desc',1,999,'default',['s_id'=>$id])->toArray();
            $goodList = $this->prefixDomainToArray('thumb',$goodList);
            $category[$k]['goods_list'] = $goodList;
        }
        $data['category'] = $category;

        return [
            'code' => 0,
            'msg' => '',
            'data' => $data
        ];
    }
    public function getGoodsByCategoryId(){
        $now = date('Y-m-d H:i:s',time());
        $id = $this->request->param('id',0);
        $cid = $this->request->param('cid',0);

        if($cid != 0 ){
            $goodsList['data'] = Goods::getProductsByCategoryID($cid,false,'id,name,sp_price,sp_market,is_recommend,is_hot,is_collective,is_integral,is_collective,thumb,prefix_title','','sort','desc',1,999,'default',['s_id'=>$id])->toArray();
        }else{
            $map['s_id'] = $id;
            $map['btime']           = ['<=',$now];
            $map['etime']           = ['>=',$now];
            $map['status']          = ['=',1];
            $map['sp_inventory']    = ['>',1];
            $field = 'id,name,sp_price,sp_market,is_recommend,is_hot,is_collective,is_integral,is_collective,thumb,prefix_title';
            $goodsList = Goods::getAll($map,$field,'sort desc',false);
        }

        $goodsList['data'] = $this->prefixDomainToArray('thumb',$goodsList['data']);

        return [
            'code' => 0,
            'msg' => '',
            'data' => $goodsList
        ];

    }

    public function getOneGoods(){
        $id = $this->request->param('id',0);

        $field = 'id,name,sp_price,sp_market,is_recommend,is_hot,is_collective,is_integral,is_collective,thumb,prefix_title';
        $goodsInfo = Goods::getProductDetail($id,$field);
        return [
            'code' => 0,
            'msg' => '',
            'data' => $goodsInfo
        ];
    }


    /**
     * 根据起点坐标和终点坐标测距离
     * @param  [array]   $from  [起点坐标(经纬度),例如:array(118.012951,36.810024)]
     * @param  [array]   $to    [终点坐标(经纬度)]
     * @param  [bool]    $km        是否以公里为单位 false:米 true:公里(千米)
     * @param  [int]     $decimal   精度 保留小数位数
     * @return [string]  距离数值
     */
    function get_distance($from, $to, $km = true, $decimal = 2)
    {
        sort($from);
        sort($to);
        $EARTH_RADIUS = 6370.996; // 地球半径系数

        $distance = $EARTH_RADIUS * 2 * asin(sqrt(pow(sin(($from[0] * pi() / 180 - $to[0] * pi() / 180) / 2), 2) + cos($from[0] * pi() / 180) * cos($to[0] * pi() / 180) * pow(sin(($from[1] * pi() / 180 - $to[1] * pi() / 180) / 2), 2))) * 1000;

        if ($km) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }



    private static function distance($distance)
    {
        if ($distance == -1) {
            return -1;
        }
        if ($distance > 1000) {
            $distance = round($distance / 1000, 2) . 'km';
        } else {
            $distance .= 'm';
        }
        return $distance;
    }

    /**
     * 获取商家分组
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function shopGroup(){
        $data = [];
        $row = Db::name('shop_group')->field('group_name')->order('group_sort desc')->select();
        foreach ($row as $v){
            $data[] = $v['group_name'];
        }
        $data = array_merge(['请选择店铺类型'],$data);
        return [
            'code' => 0,
            'msg' => '',
            'data' => $data
        ];
    }

    /**
     * 获取会员列表信息
     */
    public function membersList(){
        $sid = $this->request->param('s_id');
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $list = Db::name('user_rank')->where([
            's_id' => $sid,
            'status' => 1
        ])->order('sort desc')->select();

        if(empty($list) ){
            $row['errno']   = 1;
            $row['errmsg']  = '暂无内容';
        }else{
            $row['errno']   = 0;
            $row['data']   = $list;
            $row['errmsg']  = '获取成功';
        }
        return $row;

    }

}