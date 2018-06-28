<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/5/30
 * Time: 13:43
 */

namespace app\common\model;


use think\Model;

class Book extends Model
{


    public function getBookList( $type = 'all'){
        if( $type == 'all' ){
            //查询技师
            $technician_list = self::alias('b')
                ->join('form f','f.id = b.from_id','LEFT')
                ->join('technician t','t.id = b.project_id','LEFT')
                ->where(['b.type' => 1])
                ->select();
            //查询服务
            $server_list = self::alias('b')
                ->join('form f','f.id = b.from_id','LEFT')
                ->join('server_project s','s.id = b.project_id','LEFT')
                ->where(['b.type' => 2])->select();
        }
        $book_list = array_merge($technician_list,$server_list);
        return $book_list;

    }
}