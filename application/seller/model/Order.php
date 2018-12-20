<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/4
 * Time: 11:27
 */

namespace app\seller\model;
use think\Model;
use think\Db;

class Order  extends Model{

    /**
     * 获取店铺今天的销售状况
     * @param $store_id
     * @return mixed
     */
    public function getTodayAmount($store_id){
        $now = strtotime(date('Y-m-d'));
        $today_order = Db::name('order')->where(['add_time'=>['gt',$now],'store_id'=>$store_id])->select();
        $today['today_order']=$today['cancel_order'] =0;
        $goods_price=$total_amount=$order_prom_amount=0;
        foreach($today_order as $key=>$order){
            $today['today_order'] +=1;  //今日总订单
            if($order['order_status']==3 ){
                $today['cancel_order'] +=1;  //今日取消订单
            }
            if(($order['order_status']==1 || $order['order_status'] == 2 || $order['order_status']==4) && ($order['pay_status']== 1 || $order['pay_code'] =='cod')){
                $goods_price +=$order['goods_price']; //今日订单商品总价
                $total_amount +=$order['total_amount']; //今日已收货订单总价
                $order_prom_amount +=$order['order_prom_amount']; //今日订单优惠
            }
        }
        $today['today_amount'] = $goods_price-$order_prom_amount; //今日销售总额（有效下单）
        return $today;
    }
    
    /**
     * 获取订单商品
     * @return \think\model\relation\HasMany
     */
    public function OrderGoods()
    {
        return $this->hasMany('OrderGoods','order_id','order_id');
    }
    
    /**
     * 获取订单状态对(商家)
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getShowStatusAttr($value, $data)
    {
        $show_status = 1;
        if ($data['pay_status'] == 0 && $data['order_status'] == 0 && $data['pay_code'] != 'cod') {
            $show_status = 1;
        } else if (($data['pay_status'] == 1 || $data['pay_code'] == "cod") && $data['shipping_status'] != 1 && ($data['order_status'] == 0 || $data['order_status'] == 1)) {
            $show_status = 2;
        } else if (($data['shipping_status'] == 1 AND $data['order_status'] == 1)) {
            $show_status = 3;
        }  else if ($data['is_comment'] == 1) {//评论
            $show_status = 5;
        } else if ($data['order_status'] == 2) {
            $show_status = 4;
        }
        return $show_status;
    }

    /**
     * 获取
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getShippingAddressAttr($value, $data)
    {
        $province = Db::name('region')->where(['id' => $data['province']])->getField('name');
        $city     = Db::name('region')->where(['id' => $data['city']])->getField('name');
        $area     = Db::name('region')->where(['id' => $data['district']])->getField('name');
        return $province.','.$city.','.$area.','.$data['address'];
    }
}