<?php

class ArticleModel extends Model{
	protected $_auto = array(
	array(
	'article_insert_time',
	'time',
	self::MODEL_INSERT,
	'function'),
	array(
	'article_update_time',
	'time',
	self::MODEL_UPDATE,
	'function'));
	protected $_scope = array(
	// 命名范围normal
	'new' => array(
	'where' => array(
	'status' => 1),
	'order' => 'article_insert_time DESC',
	'limit' => 10));
	private $content_teble;
	private $article_table;
	private $joinStr;
	private $key_fileds = 'article_id';

	public function __construct($name = '', $tablePrefix = '', $connection = '') {
		parent::__construct($name, $tablePrefix, $connection);
		$this->content_teble = $this->tablePrefix . $this->name . '_contents';
		$this->article_table = $this->tablePrefix . $this->name;
		$this->joinStr = $this->content_teble . ' ON ' . $this->article_table . '.' . $this->key_fileds . ' = ' . $this->content_teble . '.' . $this->key_fileds;
		// $Model->scope(array('field'=>'id,title','limit'=>5,'where'=>'status=1','order'=>'create_time DESC'))->select();
	}

	public function getArticle($id) {
		$where = array(
		$this->article_table . '.' . $this->key_fileds => $id);

		$row = $this->join($this->joinStr, 'LEFT')->where($where)->find();
		return $row;
	}

	public function getArticleList($where) {
		$defaultWhere = array(
		'order' => 'article_insert_time',
		'limit' => 10,
		'where' => array(
		'article_status' => '1'));

		$where = array_merge($defaultWhere, $where);

		$row = $this->join($this->joinStr, 'LEFT')->select($where);

		return $row;
	}

	public function getArticleListCount() {

	}

	
	// public function inser

}

