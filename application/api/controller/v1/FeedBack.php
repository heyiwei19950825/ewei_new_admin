<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/2/19
 * Time: 15:14
 */

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\service\Token;
use app\api\validate\FeedBack as FeedBackValidate;
use think\Db;

class FeedBack extends BaseController
{
    public $uid = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
    }

    /**
     * 添加用户反馈信息
     * @return array
     */
    public function add(){
        $row = ['msg'=>'','errorCode'=>0,'data'=>[]];

        $params = $this->request->param();
        $params['mobile'] += 0;
        $userInfo = Db::name('user')->where(['id'=>$this->uid])->find();//用户信息

        (new FeedBackValidate())->goCheck();

        //查询用户提交信息数量
        $countNum = Db::name('feed_back')->where(['user_id'=>$this->uid,'msg_status'=>0])->count('id');
        if( $countNum >= 5 ){
            $row['errno'] = 60003 ;
            $row['msg'] = '请勿多次提交';
            return $row;
        }
        $data = [
            'user_mobile'   => $params['mobile'],
            'msg_type'      => $params['type'],
            'msg_content'   => $params['content'],
            'user_id'       => $this->uid,
            'user_name'     => $userInfo['nickname'],
            'msg_time'      => time(),
            's_id'          => $params['s_id']
        ];
        $fbRow = Db::name('feed_back')->insert($data);
        if( $fbRow == 1 ){
            $row['errno'] = 0 ;
            $row['msg'] = '提交成功';
        }else{
            $row['errno'] = 400004 ;
            $row['msg'] = '网络错误请重试';
        }

        return $row;
    }

    public function category(){
        $categoryArray = [];
        $category = Db::name('feed_back_category')->where([
            'pid' => 0,
            'is_hide' => 0
        ])->order('sort asc')->select();
        if( !empty($category) ){
            $category = $category->toArray();
            foreach ( $category as $k=>$v){
                $categoryArray[$k]['id'] = $v['id'];
                $categoryArray[$k]['value'] = $v['name'];
                $childs = Db::name('feed_back_category')->where([
                    'pid' => $v['id'],
                    'is_hide' => 0
                ])->order('sort asc')->select();
                $categoryArray[$k]['childs'] = [];
                if( !empty($childs) ){
                    $childs = $childs->toArray();
                    foreach ( $childs as $ck=>$cv){
                        $categoryArray[$k]['childs'][$ck]['id'] = $cv['id'];
                        $categoryArray[$k]['childs'][$ck]['value'] = $cv['name'];
                    }
                }else{
                    $childs = [];
                }
            }
        }else{
            $categoryArray = [];
        }

        $this->success('获取成功','',$categoryArray);
    }

    public function create(){
        if($this->request->isPost()){
            $params = $this->request->param();
            $userInfo = Db::name('user')->where(['id'=>$this->uid])->find();

            $verifyTime = Db::name('feed_back')->where(['user_id'=>$this->uid])->order('time desc')->find()['time'];
            $verifyTime = $verifyTime+7200;
            if($verifyTime >= time()){
                $this->error('您今天已经反馈过啦~请稍后再试');
            }
            $row = Db::name('feed_back')->insert([
                'user_id' =>$this->uid,
                'user_name' =>$userInfo['nickname'],
                'msg_type_name' =>$params['msg_type'],
                'msg_content' =>$params['msg_content'],
                'msg_time' =>strtotime($params['msg_time']),
                'msg_img' => trim($params['msg_img'],','),
                'address' =>$params['address'],
                'shop_id' => 1,
                'time' =>time(),
            ]);

            if( !empty($row) ){
                $this->success('反馈成功');
            }
        }else{
            $this->error('错误请求');
        }
    }
}