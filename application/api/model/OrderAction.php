<?php

/**
 * 立即购买-车-动作类
 * @Author  郝钱磊
 * @date  2018/7/12 0012 17:47
 * @FunctionName _initialize
 * @UpdateTime  date
 */

namespace app\api\model;

use think\Model;
use think\Request;


class OrderAction extends Model
{
    protected $table = 'tp_order';
    protected $request;//请求的参数

    /**
     * 初始化函数
     * @Author  郝钱磊
     * @date  2018/7/12 0012 17:47
     * @FunctionName _initialize
     * @UpdateTime  date
     */
    public function _initialize()
    {
        $request = Request::instance();//获取当前请求的参数
        $this->request = $request;
    }

    public function index()
    {
        return $this->findAvailableNo();
    }

    /**
     * 获取该订单的用户信息
     * @Author  郝钱磊
     * @date  2018/7/12 0012 17:56 
     * @FunctionName getOrderUserInfo
     * @UpdateTime  date
     */
    public function getOrderUserInfo()
    {

    }
    /**
     * 获取当前用户订单的json格式地址
     * @Author  郝钱磊
     * @date  2018/7/12 0012 15:52
     * @param string $user_id
     * @param string $order_id
     * @return bool|mixed
     * @FunctionName getUserOrderAddressJson
     * @UpdateTime  date
     */
    public function getUserOrderAddressJson($user_id = '', $order_id = '')
    {
        //验证参数是否是空
        if ($user_id == '' || $order_id == '') {
            return false;
        }
        //如果非空执行
        return $this->where(['order_id' => $order_id, 'user_id' => $user_id])->field('address')->find()['address'];
    }
    /**
     * 获取当前选中车的信息
     * @Author  郝钱磊
     * @date  2018/7/12 0012 17:56
     * @FunctionName getUserSelectedVehicleInfo
     * @UpdateTime  date
     */
    public function getUserSelectedVehicleInfo()
    {

    }

    /**
     * 获取当前车的规格
     * @Author  郝钱磊
     * @date  2018/7/12 0012 18:15 
     * @FunctionName getVehicleSkuInfo
     * @UpdateTime  2018/7/12 0012 18:15
     */
    public function getVehicleSkuInfo()
    {

    }

    /**
     * 订单号生成规则
     * @Author  郝钱磊
     * @date  2018/7/12 0012 16:12
     * @return string
     * @FunctionName findAvailableNo
     * @UpdateTime  2018/7/12 0012 17:12
     */
    private function findAvailableNo()
    {
        // 订单流水号前缀
        $prefix = date('YmdHis');

        for ($i = 0; $i < 10; $i++) {
            // 随机生成 6 位的数字
            $order_sn = $prefix.str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            // 判断是否已经存在
            if (!$this->where('order_sn',$order_sn)->find()) {
                return $order_sn;
            }
        }

        return false;
    }
}
