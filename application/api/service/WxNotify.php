<?php
/**
 * Created by Ewei.
 * Author: Ewei.
 * 微信公号: 眉山同城

 * Date: 2017/2/28
 * Time: 18:12
 */

namespace app\api\service;


use app\api\model\GoodsCollective;
use app\api\model\InternetOrder;
use app\api\model\Order;
use app\api\model\Goods;
use app\api\model\OrderProduct;
use app\api\model\User;
use app\api\model\UserCollective;
use app\api\service\Order as OrderService;
use app\common\model\InternetMachine;
use app\common\model\InternetOrderMachine;
use app\lib\enum\OrderStatusEnum;
use think\Db;
use think\Exception;
use think\Loader;
use think\Log;
use app\common\model\Shop;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

//Loader::import('WxPay.WxPay', EXTEND_PATH, '.Data.php');


class WxNotify extends \WxPayNotify
{
    public function NotifyProcess($data, &$msg)
    {
        if ($data['result_code'] == 'SUCCESS') {
            $orderNo = $data['out_trade_no'];
            Log::write($data,'notice');
            Db::startTrans();
            try {
                $order = Order::where('order_no', '=', $orderNo)->lock(true)->find();
                if(!empty($order)){
                    $this->updateOrderStatus($order, true,$data);
                }else{
                    //网吧订单
                   $order = InternetOrder::where('order_no', '=', $orderNo)->lock(true)->find();
                    if($order['status'] == 0){
                        //修改订单状态
                        $this->updateInternetOrder($order);
                    }

                    return true;
                }

                if ($order['order_status'] == 0 ) {
                    $service = new OrderService();
                    $status = $service->checkOrderStock($order['id']);
                    $status['pass'] = true;
                    if ($status['pass']) {
                        $this->updateOrderStatus($order, true,$data);
                        $this->updateShopBrief($data,$order);
                        $this->updateUserIntegral($order);
                        $this->reduceStock($status);

                        //团购订单
                        if( $order['order_type'] == 1 ){
                            $this->updateCollectiveStatus($order['id']);
                        }

                    } else {
                        $this->updateOrderStatus($order['id'], false,$data);
                    }
                }
                Db::commit();
            } catch (Exception $ex) {
                Db::rollback();
                Log::error($ex->getMessage());
                // 如果出现异常，向微信返回false，请求重新发送通知
                return false;
            }
        }
        return true;
    }

    /**
     * 网吧修改状态
     */
    private  function updateInternetOrder($order){
        //修改状态
        Db::startTrans();
        try {
            InternetOrder::where('order_no', '=', $order['order_no'])
                ->update(['status' => 1,'pay_time'=>time()]);
            InternetOrderMachine::where('order_no','=',$order['order_no'])
                ->update(['status'=>1]);
            $machineList = InternetOrderMachine::where('order_no','=',$order['order_no'])->select()->toArray();
            $uid = '';
            foreach ($machineList as $k=>$v){
                InternetMachine::where(['number'=>$v['machine_number']])->update([
                    'status' => 2,
                    'order_no'=>$order['order_no']
                ]);
                $uid = $v['uid'];
            }
            //系统配置  获得积分比例
            $rule = Db::name('internet_bar_setting')->where([
                'uid' =>1
            ])->field('integral')->find();

            $integral = $order['order_money']*$rule['integral'];

            //修改用户积分
            User::where([
                'id' => $uid
            ])->setInc('integral', $integral);

            //记录日志
            User::updateUserIntegral($uid,$integral,1,'订座获得积分');

            //增加下单数量
            Db::name('statistics')->where(['uid'=>1])->setInc('success_order_number');
            //增加下单成功交易金额
            Db::name('statistics')->where(['uid'=>1])->setInc('success_order_money',$order['order_money']);
            //总收入
            Db::name('statistics')->where(['uid'=>1])->setInc('income',$order['order_money']);

            //积分增长数
            Db::name('statistics')->where(['uid'=>1])->setInc('integral_number',$integral);
            Db::commit();
        } catch (Exception $ex) {
            Db::rollback();
            Log::error($ex->getMessage());
            // 如果出现异常，向微信返回false，请求重新发送通知
            return false;
        }
    }

    private function reduceStock($status)
    {
        foreach ($status['pStatusArray'] as $singlePStatus) {
            Goods::where('id', '=', $singlePStatus['id'])
                ->setDec('sp_inventory', $singlePStatus['count']);

            Goods::where('id', '=', $singlePStatus['id'])
                ->setInc('sp_market', $singlePStatus['count']);


        }
    }

    private function updateUserIntegral($orderInfo){
        User::where('id', '=', $orderInfo['buyer_id'])
            ->setInc('integral', $orderInfo['give_point']);

    }

    private function updateOrderStatus($order, $success,$data)
    {
        $status = $success ? OrderStatusEnum::PAID : OrderStatusEnum::PAID_BUT_OUT_OF;

        //判断是否是团购
        if( $order['order_type'] == 1){
            $status = 9;
        }
        Log::record($status,'notice');
        Order::where('id', '=', $order['id'])
            ->update(['order_status' => $status,'transaction_id'=>$data['transaction_id'],'pay_time'=>time()]);
    }

    private function updateShopBrief($data,$order){
        //查询订单下的商品信息
        $goodsList = Db::name('order_product')->where(['order_id'=>$order['id']])->field('s_id,num,price,vip_price,goods_money,shipping_money,give_point,goods_id,goods_name')->select();
        $shopArray = [];
        //统计出订单中每个商家获利
        foreach ( $goodsList as $v){
            if(array_key_exists($v['s_id'],$shopArray)){
                $shopArray[$v['s_id']]['money']      += $v['vip_price']!=0?$v['vip_price']*$v['num']:$v['price']*$v['num'];
                $shopArray[$v['s_id']]['goods_id']   .= ','.$v['goods_id'];
                $shopArray[$v['s_id']]['detail']     .= ','.$v['goods_name'].'X'.$v['num'];
            }else{
                $shopArray[$v['s_id']]['goods_id']    = $v['goods_id'];
                $shopArray[$v['s_id']]['detail']      = '运费【'.$v['shipping_money'].'元】,'.$v['goods_name'].'X'.$v['num'];
                $shopArray[$v['s_id']]['money']       = $v['vip_price']!=0?$v['vip_price']*$v['num']:$v['price']*$v['num']+$v['shipping_money'];
            }
        }
        Log::record($shopArray,'notice');
        foreach($shopArray as $k=>$v){
            $payment = [
                'out_trade_no'  => $data['out_trade_no'],
                'goods_id'      => $v['goods_id'],
                'shop_id'       => $k,
                'type'          => 1,
                'pay_body'      => '购买商品订单数据',
                'pay_detail'    => $v['detail'],
                'pay_status'    => 2,
                'pay_money'     => $v['money'],
                'pay_type'      => 1,
                'create_time'   => time(),
                'pay_time'      => $data['time_end'],
                'trade_no'      => $data['transaction_id'],
            ];
            //添加支付数据
            Db::name('order_payment')->insert($payment);
            //添加店铺数据
            $shop = Shop::where('id','=',$k)->find();
            //总销售额【不计算退款】
            $freeNum = $shop['shop_sales'];
            $freeNum = $freeNum*100 + ($v['money']*100+0);
            $freeNum = round($freeNum/100,2);
            //账户余额
            $account = $shop['shop_account'];
            $account = $account*100 + ($v['money']*100+0);
            $account = round($account/100,2);

            Shop::where('id','=',$k)
                ->update(['shop_sales'=>$freeNum,'shop_account'=>$account]);
            }
    }

    /**
     * 修改团购信息数据
     * @param $orderId
     */
    private function updateCollectiveStatus($orderId){
        //查询开团规则
        $collective = UserCollective::get(['order_id'=>$orderId]);
        //查询该团 参与人数
        $joinList = UserCollective::all(['collective_no'=>$collective['collective_no']])->toArray();
        $joinNum = count($joinList);

        //判断是否达到人数要求
        if( $collective['num'] == $joinNum){
            //修改开团状态
            UserCollective::update(['status'=>1],['collective_no'=>$collective['collective_no']]);
            //修改订单状态
            Order::where('id', '=', $orderId)
                ->update(['order_status' => 1]);
        }else if($collective['num'] > $joinNum){
            //修改开团状态
            UserCollective::update(['status'=>0],['order_id'=>$orderId]);
        }
    }
}