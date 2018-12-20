<?php
namespace app\common\validate;
use think\Validate;
use think\Db;
class Car extends Validate
{
    // 验证规则
    protected $rule = [
        'goods_name'                =>'require',
        'goods_remark'           => 'require',
        'equity_content'           => 'require',
        'equity_desc'           => 'require',
        'cat_id_2'                 =>'require',
        'shop_price'                 =>'require',
        'cost_price'                 =>'require',
        'original_img'                 =>'require',
        'goods_content'                 =>'require',
    ];
    //错误信息
    protected $message  = [
        'goods_content.require'        => '请先填写商品详情描述',
        'original_img.require'        => '请先上传商品图片',
        'cost_price.require'        => '请先填写本店参考成本价',
        'shop_price.require'        => '请先填写本店参考售价',
        'cat_id_2.require'        => '请先选择车系',
        'equity_desc.require'        => '请先填写权益说明',
        'equity_content.require'        => '请先填写权益内容',
        'goods_remark.require'        => '请先填写汽车简介',
        'goods_name.require'        => '请先填写汽车款式',

    ];

}