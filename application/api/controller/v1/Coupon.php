<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/29
 * Time: 0:38
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Coupon as CouponModel;
use app\api\service\Token;
use app\api\model\Cart as CartModel;


class Coupon extends  BaseController
{
    public $uid = '';

    public function _initialize()
    {
        parent::_initialize();

        if($_SERVER['PATH_INFO'] != '/api/v1/coupon/list'){
            $this->uid = Token::getCurrentUid();
        }
    }

    public function couponList(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        //获取所有的在线优惠券
        $couponList = CouponModel::getCouponList();
        if( $couponList ){
            $row['errmsg'] = '查询成功';
            $row['data'] = $couponList;
        }else{
            $row['errno'] = 1;
            $row['errmsg'] = '没有更多优惠券';
        }
        return $row;
    }

    /**
     * 领取优惠券
     * @return array
     */
    public function userGetCoupon(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $cid = $this->request->param('cid',0);
        $uid = Token::getCurrentUid();
        $data = CouponModel::addUserCoupon( $uid,$cid);
        if( $data === true ){
            $row['errmsg'] = '领取成功';
        }else{
            $row['errno'] = 1;
            $row['errmsg'] = $data;
        }
        return $row;
    }

    /**
     * 获取用户优惠券列表
     * @return array
     */
    public function getUserCouponList(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $type = $this->request->param('types',0);

        $row['data']['data'] = CouponModel::userCoupon( $this->uid,$type);
        $row['data']['types'] = $type;

        return $row;
    }


    /**
     * 获取用户优惠券列表
     * @return array
     */
    public function getUseCouponList(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $type = $this->request->param('types',0);
        //购物车商品ID
        $cartListId = [];
        //购物车商品总价
        $totalPrice = 0;
        //购物车商品列表
        $cartList = CartModel::getCartAll($this->uid,true);
        foreach ($cartList as $item) {
            $totalPrice += ($item['price']*100)*$item['num'];
            $cartListId[] = $item['goods_id'];
        }
        $row['data']['data'] = CouponModel::useCoupon( $this->uid,$cartListId ,$totalPrice/100);

        $row['data']['types'] = $type;
        return $row;
    }
}