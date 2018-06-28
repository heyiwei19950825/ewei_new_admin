<?php
/**
 * Created by Ewei.
 * Author: Ewei.
 * 微信公号: 眉山同城

 * Date: 2017/2/21
 * Time: 12:23
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\AppToken;
use app\api\service\UserToken;
use app\api\service\Token as TokenService;
use app\api\validate\AppTokenGet;
use app\api\validate\TokenGet;
use app\lib\exception\ParameterException;
use think\Db;

/**
 * 获取令牌，相当于登录
 */
class Token extends BaseController
{
    public function _initialize(){
        parent::_initialize();
    }
    /**
     * 用户获取令牌（登陆）
     * @url /token
     * @POST code
     * @note 虽然查询应该使用get，但为了稍微增强安全性，所以使用POST
     */
    public function getToken($code='',$userInfo = '')
    {
        (new TokenGet())->goCheck();
        $wx = new UserToken($code,$userInfo);
        $row = $wx->get();
        $system = Db::name('system')->field('x_logo,x_name,x_switch,x_switch_img')->find();
        $system['x_logo']       = $this->prefixDomain($system['x_logo']);
        $system['x_switch_img'] = $this->prefixDomain($system['x_switch_img']);

        return [
            'errno' => 0,
            'data' => [
                'userInfo'=>$row['userInfo'],
                'token' => $row['token'],
                'system' =>$system
            ],
            'errmsg' => ''
        ];
    }

    /**
     * 第三方应用获取令牌
     * @url /app_token?
     * @POST ac=:ac se=:secret
     */
    public function getAppToken($ac='', $se='')
    {
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        header('Access-Control-Allow-Methods: GET');
        (new AppTokenGet())->goCheck();
        $app = new AppToken();
        $token = $app->get($ac, $se);
        return [
            'token' => $token
        ];
    }

    public function verifyToken($token='')
    {
        if(!$token){
            throw new ParameterException([
                'token不允许为空'
            ]);
        }
        $valid = TokenService::verifyToken($token);
        return [
            'isValid' => $valid
        ];
    }
}