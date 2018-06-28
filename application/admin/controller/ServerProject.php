<?php
namespace app\admin\controller;

use app\common\model\ServerProject as ServerProjectModel;
use app\common\model\ServerCategory as ServerCategoryModel;
use app\common\controller\AdminBase;
use app\common\model\Technician;

/**
 * 服务管理
 * Class Article
 * @package app\admin\controller
 */
class ServerProject extends AdminBase
{
    protected $serve_project_model;
    protected $serve_category_model;
    protected $technician_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->serve_project_model  = new ServerProjectModel();
        $this->serve_category_model = new ServerCategoryModel();
        $this->technician_model     = new Technician();

        $category_list = $this->serve_category_model->where(['s_id'=>$this->admin_id])->select();
        $this->assign('category_list', $category_list);
    }

    /**
     * 服务管理
     * @param int    $cid     分类ID
     * @param string $keyword 关键词
     * @param int    $page
     * @return mixed
     */
    public function index($cid = 0)
    {
        $map   = [];
        $field = 'id,name,c_id,price,original_price,status,sort,cover_pic,sales,stock';
        if ($cid > 0) {
            $map['cid'] =  $cid;
        }
        $map['s_id'] = $this->admin_id;
        $book_goods_list  = $this->serve_project_model->field($field)->where($map)->order(['sort' => 'DESC'])->select();
        $category_list = $this->serve_category_model->column('name', 'id');

        return $this->fetch('index', ['book_goods_list' => $book_goods_list,'category_list'=>$category_list,'cid' => $cid]);
    }

    /**
     * 添加服务
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存服务
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $data['s_id']    = $this->admin_id;

            if ($this->serve_project_model->allowField(true)->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑服务
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $goods = $this->serve_project_model->where(['id'=>$id,'s_id'=>$this->admin_id])->find();

        return $this->fetch('edit', ['goods' => $goods]);
    }

    /**
     * 更新服务
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            if ($this->serve_project_model->allowField(true)->save($data, ['id'=>$id,'s_id'=>$this->admin_id]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除服务
     * @param int   $id
     * @param array $ids
     */
    public function delete($id = 0, $ids = [])
    {
        $id = $ids ? $ids : $id;
        if ($id) {
            if ($this->serve_project_model->destroy([
                'id'=>$id,'s_id'=>$this->admin_id
            ])) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('请选择需要删除的服务');
        }
    }

    /**
     * 服务审核状态切换
     * @param array  $ids
     * @param string $type 操作类型
     */
    public function toggle($ids = [], $type = '')
    {
        $data   = [];
        $status = $type == 'audit' ? 1 : 0;

        if (!empty($ids)) {
            foreach ($ids as $value) {
                $data[] = ['id' => $value, 'status' => $status];
            }
            if ($this->serve_project_model->saveAll($data)) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('请选择需要操作的服务');
        }
    }


}