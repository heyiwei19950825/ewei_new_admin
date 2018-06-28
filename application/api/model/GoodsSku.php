<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/16
 * Time: 1:41
 */

namespace app\api\model;


class GoodsSku extends BaseModel
{
    /**
     * 通过商品ID查询商品规格
     * @param $id
     * @return string
     */
    public static function getSkuByGId( $id ){
        $data = self::where('goods_id','=',$id)->field('sku_id,sku_name,attr_value_items,price,stock,group_id')->order('sku_id asc')->select();

        return $data;
    }
}