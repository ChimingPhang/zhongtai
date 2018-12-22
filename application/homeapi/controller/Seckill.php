<?php
namespace app\homeapi\controller;

use app\api\controller\Order;
use app\api\logic\CartLogic;
use app\api\logic\GoodsLogic;
use app\api\model\AccessoriesCategory;
use app\api\model\Goods;
use app\api\model\GoodsCategory;
use app\api\model\GoodsSeckill;
use app\api\logic\SecKillLogic;
use app\common\logic\PlaceOrder;
use think\Request;
use think\Cache;
use think\Db;

/**
 * 秒杀
 * @Author   XD
 * @DateTime 2018-07-11T15:09:13+0800
 */
class Seckill extends Base {
    //每页显示数
    private static $pageNum = 9;
    //页数
    public static $page = 1;
    private static $GoodsSecKill;
    private static $SecKillLogic;

    public function __construct()
    {
        parent::__construct();
        //自动加载页数
        self::Initialization();
        !is_numeric(self::$page = I('page', 1)) && $this->errorMsg(2002, 'page');
    }

    public static function Initialization()
    {
        if(!self::$GoodsSecKill)
            self::$GoodsSecKill = new GoodsSeckill();
        if(!self::$SecKillLogic)
            self::$SecKillLogic = new SecKillLogic();
    }

    /**
     * [推荐列表]
     * @Auther 蒋峰
     * @DateTime
     */
    public function test()
    {
        $list = [
            ['code' => 123456],
            ['code' => 654321],
            ['code' => 134625],
            ['code' => 526431],
            ['code' => 615324],
        ];
        $secKillLogic = new \app\common\logic\SecKill();
        $list = $secKillLogic->OpenPrizeSortList($list,265314);
        dump($list);die;
    }

    /**
     * [秒杀商品列表页面]
     * @Auther 蒋峰
     * @DateTime
     */
    public function one_dollar()
    {
        //筛选条件
        $types = [
            ['id' => 1, 'name' => '汽车'],
            ['id' => 2, 'name' => '配件'],
            ['id' => 3, 'name' => '第三方'],
        ];
        $this->assign('type', $types);

        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
        $type = I('type', 0);
        if(!in_array($type, [0,1,2,3,])) $type = 0;

        $order = [];
        if ($price) $order['deposit_price'] = $price;

        $field = "id,goods_name,goods_remark,sales_sum,price,label,type,original_img";
        $list = self::$GoodsSecKill->GoodsList(self::$page, $type, [], $order, self::$pageNum, $field);
        $this->assign('car_list', $list);

//        $this->json("0000", 'ok', ['type'=>$types, 'car_list' => $list]);

        return $this->fetch('dist/one-dollar');
    }

    /**
     * 秒杀商品列表
     */
    public function lists()
    {
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
        $type = I('type', 0);
        if(!in_array($type, [0,1,2,3,])) $type = 0;

        $order = [];
        if ($price) $order['deposit_price'] = $price;

        $field = "id,goods_name,goods_remark,sales_sum,price,label,type,original_img";
        $list = self::$GoodsSecKill->GoodsList(self::$page, $type, [], $order, self::$pageNum, $field);
        $this->assign('car_list', $list);

        $this->json("0000", 'ok', $list);

    }

    /**
     * [详情]
     * @Auther 蒋峰
     * @DateTime
     */
    public function one_dollar_detail()
    {
        empty(I('seckill_id', '')) && $this->errorMsg(2001, 'seckill_id');//必传auction_id
        !is_numeric($seckill_id = I('seckill_id', 0)) && $this->errorMsg(2002, 'seckill_id');//必传
        !is_numeric($address_id = I('address_id', 0)) && $this->errorMsg(2002, 'address_id');//选传address_id
        $info = self::$GoodsSecKill->info($seckill_id, '*');

        if(!$info) {
            $this->assign('data', $info);
            return $this->fetch('dist/one-dollar-detail');
        }

        //获取用户id
        $user_id = (new \app\api\model\Users())->getUserOnToken(I('token', '1'));
        //banner图
        $info->banner_image =$this->bannerImage($info->banner_image);
        //详情
        $info->goods_content = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $info->goods_content . '</div></body></html>');

        if($info->type == 1){
//            $spec_key = substr(strrchr($info->spec_key,'_'), 1);
            $address['province'] = self::$SecKillLogic->getDealersKill($info->id, 0, 'province');//城市
            $address['city'] = self::$SecKillLogic->getDealersKill($info->id, $address['province'][0]['id'], 'city');//城市
            $address['distribu'] = self::$SecKillLogic->getDealersKill($info->id, $address['city'][0]['id'], 'distribu');//城市
            $info->dealer = $address;
        }else{
            //查询收货地址
            $address = (new Order())->ajaxAddress($address_id == 0 ? $user_id : $address_id, $address_id == 0? 1: 2);
            $info->address = (object)array();
            if($address['id'])
                $info->address = $address;
        }

        //获取中奖用户的信息
        $info->user_name = Db::name('users')->where('user_id', $info->user_id)->getField('mobile') ?? '';

        //查询这个用户是否已经参加秒杀活动
        $count = M('order')->where(['prom_type'=>8,'prom_id'=>$seckill_id,'user_id'=>$user_id])->count();
        $info->count = empty($count)?1:2;


        //精品推荐3个
        $Goods = new Goods();
        $where['is_recommend'] = 1;
        $field = "goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,label,original_img,is_recommend,is_new,is_hot,exchange_integral";
        $info['recommend'] = $Goods->GoodsList(1, 1, $where, [], 3, $field);

        $this->assign('data', $info);
        $this->json(200, 'ok', $info);

        return $this->fetch('dist/one-dollar-detail');
    }

    /**
     * [支付]
     * @Auther 蒋峰
     * @DateTime
     */
    public function pay()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('seckill_id', '')) && $this->errorMsg(2001, 'seckill_id');//必传auction_id
        !is_numeric($seckill_id = I('seckill_id', 0)) && $this->errorMsg(2002, 'seckill_id');//必传
        empty(I('address_id', '')) && $this->errorMsg(2001, 'address_id');//必传auction_id
        !is_numeric($address_id = I('address_id', 0)) && $this->errorMsg(2002, 'address_id');//必传
        !is_numeric($type = I('type', 4)) && $this->errorMsg(2002, 'type');//必传
        $user_id = $this->checkToken(I('token'));

        //生成待支付订单
        $cartLogic = new CartLogic();
        $pay       = new \app\common\logic\Pay();
        $cartLogic->setUserId($user_id);
        $cartLogic->setSeckillModel($seckill_id);
        $cartLogic->setGoodsBuyNum(1);

        if(!$cartLogic->goods) $this->errorMsg(9999); // 没有该活动
        if($cartLogic->goods->end_time <= time()) $this->errorMsg(1477, '秒杀已结束'); //活动已结束
        if($cartLogic->goods->is_on_sale == 0) $this->errorMsg(9999); //活动已下架
        if($cartLogic->goods->state == 0) $this->errorMsg(9999); //活动删除

        if($cartLogic->goods->type == 1){
            empty($name = I('name', '')) && $this->errorMsg(2001, 'name');//必传auction_id
            empty(I('mobile', '')) && $this->errorMsg(2001, 'mobile');//必传auction_id
            !is_numeric($mobile = I('mobile', 0)) && $this->errorMsg(2002, 'mobile');//必传
            $cart_list[0] = $cartLogic->buyAuctionNow($cartLogic->goods->price,$address_id,8);
        }else{
            $cart_list[0] = $cartLogic->buyAuctionNow($cartLogic->goods->price,0,8);
        }

        $is_order = Db::name('order')->where(array('prom_id' => $seckill_id, 'prom_type' => 8))->field('order_id,pay_status,master_order_sn')->find();
        if($is_order){
            if($is_order['user_id'] == $user_id) $this->errorMsg(1477, '您已经参与过啦');
            if($is_order['user_id'] == $user_id){
                Db::name('order')->where(array('order_id' => $is_order['order_id']))->delete();
                Db::name('order_goods')->where(array('order_id' => $is_order['order_id']))->delete();
            }
        }

        $pay->payGoodsList($cart_list);
        $pay->setUserId($user_id);
        $placeOrder = new PlaceOrder($pay);
        if($cartLogic->goods->type == 1){
            $placeOrder->setType(1);
            $address['consignee'] = $name;
            $address['mobile'] = $mobile;
            $placeOrder->setUserAddress($address);
        }else{
            $placeOrder->setType(2);
            //可能存在在竞拍期间用户删除收货地址问题
            $address = Db::name('UserAddress')->where("address_id", $address_id)->find();
            $placeOrder->setUserAddress($address);
        }
        $placeOrder->addAuctionOrder($seckill_id, 8);
        $getOrderList = $placeOrder->getOrderList();
        (new Payment($type))->getCode($getOrderList[0]->order_sn);
//        update_pay_status($master_order_sn);
//        return $this->json("0000", '报名成功');
    }

    /**
     * [经销商城市联动]
     * @Auther 蒋峰
     * @DateTime
     */
    public function dealers()
    {
        (empty(I('type', '')) || !in_array($type = I('type', ''),['province', 'city', 'distribu']) ) && $this->errorMsg(2002, 'type');//必传
        (empty(I('seckill_id', '')) || !is_numeric($seckill_id = I('seckill_id', 0)) ) && $this->errorMsg(2002, 'seckill_id');//必传
        !is_numeric($pid = I('id', 0)) && $this->errorMsg(2002, 'id');
        $data = self::$SecKillLogic->getDealersKill($seckill_id, $pid, $type);
        if(!$data) $this->errorMsg(8910);
        $this->json("0000", "加载成功", $data);
    }
}