<?php

/**
 * 预约试驾
 */

namespace app\api\model;

use think\Model;
use think\Request;

class Drive extends Model {

    /**
     * 根据省份城市和车型获取到经销商
     * @Autor: 胡宝强
     * Date: 2018/8/8 10:09
     * @param $province         省份
     * @param $city             城市
     * @param $cate_id2         车型
     */
    public function getDealerName($province,$city,$cate_id2 = ''){

        if($cate_id2){
            $where['g.cat_id2'] = $cate_id2;
        }
        $where['d.status'] = 1;
        $where['d.deleted'] = 1;
        $where['d.province'] = $province;
        $where['d.city'] = $city;
        //根据车型和省市查询经销商
        //车型在goods表中，然在dealers_car 中找到dealers_id 再dealers 中根据省市找到经销商
        $dealers = M('goods')->alias('g')
            ->join("tp_dealers_car c",'c.goods_id = g.goods_id','right')
            ->join("tp_dealers d","d.id = c.dealers_id",'right')
            ->field('d.id,d.name')
            ->where($where)
            ->group("d.id")
            ->select();
        return $dealers;
    }

    /**
     * 根据省份城市和车型获取到经销商
     * @Autor: 胡宝强
     * Date: 2018/8/8 10:09
     * @param $province         省份
     * @param $city             城市
     * @param $cate_id2         车型
     */
    public function getDealerNames($province,$city,$cate_id2 = ''){

        if($cate_id2){
            $where['g.cat_id2'] = $cate_id2;
        }
        $where['d.status'] = 1;
        $where['d.deleted'] = 1;
        $where['d.province'] = $province;
        $where['d.city'] = $city;
        //根据车型和省市查询经销商
        //车型在goods表中，然在dealers_car 中找到dealers_id 再dealers 中根据省市找到经销商
        $dealers = M('goods')->alias('g')
            ->join("tp_dealers_car c",'c.goods_id = g.goods_id','right')
            ->join("tp_dealers d","d.id = c.dealers_id",'right')
            ->field('d.id,d.name,d.longitude,d.address,d.mobile')
            ->where($where)
            ->group("d.id")
            ->select();
        return $dealers;
    }
}
