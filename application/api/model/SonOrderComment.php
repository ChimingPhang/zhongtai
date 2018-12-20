<?php

/**
 * 商品动作类
 * @Author  蒋峰
 * @date  2018/7/12 0012 17:47
 * @FunctionName _initialize
 * @UpdateTime  date
 */

namespace app\api\model;

use think\Model;
use think\Request;

class SonOrderComment extends Model {

    /**
     * @var [每页展示数量]
     */
    private static $pageNum = 12;
    /**
     * @var int [总评论数]
     */
    public $count = 0;
    /**
     * [商品列表]
     * @Auther 蒋峰
     * @DateTime
     * @param int $page 页码
     * @param int $type 商品类型
     * @param array $where 检索条件
     * @param array $order 排序
     * @param int $num 数量
     * @param string $field 字段
     * @return array|bool|false|\PDOStatement|string|\think\Collection
     */
    public function commentList($page = 1, $goods_id = 0, $num = 0, $field = ''){
        if(!is_numeric($goods_id)) return array('status'=>2002,'msg'=>'goods_id');
        if(!is_string($field)) return array('status'=>2002,'msg'=>'field');

        if($page < 1) return false;
        $limit = ($page - 1) * ($num ? $num : self::$pageNum );
        $where = [];
        if($goods_id) $where['goods_id'] = $goods_id;

        //加入检索条件
        $this->where($where);
        $this->count = $this->count();

        //查询字段
        if(empty($field))
            $field = 'id,goods_id,head_pic,nickname,content,city,img,create_time';

        //加入检索条件
        $this->where($where);

        $commentList = $this->field($field)
            ->limit($limit, ($num ? $num : self::$pageNum ))
            ->order('id', 'desc')
            ->select();
        if($commentList)
            foreach ($commentList as $key => &$value){
                $value->create_time = friend_date($value->create_time);
                $value->img = array_values(M('picture')->where('id',['in', $value->img])->getfield('id,path'));
            }
        return $commentList;
//        return $this->getLastSql();
    }
}
