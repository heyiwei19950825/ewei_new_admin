<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城

 * Date: 2017/2/25
 * Time: 19:25
 */

namespace app\api\validate;


use app\lib\exception\ParameterException;
use think\Exception;

class Cart extends BaseValidate
{
    protected $rule = [
        'products' => 'checkProducts'
    ];

    protected $singleRule = [
        'product_id' => 'require|isPositiveInteger',
        'count' => 'require|isPositiveInteger',
    ];

    protected function checkProducts($values)
    {
        if(empty($values)){
            throw new ParameterException([
                'msg' => '商品列表不能为空'
            ]);
        }
        foreach ($values as $value)
        {
            $this->checkProduct($value);
        }
        return true;
    }

    private function checkProduct($value)
    {
        $validate = new BaseValidate($this->singleRule);
        $result = $validate->check($value);
        if(!$result){
            throw new ParameterException([
                'msg' => '商品列表参数错误',
            ]);
        }
    }
}