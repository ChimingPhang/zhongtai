<?php

/**
 * 订单支付
 * 
 */
namespace app\api\controller;
use think\Request;
use think\Db;

class Pay extends Base {

    /**
     * 支付接口
     * @Author   蒋峰
     * @DateTime 2018-04-27T18:04:04+0800
     * @param    integer                  $type     [description]
     * @param    string                   $order_sn [description]
     * @return   [type]                             [description]
     */
    public function index($type = 0, $order_sn = '')
    {
        $user_id = $this->checkToken(I('token'));  //用户id
        if(empty($type))
            $type = I('type',0,intval); //1 余额支付 2 app微信 3 app支付宝 4 公众号微信
        if(empty($order_sn))
            $order_sn = I('order_sn',''); //订单号

        if(empty($order_sn))
            return $this->errorMsg(2001,'order_sn');

        if(empty($type))
            return $this->errorMsg(2001,'type');
        //余额支付
        if($type == 1){
            $this->errorMsg(9999);
            //判断密码
            $pay_password = I("pay_password", '');
            $this->check_pay_password($user_id, $pay_password);

            if (stripos($order_sn, 'user') !== false) {//商品订单
                $table = 'MemberOrder';
            }else if(stripos($order_sn, 'hj') !== false){
                $table = 'employee_log';

            }else{//会员订单
                $table = 'Order';
            }
            $order = M("{$table}")->where("order_sn", $order_sn)->find();

            //判断订单是否已支付
            if($order['pay_status'] == 1)
                return $this->json(2, '此订单，已完成支付!');
            //判断余额
            $this->check_user_money($user_id, $order['order_amount']);
            // 启动事务
            Db::startTrans();
            try{

                $result = M("{$table}")->where(["order_sn"=>$order_sn])->data(array('pay_code'=>'yue_pay','pay_name'=> '余额支付','user_money' => $order['order_amount']))->update();
                if(!$result)                    
                    throw new \Exception($order_sn, 1);
                if($order['order_amount'] > 0){
                    $result = Db::table('tp_users')->where('user_id', $user_id)->setDec("user_money",$order['order_amount']);
                    if(!$result)
                        throw new \Exception("支付失败", 1);
                }
                $result = update_pay_status($order_sn);
                if(!$result)
                    throw new \Exception("更改订单状态失败", 1);
                D("Account")->add_money_log($user_id,$order['order_id'],1,$order['order_amount'],3);
                // 提交事务
                Db::commit();    
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->json(1,$e->getMessage());
            }
            $this->json(0,'支付成功'," ");
        }else{
            $payment = new Payment($type);
            $payment->getCode($order_sn);
        }

        // 启动事务
        // Db::startTrans();
        // try{
            // if($type == 1)
            //     $pay_result = $this->wx_pay();
            // else if($type == 2)
            //     $pay_result = $this->app_alipay();
            // else if($type == 3)
            //     $pay_result = $this->wx_pay();
            // else if($type == 4)
            //     $pay_result = $this->wx_pay();
        //     if(!$pay_result)
        //         throw new \Exception("支付失败或取消订单", 1);

        //     // Db::table('users');
        //     // Db::table('think_user')->find(1);
        //     // Db::table('think_user')->delete(1);
        //     // 提交事务
        //     Db::commit();    
        // } catch (\Exception $e) {
        //     // 回滚事务
        //     Db::rollback();
        //     dump($e->getMessage());

        // }
    
    }
    public function employee_pay()
    {
        $order_sn = $this->order_sn_employee(); //拼接订单号 为hj
        $user_id = $this->login();  //用户id
        $type = I('type',1,intval); //1 余额支付 2 app支付宝 3 app微信 4 公众号微信
        $pay_password = I('pay_password',1); //余额支付密码
        $order_sn_post = request()->post('order_sn'); //order_id
        if(empty($order_sn))
            $this->json(1,'参数错误，订单号不能为空', ' ');
        if(empty($type))
            $this->json(1,'参数错误，支付类型不能为空', ' ');
        $order_array = M('order_goods')->where(['order_id' => $order_sn_post])->select();
        $compen_price = 0;
        foreach ($order_array as $key => $value) {
            $compen_price += $value['compen_price'];
        }
        if($type == 1){
            $this->check_user_money($user_id,$compen_price);
            $this->check_pay_password($user_id,$pay_password);
        }


        // 启动事务
        Db::startTrans();
        try{
            $orderinfo = M('employee_log')->where(['order_id' => $order_sn_post,'pay_type' => 0])->find();
            // 提交事务
            Db::commit();
            $this->index($type,$orderinfo['order_sn']);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this->json(1,$e->getMessage(),'');
        }
    }
    /**
     * 购买会员生成订单
     * [user_pay description]
     * @Author   胡宝强
     * @DateTime 2018-04-27T16:21:25+0800
     * @param    string                   $value [description]
     * @return   [type]                          [description]
     */
    public function user_pay()
    {
        $order_sn = $this->order_sn();
        $user_id = $this->login();  //用户id
        $member_id = I('id',1,intval); //购买的会员类型id
        $type = I('type',1,intval); //1 余额支付 2 app支付宝 3 app微信 4 公众号微信
        $pay_password = I('pay_password',1); //余额支付密码
        $member = M('member')->where(['id'=>$member_id])->field('price,member_time')->find();
        if(!$member)
            return $this->json(1,'没有此会员类型',' ');
        if(empty($order_sn))
            return $this->json(1,'参数错误，订单号不能为空', ' ');
        if(empty($type))
            return $this->json(1,'参数错误，支付类型不能为空', ' ');
        $data['pay_status'] = 0;
        $data['order_sn'] = $order_sn;
        $data['user_id'] = $user_id;
        $data['order_amount'] = $member['price'];
        $data['member_id'] = $member_id;
        $data['create_time'] = time();
        $data['member_time'] = $member['member_time'];
        if($type == 1){
            $this->check_user_money($user_id,$member['price']);
            $this->check_pay_password($user_id,$pay_password);
            // $data['user_money'] = $member['price'];
        }
        $arr = M('member_order')->add($data);
        if($arr){
        	$this->index($type,$data['order_sn']);
        }else{
            $this->json(1,'支付失败',' ');
        }

    }

    /**
     * 检查余额是否可以使用
     * [check_user_money description]
     * @Author   胡宝强
     * @DateTime 2018-04-27T16:23:53+0800
     * @param    [type]                   $user_id [用户id]
     * @param    [type]                   $money   [用户要支付的金额]
     * @return   [type]                            [description]
     */
    public function check_user_money($user_id,$money)
    {
    	$user_money = M('users')->where(['user_id'=>$user_id])->getField('user_money');
    	if($user_money < $money){
    		$this->json(1,'余额不足', ' ');
    	}else{
    		return true;
    	}
    }

    /**
     * 检查支付密码是否正确
     * [check_pay_password description]
     * @Author   胡宝强
     * @DateTime 2018-04-27T16:30:48+0800
     * @param    [type]                   $user_id [用户id]
     * @param    [type]                   $money   [用户支付密码]
     * @return   [type]                            [description]
     */
    public function check_pay_password($user_id,$password)
    {
    	// $password = encrypt($password);
    	$paypwd = M('users')->where(['user_id'=>$user_id])->getField('paypwd');

    	if($paypwd != encrypt($password)){
    		$this->json(14,'支付密码不正确', ' ');
    	}else{
    		return true;
    	}
    }


    /**
     * 手机端微信支付
     * @Author   郝钱磊
     * @DateTime 2018-04-13 T10:58:10+0800
     * @return   [type]                   [description]
     */
    public function app_wx(){
        /* 修改支付金额 标价金额  total_fee   是   Int 88  订单总金额，单位为分，详见支付金额 */
        $order_sn = I('order_sn','');// 多单付款的 主单号
        $order = M('order')->where("order_sn" , $order_sn)->find(); 
        $money = $order['order_amount']*100;
        $nonce_str = $this->rand_code();        
        $data['appid'] ='wxae1de524e4b78249';   
        $data['mch_id'] = '1501781801' ;        
        $data['body'] = '商品购买';
        $data['spbill_create_ip'] = '123.12.12.123';   
        $data['out_trade_no'] = $order_sn;    
        $data['nonce_str'] = $nonce_str;                   
        $data['notify_url'] = 'http://api.maxiansheng.com/Pay/wx_notify';
        $data['trade_type'] = 'APP';      //支付方式
        // $data['total_fee'] = 1;                         //金额 微信默认单位是分
        $data['total_fee'] = $order['order_amount'];                         //金额 微信默认单位是分
        $data['sign'] = $this->getSign($data);        
        $xml = $this->ToXml($data);            
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //header("Content-type:text/xml");
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $url);
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }    else    {
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        }
        //设置header
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        //传输文件
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        // return $data;
        //返回结果
        if($data){
            curl_close($ch);
            $re = $this->FromXml($data);
            if($re['return_code'] != 'SUCCESS'){
               $this->json(1,'签名失败',$datas);
            }else{
                $arr =array(
                    'prepayid' =>$re['prepay_id'],
                    'appid' => $re['appid'],
                    'partnerid' => $re['mch_id'],
                    'package' => 'Sign=WXPay',
                    'noncestr' => $re['nonce_str'],
                    'timestamp' =>time(),
                );
                $sign = $this->getSign($arr);
                $arr['sign'] = $sign;
                $this->json(0,'签名成功',$arr);
            }
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            $this->json(1,'curl错误',$error);
        }
      
    }

    /**
     * 微信支付回调
     * [wx_notify description]
     * @Author   胡宝强
     * @DateTime 2018-04-25T09:42:47+0800
     * @return   [type]                   [description]
     */
    public function wx_notify(){
       $xmlData = file_get_contents('php://input');
       $data = $this->FromXml($xmlData);
       $file = fopen('./notify.txt', 'a+');
       fwrite($file,var_export($data,true));
       $sign = $data['sign'];
       unset($data['sign']);
       if($sign == $this->getSign($data)){
            if ($data['result_code'] == 'SUCCESS') {
                $where['order_sn'] = $data['out_trade_no'];//商户订单号
                $datas['status'] = 1;   
                $datas['transaction_id'] = $data['transaction_id'];//微信支付订单号
                $re = M('order')->where($where)->update($datas);
                if($re){
                    echo '<xml>
                    <return_code><![CDATA[SUCCESS]]></return_code>
                    <return_msg><![CDATA[OK]]></return_msg>
                    </xml>';exit();
                }
            }else{//支付失败，输出错误信息
                $file = fopen('./notify.txt', 'a+');
                fwrite($file,"错误信息：".$data['return_msg'].date("Y-m-d H:i:s"),time()."\r\n");    
            }
        }else{
            $file = fopen('./notify.txt', 'a+');
            fwrite($file,"错误信息：签名验证失败".date("Y-m-d H:i:s"),time()."\r\n");    
        }
    }

          //传输给微信的参数要组装成xml格式发送,传如参数数组!
    public function ToXml($data=array())
    {
        if(!is_array($data) || count($data) <= 0)
        {
           return '数组异常';
        }

        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    
    //生成随机字符串
    function rand_code(){
        $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';//62个字符
        $str = str_shuffle($str);
        $str = substr($str,0,32);
        return  $str;
    }

    //生成签名
    private function getSign($params) {
        ksort($params);        
        foreach ($params as $key => $item) {
            if (!empty($item)) {         
                $newArr[] = $key.'='.$item;     
            }
        }
        $stringA = implode("&", $newArr);         
        $stringSignTemp = $stringA."&key="."gccx1234561111111111111111111111";    
        // key是在商户平台API安全里自己设置的
        $stringSignTemp = MD5($stringSignTemp);       
        $sign = strtoupper($stringSignTemp);      
        return $sign;
    }

    //将xml数据转换为数组,接收微信返回数据时用到.
    public function FromXml($xml)
    {
        if(!$xml){
            echo "xml数据异常！";
        }
        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }



    /**
     * 手机端支付宝支付
     * [app_alipay description]
     * @Author   胡宝强
     * @DateTime 2018-04-23T15:03:07+0800
     * @param    string                   $value [description]
     * @return   [type]                          [description]
     */
    public function app_alipay($value='')
    {
        //实例化alipay类
        $ali = new Alipay();
        //回调地址
        $url = 'config("alipay.notify_url")';
        //调用方法
        $array = $ali->alipay($card['name'], $money, $order_number, $url);
        if($array){
            $arr['order_number'] = $array;
            $this->json(0,getMsg(0),$arr);
        }else{
            $this->json(1,'参数错误',$brr);
        }
    }

     /**
     * 支付宝支付回调
     * @author 白志杰 2018/02/06
     */
    public function notify_url(){
        vendor('Alipay.aop.AopClient');
        $order = new Orders;
        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0eWUrdw47/nSssgWZdLmBYxd/XSPGSiEA412LLS5/qq/7ZFKL0EF7fR/iHXsQa4NUypFOPPGjRNwDSSoOw/covkE39Hcu4arRGGRCV5OVcd1LC7hVHEi0H6UBtS0z4IM7BA/9Yo89FttAWJAMHaT4M1eu4grf6RG35SBtCmSLsl/lXC3O0CKpgPRVs8N/L0n3aXIkWTSlIeZT46LQHWLy/Z2QiQh0SZwTy4tGsoAKq26ZM71dtLdSBjaWKPMnSJDQcj+kczEBI58r37pGQJXAhYkn9RrkjbBSwbr+UwqwWM52kulynLmXm8nyFH79Rc3F+byC64Qto9X2VwJsX01vQIDAQAB';
        $flag = $aop->rsaCheckV1($_POST, NULL, "RSA2");
        if($flag){
            if($_POST['trade_status'] == 'TRADE_SUCCESS'){
                $where['serial_number'] = $_POST['out_trade_no'];
                $datas['state'] = 1;   
                $datas['trade_no'] = $_POST['trade_no'];        
                $res = Db::table('share_car_month_card_record')->where($where)->update($datas);
                //支付成功-增加公里数
                $result = $order->get_k_insuser($_POST['out_trade_no']);
                if($res && $result){
                    return success;
                }
            }
        }
        // vendor('Alipay.aop.AopClient');//线上
        // Loader::import('Alipay\aop\AopClient', EXTEND_PATH);//线下
    }


    /**
     * 微信公众号支付
     * [wx_pay description]
     * @Author   胡宝强
     * @DateTime 2018-04-23T14:40:28+0800
     * @param    string                   $value [description]
     * @return   [type]                          [description]
     */
    public function wx_pay()
    {
        $order_sn = I('order_sn','');// 多单付款的 主单号
        include_once  "plugins/payment/weixin/weixin.class.php"; // D:\wamp\www\svn_tpshop\www\plugins\payment\alipay\alipayPayment.class.php
        //$code = '\weixn'; // \alipay
        $code = '\\weixin'; 
        $payment = new $code();

        header("Content-type:text/html;charset=utf-8");    
        // 修改订单的支付方式
        $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");            
       
        $pay_radio = 'pay_code=weixin';
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数        
        M('order')->where("order_sn" , $order_sn)->save(array('pay_code'=>'weixin','pay_name'=>'微信公众号支付'));
        $order = M('order')->where("order_sn" , $order_sn)->find();     
        
        if($order['pay_status'] == 1){
            $this->json(1,'订单已完成支付');
        }
        $code_str = $payment->getJSAPI($order,$config_value);
    }

    // 服务器点对点 // http://www.tp-shop.cn/index.php/Home/Payment/notifyUrl
    public function notifyUrl(){
        $pay_code = I('pay_code','weixin');
        switch ($pay_code) {
            case 'alipayMobile':
                include_once  "plugins/payment/alipayMobile/alipayMobile.class.php";
                break;
            case 'appWeixinPay':
                include_once  "plugins/payment/appWeixinPay/appWeixinPay.class.php";
                break;
            case 'weixin':
                include_once  "plugins/payment/weixin/weixin.class.php";
                break;
            default:
                include_once  "plugins/payment/weixin/weixin.class.php";
                break;
        }
        // $code = '\\weixin'; 
        $payment = new $pay_code();
        $payment->response();
    }

    /**
     * 支付成功后跳转的页面
     * [go_url description]
     * @Author   胡宝强
     * @DateTime 2018-04-24T20:45:42+0800
     * @return   [type]                   [description]
     */
    public function go_url()
    {
        $id = I('id',1,intval);
        $data['id'] = $id;
        $this->json(0,'支付成功',$data);
    }

    /**
     * 支付失败或取消支付后跳转的页面
     * [back_url description]
     * @Author   胡宝强
     * @DateTime 2018-04-24T20:46:36+0800
     * @return   [type]                   [description]
     */
    public function back_url()
    {
        $id = I('id',1,intval);
        $data['id'] = $id;
        $this->json(0,'支付失败',$data);
    }

    public function order_sn()
    {
        //生成24位唯一订单号码，格式：YYYY-MMDD-HHII-SS-NNNN,NNNN-CC，其中：YYYY=年份，MM=月份，DD=日期，HH=24格式小时，II=分，SS=秒，NNNNNNNN=随机数，CC=检查码
        @date_default_timezone_set("PRC");
        while (true) {
            //订购日期
            $order_date = date('Y-m-d');
            //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
            $order_id_main = date('YmdHis') . rand(10000000, 99999999);
            //订单号码主体长度
            $order_id_len = strlen($order_id_main);

            $order_id_sum = 0;

            for ($i = 0; $i < $order_id_len; $i++) {

                $order_id_sum += (int)(substr($order_id_main, $i, 1));

            }
            //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
            $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);

            return 'user'.$order_id;
        }
    }
    public function order_sn_employee()
    {
        //生成24位唯一订单号码，格式：YYYY-MMDD-HHII-SS-NNNN,NNNN-CC，其中：YYYY=年份，MM=月份，DD=日期，HH=24格式小时，II=分，SS=秒，NNNNNNNN=随机数，CC=检查码
        @date_default_timezone_set("PRC");
        while (true) {
            //订购日期
            $order_date = date('Y-m-d');
            //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
            $order_id_main = date('YmdHis') . rand(10000000, 99999999);
            //订单号码主体长度
            $order_id_len = strlen($order_id_main);

            $order_id_sum = 0;

            for ($i = 0; $i < $order_id_len; $i++) {

                $order_id_sum += (int)(substr($order_id_main, $i, 1));

            }
            //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
            $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);

            return 'hj'.$order_id;
        }
    }

}
