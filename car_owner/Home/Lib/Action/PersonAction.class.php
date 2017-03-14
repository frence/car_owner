<?php

/*
一些公共方法放此类
因为此类没有继承Common类，故此类中的方法所有角色都可以直接调用
*/

class PersonAction extends CommonAction {

	 //个人中心首页
	 public function index(){
        //若session过期或没登录跳到登录页面
        if(!(Session('wechatOpenid')) ){
            redirect(U('Index/index'));
        }else{
            $M=D('Person');
            //获取未进行评论的洗车记录
             $res_noComment = $M->noComment();
             
            //获取所有洗车记录
            $res_all = $M->allWashrecord();

            //获取所有投诉信息
            $res_all_ad = $M->alladvice();

            //获取所有投诉信息
            $res_no_reply = $M->adviceNoReply();

            $this->assign("list_nocommnet",$res_noComment);
            $this->assign("list_all",$res_all);
            $this->assign("list_all_ad",$res_all_ad);
            $this->assign("list_no_reply",$res_no_reply);

            $this->display();
        }

	 	
	 }



	 //个人资料
    public function profile(){
    	header("content-type:text/html;charset=utf-8");
    	$customerid = Session('customerid');
    	$M =M("customer");
    	$res =$M->where(array('customerid' =>$customerid))->find();
    	$this->assign("msg",$res); 
    	$this->display();
    }

	 //消息通知
    public function message(){
        //若session过期或没登录跳到登录页面
        if(!(Session('wechatOpenid')) ){
            redirect(U('Index/index'));
        }

        $customerid = Session('customerid');
        $M =M("message");
        $res = $M->where(array("RevId"=>$customerid,"RevTypeId"=>'1',"SendStatus"=>'1',"RevStatus"=>'0'))->select();
        if(!empty($res)){
           $this->assign("msg",$res);  
        }else{
            $message = '1';
            $this->assign('message',$message);
        }
    	
    	$this->display();
    }

    //我的评价
    public function evaluate(){
        //若session过期或没登录跳到登录页面
        if(!(Session('wechatOpenid')) ){
            redirect(U('Index/index'));
        }

       $customerid = Session('customerid');
       $M = M('score'); 
       $sql ="select a.*,b.ParkName from (select * from car_score where customerid = '".$customerid."') as a left join car_portinfo as b on a.CurrentParkId = b.ParkId";
       $list = $M->query($sql);
       $this->assign('list',$list);
       $this->display();
    }


     //意见反馈
    public function advice(){
    	if(!$_POST){
            //若session过期或没登录跳到登录页面
            if(!(Session('wechatOpenid')) ){
                redirect(U('Index/index'));
            }
    		$this->display();
    	}else{
    		$M = M("useridea");
    		$content = $_POST['content'];
    		$name = Session('nickname');
    		$tel  = Session('mobile');
            $openid = Session('wechatOpenid');
    		$data['booktime'] = time();
            $data['mobile'] = $tel;
    		$data['content'] = $content;
    		$data['status'] = 0;
    		$data['remark'] = '意见反馈';
            $data['openid'] = $openid;
    		$res = $M->add($data);
    		if($res){
                $cig = C("THINK_EMAIL");
                $map ='来自手机号：'.$tel .'的意见反馈如下：'.$content.'<br />请及时处理！！！';
                think_send_mail($cig['Receiver_email'],$cig['Receiver_name'],'意见反馈',$map);
    			echo 1;
            
                die;
    		}else{
    			echo 0;
                die;
    		}
 
    	}
    }


    //修改密码
	 public function updatepas(){
        //若session过期或没登录跳到登录页面
       	header("Content-type: text/html; charset=utf-8");
		$wechatOpenid = Session('wechatOpenid');
		if(!empty($wechatOpenid)){
			$M = M('accountinfo');
			$where = "openid = '".$wechatOpenid."'";
			$info = $M->field('mobile')->where($where)->limit(1)->select();
			$this->assign('info', $info[0]);
			$send_code = random(6,1);
    		Session('Send_Code', $send_code);
    		$this->assign('send_code', $send_code);
			$this->display();
		}else{
			$url = U("Public/index");
			$this->error('Sorry，Openid is null!',$url,5);
		}
	}

	//通过短信验证码修改密码
	public function passwordbyverify(){
		if ($_POST){
			$mobile =$_POST["username"];
			$newpwd =$_POST["newpas"];
			$reverify =$_POST["verify"];
			$send_code =$_POST["send_code"];
			if(Session('Send_Code')!=$send_code){				 
				$this->error("非法验证，修改失败!","",3);
			}
			if(empty(Session('Mobile_Code')))
				$this->error("验证码过期，请重新点击发送","",3);
			if($reverify!=Session('Mobile_Code')){
				 
				$this->error("验证码错误!","",3);
			}
			if($reverify==Session('Mobile_Code')){
				$M =M("accountinfo");
				$date['password'] = pwdHash($newpwd);
            	$M->where(array('mobile'=>$mobile))->save($date);
            	//清空session
            	// session_destroy();
				$this->success("修改密码成功!",U("Person/index"));

			}
			 

    	}
    	else{
    		$send_code = random(6,1);
    		Session('Send_Code', $send_code);
    		$this->assign('send_code', $send_code);

    		$this->display();
    	}
	}

    //发送短信验证码
	public function SendSMS(){
    	$mobile = $_POST['mobile'];
    	$send_code = $_POST['send_code'];
    	if(empty($mobile)){
    		echo "手机号码不能为空";
    	}

    	if(empty(Session('Send_Code') or $send_code != Session('Send_Code'))){
    		//防用户恶意请求
    		exit('请求超时，请刷新页面后重试'.$send_code);
    	}
		$mobile_code = random(6,1);
		$content = "您的验证码是：".$mobile_code."。请不要把验证码泄露给其他人。";
		//正式环境，暂时注销
		$rst = SendSms($mobile,$content);
		//测试用例
		// $rst = 1;
		if ($rst == 1){
			Session('Mobile',$mobile);
			Session('Mobile_Code',$mobile_code);
		}
		echo $rst;		

	}

    //历史意见反馈
    public function advicepast(){
        $M = M("useridea");
        $tel  = Session('mobile');
        $openid = Session('wechatOpenid');
        $where = 'mobile ="'.$tel.'" and openid ="'.$openid .'"  ';

        $res = $M->where($where)->order("booktime DESC ")->select(); 
        if($res){
            $this->assign("list",$res);
            $this->assign("message",1);
        }else{
            $this->assign("message",0);
        }
        $this->display();
    }

}