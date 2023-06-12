<?php

namespace app\index\controller;

use library\Controller;
use think\Db;

class Task extends Base
{

    public function index()
    {
        $uid = session('user_id');
        //在线人数
//        $this->zaixiannum = db('xy_users')->where('login_status',1)->sum('login_status');
//        $this->zxnum = sysconf('site_zxnum') * $this->zaixiannum;
        $this->info = db('xy_users')->find($uid);
        $task_shouyi = db('xy_balance_log')->where('uid',$uid)->where('type',0)->select();
        $shouyi = 0;
        $today = strtotime(date('Y-m-d', time()));
        foreach ($task_shouyi as $k => $v) {
            if($v['addtime'] > $today) $shouyi +=  $v['num'];
        }
        $this->task_shouyi = $shouyi;//发放收益
        $this->task_sum = count($task_shouyi);//完成任务总数
        //首页栏目列表
//        $this->lanmu = db('xy_lanmu')->order('sort asc')->select();
        //首页任务分类列表
        $this->task_cate = db('xy_task_cate')->order('sort asc')->select();
        $this->level = db('xy_level')->order('id asc')->select();
        
        //发放收益
//        $this->task_shouyi = db('xy_balance_log')->where('uid',$uid)->where('type',0)->select();
        //完成任务总数
        //$task_sum = db('xy_balance_log')->where('uid',$uid)->where('type',0)->count();
        //$this->task_sum = sprintf("%05d", $task_sum);
        //等级分类限制
        $this->ulevel = Db::table('xy_level')->where('level',$this->info['level'])->find();
        //完成任务平均时间
//        $add_time = db('xy_task_apply')->where('member_id',$uid)->where('status',2)->avg('create_time');//领取平均时间
//        $add_time1 = db('xy_convey')->where('uid',$uid)->where('status',1)->avg('addtime');//领取平均时间
//        $end_time = db('xy_task_apply')->where('member_id',$uid)->where('status',2)->avg('shenhe_time');//审核平均时间
//        $end_time1 = db('xy_convey')->where('uid',$uid)->where('status',1)->avg('endtime');//提交平均时间
//        $task_time = $end_time - $add_time;
//        $quan_time = $end_time1 - $add_time1;
//        $res = randomMoney($task_time,$quan_time);//平均时间
//        $str_res1 = implode($res);
//        $str_res = $str_res1/60;
//        $pingjun = round($str_res);
//        $this->pj_time = ToTime($pingjun);
        $this->pj_time = 0;
            //广告
        $this->ads = db('xy_ads')->order('create_time desc')->where('status',1)->where('type',2)->select();
        return $this->fetch();
    }
    
    //分类页
    public function type()
    {
        $uid = session('user_id');
        $this->uid=$uid;
        $level_id = input('get.id/d',0);
        $this->level_id=$level_id;
        $cate_id = input('get.type/d',1);
        $this->level = db('xy_level')->where('level',$level_id)->find();
        $this->action = db('xy_task_cate')->where('action','100')->select();
        $this->action2 = db('xy_task_cate')->where('action','=',$this->level['level'])->select();
        if(count($this->action2)!=0){
            $count=count($this->action);
            foreach($this->action2 as $k=>$v){
                $num=$k+$count;
                $this->action[$num]=$this->action2[$k];
            }
        }
        $this->task_cate = db('xy_task_cate')->where('id',$cate_id)->find();
        $this->task_type = db('xy_task_type')->where('id',$this->task_cate['type'])->find();
        $this->user_info = db('xy_users')->where('id',$uid)->find();
        $where=['cid'=>$cate_id,'level'=>$level_id];
        $this->task_list = db('xy_task')->where($where)->where('end_time','>',time())->order('sort asc')->select();
        $this->status = $status= input('get.status/d',0);
        $where =[];
        if ($status) {
            $status == -1 ? $status = 0:'';
            $where['xc.status'] = $status;
        }
        $uid = session('user_id');
        //余额
        $this->balance = Db::name('xy_users')->where('id',$uid)->value('balance');

        //获取该分类下的今日收益
        $task_id = Db::name('xy_task')->where('cid',$cate_id)->select();
        $this->money = 0;
        foreach ($task_id as $key => $i) {
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $where =array(
                'member_id'=>$uid,
                'task_id'=>$i['id'],
                'status'=>2
            );
            $this->apply = Db::name('xy_task_apply')->where($where)->where('create_time', '>', $beginToday)->where('create_time', '<', $endToday)->sum('price');
            
            $this->money += $this->apply;
        }

        $this->task_apply = db('xy_task_apply')->where('member_id',$uid)->where('status',0)->select();
        $this->task_num = count($this->task_apply);
        return $this->fetch();
    }
    
    public function video(){
        $this->vod_url = input('get.vod_url/s','');
        return $this->fetch();
    }
    
    //领取任务
    public function get_task()
    {
        $uid = session('user_id');
        $member_id = session('user_id');
        $task_id = input('post.id/d',1);
        $status = 0;
        $price = input('post.price/s','');
        if(empty($member_id)){
            return json(['code'=>1,'msg'=>lang('你要干啥？')]);
        }
        if(empty($task_id)){
            return json(['code'=>1,'msg'=>lang('你要领取啥呢？')]);
        }
        if(empty($price)){
            return json(['code'=>1,'msg'=>lang('敢胡闹揍你？')]);
        }
//        $cid = Db::name('xy_task')->where('id',$task_id)->value('cid');
//        if($cid == 1){
//            $end_time = time() + config('task_chaoshi')*3600;
//        }
//        if($cid == 2){
//            $end_time = time() + config('wx_chaoshi')*3600;
//        }
        //上面我注释的
        //$shiming = Db::name('xy_users')->where('id',$uid)->value('id_status');//我注释的
        // if($shiming == 0){
        //     return json(['code'=>3]);
        // }
        $where =array(
            'member_id'=>$member_id,
            'task_id'=>$task_id,
            'status'=>0
        );
        $this->task_list = db('xy_task_apply')->where($where)->find();
        if($this->task_list){
            return json(['code'=>1,'msg'=>lang('您已领取该任务！')]);
        }
        $task_cid = db('xy_task')->where('id',$task_id)->value('cid');
        if($task_cid == 1){
            $task_num = Db::name('xy_users')->where('id',$member_id)->value('task_dznum');
            $end_time = time() + config('task_chaoshi')*3600;
        }
        if($task_cid == 2){
            $task_num = Db::name('xy_users')->where('id',$member_id)->value('task_wxnum');
            $end_time = time() + config('wx_chaoshi')*3600;
        }
        if($task_num < 1){
            return json(['code'=>1,'msg'=>lang('剩余可接任务数不足')]);
        }
        $vip_time = Db::name('xy_users')->where('id',$member_id)->value('viptime');
        if($vip_time < time()){
            return json(['code'=>4,'msg'=>lang('请您选择升级vip后在进行接单完成任务！')]);
        }
        //获取订单代号
        $header = Db::name('system_config')->where('name','order_header')->find();
        //处理uid
        $num=str_pad($uid,5,"0",STR_PAD_LEFT);
        //生成流水号
        $task_sn = $header['value']."|".date('YmdHis')."|".$num."|".rand(1000,9999);
        $data =array(
            'member_id'=>$member_id,
            'task_id'=>$task_id,
            'status'=>$status,
            'price'=>$price,
            'end_time'=>$end_time,
            'create_time'=>time(),
            'task_sn'=>$task_sn
        );
        $res = Db::table('xy_task_apply')->insert($data);
        if($res){
            Db::name('xy_task')->where('id',$task_id)->setInc('apply_num');
            Db::name('xy_task')->where('id',$task_id)->setDec('max_num');

            if($task_cid == 1){
                Db::name('xy_users')->where('id',$member_id)->setDec('task_dznum');
            }
            if($task_cid == 2){
                Db::name('xy_users')->where('id',$member_id)->setDec('task_wxnum');
            }
            return json(['code'=>0,'msg'=>lang('领取成功')]);
        }else{
            return json(['code'=>1,'msg'=>lang('领取失败')]);
        }
    }
    
	public function secToTime($times){  
        $result = '00:00:00';  
        if ($times>0) {
                $hour = floor($times/3600);
                $minute = floor(($times-3600 * $hour)/60);  
                $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);  
                $result = $hour.'时'.$minute.'分'.$second.'秒';
        }  
        return $result;  
	}
    //任务记录
    public function task_apply1()
    {
        $uid = session('user_id');
        $type = isset($_GET['type'])? $_GET['type']:0;
        $this->type = $type;
        $time = ['','today', 'yesterday', 'week', 'month'];
        $where = $time[$type];
        if(request()->isPost()) {
            if($_POST['start'] == "开始时间") {
                $_POST['start'] = '';
            }
            if($_POST['end'] == "结束时间") {
                $_POST['end'] = '';
            }
            if($_POST['start'] && $_POST['end']){
                $start = strtotime($_POST['start'] . " 00:00:00");
                $end = strtotime($_POST['end'] . " 23:59:59");
                if($start > $end) {
                    return json(['code'=>1,'info'=>lang('日期填写错误')]);
                }
                $where = array($start,$end);
            }
            else if($_POST['start'])
            {
                $start = strtotime($_POST['start'] . " 00:00:00");
                $where= array($start,time());
            }
            else if($_POST['end'])
            {
                $end = strtotime($_POST['end'] . " 23:59:59");
                $where = array('',$end);
            }
        }
        $apply_list = db('xy_task_apply')
            ->where('status',0)
            ->where('member_id',$uid)
            ->whereTime('create_time', $where)
            ->order('create_time desc')
            ->paginate(10);
        $arr = [];
        foreach($apply_list as $k => $v) {
            $arr[$k]['task_sn'] = $this->encrypt($v['task_sn']);
        }
        $this->link = $arr;
        $this->apply_list = $apply_list;
        $this->task_num = count($apply_list);
        //11-23注释 多余查询 task_apply2~5相同问题
//        $this->apply_list1 = db('xy_task_apply')->where('status',1)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list2 = db('xy_task_apply')->where('status',2)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list3 = db('xy_task_apply')->where('status',3)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list4 = db('xy_task_apply')->where('status',4)->where('member_id',$uid)->order('create_time desc')->paginate(10);

        $this->task = db('xy_task')->order('sort desc')->select();
        //$this->task_cate = db('xy_task_cate')->order('id desc')->select();
        $this->xztime = time();
        $this->status = $status= input('get.status/d',0);
        $where =[];
        if ($status) {
            $status == -1 ? $status = 0:'';
            $where['xc.status'] = $status;
        }

        //11-23注释 暂时无用 多余查询 task_apply2~5相同问题
//        $this->apply_list11 = db('xy_convey')->where('status',0)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->apply_list12 = db('xy_convey')->where('status',1)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->apply_list13 = db('xy_convey')->where('status',4)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->goods = db('xy_goods_list')->order('addtime desc')->select();

        $this->apply_list1 = [];
        $this->apply_list2 = [];
        $this->apply_list3 = [];
        $this->apply_list4 = [];
        $this->apply_list11 = [];
        $this->apply_list12 = [];
        $this->apply_list13 = [];

        return $this->fetch();
    }
    public function task_apply2()
    {
        $uid = session('user_id');
        $type = isset($_GET['type'])? $_GET['type']:0;
        $this->type = $type;
        $time = ['','today', 'yesterday', 'week', 'month'];
        $where = $time[$type];
        if(request()->isPost()) {
            if($_POST['start'] == "开始时间") {
                $_POST['start'] = '';
            }
            if($_POST['end'] == "结束时间") {
                $_POST['end'] = '';
            }
            if($_POST['start'] && $_POST['end']){
                $start = strtotime($_POST['start'] . " 00:00:00");
                $end = strtotime($_POST['end'] . " 23:59:59");
                if($start > $end) {
                    return json(['code'=>1,'info'=>lang('日期填写错误')]);
                }
                $where = array($start,$end);
            }
            else if($_POST['start'])
            {
                $start = strtotime($_POST['start'] . " 00:00:00");
                $where= array($start,time());
            }
            else if($_POST['end'])
            {
                $end = strtotime($_POST['end'] . " 23:59:59");
                $where = array(time(),$end);
            }
        }
        $apply_list1 = db('xy_task_apply')
            ->where('status',1)
            ->where('member_id',$uid)
            ->whereTime('create_time', $where)
            ->order('create_time desc')
            ->paginate(10);
        $this->apply_list1 = $apply_list1;
        $this->task_num = count($apply_list1);
//        $this->apply_list = db('xy_task_apply')->where('status',0)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list1 = db('xy_task_apply')->where('status',1)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list2 = db('xy_task_apply')->where('status',2)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list3 = db('xy_task_apply')->where('status',3)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list4 = db('xy_task_apply')->where('status',4)->where('member_id',$uid)->order('create_time desc')->paginate(10);
        $this->task = db('xy_task')->order('sort desc')->select();
//        $this->task_cate = db('xy_task_cate')->order('id desc')->select();
        $this->xztime = time();
        $this->status = $status= input('get.status/d',0);
        $where =[];
        if ($status) {
            $status == -1 ? $status = 0:'';
            $where['xc.status'] = $status;
        }

//        $this->apply_list11 = db('xy_convey')->where('status',0)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->apply_list12 = db('xy_convey')->where('status',1)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->apply_list13 = db('xy_convey')->where('status',4)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->goods = db('xy_goods_list')->order('addtime desc')->select();

        $this->apply_list = [];
        $this->apply_list2 = [];
        $this->apply_list3 = [];
        $this->apply_list4 = [];
        $this->apply_list11 = [];
        $this->apply_list12 = [];
        $this->apply_list13 = [];
        return $this->fetch();
    }
    public function task_apply3()
    {
        $uid = session('user_id');
        $type = isset($_GET['type'])? $_GET['type']:0;
        $this->type = $type;
        $time = ['','today', 'yesterday', 'week', 'month'];
        $where = $time[$type];
        if(request()->isPost()) {
            if($_POST['start'] == "开始时间") {
                $_POST['start'] = '';
            }
            if($_POST['end'] == "结束时间") {
                $_POST['end'] = '';
            }
            if($_POST['start'] && $_POST['end']){
                $start = strtotime($_POST['start'] . " 00:00:00");
                $end = strtotime($_POST['end'] . " 23:59:59");
                if($start > $end) {
                    return json(['code'=>1,'info'=>lang('日期填写错误')]);
                }
                $where = array($start,$end);
            }
            else if($_POST['start'])
            {
                $start = strtotime($_POST['start'] . " 00:00:00");
                $where= array($start,time());
            }
            else if($_POST['end'])
            {
                $end = strtotime($_POST['end'] . " 23:59:59");
                $where = array(time(),$end);
            }
        }
        $apply_list2 = db('xy_task_apply')
            ->where('status',2)
            ->where('member_id',$uid)
            ->whereTime('create_time', $where)
            ->order('create_time desc')
            ->paginate(10);
        $this->apply_list2 = $apply_list2;
        $this->task_num = count($apply_list2);
//        $this->apply_list = db('xy_task_apply')->where('status',0)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list1 = db('xy_task_apply')->where('status',1)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list2 = db('xy_task_apply')->where('status',2)->where('member_id',$uid)->order('create_time desc')->paginate(5);
//        $this->apply_list3 = db('xy_task_apply')->where('status',3)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list4 = db('xy_task_apply')->where('status',4)->where('member_id',$uid)->order('create_time desc')->paginate(10);
        $this->task = db('xy_task')->order('sort desc')->select();
//        $this->task_cate = db('xy_task_cate')->order('id desc')->select();
//        $this->xztime = time();
        $this->status = $status= input('get.status/d',0);
        $where =[];
        if ($status) {
            $status == -1 ? $status = 0:'';
            $where['xc.status'] = $status;
        }
        $this->apply_list11 = db('xy_convey')->where('status',0)->where('uid',$uid)->order('addtime desc')->paginate(10);
        $this->apply_list12 = db('xy_convey')->where('status',1)->where('uid',$uid)->order('addtime desc')->paginate(5);
        $this->apply_list13 = db('xy_convey')->where('status',4)->where('uid',$uid)->order('addtime desc')->paginate(10);
        $this->goods = db('xy_goods_list')->order('addtime desc')->select();

        $this->xztime = time();
        $this->apply_list = [];
        $this->apply_list1 = [];
        $this->apply_list3 = [];
        $this->apply_list4 = [];
//        $this->apply_list11 = [];
//        $this->apply_list12 = [];
//        $this->apply_list13 = [];
        return $this->fetch();
    }
    public function task_apply4()
    {
        $uid = session('user_id');
        $type = isset($_GET['type'])? $_GET['type']:0;
        $this->type = $type;
        $time = ['','today', 'yesterday', 'week', 'month'];
        $where = $time[$type];
        if(request()->isPost()) {
            if($_POST['start'] == "开始时间") {
                $_POST['start'] = '';
            }
            if($_POST['end'] == "结束时间") {
                $_POST['end'] = '';
            }
            if($_POST['start'] && $_POST['end']){
                $start = strtotime($_POST['start'] . " 00:00:00");
                $end = strtotime($_POST['end'] . " 23:59:59");
                if($start > $end) {
                    return json(['code'=>1,'info'=>lang('日期填写错误')]);
                }
                $where = array($start,$end);
            }
            else if($_POST['start'])
            {
                $start = strtotime($_POST['start'] . " 00:00:00");
                $where= array($start,time());
            }
            else if($_POST['end'])
            {
                $end = strtotime($_POST['end'] . " 23:59:59");
                $where = array(time(),$end);
            }
        }
        $apply_list3 = db('xy_task_apply')
            ->where('status',3)
            ->where('member_id',$uid)
            ->whereTime('create_time', $where)
            ->order('create_time desc')
            ->paginate(10);
        $this->apply_list3 = $apply_list3;
        $this->task_num = count($apply_list3);
//        $this->apply_list = db('xy_task_apply')->where('status',0)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list1 = db('xy_task_apply')->where('status',1)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list2 = db('xy_task_apply')->where('status',2)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list3 = db('xy_task_apply')->where('status',3)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list4 = db('xy_task_apply')->where('status',4)->where('member_id',$uid)->order('create_time desc')->paginate(10);
        $this->task = db('xy_task')->order('sort desc')->select();
//        $this->task_cate = db('xy_task_cate')->order('id desc')->select();
//        $this->xztime = time();
        $this->status = $status= input('get.status/d',0);
        $where =[];
        if ($status) {
            $status == -1 ? $status = 0:'';
            $where['xc.status'] = $status;
        }
//        $this->apply_list11 = db('xy_convey')->where('status',0)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->apply_list12 = db('xy_convey')->where('status',1)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->apply_list13 = db('xy_convey')->where('status',4)->where('uid',$uid)->order('addtime desc')->paginate(10);
//        $this->goods = db('xy_goods_list')->order('addtime desc')->select();
        $this->xztime = time();
        $this->apply_list = [];
        $this->apply_list1 = [];
        $this->apply_list2 = [];
        $this->apply_list4 = [];
        $this->apply_list11 = [];
        $this->apply_list12 = [];
        $this->apply_list13 = [];

        return $this->fetch();
    }
    public function task_apply5()
    {
        $uid = session('user_id');
        $type = isset($_GET['type'])? $_GET['type']:0;
        $this->type = $type;
        $time = ['','today', 'yesterday', 'week', 'month'];
        $where = $time[$type];
        if(request()->isPost()) {
            if($_POST['start'] == "开始时间") {
                $_POST['start'] = '';
            }
            if($_POST['end'] == "结束时间") {
                $_POST['end'] = '';
            }
            if($_POST['start'] && $_POST['end']){
                $start = strtotime($_POST['start'] . " 00:00:00");
                $end = strtotime($_POST['end'] . " 23:59:59");
                if($start > $end) {
                    return json(['code'=>1,'info'=>lang('日期填写错误')]);
                }
                $where = array($start,$end);
            }
            else if($_POST['start'])
            {
                $start = strtotime($_POST['start'] . " 00:00:00");
                $where= array($start,time());
            }
            else if($_POST['end'])
            {
                $end = strtotime($_POST['end'] . " 23:59:59");
                $where = array(time(),$end);
            }
        }
        $apply_list4 = db('xy_task_apply')
            ->where('status',4)
            ->where('member_id',$uid)
            ->whereTime('create_time', $where)
            ->order('create_time desc')
            ->paginate(10);
        $this->apply_list4 = $apply_list4;
        $this->task_num = count($apply_list4);
//        $this->apply_list = db('xy_task_apply')->where('status',0)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list1 = db('xy_task_apply')->where('status',1)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list2 = db('xy_task_apply')->where('status',2)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list3 = db('xy_task_apply')->where('status',3)->where('member_id',$uid)->order('create_time desc')->paginate(10);
//        $this->apply_list4 = db('xy_task_apply')->where('status',4)->where('member_id',$uid)->order('create_time desc')->paginate(5);
        $this->task = db('xy_task')->order('sort desc')->select();
//        $this->task_cate = db('xy_task_cate')->order('id desc')->select();
//        $this->xztime = time();
        $this->status = $status= input('get.status/d',0);
        $where =[];
        if ($status) {
            $status == -1 ? $status = 0:'';
            $where['xc.status'] = $status;
        }
        $this->apply_list11 = db('xy_convey')->where('status',0)->where('uid',$uid)->order('addtime desc')->paginate(10);
        $this->apply_list12 = db('xy_convey')->where('status',1)->where('uid',$uid)->order('addtime desc')->paginate(10);
        $this->apply_list13 = db('xy_convey')->where('status',4)->where('uid',$uid)->order('addtime desc')->paginate(5);
//        $this->goods = db('xy_goods_list')->order('addtime desc')->select();

        $this->xztime = time();
        $this->apply_list = [];
        $this->apply_list1 = [];
        $this->apply_list2 = [];
        $this->apply_list3 = [];
//        $this->apply_list11 = [];
//        $this->apply_list12 = [];
//        $this->apply_list13 = [];

        return $this->fetch();
    }

    
    //点赞任务到期时间
    public function task_dqtime()
    {
        $uid = session('user_id');
        $id = input('post.id');
        $applylist = db('xy_task_apply')->where('id',$id)->find();
        $times = $applylist['end_time']-time();
        $dqtime = $this->secToTime($times);
        return json(['code'=>$dqtime,'info'=>lang('更新成功')]);
      
    }
    //抢单任务到期时间
    public function task_qddqtime()
    {
        $id = input('post.id');
        $convey = db('xy_convey')->where('id',$id)->select();
        $times = $convey['daoqi_time']-time();
        $this->dqtime = $this->secToTime($times);
        return json(['code'=>$this->dqtime,'info'=>lang('更新成功')]);
    }
    //提交任务
    public function submit_task()
    {   
        $id = input('get.id/d',1);
        if(request()->isPost()){
            //return json(['code'=>1,'info'=>'图片格式错误']);
            $id = input('post.id/d',1);
            $thumb = input('post.pic/s','');
            $update_time = time();
            // echo $thumb;
            // exit;
            if (is_image_base64($thumb)){
                $thumb = '/' . $this->upload_base64('xy',$thumb);  //调用图片上传的方法
            }else{
                return json(['code'=>1,'info'=>lang('图片格式错误')]);
            }
            $res = Db::name('xy_task_apply')->where('id', $id)->update(['status' => 1,'thumb' => $thumb,'update_time' => $update_time]);
            if($res){
                return json(['code'=>0,'info'=>lang('提交成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('提交失败')]);
            }
        }else{
            $this->apply_list = db('xy_task_apply')->where('id',$id)->find();
            $this->task_title = db('xy_task')->where('id',$this->apply_list['task_id'])->value('title');
            return $this->fetch();
        }
    }
    
    //放弃任务
    public function up_task()
    {
        $id = input('post.id/d',1);
        $update_time = time();
        $res = Db::name('xy_task_apply')->where('id', $id)->update(['status' => 4,'update_time' => $update_time]);
        if($res){
            return json(['code'=>0,'info'=>lang('放弃成功')]);
        }else{
            return json(['code'=>0,'info'=>lang('放弃失败')]);
        }
    }

    public function submit_task1() {
        if(request()->isPost()){
            //$id = input('post.id/d',1);
            $arr=input('post.');
            $task_sn = $arr['task_sn'];
            $update_time = time();
            $res = Db::name('xy_task_apply')->where('task_sn', $task_sn)->update(['status' => 2,'update_time' => $update_time]);
            if($res){
                $task = Db::table('xy_task_apply')->where('task_sn', $task_sn)->find();
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
        }
    }

    public  function thaw() {
        $data = Db::name('xy_balance_log')
            ->where('num_status', 1)
            ->select();
        foreach ($data as $k => $v) {
            //$thaw_time = 7 * 24 * 3600;
            $thaw_time = 60;
            $thaw_day = $v['addtime'] + $thaw_time;
            $thaw_day = strtotime(date('Y-m-d', $thaw_day));//七日后0点
            if($thaw_day < time()) {
                //解冻状态
                $update_log = Db::name('xy_balance_log')
                    ->where('id', $v['id'])
                    ->update(['num_status' => 2]);
                //添加到账户余额
                $update_user = Db::name('xy_users')
                    ->where('id', $v['uid'])
                    ->setInc('balance', $v['num']);
            }
        }
        echo("success:" . count($data));
    }

    // 加密
    public function encrypt($request)
    {
        $key = "www.ceshi.com";
        $encrypt = openssl_encrypt($request, 'AES-128-ECB', $key, 0);
        return $encrypt;
    }
}
