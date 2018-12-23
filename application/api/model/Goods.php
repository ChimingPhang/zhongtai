<?php

/**
 * 商品动作类
 * @Author  蒋峰
 * @date  2018/7/12 0012 17:47
 * @FunctionName _initialize
 * @UpdateTime  date
 */

namespace app\api\model;

use think\Model;
use think\Request;

class Goods extends Model {

    /**
     * @var [每页展示数量]
     */
    private static $pageNum = 12;

    /**
     * [商品列表]
     * @Auther 蒋峰
     * @DateTime
     * @param int $page 页码
     * @param int $type 商品类型
     * @param array $where 检索条件
     * @param array $order 排序
     * @param int $num 数量
     * @param string $field 字段
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public function GoodsList($page = 1, $type = 0, $where = [], $order = [], $num = 0, $field = ''){
        if(!is_array($where)) return array('status'=>2002,'msg'=>'where');
        if(!is_array($order)) return array('status'=>2002,'msg'=>'order');
        if(!is_string($field)) return array('status'=>2002,'msg'=>'field');

        if($page < 1) return false;
        $limit = ($page - 1) * ($num ? $num : self::$pageNum );
        if( !isset($where['state']) ) $where['state'] = 1;
        if( !isset($where['is_on_sale']) ) $where['is_on_sale'] = 1;

        //商品类型
        if($type) $where['type'] = $type;

        //加入检索条件
        $this->where($where);

        //排序条件
        foreach ($order as $key => $value) {
            $this->order($key, $value);
        }
        $this->order('on_time', 'desc');
        //查询字段
        if(empty($field))
            $field = 'goods_id,cat_id1,cat_id2,class_id,goods_sn,goods_name,click_count,store_count,collect_sum,comment_count,weight,price,exchange_integral,goods_remark,goods_content,original_img,is_on_sale,is_recommend,is_new,is_hot,sales_sum,type';

        $this->field($field)->limit($limit, ($num ? $num : self::$pageNum ));

        if($num == 1) $goodsList = $this->find();
        else $goodsList = $this->select();

        return $goodsList;
        //return $this->getLastSql();
    }

    public function GoodsCount($type = 0, $where = []){
        if(!is_array($where)) return array('status'=>2002,'msg'=>'where');

        if( !isset($where['state']) ) $where['state'] = 1;
        if( !isset($where['is_on_sale']) ) $where['is_on_sale'] = 1;

        //商品类型
        if($type) $where['type'] = $type;

        //加入检索条件
        $this->where($where);

        $count = $this->count();

        return $count;
        //return $this->getLastSql();
    }

    /**
     * [判断商品类型]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @return mixed
     */
    public function goodsType($goods_id){
        return $this->where(array('goods_id' => $goods_id, 'is_on_sale'=>1))->getField('type');
    }

    /**
     * [添加商品点击数]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     */
    public function addClickCount($goods_id){
        $this->where(array('goods_id' => $goods_id))->setInc('click_count');
    }
}
