<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/3/21
 * Time: 21:42
 */

namespace app\api\controller\v2;

use app\common\model\Goods as GoodsModel;
use app\api\controller\BaseController;
use think\Db;

class Goods extends BaseController
{
    private $goods = '';
    public function _initialize(){
        parent::_initialize();

        $this->goods = new GoodsModel();
    }
    /**
     * 获取商品信息
     */
    public function getGoodsList(){
        $map = [];
        $map['is_virtual']       = 1;
        $map['sp_inventory']     = ['>',0];
        $isGroup = $this->request->param('isGroup',1);
        $type= $this->request->param('type','');
        $field = 'id,name,content,thumb,sp_integral,need_rank,sp_inventory';
        if($type == 'integral' ){
            $map['is_integral'] = 1;
            $field .=',sp_integral';
        }elseif($type == 'collective'){
            $map['is_collective'] = 1;
        }elseif($type == 'money'){
            $field .=',sp_price';
            $map['sp_price'] = ['>',0];
        }

        if($type == ''){
            $map['is_recommend'] =  1;
        }


        $row = $this->goods->getGoodsList($map,false,0,0,$field);
        $data = [];

        foreach ($row as $k=>$v){
            $v['thumb'] = $this->prefixDomain($v['thumb']);
            $rank = Db::name('user_rank')->where([
                'rank_id'=>$v['need_rank']
            ])->field('rank_name')->find();
            if( $isGroup == 1 ){
                $data[$rank['rank_name']][] = $v;
            }else{
                $data[] = $v;
            }
        }
        if( !empty($data) ){
            return $this->success('查询成功','',$data);
        }else{
            return $this->error('没有上架商品信息');
        }
    }
}