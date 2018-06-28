<?php

namespace app\api\model;

use think\Db;

class Goods extends BaseModel
{
    protected $autoWriteTimestamp = 'datetime';
    protected $hidden = [
        'delete_time', 'main_img_id', 'pivot', 'from',
        'create_time', 'update_time'];

    /**
     * 获取某分类下商品
     * @param $categoryId       分类ID
     * @param bool $paginate    是否是简洁模式
     * @param string $field     筛选字段
     * @param string $keyword   关键字
     * @param string $sort      排序字段
     * @param string $order     排序方式
     * @param string $type      查询类型    积分
     * @param string $condition  附加查询条件
     * @param int $page
     * @param int $size
     * @return \think\Paginator
     */
    public static function getProductsByCategoryID($categoryId, $paginate = true, $field='', $keyword='', $sort='is_recommend', $order='asc', $page = 1, $size = 30,$types = 'defalut',$condition=[])
    {
        $ids = '';
        if( $categoryId !=0 ){
            //查询判断手否是父级分类
            $categoryIds = Db::name('category')->where(['pid'=>$categoryId])->field('id')->select()->toArray();
            if( !empty($categoryIds) ){
                foreach ($categoryIds as $v) {
                    $ids .= $v['id'].',';
                }
                $ids .= $categoryId;
            }else{
                $ids = $categoryId;
            }
        }

        $now = date('Y-m-d H:i:s',time());
        if($categoryId != 0 ){
            $map['cid']     = ['in',$ids];
        }
        if( $types == 'integral' ){
            $map['is_integral'] = ['=',1];
        }

        if( $types == 'vip'){
            $map['sp_vip_price'] = ['<>',0];
        }

        $map['btime']           = ['<=',$now];
        $map['etime']           = ['>=',$now];
        $map['status']          = ['=',1];
        $map['sp_inventory']    = ['>',1];
        if( $categoryId == 9999 ){
            unset($map['cid']);
        }
        if( !empty($keyword) ){
            $map['name']    = ['like','%'.$keyword.'%'];
        }

        $query = self::
        where(
           $map
        )
            ->where($condition)
            ->field($field)
            ->order(
                $sort.' '.$order
            )->limit($size);
        if (!$paginate)
        {
            return $query->select();
        }
        else
        {
            // paginate 第二参数true表示采用简洁模式，简洁模式不需要查询记录总数

            return $query->paginate(
                $size, false, [
                'page' => $page
            ]);
        }
    }

    /**
     * 获取商品详情
     * @param $id
     * @param $field
     * @return null | Product
     */
    public static function getProductDetail($id,$field)
    {
        $now = date('Y-m-d H:i:s',time());
        $map['btime']           = ['<=',$now];
        $map['etime']           = ['>=',$now];
        $map['status']          = ['=',1];
        $map['sp_inventory']     = ['>',0];

        $product = self::where($map)->field($field)->find($id);
        if($product != NULL ){
            $product = $product->toArray();
        }
        return $product;
    }

    public static function getMostRecent($count)
    {
        $products = self::limit($count)
            ->order('create_time desc')
            ->select();
        return $products;
    }

    /**
     * @param $id
     * @param $field
     * @return null | Product
     */
    public static function getCategoryByGoodsId( $id ,$field ){
        $now = date('Y-m-d H:i:s',time());

        $map['g.btime']   = ['<=',$now];
        $map['g.etime']   = ['>=',$now];
        $map['g.status']  = ['=',1];
        $map['g.id']      = ['=',$id];
        $map['sp_inventory']     = ['>',0];

        $product = self::alias('g')
            ->join('category c','g.cid = c.id','LEFT')
            ->field( $field )
            ->where( $map )
            ->find();
        return $product;
    }

    /**
     * 获取热门商品信息
     * @return string
     */
    public static function hotGoods($filed='',$limit=4)
    {
        $now = date('Y-m-d H:i:s',time());
        $map['is_hot']     = ['=',1];
        $map['btime']   = ['<=',$now];
        $map['etime']   = ['>=',$now];
        $map['status']  = ['=',1];
        $map['sp_inventory']     = ['>',0];

        $product = self::where($map)
            ->field($filed)
            ->order('sort asc')
            ->limit($limit)
            ->select( );
        return $product;
    }

    /**
     * 获取最新商品信息
     * @return string
     */
    public static function recommendGoods($filed='',$condition=[],$limit='4')
    {
        $now = date('Y-m-d H:i:s',time());
        $map['btime']   = ['<=',$now];
        $map['etime']   = ['>=',$now];
        $map['status']  = ['=',1];
        $map['is_recommend']     = ['=',1];
        $map['sp_inventory']     = ['>',0];

        $product = self::where($map)
            ->where($condition)
            ->field($filed)
            ->order('sort asc')
            ->limit($limit)
            ->select( );
        return $product;
    }

    /**
     * 会员商品
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function vipGoods()
    {
        $now = date('Y-m-d H:i:s',time());
        $map['btime']   = ['<=',$now];
        $map['etime']   = ['>=',$now];
        $map['sp_vip_price']   = ['<>',0];
        $map['status']  = ['=',1];
        $map['is_recommend']     = ['=',1];
        $map['sp_inventory']     = ['>',0];

        $product = self::where($map)
            ->field('id,name,thumb,sp_price,prefix_title,sp_o_price,sp_market,sp_vip_price')
            ->order('sort asc')
            ->select( );
        return $product;
    }

    /**
     * 可使用积分兑换的商品
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getIsIntegralGoods(){
        $now = date('Y-m-d H:i:s',time());
        $map['btime']           = ['<=',$now];
        $map['etime']           = ['>=',$now];
        $map['status']          = ['=',1];
        $map['is_integral']     = ['=',1];
        $map['sp_inventory']     = ['>',0];

        $product = self::where($map)
            ->field('id,name,thumb,sp_price,prefix_title,sp_integral')
            ->order('sort asc')
            ->select( );

        return $product;
    }

    public static function goodsCount(){
        $now = date('Y-m-d H:i:s',time());
        $map['btime']           = ['<=',$now];
        $map['etime']           = ['>=',$now];
        $map['status']          = ['=',1];

        $row  = self::where($map)->count('id');
        return $row;
    }

    public static function getAll($condition =[],$field='',$order='',$paginate,$page_index = 0,$page_size = 0){
        $count = self::where($condition)->count();

        if ($page_size == 0) {
            $list = self::field($field)
                ->where($condition)
                ->order($order)
                ->select();
            if( $list){
                $list = $list->toArray();
            }
            $page_count = 1;
        } else {
            if( $paginate == true ){
                $list =  self::field($field)
                    ->where($condition)
                    ->order($order)
                    ->paginate($page_size, false, ['page' => $page_index]);
                return $list;
            }else{
                $start_row = $page_size * ($page_index - 1);
                $list = self::field($field)
                    ->where($condition)
                    ->order($order)
                    ->limit($start_row . "," . $page_size)
                    ->select();
                if( $list){
                    $list = $list->toArray();
                }
                if ($count % $page_size == 0) {
                    $page_count = $count / $page_size;
                } else {
                    $page_count = (int) ($count / $page_size) + 1;
                }
            }
        }
        return array(
            'data' => $list,
            'total_count' => $count,
            'page_count' => $page_count
        );
    }
}
