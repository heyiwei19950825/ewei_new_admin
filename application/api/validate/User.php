<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/21
 * Time: 17:38
 */

namespace app\api\validate;


class User extends BaseValidate
{
    protected $rule = [
        'idCard' => 'require|isIdCard',
        'name' => 'require|chs',
        'mobile' => 'require|isMobile',
    ];

    protected $message=[
        'idCard' => '请填写正确身份证号码',
        'name' => '请填写正确的姓名',
        'mobile' => '请填写正确的手机号码',
    ];
}