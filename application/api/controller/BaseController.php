<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城

 * Date: 2017/3/5
 * Time: 17:59
 */

namespace app\api\controller;


use app\api\service\Token;
use think\Cache;
use think\Controller;
use think\Db;
use think\Config;
use think\Session;


class BaseController extends Controller
{
    public function _initialize(){
        parent::_initialize();
        self::setSystem();
    }

    protected function setSystem(){
        $site_config = Db::name('system')->where(['s_id'=>1])->find();
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
    protected function checkExclusiveScope()
    {
        Token::needExclusiveScope();
    }

    protected function checkPrimaryScope()
    {
        Token::needPrimaryScope();
    }

    protected function checkSuperScope()
    {
        Token::needSuperScope();
    }

    /**
     * 字段添加域名【字符串】
     * @param $data
     * @return mixed
     */
    protected function  prefixDomain($data){
        $data = config('setting.domain'). $data;
        return $data;
    }

    /**
     * 字段添加域名【数组】
     * @param $value
     * @param $data
     * @return mixed
     */
    protected function  prefixDomainToArray($value, $data){
        if( !empty($data) ){
            foreach ( $data as $key=>&$item) {
                $item[$value] = config('setting.domain').$item[$value];
            }
        }
        return $data;
    }

    public function isMobile($value)
    {
        $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }


}