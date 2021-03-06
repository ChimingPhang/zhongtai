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
use app\common\model\CouponList;
use app\common\util\TpshopException;
use think\Model;
use think\Db;
/**
 * 计算价格类
 * Class CatsLogic
 * @package Home\Logic
 */
class Pay
{
    protected $payList;
    protected $userId;
    protected $user;

    private $totalAmount = 0;//订单总价
    private $orderAmount = 0;//应付金额
    private $shippingPrice = 0;//物流费
    private $goodsPrice = 0;//商品总价
    private $cutFee = 0;//共节约多少钱
    private $anum = 0;// 商品总共数量
    private $integralMoney = 0;//积分抵消金额
    private $userMoney = 0;//使用余额
    private $payPoints = 0;//使用积分
    private $couponPrice = 0;//优惠券抵消金额
    private $orderPromAmount = 0;
    public $dj = 0;
    public $all_car_money = 0;

    private $storeListPayInfo;//各个店铺的价格信息，key为店铺ID
    private $storeList;//店铺信息
    private $userCoupons;
    private $remaining_point = 0;  //使用的积分除了纯积分商品外还剩余的积分数量
    private $point_money_count = 0;  //使用积分和金额支付的商品的总数量
    private $point_money_all = 0;  //使用积分和金额支付的商品的总积分


    /**
     * 计算订单表的普通订单商品
     * @param $order_goods
     * @throws TpshopException
     */
    public function payOrder($order_goods){
        $this->payList = $order_goods;
        $order = Db::name('order')->where('order_id',  $this->payList[0]['order_id'])->find();
        if(empty($order)){
            throw new TpshopException('计算订单价格', 0, ['status' => -9, 'msg' => '找不到订单数据', 'result' => '']);
        }
        $reduce = tpCache('shopping.reduce');
        if($order['pay_status'] == 0 && $reduce == 2){
            $goodsListCount = count($this->payList);
            for ($payCursor = 0; $payCursor < $goodsListCount; $payCursor++) {
                $goods_stock = getGoodNum($this->payList[$payCursor]['goods_id'], $this->payList[$payCursor]['spec_key']); // 最多可购买的库存数量
                if($goods_stock <= 0 && $this->payList[$payCursor]['goods_num'] > $goods_stock){
                    throw new TpshopException('计算订单价格', 0, ['status' => -9, 'msg' => $this->payList[$payCursor]['goods_name'].','.$this->payList[$payCursor]['spec_key_name'] . "库存不足,请重新下单", 'result' => '']);
                }
            }
        }
        $this->Calculation();
    }

    /**
     * 计算购买购物车的商品
     * @param $cart_list
     * @throws TpshopException
     */
    public function payCart($cart_list){
        $this->payList = $cart_list;
        $goodsListCount = count($this->payList);
        if ($goodsListCount == 0) {
            throw new TpshopException('计算订单价格', 0, ['status' => -9, 'msg' => '你的购物车没有选中商品', 'result' => '']);
        }
        $this->Calculation();
    }

    /**
     * 计算购买商品表的商品
     * @param $goods_list
     * @throws TpshopException
     */
    public function payGoodsList($goods_list)
    {
        $goods_list_count = count($goods_list);
        if ($goods_list_count == 0) {
            throw new TpshopException('计算订单价格', 0, ['status' => -9, 'msg' => '你的购物车没有选中商品', 'result' => '']);
        }
        for ($goods_cursor = 0; $goods_cursor < $goods_list_count; $goods_cursor++) {
            if(empty($goods_list[$goods_cursor]['member_goods_price'])){
                $goods_list[$goods_cursor]['member_goods_price'] = $goods_list[$goods_cursor]['goods_price'];//优先使用member_goods_price，没有member_goods_price使用goods_price
            }
        }
        $this->payList = $goods_list;
        $this->Calculation();
    }


    /**
     * 初始化计算
     */
    private function Calculation()
    {
        $goodsListCount = count($this->payList);
        for ($payCursor = 0; $payCursor < $goodsListCount; $payCursor++) {
            if(!isset($this->payList[$payCursor]['store_id'])){
                $this->payList[$payCursor]['store_id'] = NULL;
            }
            // dump($this->payList[$payCursor]['store_id']);die;
            $this->payList[$payCursor]['goods_fee'] = $this->payList[$payCursor]['goods_num'] * $this->payList[$payCursor]['member_goods_price'];    // 小计
            $this->goodsPrice += $this->payList[$payCursor]['goods_fee']; // 商品总价
            if(array_key_exists('market_price',$this->payList[$payCursor])){
                $this->cutFee += $this->payList[$payCursor]['goods_num'] * ($this->payList[$payCursor]['market_price'] - $this->payList[$payCursor]['member_goods_price']);// 共节约
            }
            $this->anum += $this->payList[$payCursor]['goods_num'];
            $this->storeListPayInfo[$this->payList[$payCursor]['store_id']]['goods_price'] += $this->payList[$payCursor]['goods_fee']; //每个商家的商品总价
            $this->storeListPayInfo[$this->payList[$payCursor]['store_id']]['order_amount'] = $this->storeListPayInfo[$this->payList[$payCursor]['store_id']]['goods_price']; //每个商家的应付金额
            $this->storeListPayInfo[$this->payList[$payCursor]['store_id']]['total_amount'] = $this->storeListPayInfo[$this->payList[$payCursor]['store_id']]['goods_price']; //每个商家的订单总价

        }
        $store_ids = array_keys($this->storeListPayInfo);//获取store_id集合
        $this->storeList = Db::name('store')->where('store_id', 'in', $store_ids)->cache(true, 100)->getField('store_id,store_free_price,store_state');
        $this->orderAmount = $this->goodsPrice;
        $this->totalAmount = $this->goodsPrice;
    }

    /**
     * 设置用户ID
     * @throws TpshopException
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $this->userId = $user_id;
        $this->user = Db::name('users')->where(['user_id' => $this->userId])->find();
        if(empty($this->user)){
            throw new TpshopException("计算订单价格",0,['status' => -9, 'msg' => '未找到用户', 'result' => '']);
        }
    }



    /**
     * 车型订单使用积分
     * @throws TpshopException
     * @param $pay_points
     * @param $is_exchange|是否有使用积分兑换商品流程
     */
    public function useCarPayPoints($pay_points, $is_exchange = false,$all_point)
    {
        //echo $this->orderAmount;die;
        if($pay_points > 0 && $this->orderAmount > 0){
            //积分和金额同时使用
            $point_rate = tpCache('shopping.point_rate'); //兑换比例
            if($is_exchange == false){
//                $use_percent_point = tpCache('shopping.point_use_percent');     //最大使用限制: 最大使用积分比例, 例如: 为50时, 未50% , 那么积分支付抵扣金额不能超过应付金额的50%
//                $min_use_limit_point = tpCache('shopping.point_min_limit'); //最低使用额度: 如果拥有的积分小于该值, 不可使用
//                if($use_percent_point == 0){
//                    throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => '该笔订单不能使用积分', 'result' => '']);
//                }
//                if($use_percent_point > 0 && $use_percent_point < 100){
//                    //计算订单最多使用多少积分
//                    $point_limit = $this->orderAmount * $point_rate * $use_percent_point;
//                    if($pay_points > $point_limit){
//                        throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => "该笔订单, 您使用的积分不能大于" . $point_limit, 'result' => '']);
//                    }
//                }
//                if($pay_points > $this->user['pay_points']){
//                    throw new TpshopException("计算订单价格",0,['status' => -5, 'msg' => "你的账户可用积分为:" . $this->user['pay_points'], 'result' => '']);
//                }
//                if ($min_use_limit_point > 0 && $pay_points < $min_use_limit_point) {
//                    throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => "您使用的积分必须大于".$min_use_limit_point."才可以使用", 'result' => '']);
//                }
                //$order_amount_pay_point = floor($this->orderAmount * $point_rate);  //最多可以使用的积分
                //使用的积分大于订单总额的积分比例则使用的积分为
                if($pay_points > $all_point){
                    //使用的积分大于总体能用的积分
                    $this->payPoints = $all_point;
                }else{
                    $this->payPoints = $pay_points;
                }
            }else{
                //积分兑换流程
                if($pay_points <= $this->user['pay_points']){
                    $this->payPoints = $pay_points;
                }else{
                    $this->payPoints = 0;//需要兑换的总积分
                }
            }
            $surplus_pay_points = $this->payPoints;

            foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
//                $proportion = $infoVal['order_amount'] / $this->orderAmount;//每个商家订单应付金额占总应付金额比例
//                $store_integral = (int)($proportion * $surplus_pay_points); //每个商家平摊用了多少积分;
//                $surplus_pay_points = $surplus_pay_points - $store_integral;//剩余用户积分
//                $this->storeListPayInfo[$infoKey]['integral'] = $store_integral;//每个商家平摊用了多少积分
//                $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //每个商家平摊用了多少积分抵扣金额
//                //$this->storeListPayInfo[$infoKey]['order_amount'] = round(round($this->storeListPayInfo[$infoKey]['order_amount'], 4) - round($this->storeListPayInfo[$infoKey]['integral_money'], 4), 2);// 每个商家减去积分支付抵消的
                //$sheng_point = $all_point - $surplus_pay_points;  //差多少积分

                $this->storeListPayInfo[$infoKey]['integral'] = $surplus_pay_points;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //每个商家平摊用了多少积分抵扣金额
                $sheng_point = $all_point-$surplus_pay_points;  //商品的总积分进去使用的积分还剩下的积分
                $sheng_money = round($sheng_point/$point_rate,2);   //剩下的积分所转化成的金额
                $this->storeListPayInfo[$infoKey]['order_amount'] = round(round($this->storeListPayInfo[$infoKey]['order_amount'], 4) + $sheng_money, 2); //商家去掉积分抵扣的钱后要支付的金额
                $this->storeListPayInfo[$infoKey]['order_integral'] = $all_point; //这个订单要支付的总积分
            }
//            if($surplus_pay_points > 0){
//                //把剩余的用户积分平摊给第一个商家订单
//                foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
//                    $this->storeListPayInfo[$infoKey]['integral'] += $surplus_pay_points;//第一个商家平摊用了多少积分抵扣金额
//                    $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //第一个商家平摊用了多少积分抵扣金额
//                    $this->storeListPayInfo[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['integral_money'];// 第一个商家减去积分支付抵消的
//                    break;
//                }
//            }
            $this->integralMoney = $this->payPoints / $point_rate;//总积分兑换成的金额
            $this->orderAmount = $this->orderAmount - $this->integralMoney;

        }elseif($pay_points > 0 && $this->orderAmount == 0){
            //只使用积分购买
            $point_rate = tpCache('shopping.point_rate'); //兑换比例
            if($is_exchange == false){
                if($pay_points > $this->user['pay_points']){
                    throw new TpshopException("计算订单价格",0,['status' => -5, 'msg' => "你的账户可用积分为:" . $this->user['pay_points'], 'result' => '']);
                }
//                if ($min_use_limit_point > 0 && $pay_points < $min_use_limit_point) {
//                    throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => "您使用的积分必须大于".$min_use_limit_point."才可以使用", 'result' => '']);
//                }
                //$order_amount_pay_point = floor($this->orderAmount * $point_rate);  //最多可以使用的积分
                //使用的积分大于订单总额的积分比例则使用的积分为
                if($pay_points >= $all_point){
                    //使用的积分大于总体能用的积分
                    $this->payPoints = $all_point;
                }else{
                    $this->payPoints = $pay_points;
                }
            }else{
                //积分兑换流程
                if($pay_points <= $this->user['pay_points']){
                    $this->payPoints = $pay_points;
                }else{
                    $this->payPoints = 0;//需要兑换的总积分
                }
            }
            $surplus_pay_points = $this->payPoints;
            foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
                //$proportion = $infoVal['order_amount'] / $this->orderAmount;//每个商家订单应付金额占总应付金额比例
                //$store_integral = (int)($proportion * $surplus_pay_points); //每个商家平摊用了多少积分;
                //$surplus_pay_points = $surplus_pay_points - $store_integral;//剩余用户积分
                //$this->storeListPayInfo[$infoKey]['integral'] = $store_integral;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral'] = $surplus_pay_points;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //每个商家平摊用了多少积分抵扣金额
                //$this->storeListPayInfo[$infoKey]['order_amount'] = round(round($this->storeListPayInfo[$infoKey]['order_amount'], 4) - round($this->storeListPayInfo[$infoKey]['integral_money'], 4), 2);// 每个商家减去积分支付抵消的
                $this->storeListPayInfo[$infoKey]['order_amount'] = 0;
                $this->storeListPayInfo[$infoKey]['order_integral'] = $all_point; //这个订单要支付的总积分
            }
//            if($surplus_pay_points > 0){
//                //把剩余的用户积分平摊给第一个商家订单
//                foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
//                    $this->storeListPayInfo[$infoKey]['integral'] += $surplus_pay_points;//第一个商家平摊用了多少积分抵扣金额
//                    $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //第一个商家平摊用了多少积分抵扣金额
//                    //$this->storeListPayInfo[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['integral_money'];// 第一个商家减去积分支付抵消的
//                    $this->storeListPayInfo[$infoKey]['order_amount'] = 0;
//                    break;
//                }
//            }

            $this->integralMoney = $this->payPoints / $point_rate;//总积分兑换成的金额
//            $this->orderAmount = $this->orderAmount - $this->integralMoney;
            $this->orderAmount = 0;
            $this->orderIntegral = $all_point;  //这个订单要支付的总积分
        }
    }


    /**
     * 配件订单使用积分
     * @throws TpshopException
     * @param $pay_points           用户使用的积分
     * @param $is_exchange|是否有使用积分兑换商品流程
     * @param $all_point| 购买的商品总共最多可以使用多少积分
     */
    public function usePayPoints($pay_points, $is_exchange = false,$all_point)
    {
        //echo $this->orderAmount;die;
        if($pay_points > 0 && $this->orderAmount > 0){
            //积分和金额同时使用
            $point_rate = tpCache('shopping.point_rate'); //兑换比例
            if($is_exchange == false){
//                $use_percent_point = tpCache('shopping.point_use_percent');     //最大使用限制: 最大使用积分比例, 例如: 为50时, 未50% , 那么积分支付抵扣金额不能超过应付金额的50%
//                $min_use_limit_point = tpCache('shopping.point_min_limit'); //最低使用额度: 如果拥有的积分小于该值, 不可使用
//                if($use_percent_point == 0){
//                    throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => '该笔订单不能使用积分', 'result' => '']);
//                }
//                if($use_percent_point > 0 && $use_percent_point < 100){
//                    //计算订单最多使用多少积分
//                    $point_limit = $this->orderAmount * $point_rate * $use_percent_point;
//                    if($pay_points > $point_limit){
//                        throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => "该笔订单, 您使用的积分不能大于" . $point_limit, 'result' => '']);
//                    }
//                }
                if($pay_points > $this->user['pay_points']){
                    throw new TpshopException("计算订单价格",0,['status' => -5, 'msg' => "你的账户可用积分为:" . $this->user['pay_points'], 'result' => '']);
                }
//                if ($min_use_limit_point > 0 && $pay_points < $min_use_limit_point) {
//                    throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => "您使用的积分必须大于".$min_use_limit_point."才可以使用", 'result' => '']);
//                }
                //$order_amount_pay_point = floor($this->orderAmount * $point_rate);  //最多可以使用的积分
                //使用的积分大于订单总额的积分比例则使用的积分为
                if($pay_points > $all_point){
                    //使用的积分大于总体能用的积分
                    $this->payPoints = $all_point;
                }else{
                    $this->payPoints = $pay_points;
                }
            }else{
                //积分兑换流程
                if($pay_points <= $this->user['pay_points']){
                    $this->payPoints = $pay_points;
                }else{
                    $this->payPoints = 0;//需要兑换的总积分
                }
            }
            $surplus_pay_points = $this->payPoints;     //总的用户使用的积分数量
            $this->remaining_point = $this->payPoints;  //还剩下的积分数量
            $this->point_money_all = $all_point;  //除了纯积分后剩下的积分
            foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
//                $proportion = $infoVal['order_amount'] / $this->orderAmount;//每个商家订单应付金额占总应付金额比例
//                $store_integral = (int)($proportion * $surplus_pay_points); //每个商家平摊用了多少积分;
//                $surplus_pay_points = $surplus_pay_points - $store_integral;//剩余用户积分
//                $this->storeListPayInfo[$infoKey]['integral'] = $store_integral;//每个商家平摊用了多少积分
//                $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //每个商家平摊用了多少积分抵扣金额
//                //$this->storeListPayInfo[$infoKey]['order_amount'] = round(round($this->storeListPayInfo[$infoKey]['order_amount'], 4) - round($this->storeListPayInfo[$infoKey]['integral_money'], 4), 2);// 每个商家减去积分支付抵消的
                //$sheng_point = $all_point - $surplus_pay_points;  //差多少积分

                //这些商品要使用的总积分/商品的个数

                $this->storeListPayInfo[$infoKey]['integral'] = $surplus_pay_points;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //每个商家平摊用了多少积分抵扣金额
                $sheng_point = $all_point-$surplus_pay_points;  //商品的总积分进去使用的积分还剩下的积分
                $sheng_money = round($sheng_point/$point_rate,2);   //剩下的积分所转化成的金额
                $this->storeListPayInfo[$infoKey]['order_amount'] = round(round($this->storeListPayInfo[$infoKey]['order_amount'], 4) + $sheng_money, 2); //商家去掉积分抵扣的钱后要支付的金额
                $this->storeListPayInfo[$infoKey]['order_integral'] = $all_point; //这个订单要支付的总积分
            }
            //$this->payList    选择要支付的商品的信息
            /*
            foreach($this->payList as $key=>$value){
                if($value['exchange_integral'] == 2){
                    //这个商品是纯积分商品
                    $this->payList[$key]['integral'] = $value['integral'] * $value['goods_num'];
                    $this->remaining_point = $surplus_pay_points - $value['integral'] * $value['goods_num']; //剩下的积分
                }

                if($value['exchange_integral'] == 1){
                    //积分和金额的商品
                    //$this->remaining_point = $surplus_pay_points;
                    //$this->point_money_count += $value['goods_num'];
                    //$this->point_money_all += $value['integral'] * $value['goods_num'];
                    //$this->payList[$key]['integral'] = 1; //先让剩下的是积分和金额的商品每个先分到1积分
                    //$this->remaining_point -= 1;           //剩下的积分
                    //如果还剩下的积分比第一个商品的积分大则这个商品的积分就是设置的积分
                    if($this->remaining_point >= $value['integral'] * $value['goods_num']){
                        //echo $value['integral'] * $value['goods_num'];die;
                        $this->payList[$key]['integral'] = $value['integral'] * $value['goods_num'];
                        $this->remaining_point = $this->remaining_point - $value['integral'] * $value['goods_num'];
                        echo $this->payList[$key]['integral']. 'cc';die;
                    }else{

                        //$this->payList[$key]['member_goods_price'] = round(($value['integral'] * $value['goods_num'] - $this->remaining_point)/$point_rate,2);
                        if($this->remaining_point <= $value['integral'] * $value['goods_num']){
                            $this->payList[$key]['integral'] = $this->remaining_point;
                            $this->remaining_point -= $this->remaining_point;
                            echo $this->payList[$key]['integral']. 'aa';die;
                        }else{
                            $this->payList[$key]['integral'] = $value['integral'] * $value['goods_num'];
                            $this->remaining_point -= $value['integral'] * $value['goods_num'];
                            echo $this->payList[$key]['integral']. 'bb';die;
                        }
                        break;
                    }
                }
            }
            */
            //判断这个商品当是纯积分商品的时候
            foreach($this->payList as $k=>$v){
                if($v['exchange_integral'] == 2){
                    //这个商品是纯积分商品
                    $this->remaining_point = $this->remaining_point - ($v['integral'] * $v['goods_num']); //剩下的积分
                    $this->point_money_all = $this->point_money_all - $v['integral'] * $v['goods_num'];  //积分和金额混合的商品的积分总和
//                    $this->payList[$k]['integral'] = $v['integral'] * $v['goods_num'];
                    $this->payList[$k]['integral'] = $v['integral'];

                }
            }

//            echo $this->remaining_point;die;

            if($this->remaining_point == 0 && $this->point_money_all == 0){

            }else{
                //剩下的积分占总积分的比例
                $baifen = round($this->remaining_point/$this->point_money_all,4);
//            echo $baifen;die;
                $store_integral = 0;
                foreach($this->payList as $k=>$v){
                    if($v['exchange_integral'] == 1){
                        $store_integral += (int)($v['integral'] * $v['goods_num']*$baifen); //每个商家平摊用了多少积分;
                        $this->payList[$k]['integral'] = (int)($v['integral'] * $v['goods_num']*$baifen);
                    }
                }
//            echo $store_integral;die;
//            echo $this->remaining_point;die;
                $surplus_pay_pointsss = $this->remaining_point - $store_integral; //每个商品平均分配后还剩余的积分
//            echo $surplus_pay_pointsss;die;
                if($surplus_pay_pointsss > 0){
                    //把剩余的用户积分平摊给第一个商家订单
                    foreach ($this->payList as $k => $v) {
                        if($v['exchange_integral'] == 1){
                            $this->payList[$k]['integral'] += $surplus_pay_pointsss;//第一个商家平摊用了多少积分抵扣金额
                            if($this->payList[$k]['integral'] > $v['integral'] * $v['goods_num']){
                                $this->payList[$k]['integral'] = $v['integral'] * $v['goods_num'];
                            }

//                    $this->payList[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //第一个商家平摊用了多少积分抵扣金额
//                    $this->payList[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['integral_money'];// 第一个商家减去积分支付抵消的
                            break;
                        }
                    }
                }
            }


//            if($surplus_pay_points > 0){
//                //把剩余的用户积分平摊给第一个商家订单
//                foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
//                    $this->storeListPayInfo[$infoKey]['integral'] += $surplus_pay_points;//第一个商家平摊用了多少积分抵扣金额
//                    $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //第一个商家平摊用了多少积分抵扣金额
//                    $this->storeListPayInfo[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['integral_money'];// 第一个商家减去积分支付抵消的
//                    break;
//                }
//            }
            $this->integralMoney = $this->payPoints / $point_rate;//总积分兑换成的金额
            $this->orderAmount = $this->orderAmount - $this->integralMoney;

        }elseif($pay_points > 0 && $this->orderAmount == 0){
            //只使用积分购买
            $point_rate = tpCache('shopping.point_rate'); //兑换比例
            if($is_exchange == false){
                if($pay_points > $this->user['pay_points']){
                    throw new TpshopException("计算订单价格",0,['status' => -5, 'msg' => "你的账户可用积分为:" . $this->user['pay_points'], 'result' => '']);
                }
//                if ($min_use_limit_point > 0 && $pay_points < $min_use_limit_point) {
//                    throw new TpshopException("计算订单价格",0,['status' => -1, 'msg' => "您使用的积分必须大于".$min_use_limit_point."才可以使用", 'result' => '']);
//                }
                //$order_amount_pay_point = floor($this->orderAmount * $point_rate);  //最多可以使用的积分
                //使用的积分大于订单总额的积分比例则使用的积分为
                if($pay_points >= $all_point){
                    //使用的积分大于总体能用的积分
                    $this->payPoints = $all_point;
                }else{
                    $this->payPoints = $pay_points;
                }
            }else{
                //积分兑换流程
                if($pay_points <= $this->user['pay_points']){
                    $this->payPoints = $pay_points;
                }else{
                    $this->payPoints = 0;//需要兑换的总积分
                }
            }
            $surplus_pay_points = $this->payPoints;
            foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
                //$proportion = $infoVal['order_amount'] / $this->orderAmount;//每个商家订单应付金额占总应付金额比例
                //$store_integral = (int)($proportion * $surplus_pay_points); //每个商家平摊用了多少积分;
                //$surplus_pay_points = $surplus_pay_points - $store_integral;//剩余用户积分
                //$this->storeListPayInfo[$infoKey]['integral'] = $store_integral;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral'] = $surplus_pay_points;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //每个商家平摊用了多少积分抵扣金额
                //$this->storeListPayInfo[$infoKey]['order_amount'] = round(round($this->storeListPayInfo[$infoKey]['order_amount'], 4) - round($this->storeListPayInfo[$infoKey]['integral_money'], 4), 2);// 每个商家减去积分支付抵消的
                $this->storeListPayInfo[$infoKey]['order_amount'] = 0;
                $this->storeListPayInfo[$infoKey]['order_integral'] = $all_point; //这个订单要支付的总积分
            }
//            if($surplus_pay_points > 0){
//                //把剩余的用户积分平摊给第一个商家订单
//                foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
//                    $this->storeListPayInfo[$infoKey]['integral'] += $surplus_pay_points;//第一个商家平摊用了多少积分抵扣金额
//                    $this->storeListPayInfo[$infoKey]['integral_money'] = $this->storeListPayInfo[$infoKey]['integral'] / $point_rate; //第一个商家平摊用了多少积分抵扣金额
//                    //$this->storeListPayInfo[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['integral_money'];// 第一个商家减去积分支付抵消的
//                    $this->storeListPayInfo[$infoKey]['order_amount'] = 0;
//                    break;
//                }
//            }
            foreach($this->payList as $k=>$v){
                $this->payList[$k]['integral'] = $v['integral'];

            }

            $this->integralMoney = $this->payPoints / $point_rate;//总积分兑换成的金额
//            $this->orderAmount = $this->orderAmount - $this->integralMoney;
            $this->orderAmount = 0;
        }else{
            //只使用金额购买
            foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
                //$proportion = $infoVal['order_amount'] / $this->orderAmount;//每个商家订单应付金额占总应付金额比例
                //$store_integral = (int)($proportion * $surplus_pay_points); //每个商家平摊用了多少积分;
                //$surplus_pay_points = $surplus_pay_points - $store_integral;//剩余用户积分
                //$this->storeListPayInfo[$infoKey]['integral'] = $store_integral;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral'] = 0;//每个商家平摊用了多少积分
                $this->storeListPayInfo[$infoKey]['integral_money'] = 0; //每个商家平摊用了多少积分抵扣金额
                //$this->storeListPayInfo[$infoKey]['order_amount'] = round(round($this->storeListPayInfo[$infoKey]['order_amount'], 4) - round($this->storeListPayInfo[$infoKey]['integral_money'], 4), 2);// 每个商家减去积分支付抵消的
                $this->storeListPayInfo[$infoKey]['order_amount'] = round($this->storeListPayInfo[$infoKey]['order_amount'], 4);
                $this->storeListPayInfo[$infoKey]['order_integral'] = 0; //这个订单要支付的总积分
            }

            $this->orderAmount = $this->orderAmount;
        }
    }

    /**
     * 使用余额
     * @throws TpshopException
     * @param $user_money
     */
    public function useUserMoney($user_money)
    {
        if($user_money > 0 && $this->orderAmount > 0){
            if($user_money > $this->user['user_money']){
                throw new TpshopException("计算订单价格",0,['status' => -6, 'msg' =>  "你的账户可用余额为:" . $this->user['user_money'], 'result' => '']);
            }
            if($user_money > $this->orderAmount){
                $this->userMoney = $this->orderAmount;
            }else{
                $this->userMoney = $user_money;
            }
            $surplus_user_money = $this->userMoney;
            foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
                $proportion = $infoVal['order_amount'] / $this->orderAmount;//每个商家订单应付金额占总应付金额比例
                $store_user_money = round($proportion * $this->userMoney, 2);//每个商家平摊用了多少余额,保留两位小数;
                $surplus_user_money = $surplus_user_money - $store_user_money;//剩余用户金额
                $this->storeListPayInfo[$infoKey]['user_money'] = $store_user_money;
                $this->storeListPayInfo[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['user_money'];// 每个商家减去余额支付抵消的
            }
            //把剩余的用户余额平摊给第一个商家订单
            foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
                $this->storeListPayInfo[$infoKey]['user_money'] += $surplus_user_money; //第一个商家平摊用了多少余额
                $this->storeListPayInfo[$infoKey]['order_amount'] -= $surplus_user_money;// 第一个商家减去余额支付抵消的
                break;
            }
            $this->orderAmount = $this->orderAmount - $this->userMoney;
        }
    }

    /**
     * 目前仅限拼团使用
     * 减去应付金额
     * @param $cut_money
     */
    public function cutOrderAmount($cut_money){
        $this->orderAmount = $this->orderAmount - $cut_money;
    }

    /**
     *
     * 使用优惠券
     * @param $coupons => [store_id=>coupon_id]
     */
    public function useCoupons($coupons){
        // 循环优惠券
        $coupon_count = count($coupons);
        if($coupon_count > 0 && $coupons){
            $coupon_list_ids = [];
            foreach ($coupons as $couponStoreId => $couponId){
                array_push($coupon_list_ids, $couponId);
            }
            $couponList = new CouponList();
            $coupon_list = $couponList->where(['uid' => $this->user['user_id'], 'deleted' => 0, 'status' => 0, 'id' => ['in', $coupon_list_ids]])->select();
            if($coupon_list){
                $coupon_ids = get_arr_column($coupon_list, 'cid');
                $coupon_arr = Db::name('coupon')->where(['id' => ['in', $coupon_ids], 'status' => 1])->select(); // 获取有效优惠券类型表
                if ($coupon_arr) {
                    foreach ($coupon_arr as $couponKey => $couponVal) {
                        $this->couponPrice += $couponVal['money'];
                        $this->storeListPayInfo[$couponVal['store_id']]['coupon_price'] = $couponVal['money'];
                        $this->storeListPayInfo[$couponVal['store_id']]['order_amount'] = $this->storeListPayInfo[$couponVal['store_id']]['order_amount'] - $couponVal['money'];
                    }
                    $this->orderAmount = $this->orderAmount - $this->couponPrice;
                    $this->userCoupons = $coupon_list;
                }
            }
        }
    }

    /**
     * 获取用户使用是优惠券
     */
    public function getUserCoupon()
    {
        return $this->userCoupons;
    }

    /**
     * 配送
     * @param $district_id
     * @throws TpshopException
     */
    public function delivery($district_id){
        if(empty($district_id)){
            throw new TpshopException("计算订单价格",0,['status'=>-1,'msg'=>'请填写收货信息','result'=>['']]);
        }
        $GoodsLogic = new GoodsLogic();
        $checkGoodsShipping = $GoodsLogic->checkGoodsListShipping($this->payList, $district_id);
        foreach($checkGoodsShipping as $shippingKey => $shippingVal){
            if($shippingVal['shipping_able'] != true){
                throw new TpshopException("计算订单价格",0,['status'=>-1,'msg'=>'订单中部分商品不支持对当前地址的配送请返回购物车修改','result'=>['goods_shipping'=>$checkGoodsShipping]]);
            }
        }
        $store_goods_shipping = $GoodsLogic->getStoreFreight($this->payList, $district_id);
        foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
            // 如果店铺设置满额包邮并且商品总价大于或等于满额包邮则运费为零
            if($this->storeList[$infoKey]['store_free_price'] != 0 && $infoVal['goods_price'] >= $this->storeList[$infoKey]['store_free_price']){
                $this->storeListPayInfo[$infoKey]['shipping_price'] = 0;
            }else{
                $this->storeListPayInfo[$infoKey]['shipping_price'] = $store_goods_shipping[$infoKey];//各个商家的物流费
                $this->storeListPayInfo[$infoKey]['order_amount'] = $this->storeListPayInfo[$infoKey]['order_amount'] + $store_goods_shipping[$infoKey];
                $this->storeListPayInfo[$infoKey]['total_amount'] = $this->storeListPayInfo[$infoKey]['total_amount'] + $store_goods_shipping[$infoKey];
                $this->shippingPrice += $store_goods_shipping[$infoKey];
            }
        }
        $this->orderAmount = $this->orderAmount + $this->shippingPrice;
        $this->totalAmount = $this->totalAmount + $this->shippingPrice;
    }

    /**
     * 使用订单优惠
     */
    public function orderPromotion()
    {
        $time = time();
        $order_prom_where = ['type'=>['lt',2],'end_time'=>['gt',$time],'start_time'=>['lt',$time]];
        foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
            $order_prom_where['store_id'] = $infoKey;
            $order_prom_where['money'] = ['elt',$infoVal['goods_price']];
            $order_prom = Db::name('prom_order')->where($order_prom_where)->order('money desc')->find();
            if ($order_prom) {
                if ($order_prom['type'] == 0) {
                    $expression_amount = round($infoVal['goods_price'] * $order_prom['expression'] / 100, 2);//满额打折
                    $this->storeListPayInfo[$infoKey]['order_prom_amount'] = round($infoVal['goods_price'] - $expression_amount, 2);
                    $this->storeListPayInfo[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['order_prom_amount'];
                    $this->storeListPayInfo[$infoKey]['order_prom_id'] = $order_prom['id'];
                    $this->storeListPayInfo[$infoKey]['order_prom_title'] = $order_prom['title'];
                } elseif ($order_prom['type'] == 1) {
                    $this->storeListPayInfo[$infoKey]['order_prom_amount'] = $order_prom['expression'];
                    $this->storeListPayInfo[$infoKey]['order_amount'] -= $this->storeListPayInfo[$infoKey]['order_prom_amount'];
                    $this->storeListPayInfo[$infoKey]['order_prom_id'] = $order_prom['id'];
                    $this->storeListPayInfo[$infoKey]['order_prom_title'] = $order_prom['title'];
                }else{
                    $this->storeListPayInfo[$infoKey]['order_prom_amount'] = 0;
                    $this->storeListPayInfo[$infoKey]['order_prom_id'] = 0;
                    $this->storeListPayInfo[$infoKey]['order_prom_title'] = '';
                }
            }else{
                $this->storeListPayInfo[$infoKey]['order_prom_amount'] = 0;
                $this->storeListPayInfo[$infoKey]['order_prom_id'] = 0;
                $this->storeListPayInfo[$infoKey]['order_prom_title'] = '';
            }
            $this->orderPromAmount += $this->storeListPayInfo[$infoKey]['order_prom_amount'];
        }
        $this->orderAmount = $this->orderAmount - $this->orderPromAmount;
    }

    /**
     * 获取实际上使用的余额
     * @return int
     */
    public function getUserMoney()
    {
        return $this->userMoney;
    }

    /**
     * 获取订单总价
     * @return int
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * 获取订单应付金额
     * @return int
     */
    public function getOrderAmount()
    {
        return number_format($this->orderAmount, 2, '.', '');
    }

    /**
     * 获取实际上使用的积分抵扣金额
     * @return float
     */
    public function getIntegralMoney(){
        return $this->integralMoney;
    }

    /**
     * 获取实际上使用的积分
     * @return float|int
     */
    public function getPayPoints()
    {
        return $this->payPoints;
    }

    /**
     * 获取优惠券金额
     * @return int
     */
    public function getCouponPrice()
    {
       return $this->couponPrice;
    }

    /**
     * 商品总价
     * @return int
     */
    public function getGoodsPrice()
    {
        return $this->goodsPrice;
    }

    /**
     * 获取用户
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 获取计算价格的商品列表
     * @return mixed
     */
    public function getPayList()
    {
        return $this->payList;
    }

    /**
     * 获取各个商家的价格信息
     * @return mixed
     */
    public function getStoreListPayInfo()
    {
        return $this->storeListPayInfo;
    }

    /**
     * 获取运费
     * @return int
     */
    public function getShippingPrice()
    {
        return $this->shippingPrice;
    }

    /**
     * 获取订单优惠总额
     * @return int
     */
    public function getOrderPromAmount()
    {
        return $this->orderPromAmount;
    }

    public function toArray()
    {
        return [
            'shipping_price'            =>$this->shippingPrice,
            'coupon_price'              =>$this->couponPrice,
            'user_money'                =>$this->userMoney,
            'integral_money'            =>$this->integralMoney,
            'pay_points'                =>$this->payPoints,
            'order_amount'              =>round($this->orderAmount,2),
            'total_amount'              =>round($this->totalAmount,2),
            'goods_price'               =>$this->goodsPrice,
            'order_prom_amount'         =>$this->orderPromAmount,
            'store_list_pay_info'       =>$this->storeListPayInfo
        ];
    }

    /**
     * 配送
     * @param $district_id
     * @throws TpshopException
     */
    public function delivery_car($district_id,$goods_idds,$car_lists){
        if(empty($district_id)){
            throw new TpshopException("计算订单价格",0,['status'=>-1,'msg'=>'请填写收货信息','result'=>['']]);
        }
        $GoodsLogic = new GoodsLogic();
        $checkGoodsShipping = $GoodsLogic->checkGoodsListShipping_car($goods_idds, $district_id);
        foreach($checkGoodsShipping as $shippingKey => $shippingVal){
            if($shippingVal['shipping_able'] != true){
                throw new TpshopException("计算订单价格",0,['status'=>-1,'msg'=>'订单中部分商品不支持对当前地址的配送请返回购物车修改','result'=>['goods_shipping'=>$checkGoodsShipping]]);
            }
        }
        $store_goods_shipping = $GoodsLogic->getStoreFreight_car($car_lists, $district_id);
        return $store_goods_shipping;
        //dump($store_goods_shipping);die;
//        foreach ($this->storeListPayInfo as $infoKey => $infoVal) {
//            // 如果店铺设置满额包邮并且商品总价大于或等于满额包邮则运费为零
//            if($this->storeList[$infoKey]['store_free_price'] != 0 && $infoVal['goods_price'] >= $this->storeList[$infoKey]['store_free_price']){
//                $this->storeListPayInfo[$infoKey]['shipping_price'] = 0;
//            }else{
//                $this->storeListPayInfo[$infoKey]['shipping_price'] = $store_goods_shipping[$infoKey];//各个商家的物流费
//                $this->storeListPayInfo[$infoKey]['order_amount'] = $this->storeListPayInfo[$infoKey]['order_amount'] + $store_goods_shipping[$infoKey];
//                $this->storeListPayInfo[$infoKey]['total_amount'] = $this->storeListPayInfo[$infoKey]['total_amount'] + $store_goods_shipping[$infoKey];
//                $this->shippingPrice += $store_goods_shipping[$infoKey];
//            }
//        }
//        $this->orderAmount = $this->orderAmount + $this->shippingPrice;
//        $this->totalAmount = $this->totalAmount + $this->shippingPrice;
    }
}