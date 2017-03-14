<?php
class CommonAction extends Action {//公共控制器
	protected static $wechatAuth;
	protected static $wechatJS;
	protected static $wechatOpenid;

	protected function _initialize() {
		header("Content-Type:text/html; charset=utf-8");
		$this->getWeiXinConfig();
		$this->wechatOpenid = cookie('wechatOpenid');
	}


	private function getWeiXinConfig($defaultId = 1) {
		if(cookie('weiXinConfigId')){
			$defaultId = cookie('weiXinConfigId');
		}

		$config = D('Wxconfig');
		
		$data = $config->getWeiXinConfig($defaultId);
		
		array_shift($data);
		C($this->strtoupperArray($data));
	}



	protected function strtoupperArray($arr, $type = 'k') {
		$arr = $arr;
		foreach ( $arr as $k => $v ) {
			if ($type == 'k') {
				unset($arr[$k]);
				$arr[strtoupper($k)] = $v;
			}
			elseif ($type == 'v') {
				$arr[$k] = strtoupper($v);
			}
			else {
				unset($arr[$k]);
				$arr[strtoupper($k)] = strtoupper($v);
			}

		}
		//var_dump($arr);
		return $arr;
	}

	/**
	 * 初始化微信验证验证类  kevin
	 */
	protected function initWechatWebAuth() {
		if (empty($this->wechatAuth)) {
			$this->_initWechatWebAuth();
		}
	}

	private function _initWechatWebAuth() {
		import('@.Util.Wechat.WechatAuth');
		$this->wechatAuth = new WechatAuth(C('WEIXIN_APPID') ,  C('WEIXIN_APPSECRET'));
		//var_dump($this->wechatAuth);
	}


	/**
	 * 初始化微信js类  kevin
	 */
	protected function initWechatJS() {
		if(empty($this->wechatJS)){
			$this->_initWechatJS();
		}
	}

	private function _initWechatJS() {
		import('@.Util.Wechat.WechatJS');
		$this->wechatJS = new WechatJS(C('WEIXIN_APPID'), C('WEIXIN_APPSECRET'));
	}
	/**
	 * 设置openid的值 　
	 * kevin - Wed May 27 05:53:39 GMT 2015
	 */
	protected function setWechatOpenid() {
		if (empty($this->wechatOpenid)){
			$this->initWechatWebAuth();
			$url = C('SITE_URL')."index.php?m=Api&a=wechatWebAuth";
			$redirect = $this->wechatAuth->getRequestCodeURL($url, __SELF__, 'snsapi_base');
			redirect($redirect);
		}
	}

	/**
	 * 格式化为完整网址  kevin - 10:16:19
	 * @access protected
	 * @param  string $type
	 * @param  array $data
	 * @return string
	 */
	protected function formatUrl($type, $data = array()) {
		switch($type){
			case 'shareApiUrl':
				$url = C('SITE_URL')."index.php?m=Api&a=shareData&".$data;
				//$url = U(MODULE_NAME . '/Api/shareData', $data, true, false, true);
				break;

			case 'article':
				$url = C('SITE_URL')."index.php?m=Api&m=article&a=index&".$data;
				//$url = U(MODULE_NAME . '/article/index', $data, true, false, true);
				break;

		}
		return $url;
	}
	


	/*空操作*/
	function _empty() {
		$this->assign('jumpUrl', '__APP__/Public/error404');
		$this->error("抱歉您请求的页面不存在");
	}
	function error404() {
		$this->assign('jumpUrl', '__APP__/Public/error404');
		$this->error("抱歉您请求的页面不存在或已经删除。");
	}
	
	/*
	*相关标签
	*$Module：模型名称
	*$ViewName 视图名称
	*/
	public function getags($Module, $ViewName) {
		$Tag = D('Tag');
		if (!empty ($_GET['name'])) {

			header("content-Type: text/html; charset=Utf-8");
			$name = trim($_GET['name']);
			//$name =iconv("GB2312","UTF-8",$name);

			$vo = $Tag->where(array (
				'module' => $Module,
				'name' => $name
			))->field('id,count')->find(); //取得标签的ID和相关数

			import("ORG.Util.Page");
			$listRows = 10;

			$P = new Page($vo['count'], $listRows);
			$P->setConfig('header', '篇博文 ');

			$list = D($ViewName)->where(array (
				'status' => 1,
				'tagId' => $vo['id']
			))->order('id desc')->limit($P->firstRow . ',' . $P->listRows)->select();

			if ($list) { //列出相关标签
				$page = $P->show();
				$this->assign("page", $page);
				$this->assign('list', $list);
			}
			$this->assign('tag', $name);
			$this->assign("count", $vo['count']);
		} else { //列出所有标签
			//$map['module']= $Module;
			$list = $Tag->where(array (
				'module' => $Module
			))->select();
			$this->assign('tags', $list);
		}
	}
	
	
	public function adddata($ModuleName) {
		$Module = D($ModuleName);
		if ($vo = $Module->create()) {
			if ($Module->add()) {
				$this->ajaxReturn($vo, '表单数据保存成功！', 1);
			} else {
				$this->error();
			}
		} else {
			$this->error();
		}
	}
	
	
	public function gettourl($ModuleName) {//还未测试2012.12.7
		if (!empty ($_GET['id'])) {
			$url = M($ModuleName)->where(array('id'=>$_GET['id']))->getField('url');
			redirect($url, 1, ' ');
		}
	}
	
	/*检查是否登陆*/
	public function checklogin(){
		$rst = false;
		return $rst;	
	}
	
}
?>