<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\homeapi\controller;
use app\api\model\Users as user;
use app\api\model\UserSignLog;
use think\Request;
use app\common\model\Picture;
use app\common\logic\PointLogic;

class Users extends Base{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 积分日志
     * @Autor: 胡宝强
     * Date: 2018/8/30 15:51
     */
    public function integralLog($user_id, $page, $page_num=10)
    {
        $offset=$page_num*($page-1);
        $limit=$offset.",".$page_num;
        $data = M('integral_log')
            ->order('create_time desc')
            ->where(['user_id'=>$user_id])
            ->limit($limit)
            ->field('status,integral,create_time,type_name')
            ->select();
        return $data;
    }


}
