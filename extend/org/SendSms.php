<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/7/16
 * Time: 12:36
 */

namespace org;

use Qcloud\Sms\SmsSingleSender;
use Qcloud\Sms\SmsMultiSender;
use think\Db;

class SendSms
{
    // 短信应用SDK AppID
    protected  $appid = ""; // 1400开头

    // 短信应用SDK AppKey
    protected $appkey = "";
    // 签名
    protected $smsSign = ""; // NOTE: 这里的签名只是示例，请使用真实的已申请的签名，签名参数使用的是`签名内容`，而不是`签名ID`

    protected $sSender ;//单发
    protected $mSender ;//群发

    public function __construct()
    {
        $smsConfig = config('sms');

        $this->appid = $smsConfig['appId'];
        $this->appkey = $smsConfig['appKey'];
        $this->smsSign = $smsConfig['smsSign'];
        $this->sSender = new SmsSingleSender($this->appid, $this->appkey);
        $this->mSender = new SmsMultiSender($this->appid, $this->appkey);


    }

    /**
     * 单发短信
     * @param $phoneNumbers 电话号码
     * @param $content 内容
     * @param $sId 商家ID
     * @return \Exception|string
     */
    public function aloneSend($phoneNumbers,$content,$sId){
        try {
            $result =  $this->sSender->send(0, "86", $phoneNumbers,
                $content, "", "");
            $rsp = json_decode($result);
            Db::name('shop')->where(['s_id'=>$sId])->setDec('note');
            return  $result;
        } catch(\Exception $e) {
            return $e;
        }
    }

    /**
     * 模板发送
     * @param $phoneNumbers 电话号码
     * @param $templateId 模板ID
     * @param $params 参数
     * @param $sId 商家ID
     * @return \Exception|string
     */
    public function sendByTemplateId($phoneNumbers,$templateId,$params,$sId){
        try {
            $result =  $this->sSender->sendWithParam("86", $phoneNumbers, $templateId,
                $params, $this->smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            $rsp = json_decode($result,true);
            if($rsp['result'] == 0 ){
                Db::name('shop')->where(['s_id'=>$sId])->setDec('note');
            }
            return  $rsp;
        } catch(\Exception $e) {
            return $e;
        }
    }

    /**
     * 群发消息
     * @param $phoneNumbers
     * @param $content
     * @param $sId
     * @return \Exception|string
     */
    public function bunchSend($phoneNumbers,$content,$sId){
        // 群发
        try {
            $result =  $this->mSender->send(0, "86", $phoneNumbers,
                $content, "", "");
            $rsp = json_decode($result);
            Db::name('shop')->where(['s_id'=>$sId])->setDec('note',count($phoneNumbers));
            return $result;
        } catch(\Exception $e) {
            return $e;
        }
    }

    /**
     * 模板发送
     * @param $phoneNumbers 电话号码
     * @param $templateId 模板ID
     * @param $params 参数
     * @param $sId 商家ID
     * @return \Exception|string
     */
    public function bunchByTemplateId($phoneNumbers,$templateId,$params,$sId){
        try {
            $result =  $this->sSender->sendWithParam("86", $phoneNumbers[0], $templateId,
                $params, $this->smsSign, "", "");  // 签名参数未提供或者为空时，会使用默认签名发送短信
            $rsp = json_decode($result);
            Db::name('shop')->where(['s_id'=>$sId])->setDec('note',count($phoneNumbers));

            return  $result;
        } catch(\Exception $e) {
            return $e;
        }
    }

}