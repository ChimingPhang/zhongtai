<?php
// +----------------------------------------------------------------------
// | TwoThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://www.twothink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 艺品网络
// +----------------------------------------------------------------------
namespace app\common\model;
use think\Model;

/**
 * 图片模型
 * 负责图片的上传
 */

class Picture extends Model{
    protected $autoWriteTimestamp = false;
    /**
     * 文件上传
     * @param  array  $files   要上传的文件列表
     * @param  array  $setting 文件上传配置
     */
    public function upload($files,$setting){
        /* 检测图片是否存在 */
        $isData=$this->isFile(array('md5'=>$files->hash('md5'),'sha1'=>$files->hash()));
        if($isData){
            return $isData; //文件上传成功
        }
        // 上传文件验证
        $info = $files->validate([
                'ext' => $setting['ext'],
                'size' => $setting['size']
            ]
        )->rule($setting['saveName'])->move($setting['rootPath'],true,$setting['replace']);
        if($info){
            //处理文件名
            $picname = str_replace('\\','/',$info->getSaveName());
            /* 记录文件信息 */
            $value['path'] = substr($setting['rootPath'], 1).$picname;	//在模板里的url路径
            $value['md5']  = $files->hash('md5');
            $value['sha1']  = $files->hash();
            $value['status']  = 1;
            $value['create_time']  = time();
            if($add=$this->create($value)){
                $value['id'] = $add->id;
            }
            return $value; //文件上传成功
        } else {
            $this->error = $files->getError();
            return false;
        }
    }

    /**
     * 下载指定文件
     * @param  number  $root 文件存储根目录
     * @param  integer $id   文件ID
     * @param  string   $args     回调函数参数
     * @return boolean       false-下载失败，否则输出下载文件
     */
    public function download($root, $id, $callback = null, $args = null){
        /* 获取下载文件信息 */
        $file = $this->find($id);
        if(!$file){
            $this->error = '不存在该文件！';
            return false;
        }

        /* 下载文件 */
        switch ($file['location']) {
            case 0: //下载本地文件
                $file['rootpath'] = $root;
                return $this->downLocalFile($file, $callback, $args);
            case 1: //TODO: 下载远程FTP文件
                break;
            default:
                $this->error = '不支持的文件存储类型！';
                return false;

        }

    }

    /**
     * 检测当前上传的文件是否已经存在
     * @param  array   $file 文件上传数组
     * @return boolean       文件信息， false - 不存在该文件
     */
    public function isFile($file){
        if(empty($file['md5'])){
            throw new \Exception('缺少参数:md5');
        }
        /* 查找文件 */
        $map = array('md5' => $file['md5'],'sha1'=>$file['sha1'],);
        if($data=$this->field(true)->where($map)->find()){
            return $data->toArray();
        }else{
            return false;
        }
    }

    /**
     * 下载本地文件
     * @param  array    $file     文件信息数组
     * @param  callable $callback 下载回调函数，一般用于增加下载次数
     * @param  string   $args     回调函数参数
     * @return boolean            下载失败返回false
     */
    private function downLocalFile($file, $callback = null, $args = null){
        if(is_file($file['rootpath'].$file['savepath'].$file['savename'])){
            /* 调用回调函数新增下载数 */
            is_callable($callback) && call_user_func($callback, $args);

            /* 执行下载 */ //TODO: 大文件断点续传
            header("Content-Description: File Transfer");
            header('Content-type: ' . $file['type']);
            header('Content-Length:' . $file['size']);
            if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) { //for IE
                header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
            }
            readfile($file['rootpath'].$file['savepath'].$file['savename']);
            exit;
        } else {
            $this->error = '文件已被删除！';
            return false;
        }
    }

    /**
     * 清除数据库存在但本地不存在的数据
     * @param $data
     */
    public function removeTrash($data){
        $this->where(array('id'=>$data['id'],))->delete();
    }

    /**
     * 查询图片
     * @Author   蒋峰
     * @DateTime 2018-04-24T11:20:23+0800
     * @param    [type]                   $img [1,2,3]
     * @return   [type]                    ['*****.jpg','*****.jpg']
     */
    public function imgList($img)
    {
        $data = $this->where('id', ['in', $img])->field('path')->select();
        $data = array_values(array_column($data,'path'));
        
        return $data;
    }


    /*
     * 转换base64文件形式
     * @param $data
     */
    public function change_file($img)
    {
        
       if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)){
            $type = $result[2];
            // $image = base64_decode(str_replace($result[1], '', $img));

            // $new_file = $_SERVER['DOCUMENT_ROOT'] . "/static/upload/";//绝对路径
            // if(!file_exists($new_file)){
            //     //检查是否有该文件夹，如果没有就创建，并给予最高权限
            //     mkdir($new_file, 0777);
            // }
            
            $new_file = 'public/upload/order_comment/';
            if(! is_dir($new_file)){
                mkdir($_SERVER['DOCUMENT_ROOT']. '/' .$new_file, 0777);
            }

            $new_file = $new_file. date('Y');
            if(! is_dir($new_file)){
                mkdir($_SERVER['DOCUMENT_ROOT']. '/' .$new_file, 0777);
            }

            $new_file = $new_file. '/' . date('m-d');
            if(! is_dir($new_file)){
                mkdir($_SERVER['DOCUMENT_ROOT']. '/' .$new_file, 0777);
            }


            //此时要返回一个相对路径
            $new_file = $new_file. '/' .md5($img.time()).".{$type}";
            // dump($new_file);die;
            if (file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/' . $new_file, base64_decode(str_replace($result[1], '', $img))))
            {
                return '/' . $new_file;
            }
            else
            {
                return false;
            }
        }else{
            return false;
        }
    }
    /**
     * 返回PICTURE表中的添加成功的id
     * @Author   蒋峰
     * @DateTime 2018-04-24T11:20:23+0800
     * @param    [type]                   $data [sah1, md5]
     * @return   [type]                    $img 路径
     */
    public function pic_id($img, $data)
    {  

        $img_find = M("picture")->where('md5', $data['md5'])->where('sha1', $data['sha1'])->field("id")->find();
        if($img_find)
            return $img_find['id'];

        $data['path'] = $this->change_file($img);
        $data['create_time'] = time();

        if(!$data['path']) return false;
        
        $res = M("picture")->insertGetId($data);//此函数有添加功能
        return $res;
    }


}
