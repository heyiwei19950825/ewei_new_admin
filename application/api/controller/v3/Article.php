<?php
/**
 * 文章
 * User: heyiw
 * Date: 2018/4/2
 * Time: 20:30
 */

namespace app\api\controller\v3;


use app\api\controller\BaseController;
use app\api\model\Article AS ArticleModel;

use think\Db;

class Article extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取文章列表
     */
    public function getList( $page = 1, $size = 10,$cid=0){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $condition =[];
        if($cid != 0 ){
            $condition['cid'] = $cid;
        }
        $field = 'id,title,introduction,thumb,author,reading,publish_time,other_link';
        $data = ArticleModel::getAllArticle($page,$size,true,'sort','desc',$condition,$field)->toArray();
        $data['data'] = self::prefixDomainToArray('thumb',$data['data']);
        //配置参数
        $row['data'] = [
            'count' => $data['total'],//总数
            'last_page' => $data['last_page'],//下一页页码
            'currentPage' => $data['current_page'],//当前页码
            'pagesize'  => $size,//每页长度
            'totalPages' => 1, //总页数
            'data' => $data['data'],
        ];

        return $row;
    }

    /**
     * 获取文章内容
     * @param int $id
     * @return array
     */
    public function getInfo(  $id = -1  ){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $field = 'id,title,introduction,content,author,reading,thumb,publish_time,link_btn';
        $data = ArticleModel::getArticleById( $id,$field );
        ArticleModel::updateReadingNum($id);
        $data['thumb'] = self::prefixDomain($data['thumb']);
        $row['data'] = $data;
        return $row;
    }
}