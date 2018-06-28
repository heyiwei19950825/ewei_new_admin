<?php
/**
 * 插件模块.
 * User: HeYiwei
 * Date: 2018/6/8
 * Time: 18:59
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;

class PlugIn extends AdminBase
{
    public function index(){
        return $this->fetch();
    }
}