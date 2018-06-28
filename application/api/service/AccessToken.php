<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城

 * Date: 2017/3/5
 * Time: 13:32
 */

namespace app\api\service;


use think\Cache;
use think\Config;
use think\Db;
use think\Exception;
use think\Session;

class AccessToken
{

    private $tokenUrl;
    const TOKEN_CACHED_KEY = 'access';
    const TOKEN_EXPIRE_IN = 7000;

    function __construct()
    {
        if( !config('wx.app_id')){
            $site_config = Db::name('system')->find();
            $wxConfig = [
                //小程序配置
                'x_domain' => $site_config['x_domain'],
                'app_id' => $site_config['x_app_id'],
                'app_secret' => $site_config['x_app_secret'],
                //公众号配置
                'domain' => $site_config['domain'],
                'wx_id' => $site_config['app_id'],
                'wx_secret' => $site_config['app_secret'],
                'wx_noncestr' => $site_config['app_noncestr'],

                //商户配置
                'shop_id' => $site_config['shop_id'],
                'shop_key' => $site_config['shop_key'],
                'pay_back_url' => $site_config['pay_back_url'],//公众号回调
                'x_pay_back_url' => $site_config['x_pay_back_url'],//小程序回调

                //支付密钥文件
                'apiclient_cert' => $site_config['apiclient_cert'],
                'apiclient_key' => $site_config['apiclient_key'],
            ];
            $system = [
                'x_switch' => $site_config['x_switch'],
                'x_domain' => $site_config['x_domain'],
                'domain'   => $site_config['domain'],
            ];
            Config::set('setting',$system);
            Config::set('wx.app_id',$wxConfig['app_id']);
            Config::set('wx.app_secret',$wxConfig['app_secret']);
            Config::set('wx.wx_id',$wxConfig['wx_id']);
            Config::set('wx.wx_secret',$wxConfig['wx_secret']);
            Config::set('wx.wx_noncestr',$wxConfig['wx_noncestr']);
            Config::set('wx.pay_back_url',$wxConfig['pay_back_url']);
            Config::set('wx.x_pay_back_url',$wxConfig['x_pay_back_url']);
            Cache::set('wxConfig',$wxConfig);
            Session::set('wxConfig',$wxConfig);
            Session::set('system',$system);
        }

        $url = config('wx.access_token_url');
        $url = sprintf($url, config('wx.app_id'), config('wx.app_secret'));
        $this->tokenUrl = $url;
    }

    // 建议用户规模小时每次直接去微信服务器取最新的token
    // 但微信access_token接口获取是有限制的 2000次/天
    public function get()
    {
        $token = $this->getFromCache();
        if(!$token){
            return $this->getFromWxServer();
        }
        else{
            return $token;
        }
    }

    private function getFromCache(){
        $token = cache(self::TOKEN_CACHED_KEY);
        if(!$token){
            return $token;
        }
        return null;
    }

    private function getFromWxServer()
    {
        $token = curl_get($this->tokenUrl);
        $token = json_decode($token, true);
        if (!$token)
        {
            throw new Exception('获取AccessToken异常');
        }
        if(!empty($token['errcode'])){
            throw new Exception($token['errmsg']);
        }
        $this->saveToCache($token);
        return $token['access_token'];
    }
    
    private function saveToCache($token){
        cache(self::TOKEN_CACHED_KEY, $token, self::TOKEN_EXPIRE_IN);
    }

    //    private function accessIn
}