<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/5/4
 * Time: 22:36
 */

namespace app\api\service;


use app\api\model\Coupon;

class CouponServer
{
    function __construct()
    {
    }

    /**
     * 优惠券检测并返回优惠数据
     * @param int $uid
     * @param int $id
     * @return array
     */
    public static function spanCoupon( $uid = 0,$id = 0 ){
        $checkedCoupon = Coupon::getInfoById($uid,$id);

        return $checkedCoupon;
    }
}