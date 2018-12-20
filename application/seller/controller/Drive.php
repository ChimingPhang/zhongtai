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
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace app\seller\controller;

use app\seller\logic\GoodsCategoryLogic;
use app\seller\logic\GoodsLogic;
use think\Db;
use think\Page;
use think\AjaxPage;
use think\Loader;

class Drive extends Base
{

    /**
     *  预约试驾列表
     */
    public function index()
    {
        checkIsBack();
        //车系
        $category1 = M('goods_category')->where(['is_show'=>1,'parent_id'=>0])->select();

        $this->assign('category',$category1);
        return $this->fetch('goodsList');
    }






    /**
     *  商品列表
     */
    public function ajaxGoodsList()
    {
        $begin = strtotime(I('add_time_begin'));
        $end   = strtotime(I('add_time_end'));
        if ($begin && $end) {
            $where['drive_time'] = array('between', "$begin,$end");
        }

        $name = I('name');
        if($name){
            $where['name'] = ['like',"%$name%"];
        }

        $mobile = I('mobile');
        if($mobile){
            $where['mobile'] = $mobile;
        }


        $where['store_id'] = STORE_ID;
        $count = M('appointment_drive')->where($where)->count();
        $Page = new AjaxPage($count, 10);

        //是否从缓存中获取Page
        if (session('is_back') == 1) {
            $Page = getPageFromCache();
            //重置获取条件
            delIsBack();
        }
        //$goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $goodsList = M('appointment_drive')->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        //dump($goodsList);die;
        cachePage($Page);
        $show = $Page->show();

        //车系
        $catList =  M('goods_category')->cache(true)->select();
        $catList = convert_arr_key($catList, 'id');

        //配件分类
        $classList =  M('accessories_category')->cache(true)->select();
        $classList = convert_arr_key($classList, 'id');

        $this->assign('catList', $catList);
        $this->assign('classList', $classList);
        $this->assign('goodsList', $goodsList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }

}