<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/28
 * Time: 22:46
 */

namespace app\api\model;

use think\Db;
use think\Request;
use think\Cache;

class Collect extends BaseModel
{
    /**
     * 获取收藏列表
     * @param $uid
     * @return array
     */
    public static function getLit( $uid ){
        $row = Db::name('collect')->alias('c')
            ->join('goods g','g.id = c.goods_id','LEFT')
            ->where([
                'user_id'=>$uid
             ])->field('g.id,g.name,g.prefix_title,g.sp_price,g.thumb')->select();

        if( !empty($row) ){
            $row = $row->toArray();
        }else{
            return [];
        }
        return $row;
    }

    /**
     * 添加或删除收藏
     * @param $uid
     * @return array
     */
    public static function addOrDelete( $uid = -1 ,$gid = -1,$type = 0 ){
        $row = Db::name('collect')->where(['user_id'=>$uid,'goods_id'=>$gid])->find();
        if( empty($row) ){
            $data = [
                'user_id' => $uid,
                'goods_id' => $gid,
                'add_time' => time()
            ];
            Db::name('collect')->insert($data);
            $row = ['type'=>'add'];
        }else{
            Db::name('collect')->where([
                'user_id'=>$uid,
                'goods_id' => $gid
            ])->delete();
            $row = ['type'=>'delete'];
        }
        return $row;
    }

    public static function checkCollectUse( $gid = -1 ){
        $token = Request::instance()
            ->header('Ewei-Token');
        $vars = Cache::get($token);
        $row = 0;
        if( !empty($vars) && !empty($token)){
            $vars = json_decode($vars,true);
            $uid = $vars['uid'];

            $data = Db::name('collect')->where(['user_id'=>$uid,'goods_id'=>$gid])->find();
            if( !empty($data) ){
                $row = 1;
            }
        }

        return $row;
    }
}