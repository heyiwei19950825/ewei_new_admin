<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/4
 * Time: 23:24
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use think\Db;

class Banner extends BaseController
{
    /**
     * 获取banner信息
     */
    public function getList(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $sid = $this->request->param('sid',1);

        $list = Db::name('slide')->field('image,link,target')->where(['s_id'=>$sid,'status'=>1])->order('sort desc')->limit(0,8)->select();
        $list = self::prefixDomainToArray('image',$list);

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
}