<?php

class TestAction extends Action {
	 //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('Weixinpay/WxPayJsApiPay');
         
    }
	//我的账户
	public function index(){
			$Notirs =new WxPayNotifyReply();
			 $Notirs->SetReturn_code('SUCCESS');
                        $Notirs->SetReturn_msg('OK');
                        $aa = $Notirs->ToXml();
                        dump($aa);
                        echo $aa;
		 $this->display();
	}
}