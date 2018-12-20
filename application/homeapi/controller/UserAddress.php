<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\api\controller;
use app\admin\controller\Ad;
use think\Model;
use app\api\model\UserAddress as Address;
use think\Request;
use think\Hook;
class UserAddress extends Base{
    public  $model,$user_id;

    public function __construct()
    {
        parent::__construct();
    }

    public function _initialize() {
       // parent::_initialize();
        Hook::listen('app_begin_origin');
        $this->model = new Address();
        $token = I('post.token');
        $this->user_id = $this->checkToken($token);
    }


    /**
     * 收货地址的列表
     * @Autoh: 胡宝强
     * Date: 2018/7/18 18:27
     */
    public function index(){
        $models = new Address();
        $token = I('token');
        $user_id = $this->checkToken($token);
        $data = $models->list($user_id);
        $this->json('0000','获取收货地址列表成功',$data);
    }

    /**
     * 显示添加收货地址的页面
     * @Autoh: 胡宝强
     * Date: 2018/7/20 10:14
     */
    public function show_add(){
        $data = M('region')->where(['level'=>1])->field('id,name')->select();
        $this->json('0000','获取省份成功',$data);
    }

    /**
     * 添加收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/18 19:58
     */
    public function add_address(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
            $data = I('post.');
            if(empty($data['consignee'])) return $this->errorMsg('2001','consignee');
            $this->moblie($data['mobile']);
            if(empty($data['province']) || empty($data['city']) || empty($data['district'])) return $this->errorMsg('2001','district');
            if(empty($data['address'])) return $this->errorMsg('2001','address');
            $user_id = $this->checkToken($data['token']);
            $data['user_id'] = $user_id;
            $models = new Address();
            $arr = $models->add_address($data);
            if($arr) $this->json('0000','添加收货地址成功',array());
            else $this->json('9999','添加收货地址失败',array());
    }

    /**
     * 显示修改的收货地址的信息
     * @Autoh: 胡宝强
     * Date: 2018/7/18 20:29
     */
    public function edit_address(){
        $address_id = I('post.address_id');
        if(!is_numeric($address_id)) return $this->errorMsg('2002','address_id');
        $models = new Address();
        $user_id = $this->checkToken(I('token'));
        $arr = $models->edit_list($user_id,$address_id);
        if($arr) $this->json('0000','获取收货地址信息成功',$arr);
        else $this->json('9999','获取收货地址信息失败',array());
    }

    /**
     * 提交修改收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/18 20:39
     */
    public function save_address(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $models = new Address();
        $data = I('post.');
        if(!is_numeric($data['address_id'])) return $this->errorMsg('2002','address_id');
        if(empty($data['consignee'])) return $this->errorMsg('2001','consignee');
        $this->moblie($data['mobile']);
        if(empty($data['province']) || empty($data['city']) || empty($data['district'])) return $this->errorMsg('2001','district');
        if(empty($data['address'])) return $this->errorMsg('2001','address');
        $user_id = $this->checkToken($data['token']);
        $arr = $models->save_address($user_id,$data);
        if($arr) $this->json('0000','修改收货地址信息成功',$arr);
        else $this->json('9999','修改收货地址信息失败',array());
    }

    /**
     * 设置为默认的收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/19 9:18
     */
    public function set_default(){
        $address_id = I('post.address_id');
        $models = new Address();
        $user_id = $this->checkToken(I('token'));
        $arr = $models->set_default($user_id,$address_id);
        if($arr) $this->json('0000','设置收货地址信息成功',$arr);
        else $this->json('9999','设置收货地址信息失败',array());
    }

    /**
     * 删除收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/19 11:36
     */
    public function del_address(){
        $address_id = I('address_id');
        if(!is_numeric($address_id)) return $this->errorMsg('2002','address_id');
        $arr = M('user_address')->where(['address_id'=>$address_id])->save(['status'=>0]);
        if($arr) $this->json('0000','删除收货地址信息成功',$arr);
        else $this->json('9999','删除收货地址信息失败',array());
    }

    /**
     * 获取城市的信息
     * @Autoh: 胡宝强
     * Date: 2018/7/19 11:39
     */
    public function check_children(){
        $id = I('id/d');
        if(!is_numeric($id)) return $this->errorMsg('2002','id');
        $arr = M('region')->where(['parent_id'=>$id])->field('id,name')->select();
        if($arr) $this->json('0000','获取城市信息成功',$arr);
        else $this->json('9999','获取城市信息失败',array());
    }

    /**
     * 购买的时候获取用户的收货地址
     * @Autoh: 胡宝强
     * Date: 2018/7/19 14:25
     */
    public function get_address(){
        $models = new Address();
        $models->buy_address($this->user_id);
    }

    /**
     * 获取所有的城市的数组
     * @Autoh: 胡宝强
     * Date: 2018/7/26 15:56
     */
    public function address_ad(){
        $data = [];
       $province = M('region')->where(['level'=>1])->field('id as v,name as n')->select();
        $data['c'] = $province;
        foreach($province as $key=>$value){
            $city = M('region')->where(['parent_id'=>$value['v']])->field('id as v,name as n')->select();
            $data['c'][$key]['c'] = $city;
            foreach($city as $k=>$v){
                $distruct = M('region')->where(['parent_id'=>$v['v']])->field('id as v,name as n')->select();
                $data['c'][$key]['c'][$k]['c'] = $distruct;
            }
        }

        $this->json('0000','获取城市信息成功',$data);
    }

}
