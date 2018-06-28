<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/5
 * Time: 17:36
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use think\Db;
use app\api\service\Token;

class Date extends BaseController
{
    public function getDate(){
        $s_id  = $this->request->param('sid',14);
        $t_id  = $this->request->param('tid',0);

        $config = Db::name('book_config')->where([
            's_id' => $s_id
        ])->find();
        $interval = (int)$config['interval'];
        $date = [];
        $week = ['','星期一','星期二','星期三','星期四','星期五','星期六','星期日'];
        for($wi = 0; $wi < 7; $wi++){
            $timeDate = date('m/d', strtotime('+'.$wi.' day'));
            $date['date']['date'][] = [
                'id' => $wi,
                'date'=>$timeDate,
                'week'=>$wi==0?'今天':$week[date('N', strtotime('+'.$wi.' day'))]
            ];
            if( $wi == 0 ){
                $time =[];
                $h = date('H',time());
                if($h<8){
                    $h = 8;
                }
                $i = date('i',time());
                if( $i >45 ){
                    $h += 1;
                }
                if( substr($i,1,1) >5 ){
                    $i = (int)(substr($i,0,1) + 1).'5';
                }elseif(substr($i,1,1) <5){
                    $i = (int)substr($i,0,1).'5';
                }
                if(substr($i,0,1) == 0 ){
                    $i = ((int)substr($i,0,1)+1) .'5';
                }
                $is = (60 - $i)/ ($interval + 0);
                $s = 0;
                for ($di=$h;$di < 21; $di++){
                    if( $s == 0){
                        for($ii = 0; $ii < $is && $i <60;$ii++){

                            $s++;
                            $i += $interval;
                            if($i > 60){
                                $i = 60;
                            }
                            if($i == 0){
                                $i = '00';
                            }
                            $time[] = [
                                'time'=>(string)$di.':'.$i,
                                'key' => (string)$timeDate.' '.(string)$di.':'.$i
                            ];
                        }
                    }else{
                        $i = 0;
                        $is =6;
                        for($ii = 0; $ii < $is && $i <=60;$ii++){
                            $s++;
                            if($i == 0){
                                $i = '00';
                            }
                            if( $i == 60 ){

                            }else{
                                $time[] = [
                                    'time'=>(string)$di.':'.$i,
                                    'key' => $timeDate.' '.(string)$di.':'.$i
                                ];
                            }
                            $i += $interval;
                        }
                    }
                    $s++;
                }
                $date['date']['date'][$wi]['time'] =$time;
            }else{
                $time = [];
                for ($ssi=8; $ssi <=20; $ssi++){
                    for( $sssi=0; $sssi < 60;){
                        if($sssi == 0){
                            $sssi = '00';
                        }
                        $time[] = [
                            'time'=>(string)$ssi.':'.$sssi,
                            'key' => (string)$timeDate.' '.(string)$ssi.':'.$sssi
                        ];
                        $sssi += $interval;
                    }
                }
                $date['date']['date'][$wi]['time'] =$time;
            }
        }
        $book = Db::name('book')->where([
            'project_id'=>$t_id,
            's_id' => $s_id,
            'status' => [
                'in','1,2'
            ]
        ])->field('time')->select();
        $bookTime = [];
        foreach ($book as $k => $v){
            $bookTime[] = date('m/d G:i' ,strtotime($v['time']));
        }

        foreach ( $date['date']['date'] as $k=>&$v){
            foreach ($v['time'] as $ks=>&$vs){
                if(in_array($vs['key'], $bookTime)){
                    $vs['status'] = 1;
                }else{
                    $vs['status'] = 0;
                }
            }
        }
        return ['errmsg'=>'','errno'=>0,'data'=>$date];
    }
}