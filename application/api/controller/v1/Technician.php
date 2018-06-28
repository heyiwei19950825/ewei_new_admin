<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/5
 * Time: 0:53
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use think\Db;

class Technician extends BaseController
{
    /**
     * 获取技师列表
     * @return array
     */
    public function getList(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $sid = $this->request->param('sid');
        $field = 't.id,t.name,t.mobile,t.service,t.cover_pic,t.intro,c.name as c_name';
        $list = Db::name('technician')->alias('t')
            ->join('technician_category c','t.c_id = c.id','LEFT')
            ->field($field)
            ->where([
                't.s_id'=>$sid,'t.status'=>1,'c.status'=>1,
                'c_is_technician' => 1
            ])
            ->order('t.sort desc')->select();
        foreach ($list as $k=>&$v){
            if($v['service'] == ''){
                $v['service'][] = '暂无标签';
            }else{
                $v['service'] = explode(',', $v['service']);

            }
        }
        $list = self::prefixDomainToArray('cover_pic',$list);
        if(empty($list) ){
            $row['errno']   = 1;
            $row['errmsg']  = '没有更多数据';
        }else{
            $row['errno']   = 0;
            $row['data']    = $list;
            $row['errmsg']  = '查询成功';
        }

        return $row;
    }

    public function getDetail(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $id = $this->request->param('id');
        $sid = $this->request->param('sid');

        $field = 't.id,t.name,t.mobile,t.service,t.cover_pic,t.intro,c.name as c_name,t.detail,t.book_number';
        $info = Db::name('technician')->alias('t')
            ->join('technician_category c','t.c_id = c.id','LEFT')
            ->field($field)
            ->where([
                't.s_id'=>$sid,'t.status'=>1,'c.status'=>1,'t.id'=>$id
            ])
            ->find();

        $info['cover_pic'] = self::prefixDomain($info['cover_pic']);
        if($info['service'] == ''){
            $info['service'][] = '暂无标签';
        }else{
            $info['service'] = explode(',', $info['service']);

        }
        if(empty($info) ){
            $row['errno']   = 1;
            $row['errmsg']  = '没有找到';
        }else{
            $row['errno']   = 0;
            $row['data']    = $info;
            $row['errmsg']  = '查询成功';
        }

        return $row;
    }
}