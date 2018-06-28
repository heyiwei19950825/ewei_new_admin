<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/12
 * Time: 15:19
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;
use think\Db;

class Helper extends AdminBase
{
    public function index(){
        $helper = Db::name('helper')->find();
        return $this->fetch('',['helper'=>$helper['helper']]);
    }
}