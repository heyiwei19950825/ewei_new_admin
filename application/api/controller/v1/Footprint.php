<?php
/**
 * 浏览历史记录
 * User: heyiw
 * Date: 2018/1/28
 * Time: 21:28
 */

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\service\Token;
use app\api\model\Footprint as FootprintModel;


class Footprint extends BaseController
{
    public $uid = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
    }

    /**
     * 获取用户浏览历史记录
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    public function getList(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $data = FootprintModel::getList($this->uid);
        if( isset( $data[0]) ){
            $data[0] = self::prefixDomainToArray('list_pic_url',$data[0]);
        }
        if( isset( $data[1]) ) {
            $data[1] = self::prefixDomainToArray('list_pic_url', $data[1]);
        }

        $row['data'] = $data;

        return $row;
    }
}