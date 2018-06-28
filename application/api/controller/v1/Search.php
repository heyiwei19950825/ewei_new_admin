<?php
/**
 * 搜索关键字
 * User: heyiw
 * Date: 2018/1/21
 * Time: 19:37
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use think\Db;

class Search extends BaseController
{
    /**
     * 搜索页面关键字
     * @return array
     */
    public function getAll(){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $defaultKeyword = $historyKeywordList = $hotKeywordList = [];

        $hotKeywordList = Db::name('keywords')->where(
            ['is_show'=>1]
        )->order('sort_order asc')->select()->toArray();
        foreach ( $hotKeywordList as $v) {
            if( $v['is_default'] == 1 ){
                $defaultKeyword = $v;
            }
        }
        $row['data'] = [
            'defaultKeyword' => $defaultKeyword,
            'historyKeywordList' => $historyKeywordList,
            'hotKeywordList'  => $hotKeywordList,
        ];
        return $row;
    }

    public function helper( $keyword ){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $map = [];
        $map['name']    = ['like','%'.$keyword.'%'];
        $row['data'] = Db::name('goods')->field('name')->where($map)->select()->toArray();
        return  $row;
    }
}