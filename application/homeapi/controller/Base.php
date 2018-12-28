<?php
namespace app\homeapi\controller;
use app\api\model\UserSignLog;
use think\Controller;
use think\Cookie;
use think\Hook;
use think\Session;
use think\Db;

class Base extends Controller {

    protected static $redis;
    protected $userInfo;

    public $token;
    public $is_login = 0;       //是否登录
    public $is_sign = 0;        //是否签到
    /**
     * [返回报错信息]
     * @Auther 蒋峰
     * @DateTime
     * @param $code
     * @return mixed
     */
    protected function errorMsg($code, $field = ''){
        /**
         * @var array 异常返回参数
         * 0000 操作成功
         */
        $errorMsg = [
            1001 => "签名验证未通过", //签名验证错误非法请求接口
            1002 => "签名过期", //请求过期 访抓包,
            1004 => "未登录",
            1005 => "登录超时，请重新登录",
            1006 => "请求方式错误",
            1477 => 0, //异常提示信息  自定义提示信息
            2001 => "缺少必须参数" . $field,
            2002 => $field . "参数错误",
            4001 => "该订单不存在或未完成",//进行订单完成后的操作
            4002 => "订单重复提交",
            4003 => "非法请求",
            4010 => "验证码失效",
            4011 => "验证码不匹配",
            8910 => "没有数据", //数据为空时返回
            9999 => "系统错误",
            3000 => "该订单已退款，不能重复提交",
            3001 => "订单不存在",
            3002 => "订单已取消",
            3003 => "订单已支付过",
            3004 => "订单已完成",
            3005 => "订单未支付",
            3006 => "订单不是纯积分订单",
            3007 => "商品活动已结束",
            3008 => " "
        ];
        if(!$errorMsg[$code]) $errorMsg[$code] = $field;

        exit( $this->json($code, $errorMsg[$code]) );
    }

    /**
     * Base constructor.
     * @param int $is_show 是否发送给view
     */
    public function __construct($is_show = 1)
    {
        parent::__construct();
        self::redisConnect();
        Hook::listen('app_begin_origin');

        $this->token = I('token')? I('token') : session('token');
        if (!empty($this->token)) {
            $user_id = $this->checkToken($this->token);
            if ($user_id) {
                $this->is_login = 1;
                $this->is_sign = (new UserSignLog())->isSign($user_id);
            }
        }
        if ($is_show) {
            $this->assign('is_login', $this->is_login);
            $this->assign('is_sign', $this->is_sign);
        }
    }

    /**
     * [判断商品是否收藏过]
     * @Auther 蒋峰
     * @DateTime
     * @param $token
     * @param $goods_id
     * @return int
     */
    protected function userGoodsInfo($token,$goods_id)
    {
        if(empty($token) || !is_string($token)) return 0;
        $user_id = M('users')->where(array('token' => input('token')))->getField('user_id');
        $count = M('collection')->where(array('user_id' => $user_id, 'goods_id' => $goods_id,'deleted'=>0))->count();
        if($count) return 1;
        return 0;
    }

    /*
     * 初始化操作
     */
    public function _initialize() {
        Hook::listen('app_begin_origin');
    }

    /**
     * [链接redis]
     * @Auther 蒋峰
     * @DateTime
     */
    protected static function redisConnect(){
        $redis_congif = C('redis');
//        self::$redis = new \Redis();
//        self::$redis->connect($redis_congif['host'], $redis_congif['port']);
//        self::$redis->auth($redis_congif['password']);
    }
      /**
   * [login 是否登录]
   *
   * @Author 温海军    2018-05-25
   *
   * @return [type] [description]
   */
  protected function checkToken($token){
      if(empty($token)) return $this->errorMsg(1004);
      $this->userInfo =  M("users")->where(["token"=>$token])->field("user_id,email,sex,user_money,points,mobile,head_pic,nickname")->find();
      if(!$this->userInfo) return $this->errorMsg(1005);
      if(!$this->userInfo['head_pic']) $this->userInfo['head_pic'] = '';
      return (int)$this->userInfo['user_id'];
  }
  /**
   * [DeleteHtml 去除html标签]
   *
   * @Author 温海军    2018-06-19
   *
   * @param  [type] $str       [description]
   */
  protected function DeleteHtml($str)
  {
    $str = trim($str); //清除字符串两边的空格
      $str = strip_tags($str,""); //利用php自带的函数清除html格式
      $str = preg_replace("/\t/","",$str); //使用正则表达式替换内容，如：空格，换行，并将替换为空。
      $str = preg_replace("/\r\n/","",$str);
      $str = preg_replace("/\r/","",$str);
      $str = preg_replace("/\n/","",$str);
      $str = preg_replace("/ /","",$str);
      $str = preg_replace("/&nbsp;/","",$str);
      $str = preg_replace("/  /","",$str);  //匹配html中的空格
      return trim($str); //返回字符串
  }

  /**
   * [is_email 检查邮箱]
   *
   * @Author 温海军     2018-05-25
   *
   * @param  [type]  $email     [邮箱]
   *
   * @return boolean            [description]
   */
  protected function is_email($email){
    if (empty($email)) {
            $res = array('code'=>14, 'msg'=>'email为空,请填写email', 'data'=>(object)array());
            return $this->json($res['code'],$res['msg'],$res['data']);
        }else{
            //定义正则表达式
             $checkmail="/\w+([-+.']\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/";
              if(!preg_match($checkmail,$email)){
                  $res = array('code'=>15, 'msg'=>'email格式不正确,请重新输入', 'data'=>(object)array());
                  return $this->json($res['code'],$res['msg'],$res['data']);
              }
        }
  }


  /**
   * [moblie 检查手机格式]
   *
   * @Author 温海军    2018-05-25
   *
   * @param  [type] $moblie    [手机号]
   *
   * @return [type]            [description]
   */
  protected function moblie($moblie){

    if (empty($moblie)) {
            $res = array('code'=>14, 'msg'=>'手机号为空,请填写手机号', 'data'=>(object)array());
            return $this->json($res['code'],$res['msg'],$res['data']);
        }else{
            //定义正则表达式
             $checkmobile="/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\\d{8}$/";
              if(!preg_match($checkmobile,$moblie)){
                  $res = array('code'=>15, 'msg'=>'手机号格式错误', 'data'=>(object)array());
                  return $this->json($res['code'],$res['msg'],$res['data']);
              }
        }
  }
  /**
   * [verify 验证码验证]
   *
   * @Author 温海军    2018-05-25
   *
   * @param  [type] $verify    [验证码]
   *
   * @return [type]            [description]
   */
  protected function verify($verify,$mobile){
    //var_dump($_COOKIE[$mobile]);die;
  if($verify=='')
    {
      $res = array('code'=>14, 'msg'=>'请输入验证码', 'data'=>(object)array());
      return $this->json($res['code'],$res['msg'],$res['data']);
    }
    if($mobile != null){
      if($verify!==$_COOKIE[$mobile]){

              $res = array('code'=>15, 'msg'=>'输入验证码错误', 'data'=>(object)array());
              return $this->json($res['code'],$res['msg'],$res['data']);
          }
    }else{
      if($verify!==$_COOKIE['email_code'] )
      {

        $res = array('code'=>15, 'msg'=>'输入验证码错误', 'data'=>$_COOKIE['email_code']);
        return $this->json($res['code'],$res['msg'],$res['data']);
      }

    }

  }
  /**
   * [statu 获取人员的身份]
   *
   * @Author 温海军    2018-06-13
   *
   * @return [type] [description]
   */
  protected function statu($token){
      if(empty($token)){
        $res = array('code'=>1, 'msg'=>'请输入token值', 'data'=>(object)array());
        return $this->json($res['code'],$res['msg'],$res['data']);
      }
      $statu = M("Umember")->where(['token'=>$token])->field("statu,cation")->find();
      $statu['cation'] = M("Classlist")->where(['id'=>$statu['cation']])->getField("title");

      return $statu;
  }

  /**
   * [arrayAnalytic 数组解析]
   *
   * @Author 温海军    2018-05-28
   *
   * @param  [type] $data      [数据库查询数组集]
   * @param  [type] $data_arr  [需要json显示的字段]
   *
   * @return [type]            [解析后的数组]
   */
  protected  function arrayAnalytic($data,$data_arr){

      $data_mer = explode(",",$data_arr);

      foreach ($data as $pl_key => $pl_val) {
              foreach ($data_mer as $dam_key => $dam_value) {
                  $plist_ar[$pl_key][$dam_value] = $pl_val[$dam_value];
              }
               }
      foreach ($plist_ar as $pll_key => $pll_val) {
                   $plist_arr[] = $pll_val;
               }
      return  $plist_arr;

  }
  /**
   * [pic_addr 根据图片id获取地址]
   *
   * @Author 温海军    2018-05-29
   *
   * @param  [type] $pic       [图片id]
   *
   * @return [type]            [图片地址]
   */
  protected function  pic_addr($pic){
    if($pic <0 || $pic==nill){
      return false;
    }else{
      $pic_addr = M("picture")->where(['id'=>$pic])->getField("path");
    }
    //dump(M()->_sql());die;
    return $pic_addr;
  }

  protected function UnixTime($time){
    $datetime = date('Y-m-d H:i:s',$time);
    return $datetime;
  }

  /**
   * [request 判断请求方式正确性]
   *
   * @Author 温海军    2018-06-22
   *
   * @param  [type] $request   [description]
   *
   * @return [type]            [description]
   */
  protected function request($request){
    //获取当前的请求方式
     $req =  $_SERVER['REQUEST_METHOD'];
       if(!strcasecmp($req,$request)==0){
         $res = array('code'=>10, 'msg'=>'请求方式错误,正确方式为'.strtoupper($request), 'data'=>(object)array());
         return $this->json($res['code'],$res['msg'],$res['data']);
       }
  }

  /**
     * 数据统一分装返回json
     * @param type $code
     * @param type $message
     * @param type $data
     */
  protected function json($code = "0", $message = "", $info = null, $count = "")
  {
    $data['code'] = "{$code}";
    $data['message'] = $message;
    if ($count) {
      $data['counts'] = $count;
    }
    if ($info || is_array($info)) {
      $data['data'] = $info;
    }else{
            $data['data'] = (object)array();
        }
    exit(json_encode($data, JSON_UNESCAPED_UNICODE));
  }
    /**
     * 发送手机号验证码
     * [sendCode description]
     * @Author   XD
     * @DateTime 2018-07-11T15:09:13+0800
     * @return   [type]                   [description]
     */
    public function sendCode($scene,$mobile){
      //后台是否开启手机号注册功能
      // $reg_sms_enable = tpCache('sms.forget_pwd_sms_enable1');
      //短信验证码时间
      // tpCache('sms.sms_time_out');
      // dump($reg_sms_enable);die;
      //随机一个验证码
      $code           = rand(1000, 999999);  
      $params['code'] = $code;
      return XdsendSms($scene,$mobile,$params);
    }
    /**
     * 验证手机号验证码
     * [checkCode description]
     * @Author   XD
     * @DateTime 2018-07-11T17:19:15+0800
     * @return   [type]                   [description]
     */
    public function checkCode($mobile,$code){
      if(!tpCache('sms.regis_sms_enable')) return true;
      
      $time     = tpCache('sms.sms_time_out')??60;
      $out_time = time()-$time;
      $mobileCode = M('sms_log')->where(['mobile'=>$mobile,'status'=>1])->order('add_time DESC')->field('code,add_time,id')->find();
      if(!$mobileCode) $this->throwError('手机号不存在');
      if($mobileCode['add_time']<$out_time) $this->throwError('验证码已超时,请重新获取');
      if($mobileCode['code']!=$code) $this->throwError('验证码不正确');
    }
    /**
     * 抛出异常处理
     * [throwError description]
     * @Author   XD
     * @DateTime 2018-07-11T16:34:34+0800
     * @return   [type]                   [description]
     */
    public function throwError($msg){
      exit($this->json(1,$msg));
    }
   /**
    * 根据广告位置获取广告位
    * [ad_position description]
    * @Author   XD
    * @DateTime 2018-07-12T14:14:28+0800
    * @param    string                   $idstring [description]
    * @param    string                   $filed    [description]
    * @param    string                   $order    [description]
    * @return   [type]                             [description]
    */
    public function ad_position(string $idstring,$filed='*',$order='orderby desc'){
      //$idstring严格以逗号分隔(1,2,3)
      if(!$idstring && is_array($idstring)) return ['status'=>2002,'msg'=>'参数错误'];
      /**********拍卖首页广告位start*********/
      switch ($idstring){
          case 11: //拍卖首页广告位
              if(!M('ad_position')->where('position_id', $idstring)->find()){
                  $data = [
                      'position_id' => 11,
                      'position_name' => '拍卖首页广告位',
                      'is_open' => 1,
                  ];
                  M('ad_position')->save($data);
              }
              break;
      }
      /***********拍卖首页广告位end************/

      $where['enabled'] = 1;
      $where['pid'] = ['in',$idstring];
      $res = M('ad')->where($where)->field($filed)->order($order)->select();
      if(!$res)  return ['status'=>8910,'msg'=>'没有数据'];
      else return ['status'=>0000,'msg'=>'成功','result'=>$res];
    }

    /**
     * 根据文章ID获取文章列表
     * [categoryList description]
     * @Author   XD
     * @DateTime 2018-07-13T11:38:13+0800
     * @param    string                   $idstring [description]
     * @param    string                   $filed    [description]
     * @return   [type]                             [description]
     */
    public function articleList(string $idstring,$filed='*'){
      //$idstring严格以逗号分隔(1,2,3)
      if(!$idstring && is_array($idstring)) return ['status'=>-1,'msg'=>'参数错误'];
      $where['is_open'] = 1;
      $where['id'] = ['in',$idstring];
      $res = M('article')->where($where)->filed($filed)->select();
      if($res)  return ['status'=>-1,'msg'=>'参数错误'];
      else return ['status'=>1,'msg'=>'成功','result'=>$res];
    }

    /**
     * 获取配置信息
     * @Autor: 胡宝强
     * Date: 2018/8/9 11:06
     * @param $key      键名
     */
    public function getConfig($key){
        return M('config')->where(['name'=>$key])->getField('value');
    }

    /**
     * [处理轮播列表]
     * @Auther 蒋峰
     * @DateTime
     * @param $data
     * @return array
     */
    protected function bannerImage($data)
    {
        if(empty($data)) return [];
        $data = explode(',', $data);
        $arr = [];
        foreach ($data as $value){
            $arr[] = ['ad_code' => $value];
        }
        return $arr;
    }
}