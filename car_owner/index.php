<?php
		
	//����css��img��js����
	define("SITE_URL","http://wap.yizizhu.cn/");
		
		
	//���峵���Ĳ���
	define("CSS_URL",SITE_URL."Public/Client/css/"); //ǰ̨ css
	define("IMG_URL",SITE_URL."Public/Client/images/"); //ǰ̨ img
	define("JS_URL",SITE_URL."Public/Client/js/"); //ǰ̨ js
	// define("ADIMG_URL","http://admin.ylzelec.com"); //admin ͼƬ
	define("ADIMG_URL","http://admin.yizizhu.cn"); //admin ͼƬ
		
	//ϵͳ��̨����
	define("ADMIN_CSS_URL",SITE_URL."Public/Admin/css/"); //��̨ css
	define("ADMIN_IMG_URL",SITE_URL."Public/Admin/img/"); //��̨ ͼƬ
	define("ADMIN_IMAGES_URL",SITE_URL."Public/Admin/images/"); //��̨ ͼƬ
	define("ADMIN_JS_URL",SITE_URL."Public/Admin/js/"); //��̨ js
		
	//Ϊ�ϴ�ͼƬ����·��
	define("IMG_UPLOAD",SITE_URL."Uploads/");
	define("IMG_ROOTPATH",SITE_URL."Public/Images/");

		
     //��������ģʽ
     define('APP_DEBUG',TRUE);
	// define('APP_DEBUG',false);
	  
     //ǰ̨Ŀ¼
     define('APP_PATH', './Home/');
     define('W3CORE_PATH','./ThinkPHP');	 
     require W3CORE_PATH.'/ThinkPHP.php';
?>