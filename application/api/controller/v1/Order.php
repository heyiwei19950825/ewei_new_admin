<?php
/**
 * Created by Ewei..
 * Author: Ewei.
 * 微信公号：眉山同城
 * Date: 2017/2/22
 * Time: 21:52
 */

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\ExpressCompany;
use app\api\model\Order as OrderModel;
use app\api\model\OrderProduct;
use app\api\model\UserCollective;
use app\api\service\AddressService;
use app\api\service\CouponServer;
use app\api\service\Order as OrderService;
use app\api\service\Token;
use app\api\validate\IDMustBePositiveInt;
use app\api\validate\PagingParameter;
use app\api\model\OrderPayment;
use app\lib\exception\OrderException;
use app\lib\exception\SuccessMessage;

use app\api\model\GoodsCollective as GoodsCollectiveModel;
use app\api\model\UserAddress;
use app\api\model\Goods as GoodsModel;
use app\api\model\Region;
use app\api\model\Express;
use app\api\model\Shop;
use app\api\model\User;
use app\api\model\Cart as CartModel;
use app\api\service\Cart as CartService;
use app\api\model\Coupon;


use app\common\model\OrderGoodsExpress;
use app\common\model\OrderGoods;


use think\Db;
use think\Config;


class Order extends BaseController
{
    protected $beforeActionList = [
        'checkExclusiveScope' => ['only' => 'placeOrder'],
        'checkPrimaryScope' => ['only' => 'getDetail,getSummaryByUser'],
        'checkSuperScope' => ['only' => 'delivery,getSummary']
    ];

    public $uid = '';
    private $orderConfig = null;

    public function _initialize()
    {
        parent::_initialize();
        if ($_SERVER['PATH_INFO'] != '/api/v1/order/check') {
//            $this->uid = 3;
            $this->uid = Token::getCurrentUid();
            $this->orderConfig = Config::get('order');
        }
    }

    /**
     * 下单
     * @url /order
     * @HTTP POST
     */
    public function placeOrder()
    {
        //初始化变量
        $row = ['errmsg' => '', 'errno' => 0, 'data' => []];
        $payment_type = 1; //支付方式 默认微信支付
        $goodsId = $this->request->param('goodsId', 0);//商品ID
        $num = $this->request->param('num', 0);//商品数量
        $type = $this->request->param('type', 'default');//订单类型    团购  积分  普通
        $virtual = $this->request->param('virtual', 0);//判断是否是虚拟商品
        $collectiveNo = $this->request->param('collectiveNo', '');
        $addressId = $this->request->param('addressId', 0);//收货地址ID
        $buyerMessage = $this->request->param('buyer_message', '');   //下单备注
        $couponId = $this->request->param('couponId', 0);//使用的优惠券ID
        $couponInfo = [];

        //获取用户信息
        $userInfo = Db::name('user')->alias('u')->join('user_rank r', 'u.rank_id = r.rank_id', 'LEFT')->where(['u.id' => $this->uid])->find();//用户信息

        //验证并获取用户地址
        $userAddressService = new AddressService($this->uid);
        $addressOld = $userAddressService->getUserAddressAndCheck($addressId, $virtual);

        //检测是否正确返回数据
        if ($addressOld['errno'] == 1) {
            return $addressOld;
        }

        //优惠券
        if ($couponId != 0) {
            $couponInfo = CouponServer::spanCoupon($this->uid, $couponId);
        }

        if ($goodsId == 0) {//购物车购买
            $goodsPrice = $orderTotalPrice = $actualPrice = $freightPrice = $couponPrice = $couponId = $rankDiscount = $userCouponList = $shipping_company_id = $order_type = $give_point = $collectiveId = 0;
            //购物车数据粗处理
            $cartList = CartModel::getCartAll($this->uid, true);
            $cartInfo = (new CartService())->briefnessDispose($cartList,$couponInfo);
            foreach ($cartInfo as $v){
                $goodsPrice     += $userInfo['is_vip'] == 2 ? $v['price']['vip_price']:$v['price']['goods_price'];
                $freightPrice   += $v['price']['freight'];
                $couponPrice    += isset($v['price']['coupon_price'])?$v['price']['coupon_price']:0;
            }
        }
        $numberNo = 'wx_'.date("Ymd", time()) . uniqid();

        //支付记录表
        $pay_money = $goodsPrice + $freightPrice - $couponPrice;
        $paymentId = OrderPayment::createOrderPayment([
            'out_trade_no' => $numberNo,//支付订单号
            'type_alis_id' => $type,//订单类型
            'pay_body' => '',//购买数据简单记录
            'pay_detail' => '',//支付详情
            'pay_money' => $pay_money,//支付价格
            'pay_type' => 1,//支付类型  1微信支付  2 积分支付 3余额支付
            'create_time' => time(),
            'uid' => $userInfo['id']
        ]);


        //创建成功添加订单信息
        $params = [
            'type' => $type,
            'paymentType' => $payment_type,
            'buyerMessage' => $buyerMessage,
            'virtual' => $virtual,
            'couponId'=>$couponId,
            'paymentId' => $numberNo
        ];

        OrderModel::createOrder($userInfo,$addressOld,$cartInfo,$couponInfo,$params );
//        //团购添加
//        if ($type == 'collective') {
//            $data = [
//                'order_id' => $orderId,
//                'collective_no' => $collectiveNo != '' ? $collectiveNo : date("Ymd", time()) . uniqid(),
//                'uid' => $this->uid,
//                'gid' => $goodsId,
//                'num' => $product['collective']['user_number'],
//                'u_name' => $userInfo['nickname'],
//                'u_portrait' => $userInfo['portrait'],
//                'limit_time' => $product['collective']['time'],
//                'add_time' => time(),
//                'status' => 3,
//                'identity' => $collectiveNo != '' ? 0 : 1,
//            ];
//            Db::name('user_collective')->insert($data);
//            $collectiveId = Db::name('user_collective')->getLastInsID();
//        }

        $row['data'] = [
            'orderInfo' => [
                'id' => $paymentId,
            ]
        ];

        return $row;
    }

    /**
     * 获取订单详情
     * @param $id
     * @return static
     * @throws OrderException
     * @throws \app\lib\exception\ParameterException
     */
    public function getDetail($id)
    {
        $row = ['errmsg' => '', 'errno' => 0, 'data' => []];

        (new IDMustBePositiveInt())->goCheck();
        $orderDetail = OrderModel::get($id);
        if (!$orderDetail) {
            throw new OrderException();
        }
        $orderDetail['order_status_text'] = $this->orderConfig['status'][$orderDetail['order_status']];

        $address['province_name'] = Region::getRegionName($orderDetail['receiver_province']);
        $address['city_name'] = Region::getRegionName($orderDetail['receiver_city']);
        $address['district_name'] = Region::getRegionName($orderDetail['receiver_district']);
        $orderDetail['full_region'] = $address['province_name'] . $address['city_name'] . $address['district_name'];

        $orderDetail->hidden(['prepay_id']);
        $orderGoods = Db::name('order_product')->field('goods_picture,num,goods_name,vip_price,price,cost_price,sp_integral,point_exchange_type')->where(['order_id' => $orderDetail['id']])->select()->toArray();

        //快递信息
        $orderGoodsExpressModel = new OrderGoodsExpress;
        $expressRow = $orderGoodsExpressModel->where(['order_no' => $orderDetail['order_no']])->select()->toArray();
        $express = [];
        if (!empty($expressRow)) {
            foreach ($expressRow as $k => $vs) {
                $express[$k] = [
                    'express_name' => $vs['express_name'],
                    'express_company' => $vs['express_company'],
                    'express_no' => $vs['express_no'],
                    'express_time' => date('Y-m-d', $vs['shipping_time']),
                    'goods_list' => Db::name('order_product')->field('goods_name,goods_picture')->where(['goods_id' => ['in', $vs['order_goods_id_array']], 'order_id' => $orderDetail['id']])->select()->toArray()
                ];
            }
        }


        $row['data'] = [
            'orderInfo' => $orderDetail,
            'orderGoods' => $orderGoods,
            'express' => $express,
            'handleOption' => $orderDetail['order_status'] == 1 ? true : false
        ];

        return $row;
    }

    /**
     * 删除订单
     * @return [type] [description]
     */
    public function delOrder()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $row['errmsg'] = '网络异常';
            $row['errno'] = 1;
            return $row;
        }
        $row = ['errmsg' => '', 'errno' => 0, 'data' => []];
        (new IDMustBePositiveInt())->goCheck($id);
        $orderDetail = OrderModel::delOrder($id, $this->uid);
        //删除对对应商品
        OrderGoods::del($id);
        //删除团购信息
        UserCollective::del($id);

        if ($orderDetail) {
            $row['errmsg'] = '删除成功';
        } else {
            $row['errmsg'] = '网络异常';
            $row['errno'] = 1;
        }

        return $row;
    }

    /**
     * 取消订单
     * @param $id
     * @return array
     */
    public function updateStatus()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        if (empty($id)) {
            $row['errmsg'] = '网络异常';
            $row['errno'] = 1;
            return $row;
        }
        $row = ['errmsg' => '', 'errno' => 0, 'data' => []];

        $params = [
            'id' => $id,
            'uid' => $this->uid,
            'status' => $status
        ];
        $orderDetail = OrderModel::updateOrderStatic($params);

        if ($orderDetail) {
            $row['errmsg'] = '操作成功';
        } else {
            $row['errmsg'] = '网络异常';
            $row['errno'] = 1;
        }

        return $row;
    }

    /**
     * 根据用户id分页获取订单列表（简要信息）
     * @param int $page
     * @param int $size
     * @return array
     * @throws \app\lib\exception\ParameterException
     */
    public function getSummaryByUser($page = 1, $size = 15)
    {
        $row = ['errmsg' => '', 'errno' => 0, 'data' => []];
        $type = $this->request->param('types');
//        (new PagingParameter())->goCheck();
        $uid = Token::getCurrentUid();
        $param['type'] = $type;
        $pagingOrders = OrderModel::getSummaryByUser($param, $uid, $page, $size);

        if (empty($pagingOrders)) {
            $row = ['errmsg' => '暂无数据', 'errno' => 1, 'data' => [
                'list' => [],
                'types' => $type
            ]];
        }

        $row['data'] = [
            'list' => $pagingOrders,
            'types' => $type
        ];

        return $row;


    }

    /**
     * 获取全部订单简要信息（分页）
     * @param int $page
     * @param int $size
     * @return array
     * @throws \app\lib\exception\ParameterException
     */
    public function getSummary($page = 1, $size = 20)
    {
        (new PagingParameter())->goCheck();
//        $uid = Token::getCurrentUid();
        $pagingOrders = OrderModel::getSummaryByPage($page, $size);
        if ($pagingOrders->isEmpty()) {
            return [
                'current_page' => $pagingOrders->currentPage(),
                'data' => []
            ];
        }
        $data = $pagingOrders->hidden(['snap_items', 'snap_address'])
            ->toArray();
        return [
            'current_page' => $pagingOrders->currentPage(),
            'data' => $data
        ];
    }

    public function delivery($id)
    {
        (new IDMustBePositiveInt())->goCheck();
        $order = new OrderService();
        $success = $order->delivery($id);
        if ($success) {
            return new SuccessMessage();
        }
    }

    /**
     * 定时任务检测订单状态
     */
    public function checkOrderStatus()
    {
        OrderModel::checkAndDelOrder();
    }
}






















