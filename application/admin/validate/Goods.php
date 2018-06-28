<?php
namespace app\admin\validate;

use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'cid'   => 'require',
        'name' => 'require',
        'sort'  => 'require|number'
    ];

    protected $message = [
        'cid.require'   => '请选择所属分类',
        'name.require' => '请输入标题',
        'sort.require'  => '请输入排序',
        'sort.number'   => '排序只能填写数字'
    ];
}