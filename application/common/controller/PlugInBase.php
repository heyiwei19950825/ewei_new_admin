<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/16
 * Time: 15:13
 */

namespace app\common\controller;


use think\Controller;
use org\Auth;
use think\Db;
use think\Session;
class PlugInBase extends Controller
{
    protected $admin_id;

    protected function _initialize()
    {
        parent::_initialize();
        $this->admin_id = Session::get('admin_id');
        $this->getMenu();

    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu()
    {
        $menu     = [];
        $auth     = new Auth();
        $admin_id = Session::get('admin_id');

        $auth_rule_list = Db::name('auth_rule')->where(['status'=>1,'plug_in' => 2])->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        foreach ($auth_rule_list as $value) {
            if ($auth->check($value['name'], $admin_id) || $admin_id == 1) {
                $menu[] = $value;
            }
        }
        $menu = !empty($menu) ? array2tree($menu) : [];

        //冒泡排序
        $len = count($menu);
        for ($i=1; $i < $len; $i++) {
            for ($j=$len-1;$j>=$i;$j--) {
                if($menu[$j]['sort']>$menu[$j-1]['sort'])
                {//如果是从大到小的话，只要在这里的判断改成if($b[$j]>$b[$j-1])就可以了
                    $x=$menu[$j];
                    $menu[$j]=$menu[$j-1];
                    $menu[$j-1]=$x;
                }
            }
        }

        $this->assign('menu', $menu);
    }

}