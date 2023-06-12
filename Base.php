<?php
namespace app\index\controller;

use library\Controller;
use think\facade\Request;
use think\Db;

/**
 * 验证登录控制器
 */
class Base extends Controller
{
    protected $rule = ['__token__' => 'token'];
    protected $msg  = ['__token__'  => '无效token！'];

    function __construct() {
        parent::__construct();
        $uid = session('user_id');
        if (!$uid) {
            $uid = cookie('user_id');
        }
         
        if(!Cookie('lang')){
            Cookie('lang','en-us');
            // Cookie('lang','zh-cn');
        }
        
        $arr=['Index/home','Index/xnshouyi','Task/index','My/vip','Task/type','Index/xiaoxi_tan','Index/tongzhi_tan','Task/task_apply1','User/login','Index/tongzhi_tan','Index/index'];
        $controller = request()->controller();
        $action = request()->action();
        $luyou=$controller."/".$action;
        if(!in_array($luyou,$arr)){
            if(!$uid && request()->isPost()){
                $this->error(lang('请先登录'));
            }
            if(!$uid) 
            $this->redirect('User/login');
        }
        //每日任务初始化
        $userinfo = db('xy_users')->find($uid);
        $levelinfo = db('xy_level')->where('level',$userinfo['level'])->find();
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        if(date("Y-m-d",$beginToday) > $userinfo['task_time']){
            Db::name('xy_users')->where('id', $uid)->update([
                'task_time' => date("Y-m-d",$beginToday),
                'task_jdnum'=>$levelinfo['jd_num'],
                'task_tbnum'=>$levelinfo['tb_num'],
                'task_tmnum'=>$levelinfo['tm_num'],
                'task_pddnum'=>$levelinfo['pdd_num'],
                'task_wxnum'=>$levelinfo['wx_num'],
                'task_dznum'=>$levelinfo['dz_num']
            ]);
        }
        //检查VIP是否到期
        if(time() > $userinfo['viptime']){
            Db::table('xy_users')->where('id',$uid)->update(['level' => 0]);
        }
    }

    /**
     * 空操作 用于显示错误页面
     */
    public function _empty($name){
        return $this->fetch($name);
    }

    //图片上传为base64为的图片
    public function upload_base64($type,$img){
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img, $result)){
            $type_img = $result[2];  //得到图片的后缀
            //上传 的文件目录

            $App = new \think\App();
            $new_files = $App->getRootPath() . 'upload'. DIRECTORY_SEPARATOR . $type. DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m-d') . DIRECTORY_SEPARATOR ;

            if(!file_exists($new_files)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                //服务器给文件夹权限
                mkdir($new_files, 0777,true);
            }
            //$new_files = $new_files.date("YmdHis"). '-' . rand(0,99999999999) . ".{$type_img}";
            $new_files = check_pic($new_files,".{$type_img}");
            if (file_put_contents($new_files, base64_decode(str_replace($result[1], '', $img)))){
                //上传成功后  得到信息
                $filenames=str_replace('\\', '/', $new_files);
                $file_name=substr($filenames,strripos($filenames,"/upload"));
                return $file_name;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 检查交易状态
     */
    public function check_deal()
    {
        $uid = session('user_id');
        $uinfo = db('xy_users')->field('deal_status,status,balance,level dc')->find($uid);
        if($uinfo['status']==2) return ['code'=>1,'info'=>lang('该账户已被禁用')];
        if($uinfo['deal_status']==0) return ['code'=>1,'info'=>lang('该账户交易功能已被冻结')];
        if($uinfo['deal_status']==3) return ['code'=>1,'info'=>lang('该账户存在未完成订单，无法继续抢单！')];
        if($uinfo['balance']<config('deal_min_balance')) return ['code'=>1,'info'=>lang('余额低于').config('deal_min_balance').lang('，无法继续交易')];

        return false;
    }
    
}
