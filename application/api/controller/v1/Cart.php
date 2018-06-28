<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/22
 * Time: 22:26
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Shop;
use app\api\model\UserAddress;
use app\api\service\Cart as CartService;
use app\api\service\Token;
use app\api\validate\Cart as CartValidate;
use app\api\model\Cart as CartModel;
use app\api\model\Region;
use app\api\model\Coupon;
use app\api\model\Goods;
use app\api\model\User;
use app\api\model\Express;
use think\Db;


class Cart extends BaseController
{
    public $uid = '';
    public $is_vip = '';


    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
        //判断是否是VIP用户
        $user = User::get(['id'=>$this->uid]);

        if( $user->is_vip == 2 ){
            $this->is_vip = true;
        }
    }

    public function index(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        //获取购物车商品列表
        $data  = CartModel::getCartAll($this->uid );
        //检测库存和状态  如果 库存不足或状态过期则标记为过期
        foreach ($data as $v){
            $goods = Goods::getProductDetail($v['goods_id'],'id,name,thumb,sp_price,prefix_title,sp_o_price,sp_market,sp_vip_price,sp_o_price');
            if( $goods == NULL){
                $params['uid'] = $this->uid;
                $params['productIds'] = $v['goods_id'];
                CartModel::deleteCart($params);
            }else{
                //修改购物车中商品信息
                $upData['goods_picture'] =  self::prefixDomain($goods['thumb']);
                $upData['goods_name'] = $goods['name'];
                $upData['price'] = $goods['sp_price'];
                $upData['vip_price'] = $this->is_vip&&$goods['sp_vip_price']!=0&&$goods['sp_vip_price']!='0.00'?$goods['sp_vip_price']:0;
                $upData['cost_price'] = $goods['sp_o_price'];
                CartModel::update($upData,['id'=>$v['id']]);
            }
        }
        //统计购物车商品数据
        $count = CartModel::cartCount($this->uid );
        $row['data'] = [
            'cartList' => CartModel::getCartAll($this->uid ),
            'cartTotal'=>[
                'goodsCount' => $count['goodsCount'],
                'goodsAmount' => $count['goodsAmount'],
                'checkedGoodsAmount' => $count['checkedGoodsAmount'],
                'checkedGoodsCount' => $count['checkedGoodsCount'],
            ]
        ];

        return $row;
    }

    /**
     * 修改购物车
     * @return array
     */
    public function updateCarts(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $params = $this->request->param();
        (new CartValidate())->goCheck();

        $params['uid'] = $this->uid;
        $row = (new CartService())->checkGoods($params,'cart');
        //检测库存和状态
        if($row['errno']!= 0){
            return $row;
        }
        $data = CartModel::updateNumber( $params);
        if( $data != 1 ){
            $row['errmsg'] = '网络异常';
            $row['errno']  = 20001;
        }
        return $row;

    }
    /**
     * 全选购物车
     * @return array
     */
    public function checked(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $params = $this->request->param();
        (new CartValidate())->goCheck();

        $params['uid'] = $this->uid;

        $data = CartModel::updateChecked($params);
        //获取购物车商品列表
        $cartData  = CartModel::getCartAll($this->uid );
        //统计购物车商品数据
        $cartCount = CartModel::cartCount($this->uid );

        $row['data'] = [
            'cartList' => $cartData,
            'cartTotal'=>[
                'goodsCount' => $cartCount['goodsCount'],
                'goodsAmount' => $cartCount['goodsAmount'],
                'checkedGoodsAmount' => $cartCount['checkedGoodsAmount'],
                'checkedGoodsCount' => $cartCount['checkedGoodsCount'],
            ]
        ];

        if( $data != 1 ){
            $row['errmsg'] = '网络异常';
            $row['errno']  = 20001;
        }
        return $row;
    }

    /**
     * 删除购物车
     * @return array
     */
    public function deleteCarts(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $params = $this->request->param();
        (new CartValidate())->goCheck();


        $params['uid'] = $this->uid;
        $data = CartModel::deleteCart($params);

        //获取购物车商品列表
        $cartData  = CartModel::getCartAll($this->uid );
        //统计购物车商品数据
        $cartCount = CartModel::cartCount($this->uid );

        $row['data'] = [
            'cartList' => $cartData,
            'cartTotal'=>[
                'goodsCount' => $cartCount['goodsCount'],
                'goodsAmount' => $cartCount['goodsAmount'],
                'checkedGoodsAmount' => $cartCount['checkedGoodsAmount'],
                'checkedGoodsCount' => $cartCount['checkedGoodsCount'],
            ]
        ];


        if( $data != 1 ){
            $row['errmsg'] = '网络异常';
            $row['errno']  = 20001;
        }
        return $row;
    }


    /**
     *  添加购物车 信息
     */
    public function add(){
        $params = $this->request->param();
        (new CartValidate())->goCheck();
        $cartModel = new CartModel();
        $cart = new CartService();

        //判断是否是购物车数组添加
        if( isset($params['cartArray'])){//首页直接添加购物车 商铺直接添加
            //检测已添加商品
            $data  = CartModel::getCartAll($this->uid );
            if( !empty($data)){
                $delIds = '';
                $upDateIds = '';
                foreach ( $data as $k=>$v){
                    foreach ($params['cartArray'] as $ck=>$cv){
                        if( $ck == $v['goods_id']){
                            $delIds .= $v['id'].',';
                        }
                    }
                    $upDateIds .= $v['id'].',';
                }
                //修改所有的购物车商品选中状态

                $updateParams['uid']        = $this->uid;
                $updateParams['isChecked']  = 0;
                $updateParams['productIds'] = trim($upDateIds,',');
                $data = CartModel::updateChecked($updateParams);

                //删除已存在的购物车商品
                $delParams['uid'] = $this->uid;
                $delParams['productIds'] = trim($delIds,',');
                $row = CartModel::deleteCart($delParams);
            }

            foreach ( $params['cartArray'] as $k=>$v){
                $params = [];
                $params['goodsId'] = $k;
                $params['number']   = $v;
                $params['uid']   = $this->uid;

                //检测是否已经添加到购物车 如果没有添加则创建   如果添加则修改购物车信息
                $cartInfo = $cartModel->where([
                    'uid' => $this->uid,
                    'goods_id'=>$k
                ])->field('id')->find();
                $params['id']   =$cartInfo['id'];
                //检测是否存在
                if( empty($cartInfo) ){

                    $data = $cart->add($this->uid , $params,$this->is_vip);
                    //判断返回code是否为0不为0则返回错误信息
                    if( isset($data['errno']) && $data['errno'] != 0 ){
                        return $data;
                    }
                }else{//修改购物车数量
                    $row = (new CartService())->checkGoods($params,'cart');
                    //检测库存和状态
                    if($row['errno']!= 0){
                        return $row;
                    }
                    CartModel::updateNumber( $params);
                }
            }
            $row = [ 'errno' => '0',
                'errmsg' => '添加成功~'];
        }else{
            $row = $cart->add($this->uid , $params,$this->is_vip);
        }
        $row['data'] = [
            'openAttr' => false,
            'cartTotal'=>[
                'goodsCount' => CartModel::cartCount($this->uid )['goodsCount'],
            ]
        ];
        return $row;
    }

    /**
     * 统计购物车中商品数量
     * @return array
     */
    public function goodsCount(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $row['data'] = [
            'cartTotal'=>[
                'goodsCount' => CartModel::cartCount($this->uid)['goodsCount'],
            ]
        ];

        return $row;
    }

    /**
     * 检查商品状态
     * @return array
     */
    public function checkGoodsStatus(){
        $params = $this->request->param();
        $row = ( new CartService())->checkGoods($params,'cart');
        if( !empty($row['data']) ){
            unset($row['data']);
        }
        return $row;
    }
    /**
     * 确认购物车
     * @return array
     */
    public function checkoutCarts(){
        //初始化数据
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $goodsTotalPrice = $orderTotalPrice = $actualPrice= $freightPrice = $couponPrice = $couponId = $rankDiscount = $userCouponList = $couponPrice = $vipOrderTotalPrice = 0;
        ;
        $freight = $goodsInfo = $checkedCoupon = [];
        $couponId = $this->request->param('couponId');//使用的优惠券ID
        $addressId = $this->request->param('addressId');//收货地址
        $goodsId = $this->request->param('goodsId');//商品ID
        $num = $this->request->param('num');//商品ID

        $userInfo = Db::name('user')->alias('u')->join('user_rank r','u.rank_id = r.rank_id','LEFT')->where(['u.id'=>$this->uid])->find();//用户信息

        //数据验证
        (new CartValidate())->goCheck();
        //购物车商品列表
        if($goodsId == 0 ){
            $cartList = CartModel::getCartAll($this->uid,true);

            foreach ($cartList as $v) {
                if($v['vip_price'] != 0 ){
                    $vipOrderTotalPrice= 1;
                }
            }
        }else{
            $field = 'id,sp_price,sp_vip_price,name,thumb,s_id';
            $goods = Goods::getProductDetail($goodsId,$field);
            $goods['num'] = $num;
            $goods['price'] = $goods['sp_price'];
            $goods['vip_price'] = $this->is_vip&&$goods['sp_vip_price']!=0 ?$goods['sp_vip_price']:0;//vip价格
            $vipOrderTotalPrice = $this->is_vip&&$goods['sp_vip_price']!=0 ?1:0;
            $goods['goods_id'] = $goods['id'];
            $goods['goods_name'] = $goods['name'];
            $goods['thumb'] = self::prefixDomain($goods['thumb']);
            $goods['goods_picture'] = $goods['thumb'];
            $goods['shop_id'] = $goods['s_id'];
            $goods['shop_name'] = Shop::getShopInfoById($goods['s_id'])['shop_name'];
            $cartList[] = $goods;
        }

        //收货地址
        $userAddressModel = new UserAddress();
        if( $addressId == 0){
            $addressMap = ['user_id'=>$this->uid,'is_default'=>1];

        }else{
            $addressMap = ['user_id'=>$this->uid,'id'=>$addressId];
        }

        $address = $userAddressModel->where($addressMap)->find();
        if( !empty($address) ){
            $address['province_name'] = Region::getRegionName($address['province_id']);
            $address['city_name'] = Region::getRegionName($address['city_id']);
            $address['district_name'] = Region::getRegionName($address['district_id']);
            $address['full_region'] = $address['province_name'] . $address['city_name'] . $address['district_name'];
        }else{
            $address = ['id'=>0];
        }


        //优惠券 总数
        $couponNumber = Coupon::countCoupon( $this->uid);
        if( $couponId != 0 ){
            //选中的优惠券信息
            $checkedCoupon = Coupon::getInfoById($this->uid,$couponId);
            $couponPrice = $checkedCoupon['money']*100;
        }


        //计算价格
        foreach ($cartList as &$item) {
            //商品总价格
            $goodsTotalPrice += $item['vip_price']!=0?($item['vip_price']*100) * $item['num']:($item['price']*100) * $item['num'];
            if( !array_key_exists($item['eid'],$freight)){
                //运费
                $express = Express::getDetail($item['eid'],'cost,company_name');
                $freight[$item['eid']] = array(
                    'name' => $express['company_name'],
                    'cost'=>$express['cost']*100,
                );
                $item['ecost'] = $express['cost'];
            }
        }



        //按店铺分组
        $shopCartList = [];
        $freightPrice = 0;
        foreach($cartList as $v){
            $shopRow = Db::name('shop')->where(['id'=>$v['shop_id']])->find();
            $shopCartList[$shopRow['shop_name']]['goods'][] = $v;
        }


        foreach ($shopCartList as &$vs){
            $goodsPrice = $freight = 0;
            foreach ($vs['goods'] as $vsc){
                if($userInfo['is_vip'] == 2 ){
                    $goodsPrice += $vsc['vip_price'] == 0 ?($vsc['price'] * 100) * $vsc['num']:($vsc['vip_price'] * 100) * $vsc['num'];
                }else{
                    $goodsPrice += ($vsc['price'] * 100) * $vsc['num'];
                }
                if($freight ==0 ){
                    $freight = $vsc['ecost'];
                }elseif($vsc['ecost'] > $freight){
                    $freight = $vsc['ecost'];
                }
            }
            $vs['price']['goods']  = $goodsPrice/100;
            $vs['price']['freight'] = $freight;
            $freightPrice += $freight*100;
        }


        $cartList= $shopCartList;



        //计算 会员等级获得优惠价格折扣
        if( $userInfo['is_vip'] == 2 ){
            $rankDiscount = $goodsTotalPrice*100 - ($goodsTotalPrice* ($userInfo['rank_discount'])*10);
        }
        //最后商品订单价格   商品总价格 + 运费 - 会员折扣
        $actualPrice = $goodsTotalPrice - ($rankDiscount/100) + $freightPrice - $couponPrice;



        $row['data'] = [
            'checkedAddress'=>$address,//收货地址
            'checkedGoodsList'=> $cartList,//购物车列表
            'checkedCoupon'=>$checkedCoupon,//选中的优惠券ID
            'couponNumber'=>$couponNumber,//优惠券数量
            'rankDiscount' => round($rankDiscount/10000,2),//会员折扣
            'goodsTotalPrice'=>$goodsTotalPrice/100,//商品总价
            'orderTotalPrice'=>$orderTotalPrice,//订单价格
            'actualPrice'=>round($actualPrice/100,2),//最后的价格
            'freightPrice' => round($freightPrice/100,2),//运费
            'couponPrice' => $couponPrice/100,//优惠券价格
            'vipOrderTotalPrice' => $vipOrderTotalPrice,
        ];


        return $row;
    }



}