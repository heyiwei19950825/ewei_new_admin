<?php
namespace app\admin\controller;

use app\common\model\AdminUser as AdminUserModel;
use app\common\model\AuthGroup as AuthGroupModel;
use app\common\model\Shop      as ShopModel;
use app\common\model\AuthGroupAccess as AuthGroupAccessModel;
use app\common\controller\AdminBase;
use think\Config;
use think\Db;

/**
 * 管理员管理
 * Class AdminUser
 * @package app\admin\controller
 */
class AdminUser extends AdminBase
{
    protected $shop_model;
    protected $admin_user_model;
    protected $auth_group_model;
    protected $auth_group_access_model;

    protected function _initialize()
    {
        if($this->request->action() != 'save'){
            parent::_initialize();
        }
        $this->shop_model              = new ShopModel();
        $this->admin_user_model        = new AdminUserModel();
        $this->auth_group_model        = new AuthGroupModel();
        $this->auth_group_access_model = new AuthGroupAccessModel();

    }

    /**
     * 管理员管理
     * @return mixed
     */
    public function index()
    {
        $ids = [];
        $field = 's.shop_name,s.shop_logo,s.shop_phone,u.audit,s.shop_account,s.shop_integral,s.shop_shopowner,s.live_store_address,s.brief,.s.shop_create_time,s.audit_note,s.shop_status,u.username,u.id,u.create_time,u.status';
        $admin_user_ids  = $this->auth_group_access_model->where(['group_id' => 2])->select();
        foreach($admin_user_ids as $v){
            $ids[] = $v['uid'];
        }
        $admin_user_list = ShopModel::getShopUserListById($ids,[],$field);
        foreach ($admin_user_list as $k=>&$v){
            $v['user_number'] = Db::name('user')->where(['s_id'=>$v['id']])->count();
        }
        return $this->fetch('index', ['admin_user_list' => $admin_user_list]);
    }

    /**
     * 添加管理员
     * @return mixed
     */
    public function add()
    {
        $auth_group_list = $this->auth_group_model->select();

        return $this->fetch('add', ['auth_group_list' => $auth_group_list]);
    }

    /**
     * 保存管理员
     * @param $group_id
     */
    public function save($group_id = 2)
    {

        if ($this->request->isPost()) {
            $data            = $this->request->param();
            //商户注册
            $adminUser['group_id']          = 2;
            $adminUser['username']          = $data['shop_phone'];
            $adminUser['password']          = $data['password'];
            $adminUser['status']            = 1;
            $adminUser['confirm_password']  = $data['confirm_password'];
            $adminUser['audit']             = Db::name('system')->find()['shop_audit_switch'];
            $validate_result = $this->validate($adminUser, 'AdminUser');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $adminUser['password'] = md5($adminUser['password'] . Config::get('salt'));

                if ($this->admin_user_model->allowField(true)->save($adminUser)) {
                    //添加权限组
                    $auth_group_access['uid']      = $this->admin_user_model->id;
                    $auth_group_access['group_id'] = $group_id;
                    $this->auth_group_access_model->save($auth_group_access);

                    //添加商户信息
                    if( $group_id == 2 ){
                        $data['u_id'] = $this->admin_user_model->id;
                        $this->shop_model->allowField(true)->save($data);

                        //添加数据统计
                        Db::name('statistics')->insert(['s_id'=>$this->admin_user_model->id]);
                        //添加预约配置
                        Db::name('book_config')->insert(['s_id'=>$this->admin_user_model->id]);
                    }

                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑管理员
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
         $field = 's.shop_name,s.shop_logo,s.shop_phone,s.shop_account,s.shop_integral,s.shop_shopowner,s.live_store_address,s.brief,.s.shop_create_time,s.audit_note,s.shop_status,u.username,u.id,u.create_time,u.status';

         $admin_user = ShopModel::getShopUserListById($id,[],$field);
         $admin_user['account'] = $admin_user['username'];
         return $this->fetch('edit', ['admin_user' => $admin_user]);
    }

    /**
     * 更新管理员
     * @param $id
     * @param $group_id
     */
    public function update($id, $group_id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            foreach ($data as $k=>$v){
                if($v == ''){
                    unset($data[$k]);
                }
            }
            $admin_user = $this->admin_user_model->find($id);

            $admin_user->id       = $id;
            $admin_user->status   = $data['status'];
            if (!empty($data['password']) && !empty($data['confirm_password'])) {
                $admin_user->password = md5($data['password'] . Config::get('salt'));
            }
            if ($admin_user->save() !== false) {
                $auth_group_access['uid']      = $id;
                $auth_group_access['group_id'] = $group_id;
                $this->auth_group_access_model->where('uid', $id)->update($auth_group_access);
                //修改店铺信息
                if( $group_id == 2 ){
                    $data['shop_status'] = $data['status'];
                    unset($data['id']);
                    unset($data['status']);
                    unset($data['group_id']);
                    unset($data['password']);
                    unset($data['confirm_password']);
                    $this->shop_model->where('u_id', $id)->update($data);
                }
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除管理员
     * @param $id
     */
    public function delete($id)
    {
        if ($id == 1) {
            $this->error('默认管理员不可删除');
        }
        if ($this->admin_user_model->destroy($id)) {
            $this->auth_group_access_model->where('uid', $id)->delete();
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 审核
     * @param $id
     */
    public function audit($id){
        $data = [
            'audit' => $this->request->param('audit')
        ];
        if ($this->admin_user_model->where('id', $id)->update($data)) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    public function passAffirm( ){
        if( $this->request->isPost()){
            $pass = $this->request->param('password');
            $password = md5($pass . Config::get('salt'));
            $verify = $this->admin_user_model->where(['id'=>$this->admin_id,'password'=>$password])->find();
            if( $verify){
                $this->success('密码正确');
            }else{
                $this->error('密码错误');
            }
        }
    }
}