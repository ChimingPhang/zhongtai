<?php
namespace app\api\logic;

use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use think\Model;
use think\Db;

class SecKillLogic extends Model
{
    public function lists()
    {

    }

    public function info()
    {

    }

    public function secKill()
    {

    }


    /**
     * 获取订单 order_sn
     * @return string
     */
    public function get_order_sn($prefix = '')
    {
        $order_sn = null;
        // 保证不会有重复订单号存在
        while(true){
            $order_sn = $prefix.date('YmdHis').rand(1000,9999); // 订单编号
            $order_sn_count = Db::name('auction_sign_up')->where("order_sn = '$order_sn'")->count();
            if($order_sn_count == 0)
                break;
        }
        return $order_sn;
    }

    /**
     * [获取活动商品经销商]
     * @Auther 蒋峰
     * @DateTime
     * @param $goods_id
     */
    public function getDealersKill($auction_id, $pid = 0, $type = 'province'){
        switch ($type) {
            case 'province':
                $dealers = $this->dealersKill($auction_id, 0, []);
                $regionIds = implode(',',array_column($dealers,"province"));
                $data = (new GoodsLogic())->getRegion($regionIds);
                break;
            case 'city':
                $dealers = $this->dealersKill($auction_id, ['province' => $pid]);
                $regionIds = implode(',',array_column($dealers,"city"));
                $data = (new GoodsLogic())->getRegion($regionIds);
                break;
            case 'distribu':
                $data = $this->dealersKill($auction_id, ['city' => $pid],'id,name');
                break;
            default :
                $data = [];
                break;
        }
        return $data;
    }

    /**
     * [商品经销商信息]
     * @Auther 蒋峰
     * @DateTime
     * @param $auction_id
     * @param array $where
     * @param string $field
     * @return mixed
     */
    public function dealersKill($auction_id, $where = [], $field = 'id,name,province,city')
    {
        $DealersAuction = M("DealersKill");
        $DealersAuction->alias('c')
            ->join('dealers d', 'c.dealers_id = d.id', 'left')
            ->group('c.dealers_id')
            ->where(array('c.kill_id' => $auction_id, 'status' => 1,'deleted'=>1))
            ->field($field);
        if($where)
            $DealersAuction->where($where);

        return $DealersAuction->select();
    }
}