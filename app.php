<?php

return [

	
    // 应用调试模式
    'app_debug'                 => true,
    // 应用Trace调试
    'app_trace'                 => false,
    // 0按名称成对解析 1按顺序解析
    'url_param_type'            => 1,
    // 当前 ThinkAdmin 版本号
    'thinkadmin_ver'            => 'v5',

    'empty_controller'          => 'Error',

    'pwd_str'                   => '!qws6F!xffD2vx80?95jt',  //盐

    'pwd_error_num'             => 10,    //密码连续错误次数

    'allow_login_min'           => 5,     //密码连续错误达到次数后的冷却时间，分钟

    'default_filter'            => 'trim',
    //短信宝
    'smsbao' => [
        'user'=>'www.9nw.cc', //账号  无需md5
        'pass'=>'www.9nw.cc', //密码
        'sign'=>'', //签名
    ],

	'kfaddress'=> 'http://45.141.68.16/',  //客服地址
    'app_url'=>'http://192.168.0.8/',          //app下载地址
    'version'=>'1',  //版本号

    'verify'    => true,
    'deal_min_balance'=>'0',          //交易所需最小余额
    'deal_min_num3'=>'',               //匹配区间
    'deal_max_num3'=>'',               //匹配区间
    
    'deal_min_num4'=>'',               //匹配区间
    'deal_max_num4'=>'',               //匹配区间
    
    'deal_min_num5'=>'',               //匹配区间
    'deal_max_num5'=>'',               //匹配区间
    
    'deal_min_num6'=>'',               //匹配区间
    'deal_max_num6'=>'',               //匹配区间
    
    'deal_count'=>'',                 //当日交易次数限制
    'deal_zhuji_time'=>'',         //远程主机分配时间
    'deal_shop_time'=>'',          //等待商家响应时间
    'tixian_time_1'=>'00:01',           //提现开始时间
    'tixian_time_2'=>'23:59',          //提现结束时间
    'chongzhi_time_1'=>'00:01',           //充值开始时间
    'chongzhi_time_2'=>'23:59',          //充值结束时间
    'order_time_1'=>'00:01',           //抢单结束时间
    'order_time_2'=>'23:59',          //抢单结束时间


    'shop_shuoming'=>'<p>1、请根据您的会员等级进入相应抢单区；</p> <p>2、请确保账户余额充足；</p> <p>3、佣金计算：账户余额*佣金比例=收益佣金；</p> <p><font color=',         //抢单说明',
    'money_type'=>'USDT',         //现金单位',
    'money_bili'=>'1',         //现金比例',
    'hongbao_max'=>'1',         //红包最大金额',
    'hongbao_min'=>'0.01',         //红包最小金额',
    'hongbao_status'=>'1',         //红包状态',
    'task_chaoshi'=>'48',         //任务超时时间',
    'wx_chaoshi'=>'5',
    'wx_money'=>'2',
    
     // 默认语言
    'default_lang'           => Cookie('lang'),
     //'default_lang'           => 'en-us',
 
    // 开启语言切换
    // 'lang_switch_on' => true,   
    //语言列表
    'lang_list' => ['zh-cn','en-us'],
    //禁止访问模块
    //'deny_module_list'=> ['common','admin'],
    'app_map' => ['admin123'=>'admin'],
];

