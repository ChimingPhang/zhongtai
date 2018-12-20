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
class GoodsSku extends Model {
    /**
     * [汽车规格价格]
     * @Auther 蒋峰
     * @DateTime
     */
    public function skuPrice($goods_id){
        return $this->where(array('goods_id' => $goods_id, "level" => 3))->getField('id,sku_price');
    }

    /**
     * [汽车规格数量]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id3
     */
    public function skuCount($goods_id){
        $skuList = $this->where(array('goods_id' => $goods_id, "level" => 4))->getField('id,sku_count,parent_id');
        $parent = [];
        $count = 0;
        foreach ($skuList as $value){
            $parent[$value['parent_id']] += $value['sku_count'];
            $count += $value['sku_count'];
        }
        foreach ($parent as $key => $value){
            $this->where(array("id" => $key))->save(array('sku_count' => $value));
        }
        return $count;
    }
}
