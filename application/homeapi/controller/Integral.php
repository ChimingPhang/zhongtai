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
    public function search()
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

}