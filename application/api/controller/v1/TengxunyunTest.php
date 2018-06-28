<?php
namespace app\api\controller\v1;

use app\api\controller\BaseController;

use TencentYoutuyun\Youtu;
use TencentYoutuyun\Conf;
use think\Loader;
use think\Request;
class TengxunyunTest extends BaseController{

	private $appid='10116417';
	private $secretId='AKIDBD0Zt0fVt2wd5Il0VlmYDetwlLgpoPr4';
	private $secretKey='5wYv33rTq0ua1IKAkogLn11oOkutfDYf';
	private $userid='492007413';

	public function __construct()
    {
       	Conf::setAppInfo($this->appid, $this->secretId, $this->secretKey, $this->userid,conf::API_YOUTU_END_POINT );
    }

    public function index(){

    	$img = Request::instance()->param('img');
		// $str = file_get_contents($img);
		$str = file_get_contents('http://admin.ewei.com/'.$img);
		// $str = file_get_contents(__ROOT__.'/public/static/images/test.jpg');
		$img_base64=base64_encode($str);
    	$uploadRet = Youtu::facefuse($img_base64,'cf_yuren_jirou');

		return $uploadRet;
    }
	// 设置APP 鉴权信息 请在http://open.youtu.qq.com 创建应用

	// 人脸检测 调用列子
	//$uploadRet = YouTu::detectface('a.jpg', 1);
	//var_dump($uploadRet);


	// 人脸定位 调用demo
	//$uploadRet = YouTu::faceshape('a.jpg', 1);
	//var_dump($uploadRet);

	//黄图识别
	//$uploadRet = YouTu::imageporn('test.jpg', 1);
	//var_dump($uploadRet);
	//$uploadRet = YouTu::imagepornurl('http://open.youtu.qq.com/content/img/product/face/face_05.jpg', 1);
	//var_dump($uploadRet);

	//身份证ocr

	//$uploadRet = YouTu::idcardocr('test.jpg', 1);
	//var_dump($uploadRet);
	//$uploadRet = YouTu::idcardocrurl('http://open.youtu.qq.com/content/img/product/face/face_05.jpg', 1);
	//var_dump($uploadRet);

}
