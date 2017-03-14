<?php

//后台Index模块
class IndexAction extends Action {

    //在类初始化方法中，引入相关类库
    public function _initialize() {
        vendor('Weixinpay.WxPayJsApiPay');
    }

    /* 验证微信openid及登录 */

    public function index() {
        //1、获取openid
        $tools = new JsApiPay();
        $wechatOpenid = $tools->GetOpenid();

        //根据wechatOpenid查找数据库；
        if (!empty($wechatOpenid)) {
            $M = M('accountinfo');
            $info = $M->where(array('openid' => $wechatOpenid, 'status' => "1"))->limit(1)->select();
            if ($info > 0) {
                $accountid = $info[0]['accountid'];
                $M1 = M('customer');
                $info1 = $M1->where(array('accountid' => $accountid))->limit(1)->select();
                //将所需参数放入session
                Session('wechatOpenid', $wechatOpenid);
                Session('accountid', $accountid);
                Session('customerid', $info1[0]['customerid']);
                Session('mobile', $info[0]['mobile']);
                Session('nickname', $info[0]['nickname']);
                $this->redirect('Public/index');
            } else {
                $send_code = random(6, 1);
                Session('Send_Code', $send_code);
                Session('wechatOpenid', $wechatOpenid);
                $this->assign('send_code', $send_code);
                $this->display();
            }
        } else {
            $msg = "网络忙，请重试！";
            $this->error($msg);
        }
    }

    //提交注册信息
    public function CheckLogin() {
        header("Content-type: text/html; charset=utf-8");

        //注册手机号
        $Mobile = trim($_POST['username']);
        $reg_x = trim($_POST['lat']);
        $reg_y = trim($_POST['lng']);
        $reg_city = trim($_POST['city']);
        $reg_province = trim($_POST['province']);
        //验证码不能为空
        if (empty($_POST['verify'])) {
            echo 7;
            die;
        }
        //验证码是否一致
        if (Session('Mobile_Code') != $_POST['verify']) {
            echo 6;
            die;
        }

        //获取微信OPENID值；
        $wechatOpenid = Session('wechatOpenid');
        $time = time(); //当前时间
        $guid = create_guid(); //获取GUID
        $keycode = C('KEYCODE');  //获取加密KEY
        $md5 = md5($Mobile . $guid . $keycode); //md5加密数据窜
        //定义数据库
        $M = M('accountinfo'); //系统账号信息表
        $M2 = M('customer');  //车主信息表

        $where_account = "Mobile='" . $Mobile . "'";
        //$info  = $M->where(array('Mobile'=>$Mobile))->find();
        $info = $M->where($where_account)->limit(1)->select();
        $M->startTrans();
        if ($info) {
            $openid = $info[0]['openid'];
            file_put_contents("openid.log", $openid);
            if (!empty($openid)) {
                echo 3;
                die;
            } else {

                $accountid = $info[0]['accountid'];
                $where0 = "accountid=" . $accountid;
                //更新account表
                // $map['accountid'] = $accountid;
                $map['openid'] = $wechatOpenid;
                $map['entaccount'] = $md5;
                $map['lasttime'] = $time;
                $save = $M->where($where0)->save($map);

                //添加car_customer表
                $map2['accountid'] = $accountid;
                $map2['mobile'] = $Mobile;
                $map2['openid'] = $wechatOpenid;
                $map2['addtime'] = $time;
                $rst2 = $M2->add($map2);

                //更新    car_accountinfo表
                $map3['accountid'] = $accountid;
                $map3['customerid'] = $rst2;
                $M->save($map3);

                if ($save > 0 && $rst2 > 0) {
                    $M->commit();
                    get_mobile_area($Mobile);
                    checkgift($rst2, $Mobile);
                    if (C('GIFT_RECORD') == 2) {
                        $rst = reggift($rst2, $Mobile, '2', '', '');
                        echo $rst;
                    }
                    //$rsttemp = customer_temp($rst2, $Mobile);
                    Session('wechatOpenid', $wechatOpenid);
                    Session('customerid', $rst2);
                    cookie('wechatOpenid', $wechatOpenid);
                    echo 1;
                    die;
                } else {
                    $M->rollback();
                    echo 2;
                    die;
                }
            }
        } else {
            //添加    car_accountinfo表
            $map['mobile'] = $Mobile;       //手机号       
            $map['nickname'] = "";          //昵称
            $map['email'] = "";
            $map['openid'] = $wechatOpenid;       //openid
            $map['addtime'] = $time;        //添加时间
            $map['status'] = 1;
            $map['entaccount'] = $md5; //用户身份加密串=md5(手机号+GUID+握手KEY)
            $map['ipaddress'] = get_client_ip();
            $rst = $M->add($map);

            //添加car_customer表
            $map2['accountid'] = $rst;
            $map2['mobile'] = $Mobile;
            $map2['openid'] = $wechatOpenid;
            $map2['addtime'] = $time;
            $map2['reg_x'] = $reg_x;
            $map2['reg_y'] = $reg_y;
            $lastword = mb_substr($reg_city, mb_strlen($reg_city) - 1, 1);
            if ($lastword == "市") {
                $map2['reg_city'] = mb_substr($reg_city, 0, mb_strlen($reg_city) - 1);
            } else {
                $map2['reg_city'] = $reg_city;
            }
            $map2['reg_province'] = $reg_province;

            $rst2 = $M2->add($map2);

            //更新    car_accountinfo表
            $map3['accountid'] = $rst;
            $map3['customerid'] = $rst2;
            $M->save($map3);

            if ($rst > 0 && $rst2 > 0) {

                get_mobile_area($Mobile);
                checkgift($rst2, $Mobile);
                if (C('GIFT_RECORD') == 2) {
                    $rst = reggift($rst2, $Mobile, '2', '', '');
                }

                //$rsttemp = customer_temp($rst2, $Mobile);
                Session('wechatOpenid', $wechatOpenid);
                Session('customerid', $rst2);
                cookie('wechatOpenid', $wechatOpenid);
                $M->commit();
                echo 1;
                die;
            } else {
                $M->rollback();
                echo 2;
                die;
            }
        }
    }

    public function treaty() {
        $this->display();
    }

    //发送短信验证码
    public function SendSMS() {
        $mobile = trim($_POST['mobile']);
        $send_code = $_POST['send_code'];
        //获取微信OPENID值；
        $wechatOpenid = Session('wechatOpenid');
        if (empty($mobile)) {
            echo "手机号码不能为空";
        }

        if (empty(Session('Send_Code') or $send_code != Session('Send_Code'))) {
            //防用户恶意请求
            exit('请求超时，请刷新页面后重试' . $send_code);
        }

        $M = M('accountinfo'); //系统账号信息表
        $where_account = "Mobile='" . $mobile . "'";
        $info = $M->where($where_account)->limit(1)->select();
        if ($info) {
            $openid = $info[0]['openid'];
            if (!empty($openid)) {
                echo 3;
                die;
            }
        }


        $mobile_code = random(6, 1);
        // $content = "您的验证码是：".$mobile_code."。该验证码将在3分钟后失效,请不要把验证码泄露给其他人。";
        // $content = $mobile_code;
        //正式环境，暂时注销
        // 写入日志
        $Mlog = M('sms_log');
        $log_sms = array();
        $log_sms["send_time"] = time();
        $log_sms["send_date"] = date("Ymd", time());
        $log_sms["mobile"] = $mobile;
        $log_sms["sms_content"] = "发送验证码：" . $mobile_code;
        $log_sms["last_time"] = time();
        $res_log = $Mlog->add($log_sms);
        unset($log_sms);
        $rst = SendSms($mobile, $mobile_code);
        if ($rst == 1) {
            Session('Mobile', $mobile);
            Session('Mobile_Code', $mobile_code);
        } else {
            $log_sms_fail = array();
            $log_sms_fail["last_time"] = time();
            $log_sms_fail["status"] = 0;
            $log_sms_fail["remark"] = $rst;
            $log_update = $Mlog->where("sms_id =" . $res_log)->save($log_sms_fail);
            unset($log_sms_fail);
        }
        echo $rst;
    }

}
