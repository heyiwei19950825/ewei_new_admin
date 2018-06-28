<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/4/11
 * Time: 10:04
 */

namespace app\api\model;


class ArticleCategory extends BaseModel
{
    public static function getShowCategory( $size = 3 ){
            $row = self::where([
                'pid' => 0,
                's_id' => 1,
                'status' => 1,
                'show_index'=>1
            ])
                ->field('id,icon,name,description')
                ->order('sort desc')
                ->limit(0,$size)
                ->select();
        if( $row ){
            $row = $row->toArray();
        }else{
            $row = [];
        }
        return $row;
    }
}