<?php

namespace app\admin\model;

use think\Model;
use think\Db;

class Convey extends Model
{

    protected $table = 'xy_convey';

    /**
     * 创建订单
     *
     * @param int $uid
     * @return array
     */
    public function create_order($uid,$cid=1,$task_cid)
    {
        $uinfo = Db::name('xy_users')->field('deal_status,balance,level')->find($uid);
        if($uinfo['deal_status']!=2) return ['code'=>1,'info'=>'抢单已终止'];
        
        if($task_cid == 3){
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        if($task_cid == 4){
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        if($task_cid == 5){
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        if($task_cid == 6){
            $cate = Db::name('xy_task_cate')->where('id',$task_cid)->find();
        }
        if($uinfo['balance'] > $cate['max_jine']){
            $min = $cate['max_jine']*config('deal_min_num'.$task_cid)/100;
            $max = $cate['max_jine']*config('deal_max_num'.$task_cid)/100;
        }
        if($uinfo['balance'] < $cate['max_jine']){
            $min = $uinfo['balance']*config('deal_min_num'.$task_cid)/100;
            $max = $uinfo['balance']*config('deal_max_num'.$task_cid)/100; 
        }
        if($uinfo['balance'] == $cate['max_jine']){
            $min = $uinfo['balance']*config('deal_min_num'.$task_cid)/100;
            $max = $uinfo['balance']*config('deal_max_num'.$task_cid)/100; 
        }
        //return ['code'=>1,'info'=>$max];
        $goods = $this->rand_order($min,$max,$cid);
        
        //return ['code'=>1,'info'=>$goods['goods_pic']];
        $level = $uinfo['level'];
        !$uinfo['level'] ? $level = 0 : '';
        $ulevel = Db::name('xy_level')->where('level',$level)->find();

        $id = getSn('UB');
        Db::startTrans();
        $res = Db::name('xy_users')->where('id',$uid)->update(['deal_status'=>3]);//将账户状态改为交易中
        //通过商品id查找 佣金比例
        $cate = Db::name('xy_goods_cate')->find($goods['cid']);
        if($goods['num'] > $uinfo['balance']) return ['code'=>1,'info'=>'可用余额不足!'];
        
        $bili = Db::name('xy_task_cate')->where('id',$task_cid)->value('bili');
        $bili = $bili/100;
        //var_dump($cate,123,$goods);die;
        $daoqi_time = time() + config('task_chaoshi')*3600;
        $res1 = Db::name($this->table)
                ->insert([
                    'id'            => $id,
                    'uid'           => $uid,
                    'num'           => $goods['num'],
                    'addtime'       => time(),
                    'endtime'       => time()+config('deal_timeout'),
                    'task_cid'        => $task_cid,
                    'goods_id'      => $goods['id'],
                    'goods_count'   => $goods['count'],
                    'commission'    => $goods['num']*$bili,  //交易佣金按照会员等级
                    'daoqi_time'    =>$daoqi_time
                ]);
        if($res && $res1){
            if($task_cid == 3){
                Db::name('xy_users')->where('id',$uid)->setDec('task_pddnum');
            }
            if($task_cid == 4){
                Db::name('xy_users')->where('id',$uid)->setDec('task_tbnum');
            }
            if($task_cid == 5){
                Db::name('xy_users')->where('id',$uid)->setDec('task_tmnum');
            }
            if($task_cid == 6){
                Db::name('xy_users')->where('id',$uid)->setDec('task_jdnum');
            }
            Db::commit();
            //$yongjin = round($goods['num']*$bili);
            $num = round($goods['num']);
            $count = round($goods['count']);
            
            return ['code'=>0,'info'=>'抢单成功!','oid'=>$id,'num'=>$num,'goods_id'=>$goods['id'],'goods_count'=>$count,'commission'=>$goods['num']*$bili];
        }else{
            Db::rollback();
            return ['code'=>1,'info'=>'抢单失败!请稍后再试'];
        }
    }

    /**
     * 随机生成订单
     */
    private function rand_order($min,$max,$cid)
    {
        $num = mt_rand($min,$max);//随机交易额
        $goods = Db::name('xy_goods_list')
                ->orderRaw('rand()')
                ->where('goods_price','between',[$num/10,$num])
                ->where('cid','=',$cid)
                ->find();

        if (!$goods) {
            echo json_encode(['code'=>1,'info'=>'抢单失败, 该分类库存不足!']);die;
        }

        $count = $num/$goods['goods_price'];//
        
        if($count*$goods['goods_price']<$min or $count*$goods['goods_price']>$max){
            self::rand_order($min,$max,$cid);
            //$this->rand_order($min,$max,$cid);
        }
        if($count*$goods['goods_price']>$min || $count*$goods['goods_price']<$max){
            return ['count'=>$count,'id'=>$goods['id'],'num'=>$count*$goods['goods_price'],'cid'=>$goods['cid']];
        }
        
        
    }
    /**
     * 处理订单
     *
     * @param string $oid      订单号
     * @param int    $status   操作      1会员确认付款 2会员取消订单 3后台强制付款 4后台强制取消
     * @param int    $uid      用户ID    传参则进行用户判断
     * @param int    $uid      收货地址
     * @return array
     */
    public function do_order($oid,$status,$uid='')
    {
        $info = Db::name('xy_convey')->find($oid);
        if(!$info) return ['code'=>1,'info'=>'订单号不存在'];

        if($uid && $info['uid']!=$uid) return ['code'=>1,'info'=>'参数错误，请确认订单号!'];
        if(!in_array($info['status'],[0,5])) return ['code'=>1,'info'=>'该订单已处理！请刷新页面'];

        //TODO 判断余额是否足够
        $userPrice = Db::name('xy_users')->where('id',$info['uid'])->value('balance');
        if ($userPrice < $info['num']) return ['code'=>1,'info'=>'账号余额不足商品金额，请您进行充值或者继续抢单！'];

        //$tmp = ['endtime'=>time(),'status'=>$status];
        $tmp = ['endtime'=>time()+config('deal_feedze'),'status'=>5];
        Db::startTrans();
        $res = Db::name('xy_convey')->where('id',$oid)->update($tmp);
        if(in_array($status,[1,3])){
            //确认付款
            try {$res1 = Db::name('xy_users')
                        ->where('id', $info['uid'])
                        ->dec('balance',$info['num'])
                        ->inc('freeze_balance',$info['num']+$info['commission']) //冻结商品金额 + 佣金
                        ->update(['deal_status' => 1,'status'=>1]);
            } catch (\Throwable $th) {
                Db::rollback();
                return ['code'=>1,'info'=>'请检查账户余额!'];
            }
            $res2 = Db::name('xy_balance_log')->insert([
                'uid'           => $info['uid'],
                'oid'           => $oid,
                'num'           => $info['num'],
                'type'          => 4,
                'desc'          => '抢单支出'.$info['num'],
                'addtime'       => time()
            ]);
            //系统通知
            if($res && $res1 && $res2){
                Db::commit();
                $c_status = Db::name('xy_convey')->where('id',$oid)->value('c_status');
                //判断是否已返还佣金
                
                if($c_status===0) $this->deal_reward($info['uid'],$oid,$info['num'],$info['commission']);
                return ['code'=>0,'info'=>'操作成功!'];
            }else {
                Db::rollback();
                return ['code'=>1,'info'=>'操作失败'];
            }
        }elseif (in_array($status,[2,4])) {
            $res1 = Db::name('xy_users')->where('id',$info['uid'])->update(['deal_status'=>1]);
            //系统通知
            if($res && $res1!==false){
                Db::commit();
                return ['code'=>0,'info'=>'操作成功!'];
            }else {
                Db::rollback();
                return ['code'=>1,'info'=>'操作失败','data'=>$res1];
            }
        }
    }

    /**
     * 交易返佣
     *
     * @return void
     */
    public function deal_reward($uid,$oid,$num,$cnum)
    {
        //$res = Db::name('xy_users')->where('id',$uid)->where('status',1)->setInc('balance',$num+$cnum);
        $res = Db::name('xy_users')->where('id',$uid)->where('status',1)->setInc('balance',$num+$cnum);
        $res2 = Db::name('xy_users')->where('id',$uid)->where('status',1)->setDec('freeze_balance',$num+$cnum);
        if($res){
                $res1 = Db::name('xy_balance_log')->insert([
                    //记录返佣信息
                    'uid'       => $uid,
                    'oid'       => $oid,
                    //'num'       => $num+$cnum,
                    'num'       => $cnum,
                    'type'      => 0,
                    'desc'          => '抢单返佣'.$cnum,
                    'addtime'   => time()
                ]);
                //将订单状态改为已返回佣金
                Db::name('xy_convey')->where('id',$oid)->update(['c_status'=>1,'status'=>1]);
                Db::name('xy_reward_log')->insert(['oid'=>$oid,'uid'=>$uid,'num'=>$cnum,'addtime'=>time(),'type'=>2]);//记录充值返佣订单
                 /************* 发放交易奖励 *********/
                    $userList = model('admin/Users')->parent_user($uid,5);
                    if($userList){
                        foreach($userList as $v){
                            $p_level = Db::name('xy_users')->where('id',$v['id'])->value('level');
                            $rebate_price = Db::table('xy_level')->where('level',$p_level)->find();
                            if($v['status']===1 && ($cnum * $rebate_price['task_p'.$v['lv']]/100 != 0)){
                                $balance = $cnum * $rebate_price['task_p'.$v['lv']]/100;
                                Db::name('xy_users')->where('id',$v['id'])->setInc('balance',$balance);
                                Db::name('xy_reward_log')
                                ->insert([
                                    'uid'=>$v['id'],
                                    'sid'=>$uid,
                                    'oid'=>$oid,
                                    'num'=>$cnum * $rebate_price['task_p'.$v['lv']]/100,
                                    'lv'=>$v['lv'],
                                    'type'=>3,
                                    'addtime'=>time(),
                                ]);
                            }
                        }
                    }
                 /************* 发放交易奖励 *********/
        }else{
            $res1 = Db::name('xy_convey')->where('id',$oid)->update(['c_status'=>2]);//记录账号异常
        }
    }
}