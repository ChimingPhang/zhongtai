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
namespace app\admin\controller;
use app\admin\logic\OrderLogic;
use app\admin\model\OrderAction;
use think\AjaxPage;
use think\Db;
use think\Page;

class Carorder extends Base {
    public  $order_status;
    public  $shipping_status;
    public  $pay_status;
    /*
     * 初始化操作   
     */
    public function _initialize() {
        parent::_initialize();
        C('TOKEN_ON',false); // 关闭表单令牌验证
        // 订单 支付 发货状态
        $this->order_status = C('ORDER_STATUS');
        $this->pay_status = C('PAY_STATUS');
        $this->shipping_status = C('SHIPPING_STATUS');
        $this->assign('order_status',$this->order_status);
        $this->assign('pay_status',$this->pay_status);
        $this->assign('shipping_status',$this->shipping_status);
    }

    /*
     *订单首页
     */
    public function index(){
        $begin = date('Y-m-d', strtotime("-3 month"));//30天前
        $end = date('Y-m-d', strtotime('+1 days')); 	
    	$this->assign('timegap',$begin.'-'.$end);
    	//@new 新后台UI参数 @{
    	$this->assign('add_time_begin',date('Y-m-d', strtotime("-3 month")+86400));
    	$this->assign('add_time_end',date('Y-m-d', strtotime('+1 days')));
    	//}
        return $this->fetch();
    }

    /*
     *Ajax首页
     */
     public function ajaxindex(){
        $select_year = getTabByTime(I('add_time_begin')); // 表后缀
        $orderLogic = new OrderLogic();       
        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }else{
            //@new 新后台UI参数
            $begin = strtotime(I('add_time_begin'));
            $end = strtotime(I('add_time_end'));
        }

         $timegaps = I('timegaps');
         if($timegaps){
             $gaps = explode('-', $timegaps);
             $begins = strtotime($gaps[0]);
             $ends = strtotime($gaps[1]);
         }else{
             //@new 新后台UI参数
             $begins = strtotime(I('pay_time_begin'));
             $ends = strtotime(I('pay_time_end'));
         }

        // 搜索条件
        $condition = array();
        //车型订单
        $condition['type'] = 1;
        $keyType = I("keytype");
        $keywords = I('keywords','','trim');
    
        $consignee =  ($keyType && $keyType == 'consignee') ? $keywords : I('consignee','','trim');
        $consignee ? $condition['consignee'] = array('like',trim("%$consignee%")) : false;

         //下单时间
        if($begin && $end){
         $condition['add_time'] = array('between',"$begin,$end");
        }

         //支付时间
         if($begins && $ends){
             $condition['pay_time'] = array('between',"$begins,$ends");
         }
        $store_name = ($keyType && $keyType == 'store_name') ? $keywords :  I('store_name','','trim');
        if($store_name)
        {
            $store_id_arr = M('dealers')->where("name like '%$store_name%'")->getField('id',true);
            if($store_id_arr)
            {
                $condition['store_id'] = array('in',$store_id_arr);
            }else{
                $condition['store_id'] = '01';
            }
        }    
        //$condition['prom_type'] = array('lt',5);
        $order_sn = ($keyType && $keyType == 'order_sn') ? $keywords : I('order_sn') ;
        $order_sn ? $condition['master_order_sn'] = trim($order_sn) : false;
        I('prom_type') != '' ? $condition['prom_type'] = I('prom_type') : false;
        I('order_status') != '' ? $condition['order_status'] = I('order_status') : false;
        I('pay_status') != '' ? $condition['pay_status'] = I('pay_status') : false;
        I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        I('shipping_status') != '' ? $condition['shipping_status'] = I('shipping_status') : false;
        I('user_id') ? $condition['user_id'] = trim(I('user_id')) : false;
        //I('order_statis_id') != '' ? $condition['order_statis_id'] = I('order_statis_id') : false; // 结算统计的订单

         //根据体彩号码排序
         if(I('winNumber')!='' && I('killGoods') != ''){
             $seckill = M('goods_seckill')->where(['id'=>I('killGoods')])->find();
             if(empty($seckill)){
                 $this->error('没有这个秒杀商品');
             }

             $order_goods = M('order_goods')->where(['goods_id'=>I('killGoods'),'prom_type'=>8])->select();   //购买这个商品的订单
             if($order_goods){
                 $order_idd = array_column($order_goods,'order_id');         //搜索的商品的所有的秒杀的订单
                 $condition['order_id'] = ['in',$order_idd];
                 //$condition['pay_status'] = 1;

                 $sort_order['master_order_sn'] ='desc';
             }
         }
         $sort_order['order_id'] ='asc';

         if($condition['order_statis_id'] > 0) unset($condition['add_time']);

        $count = M('order'.$select_year)->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();
        // dump($condition);
        //获取订单列表
        //$orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        $orderList = M('order'.$select_year)
            ->where($condition)
            ->field('master_order_sn,order_sn,consignee,mobile,goods_price,dj,order_status,pay_status,pay_name,prom_type,add_time,pay_time,store_id,order_id,is_winning')
            ->limit("{$Page->firstRow},{$Page->listRows}")
            ->order($sort_order)
            ->select();

         if(I('winNumber')!='' && I('killGoods') != '') {
             $stepSort = array();
             foreach ($orderList as $v) {
                 $stepSort[] = substr($v['master_order_sn'],-5);
             }

             $winNumber = I('winNumber');
             if(!is_numeric($winNumber)) return false;
             $nums = [];
             foreach($stepSort  as $value){
                 $nums[] = abs(($value - $winNumber));
             }
             array_multisort($nums, SORT_ASC, $orderList);
         }

        // $orderList['goods_price'] = M('orderGoods')->where(['order_id'=>$orderList['order_id']])->getField('goods_price');
        // dump(M('order')->getLastSql());die;
         $prom_type = C('PROM_TYPE');
         $this->assign('prom_type',$prom_type);
         $store_list = M('dealers')->where(['deleted'=>1,'status'=>1])->getField('id,name');
         $this->assign('store_list',$store_list);
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);// 赋值分页输出
        
        return $this->fetch();
    }

    /*
     * ajax 发货订单列表
    */
    public function ajaxdelivery(){
    	$orderLogic = new OrderLogic();
    	$condition = array();
    	I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
    	I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
    	$condition['order_status'] = array('in','1,2,4');
    	$shipping_status = I('shipping_status');
    	$condition['shipping_status'] = empty($shipping_status) ? array('neq',1) : $shipping_status;    	
    	$count = M('order')->where($condition)->count();
    	$Page  = new AjaxPage($count,10);
    	//搜索条件下 分页赋值
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key]   =   urlencode($val);
    	}
    	$show = $Page->show();
    	$orderList = M('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
    	$this->assign('orderList',$orderList);
    	$this->assign('page',$show);// 赋值分页输出
    	return $this->fetch();
    }

    /**
     * 订单详情
     * @param $order_id
     * @return mixed
     */
    public function detail($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $button = $orderLogic->getOrderButton($order);
        // 获取操作记录
        $select_year = getTabByOrderId($order_id);        
        // 获取操作记录
        $action_log = M('order_action'.$select_year)->where(array('order_id'=>$order_id))->order('log_time desc')->select();
        $express = Db::name('delivery_doc'.$select_year)->where("order_id" , $order_id)->select();  //发货信息（可能多个）
        //查找用户昵称
		if($action_log){
			$userIds = [];
			$sellerIds = [];
			foreach ($action_log as $actionKey => $actionVal){
				if($actionVal['user_type'] == 2){
					$userIds[$actionKey] = $actionVal['action_user'];
				}
				if($actionVal['user_type'] == 1){
					$sellerIds[$actionKey] = $actionVal['action_user'];
				}
			}
			if(count($userIds) > 0){
				$users = Db::name("users")->where(['user_id'=>['in',array_unique($userIds)]])->getField("user_id,nickname");
				$this->assign('users',$users);
			}
			if(count($sellerIds) > 0){
				$users = Db::name("seller")->where(['seller_id'=>['in',array_unique($sellerIds)]])->getField("seller_id,seller_name");
				$this->assign('sellers',$users);
			}
		}
        $this->assign('order',$order);
        $this->assign('action_log',$action_log);
        $this->assign('orderGoods',$orderGoods);
        $this->assign('express',$express);
        $split = count($orderGoods) >1 ? 1 : 0;
        foreach ($orderGoods as $val){
        	if($val['goods_num']>1){
        		$split = 1;
        	}
        }
        $this->assign('split',$split);
        $this->assign('button',$button);
        return $this->fetch();
    }
    
    public function refund_order_list(){
    	$condition = array();
    	I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
    	I('order_sn') != '' ? $condition['order_sn'] = trim(I('order_sn')) : false;
    	I('mobile') != '' ? $condition['mobile'] = trim(I('mobile')) : false;
    	$condition['shipping_status'] = 0;
    	$condition['order_status'] = 3;
    	$condition['pay_status'] = array('gt',0);
    	$count = M('order')->where($condition)->count();
    	$Page  = new Page($count,10);
    	//搜索条件下 分页赋值
    	foreach($condition as $key=>$val) {
    		if(!is_array($val)){
    			$Page->parameter[$key]   =   urlencode($val);
    		}
    	}
    	$show = $Page->show();
    	$orderList = M('order')->where($condition)->limit($Page->firstRow.','.$Page->listRows)->order('add_time DESC')->select();
    	$this->assign('orderList',$orderList);
    	$this->assign('page',$show);// 赋值分页输出
    	$this->assign('pager',$Page);
    	return $this->fetch();
    }
    
    /**
     * 退回用户金额(原路/余额退还)
     * @param unknown $order_id
     * @return \think\mixed
     */
    public function refund_order_info($order_id){
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	$orderGoods = $orderLogic->getOrderGoods($order_id);
    	$this->assign('order',$order);
    	$this->assign('orderGoods',$orderGoods);
    	return $this->fetch();
    }

    //处理取消订单  订单原路退款
    public function refund_order(){
    	$data = I('post.');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($data['order_id']);
    	if(!$order){
    		$this->error('订单不存在或参数错误');
    	}
        if($data['pay_status'] == 3) {
            if ($data['refund_type'] == 1) {
                //退到用户余额  8-25
                if(updateRefundOrder($order,1)){
                    $this->success('成功退款到账户余额');
                }else{
                    $this->error('退款失败');
                }
            }
            if ($data['refund_type'] == 0) {   
	           	
            	if ($order['pay_code'] == 'weixin' || $order['pay_code'] == 'alipay' || $order['pay_code'] == 'alipayMobile') {
            		if ($order['pay_code'] == 'weixin') {
            			include_once PLUGIN_PATH . "payment/weixin/weixin.class.php";
            			$payment_obj = new \weixin();
            			$refund_data = array('transaction_id' => $order['transaction_id'], 'total_fee' => $order['order_amount'], 'refund_fee' => $order['order_amount']);
            			$result = $payment_obj->payment_refund($refund_data);
            			if ($result['return_code'] == 'SUCCESS' ) {//&& $result['result_code' == 'SUCCESS']
                            if(updateRefundOrder($order)){
                                $this->success('支付原路退款成功');
                            }else{
                                $this->error('支付原路退款成功,余额支付部分退款失败');
                            }
            			}else{
            				$this->error('支付原路退款失败'.$result['return_msg']);
            			}
            		} else {
            			include_once PLUGIN_PATH . "payment/alipay/alipay.class.php";
            			$payment_obj = new \alipay();
            			$detail_data = $order['transaction_id'] . '^' . $order['order_amount'] . '^' . '用户申请订单退款';
                        $refund_data = array('batch_no' => date('YmdHi') .'o'.$order['order_id'], 'batch_num' => 1, 'detail_data' => $detail_data);
						$CommonOrderLogic = new \app\common\logic\OrderLogic();
						$CommonOrderLogic->alterReturnGoodsInventory($order);//取消订单后改变库存
            			$payment_obj->payment_refund($refund_data);
            		}
            	} else {
            		$this->error('该订单支付方式不支持在线退回');
            	}
		
            }
        }else{
    		M('order')->where(array('order_id'=>$order['order_id']))->save($data);
    		$this->success('拒绝退款操作成功');
    	}
    }
// 虚拟订单列表
    public function virtual_list(){
    header("Content-type: text/html; charset=utf-8");
exit("请联系TPshop官网客服购买高级版支持此功能");
    }
    
    public function virtual_info(){
    	$order_id = I('order_id');
        // 获取操作表
        $select_year = getTabByOrderId($order_id);           
    	$order = M('order'.$select_year)->where(array('order_id'=>$order_id))->find();
    	if($order['pay_status'] == 1){
    		$vrorder = M('vr_order_code')->where(array('order_id'=>$order_id))->select();
    		$this->assign('vrorder',$vrorder);
    	}
    	$order_goods = M('order_goods'.$select_year)->where(array('order_id'=>$order_id))->find();
    	$order_goods['commission_money'] = $order_goods['commission']*$order_goods['goods_price']*$order_goods['goods_num']/100;
    	$order_goods['virtual_indate'] = M('goods')->where(array('goods_id'=>$order_goods['goods_id']))->getField('virtual_indate');
        $order['order_status_detail'] = C('ORDER_STATUS')[$order['order_status']];
    	$this->assign('order',$order);
    	$this->assign('order_goods',$order_goods);
    	$store = M('store')->where(array('store_id'=>$order['store_id']))->find();
    	$this->assign('store',$store);
    	return $this->fetch();
    }

    
    /*
     * 价钱修改
     */
    public function editprice($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $this->editable($order);
        if(IS_POST){
        	$admin_id = session('admin_id');
            if(empty($admin_id)){
                $this->error('非法操作');
                exit;
            }
            $update['discount'] = I('post.discount');
            $update['shipping_price'] = I('post.shipping_price');
			$update['order_amount'] = $order['goods_price'] + $update['shipping_price'] - $update['discount'] - $order['user_money'] - $order['integral_money'] - $order['coupon_price'];
            $row = M('order')->where(array('order_id'=>$order_id))->save($update);
            if(!$row){
                $this->success('没有更新数据',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
                $this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }
            exit;
        }
        $this->assign('order',$order);
        return $this->fetch();
    }

    
    /**
     * 订单取消付款
     */
    public function pay_cancel($order_id){
    	if(I('remark')){
    		$data = I('post.');
    		$note = array('退款到用户余额','已通过其他方式退款','不处理，误操作项');
    		if($data['refundType'] == 0 && $data['amount']>0){
    			accountLog($data['user_id'], $data['amount'], 0,  '退款到用户余额');
    		}
    		$orderLogic = new OrderLogic();
			$admin_id = session('admin_id'); // 当前操作的管理员
    		$d = $orderLogic->orderActionLog($data['order_id'],'取消付款',$data['remark'].':'.$note[$data['refundType']],$admin_id);
    		if($d){
    			exit("<script>window.parent.pay_callback(1);</script>");
    		}else{
    			exit("<script>window.parent.pay_callback(0);</script>");
    		}
    	}else{
    		$order = M('order')->where("order_id=$order_id")->find();
    		$this->assign('order',$order);
    		return $this->fetch();
    	}
    }

    /**
     * 订单打印
     * @param int $id 订单id
     */
    public function order_print($order_id){
        $orderLogic = new OrderLogic();
        $order = $orderLogic->getOrderInfo($order_id);
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
        $orderGoods = $orderLogic->getOrderGoods($order_id);
        $shop = tpCache('shop_info');
        $this->assign('order',$order);
        $this->assign('shop',$shop);
        $this->assign('orderGoods',$orderGoods);
        return $this->fetch('print');
    }

    /**
     * 生成发货单
     */
    public function deliveryHandle(){
        $orderLogic = new OrderLogic();
		$data = I('post.');
		$res = $orderLogic->deliveryHandle($data);
		if($res){
			$this->success('操作成功',U('Admin/Order/delivery_info',array('order_id'=>$data['order_id'])));
		}else{
			$this->success('操作失败',U('Admin/Order/delivery_info',array('order_id'=>$data['order_id'])));
		}
    }

    
    public function delivery_info(){
    	$order_id = I('order_id');
    	$orderLogic = new OrderLogic();
    	$order = $orderLogic->getOrderInfo($order_id);
    	$orderGoods = $orderLogic->getOrderGoods($order_id);
    	$this->assign('order',$order);
    	$this->assign('orderGoods',$orderGoods);
		$delivery_record = Db::name('delivery_doc')->alias('d')->join('__SELLER__ s','s.seller_id = d.admin_id', 'LEFT')->where('d.order_id', $order_id)->select();
		$this->assign('delivery_record',$delivery_record);//发货记录
    	return $this->fetch();
    }
    
    /**
     * 发货单列表
     */
    public function delivery_list(){
        return $this->fetch();
    }
	
    /*
     * ajax 退货订单列表
     */
    public function ajax_return_list(){
        // 搜索条件        
        $order_sn =  trim(I('order_sn'));
        $order_by = I('order_by') ? I('order_by') : 'id';
        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
        $status =  I('status','','trim');       
        
        $where = " 1 = 1 ";       
        $order_sn && $where.= " and order_sn like '%$order_sn%' ";
        ($status === '') ? 'do nothing' : ($where.= " and status = '$status' ");
          
        $count = M('return_goods')->where($where)->count();
        $Page  = new AjaxPage($count,13);
        $show = $Page->show();
        $list = M('return_goods')->where($where)->order("$order_by $sort_order")->limit("{$Page->firstRow},{$Page->listRows}")->select();        
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr)){
            $goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
        }
        $store_list = M('store')->getField('store_id,store_name');        
        $this->assign('store_list',$store_list);
        $this->assign('goods_list',$goods_list);
        $this->assign('list',$list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);// 赋值分页输出
        return $this->fetch();
    }
    
    /**
     * 删除某个退换货申请
     */
    public function return_del(){
        $id = I('get.id');
        M('return_goods')->where("id = $id")->delete(); 
        $this->success('成功删除!');
    }
    
    /**
     * 退换货操作
     */
    public function return_info()
    {
        $id = I('id');
        $return_goods = M('return_goods')->where("id= $id")->find();
        if($return_goods['imgs'])            
             $return_goods['imgs'] = explode(',', $return_goods['imgs']);
        $user = M('users')->where("user_id = {$return_goods[user_id]}")->find();
        $goods = M('goods')->where("goods_id = {$return_goods[goods_id]}")->find();
        $type_msg = array('退换','换货');
        $status_msg = array('未处理','处理中','已完成');
        if(IS_POST)
        {
            $data['type'] = I('type');
            $data['status'] = I('status');
            $data['refund_mark'] = I('refund_mark');                                    
            $note ="退换货:{$type_msg[$data['type']]}, 状态:{$status_msg[$data['status']]},处理备注：{$data['remark']}";
            $result = M('return_goods')->where("id= $id")->save($data);    
            if($result)
            {        
            	$type = empty($data['type']) ? 2 : 3;
            	$where = " order_id = ".$return_goods['order_id']." and goods_id=".$return_goods['goods_id'];
            	M('order_goods')->where($where)->save(array('is_send'=>$type));//更改商品状态        
                $orderLogic = new OrderLogic();
				$admin_id = session('admin_id'); // 当前操作的管理员
                $log = $orderLogic->orderActionLog($return_goods[order_id],'退换货',$note,$admin_id);
                $this->success('修改成功!');            
                exit;
            }  
        }        
        
        $this->assign('id',$id); // 用户
        $this->assign('user',$user); // 用户
        $this->assign('goods',$goods);// 商品
        $this->assign('return_goods',$return_goods);// 退换货               
        return $this->fetch();
    }
    
    /**
     * 管理员生成申请退货单
     */
    public function add_return_goods()
   {                
            $order_id = I('order_id'); 
            $goods_id = I('goods_id');
                
            $return_goods = M('return_goods')->where("order_id = $order_id and goods_id = $goods_id")->find();            
            if(!empty($return_goods))
            {
                $this->error('已经提交过退货申请!',U('Admin/Order/return_list'));
                exit;
            }
            $order = M('order')->where("order_id = $order_id")->find();
            
            $data['order_id'] = $order_id; 
            $data['order_sn'] = $order['order_sn']; 
            $data['goods_id'] = $goods_id; 
            $data['addtime'] = time(); 
            $data['user_id'] = $order[user_id];            
            $data['remark'] = '管理员申请退换货'; // 问题描述            
            M('return_goods')->add($data);            
            $this->success('申请成功,现在去处理退货',U('Admin/Order/return_list'));
            exit;
    }

    public function order_log(){
        $OrderActionModel = new OrderAction();
//        $select_year = getTabByTime(I('add_time_begin')); // 表后缀
    	$begin = I('add_time_begin') ? strtotime(I('add_time_begin')) : strtotime("-3 month")+86400;
    	$end = I('add_time_end') ? strtotime(I('add_time_end')) : strtotime('+1 days');        
        
    	$condition = array();
//    	$log =  M('order_action'.$select_year);
    	if($begin && $end){
    		$condition['oa.log_time'] = array('between',"$begin,$end");
    	}
    	$admin_id = I('admin_id');
		if($admin_id >0 ){
			$condition['oa.action_user'] = $admin_id;
		}
    	$count = $OrderActionModel->alias('oa')->where($condition)->count();
    	$Page = new Page($count,20);    	 
    	$show = $Page->show();
    	$list = $OrderActionModel->where($condition)->alias('oa')
            ->field('oa.*,u.user_id,u.nickname')
            ->join('users u','oa.action_user = u.user_id','left')
            ->order('oa.action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	$this->assign('list',$list);
    	$this->assign('page',$show);   	
    	$admin = M('admin')->getField('admin_id,user_name');
        $this->assign('begin', date('Y-m-d',$begin));
        $this->assign('end', date('Y-m-d',$end));        
    	$this->assign('admin',$admin);    	
    	return $this->fetch();
    }

    /**
     * 检测订单是否可以编辑
     * @param $order
     */
    private function editable($order){
        if($order['shipping_status'] != 0){
            $this->error('已发货订单不允许编辑');
            exit;
        }
        return;
    }

    public function export_order()
    {
        //ini_set('memory_limit', '4096M');
        // set_time_limit(0);

    	//搜索条件
		$consignee = I('consignee');
		$order_sn =  I('order_sn');
		$timegap = I('timegap');
        $timegaps = I('timegaps');
		$order_status = I('order_status');
		$order_ids = I('order_ids');
        $pay_status = I('pay_status');
		$where = array();//搜索条件
//        $where['type'] = 1;
//		if($consignee){
//			$where['consignee'] = ['like','%'.$consignee.'%'];
//		}
//		if($order_sn){
//			$where['order_sn'] = $order_sn;
//		}
//        if($pay_status){
//            $where['pay_status'] = $pay_status;
//        }
//		if($order_status){
//			$where['order_status'] = $order_status;
//		}
        //下单时间
		if($timegap){
			$gap = explode('-', $timegap);
			$begin = strtotime($gap[0]);
			$end = strtotime($gap[1]);
			$where['pay_time'] = ['between',[$begin, $end]];
		}else{
            $begin = strtotime(I('add_time_begin'));
            $end = strtotime(I('add_time_end'));
        }

        //支付时间
        if($timegaps){
            $gaps = explode('-', $timegaps);
            $begins = strtotime($gaps[0]);
            $ends = strtotime($gaps[1]);
            $where['pay_time'] = ['between',[$begins, $ends]];
        }else{
            $begins = strtotime(I('pay_time_begin'));
            $ends = strtotime(I('pay_time_end'));
        }




        $keyType = I("keytype");
        $keywords = I('keywords','','trim');

        $consignee =  ($keyType && $keyType == 'consignee') ? $keywords : I('consignee','','trim');
        $consignee ? $where['consignee'] = array('like',trim("%$consignee%")) : false;

        //下单时间
        if($begin && $end){
            $where['add_time'] = array('between',"$begin,$end");
        }
        //支付时间
        if($begins && $ends){
            $where['pay_time'] = array('between',"$begins,$ends");
        }

        $store_name = ($keyType && $keyType == 'store_name') ? $keywords :  I('store_name','','trim');
        if($store_name)
        {
            $store_id_arr = M('dealers')->where("name like '%$store_name%'")->getField('id',true);
            if($store_id_arr)
            {
                $where['store_id'] = array('in',$store_id_arr);
            }else{
                $where['store_id'] = '01';
            }
        }
        //$where['prom_type'] = array('lt',5);
        $order_sn = ($keyType && $keyType == 'order_sn') ? $keywords : I('order_sn') ;
        $order_sn ? $where['master_order_sn'] = trim($order_sn) : false;
        I('order_status') != '' ? $where['order_status'] = I('order_status') : false;
        I('pay_status') != '' ? $where['pay_status'] = I('pay_status') : false;
        I('pay_code') != '' ? $where['pay_code'] = I('pay_code') : false;
        I('shipping_status') != '' ? $where['shipping_status'] = I('shipping_status') : false;
        I('user_id') ? $where['user_id'] = trim(I('user_id')) : false;
        //I('order_statis_id') != '' ? $where['order_statis_id'] = I('order_statis_id') : false; // 结算统计的订单
        $where['type'] = 1;

        if($order_ids){
			$where['order_id'] = ['in', $order_ids];
		}

        //根据体彩号码排序
        if(I('winNumber')!='' && I('killGoods') != ''){
            $seckill = M('goods_seckill')->where(['id'=>I('killGoods')])->find();
            if(empty($seckill)){
                $this->error('没有这个秒杀商品');
            }

            $order_goods = M('order_goods')->where(['goods_id'=>I('killGoods'),'prom_type'=>8])->select();   //购买这个商品的订单
            if($order_goods){
                $order_idd = array_column($order_goods,'order_id');         //搜索的商品的所有的秒杀的订单
                $condition['order_id'] = ['in',$order_idd];
                //$condition['pay_status'] = 1;

                $sort_order['master_order_sn'] ='desc';
            }
        }

		$region	= Db::name('region')->cache(true)->getField('id,name');

        $orderList = Db::name('order')->field("master_order_sn,consignee,mobile,goods_price,order_amount,pay_name,order_id,pay_status,shipping_status,FROM_UNIXTIME(pay_time,'%Y-%m-%d %H:%i') as create_time,FROM_UNIXTIME(add_time,'%Y-%m-%d %H:%i') as create_time_add")->where($where)->order('order_id')->select();

        $strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="100">下单时间</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">支付时间</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
//    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品数量</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">商品信息</td>';
    	$strTable .= '</tr>';
    	
    	foreach($orderList as $k=>$val){
    		$strTable .= '<tr>';
    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['master_order_sn'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time_add'].' </td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';
			$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['consignee'].'</td>';
//			$strTable .= '<td style="text-align:left;font-size:12px;">'."{$region[$val['province']]},{$region[$val['city']]},{$region[$val['district']]},{$region[$val['twon']]}{$val['address']}".' </td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_name'].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->pay_status[$val['pay_status']].'</td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->shipping_status[$val['shipping_status']].'</td>';    
    		$orderGoods = D('order_goods')->where('order_id='.$val['order_id'])->select();
    		$strGoods="";
			$goods_num = 0;
    		foreach($orderGoods as $goods){
				$goods_num = $goods_num + $goods['goods_num'];
    			$strGoods .= "商品编号：".$goods['goods_sn']." 商品名称：".$goods['goods_name'];
    			if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
    			$strGoods .= "<br />";
    		}
    		unset($orderGoods);
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$goods_num.' </td>';
    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
    		$strTable .= '</tr>';
    	}
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'order');
        exit();
    }
    
    /**
     * 退货单列表
     */
    public function return_list(){
        return $this->fetch();
    }

    
    /**
     * 选择搜索商品
     */
    public function search_goods()
    {
    	$brandList =  M("brand")->select();
    	$categoryList =  M("goods_category")->select();
    	$this->assign('categoryList',$categoryList);
    	$this->assign('brandList',$brandList);   	
    	$where = ' is_on_sale = 1 ';//搜索条件
    	I('intro')  && $where = "$where and ".I('intro')." = 1";
    	if(I('cat_id')){
    		$this->assign('cat_id',I('cat_id'));    		
            $grandson_ids = getCatGrandson(I('cat_id')); 
            $where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
                
    	}
        if(I('brand_id')){
            $this->assign('brand_id',I('brand_id'));
            $where = "$where and brand_id = ".I('brand_id');
        }
    	if(!empty($_REQUEST['keywords']))
    	{
    		$this->assign('keywords',I('keywords'));
    		$where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
    	}  	
    	$goodsList = M('goods')->where($where)->order('goods_id DESC')->limit(10)->select();
                
        foreach($goodsList as $key => $val)
        {
            $spec_goods = M('spec_goods_price')->where("goods_id = {$val['goods_id']}")->select();
            $goodsList[$key]['spec_goods'] = $spec_goods;            
        }
    	$this->assign('goodsList',$goodsList);
    	return $this->fetch();
    }
    
    public function ajaxOrderNotice(){
        $order_amount = M('order')->where(array('order_status'=>0))->count();
        echo $order_amount;
    }

    /**
     * 删除订单日志
     */
    public function delOrderLogo(){
        $ids = I('ids');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！",'url'  =>'']);
        $order_ids = rtrim($ids,",");
        $res = Db::name('order_action')->whereIn('order_id',$order_ids)->delete();
        if($res !== false){
            $this->ajaxReturn(['status' => 1,'msg' =>"删除成功！",'url'  =>'']);
        }else{
            $this->ajaxReturn(['status' => -1,'msg' =>"删除失败",'url'  =>'']);
        }
    }

    /**
     * 设置这个用户为中奖用户
     * @Autor: 胡宝强
     * Date: 2018/9/7 10:46
     */
    public function winning(){
        $order_id = I('order_id');
        $order = M('order')->where(['order_id'=>$order_id])->find();
        if(empty($order)){
            $this->error('该订单不存在', U('Admin/Carorder/index'));
        }
        $order_goods = M('order_goods')->where(['order_id'=>$order_id])->field('goods_id')->find();
        if(empty($order_goods)){
            $this->error('该订单不存在', U('Admin/Carorder/index'));
        }

        //查询这个商品是否已经设置了中奖
        $goodsSeckill = M('goods_seckill')->where(['id'=>$order_goods['goods_id']])->find();
        if($goodsSeckill['user_id'] != 0){
            $this->success('这个商品已经设置了中奖用户了', U('Admin/Carorder/index'));exit;
        }

        $seckill = M('goods_seckill')->where(['id'=>$order_goods['goods_id']])->save(['user_id'=>$order['user_id']]);
        $winning = M('order')->where(['order_id'=>$order_id])->save(['is_winning'=>1]);     //修改这个订单为中奖的状态
        if($seckill && $winning){
            $this->add_return($order_id);
            $this->success('设置中奖用户成功', U('Admin/Carorder/index'));
        }else{
            $this->error('设置中奖用户失败', U('Admin/Carorder/index'));
        }
    }

    /**
     * 操作处理退款
     * @Autoh: 胡宝强
     * Date: 2018/7/19 17:39
     * @param $rec_id       子订单id
     * @param $order        订单信息
     * @param $desc         退款描述
     */
    public function add_return($order_id){
        //获取到这个订单是购买的哪个商品
        $prom_id = M('order_goods')->where(['order_id'=>$order_id])->field('prom_id')->find();
        //获取到购买这个商品的有哪些订单
        $order_goods = M('order_goods')->where(['prom_type'=>8,'prom_id'=>$prom_id['prom_id']])->select();
        //所有符合条件的订单id
        $order_ids = array_column($order_goods,'order_id');
        //查询所有的订单信息
        $order = M('order')->where(['order_id'=>['in',$order_ids],'pay_status'=>1,'refund_status'=>0,'is_winning'=>0])->select();
        $order_goodsids = array_column($order,'order_id');
        $order_goodss = M('order_goods')->alias('g')
            ->join('tp_order o','g.order_id=o.order_id')
            ->field('g.rec_id,o.order_id,o.order_sn,o.master_order_sn,g.goods_id,g.goods_num,o.user_id,o.store_id,o.type,g.spec_key,g.final_price,g.spec_key_name,g.goods_name')
            ->where(['o.order_id'=>['in',$order_goodsids]])
            ->select();

        Db::startTrans();
        try{

            $goods = M('goods')->where(['goods_id'=>$order_goods['goods_id']])->field('type')->find();
            $data['addtime'] = time();
            $data['goods_image'] = get_table_name('goods','goods_id',$order_goods['goods_id'],'original_img');
            $data['spec_key_name'] = $order_goods['spec_key_name'];
            $data['goods_name'] = $order_goods['goods_name'];
            $data['goods_type'] = $goods['type'];   //1汽车的退款 2 配件的退款
            $orderGoodsAllData = [];

            foreach($order_goodss as $key=>$value){
                $orderGoodsData['rec_id'] = $value['rec_id'];
                $orderGoodsData['order_id'] = $value['order_id'];
                $orderGoodsData['order_sn'] = $value['order_sn'];
                $orderGoodsData['master_order_sn'] = $value['master_order_sn'];
                $orderGoodsData['goods_id'] = $value['goods_id'];
                $orderGoodsData['goods_num'] = $value['goods_num'];
                $orderGoodsData['type'] = 1;
                $orderGoodsData['reason'] = '未中奖金额返还';
                $orderGoodsData['status'] = 0;
                $orderGoodsData['user_id'] = $value['user_id'];
                $orderGoodsData['store_id'] = $value['store_id'];
                $orderGoodsData['spec_key'] = $value['spec_key'];
                $orderGoodsData['refund_integral'] = 0;
                $orderGoodsData['refund_money'] = $value['refund_money'];
                $orderGoodsData['addtime'] = time();
                $orderGoodsData['goods_image'] = get_table_name('goods','goods_id',$value['goods_id'],'original_img');
                $orderGoodsData['spec_key_name'] = $value['spec_key_name'];
                $orderGoodsData['goods_name'] = $value['goods_name'];
                $orderGoodsData['goods_type'] = $value['type'];
                array_push($orderGoodsAllData, $orderGoodsData);
            }
            $result = Db::name('return_goods')->insertAll($orderGoodsAllData);

            if($result){
                //修改子订单状态
                foreach($order_goodss as $k=>$v){
                    $arr['is_send'] = 3;
                    $res = M('order_goods')->where(['rec_id'=>$v['rec_id'],'order_id'=>$v['order_id']])->save($arr);
                    $is_end = M('order_goods')->where(['is_send'=>['neq',3],'order_id'=>$v['order_id']])->select();
                    if(!$is_end){
                        M('order')->where(['order_id'=>$v['order_id']])->save(['refund_status'=>1]);
                    }
                    logOrder($v['order_id'], '用户申请退款', '申请退款', $v['user_id'], 2);
                }
                Db::commit();
                return $result;
            }

        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }


    }

}
