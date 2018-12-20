<?php

namespace app\admin\controller;

use app\admin\logic\GoodsLogic;
use app\admin\logic\SearchWordLogic;
use app\common\model\GoodsSku;
use think\AjaxPage;
use think\Page;
use think\Db;
use app\admin\model\Goods as GoodsModel;

class Parts extends Base
{
    /*商品类型 1汽车 2配件*/
    private static $goodsType = 2;

    /**
     * 配件列表
     * @Author  郝钱磊
     * @date  2018/7/13 0013 14:53
     * @return mixed
     * @FunctionName partsList
     * @UpdateTime  date
     */
    public function partsList(){
        $GoodsLogic = new GoodsLogic();
        $categoryList = $GoodsLogic->getSortCategory();
        $classList = M('accessories_category')->where('status', ['<>', -1])->cache(true)->select();
        $classList = convert_arr_key($classList, 'id');
        $this->assign('classList',$classList);
        $this->assign('categoryList',$categoryList);
        return $this->fetch();
    }


    public function ajaxPartsList()
    {
        $where = ' state = 1 and type = '. self::$goodsType; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale'); //商品状态  0未上架 1已上架 2停售
        $cat_id = I('cat_id');
        $class_id = I('class_id');
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
        }

        if($cat_id > 0)
        {
            $where .= " and (cat_id1 = $cat_id or cat_id2 = $cat_id or cat_id3 = $cat_id ) "; // 初始化搜索条件
        }
        if($class_id > 0)
        {
            $where .= " and (class_id = $class_id) "; // 初始化搜索条件
        }
        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();

//        foreach($goodsList as $key=>$value){
//            $goodsList[$key]['exchange_integral'] = explode(',',$value['exchange_integral']);
//        }
        $catList = M('goods_category')->cache(true)->select();
        $catList = convert_arr_key($catList, 'id');
        $classList = M('accessories_category')->where('status', ['<>', -1])->cache(true)->select();
        $classList = convert_arr_key($classList, 'id');
        $this->assign('catList',$catList);
        $this->assign('classList',$classList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput(){
        $GoodsLogic = new GoodsLogic();
        $goods_id = I('goods_id/d') ? I('goods_id/d') : 0;
        $this->VerifyRequest($goods_id);
        // var_dump(I('post.spec_arr/a');die;
        $str = $GoodsLogic->getSpecInput($goods_id ,I('post.spec_arr/a',[[]]));
        exit($str);
    }
    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = I('get.goods_id/d') ? I('get.goods_id/d') : 0;
        $this->VerifyRequest($goods_id);
        //$_GET['spec_type'] =  13;
        $specList = M('spec')->alias('s')
            ->join("tp_spec_type t "," s.id = t.spec_id","left")
            ->where(['t.type_id'=>$_GET['spec_type']])
            ->order('s.order desc')
            ->field("s.*")
            ->select();

        foreach($specList as $k => $v){
            $specList[$k]['spec_item'] = D('SpecItem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        }
        $items_id = M('SpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);

        // 获取商品规格图片
        if($goods_id)
        {
            $specImageList = M('SpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');
        }
        $this->assign('specImageList',$specImageList);
        $this->assign('items_ids',$items_ids);
        $this->assign('spec_type',$_GET['spec_type']);
        $this->assign('specList',$specList);
        return $this->fetch('ajax_spec_select');
    }
    /**
     * 商品修改添加
     * @Author  郝钱磊
     * @date  2018/7/13 0013 15:13
     * @return mixed
     * @FunctionName PartsEdit
     * @UpdateTime  date
     */
    public function PartsEdit()
    {
        $Goods = new GoodsModel; //
        $goods_id = I('goods_id',0);
        $type = $goods_id > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新

        //ajax提交验证
        if (I('is_ajax') && IS_POST) {


            // 数据验证
            $data =input('post.');
            $validate = \think\Loader::validate('admin/Parts');
            if (!$validate->batch()->check($data)) {
                $error = $validate->getError();
                $error_msg = array_values($error);
                $return_arr = array(
                    'status' => -1,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );
                $this->ajaxReturn($return_arr);
            }

//            $exchange_integral = $data['exchange_integral'];
//            $aa = implode(',',$exchange_integral);          //数组转成字符串
//
//            if(in_array(1,$exchange_integral) && in_array(2,$exchange_integral) && in_array(0,$exchange_integral)){
//                $data['exchange_integral'] = '0,1';
//            }elseif(in_array(0,$exchange_integral) && in_array(2,$exchange_integral)){
//                $data['exchange_integral'] = '2';
//            }elseif(in_array(1,$exchange_integral) && in_array(2,$exchange_integral)){
//                $data['exchange_integral'] = 2;
//            }else{
//                $data['exchange_integral'] = $aa;
//            }
//            if(in_array(0,$exchange_integral) && !in_array(1,$exchange_integral) && !in_array(2,$exchange_integral)){
//                $data['integral'] = 0;
//            }

            if($data['exchange_integral'] == 0){
                //纯金额
                $data['moren_integral'] = 0;
                $data['integral'] = 0;
            }elseif($data['exchange_integral'] == 1){
                //积分和金额
            }elseif($data['exchange_integral'] == 2){
                //纯积分
                $data['shop_price'] = 0;
                $data['cost_price'] = 0;
                $data['price'] = 0;
                $data['deposit_price'] = 0;
            }

            // // 判断产地
            // if(!empty($data['brand_name'])){
            //     unset($data['brand_name']);
            // }
//            if($data['weight'] == 0){
//                $this->error("商品重量不能为0");
//            }
            // $Goods->data($data, true); // 收集数据
//            $data_arr['on_time'] = time(); // 上架时间
            $data_arr['cat_id1'] = $data['cat_id']; //商品分类
            $data_arr['cat_id2'] = $data['cat_id_2'];
//            $data_arr['cat_id3'] = $data['cat_id_3'];
            $data_arr['class_id'] = $data['class_id'];
            $data_arr['goods_name'] = $data['goods_name']; //商品名称
            $data_arr['storage'] = $data['storage']; //存储方式
            $data_arr['goods_sn'] = $data['goods_sn']; //商品编号
            $data_arr['goods_remark'] = $data['goods_remark']; //商品描述
            $data_arr['shop_price'] = $data['shop_price']; //本店价
            $data_arr['plus_price'] = $data['plus_price']; //plus价
            $data_arr['market_price'] = $data['market_price']; //市场价
            $data_arr['cost_price'] = $data['cost_price']; //成本价
            $data_arr['original_img'] = $data['original_img']; //商品图片
            $data_arr['video_img'] = $data['video_img']; //商品视频
            //$data_arr['weight'] = $data['weight']; //重量
            $data_arr['key_name'] = $data['key_name']; //规格名称
//            $data_arr['store_count'] = $data['store_count']; //库存
            $data_arr['video_img'] = $data['video_img']; //商品视频
            //$data_arr['give_integral'] = $data['give_integral']; //赠送菜籽
            $data_arr['keywords'] = $data['keywords']; //关键字
            $data_arr['goods_content'] = $data['goods_content']; //商品描述
            $data_arr['goods_type'] = $data['goods_type']; //商品的类型
//            $data_arr['is_free_shipping'] = $data['is_free_shipping']; //商品是否包邮
//            $data_arr['template_id'] = $data['template_id']; //包邮的模板id
            $data_arr['is_free_shipping'] = 1; //商品是否包邮
            $data_arr['exchange_integral'] = $data['exchange_integral']; //支付方式
            $data_arr['integral'] = $data['moren_integral']; //使用的积分
            $data_arr['moren_integral'] = $data['moren_integral']; //使用的积分
            $data_arr['sales_sum'] = $data['sales_sum']; //使用的积分
            $data_arr['type'] = 2; //商品的类型
            $data_arr['sort'] = $data['sort']; //商品排序
            if ($type == 2) {
                // $goods = M('goods')->where(array('goods_id' => $goods_id, 'store_id' => STORE_ID))->find();
                $goods = M('goods')->where(array('goods_id' => $goods_id))->find();
                if ($goods) {
                    if(!empty($Goods->goods_sn)){
                        if(Db::name('goods')->where("goods_id != $goods_id and goods_sn='".$Goods->goods_sn."'")->count()>0){
                            $this->error("商品货号重复了", U('Parts/PartsEdit',array('goods_id'=>$goods_id)));
                        }
                    }
//                    // 修改商品后购物车的商品价格也修改一下
                    Db::name('cart')->where("goods_id", $goods_id)->save(array(
                        'market_price' => $_POST['market_price'], //市场价
                        'shop_price' => $_POST['shop_price'], // 本店价
                        //'plus_price' => $_POST['plus_price'], // 会员折扣价
                        'exchange_integral' => $_POST['exchange_integral'], // 商品购买的方式
                    ));

                    $update = $Goods->where(['goods_id'=>$goods_id])->save($data_arr);
                    // $update = $Goods->isUpdate(true)->save(); // 写入数据到数据库
                    // 更新成功后删除缩略图
                    if($update !== false){
                        file_put_contents(getcwd() . '/runtime/car.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t修改了商品ID为'{$data['goods_id']}'的商品:{$data['goods_name']}\n",FILE_APPEND);
                        delFile("./public/upload/goods/thumb/$goods_id", true);
                    }
                } else {
                    $this->ajaxReturn(array('status' => -1, 'msg' => '非法操作'), 'JSON');
                }
            } else {
                if(!empty($Goods->goods_sn)){
                    if(Db::name('goods')->where("goods_sn='".$Goods->goods_sn."'")->count()>0){
                        $this->error("商品货号重复了", U('Parts/PartsEdit',array('goods_id'=>$goods_id)));
                    }
                }
                $Goods->save($data_arr); // 新增数据到数据库
                $goods_id = $Goods->getLastInsID();
                //商品进出库记录日志
//                if (empty($_POST['item'])) {
//                    update_stock_log(session('admin_id'), $_POST['store_count'], array('goods_id' => $goods_id, 'goods_name' => $_POST['goods_name'], 'store_id' => 0));
//                }
                //打印操作日志
                file_put_contents(getcwd() . '/runtime/car.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t添加了商品ID为'{$goods_id}'的商品:{$data['goods_name']}\n",FILE_APPEND);
            }
            $Goods->afterSave($goods_id, 0);
            //获取这个配件商品的积分规则
            $exchange_integral = M('goods')->where(['goods_id'=>$goods_id])->getField('exchange_integral');
            $Goods->specSave($goods_id,$exchange_integral);
            // $GoodsLogic->saveGoodsAttr($goods_id, $type_id, 0); // 处理商品 属性
            $return_arr = array(
                'status' => 1,
                'msg' => '操作成功',
                'data' => array('url' => U('Parts/partsList')),
            );
            //重定向, 调整之前URL是设置参数获取方式
            session("is_back", 1);
            $this->ajaxReturn($return_arr);

        }

        $goodsInfo =M('Goods')->where('goods_id='.I('goods_id',0))->find();
        if($goodsInfo) {
            $level_cat[1] = $goodsInfo['cat_id1'];
            $level_cat[2] = $goodsInfo['cat_id2'];
            $level_cat[3] = $goodsInfo['cat_id3'];
            $this->assign('level_cat',$level_cat);//已绑定的分类
            $exchange_integral = explode(',',$goodsInfo['exchange_integral']);
            $this->assign('exchange_integral',$exchange_integral);  //积分的类型
        }


        $cat_list = M('goods_category')->where("parent_id = 0")->select();//自营店已绑定所有分类
        $part_list = M('accessories_category')->where("status = 1")->order('sort')->select();//自营店已绑定所有分类
        $goodsType = M("GoodsType")->select();
        $this->assign('cat_list',$cat_list);   // 所有分类
        $this->assign('part_list',$part_list);   // 配件分类
        $this->assign('goodsType',$goodsType);  //模型
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.I('GET.goods_id',0))->select();
        $freight_template = Db::name('freight_template')->select();
        $this->assign('freight_template',$freight_template); //订单物流模板
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        return $this->fetch('_parts');
    }

    public function del_goods_images()
    {
        M('GoodsImages')->where('img_id',I('img_id',0))->delete();
    }

    /**
     * 判断商品是否是配件商品
     * @Author  郝钱磊
     * @date  2018/7/13 0013 11:28
     * @param $goods_id
     * @return array
     * @FunctionName VerifyRequest
     * @UpdateTime  date
     */
    private function VerifyRequest($goods_id){
        //判断商品是否是配件商品
        $where = array("goods_id" => $goods_id, "type" => self::$goodsType );

        if($goods_id > 0)
        {
            $c = M('goods')->where($where)->count();
            if($c == 0)
                $this->error("非法操作",U('Car/carList'));
        }

        return $where;
    }
}
