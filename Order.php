<?php

namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Request;

/**
 * 订单列表
 */
class Order extends Base
{
    
    /**
     * 处理订单
     */
    public function do_order()
    {
        if(request()->isPost()){
            $oid = input('post.oid/s','');
            $status = input('post.status/d',1);
            if(!\in_array($status,[1,2])) json(['code'=>1,'info'=>lang('参数错误')]);

            $res = model('admin/Convey')->do_order($oid,$status,session('user_id'));
            return json($res);
        }
        return json(['code'=>1,'info'=>lang('错误请求')]);
    }

    /**
     * 获取充值订单
     */
    public function get_recharge_order()
    {
        $uid = session('user_id');
        $page = input('post.page/d',1);
        $num = input('post.num/d',10);
        $limit = ( (($page - 1) * $num) . ',' . $num );
        $info = db('xy_recharge')->where('uid',$uid)->order('addtime desc')->limit($limit)->select();
        if(!$info) return json(['code'=>1,'info'=>lang('暂无数据')]);
        return json(['code'=>0,'info'=>lang('请求成功'),'data'=>$info]);
    }

    /**
     * 验证提现密码
     */
    public function check_pwd2()
    {
        if(!request()->isPost()) return json(['code'=>1,'info'=>lang('错误请求')]);
        $pwd2 = input('post.pwd2/s','');
        $info = db('xy_users')->field('pwd2,salt2')->find(session('user_id'));
        if($info['pwd2']=='') return json(['code'=>1,'info'=>lang('未设置交易密码')]);
        if($info['pwd2']!=sha1($pwd2.$info['salt2'].config('pwd_str'))) return json(['code'=>1,'info'=>lang('密码错误!')]);
        return json(['code'=>0,'info'=>lang('验证通过')]);
    }
    
    /**
     * 取消订单
     */
    public function up_order()
    {
        $uid = session('user_id');
        $id = input('post.id');
        $endtime = time();
        $res = Db::name('xy_convey')->where('id', $id)->update(['status' => 4,'endtime' => $endtime]);
        if($res){
            Db::name('xy_users')->where('id', $uid)->update(['deal_status' => 1]);
            return json(['code'=>0,'info'=>lang('放弃成功')]);
        }else{
            return json(['code'=>0,'info'=>lang('放弃失败')]);
        }
    }
}