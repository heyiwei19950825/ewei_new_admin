<?php
/**
 * 优惠券
 * User: heyiw
 * Date: 2018/1/28
 * Time: 18:15
 */

namespace app\api\model;
use app\api\model\User;
use think\Db;

class Coupon extends BaseModel
{
    /**
     * 统计用户的优惠券总数
     * @param $uid
     * @return int
     */
    public static function countCoupon( $uid ){
        $nowTime = date('Y-m-d H:i:s',time());
        $map['c.start_time'] = ['<=',$nowTime];
        $map['c.end_time'] = ['>=',$nowTime];
        $map['c.state'] = ['=',0];
        $map['c.uid'] = ['=',$uid];
        $map['t.is_delete'] = ['=',0];
        $row = Db::name('coupon')->alias('c')->join('coupon_type t','t.coupon_type_id = c.coupon_type_id')->where($map)->count('coupon_id');
        return $row;
    }

    /**
     * 查询用户的优惠券信息
     * @param $uid
     * @return array
     */
    public static function userCoupon( $uid=-1,$type=0 ){
        $now =date('Y-m-d H:i:s',time());
        $datas =[];
        //获取优惠券列表
        $data = Db::name('coupon')->alias('c')
            ->join('coupon_type t','t.coupon_type_id = c.coupon_type_id','LEFT')
            ->where([
                'uid'=>$uid,
                'state' => $type
            ])->field(
                'c.start_time,c.end_time,coupon_name,c.coupon_id,t.coupon_type_id'
            )->select()->toArray();
        //获取优惠券对应的商品
        foreach ( $data as $item) {
            $names = '';
            $goodsRow = Db::name('coupon_goods')->alias('c')
                ->join('goods g','c.goods_id = g.id','LEFT')
                ->where([
                    'coupon_type_id' => $item['coupon_type_id']
                ])
                ->field('g.name,g.btime,g.etime,g.status')
                ->select()->toArray();
            if( empty($goodsRow) ){
                $item['goods_list'] = 'all';
            }else{
                foreach ($goodsRow as $items){
                    if( $items['btime'] > $now || $items['etime'] < $now || $items['status'] == 0 ){
                        unset($items);
                    }else{
                        $names .= $items['name'].';';
                    }

                }
            }
            $item['goods_name'] = trim($names,';');
            $datas[] = $item;
        }

        return $datas;
    }

    /**
     * 获取用户对应商品可使用的优惠券
     * @param int $uid
     * @param array $cartListId
     * @param int $totalPrice
     * @return array
     */
    public static function useCoupon( $uid = -1 ,$cartListId = [],$totalPrice = 0){
        $names =  '';
        $datas =[];
        $now =date('Y-m-d H:i:s',time());

        //获取优惠券列表
        $data = Db::name('coupon')->alias('c')
            ->join('coupon_type t','t.coupon_type_id = c.coupon_type_id','LEFT')
            ->where([
                'uid'=>$uid,
                'state' => 0,
                't.is_delete' => 0
            ])->field(
                'c.start_time,c.end_time,coupon_name,c.coupon_id,t.coupon_type_id,c.money,t.need_user_level,t.at_least'
            )->select()->toArray();

        //获取优惠券对应的商品
        foreach ( $data as &$item) {
            $goodsRow = Db::name('coupon_goods')
                ->where([
                    'coupon_type_id' => $item['coupon_type_id']
                ])
                ->field('goods_id')
                ->select()->toArray();
            //检测变量
            $isOk = false;
//            if($item['need_user_level']<= $user['rank_id']){
                if( $goodsRow != []){
                    //优惠券是否在购物车商品列表中
                    foreach ($goodsRow as $gItem) {
                        if( in_array($gItem['goods_id'],$cartListId) && $item['at_least'] <= $totalPrice ){
                            $isOk = true;
                        }
                    }
                    $item['is_ok'] = $isOk;
                }else{
                    if( ($item['at_least']+0) <= $totalPrice){
                        $item['is_ok'] = true;
                    }
                }
//            }

        }

        //获取优惠券对应的商品
        foreach ( $data as $item) {
            $goodsRow = Db::name('coupon_goods')->alias('c')
                ->join('goods g','c.goods_id = g.id','LEFT')
                ->where([
                    'coupon_type_id' => $item['coupon_type_id']
                ])
                ->field('g.name,g.btime,g.etime,g.status')
                ->select()->toArray();

            $map['g.btime']   = ['<=',$now];
            $map['g.etime']   = ['>=',$now];
            $map['g.status']  = ['=',1];


            if( empty($goodsRow) ){
                $item['goods_list'] = 'all';
            }else{
                foreach ($goodsRow as $items){
                    if( $items['btime'] > $now || $items['etime'] < $now || $items['status'] == 0 ){
                        unset($items);
                    }else{
                        $names .= $items['name'].';';
                    }
                }
            }

            $item['goods_name'] = trim($names,';');
            if( $names != '' ){
                $datas[] = $item;
            }
        }

        return $data;
    }
    /**
     * 获取在线优惠券列表
     * @return array
     */
    public static function getCouponList($limit=4){

        $map['start_time']      = ['<=',time()];
        $map['end_time']        = ['>=',time()];
        $map['is_show']         = ['=',1];
        $map['is_delete']       = ['=',0];
        $row = Db::name('coupon_type')
            ->field('coupon_type_id,coupon_name,money,count,max_fetch,at_least,need_user_level,range_type,start_time,end_time')
            ->where($map)
            ->limit($limit)
            ->select()->toArray();
        foreach ($row as &$item) {
            $item['start_time'] = date('Y-m-d',$item['start_time']);
            $item['end_time']   = date('Y-m-d',$item['end_time']);
            $item['range_type']   = $item['range_type']==0?'部分商品可用':'全商品可用';
        }

        return $row;
    }

    /**
     * 获取优惠券信息
     * @param int $id
     * @return array
     */
    public static function getInfoById( $uid,$id = -1 ){
        //初始化查询条件
        $now =date('Y-m-d H:i:s',time());
        $map['start_time']      = ['<=',$now];//开始时间
        $map['end_time']        = ['>=',$now];//结束时间
        $map['state']           = ['=',0];//优惠券状态
        $map['coupon_id']       = ['=',$id];//优惠券ID
        $map['uid']             = ['=',$uid];//用户ID

        //查询用户优惠券数据
        $row                    = Db::name('coupon')->field('coupon_id,coupon_type_id')->where($map)->find();
        $data['id']             = $row['coupon_id'];
        //查询优惠券数据
        $data                   = Db::name('coupon_type')->field('coupon_name,money,coupon_type_id')->where(['coupon_type_id'=>$row['coupon_type_id']] )->find();

        //查询优惠券对应的商品数据
        $goods                  = Db::name('coupon_goods')->where(['coupon_type_id'=>$data['coupon_type_id']])->field('goods_id')->select()->toArray();
        if(!empty($goods)){
            foreach ($goods as $v){
                $data['goods'][]  = $v['goods_id'];
            }
        }

        return $data;
    }

    //用户领取优惠券
    public static function addUserCoupon( $uid = -1,$cid = -1  ){
        //查询当前优惠券信息
        $map['start_time']      = ['<=',time()];
        $map['end_time']        = ['>=',time()];
        $map['coupon_type_id']  = ['=',$cid];

        Db::startTrans();

        try{
            $coupon = Db::name('coupon_type')->where($map)->find();

            //判断是否达到领取上线
            if( $coupon['get_number'] >= $coupon['count']){
                return '优惠券已过期';
            }

            //判断用户领取最大领取个数
            if( $coupon['max_fetch'] != 0 ){
                $userGetNumber = Db::name('coupon')->where(['uid'=>$uid,'coupon_type_id'=>$cid])->count();
                if( $userGetNumber >= $coupon['max_fetch']){
                        return '您已领取过啦';
                }
            }
            //判断用户等级是否符合
            $user = Db::name('user')->where(['id'=>$uid])->field('rank_id')->find();

            if( $user['rank_id'] > $coupon['need_user_level']){
                return '级别不够';
            }

            //领取优惠券
            Db::name('coupon')->where([
                'get_status'=> 0,
                'coupon_type_id'=>$cid
            ])->limit(1)->update([
                'uid'=>$uid,
                'get_status'=>1,
                'start_time'=>date('Y-m-d H:i:s',$coupon['start_time']),
                'end_time'=>date('Y-m-d H:i:s',$coupon['end_time']),
                'fetch_time'=>date('Y-m-d H:i:s',time())
            ]);

            //修改优惠券领取数量
            Db::name('coupon_type')->where(['coupon_type_id'=>$cid])->setInc('get_number',1);

            Db::commit();
            return true;
        }catch (\Exception $e) {

            Db::rollback();
            return false;
        }
    }

    public static function updateCoupon($id){
        Db::name('coupon')->where(['coupon_id'=>$id])->update([
            'use_time'=>time(),
            'state' => 1
        ]);
    }


    public static function briefnessDispose(){

    }

}