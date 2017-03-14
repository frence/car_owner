<?php
class PersonModel extends Model {
	//获取未评论信息
	//$where --------搜索条数
	public function noComment($where='')  {
		$customerid = Session('customerid');
  		$M = M('score');
        $sql ='select A.*,B.ParkName from ( ';
        //搜索洗车记录
        $sql .='select CurrentParkId,cost,addtime,servicetype,washerid,CurrentOwnerId from car_washrecord where status=1 and customerid ="'.$customerid.'" and ';
        //没有进行评论的检索
        $sql .='washrecordid not in (select washrecordid from car_score where  customerid ="'.$customerid.'") '.$where;
        //连表获取洗车店信息
        $sql .=' order by addtime desc)as A left join car_portinfo as B  on A.CurrentParkId =B.ParkId';

        $res = $M->query($sql);

        return $res;
	}
	//获取所有的洗车记录
	//$where --------搜索条数
	public function allWashrecord($where=''){
		$customerid = Session('customerid');
		$M = M('score');
		//$sql ='select E.*,F.ParkName from (';

		$sql ='select C.*,F.ParkName from (';

		$sql .='select A.*,B.Title,B.ScoreId,B.Score,B.scoretime from ';
		//搜索洗车流水信息
		$sql .='(select CurrentParkId,cost,addtime,servicetype,washrecordid,washerid,CurrentOwnerId from car_washrecord where status=1 and customerid ="'.$customerid.'" '.$where.' order by addtime desc)as A left join ';
		//获取评论内容
		$sql .='(select Title,ScoreId,Score,washrecordid,addtime as scoretime from car_score where customerid ="'.$customerid.'" and UpScoreId = 0) as B on A.washrecordid =B.washrecordid) as C left join ';
		//获取评论的回复信息
		//$sql .='(select Title as reply,Score from car_score where customerid ="'.$customerid.'" and UpScoreId >0 )as D on D.UpScoreId =C.ScoreId )as E left join';
		//获取洗车点信息
		$sql .=' car_portinfo as F on C.CurrentParkId = F.ParkId';

        $res = $M->query($sql);

        return $res;
	}

	//待处理的投诉
	//$where-------搜索条数
	public function adviceNoReply($where=''){
		$wechatOpenid = Session('wechatOpenid');
		$M = M('useridea');

		$res = $M->where("processtime =0 and openid ='".$wechatOpenid."'")->order("booktime DESC")->select();

		return $res;

	}

	//所有投诉
	//$where-------搜索条数
	public function alladvice($where=''){
		$wechatOpenid = Session('wechatOpenid');
		$M = M('useridea');
		$res = $M->where("openid ='".$wechatOpenid."'")->order("booktime DESC")->select();

		return $res;
	}

}