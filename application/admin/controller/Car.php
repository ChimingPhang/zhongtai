<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 商业用途务必到官方购买正版授权, 使用盗版将严厉追究您的法律责任。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use app\admin\logic\SearchWordLogic;
use app\common\model\GoodsSku;
use think\AjaxPage;
use think\Cache;
use think\Page;
use think\Db;
use app\admin\model\Goods as GoodsModel;

class Car extends Base
{
    /*商品类型 1汽车 2配件*/
    private static $goodsType = 1;
    /**
     * [汽车列表]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function carList(){
        $GoodsLogic = new GoodsLogic();
        $brandList = $GoodsLogic->getSortBrands();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     * [汽车列表]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function ajaxCarList(){
        $where = ' state = 1 and type = '. self::$goodsType; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale'); //商品状态  0未上架 1已上架 2停售
        $cat_id = I('cat_id');
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
        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();

        $catList = M('goods_category')->cache(true)->select();
        $catList = convert_arr_key($catList, 'id');

        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    /*
     * 获取商品分类
     */
    public function get_sku()
    {
        $parent_id = I('get.parent_id/d', '0'); // 商品分类 父id
        empty($parent_id) && exit('');
        $list = M('goods_sku')->where(array('parent_id' => $parent_id))->select();
        $html = '';
        foreach ($list as $k => $v) {
            $html .= "<option value='{$v['id']}' rel='{$v['commission']}'>{$v['sku_name']}</option>";
        }
        exit($html);
    }
    /**
     * [删除规格]
     * @Auther 蒋峰
     * @DateTime
     */
    public function delCarSpec(){
        // 判断子分类
        $id = I('id/d');
        if (empty($id)) {
            $this->ajaxReturn(array('status' => -1, 'msg'   => '非法操作'));
        }
        $count = Db::name('goods_sku')->where("parent_id", $id)->count("id");
        if ($count > 0) {
            $this->ajaxReturn(array('status' => -1,'msg'   => '该属性下还有属性不得删除'));
        }
//        // 判断是否存在商品
//        $goods_count = Db::name('goods')->where(['cat_id1|cat_id2|cat_id3' => $id])->count('goods_id');
//        if ($goods_count > 0) {
//            $this->error('该属性下有商品不得删除!');
//        }
        $data = Db::name('goods_sku')->where("id", $id)->find();

        // 删除分类
        $del = Db::name('goods_sku')->where("id", $id)->delete();
        if ($del !== false) {
            //打印操作日志
            file_put_contents(getcwd() . '/runtime/car.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t删除了商品({$data['goods_id']})的属性:{$data['sku_name']}({$id})\n",FILE_APPEND);
            $this->ajaxReturn(array('status' => 1,'msg'   => '删除成功!!!'));
        } else {
            $this->ajaxReturn(array('status' => -1,'msg'   => '删除失败!!!'));
        }
    }

    /**
     * [验证是否是车辆商品]
     * @Auther 蒋峰
     * @DateTime
     */
    private function VerifyRequest($goods_id){
        //汽车商品编辑基础条件
        $where = array("goods_id" => $goods_id, "type" => self::$goodsType );

        if($goods_id > 0)
        {
            $c = M('goods')->where($where)->count();
            if($c == 0)
                $this->error("非法操作",U('Car/carList'));
        }

        return $where;
    }

    /**
     * [汽车规格列表]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function carSpecList(){
        $goods_id = I('goods_id',0);

        $where = $this->VerifyRequest($goods_id);
        $goods_info = (new GoodsModel)->findGoods($where);

//        $GoodsSku = new GoodsSku();
//        $cat_list = $GoodsSku->CarSpecSelect($goods_id);
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_sku_list($goods_id);

        $this->assign('cat_list',$cat_list);
        $this->assign('goods_info',$goods_info);
        return $this->fetch();
    }

    /**
     * [编辑汽车规格]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function carSpecEdit(){
        $GoodsLogic = new GoodsLogic();
        $db_prefix = C('database.prefix');
        $goods_id = I('goods_id', 0);
        $this->VerifyRequest($goods_id);

        if (IS_GET) {
            $goods_category_info = D('GoodsSku')->where('goods_id='.$goods_id.' and id=' . I('GET.id', 0))->find();
            $level_cat = $GoodsLogic->find_parent_sku($goods_category_info['id'],$goods_id); // 获取分类默认选中的下拉框
            $cat_list = M('GoodsSku')->where("parent_id = 0 and goods_id=".$goods_id)->select(); // 已经改成联动菜单

            $this->assign('level_cat', $level_cat);
            $this->assign('cat_list', $cat_list);
            $this->assign('goods_category_info', $goods_category_info);

            return $this->fetch('_spec');
            exit;
        }

        $GoodsCategory = D('GoodsSku'); //

        $type = $_POST['id'] > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新
        $id = input('id');
        //ajax提交验证
        if ($_GET['is_ajax'] == 1) {

            $data = input('post.');
            // 数据验证
            $validate = \think\Loader::validate('CarSku');
            if (!$validate->batch()->check($data)) {
                $error = $validate->getError();
                $error_msg = array_values($error);
                //  编辑
                $return_arr = array(
                    'status' => 0,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );
                $this->ajaxReturn($return_arr);
            } else {
                $GoodsCategory->data(input('post.'), true); // 收集数据
                $level = 0;
                $GoodsCategory->parent_id = 0;
                if($_POST['parent_id_1']) {$GoodsCategory->parent_id = $_POST['parent_id_1']; $level = 1; }
                if($_POST['parent_id_2']) {$GoodsCategory->parent_id = $_POST['parent_id_2']; $level = 2; }
                if($_POST['parent_id_3']) {$GoodsCategory->parent_id = $_POST['parent_id_3']; $level = 3; }

                //编辑判断
                if($type == 2){
                    $children_where = array(
                        'parent_id_path'=>array('like','%\_'.I('id')."\_%")
                    );
                    $children = M('GoodsSku')->where($children_where)->max('level');
                    if (I('parent_id_1')) {
                        $parent_level = M('GoodsSku')->where(array('id' => I('parent_id_1')))->getField('level', false);
                        if (($parent_level + $children - $level) > 4) {
                            $return_arr = array(
                                'status' => -1,
                                'msg'   => $parent_level.'车的规格属性最多为三级'.$children,
                                'data'  => '',
                            );
                            $this->ajaxReturn($return_arr);
                        }
                    }
                    if (I('parent_id_2')) {
                        $parent_level = M('GoodsSku')->where(array('id' => I('parent_id_2')))->getField('level', false);
                        if (($parent_level + $children - $level) > 4) {
                            $return_arr = array(
                                'status' => -1,
                                'msg'   => '车的规格属性最多为三级',
                                'data'  => '',
                            );
                            $this->ajaxReturn($return_arr);
                        }
                    }
                    if (I('parent_id_3')) {
                        $parent_level = M('GoodsSku')->where(array('id' => I('parent_id_3')))->getField
                        ('level', false);
                        if (($parent_level + $children - $level) > 4) {
                            $return_arr = array(
                                'status' => -1,
                                'msg'   => '车的规格属性最多为三级',
                                'data'  => '',
                            );
                            $this->ajaxReturn($return_arr);
                        }
                    }
                }
                //查找同级分类是否有重复分类
                $par_id = ($GoodsCategory->parent_id > 0) ? $GoodsCategory->parent_id : 0;
                $sameCateWhere = ['parent_id'=>$par_id , 'goods_id' => $goods_id, 'sku_name'=>$GoodsCategory['sku_name']];
                if($id > 0){
                    $sameCateWhere['id'] = array('<>' ,$id);
                }
                $same_cate = M('GoodsSku')->where($sameCateWhere)->find();

                if($same_cate){
                    $return_arr = array(
                        'status' => 0,
                        'msg' => '同级已有相同属性存在',
                        'data' => '',
                    );
                    $this->ajaxReturn($return_arr);
                }
                if ($id > 0 && $GoodsCategory->parent_id == $id) {
                    //  编辑
                    $return_arr = array(
                        'status' => 0,
                        'msg' => '上级属性不能为自己',
                        'data' => '',
                    );
                    $this->ajaxReturn($return_arr);
                }

                /*//编辑判断
                if ($type == 2) {
                    $children_where = array(
                        'parent_id_path' => array('like', '%\_' . $_POST['id'] . "\_%")
                    );
                    $cul_level = M('GoodsSku')->where(array('id' => $_POST['id']))->getField('level', false);
                    $children = M('GoodsSku')->where($children_where)->max('level');

                    if ($_POST['parent_id_1']) {
                        if ($children - $cul_level > 1) {
                            $return_arr = array(
                                'status' => 0,
                                'msg' => '商品属性最多为三级。该属性有三级属性，不能移至其他分类下.',
                                'data' => '',
                            );
                            $this->ajaxReturn($return_arr);
                        }
                    }
                    if ($_POST['parent_id_2']) {
                        if ($children - $cul_level > 0) {
                            $return_arr = array(
                                'status' => 0,
                                'msg' => '商品属性最多为三级',
                                'data' => '',
                            );
                            $this->ajaxReturn($return_arr);
                        }
                    }
                }*/
                if ($type == 2)
                    $GoodsCategory->isUpdate(true)->save(); // 写入数据到数据库 // 写入数据到数据库
                else {
                    $GoodsCategory->save(); // 写入数据到数据库
                    $_POST['id'] = $GoodsCategory->getLastInsID();
                }

                $GoodsLogic->refresh_sku($_POST['id']);
                $res = $GoodsCategory->where(array('id' => $id))->find();
                //删除规格缓存
                Cache::rm(md5(serialize(array('goods_id' => "$goods_id", 'parent_id' => "$GoodsCategory->parent_id", 'level'=>
                    "{$res['level']}"))));
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('url' => U('Admin/Car/carSpecList',['goods_id' => $goods_id])),
                );
                $this->ajaxReturn($return_arr);

            }
        }
    }

    /**
     * [汽车编辑]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function carEdit(){
        $Goods = new GoodsModel; //实例化商品模型
        $goods_id = I('goods_id',0);

        $where = $this->VerifyRequest($goods_id);

        //ajax提交验证
        if (I('is_ajax') && IS_POST) {
            // 数据验证
            $data =input('post.');
            $validate = \think\Loader::validate('admin/Car');
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
//                $data['exchange_integral'] = 2;
//                $data['shop_price'] = 0;
//                $data['cost_price'] = 0;
//                $data['price'] = 0;
//                $data['deposit_price'] = 0;
//            }elseif(in_array(1,$exchange_integral) && in_array(2,$exchange_integral)){
//                $data['exchange_integral'] = 2;
//                $data['shop_price'] = 0;
//                $data['cost_price'] = 0;
//                $data['price'] = 0;
//                $data['deposit_price'] = 0;
//            }elseif($aa == 2){
//                $data['shop_price'] = 0;
//                $data['cost_price'] = 0;
//                $data['price'] = 0;
//                $data['deposit_price'] = 0;
//            }else{
//                $data['exchange_integral'] = $aa;
//            }
//            if(in_array(0,$exchange_integral) && !in_array(1,$exchange_integral) && !in_array(2,$exchange_integral)){
//                $data['integral'] = 0;
//            }

            if($data['exchange_integral'] == 0){
                //纯金额
                $data['integral'] = 0;
                $data['integral_money'] = 0;
            }elseif($data['exchange_integral'] == 1){
                //积分和金额
                //$data['deposit_price'] = 0;
            }elseif($data['exchange_integral'] == 2){
                //纯积分
                $data['shop_price'] = 0;
                $data['cost_price'] = 0;
                $data['price'] = 0;
                $data['deposit_price'] = 0;
            }
            //echo $data['exchange_integral'] . '--' . $data['integral'];
//            if($data['weight'] == 0){
//                $this->error("商品重量不能为0");
//            }
            // $Goods->data($data, true); // 收集数据
//            $data_arr['on_time'] = time(); // 上架时间
            $data_arr['cat_id1'] = $data['cat_id']; //商品分类
            $data_arr['cat_id2'] = $data['cat_id_2'];
//            $data_arr['cat_id3'] = $data['cat_id_3'];
            $data_arr['goods_name'] = $data['goods_name']; //商品名称
            $data_arr['goods_sn'] = $data['goods_sn']; //商品编号
            $data_arr['goods_remark'] = $data['goods_remark']; //商品描述
            $data_arr['shop_price'] = $data['shop_price']; //本店价
            $data_arr['cost_price'] = $data['cost_price']; //成本价
            $data_arr['original_img'] = $data['original_img']; //商品图片
            $data_arr['deposit_price'] = $data['deposit_price']; //商品定金价格
//            $data_arr['weight'] = $data['weight']; //重量
            $data_arr['store_count'] = $data['store_count']; //库存
            //$data_arr['give_integral'] = $data['give_integral']; //赠送积分Hbq45987@#a.b-w+
            $data_arr['keywords'] = $data['keywords']; //关键字
            $data_arr['goods_content'] = $data['goods_content']; //商品详情
            $data_arr['equity_content'] = $data['equity_content']; //权益内容
            $data_arr['equity_desc'] = $data['equity_desc']; //权益说明
            $data_arr['label_id'] = $data['label_id']; //标签id
            $data_arr['exchange_integral'] = $data['exchange_integral']; //支付方式
            $data_arr['integral'] = $data['integral']; //使用的积分
            $data_arr['integral_money'] = $data['integral_money']; //使用积分的时候的商品金额
            $data_arr['sales_sum'] = $data['sales_sum']; //使用的积分
            $data_arr['label'] = $data['label']; //标签
            $data_arr['type'] = self::$goodsType; //商品类型
            $data_arr['sort'] = $data['sort']; //商品类型
            $type = I("post.goods_id") ? 2 : 1; //1 insert  2 update

            if ($type == 2) {
                // $goods = M('goods')->where(array('goods_id' => $goods_id, 'store_id' => STORE_ID))->find();
                $goods = M('goods')->where($where)->find();
                if ($goods) {
                    if(!empty($Goods->goods_sn)){
                        if(Db::name('goods')->where("goods_id != $goods_id and goods_sn='".$Goods->goods_sn."'")->count()>0){
                            $this->error("商品货号重复了", U('Car/carEdit',array('goods_id'=>$goods_id)));
                        }
                    }
                    // 修改商品后购物车的商品价格也修改一下
                    Db::name('cart')->where("goods_id", $goods_id)->save(array(
                        //'cost_price' => $data['cost_price'], //市场价
                       // 'shop_price' => $data['shop_price'], // 本店价
                        'exchange_integral' => $_POST['exchange_integral'], // 商品购买的方式
                    ));

                    $update = $Goods->where($where)->save($data_arr);
                    // 更新成功后删除缩略图
                    if($update !== false){
                        delFile("./public/upload/goods/thumb/$goods_id", true);
                    }
                    //打印操作日志
                    file_put_contents(getcwd() . '/runtime/car.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t修改了商品ID为'{$data['goods_id']}'的商品:{$data['goods_name']}\n",FILE_APPEND);
                } else {
                    $this->ajaxReturn(array('status' => -1, 'msg' => '非法操作'), 'JSON');
                }
            } else {
                if(!empty($Goods->goods_sn)){
                    if(Db::name('goods')->where("goods_sn='".$Goods->goods_sn."'")->count()>0){
                        $this->error("商品货号重复了", U('Goods/addEditGoods',array('goods_id'=>$goods_id)));
                    }
                }
                $data_arr['is_new'] = 1; //默认新品
                $Goods->save($data_arr); // 新增数据到数据库
                $goods_id = $Goods->getLastInsID();
                //商品进出库记录日志
                if (empty($_POST['item'])) {
                    update_stock_log(session('admin_id'), $_POST['store_count'], array('goods_id' => $goods_id, 'goods_name' => $_POST['goods_name'], 'store_id' => 0));
                }

                //打印操作日志
                file_put_contents(getcwd() . '/runtime/car.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t添加了商品ID为'{$goods_id}'的商品:{$data['goods_name']}\n",FILE_APPEND);
            }
             $Goods->afterSave($goods_id);
            // $GoodsLogic->saveGoodsAttr($goods_id, $type_id, 0); // 处理商品 属性
            $return_arr = array(
                'status' => 1,
                'msg' => '操作成功',
                'data' => array('url' => U('Car/carList')),
            );
            //重定向, 调整之前URL是设置参数获取方式
            session("is_back", 1);
            $this->ajaxReturn($return_arr);

        }

        $goodsInfo =M('Goods')->where($where)->find();
        if($goodsInfo) {
            $level_cat[1] = $goodsInfo['cat_id1'];
            $level_cat[2] = $goodsInfo['cat_id2'];
            $label = $goodsInfo['label_id'];
            $this->assign('level_cat',$level_cat);//已绑定的分类
            $this->assign('label',$label);//已绑定的分类
            $exchange_integral = explode(',',$goodsInfo['exchange_integral']);
            $this->assign('exchange_integral',$exchange_integral);  //积分的类型
        }
        $cat_list = M('goods_category')->where("parent_id = 0")->select();//一级车系
        $label_list = M('goods_label')->where("status = 1")->order("sort", "asc")->select();//标签
        $this->assign('label_list',$label_list);   // 所有标签
        $this->assign('cat_list',$cat_list);   // 所有分类
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
        $goodsImages = M("GoodsImages")->where('goods_id ='.$goods_id)->select();
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        return $this->fetch('_car');
    }

    /**
     * 首页的热卖车型的添加
     * @Autor: 胡宝强
     * Date: 2018/8/27 14:25
     * @return mixed
     */
    public function top(){
//        $GoodsLogic = new GoodsLogic();
//        $brandList = $GoodsLogic->getSortBrands();
//        $categoryList = $GoodsLogic->getSortCategory();
//        $this->assign('categoryList',$categoryList);
//        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     * [汽车列表]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function topList(){
//        $where = ' state = 1 and type = '. self::$goodsType; // 搜索条件
//        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
//        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale'); //商品状态  0未上架 1已上架 2停售
//        $cat_id = I('cat_id');
//        // 关键词搜索
//        $key_word = I('key_word') ? trim(I('key_word')) : '';
//        if($key_word)
//        {
//            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
//        }
//        if($cat_id > 0)
//        {
//            $where .= " and (cat_id1 = $cat_id or cat_id2 = $cat_id or cat_id3 = $cat_id ) "; // 初始化搜索条件
//        }
        $model = M('special');
        $count = $model->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "sort desc";
        $goodsList = $model->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();

        $catList = M('goods_category')->cache(true)->select();
        $catList = convert_arr_key($catList, 'id');
        $prom_type = config('PROM_TYPE');
        $this->assign('prom_type',$prom_type);
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 删除首页推荐商品
     * @Autor: 胡宝强
     * Date: 2018/8/27 18:31
     */
    public function delGoods()
    {
        $goods_id = $_GET['id'];

        // 删除此商品
        M("goods_special")->where('id ='.$goods_id)->delete();  //商品表

        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn($return_arr);
    }
}