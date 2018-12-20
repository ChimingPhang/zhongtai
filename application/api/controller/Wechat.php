<?php
/**
 * 获取微信的信息
 */
namespace app\api\controller;
use app\common\logic\UsersLogic;
use app\common\logic\JssdkLogic;
use think\Request;
/**
 * wechat php test
 */
class Wechat extends Base {
     public $weixin_config;
  public function __construct()
    {
        parent::__construct();
    }

    public function _initialize()
    {
        //微信浏览器
        // dump($_SESSION['openid']);die;
        ////file_put_contents(getcwd().'/88.txt',print_r($_SERVER['HTTP_USER_AGENT'],true));
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            $this->weixin_config = M('wx_user')->find(); //获取微信配置
            if (is_array($this->weixin_config) && $this->weixin_config['wait_access'] == 1) {
                $wxuser = $this->getUserOpenId(); //授权获取openid以及微信用户信息
                //dump($wxuser);
               //$this->addUser($wxuser);
            }
        }
    }

    public function addUser($data = array())
    {
        $openid = $data['openid']; //第三方返回唯一标识
        $oauth = $data['oauth']; //来源
        $unionid = $data['unionid']; //$unionid
        if (!$openid || !$oauth) {
            return array('status' => -1, 'msg' => '参数有误', 'result' => '');
        }
        if(isset($data['unionid'])){
          $map['unionid'] = $data['unionid'];
          $user = get_user_info($data['unionid'],4,$oauth);
        }else{
          $user = get_user_info($openid,3,$oauth);
        }
        if(!$user){
            //账户不存在 注册一个
            $map['password'] = '';
            $map['openid'] = $openid;
            $map['nickname'] = $data['nickname'];
            $map['reg_time'] = time();
            $map['oauth'] = $oauth;
            $map['head_pic'] = $data['head_pic'];
            $map['sex'] = $data['sex'] === null ? 0 :  $data['sex'];
            $map['token'] = md5(time().mt_rand(1,99999));
            //$map['first_leader'] = cookie('first_leader'); // 推荐人id
            if($_GET['first_leader'])
                $map['first_leader'] = $_GET['first_leader']; // 微信授权登录返回时 get 带着参数的
            // 成为分销商条件
            //$distribut_condition = tpCache('distribut.condition');
            //if($distribut_condition == 0)  // 直接成为分销商, 每个人都可以做分销
            $map['is_distribut']  = 1;
            $row_id = M('users')->add($map);
            $user = M('users')->where(array('user_id'=>$row_id))->find();
        }else
        {
            // $user['token'] = md5(time().mt_rand(1,999999999));
            M('users')->where("user_id = '{$user['user_id']}'")->save(array('last_login'=>time(),'push_id'=>$map['push_id']));
        }
        return array('status'=>1,'msg'=>'登陆成功','result'=>$user);
    }

     //接入微信公众号验证
    public function index() {
        //获取参数 signature nonce token timestamp echostr
        $signature = $_GET['signature'];
        $nonce = $_GET['nonce'];
        $token = 'mxs123';
        $timestamp = $_GET['timestamp'];
        $echostr = $_GET['echostr'];
        //将nonce timestap token 形成数组 并字典排序
        $array = array($nonce, $timestamp, $token);
        sort($array);
        //拼接字符串  并进行sha1加密
        $str = implode('', $array);
        $shastr = sha1($str);
       
        //将加密后的字符串跟signature比对
        if ($shastr == $signature && $echostr) {
            //第一次接入微信  api接口的时候
            echo $echostr;
        } else {
            //关注回复
            $this->responseMsg();
        }
    }

    //关注后的推送消息
    public function responseMsg() {
        //1.获取到微信推送过来的post数据 (xml格式的)
       // $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
       $postArr = file_get_contents("php://input");
        if(!empty($postArr)){      
          //2.处理消息类型,并设置回复类型和内容
          $postObj = simplexml_load_string($postArr,'SimpleXMLElement',LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
          //判断数据包是否是订阅的事件
          if (strtolower($postObj->MsgType) == 'event') {
              //判读是否是订阅事件
              if (strtolower($postObj->Event) == 'subscribe') {
                  //推送关注信息
                  $toUser = $postObj->FromUserName;
                  $fromUser = $postObj->ToUserName;
                  $time = time();
                  $msgType = 'text';
                  $content = "您好,欢迎关注马鲜生";
                  $template = "<xml>
                                      <ToUserName><![CDATA[%s]]></ToUserName>
                                      <FromUserName><![CDATA[%s]]></FromUserName>
                                      <CreateTime>%s</CreateTime>
                                      <MsgType><![CDATA[%s]]></MsgType>
                                      <Content><![CDATA[%s]]></Content>
                                  </xml>";
                  $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                  echo $info;
              }

              if (strtolower($postObj->Event) == 'click') {
                  if (strtolower($postObj->EventKey) == 'health') {
                      $toUser = $postObj->FromUserName;
                      $fromUser = $postObj->ToUserName;
                      $time = time();
                      $msgType = 'text';
                      $content = "暂未开启";
                      $template = "<xml>
                                      <ToUserName><![CDATA[%s]]></ToUserName>
                                      <FromUserName><![CDATA[%s]]></FromUserName>
                                      <CreateTime>%s</CreateTime>
                                      <MsgType><![CDATA[%s]]></MsgType>
                                      <Content><![CDATA[%s]]></Content>
                                  </xml>";
                      $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                      echo $info;
                  }
              }
          }

            if($postObj->MsgType == 'event' && $postObj->Event == 'CLICK')
            {
                $keyword = trim($postObj->EventKey);
            }


            if(empty($keyword))
                exit("Input something...");

            // 图文回复
            $wx_img = M('wx_img')->where("keyword", "like", "%$keyword%")->find();
            if($wx_img)
            {
                $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <ArticleCount><![CDATA[%s]]></ArticleCount>
                                <Articles>
                                    <item>
                                        <Title><![CDATA[%s]]></Title>
                                        <Description><![CDATA[%s]]></Description>
                                        <PicUrl><![CDATA[%s]]></PicUrl>
                                        <Url><![CDATA[%s]]></Url>
                                    </item>
                                </Articles>
                                </xml>";
                $resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,'news','1',$wx_img['title'],$wx_img['desc']
                    , $wx_img['pic'], $wx_img['url']);
                exit($resultStr);
            }


            // 文本回复
            $wx_text = M('wx_text')->where("keyword", "like", "%$keyword%")->find();
            if($wx_text)
            {
                $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
                $contentStr = $wx_text['text'];
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
                exit($resultStr);
            }


            // 其他文本回复
            $textTpl = "<xml>
                                <ToUserName><![CDATA[%s]]></ToUserName>
                                <FromUserName><![CDATA[%s]]></FromUserName>
                                <CreateTime>%s</CreateTime>
                                <MsgType><![CDATA[%s]]></MsgType>
                                <Content><![CDATA[%s]]></Content>
                                <FuncFlag>0</FuncFlag>
                                </xml>";
            $contentStr = '欢迎来到马鲜生!';
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, 'text', $contentStr);
            exit($resultStr);

        }else{
            //file_put_contents(getcwd().'/99.txt',888);
        }
    }

    //判断用户是否关注过微信公众号
    public function subscribe()
    {
    	$openid = 'oYIWC0gZ6ktGtZjB9y7gdhoFcSXs';
        $id = 154;

        $access_token = $this->getWxAccessToken();
  
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid;
        $subscribe = $this->http_curl($url, 'get');
        // 如果未关注，生成临时二维码
        if($subscribe['subscribe'] == 0)
        {
            //生成临时二维码
            // $id = I('get.id');  //二维码参数   活动id
            $qrcode = $this ->getTemporaryQrcode($id,$access_token);  //生成带活动id的二维码url
            $res_data['url'] = $qrcode['url'];
            $res_data['img'] = $qrcode['img'];
            $res_data['subscribe'] = 0;
        }
        
        //用户已经关注了公众号
        if($subscribe['subscribe'] == 1)
        {
          $qrcode = $this ->getTemporaryQrcode($id,$access_token);  //生成带活动id的二维码url
          
            $res_data['url'] = $qrcode['url'];
            $res_data['img'] = $qrcode['img'];
            $res_data['subscribe'] = 1;
        }
        $this -> ajaxReturn($res_data);
    }

    /**
     * 根据url请求生成二维码
     * [https_post description]
     * @Author   胡宝强
     * @DateTime 2018-04-28T22:20:06+0800
     * @param    [type]                   $url  [description]
     * @param    [type]                   $data [description]
     * @return   [type]                         [description]
     */
    public function https_post($url,$data){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        $result = curl_exec($curl); 
        if (curl_errno($curl)) { 
            return 'Errno'.curl_error($curl);
        }
        curl_close($curl); 
        $result=json_decode($result,true);
        //dump($result);die;
        //$ticket = empty($result['ticket'])? '':$result['ticket'];
        return $result;
    }

    //创建临时二维码
    public function getTemporaryQrcode($id,$access_token){
         $url = str_replace("##TOKEN##", $access_token, "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=##TOKEN##");
         $qrcode = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$id.'}}}';

         //file_put_contents('access_token_zk_1.txt',$access_token."+++++".$url);
         $qrcode_data = $this ->https_post($url, $qrcode);
        
         //$qrcode_data = json_decode($qrcode_data, true);//返回二维码数据
         //获取 ticket  
         $ticket = urlencode($qrcode_data['ticket']);

         //返回二维码图片  
         $img = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;
         $qrcode_data['url'] = urldecode($qrcode_data['url']);//二维码url
         $qrcode_data['img'] = $img;//二维码图片
         return $qrcode_data;
         // return urldecode($result['url']);
    }

    /**
     * 获取access_token
     * [getWxAccessToken description]
     * @Author   胡宝强
     * @DateTime 2018-04-28T22:18:23+0800
     * @return   [type]                   [description]
     */
    public function getWxAccessToken(){
        $appid = C('weixin.appid');
        $appsecret = C('weixin.appsecret');
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
        $result = $this->http_curl($url, 'get');
    
        if (array_key_exists('errorcode', $result))
        {
            $this ->getWxAccessToken();
            exit;
        }else{
          $web_expires = time() + 7140; // 提前60秒过期
          M('wx_user')->where(array('id' => 1))->save(array('web_access_token' => $result['access_token'], 'web_expires' => $web_expires));
          return $result['access_token'];
        }
    }

    /**
     * [getBaseInfo description]
     * @return [type] [description]
     */
    public function getBaseInfo() {
        //1.获取code
        $appid = C('weixin.appid');
        $redirect_uri = urlencode(C('API_URL')."/Wechat/Index/getUserOpenId");
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
        header('location:' . $url);
    }

    /**
     * 获取用户的openid
     * [getUserOpenId description]
     * @return [type] [description]
     */
    public function getUserOpenId() {
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $baseUrl = urlencode($this->get_url());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
        } else {
            $link = $_GET['link'];
            //上面获取到code后这里跳转回来
            $code = $_GET['code'];
            $data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid
            //$get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
            //查看openid是否已注册
            $user = M("users")->where('openid', $data['openid'])->field('user_id, new_people,token')->find();
            if($user) {
                echo 111;die;
              //return header("location:http://www.maxiansheng.com/{$link}?token=".$user['token']."&type=".$user['new_people']);
            }else{
                echo 222;die;
            }
            //return header("location:http://www.maxiansheng.com/{$link}?openid=".$data['openid']);

            $data2 = $this->GetUserInfo($data['access_token'], $data['openid']);//获取微信用户信息
            $data['nickname'] = empty($data2['nickname']) ? '微信用户' : trim($data2['nickname']);
            $data['sex'] = $data2['sex'];
            $data['head_pic'] = $data2['headimgurl'];
            $data['subscribe'] = $data2['subscribe'];
            $data['oauth'] = 'weixin';
            if (isset($data2['unionid'])) {
                $data['unionid'] = $data2['unionid'];
            }
        }
    }

    /**
     * 获取当前的url 地址
     * @return type
     */
    private function get_url()
    {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
    }

    /**
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     * @return openid
     */
    public function getOpenidFromMp($code)
    {
      //file_put_contents(getcwd().'/5.txt',55);
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
        //1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
        //2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res, true);
        curl_close($ch);
        return $data;
    }

    /**
     * 构造获取open和access_toke的url地址
     * @param string $code ，微信跳转带回的code
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = C('weixin.appid');
        $urlObj["secret"] = C('weixin.appsecret');
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }

    /**
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = C('weixin.appid');
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
//        $urlObj["scope"] = "snsapi_base";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }

    /**
     * 通过access_token openid 从工作平台获取UserInfo
     * @return openid
     */
    public function GetUserInfo($access_token, $openid)
    {
      //file_put_contents(getcwd().'/7.txt',77);
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token, $openid);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);//运行curl，结果以jason形式返回
        $data = json_decode($res, true);
        curl_close($ch);
        //file_put_contents(getcwd().'/9.txt',99);
        //获取用户是否关注了微信公众号， 再来判断是否提示用户 关注
        if (!isset($data['unionid'])) {
            $access_token2 = $this->get_access_token();//获取基础支持的access_token
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token2&openid=$openid";
            $subscribe_info = httpRequest($url, 'GET');
            $subscribe_info = json_decode($subscribe_info, true);
            $data['subscribe'] = $subscribe_info['subscribe'];
        }
        return $data;
    }

    /**
     * 获取access_token
     * [get_access_token description]
     * @return [type] [description]
     */
    public function get_access_token()
    {
      //file_put_contents(getcwd().'/10.txt',1010);
        //判断是否过了缓存期
        $expire_time = $this->weixin_config['web_expires'];
        if ($expire_time > time()) {
            return $this->weixin_config['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->weixin_config[appid]}&secret={$this->weixin_config[appsecret]}";
        //file_put_contents(getcwd().'/11.txt',$url);
        $return = httpRequest($url, 'GET');
        $return = json_decode($return, 1);
        $web_expires = time() + 7140; // 提前60秒过期
        M('wx_user')->where(array('id' => 1))->save(array('web_access_token' => $return['access_token'], 'web_expires' => $web_expires));
        return $return['access_token'];
    }

     /**
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token, $openid)
    {
      //file_put_contents(getcwd().'/8.txt',88);
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?" . $bizString;
    }


      /**
       * 拼接签名字符串
       * @param array $urlObj
       * @return 返回已经拼接好的字符串
       */
      private function ToUrlParams($urlObj)
      {
        //file_put_contents(getcwd().'/4.txt',44);
          $buff = "";
          foreach ($urlObj as $k => $v) {
              if ($k != "sign") {
                  $buff .= $k . "=" . $v . "&";
              }
          }
          $buff = trim($buff, "&");
          return $buff;
      }

    /**
     * 
     * @param type $url      接口url
     * @param type $type     请求类型
     * @param type $resType  返回数据类型
     * @param type $arr      post请求的参数
     * @return type          返回数据
     * 宁曾珍   2017.11.5
     */
    function http_curl($curl, $method='get',$https=true,  $data=null){  
      $ch = curl_init();//初始化  
      curl_setopt($ch, CURLOPT_URL, $curl);//设置访问的URL  
      curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息  
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出  
      if($https){  
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//不做服务器认证  
              curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//不做客户端认证  
      }  
      if($method == 'post'){  
              curl_setopt($ch, CURLOPT_POST, true);//设置请求是POST方式  
              curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置POST请求的数据  
      }  
      $str = curl_exec($ch);//执行访问，返回结果  
      curl_close($ch);//关闭curl，释放资源  
      return json_decode($str, true);
  }  

    //微信Jssdk 操作类 用分享朋友圈 JS
    public function ajaxGetWxConfig(){
      $askUrl = I('askUrl');//分享URL
      $weixin_config = M('wx_user')->find(); //获取微信配置
      $jssdk = new JssdkLogic($weixin_config['appid'], $weixin_config['appsecret']);
      $signPackage = $jssdk->GetSignPackage(urldecode($askUrl));
      if($signPackage){
        $this->json(0,'微信分享',$signPackage);
      }else{
        return false;
      }
    }

    /**
     * 获取用户openid
     * @Author   蒋峰
     * @DateTime 2018-05-25T15:06:45+0800
     * @param    [type]                   $user_id [description]
     * @return   [type]                            [description]
     */
    public function getOpenid($user_id)
    {
        $openid = D("Users")->where('user_id', $user_id)->field('openid')->find();
        if(empty($openid['openid'])){
            return false;
        }else{
            return $openid['openid'];
        }
    }
}
