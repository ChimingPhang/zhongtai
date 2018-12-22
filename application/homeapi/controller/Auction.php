<?php
namespace app\homeapi\controller;

use app\api\logic\SocketLogic;
use app\api\model\GoodsAuction;
use app\api\logic\AuctionLogic;
use app\api\model\Auction as AuctionModel;
use think\Request;
use think\Cache;
use think\Db;

/**
 * 用户登录
 * @Author   XD
 * @DateTime 2018-07-11T15:09:13+0800
 */
class Auction extends Base {
    //每页显示数
    private static $pageNum = 10;
    //自动关闭订单时间
    private static $close_time = 3600;
    //页数
    public static $page = 1;
    public static $GoodsAuction;
    public static $AuctionLogic;
    public static $AuctionModel;

    public function __construct()
    {
        parent::__construct();
        //自动加载页数
        self::Initialization();
        !is_numeric(self::$page = I('page', 1)) && $this->errorMsg(2002, 'page');
    }

    public static function Initialization()
    {
        if(!self::$GoodsAuction)
            self::$GoodsAuction = new GoodsAuction();
        if(!self::$AuctionLogic)
            self::$AuctionLogic = new AuctionLogic();
        if(!self::$AuctionModel)
            self::$AuctionModel = new AuctionModel();
    }

    /**
     * [推荐列表]
     * @Auther 蒋峰
     * @DateTime
     */
    public function recommendList()
    {
        // token page
        $where = " 1 = 1 ";
        $user_id = (new \app\api\model\Users())->getUserOnToken(I('token'));

        if(!$user_id) $user_id = 0;
//        if($user_id) $where .= " and r.user_id =".$user_id;
//        $where .= " and a.preheat_time < ". time();
        //查询拍卖商品
        $fields = "a.id,a.goods_name,a.click_count,a.start_time,a.end_time,a.goods_remark,a.label,a.original_img,
            a.spec_key_name,a.is_end,a.price,a.num as offer_num,
            0 as bookings_type, 0 as sign_up_type";
        $auctionList = M('Auction')->alias('a')->field($fields)
            ->where($where)
            ->order('on_time', 'desc')
            ->limit((self::$page -1 ) * self::$pageNum, self::$pageNum)
            ->select();

        //查询用户设置提醒预约的商品
//        $remind = M('AuctionRemind')->where(array('user_id' => $user_id, 'status' => 0))->field('auction_id')->select();
        $bookings = M('AuctionBookings')->where(array('user_id' => $user_id))->field('auction_id')->select();
        $signUp = M('AuctionSignUp')->where(array('user_id' => $user_id))->field('auction_id')->select();
//        $remind = array_column($remind, 'auction_id');
        $bookings = array_column($bookings, 'auction_id');
        $signUp = array_column($signUp, 'auction_id');

        if($auctionList)
        foreach ($auctionList as &$value){
            $value['original_img'] = auction_thum_images($value['id'],200,150);
//            if(in_array($value['id'], $remind)) $value['remind_type'] = 1;
            if(in_array($value['id'], $bookings)) $value['bookings_type'] = 1;
            if(in_array($value['id'], $signUp)) $value['sign_up_type'] = 1;
        }

        if(!$auctionList) return $this->errorMsg(8910);
        return $this->json("0000", '加载成功', $auctionList);
    }

    /**
     * [人气排行]
     * @Auther 蒋峰
     * @DateTime
     */
    public function rankingList()
    {
        $user_id = (new \app\api\model\Users())->getUserOnToken(I('token'));
        if(!$user_id) $user_id = 0;

        // token page
        $where = " 1 = 1 ";
        $where .= ' and a.start_time > '.time();
        $where .= ' and a.preheat_time < '.time();

        $auctionList = M('Auction')->alias('a')
            ->join('auction_remind r','`a`.`id` = `r`.`auction_id`','left')
//            ->join('auction_bookings b','`a`.`id` = `b`.`auction_id`','left')
            ->where($where)
            ->field("a.id,a.goods_name,a.start_time,a.end_time,a.goods_remark,a.label,a.original_img,a.spec_key_name,a.is_end,a.price, count(r.auction_id) as remind_num, 0 as sign_up_type")
//            ->field("a.id,a.goods_name,a.start_time,a.end_time,a.goods_remark,a.label,a.original_img,a.spec_key_name,a.is_end,a.price, count(r.auction_id)+count(b.auction_id) as remind_num, 0 as sign_up_type")
            ->group("a.id")
            ->order('remind_num','desc')
            ->limit((self::$page -1 ) * self::$pageNum, self::$pageNum)
            ->select();

        $signUp = M('AuctionSignUp')->where(array('user_id' => $user_id, 'pay_status' => 1, 'state' => 0))->field('auction_id')->select();
        $signUp = array_column($signUp, 'auction_id');

        if($auctionList)
        foreach ($auctionList as &$value){
            $value['original_img'] = auction_thum_images($value['id'],200,150);
            if(in_array($value['id'], $signUp)) $value['sign_up_type'] = 1;
        }

        //加载广告
        $banner = $this->ad_position("11",'ad_name,ad_link,ad_code');
        $data = [
            'banner' => $banner['status'] == 0000 ? $banner['result'] : [],
            'auctionList' => $auctionList,
        ];
        return $this->json("0000", '加载成功', $data);

    }

    /**
     * [拍卖详情]
     * @Auther 蒋峰
     * @DateTime
     */
    public function special_auction_detail()
    {
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        //获取用户id
        $user_id = (new \app\api\model\Users())->getUserOnToken(I('token', '1'));
        //获取拍卖详情信息
        $field = 'id,goods_name,click_count,start_price as now_price,price as start_price,bail_price,markup_price,
        reserve_price,start_time,end_time,delay_time,goods_remark,goods_content,give_integral,type,label,
        banner_image,spec_key_name,is_end,num as offer_num';
        $auctionInfo = self::$AuctionModel->auctionInfo($auction_id, $field);
        if(!$auctionInfo) $this->errorMsg('9999');
        //banner图
        $auctionInfo->banner_image =$this->bannerImage($auctionInfo->banner_image);
        //添加围观量
        self::$GoodsAuction->addClickCount($auction_id);
        //是否提醒
        $auctionInfo->remind_type =self::$AuctionLogic->isRemind($auction_id, $user_id);
        //提醒人数
        $auctionInfo->remind_num =self::$AuctionLogic->remindNum($auction_id);
        //是否报名
        $auctionInfo->is_sign_up =self::$AuctionLogic->isSignUp($auction_id, $user_id)? 1: 0;
        //报名人数
        $auctionInfo->sign_up_num =self::$AuctionLogic->signUpNum($auction_id);
        //拍卖状态
        if($auctionInfo->start_time > time()){
            $auctionInfo->auction_type = 1;//等待中
        }else if($auctionInfo->end_time > time()){
            $auctionInfo->auction_type = 2;//拍卖中
        }else{
            if($auctionInfo->is_end) $auctionInfo->auction_type = 4;//已结束
            else $auctionInfo->auction_type = 3;//延迟中
        }

        $auctionInfo->goods_content = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $auctionInfo->goods_content . '</div></body></html>');
        //出价列表
        $delayTime = self::$AuctionLogic->offerList($auction_id, 1, $user_id, 3);
        $auctionInfo->offer_list = $delayTime;

//        return $this->json('0000','加载成功', $auctionInfo);
        return $this->fetch('dist/special_auction_detail');
    }

    /**
     * [提醒我]
     * @Auther 蒋峰
     * @DateTime
     */
    public function remind()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        $this->checkToken(I('token'));
        //查看拍卖商品信息
        $auctionInfo = self::$AuctionModel->auctionInfo($auction_id);
        if( $auctionInfo->start_time <= time() ) return $this->errorMsg(1477, '拍卖已开始或结束');

        $res = self::$AuctionLogic->remind($auction_id, $this->userInfo['user_id'], $auctionInfo->start_time);
        if($res) return $this->errorMsg("0000", '取消提醒成功');
        return $this->json("0000", '设置提醒成功');
    }

    /**
     * [出价]
     * @Auther 蒋峰
     * @DateTime
     */
    public function offer()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传auction_id
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        empty(I('price', '')) && $this->errorMsg(2002, 'price');//必传price
        !is_numeric($price = I('price', 0)) && $this->errorMsg(2002, 'price');//必传
        $user_id = $this->checkToken(I('token'));

        //判断是否报名
        if(!self::$AuctionLogic->isSignUp($auction_id, $user_id)) return $this->errorMsg(1477, "请先报名活动");
        //判断活动状态
        $auctionInfo = self::$AuctionModel->auctionInfo($auction_id, 'goods_name,markup_price,reserve_price,start_time,end_time,delay_time,is_end');
        if($auctionInfo->is_end == 1) return $this->errorMsg(1477, "拍卖已经结束,无法出价");
        if($auctionInfo->start_time > time()) return $this->errorMsg(1477, "拍卖还未开始,无法出价");

        //从队列中取出出价记录 $end_id领先人id $end_price出价钱数
        $priceArr = Cache::get("auction_price_{$auction_id}");
        $priceArr ?
            list($end_id,$end_price) = end($priceArr)
        :
            $end_id = $end_price = 0;

        !$priceArr && $priceArr = [];

        if ($user_id == $end_id) return $this->errorMsg(1477, "您已领先,请勿重新出价");
        if ($price <= $end_price) return $this->errorMsg(1477, "此价格已被领先,请重新出价");
        if ($price - $end_price < $auctionInfo->markup_price) return $this->errorMsg(1477, "加价请大于加价幅度");
        if($auctionInfo->end_time <= time() && !Cache::get("auction_price_{$auction_id}_delay")) {
            //处理拍卖结束流程
            self::$AuctionLogic->handleAuctionEnd($auction_id);
            return $this->errorMsg(1477, "拍卖已结束,感谢您的参与");
        }
        //出价记录加入队列
        array_push($priceArr, [$user_id,$price]);
        Cache::set("auction_price_{$auction_id}", $priceArr, 3600*7);
        //出价记录入库
        $res = self::$AuctionLogic->offer($auction_id, $user_id, $price, $this->userInfo['nickname'] ?? $this->userInfo['mobile'], $equipment =
            1,$auctionInfo);
        if($res['status'] == 0){
            Cache::set("auction_price_{$auction_id}_delay", 1, $auctionInfo->delay_time*60);
            return $this->json("0000", '出价成功');
        }else{
            array_pop($priceArr);
            Cache::set("auction_price_{$auction_id}", $priceArr, 3600*7);
            return $this->json("1477", $res['msg']);
        }

    }

    /**
     * [出价记录]
     * @Auther 蒋峰
     * @DateTime
     */
    public function offerList()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传auction_id
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        $user_id = (new \app\api\model\Users())->getUserOnToken(I('token'));

        $offerList = self::$AuctionLogic->offerList($auction_id, self::$page, $user_id);

        if(empty($offerList)) $this->errorMsg(8910);

        $this->json("0000", "加载成功", $offerList);
    }

    /**
     * [报名]
     * @Auther 蒋峰
     * @DateTime
     */
    public function signUp()
    {
//        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传auction_id
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        empty(I('address_id', '')) && $this->errorMsg(2001, 'address_id');//必传address_id
        !is_numeric($address_id = I('address_id', 0)) && $this->errorMsg(2002, 'address_id');//必传
//        empty(I('type', '')) && $this->errorMsg(2001, 'type');//必传address_id
        !is_numeric($type = I('type', 4)) && $this->errorMsg(2002, 'type');//必传
        $user_id = $this->checkToken(I('token'));

        $auction = self::$AuctionModel->auctionInfo($auction_id, 'type,is_end,bail_price,start_time');
        if(!$auction || !$auction->is_end == 0) $this->errorMsg(1477, '此拍卖已结束或已关闭');
//        if($auction->start_time > time()) return $this->errorMsg(1477, "拍卖还未开始,不能报名");
        //判断是否重复报名
        if(self::$AuctionLogic->isSignUp($auction_id, $user_id)) return $this->errorMsg(1477, "您已经报过名啦");

        //处理报名数据
        $data = [
            'auction_id' => $auction_id,
            'user_id' => $user_id,
            'price' => $auction->bail_price,
            'create_time' => time(),
            'state' => 0,
            'order_sn' => self::$AuctionLogic->get_order_sn('sign'),
            'pay_status' => 0,
            'address_id' => $address_id,
        ];

        if($auction->type == 1){
            $site = Db::name('DealersAuction')->where(array('dealers_id' => $address_id, 'auction_id' => $auction_id))
                ->count();
            if(!$site) return $this->errorMsg(1477, '经销商id错误');
            $data['type'] = 1;
        }else{
            $site = Db::name('UserAddress')->where(array('user_id' => $user_id, 'address_id' => $address_id))->count();
            if(!$site) return $this->errorMsg(1477, '收货地址id错误');
            $data['type'] = 2;
        }
        $res = self::$AuctionLogic->auctionSignUp($data);
        if(!$res) return $this->errorMsg(1477, '网络异常');

//        (new Payment($type))->getCode($res);
        update_pay_status($res);
        return $this->json("0000", '报名成功');
    }

    /**
     * [报名预处理收货信息]
     * @Auther 蒋峰
     * @DateTime
     */
    public function receiptInformation()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传auction_id
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        !is_numeric($address_id = I('address_id', 0)) && $this->errorMsg(2002, 'address_id');//选传address_id
        //判断是否实名认证
        $user_id = $this->checkToken(I('token'));

        //判断商品类型
        $auction = self::$AuctionModel->auctionInfo($auction_id, 'type,bail_price,is_end');
        if(!$auction || !$auction->is_end == 0) $this->errorMsg(1477, '此拍卖已结束或已关闭');
        $data = [];
        $data['price'] = $auction->bail_price;
        if($auction->type == 1){
            //查询经销商
            $data['type'] = 1;
            $appearance['province'] = self::$AuctionLogic->getDealersAuction($auction_id, 0, 'province');//城市
            $appearance['city'] = self::$AuctionLogic->getDealersAuction($auction_id, $appearance['province'][0]['id'], 'city');//城市
            $appearance['distribu'] = self::$AuctionLogic->getDealersAuction($auction_id, $appearance['city'][0]['id'], 'distribu');//城市
            $data['address'] = $appearance;
        }else{
            //查询收货地址
            $address = (new Order())->ajaxAddress($address_id == 0 ? $user_id : $address_id, $address_id == 0? 1: 2);
            $data['address'] = (object)array();
            if($address['id'])
                $data['address'] = $address;
            $data['type'] = 2;
        }
        return $this->json("0000", '加载成功', $data);
    }

    /**
     * [经销商城市联动]
     * @Auther 蒋峰
     * @DateTime
     */
    public function dealers()
    {
        (empty(I('type', '')) || !in_array($type = I('type', ''),['province', 'city', 'distribu']) ) && $this->errorMsg(2002, 'type');//必传
        (empty(I('auction_id', '')) || !is_numeric($auction_id = I('auction_id', 0)) ) && $this->errorMsg(2002, 'auction_id');//必传
        !is_numeric($pid = I('id', 0)) && $this->errorMsg(2002, 'id');

        $data = self::$AuctionLogic->getDealersAuction($auction_id, $pid, $type);
        if(!$data) $this->errorMsg(8910);
        $this->json("0000", "加载成功", $data);
    }

    /**
     * [搜索结果]
     * @Auther 蒋峰
     * @DateTime
     */
    public function search()
    {
        //验证参数
        empty($title = I('title', '')) && $this->errorMsg(2001, 'title'); //必传
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
//        self::$redis->Zincrby('hot_words', 1, $title);
        //排序顺序
        $order = [];
        if(!$sales_sum && !$price) $order['start_time'] = 'asc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['price'] = $price;
        //检索条件
        $where = [];
        if($title) $where['goods_name'] = ['like','%'.$title.'%'];

//        $field = "goods_id,goods_name,price,original_img,is_recommend,is_new,is_hot,type";
        $goodsList = self::$AuctionModel->GoodsList(self::$page, 0, $where, $order, self::$pageNum);
        // if(!$goodsList) $this->errorMsg(8910);

        $banner = [];
        if(self::$page == 1){
            $banner = $this->ad_position("9",'ad_name,ad_link,ad_code');
            if($banner['status'] == 0000) $banner = $banner['result'];
            else $banner = [];
            $data = [
                "banner" => $banner,
                "list" => $goodsList
            ];
        }else{
            $data = [
                "list" => $goodsList
            ];
        }

        $this->json("0000", "加载成功", $data);
    }

    /**
     * [我的拍卖]
     * @Auther 蒋峰
     * @DateTime
     */
    /*public function myAuction()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('type', '')) && $this->errorMsg(2001, 'type');//必传
        (!is_string($type = trim(I('type', 0))) || !in_array($type, ['partake', 'end', 'order']) ) && $this->errorMsg(2002, 'type');//必传
        $user_id = $this->checkToken(I('token'));

        //goods_id 164 item_id 5 token 20e157278056257c71fead302face897
        switch ($type) {
            case 'partake':
                $data = self::$AuctionLogic->auctionPartake($this->userInfo['user_id'], self::$page);
                break;
            case 'end':
                $data = self::$AuctionLogic->auctionEndList($this->userInfo['user_id'], self::$page);
                break;
            case 'order':
                $data = self::$AuctionLogic->auctionOrder($this->userInfo['user_id'], self::$page);
                break;
            default :
                $this->errorMsg(2002, 'type');//必传
        }
        if(!$data) return $this->errorMsg(8910);
        return $this->json("0000", "获取成功", $data);
    }*/

    /**
     * [获取拍卖订单]
     * @Auther 蒋峰
     * @DateTime
     */
    public function auctionOrder()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传

        $user_id = $this->checkToken(I('token'));

        $order = new \app\common\model\Order();
        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单
        $where = ' user_id=:user_id';
        $bind['user_id'] = $user_id;
        $whereSon = [];
        $type = 0;
        $where.=' and order_status <> 5 ';//作废订单不列出来
        $where.=' and deleted = 0 ';        //删除的订单不列出来
        $where.=' and prom_type = 7 ';//查询拍卖活动订单
        $limit = (self::$page - 1) * self::$pageNum;

        $order_str = "order_id DESC";
        //获取订单
        $order_list_obj = Db::name('order')->order($order_str)->where($where)->bind($bind)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,shipping_status,add_time,order_amount,type,dj')->limit($limit, self::$pageNum)->select();
//         echo M('order')->getLastSql();die;
        $arr = [];
        if($order_list_obj){
            foreach($order_list_obj as $k => $v)
            {
                $arr[$k]['order_type']= $type > 0 ? $type :$order->getOrderStatusDetailAttr(null,$v);
                $arr[$k]['order_prices'] = $v['total_amount'];//订单显示价格
                $arr[$k]['close_time'] = $v['add_time'] + self::$close_time;//订单显示价格
                $arr[$k]['type'] = $v['type'];
                $arr[$k]['order_id'] = $v['order_id'];
                $arr[$k]['master_order_sn'] = $v['master_order_sn'];
                // $v['order_button'] = $order->getOrderButtonAttr(null,$v);
                $list = Db::name('order_goods'.$select_year)->cache(true,3)->where($whereSon)->where('order_id = '.$v['order_id'])->field('goods_name,is_shouhuo,spec_key_name as spec,goods_num,final_price,rec_id,is_send,is_comment,goods_id')->find();
                $list['goods_img'] = auction_thum_images($list['goods_id'],200,150);
                $arr[$k] = array_merge($arr[$k], $list);
                // $v['store'] = M('store')->cache(true)->where('store_id = '.$v['store_id'])->field('store_id,store_name,store_qq')->find();
            }

        }
        if(!$arr) return $this->errorMsg(8910);
        return $this->json("0000", "获取成功", $arr);
    }

    /**
     * [已结束]
     * @Auther 蒋峰
     * @DateTime
     */
    public function auctionEndList()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传

        $user_id = $this->checkToken(I('token'));

        $auction = Db::name("GoodsAuction")->alias('g')
            ->join('auction_sign_up s','g.id=s.auction_id', 'left')
            ->where(array('g.is_end' => ['>' ,0],'s.user_id' => $user_id))
            ->field("g.id,g.goods_name,g.bail_price,g.deal_price,g.spec_key_name,g.user_id,0 as deal_type")
            ->limit((self::$page - 1) * self::$pageNum, self::$pageNum)
            ->select();

        if($auction)
            foreach ($auction as &$value){
                if($value['user_id'] == $user_id){
                    $value['deal_type'] = 1;
                }
                $value['goods_img'] = auction_thum_images($value['id'],200,150);
                unset($value['user_id']);
            }
        if(!$auction) return $this->errorMsg(8910);
        return $this->json("0000", "获取成功", $auction);
    }

    /**
     * [我参与的]
     * @Auther 蒋峰
     * @DateTime
     */
    public function auctionPartake()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传

        $user_id = $this->checkToken(I('token'));

        $auction = Db::name("Auction")->alias('g')
            ->join('auction_sign_up s','g.id=s.auction_id', 'left')
            ->where(array('g.is_end' => ['=' ,0],'s.user_id' => $user_id, 'g.start_time' => ['<', time()]))
            ->field("g.id,g.goods_name,g.end_time,g.bail_price,g.price,g.spec_key_name,g.create_time,g.delay_time")
            ->limit((self::$page - 1) * self::$pageNum, self::$pageNum)
            ->select();

        if($auction)
            foreach ($auction as &$value){
                if($value['create_time'] && $value['create_time'] + ($value['delay_time'] * 60) > $value['end_time']){
                    $value['end_time'] = $value['create_time'] + ($value['delay_time'] * 60);
                }
                unset($value['create_time']);
                unset($value['delay_time']);

                $value['goods_img'] = auction_thum_images($value['id'],200,150);
            }
        if(!$auction) return $this->errorMsg(8910);
        return $this->json("0000", "获取成功", $auction);
    }

    /**
     * [保证金详情]
     * @Auther 蒋峰
     * @DateTime
     */
    public function bailInfo()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传auction_id
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        //判断是否实名认证
        $user_id = $this->checkToken(I('token'));

        //判断商品类型
        $signUp = self::$AuctionLogic->isSignUp($auction_id, $user_id);
        if(!$signUp) $this->errorMsg(9999);

        $auction = self::$AuctionModel->auctionInfo($auction_id, 'id,goods_name,bail_price,spec_key_name');
        if(!$auction) $this->errorMsg(9999);

        $auction->goods_img = auction_thum_images($auction->id,200,150);

        $data = [
            "price" => $signUp['price'],
            "state" => $signUp['state'],
            "pay_name" => $signUp['pay_name'],
            "pay_time" => $signUp['pay_time'],
            "refund_time" => $signUp['refund_time'],
            "goods_info" => $auction,
        ];

        return $this->json("0000", '加载成功', $data);
    }

    /**
     * [订单详情]
     * @Auther 蒋峰
     * @DateTime
     */
    public function auctionOrderInfo()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('type', '')) && $this->errorMsg(2001, 'type');//必传
        !is_numeric($type = trim(I('type', 0))) && $this->errorMsg(2002, 'type');//必传
                empty(I('order_id', '')) && $this->errorMsg(2001, 'order_id');//必传
        !is_string($order_id = trim(I('order_id', 0))) && $this->errorMsg(2002, 'order_id');//必传
            $user_id = $this->checkToken(I('token'));

        $select_year = select_year(); // 查询 三个月,今年内,2016年等....订单

        $Order = new \app\common\model\Order();
        $map['order_id'] = $order_id;
        $map['user_id'] = $user_id;

        //1车型订单，2配件订单
        if($type == 2){
            $orderObj = $Order->where($map)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_code,province,city,district,twon,address,pay_status,shipping_status,add_time,order_amount,consignee,mobile')->find();
            if(!$orderObj){
                $this->throwError('没有获取到订单信息');
            }
            $arr                    = [];
            $arr['order_type']      = $Order->getOrderStatusDetailAttr(null,$orderObj);
            $arr['order_prices']    = $orderObj['total_amount'];
            $arr['order_id']        = $orderObj['order_id'];
            $arr['master_order_sn'] = $orderObj['master_order_sn'];
            $arr['mobile']          = $orderObj['mobile'];
            $arr['name']            = $orderObj['consignee'];
            $area_id[] = $orderObj['province'];
            $area_id[] = $orderObj['city'];
            $area_id[] = $orderObj['district'];
            $area_id[] = $orderObj['twon'];
            $area_id = implode(',', $area_id);
            $regionList = Db::name('region')->where("id", "in", $area_id)->cache(true)->order('level')->getField('id,name');
            $arr['address'] = implode('-', $regionList).'-'.$orderObj['address'];
        }else{
            $orderObj = $Order->where($map)->field('total_amount,master_order_sn,order_id,refund_status,order_status,pay_status,pay_code,shipping_status,add_time,order_amount,dj,store_id,consignee,mobile')->find();
            if(!$orderObj){
                $this->throwError('没有获取到订单信息');
            }
            $store                  = M('dealers')->where(['id'=>$orderObj['store_id']])->field('name,desc,mobile')->find();
            $arr                    = [];
            $arr['order_type']      = $Order->getOrderStatusDetailAttr(null,$orderObj);
            $arr['order_prices']    = $orderObj['dj'];
            $arr['order_id']        = $orderObj['order_id'];
            $arr['master_order_sn'] = $orderObj['master_order_sn'];
            $arr['name']            = $orderObj['consignee'];
            $arr['mobile']          = $orderObj['mobile'];
            $arr['store_name']      = $store['name'];
            $arr['store_address']   = $store['desc'];
            $arr['store_phone']     = $store['mobile'];
        }
        $arr['type'] = $type;
        $arr['close_time'] = $orderObj['add_time'] + self::$close_time;//订单显示价格


        // $v['order_button']   = $order->getOrderButtonAttr(null,$v);
        //获取订单
        $arr['list'] = M('order_goods'.$select_year)->cache(true,3)->where('order_id = '.$orderObj['order_id'])->field('goods_name,spec_key_name as spec,is_shouhuo,is_send,goods_num,final_price as goods_price,rec_id,is_comment,goods_id')->select();
        foreach ($arr['list'] as $key => $value) {
            $arr['list'][$key]['goods_img'] = auction_thum_images($value['goods_id'],200,150);
        }
        $this->json('0000','获取成功',$arr);

    }

    /**
     * [预约]
     * @Auther 蒋峰
     * @DateTime
     */
    public function makeAnAppointment()
    {
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        empty(I('token', '')) && $this->errorMsg(2001, 'token');//必传
        empty(I('auction_id', '')) && $this->errorMsg(2001, 'auction_id');//必传
        !is_numeric($auction_id = I('auction_id', 0)) && $this->errorMsg(2002, 'auction_id');//必传
        empty($name = trim(I('name', ''))) && $this->errorMsg(2001, 'name');//必传
        empty(I('mobile', '')) && $this->errorMsg(2001, 'mobile');//必传
        (!is_numeric($mobile = I('mobile', 0)) || !check_mobile($mobile) ) && $this->errorMsg(2002, 'mobile');//必传
        $this->checkToken(I('token'));
        //查看拍卖商品信息
        $auctionInfo = self::$AuctionModel->auctionInfo($auction_id);
        if( $auctionInfo->preheat_time > time() ) return $this->errorMsg(9999);
        if( $auctionInfo->start_time <= time() ) return $this->errorMsg(1477, '拍卖已开始或结束');

        $state = 0;
        if($auctionInfo->start_time - time() <= 3600) $state = 1;

        $res = self::$AuctionLogic->bookings($auction_id, $this->userInfo['user_id'], $auctionInfo->start_time,
            $name, $mobile, $state);
        if($res) return $this->errorMsg("1477", '你已经预约过啦，请勿重复预约');
        //发送短信
        $params['goods_name'] = $auctionInfo->goods_name . ' ' . $auctionInfo->spec_key_name;
        $params['time'] = friend_date($auctionInfo->start_time);
        XdsendSms(10,$mobile,$params);

        return $this->json("0000", '预约成功');
    }

    public function timingQuery()
    {
//        self::$AuctionLogic->refundSignUp();
//        die;
        self::$AuctionLogic->timingQuery();
//        self::$AuctionLogic->handleAuctionEnd(178);
    }

    /**
     * [socket]
     * @Auther 蒋峰
     * @DateTime
     */
    public function socket()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        ob_implicit_flush();

        //地址与接口，即创建socket时需要服务器的IP和端口
        $sk=new \app\api\logic\SocketLogic('0.0.0.0',33333);
//        dump($sk->init);die;
        //对创建的socket循环进行监听，处理数据
        $sk->run();
    }
}