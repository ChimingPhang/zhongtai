<?php
/**
 *经销商
 * store_list       经销商列表
 * store_add        添加经销商
 * get_city         城市联动
 * store_info_edit  修改经销商
 * store_del        删除经销商
 * add_car          添加这个经销商的车型
 * save_car         保存添加的车型
 *
 */

namespace app\admin\controller;

use app\admin\logic\StoreLogic;
use app\admin\logic\DealersLogic;
use app\common\model\ExcelToArrary;
use app\common\logic\ModuleLogic;
use Org\Util\Date;
use think\Page;
use think\Loader;
use think\Db;

class Dealers extends Base{

	/**
	 * 经销商列表
	 * @Autoh: 胡宝强
	 * Date: 2018/7/13 11:20
	 * @return mixed
	 */
	public function store_list(){
		$model =  M('dealers');
		$name = I('name');
		$map['deleted'] = 1;
		$status = I('status',2);
		if($status == 0 || $status == 1){
			$map['status']  = ['eq',$status];
		}
		if($name) $map['name'] = array('like',"%$name%");
		$count = $model->where($map)->count();
		$Page = new Page($count,60);
		$list = $model->where($map)->order('id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign('list',$list);
		$show = $Page->show();
		$this->assign('page',$show);
		$this->assign('pager',$Page);
		return $this->fetch();
	}

	/**
	 * 添加经销商
	 * @Autoh: 胡宝强
	 * Date: 2018/7/13 11:25
	 * @return mixed
	 */
	public function store_add(){
		if(IS_POST){
			$name = trim(I('name'));
			if(M('dealers')->where(['name'=>$name,'deleted'=>1])->count()>0){
				$this->ajaxReturn(['status'=>0,'msg'=>'经销商名称已存在']);
			}
			$user_name = I('user_name');
			if(M('dealers')->where(['user_name'=>$user_name,'deleted'=>1])->count()>0){
				$this->ajaxReturn(['status'=>0,'msg'=>'此用户已被占用']);
			}

			$store = I('post.');
			$store['add_time'] = time();
			$store['password'] = encrypt(trim(I('password')));
			$province = get_province_city($store['province']);
			$city = get_province_city($store['city']);
			$store['desc'] = $province . $city . $store['address'];
			$storeLogic = new DealersLogic();
			if($storeLogic->addStore($store)){
				$this->ajaxReturn(['status'=>1,'msg'=>'经销商添加成功','url'=>U('Dealers/store_list')]);
				exit;
			}else{
				$this->ajaxReturn(['status'=>0,'msg'=>'经销商添加失败']);
			}
		}
		$region_list=M("region")->where(array("parent_id"=>0))->select();
		$this->assign('region_list',$region_list);
		return $this->fetch();
	}
	/**
	 *
	 * 经销商所在城市的联动
	 * @Autoh: 胡宝强
	 * Date: 2018/7/12 17:52
	 */
	public function get_city()
	{
		$pid=I("post.pid");
		$region=M("region")->where(array("parent_id"=>$pid))->select();
		if ($region)
		{
			$data = '<option value="0">请选择城市</option>';
			foreach ($region as $key => $v)
			{
				$data.="<option value='".$v['id']."'>".$v['name']."</option>";
			}
			echo $data;
		}
	}


	/**
	 * 修改经销商
	 * @Autoh: 胡宝强
	 * Date: 2018/7/13 11:45
	 * @return mixed
	 */
	public function store_info_edit(){
        $table = 'Dealers';
		if(IS_POST){
			$map = I('post.');
//			$password = M($table)->where(['id'=>$map['id']])->getField('password');
//            if($map['password'] != $password){
//                $map['password'] = encrypt(trim($map['password']));
//            }
			$province = get_province_city($map['province']);
			$city = get_province_city($map['city']);
			$map['desc'] = $province . $city . $map['address'];
			$map['update_time'] = time();
            $arr = M($table)->where(['id'=>$map['id']])->save($map);
            if($arr){
                $this->success('经销商修改成功',U('Dealers/store_list'));
                exit;
            }else{
                $this->success('经销商修改失败',U('Dealers/store_list'));
            }
		}
		$store_id = I('id');
		$store = M($table)->where(['id'=>$store_id])->find();
		$this->assign('store',$store);
		$province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
		$this->assign('province',$province);
		$city = M('region')->where(array('level'=>2,'parent_id'=>$store['province']))->select();
		$this->assign('city',$city);

		return $this->fetch();
	}

    /**
     * 删除经销商
     * @Autoh: 胡宝强
     * Date: 2018/7/13 14:16
     */
	public function store_del(){
		$store_id = I('del_id');
        $data = M('dealers')->where(['id'=>$store_id])->save(['deleted'=>0]);
        if($data){
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '删除失败', 'result' => '']);
        }
	}

    /**
     * 添加经销商的车型
     * @Autoh: 胡宝强
     * Date: 2018/7/14 10:27
     */
    public function add_car(){
        $dealers_id = I('dealers_id',0,intval);
        if($dealers_id<1){
            $this->error('信息错误',U('Dealers/store_list'));
        }
//        $data = M('goods_sku')->where(['level'=>4,'status'=>1,'sku_count'=>['neq',0]])->order('sort_order')->select();
        $sql = "SELECT b.id,b.goods_id,CONCAT(a.sku_name, b.sku_name) AS sku_name FROM(	SELECT s.id,CONCAT('外观颜色:',p.sku_name,' 排量:',s.sku_name) AS sku_name	FROM tp_goods_sku AS p INNER JOIN tp_goods_sku AS s ON s.parent_id = p.id WHERE	p.`level` = 1 AND s.`level` = 2 ) AS a, ( SELECT m.id, n.parent_id, n.goods_id, CONCAT(' 车型:',n.sku_name, ' 内饰颜色:', m.sku_name, ' 价格:',n.sku_price,' 库存:',n.sku_count) AS sku_name FROM tp_goods_sku AS n INNER JOIN tp_goods_sku AS m ON m.parent_id = n.id WHERE	n.`level` = 3 AND m.`level` = 4) AS b where a.id = b.parent_id";
        $data = Db::query($sql);
        $carData = M('goods')->where(array('type' => 1))->cache('carGoodsName')->getfield('goods_id,goods_name');
        $res = []; //想要的结果
        foreach($data as $key=>$value){
            $res[$carData[$value['goods_id']]][] = $value;
        }
        $dealers_car = M('dealers_car')->where(['dealers_id'=>$dealers_id])->select();
		$arr = '';
		foreach($dealers_car as $key=>$val){
			$arr.=$val['sku_id'].',';
		}
        //$dealers_car = explode(',',$dealers_car['sku_id']);
        $this->assign('dealers_car',$arr);
        $this->assign('modules',$res);
        $this->assign('dealers_id',$dealers_id);
//        dump($res);die;
        return $this->fetch();
    }

	/**
	 * 保存经销商添加或者修改的车型
	 * @Autoh: 胡宝强
	 * Date: 2018/7/18 9:20
	 */
    public function save_car()
    {
        $data = I('post.');
        //$res = $data['data'];
        //$res['sku_id'] = is_array($data['right']) ? implode(',', $data['right']) : '';
		M('dealers_car')->where(['dealers_id'=>$data['dealers_id']])->delete();

		$arr = [];
		if($data['right']){
			foreach($data['right'] as $key=>$value){
				$arr[$key]['dealers_id'] = $data['dealers_id'];
				$aa = explode(',',$value);
				$arr[$key]['goods_id'] = $aa[1];
				$arr[$key]['sku_id'] = $aa[0];
			}
			$r = Db::name('dealers_car')->insertAll($arr);
			if (!$r) {
				$this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
			}
		}

        adminLog('修改经销商车型',0);
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }

	/**
	 * 显示添加excel的页面
	 * @Autor: 胡宝强
	 * Date: 2018/8/9 19:36
	 * @return mixed
	 */
	public function excel_add(){
		return $this->fetch();
	}

	/**
	 * 导入excel
	 * @Autor: 胡宝强
	 * Date: 2018/8/9 19:36
	 */
	public function add_excel(){
		$tmp_file = $_FILES['file']['tmp_name'];
		$file_upload_name = $_FILES['file']['name'];

		$file_types = explode(".", $file_upload_name);
		$file_type = $file_types [count($file_types) - 1];
		/* 判别是不是.xls文件，判别是不是excel文件 */
		if (strtolower($file_type) != "xlsx" && strtolower($file_type) != "xls") {
			$result['status'] = 1;
			$result['message'] = "不是Excel文件，重新上传";
			$this->ajaxReturn($result);
		}
		/* 设置上传路径 */
		$savePath = "./public/Excel/";
		/* 以时间来命名上传的文件 */
		$str = date('Y-m-d');
		$dir = $savePath . $str;
		if (!is_dir($dir)) {
			mkdir($dir,0777,true);
			chmod($dir, 0777);
		}
		move_uploaded_file($tmp_file,$dir.'/'.$file_upload_name);
		$file_name = $dir . "/" . $file_upload_name;

		/* 是否上传成功 */
//		if (!copy($tmp_file, $file_name)) {
//			$result['status'] = 2;
//			$result['message'] = "上传EXCEL文件失败";
//			$this->ajaxReturn($result);
//		}
		//取出excel文件并读取里面的内容
		$ExcelToArrary = new ExcelToArrary(); //实例化
		$excel_res = $ExcelToArrary->read($file_name, "UTF-8", $file_type); //传参,判断office2007还是office2003
		unset($excel_res[1]);

        foreach ($excel_res as $v) {
            $data['name'] = $v[0];
            $province = M('region')->where(['name'=>$v[1],'level'=>1])->find();
            $data['province'] =$province['id'];
            $city = M('region')->where(['name'=>$v[2],'level'=>2])->find();
            $data['city'] = $city['id'];
            $data['longitude'] = $v [3];
            $data['address'] = $v [4];
            $data['start_time'] = gmdate("H:i:s",\PHPExcel_Shared_Date::ExcelToPHP($v[5]));
            $data['end_time'] = gmdate("H:i:s",\PHPExcel_Shared_Date::ExcelToPHP($v[6]));
            $data['mobile'] = $v [7];
            $data['user_name'] = $v [8];
            $data['password'] = $v [9];
            $data['realname'] = $v [10];
            $data['desc'] = $v[1] . '-' . $v[1] . '-' . $v[4];
            $data['add_time'] = time();
            $res1 = M('dealers')->add($data);
            if (!$res1) {
                return false;
            }
        }
		if ($res1) {
			$this->success('导入成功');
		} else {
            $this->success('导入失败');
		}
	}

}