<?php
namespace app\homeapi\controller;

use app\api\model\AccessoriesCategory;
use app\api\model\GoodsAuction;
use app\api\model\GoodsCategory;
use app\api\model\GoodsImages;
use app\api\model\SonOrderComment;
use app\api\model\Goods as GoodsModel;
use app\api\logic\GoodsLogic;

/**
 * 用户登录
 * @Author   XD
 * @DateTime 2018-07-11T15:09:13+0800
 */
class Goods extends Base {
    //每页显示数
    private static $pageNum = 9;
    //页数
    public $page = 1;
    public $cartype_list = [];

    public function __construct()
    {
        parent::__construct();
        //自动加载页数
        !is_numeric($this->page = I('page', 1)) && $this->errorMsg(2002, 'page');
        $this->cartype_list = M('goods_category')->where(['level'=>2,'is_show'=>1])->field('id,name')->select();
        $this->assign('cartype_list', $this->cartype_list);
    }

    /**
     * [品牌车型/精品配件 顶部车系分类信息]
     * @Auther 蒋峰
     * @DateTime
     */
    public function classify()
    {
        //检测必传参数
        (!is_numeric($type = I('type', 0)) || !in_array($type, [1,2, 3]) ) &&  $this->errorMsg(2002, 'type');//必传

        $categoryModel = new GoodsCategory();
        $AccessoriesCategoryModel = new AccessoriesCategory();
        //加载广告位
        $banner = $this->ad_position($type == 1 ? "7" : "8",'ad_name,ad_link,ad_code');
        if($banner['code'] == 0000) $banner = $banner['result'];
        else $banner = [];
        //加载车系
        if (in_array($type, [1, 3])) {
            $category = $categoryModel->get_name();
        }

        //加载分类
        if (in_array($type, [2, 3])) {
            $class = $AccessoriesCategoryModel->get_name();
        }
        //返回数据
        $data = [
            "banner" => $banner,
            "category" => $category,
            "class" => $class
        ];

        $this->json("0000", "加载成功", $data);
    }

    /**
     * [车型列表——全部汽车&精品推荐&热卖&特价]
     * @Auther tao.chen
     * @DateTime
     */
    public function car()
    {
        //验证参数
        !empty(I('cat_id', '')) && !is_numeric($cat_id = I('cat_id', 0)) && $this->errorMsg(2002, 'cat_id');//选传
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传

        $order = [];
        if(!$sales_sum && !$price) $order['on_time'] = 'desc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['deposit_price'] = $price;


        $where = [];
        $where['exchange_integral'] = array('neq',2);
        if($cat_id) $where['cat_id2'] = $cat_id;
        if(I('is_hot')) $where['is_hot'] = 1;
        if(I('is_recommend')) $where['is_recommend'] = 1;
        if(I('is_special')) {
            $special = M('goods_special')->select();
            $goodsId = array_column($special, 'goods_id');
            if (count($goodsId)) {
                $where['goods_id'] = ['in', $goodsId];
            } else {
                return $this->json(200, 'ok', ['total'=>0, 'list' => []]);
            }
        }

        $Goods = new GoodsModel();
        $field = "goods_id,goods_name,goods_remark,sales_sum,deposit_price,price,label,original_img,is_recommend,is_new,is_hot,exchange_integral";
        $data = $Goods->GoodsList($this->page, 1, $where, $order, self::$pageNum, $field);
        $count = $Goods->GoodsCount(1, $where);
        if ($count) {
            foreach ($data as &$item) {
                $item['is_collect'] = $this->userGoodsInfo(I('token'), $item['goods_id']);//是否收藏
            }
        }

        return $this->json(200, 'ok', ['total'=>$count, 'list' => $data]);
    }


    /**
     * [收藏]
     * @Auther 蒋峰
     * @DateTime
     */
    public function collect()
    {
        empty(I('token')) && $this->errorMsg(2001, 'token');//必传
        $user_id = $this->checkToken(I('token'));
        $exchange_integral = I('exchange_integral',0);


        ( empty(I('goods_id', '')) || !is_numeric($goods_id = I('goods_id', 0)) ) && $this->errorMsg(2002, 'goods_id');

        $data = (new \app\api\model\Users())->collectSave($goods_id, $user_id,$exchange_integral);

        if($data == 1) return $this->json("0000", '收藏成功');
        else return $this->json("0000", '取消收藏成功');
    }

    /**
     * [热门搜索]
     * @Auther 蒋峰
     * @DateTime
     */
    public function searchHot()
    {
        $hot_keywords = M('config')->where(array('name'=>'hot_keywords'))->cache('cache_hot_keywords')->getfield('value');
        $hot_keywords = explode('|', $hot_keywords);
//        $hot_keywords = self::$redis->smembers('hot_keywords');
//        $num = 10 - count($hot_keywords);
//        if($num > 0)
//            $hot_keywords = array_merge($hot_keywords, self::$redis->ZREVRANGEBYSCORE('hot_words', '+inf', '-inf', array('limit' => array(0, $num))));
//        $hot_keywords = array_values(array_unique($hot_keywords));
        //读取搜索次数最多的前10个关键字
//        $hot_keywords = self::$redis->ZREVRANGEBYSCORE('hot_words', '+inf', '-inf', array('limit' => array(0, 10)));
//
        return $this->json("0000", '加载成功', $hot_keywords);
    }

    /**
     * [搜索结果]
     * @Auther 蒋峰
     * @DateTime
     */
    public function search()
    {
        //验证参数
        empty($title = I('title', '')) && $this->errorMsg(2001, 'title'); //必传
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
//        self::$redis->Zincrby('hot_words', 1, $title);
        //排序顺序
        $order = [];
        if(!$sales_sum && !$price) $order['type'] = 'asc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['deposit_price'] = $price;
        //检索条件
        $where = [];
        $where['exchange_integral'] = ['lt',2];
        if($title) $where['goods_name'] = ['like','%'.$title.'%'];

        $Goods = new GoodsModel();
        $field = "goods_id,goods_name,deposit_price,price,original_img,is_recommend,is_new,is_hot,type,exchange_integral";
        $goodsList = $Goods->GoodsList($this->page, 0, $where, $order, self::$pageNum, $field);
        // if(!$goodsList) $this->errorMsg(8910);

        $banner = [];
        if($this->page == 1){
            $banner = $this->ad_position("9",'ad_name,ad_link,ad_code');
            if($banner['status'] == 0000) $banner = $banner['result'];
            else $banner = [];
            $data = [
                "banner" => $banner,
                "list" => $goodsList
            ];
        }else{
            $data = [
                // "banner" => $banner,
                "list" => $goodsList
            ];
        }

      
        $this->json("0000", "加载成功", $data);
    }

    /**
     * 车型列表
     */
    public function spec()
    {
        !empty(I('goods_id', '')) && !is_numeric($goods_id = I('goods_id', 0)) && $this->errorMsg(2002, 'goods_id');//必传
        $Goods = new GoodsModel();
        if($Goods->goodsType($goods_id) != 1) $this->errorMsg(9999);
        $GoodsLogic = new GoodsLogic();
        $price_list = $GoodsLogic->priceList($goods_id);
        $data['price_list'] = $price_list;
        $this->json("0000", "加载成功", $data);
    }

    /**
     * [汽车详情]
     * @Auther 蒋峰
     * @DateTime
     */
    public function carInfo()
    {
        !empty(I('goods_id', '')) && !is_numeric($goods_id = I('goods_id', 0)) && $this->errorMsg(2002, 'goods_id');//必传
        $Goods = new GoodsModel();
        if($Goods->goodsType($goods_id) != 1) $this->errorMsg(9999);
        $GoodsLogic = new GoodsLogic();
        $SonOrderComment = new SonOrderComment();
        //增加点击数
        $Goods->addClickCount($goods_id);
        //加载商品轮播
        $banner = (new GoodsImages())->getImage($goods_id);

        //商品详情
        $where['goods_id'] = $goods_id;
        $field = "goods_id,goods_name,equity_content,label,equity_desc,goods_remark,price,deposit_price,store_count,sales_sum,goods_content,integral,exchange_integral,integral_money as integrals_moneys,video";
        $data = $Goods->GoodsList($this->page, 1, $where, [], 1, $field);
        $data->equity_desc = str_replace("", "<br/>", $data->equity_desc);
        $data->equity_desc = str_replace(" ", "&nbsp;", $data->equity_desc);
        $data->equity_content = str_replace("", "<br/>", $data->equity_content);
        $data->equity_content = str_replace(" ", "&nbsp;", $data->equity_content);
//        $data->goods_content = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data->goods_content . '</div></body></html>');
        //价格表
        $price_list = $GoodsLogic->priceList($goods_id);

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
        $appearance['distribu'] = $GoodsLogic->get_sku($goods_id, $appearance['city'][0]['id'], 'distribu', $appearance['interior'][0]['id']);//城市
        $data['appearance'] = $appearance;
        $data['comment'] = $SonOrderComment->commentList($this->page,$goods_id,2);
        $data['comment_count'] = $SonOrderComment->count;
        $data['is_collect'] = $this->userGoodsInfo(I('token'),$goods_id);//是否收藏

        $this->json("0000", "加载成功", $data);
    }

    /**
     * [精品配件]
     * @Auther 蒋峰
     * @DateTime
     */
    public function parts()
    {
        //获取广告图片
        $this->assign('top_ads', $this->get_web_ad_top());
        $this->assign('footer_ads', $this->get_web_ad_footer());

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

        $Goods = new GoodsModel();
        $field = "goods_id,goods_name,price,shop_price, original_img,goods_remark,sales_sum,is_recommend,is_new,is_hot,exchange_integral";

        $data['parts_list'] = $Goods->GoodsList($this->page, 2, $where, $order, 6, $field);
        $this->assign('parts_list', $data['parts_list']);

        $data['total'] = $Goods->GoodsCount(2, $where);
        $this->assign('total', $data['total']);
//        $this->json("0000", "加载成功", ['category' => $category, 'class' => $class, 'total'=>$data['total'], 'parts_list' => $data['parts_list']]);
        return $this->fetch('parts/parts');
    }

    /**
     * [配件详情页面]
     * @Auther 蒋峰
     * @DateTime
     */
    public function parts_detail()
    {
        !empty(I('goods_id', '')) && !is_numeric($goods_id = I('goods_id', 0)) && $this->errorMsg(2002, 'goods_id');//必传
        $Goods = new GoodsModel();
        if($Goods->goodsType($goods_id) != 2) $this->errorMsg(9999);
        $GoodsLogic = new GoodsLogic();
        $SonOrderComment = new SonOrderComment();
        //增加点击数
        $Goods->addClickCount($goods_id);
        //加载商品轮播
        $banner = (new GoodsImages())->getImage($goods_id);
        //商品详情
        $where['goods_id'] = $goods_id;
        $field = "goods_id,goods_name,goods_remark,price,label,store_count,sales_sum,goods_content,integral,moren_integral,equity_content,equity_desc,type,video";
        $data = $Goods->GoodsList($this->page, 2, $where, [], 1, $field);
        // $data->goods_content = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data->goods_content . '</div></body></html>');
        //价格表
        $price_list = $GoodsLogic->priceList($goods_id);

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
        if(isset($this->userInfo['user_id'])){
            $data['userIntegral'] = getIntegral($this->userInfo['user_id']);
        }

        $data["banner"] = $banner;
        $data["price_list"] = $price_list;
        $data['spec'] = $GoodsLogic->get_spec($goods_id);
        $data['spec_price'] = $GoodsLogic->get_spec_goods_price($goods_id);
        $data['comment'] = $SonOrderComment->commentList($this->page,$goods_id,2);
        $data['comment_count'] = $SonOrderComment->count;
        $data['is_collect'] = $this->userGoodsInfo(I('token'),$goods_id);//是否收藏


        $recommend_where['is_recommend'] = 1;
        $recommend_where['exchange_integral'] = array('neq',2);
        $field = "goods_id,goods_name,price,original_img,goods_remark,sales_sum,is_recommend,is_new,is_hot,exchange_integral";
        $data['recommend_list'] = $Goods->GoodsList(1, 2, $recommend_where, ['goods_id' => 'desc'], 6, $field);

        $this->assign('data', $data);
        $this->assign('spec_price_script', json_encode($data['spec_price']));
//        $this->json('200','ok', $data);
        return $this->fetch('parts/parts_detail');
    }

    /**
     * 精品配件推荐——API
     */
    public function parts_recommend()
    {
        $where['is_recommend'] = 1;
        $where['exchange_integral'] = array('neq',2);
        $order = ['goods_id' => 'desc'];

        $Goods = new GoodsModel();
        $field = "goods_id,goods_name,price,original_img,goods_remark,sales_sum,is_recommend,is_new,is_hot,exchange_integral";
        $data = $Goods->GoodsList($this->page, 2, $where, $order, 6, $field);
        $count = $Goods->GoodsCount(2, $where);
        $this->json(200, 'ok', ['total' => $count, 'list' => $data]);
    }

    /**
     * [配件购买页面]
     * @Auther 蒋峰
     * @DateTime
     */
    public function parts_buy()
    {
        !empty(I('goods_id', '')) && !is_numeric($goods_id = I('goods_id', 0)) && $this->errorMsg(2002, 'goods_id');//必传
        $Goods = new GoodsModel();
        if($Goods->goodsType($goods_id) != 2) $this->errorMsg(9999);
        $GoodsLogic = new GoodsLogic();
        $SonOrderComment = new SonOrderComment();
        //增加点击数
        $Goods->addClickCount($goods_id);
        //加载商品轮播
        $banner = (new GoodsImages())->getImage($goods_id);
        //商品详情
        $where['goods_id'] = $goods_id;
        $field = "goods_id,goods_name,goods_remark,price,label,store_count,sales_sum,goods_content,integral,moren_integral,equity_content,equity_desc,type,video";
        $data = $Goods->GoodsList($this->page, 2, $where, [], 1, $field);
        $data->goods_content = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data->goods_content . '</div></body></html>');
        //价格表
        $price_list = $GoodsLogic->priceList($goods_id);

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
        $data['spec'] = $GoodsLogic->get_spec($goods_id);
        $data['spec_price'] = $GoodsLogic->get_spec_goods_price($goods_id);
        $data['comment'] = $SonOrderComment->commentList($this->page,$goods_id,2);
        $data['comment_count'] = $SonOrderComment->count;
        $data['is_collect'] = $this->userGoodsInfo(I('token'),$goods_id);//是否收藏
//        $this->json("0000", "加载成功", $data);
        $this->assign('data', $data);
        return $this->fetch('parts/parts_detail');
    }

    /**
     * [评论]
     * @Auther 蒋峰
     * @DateTime
     */
    public function comment()
    {
        !empty(I('goods_id', '')) && !is_numeric($goods_id = I('goods_id', 0)) && $this->errorMsg(2002, 'goods_id');//必传

        $sonOrderComment = new SonOrderComment();
        $data = $sonOrderComment->commentList($this->page,$goods_id,self::$pageNum);

        if(!$data) $this->errorMsg(8910);
        $this->json("0000", "加载成功", $data, $sonOrderComment->count);
    }

    /**
     * [车辆规格联动]
     * @Auther 蒋峰
     * @DateTime
     */
    public function carSpec()
    {
        (empty(I('type', '')) || !in_array($type = I('type', ''),['appearance', 'displacement', 'model', 'interior', 'province', 'city', 'distribu']) ) && $this->errorMsg(2002, 'type');//必传
        (empty(I('goods_id', '')) || !is_numeric($goods_id = I('goods_id', 0)) ) && $this->errorMsg(2002, 'goods_id');//必传
         !is_numeric($pid = I('id', 0)) && $this->errorMsg(2002, 'id');
         //判断是否是城市联动
         if(in_array($type,['city', 'distribu']) ){
             ( empty(I('sku_id', '')) || !is_numeric($sku_id = I('sku_id', 0)) ) && $this->errorMsg(2002, 'sku_id');
         }
        $Goods = new GoodsModel();
        $GoodsLogic = new GoodsLogic();
        //判断
        if($Goods->goodsType($goods_id) != 1) $this->errorMsg(9999);

        $data = $GoodsLogic->get_sku($goods_id, $pid, $type, $sku_id);
        if(!$data) $this->errorMsg(8910);
        $this->json("0000", "加载成功", $data);
    }

    public function searchList() {
        //验证参数
        empty($title = I('title', ''));
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
//        self::$redis->Zincrby('hot_words', 1, $title);
        //排序顺序
        $order = [];
        if(!$sales_sum && !$price) $order['type'] = 'asc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['deposit_price'] = $price;
        //检索条件
        $where = [];
        $where['exchange_integral'] = ['lt',2];
        if($title) $where['goods_name'] = ['like','%'.$title.'%'];

        $Goods = new GoodsModel();
        $field = "goods_id,goods_name,goods_remark,deposit_price,price,original_img,is_recommend,is_new,is_hot,type,exchange_integral";
        $goodsList = $Goods->GoodsList($this->page, 0, $where, $order, self::$pageNum, $field);
        // if(!$goodsList) $this->errorMsg(8910);

        $banner = [];
        if($this->page == 1){
            $banner = $this->ad_position("9",'ad_name,ad_link,ad_code');
            if($banner['status'] == 0000) $banner = $banner['result'];
            else $banner = [];
            $data = [
                "banner" => $banner,
                "list" => $goodsList
            ];
        }else{
            $data = [
                "list" => $goodsList
            ];
        }
        $count = $Goods->GoodsCount(0, $where);
        $this->assign('total', $count);
        $this->assign('top_ads', $data['banner']);
        $this->assign('goods', $goodsList);
        return $this->fetch('common/search');
    }

    public function search_list() {
        //验证参数
        empty($title = I('title', ''));
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('price', '')) && !in_array($price = I('price', ''),['asc','desc']) && $this->errorMsg(2002, 'price');//选传
//        self::$redis->Zincrby('hot_words', 1, $title);
        //排序顺序
        $order = [];
        if(!$sales_sum && !$price) $order['type'] = 'asc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($price) $order['deposit_price'] = $price;
        //检索条件
        $where = [];
        $where['exchange_integral'] = ['lt',2];
        if($title) $where['goods_name'] = ['like','%'.$title.'%'];

        $Goods = new GoodsModel();
        $field = "goods_id,goods_name,goods_remark,deposit_price,price,original_img,is_recommend,is_new,is_hot,type,exchange_integral";
        $goodsList = $Goods->GoodsList($this->page, 0, $where, $order, self::$pageNum, $field);
        // if(!$goodsList) $this->errorMsg(8910);

        $banner = [];
        if($this->page == 1){
            $banner = $this->ad_position("9",'ad_name,ad_link,ad_code');
            if($banner['status'] == 0000) $banner = $banner['result'];
            else $banner = [];
            $data = [
                "banner" => $banner,
                "list" => $goodsList
            ];
        }else{
            $data = [
                "list" => $goodsList
            ];
        }
        $count = $Goods->GoodsCount(0, $where);
        $data['total'] = $count;
//        $this->assign('total', $count);
//        $this->assign('top_ads', $data['banner']);
//        $this->assign('goods', $goodsList);
        $this->json(200, 'ok', $data);

    }
}