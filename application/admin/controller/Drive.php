<?php
/**
 *预约试驾
 * index        预约列表
 *
 */

namespace app\admin\controller;

use think\Page;
use think\Loader;
use think\Db;

class Drive extends Base{

    public $table = 'appointment_drive';
    /**
     * 预约列表
     * @Autoh: 胡宝强
     * Date: 2018/8/6 17:59
     */
    public function index(){
        $model =  M($this->table);
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
        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }
        $province = I('province/d');
        $city = I('city/d');
        if($province) $map['province'] = $province;
        if($city) $map['city'] = $city;
        $dealers_name = I('dealers_name');
        if($dealers_name) $map['dealers_name'] = array('like',"%$dealers_name%");
        $name = I('name');
        if($name) $map['name'] = array('like',"%$name%");
        $mobile = I('mobile');
        if($mobile) $map['mobile'] = $mobile;
        $count = $model->where($map)->count();
        $Page = new Page($count,10);
        $list = $model->where($map)->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('list',$list);
        $show = $Page->show();
        $this->assign('page',$show);
        $this->assign('pager',$Page);

        //地区显示
        $province = M('region')->where(['level'=>1])->select();
        $this->assign('province',$province);

        //显示时间
        $begin = date('Y-m-d', strtotime("-12 month"));//30天前
        $end = date('Y-m-d', strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);
        $this->assign('add_time_begin',date('Y-m-d', strtotime("-12 month")+86400));
        $this->assign('add_time_end',date('Y-m-d', strtotime('+1 days')));
        return $this->fetch();
    }
}