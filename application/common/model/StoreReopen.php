<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: IT宇宙人
 * Date: 2015-09-09
 */
namespace app\common\model;

use think\Model;

class StoreReopen extends Model
{
    public function getReopenStateAttr($value, $data){
        $reopen_state = '';
        if ($data['reopen_state'] == 0 ){
            $reopen_state = '未上传凭证';

        }
        if ($data['re_state'] == 1 ){
            $reopen_state = '审核中';
        }
        if ($data['re_state'] == 2 ){
            $reopen_state = '审核通过';
        }
        return $reopen_state;
    }
}
