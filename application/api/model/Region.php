<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/25
 * Time: 2:25
 */

namespace app\api\model;


class Region extends BaseModel
{
    /**
     * 获取城市列表
     * @param $id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getList( $id ){
        $row = self::where(
            [
                'parent_id' => $id
            ]
        )->select();

        return $row;
    }

    /**
     * 根据ID获取名称
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function getRegionName( $id ){
        $row = self::field(
            'name'
        )->where(
            [
                'id' => $id
            ]
        )->find();

        return $row['name'];
    }
}