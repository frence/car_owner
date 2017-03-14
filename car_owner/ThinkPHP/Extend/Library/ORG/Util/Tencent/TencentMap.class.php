<?php

defined('THINK_PATH') or exit();

/**
 * 腾讯Map API
 * Copyright (c) 2015 changecard.cn All rights reserved.
 * @date: Mon Jun 01 06:51:09 GMT 2015 * @author: 
 */

class TencentMap {

	public function __construct($ak){
	}

	public function __set($key, $value){
		$this->$key = $value;
	}

	public function __get($key){
		return $this->$key;
	}

	/**
	* 腾讯API获取地理位置经纬度接口　飞天 - Mon Jun 24 06:48:15 GMT 2015
	* @access protected
	* @param string 
	* @return 
	* */
	public function getAddr($addr, $output = 'jsonp'){
		$url = 'http://apic.map.qq.com/geoc/?addr=' . $addr . '&output=' . $output . '&fr=mapapi&cb=cbibagcdf7';
		$data = $this->getUrl($url);
		preg_match('/"pointx":"(.*)",\s"pointy":"(.*)"/iS', $data, $pointy);
		return  $pointy;
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