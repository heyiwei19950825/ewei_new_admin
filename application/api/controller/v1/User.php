<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/4
 * Time: 20:24
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\User as UserModel;
use app\api\service\Token;

class User extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid(1);
    }

    public function applyVip(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $params = $this->request->param();
        $data = [
            'username' => $params['username'],
            'mobile' => $params['iphone'],
            'email' => $params['email'],
            'is_vip' => 1,
        ];

        $vipRow = UserModel::update($data,['id'=>$this->uid]);

        if( $vipRow ){
            $row['errmsg'] = '申请成功等待审核';
        }else{
            $row['errno'] = 1;

            $row['errmsg'] = '网络异常';
        }

        return $row;
    }

    /**
     * 绑定会员卡
     */
    public function bindingVip(){
        if( $this->request->isPost()){
            $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
            $params = $this->request->param();
            $user = UserModel::get([
                'mobile'=>$params['mobile']
            ]);

            if( $user ){
                if( $user['password'] ==  md5($params['password'] . config('salt')) ){
                    if($user['status'] == 2 ){
                        UserModel::update([
                            'status' =>1
                        ],[
                            'mobile'=>$params['mobile']
                        ]);

                        $row['errno'] = 0;
                        $row['errmsg'] = '绑卡成功';
                    }else{
                        //合并微信用户和会员卡信息
                        $wxUser = UserModel::get(['id'=>$this->uid]);
                        UserModel::update([
                            'openid' => $wxUser['openid'],
                            'nickname' => $wxUser['nickname'],
                            'portrait' => $wxUser['portrait']
                        ],[
                            'mobile'=>$params['mobile']
                        ]);
                        UserModel::destroy(['id'=>$this->uid]);

                        $row['errno'] = 0;
                        $row['errmsg'] = '绑卡成功';
                    }

                }
            }else{
                $row['errno'] = 1;
                $row['errmsg'] = '密码或卡号错误';
            }

            return $row;
        }
    }

    /**
     * 退出绑定
     */
    public function quit(){
        UserModel::update([
            'status' => 2,
        ],[
            'id' => $this->uid
        ]);

        $row = ['errmsg'=>'取消成功','errno'=>0,'data'=>[]];

        return $row;
    }
}