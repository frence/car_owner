<?php

class WeixinpaysAction extends Action {
	 //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('Weixinpay.WxPayJsApiPay');
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
        $notifyurl = "http://wap.yizizhu.cn/index.php/Weixinpays/notifybak/";

        
        //2、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($title);
        $input->SetAttach($title);
        $input->SetOut_trade_no($tradeno);//流水号
        $input->SetTotal_fee($totalfee);//正式金额
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
    //回调
    Public function notifybak(){
        //这里没有去做回调的判断，可以参考手机做一个判断。
        echo "success";
    }


    public function notify(){
        //1、获取openid
        $tools = new JsApiPay();
        $openid = $tools->GetOpenid();
        
        $M = D('customer');
        $M1 = D('customer_saverecord');

        //查询原本账户金额
        $where = "openid = '".$openid."'";
        $info = $M->where($where)->find();
        $oldmoney = $info['wallet'];
        $oldallmoney = $info['allmoney'];
        $customerid = $info['customerid'];
        $mobile = $info['mobile'];


        //查询充值金额
        $where1 = "openid = '".$openid."'";
        $info1 = $M1->where($where1)->order('savetime desc')->find();
        $savecost = $info1['cost'];
        $orderid = $info1['orderid'];
        $IsBackprocess = $info1['IsBackprocess'];
        $newmoney = $oldmoney + $savecost;
        $newallmoney = $oldallmoney + $savecost;

        $M->startTrans();
        if($IsBackprocess == "0"){
            //更新customer_saverecord表
            $map1['saveid'] = $info1['saveid'];
            $map1['status'] = "2";
            $map1['ispay'] = "1";
            $map1['paytime'] = time();
            $map1['oldmoney'] = $oldmoney;
            $map1['newmoney'] = $newmoney;
            $map1['mchid'] = $mch_id;
            $map1['sign'] = $sign;
            $map1['resultcode'] = $result_code;
            $map1['tracetype'] = $trace_type;
            $map1['banktype'] = $bank_type;
            $map1['totalfee'] = $total_fee;
            $map1['feetype'] = $fee_type;
            $map1['transactionid'] = $transaction_id;
            $map1['nonce_str'] = $nonce_str;
            $map1['IsBackprocess'] = "1";
            $res1 =$M1->save($map1);

            //更新customer表
            $map['customerid'] = $customerid;
            $map['wallet'] = $newmoney;
            $map['allmoney'] = $newallmoney;
            $res =$M->save($map);
            // file_put_contents("Weixinpays.log", $res);
            if($res>0 && $res1>0){
                $M->commit();
                if(C('GIFT_RECORD')==2){
                     $rst = reggift($customerid,$mobile,'3',$savecost,$orderid);
                }
            }else{
                $M->rollback();
            }

        }else{
           $M->rollback(); 
        }

    }


    //选择支付金额页面
    public function paymoney(){
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


    //回调测试
    //回调
    public function notifyy(){

        //这里没有去做回调的判断，可以参考手机做一个判断。
        $xmlObj=simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA']); //解析回调数据
        //解析返回数据
        $appid=$xmlObj->appid;//微信appid
        $mch_id=$xmlObj->mch_id;  //商户号
        $nonce_str=$xmlObj->nonce_str;//随机字符串
        $sign=$xmlObj->sign;//签名
        $result_code=$xmlObj->result_code;//业务结果
        $openid=$xmlObj->openid;//用户标识
        $is_subscribe=$xmlObj->is_subscribe;//是否关注公众帐号
        $trace_type=$xmlObj->trade_type;//交易类型，JSAPI,NATIVE,APP
        $bank_type=$xmlObj->bank_type;//付款银行，银行类型采用字符串类型的银行标识。
        $total_fee=$xmlObj->total_fee;//订单总金额，单位为分
        $fee_type=$xmlObj->fee_type;//货币类型，符合ISO4217的标准三位字母代码，默认为人民币：CNY。
        $transaction_id=$xmlObj->transaction_id;//微信支付订单号
        $out_trade_no=$xmlObj->out_trade_no;//商户订单号
        $attach=$xmlObj->attach;//商家数据包，原样返回
        $time_end=$xmlObj->time_end;//支付完成时间
        $cash_fee=$xmlObj->cash_fee;
        $return_code=$xmlObj->return_code;

        $M = D('customer');
        $M1 = D('customer_saverecord');

        //查询原本账户金额
        $where = "openid = '".$openid."'";
        $info = $M->where($where)->find();
        $oldmoney = $info['wallet'];
        $oldallmoney = $info['allmoney'];
        $customerid = $info['customerid'];
        $mobile = $info['mobile'];


        //查询充值金额
        $where1 = "openid = '".$openid."'";
        $info1 = $M1->where($where1)->order('savetime desc')->find();
        $savecost = $info1['cost'];
        $orderid = $info1['orderid'];
        $IsBackprocess = $info1['IsBackprocess'];
        $newmoney = $oldmoney + $savecost;
        $newallmoney = $oldallmoney + $savecost;

        $M->startTrans();
        if($IsBackprocess == "0"){
            //更新customer_saverecord表
            $map1['saveid'] = $info1['saveid'];
            $map1['status'] = "2";
            $map1['ispay'] = "1";
            $map1['paytime'] = time();
            $map1['oldmoney'] = $oldmoney;
            $map1['newmoney'] = $newmoney;
            $map1['mchid'] = $mch_id;
            $map1['sign'] = $sign;
            $map1['resultcode'] = $result_code;
            $map1['tracetype'] = $trace_type;
            $map1['banktype'] = $bank_type;
            $map1['totalfee'] = $total_fee;
            $map1['feetype'] = $fee_type;
            $map1['transactionid'] = $transaction_id;
            $map1['nonce_str'] = $nonce_str;
            $map1['IsBackprocess'] = "1";
            $res1 =$M1->save($map1);

            //更新customer表
            $map['customerid'] = $customerid;
            $map['wallet'] = $newmoney;
            $map['allmoney'] = $newallmoney;
            $res =$M->save($map);
            // file_put_contents("Weixinpays.log", $res);
            if($res>0 && $res1>0){
                $M->commit();
                if(C('GIFT_RECORD')==2){
                     $rst = reggift($customerid,$mobile,'3',$savecost,$orderid);
                }
            }else{
                $M->rollback();
            }

        }else{
           $M->rollback(); 
        }

        

        
        echo "success";
    }

}