<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\api\controller;

use app\common\model\Picture;

class OrderComment extends Base{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 添加评价
     * @Autoh: 蒋峰
     * Date: 2018/7/19 14:36
     */
    public function add_comment(){
        //验证请求方式
        !request()->isPost() && $this->errorMsg(1006);
        //验证登录
        empty(I('token')) && $this->errorMsg(2001, 'token');
        $this->checkToken(I('token'));
        //验证参数
        ( empty(I('rec_id', '')) || !is_numeric($rec_id = I('rec_id', 0)) ) && $this->errorMsg(2002, 'rec_id');
        empty($city = I('city', '')) && $this->errorMsg(2002, 'city');
        $content = I('content', '');
        $img = I('img/a',[]);

        //查看订单是否评论过
        $orderInfo = M("OrderGoods")->where(array("rec_id" => $rec_id))->field('rec_id,order_id,goods_id,is_comment')->find();
        if($orderInfo['is_comment']) $this->errorMsg( 1477, '您已经评论过啦');
        $order = M('order')->where(['order_id'=>$orderInfo['order_id']])->field('order_sn,master_order_sn')->find();
        if(empty($order)) $this->errorMsg( 3001, '改订单不存在');
        //拼接评论参数
        $son_order_comment_arr['order_id'] = $orderInfo['order_id'];
        $son_order_comment_arr['rec_id'] = $orderInfo['rec_id'];
        $son_order_comment_arr['user_id'] = $this->userInfo['user_id'];
        $son_order_comment_arr['head_pic'] = $this->userInfo['head_pic'];
        $son_order_comment_arr['create_time'] = time();
        $son_order_comment_arr['nickname'] = $this->userInfo['nickname'] ? $this->userInfo['nickname'] : $this->userInfo['mobile'];
        $son_order_comment_arr['goods_id'] = $orderInfo['goods_id'];
        $son_order_comment_arr['content'] = $content;
        $son_order_comment_arr['city'] = $city;
        $son_order_comment_arr['master_order_sn'] = $order['master_order_sn'];
        $son_order_comment_arr['order_sn'] = $order['order_sn'];

        //上传评论图片
        $picture = new Picture();
        $image = [];
        if (is_array($img) && !empty($img)) {
            foreach ($img as $v) {
                $datas['sha1'] = hash_file('sha1', $v);
                $datas['md5'] = hash_file('md5', $v);
                $id = $picture->pic_id($v, $datas);
                $image[] = $id;
            }
        }
        $son_order_comment_arr['img'] = implode(",", $image);
        //添加评论
        $ret = M("son_order_comment")->add($son_order_comment_arr);

        if(!$ret) $this->errorMsg( 9999);
        //评论成功修改订单状态
        (new \app\api\model\Order())->checkOrder($rec_id);
        //评论成功后获得积分
        (new \app\common\logic\PointLogic())->commentPoint($this->userInfo['user_id'],$orderInfo['order_id'],$rec_id);
        return $this->json('0000', "评论成功");
    }
}
