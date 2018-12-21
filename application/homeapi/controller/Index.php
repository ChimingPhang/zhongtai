<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\homeapi\controller;
use app\api\model\AccessoriesCategory;
use app\api\model\GoodsCategory;
use app\api\model\Navigation;
use app\api\model\Goods;
use think\Controller;
use think\Log;

class Index extends Base{

    //每页显示数
    private static $pageNum = 10;
    //页数
    public $page = 1;

    public function __construct()
    {
        parent::__construct();
        //自动加载页数
        !is_numeric($this->page = I('page', 1)) && $this->errorMsg(2002, 'page');
    }

    public function test()
    {
        $this->assign('test', '我是一个测试');
        return $this->fetch('dist/home');
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
        $data['footer_ads'] = $footer_ads;

        //获取底部的文字描述
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
//        $special = $this->ad_position(13,'ad_link,ad_code,ad_name','orderby desc');
//        $data['special'] = $special['result'];

        //热卖车型
        $goods_model = new Goods();
        $goods_where['is_on_sale'] = 1;
        $goods_where['state'] = 1;
        $goods_where['is_hot'] = 1;
        $goods_where['type'] = 1;       //查询的是车型
        $hot_car = $goods_model->GoodsList(1,1,$goods_where,['sort'=>'desc'],4,'goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,original_img,label');
        $data['hot_car'] = $hot_car;

//        $this->json('0000','ok',$data);


        $this->assign('hot_car', $hot_car);
	    $this->assign('data',  $data);
        $this->assign('test', '我是一个测试');
        return $this->fetch('dist/home');
    }

    public function home()
    {
	    $data = [];
    	//广告
	    $top_ads = $this->ad_position(3,'ad_link,ad_code,ad_name','orderby desc');
	    $data['top_ads'] = $top_ads['result'];
        //热卖车型
        $goods_model = new Goods();
        $goods_where['is_on_sale'] = 1;
        $goods_where['state'] = 1;
        $goods_where['is_hot'] = 1;
        $goods_where['type'] = 1;       //查询的是车型
        $hot_car = $goods_model->GoodsList(1,1,$goods_where,['sort'=>'desc'],4,'goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,original_img,label');
        $data['hot_car'] = $hot_car;

//        $this->json('0000','ok',$data);

        $this->assign('hot_car', $hot_car);
	    $this->assign('top_ads',  $data['top_ads']);
        $this->assign('test', '我是一个测试');
        return $this->fetch('dist/home');
    }

    /**
     * 品牌车型
     * @return mixed
     */
    public function brand_models()
    {
        //检测必传参数
        $categoryModel = new GoodsCategory();
        $AccessoriesCategoryModel = new AccessoriesCategory();
        //加载车系
        $category = $categoryModel->get_name();
        //加载分类
        $class = $AccessoriesCategoryModel->get_name();
        //返回数据
        $cate = [
            "category" => $category,
            "class" => $class
        ];
        $this->assign('category', $category);
        $this->assign('class', $class);
//        $this->json("0000", "加载成功", $cate);

        //验证参数
        !empty(I('cat_id', '')) && !is_numeric($cat_id = I('cat_id', 0)) && $this->errorMsg(2002, 'cat_id');//选传
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
        $where = [];
        if(I('is_hot')) $where['is_hot'] = 1;
        if(I('is_recommend')) $where['is_recommend'] = 1;
        if(I('is_special')) {
            $special = M('goods_special')->order('sort desc')->limit(8)->select();
            $goodsId = array_column($special, 'goods_id');
            if (count($goodsId)) {
                $where['goods_id'] = ['in', $goodsId];
            } else {
                $this->json("0000", "加载成功", []);
            }
        }

        $order = [];
        if(!$sales_sum && !$price) $order['on_time'] = 'desc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['deposit_price'] = $price;

        if($cat_id) $where['cat_id2'] = $cat_id;
        $where['exchange_integral'] = array('neq',2);

        $Goods = new Goods();
        $field = "goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,label,original_img,is_recommend,is_new,is_hot,exchange_integral";
        $data = $Goods->GoodsList($this->page, 1, $where, $order, self::$pageNum, $field);
        $this->assign('car_list', $data);
        return $this->fetch('dist/brand-models');
    }

    /**
     * 精品配件
     * @return mixed
     */
    public function parts()
    {
        //检测必传参数
        $categoryModel = new GoodsCategory();
        $AccessoriesCategoryModel = new AccessoriesCategory();
        //加载车系
        $category = $categoryModel->get_name();
        //加载分类
        $class = $AccessoriesCategoryModel->get_name();
        $this->assign('category', $category);
        $this->assign('class', $class);

        //验证参数
        !empty(I('cat_id', '')) && !is_numeric($cat_id = I('cat_id', 0)) && $this->errorMsg(2002, 'cat_id');//选传
        !empty(I('class_id', '')) && !is_numeric($class_id = I('class_id', 0)) && $this->errorMsg(2002, 'class_id');//选传

        $where = [];
        if($cat_id) $where['cat_id2'] = $cat_id;
        if($class_id) $where['class_id'] = $class_id;
        $where['exchange_integral'] = array('neq',2);
        $order = ['goods_id' => 'desc'];

        $Goods = new Goods();
        $field = "goods_id,goods_name,price,original_img,goods_remark,sales_sum,is_recommend,is_new,is_hot,exchange_integral";
        $data = $Goods->GoodsList($this->page, 2, $where, $order, self::$pageNum, $field);
//        if(!$data) $this->errorMsg(8910);
//        $this->json("0000", "加载成功", $data);
        $this->assign('parts_list', $data);

        return $this->fetch('dist/parts');
    }

    /**
     * 积分商城
     */
    public function integral_mall()
    {
    	$data = [];
        //首页轮播
        $top_ads = $this->ad_position(12,'ad_link,ad_code,ad_name','orderby desc');
        $data['top_ads'] = $top_ads['result'];

        //精品推荐
        $goods_model = new Goods();

        $goods_where['is_on_sale'] = 1;
        $goods_where['state'] = 1;
        $goods_where['is_recommend'] = 1;           //推荐商品
        $goods_where['exchange_integral'] = 2;      //纯积分商品
        $recommend_car = $goods_model->GoodsList(1,'',$goods_where,['sort'=>'desc'],1,'goods_id,goods_name,goods_remark,sales_sum,original_img,label,integral,moren_integral,type,store_count');
        $data['recommend_car'] = $recommend_car;
        $this->assign('recommend_car', $recommend_car);

        //活动介绍
        $where['is_on_sale'] = 1;
        $where['state'] = 1;
        //$where['is_recommend'] = 0;           //推荐商品
        $where['exchange_integral'] = 2;      //纯积分商品
        $activity_car = $goods_model->GoodsList($this->page,'',$where,['sort'=>'desc'], 6, 'goods_id,goods_name,goods_remark,sales_sum,original_img,label,integral,moren_integral,type,store_count');

        $data['list'] = $activity_car;
        $this->assign('list', $activity_car);
        $this->assign('top_ads', $data['top_ads']);

        return $this->fetch('dist/integral-mall');
    }


    public function user_center()
    {
        return $this->fetch('dist/user-center');
    }

    public function one_dollar()
    {
        return $this->fetch('dist/one-dollar');
    }

    public function special_offer()
    {
        return $this->fetch('dist/special-offer');
    }

    /**
     * 积分兑换商品
     * @return mixed
     */
    public function integral_mall_exchange()
    {
        //检测必传参数
        $categoryModel = new GoodsCategory();
        $AccessoriesCategoryModel = new AccessoriesCategory();
        //加载车系
        $category = $categoryModel->get_name();
        //加载分类
        $class = $AccessoriesCategoryModel->get_name();
        $this->assign('category', $category);
        $this->assign('class', $class);

        //验证参数
        !empty(I('cat_id', '')) && !is_numeric($cat_id = I('cat_id', 0)) && $this->errorMsg(2002, 'cat_id');//选传
        !empty(I('class_id', '')) && !is_numeric($class_id = I('class_id', 0)) && $this->errorMsg(2002, 'class_id');//选传
        //empty($title = I('title', '')) && $this->errorMsg(2001, 'title'); //必传
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('integral', '')) && !in_array($integral = I('integral', ''),['asc','desc']) && $this->errorMsg(2002, 'integral');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传

        //排序顺序
        $order = [];
        if(!$sales_sum && !$integral) $order['sort'] = 'desc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($integral) $order['integral'] = $integral;
        //检索条件
        $where = [];
        if($cat_id) $where['cat_id2'] = $cat_id;
        if($class_id) $where['class_id'] = $class_id;
        if ($price) $order['deposit_price'] = $price;
        $where['exchange_integral'] = 2;
        if(I('title')) $where['goods_name'] = ['like','%'.I('title').'%'];

        $Goods = new Goods();
        $field = "goods_id,goods_name,goods_remark,original_img,is_recommend,is_new,is_hot,type,integral,moren_integral,sales_sum";
        $data = $Goods->GoodsList($this->page, 0, $where, $order, 9, $field);
        $this->assign('g_list', $data);
//        $this->json(200, 'ok', $data);
        return $this->fetch('dist/integral-mall-exchange');
    }

}
