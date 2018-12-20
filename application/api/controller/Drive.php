<?php

/**
 * 用户表
 * collection       我的收藏
 * collection_del   删除收藏
 */
namespace app\api\controller;
use think\Model;
use think\Request;
use app\api\model\GoodsCategory;
use app\api\model\Drive as Drives;
class Drive extends Base{

    public $table = 'appointment_drive';

    /**
     * 显示预约试驾列表
     * @Autor: 胡宝强
     * Date: 2018/8/7 14:41
     */
    public function showAppoint(){
        //$categoryModel = new GoodsCategory();
        //加载车系
        //$category = $categoryModel->get_name();
        $category = M('goods_category')->where(['level'=>2,'is_show'=>1])->field('id,name')->select();
        $this->json('000','获取车系成功',$category);
    }
    /**
     * 添加预约试驾
     * @Autor: 胡宝强
     * Date: 2018/8/7 14:04
     */
    public function addAppoint(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = I('post.');
        if(empty($data['name'])) return $this->errorMsg('2001','name');
        $this->moblie($data['mobile']);
        if(empty($data['category_id'])) return $this->errorMsg('2001','category_id');
        if(!is_numeric($data['category_id'])) return $this->errorMsg('2002','category_id');
        $category_name = M('goods_category')->where(['id'=>$data['category_id']])->getField('name');
        if($category_name){
            $data['category_name'] = $category_name;
        }else{
            $data['category_name'] = '众泰';
        }
        if(empty($data['province'])) return $this->errorMsg('2001','province');
        if(empty($data['city'])) return $this->errorMsg('2001','city');
        if(empty($data['dealers_id'])) return $this->errorMsg('2001','dealers_id');
        if(!is_numeric($data['dealers_id'])) return $this->errorMsg('2002','dealers_id');
        $dealers_name = M('dealers')->where(['id'=>$data['dealers_id']])->getField('name');
        if($dealers_name){
            $data['dealers_name'] = $dealers_name;
        }
        if(empty($data['sex'])) return $this->errorMsg('2001','sex');
        if(!is_numeric($data['sex'])) return $this->errorMsg('2002','sex');
        $this->is_email($data['email']);
        $data['add_time'] = time();
        $data['store_id'] = $data['dealers_id'];
        $arr = M($this->table)->add($data);
        if($arr){
            //用户支付, 发送短信给商家
            $res = checkEnableSendSms("9");
            if ($res) {
                if (!empty($data['mobile'])) {
                    $sender = $data['mobile'];
                    if($data['sex'] == 1){
                        $sex = '先生';
                    }else{
                        $sex = '女士';
                    }
                    $params = array('name' => $data['name'],'sex'=>$sex,'category_name'=>$data['category_name']);
                    XdsendSms("9", $sender, $params);
                }
            }
            $this->json('0000','预约成功','');
        }else{
            $this->json('9999','预约失败','');
        }
    }

    /**
     * 获取经销商的名称
     * @Autor: 胡宝强
     * Date: 2018/8/7 15:11
     */
    public function getDealers(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = I('post.');
        if(empty($data['province'])) return $this->errorMsg('2001','province');
        if(empty($data['city'])) return $this->errorMsg('2001','city');
        if(empty($data['cate_id2'])) return $this->errorMsg('2001','cate_id2');

        $model = new Drives();
        $dealers = $model->getDealerName($data['province'],$data['city'],$data['cate_id2']);
        if($dealers){
            $this->json('0000','获取成功',$dealers);
        }else{
            $this->json('0000','暂无经销商','');
        }
    }


    /**
     * 显示销售网点
     * @Autor: 胡宝强
     * Date: 2018/8/8 11:12
     */
    public function sales(){
        if (!Request::instance()->isPost()) $this->errorMsg('1006');
        $data = I('post.');
        if(empty($data['province'])) return $this->errorMsg('2001','province');
        if(empty($data['city'])) return $this->errorMsg('2001','city');
        if(empty($data['cate_id2'])) return $this->errorMsg('2001','cate_id2');

        $model = new Drives();
        $dealers = $model->getDealerNames($data['province'],$data['city'],$data['cate_id2']);
        if($dealers){
            $this->json('0000','获取成功',$dealers);
        }else{
            $this->json('0000','暂无经销商','');
        }
    }

    /**
     * 显示省份
     * @Autor: 胡宝强
     * Date: 2018/8/8 15:47
     */
    public function show_province(){
        $data = M('region')->where(['level'=>1])->field('id,name')->select();
        $this->json('0000','获取省份成功',$data);
    }

    /**
     * 获取城市的信息
     * @Autoh: 胡宝强
     * Date: 2018/7/19 11:39
     */
    public function check_children(){
        $id = I('id/d');
        if(empty($id)) return $this->errorMsg('2002','id');
        if(!is_numeric($id)) return $this->errorMsg('2002','id');
        $arr = M('region')->where(['parent_id'=>$id])->field('id,name')->select();
        if($arr) $this->json('0000','获取城市信息成功',$arr);
        else $this->json('9999','获取城市信息失败',array());
    }
}
