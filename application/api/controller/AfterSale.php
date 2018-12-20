<?php

/**
 * 退款管理
 */
namespace app\api\controller;
use think\Model;
use app\api\model\Sale;
use think\Hook;
class AfterSale extends Base{

    public $user_id;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize(){
        Hook::listen('app_begin_origin');
        $token = I('token');
        $this->user_id = $this->checkToken($token);
    }
    /**
     * 展示这个商品要退款的页面
     * @Autoh: 胡宝强
     * Date: 2018/7/19 16:36
     */
    public function show_sale(){
        $order_id = I('order_id');
        if(!is_numeric($order_id)) return $this->errorMsg('2002','order_id');
        $rec_id = I('rec_id');
        if(!is_numeric($rec_id)) return $this->errorMsg('2002','rec_id');
        $model = new Sale();
        $arr = $model->show_sale($rec_id,$order_id);
        if($arr) $this->json('0000','获取信息成功',$arr);
        else $this->json('9999','获取信息失败',array());
    }

    /**
     * 添加商品退款
     * @Autoh: 胡宝强
     * Date: 2018/7/19 17:16
     */
    public function add_sale(){
        !request()->isPost() && $this->errorMsg(1006);
        $order_id = I('order_id');
        if(!is_numeric($order_id)) return $this->errorMsg('2002','order_id');
        $rec_id = I('rec_id');
        if(!is_numeric($rec_id)) return $this->errorMsg('2002','rec_id');
        $return_goods = M('return_goods')->where(array('rec_id'=>$rec_id))->find();
        if(!empty($return_goods)) return $this->errorMsg('3000');
        $token = I('token');
        if(empty($token)) return $this->errorMsg('2002','token');
        $user_id = $this->checkToken($token);
        $order_goods = M('order_goods')->where(array('rec_id'=>$rec_id))->find();
        $order = M('order')->where(array('order_id'=>$order_goods['order_id'],'user_id'=>$user_id))->find();
        if(empty($order)) return $this->errorMsg('9999');

        $desc = I('desc');
        $model = new Sale();
        $arr = $model->add_return($rec_id,$order,$desc);  //申请售后
        if($arr) $this->json('0000','退款成功',$arr);
        else $this->json('9999','退款失败',array());
    }

    /**
     * 退款列表
     * @Autoh: 胡宝强
     * Date: 2018/7/19 18:22
     */
    public function index(){
        $model = new Sale();
        $page=I("post.page",1);
        if (is_numeric($page)==false) $this->errorMsg(2001, 'page');
        if ($page<1) $this->errorMsg(2002, 'page');
        $token = I('token');
        if(empty($token)) return $this->errorMsg('2002','token');
        $user_id = $this->checkToken($token);
        $data = $model->get_list($user_id,$page);
        $this->json("0000", "获取退款列表成功", $data);
    }

    /**
     * 退款详情
     * @Autoh: 胡宝强
     * Date: 2018/7/19 20:06
     */
    public function detail(){
        !request()->isPost() && $this->errorMsg(1006);
        $id = I('id');
        if (is_numeric($id)==false) $this->errorMsg(2001, 'id');
        $model = new Sale();
        $token = I('token');
        if(empty($token)) return $this->errorMsg('2002','token');
        $user_id = $this->checkToken($token);
        $data = $model->details($id,$user_id);
        $this->json("0000", "获取退款列表成功", $data);
    }


}
