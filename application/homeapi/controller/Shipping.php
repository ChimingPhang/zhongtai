<?php

/**
 * 运费信息
 */
namespace app\api\controller;
use app\api\model\ShipPrice;
use app\api\model\Shipping as Ship;
use app\api\model\Snoopy;
use app\common\logic\Pay;
use think\Request;
use think\Model;

class Shipping extends Base{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 查看该地区的运费
     * @Autoh: 胡宝强
     * Date: 2018/7/20 9:26
     */
    public function index(){
        $goods_id = I('goods_id/d');//143
        $region_id = I('region_id/d');//28242
        $goods = M('goods')->where(['goods_id'=>$goods_id])->find();
        $freightLogic = new ShipPrice();
        $freightLogic->setGoodsModel($goods);
        $freightLogic->setRegionId($region_id);
        $freightLogic->setGoodsNum(1);
        $isShipping = $freightLogic->checkShipping();
        if($isShipping){
            $freightLogic->doCalculation();
            $freight = $freightLogic->getFreight();
            $data['freight'] = $freight;
            $this->json('0000','该地区可配送',$data);
        }else{
            $this->json('9999','该地区不支持配送',array());
        }
    }

    /**
     * 获取运费
     * @Autoh: 胡宝强
     * Date: 2018/7/24 16:37
     * @throws \app\common\util\TpshopException
     */
   public function shipping_price(){
       $address_id  = I('address_id');  //地址id
       $goods_id = I('goods_id');       //购买的商品id
       $user_id = I('user_id');
       $car_lists = M('cart')->where(['user_id'=>$user_id,'selected'=>1])->select();
       $pay = new Pay();
       $address = M('user_address')->where(['address_id'=>$address_id])->find();
       $price = $pay->delivery_car($address['district'],$goods_id,$car_lists);
       dump($price[0]);
   }

    /**
     * 获取快递鸟快递信息的物流
     * @Autoh: 胡宝强
     * Date: 2018/7/25 21:03
     */
    public function kuaidiniao_detail(){
        $modle = new Ship();
        $requestData= "{'OrderCode':'','ShipperCode':'YTO','LogisticCode':'800773192943701131'}";

        $datas = array(
            'EBusinessID' => C('kuaidi.EBusinessID'),
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = $modle->encrypt($requestData, C('kuaidi.AppKey'));
        $result=$modle->sendPost(C('kuaidi.ReqURL'), $datas);
        //根据公司业务处理返回的信息......
        return $result;
    }

    /**
     * 快递100 的快递信息查询
     * @Autoh: 胡宝强
     * Date: 2018/7/27 14:45
     */
    public function kuaidi_detail(){
        $typeCom = trim(I('com'));//快递公司
        $typeNu = trim(I('nu'));//快递单号
        if(empty($typeCom)) return $this->errorMsg('2001','com');
        if(empty($typeNu)) return $this->errorMsg('2001','nu');
        $url = "http://www.kuaidi100.com/query?type=".$typeCom."&postid=".$typeNu;
       // https://m.kuaidi100.com/index_all.html?type=quanfengkuaidi&postid=123456

        //        $AppKey='CTANbQFl6569';//请将XXXXXX替换成您在http://kuaidi100.com/app/reg.html申请到的KEY
        //        $url ='http://api.kuaidi100.com/api?id='.$AppKey.'&com='.$typeCom.'&nu='.$typeNu.'&show=0&muti=1&order=asc';
        //
        ////请勿删除变量$powered 的信息，否者本站将不再为你提供快递接口服务。
        //        $powered = '查询数据由：<a href="http://kuaidi100.com" target="_blank">KuaiDi100.Com （快递100）</a> 网站提供 ';
        ////优先使用curl模式发送数据
        if (function_exists('curl_init') == 1){
            $curl = curl_init();
            curl_setopt ($curl, CURLOPT_URL, $url);
            curl_setopt ($curl, CURLOPT_HEADER,0);
            curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
            curl_setopt ($curl, CURLOPT_TIMEOUT,5);
            $get_content = curl_exec($curl);
            curl_close ($curl);
        }else{
            $snoopy = new Snoopy();
            $snoopy->referer = 'http://www.baidu.com/';//伪装来源
            $snoopy->fetch($url);
            $get_content = $snoopy->results;
        }
        echo $get_content;
        exit();
    }

    /**
     * 物流接口
     * @Autoh: 胡宝强
     * Date: 2018/8/2 12:42
     */
    public function wuliu($shipping_code,$invoice_no){
        if(empty($shipping_code)) return $this->errorMsg('2001','shipping_code');
        if(empty($invoice_no)) return $this->errorMsg('2001','invoice_no');
        $url = "http://www.kuaidi100.com/query?type=".$shipping_code."&postid=".$invoice_no;
        if (function_exists('curl_init') == 1){
            $curl = curl_init();
            curl_setopt ($curl, CURLOPT_URL, $url);
            curl_setopt ($curl, CURLOPT_HEADER,0);
            curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($curl, CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
            curl_setopt ($curl, CURLOPT_TIMEOUT,5);
            $get_content = curl_exec($curl);
            curl_close ($curl);
        }else{
            $snoopy = new Snoopy();
            $snoopy->referer = 'http://www.baidu.com/';//伪装来源
            $snoopy->fetch($url);
            $get_content = $snoopy->results;
        }
        $data = json_decode($get_content);
        $data = $this->object_to_array($data);

        switch($data['state']){
            case 0:
                $state = '在途，即货物处于运输过程中';
                break;
            case 1:
                $state = '揽件，货物已由快递公司揽收并且产生了第一条跟踪信息';
                break;
            case 2:
                $state = '疑难，货物寄送过程出了问题';
                break;
            case 3:
                $state = '签收，收件人已签收';
                break;
            case 4:
                $state = '退签，即货物由于用户拒签、超区等原因退回，而且发件人已经签收';
                break;
            case 5:
                $state = '派件，即快递正在进行同城派件';
                break;
            case 6:
                $state = '退回，货物正处于退回发件人的途中';
                break;
        }

        $shipping_name = M('shipping')->where(['shipping_code'=>$data['com']])->field('shipping_name')->find();
        $data['shipping_name'] = $shipping_name['shipping_name'];
        $data['shipping_state'] = $state;
        $mobile = M('config')->where(['name'=>'phone'])->getField('value');
        $data['mobile'] = $mobile;
        echo json_encode($data);
//        echo $get_content;
        exit;
    }

    /**
     * 对象转成数组
     * @Autoh: 胡宝强
     * Date: 2018/8/2 17:22
     * @param $obj
     * @return mixed
     */
    public function object_to_array($obj){
        $_arr = is_object($obj) ? get_object_vars($obj) :$obj;
        foreach ($_arr as $key=>$val){
            $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val):$val;
            $arr[$key] = $val;
        }
        return $arr;
    }


    /**
     * 个人中心显示物流信息
     * @Autoh: 胡宝强
     * Date: 2018/8/2 10:58
     */
    public function show_kuaidi(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $order_id = I('order_id'); //主订单id
        if(empty($order_id) || !is_numeric($order_id)) $this->errorMsg('2001','order_id');
        $rec_id = I('rec_id'); //子订单id
        if(empty($rec_id) || !is_numeric($rec_id)) $this->errorMsg('2001','rec_id');
        $token = I('token');
        $user_id = $this->checkToken($token);
        $order = M('order')->where(['order_id'=>$order_id,'user_id'=>$user_id])->find();
        if(empty($order)) $this->errorMsg('3001');
        $order_goods = M('order_goods')->where(['order_id'=>$order_id,'rec_id'=>$rec_id])->find();
        if(empty($order_goods)) $this->errorMsg('3001');
        $delivery_doc = M('delivery_doc')->where(['id'=>$order_goods['delivery_id']])->field('shipping_code,invoice_no')->find();
        $this->wuliu($delivery_doc['shipping_code'],$delivery_doc['invoice_no']);
    }

}
