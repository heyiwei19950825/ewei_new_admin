<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/2/20
 * Time: 12:30
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;
use app\common\model\FeedBack ;
use app\common\model\FeedBackCategory;
use app\common\model\User as UserModel;
use app\common\lib\Helper;
use think\Db;


class Message extends AdminBase
{
    private $feedBack = null;
    private $user_model = null;
    protected $category_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->feedBack = new FeedBack();
        $this->user_model = new UserModel();
        $this->category_model = new FeedBackCategory();
        $category_level_list  = $this->category_model->getLevelList();

        $this->assign('category_level_list', $category_level_list);
    }


    /**
     * 消息列表
     */
    public function index(){
        $cateNote = [];
        if($this->admin_id != 1){
            $map = ['s_id'=>$this->admin_id,'msg_type'=>['in','2,3']];
        }else{
            $map = ['msg_type'=>['in','1,4']];
        }
        $row = $this->feedBack->where($map)->order('msg_time desc')->select();
        $note = $this->category_model->select();
        if( $note ){
            foreach ($note as $v ){
                $cateNote[$v['id']] = $v['name'];
            }
        }
        $noReadNumber = 0;
        foreach ( $row as &$v ){
            $v['msg_type'] = empty($note) ? '': $cateNote[$v['msg_type']];
            $v['msg_time'] =  Helper::time_tran($v['msg_time']);
            if( $v['msg_status'] == 0 ){
                $noReadNumber++;
            }
        }
        $this->feedBack->where('1=1')->update([
            'msg_status' => 1
        ]);
        return $this->fetch('index',['message' => $row,'cateNote'=>$cateNote,'noReadNumber'=>$noReadNumber]);
    }

    /**
     * 消息处理
     */
    public function dispose( $id ){
        if( $this->request->isPost() ){
            $msgRow = $this->feedBack->where(['s_id'=>$this->admin_id,'id'=>$id])->update(['msg_status'=>1]);
            if( $msgRow ){
                $this->success('操作成功');
            }else{
                $this->error('操作失败');
            }
        }
    }


    /**
     * 反馈列表
     */
    public function messageList(){
        $size = 10;
        $page = 1;
        $start_time = $end_time = $keyword = $cid = $msg_status = '';
        $params = $this->request->param();
        $condition  = [];
        extract($params);
        $endTime = strtotime($end_time);
        $startTime = strtotime($start_time);
        //搜过关键字
        if( !empty($params['keyword']) ){
            $condition['msg_type_name'] = ['like','%'.$keyword.'%'];
        }
        if( (!empty($msg_status) || $msg_status ==0) && $msg_status!='' ){
            $condition['msg_status'] = ['=',$msg_status];
        }

        //时间查询
        if ($start_time != '' && $end_time !=  '') {
            $condition["msg_time"] = [
                [
                    ">=",
                    $startTime
                ],
                [
                    "<=",
                    $endTime
                ]
            ];
        } elseif ($start_time != '' && $end_time == '') {
            $condition["msg_time"] = [
                ">=",
                $startTime
            ];
        } elseif ($start_time == '' && $end_time != '') {
            $condition["msg_time"] = [
                "<=",
                $endTime
            ];
        }

        $condition['s_id'] = $this->admin_id;

        $number = ($page*$size)-$size;
        $limit = $number.','.$size;
        $list = Db::name('feed_back')->where($condition)->order('msg_time desc')->limit($limit)->select()->toArray();
        $render =  Db::name('feed_back')->where($condition)->order('msg_time desc')->paginate($size, false, ['page' => $page]);
        foreach ($list as &$v){
            $v['time'] = Helper::time_tran($v['time']);
            $v['msg_time'] = date('Y-m-d H;i:s',$v['msg_time']);
        }

        return $this->fetch('message_list',['list'=>$list, 'start_time'=>$start_time,
            'end_time'=>$end_time,'keyword'=>$keyword,'render'=>$render,'msg_status'=>$msg_status]);
    }

    /**
     * 添加备注
     */
    public function addNote(){
        if($this->request->isPost()){
            $params = $this->request->param();
            if( empty($params['id']) ){
                $this->error('参数错误');
            }

            $row = Db::name('feed_back')->where([
                's_id'=>$this->admin_id,
                'id'=>$params['id']
            ])->update([
                'note'=>$params['msg']
            ]);
            if( $row ){
                $this->success('备注成功');
            }else{
                $this->error('备注失败');
            }
        }else{
            $this->error('备注失败');
        }
    }

    /**
     * 详情
     */
    public function info(){
        if($this->request->isPost()){
            $params = $this->request->param();
            if( empty($params['id']) ){
                $this->error('参数错误');
            }
            //查询信息
            $row = Db::name('feed_back')->where([
                'id'=>$params['id'],
                's_id'=>$this->admin_id,
            ])->field('msg_img,msg_time,msg_time,address,user_name,msg_content,msg_time,msg_type_name')->find();

            //修改查看状态
            Db::name('feed_back')->where([
                'id'=>$params['id']
            ])->update([
                'msg_status' => 1,
            ]);
            $row['msg_time'] = date('Y-m-d H:i:s',$row['msg_time']);
            $row['msg_img'] = explode(',',$row['msg_img']);
            $row['msg_img'] = empty($row['msg_img'])?[]:$row['msg_img'];

            $this->success('查询成功','',$row);
        }else{
            $this->error('错误请求');
        }
    }



    /**
     * 栏目管理
     * @return mixed
     */
    public function messageCategory()
    {
        return $this->fetch('message_category');
    }

    /**
     * 添加消息栏目
     * @param string $pid
     * @return mixed
     */
    public function messageCategoryAdd($pid = '')
    {
        return $this->fetch('message_category_add', ['pid' => $pid]);
    }

    /**
     * 保存消息栏目
     */
    public function messageCategorySave()
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $data['s_id'] = $this->admin_id;

            if( $data['pid'] != 0){
                $pCategory = $this->category_model->find(['id'=>$data['pid']]);
                $data['is_hid'] = $pCategory['is_hide'];
            }
            if ($this->category_model->allowField(true)->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑消息栏目
     * @param $id
     * @return mixed
     */
    public function messageCategoryEdit($id)
    {
        $category = $this->category_model->where(['s_id'=>$this->admin_id])->find($id);

        return $this->fetch('message_category_edit', ['category' => $category]);
    }

    /**
     * 更新消息栏目
     * @param $id
     */
    public function messageCategoryUpdate($id)
    {
        if ($this->request->isPost()) {
            $data            = $this->request->param();
            $validate_result = $this->validate($data, 'Category');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $children = $this->category_model->where(['path' => ['like', "%,{$id},%"],'s_id'=>$this->admin_id])->column('id');
                if( $data['is_hide'] == 1){
                    $map = [
                        'id' => ['in',implode(',',$children)],
                        's_id'=>$this->admin_id
                    ];
                    //父类隐藏 所有子类都隐藏
                    $this->category_model->where($map)->update(['is_hide' => 1]);
                    //子类开启父类也开启
                    $this->category_model->where(['id'=>$data['pid'],'s_id'=>$this->admin_id])->update(['is_hide' => 1]);
                }
                if (in_array($data['pid'], $children)) {
                    $this->error('不能移动到自己的子分类');
                } else {
                    if ($this->category_model->allowField(true)->save($data, $id) !== false) {
                        $this->success('更新成功');
                    } else {
                        $this->error('更新失败');
                    }
                }
            }
        }
    }

    /**
     * 删除消息栏目
     * @param $id
     */
    public function messageCategoryDelete($id)
    {
        $category = $this->category_model->where(['pid' => $id,'s_id'=>$this->admin_id])->find();
        $goods  = Db::name('feed_back')->where(['msg_type' => $id,'s_id'=>$this->admin_id])->find();

        if (!empty($category)) {
            $this->error('此分类下存在子分类，不可删除');
        }
        if (!empty($goods)) {
            $this->error('此分类下存在消息，不可删除');
        }
        if ($this->category_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 用户评论
     * @return mixed
     */
    public function comment(){
        return $this->fetch('');
    }

    /**
     * 消息
     */
    public function message(){
        //查询最新订单
        $book['list']= Db::name('book')->alias('b')->field('u.username,u.portrait,b.create_time,b.remark')->join('user u','b.u_id = u.id','LEFT')->where([
            'b.s_id' =>$this->admin_id,
            'b.status' => 1
        ])->select();
        $book['count'] = count($book['list']);
        if( $this->admin_id != 1 ){
            $map = ['s_id'=>$this->admin_id,'msg_type'=>['in','2,3'],'msg_status'=>0];
        }else{
            $map = ['msg_type'=>['in','1,4'],'msg_status'=>0];
        }
        $feedBook['count'] = Db::name('feed_back')->where($map)->count();

        $msg = [
            'book' => $book,
            'feedBook' => $feedBook,

        ];

        if( $this->admin_id == 1 ){
            $audit['count'] = Db::name('admin_user')->where([
                'audit' => 0
            ])->count();
            $msg['audit'] = $audit;
        }





        $this->success('查询成功','',$msg);
    }
}