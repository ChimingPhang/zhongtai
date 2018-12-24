<?php
/*
 * 车系
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;
use app\common\logic\PointLogic;

class Users extends Model
{

    protected $table = 'users';
    protected $collection_table = 'collection';
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
     * 获取用户的信息
     * @Autoh: 胡宝强
     * Date: 2018/7/17 20:15
     * @param $user_id      用户id
     */
    public function get_userinfo($user_id){
        $data = M($this->table)->where(['user_id'=>$user_id])->field('reg_time,head_pic,mobile,pay_points')->find();
        $data['reg_time'] = $this->get_reg_time($data['reg_time']);
        if($data['head_pic'] == null) $data['head_pic'] = '';
        return $data;
    }

    /**
     * 获取用户的信息
     * @Autoh: 胡宝强
     * Date: 2018/7/17 20:15
     * @param $user_id      用户id
     */
    public function get_user($user_id, $fields){
        $fields = implode(',', $fields);
        $data = M($this->table)->where(['user_id'=>$user_id])->field($fields)->find();
        $data['reg_time'] = $this->get_reg_time($data['reg_time']);
        if($data['head_pic'] == null) $data['head_pic'] = '';
        return $data;
    }

    /**
     * 获取用户注册到现在的时间
     * @Autoh: 胡宝强
     * Date: date
     * @param $time   用户注册的时间
     * @return float
     */
    public function get_reg_time($time){
        $now_time = time();
        return ceil(($now_time-$time)/(3600*24));
    }

    /**
     * @Autoh: 胡宝强
     * Date: date
     * @param $user_id
     * @param int $page
     * @return mixed
     */
    public function get_collection($user_id,$page=1){
        $page_num=10;//每页的数据
        $offset=$page_num*($page-1);
        $limit=$offset.",".$page_num;
        $data = M($this->collection_table)->alias('c')
            ->join('tp_goods g','c.goods_id = g.goods_id','left')
            ->where(['c.user_id'=>$user_id,'g.is_on_sale'=>1,'c.deleted'=>0])
            ->field('goods_name,deposit_price,c.price,shop_price,c.point,c.id,original_img,c.goods_id,g.label,g.type,c.exchange_integral')
            ->limit($limit)
            ->select();
        return $data;
    }

    /**

     * 删除收藏
     * @Autoh: 胡宝强
     * Date: 2018/7/18 13:56
     * @param $id   要删除的收藏的ID
     */
    public function del_collection($user_id,$id)
    {
        return M($this->collection_table)->where(['id' => ['in', $id], 'user_id' => $user_id])->save(['deleted'=>1]);
    }

     /* [商品收藏|取消收藏]
     * @Auther 蒋峰
     * @DateTime
     * @param int $goods_id
     * @param int $user_id
     */
    public function collectSave($goods_id = 0, $user_id = 0,$exchange_integral = 0)
    {
        $data = M($this->collection_table)->where(['goods_id'=>$goods_id,'user_id'=>$user_id,'exchange_integral'=>$exchange_integral,'deleted'=>0])->select();
        if($data){
            //如果有数值就取消收藏
            $res = M($this->collection_table)->where(['goods_id'=>$goods_id,'user_id'=>$user_id,'exchange_integral'=>$exchange_integral])->save(['deleted'=>1]);
            return 2;
        }else{
            //收藏商品
            $arr['goods_id'] = $goods_id;
            $arr['user_id'] = $user_id;
            $arr['exchange_integral'] = $exchange_integral;
            $deleted = M($this->collection_table)->where($arr)->getField('deleted');
            if($deleted == 1){
                M($this->collection_table)->where($arr)->save(['create_time'=>date('Y-m-d H:i:s'),'deleted'=>0]);
            }else{
                $model= new PointLogic();
                $model->collectionPoint($user_id,$goods_id);  //收藏获取积分
                $goods = M('goods')->where(['goods_id'=>$goods_id])->field('shop_price,deposit_price,exchange_integral,integral,integral_money,moren_integral,type')->find();
                //判断收藏的商品是什么类型的商品
                if($goods['type'] == 1){
                    //汽车
                    if($exchange_integral == 0){
                        //纯金额
                        $res['price'] = $goods['deposit_price'];
                        $res['point'] = 0;
                    }elseif($exchange_integral == 1){
                        //积分和金额
                        $res['price'] = $goods['integral_money'];
                        $res['point'] = $goods['integral'];
                    }elseif($exchange_integral == 2){
                        //纯积分
                        $res['price'] = 0;
                        $res['point'] = $goods['integral'];
                    }
                }else{
                    //配件
                    if($exchange_integral == 0){
                        //纯金额
                        $res['price'] = $goods['shop_price'];
                        $res['point'] = 0;
                    }elseif($exchange_integral == 1){
                        //积分和金额
                        $res['price'] = $goods['shop_price'];
                        $res['point'] = $goods['moren_integral'];
                    }elseif($exchange_integral == 2){
                        //纯积分
                        $res['price'] = 0;
                        $res['point'] = $goods['moren_integral'];
                    }
                }

                $res['goods_id'] = $goods_id;
                $res['user_id'] = $user_id;
                $res['exchange_integral'] = $exchange_integral;
                $res['create_time'] = date('Y-m-d H:i:s');
                M($this->collection_table)->add($res);
            }
            return 1;
        }
    }

    /**
     * [通过token获取user_id]
     * @Auther 蒋峰
     * @DateTime
     * @param $token
     * @return mixed
     */
    public function getUserOnToken($token)
    {
        $user_id = M($this->table)->where('token', $token)->getField('user_id');
        return $user_id ? $user_id : 0;
    }
}
