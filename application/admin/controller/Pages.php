<?php
/**
 * 页面管理
 * User: HeYiwei
 * Date: 2018/6/12
 * Time: 15:19
 */
namespace app\admin\controller;

use app\common\model\Pages as PagesModel;
use app\common\controller\AdminBase;

/**
 * 页面管理
 * Class Link
 * @package app\admin\controller
 */
class Pages extends AdminBase
{
    protected $pages_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->pages_model = new PagesModel();
    }

    /**
     * 页面管理
     * @return mixed
     */
    public function index()
    {
        $pages_list = $this->pages_model->where(['s_id'=>$this->admin_id])->select();
        return $this->fetch('index', ['pages_list' => $pages_list]);
    }

    /**
     * 添加页面管理
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存页面管理
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Link');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->pages_model->allowField(true)->save($data)) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑页面管理
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $link = $this->pages_model->find($id);

        return $this->fetch('edit', ['link' => $link]);
    }

    /**
     * 更新页面管理
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Link');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->pages_model->allowField(true)->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除页面管理
     * @param $id
     */
    public function delete($id)
    {
        if ($this->pages_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


    public function editUpdate(){
        if( $this->request->isPost()){
            $params = $this->request->param();
            $data = array_combine([$params['column']],[$params['value']]);
            $this->pages_model->where(['id'=>$params['row_id']])->update($data);
            return json($params['value']);
        }
    }
}