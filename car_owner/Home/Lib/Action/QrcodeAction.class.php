<?php

class QrcodeAction extends Action {

    //我的账户
    public function qrcode() {

        header("Content-type: text/html; charset=utf-8");
        //查询账户余额
        $wechatOpenid = Session('wechatOpenid');

        $M = M('customer');
        $M_gift = M('giftrecord');
        $where = "openid = '" . $wechatOpenid . "'";
        $info = $M->field('allmoney,status,mobile,balance_gift,customerid')->where($where)->find();

        if ($info['status'] == 1) {
            if (!empty($wechatOpenid)) {

                $allmoney = (int) $info['allmoney'] / 100;
                $this->assign('allmoney', $allmoney);
                $count = $M_gift->where("customerid ='".$info["customerid"]."' and status =1")->count();
                //$this->assign('gift', $info['balance_gift']);
                $this->assign('gift', $count);
                $this->assign('qrcode_end_time', C("qrcode_end_time"));
                $this->display();
            } else {
                $url = U("Public/index");
                $this->error('对不起！数据库查询出错！', $url, 20);
            }
        } else {
            $M1 = M("log");
            $tmp['titile'] = '冻结原因';
            $tmp['group_name'] = 'owner';
            $tmp['module_name'] = 'Qrcode';
            $tmp['function_name'] = 'qrcode';
            $tmp['new_content'] = 'openid为' . $wechatOpenid . ',手机号码为' . Session('mobile') . '
			,状态值为未知';
            $tmp['account'] = Session('mobile');
            $tmp['op_time'] = date('Y-m-d h:i', time());

            $res = $M1->add($tmp);
            $this->redirect('Qrcode/nomessage');
        }
    }

    //生成二维码图片
    public function addrssBook() {
        header("Content-type: text/html; charset=utf-8");
        //查询账户余额
        $wechatOpenid = Session('wechatOpenid');
        $customerid = Session('customerid');
        // echo $wechatOpenid;
        if (!empty($wechatOpenid)) {
            // $M1 = M('accountinfo');
            // $where1 = "openid='".$wechatOpenid."' ";
            // $info = $M1->field('entaccount')->where($where1)->find();
            // $str = bin2hex($info['entaccount'])."|";
            $time_value = C("qrcode_end_time");
            $str = create_customer_rand($customerid, $wechatOpenid, $time_value);
            setQRImg(bin2hex($str) . "|");
        } else {
            $url = U("Index/index");
            $this->success('登录超时，请重新登录！', $url, 5);
        }
    }

    public function nomessage() {
        header("Content-type: text/html; charset=utf-8");
        $this->display();
    }

}
