<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\api\controller;
use app\api\model\Index as Indexs;
use app\api\model\Navigation;
use app\api\model\Goods;
use think\Model;

class Index extends Base{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取首页的信息
     * @Autoh: 胡宝强
     * Date: 2018/7/18 14:33
     */
    public function index(){
        $data = [];
        //获取首页弹窗广告
        $popup_ads = $this->ad_position(2,'ad_link,ad_code,ad_name','orderby desc');
        $data['popup_ads'] = $popup_ads['result'];
        //if($popup_ads['status'] != '0000') $this->json($popup_ads['status'],$popup_ads['msg'],'');
        //if($popup_ads['status'] == '0000') $this->json('0000','ok',$popup_ads['result']);
        //获取首页顶部轮播
        $top_ads = $this->ad_position(3,'ad_link,ad_code,ad_name','orderby desc');
        $data['top_ads'] = $top_ads['result'];
        //获取首页第一个图片
        $first_ads = $this->ad_position(4,'ad_link,ad_code,ad_name','orderby desc');
        $data['first_ads'] = $first_ads['result'];
        //获取首页第二个图片
        $second_ads = $this->ad_position(5,'ad_link,ad_code,ad_name','orderby desc');
        $data['second_ads'] = $second_ads['result'];
        //获取底部的广告图片
        $footer_ads = $this->ad_position(6,'ad_link,ad_code','orderby desc');
//        $key = array_rand($footer_ads['result'],1);
//        $footer_ads = $footer_ads['result'][$key];
        $data['footer_ads'] = $footer_ads;

        //获取底部的文字描述
//        $footer_desc = M('config')->where(['name'=>'footer_desc'])->getField('value');
        $footer_desc = $this->getConfig('footer_desc');
        $data['footer_desc'] = empty($footer_desc) ? '众泰商城' : $footer_desc;
        $footer_desc_link = $this->getConfig('footer_desc_link');
        $data['footer_desc_link'] = $footer_desc_link;
        //分类导航
        $navigation = new Navigation();
        $category = $navigation->get_name();
        $data['category'] = $category;
        //活动的文字描述
        $index_activity_desc = $this->getConfig('index_activity_desc');
        $data['index_activity_desc'] = $index_activity_desc;
        //车型的文字展示
        $index_font_desc = $this->getConfig('index_font_desc');
        $data['index_font_desc'] = $index_font_desc;

        //特价车
        //$data['special'] = M('special')->order('sort desc')->limit(12)->select();
        $special = $this->ad_position(13,'ad_link,ad_code,ad_name','orderby desc');
        $data['special'] = $special['result'];

        //热卖车型
        $goods_model = new Goods();
        $goods_where['is_on_sale'] = 1;
        $goods_where['state'] = 1;
        $goods_where['is_hot'] = 1;
        $goods_where['type'] = 1;       //查询的是车型
        $hot_car = $goods_model->GoodsList(1,1,$goods_where,['sort'=>'desc'],4,'goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,original_img,label');
        $data['hot_car'] = $hot_car;

        $this->json('0000','ok',$data);
    }
}
