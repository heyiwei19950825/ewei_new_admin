<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/9
 * Time: 9:00
 */

namespace app\home\controller;


use app\common\controller\HomeBase;

class About extends HomeBase
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    public function index(){
        return $this->fetch('/home_1/about/index');
    }
}