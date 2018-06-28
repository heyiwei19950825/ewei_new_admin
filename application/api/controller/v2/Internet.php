<?php

/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/20
 * Time: 13:46
 */
namespace app\api\controller\v2;

use app\api\controller\BaseController;
use app\common\server\InternetServer;
use app\common\lib\Helper;
use app\common\model\Api;
use app\api\service\Token;

use think\Db;

class Internet extends BaseController
{
    private $api = '';

    public function _initialize(){
        parent::_initialize();
        $this->api = (new Api())->where(['key'=>'INTERNETBAR'])->find();
    }

    /**
     * 配置信息
     */
    public function internetConfig(){
        if($this->request->isPost()){
            $uid = $this->request->only('uid')['uid'];

            if(empty($uid)){
                $row = [
                    'code'  => 1,
                    'msg'   => '请正确选择公众号',
                    'data'  =>'',
                ];
                return $row;
            }
            $config = Db::name('internet_bar_setting')->where([
                'uid'=>$uid
            ])->find();

            if( $config ){
                $row['time_rule'] = json_decode($config['time_rule'],true );
                $row['number_rule'] = $config['reserve_number'];

                $row = [
                    'code'     => 0,
                    'msg'    => '',
                    'data'  => $row,
                ];
            }else{
                $row = [
                    'code'  => 1,
                    'msg'   => '未开放',
                    'data'  =>'',
                ];
            }
            return $row;
        }else{
            $row = ['code'  => 1, 'msg'   => '网络异常', 'data'  =>'',];
            return $row;
        }

    }

    /**
     * 网吧数据列表
     */
    public function internetAjax(){
        //查询数据库配置查询网吧API接口
        $computer = [];
        $apiUrl = $this->api['api'];
        $data = json_decode( Helper::curl_get($apiUrl),true);
        $uid = $this->request->param('uid',1);
        if( !empty($data)){
            $computer = InternetServer::apiDataDispose($data,$uid);
        }
        if( !empty($computer) ){
            //数据处理
            $offline = $online = $block = 0 ;
            foreach ($computer['computer'] as $k=>&$v){
                if($v['line'] == 'online' &&  $v['rule_id'] != 0){
                    $online++;
                }
                if($v['line'] == 'offline'&&  $v['rule_id'] != 0){
                    $offline++;
                }
                if( $v['sex'] == 1){
                    $v['line'] = 'online-man';
                }elseif($v['sex'] == 2){
                    $v['line'] = 'online-woman';
                }

                if( $v['rule_id'] == 0 ){
                    $v['line'] = 'block';
                    $block++;
                }
            }

            $row = [
                'count' => count($computer['computer']),
                'list'  => $computer['computer'],
                'offline' =>$offline,
                'online' => $online,
                'block' => $block
            ];
            $this->result($row,1,'查询成功','json');
        }else{
            $this->error('接口宕机请联系开发~！');
        }
    }
}