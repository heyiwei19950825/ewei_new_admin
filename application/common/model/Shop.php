<?php
namespace app\common\model;

use think\Db;
use think\Model;

class Shop extends Model
{

    public static function getShopUserListById( $ids,$condition=[],$field = '' ){
        if( is_array($ids)){
            $condition['s.u_id'] = ['in',$ids];
        }else{
            $condition['s.u_id'] = $ids;
        }
        $list = Db::name('shop')->alias('s')->join('admin_user u','s.u_id = u.id','RIGHT')->field($field)->where($condition);

        if( is_array($ids)) {
            $row = $list->select();
        }else {
            $row = $list->find();
        }
        return $row;
    }
}