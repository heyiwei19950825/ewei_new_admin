<?php
/**
 * Created by Ewei.
 * User: Ewei.
 * Date: 2017/2/18
 * Time: 12:35
 */
namespace app\api\validate;

class ThemeProduct extends BaseValidate
{
    protected $rule = [
        't_id' => 'number',
        'p_id' => 'number'
    ];
}
