<?php
/**
 * 收藏
 * User: heyiw
 * Date: 2018/1/28
 * Time: 22:45
 */

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\service\Token;
use app\api\model\Collect as CollectModel;

class Collect extends BaseController
{
    public $uid = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
    }

    /**
     * 收藏列表
     * @return array
     */
    public function getList(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $row['data'] = CollectModel::getLit( $this->uid );
        $row['data'] = self::prefixDomainToArray('thumb',$row['data']);

        return $row;
    }

    /**
     * 添加收藏
     * @return array
     */
    public function addOrDelete(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $type = $this->request->param('typeId');
        $gid = $this->request->param('valueId');

        $row['data'] = CollectModel::addOrDelete( $this->uid,$gid,$type );

        return $row;
    }
}