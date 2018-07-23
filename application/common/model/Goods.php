<?php
namespace app\common\model;

use think\Model;
use think\Session;
use think\Db;

class Goods extends Model
{
    /**
     * 查询商品列表信息
     * @param array $map
     * @param bool $paginate
     * @param int $page
     * @param int $size
     * @param string $field
     * @param string $group
     * @return mixed
     */
    public function getGoodsList( $map = array(), $paginate = true,$page = 1, $size = 15,$field = '',$group ='id' ){
        $map['status']  = ['=',1];
        $goodsDb = Db::name('goods');
        $goods_list  = $goodsDb->field($field)->where($map)->order(['publish_time' => 'DESC'])->group($group);
        if($paginate){
            $row = $goods_list->paginate($size, false, ['page' => $page]);
        }else{
            $row = $goods_list->select();
        }
        return $row;
    }



    /**
     * 通过条件查询商品信息
     * @param $id     int     商品ID号
     * @param $field  string  查询的商品表字段
     **/
    public function getGoodsInfo($id, $field){
        //关键字查询
        $param = [];
        //ID单数据查询
        $article_info  = $this->field($field)->where(array('id'=>$id))->find();

        return $article_info;
    }

    /**
     * 获取指定时间段的商品发布数量
     * @param  [type] $time [description]
     * @return [type]       [description]
     */
    public static function getCount($time = 'all'){
        $where = ' status=1 ';

        if( $time != 'all' ){
            $where .=  ' AND DATE_SUB(CURDATE(), INTERVAL'.$time.') <= DATE(create_time)';
        }

        $sql =  'SELECT COUNT(id) as number FROM ewei_goods WHERE '.$where;

        $row = Db::query($sql);
        return $row[0]['number'];
    }

    public static function goodsPerformance($id,$sId=0){
        $goods = Db::name('goods')->where(['id'=>$id,'s_id'=>$sId])->field('sp_price,royalty,royalty_type,royalty_template')->find();

        if( $goods['royalty'] == 0 ){
            $template = Db::name('royalties_template')->where(['id'=>$goods['royalty_template'],'s_id'=>$sId])->find();
            if( $template['type'] == 1 ){
                return $template['royalties'];
            }else{
                return ($goods['sp_price'] * $template['royalties']/100);
            }
        }else{
            if($goods['royalty_type'] == 1 ){
                return $goods['royalty'];
            }else{
                return ($goods['sp_price'] * $goods['royalty']/100);
            }
        }
    }
}