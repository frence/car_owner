<?php
class UserModel extends Model {
	protected $_validate = array(
	array('user_name', 'require', 'user_name_unique', 0, 'unique', 1),
	array('user_email', 'email', 'user_name_unique', 1),
	);

	protected $_auto = array (
	array('user_status', 1, 1),
	array('user_password','cryptCode',1,'function') ,
	array('user_level', 1, 1) ,
	);

	public function chkInUser($name, $pw) {
		$where = array (
		'user_name' => $name,
		'user_password' => $pw,
		);

		return $this->where($where)->find();
	}

	protected  function joinCompany() {
		$this->join_table = $this->tablePrefix . 'company_info';
		$this->joinStr = $this->join_table . ' ON ' . $this->getTableName() . '.user_company' . ' = ' . $this->join_table . '.id';
	}

	public function getUserList($where, $page) {
		$this->joinCompany();
		$rows = $this->field(array(
		$this->join_table . '.company_name',
		$this->getTableName() . '.*'))->join($this->joinStr, 'LEFT')->where($where)->order('user_id  DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		return $rows;

	}


}