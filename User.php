<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2019 
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 

// +----------------------------------------------------------------------

namespace app\index\controller;

use library\Controller;
use think\Db;
use \think\Lang;
use think\Cookie;
use \think\Config;
/**
 * 登录控制器
 */
class User extends Controller
{

    protected $table = 'xy_users';
    
    public function __construct()
    {
         if(!Cookie('lang')){
            Cookie('lang','en-us');
            echo '<script> location="" </script>';
        }
    }

    /**
     * 空操作 用于显示错误页面
     */
    public function _empty($name){

        return $this->fetch($name);
    }
    
    
    public function cutlang(){
        $lang = input('post.lang');
//        if($lang =='zh-cn'){
//            Cookie('lang','zh-cn');
//        }else if($lang =='en-us'){
//            Cookie('lang','en-us');
//        }else if($lang =='vi'){
//             Cookie('lang','vi');
//        }else if($lang =='sp'){
//            Cookie('lang','sp');
//        }
        Cookie('lang',$lang);
        echo json_encode(['code'=>1]);
    } 

    //用户登录页面
    public function login()
    {

        if(session('user_id')) $this->redirect('index/index');
        return $this->fetch();
    }

    //用户登录接口
    public function do_login()
    {
        // $this->applyCsrfToken();//验证令牌
        $tel = input('post.tel/s','');
        $num = Db::table($this->table)->where(['tel'=>$tel])->count();
        if(!$num){
            return json(['code'=>1,'info'=>lang('账号不存在')]);
        }

        $pwd         = input('post.pwd/s', ''); 
        $keep        = input('post.keep/b', false);    
        $jizhu        = input('post.jizhu/s', 0);


        $userinfo = Db::table($this->table)->field('id,pwd,salt,pwd_error_num,allow_login_time,status,login_status,headpic')->where('tel',$tel)->find();
        if(!$userinfo)return json(['code'=>1,'info'=>lang('用户不存在')]);
        if($userinfo['status'] != 1)return json(['code'=>1,'info'=>lang('用户已被禁用')]);
        //if($userinfo['login_status'])return ['code'=>1,'info'=>'此账号已在别处登录状态'];
        if($userinfo['allow_login_time'] && ($userinfo['allow_login_time'] > time()) && ($userinfo['pwd_error_num'] > config('pwd_error_num')))return ['code'=>1,'info'=>lang('密码连续错误次数太多，请').config('allow_login_min').lang('分钟后再试')];  
        if($userinfo['pwd'] != sha1($pwd.$userinfo['salt'].config('pwd_str'))){
            Db::table($this->table)->where('id',$userinfo['id'])->update(['pwd_error_num'=>Db::raw('pwd_error_num+1'),'allow_login_time'=>(time()+(config('allow_login_min') * 60))]);
            return json(['code'=>1,'info'=>lang('密码错误!')]);  
        }
        
        Db::table($this->table)->where('id',$userinfo['id'])->update(['pwd_error_num'=>0,'allow_login_time'=>0,'login_status'=>1]);
        session('user_id',$userinfo['id']);
        session('avatar',$userinfo['headpic']);

        if ($jizhu) {
            cookie('tel',$tel);
            cookie('pwd',$pwd);
        }

        if($keep){
            Cookie::forever('user_id',$userinfo['id']);
            Cookie::forever('tel',$tel);
            Cookie::forever('pwd',$pwd);
        }
        return json(['code'=>0,'info'=>lang('登录成功!')]);  
    }
    
    public function send()
    {


    	$email = @input('tel');
    	$code = rand(999,9999);
    	$type = @input('type');
    	$obj = new \email\Send();
    	$datas = $obj->send($email,lang('您的验证码为:').$code,lang('验证码'));

    	if($datas['code']==200){
    	     $res = Db::name('xy_verify_msg')->where('tel',$email)->find();
    	     $data = [
	    			'msg'=>$code,
	    			'addtime'=>time(),
	    			'type'=>$type
	    		];
    	    if($res){
    	        $ret = Db::name('xy_verify_msg')->where('tel',$email)->update($data);
    	    }else{
    	        $data['tel']=$email;
    	        $ret = Db::name('xy_verify_msg')->insert($data);
    	    }
	    	if($ret){
	    		return json(['code'=>0,'info'=>lang('发送成功')]);   
	    	}else{
	    		return json(['code'=>1,'info'=>lang('发送失败')]);   
	    	}
    	}else{
    		return json(['code'=>1,'info'=>$datas['msg']]);   
    	}
    	
    }

    /**
     * 用户注册接口
     */
    public function do_register()
    {
        $tel = input('post.tel/s','');
        $user_name   = input('post.user_name/s', '');
        //$user_name = '';    //交给模型随机生成用户名
        $task_nums    = db('xy_level')->where('level',0)->find();
        $task_pddnum = $task_nums['pdd_num'];
        $task_jdnum = $task_nums['jd_num'];
        $task_tbnum = $task_nums['tb_num'];
        $task_tmnum = $task_nums['tm_num'];
        $task_wxnum = $task_nums['wx_num'];
        $task_dznum = $task_nums['dz_num'];
        $verify      = input('post.verify/d', '');       //短信验证码
        $pwd         = input('post.pwd/s', '');
        $pwd2        = input('post.deposit_pwd/s', '');
        $invite_code = input('post.invite_code/s', ''); 
        $ip         = request()->ip();//邀请码
        $xiaoxi = sysconf('site_xiaoxi');
//        $head_pic         = '/static/headimg/0.8612276.png';
        $head_pic         = '/static/headimg/1.png';
        if(!$invite_code) return json(['code'=>1,'info'=>lang('邀请码不能为空')]);
        
        if(config('app.verify')){
            $verify_msg = Db::table('xy_verify_msg')->field('msg,addtime')->where(['tel'=>$tel,'type'=>1])->find();
            // return json(['code'=>1,'info'=>$verify_msg['msg']]);
            // exit;
            if(!$verify_msg)return json(['code'=>1,'info'=>lang('验证码不存在')]);
            if($verify != $verify_msg['msg'])return json(['code'=>1,'info'=>lang('验证码错误')]);
           // if(($verify_msg['addtime'] + (config('app.zhangjun_sms.min')*60)) < time())return json(['code'=>1,'info'=>'验证码已失效']);
        }

        $pid = 0;
        if($invite_code) {
            $parentinfo = Db::table($this->table)->field('id,status')->where('invite_code',$invite_code)->find();
            if(!$parentinfo) return json(['code'=>1,'info'=>lang('邀请码不存在')]);
            if($parentinfo['status'] != 1) return json(['code'=>1,'info'=>lang('该推荐用户已被禁用')]);

            $pid = $parentinfo['id'];
        }

        $res = model('admin/Users')->add_users($tel,$user_name,$pwd,$pid,$task_pddnum,$task_jdnum,$task_tbnum,$task_tmnum,$task_wxnum,$task_dznum,$ip,$head_pic,$xiaoxi,$pwd2);
        
        $res['code']==0 && $this->recommend($pid);
        
        return json($res);
    }
    
    public function recommend($pid)
    {
        $num = Db::name('xy_users')->where('parent_id',$pid)->count('*');
    	//满50加一抽奖
    	if($num == 50){
    		Db::name('xy_users')->where('id',$pid)->setInc('cj_num',1);
    	}
    	//满100升v1
    	if($num == 100){
    		$level = Db::name('xy_users')->where('id',$pid)->value('level');
    		if($level == 0){
    			Db::name('xy_users')->where('id',$pid)->update(['level'=>1]);
    		}
    	}
    }


    public function logout(){
        Db::table($this->table)->where('id',session('user_id'))->update(['login_status'=>0]);
        \Session::delete('user_id');
        $this->redirect('login');
    }

    /**
     * 重置密码
     */
    public function do_forget()
    {
        if(!request()->isPost()) return json(['code'=>1,'info'=>lang('错误请求')]);
        $tel = input('post.tel/s','');
        $pwd = input('post.pwd/s','');
        $verify = input('post.verify/d',0);
        if(config('app.verify')){
            $verify_msg = Db::table('xy_verify_msg')->field('msg,addtime')->where(['tel'=>$tel,'type'=>2])->find();
            if(!$verify_msg)return json(['code'=>1,'info'=>lang('验证码不存在')]);
            if($verify != $verify_msg['msg'])return json(['code'=>1,'info'=>lang('验证码错误')]);
            //if(($verify_msg['addtime'] + (config('app.zhangjun_sms.min')*60)) < time())return json(['code'=>1,'info'=>'验证码已失效']);
        }
        $res = model('admin/Users')->reset_pwd($tel,$pwd);
        return json($res);
    }

    public function register()
    {
        $param = \Request::param(true);
        $this->invite_code = isset($param[1]) ? trim($param[1]) : '';  
        return $this->fetch();
    }
    
    public function zhaohui()
    {
        return $this->fetch();
    }

    public function article_detail()
    {
        $id = input('get.id/d','');
        $title = input('get.title/s','');
        if($title == '') {
            $data['id'] = $id;
        }
        if($id == '') {
//            $data['title'] = str_replace(" ", '_', $title);
            $data['title'] = urldecode($title);
        }
        $this->msg = db('xy_index_msg')->where($data)->find();
        return $this->fetch();
    }

    public function audit() {
        if(request()->isPost()){
            //$id = input('post.id/d',1);
            $arr=input('post.');
            if(isset($arr['sn'])) {
                $task_sn = $this->decrypt($arr['sn']);
            } else {
                return json(['code'=>1,'info'=>lang('信息有误')]);
            }
            $update_time = time();
            $task = Db::table('xy_task_apply')->where('task_sn', $task_sn)->find();
            if($task['status'] == 2 || $task['status'] == 4) {
                return json(['code'=>1,'info'=>lang('提交失败')]);
            }
            $p = rand(0,1);
            if($p == 1) {
                //审核通过
                $res = Db::name('xy_task_apply')->where('task_sn', $task_sn)->update(['status' => 2,'update_time' => $update_time]);
                if($res){
                    $data['uid'] = $task['member_id'];
                    $data['oid'] = $task['id'];
                    $data['type'] = 0;
                    $data['num'] = $task['price'];
                    $data['status'] = 1;
                    $data['desc'] = '任务收入';
                    $data['addtime'] = time();
                    $data['num_status'] = 1;
                    $insert = Db::table('xy_balance_log') -> insert($data);
                    if($insert) {
                        return json(['code'=>0,'info'=>lang('提交成功')]);
                    }
                }else{
                    return json(['code'=>1,'info'=>lang('提交失败')]);
                }
            } else {
                //审核不通过
                $res = Db::name('xy_task_apply')->where('task_sn', $task_sn)->update(['status' => 3,'update_time' => $update_time]);
                if($res) {
                    return json(['code'=>0,'info'=>lang('提交成功')]);
                } else{
                    return json(['code'=>1,'info'=>lang('提交失败')]);
                }
            }
        }
    }
    
    // public function audit1() {
    //     if(request()->isGet()){
    //         //$id = input('post.id/d',1);
    //         $str=input('sn');//传入的订单信息
    //         $arr = explode(",",$str);//分割成数组
    //         foreach($arr as $k => $v) {
    //             //订单信息
    //             $task = Db::table('xy_task_apply')->where('task_sn = "'. $v . '" AND status <> 2')->find();
    //             //修改为通过
    //             if($task) {
    //                 $res = Db::name('xy_task_apply')->where('task_sn', $v)->update(['status' => 2]);
    //                 if($res) {
    //                     $data['uid'] = $task['member_id'];
    //                     $data['oid'] = $task['id'];
    //                     $data['type'] = 0;
    //                     $data['num'] = $task['price'];
    //                     $data['status'] = 1;
    //                     $data['desc'] = '任务收入';
    //                     $data['yuedu'] = 0;
    //                     $data['addtime'] = time();
    //                     $data['num_status'] = 1;
    //                     $insert = Db::table('xy_balance_log') -> insert($data);
    //                     if($insert) {
    //                         echo $v."修改成功<br>";
    //                     } else {
    //                         echo $v."修改失败<br>";
    //                     }
    //                 }
    //             } else {
    //                 echo $v."未找到<br>";
    //             }
    //         }
    //     }
    // }

    public  function thaw() {
        $thar_time = strtotime(date('Y-m-d'))-(6.5 * 24 * 3600);
        $data = Db::name('xy_balance_log')
            ->where('type', 0)
            ->where('num_status', 1)
            ->where('addtime','<',$thar_time)
            ->group('uid')
            ->field('SUM(num) as allnum,uid,GROUP_CONCAT(id) as ids')
            ->select();
        $n = '';
        if(count($data) == 0)
        {
            echo("已全部处理完成");
            die;
        }
        foreach ($data as $k => $v) {
//             $thaw_time = 7 * 24 * 3600;
// //            $thaw_time = 60;
//             $thaw_day = $v['addtime'] + $thaw_time;
//             $thaw_day = strtotime(date('Y-m-d', $thaw_day));//七日后0点
//             if($thaw_day < time()) {
                
//             }
            $jdtime = time();
            $user = Db::name('xy_users')->where('id', $v['uid'])->find();
            
            //解冻状态
            $update_log = Db::name('xy_balance_log')
                ->where('id','in',explode(',',$v['ids']))
                ->update(['num_status' => 2, 'jd_time'=>$jdtime ]);

            //添加到账户余额
            $update_user = Db::name('xy_users')
                ->where('id', $v['uid'])
                ->setInc('balance', $v['allnum']);
            if($update_user) {
                $arr = explode(',',$v['ids']);
                $max = max($arr);
                $balance = [];
                $balance['before'] = $user['balance']? $user['balance']:0;
                $balance['after'] = $balance['before'] + $v['allnum'];
                $liushui = Db::name('xy_balance_log')->where('id', $max)->update($balance);
            }
            $n = $n . ',' . $v['uid'];
        }
        echo("处理完成:" . count($data) . "---|---" . $n);
    }

    public function adddata() {
        if(empty(input('ids')) || empty(input('uid'))) {
            return '<script>alert("请输入ID号");history.go(-1);</script>';
        }
        $uids = input('ids');
        $uids = str_replace(' ','',$uids);//去除空格
        $uids = explode(',',$uids);

        $uid = input('uid'); //添加的用户的ID
        $task_num = 0;
        $deposit_num = 0;
        $log_num = 0;
        $balance = 0;
        $spacing = 20;
        $today = strtotime(date('Y-m-d',time()));

        $start = input('start')? strtotime(input('start')):strtotime("2022-12-1");
        $end = input('end')? strtotime(input('end')):$today;

        //先获取该用户目前的最后的任务日期
        $uid_task = Db::name('xy_task_apply')
                ->where('member_id', $uid)
                ->order('create_time desc')
                ->find();
        $task_time = $uid_task? $uid_task['create_time']:null;

        foreach($uids as $k => $v) {
            $task_time = !$task_time? $start:strtotime("+1 day", $task_time);
            if($task_time > $end) break;
            $uids_task = Db::name('xy_task_apply')
                ->where('member_id', $v)
                ->where('status', '>', 1)
                ->order('create_time')
                ->field('task_sn,task_id,member_id,status,price,task_type,create_time,end_time,update_time,shenhe_time')
                ->select();
            //计算时间间隔
            if($uids_task) {
                $oldtime = strtotime(date('Y-m-d', $uids_task[0]['create_time']));
                $interval = abs(floor(($oldtime - $task_time)/86400));
                $symbol = $oldtime > $task_time? "-": "+";
                $intervalstr = $symbol .$interval. " day";

                $data = [];
                foreach($uids_task as $i => $vo) {
                    if(strtotime($intervalstr, $vo['create_time']) > $end) break;
                    if(strtotime('+7 day', $vo['create_time']) > $today) break;
                    $data[$i] = $vo;
                    $data[$i]['member_id'] = $uid;
                    $data[$i]['create_time'] = strtotime($intervalstr, $vo['create_time']);
                    $data[$i]['end_time'] = strtotime($intervalstr, $vo['end_time']);
                    $data[$i]['update_time'] = $vo['update_time']? strtotime($intervalstr, $vo['update_time']):null;
                    $data[$i]['shenhe_time'] = $vo['shenhe_time']? strtotime($intervalstr, $vo['shenhe_time']):null;
                    $task_time = $data[$i]['create_time'];
                    $task_num++;
                }
                foreach (array_chunk($data, $spacing) as $k => $value) {
                    Db::name('xy_task_apply')->insertAll($value);
                }

                //提现记录
                $uids_deposit = Db::name('xy_deposit')
                    ->where('uid', $v)
                    ->order('addtime')
                    ->field('uid,num,addtime,endtime,status,shouxu,real_num,address,net')
                    ->select();
                $data = [];
                foreach($uids_deposit as $i => $vo) {
                    if(strtotime($intervalstr, $vo['addtime']) > $end) break;
                    $data[$i] = $vo;
                    $data[$i]['uid'] = $uid;
                    $data[$i]['status'] = 2;
                    $data[$i]['addtime'] = strtotime($intervalstr, $vo['addtime']);
                    $data[$i]['endtime'] = $vo['endtime']>0? strtotime($intervalstr, $vo['endtime']):0;
                    $deposit_num++;
                }
                foreach (array_chunk($data, $spacing) as $k => $value) {
                    Db::name('xy_deposit')->insertAll($value);
                }

                //明细记录
                $uids_balance = Db::name('xy_balance_log')
                    ->where('uid', $v)
                    ->where('num_status', 'in', [0,2])
                    ->order('addtime')
                    ->field('uid,oid,type,num,addtime,desc,yuedu,status,num_status')
                    ->select();
                $data = [];
                foreach($uids_balance as $i => $vo) {
                    if(strtotime($intervalstr, $vo['addtime']) > $end) break;
                    $data[$i] = $vo;
                    $data[$i]['uid'] = $uid;
                    $data[$i]['addtime'] = strtotime($intervalstr, $vo['addtime']);
                    $log_num++;
                    $balance = $vo['status'] == 1? $balance+$vo['num']:$balance-$vo['num'];
                }
                foreach (array_chunk($data, $spacing) as $k => $value) {
                    Db::name('xy_balance_log')->insertAll($value);
                }
            }
        }
        Db::name('xy_users')->where('id',$uid)->update(['balance' => $balance]);
        echo "添加任务记录".$task_num."条；<br>";
        echo "添加提现记录".$deposit_num."条；<br>";
        echo "添加明细记录".$log_num."条；<br>";
        echo '<button style="margin:20px 80px;padding: 2px 10px" onclick="history.go(-1)">返回</button>';
    }

    // public function binduser() {
    //     // if(empty(input('id')) && empty(input('uid'))) {
    //     //     return '<script>alert("请输入ID号");history.go(-1);</script>';
    //     // }
    //     $uid = input('uid');
    //     if($uid) {
    //         $update = Db::name('xy_bind')->where('id',1)->update(['uid' => $uid]);
    //     }
    //     $id = input('id');
    //     if($id) {
    //         $user = Db::name('xy_bind')->where('id',1)->find();
    //         if(empty($user['uid'])) {
    //             return '<script>alert("请先设置用户ID再进行绑定！");history.go(-1);</script>';
    //         }
    //         $task = Db::name('xy_task_apply')->where('member_id',$user['uid'])->find();
    //         if(empty($task)) {
    //             return '<script>alert("请先添加数据再进行绑定！");history.go(-1);</script>';
    //         }
    //         $update = Db::name('xy_bind')->where('id',1)->update(['binduid' => $id]);
    //     }
    //     if($update) {
    //         return '<script>alert("操作成功！");history.go(-1);</script>';
    //     } else {
    //         return '<script>alert("操作失败！");history.go(-1);</script>';
    //     }
    // }
    public function binduser() {
        if(empty(input('id')) && empty(input('uid'))) {
            return '<script>alert("请输入ID号");history.go(-1);</script>';
        }
        //获取账号ID数组
        $uid = input('uid');
        $uid = str_replace(' ','',$uid);//去除空格
        $uid = explode(',',$uid);

        //获取绑定ID数组
        $id = input('id');
        $id = str_replace(' ','',$id);//去除空格
        $id = explode(',',$id);

        if(count($uid) != count($id)) {
            return '<script>alert("两组ID数量不一致");history.go(-1);</script>';
        }

        $msg = '';
        foreach($uid as $k => $v) {
            $bind = Db::name('xy_bind')->where('uid',$v)->find();
            if($bind) {
                $update = Db::name('xy_bind')->where('uid',$v)->update(['binduid' => $id[$k]]);
                if($update) {
                    $msg .= $v . " => " . $id[$k] . " 绑定成功<br>";
                } else {
                    $msg .= $v . " => " . $id[$k] . " 绑定失败<br>";
                }
            } else {
                $data = [];
                $data['uid'] = $v;
                $data['binduid'] = $id[$k];
                $insert = Db::name('xy_bind')->insert($data);
                if($insert) {
                    $msg .= $v . " => " . $id[$k] . " 绑定成功<br>";
                } else {
                    $msg .= $v . " => " . $id[$k] . " 绑定失败<br>";
                }
            }
        }
        if(empty($msg)) {
            echo "操作失败<br>";
        } else {
            echo $msg;
        }
        echo '<button style="margin:20px 80px;padding: 2px 10px" onclick="history.go(-1)">返回</button>';
    }

    //同步前一天绑定账号订单的状态
    public function bindorder() {
        $users = Db::name('xy_bind')->where(1)->select();
        $spacing = 20;
        foreach($users as $k => $user) {
        
            $task_num = 0;
            $deposit_num = 0;
            $log_num = 0;
            $sum = 0;
            
            //同步订单
            $task_order = Db::name('xy_task_apply')
                            ->where('member_id',$user['binduid'])
                            ->where('status','in',[2,3])
                            ->whereTime('create_time', 'yesterday')
                            ->select();
            
            foreach($task_order as $k => $v) {
                $data = [];
                $data['status'] = $v['status'];
                if(!empty($v['update_time'])) {
                    $data['update_time'] = $v['update_time'];
                }
                if(!empty($v['shenhe_time'])) {
                    $data['shenhe_time'] = $v['shenhe_time'];
                }
                Db::name('xy_task_apply')
                    ->where('task_sn', $v['task_sn'])
                    ->where('member_id', $user['uid'])
                    ->update($data);
                $task_num++;
            }

            $logs = Db::name('xy_balance_log')
                        ->where('uid', $user['binduid'])
                        ->where('status', 1)
                        ->whereTime('addtime', 'yesterday')
                        ->order('addtime')
                        ->field('uid,oid,type,num,addtime,desc,yuedu,status,num_status')
                        ->select();
            foreach($logs as $k => $v) {
                $logs[$k]['uid'] = $user['uid'];
                $log_num++;
            }
            foreach (array_chunk($logs, $spacing) as $k => $log) {
                Db::name('xy_balance_log')->insertAll($log);
            }
            
            echo "用户ID：".$user['uid'];
            echo "更新任务记录".$task_num."条；<br>";
            echo "添加明细记录".$log_num."条；<br>";
        }
    }

    public  function totopat() {
        $data = Db::name('xy_balance_log')
            ->where('type', 0)
            ->where('num_status', 1)
            ->group('uid')
            ->field('SUM(num) as allnum,uid,GROUP_CONCAT(id) as ids')
            ->select();
        $n = '';
        if(count($data) == 0)
        {
            echo("已全部处理完成");
            die;
        }
        foreach ($data as $k => $v) {
            $jdtime = time();
            $user = Db::name('xy_users')->where('id', $v['uid'])->find();
            
            //解冻状态
            $update_log = Db::name('xy_balance_log')
                ->where('id','in',explode(',',$v['ids']))
                ->update(['num_status' => 2, 'jd_time'=>$jdtime ]);

            //添加到账户余额
            $update_user = Db::name('xy_users')
                ->where('id', $v['uid'])
                ->setInc('topat', $v['allnum']);
            if($update_user) {
                $arr = explode(',',$v['ids']);
                $max = max($arr);
                $balance = [];
                $balance['before'] = $user['topat']? $user['topat']:0;
                $balance['after'] = $balance['before'] + $v['allnum'];
                $data1 =array(
                    'uid' =>$v['uid'],
                    'type' =>10,
                    'oid' => $max,
                    'num' =>$v['allnum'],
                    'status' =>2,
                    'desc' =>'Transfer to TOPAT '. $v['allnum']. ' TOPAT',
                    'yuedu' =>0,
                    'addtime' => time(),
                    'before' => $balance['before'],
                    'after' => $balance['after'],
                );
                Db::name('xy_balance_log')->insert($data1);
            }
            $n = $n . ',' . $v['uid'];
        }
        echo("处理完成:" . count($data) . "---|---" . $n);
    }
    
}