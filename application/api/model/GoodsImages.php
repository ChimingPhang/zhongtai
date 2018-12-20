<?php

/**
 * 立即购买-配件-动作类
 * @Author  郝钱磊
 * @date  2018/7/12 0012 17:47
 * @FunctionName _initialize
 * @UpdateTime  date
 */
namespace app\api\model;

use think\Model;


class GoodsImages extends Model
{
    /**
     * [获取商品轮播图]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @return mixed
     */
    public function getImage($goods_id){
        $image = $this->where(array('goods_id' => $goods_id))->field('image_url as ad_code')->select();
        return $image;
    }
}
