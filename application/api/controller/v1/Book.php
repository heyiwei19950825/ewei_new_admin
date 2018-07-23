<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/6
 * Time: 0:50
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\Token;
use app\api\model\Book as BookModel;
use org\SendSms;
use think\Db;

class Book extends BaseController
{
    protected $uid;
    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
    }

    /**
     * 支付预约订单
     * @return array
     */
    public function pay(){
        if( $this->request->isPost()){
            $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
            $msg = '';
            $params = $this->request->param();
            $verifyTime = date('Y-m-d',strtotime($params['time']));
            $config = Db::name('book_config')->where([
                's_id' =>$params['s_id']
            ])->find();
            if($config['book_verify'] == 0 ){
                $verify = false;
            }else{
                //验证1天内是否有预约
                $verify = ( new BookModel)->where([
                    'u_id' => $this->uid,
                    'DATE_FORMAT(time,"%Y-%m-%d")' => ["=", $verifyTime],
                    'project_id' => ['=',$params['project_id']],
                    'status' =>['NOT IN','3,4,5']
                ])->select();
            }
            if($verify ){
                $msg ='您今天有预约';
                $data = false;
            }else{
                $params['u_id'] = $this->uid;
                $params['time'] = date('Y/',time()). $params['time'];
                $params['create_time'] = date('Y-m-d H:i:s',time());
                $params['status'] = 1;
                $params['type'] = 1;
                $params['time'] = str_replace('/','-',$params['time']);
                $data = ( new BookModel)->allowField(true)->save($params);
                Db::name('technician')->where(['id'=>$params['project_id']])->setInc('book_number');
            }
            if(empty($data) ){
                $row['errno']   = 1;
                $row['errmsg']  = $msg;
            }else{
                //发送短信通知
                $shopConfig = Db::name('shop')->where(['id'=>$params['s_id']])->find();

                $technician = Db::name('technician')->where(['id'=>$params['project_id']])->find();
                $user = Db::name('user')->where(['id'=>$this->uid])->find();
                if(in_array('recharge',json_decode($shopConfig['note_config']),true)&& $shopConfig['note'] > 0 ){
                    ( new SendSms())->sendByTemplateId($user['mobile'],156837,[$shopConfig['shop_name'],$technician['name'].'于'.$params['time']],$params['s_id']);
                }

                $row['errno']   = 0;
                $row['errmsg']  = '预约成功';
            }

            return $row;
        }
    }

    /**
     * 获取预约订单
     */
    public function getBookOrderList(){

        if($this->request->isPost()){
            $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

            $list = Db::name('book')->where([
                'u_id' => $this->uid
            ])->field('id,type,project_id,price,status,time,remark')->order('create_time desc')->select();
            $bookList = ['all'=>[],'not'=>[],'pay'=>[],'affirm'=>[],'accomplish'=>[],'past_due'=>[]];
            foreach ($list as $k=>$v){
                if($v['type'] == 1){
                    $project = Db::name('technician')->where([
                        'id'=>$v['project_id']
                    ])->field('id,name,mobile,cover_pic,status')->find();
                    $project['cover_pic'] = self::prefixDomain($project['cover_pic']);
                }
                if($v['type'] == 2){
                    $project = Db::name('server_project')->where([
                        'id'=>$v['project_id']
                    ])->field('id,name,status,cover_pic')->find();
                    $project['cover_pic'] = self::prefixDomain($project['cover_pic']);
                }

                $v['project'] = $project;
                if($v['status'] == 0 ) {
                    $v['statusNote'] = '未支付';
                }
                if($v['status'] == 1 ) {
                    $v['statusNote'] = '预约中';
                }
                if($v['status'] == 2 ) {
                    $v['statusNote'] = '已接受';
                }
                if($v['status'] == 3 ) {
                    $v['statusNote'] = '已完成';
                }
                if($v['status'] == 4 || $v['status'] == 5) {
                    $v['statusNote'] = '已过期';
                }

                if($v['status'] == 0 ){
                    $bookList['not'][] = $v;
                }
                if($v['status'] == 1 ){
                    $bookList['pay'][] = $v;
                }
                if($v['status'] == 2 ){
                    $bookList['affirm'][] = $v;
                }
                if($v['status'] == 3 ){
                    $bookList['accomplish'][] = $v;
                }
                if($v['status'] == 4){
                    $bookList['past_due'][] = $v;
                }

                $bookList['all'][] = $v;
            }

            if(empty($bookList) ){
                $row['errno']   = 1;
                $row['errmsg']  = '暂无数据';
            }else{
                $row['errno']   = 0;
                $row['data']    = $bookList;
                $row['errmsg']  = '查询成功';
            }

            return $row;
        }

    }

    /**
     *修改预约订单状态
     */
    public function updateStatus(){
        //前端修改状态
        if( $this->request->isPost()){
            $params = $this->request->param();
            $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
            $bookRow = Db::name('book')->where([
                'id'=>$params['id']
            ])->field('time,status,project_id')->find();
            $msg = '';
            if( ($bookRow['status'] != 1 &&  $bookRow['status'] != 2) ||  $bookRow['time'] < date('Y-m-d H:i:s',strtotime('+30 minute')) ){
                $msg = '订单已无法取消';
            }
            $updateRow = Db::name('book')->where([
                'id'=>$params['id']
            ])->update([
                'status' =>5
            ]);

            if(empty($updateRow) ){
                $row['errno']   = 1;
                $row['errmsg']  = $msg;
            }else{
                //修改技师预约次数
                Db::name('technician')->where(['id'=>$bookRow['project_id']])->setDec('book_number');
                $row['errno']   = 0;
                $row['errmsg']  = '取消成功';
            }
            return $row;
        }


        //定时任务修改状态
        if( $this->request->isGet()){

        }

    }
}