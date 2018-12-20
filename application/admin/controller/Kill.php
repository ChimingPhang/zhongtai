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
use app\admin\model\GoodsSeckill as GoodsModel;

class Kill extends Base
{
    /*商品类型 1汽车 2配件 3其他*/
    private static $goodsType = 0;
    /**
     * [汽车列表]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function auctionList(){
        return $this->fetch();
    }

    /**
     * [汽车列表]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function ajaxAuctionList(){
        $where = ' state = 1'; // 搜索条件
//        $where = ' state = 1 and type = '. self::$goodsType; // 搜索条件
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;
        $is_on_sale = I('is_on_sale');
        if($is_on_sale !== ''){
            if($is_on_sale == 1 || $is_on_sale == 0) $where = "$where and is_on_sale = ".I('is_on_sale'); //商品状态  0未上架 1已上架
            if($is_on_sale == 2) $where = "$where and is_on_sale = 1 and start_time > ".time(); //商品状态  0未上架 1已上架
            if($is_on_sale == 3) $where = "$where and is_on_sale = 1 and start_time < ".time()." and is_end = 0 "; //商品状态  0未上架 1已上架
            if($is_on_sale == 4) $where = "$where and is_on_sale = 1 and (is_end = 1 or is_end = 2)"; //商品状态  0未上架 1已上架
        }
        // 关键词搜索
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
        }

        $model = M('GoodsSeckill');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();

//        $catList = M('goods_category')->cache(true)->select();
//        $catList = convert_arr_key($catList, 'id');

//        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * [验证是否是活动商品]
     * @Auther 蒋峰
     * @DateTime
     */
    private function VerifyRequest($goods_id){
        //汽车商品编辑基础条件
        $where = array("id" => $goods_id );

        if($goods_id > 0)
        {
            $c = M('GoodsSeckill')->where($where)->count();
            if($c == 0)
                $this->error("非法操作",U('Kill/auctionList'));
        }

        return $where;
    }

    /**
     * [活动编辑]
     * @Auther 蒋峰 Create
     * @DateTime
     */
    public function auctionEdit(){
        $Goods = new GoodsModel; //实例化商品模型
        $goods_id = I('id',0);

        $where = $this->VerifyRequest($goods_id);

        //ajax提交验证
        if (I('is_ajax') && IS_POST) {

            // 数据验证
            $data =input('post.');
            $validate = \think\Loader::validate('admin/Kill');
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
//            if($data['weight'] == 0){
//                $this->error("商品重量不能为0");
//            }
            // $Goods->data($data, true); // 收集数据
//            $data_arr['on_time'] = time(); // 上架时间
            $data_arr['goods_name'] = $data['goods_name']; //商品名称
//            $data_arr['goods_sn'] = $data['goods_sn']; //商品编号
            $data_arr['price'] = $data['price']; //秒杀价
            $data_arr['start_time'] = strtotime($data['start_time']); //开始时间
            $data_arr['end_time'] = strtotime($data['end_time']); //结束时间
            $data_arr['delay_time'] = $data['delay_time']; //权益内容
            $data_arr['original_img'] = $data['original_img']; //权益说明
            $data_arr['label'] = $data['label']; //标签id
//            $data_arr['give_integral'] = $data['give_integral']; //标签id
            $data_arr['equity_content'] = $data['equity_content']; //商品详情
            $data_arr['equity_desc'] = $data['equity_desc']; //商品详情
            $data_arr['goods_content'] = $data['goods_content']; //商品详情
            $data_arr['type'] = $data['type']; //商品类型 1车 2 配件 3 自己添加的
            $type = I("post.id") ? 2 : 1; //1 insert  2 update
            if ($type == 2) {
                // $goods = M('goods')->where(array('goods_id' => $goods_id, 'store_id' => STORE_ID))->find();
                $goods = M('GoodsSeckill')->where($where)->find();
                if ($goods) {
                    //判断活动是否开始
//                    if($goods['start_time'] <= time()) $this->ajaxReturn(array('status' => -1,'msg' => '活动已开始无法更改', 'data' => '',));
                    if($goods['start_time'] <= time()) $this->error("活动已开始无法更改", U('Kill/auctionEdit',array('goods_id'=>$goods_id)));

                    if(!empty($Goods->goods_sn)){
                        if(Db::name('GoodsSeckill')->where("goods_id != $goods_id and goods_sn='".$Goods->goods_sn."'")->count()>0){
                            $this->error("商品货号重复了", U('Kill/auctionEdit',array('goods_id'=>$goods_id)));
                        }
                    }
                    /*// 修改商品后购物车的商品价格也修改一下
                    Db::name('cart')->where("goods_id", $goods_id)->save(array(
                        'cost_price' => $data['cost_price'], //市场价
                        'shop_price' => $data['shop_price'], // 本店价
                    ));*/

                    $update = $Goods->where($where)->save($data_arr);
                    // 更新成功后删除缩略图
                    if($update !== false){
                        delFile("./public/upload/goods/thumb/$goods_id", true);
                    }
                    //打印操作日志
                    file_put_contents(getcwd() . '/runtime/auction.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t修改了商品ID为'{$data['goods_id']}'的商品:{$data['goods_name']}\n",FILE_APPEND);
                } else {
                    //$this->ajaxReturn(array('status' => -1, 'msg' => '非法操作'), 'JSON');
                    $this->error("非法操作", U('Kill/auctionEdit',array('goods_id'=>$goods_id)));
                }
            } else {
                if(!empty($Goods->goods_sn)){
                    if(Db::name('GoodsSeckill')->where("goods_sn='".$Goods->goods_sn."'")->count()>0){
                        $this->error("商品货号重复了", U('Kill/addAuctionGoods',array('goods_id'=>$goods_id)));
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
                file_put_contents(getcwd() . '/runtime/auction.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t添加了商品ID为'{$goods_id}'的商品:{$data['goods_name']}\n",FILE_APPEND);
            }
            $Goods->afterSave($goods_id);
            // $GoodsLogic->saveGoodsAttr($goods_id, $type_id, 0); // 处理商品 属性
            $return_arr = array(
                'status' => 1,
                'msg' => '操作成功',
                'data' => array('url' => U('Kill/auctionList')),
            );
            //重定向, 调整之前URL是设置参数获取方式
            session("is_back", 1);
            $this->ajaxReturn($return_arr);

        }

        $goodsInfo =M('GoodsSeckill')->where($where)->find();
        /*if($goodsInfo) {
            $level_cat[1] = $goodsInfo['cat_id1'];
            $level_cat[2] = $goodsInfo['cat_id2'];
            $label = $goodsInfo['label_id'];
            $this->assign('level_cat',$level_cat);//已绑定的分类
            $this->assign('label',$label);//已绑定的分类
        }*/
//        $cat_list = M('goods_category')->where("parent_id = 0")->select();//一级车系
//        $label_list = M('goods_label')->where("status = 1")->order("sort", "asc")->select();//标签
//        $this->assign('label_list',$label_list);   // 所有标签
//        $this->assign('cat_list',$cat_list);   // 所有分类
        $goodsInfo['start_time'] = empty($goodsInfo['start_time'])? '':date('Y-m-d H:i', $goodsInfo['start_time']);
        $goodsInfo['end_time'] = empty($goodsInfo['end_time'])? '':date('Y-m-d H:i', $goodsInfo['end_time']);
        $this->assign('goodsInfo',$goodsInfo);  // 商品详情
//        $goodsImages = M("GoodsImages")->where('goods_id ='.$goods_id)->select();
        $goodsImages = [];
        if(!empty($goodsInfo['banner_image'])) $goodsImages = explode(',',$goodsInfo['banner_image']);
        $this->assign('goodsImages',$goodsImages);  // 商品相册
        $this->initEditor(); // 编辑器
        return $this->fetch('_auction');
    }

    /**
     * [删除活动]
     * @Auther 蒋峰
     * @DateTime
     */
    public function delAuction()
    {
        $goods_id = $_GET['id'];
        $error = '';
        // 判断此商品是否有提醒
        $c1 = M('GoodsSeckill')->where("id = $goods_id")->find();
        if($c1['start_time'] < time() && $c1['end_time'] > time()) $error .= '此商品正处在秒杀中,不得删除! <br/>';
        // 删除此商品
        M("GoodsSeckill")->where('id ='.$goods_id)->save(array('state' => 0));  //商品表
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   //$return_arr = array('status' => -1,'msg' => '删除失败','data'  =>'',);
        $this->ajaxReturn($return_arr);
    }

    /**
     * [商品批量操作]
     * @Auther 蒋峰 Edit
     * @DateTime
     */
    public function act()
    {
        $act = I('post.act', '');
        $goods_ids = I('post.goods_ids');
//        $goods_state = I('post.goods_state');
        $reason = I('post.reason','无备注');
        $return_success = array('status' => 1, 'msg' => '操作成功', 'data' => '');


        //上架
        if($act =='takeup'){
            $goodsAuction = M('GoodsSeckill')->where(array('id'=>$goods_ids))->find();

            if($goodsAuction['start_time'] < time() && $goodsAuction['is_end'] == 0) $this->ajaxReturn(array('status' => -1, 'msg' => '请将活动开始时间调到当前时间之后再上架', 'data' => ''));
            M('GoodsSeckill')->where(array('id'=>$goods_ids))->save(array('is_on_sale' =>1,'close_reason'=>''));
            //修改上架时间
            M('GoodsSeckill')->where(array('id'=>$goods_ids, 'on_time' => 0))->save(array('on_time' => time()));
            // adminLog('违规下架商品ID('.$goods_ids.')',6);
            $this->ajaxReturn($return_success);
        }

        //下架
        if($act =='takeoff'){
            $goodsAuction = M('GoodsSeckill')->where(array('id'=>$goods_ids))->find();
            if($goodsAuction['start_time'] < time() && $goodsAuction['is_end'] == 0) $this->ajaxReturn(array('status' => -1, 'msg' => '正处于秒杀中不能下架', 'data' => ''));
            M('GoodsSeckill')->where(array('id'=>$goods_ids))->save(array('is_on_sale' =>0,'close_reason'=>''));
            $this->ajaxReturn($return_success);
        }
        $return_fail = array('status' => -1, 'msg' => '没有找到该批量操作', 'data' => '');
        $this->ajaxReturn($return_fail);
    }

    /**
     * [商品列表]
     * @Auther 蒋峰
     * @DateTime
     * @return bool|mixed
     */
    public function goodsList(){
        $type = I('get.type');
        if(!$type == 1 && !$type == 2) return false;
        $GoodsLogic = new GoodsLogic();
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);
        $this->assign('type',$type);
        return $this->fetch();
    }


    /**
     * [添加拍卖商品]
     * @Auther 蒋峰
     * @DateTime
     */
    public function addKillGoods()
    {
        $goods_id = I('get.goods_id');
        if(empty($goods_id)) return false;
        //查看商品类型
        $goodsInfo = M('Goods')->where('goods_id', $goods_id)->find();
        //查询商品规格
        if($goodsInfo['type'] == 1){
            $sql = "SELECT b.id,b.goods_id,CONCAT(a.sku_name, b.sku_name) AS spec_key_name,CONCAT(a.spec_key, '_', b.spec_key) AS spec_key FROM(	
SELECT s.id,CONCAT(p.sku_name,' ',
s.sku_name) AS sku_name,CONCAT(p.id, '_', s.id) AS spec_key	FROM tp_goods_sku AS p INNER JOIN tp_goods_sku AS s ON s.parent_id = p.id WHERE	p.`level` = 1 AND s.`goods_id` = {$goods_id} AND s.`level` = 2 ) AS a, ( SELECT m.id, n.parent_id, n.goods_id, CONCAT(' ',n.sku_name, ' ', m.sku_name) AS sku_name,CONCAT(n.id, '_', m.id) AS spec_key FROM tp_goods_sku AS n INNER JOIN tp_goods_sku AS m ON m.parent_id = n.id WHERE	n.`level` = 3 AND m.`goods_id` = {$goods_id} AND m.`level` = 4) AS b where a.id = b.parent_id";
            $data = Db::query($sql);
            $this->assign('goodsSpec', $data);
        }else{
            $data = M('SpecGoodsPrice')->where('goods_id', $goods_id)->field('item_id as id,goods_id,`key` as spec_key,key_name as spec_key_name')->select();
            $this->assign('goodsSpec', $data);
        }
        $this->assign('goodsInfo', $goodsInfo);
        return $this->fetch();
    }

    /**
     * [添加秒杀商品提交]
     * @Auther 蒋峰
     * @DateTime
     */
    public function addAuctionForm()
    {
        $Goods = new GoodsModel; //实例化商品模型
        $goods_id = I('goods_id',0);

//        $where = $this->VerifyRequest($goods_id);

        //ajax提交验证
        if (I('is_ajax') && IS_POST) {

            // 数据验证
            $data =input('post.');
            $validate = \think\Loader::validate('admin/Kill');
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

            $data_arr['goods_name'] = $data['goods_name']; //商品名称
            $data_arr['goods_id'] = $data['goods_id']; //商品名称
            $data_arr['goods_remark'] = $data['goods_remark']; //商品编号
            $data_arr['price'] = $data['price']; //秒杀价格
            $data_arr['start_time'] = strtotime($data['start_time']); //秒杀开始时间
            $data_arr['end_time'] = strtotime($data['end_time']); //秒杀结束时间
            $data_arr['original_img'] = $data['original_img']; //商品图片
            $data_arr['label'] = $data['label']; //标签id
            $data_arr['spec_key_name'] = $data['spec_key_name']; //规格名称
            $data_arr['spec_key'] = $data['spec_key']; //规格
            $goodsInfo = M("Goods")->where('goods_id', $goods_id)->find();
            $data_arr['type'] = $goodsInfo['type']; //车或者配件
            $data_arr['goods_content'] = $goodsInfo['goods_content']; //商品详情
            $goodsInfo = M("GoodsImages")->where('goods_id', $goods_id)->field('image_url')->select();
            $data_arr['banner_image'] = implode(',',array_column($goodsInfo,'image_url')); //支付方式

                $data_arr['is_new'] = 1; //默认新品
                $Goods->save($data_arr); // 新增数据到数据库
                $goods_id = $Goods->getLastInsID();
                //商品进出库记录日志
                if (empty($_POST['item'])) {
                    update_stock_log(session('admin_id'), $_POST['store_count'], array('goods_id' => $goods_id, 'goods_name' => $_POST['goods_name'], 'store_id' => 0));
                }

                //打印操作日志
                file_put_contents(getcwd() . '/runtime/auction.log',"[".date("Y-m-d H:i:s") . "]" . __METHOD__." line ".__LINE__. "\n管理员:" . $_SESSION["admin_name"] . "\t添加了商品ID为'{$goods_id}'的商品:{$data['goods_name']}\n",FILE_APPEND);
//            }
            //处理经销商
            $type = I('type');
            $dealersAuction = I("dealersAuction/a");
            D("DealersKill")->where("kill_id = $goods_id")->delete(); // 根据条件更新记录
            if($type == 1 && $dealersAuction){
                $ser = [];
                foreach ($dealersAuction as $val){
                    $ser[] = [
                        'kill_id' => $goods_id,
                        'dealers_id' => $val
                    ];
                }
                D("DealersKill")->insertAll($ser);
            }
//            $Goods->afterSave($goods_id);
            // $GoodsLogic->saveGoodsAttr($goods_id, $type_id, 0); // 处理商品 属性
            $return_arr = array(
                'status' => 1,
                'msg' => '操作成功',
//                'data' => array('url' => U('Auction/auctionList')),
            );
            //重定向, 调整之前URL是设置参数获取方式
            session("is_back", 1);
            $this->ajaxReturn($return_arr);

        }

    }

    /**
     * [经销商列表]
     * @Auther 蒋峰
     * @DateTime
     */
    public function distributorList()
    {
        $auction_id = I("id", 0);
        $distributorList = D("dealers")->where(array('status' => 1, 'deleted' => 1))->field('id,name')->select();
        $distributorList = hanzi_sort($distributorList, 'asc');
        $distributorAuction = D("DealersKill")->where(array('kill_id' => $auction_id))->field('dealers_id')->select();
        $distributorAuction = array_column($distributorAuction,'dealers_id');

        $this->assign('distributorList', $distributorList);
        $this->assign('distributorAuction', $distributorAuction);
        return $this->fetch();
    }

    public function signUpList()
    {
        //delFile(RUNTIME_PATH); // 先清除缓存, 否则不好预览
        $Ad =  M('auctionSignUp');
        $pid = I('state','');
        if($pid !== '' && is_numeric($pid)){
            $where['state'] = $pid;
            $this->assign('state',$pid);
        }
        /*$keywords = I('auction_id/s',false,'trim');
        if($keywords){
            $where['auction_id'] = array('like','%'.$keywords.'%');
        }*/
        $where['pay_status'] = 1;
        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->limit($Page->firstRow.','.$Page->listRows)->field('order_sn,auction_id,price,user_id,pay_time,state')->cache(true,60)->select();
        $list = array();
        if($res){
            $ids = array_unique(array_column($res, 'auction_id'));
            $user_ids = array_unique(array_column($res, 'user_id'));
            $media = M("goods_auction")->where('id',['in',implode(',',$ids)])->cache(true)->getField("id,concat(`goods_name`,' ',`spec_key_name`) as goods_name");
            $user_media = M("users")->where('user_id',['in',implode(',',$user_ids)])->cache(true)->getField('user_id,mobile');
            foreach ($res as $val){
                $val['goods_name'] = $media[$val['auction_id']];
                $val['mobile'] = $user_media[$val['user_id']];
                $list[] = $val;
            }
        }
        //判断API模块存在
        if(is_dir(APP_PATH."/api")) $this->assign('is_exists_api',1);

        $show = $Page->show();// 分页显示输出
        $this->assign('list',$list);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function bookingsList()
    {
        //delFile(RUNTIME_PATH); // 先清除缓存, 否则不好预览
        $Ad =  M('auctionBookings');
        $where = [];
//        $pid = I('pid',0);
//        if($pid){
//            $where['pid'] = $pid;
//            $this->assign('pid',I('pid'));
//        }
//        $keywords = I('keywords/s',false,'trim');
//        if($keywords){
//            $where['ad_name'] = array('like','%'.$keywords.'%');
//        }
        $count = $Ad->where($where)->count();// 查询满足要求的总记录数
        $Page = new Page($count,15);// 实例化分页类 传入总记录数和每页显示的记录数
        $res = $Ad->where($where)->field('name,mobile')->limit($Page->firstRow.','.$Page->listRows)->select();
//        $list = array();
//        if($res){
//            $media = array('图片','文字','flash');
//            foreach ($res as $val){
//                $val['media_type'] = $media[$val['media_type']];
//                $list[] = $val;
//            }
//        }
//        dump($res);die;
//        $ad_position_list = M('AdPosition')->getField("position_id,position_name,is_open");
//        $this->assign('ad_position_list',$ad_position_list);//广告位

        //判断API模块存在
        if(is_dir(APP_PATH."/api")) $this->assign('is_exists_api',1);

        $show = $Page->show();// 分页显示输出
        $this->assign('list',$res);// 赋值数据集
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

}