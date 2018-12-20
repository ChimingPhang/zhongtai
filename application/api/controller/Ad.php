<?php
/**
 * 网站的图片的列表
 *
 * start_page       网站启动页的展示
 *
 */
namespace app\api\controller;

use think\Controller;
use think\Request;

class Ad extends Base
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 网站启动页
     * @Autoh: 胡宝强
     * Date: 2018/7/17 10:43
     */
    public function start_page(){
        $data = $this->ad_position(1,'ad_link,ad_code,ad_name','orderby desc');
        if($data['status'] != '0000') $this->json($data['status'],$data['msg'],'');
        if($data['status'] == '0000') $this->json('0000','ok',$data['result']);
    }

    /**
     * 登录注册页面头部图片
     * @Autor: 胡宝强
     * Date: 2018/8/9 10:46
     */
    public function login_page(){
        $data = M('ad')->where(['pid'=>10,'enabled'=>1])->field('ad_link,ad_code,ad_name')->find();
        if($data){
            $this->json('0000','获取图片成功',$data);
        }else{
            $this->json('0000','暂无图片','');
        }
    }

}
