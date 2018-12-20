<?php
namespace app\common\validate;
use think\Validate;
use think\Db;
class kill extends Validate
{
    // 验证规则
    protected $rule = [
        'goods_name'                =>'require',
        'price'           => 'require',
        'start_time'                 =>'require',
        'end_time'                 =>'require',
        'original_img'                 =>'require',
    ];
    //错误信息
    protected $message  = [
        'original_img.require'        => '请先上传商品图片',
        'end_time.require'        => '请设置结束时间',
        'start_time.require'        => '请设置开始时间',
        'price.require'        => '请先填写秒杀价',
        'goods_name.require'        => '请先填写商品名称',

    ];

}