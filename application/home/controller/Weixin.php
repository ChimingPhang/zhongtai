<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采有最新thinkphp5助手函数特性实现函数简写方式M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 */

namespace app\home\controller;

use app\common\logic\WechatLogic;
use think\Db;

class Weixin
{
    /**
     * 处理接收推送消息
     */
    public function index()
    {
        if(!empty(I('echostr'))){ //通过判断echostr是否是验证接口
            $bol = $this->wechatVerify();
            if($bol){
                echo I('echostr');die;
            }
        }
        $logic = new WechatLogic;
        $logic->handleMessage();
    }

    public function wechatVerify()
    {
        $nonce     = I('nonce');
        $signature = I('signature');
        $timestamp = I('timestamp');
        $token     = Db::name('wx_user')->find()['w_token'];
        $sign_arr  = [$nonce,$timestamp,$token];
//        file_put_contents('./message/sign.log', $signature."\r\n", FILE_APPEND);
// print_r($sign_arr);die;
        sort($sign_arr,SORT_STRING);
        $sign_str  = implode('', $sign_arr);
        $sign_shal = sha1($sign_str);
        if($sign_shal == $signature){
            return true;
        }
        return false;
    }
    
}