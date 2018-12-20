<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采有最新thinkphp5助手函数特性实现函数简写方式M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: dyr
 * Date: 2017-12-04
 */

namespace app\common\logic;
use app\common\model\Order;
use app\common\model\team\TeamActivity;
use app\common\model\Users;
use app\common\util\TpshopException;
use think\Cache;
use think\Hook;
use think\Model;
use think\Db;
/**
 * 提交下单类
 * Class CatsLogic
 * @package Home\Logic
 */
class PlaceOrder
{
    private $invoiceTitle;
    private $userNote;
    private $taxpayer;
    private $pay;
    private $order;
    private $userAddress;
    private $payPsw;
    private $promType;
    private $promId;
    private $type = 2;

    private $orderList;
    private $masterOrderSn;//主订单号

    public function __construct(Pay $pay)
    {
        $this->pay = $pay;
        $this->order = new Order();
    }

    /**
     * 设置密码后加密
     * @param unknown $payPsw
     */
    public function setPayPsw($payPsw)
    {
        $this->payPsw = encrypt($payPsw);
    }
    
    /**
     * 不需要加密设置
     * @param unknown $payPsw
     */
    public function setPayPswNoEncrypt($payPsw)
    {
        $this->payPsw = $payPsw;
    }

    public function setInvoiceTitle($invoiceTitle)
    {
        $this->invoiceTitle = $invoiceTitle;
    }

    public function setUserNote($userNote)
    {
        if (is_array($userNote)) {
            $this->userNote = $userNote;
        } else {
            foreach ($this->pay->getStoreListPayInfo() as $storePayKey => $storePayVal) {
                $this->userNote = [$storePayKey => $userNote];
                return;
            }
        }
    }
    public function setTaxpayer($taxpayer)
    {
        $this->taxpayer = $taxpayer;
    }

    public function setUserAddress($userAddress)
    {
        $this->userAddress = $userAddress;
    }
        public function setType($type)
    {
        $this->type = $type;
    }

    private function setPromType($prom_type)
    {
        $this->promType = $prom_type;
    }
    private function setPromId($prom_id)
    {
        $this->promId = $prom_id;
    }

    /**
     * 订单提交
     * @Autor: 胡宝强
     * Date: 2018/8/18 17:43
     * @param $action               buy_now 立即下单 cart_now 购物车下单
     * @throws TpshopException
     */
    public function addNormalOrder($action)
    {
        //$this->check();  //检查如果使用积分和余额的时候要用支付密码
        $this->queueInc();
        $this->addOrder();
        $this->addOrderGoods($action);
        $reduce = tpCache('shopping.reduce');
        foreach($this->orderList as $orderKey=>$orderVal){
            Hook::listen('user_add_order', $orderVal);//下单行为
            if($reduce== 1 || empty($reduce)){
                minus_stock($orderVal);//下单减库存
            }
//            // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
//            if ($orderVal['order_amount'] == 0) {
//                update_pay_status($orderVal['order_sn']);
//            }
        }
        $this->deductionCoupon();//扣除优惠券
        $this->changUserPointMoney();//扣除用户积分余额
        $this->queueDec();

    }

    /**
     * [拍卖下单]
     * @Auther 蒋峰
     * @DateTime
     */
    public function addAuctionOrder($auction_id, $promtype)
    {
        $this->setPromType($promtype);
        $this->setPromId($auction_id);
        $this->check();
        $this->queueInc();
        $this->addOrder();
        $this->addOrderGoods('auction');
//        $reduce = tpCache('shopping.reduce');
        foreach($this->orderList as $orderKey=>$orderVal){
            Hook::listen('user_add_order', $orderVal);//下单行为
            /*if($reduce== 1 || empty($reduce)){
                minus_stock($orderVal);//下单减库存
            }*/
            // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
            if ($orderVal['order_amount'] == 0) {
                update_pay_status($orderVal['order_sn']);
            }
        }
        $this->queueDec();

    }

    public function addTeamOrder(TeamActivity $teamActivity)
    {
        $this->setPromType(6);
        $this->setPromId($teamActivity['team_id']);
        $this->check();
        $this->queueInc();
        $this->addOrder();
        $this->addOrderGoods();
        $reduce = tpCache('shopping.reduce');
        foreach($this->orderList as $orderKey=>$orderVal){
            Hook::listen('user_add_order', $orderVal);//下单行为
            if($teamActivity['team_type'] != 2){
                if($reduce == 1 || empty($reduce)){
                    minus_stock($orderVal);//下单减库存
                }
            }
            // 如果应付金额为0  可能是余额支付 + 积分 + 优惠券 这里订单支付状态直接变成已支付
            if ($orderVal['order_amount'] == 0) {
                update_pay_status($orderVal['order_sn']);
            }
        }
        $this->queueDec();
    }



    /**
     * 获取订单表数据
     * @return Order
     */
    public function getOrderList()
    {
        return $this->orderList;
    }

    /**
     * 提交订单前检查
     * @throws TpshopException
     */
    public function check()
    {
        $pay_points = $this->pay->getPayPoints();
        $user_money = $this->pay->getUserMoney();
        if ($pay_points || $user_money) {
            $user = $this->pay->getUser();
            if ($user['is_lock'] == 1) {
                throw new TpshopException('提交订单', 0, ['status'=>-5,'msg'=>"账号异常已被锁定，不能使用余额支付！",'result'=>'']);
            }
            if (empty($user['paypwd'])) {
                throw new TpshopException('提交订单', 0, ['status'=>-6,'msg'=>"请先设置支付密码",'result'=>'']);
            }
            if (empty($this->payPsw)) {
                throw new TpshopException('提交订单', 0, ['status'=>-7,'msg'=>"请输入支付密码",'result'=>'']);
            }
            if ($this->payPsw !== $user['paypwd']) {
                throw new TpshopException('提交订单', 0, ['status'=>-8,'msg'=>'支付密码错误','result'=>'']);
            }
        }

    }

    private function queueInc()
    {
        $queue = Cache::get('queue');
        if($queue >= 100){
            throw new TpshopException('提交订单', 0, ['status' => -99, 'msg' => "当前人数过多请耐心排队!" . $queue, 'result' => '']);
        }
        Cache::inc('queue');
    }

    /**
     * 订单提交结束
     */
    private function queueDec()
    {
        Cache::dec('queue');
    }

    /**
     * 插入订单表
     * @throws TpshopException
     */
    private function addOrder()
    {
        $OrderLogic = new OrderLogic();
        $user = $this->pay->getUser();
        $store_list_pay_info = $this->pay->getStoreListPayInfo();
        $this->masterOrderSn = $OrderLogic->get_order_sn();//先生成主订单号
        $orderAllData = [];
        foreach($store_list_pay_info as $payInfoKey => $payInfoVal){
            $orderData = [
                'order_sn'         =>$OrderLogic->get_order_sn(), // 订单编号
                'master_order_sn'  =>$this->masterOrderSn, // 主订单编号
                'user_id'          =>$user['user_id'], // 用户id
                'goods_price'      =>$payInfoVal['goods_price'],//'商品价格',
                'total_amount'     =>$payInfoVal['total_amount'],// 订单总额
                'order_amount'     =>$payInfoVal['order_amount'],//'应付款金额',
                'add_time'         =>time(), // 下单时间
                'store_id'         =>$payInfoKey,
                'type'             =>$this->type,
                'dj'               =>$this->pay->dj,
                'order_integral'   =>$payInfoVal['order_integral'],
                'all_car_money'    =>$this->pay->all_car_money
            ];
            //运费
            if($this->pay->getShippingPrice() > 0){
                $orderData['shipping_price'] = $payInfoVal['shipping_price'];
            }else{
                $orderData['shipping_price'] = 0;
            }
            //使用余额
            if($this->pay->getUserMoney() > 0){
                $orderData['user_money'] = $payInfoVal['user_money'];
            }else{
                $orderData['user_money'] = 0;
            }
            //使用积分
            if($this->pay->getPayPoints() > 0){
                $orderData['integral'] = $payInfoVal['integral'];
                $orderData['integral_money'] = $payInfoVal['integral_money'];
            }else{
                $orderData['integral'] = 0;
                $orderData['integral_money'] = 0;
            }
            //使用优惠券
            if($this->pay->getCouponPrice() > 0){
                $orderData['coupon_price'] = $payInfoVal['coupon_price'];
            }else{
                $orderData['coupon_price'] = 0;
            }
            if($this->pay->getOrderPromAmount() > 0){
                $orderData['order_prom_id'] = $payInfoVal['order_prom_id'];
                $orderData['order_prom_amount'] = $payInfoVal['order_prom_amount'];
            }else{
                $orderData['order_prom_id'] = 0;
                $orderData['order_prom_amount'] = 0;
            }
            //用户备注
            if(!empty($this->userNote)){
                $orderData['user_note'] = $this->userNote[$payInfoKey];
            }
            //用户地址
            if(!empty($this->userAddress)){
                $orderData['consignee'] = $this->userAddress['consignee'];// 收货人
                $orderData['province'] = $this->userAddress['province']??'';//'省份id',
                $orderData['city'] = $this->userAddress['city']??'';//'城市id',
                $orderData['district'] = $this->userAddress['district']??'';//'县',
                $orderData['twon'] = $this->userAddress['twon']??'';// '街道',
                $orderData['address'] = $this->userAddress['address']??'';//'详细地址'
                $orderData['mobile'] = $this->userAddress['mobile']??'';//'手机',
                $orderData['zipcode'] = $this->userAddress['zipcode']??'';//'邮编',
                $orderData['email'] = $this->userAddress['email']??'';//'邮箱'
            }
            //发票抬头
            if(!empty($this->invoiceTitle)){
                $orderData['invoice_title'] = $this->invoiceTitle;
            }
            //发票纳税人识别号
            if(!empty($this->taxpayer)){
                $orderData['taxpayer'] = $this->taxpayer;
            }
            //支付方式，可能是余额支付或积分兑换，后面其他支付方式会替换
            if($orderData['integral'] > 0 || $orderData['user_money'] > 0){
                $orderData['pay_name'] = $orderData['user_money'] ? '余额支付' : '积分兑换';
            }
            if($this->promType){
                $orderData['prom_type'] = $this->promType;//订单类型
            }
            if($this->promId > 0){
                $orderData['prom_id'] = $this->promId;//活动id
            }
            array_push($orderAllData, $orderData);
        }
        $orderSaveList =  $this->order->saveAll($orderAllData);
        if ($orderSaveList === false) {
            throw new TpshopException("订单入库", 0, ['status' => -8, 'msg' => '添加订单失败', 'result' => '']);
        }
        $this->orderList = $orderSaveList;
    }

    /**
     * 插入订单商品表
     * $action   buy_now 立即下单 cart_now 购物车下单
     */
    private function addOrderGoods($action)
    {
        $payList = $this->pay->getPayList();
        $goods_ids = get_arr_column($payList,'goods_id');
        if($action == "auction"){
            $goodsArr = Db::name('goods')->where('goods_id', 'IN', $goods_ids)->getField('goods_id,cost_price,give_integral,cat_id3,integral');
        }elseif($action == "buy_car"){
            $goodss_integral = M('goods')->where('goods_id', 'IN', $goods_ids)->getField('integral');
        }else{
            $goodsArr = Db::name('goods')->where('goods_id', 'IN', $goods_ids)->getField('goods_id,cost_price,give_integral,cat_id3');
        }
//        $goods_cat_id3s = get_arr_column($goodsArr, 'cat_id3');
//        $goodsCatArr = Db::name('goods_category')->where("id", "in", $goods_cat_id3s)->cache(true, TPSHOP_CACHE_TIME)->getField('id,commission'); // 商品抽成比例
        $point_rate = tpCache('shopping.point_rate'); //兑换比例
        $orderGoodsAllData = [];
        foreach($payList as $payKey => $payItem)
        {
            $order                                   = $this->findStoreOrder($payItem['store_id']);//找到订单
            if($order['goods_price'] == 0){
                //这个商品是纯积分的没有金额
                //$totalPriceToRatio                       = $payItem['member_goods_price'] / $order['goods_price'];  //商品价格占总价的比例
                $orderDiscounts                          = $order['order_prom_amount'] + $order['coupon_price']; //订单优惠价钱
                //$finalPrice                              = round($payItem['member_goods_price'] - $orderDiscounts, 3);// 每件商品实际支付价格
                $finalPrice                              = 0;// 每件商品实际支付价格

            }else{
                if($action =='cart_now'){
                    //购物车进来的
                    $payItem['member_goods_price']  += round(($payItem['all_integral'] * $payItem['goods_num'] - $payItem['integral'])/$point_rate,2) + $payItem['member_goods_price'] * $payItem['goods_num'] - $payItem['member_goods_price'];
                }else if ($action =='auction'){

                }else{
                    //直接购买的
                    $payItem['member_goods_price']  += round(($payItem['all_integrals'] - $payItem['integral'])/$point_rate,2) + $payItem['member_goods_price'] * $payItem['goods_num'] - $payItem['member_goods_price'];
                }

                $totalPriceToRatio                       = $payItem['member_goods_price'] / $order['goods_price'];  //商品价格占总价的比例
                $orderDiscounts                          = $order['order_prom_amount'] + $order['coupon_price']; //订单优惠价钱
                $finalPrice                              = round($payItem['member_goods_price'] - ($totalPriceToRatio * $orderDiscounts), 3);// 每件商品实际支付价格
            }
            $orderGoodsData['order_id']              = $order['order_id']; // 订单id
            $orderGoodsData['goods_id']              = $payItem['goods_id']; // 商品id
            $orderGoodsData['goods_name']            = $payItem['goods_name']; // 商品名称
            $orderGoodsData['goods_sn']              = $payItem['goods_sn']; // 商品货号
            $orderGoodsData['goods_num']             = $payItem['goods_num']; // 购买数量
            $orderGoodsData['final_price']           = $finalPrice; // 每件商品实际支付价格
            if($payItem['type'] == 1){
                $orderGoodsData['goods_price']           = $payItem['dj']; // 商品价               为照顾新手开发者们能看懂代码，此处每个字段加于详细注释
            }else{
                $orderGoodsData['goods_price']           = $payItem['goods_price']; // 商品价               为照顾新手开发者们能看懂代码，此处每个字段加于详细注释
            }
            if(!empty($payItem['spec_key'])){
                $orderGoodsData['spec_key']          = $payItem['spec_key']; // 商品规格
                $orderGoodsData['spec_key_name']     = $payItem['spec_key_name']; // 商品规格名称
            }else{
                $orderGoodsData['spec_key']          = ''; // 商品规格
                $orderGoodsData['spec_key_name']     = ''; // 商品规格名称
            }
            $orderGoodsData['sku']                   = $payItem['sku']??''; // sku
    
            $orderGoodsData['member_goods_price']    = $payItem['member_goods_price']; // 会员折扣价
            $orderGoodsData['cost_price']            = $goodsArr[$payItem['goods_id']]['cost_price']; // 成本价
            $orderGoodsData['give_integral']         = $goodsArr[$payItem['goods_id']]['give_integral']; // 购买商品赠送积分
            $orderGoodsData['prom_type']             = $payItem['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            $orderGoodsData['prom_id']               = $payItem['prom_id']; // 活动id
            $orderGoodsData['store_id']              = $payItem['store_id']; // 店铺id
            $orderGoodsData['type']                  = $payItem['type'];     // 商品的类型 1 汽车 2配件

            if($action != "auction"){
                $orderGoodsData['pay_integral']          = $payItem['integral']; // 这个商品使用的积分
                $orderGoodsData['pay_integral_money']    = round($payItem['integral']/$point_rate,2); // 这个商品使用的积分兑换了多少钱
                if($action == 'buy_now'){
                    //$orderGoodsData['one_point']              = $payItem['one_point']; // 这个商品原来的总积分
                    $orderGoodsData['all_point']              = $payItem['all_integrals']; // 这个商品原来的总积分
                }elseif($action == 'cart_now'){
                    $orderGoodsData['all_point']              = $payItem['all_integral']; // 这个商品原来的总积分
                    //$orderGoodsData['one_point']              = $payItem['all_integral']; // 这个商品原来的总积分
                }elseif($action == 'buy_car'){
                    $orderGoodsData['all_point']              = $goodss_integral; // 这个商品原来的总积分
                    //$orderGoodsData['one_point']              = $goodss_integral; // 这个商品原来的总积分
                }
            }

//            $orderGoodsData['distribut']             = $goodsCatArr[$goodsArr[$payItem['goods_id']]['cat_id3']]['commission']; // 三级分销金额
            // $orderGoodsData['distribut']             = $goodsArr[$payItem['goods_id']]['distribut']; // 三级分销金额
            array_push($orderGoodsAllData, $orderGoodsData);
        }
        Db::name('order_goods')->insertAll($orderGoodsAllData);
    }

    /**
     * 扣除优惠券
     */
    public function deductionCoupon()
    {
        $userCoupons = $this->pay->getUserCoupon();
        if($userCoupons){
            $user = $this->pay->getUser();
            $couponListData['uid'] = $user['user_id'];
            $couponListData['use_time'] = time();
            $couponListData['status'] = 1;
            foreach($userCoupons as $couponItemKey=>$couponItemVal){
                $order = $this->findStoreOrder($couponItemVal['store_id']);
                $couponListData['order_id'] = $order['order_id'];
                Db::name('coupon_list')->where('id',$couponItemVal['id'])->update($couponListData);
                Db::name('coupon')->where('id',$couponItemVal['cid'])->setInc('use_num');// 优惠券的使用数量加一
            }
        }
    }

    /**
     * 扣除用户积分余额
     */
    public function changUserPointMoney()
    {
        if($this->pay->getPayPoints() > 0 || $this->pay->getUserMoney() > 0){
            $user = $this->pay->getUser();
            $user = Users::get($user['user_id']);
            if($this->pay->getPayPoints() > 0){
                $user->pay_points = $user->pay_points - $this->pay->getPayPoints();// 消费积分
            }
            if($this->pay->getUserMoney() > 0){
                $user->user_money = $user->user_money - $this->pay->getUserMoney();// 抵扣余额
            }
            $user->save();
            $storeListPayInfo = $this->pay->getStoreListPayInfo();
            $accountLogAllData = [];
            foreach($storeListPayInfo as $payInfoKey => $payInfoVal){
                $order = $this->findStoreOrder($payInfoKey);
                $accountLogData = [
                    'user_id' => $order['user_id'],
                    'user_money' => -$payInfoVal['user_money'],
                    'pay_points' => -$payInfoVal['integral'],
                    'change_time' => time(),
                    'desc' => '下单消费',
                    'order_sn' => $order['order_sn'],
                    'order_id' => $order['order_id'],
                ];
                array_push($accountLogAllData, $accountLogData);
            }
            Db::name('account_log')->insertAll($accountLogAllData);
        }
    }

    /**
     * 这方法特殊，只限拼团使用。
     * @param $order_list
     */
    public function setOrderList($order_list)
    {
        $this->orderList = $order_list;
    }

    /**
     * 获取主订单号ID
     */
    public function getMasterOrderSn()
    {
        return $this->masterOrderSn;
    }

    /**
     * 获取单个店铺订单
     * @param $store_id
     * @return null
     */
    private function findStoreOrder($store_id){
        foreach($this->orderList as $orderKey => $orderVal){
            if($orderVal['store_id'] == $store_id){
                return $orderVal;
            }
        }
        return null;
    }
}