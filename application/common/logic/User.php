<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: dyr
 * Date: 2017-12-04
 */

namespace app\common\logic;
use app\common\model\Goods;
use app\common\model\Users;
use think\Model;
use think\Db;
/**
 * 商品类
 * Class CatsLogic
 * @package Home\Logic
 */
class User
{

    private $user;
    public function __construct(Users $user)
    {
        $this->user = $user;
    }

    /**
     * 记录用户浏览商品记录
     * @param Goods $goods
     */
    public function visitGoodsLog(Goods $goods){
        $record = Db::name('goods_visit')->where(['user_id'=>$this->user['user_id'],'goods_id'=>$goods['goods_id']])->find();
        if($record){
            Db::name('goods_visit')->where(['user_id'=>$this->user['user_id'],'goods_id'=>$goods['goods_id']])->save(array('visit_time'=>time()));
        }else{
            $visit = ['user_id'=>$this->user['user_id'],'goods_id'=>$goods['goods_id'],'visit_time'=>time(),
                'cat_id1'=>$goods['cat_id1'],'cat_id2'=>$goods['cat_id2'],'cat_id3'=>$goods['cat_id3']];
            Db::name('goods_visit')->add($visit);
        }
    }

}