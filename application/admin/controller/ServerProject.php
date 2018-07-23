<?php
namespace app\admin\controller;

use app\common\model\ServerProject as ServerProjectModel;
use app\common\model\ServerCategory as ServerCategoryModel;
use app\common\controller\AdminBase;
use app\common\model\Technician;
use think\Db;

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
        $royalty_template  = Db::name('royalties_template')->where([
            's_id' => $this->admin_id,
            'status' => 1
        ])->select();
        $royalty_templates = [];
        foreach ( $royalty_template as $k=>$v){
            $vs = '';
            if( $v['type'] ==  1 ){
                $vs['name'] = $v['name'].' 【'.$v['royalties'].'元'.'】';
                $vs['id'] = $v['id'];
            }else if( $v['type'] ==  2){
                $vs['name'] = $v['name'].' 【'.$v['royalties'].'%'.'】';
                $vs['id'] = $v['id'];
            }
            $royalty_templates[] =$vs;
        }
        $this->assign('royalty_template', $royalty_templates);
    }

    /**
     * 服务管理
     * @param int $cid
     * @return mixed|void
     */
    public function index($cid = 0)
    {
        $map   = [];
        $field = 'id,name,c_id,price,original_price,status,sort,cover_pic,sales,stock,royalty_type,royalty,royalty_template';
        if ($cid > 0) {
            $map['cid'] =  $cid;
        }
        if( $this->request->isPost()){
            $map['status'] = 1;
        }

        $map['s_id'] = $this->admin_id;
        $server_list  = $this->serve_project_model->getList($map,$field);
        $category_list = $this->serve_category_model->column('name', 'id');
        if( $this->request->isPost()){
            return $this->success('查询成功','',$server_list);
        }

        foreach ($server_list as $k=> &$v){
            if( $v['royalty_type'] == 0 ){
                $vt = Db::name('royalties_template')->where(['id'=>$v['royalty_template'],'status'=>1,'s_id'=>$this->admin_id])->find();
                if( $vt['type'] ==  1 ){
                    $v['royalties'] = $vt['name'].' 【'.$vt['royalties'].'元'.'】';
                }else if( $vt['type'] ==  2){
                    $v['royalties'] = $vt['name'].' 【'.$vt['royalties'].'%'.'】';
                }
            }else{
                if(  $v['royalty'] != 0 &&  $v['royalty'] != '0.00'){
                    //固定金额
                    if( $v['royalty_type'] == 1){
                        $v['royalties'] = $v['royalty'].'元';
                    }elseif( $v['royalty_type'] == 2 ){//百分比
                        $v['royalties'] = $v['royalty'].'%';
                    }
                }else{
                    $v['royalties'] = '暂无提成';
                }
            }
        }
        return $this->fetch('index', ['book_goods_list' => $server_list,'category_list'=>$category_list,'cid' => $cid]);
    }

    /**
     * 添加服务
     * @return mixed
     */
    public function add()
    {
        $number = Db::name('server_category')->where(['s_id'=>$this->admin_id])->count('id');
        if($number == 0 ){
            $this->error('请先创建服务分类','ServerCategory/index');
        }
        return $this->fetch();
    }

    /**
     * 保存服务
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
//            if($data['royalty_type'] == 0 ){
//                $data['royalty'] = 0;
//                $data['royalty_type'] = 0;
//            }else{
//                $data['royalty_template'] = 0;
//            }

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