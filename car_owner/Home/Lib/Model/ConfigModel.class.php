<?php
class ConfigModel extends Model {
	public function getConfig() {
		$where = array (
		'config_status' => 1
		);

		$data = $this->where($where)->select();

		$config = array ();

		if ($data && is_array($data)) {
			foreach ( $data as $value ) {
				$config[$value['config_key']] = $value['config_value'];
			}
		}

		return $config;
	}
}