<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/21
 * Time: 17:31
 */

namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\service\Token;
use app\api\validate\User AS UserValidate;
use app\api\model\User AS UserModel;

use think\Db;
use think\Validate;
class User extends BaseController
{
    private $uid = '';
    public function _initialize(){
        parent::_initialize();
        if($_SERVER['PATH_INFO'] != '/api/v2/wechat/user') {
            $this->uid = Token::getCurrentUid();
        }
//        $this->uid = 9;
    }

    /**
     * 用户认证
     */
    public function authenticationUser(){
        if($this->request->isPost()){
        $params = $this->request->param();
         //抽奖认证
        if($params['type'] == 'take'){
            $rules = [
                'name'  => 'require|chs',
                'mobile'   => 'number',
            ];
            $msg = [
                'name'   => '请填写真实姓名',
                'mobile' => '请填写正确手机号码',
            ];


            $validate = new Validate($rules,$msg);
            if (!$validate->check($params)) {
                $this->error($validate->getError());
            }
            $isMobile = $this->isMobile($params['mobile']);
            if(!$isMobile){
                $this->error('请填写正确手机号码');
            }

            $userUpdate = UserModel::update([
                'username'=> $params['name'],
                'mobile'=> $params['mobile'],
                'is_authentication'=> 1,
            ],['id'=>$this->uid]);
            if( empty($userUpdate) ){
                $this->success('认证失败请重新尝试');
            }
            //添加用户号牌
            $userNumber = Db::name('user_number')->where(['key'=>$params['key']])->order('award_number desc')->find();
            if( empty($userNumber) ){
                $awardNumber = 1;
            }else{
                $awardNumber = $userNumber['award_number']+1;
            }
            $data = [
                'uid' => $this->uid,
                'key' => $params['key'],
                'award_number' => $awardNumber
            ];
            Db::name('user_number')->insert($data);
            $row = [
                'number' => $awardNumber
            ];

            $this->success('认证成功','',$row);
        }else{
            $validate = new UserValidate();
            $validate->goCheck();
            $row = UserModel::update([
                'username'=> $params['name'],
                'mobile'=> $params['mobile'],
                'id_card'=> $params['idCard'],
                'is_authentication'=> 1,
            ],['id'=>$this->uid]);
        }

        if( $row ){
            $this->success('认证成功');
        }else{
            $this->success('认证失败请重新尝试');
        }
        }else{
            $this->error('错误请求');
        }
    }

    /**
     * 查询用户中奖信息
     */
    public function win(){
        $status = $this->request->param('status','');
        $map = [];
        if($status != ''){
            $map['w.status'] = $status;
        }
        $map['g.status'] = 1;
        $map['w.uid'] = $this->uid;

        $row = Db::name('user_win')->alias('w')
            ->join('goods g','g.id=w.gid','LEFT')
            ->field('w.code,g.name,g.thumb,g.sp_integral,g.content')
            ->where($map)->select()->toArray();
        if( $row ){
            $row = self::prefixDomainToArray('thumb',$row);

            $this->success('查询成功','',$row);
        }else{
            $this->error('没有更多信息','',$row);
        }
    }

    /**
     * 查询用户积分记录
     */
    public function integralLog(){
       $userInfo =  UserModel::get([
            'id'=>$this->uid
        ])->toArray();
        $integral = $userInfo['integral'];

        $row = Db::name('user_integral_log')->where([
            'u_id'=> $this->uid
        ])->order('time desc')->select();
        $data = [
            'integral' => $integral,
            'log' => $row
        ];
        $this->success('查询成功','',$data);
    }



    /**
     * 获取用户信息
     */
    public function getUserInfo(){
        $row = ['msg'=>'','code'=>1,'data'=>[]];
        $uid = Token::getCurrentUid();
        $uid = json_decode($uid,true);
        $uerInfo =Db::name('user')->where(['id'=>$uid])->field('rank_id,nickname,portrait,sex,is_authentication')->find();
        //添加用户访问次数
        Db::name('statistics')->where(['uid'=>1])->setInc('visit');
        if(empty($uerInfo) ){
            $row['code'] = 0;
            $row['msgs'] = '未找到用户~';
        }else{
            $row['msg'] = '查询成功';
            $row['data'] = $uerInfo;
        }
        return $row;
    }
}