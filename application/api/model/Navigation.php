<?php
/*
 * 首页自定义的导航栏目
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class Navigation extends Model
{

    protected $table = 'navigation';
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

    /**
     * 获取自定义的导航信息
     * @Autoh: 胡宝强
     * Date: 2018/7/15 12:37
     * @return bool
     */
    public function get_name(){
        $data = M($this->table)->where(['is_show'=>1])->order('sort', 'asc')->limit(10)->select();
        if(empty($data)){
            return false;
        }
        return $data;
    }

}
