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
use think\AjaxPage;
use think\Page;
use think\Db;

class Accessories extends Base {

    /**
     * 分类列表
     */
    public function category(){
        $status = I('status','');
        $keyword = I('keyword');
        $status !== '' && $where['status'] = $status;
        $keyword && $where['name'] = ['like',"%$keyword%"];
        $where['status'] = ['<>',"-1"];
        $count = Db::name('accessories_category')->where($where)->count();
        $pager  = new Page($count,10);
        $brandList = Db::name('accessories_category')->where($where)->order('sort desc')->limit($pager->firstRow.','.$pager->listRows)->select();
        $this->assign('pager',$pager);
        $this->assign('brandList',$brandList);
        return $this->fetch();
    }

    /**
     * 添加修改编辑  商品品牌
     */
    public  function addEditBrand(){        
            $id = I('id',0);
            if(IS_POST)
            {
                $data = input('post.');
                if($id)
                    M("accessories_category")->update($data);
                else
                    M("accessories_category")->insert($data);
                $this->success("操作成功!!!",U('Admin/accessories/category',array('p'=>$_GET['p'])));
                exit;
            }           

           $brand = M("accessories_category")->where("id = $id")->find();
           $this->assign('brand',$brand);
           return $this->fetch('_category');
    }

    /**
     * 删除分类
     */
    public function delBrand()
    {
        $ids = I('post.ids','');
        empty($ids) && $this->ajaxReturn(['status' => -1,'msg' => '非法操作！']);
        $brind_ids = rtrim($ids,",");
        // 判断此品牌是否有商品在使用
        $goods_count = Db::name('goods')->whereIn("class_id",$brind_ids)->group('class_id')->getField('class_id',true);
        $use_brind_ids = implode(',',$goods_count);
        if($goods_count)
        {
            $this->ajaxReturn(['status' => -1,'msg' => 'ID为【'.$use_brind_ids.'】的分类有商品在用不得删除!','data'  =>'']);
        }
        $res=Db::name('accessories_category')->whereIn('id',$brind_ids)->save(['status'=>-1]);
        if($res){
            $this->ajaxReturn(['status' => 1,'msg' => '操作成功','url'=>U("Admin/accessories/category")]);
        }
        $this->ajaxReturn(['status' => -1,'msg' => '操作失败','data'  =>'']);
    }


    /**
     * 商品批量操作
     */
    public function act()
    {
        $act = I('post.act', '');
        $goods_ids = I('post.goods_ids');
        $goods_state = I('post.goods_state');
        $reason = I('post.reason','无备注');
        $return_success = array('status' => 1, 'msg' => '操作成功', 'data' => '');
        if ($act == 'hot') {
            $hot_condition['goods_id'] = array('in', $goods_ids);
            M('goods')->where($hot_condition)->save(array('is_hot' => 1));
            $this->ajaxReturn($return_success);
        }
        if ($act == 'recommend') {
            $recommend_condition['goods_id'] = array('in', $goods_ids);
            M('goods')->where($recommend_condition)->save(array('is_recommend' => 1));
            $this->ajaxReturn($return_success);
        }
        if ($act == 'new') {
            $new_condition['goods_id'] = array('in', $goods_ids);
            M('goods')->where($new_condition)->save(array('is_new' => 1));
            $this->ajaxReturn($return_success);
        }
        if($act =='takeoff'){
        	 $goods = M('goods')->field('store_id,goods_name')->where(array('goods_id'=>$goods_ids))->find();
        	$takeoff_res=M('goods')->where(array('goods_id'=>$goods_ids))->save(array('is_on_sale' =>2,'close_reason'=>$reason));
            if($takeoff_res){
                adminLog('违规下架商品ID('.$goods_ids.')',6);
                $store_msg = array(
                    'store_id' => $goods['store_id'],
                    'content' => "您的商品\"{$goods['goods_name']}\",原因：$reason",
                    'addtime' => time(),
                );
                M('store_msg')->add($store_msg);
                $this->ajaxReturn(['status' => 1, 'msg' => '操作成功', 'data' => '']);
            }
            $this->ajaxReturn(['status' => 0, 'msg' => '操作失败', 'data' => '']);
        }
        if ($act == 'examine') {
            $goods_array = explode(',', $goods_ids);
            $goods_state_cg = C('goods_state');
            if (!array_key_exists($goods_state, $goods_state_cg)) {
                $return_success = array('status' => -1, 'msg' => '操作失败，商品没有这种属性', 'data' => '');
                $this->ajaxReturn($return_success);
            }
            foreach ($goods_array as $key => $val) {
                $update_goods_state = M('goods')->where("goods_id = $val")->save(array('goods_state' => $goods_state));
                if ($update_goods_state) {
                    $update_goods = M('goods')->where(array('goods_id' => $val))->find();
                    // 给商家发站内消息 告诉商家商品被批量操作
                    $store_msg = array(
                        'store_id' => $update_goods['store_id'],
                        'content' => "您的商品\"{$update_goods[goods_name]}\"被{$goods_state_cg[$goods_state]}",
                        'addtime' => time(),
                    );
                    M('store_msg')->add($store_msg);
                }
            }
            $this->ajaxReturn($return_success);
        }
        $return_fail = array('status' => -1, 'msg' => '没有找到该批量操作', 'data' => '');
        $this->ajaxReturn($return_fail);
    }

    /**
     * 初始化商品关键词搜索
     */
    public function initGoodsSearchWord(){
        $searchWordLogic = new SearchWordLogic();
        $successNum = $searchWordLogic->initGoodsSearchWord();
        $this->success('成功初始化'.$successNum.'个搜索关键词');
    }

    /**
     * 初始化地址json文件
     */
    public function initLocationJsonJs()
    {
        $goodsLogic = new GoodsLogic();
        $region_list = $goodsLogic->getRegionList();//获取配送地址列表
        file_put_contents(ROOT_PATH."public/js/locationJson.js", "var locationJsonInfoDyr = ".json_encode($region_list, JSON_UNESCAPED_UNICODE).';');
        $this->success('初始化地区json.js成功。文件位置为'.ROOT_PATH."public/js/locationJson.js");
    }
}