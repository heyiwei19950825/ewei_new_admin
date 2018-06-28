<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/6
 * Time: 12:56
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\Token;
use think\Db;

class UserDeal extends BaseController
{
    protected $uid;
    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid(1);
    }
    public function getDeal(){
        if($this->request->isPost()){
            $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

            $list = Db::name('user_deal')->field('money,type,time,remark,give_integral,give_money')->where(['u_id'=>$this->uid])->order('time desc')->select();
            $dealList = [];
            foreach ( $list as $k=>$v){
                $v['time'] =  date( 'Y/m/d',strtotime($v['time']) );
                $dealList[] = $v;
                if($v['give_integral'] != 0){
                    $dealList[] = [
                        'type'          => 1,
                        'money'         => $v['give_integral'],
                        'money_type'    => 2,
                        'remark'        => $v['remark'].'赠送积分',
                        'time'          => date( 'Y/m/d',strtotime($v['time']) )
                    ];
                }
                if($v['give_money'] != 0){
                    $dealList[] = [
                        'type'          => 1,
                        'money'         => $v['give_money'],
                        'money_type'    => 1,
                        'remark'        => $v['remark'].'赠送金额',
                        'time'          => date( 'Y/m/d',strtotime($v['time']) )
                    ];
                }
                unset($v['give_integral']);
                unset($v['give_money']);
            }
            if(empty($dealList) ){
                $row['errno']   = 1;
                $row['errmsg']  = '没有更多数据';
            }else{
                $row['errno']   = 0;
                $row['data']    = $dealList;
                $row['errmsg']  = '查询成功';
            }

            return $row;

        }
    }
}