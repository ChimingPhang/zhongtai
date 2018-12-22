<?php
/**
 * 积分商城
 * index     积分商城首页
 * search    积分商城搜索页面
 */
namespace app\homeapi\controller;

use app\api\model\AccessoriesCategory;
use app\api\model\GoodsCategory;
use app\api\model\GoodsImages;
use app\api\model\SonOrderComment;
use app\api\model\Goods as GoodsModel;
use app\api\logic\GoodsLogic;
use app\api\model\Navigation;
use app\api\model\Goods;
use think\Request;


class Integral extends Base {
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
     * 积分商城首页
     * @Autor: 胡宝强
     * Date: 2018/8/23 16:05
     */
    public function integral_mall(){
        //首页轮播
//        $top_ads = $this->ad_position(12,'ad_link,ad_code,ad_name','orderby desc');
//        $data['top_ads'] = $top_ads['result'];

        //积分商城上面10个的导航
        //$data['height'] = M('integral_title')->where(['is_show'=>1,'type'=>0])->field('name,image,url,title')->limit(10)->select();
        //积分商城下面的2个导航
        //$data['low'] = M('integral_title')->where(['is_show'=>1,'type'=>1])->field('name,image,url,title')->limit(10)->select();
        //精品推荐
        $goods_model = new Goods();

        $goods_where['is_recommend'] = 1;           //推荐商品
        $goods_where['exchange_integral'] = 2;      //纯积分商品
        $recommend_car = $goods_model->GoodsList(1,'',$goods_where,['sort'=>'desc'],1,'goods_id,goods_name,goods_remark,sales_sum,original_img,label,integral,moren_integral,type,store_count');
        $data['recommend_car'] = $recommend_car;
        $this->assign('recommend_car', $recommend_car);

        //活动介绍
        //$where['is_recommend'] = 0;           //推荐商品
        $where['exchange_integral'] = 2;      //纯积分商品
        $activity_car = $goods_model->GoodsList($this->page,'',$where,['sort'=>'desc'], 6, 'goods_id,goods_name,goods_remark,sales_sum,original_img,label,integral,moren_integral,type,store_count');

        $data['list'] = $activity_car;
        $this->assign('list', $activity_car);


//        $this->json('0000','ok',$data);

        return $this->fetch('dist/integral-mall');
    }



    /**
     * 积分商城列表
     * @Autor: 胡宝强
     * Date: 2018/8/8 15:56
     */
    public function integral_mall_list()
    {
        //验证参数
        !empty(I('sales_sum', '')) && !in_array($sales_sum = I('sales_sum', ''),['asc','desc']) && $this->errorMsg(2002, 'sales_sum');//选传
        !empty(I('integral', '')) && !in_array($integral = I('integral', ''),['asc','desc']) && $this->errorMsg(2002, 'integral');//选传
//        self::$redis->Zincrby('hot_words', 1, $title);
        //排序顺序
        $order = [];
        if(!$sales_sum && !$integral) $order['sort'] = 'desc';
        if ($sales_sum) $order['sales_sum'] = $sales_sum;
        if ($integral) $order['integral'] = $integral;
        //检索条件
        $where = [];
        $where['exchange_integral'] = 2;

        $Goods = new GoodsModel();
        $field = "goods_id,goods_name,goods_remark,original_img,is_recommend,is_new,is_hot,type,integral,moren_integral";
        $data = $Goods->GoodsList($this->page, 0, $where, $order, self::$pageNum, $field);
        $this->assign('data', $data);
//        $this->json("0000", "加载成功", $data);
        return $this->fetch('dist/integral-mall-list');
    }

    /**
     * 积分物品详情介绍页面
     * @return mixed
     */
    public function integral_mall_detail()
    {
        //验证参数
        $goods_id = I('goods_id');
        empty($goods_id) && $this->errorMsg(2002, 'goods_id');//选传
        //检索条件
        $where['goods_id'] = $goods_id;
        $where['exchange_integral'] = 2;

        $Goods = new Goods();
        $field = "goods_id,goods_name,goods_remark,original_img,is_recommend,is_new,is_hot,type,integral,moren_integral,sales_sum";
        $data = $Goods->GoodsList($this->page, 0, $where, [], 1, $field);

        //加载商品轮播
        $banner = (new GoodsImages())->getImage($goods_id);
        $data['banner'] = $banner;

        $this->assign('detail', $data);

        $goods_where['is_recommend'] = 1;           //推荐商品
        $goods_where['exchange_integral'] = 2;      //纯积分商品
        $recommend_car = $Goods->GoodsList(1,'',$goods_where,['sort'=>'desc'],6,'goods_id,goods_name,goods_remark,sales_sum,original_img,label,integral,moren_integral,type,store_count');
        $data['recommend_car'] = $recommend_car;
        $this->assign('recommend_car', $recommend_car);

//        $this->json(200, 'ok', $data);
        return $this->fetch('dist/integral-mall-detail');
    }

    /**
     * 积分物品——精品推荐列表——API
     */
    public function integral_mall_recommend()
    {
        $Goods = new Goods();

        $goods_where['is_recommend'] = 1;           //推荐商品
        $goods_where['exchange_integral'] = 2;      //纯积分商品
        $recommend_car = $Goods->GoodsList($this->page,'',$goods_where,['sort'=>'desc'],6,'goods_id,goods_name,goods_remark,sales_sum,original_img,label,integral,moren_integral,type,store_count');
        $data['recommend_car'] = $recommend_car;
        $this->json(200, 'ok', $recommend_car);
    }


    /**
     * 积分物品兑换页面
     */
    public function integral_mall_exchange()
    {
        $data = $this->get_goods_detail();
        $this->assign('data', $data);
        $this->json(200, 'ok', $data);
        return $this->fetch('dist/integral-mall-exchange');
    }

    private function get_goods_detail()
    {
        !empty(I('goods_id', '')) && !is_numeric($goods_id = I('goods_id', 0)) && $this->errorMsg(2002, 'goods_id');//必传
        $Goods = new Goods();

        $GoodsLogic = new GoodsLogic();
        $SonOrderComment = new SonOrderComment();
        //增加点击数
        $Goods->addClickCount($goods_id);
        //加载商品轮播
        $banner = (new GoodsImages())->getImage($goods_id);
        //$this->assign('banner', $banner);
        $data['banner'] = $banner;

        //商品详情
        $where['goods_id'] = $goods_id;
        $field = "goods_id,goods_name,equity_content,label,equity_desc,goods_remark,price,
        deposit_price,store_count,sales_sum,goods_content,integral,exchange_integral,
        integral_money as integrals_moneys,video";
        $data = $Goods->GoodsList($this->page, 1, $where, [], 1, $field);
        $data->equity_desc = str_replace("", "<br/>", $data->equity_desc);
        $data->equity_desc = str_replace(" ", "&nbsp;", $data->equity_desc);
        $data->equity_content = str_replace("", "<br/>", $data->equity_content);
        $data->equity_content = str_replace(" ", "&nbsp;", $data->equity_content);
        $data->goods_content = htmlspecialchars_decode('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body><style>#goods_info_content_div p{margin:0px; padding:0px} #goods_info_content_div p img{width:100%} </style><div id="goods_info_content_div">' . $data->goods_content . '</div></body></html>');
        //价格表
        $price_list = $GoodsLogic->priceList($goods_id);
        //$this->assign('price_list', $price_list);
        $data['price_list'] = $price_list;

        //求这个商品需要多少积分
//        $exchange_integral = I('exchange_integral')??0;
        if($data['exchange_integral'] == 0){        //纯现金
            $data['most_point'] = 0;
            $data['minimum_point'] = 0;
        }elseif($data['exchange_integral'] == 1){   //积分+现金
            $data['most_point'] = $data['integral'];
            $data['minimum_point'] = 1;
        }elseif($data['exchange_integral'] == 2){   //纯积分
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

        return $data;
    }

}