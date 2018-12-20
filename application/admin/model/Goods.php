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

namespace app\admin\model;
use think\Model;
class Goods extends Model {
    /**
     * 后置操作方法
     * 自定义的一个函数 用于数据保存后做的相应处理操作, 使用时手动调用
     * @param int $goods_id 商品id
     */
    public function afterSave($goods_id)
    {
        // 商品货号
        $goods_sn = "ZT".str_pad($goods_id,7,"0",STR_PAD_LEFT);
        $this->where("goods_id = $goods_id and goods_sn = ''")->save(array("goods_sn"=>$goods_sn)); // 根据条件更新记录
        $goods_images = I('goods_images/a');
        $img_sorts = I('img_sorts/a');
        $original_img = I('original_img');
        // 商品图片相册  图册
        if(count($goods_images) > 1)
        {
            array_pop($goods_images); // 弹出最后一个
            $goodsImagesArr = M('GoodsImages')->where("goods_id = $goods_id")->getField('img_id,image_url'); // 查出所有已经存在的图片
            $goodsImagesArrRever = array_flip($goodsImagesArr);
            // 添加图片
            foreach($goods_images as $key => $val)
            {
                $sort = $img_sorts[$key];
                if($val == null)  continue;
                if(!in_array($val, $goodsImagesArr))
                {
                    $data = array( 'goods_id' => $goods_id,'image_url' => $val , 'img_sort'=>$sort);
                    M("GoodsImages")->insert($data); // 实例化User对象
                }else{
                    $img_id = $goodsImagesArrRever[$val];
                    //修改图片顺序
                    M('GoodsImages')->where("img_id = {$img_id}")->save(array('img_sort' => $sort));
                }
            }
            // 删除图片
            foreach($goodsImagesArr as $key => $val)
            {
                if(!in_array($val, $goods_images)){
                    M('GoodsImages')->where("img_id = {$key}")->delete();
                }
            }
        }

//        // 查看主图是否已经存在相册中
      /*  $c = M('GoodsImages')->where("goods_id = $goods_id and image_url = '{$original_img}'")->count();
        if($c == 0 && $original_img)
        {
            M("GoodsImages")->add(array('goods_id'=>$goods_id,'image_url'=>$original_img));
        }*/
        //delFile("./public/upload/goods/thumb/$goods_id"); // 删除缩略图
        delFile("./runtime");

    }

    /**
     * [修改商品规格]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     */
    public function specSave($goods_id,$exchange_integral = 0){
        // 商品规格价钱处理
        $item = I('item/a');
        M("SpecGoodsPrice")->where('goods_id = '.$goods_id)->delete(); // 删除原有的价格规格对象
        $store_id = 0;
        if($item)
        {
            $store_count = 0 ;
            $spec_type = 0 ;
            $dataList = [];
            $price = 0;
            foreach($item as $k => $v)
            {
                if(!empty($k)){
                    //批量添加数据
                    // $v['price'] = trim($v['price']);
                    $store_count += $v['store_count'] ; // 记录商品总库存
                    // $v['sku'] = trim($v['sku']);
//                    $SpecGoodsPriceData = trim($v['store_count']);
                    //商品展示价
                    if($price == 0 ) $price = $v['price'];
                    elseif($price > $v['price'] && $v['price'] > 0) $price = $v['price'];

                    if($exchange_integral == 0) {
                        //纯金额
                        $v['integral_price']=0;
                        $v['integral']=0;
                    }elseif($exchange_integral == 1){
                        //积分和金额
//                        $v['price']=0;
//                        $v['cost_price']=0;
//                        $v['integral_price']=0;
//                        $v['integral']=0;
                    }elseif($exchange_integral == 2){
                        //纯积分
                        $v['price']=0;
                        $v['cost_price']=0;
                    }else{
                        //当做纯金额
                        $v['integral_price']=0;
                        $v['integral']=0;
                    }

                    $SpecGoodsPriceData = array(
                        'goods_id'=>$goods_id,
                        'key'=>$k,
                        'key_name'=>$v['key_name'],
                        'price'=>trim($v['price']),
                        'cost_price' => trim($v['cost_price']),
                        'weight'=>trim($v['weight']),
                        'store_count'=>trim($v['store_count']),
                        'store_id'=>$store_id,
                        'integral'=>$v['integral'],
                        'integral_price'=>$v['integral_price']
                    );

                    M("SpecGoodsPrice")->insert($SpecGoodsPriceData);
                    // 修改商品后购物车的商品价格也修改一下
                    M('cart')->where(array("goods_id"=> $goods_id, "spec_key" => $k))->save(array(
                        'goods_price' => trim($v['price']), //市场价
                    ));
                    $spec_type = 1;
                }

//                // 修改商品后购物车的商品价格也修改一下
//                M('cart')->where("goods_id = $goods_id and spec_key = '$k'")->save(array(
//                    'shop_price'=>$v['price'], // 本店价
//                ));
            }
            M("SpecServicePrice")->insertAll($dataList);
            //记录库存修改日志
            $goods_stock = $this->where(array('goods_id'=>$goods_id))->getField('store_count');
            if($spec_type == 1)
                if($store_count != $goods_stock){
                    $stock = $store_count - $goods_stock;
                    update_stock_log($store_id, $stock,array('goods_id'=>$goods_id,'goods_name'=>$_POST['goods_name'],'store_id'=>$store_id));
                }
            //将最低规格价放到商品展示价上
            M("Goods")->where(array('goods_id' => $goods_id))->save(array('price' => $price,'store_count' => $store_count));

        }

        // 商品规格图片处理
        $item_img = I('item_img/a');
        if($item_img)
        {
            M('SpecImage')->where("goods_id = $goods_id")->delete(); // 把原来是删除再重新插入
            foreach ($item_img as $key => $val)
            {
                M('SpecImage')->insert(array('goods_id'=>$goods_id ,'spec_image_id'=>$key,'src'=>$val,'store_id'=>$store_id));
            }
        }
        refresh_stock($goods_id); // 刷新商品库存
    }

    /**
     * [查询单件商品详情]
     * @Auther 蒋峰
     * @DateTime
     */
    public function findGoods($where){
        return $this->where($where)->find();
    }

    /**
     * [车辆展示价格]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     */
    public function carPrice($goods_id){
        if($this->goodsType($goods_id) != 1) return false;
        $priceArr = (new GoodsSku())->skuPrice($goods_id);
        $price = 0;
        foreach ($priceArr as $value){
            //商品展示价
            if($price == 0 ) $price = $value;
            elseif($price > $value && $value > 0) $price = $value;
        }
        $this->where(array('goods_id' => $goods_id))->save(array('price' => $price));
        return true;
    }

    /**
     * [车辆展示数量]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @return bool
     */
    public function carCount($goods_id){
        if($this->goodsType($goods_id) != 1) return false;
        $count = (new GoodsSku())->skuCount($goods_id);
        $goods = $this->where(array('goods_id' => $goods_id))->field('store_count,goods_name')->find();
        if($goods['store_count'] != $count){
            $stock = $count - $goods['store_count'];
            update_stock_log(0, $stock,array('goods_id'=>$goods_id,'goods_name'=>$goods['goods_name'],'store_id'=>0));
        }
        $this->where(array('goods_id' => $goods_id))->save(array('store_count' => $count));
        return true;
    }
    /**
     * [判断商品类型]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @return mixed
     */
    public function goodsType($goods_id){
        return $this->where(array('goods_id' => $goods_id))->getField('type');
    }
}
