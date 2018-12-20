<?php
namespace app\api\controller;

use app\api\model\Goods;
use think\Request;
use app\api\logic\CartLogic;

/**
 * 购物车
 * @Author   XD
 * @DateTime 2018-07-11T15:09:13+0800
 *
 * 			目录
 * 	addcart 	加入购物车
 * 	delcart     删除购物车
 * 	changeNum   购物车加减
 * 	cartlist	购物车列表
 */
class Cart extends Base {
    public function __construct()
    {
        parent::__construct();
    }
    /*
     * 初始化操作
     */
    public function _initialize() {
     
    }

    /**
     * 加入购物车
     * [addcart description]
     * @Author   XD
     * @DateTime 2018-07-18T10:26:39+0800
     * @return   [type]                   [description]
     */
    public function addcart(){
    	//goods_id 164 item_id 5 token 20e157278056257c71fead302face897
    	if (!Request::instance()->isPost()) $this->errorMsg('1006');
    	$goods_id = I("goods_id/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $item_id = I("item_id/d"); // 商品规格id
        $token = I("token");
        $exchange_integral = I('exchange_integral');
        $user_id = $this->checkToken($token);
        if(empty($goods_id)){
            $this->errorMsg('2001','goods_id');
        }
        if(empty($goods_num)){
             $this->errorMsg('2001','goods_num');
        }
        if($goods_num > 200){
             $this->errorMsg(1477,'购买上限');
             $this->errorMsg('2002','goods_num');
        }
//        $goodsIntegral = M('goods')->where(['goods_id'=>$goods_id,'is_on_sale'=>1,'state'=>1])->getField('exchange_integral');
//        if($goodsIntegral == $exchange_integral){
//            $this->errorMsg('2002','exchange_integral');
//        }
        $cartLogic = new CartLogic();//实例化购物车动作类
        $cartLogic->setUserId($user_id);
        $cartLogic->setGoodsModel($goods_id);
        $cartLogic->setSpecGoodsPriceModel($item_id);
        $cartLogic->setGoodsBuyNum($goods_num);
        $result = $cartLogic->addGoodsToCart($exchange_integral);
        if($result['status']==1) $this->json('0000',$result['msg']);
        else $this->throwError($result['msg']);
        // $this->ajaxReturn($result);
    }

   	/**
   	 * 删除购物车
   	 * [delcart description]
   	 * @Author   XD
   	 * @DateTime 2018-07-18T11:59:53+0800
   	 * @return   [type]                   [description]
   	 */
    public function delcart(){
    	if (!Request::instance()->isPost()) $this->errorMsg('1006');
    	$token = I("token");
        $user_id = $this->checkToken($token);
    	$cart_ids = input('cart_ids');
    	$cart_ids = explode(',', $cart_ids);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $result = $cartLogic->delete($cart_ids);
        if($result){
            $this->json('0000','删除成功');
        }else{
           $this->throwError('删除失败');
        }
    }

     /**
     * 购物车加减
     * @Author   XD
   	 * @DateTime 2018-07-18T11:59:53+0800
     * @param $cart_id|购物车id
     * @param $goods_num|商品数量
     * @return array
     */
    public function changeNum(){
    	if (!Request::instance()->isPost()) $this->errorMsg('1006');
    	$token = I("token");
        $user_id = $this->checkToken($token);
        $cart = input('cartid');
        $goods_num = input('goods_num');
        if (empty($cart)) {
             $this->errorMsg('2001','cartid');
        }
         if (empty($goods_num)) {
             $this->errorMsg('2001','goods_num');
        }
        $cartLogic = new CartLogic();
        $result = $cartLogic->changeNum($cart,$goods_num);
        if($result['status'] == 1) $this->json('0000',$result['msg']);
        else if($result['status'] == 2) $this->json(1477,$result['msg']);
        else $this->throwError('修改失败');
    }

    /**
     * 购物车列表
     * [cartlist description]
     * @Author   XD
     * @DateTime 2018-07-18T13:55:28+0800
     * @return   [type]                   [description]
     */
    public function cartlist(){
		if (!Request::instance()->isPost()) $this->errorMsg('1006');
		$token = I("token");
		$page = I("page/d",'1');// 商品数量
	    $user_id = $this->checkToken($token);
	    $cartLogic = new CartLogic();
        $cartLogic->setUserId($user_id);
        $cartList = $cartLogic->getCartList(0,$page);//用户购物车
        // $storeCartList = $cartLogic->getStoreCartList($cartList);//
        // $userCartGoodsTypeNum = $cartLogic->getUserCartGoodsTypeNum();//获取用户购物车商品总数
        $arr = [];
        $goods_num = 0;
        $tatle_price = 0;
        if($cartList){
            foreach ($cartList as $key => $value) {
                $goods_num +=$value['goods_num']; 
                $tatle_price +=$value['goods_price']*$value['goods_num'];
                $arr['list'][$key]['goods_id'] = $value['goods_id'];
                $arr['list'][$key]['goods_num'] = $value['goods_num'];
                $arr['list'][$key]['goods_name'] = $value['goods_name'];
                $arr['list'][$key]['goods_price'] = $value['goods_price'];
                $arr['list'][$key]['spec'] = $value['spec_key_name'];
                $arr['list'][$key]['goods_img'] = goods_thum_images($value['goods_id'],240,240);
                $arr['list'][$key]['cart_id'] = $value['id'];
                $arr['list'][$key]['store_count'] = $value['store_count'];
                $arr['list'][$key]['integral'] = $value['integral']??0;
                $arr['list'][$key]['exchange_integral'] = $value['exchange_integral']??0;
            }
        }else{
            $arr['list'] = '';
        }
        $arr['tatle_price'] = $tatle_price??0;
        $arr['count_nums'] = $goods_num??0;
        //$position = $this->ad_position(6);
//        $arr['position_text'] = $position['result'][0]['ad_desc'];
//        $arr['position_pic'] = $position['result'][0]['ad_code'];
//        $arr['position_url'] = $position['result'][0]['ad_link'];

        //底部文字描述
        $footer_desc = M('config')->where(['name'=>'footer_desc'])->getField('value');
        $arr['footer_desc'] = empty($footer_desc) ? '众泰商城' : $footer_desc;
        //底部轮播
        $footer_ads = M('ad')->where(['pid'=>6])->field('ad_link,ad_code')->order('orderby desc')->select();
        $arr['footer_ads'] = $footer_ads;
        // $arr['tatle_price'] = 10;
       	$this->json('0000','成功',$arr);
    }


}