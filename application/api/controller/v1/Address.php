<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城

 * Date: 2017/2/23
 * Time: 2:56
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Region;
use app\api\model\User;
use app\api\model\UserAddress;
use app\api\service\Token;
use app\api\service\Token as TokenService;
use app\api\validate\AddressNew;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;
use think\Controller;
use think\Exception;
use think\Db;

class Address extends BaseController
{
    /**
     * 获取用户地址信息
     * @return array
     * @throws UserException
     */
    public function getUserAddress(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $uid = Token::getCurrentUid();
        $id = $this->request->param('id');
        if( $id === 0 ){
            $row['data'] = null;
            return $row;
        }

        if( !empty($id) ){//单个详情查询
            $userAddress = UserAddress::where([ 'id'=>$id] )->find()->toArray();
        }else{//列表查询
            $userAddress = UserAddress::where('user_id', $uid)->select();
        }

        if(!$userAddress){
            throw new UserException([
               'msg' => '用户地址不存在',
                'errorCode' => 60001
            ]);
        }
        if( !empty($id) ) {
            $userAddress['province_name'] = Region::getRegionName($userAddress['province_id']);
            $userAddress['city_name'] = Region::getRegionName($userAddress['city_id']);
            $userAddress['district_name'] = Region::getRegionName($userAddress['district_id']);
            $userAddress['full_region'] = $userAddress['province_name'] . $userAddress['city_name'] . $userAddress['district_name'];
        }else{
            foreach ($userAddress as $key => &$item) {
                $item['province_name'] = Region::getRegionName($item['province_id']);
                $item['city_name'] = Region::getRegionName($item['city_id']);
                $item['district_name'] = Region::getRegionName($item['district_id']);
                $item['full_region'] = $item['province_name'] . $item['city_name'] . $item['district_name'];
            }
        }

        $row['data'] = $userAddress;

        return $row;
    }

    /**
     * 更新或者创建用户收获地址
     */
    public function createOrUpdateAddress()
    {
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $validate = new AddressNew();
        $validate->goCheck();
        $uid = TokenService::getCurrentUid();
        $user = User::get($uid);
        $id = $this->request->param('id');//收货地址ID

        if(!$user){
            throw new UserException([
                'code' => 404,
                'msg' => '用户收获地址不存在',
                'errorCode' => 60001
            ]);
        }
        // 根据规则取字段是很有必要的，防止恶意更新非客户端字段
        $data = $validate->getDataByRule(input('post.'));
        $data['is_default'] =  $data['is_default'] + 0;
        if( $data['is_default'] == 1 ){
            userAddress::update(['is_default'=>0],['is_default'=>1],'');
        }
        if ( $id == 0  )
        {
            $data['user_id'] = $uid;
            if($data['is_default'] == 1){
                userAddress::update(['is_default'=>0],['is_default'=>1],'');
            }
            // 关联属性不存在，则新建
            $createRow = userAddress::create($data);
            $row['data'] = $createRow->id;
            return $row;
        }
        else
        {
            // 存在则更新
//            fromArrayToModel($user->address, $data);
            // 新增的save方法和更新的save方法并不一样
            // 新增的save来自于关联关系
            // 更新的save来自于模型
            userAddress::update($data,['id'=>$id]);
        }
        //        return new SuccessMessage();
        $row['data'] = [];

        return $row;
    }

    /**
     * 删除收货地址
     * @param $id
     * @return array
     */
    public function deleteAddress($id){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $uid = Token::getCurrentUid();

        try{
            Db::name('user_address')->where(
                [
                    'id'=> $id,
                    'user_id'=> $uid
                ]
            )->delete();
        }catch ( Exception $e ){
            return $e->getMessage();
        }

        return $row;
    }
}