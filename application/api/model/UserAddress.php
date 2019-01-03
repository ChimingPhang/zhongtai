<?php
/*
 * 首页自定义的导航栏目
 * **/
namespace app\api\model;

use think\Db;
use think\Model;
use think\Request;
use think\Response;

class UserAddress extends Model
{

    protected $table = 'user_address';
    protected $request;//请求的参数

    /**
     * 初始化参数
     * @Autoh: 胡宝强
     * Date: 2018/7/15 12:23
     */
    public function _initialize()
    {
        $request = Request::instance();//获取当前请求的参数
        $this->request = $request;
    }

    /**
     * 获取收货地址列表
     * @Autoh: 胡宝强
     * Date: 2018/7/18 19:57
     * @param $user_id  用户ID
     * @return mixed
     */
    public function list($user_id){
        $data = M('user_address')->where(['user_id'=>$user_id,'status'=>1])
            ->field('address_id,consignee,mobile,province,city,district,address,is_default')
            ->select();
        foreach($data as $key=>$value){
            $data[$key]['address'] = get_province_city($value['province']) . '-'. get_province_city($value['city']) . '-' . get_province_city($value['district']) . ' ' . $value['address'];
        }
        return $data;
    }

    /**
     * 添加收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/18 20:07
     * @param $data         收货地址数据
     */
    public function add_address($data){
        if($data['is_default'] == 1){
            $arr = M($this->table)->where(['user_id'=>$data['user_id']])->select();
            if(!empty($arr)){
                M($this->table)->where(['user_id'=>$data['user_id']])->save(['is_default'=>0]);
//                echo M('user_address')->getLastSql();die;
            }
        }
        return M($this->table)->add($data);
    }

    /**
     * 显示收货地址信息
     * @Autoh: 胡宝强
     * Date: date
     * @param $user_id          用户id
     * @param $address_id       用户收货地址ID
     */
    public function edit_list($user_id,$address_id){
        $data = M($this->table)->where(['user_id'=>$user_id,'address_id'=>$address_id,'status'=>1])->find();
        $province = get_province_city($data['province']);
        $city = get_province_city($data['city']);
        $district = get_province_city($data['district']);
        $data['country'] = $province . $city . $district;
        return $data;
    }

    /**
     * 提交修改收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/18 20:48
     * @param array|mixed|string $user_id       用户ID
     * @param array $data                       修改数据
     */
    public function save_address($user_id,$data){
        if($data['is_default'] == 1){
           $address_id = M($this->table)->where(['user_id'=>$user_id,'is_default'=>1])->getField('address_id');
            M($this->table)->where(['address_id'=>$address_id])->save(['is_default'=>0]);
        }
        $data['update_time'] = time();
       return  M($this->table)->where(['user_id'=>$user_id,'address_id'=>$data['address_id']])->save($data);
    }

    /**
     * 设置为默认的收货地址，或者取消默认的收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/19 11:24
     * @param $user_id          用户id
     * @param $address_id       收货地址id
     */
    public function set_default($user_id,$address_id){
        $arr = M($this->table)->where(['user_id'=>$user_id,'address_id'=>$address_id])->field('is_default')->find();
        if($arr['is_default'] == 1){
            return M($this->table)->where(['address_id'=>$address_id])->save(['is_default'=>0]);
        }else{
            M($this->table)->where(['user_id'=>$user_id,'address_id'=>['not in',$address_id]])->save(['is_default'=>0]);
            return M($this->table)->where(['address_id'=>$address_id])->save(['is_default'=>1]);
        }
    }

    /**
     * 购买的时候获取用户的收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/19 14:26
     * @param $user_id      用户id
     */
    public function buy_address($user_id){
        $data = M($this->table)->where(['user_id'=>$user_id,'status'=>1,'is_default'=>1])->find();
        if($data){
            return $data;
        }else{
            return M($this->table)->where(['user_id'=>$user_id,'status'=>1])->order('id desc')->limit(1)->find();
        }
    }


    /**
     *  获取用户收货地址 用于购物车确认订单页面
     */
    public function ajaxAddress($user_id, $type=1)
    {
        //$type == 1是没有address_id
        //$type == 2 是有address_id
        if($type == 1){
            $arr['address_list'] = Db::name('UserAddress')->where(["user_id"=>$user_id,'status'=>1])->order('is_default desc')->select();
        }else{
            $arr['address_list'] = Db::name('UserAddress')->where(["address_id"=>$user_id,'status'=>1])->select();
        }

        if ($arr['address_list']) {
            $area_id = array();
            foreach ($arr['address_list'] as $val) {
                $area_id[] = $val['province'];
                $area_id[] = $val['city'];
                $area_id[] = $val['district'];
                $area_id[] = $val['twon'];
            }
            $area_id = array_filter($area_id);
            $area_id = implode(',', $area_id);
            $arr['regionList'] = Db::name('region')->where("id", "in", $area_id)->getField('id,name');
        }
        if($type == 1){
            $c = Db::name('UserAddress')->where(['user_id' => $user_id, 'is_default' => 1])->count(); // 看看有没默认收货地址
            if ((count($arr['address_list']) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
                $arr['address_list'][0]['is_default'] = 1;
        }

        $res = [];
        $address = $arr['regionList'][$arr['address_list'][0]['province']].'-'.$arr['regionList'][$arr['address_list'][0]['city']].'-'.$arr['regionList'][$arr['address_list'][0]['district']].'-'. $arr['address_list'][0]['address'];
        $res['id'] =  $arr['address_list'][0]['address_id'];
        $res['name'] =  $arr['address_list'][0]['consignee'];
        $res['mobile'] =  $arr['address_list'][0]['mobile'];
        $res['address'] =  $address;

        return $res;
    }

}
