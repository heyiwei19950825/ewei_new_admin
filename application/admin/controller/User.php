<?php
namespace app\admin\controller;

use app\common\model\User as UserModel;
use app\common\controller\AdminBase;
use app\common\model\UserDeal;
use app\common\model\VoucherOrder;
use org\ExcelToArrary;
use think\Config;
use think\Db;
use app\common\model\UserRank;
use PHPExcel;

use PHPExcel_IOFactory;
/**
 * 用户管理
 * Class AdminUser
 * @package app\admin\controller
 */
class User extends AdminBase
{
    protected $user_model;
    protected $rank_model;
    protected $deal_model;
    protected $voucher_order_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->user_model = new UserModel();
        $this->rank_model = new UserRank();
        $this->deal_model = new UserDeal();
        $this->voucher_order_model = new VoucherOrder();
    }

    /**
     * 用户管理
     * @param string $keyword
     * @param int    $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['username|mobile|email'] = ['like', "%{$keyword}%"];
        }

        $uMap['s_id'] = $this->admin_id;
        $rank_list = $this->rank_model->where(['status'=>1,'s_id'=>$this->admin_id])->select();
        //普通用户
        $uMap['rank_id'] = 0;
        $user_list = $this->user_model->where($uMap)->order('id DESC')->select();
        //会员
        $map['u.rank_id'] = ['<>',0];
        $map['u.s_id'] = $this->admin_id;

        $member_list = $this->user_model->alias('u')->join('user_rank r','u.rank_id = r.id','LEFT')->field('u.id,u.mobile,u.username,u.sex,u.birthday,u.create_time,u.uni_id,r.name,u.balance,u.integral,u.status,u.validity_time')->where($map)->order('u.id DESC')->select();
        return $this->fetch('index', ['user_list' => $user_list,'rank_list'=>$rank_list, 'member_list' => $member_list, 'keyword' => $keyword]);
    }

    /**
     * 添加用户
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存用户
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $validate_result = $this->validate($data, 'User');
            $data['s_id']    = $this->admin_id;
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
//                //默认头像
//                if( empty($data['portrait']) ){
//                    $data['portrait'] = $data['sex'] == 1? '/public/static_new/img/user/male.jpg' : '/public/static_new/img/user/female.jpg';
//                }
                $data['password'] = md5($data['password'] . Config::get('salt'));

                if ( $this->user_model->allowField(true)->save($data) ) {
                    $id =$this->user_model->id;
                    //创建用户时开通会员
                    if(!empty($data['rank'])){
                        $this->user_model->where(['id'=>$id])->update(['uni_id'=>date('md').rand(1000,9999)]);
                        $this->user_model->where(['id'=>$id])->update(['rank_id'=>$data['rank']]);
                        $this->user_model->where(['id'=>$id])->setInc('integral',$data['give_integral']);
                        $this->user_model->where(['id'=>$id])->setInc('balance',$data['give_money'] + $data['money']);
                        //添加办卡订单
                        $orderData = [
                            's_id'          => $this->admin_id,
                            'u_id'          => $id,
                            'order'         => 'BK_'.date('YmdHis').uniqid(),
                            'money'         => $data['money'],
                            'time'          => date('Y;m:d H:i:s'),
                            'remark'        => $data['remark']==''?'办卡订单':$data['remark'],
                            'give_money'    => $data['give_money'],
                            'give_integral' => $data['give_integral']
                        ];
                        $this->voucher_order_model->allowField(true)->save($orderData);
                        //  添加用户消费记录
                        Db::name('user_deal')->insert([
                            'u_id' =>$id,
                            'type' =>1,
                            'money' =>$data['money'],
                            'remark' => '办卡',
                            'time'   => date('Y-m--d H:i:s',time()),
                            'give_money'    => $data['give_money'],
                            'give_integral' => $data['give_integral'],
                            'deal_type' =>1,
                            's_id' =>$this->admin_id
                        ]);
                        //添加商户金额
                        Db::name('shop')->where([
                            'u_id' => $this->admin_id
                        ])->setInc('shop_account',($data['money']+$data['give_money']) );
                        Db::name('shop')->where([
                            'u_id' => $this->admin_id
                        ])->setInc('shop_integral',($data['give_integral']) );
                    }
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑用户
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $user = $this->user_model->where(['id'=>$id,'s_id'=>$this->admin_id])->find();
        $rank_list = $this->rank_model->where(['status'=>1,'s_id'=>$this->admin_id])->select();
        return $this->fetch('edit', ['user' => $user,'rank_list'=>$rank_list]);
    }

    /**
     * 更新用户
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->post();
            $user           = $this->user_model->where(['id'=>$id,'s_id'=>$this->admin_id])->find();
            $user->id       = $id;
            $user->username = $data['username'];
            $user->mobile   = $data['mobile'];
            $user->sex      = $data['sex'];
            $user->birthday = $data['birthday'];
            $user->remarks  = $data['remarks'];
            $user->portrait = $data['portrait'];
            $user->guest_way= $data['guest_way'];
            if(isset($data['rank_id']) ){
                $user->rank_id  = $data['rank_id'];
                $user->validity_time= $data['validity_time'];
            }
            if (!empty($data['password'])) {
                $user->password = md5($data['password'] . Config::get('salt'));
            }
            if ($user->save() !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除用户
     * @param $id
     */
    public function delete($id)
    {
        if ($this->user_model->destroy([
            'id'=>$id,'s_id'=>$this->admin_id
        ])) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 办卡
     */
    public function manageMember(){
        if( $this->request->isPost()){
            $params = $this->request->param();
            //会员信息修改
            $this->user_model->where(['id'=>$params['id'],'s_id'=>$this->admin_id])->update([
                'uni_id'        => date('md').rand(1000,9999),
                'rank_id'       => $params['rank'],
                'validity_time' => $params['validity_time']
            ]);
            $this->user_model->where(['id'=>$params['id'],'s_id'=>$this->admin_id])->setInc('integral',$params['give_integral']);
            $this->user_model->where(['id'=>$params['id'],'s_id'=>$this->admin_id])->setInc('balance',$params['give_money'] + $params['money']);
            $params['remark'] = $params['remark']==''?'办卡':$params['remark'];

            //添加办卡订单
            $orderData = [
                's_id'          => $this->admin_id,
                'u_id'          => $params['id'],
                'order'         => 'BK_'.date('YmdHis').uniqid(),
                'money'         => $params['money'],
                'time'          => date('Y;m:d H:i:s'),
                'remark'        => $params['remark']==''?'办卡':$params['remark'],
                'give_money'    => $params['give_money'],
                'give_integral' => $params['give_integral']
            ];

            $row = $this->voucher_order_model->allowField(true)->save($orderData);


            //  添加用户消费记录
            Db::name('user_deal')->insert([
                'u_id' =>$params['id'],
                'type' =>1,
                'money' =>$params['money'],
                'remark' => $params['remark'],
                'time'   => date('Y-m--d H:i:s',time()),
                'give_money'    => $params['give_money'],
                'give_integral' => $params['give_integral'],
                'deal_type' =>1,
                's_id' =>$this->admin_id
            ]);
            //添加商户金额
            Db::name('shop')->where([
                'u_id' => $this->admin_id
            ])->setInc('shop_account',($params['money']+$params['give_money']) );
            Db::name('shop')->where([
                'u_id' => $this->admin_id
            ])->setInc('shop_integral',($params['give_integral']) );

            if($row){
                $this->success('开卡成功');
            } else {
                $this->error('开卡失败');
            }
        }
    }

    /**
     * 获取用户详情
     * @param $id
     * @return \think\response\Json
     */
    public function getUserInfo($id){
        $userInfo = $this->user_model->field('username,mobile,id,balance,integral,portrait,rank_id')->where(['id'=>$id,'s_id'=>$this->admin_id])->find();
        if(!empty($userInfo)){
            $userInfo = $userInfo->toArray();

            $userInfo['rank'] = $this->rank_model->field('name,discount')->where(['id'=>$userInfo['rank_id'],'s_id'=>$this->admin_id])->find()->toArray();

        }

        return json(['data'=>$userInfo,'code'=>1,'message'=>'操作完成']);
    }

    /**
     * 获取用户列表
     * @return \think\response\Json
     */
    public function search(){
            $ids = UserRank::getRankIds(['s_id'=>$this->admin_id]);

            $user = $this->user_model
                ->field('username,mobile,id')
                ->where([
                    's_id'=>$this->admin_id,
                    'rank_id' => ['in',$ids]
                ])
                ->select();
            return json(['data'=>$user,'code'=>1,'message'=>'操作完成']);
    }


    /**
     * 会员消费
     */
    public function deal()
    {
        if ($this->request->isPost()) {

            $params = $this->request->param();
            if (empty($params['u_id'])) {
                return $this->error('请选择用户');
            }
            if (empty($params['money'])) {
                return $this->error('请填写消费金额');
            }
            $user = $this->user_model->where(['id' => $params['u_id']])->find();
            if (empty($user)) {
                return $this->error('用户不存在');
            }

            //会员优惠 折扣
            $rank = $this->rank_model->where([
                'id' => $user['rank_id'],
                's_id'=>$this->admin_id
            ])->find();
            if (empty($rank)) {
                return $this->error('网络异常');
            }

            $discount = $rank['discount'] / 10;
            if ($discount != 0) {
                $params['money'] = $params['money'] * $discount;
            }
            //优先使用自定义价格
            if($params['deal_discount'] != 0 ){
                $params['money'] = $params['deal_discount'];
            }

            if ($params['money'] > $user['balance']) {
                return $this->error('用户余额不足');
            }
            //添加商户金额
            Db::name('shop')->where([
                'u_id' => $this->admin_id
            ])->setDec('shop_account',($params['money']) );

            $params['type'] = 0;
            $params['remark'] = empty($params['remark']) ? '用户到店消费' : $params['remark'];
            $row = $this->deal_model->deal($params,$this->admin_id);

            return $this->success('操作成功', '', $row);
        }
    }

    /**
     * 会员充值
     */
    public function recharge(){
        if ($this->request->isPost()) {
            $params = $this->request->param();
            if (empty($params['u_id'])) {
                return $this->error('请选择用户');
            }
            if (empty($params['money'])) {
                return $this->error('请填写充值金额');
            }
            if(empty($params['give_integral']) ){
                $params['give_integral'] = 0;
            }
            if(empty($params['give_money']) ){
                $params['give_money'] = 0;
            }

            $user = $this->user_model->where(['id' => $params['u_id'],'s_id'=>$this->admin_id])->find();
            if (empty($user)) {
                return $this->error('用户不存在');
            }

            //会员信息修改
            $this->user_model->where(['id'=>$params['u_id'],'s_id'=>$this->admin_id])->setInc('integral',$params['give_integral']);
            $this->user_model->where(['id'=>$params['u_id'],'s_id'=>$this->admin_id])->setInc('balance',$params['give_money'] + $params['money']);
            $params['remark'] =$params['remark']==''?'充值':$params['remark'];
            //添加办卡订单
            $orderData = [
                's_id'          =>$this->admin_id,
                'u_id'          => $params['u_id'],
                'order'         => 'CZ_'.date('YmdHis').uniqid(),
                'money'         => $params['money'],
                'time'          => date('Y;m:d H:i:s'),
                'remark'        => $params['remark']==''?'充值':$params['remark'],
                'give_money'    => $params['give_money'],
                'give_integral' => $params['give_integral']
            ];

            $row =$this->voucher_order_model->allowField(true)->save($orderData);

            //  添加用户消费记录
            Db::name('user_deal')->insert([
                'u_id' =>$params['u_id'],
                'type' =>1,
                'money' =>$params['money'],
                'remark' => $params['remark'],
                'time'   => date('Y-m--d H:i:s',time()),
                'give_money'    => $params['give_money'],
                'give_integral' => $params['give_integral'],
                'deal_type' =>2,
                's_id' =>$this->admin_id
            ]);

            //添加商户金额
            Db::name('shop')->where([
                'u_id' => $this->admin_id
            ])->setInc('shop_account',($params['money']+$params['give_money']) );
            Db::name('shop')->where([
                'u_id' => $this->admin_id
            ])->setInc('shop_integral',($params['give_integral']) );


            if($row){
                return $this->success('操作成功');
            }else{
                return $this->error('操作失败');
            }
        }
    }

    /**
     * 用户账单
     */
    public function bill(){
        $id = $this->request->param('id');
        $list = $this->deal_model->where([
            'u_id' => $id
        ])->field('money,type,time,remark,give_integral,give_money,money_type')->order('time desc')->select();
        $dealList = [];
        foreach ( $list as $k=>$v){
            $v['time'] =  date( 'Y/m/d H:i',strtotime($v['time']) );
            $dealList[] = $v;
            if($v['give_integral'] != 0){
                $dealList[] = [
                    'type'          => 1,
                    'money'         => $v['give_integral'],
                    'money_type'    => 2,
                    'remark'        => $v['remark'].'赠送积分',
                    'time'          => date( 'Y/m/d H:i',strtotime($v['time']) )
                ];
            }
            if($v['give_money'] != 0){
                $dealList[] = [
                    'type'          => 1,
                    'money'         => $v['give_money'],
                    'money_type'    => 1,
                    'remark'        => $v['remark'].'赠送金额',
                    'time'          => date( 'Y/m/d H:i',strtotime($v['time']) )
                ];
            }
            unset($v['give_integral']);
            unset($v['give_money']);

        }

        return $this->fetch('/user/bill',['list'=>$dealList]);
    }


    //导入Excel
    public function into()
    {
        if (!empty ($_FILES ['file_stu'] ['name'])) {
            $tmp_file = $_FILES ['file_stu'] ['tmp_name'];
            $file_types = explode(".", $_FILES ['file_stu'] ['name']);
            $file_type = $file_types [count($file_types) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type) != "xlsx") {
                $this->error('不是Excel文件，重新上传');
            }
            /*设置上传路径*/
            /*百度有些文章写的上传路径经过编译之后斜杠不对。不对的时候用大写的DS代替，然后用连接符链接就可以拼凑路径了。*/
            $savePath = ROOT_PATH . 'public' . DS . 'uploads' . DS;/*以时间来命名上传的文件*/
            $str = date('Ymdhis');
            $file_name = $str . "." . $file_type;

            /*是否上传成功*/

            if (!copy($tmp_file, $savePath . $file_name)) {
                $this->error('上传失败');
            }
            /*
            *对上传的Excel数据进行处理生成编程数据,这个函数会在下面第三步的ExcelToArray类中
            *注意：这里调用执行了第三步类里面的read函数，把Excel转化为数组并返回给$res,再进行数据库写入
            */
            //引入这个类试了百度出来的好几个方法都不行。最后简单粗暴的使用了require方式。这个类想放在哪里放在哪里。只要路径对就行。
            $ExcelToArrary = new ExcelToArrary();//实例化
            $res=$ExcelToArrary->read($savePath.$file_name,"UTF-8",$file_type);//传参,判断office2007还是office2003
            $data = [];
            /*对生成的数组进行数据库的写入*/
            foreach ($res as $k => $v) {
                if ($k > 1) {
                    $data[$k]['s_id'] = $this->admin_id;
                    $data[$k]['username'] = $v[0];
                    $data[$k]['sex'] = $v[1]=='男'?1:2;
                    $data[$k]['mobile'] = $v[2];
                    $data[$k]['birthday'] = $v[3];
                    $data[$k]['create_time'] = $v[4];
                    $data[$k]['rank_id'] = $v[5];
                    $data[$k]['balance'] = $v[6];
                    $data[$k]['give_balance'] = $v[6];
                    $data[$k]['integral'] = $v[7];
                    $data[$k]['validity_time'] = $v[8];
                    $data[$k]['password'] = md5(123456 . config('salt')) ;

                }
            }
            $results = true;
            //插入的操作最好放在循环外面
            foreach ($data as $k => $v){
                $result = Db::name('user')->insert($v);
                if(!$result){
                    $results = false;
                }
            }

            if($results){
                return $this->success('导入成功');
            }else{
                return $this->error('导入失败');
            }

        }

    }

}

