<?php
/**
 * Created by PhpStorm.
 * User: heyiw
 * Date: 2018/1/21
 * Time: 14:16
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\model\Article AS ArticleModel;
use app\api\model\ArticleGoods;

class Article extends BaseController
{
    /**
     *  文章列表页
     * @param int $page
     * @param int $size
     * @return array
     */
    public function articleList( $page = 1, $size = 10,$cid=0){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $condition =[];
        $condition['cid'] = $cid;
        $data = ArticleModel::getAllArticle($page,$size,true,'sort','desc',$condition)->toArray();

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
     * 获取在线的文章分类
     */
    public function articleCategoryList($id= 0 ){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $data = ArticleModel::gteCategoryList([
            'status' => 1
        ],'id,name,icon,thumb,description','sort desc');
        $currentCategory = [];
        $brotherCategory = self::prefixDomainToArray('icon',$data);
        if($id != 0 ){
            foreach ($brotherCategory as $v){
                if( $v['id'] == $id){
                    $currentCategory = $v;
                }
            }
        }

        $row['data'] = [
            'brotherCategory'=>$brotherCategory,
            'currentCategory'=>$currentCategory,
        ];

        return $row;
    }
    /**
     * 获取文章内容
     * @param int $id
     * @return array
     */
    public function articleOne( $id = -1 ){

        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];
        $field = 'id,title,introduction,content,author,reading,thumb,publish_time';
        $data = ArticleModel::getArticleById( $id,$field );
        $data['goodsList'] = ArticleGoods::getGoodsByArticleId($id);
        $data['goodsList'] = self::prefixDomainToArray('thumb',$data['goodsList']);
        ArticleModel::updateReadingNum($id);
        $data['thumb'] = self::prefixDomain($data['thumb']);
        $row['data'] = $data;
        return $row;
    }

    /**
     * 更具分类ID获取文藏
     * @param int $id
     * @return array
     */
    public function relatedByCid( $id = -1 ){
        $row = ['errmsg'=>'','errno'=>0,'data'=>[]];

        $field = 'cid';
        //获取当前文章的分类
        $info = ArticleModel::getArticleById( $id ,$field)->toArray();
        if( empty($info) ){
            $row['errmsg'] = '暂无数据';
            $row['errno']  = 1;
            return $row;
        }

        $field = 'id,title,introduction,reading,thumb';
        //通过分类获取分类下的文章
        $row['data']  = ArticleModel::getListByCid($info['cid'],$field);
        $row['data'] = self::prefixDomainToArray('thumb',$row['data']);

        return $row;
    }
}