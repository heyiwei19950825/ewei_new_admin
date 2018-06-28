<?php
/**
 * 服务项目
 * User: HeYiwei
 * Date: 2018/5/29
 * Time: 21:57
 */

namespace app\common\model;


use think\Db;
use think\Model;

class ServerProject extends Model
{


    public function getList( $params ){
        $projectList = Db::name('server_project')->field('name,c_id,id,price')->where('')->select();
        return $projectList;
    }

    public function  getInfo($params){
        $projectList = Db::name('server_project')->field('name,c_id,id,price')->where(['id'=>$params['id']])->find();
        return $projectList;
    }
}