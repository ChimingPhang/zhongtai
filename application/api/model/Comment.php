<?php
/*
 * 车系
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class Comment extends Model
{

    protected $table = 'order_comment';
    protected $collection_table = 'son_order_comment';
    protected $request;//请求的参数

    /**
     * 初始化参数
     * @Autoh: 胡宝强
     * Date: 2018/7/15 12:23
     */
    public function _initialize()
    {
        $request = Request::instance();//获取当前请求的参数
        $this->request = $request;
    }



}
