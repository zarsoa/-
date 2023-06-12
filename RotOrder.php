<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;

/**
 * 下单控制器
 */
class RotOrder extends Base
{
    
    /**
     * 首页
     */
    public function index()
    {
        $uid = session('user_id');
        $cate_id = input('get.id/d',1);
        $this->task_cate = db('xy_task_cate')->where('id',$cate_id)->find();
        $this->user_info = db('xy_users')->where('id',$uid)->find();
        //总共做了多少次
        $where1 =array(
            'uid'=>$uid,
            'status'=>1
        );
        $this->commissionz = Db::name('xy_convey')->where($where1)->count();
        //获取该分类下的今日收益
        $where =array(
            'uid'=>$uid,
            'status'=>1,
            'task_cid'=>$cate_id
        );
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $this->commission = Db::name('xy_convey')->where($where)->where('addtime', '>', $beginToday)->where('addtime', '<', $endToday)->sum('commission');
        return $this->fetch();
    }
  /**
    *提交抢单
    */
    public function submit_order()
    {
        $uid = session('user_id');
        $task_cid = input('get.task_cid/s','');
        $ujine = Db::name('xy_users')->where('id',$uid)->value('balance');
        if($task_cid == 3){
            $task_num = Db::name('xy_users')->where('id',$uid)->value('task_pddnum');
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        if($task_cid == 4){
            $task_num = Db::name('xy_users')->where('id',$uid)->value('task_tbnum');
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        if($task_cid == 5){
            $task_num = Db::name('xy_users')->where('id',$uid)->value('task_tmnum');
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        if($task_cid == 6){
            $task_num = Db::name('xy_users')->where('id',$uid)->value('task_jdnum');
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        $shiming = Db::name('xy_users')->where('id',$uid)->value('id_status');
        if($shiming == 0){
            return json(['code'=>3]);
        }
        if($task_num < 1){
            return json(['code'=>1,'info'=>lang('剩余可接任务数不足')]);
        }
        if(config('deal_count') < 1){
            return json(['code'=>1,'info'=>lang('今日次数已用完')]);
        }
        if($ujine < $cate['min_jine']){
            return json(['code'=>1,'info'=>lang('您的账户余额不足最小抢单金额').$cate['min_jine'].'-'.$cate['max_jine'].config('money_type')]);
        }
        $res = check_time(config('order_time_1'),config('order_time_2'));
        $str = config('order_time_1').":00  - ".config('order_time_2').":00";
        if($res) return json(['code'=>1,'info'=>lang('抢单时间为').$str]);
        //随机成功或失败
        $a = $cate['gailv'];
        $b = 100 - $a;
        $sjnum =array('a'=>$a,'b'=>$b);
        if(get_rand($sjnum) == 'b'){
            return json(['code'=>1,'info'=>lang('手慢了，再接再厉！')]);
        }
        
        $tmp = $this->check_deal($task_cid);
        if($tmp) return json($tmp);

        $uid = session('user_id');
        //检查交易状态
        // $sleep = mt_rand(config('min_time'),config('max_time'));
        $res = db('xy_users')->where('id',$uid)->update(['deal_status'=>2]);//将账户状态改为等待交易
        if($res === false) return json(['code'=>1,'info'=>lang('抢单失败，请稍后再试！')]);
        // session_write_close();//解决sleep造成的进程阻塞问题
        // sleep($sleep);
        //
        $cid = input('post.cid/d',1);
        $count = db('xy_goods_list')->where('cid','=',$cid)->count();
        

        if($count < 1) return json(['code'=>1,'info'=>lang('抢单失败，商品库存不足！')]);


        $res = model('admin/Convey')->create_order($uid,$cid,$task_cid);
        return json($res);
    }

    public function order_xq(){
        $goods_id = input('post.goods_id/s','');
        $goods_info = db('xy_goods_list')->where('id',$goods_id)->find();
        return json(['goods_pic'=>$goods_info['goods_pic'],'goods_name'=>$goods_info['goods_name'],'goods_price'=>$goods_info['goods_price']]);
    }
    
}
    
    
    