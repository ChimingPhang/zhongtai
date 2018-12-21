<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\homeapi\controller;
use app\api\logic\GoodsLogic;
use app\api\model\AccessoriesCategory;
use app\api\model\GoodsAuction;
use app\api\model\GoodsCategory;
use app\api\model\GoodsImages;
use app\api\model\Navigation;
use app\api\model\Goods;
use app\api\model\SonOrderComment;
use think\Controller;

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
        $special = $this->ad_position(13,'ad_link,ad_code,ad_name','orderby desc');
        $data['special'] = $special['result'];

        //热卖车型
        $goods_model = new Goods();
        $goods_where['is_on_sale'] = 1;
        $goods_where['state'] = 1;
        $goods_where['is_hot'] = 1;
        $goods_where['type'] = 1;       //查询的是车型
        $hot_car = $goods_model->GoodsList(1,1,$goods_where,['sort'=>'desc'],6,'goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,original_img,label');
        $this->assign('hot_car', $hot_car);

        return $this->fetch('dist/home');
    }

    /**
     * 首页
     * @return mixed
     */
    public function home()
    {
        //拍卖车
        $Goods = new GoodsAuction();
        //$field = "id,goods_sn,goods_name,goods_remark,start_price,start_time,end_time,video,spec_key,spec_key_name,original_img";
        $field = [
            "id",           //商品id
            "goods_sn",     //商品编号
            "goods_name",   //商品名
            "goods_remark", //商品描述
            "start_price",  //起拍价
            "start_time",   //开始时间
            "end_time",     //结束时间
            "video",        //视频地址
            "spec_key",     //规格id
            "spec_key_name",//规格名
            "original_img"  //图片地址
        ];
        $field = implode(',', $field);
        $where = ['is_on_sale' => 1, 'is_end' => 0];
        $auc_car = $Goods->GoodsList($this->page, 1, $where, ['on_time' => 'desc'], 6, $field);
        $this->assign('auc_car', $auc_car);

        //热卖车型
        $goods_model = new Goods();
        $goods_where['is_on_sale'] = 1;
        $goods_where['state'] = 1;
        $goods_where['is_hot'] = 1;
        //$goods_field = 'goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,original_img,label';
        $goods_field = [
            'goods_id',     //商品id
            'goods_name',   //商品名
            'goods_remark', //商品描述
            'sales_sum',    //销量
            'deposit_price',//定金
            'price',        //售价
            'original_img', //图片
            'label'         //标签
        ];
        $goods_field = implode(',', $goods_field);
        $hot_car = $goods_model->GoodsList(1, 1, $goods_where, ['sort'=>'desc'], 4, $goods_field);
        $this->assign('hot_car', $hot_car);

//        $this->json('0000','ok', ['auc_car' =>$auc_car, 'hot_car' => $hot_car]);
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
        $this->assign('category', $category);
        $this->assign('class', $class);

        //验证参数
        !empty(I('cat_id', '')) && !is_numeric($cat_id = I('cat_id', 0)) && $this->errorMsg(2002, 'cat_id');//选传
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
        $where = [];
//        if(I('is_hot')) $where['is_hot'] = 1;
//        if(I('is_recommend')) $where['is_recommend'] = 1;
//        if(I('is_special')) {
//            $special = M('goods_special')->order('sort desc')->limit(8)->select();
//            $goodsId = array_column($special, 'goods_id');
//            if (count($goodsId)) {
//                $where['goods_id'] = ['in', $goodsId];
//            }
//        }

        $order = [];
        if(!$sales_sum && !$price) $order['on_time'] = 'desc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['deposit_price'] = $price;

        if($cat_id) $where['cat_id2'] = $cat_id;
        $where['exchange_integral'] = array('neq',2);

        $Goods = new Goods();
        $field = "goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,label,original_img,is_recommend,is_new,is_hot,exchange_integral";
        $car_list = $Goods->GoodsList($this->page, 1, $where, $order, self::$pageNum, $field);
        $this->assign('car_list', $car_list);

//        $this->json(200, 'ok', ['category' => $category, 'class' => $class, 'car_list' => $car_list]);
        return $this->fetch('dist/brand-models');
    }

    /**
     * 品牌车详情
     */
    public function brand_models_detail()
    {
        $this->assign('input', I(''));
        !empty(I('goods_id', '')) && !is_numeric($goods_id = I('goods_id', 0)) && $this->errorMsg(2002, 'goods_id');//必传
        $Goods = new Goods();
        if($Goods->goodsType($goods_id) != 1) $this->errorMsg(9999);//不是汽车

        $GoodsLogic = new GoodsLogic();
        $SonOrderComment = new SonOrderComment();
        //增加点击数
        $Goods->addClickCount($goods_id);
        //加载商品轮播
        $banner = (new GoodsImages())->getImage($goods_id);
        //$this->assign('banner', $banner);

        //商品详情
        $where['goods_id'] = $goods_id;
        $field = "goods_id,goods_name,equity_content,label,equity_desc,goods_remark,price,deposit_price,store_count,sales_sum,goods_content,integral,exchange_integral,integral_money as integrals_moneys,video";
        $data = $Goods->GoodsList($this->page, 1, $where, [], 1, $field);
        $data->equity_desc = str_replace("", "<br/>", $data->equity_desc);
        $data->equity_desc = str_replace(" ", "&nbsp;", $data->equity_desc);
        $data->equity_content = str_replace("", "<br/>", $data->equity_content);
        $data->equity_content = str_replace(" ", "&nbsp;", $data->equity_content);
        $data->goods_content = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data->goods_content . '</div></body></html>');
        //价格表
        $price_list = $GoodsLogic->priceList($goods_id);
        //$this->assign('price_list', $price_list);

        //求这个商品需要多少积分
        $exchange_integral = I('exchange_integral')??0;
        if($exchange_integral == 0){
            $data['most_point'] = 0;
            $data['minimum_point'] = 0;
        }elseif($exchange_integral == 1){
            $data['most_point'] = $data['integral'];
            $data['minimum_point'] = 1;
        }elseif($exchange_integral == 2){
            $data['most_point'] = $data['integral'];
            $data['minimum_point'] = $data['integral'];
        }
        $point_rate = tpCache('shopping.point_rate'); //兑换比例
        $data['integral_money'] = round($data['most_point']/$point_rate,2);  //积分最高可以抵多少钱

        //获取这个用户还有多少积分
        if(I('token')){
            $user_id = $this->checkToken(I('token'));
            $data['userIntegral'] = getIntegral($user_id);
        }

        $data["banner"] = $banner;
        $data["price_list"] = $price_list;

        $data['spec'] = $GoodsLogic->get_sku($goods_id);//外观颜色
        $appearance['displacement'] = $GoodsLogic->get_sku($goods_id, $data['spec'][0]['id'], 'displacement');//排量
        $appearance['model'] = $GoodsLogic->get_sku($goods_id, $appearance['displacement'][0]['id'], 'model');//型号
        $appearance['interior'] = $GoodsLogic->get_sku($goods_id, $appearance['model'][0]['id'], 'interior');//内饰颜色
        $appearance['province'] = $GoodsLogic->get_sku($goods_id, $appearance['interior'][0]['id'], 'province');//城市
        $appearance['city'] = $GoodsLogic->get_sku($goods_id, $appearance['province'][0]['id'], 'city', $appearance['interior'][0]['id']);//城市
        $appearance['distribu'] = $GoodsLogic->get_sku($goods_id, $appearance['city'][0]['id'], 'distribu', $appearance['interior'][0]['id']);//经销商
        $data['appearance'] = $appearance;
        $data['comment'] = $SonOrderComment->commentList($this->page,$goods_id,2);
        $data['comment_count'] = $SonOrderComment->count;
        $data['is_collect'] = $this->userGoodsInfo(I('token'),$goods_id);//是否收藏
//        $this->json("0000", "加载成功", $data);
        return $this->fetch('dist/brand-models-detail');
    }

    /**
     * [判断商品是否收藏过]
     * @Auther 蒋峰
     * @DateTime
     * @param $token
     * @param $goods_id
     * @return int
     */
    private function userGoodsInfo($token,$goods_id)
    {
        if(empty($token) || !is_string($token)) return 0;
        $user_id = M('users')->where(array('token' => input('token')))->getField('user_id');
        $count = M('collection')->where(array('user_id' => $user_id, 'goods_id' => $goods_id,'deleted'=>0))->count();
        if($count) return 1;
        return 0;
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
        //首页轮播
//        $top_ads = $this->ad_position(12,'ad_link,ad_code,ad_name','orderby desc');
//        $data['top_ads'] = $top_ads['result'];

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

    /**
     * 拍卖商品类表
     * @return mixed
     */
    public function special_offer()
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
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
        $where = ['is_on_sale' => 1, 'is_end' => 0];
//        if (I('is_start')) {
//            $where['start_time'] = ['elt', time()];
//        } else {
//            $where['start_time'] = ['gt', time()];
//        }


        $order = [];
        if(!$sales_sum && !$price) $order['on_time'] = 'desc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['deposit_price'] = $price;

        $Goods = new GoodsAuction();
        $field = "goods_id,goods_sn,goods_name,goods_remark,start_price,start_time,end_time,
        video,spec_key,spec_key_name,is_on_sale,is_end,is_recommend,original_img";
        $data = $Goods->GoodsList($this->page, 1, $where, $order, 9, $field);
        //推荐
//        $where['is_recommend'] = 1;
//        $data['recommend'] = $Goods->GoodsList(1, 1, $where, $order, self::$pageNum, $field);

//        if(!$data) $this->errorMsg(8910);
//        $this->json("0000", "加载成功", $data);
        $this->assign('g_list', $data);
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