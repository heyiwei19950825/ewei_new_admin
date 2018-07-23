<?php
namespace app\common\controller;

use org\Auth;
use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

/**
 * 后台公用基础控制器
 * Class AdminBase
 * @package app\common\controller
 */
class AdminBase extends Controller
{
    protected $admin_id;
    protected $systemConfig;
    protected $shopConfig;
    protected function _initialize()
    {
        parent::_initialize();
        $this->admin_id = Session::get('admin_id');

        $this->checkAuth();
        $this->getMenu();
        //权限Id
        $groupId = Db::name('auth_group_access')->where(['uid'=>Session::get('admin_id')])->field('group_id')->find()['group_id'];
        $this->assign('group', $groupId);

        //系统配置文件
        $this->systemConfig = Db::name('system')->find();
        //店铺配置文件
        $this->shopConfig = Db::name('shop')->where(['u_id'=>$this->admin_id])->find();

        // 输出当前请求控制器（配合后台侧边菜单选中状态）
        $this->assign('controller', Loader::parseName($this->request->controller()));
        $this->assign('system', $this->systemConfig);

    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth()
    {

        if (!Session::has('admin_id')) {
            $this->redirect('admin/login/index');
        }

        $module     = $this->request->module();
        $controller = $this->request->controller();
        $action     = $this->request->action();

        // 排除权限
        $not_check = ['admin/Index/index', 'admin/AuthGroup/getjson', 'admin/System/clear'];
        if (!in_array($module . '/' . $controller . '/' . $action, $not_check)) {
            $auth     = new Auth();
            $admin_id = Session::get('admin_id');

            if (!$auth->check($module . '/' . $controller . '/' . $action, $admin_id) && $admin_id != 1) {
                $this->error('没有权限');
            }
        }
    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu()
    {
        $menu     = [];
        $auth     = new Auth();
        $admin_id = Session::get('admin_id');

        $auth_rule_list = Db::name('auth_rule')->where(['status'=>1,'plug_in' => 1])->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
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

    /**
     * 对象转换为数组
     * @param $data
     * @return array
     */
    public function objectToArray($data){
        $array = [];
        foreach( $data as $k=>$v){
            $array[] = $v;
        }

        return $array;
    }


}