<?php
/*
 * 车系
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class AccessoriesCategory extends Model
{

    protected $table = 'accessories_category';
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
     *
     * @Autoh: 胡宝强
     * Date: 2018/7/15 12:37
     * @return bool
     */
    public function get_name()
    {
        $data = M($this->table)->where(['status' => 1])->field('id,name')->select();
        if (empty($data)) {
            return false;
        }

        return $data;
    }

}
