<?php

/*
  一些公共方法放此类
  因为此类没有继承Common类，故此类中的方法所有角色都可以直接调用
 */

class PublicAction extends Action {

    public function index() {
        header("Content-Type: image/*;");
        header("Cache-Control: max-age=86400,must-revalidate;");
        $M = M("pageflash");
        $res = $M->where(array("ChangeId" => "0002"))->order('PageId desc')->select();
        $this->assign("flash", $res);

        $M1 = M('giftrecord');
        $M2 = M('business');
        $M3 = M("announce");
        $customerid = Session('customerid');
        /*         * *********礼券/卡券信息提醒*********** */
        $list_card = array(); //卡券
        $list_gift = array(); //礼券

        $where = 'customerid="' . $customerid . '" and status=1 and isread= 0 and gift_id = 0';
        $list = $M1->where($where)->order('addtime desc')->select();
        $record_count = count($list);
        if ($record_count > 0) {
            foreach ($list as $k => $v) {
                //读取商户名称
                $list2 = $M2->where(array('businessid' => $v['businessid']))->find();
                $list[$k]['businessname'] = $list2['businessname'];
                if ($list[$k]['enddate'] == 0) {
                    $list[$k]['enddate'] = '长期有效';
                } else {
                    $list[$k]['enddate'] = date('Y-m-d', $list[$k]['enddate']);
                }

                $list[$k]['Cost'] = (int) $v['Cost'] / 100;
                //将状态改成已领
                $map['GiftId'] = $v['GiftId'];
                $map['isread'] = "1";
                $M1->save($map);
            }
        }
        $sql = "select A.*,B.gift_name,B.company_name from (select * from car_giftrecord where customerid = '" . $customerid . "' and gift_id <> 0 and isread = 0) as A  left join car_gift_info as B on A.gift_id =B.gift_id ";
        $list2 = $M1->query($sql);
        foreach ($list2 as $k => $v) {

            if ($list2[$k]['enddate'] == 0) {
                $list2[$k]['enddate'] = '长期有效';
            } else {
                $list2[$k]['enddate'] = date('Y-m-d', $list2[$k]['enddate']);
            }

            $list2[$k]['Cost'] = (int) $v['Cost'] / 100;
            //将状态改成已领
            $map['GiftId'] = $v['GiftId'];
            $map['isread'] = "1";
            $M1->save($map);
        }
        /*         * *********礼券/卡券信息提醒 END*********** */


        /*         * *******************查看是否有公告********** */
        $res_notice = $M3->where("status =1")->order("id DESC")->limit(1)->select();

        $this->assign('notice', $res_notice[0]);
        /*         * *******************查看是否有公告END********** */

        $this->assign('list_card', $list2[0]);
        $this->assign('list_gift', $list[0]);
        $this->display();
    }

    public function profile() {
        if (!isset($_SESSION['loginUserName'])) {/* Session部存在 */
            $this->redirect('Public/login');
        } else {

            $Mobile = $_SESSION['loginUserName'];
            $M = D('Accountinfo');
            if ($_POST) {
                $data['Nickname'] = $_POST['Nickname'];
                $data['Email'] = $_POST['Email'];
                $where = "Mobile = '" . $Mobile . "'";
                $rst = $M->where($where)->save($data);
                if ($rst >= 0) {
                    $url = U("Index/index");
                    $this->success('资料修改成功', $url, 3);
                } else {
                    echo "<script>history.go(-1);alert('修改失败');</script>";
                }
            } else {
                $this->assign('Mobile', $Mobile);
                $where = "Mobile = '" . $Mobile . "'";
                $info = $M->where($where)->limit(1)->select();

                $this->assign('info', $info[0]);
                $this->display();
            }
        }
    }

    //修改密码
    public function password() {
        if (!isset($_SESSION['loginUserName'])) {/* Session部存在 */
            $this->redirect('Public/login');
        } else {
            $Mobile = $_SESSION['loginUserName'];
            if ($_POST) {
                $newpassword = $_POST['newpassword'];
                $repassword = $_POST['repassword'];
                if ($newpassword == $repassword) {
                    $M = D('Accountinfo');
                    $where = "Mobile=" . $Mobile;
                    $newpwd = pwdHash($newpassword);
                    $data['PWD'] = $newpwd;
                    $rst = $M->where($where)->save($data);
                    if ($rst > 0) {
                        $url = U("Index/index");
                        $this->success('密码修改成功', $url, 3);
                    } else {
                        $this->error('密码修改失败！', '', 20);
                    }
                } else {
                    echo "<script>history.go(-1);alert('新密码和验证密码不一致');</script>";
                }
            } else {
                $this->assign('Mobile', $Mobile);
                $this->display();
            }
        }
    }

    /* 验证码 */

    public function verify() {
        ob_clean();
        import('ORG.Util.Image');
        Image::buildImageVerify(4, 1, gif, 97, 42, 'verify');
    }

    /* 登录表单 */

    public function login() {
        $cookie = cookie('uid');
        if (!isset($_SESSION['username'])) {/* Session不存在 */
            /* 检查Cookie是否有效 */
            if (!empty($cookie)) {
                /* 根据Cookie写Session保证其他地方的判断不会出错 */
                cookie('username', $_SESSION[('username')]);
            } else {
                $this->redirect('Public/register');
                exit();
            }
            $this->redirect('Public/register');
        } else {
            $this->redirect('Public/register');
        }
    }

    /* 注销登出 */

    public function logout() {

        /* 释放Session */
        session_destroy();
        /* 删除Cookie */
        //cookie(null);
        //$url = U('Public/login');
        $url = U("Public/login");
        $this->redirect($url);
    }

    /* 登录检查，处理登录表单 */

    public function checkLogin() {
        $username = $_POST['username'];
        $password = $_POST['password'];


        if ($username == '' || $password == '') {/* 为空跳到默认网关 */
            $this->redirect('Public/login');
        }


        if (empty($_POST['verify'])) {
            echo "<script>history.go(-1);alert('验证码必须');</script>";
        }

        if ($_SESSION['verify'] != md5($_POST['verify'])) {
            $this->error('验证码错误！');
        }

        $M = M("car_accountinfo");
        /* 判断帐号、密码] */
        $User = $M->where(array('Mobile' => $username))->find();
        $newpwd = pwdHash($password);
        if (!$User || $newpwd != $User['PWD']) {
            $this->error("用户名或密码错误！", '', 20);
        }

        if (!$User['Status']) {
            $this->error("帐号被锁定，请稍后再试！", '', 20);
        } else {
            /* 身份验证通过 */
            $date['ipaddress'] = get_client_ip();
            $date['LastTime'] = time();
            /* 开始写登录日志 */
            if ($M->where(array('UserId' => $User['UserId']))->save($date)) {
                /* 更新登录次数 */
                $M->where(array('UserId' => $User['UserId']))->setInc('LoginNum');
            }


            unset($_SESSION['verify']); /* 释放掉验证码Session */
            /* 写Session */
            $_SESSION['loginUserName'] = $User['Mobile'];
            $_SESSION['username'] = $User['Nickname'];

            /* 写Cookies记住登录 */
            if (I('remember') == '1') {
                //cookie('uid',$_SESSION[C('AUTH_CONFIG.USER_AUTH_KEY')]);
                cookie('username', $_SESSION[('username')]);
            }
            $this->success("登录成功！", U("Index/index"));
        }
    }

    //展示注册页
    public function register() {
        header("Content-type: text/html; charset=utf-8");
        $send_code = random(6, 1);
        // $send_code = "dd";
        Session('Send_Code', $send_code);
        $this->assign('send_code', $send_code);
        $this->display();
    }

    //发送注册短信验证码
    public function SendSMSforReg() {
        $mobile = $_POST['mobile'];
        $send_code = $_POST['send_code'];
        if (empty($mobile)) {
            echo "手机号码不能为空";
        }

        if (empty(Session('Send_Code') or $send_code != Session('Send_Code'))) {
            //防用户恶意请求
            exit('请求超时，请刷新页面后重试' . $send_code);
        }

        $mm = D('accountinfo');
        $condition = "Mobile='" . $mobile . "'";
        $info = $mm->where($condition)->limit(1)->select();
        if ($info) {
            echo "对不起！该手机号码" . $mobile . "已注册";
        } else {
            $mobile_code = random(6, 1);
            $content = "您的验证码是：" . $mobile_code . "。请不要把验证码泄露给其他人。";
            //正式环境，暂时注销
            $rst = SendSms($mobile, $content);
            //测试用例
            //$rst = 1;
            if ($rst == 1) {
                Session('Mobile', $mobile);
                Session('Mobile_Code', $mobile_code);
            }
            echo $rst;
        }
    }

    //发送短信验证码
    public function SendSMS() {
        $mobile = $_POST['mobile'];
        $send_code = $_POST['send_code'];
        if (empty($mobile)) {
            echo "手机号码不能为空";
        }

        if (empty(Session('Send_Code') or $send_code != Session('Send_Code'))) {
            //防用户恶意请求
            exit('请求超时，请刷新页面后重试' . $send_code);
        }
        $mobile_code = random(6, 1);
        // $content = "您的验证码是：".$mobile_code."。该验证码将在3分钟后失效,请不要把验证码泄露给其他人。";
        // $content = $mobile_code;
        //正式环境，暂时注销
        $rst = SendSms($mobile, $mobile_code);
        //测试用例
        // $rst = 1;
        if ($rst == 1) {
            Session('Mobile', $mobile);
            Session('Mobile_Code', $mobile_code);
        }
        echo $rst;
    }

}
