<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/24
 * Time: 16:02
 */

namespace app\admin\validate;


use think\Validate;

class Royalties extends  Validate
{
    protected $rule = [
        'name' => 'require',
    ];

    protected $message = [
        'name.require' => '请输入模板名称',
    ];
}