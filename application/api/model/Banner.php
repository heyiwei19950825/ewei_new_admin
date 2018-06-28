<?php

namespace app\api\model;

use think\Model;

class Banner extends BaseModel
{
    public function items()
    {
        return $this->hasMany('BannerItem', 'cid', 'id');
    }
    //

    /**
     * @param $id int banner所在位置
     * @return Banner
     */
    public static function getBannerById($id)
    {
        $banner = self::with(['items'])
            ->find($id);
//         $banner = BannerModel::relation('items,items.img')
//             ->find($id);
        return $banner;
    }
}
