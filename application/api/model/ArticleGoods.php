<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/13
 * Time: 11:14
 */

namespace app\api\model;


use think\Db;

class ArticleGoods extends BaseModel
{
    /**
     * 根据文章ID获取文章下的所有商品信息
     * @param $id
     * @return array
     */
    public static function getGoodsByArticleId( $id ){
        $now = date('Y-m-d H:i:s',time());

        $map['g.btime']   = ['<=',$now];
        $map['g.etime']   = ['>=',$now];
        $map['g.status']  = ['=',1];
        $map['a.article_id']  = ['=',$id];
        $row = Db::name('article_goods')->alias('a')
            ->join('goods g','g.id=a.goods_id','LEFT')
            ->field('g.id,g.name,g.thumb,g.sp_price,g.sp_o_price,g.sp_market')
            ->where($map)->select();
        if( !empty($row) || $row != NULL){
            $row = $row->toArray();
        }
        return $row;
    }
}