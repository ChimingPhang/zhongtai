<?php
namespace app\api\controller;

/**
 * 用户登录
 * @Author   XD
 * @DateTime 2018-07-11T15:09:13+0800
 */
class UserLogin extends Base {

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
     * 登录
     * [reg description]
     * @Author   XD
     * @DateTime 2018-07-11T16:54:14+0800
     * @return   [type]                   [description]
     */
    public function user_login(){
      $data = input('');
      $UserLogic = new \app\common\logic\UsersLogic;
      $res = $UserLogic->login($data['mobile'],$data['password']);
      if($res['status']!=1)$this->throwError($res['msg']);
      else $this->json('0000','登录成功',['token'=>$res['result']['token']]);
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        setcookie('cn', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        //$this->success("退出成功",U('Mobile/Index/index'));
        header("Location:" . U('Mobile/Index/index'));
        exit();
    }

    /**
     * 忘记密码
     * [forgot_password description]
     * @Author   XD
     * @DateTime 2018-07-12T09:46:04+0800
     * @return   [type]                   [description]
     */
    public function forgot_password(){
      $data = input('');
      //验证手机号格式
      $this->checkCode($data['mobile'],$data['code']);
      $res  = passwordForApp($data['mobile'],$data['password']);
      if($res['status']!=1)$this->throwError($res['msg']);
      else $this->json('0000',$res['msg'],['token'=>$res['result']['token']]);
    }
    /**
     * 发送忘记密码手机号验证码
     * [sendCode description]
     * @Author   XD
     * @DateTime 2018-07-11T15:09:13+0800
     * @return   [type]                   [description]
     */
    public function forgot_code(){
        $mobile = input('mobile');
        //验证手机号格式
        $this->moblie($mobile);
        //后台是否开启手机号忘记密码功能
        $reg_sms_enable = tpCache('sms.forget_pwd_sms_enable');
        //代表忘记密码
        $type = 2;
        if($reg_sms_enable){
            $res = M('users')->where(['mobile'=>$mobile])->count();
            if(!$res) $this->throwError('账号不存在');
            $res        = checkEnableSendSms($type);
            if($res['status']!=1) $this->throwError($res['msg']);
            $res = $check_code = $this->sendCode($type,$mobile);
            if($res['status']!=1) $this->throwError($res['msg']);
            else $this->json('0000','发送成功',['code'=>$res['code']]);
        }else{
            $this->throwError('对不起，暂未开启忘记密码手机验证码功能!');
        }
    }
}