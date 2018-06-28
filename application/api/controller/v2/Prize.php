<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/22
 * Time: 4:19
 */

namespace app\api\controller\v2;


use app\api\controller\BaseController;
use app\api\model\User;
use app\api\service\Token;
use app\common\lib\Helper;
use app\common\model\Goods as GoodsModel;

use think\Db;

class Prize extends BaseController
{
    private  $uid = '';
    private $goods = '';

    public function _initialize(){
        parent::_initialize();
        $this->goods = new GoodsModel();

        $this->uid = Token::getCurrentUid();
//        $this->uid = 9;
    }

    public function rule(){
        $uid = $this->request->param('uid',1);
        $prize = Db::name('prize')->where([
            'uid'=>$uid
        ])->find();
        if($prize){
            $rule = json_decode($prize['rule'],true);
            $integral = $prize['integral'];
            foreach ( $rule  as $k => &$v){
                $map['id'] = $v['goods_id'];
                $goodsInfo = $this->goods->getGoodsList($map,false,0,0,'id,name,content,thumb,sp_integral,need_rank')->toArray();
                //奖项设置为空的话直接返回活动还未开始
                if( empty($goodsInfo) ){
                    $data = [
                        'integral' => 0,
                    ];
                    $this->error('活动还未开始','',$data);

                }
                $goodsInfo[0]['thumb'] = $this->prefixDomain($goodsInfo[0]['thumb']);
                $v['goodsInfo'] = $goodsInfo[0];
            }
        }
        $data = [
            'integral' => $integral,
            'rule' =>$rule
        ];
        $this->success('查询成功','',$data);
    }

    public function win(){
        if( $this->request->isPost()){
//            $gid = $this->request->param('gid',0);
            $uid = $this->request->param('uid',1);

            $prize = Db::name('prize')->where([
                'uid'=>$uid
            ])->find();
            $prize_arr = json_decode($prize['rule'],true);
            foreach ($prize_arr as $k=>$v){
                $goods = Db::name('goods')->where([
                    'id'=>$v['goods_id']
                ])->field('name,content,sp_integral')->find();
                $prize_arr[$k]['prize'] = $goods;
            }
            /*
             * 每次前端页面的请求，PHP循环奖项设置数组，
             * 通过概率计算函数get_rand获取抽中的奖项id。
             * 将中奖奖品保存在数组$res['yes']中，
             * 而剩下的未中奖的信息保存在$res['no']中，
             * 最后输出json个数数据给前端页面。
             */
            foreach ($prize_arr as $key => $val) {
                $arr[$key] = $val['rule_odds'];
            }

            $rid = $this->get_rand($arr); //根据概率获取奖项id

            $res['yes'] = $prize_arr[$rid]['prize']['name']; //中奖项
            $yes = $prize_arr[$rid];

            unset($prize_arr[$rid-1]); //将中奖项从数组中剔除，剩下未中奖项
            shuffle($prize_arr); //打乱数组顺序
            for($i=0;$i<count($prize_arr);$i++){
                $pr[] = $prize_arr[$i]['prize'];
            }
//            $res['no'] = $pr;

            $integral = $prize['integral'];
            $userInfo = User::get(['id'=>$this->uid])->toArray();

            if($userInfo['integral'] < $integral){
                $this->error('账户积分不足');
            }

            $row = Db::name('user_win')->insert([
               'gid'=>$yes['goods_id'],
                'uid'=>$this->uid,
                'code'=>'WIN_'.Helper::createCode(8),
                'add_time' => time()
            ]);

            if( $row ){
                //抽奖消费积分 记录日志
                User::updateUserIntegral($this->uid,$integral,0,'抽奖消费积分');
                $res['key'] = $rid;
                $this->success('恭喜中奖','',$res);
            }else{
                $this->error('网络异常请联系管理员');
            }
        }
    }

    /**
     * 检测用户是否抽过奖
     */
    public function check(){
        if($this->request->isPost()){
            $row = Db::name('user_number')->where([
                'key'=>$this->request->param('key'),
                'uid'=>$this->uid
            ])->find();
            if( $row ){
                $this->success('已经参加过活动','',$row['award_number']);
            }else{
                $this->success('未参加过活动');
            }
        }else{
            $this->error('网络异常');
        }

    }

    /*
     * 经典的概率算法，
     * $proArr是一个预先设置的数组，
     * 假设数组为：array(100,200,300，400)，
     * 开始是从1,1000 这个概率范围内筛选第一个数是否在他的出现概率范围之内，
     * 如果不在，则将概率空间，也就是k的值减去刚刚的那个数字的概率空间，
     * 在本例当中就是减去100，也就是说第二个数是在1，900这个范围内筛选的。
     * 这样 筛选到最终，总会有一个数满足要求。
     * 就相当于去一个箱子里摸东西，
     * 第一个不是，第二个不是，第三个还不是，那最后一个一定是。
     * 这个算法简单，而且效率非常 高，
     * 关键是这个算法已在我们以前的项目中有应用，尤其是大数据量的项目中效率非常棒。
     */
    function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }


}