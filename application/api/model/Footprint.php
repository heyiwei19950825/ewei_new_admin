<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/28
 * Time: 22:07
 */

namespace app\api\model;
use think\Request;
use think\Cache;
use think\Db;

class Footprint extends BaseModel
{
    public static function getList($uid){
        $nowTime = date('Y-m-d',time());
        $data =  Db::name('footprint')->where([
            'user_id'=>$uid,
        ])->order('add_time desc')->select();
        $datas = [];
        foreach ($data as $item) {
            if( date('Y-m-d',time($item['add_time'])) == $nowTime ){
                $item['add_time'] = '今天';
                $datas[0][] = $item;
            }else{
                $item['add_time'] = '过去';
                $datas[1] = $item;
            }
        }

        return $datas;
    }

    /**
     * 添加用户浏览记录
     * @param $goodsInfo
     */
    public static function addFootprint( $goodsInfo ){

        $token = Request::instance()
            ->header('Ewei-Token');
        $vars = Cache::get($token);

        if( !empty($token) && !empty($vars)){
            $vars = json_decode($vars,true);
            $uid = $vars['uid'];
            $sql = "select id from ewei_footprint where goods_id={$goodsInfo['id']} AND user_id = ".$uid;
            $row = Db::name('footprint')->query($sql);
            if( empty($row) ){
                $data = [
                    'user_id' => $uid,
                    'name' => $goodsInfo['name'],
                    'list_pic_url' => $goodsInfo['thumb'],
                    'retail_price' => $goodsInfo['sp_price'],
                    'goods_brief' => $goodsInfo['prefix_title'],
                    'add_time' => time(),
                    'goods_id' => $goodsInfo['id'],
                ];
                Db::name('footprint')->insert($data);
            }else{
                $data = [
                    'user_id' => $uid,
                    'name' => $goodsInfo['name'],
                    'list_pic_url' => $goodsInfo['thumb'],
                    'retail_price' => $goodsInfo['sp_price'],
                    'goods_brief' => $goodsInfo['prefix_title'],
                    'add_time' => time(),
                    'goods_id' => $goodsInfo['id'],
                ];
                Db::name('footprint')->where([
                    'id'=>$row[0]['id']
                ])->update($data);
            }
        }


    }
}