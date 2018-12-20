<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\api\controller;
use app\api\model\Users as user;
use app\api\model\UserSignLog;
use think\Model;
use think\Request;
use app\common\model\Picture;
use app\common\logic\PointLogic;

class Users extends Base{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 联系电话
     * [get_phone description]
     * @Author   XD
     * @DateTime 2018-07-27T02:25:36+0800
     * @return   [type]                   [description]
     */
    public function get_phone(){
        $data['phone']=tpCache('shop_info')['phone'];
        $this->json('0000','请求成功',$data);

    }
    /**
     * 获取用户的一些信息
     * @Autoh: 胡宝强
     * Date: 2018/7/17 19:46
     */
    public function userinfo()
    {
        $token = I('token');
        $user_id = $this->checkToken($token);
        $model = new user();
        $data = $model->get_userinfo($user_id);
        $data['is_sign'] = (new UserSignLog())->isSign($user_id);
        $this->json('0000','获取用户信息成功',$data);
    }

    /**
     * 修改头像
     * [update_img description]
     * @Author   XD
     * @DateTime 2018-07-27T00:38:46+0800
     * @return   [type]                   [description]
     */
    public function update_img(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $token = I('token');
        $user_id = $this->checkToken($token);
        $picture= new Picture();
        // $file = request()->file('img');
        $data=I("post.");
        $img = $picture->change_file($data['img']);
        // 移动到框架应用根目录/public/uploads/ 目录下
        // $info = $file->move(ROOT_PATH . 'public' . DS . 'upload');
        // if($info){
        // // 成功上传后 获取上传信息
        // // 输出 jpg
        // // 输出 42a79759f284b767dfcb2a0197904287.jpg
        // $img =  '/public/upload/'. $info->getSaveName();
        // }else{
        // // 上传失败获取错误信息
        // $this->throwError($file->getError());
        // }
        $res = M('users')->where(['user_id'=>$user_id])->save(['head_pic'=>$img]);
        if($res) $this->json('0000','修改成功');
        else $this->json('0000','修改失败');
    }

     /**
     * 修改头像接口
     * @Author   ZCQ
     * @DateTime 2018-04-17 T10:58:10+0800
     * @return   [type]                   [description]
     */
     public function upd_img()
     { 
        if(!request()->isPost())
        {
            $this->json(1,'请求方式不正确');
        }
        $user_id=$this->login();
        // $user_id=154;

        $data=I("post.");
        if (empty($data)) 
        {
           $this->json(1,"非法请求！参数错误");
        }
        $picture= new Picture();

        if($data['type']=="web")
        {   
            $img= $picture->change_file($data['img']);
           
            
        }
        else if($data['type']=="android" || $data['type']=="ios" )
        {
            $file1 = request()->file('img');
            $ret=$picture->upload($file1,config('picture_upload'));
            $img = $ret['path'];
        }

        
        $table="users";
        $key="user_id";
        $val=$user_id;
        $value=array("img"=>$img);
        
        // $img=M('users')->where(array("user_id"=>154))->field("img")->find();
        $res=save_value($table,$key,$val,$value);
        // var_dump($res);die;

        if ($res) 
        {
          $this->json(0,"修改成功",[]);
        }
        else
        {
          $this->json(0,"修改失败",[]);
        }
     
   }


    /**
     * 我的收藏列表
     * @Autoh: 胡宝强
     * Date: 2018/7/12 20:54
     */
    public function collection()
    {
        $token = I('token');
        $user_id=$this->checkToken($token);
        $page=I("post.page",1);
        if (is_numeric($page)==false) $this->errorMsg(2001, 'page');
        if ($page<1) $this->errorMsg(2002, 'page');
        $model = new user();
        $data = $model->get_collection($user_id,$page);
        $this->json("0000", "获取收藏列表成功", $data);
    }

    /**
     * 删除收藏
     * @Autoh: 胡宝强
     * Date: 2018/7/12 21:11
     */
    public function collection_del()
    {
        $data=I("post.");
        $user_id = $this->checkToken($data['token']);
        if(empty($data['id'])) return $this->errorMsg(2001, 'id');
        if(is_array($data['id'])) $data['id'] = implode(',',$data['id']);
        $model = new user();
        $data = $model->del_collection($user_id,$data['id']);
        if($data){
            $this->json("0000", "删除收藏成功", $data);
        }else{
            $this->json("9999", "删除收藏失败", $data);
        }
    }

    /**
     * [签到]
     * @Auther 蒋峰
     * @DateTime
     */
    public function sign_in()
    {
        empty(I('token')) && $this->errorMsg(2001, 'token');//必传
//        empty($today = I('today')) && $this->errorMsg(2001, 'today');//必传
//        if(!strtotime($today)) $this->errorMsg(2002, 'token');//必传
//        if(strtotime(date('Y/m/d')) != strtotime($today)) return $this->errorMsg(4003);
        $this->checkToken(I('token'));

        $SignLog = new UserSignLog();
        $res = $SignLog->toSign($this->userInfo['user_id'],date('Y/m/d'), 0);

        if($res) {
            $point = M('users')->where(['user_id'=>$this->userInfo['user_id']])->getField('pay_points');
            $data['point'] = $point;
            return $this->json("0000", "签到成功",$data);
        }else{
            return $this->json("1477", "你已经签到过啦");
        }

    }

    /**
     * [查询签到日志]
     * @Auther 蒋峰
     * @DateTime
     */
    public function sign_query()
    {
        empty(I('token')) && $this->errorMsg(2001, 'token');//必传
        empty($today = I('today')) && $this->errorMsg(2001, 'today');//必传
        if(!strtotime($today)) $this->errorMsg(2002, 'token');//必传

        $this->checkToken(I('token'));

        $SignLog = new UserSignLog();
        $res = $SignLog->querySign($this->userInfo['user_id'],$today);
        return $this->json("0000", "加载成功", $res);
    }

    /**
     * [身份认证]
     * @Auther 蒋峰
     * @DateTime
     */
    public function authentication()
    {
        empty(I('token')) && $this->errorMsg(2001, 'token');//必传
        empty($name = I('name')) && $this->errorMsg(2001, 'name');//必传
        if(!strtotime($name)) $this->errorMsg(2002, 'name');//必传
        empty($type = I('type')) && $this->errorMsg(2001, 'type');//必传
        if(!strtotime($type)) $this->errorMsg(2002, 'type');//必传
        empty($id_card = I('id_card')) && $this->errorMsg(2001, 'id_card');//必传
        if(!strtotime($id_card)) $this->errorMsg(2002, 'id_card');//必传
        $this->checkToken(I('token'));

        $this->errorMsg(1477, '认证失败');
    }

    /**
     * 根据用户的积分获取可以抵扣多少钱
     * @Autor: 胡宝强
     * Date: 2018/8/27 16:02
     * @return float
     */
    public function getIntegralMoney(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $integral = I('integral');  //用户输入的积分数量
        if(empty($integral))    $this->errorMsg(2002, 'integral');//必传
        if(!is_numeric($integral)) $this->errorMsg(2002, 'integral');//必传
        $point_rate = tpCache('shopping.point_rate'); //兑换比例
        $data['money'] =  round($integral/$point_rate,2);  //积分最高可以抵多少钱
        return $this->json('0000','ok',$data);
    }

    /**
     * 配件提交订单的时候的金额数量
     * @Autor: 胡宝强
     * Date: 2018/8/29 16:28
     */
    public function getGoodsPrice(){
        $action = I('action');  //buy_now 直接购买 cart_now 购物车购买
        if(empty($action))  $this->errorMsg(2002, 'action');//必传
        $integral = I('integral');  //使用的积分数量
        if(empty($integral))  $this->errorMsg(2002, 'integral');//必传
        $point_rate = tpCache('shopping.point_rate'); //兑换比例
        if($action == 'buy_now'){
            //直接购买
            $item_id = I('item_id'); //规格id
            if(empty($item_id))  $this->errorMsg(2002, 'item_id');//必传
            $goods_id = I('goods_id');  //商品id
            if(empty($goods_id))  $this->errorMsg(2002, 'goods_id');//必传
            //$exchange_integral = I('exchange_integral'); //购买的方式 1 积分和金额购买 2 纯积分购买
            $goods_num = I('goods_num'); //购买的商品数量
            if(empty($goods_num))  $this->errorMsg(2002, 'goods_num');//必传
            if(!is_numeric($goods_num)) $this->errorMsg(2002, 'goods_num');//必传
            $goods = M('spec_goods_price')->where(['item_id'=>$item_id,'goods_id'=>$goods_id])->field('integral_price,integral')->find();
            $price = $goods['integral_price'] * $goods_num;     //商品的总金额
            $all_integral = $goods['integral'] * $goods_num;           //商品的总积分
            if($integral > $all_integral){
                $this->errorMsg(2002, 'integral');//积分数量不能比总积分大
            }
            $data['money'] = $price + round(($all_integral - $integral)/$point_rate,2);  //剩余的商品要支付的金额
        }else{
            //购物车购买
            $token = I('token');
            if(empty($token))  $this->errorMsg(2002, 'token');//必传
            $user_id = $this->checkToken($token);       //用户id
            $cartWhere['user_id'] = $user_id;
            $cartWhere['selected'] = 1;
            $cartList = M('cart')->where($cartWhere)->select(); //购物车中的商品的
            $goods_price = 0;
            $all_integral = 0;
            foreach ($cartList as $key => $value) {
                $goods_price+= $value['goods_num'] * $value['goods_price'];
                $all_integral+= $value['goods_num'] * $value['integral'];
            }


            if($integral > $all_integral){
                $this->errorMsg(2002, 'integral');//积分数量不能比总积分大
            }
            $data['money'] = $goods_price + round(($all_integral - $integral)/$point_rate,2);  //剩余的商品要支付的金额
        }
        $data['integral_price'] = round($integral/$point_rate,2);
        return $this->json('0000','ok',$data);
    }

    /**
     * 积分日志
     * @Autor: 胡宝强
     * Date: 2018/8/30 15:51
     */
    public function integralLog(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $token = I('token');
        if(empty($token))  $this->errorMsg(2002, 'token');//必传
        $user_id = $this->checkToken($token);       //用户id
        $page = intval(I('page',1));
        $page_num=10;//每页的数据
        $offset=$page_num*($page-1);
        $limit=$offset.",".$page_num;
        $data = M('integral_log')
            ->order('create_time desc')
            ->where(['user_id'=>$user_id])
            ->limit($limit)
            ->field('status,integral,create_time,type_name')
            ->select();
        $this->json('0000','获取列表成功',$data);
    }

    /**
     * 分享获得积分
     * @Autor: 胡宝强
     * Date: 2018/8/30 16:25
     */
    public function integralShare(){
        $today = strtotime(date('Y-m-d',time()));
        $tomorrow = strtotime(date('Y-m-d',strtotime( "+1 day",time())));

        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $token = I('token');
        if(empty($token))  $this->errorMsg(2002, 'token');//必传
        $user_id = $this->checkToken($token);       //用户id
        $goods_id = I('goods_id', 0); //商品id
        $type = I('type',1);        //分享类型   1微信 2朋友圈 3qq 4微博',
        $prom_id = I('prom_id',1);  //商品类型   1普通汽车 2 普通配件 2 积分商城商品 3 秒杀商品 4 拍卖商品

        $data = [
            'user_id' => $user_id,
            'goods_id' => intval($goods_id),
            'prom_id' => $prom_id,
            'type' => intval($type),
            'create_time' => time(),
        ];
        M("GoodsShare")->add($data);
        $where['user_id'] = $user_id;
        $where['create_time'] = array('between',"$today,$tomorrow");
        $count = M('GoodsShare')->where($where)->count();
        //分享次数小于6的时候才可以获得积分
       if($count<6){
           $model= new PointLogic();
           $model->goodsFriend($user_id);
       }
        $this->json('0000', '分享成功','');
    }
}
