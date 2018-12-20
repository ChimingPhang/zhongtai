<?php
/*
 * 车系
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class GoodsSku extends Model
{
    /**
     * [获取规格 由于数据结构原因无法优化]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @param int $pid
     * @param int $level
     * @param string $field
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getSku($goods_id, $pid = 0, $level = 1, $field = "*"){
        //EXPLAIN SELECT g.*,count(*) FROM tp_goods_sku AS g LEFT JOIN tp_goods_sku as gs on g.goods_id=gs.goods_id WHERE gs.goods_id = 3 AND gs.`level` = 4 AND g.`level` = 1 and gs.parent_id_path LIKE CONCAT(g.parent_id_path, '%') GROUP BY g.id

        if($level == 4){
            return $this->alias('g')->where(array('goods_id' => $goods_id, 'parent_id' => $pid, 'level'=> $level))
                ->field($field)
                ->select();
        }
        return $this->alias('g')->join('goods_sku gs','g.goods_id=gs.goods_id')
            ->where(array('g.goods_id' => $goods_id, 'g.parent_id' => $pid, 'g.level'=> $level,'gs.level' => 4))
            ->where('gs.parent_id_path like CONCAT(g.parent_id_path, \'%\')')
            ->field($field)
            ->group('g.id')
            ->cache(md5(serialize(array('goods_id' => "$goods_id", 'parent_id' => "$pid", 'level'=>
                "$level"))))
            ->select();
    }
}
