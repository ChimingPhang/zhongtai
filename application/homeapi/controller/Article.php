<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\homeapi\controller;
use think\Model;
use think\Request;

class Article extends Base{

    /**
     * 法律声明
     * @Autoh: 胡宝强
     * Date: 2018/7/27 9:52
     */
    public function laws(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = M('article')->where(['article_id'=>1,'is_open'=>1])->field('article_id as id,title,content')->find();
        if($data){
//            $data['content'] = str_replace(" ", "<br/>", $data['content']);
//            $data['content'] = str_replace(" ", "&nbsp;", $data['content']);
            $data['content'] = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data['content'] . '</div></body></html>');

            $this->json('0000','ok',$data);
        }else{
            $this->json('000','暂无数据',[]);
        }
    }

    /**
     * 联系我们
     * @Autoh: 胡宝强
     * Date: 2018/7/27 9:58
     */
    public function contact(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = M('article')->where(['article_id'=>2,'is_open'=>1])->field('article_id as id,title,content')->find();
        if($data){
//            $data['content'] = str_replace(" ", "<br/>", $data['content']);
//            $data['content'] = str_replace(" ", "&nbsp;", $data['content']);
            $data['content'] = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data['content'] . '</div></body></html>');
            $this->json('0000','ok',$data);
        }else{
            $this->json('0000','暂无数据',[]);
        }
    }

    /**
     * [拍卖协议]
     * @Auther 蒋峰
     * @DateTime
     */
    public function auctionAgreement(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = M('article')->where(['article_id'=>3,'is_open'=>1])->field('article_id as id,title,content')->find();
        if($data){
//            $data['content'] = str_replace(" ", "<br/>", $data['content']);
//            $data['content'] = str_replace(" ", "&nbsp;", $data['content']);
            $data['content'] = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data['content'] . '</div></body></html>');
            $this->json('0000','ok',$data);
        }else{
            $this->json('0000','暂无数据',[]);
        }
    }

    /**
     * 菜单分页
     * @Autor: 胡宝强
     * Date: 2018/8/8 19:20
     */
    public function menu(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = M('menu')->where(['is_show'=>1])->order('sort desc')->field('name,url,image')->limit(6)->select();
        if($data){
            $this->json('0000','获取菜单成功',$data);
        }else{
            $this->json('0000','暂无数据',[]);
        }
    }

    /**
     * 底部导航
     * @Autor: 胡宝强
     * Date: 2018/8/8 19:20
     */
    public function footer(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = M('footer')->where(['is_show'=>1])->order('sort desc')->field('name,url,image,img_select')->limit(10)->select();
        if($data){
            $this->json('0000','获取导航成功',$data);
        }else{
            $this->json('0000','暂无数据',[]);
        }
    }

    /**
     * 显示网站的一些配置的公用信息
     * @Autor: 胡宝强
     * Date: 2018/8/14 12:51
     */
    public function index(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $store_logo = $this->getConfig('store_logo');
        $store_logo_link = $this->getConfig('store_logo_link');
        $data['store_logo'] = $store_logo;
        $data['store_logo_link'] = $store_logo_link;
        if($data){
            $this->json('0000','获取导航成功',$data);
        }else{
            $this->json('0000','暂无数据',[]);
        }
    }

    /**
     * 积分说明
     * @Autor: 胡宝强
     * Date: 2018/9/10 11:40
     */
    public function pointDesc(){
        $data = M('article')->where(['article_id'=>5,'is_open'=>1])->field('article_id as id,title,content')->find();
        if($data){
            $data['content'] = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data['content'] . '</div></body></html>');
        }else{
            $data = [];
        }
        return $data;
    }
}
