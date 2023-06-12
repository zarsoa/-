<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\facade\Config;

class My extends Base
{
    protected $msg = ['__token__'  => '请不要重复提交！'];
    /**
     * 首页
     */
    public function index()
    {
        $uid = session('user_id');
        $this->info = db('xy_users')->find(session('user_id'));
        $level = $this->info['level'];
        !$level ? $level = 0 :'';
        $this->level_name = db('xy_level')->where('level',$level)->value('name');
        $data = db('xy_balance_log')->where('uid',session('user_id'))->select();
        $du = 0;
        $z2 = 0;
        $rw = 0;
        foreach ($data as $k => $v) {
            if($v['yuedu'] == 0) $du++;
            if($v['type'] == 0) {
                $z2 += $v['num'];
                if($v['num_status'] == 1) {
                    $rw += $v['num'];
                }
            }
        }
        $this->xiaoxi_du = $du;//未读消息
        $this->z = $z2;//历史收益
        $this->jrrw = $rw;//冻结资金
        $this->rate = 1.001;
        $this->dollar = $this->info['topat'] * $this->rate;

        //未读消息
//        $this->xiaoxi_du = db('xy_balance_log')->where('uid',session('user_id'))->where('yuedu',0)->count();
        //总收益
//        $z1 = db('xy_reward_log')->where('uid',session('user_id'))->sum('num');
//        $z2 = db('xy_balance_log')->where('uid',session('user_id'))->where('type',0)->sum('num');
//        $z3 = db('xy_balance_log')->where('uid',session('user_id'))->where('type',2)->whereTime('addtime','today')->sum('num');
//        $z4 = db('xy_balance_log')->where('uid',session('user_id'))->where('type',3)->whereTime('addtime','today')->sum('num');
        //$this->z = $z1 + $z2 + $z3 + $z4;
        //今日任务
        //$this->jrrw = db('xy_balance_log')->where('uid',session('user_id'))->where('type',0)->whereTime('addtime','today')->sum('num');
        //冻结资金
        //$this->jrrw = db('xy_balance_log')->where('uid',session('user_id'))->where('type',0)->where('num_status',1)->sum('num');
        //今日团队
        //$this->jrtd = db('xy_reward_log')->where('uid',session('user_id'))->whereTime('addtime','today')->sum('num');
        //广告
        $this->ads = db('xy_ads')->order('create_time desc')->where('status',1)->where('type',3)->select();
        //签到
//        $this->qd_num = Db::table('xy_users')->where('id',$uid)->value('qd_num');
//        $this->qd_num +=1;
//        $this->dqtime = time();
//        if((time() - $this->info['qd_time']) > 172800){
//            Db::table('xy_users')->where('id',$uid)->update(['qd_num' => 0]);
//        }
//        if((time() - $this->info['qd_time']) > 86400){
//            Db::table('xy_users')->where('id',$uid)->update(['qiandao' => 0]);
//        }
//        if($this->info['qd_num'] == 4){
//            $this->qiandao_num = Db::table('xy_qiandao')->where('id',1)->value('num5');
//        }else{
//            $this->qiandao_num = Db::table('xy_qiandao')->where('id',1)->value('num');
//        }
        //大转盘状态
        //$this->cj_status = Db::table('xy_cj_set')->where('id',1)->value('status');
        //$this->qiandao_status = Db::table('xy_qiandao')->where('id',1)->value('status');

        //以下参数防止报错
        $this->cj_status = 0;
        $this->jrtd = 0;
        $this->qiandao_num = 0;
        $this->qd_num = 0;
        $this->dqtime = time();
        $this->qiandao_status = 0;
        return $this->fetch();
    }

    
    public function reload()
    {
        $id = session('user_id');;
        $user = db('xy_users')->find($id);

        $n = ($id%20);

        $dir = './upload/qrcode/user/'.$n . '/' . $id . '.png';
        if(file_exists($dir)) {
            unlink($dir);
        }

        $res = model('admin/Users')->create_qrcode($user['invite_code'],$id);
        if(0 && $res['code']!==0){
            return $this->error(lang('失败'));
        }
        return $this->success(lang('成功'));
    }
    //资金明细
    public function mingxi1()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->paginate(10);//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi2()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->select();//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi3()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->paginate(10);//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi4()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->select();//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi5()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->select();//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi6()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->select();//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi7()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->select();//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi8()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->select();//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    public function mingxi9()
    {
        $uid = session('user_id');
        $this->list1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->order('id desc')->paginate(10);//任务收益
        $this->list2 = db('xy_recharge')->where('uid',$uid)->order('id desc')->select();//充值
        $this->list3 = db('xy_deposit')->where('uid',$uid)->order('id desc')->select();//提现
        $this->list4 = db('xy_balance_log')->where('uid',$uid)->where('type',6)->order('id desc')->paginate(10);//转账收入
        $this->list5 = db('xy_balance_log')->where('uid',$uid)->where('type',7)->order('id desc')->paginate(10);//转账支出
        $this->list6 = db('xy_balance_log')->where('uid',$uid)->where('type',2)->order('id desc')->paginate(10);//签到
        $this->list7 = db('xy_balance_log')->where('uid',$uid)->where('type',3)->order('id desc')->paginate(10);//红包
        $this->list8 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//充值
        $this->list9 = db('xy_reward_log')->where('uid',$uid)->where('type',1)->order('id desc')->paginate(10);//升级
        $task1 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $task2 = db('xy_reward_log')->where('uid',$uid)->where('type',3)->order('id desc')->select();//任务
        $this->list10 = $task1 + $task2;//任务
        return $this->fetch();
    }
    
    
    
    
    
    //在线抽奖
    public function zhuanpan()
    {
        $uid = session('user_id');
        $this->userinfo = db('xy_users')->where('id',$uid)->find();
        $cj_set = db('xy_cj_set')->value('num');
        if((time() - $this->userinfo['cj_time']) > 86400){
            db('xy_users')->where('id',$uid)->update(['cj_num'=>$cj_set]);
        }
        if(request()->isPost()) {
            if($this->userinfo['id_status'] == 0){
                return json(['code'=>3]);
            }
            if($this->userinfo['level'] < 1){
                return json(['code'=>2,'info'=>lang('普通会员不可抽奖，请升级VIP会员')]);
            }
            if($this->userinfo['cj_num'] >= 1){
                $num = $this->userinfo['cj_num'] - 1;
                db('xy_users')->where('id',$uid)->update(['cj_num'=>$num,'cj_time'=>time()]);
                return json(['code'=>0]);
            }else{
                return json(['code'=>1,'info'=>lang('抽奖次数不足')]);
            }
            
        }else{
            $this->jp1 = db('xy_cj_jp')->where('id',1)->find();
            $this->jp2 = db('xy_cj_jp')->where('id',2)->find();
            $this->jp3 = db('xy_cj_jp')->where('id',3)->find();
            $this->jp4 = db('xy_cj_jp')->where('id',4)->find();
            $this->jp5 = db('xy_cj_jp')->where('id',5)->find();
            $this->jp6 = db('xy_cj_jp')->where('id',6)->find();
            $this->jp7 = db('xy_cj_jp')->where('id',7)->find();
            $this->jp8 = db('xy_cj_jp')->where('id',8)->find();
            $this->jp9 = db('xy_cj_jp')->where('id',9)->find();
            $this->jp10 = db('xy_cj_jp')->where('id',10)->find();
            $this->content = db('xy_cj_set')->where('id',1)->value('content');
            $this->shengyu = db('xy_users')->where('id',$uid)->value('cj_num');
            $this->log_list = db('xy_cj_log')->select();
            $this->userinfo = db('xy_users')->select();
            return $this->fetch(); 
        }
    }
    public function cj_do()
    {
        $uid = session('user_id');
        $id = input('post.id/s', '');
        $jp = input('post.jp/s', '');
        //查询奖品
        $jpinfo = db('xy_cj_jp')->find($id);
        //添加log
        $uinfo = db('xy_users')->find($uid);
        $data = ['tel' => $uinfo['tel'], 'jp' => $jp, 'jid' => $id, 'create_time' => time()];
        if($jpinfo['type'] == 4){
            return json(['code'=>0,'info'=>lang('继续加油哦')]);
        }
        $res = Db::name('xy_cj_log')->data($data)->insert();
        //更新数据
        if($jpinfo['type'] == 1){
            $res1 = db('xy_users')->where('id',$uid)->update(['level'=>$jpinfo['lid']]);
        }
        if($jpinfo['type'] == 2){
            $res1 = db('xy_users')->where('id',$uid)->setInc('balance',$jpinfo['num']);
        }
        if($jpinfo['type'] == 3){
            if($jpinfo['cid'] == 1){
                $res1 = db('xy_users')->where('id',$uid)->setInc('task_dznum',$jpinfo['num']);
            }
            if($jpinfo['cid'] == 2){
                $res1 = db('xy_users')->where('id',$uid)->setInc('task_wxnum',$jpinfo['num']);
            }
            if($jpinfo['cid'] == 3){
                $res1 = db('xy_users')->where('id',$uid)->setInc('task_pddnum',$jpinfo['num']);
            }
            if($jpinfo['cid'] == 4){
                $res1 = db('xy_users')->where('id',$uid)->setInc('task_tbnum',$jpinfo['num']);
            }
            if($jpinfo['cid'] == 5){
                $res1 = db('xy_users')->where('id',$uid)->setInc('task_tmnum',$jpinfo['num']);
            }
            if($jpinfo['cid'] == 6){
                $res1 = db('xy_users')->where('id',$uid)->setInc('task_jdnum',$jpinfo['num']);
            }
            
        }
        if($res && $res1){
            return json(['code'=>0,'info'=>lang('领取成功')]);
        }else{
            return json(['code'=>1,'info'=>lang('领取失败')]);
        }
        
    }
    
    //团队
    public function team()
    {
        $uid = session('user_id');
        $uid1s = model('admin/Users')->child_user($uid,1,0);
        $uid2s = model('admin/Users')->child_user($uid,2,0);
        $uid3s = model('admin/Users')->child_user($uid,3,0);
        $uid4s = model('admin/Users')->child_user($uid,4,0);
        $uid5s = model('admin/Users')->child_user($uid,5,0);
        $this->level = db('xy_level')->select();
        $this->users = db('xy_users')->select();
        $this->deposit = db('xy_deposit')->where('uid',$uid)->sum('num');
        $this->zhitui = db('xy_users')->where('parent_id',$uid)->count();
        //$useryj1 = db('xy_balance_log')->where('uid',$uid)->where('type',0)->sum('num');
        $this->useryj = db('xy_reward_log')->where('uid',$uid)->where('type','neq',2)->sum('num');

        if(empty($uid1s)){
            $this->list1 = '';
            $lists1r = array();
            $lists1y = array();
            $lists1x = array();
            $lists1c = array();
            $lists1t = array();
        }else{
            foreach ($uid1s as $v) {
                $lists1 = db('xy_users')->where('id',$v)->select();
                $this->list1[] = $lists1;
                $lists1r[] = db('xy_users')->where('id',$v)->count();
                $lists1y[] = db('xy_users')->where('id',$v)->sum('balance');
                $lists1x[] = db('xy_users')->where('id',$v)->whereTime('addtime','today')->count();
                $lists1c[] = db('xy_recharge')->where('uid',$v)->where('pay_type',2)->sum('num');
                $lists1t[] = db('xy_deposit')->where('uid',$v)->sum('num');
            }
        }
        $lists1rs = array_sum($lists1r);
        $lists1ye = array_sum($lists1y);
        $lists1xz = array_sum($lists1x);
        $lists1cz = array_sum($lists1c);
        $lists1tx = array_sum($lists1t);

        if(empty($uid2s)){
            $this->list2 = '';
            $lists2r = array();
            $lists2y = array();
            $lists2x = array();
            $lists2c = array();
            $lists2t = array();
        }else{
            foreach ($uid2s as $v) {
                $lists2 = db('xy_users')->where('id',$v)->select();
                $this->list2[] = $lists2;
                $lists2r[] = db('xy_users')->where('id',$v)->count();
                $lists2y[] = db('xy_users')->where('id',$v)->sum('balance');
                $lists2x[] = db('xy_users')->where('id',$v)->whereTime('addtime','today')->count();
                $lists2c[] = db('xy_recharge')->where('uid',$v)->where('pay_type',2)->sum('num');
                $lists2t[] = db('xy_deposit')->where('uid',$v)->sum('num');
            }
        }
        $lists2rs = array_sum($lists2r);
        $lists2ye = array_sum($lists2y);
        $lists2xz = array_sum($lists2x);
        $lists2cz = array_sum($lists2c);
        $lists2tx = array_sum($lists2t);
        
        if(empty($uid3s)){
            $this->list3 = '';
            $lists3r = array();
            $lists3y = array();
            $lists3x = array();
            $lists3c = array();
            $lists3t = array();
        }else{
            foreach ($uid3s as $v) {
                $lists3 = db('xy_users')->where('id',$v)->select();
                $this->list3[] = $lists3;
                $lists3r[] = db('xy_users')->where('id',$v)->count();
                $lists3y[] = db('xy_users')->where('id',$v)->sum('balance');
                $lists3x[] = db('xy_users')->where('id',$v)->whereTime('addtime','today')->count();
                $lists3c[] = db('xy_recharge')->where('uid',$v)->where('pay_type',2)->sum('num');
                $lists3t[] = db('xy_deposit')->where('uid',$v)->sum('num');
            }
        }
        $lists3rs = array_sum($lists3r);
        $lists3ye = array_sum($lists3y);
        $lists3xz = array_sum($lists3x);
        $lists3cz = array_sum($lists3c);
        $lists3tx = array_sum($lists3t);

        if(empty($uid4s)){
            $this->list4 = '';
            $lists4r = array();
            $lists4y = array();
            $lists4x = array();
            $lists4c = array();
            $lists4t = array();
        }else{
            foreach ($uid4s as $v) {
                $lists4 = db('xy_users')->where('id',$v)->select();
                $this->list4[] = $lists4;
                $lists4r[] = db('xy_users')->where('id',$v)->count();
                $lists4y[] = db('xy_users')->where('id',$v)->sum('balance');
                $lists4x[] = db('xy_users')->where('id',$v)->whereTime('addtime','today')->count();
                $lists4c[] = db('xy_recharge')->where('uid',$v)->where('pay_type',2)->sum('num');
                $lists4t[] = db('xy_deposit')->where('uid',$v)->sum('num');
            }
        }
        $lists4rs = array_sum($lists4r);
        $lists4ye = array_sum($lists4y);
        $lists4xz = array_sum($lists4x);
        $lists4cz = array_sum($lists4c);
        $lists4tx = array_sum($lists4t);

        if(empty($uid5s)){
            $this->list5 = '';
            $lists5r = array();
            $lists5y = array();
            $lists5x = array();
            $lists5c = array();
            $lists5t = array();
        }else{
            foreach ($uid5s as $v) {
                $lists5 = db('xy_users')->where('id',$v)->select();
                $this->list5[] = $lists5;
                $lists5r[] = db('xy_users')->where('id',$v)->count();
                $lists5y[] = db('xy_users')->where('id',$v)->sum('balance');
                $lists5x[] = db('xy_users')->where('id',$v)->whereTime('addtime','today')->count();
                $lists5c[] = db('xy_recharge')->where('uid',$v)->where('pay_type',2)->sum('num');
                $lists5t[] = db('xy_deposit')->where('uid',$v)->sum('num');
            }
        }
        $lists5rs = array_sum($lists5r);
        $lists5ye = array_sum($lists5y);
        $lists5xz = array_sum($lists5x);
        $lists5cz = array_sum($lists5c);
        $lists5tx = array_sum($lists5t);

        $zrs = $lists1rs + $lists2rs + $lists3rs + $lists4rs + $lists5rs;
        $zye = $lists1ye + $lists2ye + $lists3ye + $lists4ye + $lists5ye;
        $zxz = $lists1xz + $lists2xz + $lists3xz + $lists4xz + $lists5xz;
        $zcz = $lists1cz + $lists2cz + $lists3cz + $lists4cz + $lists5cz;
        $ztx = $lists1tx + $lists2tx + $lists3tx + $lists4tx + $lists5tx;
        $this->zrs = number_format($zrs, 0, '.', '');
        $this->zye = number_format($zye, 0, '.', '');
        $this->zxz = number_format($zxz, 0, '.', '');
        $this->zcz = number_format($zcz, 0, '.', '');
        $this->ztx = number_format($ztx, 0, '.', '');
        return $this->fetch();
    }

    //头像设置
    public function headimg()
    {
        $uid = session('user_id');
        $arr = [];
        for($i = 0;$i<61;$i++) {
            $arr[$i] = $i;
        }
        $this->arr = $arr;
        if(request()->isPost()) {
            $username = input('post.pic/s', '');
            $res = db('xy_users')->where('id',session('user_id'))->update(['headpic'=>$username]);
            if($res!==false){
                return json(['code'=>0,'info'=>lang('操作成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('操作失败')]);
            }
        }
        $this->info = db('xy_users')->find($uid);
        return $this->fetch();
    }
    
    //银行卡设置
    public function shiming()
    {
        $id = input('post.id/d',0);
        $uid = session('user_id');
        $uinfo = db('xy_users')->find($uid);
        $info = db('xy_bankinfo')->where('uid',$uid)->find();
        if(request()->isPost()){
            $picz = input('picz');
            $picf = input('picf');
            if (is_image_base64($picz) && is_image_base64($picf)){
                $picz = '/' . $this->upload_base64('xy',$picz);  
                $picf = '/' . $this->upload_base64('xy',$picf);  
            }else{
                return json(['code'=>1,'info'=>lang('图片格式错误')]);
            }

            if($uinfo['id_status'] == 1){
               return json(['code'=>1,'info'=>lang('您已认证过了，如需修改请联系客服')]);
            }
            //同一姓名和卡号只绑定一次
            $id_cards = db('xy_bankinfo')->where('id_card',$_POST['id_card'])->find();
            if ($id_cards && $id_cards['uid'] != $uid) {
                return json(['code'=>1,'info'=>lang('该身份证号已绑定其他账号!')]);
            }

            $_POST['uid'] = $uid; 
            $data = [
                'picf' => $picf,
                'picz' => $picz,
                'uid' => $uid,
                'username'=>$_POST['username'],
                'id_card'=>$_POST['id_card'],
                ];
            //$res1 = db('xy_users')->where('id',$uid)->update(['id_status'=>1]);
            $res = db('xy_bankinfo')->insert($data);
            if($res){
                return json(['code'=>0,'info'=>lang('提交成功,请耐心等待审核!')]);
            }else{
                return json(['code'=>1,'info'=>lang('操作失败')]);
            }
        }
        $this->info = $info;
        $this->userinfo = db('xy_users')->find($uid);
        return $this->fetch();        
    }
    
    
    //银行卡设置
    public function bind_bank()
    {
        $id = input('post.id/d',0);
        $uid = session('user_id');
        $info = db('xy_bankinfo')->where('uid',$uid)->find();
        $uinfo = db('xy_users')->find($uid);
        if(request()->isPost()){
            $addr  = input('post.address/s','');
            $net  = input('post.net/s','');

            if(empty($addr)) {
                return json(['code'=>1,'info'=>lang('地址不能为空')]);
            }
            if(empty($net)) {
                return json(['code'=>1,'info'=>lang('主网络不能为空')]);
            }

            if($addr == $info['address'] && $net == $info['net']) {
                return json(['code'=>1,'info'=>lang('信息未更改')]);
            }

            $data['uid'] = $uid;
            $data['address'] = $addr;
            $data['net'] = $net;
            $data['status'] = 1;

            if($info) {
                $update = Db::table('xy_bankinfo')->where('uid',$uid)->update($data);
            } else {
                $update = Db::table('xy_bankinfo')->insert($data);
            }
            if($update) {
                return json(['code'=>0,'info'=>lang('设置成功')]);
            }
            // $username = $info['username'];
            // $bankname = input('post.bankname/s','');
            // $cardnum = input('post.cardnum/s','');
            // $site  = input('post.site/s','');
            // $tel  = input('post.tel/s','');
            // $id_card  = $info['id_card'];
            // $bkname  = input('post.bkname/s','');

            // $real = db('xy_bankinfo')->where('uid',$uid)->find();
            // if($real['status'] == 1){
            //     return json(['code'=>1,'info'=>lang('您已认证过了，如需修改请联系客服')]);
            // }
            // //同一姓名和卡号只绑定一次
            // $cardnums = db('xy_bankinfo')->where('cardnum',$cardnum)->find();
            // if ($cardnums && $cardnums['uid'] != $uid) {
            //     return json(['code'=>1,'info'=>lang('该银行卡已绑定其他账号!')]);
            // }
            // $id_cards = db('xy_bankinfo')->where('id_card',$id_card)->find();
            // if ($id_cards && $id_cards['uid'] != $uid) {
            //     return json(['code'=>1,'info'=>lang('该身份证号已绑定其他账号!')]);
            // }
            // if ($tels && $tels['uid'] != $uid) {
            //     return json(['code'=>1,'info'=>lang('该手机号已绑定其他账号!')]);
            // }
            // $tels = db('xy_bankinfo')->where('tel',$tel)->find();
            // if ($uinfo['tel'] != $tel) {
            //     return json(['code'=>1,'info'=>lang('认证手机号与注册手机号不匹配!')]);
            // }
            // if ($username != $bkname) {
            //     return json(['code'=>1,'info'=>lang('银行卡收款姓名与真实姓名不匹配!')]);
            // }
            
            // error_reporting(E_ALL || ~E_NOTICE);
            // $host = "https://bcard3and4.market.alicloudapi.com";
            // $path = "/bank3Check";
            // $method = "GET";
            // $appcode = "96b40467fb6f49a08568736dace43453";//开通服务后 买家中心-查看AppCode
            // $headers = array();
            // array_push($headers, "Authorization:APPCODE " . $appcode);
            // $querys = "idCard=".$id_card."&accountNo=".$cardnum."&name=".urlencode($username);
            
            // $bodys = "";
            // $url = $host . $path . "?" . $querys;
            
            // $curl = curl_init();
            // curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            // curl_setopt($curl, CURLOPT_URL, $url);
            // curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            // curl_setopt($curl, CURLOPT_FAILONERROR, false);
            // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($curl, CURLOPT_HEADER, true);
            // if (1 == strpos("$" . $host, "https://")) {
            //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            //     curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            // }
            // $out_put = curl_exec($curl);
            
            // $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            // list($header, $body) = explode("\r\n\r\n", $out_put, 2);
            // $array=explode('"', $body);
            // $cha_api = $array[3];
            
            // if ($httpCode == 200) {
            //     if ($cha_api == 204){
            //         return json(['code'=>1,'info'=>lang('姓名错误')]);
            //     }
            //     if ($cha_api == 205){
            //         return json(['code'=>1,'info'=>lang('身份证号错误')]);
            //     }
            //     if ($cha_api == 206){
            //         return json(['code'=>1,'info'=>lang('银行卡号输入错误')]);
            //     }
            //     if ($cha_api == 203){
            //         return json(['code'=>1,'info'=>lang('同一身份证号重复调用次数达到上限，请12小时后在请求')]);
            //     }
            //     if ($cha_api == 02){
            //         return json(['code'=>1,'info'=>lang('您的身份证信息不匹配，请重新填写!')]);
            //     }
            //     if ($cha_api == 202){
            //         return json(['code'=>1,'info'=>lang('验证失败')]);
            //     }
            //     if ($cha_api == 01){
            //         $data =array(
            //             'bankname' =>$bankname,
            //             'cardnum' =>$cardnum,
            //             'site' =>$site,
            //             'tel' =>$tel,
            //             'status' =>1
            //         );
            //         $res = db('xy_bankinfo')->where('uid',$uid)->update($data);
        
            //         if($res){
            //             return json(['code'=>0,'info'=>lang('验证成功')]);
            //         }else{
            //             return json(['code'=>1,'info'=>lang('操作失败')]);
            //         }
            //     }
            // }  else {
            //     if ($httpCode == 400 && strpos($header, "Invalid Param Location") !== false) {
            //         print("参数错误");
            //     } elseif ($httpCode == 400 && strpos($header, "Invalid AppCode") !== false) {
            //         print("AppCode错误");
            //     } elseif ($httpCode == 400 && strpos($header, "Invalid Url") !== false) {
            //         print("请求的 Method、Path 或者环境错误");
            //     } elseif ($httpCode == 403 && strpos($header, "Unauthorized") !== false) {
            //         print("服务未被授权（或URL和Path不正确）");
            //     } elseif ($httpCode == 403 && strpos($header, "Quota Exhausted") !== false) {
            //         print("套餐包次数用完");
            //     } elseif ($httpCode == 500) {
            //         print("API网关错误");
            //     } elseif ($httpCode == 0) {
            //         print("URL错误");
            //     } else {
            //         print("参数名错误 或 其他错误");
            //         print($httpCode);
            //         $headers = explode("\r\n", $header);
            //         $headList = array();
            //         foreach ($headers as $head) {
            //             $value = explode(':', $head);
            //             $headList[$value[0]] = $value[1];
            //         }
            //         print($headList['x-ca-error-message']);
            //     }
            // }
        }
        $this->info = $info;
        $this->userinfo = $uinfo;
        return $this->fetch();        
    }
    /**
     * 账号修改
     */
    public function edit_username(){
        $uid = session('user_id');
        if(request()->isPost()) {
            $username = input('post.username/s', '');
            $res = db('xy_users')->where('id',session('user_id'))->update(['username'=>$username]);
            if($res!==false){
                return json(['code'=>0,'info'=>lang('操作成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('操作失败')]);
            }
        }
        $this->info = db('xy_users')->find($uid);
        return $this->fetch();
    }
    
    /**
     * 升级会员
     */
    public function vip()
    {
        $this->dqtime = time();
        $this->level = db('xy_level')->where('level', '>=', 0)->order('id asc')->select();
        $this->user = db('xy_users')->where('id',session('user_id'))->find();
        $this->pay = [];
        $date1 = $this->user['viptime'];
        $date2 = time();
        $this->leftdays = ceil(abs($date1 - $date2)/86400);
        return $this->fetch();
    }
    
    /**
     * 会员转账
     */
    public function pay()
    {
        $uid = session('user_id');
        if(request()->isPost()){
            $money = input('post.money/s','');
            $tel = input('post.tel/s','');
            $user = db('xy_users')->where('id',session('user_id'))->find();
            $tel1 = db('xy_users')->where('tel',$tel)->find();
            $bank = db('xy_bankinfo')->where('uid',$uid)->find();
            if($user['level'] < 1){
                return json(['code'=>2,'info'=>lang('普通会员不可转账，请升级VIP会员')]);
            }
            if($user['id_status'] == 0){
                return json(['code'=>4]);
            }
            if($bank['status'] == 0){
                return json(['code'=>5,'info'=>lang('您还未绑定银行卡，请先绑定银行卡')]);
            }
            if(empty($tel1)){
                return json(['code'=>3,'info'=>lang('转账手机号错误')]);
            }
            $res = db('xy_users')->where('tel',$tel)->setInc('balance',$money);
            $resc = Db::name('xy_balance_log')
                        ->insert([
                            'uid'=>$uid,
                            'num'=>$money,
                            'type'=>7,
                            'desc'=>lang('转账支出').$money,
                            'yuedu'=>0,
                            'status'=>2,
                            'addtime'=>time(),
                        ]);
            $res1 = db('xy_users')->where('id',$uid)->setDec('balance',$money);
            $resr = Db::name('xy_balance_log')
                        ->insert([
                            'uid'=>$tel1['id'],
                            'num'=>$money,
                            'type'=>6,
                            'desc'=>lang('转账入账').$money,
                            'yuedu'=>0,
                            'addtime'=>time(),
                        ]);
            if($res && $resc && $resr && $res1){
                return json(['code'=>0,'info'=>lang('转账成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('转账失败')]);
            }
        }else{
            return $this->fetch();
        }
    }
    
    /**
     * 余额充值
     */
    public function yue()
    {
        $this->pay = db('xy_pay')->where('status',1)->order('id asc')->select();
        return $this->fetch();
    }
    
    /**
     * 用户付款
     */
    public function recharge()
    {
        $uid = session('user_id');
        
        $this->pay_name = input('get.name2/s','');
        $this->level = input('get.level/s','');
        $this->pay_type = input('get.pay_type/s','');
        $this->payinfo = db('xy_pay')->where('id',3)->find();
        $this->zfbinfo = db('xy_pay')->where('id',1)->find();
        $this->wxinfo = db('xy_pay')->where('id',2)->find();
        $this->leveinfo = db('xy_level')->order('id asc')->select();
        $userinfo = db('xy_users')->where('id',$uid)->find();
        if($this->pay_type == 1){
            if($userinfo['level'] > 0 && $userinfo['viptime'] > time()){
                //现在等级,减免金额
                $xzlevel = Db::table('xy_level')->where('level',$userinfo['level'])->find();
                $danjia = $xzlevel['num']/$xzlevel['yx_time'];
                $shengyuday = $userinfo['viptime'] - time();
                $shengyuday = $shengyuday/86400;
                $synum = $shengyuday*$danjia;
                $xznum = input('get.num/d',0);
                $this->num = $xznum - $synum;
            }else{
                $this->num = input('get.num/d',0);
            } 
        }else{
            $this->num = input('get.num/d',0);
        }
        return $this->fetch();
    }
    
    /**
     * 用户账号充值
     */
    public function user_recharge()
    {
        $uid = session('user_id');
        $userinfo = db('xy_users')->where('id',$uid)->find();
        $real_name = $userinfo['username'];
        $tel = $userinfo['tel'];
        $num = input('post.num/s','');
        if($num > $userinfo['topat']) {
            return json(['code'=>1,'info'=>lang('TOPAT不足')]);
        }
        $pay_name = input('post.pay_name/s','');
        $level = input('post.level/s','');
        if($level == 0 && time() < $userinfo['viptime']) {
            return json(['code'=>1,'info'=>lang('已激活')]);
        }
        $pay_type = input('post.pay_type/s','');
        $res = check_time(config('chongzhi_time_1'),config('chongzhi_time_2'));
        $str = config('chongzhi_time_1').":00  - ".config('chongzhi_time_2').":00";
        if($res) return json(['code'=>1,'info'=>lang('充值时间为').$str]);
        if($pay_name == 'zfb'){
            $type = 1;
        }
        if($pay_name == 'yue'){
            $type = 2;
        }
        if($pay_name == 'card'){
            $type = 3;
        }
        $pic = input('post.pic/s','');
        if($type == 2){
            if($userinfo['balance'] >= $num){
                if($pay_type == 1){
                    $data =array(
                        'uid'       => $uid,
                        'real_name' => $real_name,
                        'tel'       => $tel,
                        'type'       => $type,
                        'num'       => $num,
                        'pic'       => $pic,
                        'addtime'   => time(),
                        'status'       => 2,
                        'pay_name'       => $pay_name,
                        'level'       => $level,
                        'pay_type'       => $pay_type,
                    );
                    $res3 = db('xy_recharge')->insertGetId($data);
                    $tsak_num    = db('xy_level')->where('level',$level)->find();
                    $level_name = $tsak_num['name'];
                    $tsak_num['dz_num'] = $level > 0? $tsak_num['dz_num']:$num*100/5;
                    $desc = $level > 0? lang('升级为').' '.$level_name:lang('激活').' '.$level_name;
                    $xiaoxi = $level > 0? lang('升级').' '.$level_name.' '.lang('成功，请刷新查看') : lang('激活').' '.$level_name.' '.lang('成功，请刷新查看');
                    $yx_time = $tsak_num['yx_time']*86400 + time();
                    $before = $userinfo['topat']? $userinfo['topat']:0;
                    $after = $before - $num;
                    $res1 = Db::name('xy_users')
                        ->where('id',$uid)
                        ->update([
                            'level'=>$level,
                            'task_jdnum'=>$tsak_num['jd_num'],
                            'task_tbnum'=>$tsak_num['tb_num'],
                            'task_tmnum'=>$tsak_num['tm_num'],
                            'task_pddnum'=>$tsak_num['pdd_num'],
                            'task_wxnum'=>$tsak_num['wx_num'],
                            'task_dznum'=>$tsak_num['dz_num'],
                            'viptime'=>$yx_time,
                            'xiaoxi'=>$xiaoxi,
                            'topat'=>$after
                            ]);
                    $res2 = Db::name('xy_balance_log')
                        ->insert([
                            'uid'=>$uid,
                            'oid'=>$res3,
                            'num'=>$num,
                            'type'=>14,
                            'status'=>2,
                            'desc'=>lang('升级为').' '.$level_name,
                            'yuedu'=>0,
                            'addtime'=>time(),
                            'before'=> $before,
                            'after'=> $after
                        ]);
                }
                if($res1 && $res2 && $res3){
                    //Db::name('xy_users')->where('id',$uid)->setDec('topat',$num);
                        
                    /************* 发放推广奖励 *********/
                    //将账号状态改为已发放推广奖励
                    $userList = model('admin/Users')->parent_user($uid,5);
                    if($userList){
                        foreach($userList as $v){
                            $p_level = Db::name('xy_users')->where('id',$v['id'])->value('level');
                            $rebate_price = Db::table('xy_level')->where('level',$p_level)->find();
                            if($v['status']===1 && ($num * $rebate_price['rebate_price_'.$v['lv']] != 0)){
                                $balance = $num * $rebate_price['rebate_price_'.$v['lv']];
                                Db::name('xy_users')->where('id',$v['id'])->setInc('balance',$balance);
//                                //处理uid
//                                $num=str_pad($v['id'],5,"0",STR_PAD_LEFT);
//                                //生成流水号
//                                $recharge_sn = 'rc'."|".date('YmdHis')."|".$num."|".rand(1000,9999);
                                Db::name('xy_reward_log')
                                ->insert([
                                    'uid'=>$v['id'],
                                    'oid'=>$res3,
                                    'sid'=>$uid,
                                    'num'=>$num * $rebate_price['rebate_price_'.$v['lv']],
                                    'lv'=>$v['lv'],
                                    'type'=>1,
                                    'addtime'=>time(),
                                ]);
                            }
                        }
                    }
                    /************* 发放推广奖励 *********/                      
                    return json(['code'=>5,'info'=>lang('操作成功')]);
                }else{
                    return json(['code'=>6,'info'=>lang('操作失败')]);
                }
                
            }else{
                return json(['code'=>1,'info'=>lang('账户余额不足，请稍充值')]);
            }
        }else{
            if (is_image_base64($pic)){
                $pic = '/' . $this->upload_base64('xy',$pic);  //调用图片上传的方法
            }else{
                return json(['code'=>1,'info'=>lang('图片格式错误')]);
            }
            $data =array(
                'uid'       => $uid,
                'real_name' => $real_name,
                'tel'       => $tel,
                'type'       => $type,
                'num'       => $num,
                'pic'       => $pic,
                'addtime'   => time(),
                'status'       => 1,
                'pay_name'       => $pay_name,
                'level'       => $level,
                'pay_type'       => $pay_type,
            );
            $res = db('xy_recharge')->insert($data);
            if($res){
                return json(['code'=>0,'info'=>lang('提交成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('提交失败，请稍后再试')]);
            }
        }
    }

    // 充值记录
    public function recharge_log()
    {
        $id = session('user_id');
        $where=[];
        $this->_query('xy_recharge')
            ->where('uid',$id)->where($where)->order('id desc')->page();
    }
    
    //邀请界面
    public function invite()
    {
        $uid = session('user_id');
        $this->assign('pic','/upload/qrcode/user/'.($uid%20).'/'.$uid.'.png');

        $user = db('xy_users')->find($uid);

        $url = SITE_URL . url('@index/user/register/invite_code/'.$user['invite_code']);
        $this->assign('url',$url);
        $this->assign('invite_code',$user['invite_code']);
        return $this->fetch();
    }

    //我的资料
    public function do_my_info()
    {
        if(request()->isPost()){
            $headpic    = input('post.headpic/s','');
            $wx_ewm    = input('post.wx_ewm/s','');
            $zfb_ewm    = input('post.zfb_ewm/s','');
            $nickname   = input('post.nickname/s','');
            $sign       = input('post.sign/s','');
            $data = [
                'nickname'  => $nickname,
                'signiture' => $sign
            ];

            if($headpic){
                if (is_image_base64($headpic))
                    $headpic = '/' . $this->upload_base64('xy',$headpic);  //调用图片上传的方法
                else
                    return json(['code'=>1,'info'=>lang('图片格式错误')]);
                $data['headpic'] = $headpic;
            }

            if($wx_ewm){
                if (is_image_base64($wx_ewm))
                    $wx_ewm = '/' . $this->upload_base64('xy',$wx_ewm);  //调用图片上传的方法
                else
                    return json(['code'=>1,'info'=>lang('图片格式错误')]);
                $data['wx_ewm'] = $wx_ewm;
            }

            if($zfb_ewm){
                if (is_image_base64($zfb_ewm))
                    $zfb_ewm = '/' . $this->upload_base64('xy',$zfb_ewm);  //调用图片上传的方法
                else
                    return json(['code'=>1,'info'=>lang('图片格式错误')]);
                $data['zfb_ewm'] = $zfb_ewm;
            }

            $res = db('xy_users')->where('id',session('user_id'))->update($data);
            if($res!==false){
                if($headpic) session('avatar',$headpic);
                return json(['code'=>0,'info'=>lang('操作成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('操作失败')]);
            }
        }elseif(request()->isGet()){
            $info = db('xy_users')->field('username,headpic,nickname,signiture sign,wx_ewm,zfb_ewm')->find(session('user_id'));
            return json(['code'=>0,'info'=>lang('请求成功'),'data'=>$info]);
        }
    }
    // 最新消息
    public function msg()
    {
        $this->lists = db('xy_balance_log')->where('uid',session('user_id'))->select();
        db('xy_balance_log')->where('uid',session('user_id'))->update(['yuedu'=>1]);
        return $this->fetch();
    }
    
    // 最新消息
    public function article()
    {
        $this->lists = db('xy_index_msg')->where('type', 0)->select();
        return $this->fetch();
    }
    
    // 最新消息——内容
    public function article_detail()
    {
        $id = input('get.id/d','');
        $title = input('get.title/s','');
        if($title == '') {
            $data['id'] = $id;
        }
        if($id == '') {
            $data['title'] = $title;
        }
        $this->msg = db('xy_index_msg')->where($data)->find();
        return $this->fetch();
    }

    // 提现
    public function tixian()
    {
        //提现手续费
        $userinfo = db::table('xy_users')
            ->alias('a')
            ->leftjoin('xy_level b', 'a.level = b.level')
            ->leftjoin('xy_bankinfo i', 'a.id = i.uid')
            ->where('a.id',session('user_id'))
            ->field('a.*, b.*, i.address, i.net')
            ->find();
        $this->userinfo = $userinfo;
        $this->tixian = $userinfo;

        $user_id = session('user_id');
        //$this->address = Config::get('address.' . $user_id );
        $this->address = $userinfo['address'];

        $this->fee = 20;

        if(request()->isPost()){
            $num = input('post.num/d','');
            $pwd2 = input('post.pwd2/s','');
            $net = input('post.net/s','');
            $addr = input('post.address/s','');
            $fee = $this->fee;
            if(empty($addr)) {
                return json(['code'=>1,'info'=>lang('请输入地址')]);
            }
            if(empty($net)) {
                return json(['code'=>1,'info'=>lang('请输入主网络')]);
            }
            if(empty($num)) {
                return json(['code'=>1,'info'=>lang('请输入提现金额')]);
            }
            if(empty($pwd2)) {
                return json(['code'=>1,'info'=>lang('请输入提现密码')]);
            }
            if($num > $userinfo['balance']){
                return json(['code'=>1,'info'=>lang('余额不足')]);
            }
            if($num < $this->tixian['tixian_min']){
                return json(['code'=>1,'info'=>lang('最低提现金额').$this->tixian['tixian_min'].config('money_type')]);
            }
            if($num > $this->tixian['tixian_max']){
                return json(['code'=>1,'info'=>lang('最高提现金额').$this->tixian['tixian_max'].config('money_type')]);
            }
            if($fee > $userinfo['topat']){
                return json(['code'=>1,'info'=>lang('TOPAT不足')]);
            }
            if($userinfo['pwd2']!=sha1($pwd2.$userinfo['salt2'].config('pwd_str'))){
                return json(['code'=>1,'info'=>lang('提现密码错误')]);
            }
            $money = $num;
            $b_before = $userinfo['balance']? $userinfo['balance']:0;
            $b_after = $b_before - $num;

            $t_before = $userinfo['topat']? $userinfo['topat']:0;
            $t_after = $t_before - $fee;

            $res = db('xy_users')->where('id',session('user_id'))->update(['freeze_balance'=>$num, 'balance'=>$b_after, 'topat'=>$t_after]);
            $time = time();
            if($res!==false){
                $data =array(
                    'uid' =>session('user_id'),
                    'num' =>$money,
                    'addtime' =>$time,
                    'status' =>1,
                    'address' =>$addr,
                    'net' =>$net,
                    'shouxu' =>$fee,
                    'real_num' =>$num,
                );
                db::table('xy_deposit')->insert($data);
                $oid = Db::table('xy_deposit')->where('uid',session('user_id'))->getLastInsID();
                $data1 =array(
                    'uid' =>session('user_id'),
                    'type' =>7,
                    'oid' => $oid,
                    'num' =>$money,
                    'status' =>2,
                    'desc' =>lang('提现支出').' '.$money.config('money_type'),
                    'yuedu' =>0,
                    'addtime' => $time,
                    'before' => $b_before,
                    'after' => $b_after,
                );
                db::table('xy_balance_log')->insert($data1);
                $data2 =array(
                    'uid' =>session('user_id'),
                    'type' =>15,
                    'oid' => $oid,
                    'num' =>$fee,
                    'status' =>2,
                    'desc' =>lang('提现手续费').' '.$fee.config('topat.name'),
                    'yuedu' =>0,
                    'addtime' => $time,
                    'before' => $t_before,
                    'after' => $t_after,
                );
                db::table('xy_balance_log')->insert($data2);

                //是否为绑定uid,是则复制一份
                $bind = Db::name('xy_bind')->where('binduid',session('user_id'))->select();
                if($bind) {
                    $databind =array(
                        'num' =>$money,
                        'addtime' =>$time,
                        'status' =>1,
                        'address' =>$addr,
                        'net' =>$net,
                        'shouxu' =>$fee,
                        'real_num' =>$num,
                    );
                    $databind1 =array(
                        'type' =>7,
                        'oid' => $oid,
                        'num' =>$fee,
                        'status' =>2,
                        'desc' =>lang('提现支出').' '.$money.config('money_type'),
                        'yuedu' =>0,
                        'addtime' =>$time,
                    );
                    $databind2 =array(
                        'type' =>15,
                        'oid' => $oid,
                        'num' =>$fee,
                        'status' =>2,
                        'desc' =>lang('提现手续费').' '.$money.config('topat.name'),
                        'yuedu' =>0,
                        'addtime' =>$time,
                    );
                    foreach($bind as $k => $b) {
                        $databind['uid'] = $b['uid'];
                        $databind1['uid'] = $b['uid'];
                        $databind2['uid'] = $b['uid'];
                        Db::table('xy_deposit')->insert($databind);
                        db::table('xy_balance_log')->insert($databind1);
                        db::table('xy_balance_log')->insert($databind2);
                        db('xy_users')->where('id',$b['uid'])->update(['freeze_balance'=>$money]);
                        db('xy_users')->where('id',$b['uid'])->setDec('balance',$money);
                    }
                }
                return json(['code'=>0,'info'=>lang('提交成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('提交失败')]);
            }
        }else{
            $this->bankinfo = [];
            return $this->fetch();
        }
    }

   // 提现记录
    public function tixian_log()
    {
        $id = session('user_id');
        $where=[];
        $this->list = db('xy_deposit')->where('uid',$id)->where($where)->order('addtime desc')->paginate(10);
        return $this->fetch();
    }
    
    // 修改登录密码
    public function edit_password()
    {
        if(request()->isPost()){
            if(!request()->isPost()) return json(['code'=>1,'info'=>lang('错误请求')]);
            $o_pwd = input('old_pwd/s','');
            $pwd = input('new_pwd/s','');
            $type = input('type/d',1);
            $uinfo = db('xy_users')->field('pwd,salt,tel')->find(session('user_id'));
            if($uinfo['pwd']!=sha1($o_pwd.$uinfo['salt'].config('pwd_str'))){
                return json(['code'=>1,'info'=>lang('密码错误!')]);
            }else{
                $res = model('admin/Users')->reset_pwd($uinfo['tel'],$pwd,$type);
                return json($res);
            }
        }else{
            return $this->fetch();
        }
    }
    
    // 修改提现密码
    public function edit_tx_password()
    {
        if(request()->isPost()){
            if(!request()->isPost()) return json(['code'=>1,'info'=>lang('错误请求')]);
            $o_pwd = input('old_pwd/s','');
            $pwd = input('new_pwd/s','');
            $type = input('type/d',2);
            $changetype = input('changetype/d',1);
            $vercode = input('vercode/d','');
            $tel = input('tel/s','');
            $uinfo = db('xy_users')->field('pwd2,salt2,tel')->find(session('user_id'));
            if($changetype ==1)
            {
                if($uinfo['pwd2']!=sha1($o_pwd.$uinfo['salt2'].config('pwd_str')))
                {
                    return json(['code'=>1,'info'=>lang('密码错误!')]);
                }else{
                    $res = model('admin/Users')->reset_pwd($uinfo['tel'],$pwd,$type);
                    return json($res);
                }
            }
            else
            {
                $verify_msg = Db::table('xy_verify_msg')->field('msg,addtime')->where(['tel'=>$tel,'type'=>4])->find();
                if(!$verify_msg)
                {
                    return json(['code'=>1,'info'=>lang('验证码不存在')]);
                }
                if($verify_msg['msg']!=$vercode)
                {
                    return json(['code'=>1,'info'=>lang('验证码错误') . $verify_msg['msg']]);
                }else{
                    $res = model('admin/Users')->reset_pwd($uinfo['tel'],$pwd,$type);
                    return json($res);
                }
            }
            
        }else{
            $uid = session('user_id');
            $this->info = db('xy_users')->where('id',$uid)->find();
            return $this->fetch();
        }
    }
    
    // 修改账号
    public function edit_tel()
    {
        if(request()->isPost()){
            $tel = input('tel/s','');
            if(db('xy_users')->where('id',session('user_id'))->update(['tel'=>$tel])){
                if(db('xy_users')->where('tel',$tel)->find()){
                    return json(['code'=>0,'info'=>lang('手机号已存在')]);
                }else{
                    return json(['code'=>0,'info'=>lang('修改成功!')]);
                }
            }else{
                return json(['code'=>1,'info'=>lang('修改失败!')]);
            }
        }else{
            return $this->fetch();
        }
    }

    // 冻结余额
    public function dongjie()
    {
        $uid = session('user_id');
        $this->info = db('xy_users')->where('status', 1)->find(session('user_id'));
        // dump($this->info);exit;
        return $this->fetch();
    }

    public function about_us()
    {
        return $this->fetch();
    }

    public function topat()
    {
        $uid = session('user_id');
        $userinfo = db::table('xy_users')
            ->alias('a')
            ->leftjoin('xy_level b', 'a.level = b.level')
            ->leftjoin('xy_bankinfo i', 'a.id = i.uid')
            ->where('a.id',$uid)
            ->field('a.*, b.*, i.address, i.net')
            ->find();
        $this->userinfo = $userinfo;
        $this->tixian = $userinfo;
        if(request()->isPost()){
            $num = input('post.num/d','');
            $pwd2 = input('post.pwd2/s','');
            $net = input('post.net/s','');
            $addr = input('post.address/s','');
            $fee = 0;
            if(empty($addr)) {
                return json(['code'=>1,'info'=>lang('请输入邮箱或地址')]);
            }
            if(empty($net)) {
                return json(['code'=>1,'info'=>lang('请输入主网络')]);
            }
            if(empty($num)) {
                return json(['code'=>1,'info'=>lang('请输入数额')]);
            }
            if(empty($pwd2)) {
                return json(['code'=>1,'info'=>lang('请输入提现密码')]);
            }
            if($num + $fee> $userinfo['topat']){
                return json(['code'=>1,'info'=>lang('余额不足')]);
            }
            if($num < 10){
                return json(['code'=>1,'info'=>lang('最低操作金额').'10'.config('money_type')]);
            }
            // if($num > $this->tixian['tixian_max']){
            //     return json(['code'=>1,'info'=>lang('最高操作金额').$this->tixian['tixian_max'].config('money_type')]);
            // }
            if($userinfo['pwd2']!=sha1($pwd2.$userinfo['salt2'].config('pwd_str'))){
                return json(['code'=>1,'info'=>lang('提现密码错误')]);
            }
            $to = db('xy_users')->where('tel',$addr)->find();
            if(empty($to)) {
                return json(['code'=>1,'info'=>lang('账号不存在')]);
            }
            $money = $num + $fee;
            $res = db('xy_users')->where('id',session('user_id'))->setDec('topat',$money);
            $time = time();
            $before = $userinfo['topat']? $userinfo['topat']:0;
            $after = $before - $money;
            if($res!==false){
                $data =array(
                    'uid' =>session('user_id'),
                    'num' =>$money,
                    'addtime' =>$time,
                    'status' =>2,
                    'address' =>$addr,
                    'type' => 1,
                    'net' =>$net,
                    'shouxu' =>$fee,
                    'real_num' =>$num,
                );
                db::table('xy_deposit')->insert($data);
                $oid = Db::table('xy_deposit')->where('uid',session('user_id'))->getLastInsID();
                $data1 =array(
                    'uid' =>session('user_id'),
                    'type' =>13,
                    'oid' => $oid,
                    'num' =>$money,
                    'status' =>2,
                    'desc' =>lang('转账支出').$money.' TOPAT',
                    'yuedu' =>0,
                    'addtime' => $time,
                    'before' => $before,
                    'after' => $after,
                );
                db::table('xy_balance_log')->insert($data1);

                $transfer = db('xy_users')->where('tel',$addr)->setInc('topat',$money);
                $before1 = $transfer['topat'];
                $after1 = $before1 + $money;
                if($transfer) {
                    $data2 =array(
                        'uid' =>$to['id'],
                        'type' =>12,
                        'oid' => $oid,
                        'num' =>$money,
                        'status' =>1,
                        'desc' =>lang('转账入账').$money.' TOPAT',
                        'yuedu' =>0,
                        'addtime' => $time,
                        'before' => $before1,
                        'after' => $after1,
                    );
                    db::table('xy_balance_log')->insert($data2);
                }
                return json(['code'=>0,'info'=>lang('操作成功')]);
            }else{
                return json(['code'=>1,'info'=>lang('操作失败')]);
            }
        }else{
            return $this->fetch();
        }
    }

    public function topat_log()
    {
        $id = session('user_id');
        $this->list = db('xy_balance_log')->where('uid',$id)->where('type', '>', 9)->order('addtime desc')->paginate(10);
        return $this->fetch();
    }

    public function airdrop() {
        return $this->fetch();
    }
}