<?php
		
	//定义css、img、js常量
	define("SITE_URL","http://wap.yizizhu.cn/");
		
		
	//定义车主的参数
	define("CSS_URL",SITE_URL."Public/Client/css/"); //前台 css
	define("IMG_URL",SITE_URL."Public/Client/images/"); //前台 img
	define("JS_URL",SITE_URL."Public/Client/js/"); //前台 js
	// define("ADIMG_URL","http://admin.ylzelec.com"); //admin 图片
	define("ADIMG_URL","http://admin.yizizhu.cn"); //admin 图片
		
	//系统后台定义
	define("ADMIN_CSS_URL",SITE_URL."Public/Admin/css/"); //后台 css
	define("ADMIN_IMG_URL",SITE_URL."Public/Admin/img/"); //后台 图片
	define("ADMIN_IMAGES_URL",SITE_URL."Public/Admin/images/"); //后台 图片
	define("ADMIN_JS_URL",SITE_URL."Public/Admin/js/"); //后台 js
		
	//为上传图片设置路径
	define("IMG_UPLOAD",SITE_URL."Uploads/");
	define("IMG_ROOTPATH",SITE_URL."Public/Images/");

		
     //开启调试模式
     define('APP_DEBUG',TRUE);
	// define('APP_DEBUG',false);
	  
     //前台目录
     define('APP_PATH', './Home/');
     define('W3CORE_PATH','./ThinkPHP');	 
     require W3CORE_PATH.'/ThinkPHP.php';
?>