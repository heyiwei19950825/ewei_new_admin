<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/2/1
 * Time: 1:06
 */

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\UserAddress;
use app\api\model\Goods as GoodsModel;
use app\api\model\Region;
use app\api\model\Express;

use app\api\service\Token;

class Integral extends BaseController
{

    public $uid = '';

    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
    }


    /**
     * 积分购物车
     */
    public function IntegralCart(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $goodsId = $this->request->param('goodsId',0);
        $num = $this->request->param('num',0);

        //收货地址
        $userAddressModel = new UserAddress();
        $address = $userAddressModel->where([
            'user_id'=>$this->uid,
            'is_default'=>1
        ])->find();
        if( !empty($address) ){
            $address['province_name'] = Region::getRegionName($address['province_id']);
            $address['city_name'] = Region::getRegionName($address['city_id']);
            $address['district_name'] = Region::getRegionName($address['district_id']);
            $address['full_region'] = $address['province_name'] . $address['city_name'] . $address['district_name'];
        }else{
            $address = ['id'=>0];
        }

        $field = 'name,is_integral,sp_integral,thumb,eid';
        $product = GoodsModel::getProductDetail($goodsId,$field);
        $product['thumb'] = self::prefixDomain($product['thumb']);

        //判断是否是积分购买
        if($product['is_integral'] == 0 ){
            $row['errmsg'] = '非法请求';
            $row['errno']  = 10001;

            return  $row;
        }
        $actualPrice = $product['sp_integral'] * $num;
        //运费
        $express = Express::getDetail($product['eid'],'cost,company_name');

        $row['data'] = [
            'checkedAddress' =>$address,//收货地址
            'freightPrice'   => $express['cost'],//运费
            'actualPrice'    => $actualPrice,//最后的价格
            'product'        => $product    //商品信息

        ];
        return $row;
    }
}


