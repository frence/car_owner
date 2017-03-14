<?php

class AccountAction extends CommonAction {
	//我的账户
	public function account(){

		header("Content-type: text/html; charset=utf-8");
		//查询账户余额
		$wechatOpenid = Session('wechatOpenid');
		$customerid = Session('customerid');
		$skin = "2";
		if($_GET){
			$skin = $_GET['skin'];
		}
		if(!empty($wechatOpenid) && !empty($customerid)){
			$M = M('customer');
			$where = "openid = '".$wechatOpenid."'";
			$info = $M->field('customerid,wallet,allmoney,othermoney,balance_gift')->where($where)->find();
			$info['wallet'] = (int)$info['wallet']/100;
			$info['allmoney'] = (int)$info['allmoney']/100;
			$info['othermoney'] = (int)$info['othermoney']/100;
			$customerid = $info['customerid'];
			Session('customerid',$customerid);

			/*************查询可用礼券********/
			$M1 = M('giftrecord');
			$sql = "select a.*,b.businessname from
			(select * from car_giftrecord where customerid = '".$customerid."'     and gift_id = 0 ) as a left join car_business as b
			on   a.businessid = b.businessid order by a.enddate asc";
			$list = $M1->query($sql);

			foreach($list as $k => $v){
				//时间格式化
				$list[$k]['enddate'] = date('Y-m-d',$list[$k]['enddate']);
				$list[$k]['Cost'] = (int)$v['Cost']/100;
				$list[$k]['balance'] =(int)$v['balance']/100;
			}
			$countrecord = $M1->where(array('customerid' =>$customerid,'status' =>'1','gift_id'=>0))->count();
			/*************查询可用礼券END********/


			/*************卡券***************/
			$sql_card_gift = "select A.*,B.gift_name,B.company_name from (select * from car_giftrecord where customerid = '".$customerid."' and gift_id <> 0 order by addtime DESC) as A  left join car_gift_info as B on A.gift_id =B.gift_id ";


			$list_card_gift = $M1->query($sql_card_gift);
			//将能使用和不能使用的卡券进行分离
			$list_card_gift_Y = array();
			$list_card_gift_N = array();
			$list_card_gift_O = array();
			foreach ($list_card_gift as $key => $value) {
				 //可使用卡券
				 if($value['status']==1){
				 	array_push($list_card_gift_Y, $list_card_gift[$key]);
				 //过期卡券
				 }elseif($value['status']==2){
				 	array_push($list_card_gift_N, $list_card_gift[$key]);
				 //已使用卡券
				 }else{
				 	array_push($list_card_gift_O, $list_card_gift[$key]);
				 }
			}

			/*************卡券END*************/


			/*************福利处理************/
			$M2 = M('gift_batch');
			$where = 'status =1 and ((is_check =1 and check_result=1) or is_check =0) and no_use_num >0';
			$list_Fuli = $M2->field('package_name,batch_code,batch_no,activity_idea,batch_pic')->where($where)->select();

			 


			/*************福利处理END************/


			$this->assign('list',$list);
			$this->assign('list_Fuli',$list_Fuli);
			$this->assign('list_card_gift_Y',$list_card_gift_Y);
			$this->assign('list_card_gift_N',$list_card_gift_N);
			$this->assign('list_card_gift_O',$list_card_gift_O);
			$this->assign('countrecord',$countrecord);
			$this->assign('info', $info);
			$this->assign('skin', $skin);
			$this->display();
		}else{
			// $url = U("Index/index");
			// $this->error('',$url,0);
			redirect(U('Index/index'));
		}
		
	}

	//订单评价
	public function carscore(){
	if ($_POST){
			//获取评价信息并写入数据库
			$parkname = $_POST["parkname"];
			$customerid = Session('customerid');
			$currentparkid = $_POST["currentparkid"];
			$currentownerid = $_POST["currentownerid"];
			$washerid = $_POST["washerid"];
			$washrecordid = $_POST["washrecordid"];
			$textarea = $_POST["textarea"];
			$score = $_POST["score"];
			$M = M('score');
			$M1 = M('washer');
			$M2 = M('portinfo');
			$info = $M->where(array('washrecordid' =>$washrecordid))->count();
			if($info == "0"){
				$date['washerid'] = $washerid;
				$date['customerid'] = $customerid;
				$date['washrecordid'] = $washrecordid;
				$date['Title'] = $textarea;
				$date['Score'] = $score;
				$date['addtime']= time();
				$date['IPaddress'] = get_client_ip();
				$date['CurrentParkId'] = $currentparkid;
				$date['CurrentOwnerId']  = $currentownerid;
				$rst = $M->add($date);

				//洗车机评价信息更新
				$avgscore = $M->where(array('washerid'=>$washerid))->avg('Score');
				$ceilscore = ceil($avgscore);
				//更新入库	
				$where1 = "washerid=".$washerid;				
				$map['washerid'] = $washerid;
				$map['score'] = $ceilscore;
				$M1->where($where1)->save($map);

				//洗车场评价信息更新
				$avgportinfo = $M->where(array('CurrentParkId'=>$currentparkid))->avg('Score');
				$ceilportinfo = ceil($avgportinfo);
				//更新洗车场信息表
				$where2 = "ParkId=".$currentparkid;
				$map2['ParkId'] = $currentparkid;
				$map2['Score'] = $ceilportinfo;
				$M2->where($where2)->save($map2);


				if($rst >0){				
					echo "1";
				}else{
					echo "0";
				}
			}else{
				echo "2";
			}
			
		}else{
			$this->assign('parkname',$_GET["parkname"]);
			$this->assign('currentparkid',$_GET["currentparkid"]);
			$this->assign('currentownerid',$_GET["currentownerid"]);
			$this->assign('washerid',$_GET["washerid"]);
			$this->assign('washrecordid',$_GET["washrecordid"]);
			$this->display();
		}





	}




	//账户明细
	public function zhmx(){
			//消费纪录
			$customerid = Session('customerid');
			$M2 = M('washrecord');
			$M3 = M('score');			
			$sql2 = "select a.*, b.ParkName from 
				(select * from car_washrecord
				 where customerid = ".$customerid."
				 and status = 1 order by addtime desc) as a left join car_portinfo as b
				 on a.CurrentParkId = b.ParkId";
			$list2 = $M2->query($sql2);
			//填充洗车数据
			foreach($list2 as $k => $v){
	
				//时间格式化
				$list2[$k]['times'] = date('Y-m-d H:i:s',$list2[$k]['addtime']);
				$list2[$k]['cost'] = (int)$v['cost']/100;

				//是否已评价
				$scoreinfo = $M3->where(array('washrecordid' =>$v['washrecordid']))->find();
				if($scoreinfo>0){
					$list2[$k]['classtype'] = "yp";
					$list2[$k]['ScoreId'] = $scoreinfo['ScoreId'];
				}else{
					$list2[$k]['classtype'] = "wp";
				}
			}

			//查询充值记录表
			$M4 = M('customer_saverecord');
			$list3 = $M4->where(array('customerid' => $customerid,'ispay' => '1'))->order('savetime desc')->select();
			foreach($list3 as $k => $v){
				//时间格式化
				$list3[$k]['times'] = date('Y-m-d H:i:s',$list3[$k]['paytime']);
				$list3[$k]['cost'] = (int)$v['cost']/100;
				$channel = $list3[$k]['channel'];
				if($channel == "1"){
					$list3[$k]['channelname'] = "微信充值";
				}else if($channel == "2"){
					$list3[$k]['channelname'] = "支付宝充值";
				}else if($channel == "3"){
					$list3[$k]['channelname'] = "银联充值";
				}else if($channel == "4"){
					$list3[$k]['channelname'] = "系统退款";
				}else if($channel == "5"){
					$list3[$k]['channelname'] = "系统充值";
				}else if($channel == "6"){
					$list3[$k]['channelname'] = "补偿退款";
				}
			}

			//查询礼券扣款详情
			$M1 = M('consume_record');
			$list1 = $M1->where(array('customerid' =>$customerid,'status' =>'1'))->order('addtime desc')->select();
			foreach($list1 as $k => $v){
				//时间格式化
				$list1[$k]['addtime'] = date('Y-m-d H:i:s',$list1[$k]['addtime']);
				$list1[$k]['consume_money'] = (int)$v['consume_money']/100;
			}

			if(!empty($list1)){
				$this->assign('list1',$list1);
			}else{
				$message = '1';
				$this->assign('message1',$message);
			}
			if(!empty($list2)){
				$this->assign('list2',$list2);
			}else{
				$message = '2';
				$this->assign('message2',$message);
			}
			if(!empty($list3)){
				$this->assign('list3',$list3);
			}else{
				$message = '3';
				$this->assign('message3',$message);
			}
			$this->display();

	}


	//跳转链接
	public function sysm(){
		$this->display();
	}

	//查询评价信息
	public function appraise(){
		$ScoreId = $_GET['ScoreId'];
		$parkname = $_GET['parkname'];
		$M = M('score');
		$info = $M->where(array('ScoreId' => $ScoreId))->find();
		$info['ParkName'] = $parkname;
		$this->assign('info',$info);
		$this->display();
	}

	//跳转链接
	public function test(){
		$this->initWechatJS();
		$this->assign('signPackage', $this->wechatJS->GetSignPackage());
		$this->display();
	}

	//卡券使用页面
	public function cardgift(){
		$gift_id = $_GET['id'];
		$this->assign('gift_id',$gift_id);
		$this->display();
	}

	//卡券生成二维码

	public function formCode(){
		header("Content-type: text/html; charset=utf-8");
		$gift_id = $_GET['id'];
		
		//查询账户余额
		$wechatOpenid = Session('wechatOpenid');
		$customerid = Session('customerid');
		if(!empty($wechatOpenid)){
			$M1 = M('giftrecord');
			$temp = $M1->field("giftnum")->where(array("GiftId"=>$gift_id))->find();
			// $where1 = "openid='".$wechatOpenid."' ";
			// $info = $M1->field('entaccount')->where($where1)->find();
			$str = bin2hex($temp['giftnum'])."!";
			
			setQRImg($str);
		}else{
			$url = U("Index/index");
			$this->success('登录超时，请重新登录！',$url,5);
		}
	}

	//根据福利码领取福利-------已舍弃
	public function  drawFuli(){
		header("Content-type: text/html; charset=utf-8");
		$gift_id = $_POST['id'];     //福利券号
		$gift_code = $_POST['code']; //福利号
		$tel = $_POST['tel'];		//手机号

		echo send_package($gift_code,$tel);
	}
	public function  drawFuli_old(){
		header("Content-type: text/html; charset=utf-8");
		$gift_id = $_POST['id'];     //福利券号
		$gift_code = $_POST['code']; //福利号
		$tel = $_POST['tel'];		//手机号

		//根据手机号确认客户身份
		$M = M("customer");
		$M_gf = M("giftrecord");
		$M_dl = M("gift_detail");
		$res_customer = $M->field('customerid')->where(array("mobile"=>$tel))->find();
		$customerid = $res_customer['customerid'];
		//判断此手机号此次券是否已经领取
		$res_gi_ling = $M_gf->field('GiftId')->where(array("customerid"=>$customerid,"gift_id"=>$gift_id))->find();
		if($res_gi_ling['GiftId']){
			echo 8;//此手机号已领用过
			die;
		}

		$now = time();
		//判断此福利号是否已被使用
		$res_dl_is_ling = $M_dl->field('current_status')->where(array("gift_id"=>$gift_id,"gift_code"=>$gift_code))->find();
		if($res_dl_is_ling['current_status'] ==2){
			$M_if = M("gift_info");
			
			$M_lg = M("gift_log");
			$M_wf = M("customer_welfare");
			
			$M_if->startTrans();
			/**********更新次券信息car_gift_info******/
			$res_if_find = $M_if->field("used_num,company_name,is_range,gift_pic,OwnerId")->where(array("gift_id"=>$gift_id))->find();
			$map1['gift_id'] = $gift_id;
			$map1['last_time'] = $now;
			$map1['last_type'] = '03';
			$map1['last_use_time'] = $now;
			$map1['used_num'] = $res_if_find['used_num'] + 1;
			 

			$res_if = $M_if->save($map1);
			/**********更新次券信息car_gift_info END******/

			/**********更新次券信息细节car_gift_detail******/
			$map2['current_status'] = 3;
			$map2['last_time'] = $now;

			$res_dl = $M_dl->where(array("gift_id"=>$gift_id,"gift_code"=>$gift_code))->save($map2);
			/**********更新次券信息细节car_gift_detail END******/

			/**********更新次券日志表gift_log******/
			$res_lg_find = $M_dl->field("gift_num,remark,startdate,enddate,ent_value")->where(array("gift_id"=>$gift_id,"gift_code"=>$gift_code))->find();
			$map3['gift_id'] = $gift_id;
			$map3['gift_num'] = $res_lg_find['gift_num'] ;
			$map3['add_time'] = $now;
			$map3['operator'] = $tel;
			$map3['op_type'] = 3;

			$res_lg = $M_lg->add($map3);
			/**********更新次券日志表car_gift_log END******/

			/**********更新车主福利信息表car_customer_welfare ******/

			$map4['mobile'] =$tel;
			$map4['gift_code'] =$gift_code;
			$map4['add_time'] =$now;
			$map4['businessname'] =$res_if_find['company_name'];

			$res_wf = $M_wf->add($map4);
			/**********更新车主福利信息表car_customer_welfare END******/

			/**********更新礼券记录表car_giftrecord ******/
			$map5['customerid'] = $customerid;
			$map5['addtime'] = $now;
			$map5['startdate'] = $res_lg_find['startdate'];
		    $map5['enddate'] = $res_lg_find['enddate'];
			$map5['giftnum'] = $res_lg_find['ent_value'];
			$map5['status'] = 1;
			$map5['lasttime'] = $now;
			$map5['activity_idea'] = $res_lg_find['remark'];
			$map5['isread'] = 1;
			$map5['picurl'] = $res_if_find['gift_pic'];
			$map5['gift_id'] = $gift_id;
			$map5['gift_code'] = $gift_code;
			$map5['OwnerId'] = $res_if_find['OwnerId'];

			$res_gf = $M_gf->add($map5);
			/**********更新礼券记录表car_giftrecord END******/


			if($res_if>0 && $res_dl>0 && $res_lg && $res_wf >0 && $res_gf >0){
				$M_if->commit();
				echo 1;	
			}elseif($res_if <= 0){
				$M_if->rollback();
				echo 2;		//更新数据失败
			}elseif($res_wf <= 0){
				$M_if->rollback();
				echo 3;		//更新数据失败
			}elseif($res_lg <= 0){
				$M_if->rollback();
				echo 4;		//更新数据失败
			}elseif($res_dl <= 0){
				$M_if->rollback();
				echo 5;    //福利码错误
			}else{
				$M_if->rollback();
				echo 6;   //更新数据失败
			}
		}else{
			echo 7;//福利码已被使用
		}
		//$this->error("","",123);
	}


	//根据编号获取次券简介
	public function getIntroBycode(){
		$code = $_POST['code'];
		$M = M("gift_batch");
		$res = $M->field('remark')->where(array("batch_no"=>$code))->find();
		echo $res['remark'];
	}
}