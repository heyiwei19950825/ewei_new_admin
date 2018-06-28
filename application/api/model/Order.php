<?php

namespace app\api\model;

use think\Model;
use think\Config;
use think\Db;

class Order extends BaseModel
{
    /**
     * 修改订单状态
     * @param array $param
     */
    public static function updateOrderStatic($param = [] ){

        $updateExecute = Db::name('order')->where(['id'=>$param['id'],'buyer_id'=>$param['uid']]);
        $row = $updateExecute->update(['order_status'=>$param['status']]);
        $orderInfo = $updateExecute->field('order_type')->find();

        //检查判断订单如果是团购订单  则修改对应的团购信息
        if( $param['status'] == 8 && $orderInfo != NULL && $orderInfo['order_type'] == 1 ){
            Db::name('user_collective')->where(['order_id'=>$param['id']])->update(['status'=>1]);
        }

        return $row;
    }

    public static function getSummaryByUser( $param,$uid){
        $row = [];
        $param['buyer_id'] = $uid;
        if( $param['type'] != '9999' && $param['type'] != ''){//不是全部查询
            if( $param['type'] == '4' ){//退换货
                $param['order_status'] = ['in','4,5,6'];
            }else{
                $param['order_status'] = $param['type'];
            }
        }
        unset($param['type']);
        $data = Db::name('order')->where($param)->order('create_time desc,order_status asc')->select();
        $orderConfig = Config::get('order')['status'];

        foreach ( $data as$key=> $item){
            $row[$key]['id'] = $item['id'];
            $row[$key]['order_sn'] = $item['order_no'];
            $row[$key]['order_status_text'] = $orderConfig[$item['order_status']];
            $row[$key]['actual_price'] = $item['order_money'];
            $row[$key]['handleOption'] = $item['order_status'] == 1 ? true:false;
            $row[$key]['order_type'] = $item['order_type'];
            $row[$key]['point'] = $item['point'];
            $row[$key]['order_status'] = $item['order_status'];
            $row[$key]['goodsList'] = Db::name('order_product')->where(['order_id'=>$item['id']])->field('num,goods_name,goods_picture')->select()->toArray();
        }

        return $row;
    }


    /**
     * 删除订单信息
     * @param  [type] $id  [description]
     * @param  [type] $uid [description]
     * @return [type]      [description]
     */
    public static function delOrder( $id,$uid ){
        $updateExecute = Db::name('order')->where(['id'=>$id,'buyer_id'=>$uid]);
        $orderInfo = $updateExecute->field('order_type')->find();

        //检查判断订单如果是团购订单  则修改对应的团购信息
        if( $orderInfo != NULL && $orderInfo['order_type'] == 1 ){
            Db::name('user_collective')->where(['order_id'=>$id])->update(['status'=>1]);
        }

        $row = Db::name('order')->where(['id'=>$id,'buyer_id'=>$uid])->delete();

        return $row;
    }

    /**
     * 通过订单Id查询
     * @return array
     */
    public static function getGoodsInfoByOrderId( $param = []){
        $row = Db::name('order_product')->alias('p')
            ->join('order o','p.order_id = o.id','LEFT')
            ->where($param)
            ->find();

        return $row;
    }

    /**
     * 检测订单状态判断是否支付是否已过期
     */
    public static function checkAndDelOrder(){
        //查询已过期并且未支付的订单
        $row = Db::name('order')->where([
            'order_status' => 0
        ])->select();

        foreach ($row as $item) {
            if( $item['create_time'] + 1800 < time() ){
                //删除订单
                Db::name('order')->where([
                    'id'=>$item['id']
                ])->delete();
                //删除订单相关商品
                Db::name('order_product')->where([
                    'order_id'=>$item['id']
                ])->select();
                //判断是否是团购
                if( $item['order_type'] == 1 ){
                    Db::name('user_collective')->where([
                        'order_id'=>$item['id']
                    ])->delete();
                }
            }
        }
    }

    public static function createOrder( $userInfo,$addressOld,$cartInfo,$couponInfo,$params ){
        foreach($cartInfo as $k=>$v){
            //订单总价
            $goodsPrice = $userInfo['is_vip'] == 2 ?$v['price']['vip_price']:$v['price']['goods_price'];
            $couponPrice = isset($v['price']['coupon_price'])?$v['price']['coupon_price']:0;
            $actualPrice = $goodsPrice + $v['price']['freight'] - $couponPrice ;
            $param = [
                'order_from' => $userInfo['from'],//订单来源
                'order_no' => date('YmdHis', time()) . rand(1000, 9999),//订单编号
                'out_trade_no' => '',//外部交易号
                'order_type' => $params['type'],//订单类型
                'payment_type' => $params['paymentType'],//支付类型。取值范围：1.WEIXIN (微信支付) 2.INTEGRAL (积分支付)
                'buyer_id' => $userInfo['id'],//买家id
                'user_name' => $userInfo['nickname'],//买家会员名称
                'buyer_message' => $params['buyerMessage'],//买家附言
                'receiver_mobile' => $addressOld['info']['mobile'],//收货人的手机号码
                'receiver_province' => $addressOld['info']['province_id'],//收货人所在省
                'receiver_city' => $addressOld['info']['city_id'],//收货人所在城市
                'receiver_district' => $addressOld['info']['district_id'],//收货人所在街道
                'receiver_address' => $addressOld['info']['address'],//收货人详细地址
                'receiver_name' => $addressOld['name'],//收货人姓名
                's_id' => $k,//卖家店铺id
                'shop_name' => $v['goods_list'][0]['shop_name'],//卖家店铺名称
                'seller_memo' => '',//卖家对订单的备注
                'goods_money' => $goodsPrice/100,//商品总价
                'order_money' => $actualPrice/100,//订单总价
                'point_money' => 0,//订单消耗积分抵多少钱
                'coupon_money' => isset($v['price']['coupon_price'])? $v['price']['coupon_price']/ 100:0,//订单代金券支付金额
                'coupon_id' =>  isset($v['price']['coupon_price'])?$params['couponId']:0,//订单代金券id
                'shipping_money' => $v['price']['freight'] / 100,//订单运费
                'give_point' => $v['price']['give_point'],//订单赠送积分
                'shipping_company_id' => 0,//配送物流公司ID
                'create_time' => time(),//订单创建时间
                'finish_time' => 0,//订单完成时间
                'operator_type' => 2,//操作人类型  1店铺  2用户
                'is_vip' => $userInfo['is_vip'] == 2 ? 1: 0,//Vip订单
                'is_virtual' => $params['virtual'], //是否是虚拟订单,
                'prepay_id' => $params['paymentId']
            ];
            $orderId  = Db::name('order')->insertGetId($param);

            //添加订单对应的商品数据
            foreach ( $v['goods_list'] as $vs){
                $goodsParam = [
                    'order_id' => $orderId,//订单号
                    'cid' => $vs['cid'],
                    'goods_id' => $vs['goods_id'],
                    'goods_name' => $vs['goods_name'],
                    'price' => $vs['price'],//商品价格
                    'vip_price' => $vs['vip_price'],//vip价格
                    'cost_price' => $vs['cost_price'],//商品成本价
                    'num' => $vs['num'],//购买数量
                    'goods_money' => $vs['price'] * $vs['num'],//商品总价
                    'goods_picture' => $vs['goods_picture'],//商品图片
                    's_id' => $vs['shop_id'],//商铺ID
                    'buyer_id' => $userInfo['id'],//用户ID
                    'goods_type' => 1,//商品类型
                    'order_type' => $param['order_type'],//订单类型
                ];

                Db::name('order_product')->insert($goodsParam);
            }
        }
    }
}
