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
 * Date: 2015-09-09
 */
namespace app\admin\controller;
use app\admin\logic\UpgradeLogic;
use app\common\logic\Saas;
use think\Controller;
use think\Session;
class Base extends Controller {

    /**
     * 析构函数
     */
    function __construct() 
    {
	    Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.tp-shop.cn/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        parent::__construct();		
        $upgradeLogic = new UpgradeLogic();
        $upgradeMsg = $upgradeLogic->checkVersion(); //升级包消息        
        $this->assign('upgradeMsg',$upgradeMsg);
        tpversion();
   }    
    
    /*
     * 初始化操作
     */
    public function _initialize()
    {
        Saas::instance()->checkSso();

        $this->assign('action',ACTION_NAME);
        //过滤不需要登陆的行为
        if (!in_array(ACTION_NAME, array('login', 'vertify','forget_pwd'))) {
            if (session('admin_id') > 0) {
                $this->check_priv();//检查管理员菜单操作权限
            } else {
                (ACTION_NAME == 'index') && $this->redirect( U('Admin/Admin/login'));
                $this->error('请先登录', U('Admin/Admin/login'), null, 1);
            }
        }
        $this->public_assign();
    }
    
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
       $tpshop_config = array();
       $tp_config = M('config')->select();       
       foreach($tp_config as $k => $v)
       {
          $tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }
       $this->assign('tpshop_config', $tpshop_config);       
    }
    
    public function check_priv()
    {
    	$ctl = CONTROLLER_NAME;
    	$act = ACTION_NAME;
		$act_list = session('act_list');
		//无需验证的操作
		$uneed_check = array('login','logout','vertifyHandle','vertify','imageUp','upload','login_task','forget_pwd');
    	if($ctl == 'Index' || $act_list == 'all'){
    		//后台首页控制器无需验证,超级管理员无需验证
    		return true;
    	}elseif(request()->isAjax() || strpos($act,'ajax')!== false || in_array($act,$uneed_check)){
    		//所有ajax请求不需要验证权限
    		return true;
    	}else{
    		$right = M('system_menu')->where("id in ($act_list)")->cache(true)->getField('right',true);
            $role_right = '';
    		foreach ($right as $val){
    			$role_right .= $val.',';
    		}
    		$role_right = explode(',', $role_right);
    		//检查是否拥有此操作权限
    		if(!in_array($ctl.'@'.$act, $role_right)){
    			$this->error('您没有操作权限'.($ctl.'@'.$act).',请联系超级管理员分配权限',U('Admin/Index/welcome'));
    		}
    	}
    }
	
    public function ajaxReturn($data,$type = 'json'){                        
        exit(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 初始化编辑器链接
     * 本编辑器参考 地址 http://fex.baidu.com/ueditor/
     */
    protected function initEditor()
    {
        $this->assign("URL_upload", U('Admin/Ueditor/imageUp',array('savepath'=>'goods'))); // 图片上传目录
        $this->assign("URL_imageUp", U('Admin/Ueditor/imageUp',array('savepath'=>'article'))); //  不知道啥图片
        $this->assign("URL_fileUp", U('Admin/Ueditor/fileUp',array('savepath'=>'article'))); // 文件上传s
        $this->assign("URL_scrawlUp", U('Admin/Ueditor/scrawlUp',array('savepath'=>'article')));  //  图片流
        $this->assign("URL_getRemoteImage", U('Admin/Ueditor/getRemoteImage',array('savepath'=>'article'))); // 远程图片管理
        $this->assign("URL_imageManager", U('Admin/Ueditor/imageManager',array('savepath'=>'article'))); // 图片管理
        $this->assign("URL_getMovie", U('Admin/Ueditor/getMovie',array('savepath'=>'article'))); // 视频上传
        $this->assign("URL_home", C('FILE_URL'));
    }
}