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
 * 评论咨询投诉管理
 * @author soubao 当燃
 * @Date: 2016-06-20
 */

namespace app\seller\controller;

use think\AjaxPage;
use think\Db;
  
class Comment extends Base
{
    public function index()
    {
        checkIsBack();
        return $this->fetch();
    }

    public function detail()
    {
        $id = I('get.id/d');
        $res = M('order_comment')->where(array('order_commemt_id' => $id))->find();
        $user = Db::name('users')->where('user_id', $res['user_id'])->find();
        if (!$res) {
            exit($this->error('不存在该评论'));
        }
//        if (IS_POST) {
//            $add['parent_id'] = $id;
//            $add['content'] = trim(I('post.content'));
//            $add['goods_id'] = $res['goods_id'];
//            $add['add_time'] = time();
//            $add['username'] = '卖家';
//            $add['is_show'] = 1;
//            $add['store_id'] = STORE_ID;
//            empty($add['content']) && $this->error('请填写回复内容');
//            $row = M('comment')->add($add);
//            if ($row !== false) {
//                $this->success('添加成功');
//                exit();
//            } else {
//                $this->error('添加失败');
//                exit();
//            }
//        }
        $this->assign('comment', $res);
        $this->assign('user', $user);
        $reply = M('son_order_comment')->alias('s')
            ->join('tp_goods g','s.goods_id = g.goods_id','left')
            ->field('s.*,g.goods_name,g.original_img')
            ->where(array('s.order_id'=>$res['order_id']))->select(); //评论的单个商品的
        $this->assign('reply',$reply);
        return $this->fetch();
    }

    public function del()
    {
        $id = I('get.id/d');
        $row = M('son_order_comment')->where(array('id' => $id))->save(['deleted' => 1]);
        if ($row) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 软删除回复
     * @throws \think\Exception
     */
    public function delReply()
    {
        $reply_id = input('reply_id/d');
        $comment_id = input('comment_id/d');
        $reply = Db::name('reply')->where(array('reply_id' => $reply_id))->update(['deleted' => 1]);
        $comment = Db::name('comment')->where(array('comment_id' => $comment_id))->setDec('reply_num');
        if($reply){
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '删除失败', 'result' => '']);
    }

    public function op()
    {
        $type = I('post.type');
        $selected_id = I('post.selected');
        $row = false;
        if (!in_array($type, array('del', 'show', 'hide')) || !$selected_id) {
            $this->error('非法操作');
        }
        if ($type == 'del') {
            //删除回复
            $row = Db::name('comment')->where('comment_id', 'in', $selected_id)->whereOr('parent_id', 'in', $selected_id)->delete();
        }
        if ($type == 'show') {
            $row = Db::name('comment')->where('comment_id', 'in', $selected_id)->save(array('is_show' => 1));
        }
        if ($type == 'hide') {
            $row = Db::name('comment')->where('comment_id', 'in', $selected_id)->save(array('is_show' => 0));
        }
        if (!$row) {
            $this->error('操作失败');
        } else {
            $this->success('操作成功');
        }
    }

    /**
     * 首页加载
     * @Autoh: 胡宝强
     * Date: 2018/7/13 16:50
     * @return mixed
     */
    public function ajaxindex()
    {
        $username = I('mobile', '', 'trim');
        $order_sn = I('order_sn','','trim');

        $where['o.store_id'] = STORE_ID;
        $where['s.deleted'] = 0;
        if($username){
            $where['u.mobile'] = $username;
        }
        if($order_sn){
            $where['o.order_sn'] = $order_sn;
        }

        $count = M('son_order_comment')->alias('s')
            ->join('tp_goods g','s.goods_id = g.goods_id','left')
            ->join('__ORDER__ o', 's.order_id = o.order_id','left')
            ->join('__USERS__ u','u.user_id = s.user_id','LEFT')
            ->where($where)
            ->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();
        $comment_list = M('son_order_comment')->alias('s')
            ->join('tp_goods g','s.goods_id = g.goods_id','left')
            ->join('__ORDER__ o', 's.order_id = o.order_id','left')
            ->join('__USERS__ u','u.user_id = s.user_id','LEFT')
            ->where($where)
            ->field('s.*,g.goods_name,g.original_img,u.mobile as mobile')
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        foreach($comment_list as $key=>$value){
            $comment_list[$key]['picture'] = M('picture')->where(['id'=>['in',$value['img']]])->select();
        }

//        $count = DB::name('order_comment')->alias('c')
//            ->join('__USERS__ u','u.user_id = c.user_id','left')
//            ->join('__ORDER__ o', 'c.order_id = o.order_id','left')
//            ->where($where)
//            ->count();
//        $Page = new AjaxPage($count, 16);
//        //是否从缓存中读取Page
//        if (session('is_back') == 1) {
//            $Page = getPageFromCache();
//            delIsBack();
//        }
//
//        $comment_list = Db::name('order_comment')->alias('c')
//            ->field('c.*,u.mobile as mobile,o.order_sn')
//            ->join('__USERS__ u','u.user_id = c.user_id','LEFT')
//            ->join('__ORDER__ o', 'c.order_id = o.order_id','left')
//            ->where($where)
//            ->order('c.commemt_time DESC')
//            ->limit($Page->firstRow.','.$Page->listRows)
//            ->select();
//        if (!empty($comment_list)) {
//            $goods_id_arr = get_arr_column($comment_list, 'goods_id');
//            $goods_list = M('Goods')->where("goods_id", "in", implode(',', $goods_id_arr))->getField("goods_id,goods_name");
//            $this->assign('goods_list', $goods_list);
//        }
        cachePage($Page);
        $show = $Page->show();
        $this->assign('comment_list', $comment_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('page', $show);// 赋值分页输出
        return $this->fetch();
    }


    public function consult_info()
    {
        $id = I('id/d',0);
        $res = M('goods_consult')->where(array('id' => $id))->find();
        if (!$res) {
            $this->error('不存在该咨询');
            exit;
        }
        if (IS_POST) {
            $add['parent_id'] = $id;
            $add['content'] = I('post.content');
            $add['goods_id'] = $res['goods_id'];
            $add['consult_type'] = $res['consult_type'];
            $add['add_time'] = time();
            $add['store_id'] = STORE_ID;
            $add['is_show'] = 1;
            $row = Db::name('goods_consult')->add($add);
            if ($row) {
                $add['add_time']=date('Y-m-d H:i',$add['add_time']);
                Db::name('goods_consult')->where(['id'=>$id])->save(['status'=>1]);
                $this->ajaxReturn(['status'=>1,'msg'=>'添加成功','resault'=>$add]);
            } else {
                $this->ajaxReturn(['status'=>-1,'msg'=>'添加失败']);
            }
            exit;
        }
        $reply = M('goods_consult')->where(array('parent_id' => $id))->select(); // 咨询回复列表
        $this->assign('id', $id);
        $this->assign('comment', $res);
        $this->assign('reply', $reply);
        return $this->fetch();
    }
}