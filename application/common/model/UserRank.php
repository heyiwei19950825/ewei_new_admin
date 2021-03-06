<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/5/30
 * Time: 14:42
 */

namespace app\common\model;


use think\Db;
use think\Model;

class UserRank extends Model
{
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 获取商家用户的级别分类ID
     * @param $map
     * @return array
     */
    static function getRankIds($map){
        //查询对应的会员等级
        $row =Db::name('user_rank')->where([
            's_id' =>$map['s_id']
        ])->field('id')->select();
        $ids = [];
        foreach ($row as $k=>$v) {
            $ids[] = $v['id'];
        }

        return $ids;
    }

    /**
     * 获取会员等级详情
     * @param $params
     * @param string $field
     * @return array|false|\PDOStatement|string|\think\Collection|Model
     */
    public function getRankInfo($params,$field=''){
        if( empty($params['id']) ){
            return false;
        }
        if( is_array($params['id'])){
            $userRank = Db::name('user_rank')->field($field)->where($params)->select();
        }else{
            $userRank = Db::name('user_rank')->field($field)->where($params)->find();
        }

        return $userRank;
    }
}