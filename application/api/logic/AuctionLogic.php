<?php
namespace app\api\logic;

use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use think\Model;
use think\Db;

class AuctionLogic extends Model
{
    private static $order_close_time = 3600*3;//订单保留时间
    private static $bookings_remind_time = 60*60;//预约提醒时间
    private static $remind_time = 5*60;//提醒时间

    /**
     * [出价列表]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param $page
     * @param $num
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function offerList($auction_id, $page, $user_id, $num = 20 )
    {
        $offerList = Db::name('auction_offer')->where('auction_id', $auction_id)->order('create_time', 'desc')->field('user_id,username,price,create_time,equipment')->limit(($page - 1) * $num, $num)->select();
        if($offerList)
        foreach ($offerList as &$value) {
            if($value['user_id'] == $user_id){
                $value['username'] = '我';
            }
            if(check_mobile($value['username'])){
                $value['username'] = mobile_hide($value['username']);
            };
            unset($value['user_id']);
        }
        return $offerList;
    }

    /**
     * [出价]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param $user_id
     * @param $price
     * @param $nickname
     * @param int $equipment
     * @return array
     */
    public function offer($auction_id, $user_id, $price, $nickname, $equipment = 1,$auctionInfo)
    {
        $data = [
            'auction_id' => $auction_id,
        ];
        $auctionOffer = Db::name("AuctionOffer")->where($data)->order('price','desc')->find();
        if($auctionOffer){
            if ($user_id == $auctionOffer['user_id']) return array('status' => 1, 'msg' => '您已领先,请勿重新出价');
            if ($price <= $auctionOffer['price']) return array('status' => 1, 'msg' => '此价格已被领先,请重新出价');
            if ($price - $auctionOffer['price'] < $auctionInfo->markup_price) return array('status' => 1, 'msg' => '加价请大于加价幅度');
        }

        $data = [
            'auction_id' => $auction_id,
            'user_id' => $user_id,
            'price' => $price,
            'username' => $nickname,
            'create_time' => time(),
            'equipment' => $equipment,
        ];
        Db::name("AuctionOffer")->add($data);
        return array('status' => 0, 'msg' => '出价成功');
    }

    /**
     * [提醒人数]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @return int|string
     */
    public function remindNum($auction_id)
    {
        return Db::name('auction_remind')->where('auction_id', $auction_id)->count();
    }

    /**
     * [设置提醒]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param $user_id
     * @param $start_time
     * @return bool
     */
    public function remind($auction_id, $user_id, $start_time)
    {
        $num = Db::name('auction_remind')->where('auction_id', $auction_id)->where('user_id', $user_id)->count();
        if($num){
            Db::name('auction_remind')->where('auction_id', $auction_id)->where('user_id', $user_id)->delete();
            return true;
        }else{
            Db::name('auction_remind')->add(array('auction_id'=> $auction_id,'user_id'=> $user_id,'create_time'=> time(),'start_time' => $start_time));
            return false;
        }
    }

    /**
     * [设置预约]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param $user_id
     * @param $start_time
     * @return bool
     */
    public function bookings($auction_id, $user_id, $start_time, $name, $mobile, $status = 0)
    {
        $num = Db::name('auction_bookings')->where('auction_id', $auction_id)->where('user_id', $user_id)->count();
        if($num){
            return true;
        }else{
            Db::name('auction_bookings')->add(array('auction_id'=> $auction_id,'user_id'=> $user_id,'create_time'=> time(),'start_time' => $start_time,'name' => $name,'mobile' => $mobile, 'state' => $status));
            return false;
        }
    }

    /**
     * [判断用户是否报名]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param $user_id
     * @return int|string
     */
    public function isSignUp($auction_id, $user_id)
    {
        return Db::name('auction_sign_up')->where(array('auction_id'=> $auction_id, 'user_id'=> $user_id))->find();
    }

    /**
     * [判断用户是否报名]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param $user_id
     * @return int|string
     */
    public function isRemind($auction_id, $user_id)
    {
        return Db::name('auction_remind')->where(array('auction_id'=> $auction_id, 'user_id'=> $user_id))->count();
    }

    /**
     * [拍卖报名人数]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @return int|string
     */
    public function signUpNum($auction_id)
    {
//        return Db::name('auction_sign_up')->where(array('auction_id'=> $auction_id, 'pay_status' => 1 ))->count();
        return Db::name('auction_sign_up')->where(array('auction_id'=> $auction_id))->count();
    }

    /**
     * [获取活动商品经销商]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     */
    public function getDealersAuction($auction_id, $pid = 0, $type = 'province'){
        switch ($type) {
            case 'province':
                $dealers = $this->dealersAuction($auction_id, 0, []);
                $regionIds = implode(',',array_column($dealers,"province"));
                $data = (new GoodsLogic())->getRegion($regionIds);
                break;
            case 'city':
                $dealers = $this->dealersAuction($auction_id, ['province' => $pid]);
                $regionIds = implode(',',array_column($dealers,"city"));
                $data = (new GoodsLogic())->getRegion($regionIds);
                break;
            case 'distribu':
                $data = $this->dealersAuction($auction_id, ['city' => $pid],'id,name');
                break;
            default :
                $data = [];
                break;
        }
        return $data;
    }

    /**
     * [商品经销商信息]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param array $where
     * @param string $field
     * @return mixed
     */
    public function dealersAuction($auction_id, $where = [], $field = 'id,name,province,city')
    {
        $DealersAuction = M("DealersAuction");
        $DealersAuction->alias('c')
            ->join('dealers d', 'c.dealers_id = d.id', 'left')
            ->group('c.dealers_id')
            ->where(array('c.auction_id' => $auction_id, 'status' => 1,'deleted'=>1))
            ->field($field);
        if($where)
            $DealersAuction->where($where);

        return $DealersAuction->select();
    }

    /**
     * [定时查询|定时任务调用方法]
     * @Auther 蒋峰
     * @DateTime
     */
    public function timingQuery()
    {
        //查询代开始活动
        $auction = Db::name('GoodsAuction')->where(array('start_time' => ['>', time()], 'state'=>1, 'is_on_sale' => 1))
            ->field('id,goods_name,spec_key_name')
            ->select();
        if($auction)
        foreach ($auction as $value) {
            //预约提醒
            $this->remindAuctionBookings($value['id']);
            //短信提醒
            $this->remindAuction($value['id']);
        }

        //查已结束开始活动
        $auction = Db::name('GoodsAuction')->where(array('end_time' => ['<=', time()], 'state'=>1, 'is_on_sale' => 1,
            "is_end" => 0))
            ->field('id')
            ->select();
        if($auction)
        foreach ($auction as $value){
            //订单结束处理
            $this->handleAuctionEnd($value['id']);
        }

        //订单过期未付款处理
        $this->orderEnd();

        //活动结束退还保证金
        $this->refundSignUp();

        //查询代开始活动提醒用户
        /*if($auction)
        foreach ($auction as $value){
            //用户提醒状态
            $users = Db::name('AuctionRemind')->where(array('auction_id' => $value['id'], 'status' => 0))->field('user_id')->select();
            if($users){
                $user_ids = implode(",", array_column($users, 'user_id'));
                //查询要发送的用户手机号
                $usersMobile = Db::name('Users')->where(array('user_id' => ['in', $user_ids]))->field('mobile')
                    ->select();

                $params['goods_name'] = $value['goods_name'] . ' ' . $value['spec_key_name'];
                //发送短信
                $result = XdsendSms(8,implode(",", array_column($usersMobile, 'mobile')),$params);

                if($result['status'] == 1){
                    //标记短信发送状态

                    Db::name('AuctionRemind')->where(array('auction_id' => $value['id'], 'user_id' => ['in', $user_ids]))->save(array('status' => 1));
                }
            }
        }*/
    }

    /**
     * [拍卖结束处理]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     */
    public function handleAuctionEnd($auction_id, $end = 0)
    {
        $auctionInfo = Db::name("GoodsAuction")->where(array('id' => $auction_id, 'is_on_sale' => 1, 'is_end' => 0))->cache(true, 60)
            ->find();

        //判断拍卖状态
        if(!$auctionInfo) return false;
        //判断拍卖是否结束
        if($auctionInfo['end_time'] > time() ) return false;

        $end_offer = Db::name("AuctionOffer")->where("auction_id", $auction_id)->order("create_time", "desc")->find();
        if($end_offer){
            //判断是否处于延迟
            if($end_offer['create_time'] + ( $auctionInfo['delay_time'] * 60 ) > time()){
                return false;
            }
            $sign_up = Db::name("AuctionSignUp")->where(array('auction_id' => $auction_id, 'user_id' => $end_offer['user_id'], 'pay_status' => 1))->find();
            if(!$sign_up) return false;
            // 启动事务
            Db::startTrans();
            try{
                //修改拍卖状态
                if(! Db::name("GoodsAuction")->where('id', $auction_id)->save(array('is_end' => 1,'user_id' => $sign_up['user_id'],'last_update' => time(), 'deal_price' => $end_offer['price'])))
                    throw new \Exception("修改订单状态错误", 1);
                //修改中拍用户保证金状态
                if(! Db::name("AuctionSignUp")->where(array('auction_id' => $auction_id, 'user_id' => $end_offer['user_id'], 'pay_status' => 1))->save(array('frozen' => 1)))
                    throw new \Exception("修改保证金状态错误", 1);
                //生成待支付订单
                $cartLogic = new CartLogic();
                $pay       = new Pay();
                $cartLogic->setUserId($sign_up['user_id']);
                $cartLogic->setAuctionModel($auction_id);
                $cartLogic->setGoodsBuyNum(1);
                if($auctionInfo['type'] == 1){
                    $cart_list[0] = $cartLogic->buyAuctionNow($end_offer['price'],$sign_up['address_id']);
                }else{
                    $cart_list[0] = $cartLogic->buyAuctionNow($end_offer['price']);
                }
                $pay->payGoodsList($cart_list);
                $pay->setUserId($sign_up['user_id']);
                $placeOrder = new PlaceOrder($pay);
                if($auctionInfo['type'] == 1){
                    $placeOrder->setType(1);
                }else{
                    $placeOrder->setType(2);
                    //可能存在在竞拍期间用户删除收货地址问题
                    $address = Db::name('UserAddress')->where("address_id", $sign_up['address_id'])->find();
                    $placeOrder->setUserAddress($address);
                }
                $placeOrder->addAuctionOrder($auction_id);
                $master_order_sn = $placeOrder->getMasterOrderSn();
                if(!$master_order_sn) throw new \Exception("生成订单错误", 1);
                //向竞拍用户发送提醒
                $offer = Db::name("AuctionOffer")->where("auction_id", $auction_id)->order("create_time", "desc")->group('user_id')->field('user_id')->select();
                //查询要发送短信的用户
                $user_ids = implode(",", array_column($offer, 'user_id'));
                $usersMobile = Db::name('Users')->where(array('user_id' => ['in', $user_ids]))->field('mobile')->select();

                if($usersMobile)
                foreach ($usersMobile as $value){
                    //发送短信
                    $params['goods_name'] = $auctionInfo['goods_name'] . ' ' . $auctionInfo['spec_key_name'];
                    if($value['user_id'] == $end_offer['user_id']){
                        //竞拍成功提醒
                        $params['time'] = date("Y-m-d H:i:s", time() + self::$order_close_time);
                        XdsendSms(11,$value['mobile'],$params);
                    }else{
                        //竞拍失败提醒
                        XdsendSms(12,$value['mobile'],$params);
                    }
                }

                // 提交事务
                Db::commit();
                echo $master_order_sn;

            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();

                if($e->getCode() == 1 && $end == 0){
                    return $this->auctionEnd($auction_id, 1);
                }
                return $e->getMessage();
            }
        }else{
            //流拍
            Db::name("GoodsAuction")->where('id', $auction_id)->save(array('is_end' => 2, 'last_update' => time()));
            return true;
        }
        return false;

    }

    /**
     * [预约提醒]
     * @Auther 蒋峰
     * @DateTime
     */
    public function remindAuctionBookings($auction_id)
    {
        $auctionInfo = Db::name("GoodsAuction")->where(array('id' => $auction_id, 'is_end' => 0))->cache(true, 60)->find();

        //判断拍卖状态
        if(!$auctionInfo) return false;
        //判断拍卖是否结束
        if($auctionInfo['start_time'] > time() + self::$bookings_remind_time ) return false;

        //用户提醒状态
        $users = Db::name('AuctionBookings')->where(array('auction_id' => $auction_id, 'status' => 0))->field('user_id')->select();
        if($users){
            $user_ids = implode(",", array_column($users, 'user_id'));
            //查询要发送的用户手机号
//            $usersMobile = Db::name('Users')->where(array('user_id' => ['in', $user_ids]))->field('mobile')
//                ->select();

            $params['goods_name'] = $auctionInfo['goods_name'] . ' ' . $auctionInfo['spec_key_name'];
            $params['time'] = friend_date($auctionInfo['start_time']);
            //发送短信
            $result = XdsendSms(10,implode(",", array_column($users, 'mobile')),$params);

            if($result['status'] == 1){
                //标记短信发送状态
                Db::name('AuctionBookings')->where(array('auction_id' => $auctionInfo['id'], 'user_id' => ['in', $user_ids]))->save(array('status' => 1));
            }
        }
        return true;
    }

    /**
     * [开始前提醒]
     * @Auther 蒋峰
     * @DateTime
     */
    public function remindAuction($auction_id)
    {
        $auctionInfo = Db::name("GoodsAuction")->where(array('id' => $auction_id, 'is_end' => 0))->cache(true, 60)->find();

        //判断拍卖状态
        if(!$auctionInfo) return false;
        //判断拍卖是否结束
        if($auctionInfo['start_time'] > time() + self::$remind_time ) return false;

        //用户提醒状态
        $users = Db::name('AuctionRemind')->where(array('auction_id' => $auction_id, 'status' => 0))->field('user_id')->select();
        if($users){
            $user_ids = implode(",", array_column($users, 'user_id'));
            //查询要发送的用户手机号
            $usersMobile = Db::name('Users')->where(array('user_id' => ['in', $user_ids]))->field('mobile')
                ->select();

            $params['goods_name'] = $auctionInfo['goods_name'] . ' ' . $auctionInfo['spec_key_name'];
            $params['time'] = friend_date($auctionInfo['start_time']);
            //发送短信
            $result = XdsendSms(8,implode(",", array_column($usersMobile, 'mobile')),$params);

            if($result['status'] == 1){
                //标记短信发送状态
                Db::name('AuctionRemind')->where(array('auction_id' => $auctionInfo['id'], 'user_id' => ['in', $user_ids]))->save(array('status' => 1));
            }
        }
        return true;
    }

    /**
     * [订单结束未支付]
     * @Auther 蒋峰
     * @DateTime
     */
    public function orderEnd(){
        //查看订单未支付
        $order = Db::name("order")->where(array('pay_status' => 0, 'prom_type' => 7))->field('order_id,user_id,prom_id,add_time')->select();
        if($order)
        foreach ($order as $value){
            //判断是否过期
            if($value['add_time'] + self::$order_close_time < time()){
                // 启动事务
                Db::startTrans();
                try{
                    //关闭订单
                    if(! Db::name('order')->where(array('order_id' => $value['order_id']))->save(array('order_status' => 3)))
                        throw new \Exception("修改订单状态错误", 1);
                    //修改中拍用户保证金状态
                    if(! Db::name("AuctionSignUp")->where(array('auction_id' => $value['prom_id'], 'user_id' => $value['user_id'], 'pay_status' => 1))->save(array('state' => 2)))
                        throw new \Exception("修改保证金状态错误", 1);
                    // 提交事务
                    Db::commit();
                    return true;
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return $e->getMessage();
                }
            }
        }
        return true;
    }

    /**
     * [退回保证金]
     * @Auther 蒋峰
     * @DateTime
     */
    public function refundSignUp(){
        //微信支付
        include_once PLUGIN_PATH . "payment/weixin/weixin.class.php";

        $auction = Db::name('goods_auction')->alias('a')->join('auction_sign_up s', 'a.id=s.auction_id and s.frozen=0 and s.state=0 and s.pay_status=1')->where(array("a.is_end" => ['>', 0]))->field('s.*')
            ->select();
        if($auction)
        foreach ($auction as $value) {
            // 启动事务
            Db::startTrans();
            try{
                //退回保证金
                if($value["pay_code"] == "weixin"){
                    $payment_obj = new \weixin();
                    $refund_data = array('transaction_id' => $value['transaction_id'], 'total_fee' => $value['price'], 'refund_fee' => $value['price']);
                    $result = $payment_obj->payment_refund($refund_data);
                    if ($result['return_code'] == 'SUCCESS' ) {//&& $result['result_code' == 'SUCCESS']
                        //修改中拍用户保证金状态
                        if(! Db::name('auction_sign_up')->where(array('auction_id' => $value['auction_id'],'user_id' => $value['user_id'],'pay_status' => 1,'state' => 0, 'frozen' => 0))->save(array('state' => 1,'refund_time' => time())))
                            throw new \Exception("修改保证金状态错误", 1);
                    }else{
                        throw new \Exception("退款失败", 1);
                    }
                }
                // 提交事务
                Db::commit();
                return true;
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $e->getMessage();
            }
        }
        return true;
    }

    /**
     * [报名]
     * @Auther 蒋峰
     * @DateTime
     */
    public function auctionSignUp($data)
    {
        if($signUp = Db::name('AuctionSignUp')->where(array('auction_id' => $data['auction_id'], 'user_id' => $data['user_id']))->find()){
            return $signUp['order_sn'];
        }else{
            if(!@Db::name('AuctionSignUp')->insert($data))
                return false;
            return $data['order_sn'];
        }
    }

    /**
     * 获取订单 order_sn
     * @return string
     */
    public function get_order_sn($prefix = '')
    {
        $order_sn = null;
        // 保证不会有重复订单号存在
        while(true){
            $order_sn = $prefix.date('YmdHis').rand(1000,9999); // 订单编号
            $order_sn_count = Db::name('auction_sign_up')->where("order_sn = '$order_sn'")->count();
            if($order_sn_count == 0)
                break;
        }
        return $order_sn;
    }

}