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
    public function  getList($params,$field='name,c_id,id,price'){
        $projectList = Db::name('technician')->field($field)->where($params)->select();

        return $projectList;
    }

    /**
     * 查询员工信息
     * @param $params
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     */
    public function  getInfo($params,$field ='name,c_id,id,price'){
        $projectList = Db::name('technician')->field($field)->where(['id'=>$params['id']])->find();

        return $projectList;
    }
}