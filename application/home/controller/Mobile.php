<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/9
 * Time: 11:14
 */

namespace app\home\controller;


use app\common\controller\HomeBase;

class Mobile extends HomeBase
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    public function index(){
            return $this->fetch('/home_1/mobile/index');
    }
}