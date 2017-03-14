<?php
class AdminModel extends Model {
	public function chkInAdmin($name, $pw) {
		$where = array (
		'admin_name' => $name,
		'admin_password' => $pw,
		);

		return $this->where($where)->find();
	}

}