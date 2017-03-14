<?php



class ArticleAction extends CommonAction {
   	private  $m_article;
	public function index(){
		//$id = I('get.id');
		$id = $_REQUEST['id'];
		if (!empty($id)){			
			$M = D('Article');
			$condition  = "article_id = ".$id;			
			$sql = "select A.*,wb_article_contents.article_content from  (select * from wb_article where ".$condition ." ) as A left join  wb_article_contents  on wb_article_contents.article_id = A.article_id  ";
			$row = $M->query($sql);
			
			//$this->m_article = D('Article');
			//$row = $this->m_article->getArticle($id);

			$row['article_desc'] = $this->descMbSubstr($row[0]['article_content']);
			$row['article_title'] = $row[0]['article_title'];
			//$row['article_url'] = $this->formatUrl('article', 'id='.$row[0]['article_id']);
			$url = "http://wap.yizizhu.net/indexwap.php?m=article&a=index&id=".$row[0]['article_id'];
			$row['article_url'] = $url;
						//$this->assign('data', $row);
			$this->assign('article_id', $row[0]['article_id']);
			$this->assign('article_title', $row[0]['article_title']);
			$this->assign('article_desc', $row[0]['article_desc']);
			$this->assign('article_url', $url);
			$this->assign('article_image', $row[0]['article_image']);
			$this->assign('article_content', $row[0]['article_content']);
			
			//$aid = C('WEIXIN_APPID') ;
			$aid = $row[0]['article_id'];
			$this->assign('aid', $aid);
			
			//获取微信用户的OPenId
			$this->setWechatOpenid();
			
			
			//实例化微信js类 
			$this->initWechatJS();

			//简化原来的设计, 直接在网址后添加 wechatOpenID 参数 该参数用来 wethatJS 成功后提交的网址
			//得到shareApiUrl的格式如:http://test.jia366.com/WeiXin-Api-shareData-id-1.html?wechatOpenID=ox4Pytzf1HA25DGyWkvDp8KzjQxo
			$shareApiUrl = $this->formatUrl('shareApiUrl', 'id='.$row[0]['article_id'].'&wechatOpenID='.$this->wechatOpenid);

			$this->assign('signPackage', $this->wechatJS->GetSignPackage());			
			$this->assign('shareApiUrl', $shareApiUrl);
			
			
			$this->display();
		}
		else{
			echo "没有参数";	
		}
	}




	/**
	 * 截取文章内容到一定长度做分享的简介  kevin - 10:16:19
	 * @access protected
	 * @param  string $str
	 * @param  int $leng
	 * @return string
	 */
	protected function descMbSubstr($str, $leng = 100) {
		return mb_substr(strip_tags(str_replace(array(
		"\r\n",
		"\n"), '', $str)), 0, $leng);
	}


	
}