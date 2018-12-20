<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */

namespace app\api\logic;

use app\api\model\GoodsAuction;
use app\api\model\GoodsSeckill;
use app\common\model\Cart;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\logic\GoodsPromFactory;
use app\common\util\TpshopException;
use app\seller\model\SpecGoodsPrice;
use think\Model;
use think\Db;

/**
 * 购物车 逻辑定义
 * Class CatsLogic
 * @package common\Logic
 */
class CartLogic extends Model
{
    public $goods;//商品模型
    protected $specGoodsPrice;//商品规格模型
    protected $goodsBuyNum;//购买的商品数量
    protected $session_id;//session_id
    protected $user_id = 0;//user_id
    protected $userGoodsTypeCount = 0;//用户购物车的全部商品种类
    protected $userStoreCouponNumArr; //用户符合购物车店铺可用优惠券数量
    protected $limit_points = 0;  //最低使用的积分数量
    protected $height_points = 0;  //最高使用的积分数量
    protected $all_point = 0;     //使用全部积分的数量
    protected $most_point = 0;      //购物车中的商品最多可以使用多少积分
    protected $minimum_point = 0;   //购物车中的商品最少可以使用多少积分

    public function __construct()
    {
        parent::__construct();
        $this->session_id = session_id();
    }

    /**
     * 将session_id改成unique_id
     * @param $uniqueId|api唯一id 类似于 pc端的session id
     */
    public function setUniqueId($uniqueId){
        $this->session_id = $uniqueId;
    }

    /**
     * 包含一个商品模型
     * @param $goods_id
     */
    public function setGoodsModel($goods_id)
    {
        if($goods_id){
            $goodsModel = new Goods();
            $this->goods = $goodsModel::get($goods_id,'',10);
        }
    }

    /**
     * 包含一个商品模型
     * @param $goods_id
     */
    public function setAuctionModel($goods_id)
    {
        if($goods_id){
            $goodsModel = new GoodsAuction();
            $this->goods = $goodsModel::get($goods_id,'',10);
        }
    }

    /**
     * 包含一个商品模型
     * @param $goods_id
     */
    public function setSeckillModel($goods_id)
    {
        if($goods_id){
            $goodsModel = new GoodsSeckill();
            $this->goods = $goodsModel::get($goods_id,'',10);
        }
    }

    /**
     * 包含一个商品规格模型
     * @param $item_id
     */
    public function setSpecGoodsPriceModel($item_id)
    {
        if($item_id){
            $specGoodsPriceModel = new SpecGoodsPrice();
            $this->specGoodsPrice = $specGoodsPriceModel::get($item_id,'',10);
        }
    }
     /**
     * 包含一个商品规格模型
     * @param $item_id
     */
    public function setGoodsSkuModel($item_id)
    {
        if($item_id){
            $specGoodsPriceModel = new GoodsSku();
            $this->specGoodsPrice = $specGoodsPriceModel::get($item_id,'',10);
        }
    }

    /**
     * 设置用户ID
     * @param $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * 设置购买的商品数量
     * @param $goodsBuyNum
     */
    public function setGoodsBuyNum($goodsBuyNum)
    {
        $this->goodsBuyNum = $goodsBuyNum;
    }
        /**
     * 立即购买
     * @return array|mixed
     * @throws TpshopException
     */
    public function CarbuyNow($key_name='',$store_id='',$pay_points = 0,$exchange_integral = 0,$item_id=''){
        if(empty($this->goods)){
            // throw new TpshopException('立即购买',0,['status'=>0,'msg'=>'购买商品不存在','result'=>'']);
            throwError('购买商品不存在');
        }
        if(empty($this->goodsBuyNum)){
            // throw new TpshopException('立即购买',0,['status'=>0,'msg'=>'购买商品数量不能为0','result'=>'']);
             throwError('购买商品数量不能为0');
        }
        if($exchange_integral == 0){
            //纯金额
            $buyGoodss['all_integrals'] = 0;
            $buyGoodss['dj'] = $this->goods['deposit_price']??0;
        }elseif($exchange_integral == 2){
            $buyGoodss['all_integrals'] = $this->goods['integral'] * $this->goodsBuyNum;
            $buyGoodss['dj'] = 0;
        }elseif($exchange_integral == 1){
            $buyGoodss['all_integrals'] = $this->goods['integral'] * $this->goodsBuyNum;
            $buyGoodss['dj'] = $this->goods['integral_money']??0;
        }
        $buyGoods = [
            'user_id'=>$this->user_id,
            'session_id'=>$this->session_id,
            'goods_id'=>$this->goods['goods_id'],
            'goods_sn'=>$this->goods['goods_sn'],
            'goods_name'=>$this->goods['goods_name'],
            'market_price'=>$this->goods['market_price'],
            'goods_price'=>$this->goods['shop_price'],
            'member_goods_price'=>$this->goods['shop_price'],
            //'dj'=>$this->goods['deposit_price']??0,
            'goods_num' => $this->goodsBuyNum, // 购买数量
            'add_time' => time(), // 加入购物车时间
            'prom_type' => 0,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'prom_id' => 0,   // 活动id
            'store_id' => $store_id,   // 店铺id
            'type'=>$this->goods['type'],   //商品的类型 1 汽车 2 配件
            'weight' => $this->goods['weight'],   // 商品重量
            'goods'=>$this->goods,
            'integral' =>$pay_points,
            //'all_integrals' =>$this->goods['integral'] * $this->goodsBuyNum
        ];
        $buyGoods = array_merge($buyGoodss,$buyGoods);
        //判断商品是车  使用定金
        if($this->goods['type'] == 1){
            if($exchange_integral == 0){
                //纯金额
                $buyGoods['member_goods_price'] = $this->goods['deposit_price'];
            }elseif($exchange_integral == 1){
                //积分和金额商品
                $buyGoods['member_goods_price'] = $this->goods['integral_money'];
            }else{
                //纯积分商品
                $buyGoods['member_goods_price'] = 0;
            }

            //计算车型订单的总金额
            if(empty($this->specGoodsPrice)){

                $specGoodsPriceCount = M('goods_sku')->where("goods_id", $this->goods['goods_id'])->count('item_id');
                if($specGoodsPriceCount > 0){
                    // throw new TpshopException('立即购买',0,['status' => 0, 'msg' => '必须传递商品规格', 'result' => '']);
                    throwError('必须传递商品规格');
                }
            }else{
                $specGoodsPrice = M('goods_sku')->where("id", $item_id)->field(true)->find();
                if($specGoodsPrice['level'] == 4){
                    $specGoodsPrices = M('goods_sku')->where("id", $specGoodsPrice['parent_id'])->field(true)->find();
                    $buyGoods['all_car_money'] = $specGoodsPrices['sku_price'];
                }
            }
        }


             //$buyGoods['member_goods_price'] = $this->specGoodsPrice['price'];
            // $buyGoods['goods_price'] = $this->specGoodsPrice['price'];
            $buyGoods['spec_key'] = $this->specGoodsPrice['parent_id_path'];
            $buyGoods['spec_key_name'] = $key_name; // 规格 key_name
            // $buyGoods['sku'] = $this->specGoodsPrice['sku']; //商品条形码
            // $prom_type = $this->specGoodsPrice['prom_type'];
            $store_count = $this->specGoodsPrice['sku_count'];
        if($this->goodsBuyNum > $store_count){
            // throw new TpshopException('立即购买',0,['status' => 0, 'msg' => '商品库存不足，剩余'.$store_count, 'result' => '']);
            throwError('商品库存不足，剩余'.$store_count);
        }
        $goodsPromFactory = new GoodsPromFactory();
        if ($goodsPromFactory->checkPromType($prom_type)) {
            $goodsPromLogic = $goodsPromFactory->makeModule($this->goods,$this->specGoodsPrice);
            if($goodsPromLogic->checkActivityIsAble()) {
                $buyGoods = $goodsPromLogic->buyNow($buyGoods);
            }
        }
        $cart = new Cart();
        $buyGoods['cut_fee'] = $cart->getCutFeeAttr(0,$buyGoods);
        $buyGoods['goods_fee'] = $cart->getGoodsFeeAttr(0,$buyGoods);
        $buyGoods['total_fee'] = $cart->getTotalFeeAttr(0,$buyGoods);
        $buyGoods['cat_id3'] = $this->goods['cat_id3'];
        return $buyGoods;
    }
    /**
     * 立即购买
     * @return array|mixed
     * @throws TpshopException
     * $pay_points          支付的积分数量
     * $exchange_integral   商品的积分类型 0 纯金额 1积分和金额 2 纯积分
     */
    public function buyNow($pay_points=0,$exchange_integral=0,$all_points = 0,$one_point=0){
        if(empty($this->goods)){
            // throw new TpshopException('立即购买',0,['status'=>0,'msg'=>'购买商品不存在','result'=>'']);
            throwError('购买商品不存在');
        }
        if(empty($this->goodsBuyNum)){
            // throw new TpshopException('立即购买',0,['status'=>0,'msg'=>'购买商品数量不能为0','result'=>'']);
            throwError('购买商品数量不能为0');
        }

        if($exchange_integral == 0){
            $buyGoodss['all_integrals'] = 0;
            $buyGoodss['one_point'] = 0;
        }else{
            $buyGoodss['all_integrals'] = $all_points;
            //$buyGoodss['one_point'] = $one_point;
        }

        $buyGoods = [
            'user_id'=>$this->user_id,
            'session_id'=>$this->session_id,
            'goods_id'=>$this->goods['goods_id'],
            'goods_sn'=>$this->goods['goods_sn'],
            'goods_name'=>$this->goods['goods_name'],
            'market_price'=>$this->goods['market_price'],
            'goods_price'=>$this->goods['shop_price'],
            'member_goods_price'=>$this->goods['shop_price'],
            'goods_num' => $this->goodsBuyNum, // 购买数量
            'add_time' => time(), // 加入购物车时间
            'prom_type' => 0,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
            'prom_id' => 0,   // 活动id
            // 'store_id' => $this->goods['store_id'],   // 店铺id
            'weight' => $this->goods['weight'],   // 商品重量
            'goods'=>$this->goods,
            'integral'=>$pay_points,
            'type'=>$this->goods['type']
            //'all_integrals' =>$this->goods['integral'] * $this->goodsBuyNum
        ];
        $buyGoods = array_merge($buyGoodss,$buyGoods);
        if(empty($this->specGoodsPrice)){
            $specGoodsPriceCount = Db::name('SpecGoodsPrice')->where("goods_id", $this->goods['goods_id'])->count('item_id');
            if($specGoodsPriceCount > 0){
                // throw new TpshopException('立即购买',0,['status' => 0, 'msg' => '必须传递商品规格', 'result' => '']);
                throwError('必须传递商品规格');
            }
            $prom_type = $this->goods['prom_type'];
            $store_count = $this->goods['store_count'];
        }else{
            if($exchange_integral == 2){
                //纯积分
                $buyGoods['member_goods_price'] = 0;
                $buyGoods['goods_price'] = 0;
                $buyGoods['integral_alls'] = $this->specGoodsPrice['integral'];
            }elseif($exchange_integral == 1){
                //积分和金额
                $buyGoods['member_goods_price'] = $this->specGoodsPrice['integral_price'];
                $buyGoods['goods_price'] = $this->specGoodsPrice['integral_price'];
                $buyGoods['integral_alls'] = $this->specGoodsPrice['integral'];
            }else{
                //纯金额
                $buyGoods['member_goods_price'] = $this->specGoodsPrice['price'];
                $buyGoods['goods_price'] = $this->specGoodsPrice['price'];
                $buyGoods['integral_alls'] = 0;
            }

            $buyGoods['spec_key'] = $this->specGoodsPrice['key'];
            $buyGoods['spec_key_name'] = $this->specGoodsPrice['key_name']; // 规格 key_name
            $buyGoods['sku'] = $this->specGoodsPrice['sku']; //商品条形码
            $prom_type = $this->specGoodsPrice['prom_type'];
            $store_count = $this->specGoodsPrice['store_count'];
        }
        if($this->goodsBuyNum > $store_count){
            // throw new TpshopException('立即购买',0,['status' => 0, 'msg' => '商品库存不足，剩余'.$this->goods['store_count'], 'result' => '']);
            throwError('商品库存不足，剩余'.$store_count);
        }
        $goodsPromFactory = new GoodsPromFactory();
        if ($goodsPromFactory->checkPromType($prom_type)) {
            $goodsPromLogic = $goodsPromFactory->makeModule($this->goods,$this->specGoodsPrice);
            if($goodsPromLogic->checkActivityIsAble()) {
                $buyGoods = $goodsPromLogic->buyNow($buyGoods);
            }
        }
        $cart = new Cart();
        $buyGoods['cut_fee'] = $cart->getCutFeeAttr(0,$buyGoods);
        $buyGoods['goods_fee'] = $cart->getGoodsFeeAttr(0,$buyGoods);
        $buyGoods['total_fee'] = $cart->getTotalFeeAttr(0,$buyGoods);
        $buyGoods['cat_id3'] = $this->goods['cat_id3'];
        return $buyGoods;
    }

    /**
     * [生成拍卖订单数据]
     * @Auther 蒋峰
     * @DateTime
     * @return array|mixed
     */
    public function buyAuctionNow($price,$store_id=0,$prom_type = 7){
        if(empty($this->goods)){
            // throw new TpshopException('立即购买',0,['status'=>0,'msg'=>'购买商品不存在','result'=>'']);
            throwError('购买商品不存在');
        }
        if(empty($this->goodsBuyNum)){
            // throw new TpshopException('立即购买',0,['status'=>0,'msg'=>'购买商品数量不能为0','result'=>'']);
            throwError('购买商品数量不能为0');
        }
        $buyGoods = [
            'user_id'=>$this->user_id,
            'session_id'=>$this->session_id,
            'goods_id'=>$this->goods['id'],
            'goods_sn'=>$this->goods['goods_sn'],
            'goods_name'=>$this->goods['goods_name'],
            'goods_price'=>$price,
            'member_goods_price'=>$price,
            'goods_num' => $this->goodsBuyNum, // 购买数量
            'add_time' => time(), // 加入购物车时间
            'prom_type' => $prom_type,   // 订单类型：0默认1抢购2团购3优惠4预售5虚拟6拼团7拍卖
            'prom_id' => $this->goods['id'],   // 活动id
             'store_id' => $store_id,   // 店铺id
//            'weight' => $this->goods['weight'],   // 商品重量
            'goods'=>$this->goods,
            'spec_key_name' => $this->goods['spec_key_name'],
            'spec_key' => $this->goods['spec_key'],
            'type'=>$this->goods['type']
        ];

        $goodsPromFactory = new GoodsPromFactory();
        if ($goodsPromFactory->checkPromType($prom_type)) {
            $goodsPromLogic = $goodsPromFactory->makeModule($this->goods,$this->specGoodsPrice);
            if($goodsPromLogic->checkActivityIsAble()) {
                $buyGoods = $goodsPromLogic->buyNow($buyGoods);
            }
        }
        $cart = new Cart();
        $buyGoods['cut_fee'] = $cart->getCutFeeAttr(0,$buyGoods);
        $buyGoods['goods_fee'] = $cart->getGoodsFeeAttr(0,$buyGoods);
        $buyGoods['total_fee'] = $cart->getTotalFeeAttr(0,$buyGoods);
        return $buyGoods;
    }
    /**
     * modify ：addCart
     * @return array
     */
    public function addGoodsToCart($exchange_integral = 0)
    {
        if(empty($this->goods)){
            return ['status'=>-3,'msg'=>'购买商品不存在','result'=>''];
        }
//        if($this->goods['exchange_integral'] > 0){
//            return ['status'=>0,'msg'=>'积分商品跳转','result'=>['url'=>U('Goods/goodsInfo',['id'=>$this->goods['goods_id'],'item_id'=>$this->specGoodsPrice['item_id']],'',true)]];
//        }
        $userCartCount = Db::name('cart')->where(['user_id'=>$this->user_id,'session_id'=>$this->session_id])->count();//获取用户购物车的商品有多少种
//        if ($userCartCount >= 20) {
//            return array('status' => -9, 'msg' => '购物车最多只能放20种商品', 'result' => '');
//        }
        $specGoodsPriceCount = Db::name('SpecGoodsPrice')->where("goods_id", $this->goods['goods_id'])->count('item_id');
        if(empty($this->specGoodsPrice) && !empty($specGoodsPriceCount)){
            return array('status' => -1, 'msg' => '必须传递商品规格', 'result' => '');
        }
        //有商品规格，和没有商品规格
        if($this->specGoodsPrice){
            if($this->specGoodsPrice['prom_type'] == 1){
                $result = $this->addFlashSaleCart();
            }elseif($this->specGoodsPrice['prom_type'] == 2){
                $result = $this->addGroupBuyCart();
            }elseif($this->specGoodsPrice['prom_type'] == 3){
                $result = $this->addPromGoodsCart();
            }else{
                $result = $this->addNormalCart($exchange_integral);
            }
        }else{
            if($this->goods['prom_type'] == 1){
                $result = $this->addFlashSaleCart();
            }elseif($this->goods['prom_type'] == 2){
                $result = $this->addGroupBuyCart();
            }elseif($this->goods['prom_type'] == 3){
                $result = $this->addPromGoodsCart();
            }else{
                $result = $this->addNormalCart($exchange_integral);
            }
        }
        $result['result'] = $UserCartGoodsNum = $this->getUserCartGoodsNum(); // 查找购物车数量
        // setcookie('cn', $UserCartGoodsNum, null, '/');
        return $result;
    }

    /**
     * 购物车添加普通商品
     * @return array
     * $exchange_integral       0纯金额 1积分和金额 2纯积分
     */
    private function addNormalCart($exchange_integral=0){
        if(empty($this->specGoodsPrice)){
            $price =  $this->goods['shop_price'];
            $store_count =  $this->goods['store_count'];
        }else{
            //如果有规格价格，就使用规格价格，否则使用本店价。
            if($exchange_integral == 2){
                //纯积分
                $price = 0;
            }elseif($exchange_integral == 1){
                //积分和金额
                $price = $this->specGoodsPrice['integral_price'];
            }else{
                //纯金额
                $price = $this->specGoodsPrice['price'];
            }

            $store_count = $this->specGoodsPrice['store_count'];
        }
        // 查询购物车是否已经存在这商品
        if (!$this->user_id) {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: ''),'exchange_integral'=>$exchange_integral]);
        } else {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: ''),'exchange_integral'=>$exchange_integral]);
        }
        // 如果该商品已经存在购物车
        if ($userCartGoods) {
            $userWantGoodsNum = $this->goodsBuyNum + $userCartGoods['goods_num'];//本次要购买的数量加上购物车的本身存在的数量
//            if($userWantGoodsNum > 200){
//                $userWantGoodsNum = 200;
//            }
            if($userWantGoodsNum > $store_count){
                empty($userCartGoods['goods_num']) && $userCartGoods['goods_num'] = 0;
                return array('status' => -4, 'msg' => '商品库存不足，剩余'.$store_count.',当前购物车已有'.$userCartGoods['goods_num'].'件', 'result' => '');
            }
            $cartResult = $userCartGoods->save(['goods_num' => $userWantGoodsNum,'goods_price'=>$price,'member_goods_price'=>$price]);
       }else{
            //如果该商品没有存在购物车
            if($this->goodsBuyNum > $store_count){
                return array('status' => -4, 'msg' => '商品库存不足，剩余'.$this->goods['store_count'], 'result' => '');
            }
            if($exchange_integral == 0){
                //纯金额
                $cartAddDatas['integral'] = 0;
                $cartAddDatas['all_integral'] = 0;
                $cartAddDatas['member_goods_price'] = $price;
            }elseif($exchange_integral == 2){
                //纯积分
//                $cartAddDatas['integral'] = $this->goods['integral'];
//                $cartAddDatas['all_integral'] = $this->goods['integral'];
//                $cartAddDatas['member_goods_price'] = 0;
                $cartAddDatas['integral'] = $this->specGoodsPrice['integral'];
                $cartAddDatas['all_integral'] = $this->specGoodsPrice['integral'];
                $cartAddDatas['member_goods_price'] = 0;
            }else{
                //积分和金额
//                $cartAddDatas['integral'] = $this->goods['integral'];
//                $cartAddDatas['all_integral'] = $this->goods['integral'];
//                $cartAddDatas['member_goods_price'] = 0;
                $cartAddDatas['integral'] = $this->specGoodsPrice['integral'];
                $cartAddDatas['all_integral'] = $this->specGoodsPrice['integral'];
                $cartAddDatas['member_goods_price'] = $this->specGoodsPrice['integral_price'];;
            }
            $cartAddData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'goods_price' => $price,  // 原价
                //'member_goods_price' => $price,  // 会员折扣价 默认为 购买价
                'goods_num' => $this->goodsBuyNum, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 0,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'prom_id' => 0,   // 活动id
                // 'store_id' => $this->goods['store_id'],   // 店铺id
                //'integral'=>$this->goods['integral'],
                'exchange_integral'=>$exchange_integral,
                //'all_integral'=>$this->goods['integral'],
            );

            $cartAddData = array_merge($cartAddDatas,$cartAddData);

            if($this->specGoodsPrice){
                $cartAddData['spec_key'] = $this->specGoodsPrice['key'];
                $cartAddData['spec_key_name'] = $this->specGoodsPrice['key_name']; // 规格 key_name
                $cartAddData['sku'] = $this->specGoodsPrice['sku']; //商品条形码
            }
            $cartResult = Db::name('Cart')->insertGetId($cartAddData);
        }
        if($cartResult !== false){
            return array('status' => 1, 'msg' => '成功加入购物车', 'result' => '');
        }else{
            return array('status' => -1, 'msg' => '加入购物车失败', 'result' => '');
        }
    }

    /**
     * 购物车添加秒杀商品
     * @return array
     */
    private function addFlashSaleCart(){
        $flashSaleLogic = new FlashSaleLogic($this->goods, $this->specGoodsPrice);
        $flashSale = $flashSaleLogic->getPromModel();
        $flashSaleIsEnd = $flashSaleLogic->checkActivityIsEnd();
        if($flashSaleIsEnd){
            return array('status' => -1, 'msg' => '秒杀活动已结束', 'result' => '');
        }
        $flashSaleIsAble = $flashSaleLogic->checkActivityIsAble();
        if(!$flashSaleIsAble){
            //活动没有进行中，走普通商品下单流程
            return $this->addNormalCart();
        }else{
            //活动进行中
            if ($this->user_id == 0) {
                return array('status' => -101, 'msg' => '购买活动商品必须先登录', 'result' => '');
            }
        }
        if($this->goodsBuyNum > $flashSale['buy_limit']){
            return array('status' => -4, 'msg' => '每人限购'.$flashSale['buy_limit'].'件', 'result' => '');
        }
        //获取用户购物车的抢购商品
        if (!$this->user_id) {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: '')]);
        } else {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: '')]);
        }
        $userCartGoodsNum = empty($userCartGoods) ? 0 : $userCartGoods['goods_num'];///获取用户购物车的抢购商品数量
        $userFlashOrderGoodsNum = $flashSaleLogic->getUserFlashOrderGoodsNum($this->user_id); //获取用户抢购已购商品数量
        $flashSalePurchase = $flashSale['goods_num'] - $flashSale['buy_num'];//抢购剩余库存
        $userBuyGoodsNum = $this->goodsBuyNum + $userFlashOrderGoodsNum + $userCartGoodsNum;
        if($userBuyGoodsNum > $flashSale['buy_limit']){
            return array('status' => -4, 'msg' => '每人限购'.$flashSale['buy_limit'].'件，您已下单'.$userFlashOrderGoodsNum.'件'.'购物车已有'.$userCartGoodsNum.'件', 'result' => '');
        }
        $userWantGoodsNum = $this->goodsBuyNum + $userCartGoodsNum;//本次要购买的数量加上购物车的本身存在的数量
//        if($userWantGoodsNum > 200){
//            $userWantGoodsNum = 200;
//        }
        if($userWantGoodsNum > $flashSalePurchase){
            return array('status' => -4, 'msg' => '商品库存不足，剩余'.$flashSalePurchase.',当前购物车已有'.$userCartGoodsNum.'件', 'result' => '');
        }
        // 如果该商品已经存在购物车
        if($userCartGoods){
            $cartResult = $userCartGoods->save(['goods_num' => $userWantGoodsNum]);
        }else{
            $cartAddFlashSaleData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'member_goods_price' => $flashSale['price'],  // 会员折扣价 默认为 购买价
                'goods_num' => $userWantGoodsNum, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 1,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'store_id' => $this->goods['store_id'],   // 店铺id
            );
            //商品有规格
            if($this->specGoodsPrice){
                $cartAddFlashSaleData['spec_key'] = $this->specGoodsPrice['key'];
                $cartAddFlashSaleData['spec_key_name'] = $this->specGoodsPrice['key_name']; // 规格 key_name
                $cartAddFlashSaleData['sku'] = $this->specGoodsPrice['sku']; //商品条形码
                $cartAddFlashSaleData['goods_price'] = $this->specGoodsPrice['price'];   // 规格价
                $cartAddFlashSaleData['prom_id'] = $this->specGoodsPrice['prom_id']; // 活动id
            }else{
                $cartAddFlashSaleData['goods_price'] =  $this->goods['shop_price'];   // 原价
                $cartAddFlashSaleData['prom_id'] = $this->goods['prom_id'];// 活动id
            }
            $cartResult = Db::name('Cart')->insert($cartAddFlashSaleData);
        }
        if($cartResult !== false){
            return array('status' => 1, 'msg' => '成功加入购物车', 'result' => '');
        }else{
            return array('status' => -1, 'msg' => '加入购物车失败', 'result' => '');
        }
    }

    /**
     *  购物车添加团购商品
     * @return array
     */
    private function addGroupBuyCart(){
        $groupBuyLogic = new GroupBuyLogic($this->goods, $this->specGoodsPrice);
        $groupBuy = $groupBuyLogic->getPromModel();
        //活动是否已经结束
        if($groupBuy['is_end'] == 1 || empty($groupBuy)){
            return array('status' => -1, 'msg' => '团购活动已结束', 'result' => '');
        }
        $groupBuyIsAble = $groupBuyLogic->checkActivityIsAble();
        if(!$groupBuyIsAble){
            //活动没有进行中，走普通商品下单流程
            return $this->addNormalCart();
        }else{
            //活动进行中
            if ($this->user_id == 0) {
                return array('status' => -101, 'msg' => '购买活动商品必须先登录', 'result' => '');
            }
        }
        //获取用户购物车的团购商品
        if (!$this->user_id) {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: '')]);
        } else {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: '')]);
        }
        $userCartGoodsNum = empty($userCartGoods) ? 0 : $userCartGoods['goods_num'];///获取用户购物车的团购商品数量
        $userWantGoodsNum = $userCartGoodsNum + $this->goodsBuyNum;//购物车加上要加入购物车的商品数量
        $groupBuyPurchase = $groupBuy['goods_num'] - $groupBuy['buy_num'];//团购剩余库存
//        if($userWantGoodsNum > 200){
//            $userWantGoodsNum = 200;
//        }
        if($userWantGoodsNum > $groupBuyPurchase){
            return array('status' => -4, 'msg' => '商品库存不足，剩余'.$groupBuyPurchase.',当前购物车已有'.$userCartGoodsNum.'件', 'result' => '');
        }
        // 如果该商品已经存在购物车
        if($userCartGoods){
            $cartResult = $userCartGoods->save(['goods_num' => $userWantGoodsNum]);
        }else{
            $cartAddFlashSaleData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'member_goods_price' => $groupBuy['price'],  // 会员折扣价 默认为 购买价
                'goods_num' => $userWantGoodsNum, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 2,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'store_id' => $this->goods['store_id'],   // 店铺id
            );
            //商品有规格
            if($this->specGoodsPrice){
                $cartAddFlashSaleData['spec_key'] = $this->specGoodsPrice['key'];
                $cartAddFlashSaleData['spec_key_name'] = $this->specGoodsPrice['key_name']; // 规格 key_name
                $cartAddFlashSaleData['sku'] = $this->specGoodsPrice['sku']; //商品条形码
                $cartAddFlashSaleData['goods_price'] = $this->specGoodsPrice['price'];   // 规格价
                $cartAddFlashSaleData['prom_id'] = $this->specGoodsPrice['prom_id']; // 活动id
            }else{
                $cartAddFlashSaleData['goods_price'] =  $this->goods['shop_price'];   // 原价
                $cartAddFlashSaleData['prom_id'] = $this->goods['prom_id'];// 活动id
            }
            $cartResult = Db::name('Cart')->insert($cartAddFlashSaleData);
        }
        if($cartResult !== false){
            return array('status' => 1, 'msg' => '成功加入购物车', 'result' => '');
        }else{
            return array('status' => -1, 'msg' => '加入购物车失败', 'result' => '');
        }
    }

    /**
     *  购物车添加优惠促销商品
     * @return array
     */
    private function addPromGoodsCart(){
        $promGoodsLogic = new PromGoodsLogic($this->goods, $this->specGoodsPrice);
        $promGoods = $promGoodsLogic->getPromModel();
        //活动是否存在，是否关闭，是否处于有效期
        if($promGoodsLogic->checkActivityIsEnd() || !$promGoodsLogic->checkActivityIsAble()){
            //活动不存在，已关闭，不处于有效期,走添加普通商品流程
            return $this->addNormalCart();
        }else{
            //活动进行中
            if ($this->user_id == 0) {
                return array('status' => -101, 'msg' => '购买活动商品必须先登录', 'result' => '');
            }
        }
        //如果有规格价格，就使用规格价格、库存，否则使用本店价、库存。
        if ($this->specGoodsPrice) {
            $priceBefore = $this->specGoodsPrice['price'];
            $storeCount = $this->specGoodsPrice['store_count'];
        } else {
            $priceBefore = $this->goods['shop_price'];
            $storeCount = $this->goods['store_count'];
        }
        //计算优惠价格
        $priceAfter = $promGoodsLogic->getPromotionPrice($priceBefore);
        // 查询购物车是否已经存在这商品
        if (!$this->user_id) {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: '')]);
        } else {
            $userCartGoods = Cart::get(['user_id'=>$this->user_id,'goods_id'=>$this->goods['goods_id'],'spec_key'=>($this->specGoodsPrice['key'] ?: '')]);
        }
        $userCartGoodsNum = empty($userCartGoods) ? 0 : $userCartGoods['goods_num']; ///获取用户购物车的促销商品数量
        $userWantGoodsNum = $this->goodsBuyNum + $userCartGoods['goods_num']; //本次要购买的数量加上购物车的本身存在的数量
        $UserPromOrderGoodsNum = $promGoodsLogic->getUserPromOrderGoodsNum($this->user_id); //获取用户促销已购商品数量
        $userBuyGoodsNum = $userWantGoodsNum+$UserPromOrderGoodsNum; //本次要购买的数量+购物车本身数量+已经买
        if($userBuyGoodsNum > $promGoods['buy_limit']){
            return array('status' => -4, 'msg' => '每人限购'.$promGoods['buy_limit'].'件，您已下单'.$UserPromOrderGoodsNum.'件，'.'购物车已有'.$userCartGoods['goods_num'].'件', 'result' => '');
        }
        $userWantGoodsNum = $this->goodsBuyNum + $userCartGoodsNum;//本次要购买的数量加上购物车的本身存在的数量
//        if($userWantGoodsNum > 200){
//            $userWantGoodsNum = 200;
//        }
        if($userWantGoodsNum > $storeCount ){   //用户购买量不得超过库存
            return array('status' => -4, 'msg' => '商品活动库存不足，剩余'.$storeCount.',当前购物车已有'.$userCartGoodsNum.'件', 'result' => '');
        }

        // 如果该商品已经存在购物车
        if ($userCartGoods) {
            $cartResult = $userCartGoods->save(['goods_num' => $userWantGoodsNum, 'goods_price' => $priceBefore, 'member_goods_price' => $priceAfter]);
        }else{
            $cartAddData = array(
                'user_id' => $this->user_id,   // 用户id
                'session_id' => $this->session_id,   // sessionid
                'goods_id' => $this->goods['goods_id'],   // 商品id
                'goods_sn' => $this->goods['goods_sn'],   // 商品货号
                'goods_name' => $this->goods['goods_name'],   // 商品名称
                'market_price' => $this->goods['market_price'],   // 市场价
                'goods_price' => $priceBefore,  // 原价
                'member_goods_price' => $priceAfter,  // 会员折扣价 默认为 购买价
                'goods_num' => $userWantGoodsNum, // 购买数量
                'add_time' => time(), // 加入购物车时间
                'prom_type' => 3,   // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                'store_id' => $this->goods['store_id'],   // 店铺id
            );
            //商品有规格
            if($this->specGoodsPrice){
                $cartAddData['spec_key'] = $this->specGoodsPrice['key'];
                $cartAddData['spec_key_name'] = $this->specGoodsPrice['key_name']; // 规格 key_name
                $cartAddData['sku'] = $this->specGoodsPrice['sku']; //商品条形码
                $cartAddData['prom_id'] = $this->specGoodsPrice['prom_id']; // 活动id
            }else{
                $cartAddData['prom_id'] = $this->goods['prom_id'];// 活动id
            }
            $cartResult = Db::name('Cart')->insert($cartAddData);
        }
        if($cartResult !== false){
            return array('status' => 1, 'msg' => '成功加入购物车', 'result' => '');
        }else{
            return array('status' => -1, 'msg' => '加入购物车失败', 'result' => '');
        }
    }

    /**
     * 获取用户购物车商品总数
     * @return float|int
     */
    public function getUserCartGoodsNum()
    {
        if ($this->user_id) {
            $goods_num = Db::name('cart')->where(['user_id' => $this->user_id])->sum('goods_num');
        } else {
            $goods_num = Db::name('cart')->where(['session_id' => $this->session_id])->sum('goods_num');
        }
        return empty($goods_num) ? 0 : $goods_num;
    }

    /**
     * 获取用户购物车商品总数
     * @return float|int
     */
    public function getUserCartGoodsTypeNum()
    {
        if ($this->user_id) {
            $goods_num = Db::name('cart')->where(['user_id' => $this->user_id])->count();
        } else {
            $goods_num = Db::name('cart')->where(['session_id' => $this->session_id])->count();
        }
        return empty($goods_num) ? 0 : $goods_num;
    }
    
    /**
     * @param int $selected|是否被用户勾选中的 0 为全部 1为选中  一般没有查询不选中的商品情况
     * 获取用户的购物车列表
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getCartList($selected = 0,$page=1){
        $cart = new Cart();
        $offset = ($page - 1) * 10;
        // 如果用户已经登录则按照用户id查询
        if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
        } else {
            $cartWhere['session_id'] = $this->session_id;
        }
        if($selected != 0){
            $cartWhere['selected'] = 1;
        }

        $cartList = $cart->with('goods')->where($cartWhere)->limit($offset,10)->select();  // 获取购物车商品
        foreach($cartList as $key=>$value){
            $cartList[$key]['store_count'] = M('spec_goods_price')->where(['goods_id'=>$value['goods_id'],'key'=>$value['spec_key']])->getField('store_count');
        }
        $cartCheckAfterList = $this->checkCartList($cartList);
//      $cartCheckAfterList = $cartList;
        $cartGoodsTotalNum = array_sum(array_map(function($val){return $val['goods_num'];}, $cartCheckAfterList));//购物车购买的商品总数
        // setcookie('cn', $cartGoodsTotalNum, null, '/');
        return $cartCheckAfterList;
    }

    /**
     * 过滤掉无效的购物车商品
     * @param $cartList
     */
    public function checkCartList($cartList){
        $goodsPromFactory = new GoodsPromFactory();
        foreach($cartList as $cartKey=>$cart){
            //商品不存在或者已经下架或则商品数量为零的
            if(empty($cart['goods']) || $cart['is_on_sale'] != 1 || $cart['goods_num'] == 0){
                $cart->delete();
                unset($cartList[$cartKey]);
                continue;
            }
            //活动商品的活动是否失效
            if ($goodsPromFactory->checkPromType($cart['prom_type'])) {
                if (!empty($cart['spec_key'])) {
                    $specGoodsPrice = SpecGoodsPrice::get(['goods_id' => $cart['goods_id'], 'key' => $cart['spec_key']], '', true);
                    if($specGoodsPrice['prom_id'] != $cart['prom_id']){
                        $cart->delete();
                        unset($cartList[$cartKey]);
                        continue;
                    }
                } else {
                    if($cart['goods']['prom_id'] != $cart['prom_id']){
                        $cart->delete();
                        unset($cartList[$cartKey]);
                        continue;
                    }
                    $specGoodsPrice = null;
                }
                $goodsPromLogic = $goodsPromFactory->makeModule($cart['goods'], $specGoodsPrice);
                if ($goodsPromLogic && !$goodsPromLogic->isAble()) {
                    $cart->delete();
                    unset($cartList[$cartKey]);
                    continue;
                } elseif ($goodsPromLogic && $goodsPromLogic->isAble()) {
                    $prom = $goodsPromLogic->getPromModel();
                    $cart['prom_title'] = $prom['title'];
                }
            }else{
                $cart['prom_title'] = '';
            }
        }
        return $cartList;
    }

    /**
     *  modify ：cart_count
     *  获取用户购物车欲购买的商品有多少种
     * @return int|string
     */
    public function getUserCartOrderCount(){
        $count = Db::name('Cart')->where(['user_id' => $this->user_id , 'selected' => 1])->count();
        return $count;
    }

    /**
     * 用户登录后 对购物车操作
     * modify：login_cart_handle
     */
    public function doUserLoginHandle()
    {
        if (empty($this->session_id) || empty($this->user_id)) {
            return;
        }
        //登录后将购物车的商品的 user_id 改为当前登录的id
        $cart = new Cart();
        $cart->save(['user_id' => $this->user_id], ['session_id' => $this->session_id, 'user_id' => 0]);
        // 查找购物车两件完全相同的商品
        $cart_id_arr = $cart->field('id')->where(['user_id' => $this->user_id])->group('goods_id,spec_key')->having('count(goods_id) > 1')->select();
        if (!empty($cart_id_arr)) {
            $cart_id_arr = get_arr_column($cart_id_arr, 'id');
            M('cart')->delete($cart_id_arr); // 删除购物车完全相同的商品
        }
    }

    /**
     * 更改购物车的商品数量
     * @param $cart_id|购物车id
     * @param $goods_num|商品数量
     * @return array
     */
    public function changeNum($cart_id, $goods_num){
        $Cart = new Cart();
        $cart = $Cart::get($cart_id);
        // dump($cart->limit_num);die;
        // if($goods_num > $cart->limit_num){
        //     return ['status' => 0, 'msg' => '商品数量不能大于'.$cart->limit_num, 'result' => ['limit_num'=>$cart->limit_num]];
        // }
//        if($goods_num > 200){
//            return ['status' => 2, 'msg' => '购买上限', 'result' => ''];
//            $goods_num = 200;
//        }
        $cart->goods_num = $goods_num;
        $cart->save();
        return ['status' => 1, 'msg' => '修改商品数量成功', 'result' => ''];
    }

    /**
     * 删除购物车商品
     * @param array $cart_ids
     * @return int
     * @throws \think\Exception
     */
    public function delete($cart_ids = array()){
        if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
        } else {
            $cartWhere['session_id'] = $this->session_id;
            $user['user_id'] = 0;
        }
        $delete = Db::name('cart')->where($cartWhere)->where('id','IN',$cart_ids)->delete();
        return $delete;
    }
   /**
    * 改变购物车商品选中状态
    * [sel_cart description]
    * @Author   XD
    * @DateTime 2018-07-18T16:42:43+0800
    * @return   [type]                   [description]
    */
    public function sel_cart($cart){
        $Cart = new Cart();
         if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
        } else {
            $cartWhere['session_id'] = $this->session_id;
        }
        $res = $Cart->where('id', 'IN', $cart)->where($cartWhere)->update(['selected' => 1]);
        $res1 = $Cart->where('id', 'NOTIN', $cart)->where($cartWhere)->update(['selected' => 0]);
        return $res;
    }

    /**
     *  更新购物车，并返回计算结果
     * @param array $cart
     * @return array
     */
    public function AsyncUpdateCart($cart = [])
    {
        $storeCartList = $cartSelectedId = $cartNoSelectedId = [];
        if (empty($cart)) {
            return ['status' => 0, 'msg' => '购物车没商品', 'result' => compact('total_fee', 'goods_fee', 'goods_num', 'storeCartList')];
        }
        foreach ($cart as $key => $val) {
            if ($cart[$key]['selected'] == 1) {
                $cartSelectedId[] = $cart[$key]['id'];
            } else {
                $cartNoSelectedId[] = $cart[$key]['id'];
            }
        }
        $Cart = new Cart();
        if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
        } else {
            $cartWhere['session_id'] = $this->session_id;
        }
        if (!empty($cartNoSelectedId)) {
            $Cart->where('id', 'IN', $cartNoSelectedId)->where($cartWhere)->update(['selected' => 0]);
        }
        if (empty($cartSelectedId)) {
            $cartPriceInfo = $this->getCartPriceInfo();
            $cartPriceInfo['storeCartList'] = $storeCartList;
            return ['status' => 1, 'msg' => '购物车没选中商品', 'result' => $cartPriceInfo];
        }
        $cartList = $Cart->where('id', 'IN', $cartSelectedId)->where($cartWhere)->select();
        foreach($cartList as $cartKey=>$cartVal){
            if($cartList[$cartKey]['selected'] == 0){
                $Cart->where('id', 'IN', $cartSelectedId)->where($cartWhere)->update(['selected' => 1]);
                break;
            }
        }
        if ($cartList) {
            $cartList = collection($cartList)->append(['cut_fee', 'total_fee', 'goods_fee'])->toArray();
            $cartPriceInfo = $this->getCartPriceInfo($cartList);
            $storeCartList = array_values($this->getStoreCartList($cartList));
            $cartPriceInfo['storeCartList'] = $storeCartList;
            return ['status' => 1, 'msg' => '计算成功', 'result' => $cartPriceInfo];
        } else {
            $cartPriceInfo = $this->getCartPriceInfo();
            $cartPriceInfo['storeCartList'] = $storeCartList;
            return ['status' => 1, 'msg' => '购物车没选中商品', 'result' => $cartPriceInfo];
        }
    }

    /**
     * 转换成带店铺数据的购物车商品
     * @param $cartList|购物车列表
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getStoreCartList($cartList){
        $storeId = get_arr_column($cartList,'store_id');
        $storeList = Db::name('store')->cache(true,10)->where('store_id','in',$storeId)->select();
        foreach($storeList as $storeKey => $storeVal)
        {
            foreach($cartList as $cartKey => $cartVal)
            {
                if($storeList[$storeKey]['store_id'] == $cartList[$cartKey]['store_id']){
                    $storeList[$storeKey]['cartList'][] = $cartList[$cartKey];
                    $storeList[$storeKey]['store_total_price'] += $cartList[$cartKey]['total_fee'];//店铺商品优惠前购买的总价
                    $storeList[$storeKey]['store_goods_price'] += $cartList[$cartKey]['goods_fee'];//店铺商品优惠后购买的总价
                    $storeList[$storeKey]['store_cut_price'] += $cartList[$cartKey]['cut_fee'];//店铺商品节省的总价
                    $storeList[$storeKey]['store_goods_weight'] += $cartList[$cartKey]['goods']['weight'] * $cartList[$cartKey]['goods_num'];//店铺商品的总重量
                }
            }
        }
        return $storeList;
    }


    /**
     * 转换购物车的优惠券数据
     * @param $storeCartList|购物车商品
     * @param $userCouponList|用户优惠券列表
     * @return array
     */
    public function getCouponCartList($storeCartList, $userCouponList)
    {
        $userCouponArray = collection($userCouponList)->toArray();
        $couponNewList = [];
        $coupon_num=[];
        foreach ($userCouponArray as $couponKey => $couponItem) {
            foreach ($storeCartList as $storeCartKey => $storeCartItem) {
                //过滤掉购物车没有的店铺优惠券
                if ($userCouponArray[$couponKey]['store_id'] == $storeCartList[$storeCartKey]['store_id']) {
                    if ($userCouponArray[$couponKey]['coupon']['use_type'] == 0) {
                        //是否满足在该店铺购买的价格
                        if ($storeCartList[$storeCartKey]['store_goods_price'] >= $userCouponArray[$couponKey]['coupon']['condition']) {
                            $userCouponArray[$couponKey]['coupon']['able'] = 1;
                            $coupon_num[$storeCartList[$storeCartKey]['store_id']] +=1;
                        } else {
                            $userCouponArray[$couponKey]['coupon']['able'] = 0;
                        }
                    } elseif ($userCouponArray[$couponKey]['coupon']['use_type'] == 1) {
                        //是否满足购买指定商品的价格
                        $pointGoodsPrice = 0;//指定商品的购买总价
                        $CouponGoodsId = get_arr_column($userCouponArray[$couponKey]['coupon']['goods_coupon'], 'goods_id');
                        foreach ($storeCartList[$storeCartKey]['cartList'] as $cartKey => $cartItem) {
                            if (in_array($storeCartList[$storeCartKey]['cartList'][$cartKey]['goods_id'], $CouponGoodsId)) {
                                $pointGoodsPrice += $storeCartList[$storeCartKey]['cartList'][$cartKey]['goods_price'] * $storeCartList[$storeCartKey]['cartList'][$cartKey]['goods_num'];
                            }
                        }
                        if ($pointGoodsPrice >= $userCouponArray[$couponKey]['coupon']['condition']) {
                            $userCouponArray[$couponKey]['coupon']['able'] = 1;
                            $coupon_num[$storeCartList[$storeCartKey]['store_id']] +=1;
                        } else {
                            $userCouponArray[$couponKey]['coupon']['able'] = 0;
                        }
                    } elseif ($userCouponArray[$couponKey]['coupon']['use_type'] == 2) {
                        //是否满足购买指定商品分类的价格
                        $pointGoodsCatPrice = 0;//指定商品分类的购买总价
                        $CouponGoodsCatId = get_arr_column($userCouponArray[$couponKey]['coupon']['goods_coupon'], 'goods_category_id');
                        foreach ($storeCartList[$storeCartKey]['cartList'] as $cartKey => $cartItem) {
                            if (in_array($storeCartList[$storeCartKey]['cartList'][$cartKey]['goods']['cat_id3'], $CouponGoodsCatId)) {
                                $pointGoodsCatPrice += $storeCartList[$storeCartKey]['cartList'][$cartKey]['goods_price'] * $storeCartList[$storeCartKey]['cartList'][$cartKey]['goods_num'];
                            }
                        }
                        if ($pointGoodsCatPrice >= $userCouponArray[$couponKey]['coupon']['condition']) {
                            $userCouponArray[$couponKey]['coupon']['able'] = 1;
                            $coupon_num[$storeCartList[$storeCartKey]['store_id']] +=1;
                        } else {
                            $userCouponArray[$couponKey]['coupon']['able'] = 0;
                        }
                    } else {
                        $userCouponList[$couponKey]['coupon']['able'] = 1;
                        $coupon_num[$storeCartList[$storeCartKey]['store_id']] +=1;
                    }
                    $couponNewList[] = $userCouponArray[$couponKey];
                }
            }
        }
        $this->userStoreCouponNumArr = $coupon_num;
        return $couponNewList;
    }

    public function getUserStoreCouponNumArr(){
        return $this->userStoreCouponNumArr;
    }

    /**
     * 获取购物车的价格详情
     * @param $cartList|购物车列表
     * @return array
     */
    public function getCartPriceInfo($cartList = null){
        $total_fee = $goods_fee = $goods_num = 0;//初始化数据。商品总额/节约金额/商品总共数量
        if($cartList){
            foreach ($cartList as $cartKey => $cartItem) {
                $total_fee += $cartItem['goods_fee'];
                $goods_fee += $cartItem['cut_fee'];
                $goods_num += $cartItem['goods_num'];
            }
        }
        return ['total_fee'=>$total_fee,'goods_fee'=>$goods_fee,'goods_num'=>$goods_num];
    }


    /**
     * @param $invoice_title
     * @param $taxpayer
     * @param $invoice_desc
     * @return array
     */
    public function save_invoice($invoice_title,$taxpayer,$invoice_desc){
        if(empty($invoice_title))return $result = ['status' => -1, 'msg' => '请填写发票抬头', 'result' =>''];
        if(empty($invoice_desc))return $result = ['status' => -1, 'msg' => '请填写发票内容', 'result' =>''];
        //B.1校验用户是否有历史发票记录
        $map['user_id'] =  $this->user_id;
        $info           = M('user_extend')->where($map)->find();
        //B.2发票信息
        $data['invoice_title'] = $invoice_title;
        $data['taxpayer']      = $taxpayer;
        $data['invoice_desc']  = $invoice_desc;
        //B.3发票抬头
        if($invoice_title=="个人"){
            $data['invoice_title'] ="个人";
            $data['taxpayer']      = "";
        }else{
            (empty($invoice_title)||empty($taxpayer)) && $result =['status' => -2, 'msg' => '发票信息请填完整', 'result' =>''];
        }
        //是否存贮过发票信息
        if(empty($info)){
            $data['user_id'] = $this->user_id;
            (M('user_extend')->add($data))?$status=1:$status=-1;
        }else{
            (M('user_extend')->where($map)->save($data))?$status=1:$status=-1;
        }
        $result = ['status' => $status, 'msg' => '保存成功', 'result' =>''];
        return  $result;
    }
    /**
     * 检查购物车数据是否满足库存购买
     * @param $cartList
     * @throws TpshopException
     */
    public function checkStockCartList($cartList)
    {
        foreach ($cartList as $cartKey => $cartVal) {
            if($cartVal->goods_num > $cartVal->limit_num){
                // throw new TpshopException('检查购物车购买数量',0,['status' => 0, 'msg' => $cartVal->goods_name.'购买数量不能大于'.$cartVal->limit_num, 'result' => ['limit_num'=>$cartVal->limit_num]]);
               throwError('购买数量不能大于'.$cartVal->limit_num);
            }
        }
    }
    /**
     * 清除用户购物车选中
     * @throws \think\Exception
     */
    public function clear()
    {
        Db::name('cart')->where(['user_id' => $this->user_id, 'selected' => 1])->delete();
    }



    /**
     * 检查购物车中的商品积分使用规则
     * @Autor: 胡宝强
     * Date: 2018/8/17 16:34
     * @param $cartList             购物车中商品的数组
     * @param $pay_points           购买购物车中的商品用户传的积分
     */
    public function checkBuyPoint($cartList,$pay_points){
        foreach($cartList as $key=>$value){
            if($value['exchange_integral'] == 1){
                //积分和金额的购买
                $this->limit_points += 1;
                $this->height_points += $value['integral']*$value['goods_num'];
                $this->all_point += $value['integral']*$value['goods_num'];
            }elseif($value['exchange_integral'] == 2){
                //纯积分购买
                $this->limit_points += $value['integral']*$value['goods_num'];
                $this->height_points += $value['integral']*$value['goods_num'];
                $this->all_point += $this->all_point + $value['integral']*$value['goods_num'];
            }
        }
        if($pay_points < $this->limit_points){
            throwError('使用的积分不能少于'.$this->limit_points);
        }elseif($pay_points > $this->height_points){
            throwError('使用的积分不能大于'.$this->height_points);
        }
    }

    /**
     * 获取购物车中商品的总共需要的积分
     * @Autor: 胡宝强
     * Date: 2018/8/18 10:44
     * @param $cartList        购物车商品的数组
     */
    public function getCarPoint($cartList){
        $all_point = 0;
        foreach($cartList as $key=>$value){

            $all_point+= $value['integral'] * $value['goods_num'];
        }
        return $all_point;
    }

    /**
     * 获取购物车中的商品最多和最少要使用的积分数量
     * @Autor: 胡宝强
     * Date: 2018/8/24 13:54
     * @param $cartList     购物车商品列表
     */
    public function getCartHlPoint($cartList){
        $this->most_point = 0;
        $this->minimum_point = 0;
        foreach($cartList as $key=>$value){
            if($value['exchange_integral'] == 0){
                //纯金额
                $this->most_point += 0;
                $this->minimum_point += 0;
            }elseif($value['exchange_integral'] == 1){
                //积分和金额
                $this->minimum_point+= 1;
                $this->most_point+= $value['integral']*$value['goods_num'];

            }else{
                //纯积分
                $this->most_point+= $value['integral']*$value['goods_num'];
                $this->minimum_point+= $value['integral']*$value['goods_num'];
            }
        }

        $data['most_point'] = $this->most_point;
        $data['minimum_point'] = $this->minimum_point;
        return $data;
    }
}