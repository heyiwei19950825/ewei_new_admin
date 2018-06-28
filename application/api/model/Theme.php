<?php
/**
 * Created by Ewei..
 * User: Ewei.
 * Date: 2017/2/16
 * Time: 1:59
 */

namespace app\api\model;

class Theme extends BaseModel
{
    protected $table = 'ewei_nav';
    public static function getAllThemeList($field,$limit)
    {
        $row = self::where(['status'=>1])
            ->field($field)
            ->limit($limit)
            ->order('sort desc')
            ->select();

        return $row;
    }
}