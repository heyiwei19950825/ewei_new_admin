<?php
namespace app\common\model;

use think\Db;
use think\Model;

class User extends Model
{
    protected $insert = ['create_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * 获取用户信息
     * @param $params
     * @param string $field
     * @return bool|false|\PDOStatement|string|\think\Collection
     */
    public function getUserInfo($params,$field=''){
        if( empty($params['id']) ){
            return false;
        }
        if( is_array($params['id'])){
            $user = Db::name('user')->field($field)->where($params)->select();
        }else{
            $user = Db::name('user')->field($field)->where($params)->find();
        }


        return $user;
    }
}