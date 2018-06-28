<?php
namespace app\admin\validate;

use think\Validate;

class User extends Validate
{
    protected $rule = [
        'username'         => 'require',
        'password'         => 'require|confirm:confirm_password',
        'confirm_password' => 'require|confirm:password',
        'mobile'           => 'require|number|length:11|unique:user',
        'email'            => 'email',
        // 'status'           => 'require',
    ];

    protected $message = [
        'mobile.require'         => '请输入手机号',
        'username.require'         => '请输入用户名',
        'password.confirm'         => '两次输入密码不一致',
        'confirm_password.confirm' => '两次输入密码不一致',
        'mobile.number'            => '手机号格式错误',
        'mobile.length'            => '手机号长度错误',
        'mobile.unique'            => '手机号已存在',
        'email.email'              => '邮箱格式错误',
        'password.require'         => '请输入密码',
        'confirm_password.require'         => '请再次输入密码',

        // 'status.require'           => '请选择状态'
    ];
}