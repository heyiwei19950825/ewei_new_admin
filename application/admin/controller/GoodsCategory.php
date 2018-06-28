<?php
/**
 * 服务分类
 * User: HeYiwei
 * Date: 2018/5/29
 * Time: 21:31
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;
use app\common\model\Goods;
use app\Common\model\GoodsCategory as GoodsCategoryModel;

class GoodsCategory extends AdminBase
{
    protected $category_list;
    protected $goods_category_model;
    protected $goods_model;
    protected function _initialize()
    {
        parent::_initialize();
        $this->goods_category_model = new GoodsCategoryModel();
        $this->goods_model = new Goods();
        $this->category_list = $this->goods_category_model->where([
            's_id' => $this->admin_id
        ])->select();
        $category_list = array2level($this->category_list);
        $this->assign('category_list',$category_list);
    }

    /**
     * 商品分类
     * @return mixed
     */
    public function index(){
        return $this->fetch('');
    }

    /**
     * 商品分类添加
     * @param string $pid
     * @return mixed
     */
    public function add($pid = ''){
        return $this->fetch('',['pid' => $pid]);
    }

    /**
     * 保存商品分类
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $data['s_id']    = $this->admin_id;
            if ($this->goods_category_model->allowField(true)->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑商品分类
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $category = $this->goods_category_model->where(['id'=>$id,'s_id'=>$this->admin_id])->find();
        if($category){
            return $this->fetch('edit', ['category' => $category]);
        }else{
            return $this->error('您没有编辑的权限');
        }
    }

    /**
     * 更新商品分类
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            if ($this->goods_category_model->allowField(true)->save($data,['id'=>$id,'s_id'=>$this->admin_id]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除商品分类
     * @param $id
     */
    public function delete($id)
    {
        $server  = $this->goods_model->where(['cid' => $id])->find();

        if (!empty($server)) {
            $this->error('此分类下存在商品，不可删除');
        }
        if ($this->goods_category_model->destroy([
            'id'=>$id,'s_id'=>$this->admin_id
        ])) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

}