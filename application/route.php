<?php
/**
 * 路由注册
 *
 * 以下代码为了尽量简单，没有使用路由分组
 * 实际上，使用路由分组可以简化定义
 * 并在一定程度上提高路由匹配的效率
 */

// 写完代码后对着路由表看，能否不看注释就知道这个接口的意义
use think\Route;

//=============================================api======================================================================

//用户
Route::post('api/:version/token/user', 'api/:version.Token/getToken');
Route::post('api/:version/user/binding', 'api/:version.User/bindingVip');
Route::post('api/:version/user/quit', 'api/:version.User/quit');

//用户消费记录
Route::post('api/:version/deal/get', 'api/:version.UserDeal/getDeal');

//预约订单
Route::post('api/:version/get/bookOrder', 'api/:version.Book/getBookOrderList');
//修改状态
Route::post('api/:version/book/cancel', 'api/:version.Book/updateStatus');

//banner
Route::get('api/:version/banner/getBanner', 'api/:version.Banner/getList');

//技师
Route::get('api/:version/technician/getTechnician', 'api/:version.Technician/getList');
Route::get('api/:version/technician/getTechnicianDetail', 'api/:version.technician/getDetail');


//商家详情
Route::get('api/:version/shop/getInfo', 'api/:version.Shop/shopInfo');
//获取商家会员列表
Route::get('api/:version/shop/membersList', 'api/:version.Shop/membersList');

//获取预约时间
Route::get('api/:version/date/get', 'api/:version.Date/getDate');
Route::post('api/:version/book/pay', 'api/:version.Book/pay');

//意见反馈
Route::post('api/:version/feedBack/add', 'api/:version.FeedBack/add');

//发送模板消息
Route::get('api/:version/message/send', 'api/:version.Message/send');
//获取公告信息
Route::get('api/:version/notice/list', 'api/:version.Notice/getList');

//=============================================api-end==================================================================

//=============================================admin====================================================================

Route::get('admin/turnover/index', 'admin/Sell/turnover');
Route::get('admin/statistics/index', 'admin/Sell/statistics');
Route::post('admin/statistics/index', 'admin/Sell/statistics');

//=============================================admin-end================================================================

//=============================================home_1===================================================================
Route::get('about', 'home/about/index');//关于我们
Route::get('cases', 'home/cases/index');//成功案例
Route::get('service', 'home/service/index');//服务范围
Route::get('mobile', 'home/mobile/index');//移动端
Route::get('solutions', 'home/solutions/index');//解决方案
Route::get('news', 'home/news/index');//新闻
Route::get('contact', 'home/contact/index');//联系我们

//=============================================home_1-end===============================================================
