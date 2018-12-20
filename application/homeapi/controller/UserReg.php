<?php
namespace app\api\controller;
use app\common\logic\PointLogic;

/**
 * 用户注册
 * @Author   XD
 * @DateTime 2018-07-11T15:09:13+0800
 */
class UserReg extends Base {

    public function __construct()
    {
        parent::__construct();
    }
    /*
     * 初始化操作
     */
    public function _initialize() {
     
    }
    /**
     * 注册
     * [reg description]
     * @Author   XD
     * @DateTime 2018-07-11T16:54:14+0800
     * @return   [type]                   [description]
     */
    public function reg(){
      $data = input('');
//      if($data['first_leader'] !=0){
//          $model = new PointLogic();
//          $model->invitationPoint($data['first_leader']);
//      }

      //验证手机号格式
      $this->checkCode($data['mobile'],$data['code']);
      $res  = reg($data['mobile'],$data['password'],$data['email'],'','','',0);
      if($res['status']!=1)$this->throwError($res['msg']);
      else $this->json('0000','注册成功',['token'=>$res['result']['token']]);
    }
    /**
     * 获取短信验证码失效时间
     * [gettime description]
     * @Author   XD
     * @DateTime 2018-07-11T17:32:42+0800
     * @return   [type]                   [description]
     */
    public function gettime(){
    	$time = tpCache('sms.sms_time_out')??60;
    	$this->json('0000','获取时间成功',['out_time'=>$time]);
    }
    /**
     * 发送注册手机号验证码
     * [sendCode description]
     * @Author   XD
     * @DateTime 2018-07-11T15:09:13+0800
     * @return   [type]                   [description]
     */
    public function regcode(){
    	$mobile = input('mobile');
        //代表注册
        $type = 1;
    	//后台是否开启手机号注册功能
    	$reg_sms_enable = tpCache('sms.regis_sms_enable');
    	if($reg_sms_enable){
            //验证手机号格式
            $this->moblie($mobile);

    		$res = M('users')->where(['mobile'=>$mobile])->count();
    		if($res) $this->throwError('账号已存在');
    		$res        = checkEnableSendSms(1);
    		if($res['status']!=1) $this->throwError($res['msg']);
    		$res = $check_code = $this->sendCode(1,$mobile);
    		if($res['status']!=1) $this->throwError($res['msg']);
    		else $this->json('0000','发送成功',['code'=>$res['code']]);
    	}else{
    		$this->throwError('对不起，暂未开启注册手机号验证码功能!');
    	}
    }
   
    /**
     * 接收短信回调
     * [callbackSms description]
     * @Author   XD
     * @DateTime 2018-07-23T12:00:59+0800
     * @return   [type]                   [description]
     */
    public function callbackSms(){

    }

}