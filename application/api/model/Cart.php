<?php

namespace app\api\model;

use think\Db;

class Cart extends BaseModel
{

    /**
     * 添加购物车
     * @param int $uid
     * @param array $params
     * @return mixed
     */
   public static  function add($uid = -1, $params = []){
       $row = self::where([
           'uid' => $uid,
           'goods_id' => $params['goodsId']
       ])->find();
       $shop = Db::name('shop')->field('shop_name')->find(['id'=>$params['shop_id']]);
       //未添加过此商品
       if( empty($row) ){
           $createRow = self::create(
               [
                   'uid'                =>$uid,
                   'cid'                =>$params['cid'],
                   'shop_id'            =>$params['shop_id'],
                   'shop_name'          =>$shop['shop_name'],
                   'goods_id'           =>$params['goodsId'],
                   'goods_name'         =>$params['goods_name'],
                   'price'              =>$params['price'] ,
                   'num'                =>$params['number'],
                   'cost_price'         =>$params['cost_price'],
                   'vip_price'          =>$params['vip_price'],
                   'goods_picture'      =>$params['goods_picture'],
                   'give_point'         =>$params['give_integral'],
                   'eid'                =>$params['eid'],
               ]
           );
           $state = $createRow->id;
       }else{
           $row->num = $row->num +$params['number'];
           $state = $row->save();
       }
       if( empty( $state) ){
           return [
               'errno' => '4007',
               'errmsg' => '系统繁忙，稍后尝试',
           ];
        }
       return [
           'errno' => '0',
           'errmsg' => '添加成功~'
       ];
   }

    /**
     * 统计购物车商品数量
     * @param int $uid
     * @return array
     */
    public static function cartCount( $uid=-1 ){
        $sql = "SELECT  price,vip_price,num  FROM ewei_cart WHERE  uid = {$uid}";
        $checkSql = "SELECT price,vip_price,num FROM ewei_cart WHERE  uid = {$uid} AND checked = 1";

        $row = self::query( $sql );
        $checkRow = self::query( $checkSql );
        $goodsCount = $goodsAmount = $checkedGoodsAmount = $checkedGoodsCount=  0;//购物车中商品数量 和价格

        //计算全部的
        if( !empty($row) ){
            foreach ($row as $k=>$v){
                $goodsCount++;
                if( $v['vip_price'] == 0){
                    $goodsAmount += $v['price']*$v['num'];
                }else{
                    $goodsAmount += $v['vip_price']*$v['num'];
                }
            }
        }
        //计算选中的
        if( !empty($checkRow) ){
            foreach ($checkRow as $k=>$v){
                $checkedGoodsCount++;
                if( $v['vip_price'] == 0){
                    $checkedGoodsAmount += $v['price']*$v['num'];
                }else{
                    $checkedGoodsAmount += $v['vip_price']*$v['num'];
                }
            }
        }


        $data['goodsCount']         = $goodsCount;
        $data['goodsAmount']        = $goodsAmount;
        $data['checkedGoodsAmount'] = $checkedGoodsAmount;
        $data['checkedGoodsCount']  = $checkedGoodsCount;

        return $data;
    }

    /**
     * 获取购物车列表商品
     * @param int $uid
     * @param bool $ckeched
     * @return $this|array
     */
    public static function getCartAll( $uid = -1,$ckeched=false ){
        $row = self::where([
            'uid' => $uid,
        ]);
        if( $ckeched ){
            $row->where([
                'checked'=> 1
            ]);
        }

        $data = $row->order('id desc')->select()->toArray();
//        echo self::getLastSql();die;
//        dump($data);die;
        if( empty($data) ){
            return [];
        }

        return $data;
    }

    /**
     * 修改购物车商品数量
     * @param $req
     * @return $this
     */
    public  static function updateNumber( $req ){
        //修改购物车商品价格和数量
        $row = self::where([
            'goods_id' => $req['goodsId'],
            'id'=>$req['id'],
            'uid'=> $req['uid']
        ])->update([
            'num'  => $req['number'],
        ]);

        return $row;
    }

    /**
     * 修改购物车选中状态
     * @param $req
     * @return bool
     */
    public static function updateChecked($req){
        $productIds = explode(',',$req['productIds']);
        foreach ($productIds as $id ){
            self::where([
                'id' => $id,
                'uid'=> $req['uid']
            ])->update([
                'checked'  => $req['isChecked'],
            ]);
        }

        return true;
    }

    /**
     * 删除购物车
     * @param $req
     * @return bool
     */
    public static function deleteCart($req){
        $productIds = explode(',',$req['productIds']);
        foreach ($productIds as $id ){
            self::where([
                'id' => $id,
                'uid'=> $req['uid']
            ])->delete();
        }

        return true;
    }


}
