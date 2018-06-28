<?php
/**
 * 公告
 * User: heyiw
 * Date: 2018/4/2
 * Time: 20:30
 */

namespace app\api\controller\v3;


use app\api\controller\BaseController;
use think\Db;

class Inform extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();

    }

    /**
     * 获取公告
     */
   public function getList(){
        $row = Db::name('system')->field('x_inform')->where(['s_id'=>1])->find();
        $inform = json_decode($row['x_inform']);
        $this->success('请求成功','',$inform);
   }
}