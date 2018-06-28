<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/20
 * Time: 17:28
 */

namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\model\User;
use app\api\validate\TokenGet;
use think\Cache;
use think\Exception;
use app\api\service\Token;
use app\lib\exception\TokenException;
use think\Db;
use app\lib\enum\ScopeEnum;
use app\api\service\WxNotify;
use app\api\service\AccessToken;


class WeChat extends BaseController
{
    private $wx_id;
    private $wx_secret;
    private $wx_noncestr;
    public function _initialize()
    {
        parent::_initialize();
        $this->wx_id = config('wx.wx_id');
        $this->wx_secret = config('wx.wx_secret');
        $this->wx_noncestr = config('wx.wx_noncestr');
    }

    /**
     * 微信号授权地址
     */
    public function response(){
        (new TokenGet())->goCheck();
        $state = str_replace('@','.',$_REQUEST['state']);
        $code = $_REQUEST['code'];//微信返回Code码
        $state =  urldecode($state);//参数
        //获取OPENID
        $getTokenUrl = sprintf(
            config('wx.user_token'), $this->wx_id, $this->wx_secret, $code);
        $result = curl_get($getTokenUrl);
        $result = json_decode($result,true);
        if(  array_key_exists('errcode',$result) ){
            throw new Exception('未获取到用户数据，微信内部错误');
        }
        $access_token = (new AccessToken())->get();

        $getUserUrl = sprintf(
            config('wx.get_info_url'), $access_token, $result['openid']);


        $userInfo = curl_get($getUserUrl);
        $userInfo = json_decode($userInfo,true);
        if(  array_key_exists('errcode',$userInfo) ){
            if($userInfo['errcode'] == 40001){
                header('Location: '.$state);
            }
            throw new Exception('未获取到用户数据，微信内部错误');
        }else{
            $row = $this->grantToken($userInfo);
            $url = $state.'?token='.$row['token'];
            header('Location: '.$url);
        }
    }

    /**
     * 授权token
     * @param $userInfo
     * @return array
     */
    private function grantToken($userInfo)
    {
        $openid = $userInfo['openid'];
        $user = User::getByOpenID($openid);
        if (!$user)
            // 借助微信的openid作为用户标识
            // 但在系统中的相关查询还是使用自己的uid
        {

            $user = $this->newUser($userInfo);
            $uid = $user->id;
        }
        else {

            $uid = $user->id;
            if($userInfo['subscribe'] == 1 ){
                //更新用户数据
                User::update([
                    'nickname'=>$userInfo['nickname'],
                    'portrait'=>$userInfo['headimgurl'],
                    'subscribe'=>$userInfo['subscribe']
                ],['id'=>$uid]);
            }

        }

        $cachedValue = $this->prepareCachedValue($userInfo, $uid);
        $token = $this->saveToCache($cachedValue);
        return [
            'token'=>$token,
            'userInfo'=>$user
        ];
    }
    // 写入缓存
    private function saveToCache($wxResult)
    {
        $TokenServer = new Token();
        $key = $TokenServer::generateToken();
        $value = json_encode($wxResult);
        $result = Cache::set($key, $value);
        if (!$result){
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 10005
            ]);
        }
        return $key;
    }

    private function prepareCachedValue($userInfo, $uid)
    {
        $cachedValue = $userInfo;
        $cachedValue['uid'] = $uid;
        $cachedValue['scope'] = ScopeEnum::User;
        return $cachedValue;
    }

    // 创建新用户
    private function newUser($userInfo)
    {
        // 有可能会有异常，如果没有特别处理
        // 这里不需要try——catch
        // 全局异常处理会记录日志
        // 并且这样的异常属于服务器异常
        // 也不应该定义BaseException返回到客户端
        $uniqid = uniqid();
        $user = User::create(
            [
                'uni_id'=>$uniqid,
                'nickname'=>$userInfo['subscribe']==0?$uniqid:$userInfo['nickname'],
                'portrait'=>$userInfo['subscribe']==0?'':$userInfo['headimgurl'],
                'create_time'=>time(),
                'update_time'=>time(),
                'openid' => $userInfo['openid'],
                'last_login_time' => time(),
                'from' => 1,
                'sex' => $userInfo['subscribe']==0?0:$userInfo['sex'],
                'rank_id' => 4,
                'city'=>$userInfo['subscribe']==0?'':$userInfo['country'].' '.$userInfo['province'].' '.$userInfo['city'],
                'subscribe' => $userInfo['subscribe']
//                'last_login_ip' =>Request::ip(0,true)
            ]);

        Db::name('statistics')->where(['uid'=>1])->setInc('user_number');
        return $user;
    }

    /**
     * 配置
     */
    public function wxConfig(){
        if(!$this->request->isPost()){
            $this->error('错误访问');
        }

        $para_filter = [];
        //js_ticket
        $access_token = Cache::get('access_token');
        $noncestr = config('wx.wx_noncestr');
        $timestamp = time();

        $ticket =cache('js_ticket');
        if( !$ticket ){
            $getJsTicket = sprintf(
                config('wx.js_ticket'), $access_token);
            $result = curl_get($getJsTicket);
            $result = json_decode($result,true);
            if($result['errcode'] != 0 ){
                throw new Exception('未获取到用户数据，微信内部错误');
            }
            cache('js_ticket', $result['ticket'], 7000);
            $ticket= $result['ticket'];
        }

        $para_filter['noncestr'] = $noncestr;
        $para_filter['jsapi_ticket'] = $ticket;
        $para_filter['timestamp'] = $timestamp;
        $para_filter['url'] = $_SERVER['HTTP_HOST'];
        $para_sort = $this->argSort($para_filter);
        $prestr = $this->createLinkstring($para_sort);
        $sign = sha1($prestr);

        $data['appid'] = config('wx.wx_id');
        $data['timestamp'] = $timestamp;
        $data['sign'] = $sign;
        $data['noncestr'] = $noncestr;

        $this->success('微信配置信息','',$data);
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * @return $para 排序后的数组
     */
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * @return $arg 拼接完成以后的字符串
     */
    private function createLinkstring($para)
    {
        $arg  = "";
        /*不能用http_build_query,会将url中的特殊字符转义,转义后生成的签名是错误的*/
        foreach($para as $key => $val)
        {
            $arg .= $key."=".$val."&";
        }
        $arg = trim($arg,'&');

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc())
        {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 支付成功回调
     */
    public function redirectNotify()
    {

        $notify = new WxNotify();
        $notify->handle();
    }

}
