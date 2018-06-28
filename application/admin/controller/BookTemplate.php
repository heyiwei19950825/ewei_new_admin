<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/5/30
 * Time: 12:27
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;

class BookTemplate extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    public function index(){
        return $this->fetch();
    }
}