<?php
/**
 * 运费
 * User: heyiw
 * Date: 2018/1/28
 * Time: 19:52
 */

namespace app\api\model;

use think\Db;

class Express extends BaseModel
{
    /**
     * 查询运费模板信息
     * @param int $id
     * @param string $field
     * @return array
     */
    public static function getDetail( $id = -1 ,$field = '' ){
        $row = Db::name('express_company')->field($field)->where(['id'=> $id])->find();

        if( $id == -1 || empty($row)){
            return [];
        }

        return $row;
    }
}