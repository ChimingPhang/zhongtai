<?php
namespace app\common\validate;
use think\Validate;
use think\Db;
class Auction extends Validate
{
    // 验证规则
    protected $rule = [
        'goods_name'                =>'require',
        'start_price'           => 'require',
        'bail_price'           => 'require',
        'markup_price'           => 'require',
//        'brokerage_price'                 =>'require',
//        'reserve_price'                 =>'require',
        'preheat_time'              =>'require',
        'start_time'                 =>'require',
        'end_time'                 =>'require',
        'delay_time'                 =>'require',
        'original_img'                 =>'require',
    ];
    //错误信息
    protected $message  = [
        'original_img.require'        => '请先上传商品图片',
        'delay_time.require'        => '请设置延时时间',
        'end_time.require'        => '请设置结束时间',
        'start_time.require'        => '请设置开始时间',
        'preheat_time.require'        => '请设置预约时间',
//        'reserve_price.require'        => '请先填写起拍价',
//        'brokerage_price.require'        => '请先填写起拍价',
        'markup_price.require'        => '请先填写加价幅度',
        'bail_price.require'        => '请先填写保证金',
        'start_price.require'        => '请先填写起拍价',
        'goods_name.require'        => '请先填写商品名称',

    ];

}