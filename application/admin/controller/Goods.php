<?php
/**
 * ============================
 * @Author:   Ewei
 * @Version:  1.0
 * @DateTime: 2017-8-6 14:52
 * ============================
 */
namespace app\admin\controller;

use app\common\model\Goods as GoodsModel;
use app\common\model\GoodsCategory as CategoryModel;
use app\common\controller\AdminBase;
use think\Db;


/**
 * 商品管理
 * Class Article
 * @package app\admin\controller
 */
class Goods extends AdminBase
{
    protected $goods_model;
    protected $category_model;
    protected $category_level_list;
    protected $shop_model;
    protected $royalty_template;

    protected function _initialize()
    {
        parent::_initialize();
        $this->goods_model      = new GoodsModel();
        $this->category_model   = new CategoryModel();

        //获取分类
        $category_level_list  = $this->category_model->field('id,pid,name')->where([
            's_id' => $this->admin_id
        ])->order('s_id asc,sort desc')->select();

        $category_level_list = array2level($category_level_list);
        $this->category_level_list = $category_level_list;
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
        $this->assign('category_level_list', $category_level_list);
        $this->assign('royalty_template', $royalty_templates);
    }

    /**
     * 商品管理
     * @param int    $cid     分类ID
     * @param string $keyword 关键词
     * @param int    $page
     * @return mixed
     */
    public function index($cid = 0, $keyword = '', $page = 1)
    {
        $map = $goods_list = [];
        $field = 'g.id,g.s_id,g.name,g.cid,g.thumb,g.is_recommend,g.status,g.is_hot,g.is_top,g.sort,g.sp_price,g.sp_inventory,g.sp_market,g.publish_time,g.etime,c.name as cname,g.royalty_template,g.royalty_type,g.royalty';

        if ($cid > 0) {
            $category_children_ids = $this->category_model->where(['path' => ['like', "%,{$cid},%"]])->column('id');
            $category_children_ids = (!empty($category_children_ids) && is_array($category_children_ids)) ? implode(',', $category_children_ids) . ',' . $cid : $cid;
            $map['g.cid']            = ['IN', $category_children_ids];
        }
        if (!empty($keyword)) {
            $map['g.name'] = ['like', "%{$keyword}%"];
        }
        $map['is_virtual'] = 0;
        $map['g.s_id'] = $this->admin_id;

        $goods_list  = $this->goods_model->alias('g')
            ->join('goods_category c','g.cid = c.id','LEFT')
            ->where($map)
            ->field($field)->order('s_id asc,sort desc')->select();
        foreach ($goods_list as $k=> &$v){
            if( $v['royalty_type'] == 0 ){
                $vt = Db::name('royalties_template')->where(['id'=>$v['royalty_template'],'status'=>1,'s_id'=>$this->admin_id])->find();
                if( $vt['type'] ==  1 ){
                    $v['royalties'] = $vt['name'].' 【'.$vt['royalties'].'元'.'】';
                }else if( $vt['type'] ==  2){
                    $v['royalties'] = $vt['name'].' 【'.$vt['royalties'].'%'.'】';
                }
            }else{
                if(  $v['royalty'] != 0){
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
        return $this->fetch('index', ['goods_list' => $goods_list, 'cid' => $cid, 'keyword' => $keyword]);
    }

    /**
     * 添加商品
     * @return mixed
     */
    public function add()
    {
        //必须创建分类
        if( empty($this->category_level_list) ){
            $this->error('请先创建商品分类','admin/GoodsCategory/add');
        }

        return $this->fetch();
    }

    /**
     * 保存商品
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $data['s_id']   = $this->admin_id;
            $validate_result = $this->validate($data, 'Goods');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $row = $this->goods_model->allowField(true)->save($data);
                if ($row) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑商品
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $goods = $this->goods_model->where([
            'id'=>$id,
            's_id'=>$this->admin_id
        ])->find();
        //没有查询到商品信息
        if( empty($goods) ){
            $this->error('没有找到对应的商品信息~');
        }
        return $this->fetch('edit', ['goods' => $goods]);

    }

    /**
     * 更新商品
     */
    public function update()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            if($data['royalty_type'] == 0 ){
                $data['royalty'] = 0;
                $data['royalty_type'] = 0;
            }else{
                $data['royalty_template'] = 0;
            }
            //检查是否有存在并权限修改
            $checkIssue = $this->goods_model->where(['id'=>$data['id'],'s_id'=>$this->admin_id])->find();
            if( $checkIssue == false ){
                $this->error('没有权限修改或修改商品不存在~');
            }
            $validate_result = $this->validate($data, 'Goods');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {

                if ($this->goods_model->allowField(true)->save($data,['id'=>$data['id']]) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除商品
     * @param int   $id
     * @param array $ids
     */
    public function delete($id = 0, $ids = [])
    {
        $id = $ids ? $ids : $id;
        if ($id) {
            if ($this->goods_model->where(['id'=>['in',$id],'s_id'=>$this->admin_id])->delete()) {
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        } else {
            $this->error('请选择需要删除的商品');
        }
    }

    /**
     * 商品审核状态切换
     * @param array  $ids
     * @param string $type 操作类型
     */
    public function toggle($ids = [], $type = '')
    {
        $status = $type == 'audit' ? 1 : 0;
        $new = date('Y-m-d H:i:s',time());
        //检测是否符合上架条件
        if( $status == 1 && !empty($ids)){
            $row = $this->goods_model->pageQuery(0,0,[
                'id'=>['in',$ids],
                'etime'=>['>',$new],
                'sp_inventory'=>['>',0]
            ],'','id',false);
            if( $row['total_count'] == 0 ){
                $this->error('没有符合条件的商品信息');
            }else{
                $ids = [];
                foreach ($row['data'] as $v){
                    $ids[] = $v['id'];
                }
            }
        }

        if (!empty($ids)) {
            if ($this->goods_model->pageSave(['status' => $status],['id'=>['in',$ids]])) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        } else {
            $this->error('请选择需要操作的商品');
        }
    }

    /**
     * 商品列表数据
     * @return [type] [description]
     */
    public function goodsList(){
        return $this->fetch('goods_list');
    }

    /**
     * 获取商品数据列表  已json格式返回
     * @param  integer $page    [description]
     * @param  string  $keyword [description]
     * @return [type]           [description]
     */
    public function getGoodsList($page = 1,$keyword = ''){
        $field = 'id,name,cid,thumb,status,sort,sp_price,sp_o_price,status,prefix_title,sp_inventory,sp_market,publish_time';
        $map['status'] = 1;
        if (!empty($keyword)) {
            $map['name'] = ['like', "%{$keyword}%"];
        }
        $goods_list  = $this->goods_model->pageQuery($page,10,$map,'id desc',$field);

        $data = array(
            'list' => $goods_list['data'],
            'pages' => $goods_list['page_count']
        );

        return json(['data'=>$data,'code'=>1,'message'=>'操作完成']);
    }

    /**
     * 通过多个id号获取商品数据
     * @param  string $ids [description]
     * @return [type]      [description]
     */
    public function getGoodsListByIds($ids = ''){
        if($this->request->isPost()){
            $field = 'id,name,cid,thumb,status,sort,sp_price,sp_o_price,status,prefix_title,sp_inventory,sp_market,publish_time';
            if( $ids != ''){
                $map['id'] = ['in',$ids];
                //数据处理
                $goods_list = $this->goods_model->pageQuery(0,0,$map,'id desc',$field);
                if( $goods_list ){
                    return json(['data'=>$goods_list['data'],'code'=>1,'message'=>'操作完成']);
                }else{
                    return json(['data'=>$goods_list['data'],'code'=>0,'message'=>'没有数据']);
                }

            }
        }
    }

    /**
     * 虚拟商品列表
     * */
    public function virtual($keyword = '', $page = 1){
        $map = $goods_list = [];
        $field = 'id,s_id,name,cid,thumb,status,sort,sp_price,sp_inventory,sp_market,publish_time,sp_integral,etime';
        $map['is_virtual'] = 1;
        if (!empty($keyword)) {
            $map['name'] = ['like', "%{$keyword}%"];
        }

        $goodsListObject  = $this->goods_model->pageQuery($page,30,$map,'sort desc',$field,true);
        if($goodsListObject){
            foreach ($goodsListObject as $key => $value) {
                $value['shop'] =$this->shop_model->getInfo(['id'=>$value['s_id']],'shop_logo,shop_name');
                //获取分类名称
                foreach ($this->category_level_list as $v){
                    if( $v['id'] == $value['cid']){
                        $value['category'] = $v['name'];
                    }
                }
                $goods_list[] = $value;
            }
        }
        $category_list = $this->category_model->column('name', 'id');

        return $this->fetch('virtual', ['controller'=>'virtual','goods_list' => $goods_list,'goodsListObject'=>$goodsListObject, 'category_list' => $category_list, 'keyword' => $keyword]);
    }
    /**
     * 添加虚拟物品
     */
    public function virtualAdd(){
        if($this->request->isPost()){
            $params = $this->request->param();
            $params['s_id'] = $this->instance_id;

            if($this->goods_model->pageSave($params)){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $rank = Db::name('user_rank')->select();
            return $this->fetch('',['controller'=>'virtual','rank'=>$rank]);
        }
    }
    /**
     * 编辑虚拟物品
     */
    public function virtualEdit($id){
        if($this->request->isPost()){
            $data = $this->request->param();
            if ($this->goods_model->allowField(true)->save($data, $id) !== false) {
                $this->success('更新成功');
            }else{
                $this->error('更新失败');
            }
        }else{
            $goods = $this->goods_model->getInfo(['id'=>$id]);
            //没有查询到商品信息
            if( empty($goods) ){
                $this->error('没有找到对应的商品信息~');
            }
            $rank = Db::name('user_rank')->select();
            return $this->fetch('',['controller'=>'virtual','goods'=>$goods,'rank'=>$rank]);
        }
    }

    /**
     * 删除虚拟物品
     * @param $id
     * @param $ids
     */
    public function virtualDelete($id,$ids){
        $id = $ids ? $ids : $id;
        if ($id) {
            if ($this->goods_model->pageDel(['id'=>['in',$id]])) {
                $row = Db::name('goods_collective')->where(['goods_id'=>['in',$id]])->delete();
                $this->success('删除成功');
            } else {
                $this->error('删除失败');

            }
        } else {
            $this->error('请选择需要删除的商品');
        }
    }
}