<?php
namespace app\admin\controller;

use app\common\model\Technician as TechnicianModel;
use app\common\model\TechnicianCategory as TechnicianCategoryModel;
use app\common\controller\AdminBase;
use app\common\model\TechnicianPerformance;

/**
 * 技师管理
 * Class Article
 * @package app\admin\controller
 */
class Technician extends AdminBase
{
    protected $technician_model;
    protected $technician_category_model;
    protected $technician_performance_model;


    protected function _initialize()
    {
        parent::_initialize();
        $this->technician_model  = new TechnicianModel();
        $this->technician_category_model = new TechnicianCategoryModel();
        $category_list = $this->technician_category_model->where(['s_id'=>$this->admin_id])->select();
        $this->technician_performance_model = new TechnicianPerformance();
        $this->assign('category_list', $category_list);
    }

    /**
     * 技师管理
     * @param int    $cid     分类ID
     * @param string $keyword 关键词
     * @param int    $page
     * @return mixed
     */
    public function index($cid = 0)
    {
        $map   = [];
        $field = 'id,name,c_id,status,sort,mobile,cover_pic,service ';
        if ($cid > 0) {
            $map['cid'] =  $cid;
        }
        $map['s_id'] = $this->admin_id;
        $technician_list  = $this->technician_model->field($field)->where($map)->order(['sort' => 'DESC'])->select();
        $category_list = $this->technician_category_model->column('name', 'id');

        return $this->fetch('index', ['technician_list' => $technician_list,'category_list'=>$category_list,'cid' => $cid]);
    }

    /**
     * 添加技师
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存技师
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $data['s_id']    = $this->admin_id;

            if ($this->technician_model->allowField(true)->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑技师
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $goods = $this->technician_model->where(['id'=>$id,'s_id'=>$this->admin_id])->find();

        return $this->fetch('edit', ['goods' => $goods]);
    }

    /**
     * 更新技师
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            if ($this->technician_model->allowField(true)->save($data,  ['id'=>$id,'s_id'=>$this->admin_id]) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除技师
     * @param int   $id
     * @param array $ids
     */
    public function delete($id = 0, $ids = [])
    {
        $id = $ids ? $ids : $id;
        if ($id) {
            if ($this->technician_model->destroy([
                'id'=>$id,'s_id'=>$this->admin_id
            ])) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('请选择需要删除的技师');
        }
    }

    /**
     * 技师审核状态切换
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
            if ($this->technician_model->saveAll($data)) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('请选择需要操作的技师');
        }
    }

    /**
     * 获取标记分类的员工列表
     */
    public function getLabelList(){
        $cObject = $this->technician_category_model->where(['s_id'=>$this->admin_id,'is_form'=>1])->field('id,name')->select();
        $row = [];
        foreach ($cObject as $k=>$v){
            $technician = $this->technician_model->where(['s_id'=>$this->admin_id,'c_id'=>['in',$v['id']]])->field('id,name')->select();
            $row[] = ['id'=>$v['id'],'name'=>$v['name'],'tList'=>$technician];
        }
        if( $row ){
            $this->success('查询成功','',$row );
        }else{
            $this->error('暂无数据');
        }
    }

    /**
     * 员工绩效
     * @return mixed
     */
    public function performance(){
        $start_time = $this->request->param('start_time',date('Y-m-d'));
        $end_time = $this->request->param('end_time',date('Y-m-d'));
        $start = $start_time.' 00:00:00';
        $end = $end_time.' 24:59:59';
        $technicianList = $this->technician_model->getList(['s_id' =>$this->admin_id],'id,name');
        $payments_list = $this->technician_performance_model->getPerformanceList(
            [
                'start'=>$start,
                'end'=>$end,
                's_id' => $this->admin_id
            ],'s.*,u.username,u.uni_id'
        );
        //业绩
        foreach($technicianList as &$v){
            $v['performance']['goods'] = 0 ;
            $v['performance']['server'] = 0 ;
        }
        foreach( $technicianList as &$v){
            foreach($payments_list as $pv){
                if($pv['t_id'] == $v['id']){
                    if($pv['type'] == 1 ){//商品提成
                        $v['performance']['goods'] += $pv['performance'];
                    }else{//服务业绩
                        $v['performance']['server'] += $pv['performance'];
                    }
                }
            }
        }
        return $this->fetch('performance',['list'=>$payments_list,'technicianList'=>$technicianList,'start_time'=>$start_time,'end_time'=>$end_time]);
    }
}