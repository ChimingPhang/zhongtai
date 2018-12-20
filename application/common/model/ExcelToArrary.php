<?php

/**
 * php excel导入类
 */


namespace app\common\model;
use think\Model;
class ExcelToArrary extends Model{

    public function __construct() {
        vendor("Excel.PHPExcel"); //引入phpexcel类(注意你自己的路径)
        vendor("Excel.PHPExcel.IOFactory");
    }

    public function read($filename, $encode, $file_type) {
        if (strtolower($file_type) == 'xls') {//判断excel表类型为2003还是2007
            Vendor("Excel.PHPExcel.Reader.Excel5");
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        } elseif (strtolower($file_type) == 'xlsx') {
            Vendor("Excel.PHPExcel.Reader.Excel2007");
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        }
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filename);
        $objWorksheet = $objPHPExcel->getActiveSheet();
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
        $excelData = array();
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $excelData[$row][] = (string) $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
            }
        }
        return $excelData;
    }


    /*
     * 导出exel的函数
     */
    public function push($data,$name='Excel')  
    {  
        error_reporting(E_ALL);  
        date_default_timezone_set('Europe/London');  
        $objPHPExcel = new \PHPExcel();  
  
        /*以下是一些设置 ，什么作者  标题啊之类的*/  
         $objPHPExcel->getProperties()->setCreator("转弯的阳光")  
           ->setLastModifiedBy("转弯的阳光")  
           ->setTitle("数据EXCEL导出")  
           ->setSubject("数据EXCEL导出")  
           ->setDescription("备份数据")  
           ->setKeywords("excel")  
          ->setCategory("result file");  
         /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/  
         $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推  
                          ->setCellValue('A1', '标题')     
                          ->setCellValue('B1', '关键字')  
                          ->setCellValue('C1', '主图')  
                          // ->setCellValue('D1', '内容')  
                          ->setCellValue('D1', '标签')  
                          ->setCellValue('E1', '链接');
        foreach($data as $k => $v){              
             $num=$k+2; 
             $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推  
                          ->setCellValue('A'.$num, $v['title'])     
                          ->setCellValue('B'.$num, $v['keyword'])  
                          ->setCellValue('C'.$num, $v['path'])  
                          // ->setCellValue('D'.$num, $v['content'])  
                          ->setCellValue('D'.$num, $v['tags'])  
                          ->setCellValue('E'.$num, $v['url']);
            }  
  
            $objPHPExcel->getActiveSheet()->setTitle('information');  
            $objPHPExcel->setActiveSheetIndex(0);  
             header('Content-Type: applicationnd.ms-excel');  
             header('Content-Disposition: attachment;filename="'.$name.'.xls"');  
             header('Cache-Control: max-age=0');  
             $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
             $objWriter->save('php://output');  
             exit;  
      }  
       /*
     * 导出exel的函数
     */
    public function push1($data,$name='Excel')  
    {  
        error_reporting(E_ALL);  
        date_default_timezone_set('Europe/London');  
        $objPHPExcel = new \PHPExcel();  
  
        /*以下是一些设置 ，什么作者  标题啊之类的*/  
         $objPHPExcel->getProperties()->setCreator("转弯的阳光")  
           ->setLastModifiedBy("转弯的阳光")  
           ->setTitle("数据EXCEL导出")  
           ->setSubject("数据EXCEL导出")  
           ->setDescription("备份数据")  
           ->setKeywords("excel")  
          ->setCategory("result file");  
         /*以下就是对处理Excel里的数据， 横着取数据，主要是这一步，其他基本都不要改*/  
         $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推  
                          ->setCellValue('A1', '企业名称')     
                          ->setCellValue('B1', '参与活动名称')  
                          ->setCellValue('C1', '用户名')  
                          ->setCellValue('D1', '用户id')  
                          // ->setCellValue('D1', '内容')  
                          ->setCellValue('E1', '用户步数')  
                          ->setCellValue('F1', '到达点位')
                          ->setCellValue('G1', '点赞数')
                          ->setCellValue('H1', '字符拼接');
        foreach($data as $k => $v){       
             $num=$k+2; 
             $objPHPExcel->setActiveSheetIndex(0)//Excel的第A列，uid是你查出数组的键值，下面以此类推  
                          ->setCellValue('A'.$num, $v['enterprise_name'])     
                          ->setCellValue('B'.$num, $v['activity_name'])  
                          ->setCellValue('C'.$num, $v['user_name'])  
                          ->setCellValue('D'.$num, $v['activity_umember_id'])  
                          ->setCellValue('E'.$num, $v['steps'])  
                          ->setCellValue('F'.$num, $v['point_name'])  
                          ->setCellValue('G'.$num, $v['ups'])
                          ->setCellValue('H'.$num, $v['all']);
            }  
  
            $objPHPExcel->getActiveSheet()->setTitle('报名用户');  
            $objPHPExcel->setActiveSheetIndex(0);  
             header('Content-Type: applicationnd.ms-excel');  
             header('Content-Disposition: attachment;filename="'.$name.'.xls"');  
             header('Cache-Control: max-age=0');  
             $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  
             $objWriter->save('php://output');  
             exit;  
      }  




}
   
