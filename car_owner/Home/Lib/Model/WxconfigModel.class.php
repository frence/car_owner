<?php

class WxConfigModel extends Model {
	public function getWeiXinConfig($id) {
		$where = array (
				'weixin_id' => $id 
		);
		
		$data = $this->where($where)->find();
		
		return $data;
	}
}