<?php
namespace app\admin\validate;

use think\Validate;

class Nav extends Validate
{
    protected $rule = [
        'name' => 'require',
        'sort' => 'require|number'
    ];

    protected $message = [
        'name.require' => '请输入导航名称',
        'sort.require' => '请输入排序',
        'sort.number'  => '排序只能填写数字'
    ];
}