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
 * 运费模板管理
 * Date: 2017-11-14
 */

namespace app\admin\controller;

use app\seller\logic\FreighLogic;
use app\common\model\FreightTemplate;
use think\Db;
use think\Loader;
use think\Page;

class Freight extends Base
{

    /**
     * 显示运费模板
     * @Autoh: 胡宝强
     * Date: 2018/7/16 19:05
     * @return mixed
     */
    public function index()
    {
        $FreightTemplate = new FreightTemplate();
        $count = $FreightTemplate->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $template_list = $FreightTemplate->append(['type_desc'])->with('freightConfig')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('page', $show);
        $this->assign('template_list', $template_list);
        return $this->fetch();
    }

    /**
     * 添加运费模板的页面
     * @Autoh: 胡宝强
     * Date: 2018/7/16 19:05
     * @return mixed
     */
    public function info()
    {
        $template_id = input('template_id');
        if ($template_id) {
            $FreightTemplate = new FreightTemplate();
            $freightTemplate = $FreightTemplate->with('freightConfig')->where(['template_id' => $template_id])->find();
//            dump($freightTemplate);die;
            if (empty($freightTemplate)) {
                $this->error('非法操作');
            }
            $this->assign('freightTemplate', $freightTemplate);
        }
        return $this->fetch();
    }

    /**
     *  保存运费模板
     * @throws \think\Exception
     */
    public function save()
    {
        $FreighLogic = new FreighLogic();
        $res = $FreighLogic->addEditFreighTemplate();
        $this->ajaxReturn($res);
    }

    /**
     * 删除运费模板
     * @throws \think\Exception
     */
    public function delete()
    {
        $template_id = input('template_id');
        $action = input('action');
        if (empty($template_id)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }
        if ($action != 'confirm') {
            $goods_count = Db::name('goods')->where(['template_id' => $template_id])->count();
            if ($goods_count > 0) {
                $this->ajaxReturn(['status' => -1, 'msg' => '已有' . $goods_count . '种商品使用该运费模板，确定删除该模板吗？继续删除将把使用该运费模板的商品设置成包邮。', 'result' => '']);
            }
        }
        Db::name('goods')->where(['template_id' => $template_id])->update(['template_id' => 0, 'is_free_shipping' => 1]);
        Db::name('freight_region')->where(['template_id' => $template_id])->delete();
        Db::name('freight_config')->where(['template_id' => $template_id])->delete();
        $delete = Db::name('freight_template')->where(['template_id' => $template_id])->delete();
        if ($delete !== false) {
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '删除失败', 'result' => '']);
        }
    }


    public function area()
    {
        $province_list = Db::name('region')->where(array('parent_id' => 0, 'level' => 1))->select();
        $this->assign('province_list', $province_list);
        return $this->fetch();
    }

    /**
     * 检查模板，如果模板下没有配送区域配置，就删除该模板
     * @param $template_id
     */
    private function checkFreightTemplate($template_id)
    {
        $freight_config = Db::name('freight_config')->where(['store_id' => STORE_ID, 'template_id' => $template_id])->find();
        if (empty($freight_config)) {
            Db::name('freight_template')->where('template_id', $template_id)->delete();
        }
    }

}