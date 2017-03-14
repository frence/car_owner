<?php

class AccountinfoAction extends CommonAction {
	protected function _initialize() {
		parent::_initialize();
		//$this->is_login();
	}


    
	public function index(){
		//redirect(U('login'), 0, L('redirect'));
		header("Content-type: text/html; charset=utf-8");
        $M = D('Accountinfo');
		$pagesize = 10;
        import("ORG.Util.Page");
		$count = $M->count(); //��������
		$p = new Page($count, $pagesize);
		$lists = $M->limit($p->firstRow . ',' . $p->listRows)->select();

		$p->setConfig('header', '��');
		$p->setConfig('prev', "��һҳ");
		$p->setConfig('next', '��һҳ');
		$p->setConfig('first', '��ҳ');
		$p->setConfig('last', 'ĩҳ');

		$this->assign("pagelist", $p->show());
		$this->assign('info', $lists);
 
		$this->display();
	}
 
	
	//����ע����Ϣ
	public function add(){
	header("Content-type: text/html; charset=utf-8");
		if ($_POST){
			$Mobile = $_POST['Mobile'];
			$Maccount = D('Accountinfo');
			$where1 = "Mobile='".$Mobile."' ";
			$info = $Maccount->where($where1)->find();
			if ($info){
				$this->error('�Բ��𣡸��ֻ����Ѿ�ע�����');
			}else{
				$company_id = $_SESSION[('company_id')];
				$Remark = $_POST['Remark'];
				$Nickname = $_POST['Nickname'];	
				$UserLevel = $_POST['UserLevel'];
				$Status = $_POST['Status'];
				$Pwd = $_POST['PWD'];
				$newpwd = pwdHash($Pwd);
				$now = time();
				$data1['id'] = $company_id;
				$data1['Mobile'] = $Mobile;
				$data1['PWD'] = $newpwd;
				$data1['AddTime'] = $now;
				$data1['LastTime'] = $now;
				$data1['ipaddress'] =  get_client_ip();	
				$data1['Nickname'] =  $Nickname;
				$data1['Remark'] =  $Remark;
				$data1['UserLevel'] =  $UserLevel;
				$data1['Status'] =  $Status;
				$rst1 = $Maccount->add($data1);				 
				if ($rst1 > 0){
					$url = U("Accountinfo/index");
					$this->success('��ӳɹ���',$url,5);
				}else{
					$this->error('�Բ�����Ӳ���Ա��','',20);
				}
			}
		}else{
			$this->display();
		}
	}
	
	
	//�޸�
	public function edit(){
		header("Content-type: text/html; charset=utf-8");
		$Maccount = D('Accountinfo');
		if ($_POST){
			$Mobile = $_POST['Mobile'];			
			$where = "Mobile='".$Mobile."' ";
			$company_id = $_SESSION[('company_id')];
			$Remark = $_POST['Remark'];
			$Nickname = $_POST['Nickname'];	
			$UserLevel = $_POST['UserLevel'];
			$Status = $_POST['Status'];
			$now = time();
			$data1['LastTime'] = $now;
			$data1['ipaddress'] =  get_client_ip();	
			$data1['Nickname'] =  $Nickname;
			$data1['Remark'] =  $Remark;
			$data1['UserLevel'] =  $UserLevel;
			$data1['Status'] =  $Status;
			$rst1 = $Maccount->where($where)->save($data1);				 
			if ($rst1 > 0){
				$url = U("Accountinfo/index");
				$this->success('�޸ĳɹ���',$url,5);
			}else{
				$this->error('�Բ����޸�ʧ�ܣ�','',20);
			}
		}else{
			$UserId = $_REQUEST['UserId'];
			$info = $Maccount->find($UserId );
			$this->assign('info', $info);
			$this->display();
		}
	}
	
	//ɾ��
	public function delete(){
		$Maccount = D('Accountinfo');
		$UserId = $_REQUEST['UserId'];
		$where = "UserId=".$UserId;
		$rst = $Maccount->where($where)->delete();
		if ($rst >= 0){
			$url = U("Accountinfo/index");
			$this->success('ɾ���ɹ���',$url,5);
		}else{
			$this->error('�Բ���ɾ��ʧ�ܣ�','',20);
		}
	}
	
}