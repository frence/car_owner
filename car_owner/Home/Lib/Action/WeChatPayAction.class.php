<?php
/**
 * @brief 微信支付模块
 * @author bfr
 *
 */
class WeChatPayAction extends Action {
	/**
	 * @brief 加载所需的类文件
	 */
	public function _initialize() {
		vendor('WeChatPay.PayApiPay');
		vendor('WeChatPay.WxPayJsApiPay');
		vendor('WeChatPay.WxPay.Data');
	}
	
	/**
	 * @brief 显示支付界面
	 */
	public function index() {
		header("Content-type: text/html; charset=utf-8");
		$order_data = M('Order')->where(array('id'=>$this->_get('flag')))->find();
		
		$_body = '';
		$price_detail = unserialize($order_data['tg_price_detail']);
		foreach ($price_detail as $items) {
			$_body .=  $items['tg_parts_name'] . '：' . $items['tg_color_name'] . "\t";
		}
		if (mb_strlen($_body, 'utf8') > 50) $_body = mb_substr($_body, 0, 50,'UTF-8');

		//①、获取用户openid
		$tools = new JsApiPay();
		$openId = $tools->GetOpenid();
		$this->order_data = $order_data;
		
		//②、统一下单
		$input = new WxPayUnifiedOrder();
		$order_id = $order_data['id'];
		$input->SetAttach("$order_id");
		$_sn = $order_data['tg_order_sn'];
		$input->SetOut_trade_no($_sn);//订单号
													
		$totalprice = $order_data['tg_totalprice']*100;	//支付金额
		$input->SetTotal_fee("$totalprice");
		$input->SetTotal_fee("1");
		$input->SetTime_start(date("YmdHis"));
		$input->SetTime_expire(date("YmdHis", time() + 600));
		$input->SetGoods_tag("test");
		$input->SetNotify_url("http://wap.ylzelec.com/index.php/WeChatPay/payCallBack/");//回调地址  这个很重要，比如你付款成功之后想干一些事情，就会跳转到你指定的这个地址
                //$input->SetNotify_url("https://wap.ylzelec.com/index.php/WeChatPay/payCallBack/");//回调地址  这个很重要，比如你付款成功之后想干一些事情，就会跳转到你指定的这个地址
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($openId);
		$order = WxPayApi::unifiedOrder($input);
		$jsApiParameters = $tools->GetJsApiParameters($order);
		
		//获取共享收货地址js函数参数
		$editAddress = $tools->GetEditAddressParameters();
		
		//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
		/**
		 * 注意：
		 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
		 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
		 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
		 */
		$this->jsApiParameters = $jsApiParameters;
		$this->editAddress = $editAddress;
		$this->display();
	}
	
	/**
	 * @brief 支付成功之后的回调地址
	 */
	public function payCallBack() {

		echo "SUCCESS";
		die;



		$notify = new WxPayResults();
		
		$_backArr = $notify->FromXml($GLOBALS['HTTP_RAW_POST_DATA']);
		//如果验证失败就重新组装数据返回回去
		
		if (true === $notify->CheckSign()) {
			$data = array();
			$_m = M('Order');
			$data['id'] = $_backArr['attach'];
			$data['tg_status'] = 1;
			$_order = $_m->where(array('id'=>$data['id']))->find();
			$_price_detail = unserialize(stripslashes($_order['tg_price_detail']));
			$_parts = M('Parts');
			
			//更新订单表里面的状态
			if(!$_m->save($data)) {
				$notify->SetReturn_code('FALL');
				$notify->SetReturn_msg('订单状态重置失败！');
			} else {
				//减掉配件表里面的库存量
				$this->setOrderDetail($_price_detail);
				$notify->SetReturn_code('SUCCESS');
			}
		} else {
			$notify->SetReturn_code('FALL');
			$notify->SetReturn_msg('签名失败！');
		}
		echo $notify->ToXml();
	}
	
	/**
	 * @brief 更改库存量和销售量
	 */
	private function setOrderDetail($_price_detail) {
		$_m = M('Parts');
		foreach ($_price_detail as $items) {
			$_m->where(array('id'=>$items['tg_parts_id']))->setDec('tg_inventory', intval($items['tg_num']));
			$_m->where(array('id'=>$items['tg_parts_id']))->setInc('tg_sales', intval($items['tg_num']));
			continue;
		}
	}
}
?>