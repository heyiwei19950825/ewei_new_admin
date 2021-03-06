<?php
/**
 * Created by PhpStorm.
 * User: HeYiwei
 * Date: 2018/6/2
 * Time: 23:17
 */

namespace app\admin\controller;


use app\common\controller\AdminBase;
use app\common\model\Shop as ShopModel;
use think\Db;

class Shop extends AdminBase
{
    protected $shop_model;
    protected function _initialize()
    {
        if($this->request->action() !='register' ){
            parent::_initialize(); // TODO: Change the autogenerated stub
            $this->shop_model = new ShopModel();

        }

    }

    public function setting(){
        $shop = $this->shop_model->where(['u_id'=>$this->admin_id])->find();
        $shop['note_config'] = json_decode($shop['note_config']);
        $shop['book'] = Db::name('book_config')->where(['s_id'=>$this->admin_id])->find();
        $recharge = $consumption = $book = false;
        if( !empty($shop['note_config']) ){
            if(in_array('recharge', $shop['note_config'])){
                $recharge = true;
            }
            if(in_array('consumption', $shop['note_config'])){
                $consumption = true;
            }

            if(in_array('book', $shop['note_config'])){
                $book = true;
            }
        }

        return $this->fetch('',['shop'=>$shop,'book' =>$book,'recharge'=>$recharge,'consumption'=>$consumption]);
    }

    public function update(){
        if($this->request->isPost()){
            $params = $this->request->param();
            $data =[
                "shop_logo" => $params['shop_logo'],
                "shop_shopowner" => $params['shop_shopowner'],
                "shop_name" =>  $params['shop_name'],
                "shop_phone" =>  $params['shop_phone'],
                "live_store_address" =>  $params['live_store_address'],
                "business_time" => $params['business_time'],
                "brief" => $params['brief'],
                "content" => $params['content'],
                "is_discount" => $params['is_discount'],
                "note_config" => isset($params['note_config'])?json_encode($params['note_config']):[]
            ];
            $book = [
                'switch' => $params['switch'],
                'interval' => $params['interval'],
                'book_verify' => $params['book_verify']
            ];

            $row =$this->shop_model->where(['id'=>$params['id']])->update($data);
            $bookRow = Db::name('book_config')->where(['s_id'=>$this->admin_id])->update($book);

            if($row || $bookRow){
                $this->success('修改成功');
            } else {
                $this->error('没有修改');
            }
        }
    }

    /**
     * 商户注册
     */
    public function register(){
        return $this->fetch();
    }
}