<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/7/10
 * Time: 20:26
 */

namespace app\admin\validate;


use think\Validate;

class Payments extends Validate
{
    protected $rule = [
        'type' => 'require',
        'money' => 'require|number'
    ];

    protected $message = [
        'type.require' => '请选择类型',
        'money.require' => '请填写金额',
        'money.number'  => '金额必须是数字'
    ];
}