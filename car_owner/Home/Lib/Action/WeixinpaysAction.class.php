<?php

class WeixinpaysAction extends Action {
	 //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('Weixinpay/WxPayJsApiPay');
         
    }
    public function index(){

        //1、获取openid
        $tools = new JsApiPay();
        $openId = $tools->GetOpenid();

        //读取数据库
        $M = M('customer_saverecord');
        $where = "openid = '".$openId."'";
        $info = $M->where($where)->order('savetime desc')->find();

        //获取参数
        $title = "易自助";
        $tradeno = $info['orderid'];
        $totalfee = $info['cost'];
        $paymoney = $totalfee /100;
        $timestart = date("YmdHis");
        $timeexpire = date("YmdHis", time() + 600);
        $notifyurl = SITE_URL."index.php/Weixinpays/notify/";
 
        //2、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($title);
        $input->SetAttach($title);
        $input->SetOut_trade_no($tradeno);//流水号
        $input->SetTotal_fee(1);//测试金额 1分
        //$input->SetTotal_fee($totalfee);//正式金额
        $input->SetTime_start($timestart);
        $input->SetTime_expire($timeexpire);
        $input->SetGoods_tag($title);
        $input->SetNotify_url($notifyurl);   //支付回调地址，这里改成你自己的回调地址。
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        $this->jsApiParameters=$jsApiParameters;
        $this->assign('paymoney',$paymoney);
         
        
        
        $this->display();
  
        
    }

    //支付回调
    public function notify(){
  
        //通用通知接口
        $notify = new WxPayResults();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->FromXml($xml);
        //回调数据转Array
        $xmlArray = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        
        //解析返回数据
        $appid=$xmlArray['appid'];//微信appid
        $mch_id=$xmlArray['mch_id'];  //商户号
        $device_info=$xmlArray['device_info']; //设备号
        $nonce_str=$xmlArray['nonce_str'];//随机字符串
        $sign=$xmlArray['sign'];//签名
        $result_code=$xmlArray['result_code'];//业务结果
        $err_code = $xmlArray['err_code'];//错误代码
        $err_code_des = $xmlArray['err_code_des'];//错误代码描述
        $openid=$xmlArray['openid'];//用户标识
        $is_subscribe=$xmlArray['is_subscribe'];//是否关注公众帐号
        $trade_type=$xmlArray['trade_type'];//交易类型，JSAPI,NATIVE,APP
        $bank_type=$xmlArray['bank_type'];//付款银行，银行类型采用字符串类型的银行标识。
        $total_fee=$xmlArray['total_fee'];//订单总金额，单位为分
        $fee_type=$xmlArray['fee_type'];//货币类型，符合ISO4217的标准三位字母代码，默认为人民币：CNY。
        $cash_fee=$xmlArray['cash_fee'];//现金支付金额
        $transaction_id=$xmlArray['transaction_id'];//微信支付订单号
        $out_trade_no=$xmlArray['out_trade_no'];//商户订单号
        $attach=$xmlArray['attach'];//商家数据包，原样返回
        $time_end=$xmlArray['time_end'];//支付完成时间
        
        $Notirs =new WxPayNotifyReply();
        
        //判断签名和返回数据
        if($notify->CheckSign() == true && $result_code == "SUCCESS"){
            $M = M('customer');
            $M1 = M('customer_saverecord');

            //查询原本账户金额
            $where = "openid = '".$openid."'";
            $info = $M->where($where)->find();
            $oldmoney = $info['wallet'];
            $oldallmoney = $info['allmoney'];
            $customerid = $info['customerid'];
            $mobile = $info['mobile'];


            //查询充值金额
            //$where1 = "openid = '".$openid."' and orderid = '".$out_trade_no."' and ispay = 0 ";
            $where1 = "openid = '".$openid."' and orderid = '".$out_trade_no."'";
            $info1 = $M1->where($where1)->find();

            // 写入日志
            $Mlog = M('runlog');
            $log_souce = "Weixinpay_notify";
            $ipaddress = get_client_ip();
            $log_title = " ";
            $logtime = date("Y-m-d H:i:s" , time());
            $logmap['souce'] = $log_souce;
            $logmap['title'] = $log_title;
            $logmap['logtime'] = $logtime;
            $logrst = $Mlog->add($logmap);

            //判断返回的订单号是否有记录
            if(!$info1){
                $Notirs->SetReturn_code('FAIL');
                $Notirs->SetReturn_msg('订单状态更新失败！');
                
            //处于未支付状态
            }elseif($info1["ispay"]==0){
                $saveid = $info1['saveid'];
                $savecost = $info1['cost'];
                $orderid = $info1['orderid'];
                $IsBackprocess = $info1['IsBackprocess'];
                $newmoney = $oldmoney + $savecost;
                $newallmoney = $oldallmoney + $savecost;

                if($IsBackprocess == "0"){

                    $M->startTrans();
                    //更新customer_saverecord表
                    $map1['status'] = 2;
                    $map1['ispay'] = 1;
                    $map1['paytime'] = time();
                    $map1['oldmoney'] = $oldmoney;
                    $map1['newmoney'] = $newmoney;
                    $map1['mchid'] = $mch_id;
                    $map1['sign'] = $sign;
                    $map1['resultcode'] = $result_code;
                    $map1['tracetype'] = $trade_type;
                    $map1['banktype'] = $bank_type;
                    $map1['totalfee'] = $total_fee;
                    $map1['feetype'] = $fee_type;
                    $map1['transactionid'] = $transaction_id;
                    $map1['nonce_str'] = $nonce_str;
                    $map1['IsBackprocess'] = "1";
                    $map1['err_code'] = $err_code;
                    $map1['err_code_des'] = $err_code_des;
                    $map1['cash_fee'] = $cash_fee;
                    $map1['time_end'] = $time_end;
                    
                    $where2 = "saveid =".$saveid;
                    $res1 =$M1->where($where2)->save($map1);
        
                    //更新customer表
                    $map['wallet'] = $newmoney;
                    $map['allmoney'] = $newallmoney;
                    $where3 = "customerid =".$customerid;
                    $res =$M->where($where3)->save($map);
                    if($res>0 && $res1>0){
                        $M->commit();
                        
                        if(C('GIFT_RECORD')==2){
                            $rst = reggift($customerid,$mobile,'3',$savecost,$orderid);
                        }
               
                        $Notirs->SetReturn_code('SUCCESS');
                        $Notirs->SetReturn_msg('OK');
                      
                         
                    }else{
                        $M->rollback();
                        $Notirs->SetReturn_code('FAIL');
                        $Notirs->SetReturn_msg('订单状态更新失败！');
                        
                    }
                }else{
                   $Notirs->SetReturn_code('SUCCESS');
                   $Notirs->SetReturn_msg('OK');
                     
                }
            //支付成功状态
            }else{
                $Notirs->SetReturn_code('SUCCESS');
                $Notirs->SetReturn_msg('OK');
                
            }
        }else{
            $Notirs->SetReturn_code('FAIL');
            $Notirs->SetReturn_msg('签名失败！');
             
        }
        $back_msg = $Notirs->ToXml();
        echo $back_msg;
    }


    //选择支付金额页面
    public function paymoney(){
        $M = M("customer");
        $where = "openid = '".Session('wechatOpenid')."'";
        $res = $M->field("mobile_type")->where($where)->find();
        $tel = substr(Session('mobile'),0,3)." ".substr(Session('mobile'),3,4)." ".substr(Session('mobile'),7,4);
        $this->assign("tel",Session('mobile'));
        $this->assign("type",$res["mobile_type"]);
        $this->display();
    }


    //预写入数据库
    public function saverecord(){

         //获取参数
        $paymoney = $_POST['paymoney'];
        $totalfee = (int)$paymoney *100;
        $tradeno = build_order_no();
        $timestart = time();



        //预写入数据库
        $M = D('customer_saverecord');
        $M->startTrans();
        //获取custonid
        $customerid = Session('customerid');
        $openId = Session('wechatOpenid');
        if(empty($customerid)){
            $M1 = M('customer');
            $where1 = "openid = '".$openId."'";
            $info = $M->field('customerid')->where($where)->find();
            $customerid = $info['customerid'];
        }
        //写入数据库
        $map['customerid'] = $customerid;
        $map['cost'] = $totalfee;
        $map['status'] = "1";
        $map['channel'] = "1";
        $map['ispay'] = "0";
        $map['orderid'] = $tradeno;
        $map['savetime'] = $timestart;
        $map['money_resource'] = "1";
        $map['openid'] = $openId;
        $rst = $M->add($map);

        if($rst>0){
            $M->commit();
            redirect(U('Weixinpays/index'));
        }else{
            $M->rollback();
            $msg = "对不起！数据库错误";
            $this->error($msg);
        }
    }
 	



    public function xmlToArray($xml){       
        //将XML转为array        
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);      
        return $array_data;
    }

}