<?php
/**
 * 积分操作表
 * 积分转换：100积分=1元
 * 1.用户使用现金消费并核销券码、确认收货后获得实际支付现金的20%的积分（例如：支付购车定金5000元，则可获得积分5000*20%=1000积分（10元））；
 * 2.用户将商品链接或者店铺连接分享给好友、朋友圈都可获得100个积分，每天上限为5次（100积分=1元，分享5次则可获得500积分=5元）；
 * 3.每天签到即可领积分50个，连续7天签到则可额外获得500积分，第8天则清零（若用户连续签到7天则可获得积分50*7+500=850积分=8.5元）；
 * 4.邀请好友关注公众号，推荐人可得200个积分，一天推荐5个朋友封顶（200积分=2元，若用户推荐5则可获得积分200*5=1000积分=10元）；
 * 5.邀请朋友注册众泰车商城，推荐人可获得800个积分=8元；
 * 6.用户固定得分：用户认证成功得1000积分=10元；
 * 7.用户购物评价后可获得200个积分=2元；
 * 8.用户收藏商品后即可获得50个积分=0.5元。
 */

namespace app\common\logic;

use think\AjaxPage;
use think\Model;
use think\Db;


class PointLogic extends Model
{
    /**
     * 往用户表中添加积分
     * @Autor: 胡宝强
     * Date: 2018/8/14 15:58
     * @param $user_id      用户ID
     * @param $point        用户要改变的积分
     */
    public function userPoint($user_id,$point){
        M('users')->where(['user_id'=>$user_id])->setInc('pay_points',$point);
    }

    /**
     * 下单购买获得积分
     * @Autor: 胡宝强
     * Date: 2018/8/15 10:01
     * @param $user_id          用户ID
     * @param $order_id         订单ID
     * @param $money            订单金额
     */
	public function payGetPoint($user_id,$order_id,$money){
        $point = $money*0.2;
        $this->userPoint($user_id,$point);
        integral_log($user_id,2,$point,6,'购买获得',$order_id,'');
    }

    /**
     * 收货成功后获得积分
     * @Autor: 胡宝强
     * Date: 2018/8/22 11:47
     * @param $user_id          用户id
     * @param $order_id         订单id
     * @param $rec_id           子订单id
     * @param $money            订单的金额
     */
    public function deliveryPoint($user_id,$order_id,$rec_id,$money=0){
        $point = $money * 0.2;
        $this->userPoint($user_id,$point);
        integral_log($user_id,2,$point,3,'确认收货',$order_id,$rec_id);
    }

    /**
     * 分享好友
     * @Autoh: 胡宝强
     * Date: 2018/8/7 11:18
     */
    public function goodsFriend($user_id){
        $point = 100;
        $this->userPoint($user_id,$point);
        integral_log($user_id,2,$point,9,'分享赠送','','');
    }

    /**
     * 收藏商品活动积分
     * @Autor: 胡宝强
     * Date: 2018/8/14 15:36
     * @param $user_id      用户ID
     * @param $goods_id     商品ID
     */
    public function collectionPoint($user_id,$goods_id){
        $point = 50;
        $arr = M('collection')->where(['user_id'=>$user_id,'goods_id'=>$goods_id])->find();
        if(!$arr){
            //没有收藏过这个商品
            $this->userPoint($user_id,$point);
            integral_log($user_id,2,$point,2,'收藏赠送','','');
        }
    }

    /**
     * 用户评价后获得积分
     * @Autor: 胡宝强
     * Date: 2018/8/14 17:34
     * @param $user_id         用户ID
     * @param $order_id        主订单ID
     * @param $rec_id          子订单ID
     */
    public function commentPoint($user_id,$order_id,$rec_id){
        $point = 200;
        $this->userPoint($user_id,$point);
        integral_log($user_id,2,$point,3,'评论赠送',$order_id,$rec_id);
    }

    /**
     * 签到获取积分
     * @Autor: 胡宝强
     * Date: 2018/8/14 19:04
     * @param $user_id      用户ID
     */
    public function signPoint($user_id,$count){
        if($count == 7){
            $point = 550;
        }else{
            $point = 50;
        }
        $this->userPoint($user_id,$point);
        integral_log($user_id,2,$point,4,'签到赠送','','');
    }

    /**
     * 邀请好友注册
     * @Autor: 胡宝强
     * Date: 2018/8/14 20:10
     * @param $user_id      用户id
     */
    public function invitationPoint($user_id){
        $point = 800;
        $this->userPoint($user_id,$point);
        integral_log($user_id,2,$point,5,'邀请好友注册赠送','','');
    }
}