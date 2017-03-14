<?php

defined('THINK_PATH') or exit();

/**
 * 百度Map API
 * Copyright (c) 2015 changecard.cn All rights reserved.
 * @date: Mon Jun 01 06:51:09 GMT 2015 * @author: 
 */

class BaiduMap {
	protected $ak;


	public function __construct($ak){
	}

	public function __set($key, $value){
		$this->$key = $value;
	}

	public function __get($key){
		return $this->$key;
	}

	/**
	* 百度APII获取地理位置经纬度接口方法　飞天 - Mon Jun 01 06:48:15 GMT 2015
	* @access protected
	* @param string 
	* @return 
	* */
	public function getAddr($addr, $output = 'json'){
		$url = 'http://api.map.baidu.com/geocoder/v2/?address=' . $addr . '&output=' . $output . '&ak=' . $this->ak;
		$data = $this->getUrl($url);
		return  json_decode($data, true);
	}


	protected function getUrl($url){
		$ch  =  curl_init();
		curl_setopt($ch, CURLOPT_URL,  $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 0);
		$str = curl_exec($ch);
		curl_close($ch);
		return $str;
	}



}