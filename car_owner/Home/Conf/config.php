<?php
$config =array(

		'REG_GIFT_RANGE' => '1', //注册送礼有效期限1表示一个月
   		'SAVE_GIFT_RANGE' => '3', //充值送礼有效期限3表示三个月

		'GIFT_RECORD' => '1',//礼券赠送，1为不赠送，2为赠送
		'MOBILE_CODE' => '1',//1为测试，2为正式短信通道
		'SITE_URL' =>'http://wap.ylzelec.com/', 
		'APP_AUTOLOAD_PATH' =>'@.Common',//'@.Common,@.Tool'     
		'URL_MODEL'=>1, // 如果你的环境不支持PATHINFO 请设置为3
		'PAGEFLASH_URL'        =>'http://admin.ylzelec.com',  //幻灯片的地址前缀
		'URL_CASE_INSENSITIVE'  => true,   // 默认false 表示URL区分大小写 true则表示不区分大
		'SESSION_AUTO_START'        =>true,
        'USER_AUTH_ON'              =>true,//
        'USER_AUTH_TYPE'			=>1,		// 默认认证类型 1 登录认证 2 实时认证
		'VAR_PAGE'                  =>'p',
		 'SHOW_PAGE_TRACE' =>true,
		//'SHOW_PAGE_TRACE' =>false,
		'UPLOAD_SIZE' =>314572800,		

		'KEYCODE' =>'405a81f0729af5803',	//加密key
		'INITPWD' =>'ft123456',	//初始化密码
		
		// /*SESSIONDB设置*/
		'SESSION_TYPE'   => 'Db',//使用数据库方式记录SESSION精确控制SESSION过期时间
		'SESSION_EXPIRE' => 30*600,//设置60秒过期
		//'TMPL_STRIP_SPACE' => true,//页面压缩
	
		// /*Cookie设置 */
		'COOKIE_EXPIRE'         => 3600,    // Coodie有效期(秒)
		'COOKIE_DOMAIN'         => 'ylzelec.com',      // Cookie有效域名
		// 'COOKIE_PATH'           => '/auth',     // Cookie路径
		// 'COOKIE_PREFIX'         => 'yizizhu_xiche_',// Cookie前缀 避免冲突
	
		// /*表单令牌验证配置*/
		// 'TOKEN_ON'=>true,  // 是否开启令牌验证
		// 'TOKEN_NAME'=>'__hash__',    // 令牌验证的表单隐藏字段名称
		// 'TOKEN_TYPE'=>'md5',  //令牌哈希验证规则 默认为MD5
		// 'TOKEN_RESET'=>true,  //令牌验证出错后是否重置令牌 默认为true
		
		
		    /*数据库配置*/
		'DB_TYPE'        =>  'mysql',//数据库类型
		// 'DB_HOST'        =>  'localhost',//数据库主机
		'DB_HOST'        =>  '121.40.123.162',//数据库主机
		'DB_NAME'        =>  'washer',//数据库名
		'DB_USER'        =>  'washer',//用户名
		'DB_PWD'         =>  'washer123',//密码
		// 'DB_NAME'        =>  'car',//数据库名
		// 'DB_USER'        =>  'root',//用户名
		// 'DB_PWD'         =>  '',//密码
		'DB_PORT'        =>  '3306',//端口
		'DB_PREFIX'      =>  'car_',//表前缀
		'DB_PARAMS' => array('persist' => true),
		
		/*多语言配置*/
		'LANG_SWITCH_ON' 		=> true,
		'DEFAULT_LANG'          =>  'zh-cn', // 默认语言
		'LANG_AUTO_DETECT' => false, // 自动侦测语言 开启多语言功能后有效
		'LANG_LIST'        => 'zh-cn', // 允许切换的语言列表 用逗号分隔
		'VAR_LANGUAGE'     => 'l', // 默认语言切换变量

		//二维码过期时间,秒为单位
		'qrcode_end_time' =>'10',
		//邮件配置
 		'THINK_EMAIL' =>array(
		'SMTP_HOST'   => 'smtp.exmail.qq.com', //SMTP服务器
    	'SMTP_PORT'   => '465', //SMTP服务器端口
    	'SMTP_USER'   => 'vip@yizizhu.cn', //SMTP服务器用户名
    	'SMTP_PASS'   => 'q123456', //SMTP服务器密码
    	'FROM_EMAIL'  => 'vip@yizizhu.cn', //发件人EMAIL
    	'FROM_NAME'   => 'VIP客服', //发件人名称
    	'REPLY_EMAIL' => '', //回复EMAIL（留空则为发件人EMAIL）
    	'REPLY_NAME'  => '', //回复名称（留空则为发件人名称）
    	'Receiver_email' =>'532987614@qq.com',//接收者邮箱
    	'Receiver_name' =>'风雨无阻',//接收者名称
    	),
	);
	return array_merge($config);
	


?>