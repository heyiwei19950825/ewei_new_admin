<?php
/**
 * 公共助手函数
 * @authors Your Name (you@example.org)
 * @date    2017-12-26 23:06:37
 * @version $Id$
 */
namespace app\common\lib;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Style_Fill;
use PHPExcel_Style_Border;
use PHPExcel_Style_Alignment;

class Helper {
	//////////////////////////////////
    ///////////时间处理//////////////
    ////////////////////////////////
    //将秒数转换为时间（年、天、小时、分、秒）
    //
	/**
	 * 时间转时间戳
	 *
	 * @param unknown $time            
	 */
	static function getTimeTurnTimeStamp($time)
	{
	    $time_stamp = strtotime($time);
	    return $time_stamp;
	}

    /**
     * 时间转换为多少分钟前
     * @param $the_time
     * @return string
     */

    static function time_tran($time) {
        $time = is_int($time)?$time: strtotime($time);
        $time = (int) substr($time, 0, 10);
        $int = time() - $time;
        if ($int <= 2){
            $str = sprintf('刚刚', $int);
        }elseif ($int < 60){
            $str = sprintf('%d秒前', $int);
        }elseif ($int < 3600){
            $str = sprintf('%d分钟前', floor($int / 60));
        }elseif ($int < 86400){
            $str = sprintf('%d小时前', floor($int / 3600));
        }elseif ($int < 2592000){
            $str = sprintf('%d天前', floor($int / 86400));
        }else{
            $str = date('Y-m-d H:i:s', $time);
        }
        return $str;
    }



    /**
     * Excel表格导出
     * @param $title
     * @param $data
     */
    public  static function exportExport( $filename, $data){
        //计算字段长度
        $lineLength = count($data[1]);
        $array = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','I','S','T','U','V','W','X','Y','Z'];
        $filename=str_replace('.xls', '', $filename).'.xls';
        $phpexcel = new PHPExcel();
        $objActSheet = $phpexcel->getActiveSheet();
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $phpexcel->getActiveSheet()->fromArray($data);
        $phpexcel->getActiveSheet()->setTitle('Sheet1');

        //设置样式
        $phpexcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//设置excel文件默认水平垂直方向居中
        $phpexcel->getDefaultStyle()->getFont()->setSize(8)->setName("微软雅黑");//设置默认字体大小和格式
        $objActSheet->getStyle("A1:".$array[$lineLength-1]."1")->getFont()->setSize(10)->setBold(true);//设置第一行字体大小和加粗
        $objActSheet->getDefaultRowDimension()->setRowHeight(30);//设置默认行高
        $objActSheet->getRowDimension(1)->setRowHeight(20);//设置第一行行高
        //设置列的宽度
        for ($i=0;$i<=$lineLength-1;$i++){
            if($i==$lineLength-1){
                $objActSheet->getColumnDimension( $array[$i] )->setWidth(50);
            }else{
                $objActSheet->getColumnDimension( $array[$i] )->setWidth(20);
            }
        }
        $styleThinBlackBorderOutline = array(
            'borders' => array (
                'outline' => array (
                    'style' => PHPExcel_Style_Border::BORDER_THICK, //设置border样式 'color' => array ('argb' => 'FF000000'), //设置border颜色
                ),
            ));
        $phpexcel->getActiveSheet()->getStyle("A1:".$array[$lineLength-1]."1")->applyFromArray($styleThinBlackBorderOutline);

        //设置背景色
        $phpexcel->getActiveSheet()->getStyle( "A1:".$array[$lineLength-1]."1")->getFill()->setFillType('solid');
        $phpexcel->getActiveSheet()->getStyle("A1:".$array[$lineLength-1]."1")->getFill()->getStartColor()->setRGB('FFEB9C');
        $phpexcel->setActiveSheetIndex(0);


        ob_end_clean();
        ob_start();
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0
        $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objwriter->save('php://output');
        exit;
        

    }


    /**
     * @param string $url get请求地址
     * @param int $httpCode 返回状态码
     * @return mixed
     */
    public static function  curl_get($url, &$httpCode = 0)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //不做证书校验,部署在linux环境下请改为true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $file_contents = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $file_contents;
    }

    /**
     * 生成随机Code码
     * @param $length
     * @return string
     */
    public static function createCode($length){
        $chars ='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ( $i = 0; $i < $length; $i++ )
        {
            $code .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }

        return $code;
    }

    /*
    * 百度地图BD09坐标---->中国正常GCJ02坐标
    * 腾讯地图用的也是GCJ02坐标
    * @param double $lat 纬度
    * @param double $lng 经度
    * @return array();
    */
    public static function Convert_BD09_To_GCJ02($lat,$lng){
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta);
        $lat = $z * sin($theta);
        return array('lng'=>$lng,'lat'=>$lat);
    }
   
}