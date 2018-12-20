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
 * Author: dyr
 * Date: 2016-08-23
 */

 /**
     * 注册
     * @param $username  邮箱或手机
     * @param $password  密码
     * @param $password2 确认密码
     * @param int $push_id
     * @param string $nickname
     * @param string $head_pic
     * @return array
     */
    function reg($mobile, $password, $email, $push_id = 0, $nickname = "", $head_pic = "",$user_i='')
    {
        $is_validated = 0;
        if (check_email($email)) {
            $is_validated = 1;
            $map['email_validated'] = 1;
            $map['email'] = $email; //邮箱注册
        }

        if (check_mobile($mobile)) {
            $is_validated = 1;
            $map['mobile_validated'] = 1;
            $map['nickname'] = $map['mobile'] = $mobile; //手机注册
        }
//        if(is_numeric($user_id)){
//            $is_validated = 1;
//            $map['first_leader'] = $user_id;
//        }

        if (!empty($nickname)) {
            $map['nickname'] = $nickname;
        }

        if (!empty($head_pic)) {
            $map['head_pic'] = $head_pic;
        }else{
            //默认头像
            $map['head_pic']='/public/images/icon_goods_thumb_empty_300.png';
        }
        if (strlen($password) < 6) {
            return array('status' => -1, 'msg' => '密码不能低于6位字符', 'result' => '');
        }
        if ($is_validated != 1)
            return array('status' => -1, 'msg' => '请用手机号或邮箱注册', 'result' => '');

        if (!$mobile || !$password)
            return array('status' => -1, 'msg' => '请输入用户名或密码', 'result' => '');
      
        //验证是否存在用户名
        if (get_user_info($mobile, 1) || get_user_info($mobile, 2))
            return array('status' => -1, 'msg' => '账号已存在', 'result' => '');

        $map['password'] = encrypt($password);
        $map['reg_time'] = time();
        //$map['first_leader'] = cookie('first_leader');  //推荐人id
        // 如果找到他老爸还要找他爷爷他祖父等
        //if ($map['first_leader']) {
            //$first_leader = M('users')->where("user_id = {$map['first_leader']}")->find();
            //$map['second_leader'] = $first_leader['first_leader'];
            //$map['third_leader'] = $first_leader['second_leader'];
            //他上线分销的下线人数要加1
           // M('users')->where(array('user_id' => $map['first_leader']))->setInc('underling_number');
            //M('users')->where(array('user_id' => $map['second_leader']))->setInc('underling_number');
           // M('users')->where(array('user_id' => $map['third_leader']))->setInc('underling_number');
        //} else {
        //    $map['first_leader'] = 0;
        //}

        // 成为分销商条件
        //$distribut_condition = tpCache('distribut.condition');
        //if($distribut_condition == 0)  // 直接成为分销商, 每个人都可以做分销
        $map['is_distribut'] = 1; // 默认每个人都可以成为分销商
        $map['push_id'] = $push_id; //推送id
        $map['token'] = md5(time() . mt_rand(1, 999999999));
        $map['last_login'] = time();
        $user_id = M('users')->add($map);
        if (!$user_id)
            return array('status' => -1, 'msg' => '注册失败', 'result' => '');

        $pay_points = tpCache('basic.reg_integral'); // 会员注册赠送积分
        if ($pay_points > 0)
            accountLog($user_id, 0, $pay_points, '会员注册赠送积分'); // 记录日志流水
        $user = M('users')->where("user_id = {$user_id}")->find();

        return array('status' => 1, 'msg' => '注册成功', 'result' => $user);
    }
    /**
     *  针对 APP 修改密码的方法
     * @param $user_id  用户id
     * @param $old_password  旧密码
     * @param $new_password  新密码
     * @param bool $is_update
     * @return array
     */
    function passwordForApp($mobile, $new_password, $is_update = true)
    {
        $user = M('users')->where('mobile', $mobile)->find();
        if(!$user) return array('status' => -1, 'msg' => '用户不存在', 'result' => '');
        if (strlen($new_password) < 6) {
            return array('status' => -1, 'msg' => '密码不能低于6位字符', 'result' => '');
        }
        //验证原密码
        // if ($is_update && ($user['password'] != '' && $old_password != $user['password'])) {
        //     return array('status' => -1, 'msg' => '旧密码错误', 'result' => '');
        // }
        if($user['password'] == encrypt($new_password))  return array('status' => -1, 'msg' => '新密码与旧密码重复', 'result' => '');
        $row = M('users')->where("mobile='{$mobile}'")->update(array('password' => encrypt($new_password)));
        if (!$row) {
            return array('status' => -1, 'msg' => '密码修改失败', 'result' => '');
        }
        return array('status' => 1, 'msg' => '密码修改成功', 'result' => $user);
    }


    /**
     * 抛出异常处理
     * [throwError description]
     * @Author   XD
     * @DateTime 2018-07-11T16:34:34+0800
     * @return   [type]                   [description]
     */
    function throwError($msg){
      exit(jsons(1,$msg));
    }

     /**
     * 数据统一分装返回json
     * @param type $code
     * @param type $message
     * @param type $data
     */
   function jsons($code = "0", $message = "", $info = null, $count = "")
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