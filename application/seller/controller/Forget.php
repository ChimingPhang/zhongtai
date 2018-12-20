<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 商业用途务必到官方购买正版授权, 使用盗版将严厉追究您的法律责任。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 当燃
 * Date: 2015-09-09
 */

namespace app\seller\controller;

use app\admin\logic\UpgradeLogic;
use think\Controller;
use think\Session;
use think\Db;

class Forget extends Controller
{
    //显示找回密码页面
    public function forget_pwd(){

        return $this->fetch();
    }

    /**
     * 设置新的密码
     * @Autor: 胡宝强
     * Date: 2018/10/10 17:08
     */
    public function new_pwd(){
        $data = input('');
        //验证手机号格式
        $this->checkCode($data['mobile'],$data['code']);
        $res  = passwordForSeller($data['user_name'],$data['mobile'],$data['new_password']);
        if($res['status']!=1) ajaxReturn(array('status' => -1, 'msg' => $res['msg']));
        else  ajaxReturn(array('status' => 1, 'msg' => '修改成功','url'=>U('Seller/Admin/login')));
    }

    /**发送验证码
     * @Autor: 胡宝强
     * Date: 2018/10/10 18:08
     */
    public function send_validate_code()
    {

        $this->send_scene = C('SEND_SCENE');

        $mobile = I('mobile');
        if(empty($mobile)){
            ajaxReturn(array('status' => -1, 'msg' => '手机号不能为空'));
        }
        $user_name = I('user_name');
        if(empty($user_name)){
            ajaxReturn(array('status' => -1, 'msg' => '账号不能为空'));
        }

        $res = M('dealers')->where(['user_name'=>$user_name,'mobile'=>$mobile])->count();
        if(!$res) ajaxReturn(array('status' => -1, 'msg' => '账号和手机号不匹配'));

        $res = checkEnableSendSms(2);
        $check_code = $this->sendCode(2,$mobile);
        if($check_code['status']!=1) ajaxReturn(array('status' => -1, 'msg' =>$check_code['msg'] ));
        else ajaxReturn(array('status' => 1, 'msg' =>'发送成功' ));
    }

    /**
     * 发送验证码
     * @Autor: 胡宝强
     * Date: 2018/10/11 11:28
     * @param $scene    验证码类型
     * @param $mobile   手机号
     * @return array
     */
    public function sendCode($scene,$mobile){
        $code           = rand(1000, 999999);
        $params['code'] = $code;
        return XdsendSms($scene,$mobile,$params);
    }

    /**
     * 检查验证码
     * @Autor: 胡宝强
     * Date: 2018/10/11 11:29
     * @param $mobile       手机号
     * @param $code         验证码
     * @return bool
     */
    public function checkCode($mobile,$code){
        if(!tpCache('sms.regis_sms_enable')) return true;
        $time     = tpCache('sms.sms_time_out')??60;
        $out_time = time()-$time;
        $mobileCode = M('sms_log')->where(['mobile'=>$mobile,'status'=>1])->order('add_time DESC')->field('code,add_time,id')->find();
        if(!$mobileCode) ajaxReturn(array('status' => -1, 'msg' =>'手机号不存在' ));
        if($mobileCode['add_time']<$out_time) ajaxReturn(array('status' => -1, 'msg' =>'验证码已超时,请重新获取' ));
        if($mobileCode['code']!=$code) ajaxReturn(array('status' => -1, 'msg' =>'验证码不正确' ));
    }

}