<?php

class Article_share_dataModel extends Model{
	public function upData($data) {
		$this->chkArticleId($data);

		$articleShareUser = M('Article_share_user');
		$arr = array(
		'openid' => $data['wechatOpenID'],
		'share_user_share_type' => getWechatShareType($data['type']),
		'article_id' => $data['id'],
		'share_user_add_time' => time()
		);
		$articleShareUser->create();
		$id = $articleShareUser->add($arr);

		if(empty($id)){
			return false;
		}
		return true;
	}

	protected function chkArticleId($data) {
		$arr = array(
		'share_data_article_id' => $data['id']);

		$row = $this->find($data['id']);

		if(empty($row)){
			$arr['share_data_share_count'] = 1;
			$this->create($arr);
			$this->add();
		}
		else{
			$arr['share_data_share_count'] = ++$row['share_data_share_count'];
			//$this->create($arr);
			$row = $this->save($arr);
		}

	}

}

