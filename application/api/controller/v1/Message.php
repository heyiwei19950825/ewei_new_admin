<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/7
 * Time: 19:05
 */

namespace app\api\controller\v1;


use app\api\service\WxMessage;

class Message extends WxMessage
{

    public function send(){
        $this->sendMessage('oc6_40GtOEWKF7_89G6Kvf7A9vaQ');
    }

}