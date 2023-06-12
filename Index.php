<?php

namespace app\index\controller;

use library\Controller;
use think\Db;

/**
 * 应用入口
 * Class Index
 * @package app\index\controller
 */
class Index extends Base
{
    /**
     * 入口跳转链接
     */
    public function index()
    {
        $this->redirect('home');
    }

    //首页
    public function home()
    {
        $uid = session('user_id');
        $this->uid = $uid;

        //在线人数
        // $online = db('xy_users')->where('login_status',1)->select();
        // $this->zaixiannum = count($online);
        // foreach ($online as $k => $v) {
        //     if($v['id'] == $uid) {
        //         $this->info = $v;
        //     }
        // }
        //在线人数
//        $this->zaixiannum = db('xy_users')->where('login_status',1)->count();
        //$this->zxnum = sysconf('site_zxnum') * $this->zaixiannum;
        //虚拟收益
        //$this->xnshouyi = sysconf('site_xnshouyi');
        $this->zxnum = 0;
        $this->xnshouyi = 0;
       $this->info = db('xy_users')->where('id',$uid)->find();
        //首页栏目列表
//        $this->lanmu = db('xy_lanmu')->order('sort asc')->where('status',1)->select();
        //首页任务分类列表
//        $this->task_cate = db('xy_task_cate')->order('sort asc')->select();
//        $this->level = db('xy_level')->order('id asc')->select();
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
//        $this->qiandao_status = Db::table('xy_qiandao')->where('id',1)->value('status');
        //每日红包奖励
//        if((time() - $this->info['lingqu_time']) > 86400){
//            Db::table('xy_users')->where('id',$uid)->update(['hongbao' => 0]);
//        }
        //默认公告
        $this->gonggao_tan = Db::table('xy_message')->where('id',1)->find();
        
        //等级分类限制
//        $this->ulevel = Db::table('xy_level')->where('level',$this->info['level'])->find();
        //广告
//        $this->ads = db('xy_ads')->order('create_time desc')->where('status',1)->where('type',1)->select();
        $this->sylm = [
            ['name'=>'iphone','url'=>'https://www.apple.com','img'=>'ip'],
        	['name'=>'E-bay','url'=>'https://www.ebay.com','img'=>'ebay'],
        	['name'=>'Amazon','url'=>'https://www.amazon.com','img'=>'ama'],
        	['name'=>'Tesla','url'=>'https://www.tesla.com/','img'=>'tl'],
        	['name'=>'shopee','url'=>'https://shopee.com.my','img'=>'shop'],
        	['name'=>'Tiktok','url'=>'https://tiktok.fail/browse','img'=>'tk'],
        	['name'=>'flipkart','url'=>'https://www.flipkart.com/','img'=>'fk'],
        	['name'=>'facebook','url'=>'https://www.facebook.com','img'=>'fb'],
        	];

        $this->dqtime = time();
        $this->level = [];
        $this->task_cate = [];
        $this->lanmu = [];
        $this->qd_num = 0;
        $this->qiandao_num = 0;
        $this->qiandao_status = 0;
        $this->ulevel = 0;
        $this->ads =[];

        return $this->fetch();
    }
    //虚拟收益
    public function xnshouyi()
    {
        $shouyi = sysconf('site_xnshouyi');
        $num = sysconf('site_zxnum');
        $num = rand($num-100,$num+200);
        $stime = strtotime("2023-03-17");
        $etime = time();
        $date = $etime-$stime;
        $shouyi = round($shouyi + round($date + $date*5.5,2),2);
        return json(['code'=>$shouyi,'num'=>$num,'info'=>lang('更新成功')]);
    }
    //消息弹窗
    public function xiaoxi_tan()
    {
        $uid = session('user_id');
        if($uid){
            $xiaoxi_tan = Db::table('xy_message')->where('id',2)->find();
            $user_news = Db::table('xy_users')->where('id',$uid)->value('news');
            return json(['code'=>$user_news,'info'=>$xiaoxi_tan['content']]);
        }
    }
    //消息弹窗关闭
    public function xiaoxi_close()
    {
        $uid = session('user_id');
        $xiaoxi_tan = Db::table('xy_users')->where('id',$uid)->update(['news'=>0]);
    }
    // //通知弹窗
    public function tongzhi_tan()
    {
        $uid = session('user_id');
        if($uid){
            $user_news = Db::table('xy_users')->where('id',$uid)->find();
            return json(['code'=>$user_news['xiaoxi']]);
        }
    }
    //通知弹窗关闭
    public function tongzhi_close()
    {
        $uid = session('user_id');
        if($uid){
            $xiaoxi_tan = Db::table('xy_users')->where('id',$uid)->update(['xiaoxi'=>0]);
        }
    }
    //签到
    function qiandao()
    {
        $num = input('post.num/s','');
        $num5 = Db::table('xy_qiandao')->where('id',1)->value('num5');
        $uid = session('user_id');
        $info = Db::table('xy_users')->where('id',$uid)->find();
        $info['qd_num'] +=1;
        $info['balance'] += $num;
        if($info['qiandao'] == 1){
            return json(['code'=>1,'info'=>lang('您今天已经签到过了！')]);
        }else{
            if($info['qd_num'] == 5){
                $info['balance'] += $num5;
                $res = Db::table('xy_users')->where('id',$uid)->update(['qiandao' => 1,'qd_num' => 0,'balance' => $info['balance'],'qd_time' => time()]);
            }else{
                $res = Db::table('xy_users')->where('id',$uid)->update(['qiandao' => 1,'qd_num' => $info['qd_num'],'balance' => $info['balance'],'qd_time' => time()]);
            }
            if($res){
                //写入日志
                Db::name('xy_balance_log')->insert([
                    'uid'           => $uid,
                    'num'           => $num,
                    'type'          => 2,
                    'status'        => 1,
                    'desc'          => lang('签到奖励').$num,
                    'addtime'       => time()
                ]);
                return json(['code'=>0,'info'=>lang('签到成功'),'data'=>$res]);
            }else{
                return json(['code'=>1,'info'=>lang('签到失败')]);
            }
        }
    }
    

    //红包
    public function hongbao()
    {
        $mins = config('hongbao_min');
        $maxs = config('hongbao_max');
        $res = hongbao($mins,$maxs);
        if($res){
            return json(['code'=>0,'info'=>$res,'data'=>$res]);
        }else{
            return json(['code'=>1,'info'=>lang('暂无数据')]);
        }
    }
    //领取
    public function lingqu()
    {
        $num = input('post.hb_price/s','');
        $uid = session('user_id');
        $info = Db::table('xy_users')->where('id',$uid)->find();
        if($info['level'] < 1){
            return json(['code'=>1,'info'=>lang('普通会员不可领取哦！请升级VIP会员')]);
        }
        $info['balance'] +=$num;
        if($info['hongbao'] == 1){
            return json(['code'=>1,'info'=>lang('您今天已经领取过了！')]);
        }else{
            $res = Db::table('xy_users')->where('id',$uid)->update(['balance' => $info['balance'],'hongbao' => 1,'lingqu_time' => time()]);
            if($res){
                //写入日志
                Db::name('xy_balance_log')->insert([
                    'uid'           => $uid,
                    'num'           => $num,
                    'type'          => 3,
                    'status'        => 1,
                    'desc'          => lang('每日红包奖励').$num,
                    'addtime'       => time()
                ]);
                return json(['code'=>0,'info'=>lang('领取成功'),'data'=>$res]);
            }else{
                return json(['code'=>1,'info'=>lang('领取失败')]);
            }
        }
    }


}
