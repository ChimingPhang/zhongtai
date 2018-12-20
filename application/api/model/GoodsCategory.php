<?php
/*
 * 车系
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class GoodsCategory extends Model
{

    protected $table = 'goods_category';
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
     * 获取所有车系
     * @Autoh: 胡宝强
     * Date: 2018/7/15 12:37
     * @return array
     */
    public function get_name()
    {
        $data = M($this->table)->where(['is_show' => 1])->select();
        if (empty($data)) {
            return array();
        }
        $arr = [];
        foreach($data as $key=>$value){

            if($value['parent_id'] == 0){
                $arr[$key]['name'] = $value['name'];
                $arr[$key]['list'] = M($this->table)->where(['parent_id'=>$value['id'],'is_show'=>1])->field('id,name')->select();
            }
        }

        return array_values($arr);
    }

}
