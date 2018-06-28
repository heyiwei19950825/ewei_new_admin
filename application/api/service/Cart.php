<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/23
 * Time: 1:12
 */

namespace app\api\service;

use app\api\model\Goods;
use app\api\model\Cart as CartModel;
use app\api\model\Express;

class Cart
{
    protected $uid;
    function __construct()
    {
    }

    public function add($uid, $params,$vip){
        //检测商品库存和状态
        $row = $this->checkGoods($params,'cart');

        //成功
        if( $row['errno'] == 0 ){
            $row = $row['data'];
            $params['goods_name']= $row['name'];
            $params['price']= $row['sp_price'];
            $params['vip_price']= $vip&&$row['sp_vip_price']!=0?$row['sp_vip_price']:0;
            $params['cost_price']= $row['sp_o_price'];
            $params['goods_picture']=  config('setting.domain').$row['thumb'];
            $params['shop_id']= $row['s_id'];
            $params['cid']= $row['cid'];
            $params['give_integral']= $row['give_integral'];
            $params['eid']= $row['eid'];
            $rows = CartModel::add($uid, $params);
        }else{//错误
            $rows = $row;
        }


        return $rows;
    }

    public function checkGoods( $params,$type){
        $field = 'sp_inventory,btime,etime,status,name,s_id,thumb,sp_price,cid,sp_vip_price,sp_o_price,give_integral,eid';
        $goodsInfo = Goods::getProductDetail($params['goodsId'],$field);
        //商品不存在
        if( empty( $goodsInfo )){
            return [
                'errno' => '4005',
                'errmsg' => '已下架',
            ];
        }
        //未审核通过
        if( $goodsInfo['status'] == 0 || $goodsInfo['status'] == '0'  ){
            return [
                'errno' => '4003',
                'errmsg' => '已下架',
            ];
        }
        //未到开始时间或已过上架时间
        if( !$goodsInfo['btime'] > date('Y-m-d H:i:s',time())){
            return [
                'errno' => '4002',
                'errmsg' => '抢购还没有开始',
            ];
        }

        if( !$goodsInfo['etime'] >= date('Y-m-d H:i:s',time()) ){
            return [
                'errno' => '4006',
                'errmsg' => '已下架',
            ];
        }
        //添加购物车是检测用户购物车中的当前商品数量
        if($type == 'cart'){
            $cartGoodsInfo = CartModel::get(['goods_id'=>$params['goodsId']]);
            $params['number'] += $cartGoodsInfo['num'];
        }

        //库存不足
        if( $goodsInfo['sp_inventory'] < $params['number']){
            return [
                'errno' => '4004',
                'errmsg' => '库存不足',
            ];
        }

        return [
            'errno' => '0',
            'errmsg' => '',
            'data'   => $goodsInfo
        ];
    }

    /**
     * 下单前数据简单处理
     * @param array $cartList
     * @param array $couponInfo
     * @return array
     */
    public function  briefnessDispose($cartList = [],$couponInfo = []){
        //初始化数据
        $row = [];
        if($couponInfo != []){
            $couponInfo['money'] = $couponInfo['money'] * 100;
        }

        $useCoupon = false;//检测是否已经添加过优惠券记录
        foreach($cartList as $k=>$v){
            if(!array_key_exists($v['shop_id'],$row)){
                $row[$v['shop_id']] = [];
            }

            //优惠券
            if($couponInfo != []){
                if( !empty($couponInfo['goods']) ){
                    if( in_array($v['goods_id'],$couponInfo['goods']) && !$useCoupon ){
                        $v['coupon'] = $couponInfo;
                        $useCoupon = true;
                    }
                }
            }


            //运费计算[商品的运费只会统计一次且使用最大值]
            $row[$v['shop_id']]['goods_list'][] = $v;

            $goods_price     = !isset($row[$v['shop_id']]['price']['goods_price'])?0:$row[$v['shop_id']]['price']['goods_price'];
            $vip_price       = !isset($row[$v['shop_id']]['price']['vip_price'])?0:$row[$v['shop_id']]['price']['vip_price'];
            $cost_price      = !isset($row[$v['shop_id']]['price']['cost_price'])?0:$row[$v['shop_id']]['price']['cost_price'];
            $freight         = !isset($row[$v['shop_id']]['price']['freight'])?0:$row[$v['shop_id']]['price']['freight'];
            $givePoint       = !isset($row[$v['shop_id']]['price']['give_point'])?0:$row[$v['shop_id']]['price']['give_point'];

            $row[$v['shop_id']]['price']['goods_price'] = $goods_price + ($v['price'] * 100 ) * $v['num'];
            $row[$v['shop_id']]['price']['vip_price']   = $vip_price + ( ($v['vip_price'] * 100 ) * $v['num'] ==0?($v['price'] * 100 ) * $v['num']:($v['vip_price'] * 100 ) * $v['num'] );//会员价
            $row[$v['shop_id']]['price']['cost_price']  = $cost_price + ($v['cost_price'] * 100 ) * $v['num'];//原价
            $row[$v['shop_id']]['price']['freight']     = $freight;//原价
            $row[$v['shop_id']]['price']['give_point']  = $givePoint + ($v['give_point'] * $v['num']);//赠送积分

            //满减减

            //满免运费

            //选取最高价格的运费
            $nowFreight = Express::getDetail($v['eid'],'cost')['cost'] * 100;
            if( $freight < $nowFreight ){
                $row[$v['shop_id']]['price']['freight'] = $nowFreight;
            }

            //检测当前商家数据中是否有优惠券减免数据
            if($couponInfo != []) {
                if (isset($v['coupon'])) {
                    $row[$v['shop_id']]['price']['coupon_price'] = $couponInfo['money'];
                }
            }
        }

        //优惠券
        if($couponInfo != []) {
            if (empty($couponInfo['goods'])) { //如果是全商品使用
                //优先优惠自营商品
                if (array_key_exists(1, $row)) {
                    $row[1]['goods_list'][0]['coupon'] = $couponInfo;
                }
                $row[1]['price']['coupon'] = $couponInfo['money'];
            }
        }

       return $row;
    }

}