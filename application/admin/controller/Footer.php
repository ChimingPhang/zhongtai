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
 * Author: 当燃      
 * Date: 2015-10-09
 */

namespace app\admin\controller;

use app\admin\logic\GoodsLogic;
use app\common\logic\ModuleLogic;
use think\Db;
use think\Cache;

class Footer extends Base{
       /**
        * 自定义导航
        */
    public function index(){
           $model = M("footer");
           $navigationList = $model->order("id desc")->select();            
           $this->assign('navigationList',$navigationList);
           return $this->fetch();
     }
    
     /**
     * 添加修改编辑 前台导航
     */
    public  function addEditNav(){
            $model = M("footer");
            if(IS_POST)
            {
                    if (I('id'))
                        $model->update(I('post.'));
                    else
                        $model->add(I('post.'));
                    
                    $this->success("操作成功!!!",U('Admin/footer/index'));
                    exit;
            }                    
           // 点击过来编辑时                 
			$id = I('id',0);
           $navigation = DB::name('footer')->where('id',$id)->find();
           $this->assign('navigation',$navigation);
           return $this->fetch('_navigation');
    }   
    
    /**
     * 删除前台 自定义 导航
     */
	public function delNav()
	{
            // 删除导航
            M('footer')->where("id",I('id'))->delete();
            $this->success("操作成功!!!",U('Admin/footer/index'));
	}

	public function ajax_delNav()
	{
            // 删除导航
            M('footer')->where("id",I('id'))->delete();
	    $this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!!'));		 
	}
	
	public function refreshMenu(){
		$pmenu = $arr = array();
		$rs = M('system_module')->where('level>1 AND visible=1')->order('mod_id ASC')->select();
		foreach($rs as $row){
			if($row['level'] == 2){
				$pmenu[$row['mod_id']] = $row['title'];//父菜单
			}
		}

		foreach ($rs as $val){
			if($row['level']==2){
				$arr[$val['mod_id']] = $val['title'];
			}
			if($row['level']==3){
				$arr[$val['mod_id']] = $pmenu[$val['parent_id']].'/'.$val['title'];
			}
		}
		return $arr;
	}

}