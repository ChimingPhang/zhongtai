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
namespace app\seller\controller;

use app\seller\logic\GoodsCategoryLogic;
use app\seller\logic\GoodsLogic;
use think\Db;
use think\Page;
use think\AjaxPage;
use think\Loader;

class Goods extends Base
{

    /**
     * 删除分类
     */
    public function delGoodsCategory()
    {
        // 判断子分类
        $GoodsCategory = M("GoodsCategory");
        $count = $GoodsCategory->where("parent_id", $_GET['id'])->count("id");
        $count > 0 && $this->error('该分类下还有分类不得删除!', U('Admin/Goods/categoryList'));
        // 判断是否存在商品
        $goods_count = M('Goods')->where("cat_id", $_GET['id'])->count('1');
        $goods_count > 0 && $this->error('该分类下有商品不得删除!', U('Admin/Goods/categoryList'));
        // 删除分类
        $GoodsCategory->where("id", $_GET['id'])->delete();
        $this->success("操作成功!!!", U('Admin/Goods/categoryList'));
    }

    /**
     *  商品列表
     */
    public function goodsList()
    {
        checkIsBack();
        //车系
        $category1 = M('goods_category')->where(['is_show'=>1,'parent_id'=>0])->select();
        //$category2 = M('goods_category')->where(['is_show'=>1,'parent_id'=>0])->select();

//        $array = $this->getTree($category);
//        $select = '';
//        foreach ($array AS $var)
//        {
//            $select .= '<option value="' . $var['id'] . '" >';
//            if ($var['level'] > 0)
//            {
//                $select .= str_repeat('&nbsp;', $var['levels']*4);
//            }
//            $select .= htmlspecialchars(addslashes($var['name'])) . '</option>';
//        }
        $this->assign('category',$category1);
        return $this->fetch('goodsList');
    }

    /**
     * 获取车系的分类
     * @Autoh: 胡宝强
     * Date: 2018/7/14 17:30
     */
    public function get_cate()
    {
        $pid=I("post.pid");
        $region=M("goods_category")->where(array("parent_id"=>$pid))->select();
        if ($region)
        {
            $data = '<option value="0">请选择车系</option>';
            foreach ($region as $key => $v)
            {
                $data.="<option value='".$v['id']."'>".$v['name']."</option>";
            }
            echo $data;
        }
    }

    public function getTree($array, $pid =0, $level = 0){

        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($array as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['parent_id'] == $pid){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['levels'] = $level;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($array[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                $this->getTree($array, $value['id'], $level+1);

            }
        }
        return $list;
    }


    /**
     *  商品列表
     */
    public function ajaxGoodsList()
    {
//        //$where['store_id'] = STORE_ID;
//        $intro = I('intro', 0);
//        $store_cat_id1 = I('store_cat_id1', '');
//        $key_word = trim(I('key_word', ''));
//        $orderby1 = I('post.orderby1', '');
//        $orderby2 = I('post.orderby2', '');
//        $suppliers_id = input('suppliers_id','');
//        if($suppliers_id !== ''){
//            $where['suppliers_id'] = $suppliers_id;
//        }
//        if (!empty($intro)) {
//            $where[$intro] = 1;
//        }
//        if ($store_cat_id1 !== '') {
//            $where['store_cat_id1'] = $store_cat_id1;
//        }
//        //$where['is_on_sale'] = 1;
//        //$where['goods_state'] = 1;
//        if ($key_word !== '') {
//            $where['goods_name|goods_sn'] = array('like', '%' . $key_word . '%');
//        }
//        $order_str = array();
//        if ($orderby1 !== '') {
//            $order_str[$orderby1] = $orderby2;
//        }
//        $model = M('Goods');


        $cat_id1 = I('cat_id1');
        if($cat_id1){
            $where['g.cat_id1'] = $cat_id1;
        }
        $cat_id2 = I('cat_id2');
        if($cat_id2){
            $where['g.cat_id2'] = $cat_id2;
        }

        $key_word = I('key_word');
        if($key_word){
            $where['goods_name'] = ['like',"%$key_word%"];
        }
        $dealers_car = M('dealers_car')->where(['dealers_id'=>STORE_ID])->field('sku_id')->select();
        $idd = '';
        foreach($dealers_car as $key=>$value){
            $idd.=$value['sku_id']. ',';
        }
        $where['gs.id'] = ['in',$idd];
        $where['g.state'] = 1;
        $where['g.is_on_sale'] = 1;
        $count = M('goods_sku')->alias('gs')
            ->join('tp_goods g','gs.goods_id = g.goods_id')
            ->where($where)
            //->group('gs.goods_id')
            ->count();
        $Page = new AjaxPage($count, 10);

        //是否从缓存中获取Page
        if (session('is_back') == 1) {
            $Page = getPageFromCache();
            //重置获取条件
            delIsBack();
        }
        //$goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $goodsList = M('goods_sku')->alias('gs')
            ->join('tp_goods g','gs.goods_id = g.goods_id')
            ->where($where)
            //->group('gs.goods_id')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->field("gs.*,g.goods_name,g.cat_id1,g.cat_id2,g.class_id,g.type,g.is_on_sale")
            ->select();
        //dump($goodsList);die;
        cachePage($Page);
        $show = $Page->show();

        //车系
        $catList =  M('goods_category')->cache(true)->select();
        $catList = convert_arr_key($catList, 'id');

        //配件分类
        $classList =  M('accessories_category')->cache(true)->select();
        $classList = convert_arr_key($classList, 'id');

        $this->assign('catList', $catList);
        $this->assign('classList', $classList);
        $this->assign('goodsList', $goodsList);
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }
    
    public function goods_offline(){
    	$where['store_id'] = STORE_ID;
    	$model = M('Goods');
        $suppliers_id = input('suppliers_id');
        if($suppliers_id){
            $where['suppliers_id'] = $suppliers_id;
        }
    	if(I('is_on_sale') == 2){
    		$where['is_on_sale'] = 2;
    	}else{
  			$where['is_on_sale'] = 0;
    	}
    	$goods_state = I('goods_state', '', 'string'); // 商品状态  0待审核 1审核通过 2审核失败
    	if($goods_state != ''){
    		$where['goods_state'] = intval($goods_state);
    	}
    	$store_cat_id1 = I('store_cat_id1', '');
    	if ($store_cat_id1 !== '') {
    		$where['store_cat_id1'] = $store_cat_id1;
    	}
    	$key_word = trim(I('key_word', ''));
    	if ($key_word !== '') {
    		$where['goods_name|goods_sn'] = array('like', '%' . $key_word . '%');
    	}
    	$count = $model->where($where)->count();
    	$Page = new Page($count, 10);
    	$goodsList = $model->where($where)->order('goods_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    	$show = $Page->show();
    	$store_goods_class_list = M('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select();
    	$this->assign('store_goods_class_list', $store_goods_class_list);
    	$suppliers_list = M('suppliers')->where(array('store_id'=>STORE_ID))->select();
    	$this->assign('suppliers_list', $suppliers_list);
		$this->assign('state',C('goods_state'));
    	$this->assign('goodsList', $goodsList);
    	$this->assign('page', $show);// 赋值分页输出
    	return $this->fetch();
    } 

    public function stock_log()
    {
        $map['store_id'] = STORE_ID;
        $mtype = I('mtype');
        if ($mtype == 1) {
            $map['stock'] = array('gt', 0);
        }
        if ($mtype == -1) {
            $map['stock'] = array('lt', 0);
        }
        $goods_name = I('goods_name');
        if ($goods_name) {
            $map['goods_name'] = array('like', "%$goods_name%");
        }
        $ctime = urldecode(I('post.ctime'));
        if ($ctime) {
            $gap = explode(' - ', $ctime);
            $this->assign('ctime', $gap[0] . ' - ' . $gap[1]);
            $this->assign('start_time', $gap[0]);
            $this->assign('end_time', $gap[1]);
            $map['ctime'] = array(array('gt', strtotime($gap[0])), array('lt', strtotime($gap[1])));
        }
        $count = Db::name('stock_log')->where($map)->count();
        $Page = new Page($count, 20);
        $show = $Page->show();
        $this->assign('page', $show);// 赋值分页输出
        $stock_list = Db::name('stock_log')->where($map)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('stock_list', $stock_list);
        return $this->fetch();
    }
    
    public function stock_list(){
    	$map['store_id'] = STORE_ID;
    	$goods_name = I('goods_name');
    	$spec_name = I('spec_name');
    	if ($goods_name) {
    		$map['goods_name'] = array('like', "%$goods_name%");
    		$gids = M('goods')->where($map)->getField('goods_id',true);
            unset($map['goods_name']);
    		if($gids){
    			$map['goods_id'] = array('in',$gids);
    		}
    	}
    	if($spec_name){
    		$map['key_name'] = array('like', "%$spec_name%");
    	}
    	$count = Db::name('spec_goods_price')->where($map)->count();
    	$Page = new Page($count, 20);
    	$show = $Page->show();
    	$this->assign('page', $show);// 赋值分页输出
    	$stock_list = Db::name('spec_goods_price')->where($map)->order('item_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
    	$this->assign('stock_list', $stock_list);
    	if($stock_list){
    		$goodsid = get_arr_column($stock_list, 'goods_id');
    		$goods = M('goods')->where(array('goods_id'=>array('in',$goodsid)))->getField('goods_id,goods_name');
    		$this->assign('goods',$goods);
    	}
    	return $this->fetch();
    }
    
    public function updateGoodsStock(){
    	$item_id = I('item_id/d');
    	$store_count = I('store_count/d');
    	$old_stock = I('old_stock');
    	$spec_goods = Db::name('spec_goods_price')->alias('s')->field('s.*,g.goods_name')->join('__GOODS__ g', 'g.goods_id = s.goods_id', 'LEFT')->where(['s.item_id'=>$item_id])->find();
    	$r = M('spec_goods_price')->where(array('item_id'=>$item_id))->save(array('store_count'=>$store_count));
    	if($r){
    		$stock = $store_count - $old_stock;
    		$goods = array('goods_id'=>$spec_goods['goods_id'],'goods_name'=>$spec_goods['goods_name'],'key_name'=>$spec_goods['key_name'],'store_id'=>STORE_ID);
    		update_stock_log(STORE_ID, $stock,$goods);
    		exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
    	}else{
    		exit(json_encode(array('status'=>0,'msg'=>'修改失败')));
    	}
    }

    /**
     *
     */
    public function addStepOne(){
        //限制发布商品数量，0为不限制
        $alreadyPushNum = Db::name('goods')->count();
        $sgGoodsLimit = Db::name('store_grade')->where(['sg_id' => $this->storeInfo['grade_id']])->getField('sg_goods_limit');
        if($alreadyPushNum >= $sgGoodsLimit && $sgGoodsLimit > 0 && $this->storeInfo['is_own_shop'] !=1){
            $this->error("可发布商品数量已达到上限", U('Goods/goodsList'));
        }
        $goods_id = input('goods_id');
        if($goods_id){
            $goods = Db::name('goods')->where('goods_id',$goods_id)->find();
            $this->assign('goods',$goods);
        }
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $this->assign('goodsCategoryLevelOne',$goodsCategoryLevelOne);
        return $this->fetch();
    }

    /**
     * 添加修改商品
     */
    public function addEditGoods()
    {
        $goods_id = I('goods_id/d', 0);
        $goods_cat_id3 = I('cat_id3/d', 0);
        if(empty($goods_id)){
            if(empty($goods_cat_id3)){
                $this->error("您选择的分类不存在，或没有选择到最后一级，请重新选择分类。", U('Goods/addStepOne'));
            }
            $goods_cat[0] = Db::name('goods_category')->where('id', $goods_cat_id3)->find();
            $goods_cat[1] = Db::name('goods_category')->where('id', $goods_cat[0]['parent_id'])->find();
            $goods_cat[2] = Db::name('goods_category')->where('id', $goods_cat[1]['parent_id'])->find();
        }else{
            $Goods = new \app\seller\model\Goods();
            $goods = $Goods->where(['goods_id' => $goods_id, 'store_id' => STORE_ID])->find();
            if(empty($goods)){
                $this->error("非法操作", U('Goods/goodsList'));
            }else{
                $this->assign('goodsInfo', $goods);  // 商品详情
            }
            $goods_cat = Db::name('goods_category')->where('id','IN',[$goods['cat_id1'],$goods['cat_id2'],$goods['cat_id3']])->order('level desc')->select();
        }
        $GoodsLogic = new GoodsLogic();
        $store_goods_class_list = Db::name('store_goods_class')->where(['parent_id' => 0, 'store_id' => STORE_ID])->select(); //店铺内部分类
        $brandList = $GoodsLogic->getSortBrands();
        $goodsType = Db::name("GoodsType")->select();
        $suppliersList = Db::name("suppliers")->select();
        $goodsImages = Db::name("GoodsImages")->where('goods_id', $goods_id)->select();
        $freight_template = Db::name('freight_template')->where(['store_id' => STORE_ID])->select();
        $this->assign('freight_template',$freight_template);
        $this->assign('goods_cat', $goods_cat);
        $this->assign('store_id', STORE_ID);
        $this->assign('store_goods_class_list', $store_goods_class_list);
        $this->assign('brandList', $brandList);
        $this->assign('goodsType', $goodsType);
        $this->assign('suppliersList', $suppliersList);
        $this->assign('goodsImages', $goodsImages);  // 商品相册
        return $this->fetch('_goods');
    }

    /**
     * 商品保存
     */
    public function save(){
        // 数据验证
        $data =input('post.');
        $goods_id = input('post.goods_id');
        $goods_cat_id3 = input('post.cat_id3');
        $spec_goods_item = input('post.item/a',[]);
        $store_count = input('post.store_count');
        $is_virtual = input('post.is_virtual');
        $virtual_indate = I('post.virtual_indate');//虚拟商品有效期
        $exchange_integral = I('post.exchange_integral');//虚拟商品有效期
        $validate = Loader::validate('Goods');
        $data['store_id'] = STORE_ID;
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
        $data['on_time'] = time(); // 上架时间
        $type_id = M('goods_category')->where("id", $goods_cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        $stores = M('store')->where(array('store_id' => STORE_ID))->getField('store_id , goods_examine,is_own_shop' , 1);
        $store_goods_examine = $stores[STORE_ID]['goods_examine'];
        if ($store_goods_examine) {
            $data['goods_state'] = 0; // 待审核
            $data['is_on_sale'] = 0; // 下架
        } else {
            $data['goods_state'] = 1; // 出售中
        }
        //总平台自营标识为2 , 第三方自营店标识为1
        $is_own_shop = (STORE_ID == 1) ? 2 : ($stores[STORE_ID]['is_own_shop']);
        $data['is_own_shop'] = $is_own_shop;
        $data['goods_type'] = $type_id ? $type_id : 0;
        $data['virtual_indate'] = !empty($virtual_indate) ? strtotime($virtual_indate) : 0;
        $data['exchange_integral'] = ($is_virtual == 1) ? 0 : $exchange_integral;
        if ($goods_id > 0) {
            $Goods = \app\seller\model\Goods::get(['goods_id' => $goods_id, 'store_id' => STORE_ID]);
            if(empty($Goods)){
                $this->ajaxReturn(array('status' => 0, 'msg' => '非法操作','result'=>''));
            }
            if (empty($spec_goods_item) && $store_count != $Goods['store_count']) {
                $real_store_count = $store_count - $Goods['store_count'];
                update_stock_log(session('admin_id'), $real_store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            } else {
                unset($data['store_count']);
            }
            $Goods->data($data, true); // 收集数据
            $update = $Goods->save(); // 写入数据到数据库
            // 更新成功后删除缩略图
            if($update !== false){
                // 修改商品后购物车的商品价格也修改一下
                Db::name('cart')->where("goods_id", $goods_id)->where("spec_key = ''")->save(array(
                    'market_price' => $Goods['market_price'], //市场价
                    'goods_price' => $Goods['shop_price'], // 本店价
                    'member_goods_price' => $Goods['shop_price'], // 会员折扣价
                ));
                delFile("./public/upload/goods/thumb/$goods_id", true);
            }
        } else {
            $Goods = new \app\seller\model\Goods();
            $Goods->data($data, true); // 收集数据
            $Goods->save(); // 新增数据到数据库
            $goods_id = $Goods->getLastInsID();
            //商品进出库记录日志
            if (empty($spec_goods_item)) {
                update_stock_log(session('admin_id'), $store_count, array('goods_id' => $goods_id, 'goods_name' => $Goods['goods_name'], 'store_id' => STORE_ID));
            }
        }
        $Goods->afterSave($goods_id, STORE_ID);
        $GoodsLogic = new GoodsLogic();
        $GoodsLogic->saveGoodsAttr($goods_id, $type_id); // 处理商品 属性
        $this->ajaxReturn([ 'status' => 1, 'msg' => '操作成功', 'result' => ['goods_id'=>$Goods->goods_id]]);
    }

    /**
     * 更改指定表的指定字段
     */
    public function updateField()
    {
        $primary = array(
            'goods' => 'goods_id',
            'goods_attribute' => 'attr_id',
            'ad' => 'ad_id',
        );
        $id = I('id/d', 0);
        $field = I('field');
        $value = I('value');
        Db::name($_POST['table'])->where($primary[$_POST['table']], $id)->where('store_id', STORE_ID)->save(array($field => $value));
        $return_arr = array(
            'status' => 1,
            'msg' => '操作成功',
            'data' => array('url' => U('Goods/goodsAttributeList')),
        );
        $this->ajaxReturn($return_arr);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput()
    {
        $cat_id3 = I('cat_id3/d', 0);
        $goods_id = I('goods_id/d', 0);
        empty($cat_id3) && exit('');
        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('');
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($goods_id, $type_id);
        exit($str);
    }

    /**
     * 删除商品
     */
    public function delGoods()
    {
        $ids= I('ids');
        $GoodsLogic = new GoodsLogic();
        $res = $GoodsLogic->delStoreGoods($ids);
        $this->ajaxReturn($res);
    }

    /**
     * ajax 获取 品牌列表
     */
    public function getBrandByCat()
    {
        $db_prefix = C('database.prefix');
        $type_id = I('type_id/d');
        if ($type_id) {
//            $list = M('brand')->join("left join {$db_prefix}brand_type on {$db_prefix}brand.id = {$db_prefix}brand_type.brand_id and  type_id = $type_id")->order('id')->select();
            $list = Db::name('brand')->alias('b')->join('__BRAND_TYPE__ t', 't.brand_id = b.id', 'LEFT')->where(['t.type_id' => $type_id])->order('b.id')->select();
        } else {
            $list = M('brand')->order('id')->select();
        }
//        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}brand) ")->getField("id,name,parent_id");
        $goods_category_list = Db::name('goods_category')
            ->where('id', 'IN', function ($query) {
                $query->name('brand')->where('')->field('cat_id1');
            })
            ->getField("id,name,parent_id");
        $goods_category_list[0] = array('id' => 0, 'name' => '默认');
        asort($goods_category_list);
        $this->assign('goods_category_list', $goods_category_list);
        $this->assign('type_id', $type_id);
        $this->assign('list', $list);
        return $this->fetch();
    }


    /**
     * ajax 获取 规格列表
     */
    public function getSpecByCat()
    {

        $db_prefix = C('database.prefix');
        $type_id = I('type_id/d');
        if ($type_id) {
//            $list = M('spec')->join("left join {$db_prefix}spec_type on {$db_prefix}spec.id = {$db_prefix}spec_type.spec_id  and  type_id = $type_id")->order('id')->select();
            $list = Db::name('spec')->alias('s')->join('__SPEC_TYPE__ t', 't.spec_id = s.id', 'LEFT')->where(['t.type_id' => $type_id])->order('s.id')->select();
        } else {
            $list = M('spec')->order('id')->select();
        }
//        $goods_category_list = M('goods_category')->where("id in(select cat_id1 from {$db_prefix}spec) ")->getField("id,name,parent_id");
        $goods_category_list = Db::name('goods_category')
            ->where('id', 'IN', function ($query) {
                $query->name('spec')->where('')->field('cat_id1');
            })
            ->getField("id,name,parent_id");
        $goods_category_list[0] = array('id' => 0, 'name' => '默认');
        asort($goods_category_list);
        $this->assign('goods_category_list', $goods_category_list);
        $this->assign('type_id', $type_id);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect()
    {
        $goods_id = I('goods_id/d', 0);
        $cat_id3 = I('cat_id3/d', 0);
        empty($cat_id3) && exit('');
        $goods_id = $goods_id ? $goods_id : 0;

        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 找到这个分类对应的type_id
        empty($type_id) && exit('');
        $spec_id_arr = M('spec_type')->where("type_id", $type_id)->getField('spec_id', true); // 找出这个类型的 所有 规格id
        empty($spec_id_arr) && exit('');

        $specList = D('Spec')->where("id", "in", implode(',', $spec_id_arr))->order('`order` desc')->select(); // 找出这个类型的所有规格
        if ($specList) {
            foreach ($specList as $k => $v) {
                $specList[$k]['spec_item'] = D('SpecItem')->where(['store_id' => STORE_ID, 'spec_id' => $v['id']])->getField('id,item'); // 获取规格项
            }
        }

        $items_id = M('SpecGoodsPrice')->where("goods_id", $goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);

        // 获取商品规格图片                
        if ($goods_id) {
            $specImageList = M('SpecImage')->where("goods_id", $goods_id)->getField('spec_image_id,src');
        }
        $this->assign('specImageList', $specImageList);

        $this->assign('items_ids', $items_ids);
        $this->assign('specList', $specList);
        return $this->fetch('ajax_spec_select');
    }

    /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */
    public function ajaxGetSpecInput()
    {
        $GoodsLogic = new GoodsLogic();
        $goods_id = I('get.goods_id/d', 0);
        $spec_arr = I('spec_arr/a', []);
        $str = $GoodsLogic->getSpecInput($goods_id, $spec_arr, STORE_ID);
        $this->ajaxReturn(['status'=>1,'msg'=>'','result'=>$str]);
    }

    /**
     * 商家发布商品时添加的规格
     */
    public function addSpecItem()
    {
        $spec_id = I('spec_id/d', 0); // 规格id
        $spec_item = I('spec_item', '', 'trim');// 规格项

        $c = M('spec_item')->where(['store_id' => STORE_ID, 'item' => $spec_item, 'spec_id' => $spec_id])->count();
        if ($c > 0) {
            $return_arr = array(
                'status' => -1,
                'msg' => '规格已经存在',
                'data' => '',
            );
            exit(json_encode($return_arr));
        }
        $data = array(
            'spec_id' => $spec_id,
            'item' => $spec_item,
            'store_id' => STORE_ID,
        );
        M('spec_item')->add($data);

        $return_arr = array(
            'status' => 1,
            'msg' => '添加成功!',
            'data' => '',
        );
        exit(json_encode($return_arr));
    }

    /**
     * 商家发布商品时删除的规格
     */
    public function delSpecItem()
    {
        $spec_id = I('spec_id/d', 0); // 规格id
        $spec_item = I('spec_item', '', 'trim');// 规格项
        $spec_item_id = I('spec_item_id/d', 0); //规格项 id

        if (!empty($spec_item_id)) {
            $id = $spec_item_id;
        } else {
            $id = M('spec_item')->where(['store_id' => STORE_ID, 'item' => $spec_item, 'spec_id' => $spec_id])->getField('id');
        }

        if (empty($id)) {
            $return_arr = array('status' => -1, 'msg' => '规格不存在');
            exit(json_encode($return_arr));
        }
        $c = M("SpecGoodsPrice")->where("store_id", STORE_ID)->where(" `key` REGEXP :id1 OR `key` REGEXP :id2 OR `key` REGEXP :id3 or `key` = :id4")->bind(['id1' => '^' . $id . '_', 'id2' => '_' . $id . '_', 'id3' => '_' . $id . '$', 'id4' => $id])->count(); // 其他商品用到这个规格不得删除
        if ($c) {
            $return_arr = array('status' => -1, 'msg' => '此规格其他商品使用中,不得删除');
            exit(json_encode($return_arr));
        }
        M('spec_item')->where(['id' => $id, 'store_id' => STORE_ID])->delete(); // 删除规格项
        M('spec_image')->where(['spec_image_id' => $id, 'store_id' => STORE_ID])->delete(); // 删除规格图片选项
        $return_arr = array('status' => 1, 'msg' => '删除成功!');
        exit(json_encode($return_arr));
    }

    /**
     * 商品规格列表
     */
    public function specList()
    {
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $this->assign('cat_list', $goodsCategoryLevelOne);
        return $this->fetch();
    }

    /**
     *  商品规格列表
     */
    public function ajaxSpecList()
    {
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $cat_id3 = I('cat_id3/d', 0);
        $spec_id = I('spec_id/d', 0);
        $type_id = M('goods_category')->where("id", $cat_id3)->getField('type_id'); // 获取这个分类对应的类型
        if (empty($cat_id3) || empty($type_id)) exit('');

        $spec_id_arr = M('spec_type')->where("type_id", $type_id)->getField('spec_id', true); // 获取这个类型所拥有的规格
        if (empty($spec_id_arr)) exit('');

        $spec_id = $spec_id ? $spec_id : $spec_id_arr[0]; //没有传值则使用第一个

        $specList = M('spec')->where("id", "in", implode(',', $spec_id_arr))->getField('id,name,cat_id1,cat_id2,cat_id3');
        $specItemList = M('spec_item')->where(['store_id' => STORE_ID, 'spec_id' => $spec_id])->order('id')->select(); // 获取这个类型所拥有的规格
        //I('cat_id1')   && $where = "$where and cat_id1 = ".I('cat_id1') ;                       
        $this->assign('spec_id', $spec_id);
        $this->assign('specList', $specList);
        $this->assign('specItemList', $specItemList);
        return $this->fetch();
    }

    /**
     *  批量添加修改规格
     */
    public function batchAddSpecItem()
    {
        $spec_id = I('spec_id/d', 0);
        $item = I('item/a');
        $spec_item = M('spec_item')->where(['store_id' => STORE_ID, 'spec_id' => $spec_id])->getField('id,item');
        foreach ($item as $k => $v) {
            $v = trim($v);
            if (empty($v)) continue; // 值不存在 则跳过不处理
            // 如果spec_id 存在 并且 值不相等 说明值被改动过
            if (array_key_exists($k, $spec_item) && $v != $spec_item[$k]) {
                M('spec_item')->where(['id' => $k, 'store_id' => STORE_ID])->save(array('item' => $v));
                // 如果这个key不存在 并且规格项也不存在 说明 需要插入
            } elseif (!array_key_exists($k, $spec_item) && !in_array($v, $spec_item)) {
                M('spec_item')->add(array('spec_id' => $spec_id, 'item' => $v, 'store_id' => STORE_ID));
            }
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'保存成功','result'=>'']);
    }

    /**
     * 品牌列表
     */
    public function brandList()
    {
        $keyword = I('keyword');
        $brand_model = Db::name('brand');
        $brand_where['store_id'] = STORE_ID;
        if ($keyword) {
            $brand_where['name'] = ['like', '%' . $keyword . '%'];
        }
        $count = $brand_model->where($brand_where)->count();
        $Page = new Page($count, 16);
        $brandList = $brand_model->where($brand_where)->order("`sort` asc")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();
        $cat_list = M('goods_category')->where("parent_id = 0")->getField('id,name'); // 已经改成联动菜单
        $this->assign('cat_list', $cat_list);
        $this->assign('show', $show);
        $this->assign('brandList', $brandList);
        return $this->fetch('brandList');
    }

    /**
     * 添加修改编辑  商品品牌
     */
    public function addEditBrand()
    {
        $id = I('id/d', 0);
        if (IS_POST) {
            $data = input('post.');
            $data['status']=1;
            if ($id) {
                Db::name('brand')->update($data);
            } else {
                $data['store_id'] = STORE_ID;
                M("Brand")->insert($data);
            }

            $this->success("操作成功!!!", U('Seller/Goods/brandList', array('p' => input('p'))));
            exit;
        }
        $GoodsCategoryLogic = new GoodsCategoryLogic();
        $GoodsCategoryLogic->setStore($this->storeInfo);
        $goodsCategoryLevelOne = $GoodsCategoryLogic->getStoreGoodsCategory();
        $this->assign('cat_list', $goodsCategoryLevelOne);
        $brand = Db::name('brand')->where(array('id' => $id, 'store_id' => STORE_ID))->find();
        $this->assign('brand', $brand);
        return $this->fetch('_brand');
    }

    /**
     * 删除品牌
     */
    public function delBrand()
    {
        $model = M("Brand");
        $id = I('id/d');
        $model->where(['id' => $id, 'store_id' => STORE_ID])->delete();
        $return_arr = array('status' => 1, 'msg' => '操作成功', 'data' => '',);
        $this->ajaxReturn($return_arr);
    }

    public function brand_save()
    {
        $data = I('post.');
        if ($data['act'] == 'del') {
            $goods_count = M('Goods')->where("brand_id", $data['id'])->count('1');
            if ($goods_count) respose(array('status' => -1, 'msg' => '此品牌有商品在用不得删除!'));
            $r = M('brand')->where('id', $data['id'])->delete();
            if ($r) {
                respose(array('status' => 1));
            } else {
                respose(array('status' => -1, 'msg' => '操作失败'));
            }
        } else {
            if (empty($data['id'])) {
                $data['store_id'] = STORE_ID;
                $r = M('brand')->add($data);
            } else {
                $r = M('brand')->where('id', $data['id'])->save($data);
            }
        }
        if ($r) {
            $this->success("操作成功", U('Store/brand_list'));
        } else {
            $this->error("操作失败", U('Store/brand_list'));
        }
    }

    /**
     * 删除商品相册图
     */
    public function del_goods_images()
    {
        $path = I('filename', '');
        $goods_images = M('goods_images')->where(array('image_url' => $path))->select();
        foreach ($goods_images as $key => $val) {
            $goods = M('goods')->where(array('goods_id' => $goods_images[$key]['goods_id']))->find();
            if ($goods['store_id'] == STORE_ID) {
                M('goods_images')->where(array('img_id' => $goods_images[$key]['img_id']))->delete();
            }
        }
    }

    /**
     * 重新申请商品审核
     */
    public function goodsUpLine()
    {
        $goods_ids = input('goods_ids');
        $res = Db::name('goods')->where('goods_id', 'in', $goods_ids)->where('store_id', STORE_ID)->update(['is_on_sale' => 0, 'goods_state' => 0]);
        if($res !== false){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }

    }
}