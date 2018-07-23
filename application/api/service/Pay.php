<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城

 * Date: 2017/2/26
 * Time: 16:02
 */

namespace app\api\service;


use app\api\model\Order as OrderModel;
use app\api\model\OrderPayment;
use app\api\model\Shop;
use app\lib\exception\OrderException;
use app\lib\exception\TokenException;
use app\api\model\OrderRefundAccountRecords as OrderRefund;
use app\api\model\UserCollective;
use think\Db;
use think\Exception;
use think\Loader;
use think\Log;
use think\Session;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');


class Pay
{
    private $orderNo;
    private $payId;
    private $payBackUrl;
    function __construct($payId,$type)
    {
        if (!$payId)
        {
            throw new Exception('订单号不允许为NULL');
        }
        $this->payId = $payId;
        $site_config = Session::get('wxConfig');

        //判断是小程序支付还是公众号支付 获取不同的配置
        if( $type =='xWeChat'){
            $this->payBackUrl = $site_config['x_pay_back_url'];
        }else{
            $this->payBackUrl = $site_config['pay_back_url'];
        }
    }

    /**
     * 下单
     * @return array
     */
    public function pay()
    {
        $this->checkOrderValid();

        $order = OrderPayment::get($this->payId);
//        //积分购买计算运费
//        if( $order['order_type'] == 2 ){
//            if( $order['shipping_money'] != 0 ){
//                $order['order_money'] =  $order['shipping_money'];
//            }else{
//                return 'SUCCESS';
//            }
//        }

        return $this->makeWxPreOrder($order['order_money']);

    }

    /**
     * 退款
     */
    public function refund(){
        $order = OrderRefund::get(['order_id'=>$this->orderID]);
        return $this->makeWxRefundOrder($order);
    }

    //构建微信退款订单信息
    private function makeWxRefundOrder( $order ){
        $wxOrderData = new \WxPayRefund();
        $wxOrderData->SetTransaction_id($order->transaction_id);//微信生成的订单号
        $wxOrderData->SetOut_refund_no($order->refund_trade_no);//商户系统内部的退款单号
        $wxOrderData->SetTotal_fee($order->refund_money*100);//订单金额
        $wxOrderData->SetRefund_fee($order->refund_money*100);//退款金额
//        $wxOrderData->SetOp_user_id(1493263542);//商户号操作用户ID // 眉山同城平台
        $wxOrderData->SetOp_user_id(1492410722);//商户号操作用户ID  //九宴食材 小程序美食e+

        $refund = \WxPayApi::refund($wxOrderData);

        //退款成功
        if($refund['result_code'] == 'SUCCESS'){
            $this->refundUpdateOrderStatus( $refund );
        }else{//退款失败
            Db::name('order_payment')->insert([
                'out_trade_no' => $order->refund_trade_no,
                'shop_id' => $order->s_id,
                'type' => 1,
                'type_alis_id' => $order->order_id,
                'pay_body' => $order->remark,
                'pay_detail' => $refund->err_code_des,
                'pay_money' => $order->refund_money,
                'pay_status' => 1,
                'pay_type' => 0,
                'create_time' => time(),
                'pay_time' => $order->refund_time,
                'trade_no' => $order->transaction_id,
            ]);
        }
    }

    /**
     * @param $refund
     */
    private function refundUpdateOrderStatus($refund){
        $orderRow = OrderModel::get(['order_no'=>$refund['out_trade_no']]);
        //修改订单状态
        OrderModel::update([
            'order_status' => 6,
            'refund_money' => $refund['refund_fee']/100
        ],['order_no'=>$orderRow['order_no']]);
        //修改退款信息状态
        OrderRefund::update([
            'status'=> 1,
            'remark' => '退款成功',
            'refund_time' => time()
        ],['order_id'=>$orderRow['id']]);

        //修改账户余额
        $shop = Shop::where('id','=',$orderRow['s_id'])->find();
        $account = $shop['shop_account'];
        $account = $account*100 - ($refund['refund_fee']+0);
        $account = round($account/100,2);

        Shop::where(['id'=>$orderRow['s_id']])->update(
           [ 'shop_account'=>$account]
        );

        if($orderRow['order_type'] == 1){
            //修改用户开团状态
            UserCollective::update([
                'status' => 4
            ],['order_id'=>$orderRow['id']]);
        }
    }

    // 构建微信支付订单信息
    private function makeWxPreOrder($totalPrice,$body = '眉山智慧餐饮')
    {
        $openid = Token::getCurrentTokenVar('openid');
        if (!$openid)
        {
            throw new TokenException();
        }
        $wxOrderData = new \WxPayUnifiedOrder();
        $wxOrderData->SetOut_trade_no($this->orderNo);
        $wxOrderData->SetTrade_type('JSAPI');
        $wxOrderData->SetTotal_fee($totalPrice * 100);
        $wxOrderData->SetBody($body);
        $wxOrderData->SetOpenid($openid);
        $wxOrderData->SetNotify_url($this->payBackUrl);
        return $this->getPaySignature($wxOrderData);
    }

    //向微信请求订单号并生成签名
    private function getPaySignature($wxOrderData)
    {
        $wxOrder = \WxPayApi::unifiedOrder($wxOrderData);
        // 失败时不会返回result_code
        if($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] !='SUCCESS'){
            Log::record($wxOrder,'error');
            Log::record('获取预支付订单失败','error');
//            throw new Exception('获取预支付订单失败');
        }
        $this->recordPreOrder($wxOrder);
        $signature = $this->sign($wxOrder);
        return $signature;
    }

    private function recordPreOrder($wxOrder){
        // 必须是update，每次用户取消支付后再次对同一订单支付，prepay_id是不同的
        OrderModel::where('id', '=', $this->orderID)
            ->update(['prepay_id' => $wxOrder['prepay_id']]);
    }

    // 签名
    private function sign($wxOrder)
    {
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid(config('wx.app_id'));
        $jsApiPayData->SetTimeStamp((string)time());
        $rand = md5(time() . mt_rand(0, 1000));
        $jsApiPayData->SetNonceStr($rand);
        $jsApiPayData->SetPackage('prepay_id=' . $wxOrder['prepay_id']);
        $jsApiPayData->SetSignType('md5');
        $sign = $jsApiPayData->MakeSign();
        $rawValues = $jsApiPayData->GetValues();
        $rawValues['paySign'] = $sign;
        return $rawValues;
    }

    /**
     * @return bool
     * @throws OrderException
     * @throws TokenException
     */
    private function checkOrderValid()
    {
        $order = OrderPayment::where('id', '=', $this->payId)
            ->find();
        if (!$order)
        {
            throw new OrderException();
        }
//        $currentUid = Token::getCurrentUid();
        if(!Token::isValidOperate($order->buyer_id))
        {
            throw new TokenException(
                [
                    'msg' => '订单与用户不匹配',
                    'errorCode' => 10003
                ]);
        }
        if($order->order_status != 0 && $order->order_type != 2){
            throw new OrderException([
                'msg' => '订单已支付过啦',
                'errorCode' => 80003,
                'code' => 400
            ]);
        }
        $this->orderNo = $order->order_no;
        return true;
    }
}