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
 * ============================================================================
 * 评论咨询投诉管理
 * @author soubao 当燃
 * @Date: 2016-06-20
 */

namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;

class Comment extends Base {
	public function index(){
		return $this->fetch();
	}

	public function detail(){
		$id = I('get.id/d');
		$res = M('order_comment')->where(array('order_commemt_id'=>$id))->find();
		if(!$res){
			exit($this->error('不存在该评论'));
		}
		if(IS_POST){
			$add['parent_id'] = $id;
			$add['content'] = I('post.content');
			$add['goods_id'] = $res['goods_id'];
			$add['add_time'] = time();
			$add['username'] = '平台';
			$add['is_show'] = 1;
			//$add['seller_id'] = session('seller_id');
			$row =  M('comment')->add($add);
			if($row){
				$this->success('添加成功');exit;
			}else{
				$this->error('添加失败');
			}
		}
		$reply = M('son_order_comment')->alias('s')
			->join('tp_goods g','s.goods_id = g.goods_id','left')
			->field('s.*,g.goods_name,g.original_img')
			->where(array('s.order_id'=>$res['order_id']))->select(); //评论的单个商品的
		$this->assign('comment',$res);
		$this->assign('reply',$reply);
		return $this->fetch();
	}

	/**
	 * Notes:删除评论
	 */
	public function del(){
		$id = I('get.id/d');
		$row = M('son_order_comment')->where(array('id'=>$id))->save(['deleted'=>1]);
		if($row){
			$this->success('删除成功');exit;
		}else{
			$this->error('删除失败');
		}
	}

	/**
	 * Notes:多选操作
	 */
	public function op(){
		$type = I('post.type');
		$selected_id = I('post.selected');
		$row = false;
		if(!in_array($type,array('del','show','hide')) || !$selected_id)
			$this->error('非法操作');
		$where = "id IN ({$selected_id})";
		if($type == 'del'){
			$where .= " OR id IN ({$selected_id})";
			$row = M('son_order_comment')->where($where)->save(['deleted'=>1]); //删除评论
		}
		if($type == 'show'){
			$row = M('son_order_comment')->where($where)->save(array('status'=>1));
		}
		if($type == 'hide'){
			$row = M('son_order_comment')->where($where)->save(array('status'=>0));
		}
		if($row !== false){
			$this->success('操作成功');
		}else{
			$this->error('操作失败');
		}

	}

	public function ajaxindex(){
		$username = I('mobile');
		$order_sn = I('order_sn');
		//$where['c.parent_id'] = 0;
		$where['s.deleted'] = 0;
		if($username){
			$where['u.mobile'] = $username;
		}
		if($order_sn){
			$where['s.master_order_sn'] = $order_sn;
		}
		//$count = Db::name('comment')->alias('c')->join('__USERS__ u','u.user_id = c.user_id','LEFT')->where($where)->count();
//		$count = DB::name('order_comment')->alias('c')
//			->join('__USERS__ u','u.user_id = c.user_id','left')
//			->join('__ORDER__ o', 'c.order_id = o.order_id','left')
//			->where($where)
//			->count();
//		$Page  = new AjaxPage($count,10);
//		$show = $Page->show();

//        $comment_list = Db::name('comment')->alias('c')->field('c.*,u.nickname as nickname')->join('__USERS__ u','u.user_id = c.user_id','LEFT')->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
//		$comment_list = Db::name('order_comment')->alias('c')
//			->field('c.*,u.mobile as mobile,o.order_sn')
//			->join('__USERS__ u','u.user_id = c.user_id','LEFT')
//			->join('__ORDER__ o', 'c.order_id = o.order_id','left')
//			->where($where)
//			->order('c.commemt_time DESC')
//			->limit($Page->firstRow.','.$Page->listRows)
//			->select();

		$count = M('son_order_comment')->alias('s')
			->join('tp_goods g','s.goods_id = g.goods_id','left')
			->join('__USERS__ u','u.user_id = s.user_id','LEFT')
			->where($where)
			->count();
		$Page  = new AjaxPage($count,10);
		$show = $Page->show();
		$comment_list = M('son_order_comment')->alias('s')
			->join('tp_goods g','s.goods_id = g.goods_id','left')
			->join('__USERS__ u','u.user_id = s.user_id','LEFT')
			->where($where)
			->field('s.*,g.goods_name,g.original_img,u.mobile as mobile')
			->limit($Page->firstRow.','.$Page->listRows)
			->select();

		foreach($comment_list as $key=>$value){
			$comment_list[$key]['picture'] = M('picture')->where(['id'=>['in',$value['img']]])->select();
		}


//		if(!empty($comment_list))
//        {
//            $goods_id_arr = get_arr_column($comment_list, 'goods_id');
//            $goods_list = M('Goods')->where("goods_id in (".  implode(',', $goods_id_arr).")")->getField("goods_id,goods_name");
//        }
		//$this->assign('goods_list',$goods_list);
		$this->assign('comment_list',$comment_list);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('pager',$Page);// 赋值分页输出
		return $this->fetch();
	}

	public function index1(){
		$count = M('son_order_comment')->alias('s')
			->join('tp_goods g','s.goods_id = g.goods_id','left')
			->count();
		$Page  = new AjaxPage($count,10);
		$show = $Page->show();
		$comment_list = M('son_order_comment')->alias('s')
			->join('tp_goods g','s.goods_id = g.goods_id','left')
			->field('s.*,g.goods_name,g.original_img')
			->limit($Page->firstRow.','.$Page->listRows)
			->select();
		$this->assign('comment_list',$comment_list);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('pager',$Page);// 赋值分页输出
		return $this->fetch();
	}

	public function ask_list(){
		return $this->fetch();//咨询列表
	}

	public function ajax_ask_list(){
		$username = I('username');
		$content = I('content');
		$where = '';
		if($username){
			$where = " AND username like'%$username%'";
		}
		if($content){
			$where = " AND content like '%{$content}%'";
		}
		$sql = "SELECT COUNT(1) as total_count FROM __PREFIX__goods_consult WHERE parent_id=0".$where;
		$count = Db::query($sql);
		$Page  = new AjaxPage($count[0]['total_count'],15);
		$show = $Page->show();

		$sql = "SELECT * FROM __PREFIX__goods_consult WHERE parent_id=0".$where.' ORDER BY add_time DESC LIMIT '.$Page->firstRow.','.$Page->listRows;
		$comment_list =  Db::query($sql);
		if(!empty($comment_list))
		{
			$goods_id_arr = get_arr_column($comment_list, 'goods_id');
			$goods_list = M('Goods')->where("goods_id in (".  implode(',', $goods_id_arr).")")->getField("goods_id,goods_name");
		}
		$consult_type = array(0=>'默认咨询',1=>'商品咨询',2=>'支付咨询',3=>'配送',4=>'售后');
		$this->assign('consult_type',$consult_type);
		$this->assign('goods_list',$goods_list);
		$this->assign('comment_list',$comment_list);
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('pager',$Page);// 赋值分页输出
		return $this->fetch();
	}

	public function consult_info(){
		$id = I('get.id');
		$res = M('goods_consult')->where(array('id'=>$id))->find();
		if(!$res){
			exit($this->error('不存在该评论'));
		}
		if(IS_POST){
			$add['parent_id'] = $id;
			$add['content'] = I('post.content');
			$add['goods_id'] = $res['goods_id'];
			$add['add_time'] = time();
			$add['username'] = 'admin';
			$add['is_show'] = 1;
			$row =  M('comment')->add($add);
			if($row){
				$this->success('添加成功');
				exit;
			}else{
				$this->error('添加失败');
			}
		}
		$reply = M('goods_consult')->where(array('parent_id'=>$id))->select(); // 咨询回复列表
		$this->assign('comment',$res);
		$this->assign('reply',$reply);
		return $this->fetch();
	}

	public function ask_handle(){
		$type = I('type');
		$selected_id = I('selected/a');
		if (!in_array($type, array('del', 'show', 'hide')) || !$selected_id)
			$this->error('操作失败');

		$selected_id = implode(',', $selected_id);
		$row = false;
		if ($type == 'del') {
			//删除咨询
			$row = M('goods_consult')->where('id', 'IN', $selected_id)->whereOr('parent_id', 'IN', $selected_id)->delete();
		}
		if ($type == 'show') {
			$row = M('goods_consult')->where('id', 'IN', $selected_id)->save(array('is_show' => 1));
		}
		if ($type == 'hide') {
			$row = M('goods_consult')->where('id', 'IN', $selected_id)->save(array('is_show' => 0));
		}
		if($row !== false){
			$this->success('操作完成');
		}else{
			$this->error('操作失败');
		}
	}
	public function ask_del(){
		$id = I('get.id/d');
		$row = M('goods_consult')->where('id', $id)->whereOr('parent_id', $id)->delete();
		if($row){
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
		exit;
	}

	public function complain_list(){
		$timegap = I('timegap');
		$nickname = I('accuser_name');
		$complain_state = I('complain_state');
		$map = array();
		if($timegap){
			$gap = explode(' - ', $timegap);
			$begin = $gap[0];
			$end = $gap[1];
			$map['appeal_datetime'] = array('between',array(strtotime($begin),strtotime($end)));
		}
		if($complain_state){
			$map['complain_state'] = $complain_state;
		}
		if($nickname){
			$map['accuser_name'] = $nickname;
		}
		$count = M('complain')->where($map)->count();
		$page = new Page($count);
		$lists  = M('complain')->where($map)->order('appeal_datetime desc')->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('pager',$page);
		$this->assign('lists',$lists);
		$this->assign('complain_state',$complain_state);
		return $this->fetch();
	}

	public function subject_list(){
		$count = M('complain_subject')->where(array('subject_state'=>1))->count();
		$page = new Page($count);
		$lists  = M('complain_subject')->where(array('subject_state'=>1))->limit($page->firstRow.','.$page->listRows)->select();
		$this->assign('page',$page->show());
		$this->assign('pager',$page);
		$this->assign('list',$lists);
		return $this->fetch();
	}

	public function subject_del(){
		$subject_id = I('del_id');



		if($subject_id>0){
			if(M('complain_subject')->where(array('subject_id'=>$subject_id))->save(array('subject_state'=>2))){

				$count = M('complain_subject')->where(array('subject_state'=>1))->count();
				$page = new Page($count);

				respose(1);
			}else{
				respose('删除失败');
			}
		}
	}

	public function subject_info(){
		if(IS_POST){
			$data = I('post.');
			$data['subject_state'] = 1;
			if(M('complain_subject')->add($data)){
				$this->success('添加成功',U('Comment/subject_list'));exit;
			}else{
				$this->error('添加失败,',U('Comment/subject_list'));
			}
		}
		return $this->fetch();
	}

}