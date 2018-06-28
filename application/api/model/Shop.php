<?php
/**
 * 获取商家信息
 * User: heyiw
 * Date: 2018/2/3
 * Time: 17:00
 */

namespace app\api\model;

use think\Model;
use think\Db;


class Shop extends BaseModel
{
    /**
     * 通过商家ID获取商家信息
     * @param int $id
     * @param string $field
     * @return array|false|mixed|\PDOStatement|string|Model
     */
    public static function getShopInfoById( $id=-1,$field = ''){
        $row = Db::name('shop')->where([
            'id'=>$id,
            'shop_status' => 1,
        ])->field($field)->find();

        return $row;
    }
}