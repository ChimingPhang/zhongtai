<?php

namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class Order extends Model
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

    /**
     * 返回该订单的状态字符串
     * @Author  郝钱磊
     * @date  2018/7/12 0012 15:26
     * @param $value
     * @return mixed
     * @FunctionName getOrderStatus
     * @UpdateTime  date
     */
    public function getOrderStatus(int $value)
    {
        if (!is_numeric($value)) {
            return false;
        }
        // 0 待付款 、1 已付款 、2 已发货 、3 交易成功 、4 已取消
        $status = [0 => '待付款', 1 => '已付款', 2 => '已发货', 3 => '交易成功', 4 => '已取消'];
        return $status[$value];
    }

    /**
     * 退货的状态返回
     * @Author  郝钱磊
     * @date  2018/7/12 0012 15:30
     * @param int $value
     * @return bool|mixed
     * @FunctionName getIsRefundGood
     * @UpdateTime  date
     */
    public function getIsRefundGood(int $value)
    {
        if (!is_numeric($value)) {
            return false;
        }
        // 0 未退款 、1 待退款 、2 已退款 、3 已拒绝退款
        $status = [0 => '未退款', 1 => '待退款', 2 => '已退款', 3 => '已拒绝退款'];
        return $status[$value];
    }
    /**
     * 订单是否已经评价
     * @Author  郝钱磊
     * @date  2018/7/12 0012 15:58
     * @param $value
     * @return bool|mixed
     * @FunctionName getOrderIsComment
     * @UpdateTime  date
     */
    public function getOrderIsComment($value)
    {
        //验证参数是否是空
        if (!is_numeric($value)) {
            return false;
        }
        //是否评价0：未评价；1：已评价
        $status = [0 => '未评价', 1 => '已评价'];
        return $status[$value];
    }

    public function index(){
        return $this->findAvailableNo();
    }

    /**
     * [处理订单评论状态]
     * @Auther 蒋峰
     * @DateTime
     * @param $rec_id
     */
    public function checkOrder($rec_id){
        $rec = M('order_goods')->where('rec_id', $rec_id)->find();
        M("order_goods")->where('rec_id', $rec_id)->save(['is_comment'=> 1]);
        $count = $this->alias('o')->join('order_goods og', 'o.order_id=og.order_id')->where('o.order_id', $rec['order_id'])->where('og.is_comment', 0)->count();
        if(!$count) $this->where('order_id', $rec['order_id'])->save(['is_comment' => 1,'order_status' => 4]);
    }

}
