<?php
/**
 * 城市列表
 * User: heyiw
 * Date: 2018/1/25
 * Time: 2:23
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Region as RegionModel;

class Region extends BaseController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取城市地址
     * @param $parentId
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function regionList( $parentId ){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $row['data'] = RegionModel::getList($parentId);

        return $row;
    }
}