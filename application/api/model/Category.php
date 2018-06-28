<?php

namespace app\api\model;

use think\Model;

class Category extends BaseModel
{
    public function products()
    {
        return $this->hasMany('Goods', 'cid', 'id');
    }

    public function img()
    {
        return $this->belongsTo('Image', 'topic_img_id', 'id');
    }

    public static function getCategories($ids)
    {
        $categories = self::with('products')
            ->with('products.img')
            ->select($ids);
        return $categories;
    }
    
    public static function getCategory($id,$field)
    {

        $category = self::field($field)
            ->where(['is_hide'=>0])
            ->find($id);
        return $category;
    }

    /**
     * 获取顶级分类
     * @param $field string
     * @return object FilterCategory
     */
    public static function filterCategory( $field='' ){
        $filterCategory = self::where(['pid'=>0,'is_hide'=>0,'s_id'=>1])
            ->field($field)
            ->order('sort asc')
            ->select();
        return $filterCategory;
    }

    /**
     * @param $id
     * @param string $field
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function childCategory( $id,$field='' ){
        $map['pid'] = $id;
        $map['is_hide'] = 0;
        $map['s_id']  = 1;
        $filterCategory = self::where($map)
            ->field($field)
            ->order('sort asc')
            ->select();

        return $filterCategory;
    }
}
