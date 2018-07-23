<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/7/10
 * Time: 21:49
 */

namespace app\common\model;

use think\Db;
use think\Model;

class TechnicianPerformance extends Model
{

    /**
     * 获取提成列表
     * @param $params
     * @param $field
     * @return $this
     */
    static public function getPerformanceList($params,$field){
        $map['s.time'] = [
                ['egt',$params['start']],
                ['elt',$params['end']],
                'AND'
            ];

        $map['s.shop_id'] = $params['s_id'];
        $row =  Db::name('technician_performance')->alias('s')
            ->join('user u','u.id = s.u_id','LEFT')
            ->where($map)
            ->field($field)->select();
        return $row;
    }
}