<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/13 0013
 * Time: 14:23
 */
namespace app\common\validate;
use think\Validate;
use think\Db;
class Parts extends Validate
{
    // 验证规则
    protected $rule = [
        'goods_name'                =>'require',
        'goods_remark'                 =>'require',
//        'cat_list'                 =>'require',
        'original_img'                 =>'require',
        'goods_content'                 =>'require',
        'shop_price'                 =>'require',
        'market_price'                 =>'require',
        'cost_price'                 =>'require',
        'cat_id_2'                 =>'require',
        'cat_id'                 =>'require',
//        'weight'                => 'require',
//        'store_count'                => 'require',
        'keywords'                => 'require',
    ];
    //错误信息
    protected $message  = [
        'goods_content.require'        => '请先填写商品详情描述',
        'original_img.require'        => '请先上传商品图片',
        'cost_price.require'        => '请先填写本店参考成本价',
        'shop_price.require'        => '请先填写本店参考售价',
        'cat_id_2.require'        => '请先选择配件分类',
        'cat_id.require'        => '请先选择车系',
        'goods_name.require'        => '请先填写配件名称',
        'goods_remark.require'        => '请先填写配件简介',
//        'weight.require'        => '请先填写配件重量',
        'keywords.require'        => '请先填写配件关键词',
        'market_price.require'        => '请先填写配件市场价',

    ];

}