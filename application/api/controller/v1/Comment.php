<?php
/**
 * 评论
 * User: heyiw
 * Date: 2018/3/13
 * Time: 11:40
 */

namespace app\api\controller\v1;


use app\api\controller\BaseController;
use app\api\service\Token;
use think\Db;

class Comment extends BaseController
{
    private $uid = 0;
    public function _initialize()
    {
        parent::_initialize();
        $this->uid = Token::getCurrentUid();

    }
    /*
     * 添加文藏评论
     */
    public function addArticleComment(){
        if( $this->request->isPost() ){
            $row = ['errmsg'=>'成功留言','errno'=>0,'data'=>[]];
            $params = $this->request->param();

            $data = Db::name('article_comment')->insert([
                'uid'=>$this->uid,
                'aid'=>$params['valueId'],
                'content'=>$params['content'],
                'time'=>time()
            ]);

            //判断是否留言成功
            if( $data == NULL){
                $row['errmsg'] = '留言失败';
                $row['errno'] = 1;
            }

            return $row;
        }
    }

    /**
     * 获取当前文章的评论
     */
    public function articleCommentList(){
        $row = ['errmsg'=>'查询成功','errno'=>0,'data'=>[]];
        $params = $this->request->param();
        $count = 0;
        $data = Db::name('article_comment')->alias('c')
            ->join('user u','c.uid=u.id','LEFT')
            ->where(['aid'=>$params['valueId']])
            ->field('c.id,u.nickname,u.portrait,c.content,c.time')
            ->limit($params['size'])
            ->order('time desc')
            ->select();

        if( $data != NULL || !empty($data) ){
            $data = $data->toArray();
            foreach ($data as &$item) {
                $item['time'] = date('Y/m/d H:i',$item['time']);
            }
            if( count($data)  == 5 ){
                $count = Db::name('article_comment')->count('id');
            }else{
                $count = count($data);
            }
            $row['data']['data'] = $data;
            $row['data']['count'] = $count;
            $row['data']['currentPage'] = isset($params['page'])?$params['page']:0;
        }else{
            $row['errmsg'] = '暂无数据';
        }

        return $row;
    }

    /**
     * 统计文章总数
     */
    public function articleCommentCount(){
        $row = ['errmsg'=>'查询成功','errno'=>0,'data'=>[]];

        $params = $this->request->param();
        $row['data']['hasPicCount'] = 0;
        $row['data']['allCount'] = Db::name('article_comment')->where([
            'aid'=>$params['valueId']
        ])->count();


        return $row;


    }
}