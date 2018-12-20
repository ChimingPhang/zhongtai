<?php
/*
 * 售后服务
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;
use think\Db;

class Sale extends Model
{

    protected $table = 'order_goods';
    protected $request;//请求的参数

    /**
     * 初始化参数
     * @Autoh: 胡宝强
     * Date: 2018/7/15 12:23
     */
    public function _initialize()
    {
        $request = Request::instance();//获取当前请求的参数
        $this->request = $request;
    }

    /**
     * 显示要添加退款内容时的商品信息
     * @Autoh: 胡宝强
     * Date: 2018/7/19 17:03
     * @param $rec_id       子订单id
     * @param $order_id     主订单id
     */
    public function show_sale($rec_id,$order_id){
        $data = M('order_goods')
            ->where(['rec_id'=>$rec_id])
            ->field('goods_id,goods_name,goods_num,final_price,spec_key_name')
            ->find();
        //$goods = M('goods')->where(['goods_id'=>$data['goods_id']])->field('original_img')->find();
        $arr['price'] = $data['final_price'] * $data['goods_num'];
        $arr['original_img'] = goods_thum_images($data['goods_id'],200,150);
        $arr['goods_num'] = $data['goods_num'];
        $arr['goods_sku'] = $data['spec_key_name'];
        return $arr;
    }

    /**
     * 操作处理退款
     * @Autoh: 胡宝强
     * Date: 2018/7/19 17:39
     * @param $rec_id       子订单id
     * @param $order        订单信息
     * @param $desc         退款描述
     */
    public function add_return($rec_id,$order,$desc = ''){
        Db::startTrans();
        try{
            $order_goods = M('order_goods')->where(['rec_id'=>$rec_id])->find();
            $goods = M('goods')->where(['goods_id'=>$order_goods['goods_id']])->field('type')->find();
            $data['rec_id'] = $rec_id;
            $data['order_id'] = $order['order_id'];
            $data['order_sn'] = $order['order_sn'];
            $data['master_order_sn'] = $order['master_order_sn'];
            $data['goods_id'] = $order_goods['goods_id'];
            $data['goods_num'] = $order_goods['goods_num'];
            $data['type'] = 1; // 0只退款 1 退货退款
            $data['reason'] = $desc;
            $data['status'] = 0; //待审核
            $data['user_id'] = $order['user_id'];
            $data['store_id'] = $order['store_id'];  //经销商id
            $data['spec_key'] = $order_goods['spec_key'];
            $data['refund_integral'] = $order_goods['pay_integral'] * $order_goods['goods_num'];//要退的积分
            $data['refund_money'] = $order_goods['final_price'];//退的金额
            $data['addtime'] = time();
            $data['goods_image'] = get_table_name('goods','goods_id',$order_goods['goods_id'],'original_img');
            $data['spec_key_name'] = $order_goods['spec_key_name'];
            $data['goods_name'] = $order_goods['goods_name'];
            $data['goods_type'] = $goods['type'];   //1汽车的退款 2 配件的退款
            $result = Db::name('return_goods')->add($data);
            //修改子订单状态
            $arr['is_send'] = 3;
            $res = M('order_goods')->where(['rec_id'=>$rec_id,'order_id'=>$order['order_id']])->save($arr);

            if($res){
                $is_end = M('order_goods')->where(['is_send'=>['neq',3],'order_id'=>$order['order_id']])->select();
                if(!$is_end){
                    M('order')->where(['order_id'=>$order['order_id']])->save(['refund_status'=>1]);
                }
                Db::commit();
            }

            logOrder($order['order_id'], '用户申请退款', '申请退款', $order['user_id'], 2);
            return $result;

        }catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }


    }

    /**
     * 显示退款列表
     * @Autoh: 胡宝强
     * Date: 2018/7/19 18:24
     * @param $user_id      用户id
     * @param $page         分页
     */
    public function get_list($user_id,$page){
        $where = array('user_id'=>$user_id);
        $page_num=10;//每页的数据
        $offset=$page_num*($page-1);
        $limit=$offset.",".$page_num;

        $list = M('return_goods')
            ->where($where)
            ->field('id,rec_id,order_id,master_order_sn as order_sn,goods_id,goods_num,goods_name,goods_image,spec_key,spec_key_name,goods_type')
            ->order("id desc")
            ->limit($limit)
            ->select();
        return $list;
    }

    /**
     * 退款详情
     * @Autoh: 胡宝强
     * Date: 2018/7/19 20:11
     * @param $id       退款id
     * @param $user_id  用户id
     */
    public function details($id,$user_id){
        $data = M('return_goods')
            ->where(['id'=>$id,'user_id'=>$user_id])
            ->field('status,refund_money,addtime,refund_time,checktime,receivetime,canceltime,reason,goods_num,goods_image,goods_name,spec_key_name')
            ->find();
        //-2用户取消-1不同意0待审核1通过2已发货3待退款4换货完成5退款完成6申诉仲裁
        switch($data['status']){
            case '-2' :   $data['type_name'] = '取消退款';
                          $data['return_time'] = $data['canceltime'];
                          break;
            case '-1' :   $data['type_name'] = '未同意退款';
                          $data['return_time'] = $data['checktime'];
                          break;
            case '0' :    $data['type_name'] = '待审核';
                          $data['return_time'] = $data['addtime'];
                          break;
            case '1' :    $data['type_name'] = '通过退款';
                          $data['return_time'] = $data['checktime'];
                          break;
            case '2' :    $data['type_name'] = '商品已发出';
                          $data['return_time'] = $data['addtime'];
                          break;
            case '3' :    $data['type_name'] = '待退款';
                          $data['return_time'] = $data['checktime'];
                          break;
            case '4' :    $data['type_name'] = '换货完成';
                          $data['return_time'] = $data['receivetime'];
                          break;
            case '5' :    $data['type_name'] = '退款完成';
                          $data['return_time'] = $data['refund_time'];
                          break;
        }
        return $data;
    }





}
