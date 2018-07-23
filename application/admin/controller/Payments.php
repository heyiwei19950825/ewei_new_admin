<?php
namespace app\admin\controller;

use app\common\model\payments as paymentsModel;
use app\common\controller\AdminBase;
use app\common\model\ShopPayments;
use think\Db;

/**
 * 店铺收支
 * Class payments
 * @package app\admin\controller
 */
class Payments extends AdminBase
{
    protected $payments_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->payments_model = new ShopPayments();
    }

    /**
     * 店铺收支
     * @return mixed
     */
    public function index()
    {
        $start_time = $this->request->param('start_time',date('Y-m-d'));
        $end_time = $this->request->param('end_time',date('Y-m-d'));
        $start = $start_time.' 00:00:00';
        $end = $end_time.' 24:59:59';

        $payments_list = $this->payments_model
            ->where(['time'=>['>',$start]])
            ->where(['time' => ['<',$end]])
            ->where(['s_id'=>$this->admin_id])
            ->select();
        return $this->fetch('index', ['payments_list' => $payments_list,'start_time'=>$start_time,'end_time'=>$end_time]);
    }

    /**
     * 添加店铺收支
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存店铺收支
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $data['s_id']    = $this->admin_id;
            $data['time']    = date('Y-m-d H:i:s',time());
            $validate_result = $this->validate($data, 'payments');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->payments_model->allowField(true)->save($data)) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑店铺收支
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $payments = $this->payments_model->find($id);

        return $this->fetch('edit', ['payments' => $payments]);
    }

    /**
     * 更新店铺收支
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'payments');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->payments_model->allowField(true)->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除店铺收支
     * @param $id
     */
    public function delete($id)
    {
        if ($this->payments_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}