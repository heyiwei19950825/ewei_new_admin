<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/8/15
 * Time: 23:20
 */

namespace app\lib\exception;


use think\exception\Handle;
use Exception;

class AdminExceptionHandler extends Handle
{
    public function render(Exception $e)
    {
        if(config('app_debug')){
            // 调试状态下需要显示TP默认的异常页面，因为TP的默认页面
            // 很容易看出问题
            return parent::render($e);
        }else{
            return view(config('http_exception_template.404'));
        }
    }
}