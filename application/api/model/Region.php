<?php
/*
 * 车系
 * **/
namespace app\api\model;

use think\Model;
use think\Request;
use think\Response;

class Region extends Model
{
    /***/
    public function getCity($pid = 0, $level = 1){
        return $this->where(array('parent_id' => $pid, 'level'=> $level))->field('id,name')
            ->select();
    }
}
