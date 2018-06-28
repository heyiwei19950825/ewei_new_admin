<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/22
 * Time: 14:37
 */

namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\model\User;
use app\api\service\Token;
use app\lib\exception\TokenException;
use think\Db;
use think\Log;
use think\Loader;
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');
class Pay extends BaseController
{
    private $uid ;
    private $orderNo;
    public function _initialize(){
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
        User::verifyAttestation($this->uid);
        $wxConfig = config('wx');


        //微信支付配置
        define('APPID', $wxConfig['wx_id']);
        define('MCHID', $wxConfig['shop_id']);
        define('KEY', $wxConfig['shop_key']);
        define('APPSECRET', $wxConfig['wx_noncestr']);


        define('SSLCERT_PATH', $wxConfig['apiclient_cert']);
        define('SSLKEY_PATH', $wxConfig['apiclient_key']);
    }

    //创建微信支付订单
    public function pay(){
        if( $this->request->isPost()){
            $order_no = $this->request->param('order_no','');
            if( empty($order_no) ){
                $this->error('订单失败');
            }
            $order = Db::name('internet_order')->where([//网吧座位订单
                'order_no' =>$order_no,
                'status' => 0
            ])->find();

            if( empty($order) ){
                $order = Db::name('order')->where([//虚拟商品订单
                    'order_no' =>$order_no,
                    'order_status' => 0
                ])->find();
                if( empty($order) ){
                    $this->error('订单错误');
                }
            }

            $this->orderNo = $order_no;
            return $this->makeWxPreOrder($order['order_money'],'锦朝网吧订座');
        }

    }

    // 构建微信支付订单信息
    private function makeWxPreOrder($totalPrice,$body = '蔬菜采购')
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
        $wxOrderData->SetNotify_url(config('wx.pay_back_url'));

        return $this->getPaySignature($wxOrderData);
    }

    private function getPaySignature($wxOrderData)
    {

        $wxOrder = \WxPayApi::unifiedOrder($wxOrderData);
        // 失败时不会返回result_code
        if($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] !='SUCCESS'){
            Log::record($wxOrder,'error');
            Log::record('获取预支付订单失败','error');
        }
        $this->recordPreOrder($wxOrder);
        $signature = $this->sign($wxOrder);
        return $signature;
    }


    private function recordPreOrder($wxOrder){

        // 必须是update，每次用户取消支付后再次对同一订单支付，prepay_id是不同的
        Db::name('internet_order')->where('order_no', '=', $this->orderNo)
            ->update(['prepay_id' => $wxOrder['prepay_id']]);
    }


    // 签名
    private function sign($wxOrder)
    {
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid(config('wx.wx_id'));
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
}