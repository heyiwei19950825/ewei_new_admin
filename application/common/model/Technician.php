<?php
/**
 * 技师管理
 * User: HeYiwei
 * Date: 2018/5/30
 * Time: 11:43
 */

namespace app\common\model;


use think\Db;
use think\Model;

class Technician extends  Model
{
    public function  getList($params){
        $projectList = Db::name('technician')->field('name,c_id,id,price')->where('')->select();

        return $projectList;
    }

    public function  getInfo($params){
        $projectList = Db::name('technician')->field('name,c_id,id,price')->where(['id'=>$params['id']])->find();

        return $projectList;
    }
}