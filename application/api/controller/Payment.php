<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace app\api\controller; 

class Payment extends Base{
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code

    public function index(){
	//file_put_contents(getcwd().'/pay.log',"测试\n\t",FILE_APPEND);
	//echo getcwd().'/pay.log';
//die;
    
     //echo 111;die;
     // return $this->getCode('20180426170848341771');
    }

    /**
     * 析构流函数
     * @Author   蒋峰
     * @DateTime 2018-04-26T19:47:39+0800
     * @param    [type]                   $type [支付方式 1 余额支付 2 app微信 3 app支付宝 4 公众号微信]
     */
    public function  __construct($type = 1) {   
        parent::__construct();                                                  
        switch ($type) {
            case 2:
                $this->pay_code = "appWeixinPay";
                break;
            case 3:
                $this->pay_code = "appAlipay";
                break;
            case 4:
                $this->pay_code = "weixin";
                break;
            default:
                $this->pay_code = I('pay_code');
                break;
        }

        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA']; 
        if(empty($this->pay_code))
            $this->errorMsg(2001, "type");
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php";
        $code = '\\'.$this->pay_code; 
        //实例化支付类
        $this->payment = new $code();

    }
   
    /**
     * 提交支付方式
     * @Author   蒋峰
     * @DateTime 2018-04-26T20:49:35+0800
     * @param    [type]                   $order_id        [订单id ]
     * @param    [type]                   $order_sn [多单付款的 主单号]
     * @return   [type]                                    [description]
     */
    public function getCode($order_sn = ''){

        C('TOKEN_ON',false); // 关闭 TOKEN_ON
        header("Content-type:text/html;charset=utf-8");
        // 修改订单的支付方式
        $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
        if($this->pay_code == 'balance')
            $payment_arr[$this->pay_code] = '余额支付';
        //判断订单类型
        if($order_sn)
        {
            if (stripos($order_sn, 'user') !== false) {//商品订单
                M('MemberOrder')->where("order_sn", $order_sn)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
                $order = M('MemberOrder')->where("order_sn", $order_sn)->find();
            }else if (stripos($order_sn, 'sign') !== false) {//保障金订单
                M('AuctionSignUp')->where("order_sn", $order_sn)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
                $order = M('AuctionSignUp')->where("order_sn", $order_sn)->find();
                $order['order_amount'] = $order['price'];
            }else if(stripos($order_sn, 'hj') !== false){
                $order_sn_post = M('employee_log')->where("order_sn", $order_sn)->find();
                M('employee_log')->where("order_sn", $order_sn)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
                $is_update_end_amount = M('order')->where(['order_id' => $order_sn_post['order_id']])->setInc('end_amount', $order_sn_post['order_amount']);
                if(!$is_update_end_amount) exit(json_encode(["code"=> 1, "message" => '订单错误']));
                $is_update_total_amount = M('order')->where(['order_id' => $order_sn_post['order_id']])->setInc('total_amount', $order_sn_post['order_amount']);
                if(!$is_update_total_amount) exit(json_encode(["code"=> 1, "message" => '订单错误']));
                $is_update_order_amount = M('order')->where(['order_id' => $order_sn_post['order_id']])->setInc('order_amount', $order_sn_post['order_amount']);
                if(!$is_update_order_amount) exit(json_encode(["code"=> 1, "message" => '订单错误']));
                $order = M('employee_log')->where("order_sn", $order_sn_post['order_sn'])->find();

            }else{//会员订单
                M('order')->where("order_sn", $order_sn)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
                $order = M('order')->where("order_sn", $order_sn)->find();
            }

                   
            if(!$order) $this->errorMsg(2002,"order_sn");
            // 如果是主订单号过来的, 说明可能是合并付款的

            // order_amount
            if($order['pay_status'] == 1){
                $this->errorMsg(1477,"此订单，已完成支付");
            }
            // tpshop 订单支付提交
            $pay_radio = "pay_radio=" . $this->pay_code;
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数

             //微信JS支付
            if($this->pay_code == 'weixin' && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
                $code_str = $this->payment->getJSAPI($order,$config_value);
            }
            else
            {
                if(!$order['body']) $order['body'] = $order['order_sn'];
                if(!$order['subject']) $order['subject'] = '众泰汽车';
                $code_str = $this->payment->get_code($order,$config_value);
            } 
            //微信JS支付
            // $code_str = $this->payment->getJSAPI($order,$config_value);
            
            exit($code_str);
        }else{
            exit(json_encode(["code"=> 1, "message" => '请传入订单号']));
        }

    }


    public function getPay(){
        C('TOKEN_ON',false); // 关闭 TOKEN_ON
        header("Content-type:text/html;charset=utf-8");
        $order_id = I('order_id/d'); // 订单id
        // 修改充值订单的支付方式
        $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
        M('recharge')->where("order_id", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
        $order = M('recharge')->where("order_id", $order_id)->find();
        if($order['pay_status'] == 1){
            $this->error('此充值订单，已完成支付!');
        } 
        $pay_radio = $_REQUEST['pay_radio'];
        $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        $order['order_amount'] = $order['account'];
        //微信JS支付
        if($this->pay_code == 'weixin' && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $code_str = $this->payment->getJSAPI($order,$config_value);
            exit($code_str);
        }else{
            $code_str = $this->payment->get_code($order,$config_value);
        }
        $this->assign('code_str', $code_str);
        $this->assign('order_id', $order_id);
        return $this->fetch('recharge'); //分跳转 和不 跳转
    }
    
    // 服务器点对点 // http://www.tp-shop.cn/index.php/Home/Payment/notifyUrl        
    public function notifyUrl(){            
         $this->payment->response();            
         exit();
    }
    
    // 页面跳转 // http://www.tp-shop.cn/index.php/Home/Payment/returnUrl        
    public function returnUrl(){
         $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';            
         if(stripos($result['order_sn'],'recharge') !== false)
         {
            $order = M('recharge')->where("order_sn", $result['order_sn'])->find();
            $this->assign('order', $order);
            if($result['status'] == 1)
                return $this->fetch('recharge_success');//充值成功
            else
                return $this->fetch('recharge_error');
            exit();
         }
         // 先查看一下 是不是 合并支付的主订单号
         $sum_order_amount = M('order')->where("master_order_sn", $result['order_sn'])->sum('order_amount');
         if($sum_order_amount)
         {
             $this->assign('master_order_sn', $result['order_sn']); // 主订单号
             $this->assign('sum_order_amount', $sum_order_amount); // 所有订单应付金额
             }
         else
         {
             $order = M('order')->where("order_sn", $result['order_sn'])->find();
             $this->assign('order', $order);
         }
               
         if($result['status'] == 1)
             return $this->fetch('success');   
         else
             return $this->fetch('error');   
    }

    public function notifyBack(){
        $this->payment->transfer_response();
        exit();
    }
}
