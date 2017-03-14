<?php

class AttentionAction extends Action {
	//我的账户
		public function attention(){

		header("Content-type: text/html; charset=utf-8");
		$wechatOpenid = Session('wechatOpenid');
		if(!empty($wechatOpenid)){
			//查询客户ID
			$M2 = M('customer');
			$where2 = "openid = '".$wechatOpenid."'";
			$info2 = $M2->field('customerid,wallet,allmoney,othermoney')->where($where2)->find();
			$customerid = $info2['customerid'];

			//查询客户关注洗车机信息
			$M1 = M('washer');
			$M  = M('customerpark');
			$sql = "select a.*,b.* from  (select  customerparkid,customerid,ParkId  from  car_customerpark  where  customerid = ".$customerid." and  status = 1 order by addtime desc)  as  A  left join  car_portinfo as B  on A.ParkId = B.ParkId and B.status = 1 ";			
			$list = $M->query($sql);
			$countpark = $M->where(array('customerid'=>$customerid))->count();
			foreach($list as $k => $v){
			      	$list[$k]['free'] = $M1->where(array('CurrentParkId' =>$v['ParkId'],'isservice'=>0))->count();
			      	$list[$k]['price_self'] = (int)$v['price_self']/100;
			      	$list[$k]['price_help'] = (int)$v['price_help']/100;
			}
			if(!empty($list)){
				$this->assign('list',$list);
				$this->assign('countpark',$countpark);
				$this->display();
			}else{
				$message = 'null';
				$this->assign('message',$message);
			    $this->display();
			}
			
		}else{
			$url = U("Public/index");
			$this->error('Sorry,Db is Error！',$url,5);
		}
		
		
	}


	//取消关注洗车场
	public function unselect(){
		$customerparkid = $_POST["customerparkid"];
		if(!empty($customerparkid)){
			$M = M('customer');
			$wechatOpenid = Session('wechatOpenid');
			$where = "openid = '".$wechatOpenid."'";
			$info = $M->field('customerid')->where($where)->limit(1)->select();
			$customerid = $info[0]['customerid'];

			$M = M("customerpark");
			$where = " ParkId = ".$customerparkid." and customerid =".$customerid;
			$rst = $M->where($where)->delete();
			if($rst>0){
			   //删除成功，返回1
				echo 1;
			}else{
				echo 0;
			}	
		}else{
			echo -1;
		}

	}
    
	
 
	

	
	


	
}