<?php
namespace app\api\logic;

use app\api\model\GoodsSku;
use app\api\model\Region;
use think\Model;
use think\Db;

class GoodsLogic extends Model
{
    /**
     * [获取规格属性]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @return array
     */
    public function get_spec($goods_id){
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('SpecGoodsPrice')->where(['goods_id'=>$goods_id])->getField("GROUP_CONCAT(`key` ORDER BY store_count desc SEPARATOR '_') ");
        $filter_spec = array();
        if($keys)
        {
//            $specImage =  M('SpecImage')->where("goods_id",$goods_id)->where("src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_',',',$keys);
            $sql  = "SELECT a.name,a.order,b.* FROM __PREFIX__spec AS a INNER JOIN __PREFIX__spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY b.spec_id";
            $filter_spec2 = Db::query($sql);
            foreach ($filter_spec2 as $value ){
                $filter_spec[intval($value['spec_id'])] = [
                    "name" => $value['name'],
                ];
            }
            foreach ($filter_spec as &$value){
                foreach ($filter_spec2 as $val ){
                    if($value['name'] == $val['name'])
                        $value['son'][] = [
                            "id" => $val['id'],
                            "name" => $val['item'],
                        ];
                }
            }
        }
        return array_values($filter_spec);
    }

    /**
     * [获取货品数据]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @return mixed
     */
    public function get_spec_goods_price($goods_id){
        return M("SpecGoodsPrice")->where(array('goods_id' => $goods_id))->field('item_id,key,price,store_count,integral_price,integral')->select();
    }

    /**
     * [获取库存]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @param $key
     * @return mixed
     */
    public function get_spec_goods_count($goods_id,$key){
        return D("SpecGoodsPrice")->where(array('goods_id' => $goods_id))->where(array('key' => $key))->find();
    }

    /**
     * [获取汽车商品规格]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     */
    public function get_sku($goods_id, $pid = 0, $type = 'appearance', $sku_id = 0){
        $GoodsSku = new GoodsSku();
        switch ($type) {
            case 'appearance':
                $data = $GoodsSku->getSku($goods_id, $pid, 1, 'g.id,g.sku_name,g.sku_img');
                break;
            case 'displacement':
                $data = $GoodsSku->getSku($goods_id, $pid, 2, 'g.id,g.sku_name,g.sku_img');
                break;
            case 'model':
                $data = $GoodsSku->getSku($goods_id, $pid, 3, 'g.id,g.sku_name,g.sku_price');
                break;
            case 'interior':
                $data = $GoodsSku->getSku($goods_id, $pid, 4, 'id,sku_name,sku_count,sku_img');
                break;
            case 'province':
                $dealers = $this->dealersCar($goods_id, $pid, []);
                $regionIds = implode(',',array_column($dealers,"province"));
                $data = $this->getRegion($regionIds);
                break;
            case 'city':
                $dealers = $this->dealersCar($goods_id, $sku_id, ['province' => $pid]);
                $regionIds = implode(',',array_column($dealers,"city"));
                $data = $this->getRegion($regionIds);
                break;
            case 'distribu':
                $data = $this->dealersCar($goods_id, $sku_id, ['city' => $pid],'id,name');
                break;
            default :
                $data = [];
                break;
        }
        return $data;
    }

    /**
     * [获取销售车辆的商家]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     * @param $sku_id
     * @param array $where
     * @return null
     */
    public function dealersCar($goods_id, $sku_id, $where = [], $field = 'id,name,province,city')
    {
        $DealersCar = M("DealersCar");
        $DealersCar->alias('c')
            ->join('dealers d', 'c.dealers_id = d.id', 'left')
            ->group('c.dealers_id')
            ->where(array('c.goods_id' => $goods_id, 'c.sku_id' => $sku_id, 'status' => 1,'deleted'=>1))
            ->field($field);
        if($where)
            $DealersCar->where($where);

        return $DealersCar->select();
    }

    /**
     * [获取城市]
     * @Auther 蒋峰
     * @DateTime
     * @param $ids
     * @return mixed
     */
    public function getRegion($ids)
    {
        return M("Region")->where(array('id' => ['in', $ids]))->field('id,name')->select();
    }

    /**
     * [商品价格表]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_list
     */
    public function priceList($goods_list)
    {
        $goods_type = M("goods")->where(array("goods_id" => $goods_list))->getField('type');
        if($goods_type == 1){
            //获取所有规格
            $goods_spec_data = M("goods_sku")->where(array("goods_id" => $goods_list))->select();
            //获取要拼接的参数
            $data = $this->specName($goods_spec_data);
        }else{
            //获取所有规格
            $data = M("spec_goods_price")->where(array("goods_id" => $goods_list))->field("key_name,price")->select();
        }

        return $data;
    }

    /**
     * [拼接规格名称]  暂时没想到优化方案
     * @Auther 蒋峰
     * @DateTime
     * @param $data
     * @param int $level
     * @param array $array
     * @return array
     */
    public function specName($data, $level = 3, $array = [])
    {
        foreach ($data as $key => $val) {
            if($val['level'] == 3){
                foreach ($data as $k => $v) {
                    if($v['level'] == 2 && $v['parent_id_path'] == substr($val['parent_id_path'] , 0,  strrpos($val['parent_id_path'], "_"))) {
                        foreach ($data as $kk => $vv) {

                            if($vv['level'] == 1 && $vv['parent_id_path'] == substr($v['parent_id_path'] , 0,  strrpos($v['parent_id_path'], "_"))) {
                                $array[] = [
                                    "key_name" => $vv['sku_name'] . ' ' . $v['sku_name'] . ' ' . $val['sku_name'],
                                    "price" => $val['sku_price'],
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $array;
    }
}