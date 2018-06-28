<?php
/**
 * 用户收货地址数据处理
 * User: heyiw
 * Date: 2018/5/4
 * Time: 20:28
 */

namespace app\api\service;

use app\api\model\Region;
use app\api\model\UserAddress;
use think\Db;

class AddressService
{

    protected $userInfo;
    protected $uid;

    public function __construct($uid)
    {
        $this->uid = $uid;
        $this->userInfo = Db::name('user')->alias('u')->join('user_rank r','u.rank_id = r.rank_id','LEFT')->where(['u.id'=>$uid])->find();//用户信息
    }

    /**
     * @param int $id  收货地址ID   没有默认为0
     * @param int $virtual 手否是虚拟商品  0 否   1 是
     * @return array
     */
    public function getUserAddressAndCheck( $id = 0 ,$virtual =0 ){
        //收货地址
        $userAddressModel = new UserAddress();
        if( $id != 0 ){
            $addressMap = ['id'=>$id,'user_id'=>$this->uid];
        }else{
            $addressMap = ['is_default'=>1,'user_id'=>$this->uid];
        }
        //初始化收货地址数据
        $userAddress = $userAddressModel->where($addressMap)->field('name,mobile,province_id,city_id,district_id,address,is_default')->find();
        if($userAddress != NULL){
            $addressOld = $userAddress->toArray();
        }
        //没有添加收货地址
        if( empty($addressOld)){
            if($virtual != 1 ){
                $row = ['errmsg'=>'请选择或创建收货地址','errno'=>1,'data'=>[]];
                return $row;
            }else{//虚拟商品没有收货地址
                $addressOld['mobile'] = $this->userInfo['mobile'];
                $addressOld['province_id'] = 0;
                $addressOld['city_id'] = 0;
                $addressOld['district_id'] = 0;
                $addressOld['address'] = $this->userInfo['city']==NULL?'':$this->userInfo['city'];
            }
        }

        //重新编排数据
        if( !empty($userAddress) ){
            $address['province_name']   = Region::getRegionName($addressOld['province_id']);
            $address['city_name']       = Region::getRegionName($addressOld['city_id']);
            $address['district_name']   = Region::getRegionName($addressOld['district_id']);
            $address['full_region']     = $address['province_name'] . $address['city_name'] . $address['district_name'];
            $address['name']            = $userAddress['name'];
            $address['mobile']          = $userAddress['mobile'];
            $address['info']          = $addressOld;
        }else{
            $address = ['id'=>0];
        }
        $address['errno']   = 0;

        return $address;
    }
}