<?php
/**
 * 服务分类
 * User: HeYiwei
 * Date: 2018/5/29
 * Time: 21:31
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;
use app\common\model\ServerCategory as ServerCategoryModel;
use app\common\model\ServerProject as ServerProjectModel;
class ServerCategory extends AdminBase
{
    protected $category_list;
    protected $serve_category_model;
    protected $serve_project_model;
    protected function _initialize()
    {
        parent::_initialize();
        $this->serve_category_model = new ServerCategoryModel();
        $this->serve_project_model = new ServerProjectModel();
        $this->category_list = $this->serve_category_model->where(['s_id'=>$this->admin_id])->select() ;
        $this->assign('category_list',$this->category_list);
    }

    /**
     * 预约分类
     * @return mixed
     */
    public function index(){

        return $this->fetch('');
    }


    public function add($pid = ''){
        return $this->fetch('',['pid' => $pid]);
    }

    /**
     * 保存栏目
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $data['s_id']    = $this->admin_id;

            if ($this->serve_category_model->allowField(true)->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }
    /**
     * 编辑栏目
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $category = $this->serve_category_model->where(['id'=>$id,'s_id'=>$this->admin_id])->find();
        if($category){
            return $this->fetch('edit', ['category' => $category]);
        }else{
            return $this->error('您没有编辑的权限');
        }
    }

    /**
     * 更新栏目
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            if ($this->serve_category_model->allowField(true)->save($data,['id'=>$id,'s_id'=>$this->admin_id]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除栏目
     * @param $id
     */
    public function delete($id)
    {
        $server  = $this->serve_project_model->where(['c_id' => $id])->find();

        if (!empty($server)) {
            $this->error('此分类下存在文章，不可删除');
        }
        if ($this->serve_category_model->destroy([
            'id'=>$id,'s_id'=>$this->admin_id
        ])) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

}