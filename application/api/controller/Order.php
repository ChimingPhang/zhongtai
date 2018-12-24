<?php

namespace app\api\controller;

use app\api\logic\CartLogic;
use think\Request;
use think\Cache;
use think\Db;
use think\Loader;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\util\TpshopException;
use app\api\model\Users;
/**
 * 订单
 * @Author   XD
 * @DateTime 2018-07-11T15:09:13+0800
 *
 *          目录
 *  commit        进入订单
 *  carOrder      生成车型订单
 *  commit_order  提交订单
 *  pay           立即付款
 *  delorder      取消订单
 *  userOrder     我的订单列表
 *  detail        订单详情
 *  orderGoods    订单商品详情
 */
class Order extends Base
{
    public function __construct()
    {
        parent::__construct();
    }
    /*
     * 初始化操作
     */
    public function _initialize() {
     
    }
   /**
    * 进入订单
    * [commit description]
    * @Author   XD
    * @DateTime 2018-07-18T16:11:11+0800
    * @return   [type]                   [description]
    */
    public function commit()
    {
        // token 0e0fb838d23f67804c126be0e977c9f6
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $action    = I("action"); 
        $token     = I("token");
        $address_id     = I("address_id");
        $exchange_integral = I('exchange_integral')??0;
        $user_id = $this->checkToken($token);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        //立即购买入口
        if($action == 'buy_now'){
            $goods_id  = I("goods_id/d"); // 商品id
            $goods_num = I("goods_num/d");// 商品数量
            $item_id   = I("item_id/d"); // 商品规格id
            if(empty($goods_id)) $this->errorMsg('2001','goods_id');
            if(empty($goods_num)) $this->errorMsg('2001','goods_num');
            if(empty($item_id)) $this->errorMsg('2001','item_id');
            $cartLogic->setGoodsModel($goods_id);
            $cartLogic->setSpecGoodsPriceModel($item_id);
            $cartLogic->setGoodsBuyNum($goods_num);
            $buyGoods = [];
            try{
                $allPoint = M('spec_goods_price')->where(['item_id'=>$item_id,'goods_id'=>$goods_id])->getField('integral');
                $all_points = $allPoint*$goods_num;
                $buyGoods = $cartLogic->buyNow(0,$exchange_integral,$all_points);
            }catch (TpshopException $t){
                $error = $t->getErrorArr();
                $this->throwError($error['msg']);
            }
            $cartList[0] = $buyGoods;
            $cartGoodsTotalNum = $goods_num;
            $goods = M('goods')->where(['goods_id'=>$goods_id,'is_on_sale'=>1,'state'=>1])->field('exchange_integral,integral')->find();
            if(empty($goods)) $this->errorMsg('8910');
            if($exchange_integral == 2){
                //纯积分购买
                $most_point = $cartList[0]['integral_alls'] * $goods_num;
                $minimum_point = $cartList[0]['integral_alls'] * $goods_num;
            }elseif($exchange_integral == 1){
                //积分金额购买
                $most_point = $cartList[0]['integral_alls'] * $goods_num;
                $minimum_point = 1;
            }else{
                $most_point = 0;
                $minimum_point = 0;
            }

        }else{
            //购物车入口
            $cart_id   = I("cart_id"); // 购物车ID
            if(empty($cart_id)) $this->errorMsg('2001','cart_id');
            $cartLogic->sel_cart($cart_id);
            if ($cartLogic->getUserCartOrderCount() == 0){
                $this->throwError('你的购物车没有选中商品');
            }
            $cartList = $cartLogic->getCartList(1); // 获取用户选中的购物车商品
            $pointts = $cartLogic->getCartHlPoint($cartList);  //检查积分最多和最少可以使用
            $most_point = $pointts['most_point'];           //最多可以使用多少积分
            $minimum_point = $pointts['minimum_point'];     //最少可以使用多少积分
            $cartGoodsTotalNum = array_sum(array_map(function($val){return $val['goods_num'];}, $cartList));//购物车购买的商品总数
        }
        $arr = [];
        // $cartGoodsList = get_arr_column($cartList,'goods');
        // $cartGoodsId = get_arr_column($cartGoodsList,'goods_id');
        // $cartGoodsCatId = get_arr_column($cartGoodsList,'cat_id3');
        // $storeCartList = $cartLogic->getStoreCartList($cartList);//转换成带店铺数据的购物车商品
        $arr['tatle_price']= array_sum(array_map(function($val){return $val['goods_price']*$val['goods_num'];}, $cartList));//商品总价
        // $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, $cartGoodsId, $cartGoodsCatId);//用户可用的优惠券列表
        //运费设置为包邮状态
        $arr['yunfei'] = 0;

        //查看这个订单最多和最少可以使用多少积分
        $arr['most_point'] = $most_point;
        $arr['minimum_point'] = $minimum_point;
        $arr['userIntegral'] = getIntegral($user_id);       //用户还有多少积分
        $point_rate = tpCache('shopping.point_rate'); //兑换比例
        $arr['integral_money'] = round($arr['most_point']/$point_rate,2);  //积分最高可以抵多少钱
        //获取用户地址
        if(empty($address_id)){
            $address = $this->ajaxAddress($user_id);
        }else{
            $address = $this->ajaxAddress($address_id,2);
            
        }

        if(!$address) $arr['address'] = '';
        else $arr['address'] = $address;
        foreach ($cartList as $key => $value) {
           $arr['list'][$key]['goods_name']  = $value['goods_name'];
           $arr['list'][$key]['goods_num']   = $value['goods_num'];
           $arr['list'][$key]['goods_price'] = $value['goods_price'];       //纯金额购买
           $arr['list'][$key]['spec']        = $value['spec_key_name'];
            if($action == 'buy_now'){
                $arr['list'][$key]['integral']    = $value['integral_alls'];      //积分兑换的积分
            }else{
                $arr['list'][$key]['integral']    = $value['integral'];      //积分兑换的积分
            }

           $arr['list'][$key]['goods_img'] = goods_thum_images($value['goods_id'],200,150);
        }
        $this->json('0000','获取成功',$arr);
    }
    /**
     * 生成车型订单
     * [carOrder description]
     * @Author   XD
     * @DateTime 2018-07-20T16:57:46+0800
     * @return   [type]                   [description]
     */
    public function carOrder(){
        //token  c7adefc3f803c583291c4b1476c9967a
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $token      = I("token");
        $data = I("");
        if(empty($data['goods_id'])) $this->errorMsg('2001','goods_id');
        if(empty($data['goods_num'])) $this->errorMsg('2001','goods_num');
        if(empty($data['item_id'])) $this->errorMsg('2001','item_id');
        if(empty($data['store_id'])) $this->errorMsg('2001','store_id');
        if(empty($data['mobile'])) $this->errorMsg('2001','mobile');
        if(empty($data['name'])) $this->errorMsg('2001','name');
        if(empty($data['spec_name'])) $this->errorMsg('2001','spec_name');
        if(empty($data['exchange_integral'])) $data['exchange_integral'] = 0;
        if($data['pay_points']) if(!is_numeric($data['pay_points'])) $this->errorMsg('2002','pay_points');
        $goods = M('goods')->where(['goods_id'=>$data['goods_id'],'is_on_sale'=>1,'state'=>1])->field('exchange_integral,integral')->find();
        if(empty($goods)) $this->errorMsg('8910');
        if($data['exchange_integral'] == 2){
            //纯积分购买
            if($goods['integral'] * $data['goods_num'] != $data['pay_points']) $this->errorMsg('2001','必须使用同等积分兑换');
        }elseif($data['exchange_integral'] == 1){
            if($data['pay_points'] < 1) $this->errorMsg('2001','最少使用1积分');
            if($data['pay_points'] > $goods['integral'] * $data['goods_num']) $this->errorMsg('2001','不能大于该商品使用的积分');
        }else{
            //纯金额购买
            $data['pay_points'] = 0;
        }

        $user_id   = $this->checkToken($token);
        $users = M('users')->where(['user_id'=>$user_id])->field('pay_points')->find();
        if($data['pay_points'] > $users['pay_points']){
            $this->errorMsg('3008','积分不足');
        }
        $cartLogic = new CartLogic();
        $pay       = new Pay();
        $cartLogic->setUserId($user_id);
        $cartLogic->setGoodsModel($data['goods_id']);
        $cartLogic->setGoodsSkuModel($data['item_id']);
        $cartLogic->setGoodsBuyNum($data['goods_num']);
        $cart_list[0] = $cartLogic->CarbuyNow($data['spec_name'],$data['store_id'],$data['pay_points'],$data['exchange_integral'],$data['item_id']);
//        dump($cart_list);die;
        $pay->payGoodsList($cart_list);
        $pay->setUserId($user_id);
        $address['consignee'] = $data['name'];
        $address['mobile'] = $data['mobile'];
        $pay->dj = $cart_list[0]['dj'];//设置定金
        $pay->all_car_money = $cart_list[0]['all_car_money']; //设置订单的总金额
        // $pay->orderPromotion();//优惠价格
        // $pay->useCoupons($coupon_id);//优惠券
        // $pay->useUserMoney($user_money);//使用余额

        //判断是否立即订购还是积分兑换
        if($data['exchange_integral'] == 0){
            //纯金额
            $integral_all = 0;
        }else{
            $integral_all = $goods['integral'] * $data['goods_num'];
        }

        $pay->useCarPayPoints($data['pay_points'],false,$integral_all);  //使用积分
        // if ($_REQUEST['act'] == 'submit_order') {
            $placeOrder = new PlaceOrder($pay);
            $placeOrder->setType(1);
            $placeOrder->setUserAddress($address);
            // $placeOrder->setInvoiceTitle($invoice_title);//发票
            // $placeOrder->setUserNote($user_note);//用户留言
            // $placeOrder->setTaxpayer($taxpayer);//纳税人识别号
            // $placeOrder->setPayPsw($pay_pwd);

            $placeOrder->addNormalOrder('buy_car');
            $cartLogic->clear();
            $master_order_sn['master_order_sn'] = $placeOrder->getMasterOrderSn();
            if(!$master_order_sn['master_order_sn']) $this->throwError('订单提交失败');
            $this->json('0000','提交订单成功',$master_order_sn['master_order_sn']);
    }
    /**
     * 提交订单
     * [commit_order description]
     * @Author   XD
     * @DateTime 2018-07-19T11:19:26+0800
     * @return   [type]     [description]
     */
    public function commit_order(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $action     = I("action"); 
        $token      = I("token");
        $address_id = I("address_id");
        $pay_points = I('pay_points')??0;//使用积分
        $exchange_integral = I('exchange_integral')??0;  //商品支持什么形式的购买 0 纯金额 1积分和金额 2 纯积分
        $user_id    = $this->checkToken($token);
        $cartLogic  = new CartLogic();
        $cartLogic->setUserId($user_id);
        //  $cartLogic->setPayPoint($pay_points);
        if(empty($address_id)) $this->errorMsg('3008','请添加收货地址');
        $address = Db::name('UserAddress')->where("address_id", $address_id)->find();
        $pay = new Pay();
          try{
            if($action == 'buy_now'){
                $goods_id  = I("goods_id/d"); // 商品id
                $goods_num = I("goods_num/d");// 商品数量
                $item_id   = I("item_id/d"); // 商品规格id
                if(empty($goods_id)) $this->errorMsg('2001','goods_id');
                if(empty($goods_num)) $this->errorMsg('2001','goods_num');
                if(empty($item_id)) $this->errorMsg('2001','item_id');

                //判断传过来的商品积分类型对不对
                //$goodsIntegral = M('goods')->where(['goods_id'=>$goods_id,'is_on_sale'=>1,'state'=>1])->getField('exchange_integral');
//                if(substr_count($goodsIntegral,$exchange_integral)<=0){
//                    $this->errorMsg('2002','exchange_integral');
//                }
                //判断是纯金额商品的时候积分数量为0
                if($exchange_integral == 0){
                    $pay_points = 0;
                }

                $cartLogic->setGoodsModel($goods_id);
                $cartLogic->setSpecGoodsPriceModel($item_id);
                $cartLogic->setGoodsBuyNum($goods_num);
                $allPoint = M('spec_goods_price')->where(['item_id'=>$item_id,'goods_id'=>$goods_id])->getField('integral');
//                $all_points = $allPoint*$goods_num;
                $all_points = $allPoint;
//                $cart_list[0] = $cartLogic->buyNow($pay_points,$exchange_integral,$all_points);
                $cart_list[0] = $cartLogic->buyNow($all_points,$exchange_integral,$all_points);

//                dump($cart_list[0]);die;
                $pay->payGoodsList($cart_list);
//                /$points = M('goods')->where(['goods_id'=>$goods_id])->field('exchange_integral,integral')->find();
//                if($points['exchange_integral'] == 1 || $points['exchange_integral'] == 2) {
//                    //这个商品是可以用积分的商品
//                    $all_point = $points['integral'] * $goods_num;
//                    if($pay_points > $all_point) $this->errorMsg('9999', '使用的积分大于商品总积分');
//                    if($pay_points < 1) $this->errorMsg('9999', '最少使用1积分');
//                }else{
//                    $all_point = 0;
//                }

                if($pay_points) if(!is_numeric($pay_points)) $this->errorMsg('2002','pay_points');
                $goods = M('goods')->where(['goods_id'=>$goods_id,'is_on_sale'=>1,'state'=>1])->field('exchange_integral,integral')->find();
                if(empty($goods)) $this->errorMsg('8910');
                if($exchange_integral == 2){
                    //纯积分购买
//                    if($goods['integral'] * $goods_num != $pay_points) $this->errorMsg('2001','必须使用同等积分兑换');
                    if($cart_list[0]['integral_alls'] * $goods_num != $pay_points) $this->errorMsg('2001','必须使用同等积分兑换');
                    //$all_point = $goods['integral'] * $goods_num;

                }elseif($exchange_integral == 1){
                    //积分的
                    if($pay_points < 1) $this->errorMsg('2001','最少使用1积分');
//                    if($pay_points > $goods['integral'] * $goods_num) $this->errorMsg('2001','不能大于该商品使用的积分');
                    if($pay_points > $cart_list[0]['integral_alls'] * $goods_num) $this->errorMsg('2001','不能大于该商品使用的积分');
                    //$all_point = $goods['integral'] * $goods_num;
                }else{
                    $pay_points = 0;
                    //$all_point = 0;
                }
                $all_point = $cart_list[0]['integral_alls'] * $goods_num;
            }else{
                $cartLogic->setUserId($user_id);
                $cart_list = $cartLogic->getCartList(1);
                if(empty($cart_list))  $this->errorMsg('2001','请选择要购买的商品');
                $all_point = $cartLogic->getCarPoint($cart_list);
                $cartLogic->checkStockCartList($cart_list);
                $cartLogic->checkBuyPoint($cart_list,$pay_points);  //检查积分的使用
                $pay->payCart($cart_list);
            }
            $pay->setUserId($user_id);
            // $pay->delivery($address['district']);//设置邮费
            // $pay->orderPromotion();//优惠价格
            // $pay->useCoupons($coupon_id);//优惠券
            // $pay->useUserMoney($user_money);//使用余额
            $pay->usePayPoints($pay_points,false,$all_point);
            // if ($_REQUEST['act'] == 'submit_order') {
                $placeOrder = new PlaceOrder($pay);
                $placeOrder->setUserAddress($address);
                // $placeOrder->setInvoiceTitle($invoice_title);//发票
                // $placeOrder->setUserNote($user_note);//用户留言
                // $placeOrder->setTaxpayer($taxpayer);//纳税人识别号
                // $placeOrder->setPayPsw($pay_pwd);
//die;
                $placeOrder->addNormalOrder($action);
                $cartLogic->clear();
                $master_order_sn['master_order_sn'] = $placeOrder->getMasterOrderSn();
                if(!$master_order_sn['master_order_sn']) $this->throwError('订单提交失败');
                $this->json('0000','提交订单成功',$master_order_sn['master_order_sn']);
            // }
            // $result = $pay->toArray();
            // $return_arr = array('status' => 1, 'msg' => '计算成功', 'result' => $result); // 返回结果状态
            // $this->ajaxReturn($return_arr);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->throwError($error['msg']);
        }
    }

    /**
     * 立即付款
     */
    public function pay()
    {
        $master_order_sn = I('master_order_sn', '');
        $token = I('token', '');
        $type = I('type', 4);

        if(empty($master_order_sn)) $this->errorMsg(2001, 'master_order_sn');
        if(empty($token)) $this->errorMsg(2001, 'token');
        if(empty($type) && !is_numeric($type)) $this->errorMsg(2002, 'type');
        $user_id = $this->checkToken($token);

        // 如果是主订单号过来的, 说明可能是合并付款的，只有在提交订单时候才会传主单号来付款
        $order_where['user_id'] = $user_id;
        $order_where['master_order_sn'] = $master_order_sn;
        $order = M('Order')->where($order_where)->find();
        if(empty($order)){
            $this->errorMsg(3001);
        }
        if($order['order_status'] == 3){
            $this->errorMsg(3002);
        }
        if ($order['pay_status'] == 1) {
            $this->errorMsg(3003);
        }
        //判断这个商品的活动是否已经结束了
        if($order['prom_type'] == 8){
           $seckill =  M('goods_seckill')->where(['id'=>$order['prom_id']])->field('end_time')->find();
            if($seckill['end_time'] < time()){
                $this->errorMsg(3007);
            }
        }

        //(new Payment($type))->getCode($order['order_sn']);

        $result = update_pay_status($master_order_sn, '测试流程');
        if($result === false) $this->json(1477,'支付失败');
        return $this->json('0000','支付成功');
//
//        $this->throwError('该接口暂时还没进行开发!');

//        if ($master_order_sn) {
//            $order_list = Db::name('order')->where($order_where)->select();
//            if (count($order_list) > 0) {
//                $sum_order_amount = 0;
//                $order_pay_status_arr = get_arr_column($order_list, 'pay_status');
//                if (!in_array(0, $order_pay_status_arr)) {
//                    $this->redirect('Order/order_list');
//                }
//                foreach ($order_list as $orderKey => $orderVal) {
//                    $sum_order_amount += $orderVal['order_amount'];
//                }
//            }
//        } else {
//            // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
//            if ($order['pay_status'] == 1) {
//                $this->redirect('Order/order_detail', ['id' => $order_id]);
//            }
//        }
        /*$paymentList = M('Plugin')->where("`type`='payment' and status = 1 and  scene in(0,2)")->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach ($paymentList as $key => $val) {
            $val['config_value'] = unserialize($val['config_value']);
            if ($val['config_value']['is_bank'] == 2) {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }

        $bank_img = include APP_PATH . 'home/bank.php'; // 银行对应图片
        $this->assign('paymentList', $paymentList);
        $this->assign('bank_img', $bank_img);
        $this->assign('master_order_sn', $master_order_sn); // 主订单号
        $this->assign('sum_order_amount', $sum_order_amount); // 所有订单应付金额        
        $this->assign('order', $order);
        $this->assign('bankCodeList', $bankCodeList);
        $this->assign('pay_date', date('Y-m-d', strtotime("+1 day")));
        return $this->fetch();*/
    }

    /**
     * 纯积分订单支付
     * @Autor: 胡宝强
     * Date: 2018/8/31 9:44
     */
    public function pointPay(){

        $master_order_sn = I('master_order_sn', '');
        $token = I('token', '');
        $type = I('type', 4);

        if(empty($master_order_sn)) $this->errorMsg(2001, 'master_order_sn');
        if(empty($token)) $this->errorMsg(2001, 'token');
        if(empty($type) && !is_numeric($type)) $this->errorMsg(2002, 'type');
        $user_id = $this->checkToken($token);

        // 如果是主订单号过来的, 说明可能是合并付款的，只有在提交订单时候才会传主单号来付款
        $order_where['user_id'] = $user_id;
        $order_where['master_order_sn'] = $master_order_sn;
        $order = M('Order')->where($order_where)->find();
        if(empty($order)){
            $this->errorMsg(3001);
        }
        if($order['order_status'] == 3){
            $this->errorMsg(3002);
        }
        if ($order['pay_status'] == 1) {
            $this->errorMsg(3003);
        }
        if($order['order_amount'] != 0){
            $this->errorMsg(3006);
        }

        $userPoint = getIntegral($user_id); //用户的积分
        $orderPoint = $order['integral'];   //订单的积分
        if($orderPoint > $userPoint){
            $this->errorMsg('2001','积分不足');
        }
        update_pay_status($order['master_order_sn'],time());
        $orders = M('Order')->where(['master_order_sn'=>$master_order_sn,'user_id'=>$user_id])->getField('pay_status');
        if($orders == 1){
            $this->json('0000','支付成功','');
        }else{
            $this->json('9999','支付失败','');
        }

    }
    /**
     * 取消订单
     * [delorder description]
     * @Author   XD
     * @DateTime 2018-07-19T16:17:11+0800
     * @return   [type]                   [description]
     */
    public  function delorder(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $order_id = I("order_id"); // 订单id
        $master_order_sn = I("master_order_sn");// 订单编号
        $token = I("token");
        $user_id = $this->checkToken($token);
        if(empty($order_id)) $this->errorMsg('2001','order_id');
        if(empty($master_order_sn)) $this->errorMsg('2001','master_order_sn');
        $orderLogic = new \app\api\logic\OrderLogic;
        $data = $orderLogic->cancel_order($user_id,$order_id);
        if($data['status'] == 1) $this->json('0000',$data['msg']);
        else $this->throwError($data['msg']);
    }

    /**
     * 我的订单列表
     * [userOrder description]
     * @Author   XD
     * @DateTime 2018-07-19T16:17:58+0800
     * @return   [type]                   [description]
     */
    public function userOrder(){
        //goods_id 164 item_id 5 token 20e157278056257c71fead302face897
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $order_type = I("order_type/d"); // 商品id
        $page = I("page/d",'1');// 商品数量
        $token = I("token");
        $user_id = $this->checkToken($token);
        if(empty($order_type))  $this->errorMsg('2001','order_type');
        $order = new \app\common\model\Order();
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $where = ' user_id=:user_id';
        $bind['user_id'] = $user_id;
        $whereSon = [];
        $type = 0;
        switch ($order_type) {
            //全部订单
            case '1':
            $where.=' and order_status <> 5 ';//作废订单不列出来
                break;
            //代付款订单
            case '2':
            $where.= C('WAITPAY');//作废订单不列出来
                break;
            //待收货订单
            case '3':
                $order_ids = Db::table('tp_order')->alias('o')->join('order_goods og','o.order_id=og.order_id', 'left')
                    ->where('o.user_id',$user_id)->where('og.is_send', 1)->where('og.is_shouhuo', 0)
                    ->group('o.order_id')->field('o.order_id')
                    ->select();

                if(!$order_ids) $this->json('0000','获取成功',[]);
                //if(!$order_ids) return $this->errorMsg(8910);
                $where .= " and order_id in(" . implode(',',array_column($order_ids, 'order_id'))  . ")";
                $whereSon = [
                    'is_send' => 1,
                    'is_shouhuo' => 0,
                ];
                $type = 5;
//            $where.=C('WAITRECEIVE');//作废订单不列出来
                break;
            //已完成订单
            case '4':
                $order_ids = Db::table('tp_order')->alias('o')->join('order_goods og','o.order_id=og.order_id', 'left')
                    ->where('o.user_id',$user_id)->where('og.is_shouhuo', 1)
                    ->group('o.order_id')->field('o.order_id')
                    ->select();
                if(!$order_ids) $this->json('0000','获取成功',[]);
                //if(!$order_ids) return $this->errorMsg(8910);
                $where .= " and order_id in(" . implode(',',array_column($order_ids, 'order_id'))  . ")";
                $whereSon = [
                    'is_shouhuo' => 1,
                ];
                $type = 3;

//            $where.=C('FINISH');//作废订单不列出来
                break;
            //已取消订单
            case '5':
            $where.=C('CANCEL');//作废订单不列出来
                break;
            //待评价订单
            case '6':
                $order_ids = Db::table('tp_order')->alias('o')->join('order_goods og','o.order_id=og.order_id', 'left')
                    ->where('o.user_id',$user_id)->where('og.is_shouhuo', 1)->where('og.is_comment',0)->where(['og.is_send'=>['lt',3]])
                    ->group('o.order_id')->field('o.order_id')
                    ->select();
                if(!$order_ids) $this->json('0000','获取成功',[]);
                //if(!$order_ids) return $this->errorMsg(8910);
                $where .= " and order_id in(" . implode(',',array_column($order_ids, 'order_id'))  . ")";
                $whereSon = [
                    'is_shouhuo' => 1,
                    'is_comment' => 0
                ];
                $type = 2;
//             $where.=C('WAITCCOMMENT');
                break;
            //待发货订单
            case '7':
            $where.=C('WAITSEND');
                break;
            
        }
        $where.=' and deleted = 0 ';        //删除的订单不列出来
        //$where.=' and prom_type < 5 ';//虚拟订单不列出来
        $limit = ($page - 1) * 10;
        // $offset = ($page-1)*10;
        // $count = M('order'.$select_year)->where($where)->bind($bind)->count();
        // $Page = new Page($count, 10);
        // $show = $Page->show();
        $order_str = "order_id DESC";
        //获取订单
        $order_list_obj = M('order')->order($order_str)->where($where)->bind($bind)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,shipping_status,add_time,order_amount,type,dj,integral,prom_type,is_winning')->limit($limit,10)->select();
//         echo M('order')->getLastSql();die;
        $arr = [];
        if($order_list_obj){
            foreach($order_list_obj as $k => $v)
            {
                $arr[$k]['order_type']= $type > 0 ? $type :$order->getOrderStatusDetailAttr(null,$v);
//                if($v['type'] == 2){
//                    $arr[$k]['order_prices'] = $v['total_amount'];
//                }else{
//                    $arr[$k]['order_prices'] = $v['dj'];
//                }
                $arr[$k]['order_prices'] = $v['order_amount'];
                $arr[$k]['integral'] = $v['integral'];
                $arr[$k]['type'] = $v['type'];
                $arr[$k]['order_id'] = $v['order_id'];
                if($v['pay_status'] == 0 || $v['pay_status'] == 2){
                    $arr[$k]['master_order_sn'] = substr($v['master_order_sn'],0,8)."****";
                }else{
                    $arr[$k]['master_order_sn'] = $v['master_order_sn'];
                }
                $arr[$k]['prom_type'] = $v['prom_type'];         //0 普通订单 7拍卖订单 8秒杀订单
                $arr[$k]['is_winning'] = $v['is_winning'];      //1中奖的订单
                if($v['order_status'] == 3){
                    $arr[$k]['cancle_order'] = 1;
                }else{
                    $arr[$k]['cancle_order'] = 0;
                }
                // $v['order_button'] = $order->getOrderButtonAttr(null,$v);
                $arr[$k]['list'] = M('order_goods'.$select_year)->cache(true,3)->where($whereSon)->where('order_id = '.$v['order_id'])->field('goods_name,is_shouhuo,spec_key_name as spec,goods_num,final_price,rec_id,is_send,is_comment,goods_id,pay_integral,goods_price,all_point')->select();
                foreach ($arr[$k]['list'] as $key => $value) {
                    $arr[$k]['list'][$key]['goods_img'] = goods_thum_images($value['goods_id'],200,150);
                }
                // $v['store'] = M('store')->cache(true)->where('store_id = '.$v['store_id'])->field('store_id,store_name,store_qq')->find();
            }

        }
        $this->json('0000','获取成功',$arr);
    }

    public function getUserOrder($user_id){
        $order_type = I("order_type/d", 1); // 商品id
        $page = I("page/d",'1');// 商品数量
        $order = new \app\common\model\Order();
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $where = ' user_id=:user_id';
        $bind['user_id'] = $user_id;
        $whereSon = [];
        $type = 0;
        switch ($order_type) {
            //全部订单
            case '1':
                $where.=' and order_status <> 5 ';//作废订单不列出来
                break;
            //代付款订单
            case '2':
                $where.= C('WAITPAY');//作废订单不列出来
                break;
            //待收货订单
            case '3':
                $order_ids = Db::table('tp_order')->alias('o')->join('order_goods og','o.order_id=og.order_id', 'left')
                    ->where('o.user_id',$user_id)->where('og.is_send', 1)->where('og.is_shouhuo', 0)
                    ->group('o.order_id')->field('o.order_id')
                    ->select();

                if(!$order_ids) $this->json('0000','获取成功',[]);
                //if(!$order_ids) return $this->errorMsg(8910);
                $where .= " and order_id in(" . implode(',',array_column($order_ids, 'order_id'))  . ")";
                $whereSon = [
                    'is_send' => 1,
                    'is_shouhuo' => 0,
                ];
                $type = 5;
//            $where.=C('WAITRECEIVE');//作废订单不列出来
                break;
            //已完成订单
            case '4':
                $order_ids = Db::table('tp_order')->alias('o')->join('order_goods og','o.order_id=og.order_id', 'left')
                    ->where('o.user_id',$user_id)->where('og.is_shouhuo', 1)
                    ->group('o.order_id')->field('o.order_id')
                    ->select();
                if(!$order_ids) $this->json('0000','获取成功',[]);
                //if(!$order_ids) return $this->errorMsg(8910);
                $where .= " and order_id in(" . implode(',',array_column($order_ids, 'order_id'))  . ")";
                $whereSon = [
                    'is_shouhuo' => 1,
                ];
                $type = 3;

//            $where.=C('FINISH');//作废订单不列出来
                break;
            //已取消订单
            case '5':
                $where.=C('CANCEL');//作废订单不列出来
                break;
            //待评价订单
            case '6':
                $order_ids = Db::table('tp_order')->alias('o')->join('order_goods og','o.order_id=og.order_id', 'left')
                    ->where('o.user_id',$user_id)->where('og.is_shouhuo', 1)->where('og.is_comment',0)->where(['og.is_send'=>['lt',3]])
                    ->group('o.order_id')->field('o.order_id')
                    ->select();
                if(!$order_ids) $this->json('0000','获取成功',[]);
                //if(!$order_ids) return $this->errorMsg(8910);
                $where .= " and order_id in(" . implode(',',array_column($order_ids, 'order_id'))  . ")";
                $whereSon = [
                    'is_shouhuo' => 1,
                    'is_comment' => 0
                ];
                $type = 2;
//             $where.=C('WAITCCOMMENT');
                break;
            //待发货订单
            case '7':
                $where.=C('WAITSEND');
                break;

        }
        $where.=' and deleted = 0 ';        //删除的订单不列出来
        $limit = ($page - 1) * 10;
        $order_str = "order_id DESC";
        //获取订单
        $order_list_obj = M('order')->order($order_str)->where($where)->bind($bind)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,shipping_status,add_time,order_amount,type,dj,integral,prom_type,is_winning')->limit($limit,10)->select();
        $arr = [];
        if($order_list_obj){
            foreach($order_list_obj as $k => $v)
            {
                $arr[$k]['order_type']= $type > 0 ? $type :$order->getOrderStatusDetailAttr(null,$v);
                $arr[$k]['order_prices'] = $v['order_amount'];
                $arr[$k]['integral'] = $v['integral'];
                $arr[$k]['type'] = $v['type'];
                $arr[$k]['order_id'] = $v['order_id'];
                if($v['pay_status'] == 0 || $v['pay_status'] == 2){
                    $arr[$k]['master_order_sn'] = substr($v['master_order_sn'],0,8)."****";
                }else{
                    $arr[$k]['master_order_sn'] = $v['master_order_sn'];
                }
                $arr[$k]['prom_type'] = $v['prom_type'];         //0 普通订单 7拍卖订单 8秒杀订单
                $arr[$k]['is_winning'] = $v['is_winning'];      //1中奖的订单
                if($v['order_status'] == 3){
                    $arr[$k]['cancle_order'] = 1;
                }else{
                    $arr[$k]['cancle_order'] = 0;
                }
                // $v['order_button'] = $order->getOrderButtonAttr(null,$v);
                $arr[$k]['list'] = M('order_goods'.$select_year)->cache(true,3)->where($whereSon)->where('order_id = '.$v['order_id'])->field('goods_name,is_shouhuo,spec_key_name as spec,goods_num,final_price,rec_id,is_send,is_comment,goods_id,pay_integral,goods_price,all_point')->select();
                foreach ($arr[$k]['list'] as $key => $value) {
                    $arr[$k]['list'][$key]['goods_img'] = goods_thum_images($value['goods_id'],200,150);
                }
                // $v['store'] = M('store')->cache(true)->where('store_id = '.$v['store_id'])->field('store_id,store_name,store_qq')->find();
            }

        }
        return $arr;
    }

    /**
     * 订单详情
     * [detail description]
     * @Author   XD
     * @DateTime 2018-07-20T10:05:49+0800
     * @return   [type]                   [description]
     */
    public function detail(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $order_id = I("order_id/d"); // 商品id
        $token = I("token");
        $type = I('type/d');
        $user_id = $this->checkToken($token);
        if(empty($order_id))  $this->errorMsg('2001','order_id');
        if(empty($type))  $this->errorMsg('2001','type');
        $Order = new \app\common\model\Order();
        $map['order_id'] = $order_id;
        $map['user_id'] = $user_id;

        //1车型订单，2配件订单
        if($type == 2){
            $orderobj = $Order->where($map)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,shipping_status,add_time,order_amount,consignee,mobile,integral,order_integral')->find()->toArray();
            if(!$orderobj){
                $this->throwError('没有获取到订单信息');
            }
            $arr                    = [];
            $arr['order_type']      = $Order->getOrderStatusDetailAttr(null,$orderobj);
            $arr['order_prices']    = $orderobj['order_amount']??0;    //订单实际支付的金额
            $arr['order_id']        = $orderobj['order_id'];
            $arr['master_order_sn'] = substr($orderobj['master_order_sn'],0,8)."****";
            $arr['mobile']          = $orderobj['mobile'];
            $arr['name']            = $orderobj['consignee'];
            $arr['integral']        = $orderobj['integral']??0;        //订单实际使用的积分
            $arr['total_amount']    = $orderobj['total_amount']??0;    //订单原来的价格
            $arr['order_integral']  = $orderobj['order_integral']??0;  //订单原来要使用的积分

        }else{
             $orderobj = $Order->where($map)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,shipping_status,add_time,order_amount,dj,store_id,consignee,mobile,integral,order_integral')->find()->toArray();
            if(!$orderobj){
                $this->throwError('没有获取到订单信息');
            }
            $store                  = M('dealers')->where(['id'=>$orderobj['store_id']])->field('name,desc,mobile')->find();
            $arr                    = [];
            $arr['order_type']      = $Order->getOrderStatusDetailAttr(null,$orderobj);
            $arr['order_prices']    = $orderobj['order_amount']??0;      //订单实际支付的金额
            $arr['order_id']        = $orderobj['order_id'];
            $arr['master_order_sn'] = substr($orderobj['master_order_sn'],0,8)."****";
            $arr['name']            = $orderobj['consignee'];
            $arr['mobile']          = $orderobj['mobile'];
            $arr['store_name']      = $store['name'];
            $arr['store_address']   = $store['desc'];
            $arr['store_phone']     = $store['mobile'];
            $arr['integral']        = $orderobj['integral']??0;        //订单实际使用的积分
            $arr['total_amount']    = $orderobj['total_amount']??0;    //订单原来的价格
            $arr['order_integral']  = $orderobj['order_integral']??0;  //订单原来要使用的积分
        }
        $arr['type'] = $type;
     
        // $v['order_button']   = $order->getOrderButtonAttr(null,$v);
        //获取订单
        $arr['list'] = M('order_goods'.$select_year)->cache(true,3)->where('order_id = '.$orderobj['order_id'])->field('goods_name,spec_key_name as spec,is_shouhuo,is_send,goods_num,goods_price,final_price,rec_id,is_comment,goods_id,pay_integral,all_point,type')->select();
        foreach ($arr['list'] as $key => $value) {
            $arr['list'][$key]['goods_img'] = goods_thum_images($value['goods_id'],200,150);
        }
        $this->json('0000','获取成功',$arr);   
    }

    /**
     * 订单商品详情
     * [orderGoods description]
     * @Author   XD
     * @DateTime 2018-07-20T10:18:14+0800
     * @return   [type]                   [description]
     */
    public function orderGoods(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $order_id    = I("order_id/d"); // 商品id
        $rec_id      = I("rec_id/d"); // 商品id
        $token       = I("token");
        $user_id     = $this->checkToken($token);
        if(empty($order_id))  $this->errorMsg('2001','order_id');
        if(empty($rec_id))  $this->errorMsg('2001','rec_id');
        $Order = new \app\common\model\Order();
        $map['order_id'] = $order_id;
        $map['user_id'] = $user_id;
        $orderobj = $Order->where($map)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,shipping_status,add_time,order_amount')->find()->toArray();
        if(!$orderobj){
            $this->throwError('没有获取到订单信息');
        }
        if($Order->getOrderStatusDetailAttr(null,$orderobj)!=6)  $this->throwError('该订单不能进行评价');
        $arr = M('order_goods')->where('rec_id = '.$rec_id)->field('goods_name,spec_key_name as spec,goods_num,final_price  as goods_price,rec_id,is_comment,goods_id')->find(); 
        $arr['goods_img'] = goods_thum_images($arr['goods_id'],200,150);
        $this->json('0000','获取成功',$arr);   

    }

    /**
     * 更新购物车，并返回计算结果
     */
    public function AsyncUpdateCart($user_id,$cart_id)
    {
        $cart = explode(',', $cart_id);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $result = $cartLogic->AsyncUpdateCart($cart);
        return $result;
    }

    /**
     *  获取用户收货地址 用于购物车确认订单页面
     */
    public function ajaxAddress($user_id,$type=1)
    {
        //$type == 1是没有address_id
        //$type == 2 是有address_id
        if($type == 1){
            $arr['address_list'] = Db::name('UserAddress')->where(["user_id"=>$user_id,'status'=>1])->order('is_default desc')->select();
        }else{
             $arr['address_list'] = Db::name('UserAddress')->where(["address_id"=>$user_id,'status'=>1])->select();
        }

        if ($arr['address_list']) {
            $area_id = array();
            foreach ($arr['address_list'] as $val) {
                $area_id[] = $val['province'];
                $area_id[] = $val['city'];
                $area_id[] = $val['district'];
                $area_id[] = $val['twon'];
            }
            $area_id = array_filter($area_id);
            $area_id = implode(',', $area_id);
            $arr['regionList'] = Db::name('region')->where("id", "in", $area_id)->getField('id,name');
            // $this->assign('regionList', $regionList);
        }
        if($type == 1){
            $c = Db::name('UserAddress')->where(['user_id' => $user_id, 'is_default' => 1])->count(); // 看看有没默认收货地址
            if ((count($arr['address_list']) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
                $arr['address_list'][0]['is_default'] = 1;
        }

         $res = [];
         $address = $arr['regionList'][$arr['address_list'][0]['province']].'-'.$arr['regionList'][$arr['address_list'][0]['city']].'-'.$arr['regionList'][$arr['address_list'][0]['district']].'-'. $arr['address_list'][0]['address'];
         $res['id'] =  $arr['address_list'][0]['address_id'];
         $res['name'] =  $arr['address_list'][0]['consignee'];
         $res['mobile'] =  $arr['address_list'][0]['mobile'];
         $res['address'] =  $address;
        return $res;
        // $this->assign('arr['address_list']', $arr['address_list']);
        // return $this->fetch('ajax_address');
    }

    /**
     * 生成的订单的详情
     * @Autoh: 胡宝强
     * Date: 2018/7/25 22:26
     */
    public function orderDetail(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $order_sn = I("order_sn"); //订单号
        $token = I("token");
        //$type = I('type/d');
        $user_id = $this->checkToken($token);
        if(empty($order_sn))  $this->errorMsg('2001','order_sn');
        //if(empty($type))  $this->errorMsg('2001','type');
        $Order = new \app\common\model\Order();
        $map['master_order_sn'] = $order_sn;
        $map['user_id'] = $user_id;

        //获取用户的积分
        $model = new Users();
        $users = $model->get_userinfo($user_id);

        //1车型订单，2配件订单
        $orderobj = $Order->where($map)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,shipping_status,add_time,order_amount,dj,store_id,consignee,mobile,integral,integral_money')->find();
        if(!$orderobj){
            $this->throwError('没有获取到订单信息');
        }
        if($orderobj['pay_status'] == 1){
            $this->throwError('订单已经支付了');
        }
        $store                  = M('dealers')->where(['id'=>$orderobj['store_id']])->field('name,desc,mobile')->find();
        $arr                    = [];
        //$arr['order_type']      = $Order->getOrderStatusDetailAttr(null,$orderobj);
//        $arr['tatle_price']     = $orderobj['dj'];
        $arr['tatle_price']     = $orderobj['order_amount'];  //要支付的金额
        $arr['order_id']        = $orderobj['order_id'];
        $arr['master_order_sn'] = substr($orderobj['master_order_sn'],0,8)."****";
        $arr['name']            = $orderobj['consignee'];
        $arr['mobile']          = $orderobj['mobile'];
        $arr['store_name']      = $store['name'];
        $arr['store_address']   = $store['desc'];
        $arr['store_mobile']    = $store['mobile'];
        $arr['points']          = $orderobj['integral'];
        $arr['integral_money']  = $orderobj['integral_money'];


        // $v['order_button']   = $order->getOrderButtonAttr(null,$v);
        //获取订单
        $arr['list'] = M('order_goods'.$select_year)->cache(true,3)->where('order_id = '.$orderobj['order_id'])->field('goods_name,spec_key_name as spec,goods_num,final_price as goods_price,goods_id,pay_integral')->find();

            $arr['list']['goods_img'] = goods_thum_images($arr['list']['goods_id'],200,150);

        $this->json('0000','成功',$arr);
    }

    /**
     * 确认收货
     * @Autoh: 胡宝强
     * Date: 2018/8/2 10:06
     */
    public function queren(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $select_year = select_year();
        $order_id = I("order_id"); //订单号
        $rec_id = I('rec_id'); //子订单号
        $token = I("token");
        $user_id = $this->checkToken($token);
        if(empty($order_id))  $this->errorMsg('2001','order_id');
        if(empty($rec_id))  $this->errorMsg('2001','rec_id');
        $order = M('order'.$select_year)->where(['order_id'=>$order_id,'user_id'=>$user_id])->find();
        if(empty($order)) $this->errorMsg('3001');
        if($order['order_status'] == 0) $this->errorMsg('3005');
        if($order['order_status'] == 3) $this->errorMsg('3004');
        if($order['order_status'] == 4) $this->errorMsg('3002');
        $order_goods = M('order_goods')->where(['rec_id'=>$rec_id,'order_id'=>$order_id])->find();
        if(empty($order_goods)) $this->errorMsg('3001');
        $order_goods_save = M('order_goods')->where(['rec_id'=>$rec_id,'order_id'=>$order_id])->save(['is_shouhuo'=>1,'shouhuo_time'=>time()]);
        $shouhuo = M('order_goods')->where(['order_id'=>$order_id,'is_shouhuo'=>1])->select();
        if(!$shouhuo){
            $arr = M('order'.$select_year)->where(['order_id'=>$order_id,'user_id'=>$user_id])->save(['order_status'=>2,'confirm_time'=>time()]);
            if($order_goods_save && $arr) {
                (new \app\common\logic\PointLogic())->deliveryPoint($user_id,$order_id,$rec_id,$order_goods['final_price']);

                $this->json('0000','确认收货成功');
            }else {
                $this->json('9999','确认收货失败');
            }
        }else{
            if($order_goods_save) $this->json('0000','确认收货成功');
            else $this->json('9999','确认收货失败');
        }
    }

    /**
     * 删除订单
     * @Autor: 胡宝强
     * Date: 2018/8/10 20:01
     */
    public function deleteOrder(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $token = I("token");
        $user_id = $this->checkToken($token);
        $order_id = I('order_id');
        if(empty($order_id))  $this->errorMsg('2001','order_id');
        if(!is_numeric($order_id)) $this->errorMsg('2001','order_id');
        $data = M('order')->where(['order_id'=>$order_id,'user_id'=>$user_id])->find();
        if(empty($data)) $this->errorMsg('9999','该订单不存在');
        $row = M('order')->where(['user_id'=>$user_id,'order_id'=>$order_id])->update(['deleted'=>1]);
        if ($row) {
            $order_goods = M('order_goods')->where(['order_id'=>$order_id])->update(['deleted'=>1]);
            if($order_goods){
                $this->json('0000','删除订单成功');
            }else{
                $this->json('9999','删除订单失败');
            }
        }else{
            $this->json('9999','删除订单失败');
        }
    }

}
