<?php
/*
 * 车系
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class IntegralLog extends Model
{

    protected $table = 'integral_log';
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
     * 获取积分列表
     * @Autoh: 胡宝强
     * Date: 2018/7/15 12:37
     * @return bool
     */
    public function get_name()
    {
        $data = M($this->table)->order('create_time desc')->select();
        if (empty($data)) {
            return false;
        }

        return $data;
    }

    /**
     * 积分的增加减少操作
     * @Autoh: 胡宝强
     * Date: 2018/7/15 14:00
     * @param $user_id      用户id
     * @param $count        积分变化的数量
     * @param $status       积分的变化状态 1支出 2收入
     * @param $type         积分是什么情况下变化的 1注册赠送
     * @param $typename     积分变化说明
     * @param $order_id     订单id
     * @param $rec_id       子订单id
     */
    public function add_integral($user_id,$count=0,$status='',$type=0,$typename='',$order_id='',$rec_id=''){
        $data['user_id'] = $user_id;
        $data['integral'] = $count;
        $data['status'] = $status;
        $data['type'] = $type;
        $data['type_name'] = $typename;
        $data['order_id'] = $order_id;
        $data['rec_id'] = $rec_id;
        $data['create_time'] = time();
        M($this->table)->add($data);
    }

    /**
     * 积分日志
     * @Autor: 胡宝强
     * Date: 2018/8/30 15:51
     */
    public function integralLog($user_id, $page, $page_num=10)
    {
        $offset=$page_num*($page-1);
        $limit=$offset.",".$page_num;
        $data = M($this->table)
            ->order('create_time desc')
            ->where(['user_id'=>$user_id])
            ->limit($limit)
            ->field('status,integral,create_time,type_name')
            ->select();
        return $data;
    }

}
