<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 当燃
 * Date: 2016-03-19
 */
namespace app\common\logic;
use think\Model;
use think\Db;
use think\Page;
/**
 *
 * Class orderLogic
 * @package common\Logic
 */
class StoreLogic extends Model
{
    /**
     * 更新店铺评分
     * @param $store_id
     */
    public function updateStoreScore($store_id){
        $store_where = array('store_id'=>$store_id,'deleted'=>0);
        $store['store_desccredit'] = M('order_comment')->where($store_where)->avg('describe_score');
        $store['store_servicecredit'] = M('order_comment')->where($store_where)->avg('seller_score');
        $store['store_deliverycredit'] = M('order_comment')->where($store_where)->avg('logistics_score');
        M('store')->where(array('store_id'=>$store_id))->save($store);
    }



    /**
     * 获取用户收藏的店铺
     * @author dyr
     * @param $user_id
     * @param null $sc_id
     * @return mixed
     */
    public function getCollectStore($user_id,$sc_id=null)
    {
        if(!empty($sc_id)){
            $store_collect_where['s.sc_id'] = $sc_id;
        }
        $store_collect_where['sc.user_id'] = $user_id;
        $count = M('store_collect')->alias('sc')
                ->join('__STORE__ s','s.store_id = sc.store_id' , 'LEFT')
                ->where($store_collect_where)
                ->count();
        $page = new Page($count,10);
        $show = $page->show();
        if ($count === 0){
            $return['result'] = array();
            $return['show'] = $show;
            return $return;
        }
        $store_collect_list = Db::name('store_collect')
            ->alias('sc')
            ->field('sc.log_id,s.store_id,s.store_qq,s.store_name,s.store_logo,s.store_avatar,s.store_qq,s.store_desccredit,s.store_servicecredit,
            s.store_deliverycredit,r1.name as province_name,r2.name as city_name,r3.name as district_name,s.deleted as goods_array,s.store_collect')
            ->join('__STORE__ s','s.store_id = sc.store_id')
            ->join('__REGION__ r1','r1.id = s.province_id', 'LEFT')
            ->join('__REGION__ r2 ','r2.id = s.city_id', 'LEFT')
            ->join('__REGION__ r3 ','r3.id = s.district', 'LEFT')
            ->where($store_collect_where)
            ->order('sc.add_time DESC')
            ->limit($page->firstRow,$page->listRows)
            ->cache(true,10)
            ->select();
        foreach($store_collect_list as $key=>$value){
            $store_collect_list[$key]['goods_array'] = $this->getStoreGoods($value['store_id'],3);
        }
        $return['result'] = $store_collect_list;
        $return['show'] = $show;
        return $return;
    }

    /**
     * 店铺街
     * @param null $sc_id 分类id
     * @param int $province_id
     * @param int $city_id
     * @param null $order
     * @param int $item 记录条数
     * @return mixed
     */
    public function getStoreList($sc_id = null, $province_id = 0, $city_id = 0, $order = null, $item = 10 , $key = '')
    {
        $store_where = array('s.store_state' => 1,'s.store_recommend'=>1);
        if (!empty($sc_id)) {
            $store_where['s.sc_id'] = $sc_id;
        }

        if (!empty($province_id)) {
            $store_where['s.province_id'] = $province_id;
        }

        if (!empty($city_id)) {
            $store_where['s.city_id'] = $city_id;
        }

        if($order){
            $orderBy['s.'.$order] = 'desc';
        }else{
            $orderBy = array('s.store_sort' => 'desc');
        }
        if(!empty($key)){
            $store_where['s.store_name'] = array('like' , "%$key%");
        }
        $store_count = M('store')->alias('s')->where($store_where)->count();
        $page = new Page($store_count, $item);
        $show = $page->show();
        $store_list = M('store')
            ->alias('s')
            ->field("s.store_id,s.store_qq,s.store_name,s.seo_description,s.store_logo,s.store_banner,s.store_aliwangwang,s.store_qq,s.store_desccredit,s.store_servicecredit,
            s.store_deliverycredit,r1.name as province_name,r2.name as city_name,r3.name as district_name,s.deleted as goods_array")
            ->join('__REGION__ r1 ',' r1.id = s.province_id' , 'LEFT')
            ->join('__REGION__ r2 ',' r2.id = s.city_id', 'LEFT')
            ->join('__REGION__ r3 ',' r3.id = s.district', 'LEFT')
            ->where($store_where)
            ->order($orderBy)
            ->limit($page->firstRow, $page->listRows)
            ->select();
        foreach ($store_list as $key => $value) {
            $store_list[$key]['goods_array'] =$this->getStoreGoods($value['store_id'], 4);
        }
        $return['result'] = $store_list;
        $return['show'] = $show;
        $return['pages'] = $page;
        return $return;
    }

    /**
     * 获取收藏商家数量
     * @param type $user_id
     * @param type $sc_id
     * @return type
     */
    public function getCollectNum($user_id, $sc_id=null)
    {
        if(!empty($sc_id)){
            $store_collect_where['s.sc_id'] = $sc_id;
        }
        $store_collect_where['sc.user_id'] = $user_id;
        $count = M('store_collect')->alias('sc')
                ->join('__STORE__ s','s.store_id = sc.store_id' , 'LEFT')
                ->where($store_collect_where)
                ->count();
        return $count;
    }

    /**
     * 店铺收藏or取消操作
     * @author dyr
     * @param $user_id
     * @param $store_id
     * @param $nickname
     * @return array
     */
    public function collectStoreOrNo($user_id, $store_id, $nickname)
    {
        if (empty($store_id) || empty($user_id)) {
            $res = array('status' => -1, 'msg' => '参数错误');
            return $res;
        }
        $store_collect_info = M('store_collect')->where(array('store_id' => $store_id, 'user_id' => $user_id))->find();
        if (empty($store_collect_info)) {
            //收藏
            $store_name = M('store')->where(array('store_id' => $store_id))->getField('store_name');
            $store_collect_data = array(
                'user_id' => $user_id,
                'store_id' => $store_id,
                'add_time' => time(),
                'store_name' => $store_name,
                'user_name' => $nickname
            );
            M('store_collect')->add($store_collect_data);
            M('store')->where(array('store_id' => $store_id))->setInc('store_collect');
            $res = array('status' => 1, 'msg' => '关注成功');
        } else {
            //取消收藏
            $store_collect = M('store')->where(array('store_id' => $store_id))->getField('store_collect');
            if ($store_collect > 0){
                M('store')->where(array('store_id' => $store_id))->setDec('store_collect');
            }
            M('store_collect')->where(array('store_id' => $store_id, 'user_id' => $user_id))->delete();
            $res = array('status' => 1, 'msg' => '取消成功');
        }
        return $res;
    }

    /**
     * 店铺街
     * @author dyr
     * @param $sc_id 店铺分类ID，可不传，不传将检索所有分类
     * @param int $p 分页
     * @param int $item 每页多少条记录
     * @param int $sale_order 排序方式
     * @param int $user_id
     * @param array $address  地址
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getStreetList($sc_id,$p=1,$item=10,$sale_order=0,$user_id=0,$address=[],$lng=0,$lat=0,$search_key='')
    {
        $store_where = array('s.store_state' => 1,'s.store_recommend'=>1);
        if (!empty($sc_id)) {
            $store_where['s.sc_id'] = $sc_id;
        }
        
        if(!empty($search_key)){
            $store_where['s.store_name'] = ['like' , "%$search_key%"];
        }

        if (!empty($address['province_id'])) {
            $store_where['s.province_id'] = $address['province_id'];
        }
        if (!empty($address['city_id'])) {
            $store_where['s.city_id'] = $address['city_id'];
        }
        $sale_order = $sale_order ? 'asc' : 'desc';
        $store_list = Db::name('store')->alias('s')
            ->field('s.store_id,s.store_avatar,s.store_phone,s.store_logo,s.store_name,s.store_desccredit,s.store_servicecredit,
						s.store_collect,s.store_deliverycredit,r1.name as province_name,r2.name as city_name,r3.name as district_name,longitude,latitude,
						s.deleted as goods_array,sc.add_time ,round(SQRT((POW((('.$lng.' - longitude)* 111),2))+  (POW((('.$lat.' - latitude)* 111),2))),2) AS distance') 
            ->join('__REGION__ r1 ', 'r1.id = s.province_id', 'LEFT')
            ->join('__REGION__ r2 ', 'r2.id = s.city_id', 'LEFT')
            ->join('__REGION__ r3 ', 'r3.id = s.district', 'LEFT')
            ->join('__STORE_COLLECT__ sc', 'sc.store_id = s.store_id AND sc.user_id = '.$user_id,'LEFT')
            ->where($store_where)
            ->page($p,$item)
            ->order("store_sales {$sale_order}, distance asc")
            ->select();

        return $store_list;
    }
    /**
     * 获取店铺商品详细
     * @param $store_id
     * @param $limit
     * @return mixed
     */
    public function getStoreGoods($store_id,$limit)
    {
        $goods_model = M('goods');
        $goods_where = array(
            'is_on_sale'=>1,
            'goods_state'=>1,
            'store_id'=>$store_id
        );
        $res['goods_list'] = $goods_model->field('goods_id,goods_name,shop_price,is_virtual')->where($goods_where)->limit($limit)->order('sort desc')->select();
        $count_where = array(
            'store_id'=>$store_id
        );
        $res['goods_count'] = $goods_model->where($count_where)->count();
        return $res;
    }

}