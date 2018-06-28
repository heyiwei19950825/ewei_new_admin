<?php

namespace app\api\model;

use think\Model;

class BannerItem extends BaseModel
{

    /**
     * 更具父类bannerID获取banner列表
     * @param $id
     * @return Row
     */
    public static function getBannerList( $id, $filed, $limit){
        $row = self::where( ['cid'=>$id,'status'=>1])
            ->field($filed)
            ->order('sort asc')
            ->limit($limit)
            ->select();

        return $row;
    }
}
