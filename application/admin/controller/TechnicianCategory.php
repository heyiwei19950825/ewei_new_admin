<?php
/**
 * 服务分类
 * User: HeYiwei
 * Date: 2018/5/29
 * Time: 21:31
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;
use app\common\model\Technician as TechnicianModel;
use app\common\model\TechnicianCategory as TechnicianCategoryModel;
class TechnicianCategory extends AdminBase
{
    protected $category_list;
    protected $technician_category_model;
    protected $technician_model;
    protected function _initialize()
    {
        parent::_initialize();
        $this->technician_category_model = new TechnicianCategoryModel();
        $this->technician_model = new TechnicianModel();
        $this->category_list = $this->technician_category_model->where(['s_id'=>$this->admin_id])->select();
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

            if( $data['is_technician'] == 1 ){
                $this->technician_category_model->where([
                    's_id' => $this->admin_id
                ])->update([
                    'is_technician' => 0
                ]);
            }

            if ($this->technician_category_model->allowField(true)->save($data)) {
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
        $category = $this->technician_category_model->where(['id'=>$id,'s_id'=>$this->admin_id])->find();

        return $this->fetch('edit', ['category' => $category]);
    }

    /**
     * 更新栏目
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            if( $data['is_technician'] == 1 ){
                $this->technician_category_model->where([
                    's_id' => $this->admin_id
                ])->update([
                    'is_technician' => 0
                ]);
            }

            if ($this->technician_category_model->allowField(true)->save($data, ['id'=>$id,'s_id'=>$this->admin_id]) !== false) {
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
        $article  = $this->technician_model->where(['c_id' => $id])->find();

        if (!empty($article)) {
            $this->error('此分类下存在文章，不可删除');
        }
        if ($this->technician_category_model->destroy([
            'id'=>$id,'s_id'=>$this->admin_id
        ])) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 标记为技师
     */
    public function isTechnician($id){
            //全部修改为非技师
            $this->technician_category_model->where([
                's_id' => $this->admin_id
            ])->update([
                'is_technician' => 0
            ]);

            //修改为技师分类
            $row = $this->technician_category_model->where([
                'id' => $id,
                's_id' => $this->admin_id
            ])->update([
                'is_technician' => 1
            ]);

            if( $row ){
                $this->success('标记成功');
            }else{
                $this->error('操作错误');
            }
    }
    /**
     * 标记为结算表单
     */

    public function isForm( $id ){
        $infoRow = $this->technician_category_model->where([
            'id' => $id,
            's_id' => $this->admin_id
        ])->find();

        //取消或标记
        if($infoRow['is_form'] == 0 ){
            $count = $this->technician_category_model->where([
                's_id'=>$this->admin_id,
                'is_form' => 1
            ])->count();
            if($count >= 3){
                $this->error('最多选择3个');
            }
        }

        $label = $infoRow['is_form'] == 0 ? 1:0;

        //修改为技师分类
        $row = $this->technician_category_model->where([
            'id' => $id,
            's_id' => $this->admin_id
        ])->update([
            'is_form' => $label
        ]);

        if( $row ){
            $this->success('标记成功');
        }else{
            $this->error('操作错误');
        }
    }


    public function getLabelCategory(){
        if( !$this->request->isPost()){
            return false;
        }


        if( $row ){
            $this->success('查询成功','',$row);
        }else{
            $this->error('暂无数据，请先添加或选择标记员工分类');
        }
    }

}