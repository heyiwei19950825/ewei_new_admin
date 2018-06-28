<?php
/**
 * 任务
 * User: heyiw
 * Date: 2018/4/2
 * Time: 20:30
 */

namespace app\api\controller\v3;


use app\api\controller\BaseController;
use think\Db;

class Task extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();

    }

    /**
     * 获取导航列表
     */
   public function getList(){
        $banner = Db::name('banner_item')->field('name,link,image')->select()->toArray();
        foreach ($banner as &$v){
            $v['image'] = $this->prefixDomain($v['image']);
        }
        $this->success('请求成功','',$banner);
   }
}