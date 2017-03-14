<?php

class ApiAction extends CommonAction {
	/**
	 * 接收微信分享成功后的操作  kevin 
	 */
	public function shareData() {
		$dataServer = I('get.');
		$dataClient = I('post.');
		$shareData = D('Article_share_data');
		$data = array_merge($dataServer, $dataClient);
		$return = $shareData->upData($data) ? 0 : 1;

		$this->show(json_encode(array(
		'message' => $return)));
		exit();
	}

	/**
	 * 接收微信网页登录获取接口后返回的操作  kevin 
	 */
	public function wechatWebAuth() {
		$code = I('get.code');
		$this->initWechatWebAuth();		
		$userinfo = $this->wechatAuth->getAccessToken('code', $code);
		cookie('wechatOpenid', $userinfo['openid']);
		redirect(htmlspecialchars_decode(I('get.state')));
	
	}

}