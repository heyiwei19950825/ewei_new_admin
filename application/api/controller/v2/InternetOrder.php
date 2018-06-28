<?php
/**
 * 网吧订单
 * User: heyiw
 * Date: 2018/3/21
 * Time: 10:16
 */

namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\model\User;
use app\common\lib\Helper;
use app\common\model\InternetOrder as InternetOrderModel;
use app\api\service\Token;
use app\common\server\InternetServer;

use think\Db;
class InternetOrder  extends BaseController
{
    private $uid = '';
    public function _initialize(){
        parent::_initialize();
        $this->uid = Token::getCurrentUid();
//        $this->uid = 9;
        User::verifyAttestation($this->uid);
    }

    /**
     * 创建订单
     */
    public function addOrder(){
        if( $this->request->isPost()){
            $userInfo = User::get([
                'id' => $this->uid
            ]);
            //增加下单数量
            Db::name('statistics')->where(['uid'=>1])->setInc('place_order_number');
            $params = $this->request->param('data');
            $coupon = $this->request->param('coupon',0);
            if( empty($params)){
                $this->error('请选择预定座位');
            }
            $params =json_decode($params);
            $orderMoney = 0;
            $order_no = date('YmdHis',time()).rand(1000,9999);
            $machine = [];
            foreach ($params as $key=>$item) {

                $row = Db::name('internet_machine')->where([
                    'status'=> 0,
                    'number' => $key
//                    'order_no' => '',
//                    'lock_time' => ''
                ])->find();
                if( empty($row) ){
                    $this->error($key.'机器已被预定，请换一台');
                }
                $row = InternetServer::selectMachineRelevance($key);
                $price = ($row['price']*10) * ($item*10);
                $orderMoney += $price;
                $machine[] = [
                    'uid' => $this->uid,
                    'order_no' => $order_no,
                    'machine_number' => $key,
                    'duration' => $item,
                    'price' =>  $row['price'],
                    'pay_price' =>  round($price/100,1),
                    'preferential_price' => round($price/100,1),
                    'status' => 0,
                    'add_time' => time(),
                ];
            }

            if($coupon == 0 ){
                $coupon_price = 0;
            }

            $orderMoney = round($orderMoney/100,1);
            $data= [
                'uid' => $this->uid,//用户ID
                'name'=> $userInfo['username'],//用户真实姓名
                'order_no'=> $order_no,//订单号
                'price' => $orderMoney,//真实价格
                'preferential_price' => 0,//优惠价格
                'coupon' => $coupon,//优惠券编号
                'coupon_price' => $coupon_price,//优惠券价格
                'rank' => $userInfo['rank_id'],//会员等级
                'rank_preferential_price' => 0,//会员优惠价格
                'order_money' => $orderMoney,//订单价格
                'pay_price' => 0,//订单价格 微信支付价格
                'pay_time' => 0,//支付时间
                'add_time' => time(),//订单创建时间
                'status' => 0,//状态,
                'check_status' => 0, //检测数据使用,
                'code' => Helper::createCode(8),
            ];

            //事务开始
            Db::startTrans();
            try{
                Db::name('internet_order')->insert($data);
                Db::name('internet_order_machine')->insertAll($machine);
                // 提交事务
                Db::commit();
                $data = [
                    'order_no' => $order_no,
                    'price' => $orderMoney
                ];

                return [
                    'code' => 1,
                    'msg' => '下单成功',
                    'data' => $data
                ];

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error('下单失败');
            }
        }else{
            $this->error('下单失败');
        }
    }

    /**
     * 查询订单
     */
    public function orderList(){
        $status = $this->request->param('status','');
        $map['uid'] = $this->uid;
        if( $status != '' ){
            $map['status'] = $status;
        }
        $internetOrderModel = new InternetOrderModel();
        //删除未支付订单
        $internetOrderModel->where([
            'status' => 0
        ])->delete();
        //查询订单
        $row = $internetOrderModel->getOrderList(1, 10, $map, 'add_time desc', 'order_no,status,order_money,add_time,code');
//        $row =  Db::name('internet_order')
//            ->field('order_no,status,order_money,add_time')
//            ->where($map)
//            ->order('add_time desc')
//            ->select()->toArray();
        $row = $row['data'];
        //循环查询对应座位信息
        foreach ($row as $k=>$v){
            $machineArray = [];
            $machineList = Db::name('internet_order_machine')
                ->where( [
                    'order_no' =>$v['order_no']
                ] )
                ->select()->toArray();
            foreach ($machineList as $mV){
                $machineArray[] = [
                    'number' => $mV['machine_number'],
                    'duration' => $mV['duration'],
                ];
            }
            $row[$k]['add_time'] = date('Y-m-d H:i:s',$row[$k]['add_time']);
            $row[$k]['machineList'] = $machineArray;
            $orderStatus = config('internet.orderStatus');
            $row[$k]['status'] = $orderStatus[ $row[$k]['status']];
        }
        if( !empty($row) ){

            $this->success('查询成功','',$row);
        }else{
            $this->error('没有更多数据');
        }
    }


}