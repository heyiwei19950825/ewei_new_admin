<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/4/19
 * Time: 10:18
 */

namespace app\api\validate;


class ShopEnter extends BaseValidate
{
    protected $rule = [
        'user_name' => 'require|isNotEmpty',
        'user_phone' => 'require|isMobile',
        'wechat' => 'require|isNotEmpty',
        'shop_name' => 'require|isNotEmpty',
        'address' => 'require|isNotEmpty',
        'reason'=>'require|isNotEmpty'
    ];

    protected $message = [
        'user_phone.require'   => '请填写正确手机号',
        'user_phone.isMobile'   => '请填写正确手机号',
        'user_name.require' => '必须填写真实姓名',
        'wechat.require' => '填写您常用的微信号',
        'shop_name.require' => '请给您的店铺起个名',
        'address.require' => '请填写详细地址',
        'reason.require' => '请简单描述申请理由',
    ];
}