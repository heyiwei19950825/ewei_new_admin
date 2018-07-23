<?php
namespace app\admin\controller;

use app\common\controller\AdminBase;
use think\Db;

/**
 * 后台首页
 * Class Index
 * @package app\admin\controller
 */
class Index extends AdminBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        $statistics =[];

        if($this->admin_id != 1 ){
            $map = ['s_id'=>$this->admin_id];
            $where = 'where s_id = '.$this->admin_id.' and ';
            $groupWhere = 'where s_id = '.$this->admin_id;
        }else{
            $map = [];
            $statistics['shop'] = Db::name('auth_group_access')->where([
                'group_id' => 2
            ])->count();
            $where = ' where ';
            $groupWhere = '';

        }

        //查询今天预约订单数量
        $statistics['user'] = Db::name('user')->where($map)->count();
        $toDayBookSql = 'SELECT count(id) as number FROM os_book '.$where.' DATEDIFF(time,NOW())=0';
        $statistics['today_book'] =  Db::query($toDayBookSql)[0]['number'];

        //统计当月预约订单数量
        $monthSql = 'SELECT count(id) as number FROM os_book '.$where.' DATE_FORMAT( time, \'%Y%m\' ) = DATE_FORMAT( CURDATE( ) , \'%Y%m\' )';
        $statistics['monthSql_book'] =  Db::query($monthSql)[0]['number'];

        //统计访问数量
        $statistics['visit'] =  Db::name('statistics')->where($map)->sum('visit');

        //技师数量
        $statistics['technician'] = Db::name('technician')->where($map)->count('id');


        //今天
        $timeMap['toDay'] = [
            [
                ">",
                date('Y-m-d',time())
            ],
            [
                "<",
                date('Y-m-d',strtotime('+1 day'))
            ]
        ];
        //本月
        $timeMap['month'] = [
            [
                ">",
                date('Y-m',time())
            ],
            [
                "<",
                date('Y-m',strtotime('+1 month'))
            ]
        ];
        //本年
        $timeMap['year'] = [
            [
                ">",
                date('Y',time())
            ],
            [
                "<",
                date('Y',strtotime('+1 year'))
            ]
        ];
        foreach( $timeMap as $k=>$v){
            $userRechargeMap['time'] =  $v;
            //充值统计
            $user_recharge= Db::name('user_deal')
                ->field('count(id) as number,sum(money) as money,sum(give_integral) as give_integral,sum(give_money) as give_money')
                ->where($map)
                ->where([
                    'deal_type' => 2
                ])
                ->where($userRechargeMap)->select();
            $statistics['user_recharge'][$k] = $user_recharge[0];

            //消费统计
            $user_deal = Db::name('user_deal')
                ->field('count(id) as number,sum(money) as money,sum(give_integral) as give_integral,sum(give_money) as give_money')
                ->where($map)
                ->where([
                    'deal_type' => 3
                ])
                ->where($userRechargeMap)->select();
            $statistics['user_deal'][$k] = $user_deal[0];

            //办卡统计
            $user_open= Db::name('user_deal')
                ->field('count(id) as number,sum(money) as money,sum(give_integral) as give_integral,sum(give_money) as give_money')
                ->where($map)
                ->where([
                    'deal_type' => 1
                ])
                ->where($userRechargeMap)->select();
                $statistics['user_open'][$k] = $user_open[0];

        }

        //预约订单数
        foreach( $timeMap as $k=>$v) {
            $userRechargeMap['time'] = $v;
            //充值统计
            $userRechargeModel = Db::name('book')
                ->where($map)
                ->where($userRechargeMap);
            $statistics['book'][$k] = [
                'count' => $userRechargeModel->count('id'),
                'sum'  =>  $userRechargeModel->sum('price')
            ];
        }
        //按照天查询订单数量
        $daysSql = 'select DATE_FORMAT(time,\'%Y%m%d\') days,count(id) count from os_book '.$groupWhere.'  group by days ';
        $daysSumSql = 'select DATE_FORMAT(time,\'%Y%m%d\') days,sum(price) count from os_book '.$groupWhere.'  group by days ';
        $statistics['book_days']['count'] =  Db::query($daysSql);
        $statistics['book_days']['sum'] =  Db::query($daysSumSql);
        //按照月查询订单数量
        $monthSql = 'select DATE_FORMAT(time,\'%Y%m\') months,count(id) count from os_book '.$groupWhere.'  group by months ';
        $monthSumSql = 'select DATE_FORMAT(time,\'%Y%m\') months,sum(price) count from os_book '.$groupWhere.'  group by months ';
        $statistics['book_month']['count'] =  Db::query($monthSql);
        $statistics['book_month']['sum'] =  Db::query($monthSumSql);

        //按照年查询订单数量
        $yearSql = 'select DATE_FORMAT(time,\'%Y\') years,count(id) count from os_book '.$groupWhere.'  group by years ';
        $yearSumSql = 'select DATE_FORMAT(time,\'%Y\') years,sum(price) count from os_book '.$groupWhere.'  group by years ';
        $statistics['book_year']['count'] =  Db::query($yearSql);
        $statistics['book_year']['sum'] =  Db::query($yearSumSql);


        //余额统计[按会员分组]
        $balance = Db::name('user')->alias('u')->join('user_rank r','u.rank_id = r.id','LEFT')->where([
            'u.s_id'        => $this->admin_id
        ])->field('SUM(u.balance) as balance, SUM(u.give_balance) as give_balance,r.name')->group('u.rank_id')->select();
        $statistics['countBalance'] = 0;

        foreach ($balance as $v){
            $statistics['countBalance'] += ($v['balance']*100);
        }
        $statistics['countBalance'] =  $statistics['countBalance'] /100;
        $statistics['shop_detail'] = Db::name('shop')->where(['u_id'=>$this->admin_id])->find();
        return $this->fetch('index', ['statistics' => $statistics]);
    }
}