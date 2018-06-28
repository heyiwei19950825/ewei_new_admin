<?php
/**
 * Created by Ewei.
 * Author: Ewei.
 * Date: 2017/2/18
 * Time: 13:47
 */

namespace app\lib\exception;


class ProductException extends BaseException
{
    public $errno = 20000;
    public $errmsg = '指定商品不存在，请检查商品ID';
}