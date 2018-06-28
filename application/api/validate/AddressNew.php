<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城

 * Date: 2017/2/23
 * Time: 3:01
 */

namespace app\api\validate;


class AddressNew extends BaseValidate
{
    // 为防止欺骗重写user_id外键
    // rule中严禁使用user_id
    // 获取post参数时过滤掉user_id
    // 所有数据库和user关联的外键统一使用user_id，而不要使用uid
    protected $rule = [
        'name' => 'require|isNotEmpty',
        'mobile' => 'require|isMobile',
        'province_id' => 'require|isNotEmpty',
        'city_id' => 'require|isNotEmpty',
        'district_id' => 'require|isNotEmpty',
        'address' => 'require|isNotEmpty',
        'is_default'=>'require'
    ];

    protected $message = [
        'mobile.isMobile'   => '请填写正确手机号',

    ];
}