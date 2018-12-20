<?php

/**
 * 商品动作类
 * @Author  蒋峰
 * @date  2018/7/12 0012 17:47
 * @FunctionName _initialize
 * @UpdateTime  date
 */

namespace app\api\model;

use function GuzzleHttp\Psr7\str;
use think\Model;
use think\Request;
use app\common\logic\PointLogic;

class UserSignLog extends Model {

    /**
     * [签到]
     * @Auther 蒋峰
     * @DateTime
     * @param $user_id
     * @param $sing_time
     * @param $month
     * @return bool
     */
    public function toSign($user_id,$sing_time,$month)
    {
        $data = $this->where('sing_time',$sing_time)->where('user_id', $user_id)->find();
        if($data) return false;
//        $sing_time+24*3600
        $end_time = $this->where(['user_id'=>$user_id])->order('create_time desc')->limit(1)->find();
        $model= new PointLogic();
        if(!empty($end_time)){

            $time = strtotime(date('Y-m-d',time()))- strtotime(date("Y-m-d",$end_time['create_time']));
            $times = $time/86400;
            if($times>1){
                $arr['count'] = 1;
            }else{
                if($end_time['count'] == 7){
                    $arr['count'] = 1;
                }else{
                    $arr['count'] = $end_time['count'] + 1;
                }
            }
            $arr['user_id'] =$user_id;
            $arr['sing_time'] =$sing_time;
            $arr['create_time'] =time();
            $arr['this_month'] =$month;
            $data = $this->insert($arr);
            $model->signPoint($user_id,$arr['count']); //签到获取积分
        }else{
            $data = [
                'user_id' => $user_id,
                'sing_time' => $sing_time,
                'create_time' => time(),
                'this_month' => $month,
                'count' =>1
            ];
            $data = $this->insert($data);
            $model->signPoint($user_id,1); //签到获取积分
        }

        if($data) return true;
        return false;
    }

    /**
     * [查询签到记录]
     * @Auther 蒋峰
     * @DateTime
     * @param $user_id
     * @param $sing_time
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function querySign($user_id,$sing_time)
    {
        $sing_time = date('Y/m',strtotime($sing_time));
        return $this->where('user_id', $user_id)->where('sing_time',['like', "{$sing_time}/__"])->field('sing_time as date,concat(\'mark1\') as className')->select();
    }

    public function isSign($user_id)
    {
        return $this->where('user_id', $user_id)->where('sing_time',date('Y/m/d'))->count();
    }
}
