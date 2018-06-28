<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/4/2
 * Time: 20:30
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\Token;
use think\Db;

class Nav extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();

//        $this->uid = Token::getCurrentUid();
    }

    /**
     * 获取导航列表
     */
   public function navList(){

        if( $this->request->isPost()){
            $nav = Db::name('theme')->field('icon,name,link,background_color')->order('sort asc')->select()->toArray();
            foreach ($nav as &$v){
                $v['icon'] = $this->prefixDomain($v['icon']);
            }
            $this->success('请求成功','',$nav);
        }else{
            $this->error('请求错误');
        }
   }
}