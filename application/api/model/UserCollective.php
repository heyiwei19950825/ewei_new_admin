<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/6
 * Time: 15:16
 */

namespace app\api\model;


use think\Db;

class UserCollective extends  BaseModel
{
    /**
     * 获取用户开团信息
     * @param array $param
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public static function getInfo($param = []){
        $row = self::where($param)->find();
        if( $row != NULL ){
            $row = $row->toArray();

            //判断拼团信息是否过期
            if( ($row['add_time'] +$row['limit_time']) < time() && $row['status'] == 0 || $row['status'] == 3){
                self::update(['status'=>2],['collective_no'=>$row['collective_no']]);
                $row['status'] = 2;
            }

        }
        return $row;
    }


    public static function getList( $param = [],$field = '',$cOrderBy ){
        $row = Db::name('user_collective')->where($param)->field($field)->order($cOrderBy)->select()->toArray();
        return $row;
    }

    public static function getNoOnLine(){
        $map = [
            'status'=> ['IN','0,2']
        ];
        $row = self::where($map)->select()->toArray();
        $refund = [];

        foreach ($row as $item) {
            //判断拼团信息是否过期
            if( ($item['add_time'] +$item['limit_time']) < time()){
                //已支付的退款
                if( $item['status'] === 0 || $item['status'] === 2 ){
                    $order =  Db::name('order')->where(['id'=>$item['order_id']])->find();
                    $orderGoods =  Db::name('order_product')->where(['order_id'=>$item['order_id']])->find();
                    $data = [
                        'order_goods_id' =>$orderGoods['order_goods_id'],
                        'refund_trade_no' => 'T_'.date('YmdHis',time()).rand(1000,9999),
                        'refund_money' => $orderGoods['goods_money'],
                        'refund_way' => 1,
                        'buyer_id' => $orderGoods['buyer_id'],
                        'refund_time' => time(),
                        'pay_id' =>$order['prepay_id'],
                        'order_id' =>$order['id'],
                        'transaction_id' =>$order['transaction_id'],
                        'remark' => '团购失败 退款',
                        's_id' => $order['s_id']
                    ];
                    Db::name('order_refund_account_records')->where(['id'=>$item['order_id']])->insert($data);

                    $refund[] = $order;
                }
            };
        }

        return $refund;

    }

    public static function del($id){
        Db::name('user_collective')->where([
            'order_id'=>$id
        ])->delete();
    }
}