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
 * 发票控制器
 * Author: 545
 * Date: 2017-10-23
 */

namespace app\seller\controller;
use think\Db;
use think\Page;

class Invoice extends Base {
    /*
     * 初始化操作
     */
    public $store_id;
    
    public function _initialize() {
        parent::_initialize();
        C('TOKEN_ON', false); // 关闭表单令牌验证       
    }

    /*
     * 发票列表
     */

    public function index() {
    /*code_14发票模块逻辑代码*/
        $M = M('invoice');
        $begin   = I("add_time_begin");
        $end     = I("add_time_end");
        $map['i.store_id'] = STORE_ID;
        if(!empty( $begin )&&!empty( $end )){
            $this->assign('add_time_begin', $begin);
            $this->assign('add_time_end', $end);
            $map['atime'] = array('between', array(strtotime($begin), strtotime($end)));
        }  
        $status=I('status');
        ($status>=0) && $map['status'] = I('status');       
        ($order_sn=I('order_sn'))&&$map['o.order_sn']=['like', '%'.$order_sn.'%'];
        $this->assign('status', $status);
        // B. 开始查询
        $count = $M->alias('i')->where($map)->join( c('prefix'). 'order o','i.order_id=o.order_id','left')->count();
        // B.2 开始分页
        $Page = new Page($count, 15);
        $show = $Page->show();
        $field=['i.*','o.order_id','o.order_sn'];
        $list = $M->alias('i')->field($field)->where($map)->limit($Page->firstRow . ',' . $Page->listRows)
                ->join( c('prefix').'order o','i.order_id=o.order_id','left')->order('invoice_id desc')->select();
        //C.1 联表
        foreach ($list as $key => $val) {
            $val['nickname'] = M('users')->cache(true)
                    ->where('user_id = ' . $val['user_id'])
                    ->getfield('nickname');
            $val['store_name'] = M('store')->cache(true)
                    ->where('store_id = ' . $val['store_id'])
                    ->getfield('store_name');
            $invoice_list[] = $val;
        }
        //待开发票
        $this->assign('wait',  M('invoice')->where(['status'=>0,'store_id'=>STORE_ID])->count());       
        //累计开发票数
        $this->assign('total',  M('invoice')->where(['status'=>1,'store_id'=>STORE_ID])->count());
        $this->assign('page', $show);
        $this->assign('pager', $Page);
        $this->assign('list', $invoice_list);
        return $this->fetch();
	/*code_14发票模块逻辑代码*/
    }
    
    //开票时间
    function changetime(){
        /*code_14发票模块逻辑代码*/
        if(IS_AJAX){
            $invoice_id=I('invoice_id');
            empty($invoice_id)&&$this->ajaxReturn(['status' => -1, 'msg' => '', 'result' =>''] );
            $map['invoice_id']=$invoice_id;
            $map['store_id']  =STORE_ID;
            (M('invoice')->where($map)->save(['ctime'=>time()]))?$status=1:$status=-1;
            $result = ['status' => $status, 'msg' => '', 'result' =>''];
            $this->ajaxReturn($result);
        }
	/*code_14发票模块逻辑代码*/
    }
}
