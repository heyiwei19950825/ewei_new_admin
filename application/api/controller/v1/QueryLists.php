<?php 

namespace app\api\controller\v1;

use QL\QueryList;


class QueryLists
{

	public function index()
    {

    	$rules = array(
    		//采集id为one这个元素里面的纯文本内容
		    'title' => array('.at-title','text'),
		    //采集id为one这个元素里面的纯文本内容
		    'text' => array('.at-content','html'),
		    //采集class为two下面的超链接的链接
		    'link' => array('.two>a','href'),
		    //采集class为two下面的第二张图片的链接
		    'img' => array('.imgforhtml>img','src'),
		);



       //采集某页面所有的图片
       $data = QueryList::Query('http://3g.k.sohu.com/t/n255214700?gotoId=255214700',$rules)->data;
       //打印结果
       print_r($data);
    }


}

