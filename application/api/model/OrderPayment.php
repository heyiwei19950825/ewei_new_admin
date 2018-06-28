<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/5/19
 * Time: 12:13
 */

namespace app\api\model;


use think\Db;

class OrderPayment
{
    public static function createOrderPayment($data){

        $id = Db::name('order_payment')->insertGetId($data);
        return $id;
    }

    public static function get($id)
    {
        $row = Db::name('order_payment')->find(['id'=>$id]);
        return $row;
    }
}