<?php
//将全角数字转换为半角
function GetAlabNum($fnum) {
    $nums = array("０", "１", "２", "３", "４", "５", "６", "７", "８", "９");
    $fnums = "0123456789";

    for ($i = 0; $i <= 9; $i++) {
        $fnum = str_replace($nums[$i], $fnums[$i], $fnum);
    }

    $fnum = preg_replace("/[^0-9.]|^0{1,}/", "", $fnum);

    if ($fnum == "") {
        $fnum = 0;
    }
    return $fnum;
}

function get_mobile_area($mobile) {
    if (isset($mobile)) {
        $mobile_v = GetAlabNum(trim($mobile));
    }
    $tel = substr($mobile_v, 0, 7);
    $M = M('mobile');
    $where = "MobileNumber='" . $tel . "'  ";
    $info = $M->field('MobileArea,MobileType')->where($where)->limit(1)->select();
    if ($info) {
        $MobileArea = $info[0]['MobileArea'];
        $MobileType = $info[0]['MobileType'];
        $arealist = explode(' ', $MobileArea);
        $mobile_province = $arealist[0];
        $mobile_city = $arealist[1];
        $data['mobile_province'] = $mobile_province;
        $data['mobile_city'] = $mobile_city;
        $data['mobile_type'] = $MobileType;
        $data['mobile_isprocess'] = 1;

        $AreaNo = "";
        if ($mobile_province != "" && $mobile_city != "") {
            $Marea = M('area');
            $where1 = "AreaName='" . $mobile_province . "'  and  AreaLevel = 1";
            $info_province = $Marea->field('AreaNo')->where($where1)->limit(1)->select();
            $AreaNo_province = $info_province[0]['AreaNo'];
            $where2 = "AreaName='" . $mobile_city . "'  and  AreaLevel = 2  and  UpAreaNo='" . $AreaNo_province . "' ";
            $info_city = $Marea->field('AreaNo')->where($where2)->limit(1)->select();
            $AreaNo = $info_city[0]['AreaNo'];
        }
        $data['mobile_areano'] = $AreaNo;
    } else {
        $data['mobile_province'] = "";
        $data['mobile_city'] = "";
        $data['mobile_type'] = "";
        $data['mobile_isprocess'] = -1;
    }
    $M1 = M('customer');
    $where = "mobile='" . $mobile . "'";
    $rst = $M1->where($where)->save($data);
        
    return $rst;
}
        

/* * *********************得到真实IP地址************************ */

function get_real_ip() {
    $ip = false;
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) {
            array_unshift($ips, $ip);
            $ip = FALSE;
        }
        for ($i = 0; $i < count($ips); $i++) {
            if (!eregi("^(10|172\.16|192\.168)\.", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

//添加日志
function addlog($account, $titile, $new_content, $old_content, $module_name, $function_name) {
    $M = M('log');
    $now = date("Y-m-d H:i:s");
    $data['account'] = $account;
    $data['titile'] = $titile;
    $data['new_content'] = $new_content;
    $data['old_content'] = $old_content;
    $data['group_name'] = 'admin';
    $data['module_name'] = $module_name;
    $data['function_name'] = $function_name;
    $data['op_time'] = $now;
    $data['ipaddress'] = get_real_ip();
    $M->add($data);
}

//赠送礼包
function send_package($package_code, $mobile) {
    $package_code = trim($package_code);
    $M = M("gift_package");
    $where = "package_code='" . $package_code . "' ";
    $info_package = $M->field("current_status,batch_no")->where($where)->limit(1)->select();
    $current_status = $info_package[0]["current_status"];

    if ($current_status == 1) { //判断领券码的状态(1表示添加未领取,2表示已领取)
        $batch_no = $info_package[0]["batch_no"];
        $Mcustomer = M("customer");
        $where_customer = "mobile='" . $mobile . "'";
        $info = $Mcustomer->field("customerid,balance_gift,all_gift")->where($where_customer)->limit(1)->select();
        if ($info) {//判断是否是车主用户
            $M3 = M("customer_package");
            $where3 = "mobile='" . $mobile . "'  and  batch_no='" . $batch_no . "'  ";
            $send_count = $M3->where($where3)->count();
            if ($send_count == 0) {//判断该礼券是否已经领取过
                $M4 = M("gift_batch");
                $info_batch = $M4->field("user_id,user_name,activity_idea")->find($batch_no); //读取礼包信息
                $activity_idea = $info_batch["activity_idea"];
                $businessid = $info_batch["user_id"];
                $businessname = $info_batch["user_name"];

                $ok = 0;
                $now = time();
                $customerid = $info[0]["customerid"];
                $balance_gift = $info[0]["balance_gift"];
                $all_gift = $info[0]["all_gift"];
                $Mdetail = M("gift_detail");
                $where_detail = "batch_no='" . $batch_no . "'  and  package_code='" . $package_code . "'   and  current_status = 2 ";
                //读取礼包中礼券的数量
                $lists = $Mdetail->field("gift_id,gift_code,ent_value,cost_self,cost_foam,price,compute_price")->where($where_detail)->select();
                $gift_count = count($lists);
                $M->startTrans();
                foreach ($lists as $key => $val) {
                    $gift_id = $val["gift_id"];
                    $gift_code = $val["gift_code"];
                    $ent_value = $val["ent_value"];
                    $Mgift = M("gift_info");
                    $info_gift = $Mgift->field("gift_pic,company_name,OwnerId,businessid,is_range")->find($gift_id);
                    $is_range = intval($info_gift["is_range"]);
                    $gift_pic = $info_gift["gift_pic"];
                    $company_name = $info_gift["company_name"];
                    $OwnerId = $info_gift["OwnerId"];
                    $businessid = $info_gift["businessid"];
                    if ($is_range > 0) {
                        $add_value = $is_range;
                        $date_month = date("Y-m-d", strtotime("+" . $add_value . "months", $now)); //加一月日期
                        $enddate = strtotime($date_month);
                    } else {
                        $enddate = 0;
                    }

                    //添加车主礼券信息
                    $Mgiftrecord = M("giftrecord");
                    $data = array();
                    $data['customerid'] = $customerid;
                    $data['Cost'] = 0;
                    $data['balance'] = 0;
                    $data['addtime'] = $now;
                    $data['startdate'] = $now;
                    $data['enddate'] = $enddate;
                    $data['giftnum'] = $ent_value;
                    $data['order_num'] = $batch_no;
                    $data['lasttime'] = $now;
                    $data['businessid'] = $businessid;
                    $data['business_ordercustomer_id'] = 0;
                    $data['activity_idea'] = $activity_idea;
                    $data['ordertypeid'] = 1; //普通红包
                    $data['othermoney'] = 0;
                    $data['company_name'] = $company_name;
                    $data['gift_id'] = $gift_id;
                    $data['gift_code'] = $gift_code;
                    $data['OwnerId'] = $OwnerId;
                    $data['picurl'] = $gift_pic;
                    $rst = $Mgiftrecord->add($data);
                    unset($data);


                    $M6 = M("gift_detail");
                    $where6 = "ent_value='" . $ent_value . "' ";
                    $data6 = array();
                    $data6["current_status"] = 3;
                    $data6["last_time"] = $now;
                    $data6["startdate"] = $now;
                    $data6["enddate"] = $enddate;
                    $rst6 = $M6->where($where6)->save($data6);
                    unset($data6);
                    if ($rst > 0 && $rst6 > 0) {
                        $ok = $ok + 1;
                    }
                }
                if ($gift_count == $ok) {
                    $M1 = M("customer");
                    $where1 = "customerid='" . $customerid . "'";
                    $data1 = array();
                    $balance_gift = $balance_gift + $ok;
                    $all_gift = $all_gift + $ok;
                    $data1["balance_gift"] = $balance_gift;
                    $data1["all_gift"] = $all_gift;
                    $rst1 = $M1->where($where1)->save($data1);
                    unset($data1);

                    $M2 = M("customer_package");
                    $data2 = array();
                    $data2['customerid'] = $customerid;
                    $data2['mobile'] = $mobile;
                    $data2['package_code'] = $package_code;
                    $data2['add_time'] = $now;
                    $data2['status'] = 1;
                    $data2['remark'] = "";
                    $data2['customer_type'] = 0;
                    $data2['businessid'] = $businessid;
                    $data2['businessname'] = $businessname;
                    $data2['batch_no'] = $batch_no;
                    $data2['current_status'] = 1;
                    $data2['last_time'] = $now;
                    $rst2 = $M2->add($data2);
                    unset($data2);


                    //修改礼包中份数的状态
                    $data5 = array();
                    $data5["use_time"] = $now;
                    $data5["last_time"] = $now;
                    $data5["current_status"] = 2;
                    $data5["customerid"] = $customerid;
                    $data5["mobile"] = $mobile;
                    $where5 = "package_code='" . $package_code . "'  and   current_status = 1 ";
                    $rst5 = $M->where($where5)->save($data5);
                    unset($data5);



                    if ($rst1 > 0 && $rst2 > 0 && $rst5 > 0) {
                        $M->commit();
                        $rst_value = 1;
                        /* 写日志 */
                        $account = $mobile;
                        $titile = "礼包领取成功";
                        $new_content = "手机：" . $mobile . "礼包领券码:" . $package_code;
                        $old_content = "返回-5表示发放事务处理";
                        $module_name = "车主端-我的账号";
                        $function_name = "福利-领取";
                        addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
                    } else {
                        $M->rollback();
                        $rst_value = -5;
                        /* 写日志 */
                        $account = $mobile;
                        $titile = "礼包领取失败";
                        $new_content = "手机：" . $mobile . "礼包领券码:" . $package_code;
                        $old_content = "返回-5表示发放事务处理";
                        $module_name = "车主端-我的账号";
                        $function_name = "福利-领取";
                        addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
                    }
                } else {
                    $M->rollback();
                    $rst_value = -3; //表示发放细节失败
                    /* 写日志 */
                    $account = $mobile;
                    $titile = "礼包领取失败";
                    $new_content = "手机：" . $mobile . "礼包领券码:" . $package_code;
                    $old_content = "返回-3表示发放细节失败";
                    $module_name = "车主端-我的账号";
                    $function_name = "福利-领取";
                    addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
                }
            } else {
                $rst_value = -4; //表示该用户已经领过该礼包.(一个手机号只能领取一种礼包一次)
                /* 写日志 */
                $account = $mobile;
                $titile = "礼包领取失败";
                $new_content = "手机：" . $mobile . "礼包领券码:" . $package_code;
                $old_content = "返回-4表示该用户已经领过该礼包.(一个手机号只能领取一种礼包一次)";
                $module_name = "车主端-我的账号";
                $function_name = "福利-领取";
                addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
            }
        } else {
            $rst_value = -2; //表示该手机号不是我们的客户
            /* 写日志 */
            $account = $mobile;
            $titile = "礼包领取失败";
            $new_content = "手机：" . $mobile . "礼包领券码:" . $package_code;
            $old_content = "返回-2表示该手机号不是我们的客户";
            $module_name = "车主端-我的账号";
            $function_name = "福利-领取";
            addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
        }
    } else {
        $rst_value = -1; //表示该礼包已被领取或者礼包号错误.
        /* 写日志 */
        $account = $mobile;
        $titile = "礼包领取失败";
        $new_content = "手机：" . $mobile . "礼包领券码:" . $package_code;
        $old_content = "返回-1表示该礼包已被领取或者礼包号错误";
        $module_name = "车主端-我的账号";
        $function_name = "福利-领取";
        addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
    }
    return $rst_value;
}

/**
 * @desc 根据两点间的经纬度计算距离 
 * @param float $lat 纬度值 
 * @param float $lng 经度值 
 */
function getDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6367000;


    $lat1 = ($lat1 * pi() ) / 180;
    $lng1 = ($lng1 * pi() ) / 180;

    $lat2 = ($lat2 * pi() ) / 180;
    $lng2 = ($lng2 * pi() ) / 180;


    $calcLongitude = $lng2 - $lng1;
    $calcLatitude = $lat2 - $lat1;
    $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;

    return round($calculatedDistance);
}

//删除过期的二维码
//function invalidate_encode() {
//    $now = time();
//    $M = M('customer_rand');
//    $where = " end_time<" . $now . "  and  status = 1  ";
//    //$rst = $M->where($where)->delete();
//    $data['status'] = 0;
//    $data['last_time'] = $now;
//    $M->where($where)->save($data);
//    return 1;
//}

//生成用户二维码随机串
//$openid表示客户的openID
//$time_value表示间隔秒数，1表示一秒，60表示60秒
function create_customer_rand($customerid, $openid, $time_value) {
    $add_time = time();
    $M1 = M('customer_rand');
    $where1 = "openid='" . $openid . "' ";
    $ret_delete = $M1->where($where1)->delete();


//    $delete_time  = $add_time - 60*2;//两分钟前的数据
//    $where_delete = "add_time < ".$delete_time;
//    $ret_delete = $M1->where($where_delete)->delete();

    //invalidate_encode();

    $temp_code = create_guid();
    $temp_code1 = preg_replace('#-#', '', $temp_code); //去除字符串中－
    $temp = random(6, 1);
    $encode = md5(md5(md5($temp_code1) . $temp) . $openid);
        
    $end_time = $add_time + $time_value;
    $M = M('customer_rand');
    $data['rand_code'] = $temp_code;
    $data['add_time'] = $add_time;
    $data['end_time'] = $end_time;
    $data['openid'] = $openid;
    $data['customerid'] = $customerid;
    $data['status'] = 1;
    $data['time_code'] = $temp;
    $data['ent_code'] = $encode;
    $rst = $M->add($data);
    if ($rst > 0) {
        $rst_value = $encode;
    } else {
        $rst_value = "";
    }
    return $rst_value;
}

//二维数组去掉重复值,根据制定字段去重
function unique_arr_by_key($array2D, $key) {
    $arr = array();

    foreach ($array2D as $k => $v) {
        if (in_array($array2D[$k][$key], $arr)) {

            unset($array2D[$k]);
        } else {
            array_push($arr, $v[$key]);
        }
    }

    return $array2D;
}

//二维数组去重
function unique_arr($array2D, $stkeep = false, $ndformat = true) {
    // 判断是否保留一级数组键 (一级数组键可以为非数字)  
    if ($stkeep)
        $stArr = array_keys($array2D);

    // 判断是否保留二级数组键 (所有二级数组键必须相同)  
    if ($ndformat)
        $ndArr = array_keys(end($array2D));

    //降维,也可以用implode,将一维数组转换为用逗号连接的字符串  
    foreach ($array2D as $v) {
        $v = join(",", $v);
        $temp[] = $v;
    }

    //去掉重复的字符串,也就是重复的一维数组  
    $temp = array_unique($temp);

    //再将拆开的数组重新组装  
    foreach ($temp as $k => $v) {
        if ($stkeep)
            $k = $stArr[$k];
        if ($ndformat) {
            $tempArr = explode(",", $v);
            foreach ($tempArr as $ndkey => $ndval)
                $output[$k][$ndArr[$ndkey]] = $ndval;
        } else
            $output[$k] = explode(",", $v);
    }

    return $output;
}

//检查车主是否还有未领取的普通抵用券
function checkgift($customerid, $Mobile) {
    $back_code = "0000";
    $M = M('business_customer');
    $where = "Mobile='" . $Mobile . "' and  Status = 1 ";
    $info = $M->field('MemberId')->where($where)->select();
    $count_customer = count($info);
    $Mcustomer = M('customer');
    $wherecount = "customerid=" . $customerid;
    $mcustomer_info = $Mcustomer->where($wherecount)->count();
    $count1 = $mcustomer_info;
    if ($count_customer > 0 && $count1 == 1) {
        //echo 'step:3<br>';
        $Minfo = M('customer');
        $info1 = $Minfo->field('allmoney,othermoney,gift_num')->find($customerid);
        $allmoney = $info1['allmoney'];
        $othermoney = $info1['othermoney'];
        $gift_num = $info1['gift_num'];

        $ordertypeid = 1;  //普通红包
        $now = time();
        foreach ($info as $key => $val) {
            $MemberId = $val['MemberId'];
            $where1 = "MemberId=" . $MemberId . "  and  status = 1  "; //读取每个用户未使用的礼券记录
            $M1 = M('business_ordercustomer');
            $list1 = $M1->field('business_ordercustomer_id,businessid,order_num,peoplenum,everymoney')->where($where1)->select();
            $countlist = count($list1);
            $count_record = 0;
            if ($countlist > 0) {
                $new_allmoney = $allmoney;
                $new_othermoney = $othermoney;
                $new_gift_num = $gift_num;
                foreach ($list1 as $k => $v) {
                    $business_ordercustomer_id = $v['business_ordercustomer_id'];
                    $businessid = $v['businessid'];
                    $order_num = $v['order_num'];
                    $add_num = $v['peoplenum'];
                    $giftmoney = $v['everymoney'];

                    $add_giftvalue = $add_num * $giftmoney;  //新增礼券金额
                    $new_allmoney = $new_allmoney + $add_giftvalue; //最新的总金额
                    $new_othermoney = $new_othermoney + $add_giftvalue; //最新的礼券金额
                    $new_gift_num = $new_gift_num + $add_num;  //最新礼券数量

                    $M4 = M('business_orderinfo');
                    $orderinfo = $M4->field('remark,balance,picurl,startdate,enddate')->find($order_num);
                    $activity_idea = $orderinfo['remark'];
                    $picurl = $orderinfo['picurl'];
                    $startdate = $orderinfo['startdate'];
                    $enddate = $orderinfo['enddate'];
                    $giftnum = "";  //礼券编号

                    $M5 = M('giftrecord');
                    $M5->startTrans();

                    //添加车主礼券获取记录
                    $othermoney_temp = $othermoney;
                    $success_num = 0;

                    for ($i = 0; $i < $add_num; $i++) {
                        $data5 = array();
                        $othermoney_temp = $othermoney_temp + $giftmoney;
                        $data5['customerid'] = $customerid;
                        $data5['Cost'] = $giftmoney;
                        $data5['balance'] = $giftmoney;
                        $data5['addtime'] = $now;
                        $data5['startdate'] = $startdate;
                        $data5['enddate'] = $enddate;
                        $data5['giftnum'] = $giftnum;
                        $data5['order_num'] = $order_num;
                        $data5['businessid'] = $businessid;
                        $data5['business_ordercustomer_id'] = $business_ordercustomer_id;
                        $data5['activity_idea'] = $activity_idea;
                        $data5['ordertypeid'] = $ordertypeid;
                        $data5['othermoney'] = $othermoney_temp;
                        $data5['picurl'] = $picurl;
                        $rst = $M5->add($data5);
                        if ($rst > 0) {
                            $success_num = $success_num + 1;
                        }
                        unset($data5);
                    }
                    if ($success_num == $add_num) {
                        $rst5 = 1;
                    }

                    //修改车主信息
                    $M6 = M('customer');
                    $where6 = "customerid=" . $customerid;
                    $data6['allmoney'] = $new_allmoney; //新的总金额
                    $data6['othermoney'] = $new_othermoney;  //新的总礼券金额
                    $data6['gift_num'] = $new_gift_num;  //新的总礼券数量
                    $rst6 = $M6->where($where6)->save($data6);

                    //修改商户礼券发放记录 
                    $data1['status'] = 2;  //表示已发放
                    $data1['send_time'] = $now;
                    $where1 = "business_ordercustomer_id=" . $business_ordercustomer_id;
                    $rst1 = $M1->where($where1)->save($data1);

                    if ($rst5 > 0 && $rst6 > 0 && $rst1 > 0) {
                        $M1->commit();
                        $count_record = $count_record + 1;
                    } else {
                        $M1->rollback();
                    }
                }
            }
            if ($count_record == $countlist) {
                $back_code = '0000';
            } else {
                $back_code = '1111';
            }
        }
    } else {
        $back_code = "0000";
    }
    return $back_code;
}

function reggift($customerid, $Mobile, $ordertypeid, $processmoney, $orderid) {
    $now = time();
    //1483200000 是2017-01-01 00:00:00的时间戳
    if ($now > 1483200000) {
        /* 写日志 */
//        $account = "test";
//        $titile = "测试送券" . $Mobile;
//        $new_content = "手机号：" . $Mobile;
//        $module_name = "common";
//        $function_name = "reggift_new";
//        $old_content = "送次券";
//        addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
        reggift_new($customerid, $Mobile, $ordertypeid, $processmoney, $orderid);
    } else {
        /* 写日志 */
//        $account = "test";
//        $titile = "测试送券" . $Mobile;
//        $new_content = "手机号：" . $Mobile;
//        $module_name = "common";
//        $function_name = "reggift_old";
//        $old_content = "送抵用券";
//        addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
        reggift_old($customerid, $Mobile, $ordertypeid, $processmoney, $orderid);
    }
}

//注册赠送礼券(次券)
/* * ************* */
//ordertypeid 2注册，3充值
//processmoney 充值金额(以)
//orderid 商户订单号
function reggift_new($customerid, $Mobile, $ordertypeid, $processmoney, $orderid) {
    $back_code = "0000";
    if ($ordertypeid == 2) {
        $gift_id = 1;
        $OwnerId = 1;
        $Mcustomer = M('customer');
        $info_customer = $Mcustomer->field("othermoney,allmoney,wallet,balance_gift,all_gift")->find($customerid);
        $new_othermoney = $info_customer["othermoney"];
        $M = M("giftrecord");
        $where = "customerid = " . $customerid . " and  gift_id = " . $gift_id . "  and  OwnerId = " . $OwnerId; //读取该客户由易联众发放的注册是否已经存在
        $giftcount = $M->where($where)->count();
        if ($giftcount == 0) {

            $now = time();
            $M_gift_detail = M("gift_detail");
            $where_detail = "gift_id = " . $gift_id . "  and  current_status = 1 ";

            $M1 = M("gift_info");
            $info1 = $M1->field("is_range,price,compute_price,cost_self,cost_foam,gift_pic,company_name,intro,businessid")->find($gift_id);
            $is_range = $info1["is_range"];
            $price = $info1["price"];
            $compute_price = $info1["compute_price"];
            $cost_self = $info1["cost_self"];
            $cost_foam = $info1["cost_foam"];
            $gift_pic = $info1["gift_pic"];
            $company_name = $info1["company_name"];
            $intro = $info1["intro"];
            $businessid = $info1["businessid"];
            if ($is_range > 0) {
                $add_value = $is_range;
                $date_month = date("Y-m-d", strtotime("+" . $add_value . "months", $now)); //加一月日期
                $enddate = strtotime($date_month);
            } else {
                $enddate = 0;
            }
            $orderinfo = $M_gift_detail->field('gift_code,ent_value,batch_no')->where($where_detail)->limit(1)->select();
            if ($orderinfo) {
                $ent_value = $orderinfo[0]['ent_value'];
                $gift_code = $orderinfo[0]['gift_code'];
                $batch_no = $orderinfo[0]['batch_no'];

                $M5 = M('giftrecord');
                $M5->startTrans();
                $data5 = array();
                $data5['customerid'] = $customerid;
                $data5['Cost'] = 0;
                $data5['balance'] = 0;
                $data5['addtime'] = $now;
                $data5['startdate'] = $now;
                $data5['enddate'] = $enddate;
                $data5['giftnum'] = $ent_value;
                $data5['order_num'] = $batch_no;
                $data5['businessid'] = $businessid;
                $data5['business_ordercustomer_id'] = 0;
                $data5['activity_idea'] = $intro;
                $data5['ordertypeid'] = $ordertypeid;
                $data5['othermoney'] = $new_othermoney;
                $data5['company_name'] = $company_name;
                $data5['gift_id'] = $gift_id;
                $data5['gift_code'] = $gift_code;
                $data5['OwnerId'] = $OwnerId;
                $data5['picurl'] = $gift_pic;
                $rst5 = $M5->add($data5);
                unset($data5);

                $M6 = M('customer');
                $where6 = "customerid=" . $customerid;
                $data6 = array();
                $data6['all_gift'] = array("exp", "all_gift+1"); //
                $data6['balance_gift'] = array("exp", "balance_gift+1"); //
                $rst6 = $M6->where($where6)->save($data6);
                unset($data6);

                $where1 = "ent_value='" . $ent_value . "'  and   current_status = 1";
                $data1 = array();
                $data1["current_status"] = 3;
                $data1["last_time"] = $now;
                $data1["startdate"] = $now;
                $data1["enddate"] = $enddate;
                $rst1 = $M_gift_detail->where($where1)->save($data1);
                unset($data1);


                $where2 = "gift_id = " . $gift_id;
                $data2 = array();
                $data2["used_num"] = array("exp", "used_num+1");
                $data2["last_use_time"] = $now;
                $rst2 = $M1->where($where2)->save($data2);
                unset($data2);

                if ($rst5 > 0 && $rst6 > 0 && $rst1 > 0 && $rst2 > 0) {
                    $M5->commit();
                    $back_code = '0000'; //success
                } else {
                    $M5->rollback();
                    $back_code = '0020'; //sendgift_fail
                }
            } else {
                $back_code = '0021'; //         set back_msg = 'gift_isnull';
            }
        } else {
            $back_code = '0022'; //reggift_issend  该抵用券已经赠送过。
        }
    } else {
        $back_code = '0023'; //交易类型错误
    }
    return $back_code;
}

//注册赠送礼券_old
/* * ************* */
//ordertypeid 2注册，3充值
//processmoney 充值金额(以)
//orderid 商户订单号
function reggift_old($customerid, $Mobile, $ordertypeid, $processmoney, $orderid) {
    $back_code = "0000";
    if ($ordertypeid == 2) {
        $M = M("giftrecord");
        $where = "customerid = " . $customerid . " and  ordertypeid = " . $ordertypeid;
        $giftcount = $M->where($where)->count();
        if ($giftcount == 0) {
            /* 写日志 */
//            $account = "test";
//            $titile = "测试送券" . $Mobile;
//            $new_content = "手机号：" . $Mobile;
//            $module_name = "common";
//            $function_name = "reggift_old";
//            $old_content = "送抵用券:未送券";
//            addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);

            $now = time();
            $M1 = M("business_orderinfo");
            if ($ordertypeid == 2) {
                $add_value = C('REG_GIFT_RANGE');
                $date_month = date("Y-m-d", strtotime("+" . $add_value . "months", $now)); //加一月日期
                $enddate = strtotime($date_month);
                $where1 = "ordertypeid = 2 and   status =  1 and balance > 0 ";

                /* 写日志 */
//                $account = "test";
//                $titile = "赠送注册券";
//                $new_content = "手机号：" . $Mobile;
//                $module_name = "common";
//                $function_name = "reggift_old";
//                $old_content = "送抵用券:未送券";
//                addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
            } else if ($ordertypeid == 3) {
                $add_value = C('SAVE_GIFT_RANGE');
                $date_month_three = date("Y-m-d", strtotime("+" . $add_value . "months", $now)); //加三月日期
                $enddate = strtotime($date_month_three);
                $where1 = "ordertypeid = 3 and   status =  1 and balance > 0   and  paymoney=" . $processmoney;  //以分为单位     

                /* 写日志 */
//                $account = "test";
//                $titile = "赠送充值券";
//                $new_content = "手机号：" . $Mobile;
//                $module_name = "common";
//                $function_name = "reggift_old";
//                $old_content = "送抵用券:未送券";
//                addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
            }
            $orderinfo = $M1->field('everymoney,order_num,title,picurl')->where($where1)->limit(1)->select();
            if ($orderinfo) {
                /* 写日志 */
//                $account = "test";
//                $titile = "赠送券-礼券足够";
//                $new_content = "手机号：" . $Mobile;
//                $module_name = "common";
//                $function_name = "reggift_old";
//                $old_content = "送抵用券:未送券";
//                addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);

                $everymoney = $orderinfo[0]['everymoney']; //表示假如是充值赠送的话，是赠送金额
                $order_num = $orderinfo[0]['order_num'];
                $activity_idea = $orderinfo[0]['title'];
                $picurl = $orderinfo[0]['picurl'];
                $Minfo = M('customer');
                $info = $Minfo->field('allmoney,othermoney,gift_num')->find($customerid);
                $allmoney = $info['allmoney'];
                $othermoney = $info['othermoney'];
                $gift_num = $info['gift_num'];

                $peoplenum = 1;
                $giftmoney = $everymoney * $peoplenum;

                $businessid = 1;
                $CustomerName = "";
                $new_allmoney = $allmoney + $everymoney;
                $new_othermoney = $othermoney + $everymoney;
                $new_gift_num = $gift_num + 1;

                $M2 = M('business_customer');
                $check_sql = "businessid=" . $businessid . "  and  Mobile='" . $Mobile . "'  ";
                $existinfo = $M2->field('MemberId')->where($check_sql)->order('MemberId asc')->limit(1)->select();
                $M2->startTrans();
                if (count($existinfo) == 0) {
                    $data2['businessid'] = $businessid;
                    $data2['CustomerName'] = $CustomerName;
                    $data2['Mobile'] = $Mobile;
                    $data2['addtime'] = $now;
                    $data2['lasttime'] = $now;
                    $rst2 = $M2->add($data2);
                } else {
                    $rst2 = $existinfo[0]['MemberId'];
                }

                $M3 = M('business_ordercustomer');
                $data3['businessid'] = $businessid;
                $data3['addtime'] = $now;
                $data3['startdate'] = $now;
                $data3['endtdate'] = 0;
                $data3['giftmoney'] = $giftmoney;
                $data3['status'] = 2; //已赠送
                $data3['order_num'] = $order_num;
                $data3['MemberId'] = $rst2;
                $data3['send_time'] = $now;
                $data3['Operator'] = ""; //系统
                $data3['peoplenum'] = $peoplenum;
                $data3['everymoney'] = $everymoney;
                $rst3 = $M3->add($data3);

                $M4 = M('business_orderinfo');
                $where4 = "order_num='" . $order_num . "' ";
                $data4['lasttime'] = $now;
                $data4['balance'] = array('exp', 'balance - 1'); //
                $rst4 = $M4->where($where4)->save($data4);

                $M5 = M('giftrecord');
                $data5['customerid'] = $customerid;
                $data5['Cost'] = $giftmoney;
                $data5['balance'] = $giftmoney;
                $data5['addtime'] = $now;
                $data5['startdate'] = $now;
                $data5['enddate'] = $enddate;
                $data5['giftnum'] = $peoplenum;
                $data5['order_num'] = $order_num;
                $data5['businessid'] = $businessid;
                $data5['business_ordercustomer_id'] = $rst3;
                $data5['activity_idea'] = $activity_idea;
                $data5['ordertypeid'] = $ordertypeid;
                $data5['othermoney'] = $new_othermoney;

                $data5['picurl'] = $picurl;
                $rst5 = $M5->add($data5);

                $M6 = M('customer');
                $where6 = "customerid=" . $customerid;
                $data6['allmoney'] = $new_allmoney; //
                $data6['othermoney'] = $new_othermoney;
                $data6['gift_num'] = $new_gift_num;
                $rst6 = $M6->where($where6)->save($data6);

                if ($ordertypeid == 3) {
                    $M7 = M('customer_saverecord');
                    $where7 = "orderid = '" . $orderid . "'  and  isgift = 0 ";
                    $data7['isgift'] = 1;
                    $data7['gift_time'] = $now;
                    $rst7 = $M7->where($where7)->save($data7);
                    if ($rst2 > 0 && $rst3 > 0 && $rst4 > 0 && $rst5 > 0 && $rst6 > 0 && $rst7 > 0) {
                        $M2->commit();
                        $back_code = '0000'; //success
                    } else {
                        $M2->rollback();
                        $back_code = '0020'; //sendgift_fail
                    }
                } else {
                    if ($rst2 > 0 && $rst3 > 0 && $rst4 > 0 && $rst5 > 0 && $rst6 > 0) {
                        $M2->commit();
                        $back_code = '0000'; //success
                    } else {
                        $M2->rollback();

                        /* 写日志 */
                        $account = "test";
                        $titile = "赠送券-失败,事务回滚";
                        $new_content = "手机号：" . $Mobile;
                        $module_name = "common";
                        $function_name = "reggift_old";
                        $old_content = "送抵用券:未送券";
                        addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);
                        $back_code = '0020'; //sendgift_fail
                    }
                }
            } else {
                /* 写日志 */
                $account = "test";
                $titile = "赠送券-礼券库存不足";
                $new_content = "手机号：" . $Mobile;
                $module_name = "common";
                $function_name = "reggift_old";
                $old_content = "送抵用券:未送券";
                addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);

                $back_code = '0021'; //         set back_msg = 'gift_isnull';
            }
        } else {
            /* 写日志 */
            $account = "test";
            $titile = "测试送券" . $Mobile;
            $new_content = "手机号：" . $Mobile;
            $module_name = "common";
            $function_name = "reggift_old";
            $old_content = "已送券";
            addlog($account, $titile, $new_content, $old_content, $module_name, $function_name);

            $back_code = '0022'; //reggift_issend  该抵用券已经赠送过。
        }
    } else {
        $back_code = '0023'; //交易类型错误
    }
    return $back_code;
}

//注册账户余额迁移
function customer_temp($customerid, $Mobile) {
    $back_code = "0000";
    $M = M("customer_temp");
    $where = "mobile = " . $Mobile . " and  isprocess = 0";
    $customertemp = $M->where($where)->find();

    if ($customertemp) {
        $allmoneytemp = $customertemp['allmoney'];
        $customeridtemp = $customertemp['customerid'];
        $now = time();

        //更新customer表
        $M1 = M("customer");
        $where1 = "customerid=" . $customerid;
        $info = $M1->where($where1)->find();
        $oldwallet = $info['wallet'];
        $oldallmoney = $info['allmoney'];
        $M1->startTrans();
        //新入库金额
        $newwallet = $oldwallet + $allmoneytemp;
        $newallmoney = $oldallmoney + $allmoneytemp;

        $data1['wallet'] = $newwallet;
        $data1['allmoney'] = $newallmoney;
        $rst1 = $M1->where($where1)->save($data1);

        //更新充值记录表
        $M2 = M('customer_saverecord');
        $data2['customerid'] = $customerid;
        $data2['cost'] = $allmoneytemp;
        $data2['status'] = "2";
        $data2['channel'] = "6";
        $data2['ispay'] = "1";
        $data2['orderid'] = build_order_no();
        $data2['savetime'] = $now;
        $data2['paytime'] = $now;
//        $data2['oldmoney'] = $oldallmoney;
//        $data2['newmoney'] = $newallmoney;
        $data2['oldmoney'] = $oldwallet; //旧的现金余额
        $data2['newmoney'] = $newwallet; //新的现金余额
        $rst2 = $M2->add($data2);

        $M3 = M('customer_temp');
        $where3 = "customerid = " . $customeridtemp;
        $data3['isprocess'] = "1";
        $rst3 = $M3->where($where3)->save($data3);

        if ($rst1 > 0 && $rst2 > 0 && $rst3 > 0) {
            $M1->commit();
            $back_code = "0000";
        } else {
            $M1->rollback();
            $back_code = "0020";
        }
    } else {
        $back_code = "0000";
    }
    return $back_code;
}

function create_guid() {
    $charid = strtoupper(md5(uniqid(mt_rand(), true)));
    $hyphen = chr(45); // "-"
    $uuid = chr(123)// "{"
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125); // "}"

    $uuid = ltrim($uuid, "{"); //取出第一个{
    $uuid = rtrim($uuid, "}"); //去除最后的}
    return $uuid;
}

/*
 * 动态生成IMG图
 * size :字体大小
 * text :文字内容
 *  */

function textimg($size, $text) {

    header("Content-type: image/png");
    $data['size'] = $size;
    $data['text'] = $text;
    import('ORG.Util.TextImage');
    $img = new TextImage($data);
    echo $img::draw();
}

function getWechatShareType($type) {
    $arr = array(
        0 => 'unkonw',
        1 => 'onMenuShareAppMessage',
        2 => 'onMenuShareTimeline',
        3 => 'onMenuShareQQ',
        4 => 'onMenuShareWeibo',
        5 => 'onMenuShareQZone',
    );

    $type_id = array_search($type, $arr);

    if (empty($type_id))
        $type_id = 0;

    return $type_id;
}

/**
 * 系统邮件发送函数
 * @param string $to    接收邮件者邮箱
 * @param string $name  接收邮件者名称
 * @param string $subject 邮件主题 
 * @param string $body    邮件内容
 * @param string $attachment 附件列表
 * @return boolean 
 */
function think_send_mail($to, $name, $subject = '', $body = '', $attachment = null) {
    $config = C('THINK_EMAIL');
    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
    $mail = new PHPMailer(); //PHPMailer对象
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP();  // 设定使用SMTP服务
    $mail->SMTPDebug = 0;                     // 关闭SMTP调试功能
    // 1 = errors and messages
    // 2 = messages only
    $mail->SMTPAuth = true;                  // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl';                 // 使用安全协议
    $mail->Host = $config['SMTP_HOST'];  // SMTP 服务器
    $mail->Port = $config['SMTP_PORT'];  // SMTP服务器的端口号
    $mail->Username = $config['SMTP_USER'];  // SMTP服务器用户名
    $mail->Password = $config['SMTP_PASS'];  // SMTP服务器密码
    $mail->SetFrom($config['FROM_EMAIL'], $config['FROM_NAME']);
    $replyEmail = $config['REPLY_EMAIL'] ? $config['REPLY_EMAIL'] : $config['FROM_EMAIL'];
    $replyName = $config['REPLY_NAME'] ? $config['REPLY_NAME'] : $config['FROM_NAME'];
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($to, $name);
    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }
    return $mail->Send() ? true : $mail->ErrorInfo;
}

function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
    if (function_exists("mb_substr")) {
        if ($suffix) {
            if ($str == mb_substr($str, $start, $length, $charset)) {

                return mb_substr($str, $start, $length, $charset);
            } else {

                return mb_substr($str, $start, $length, $charset) . "...";
            }
        } else {

            return mb_substr($str, $start, $length, $charset);
        }
    } elseif (function_exists('iconv_substr')) {
        if ($suffix) {

            if ($str == iconv_substr($str, $start, $length, $charset)) {

                return iconv_substr($str, $start, $length, $charset);
            } else {

                return iconv_substr($str, $start, $length, $charset) . "...";
            }
        } else {

            return iconv_substr($str, $start, $length, $charset);
        }
    }
    $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("", array_slice($match[0], $start, $length));
    if ($suffix)
        return $slice . "…";
    return $slice;
}

/* * ********************* 判断是否手机号是否符合规定 ********************* */

function is_mobile($mobile) {
    $rst = 0;
    $mobile_len = strlen($mobile);
    if ($mobile_len == 11) {
        $search = '/^(1(([35][0-9])|(47)|[8][012356789]))\d{8}$/';
        if (preg_match($search, $mobile)) {
            $rst = 1;
        } else {
            $rst = 0;
        }
    } else {
        $rst = 0;
    }
    return $rst;
}

function delhtml($html) {
    $search = array("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
        "'<[\/\!]*?[^<>]*?>'si", // 去掉 HTML 标记
        "'([\r\n])[\s]+'", // 去掉空白字符
        "'&(quot|#34);'i", // 替换 HTML 实体
        "'&(amp|#38);'i",
        "'&(lt|#60);'i",
        "'&(gt|#62);'i",
        "'&(nbsp|#160);'i"
    );                    // 作为 PHP 代码运行
    $replace = array("", "", "\\1", "\"", "&", "<", ">", " ");
    $html = preg_replace($search, $replace, $html);
    return $html;
}

//批量过滤post,get敏感数据  
function stripslashes_array(&$array) {

    while (list($key, $var) = each($array)) {

        if ($key != 'argc' && $key != 'argv' && (strtoupper($key) != $key || '' . intval($key) == "$key")) {

            if (is_string($var)) {

                $array[$key] = stripslashes($var);
            }

            if (is_array($var)) {

                $array[$key] = stripslashes_array($var);
            }
        }
    }

    return $array;
}

//防止SQL注入
function lib_replace_end_tag($str) {
    if (empty($str))
        return false;

    $str = htmlspecialchars($str);

    $str = str_replace('/', "", $str);

    $str = str_replace("\\", "", $str);

    $str = str_replace("&gt", "", $str);

    $str = str_replace("&lt", "", $str);

    $str = str_replace("<SCRIPT>", "", $str);

    $str = str_replace("</SCRIPT>", "", $str);

    $str = str_replace("<script>", "", $str);

    $str = str_replace("</script>", "", $str);

    $str = str_replace("select", "select", $str);

    $str = str_replace("join", "join", $str);

    $str = str_replace("union", "union", $str);

    $str = str_replace("where", "where", $str);

    $str = str_replace("insert", "insert", $str);

    $str = str_replace("delete", "delete", $str);

    $str = str_replace("update", "update", $str);

    $str = str_replace("like", "like", $str);

    $str = str_replace("drop", "drop", $str);

    $str = str_replace("create", "create", $str);

    $str = str_replace("modify", "modify", $str);

    $str = str_replace("rename", "rename", $str);

    $str = str_replace("alter", "alter", $str);

    $str = str_replace("cas", "cast", $str);

    $str = str_replace("&", "&", $str);

    $str = str_replace(">", ">", $str);

    $str = str_replace("<", "<", $str);

    $str = str_replace(" ", chr(32), $str);

    $str = str_replace(" ", chr(9), $str);

    $str = str_replace("    ", chr(9), $str);

    $str = str_replace("&", chr(34), $str);

    $str = str_replace("'", chr(39), $str);

    $str = str_replace("<br />", chr(13), $str);

    $str = str_replace("''", "'", $str);

    $str = str_replace("css", "'", $str);

    $str = str_replace("CSS", "'", $str);

    return $str;
}

function blogTags($tags) {

    $tags = explode(' ', $tags);
    $str = '';
    foreach ($tags as $key => $val) {
        $tag = trim($val);
        $str .= ' <a href="' . __APP__ . '/Blog/tag/name/' . urlencode($tag) . '">' . $tag . '</a>  ';
    }
    return $str;
}

function webTags($tags) {

    $tags = explode(' ', $tags);
    $str = '';
    foreach ($tags as $key => $val) {
        $tag = trim($val);
        $str .= ' <a href="' . __APP__ . '/Web/tag/name/' . urlencode($tag) . '">' . $tag . '</a>  ';
    }
    return $str;
}

/**
 * 产生随机字符串
 */
/*
  function random() {
  $hash = '';
  $chars = 'abcdef0123';
  $max = strlen($chars) - 1;
  for($i = 0; $i < 2; $i++) {
  $hash .= $chars[mt_rand(0, $max)];
  }
  return $hash;
  } */


function tourl($url) {
    $url = str_replace("//", "#", $url);
    $url = str_replace(":", "%", $url);
    $url = str_replace(".", "@", $url);

    for ($i = 0; $i < strlen($url); $i++) {
        $urls.= $url[$i] . random();
    }
    return $urls;
}

function sizecount($filesize) {
    if ($filesize >= 1073741824) {
        $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
    } elseif ($filesize >= 1048576) {
        $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
    } elseif ($filesize >= 1024) {
        $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
    } else {
        $filesize = $filesize . ' Bytes';
    }
    return $filesize;
}

function color_txt($str) {

    if (function_exists('iconv_strlen')) {
        $len = iconv_strlen($str);
    } else if (function_exists('mb_strlen')) {
        $len = mb_strlen($str);
    }
    $colorTxt = '';
    for ($i = 0; $i < $len; $i++) {
        $colorTxt .= '<span style="color:' . rand_color() . '">' . msubstr($str, $i, 1, 'utf-8', '') . '</span>';
    }

    return $colorTxt;
}

function rcolor() {
    $rand = rand(0, 255);
    return sprintf("%02X", "$rand");
}

function rand_color() {

    return '#' . rcolor() . rcolor() . rcolor();
}

function getTitleSize($count) {

    $size = (ceil($count / 10) + 11) . 'px';
    return $size;
}

function showExt($ext, $pic = true) {
    static $_extPic = array(
        'dir' => "folder.gif",
        'doc' => 'msoffice.gif',
        'rar' => 'rar.gif',
        'zip' => 'zip.gif',
        'txt' => 'text.gif',
        'pdf' => 'pdf.gif',
        'html' => 'html.gif',
        'png' => 'image.gif',
        'gif' => 'image.gif',
        'jpg' => 'image.gif',
        'php' => 'text.gif',
    );
    static $_extTxt = array(
        'dir' => '文件夹',
        'jpg' => 'JPEG图象',
    );
    if ($pic) {
        if (array_key_exists(strtolower($ext), $_extPic)) {
            $show = "<IMG SRC='__PUBLIC__/wblog/extension/" . $_extPic[strtolower($ext)] . "' BORDER='0' alt='' align='absmiddle'>";
        } else {
            $show = "<IMG SRC='__PUBLIC__/wblog/extension/common.gif' WIDTH='16' HEIGHT='16' BORDER='0' alt='文件' align='absmiddle'>";
        }
    } else {
        if (array_key_exists(strtolower($ext), $_extTxt)) {
            $show = $_extTxt[strtolower($ext)];
        } else {
            $show = $ext ? $ext : '文件夹';
        }
    }

    return $show;
}

function byte_format($input, $dec = 0) {

    $prefix_arr = array("B", "K", "M", "G", "T");
    $value = round($input, $dec);
    $i = 0;
    while ($value > 1024) {

        $value /= 1024;
        $i++;
    }
    $return_str = round($value, $dec) . $prefix_arr[$i];
    return $return_str;
}

/**
  +----------------------------------------------------------
 * 输出安全的html，用于过滤危险代码
  +----------------------------------------------------------
 * @access public
  +----------------------------------------------------------
 * @param string $text 要处理的字符串
 * @param mixed $allowTags 允许的标签列表，如 table|td|th|td
  +----------------------------------------------------------
 * @return string
  +----------------------------------------------------------
 */
function safeHtml($text, $allowTags = null) {
    $htmlTags = array(
        'allow' => 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a',
        'ban' => 'html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml',
    );
    $text = trim($text);
    //完全过滤注释
    $text = preg_replace('/<!--?.*-->/', '', $text);
    //完全过滤动态代码
    $text = preg_replace('/<\?|\?' . '>/', '', $text);
    //完全过滤js
    $text = preg_replace('/<script?.*\/script>/', '', $text);

    $text = str_replace('[', '&#091;', $text);
    $text = str_replace(']', '&#093;', $text);
    $text = str_replace('|', '&#124;', $text);
    //过滤换行符
    $text = preg_replace('/\r?\n/', '', $text);
    //br
    $text = preg_replace('/<br(\s\/)?' . '>/i', '[br]', $text);
    $text = preg_replace('/(\[br\]\s*){10,}/i', '[br]', $text);
    //过滤危险的属性，如：过滤on事件lang js
    while (preg_match('/(<[^><]+)(lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1], $text);
    }
    while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1] . $mat[3], $text);
    }
    if (empty($allowTags)) {
        $allowTags = $htmlTags['allow'];
    }
    //允许的HTML标签
    $text = preg_replace('/<(' . $allowTags . ')( [^><\[\]]*)>/i', '[\1\2]', $text);
    //过滤多余html
    if (empty($banTag)) {
        $banTag = $htmlTags['ban'];
    }
    $text = preg_replace('/<\/?(' . $banTag . ')[^><]*>/i', '', $text);
    //过滤合法的html标签
    while (preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i', $text, $mat)) {
        $text = str_replace($mat[0], str_replace('>', ']', str_replace('<', '[', $mat[0])), $text);
    }
    //转换引号
    while (preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1] . '|' . $mat[3] . '|' . $mat[4], $text);
    }
    //空属性转换
    $text = str_replace('\'\'', '||', $text);
    $text = str_replace('""', '||', $text);
    //过滤错误的单个引号
    while (preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i', $text, $mat)) {
        $text = str_replace($mat[0], str_replace($mat[1], '', $mat[0]), $text);
    }
    //转换其它所有不合法的 < >
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);
    $text = str_replace('"', '&quot;', $text);
    //反转换
    $text = str_replace('[', '<', $text);
    $text = str_replace(']', '>', $text);
    $text = str_replace('|', '"', $text);
    //过滤多余空格
    $text = str_replace('  ', ' ', $text);
    return $text;
}

/* * 已调试，可用
  +----------------------------------------------------------
 * 删除html标签，得到纯文本。可以处理嵌套的标签
  +----------------------------------------------------------
 * @access public
  +----------------------------------------------------------
 * @param string $string 要处理的html
  +----------------------------------------------------------
 * @return string
  +----------------------------------------------------------
 */

function deleteHtmlTags($string) {
    while (strstr($string, '>')) {
        $currentBeg = strpos($string, '<');
        $currentEnd = strpos($string, '>');
        $tmpStringBeg = @substr($string, 0, $currentBeg);
        $tmpStringEnd = @substr($string, $currentEnd + 1, strlen($string));
        $string = $tmpStringBeg . $tmpStringEnd;
    }
    return $string;
}

//取两个日期内天数差
function SubDay($ntime, $ctime) {
    $dayst = 3600 * 24;
    $cday = ceil(($ntime - $ctime) / $dayst);
    return $cday;
}

//取当前时间后几天，天数增加单位为1
function AddDay($ntime, $aday) {
    $dayst = 3600 * 24;
    $oktime = $ntime + ($aday * $dayst);
    return $oktime;
}

//默认0001表示显示在首页 ，0002表示 首页图片新闻,0003表示活动详情页，0004
function getFlash($channel, $intNum) {
    $pageflash1 = D('pageflash');
    $condition1 = "ChangeId='" . $channel . "' and  status = 1";
    $infoflash1 = $pageflash1->field('Title,BigFileName,TargetLink,AltText')->where($condition1)->order('showsort desc')->limit($intNum)->select();
    return $infoflash1;
}

//默认0001表示显示在首页
function getFlashByRelation($channel, $intNum, $OtherId) {
    $pageflash1 = D('pageflash');
    $condition1 = "ChangeId='" . $channel . "' and  status = 1 and  OtherId = " . $OtherId;
    $infoflash1 = $pageflash1->field('Title,BigFileName,TargetLink,AltText')->where($condition1)->order('showsort desc')->limit($intNum)->select();
    return $infoflash1;
}

//加密算法
function pwdHash($password) {
    return md5(md5(trim($password)) . C('KEYCODE'));
}

/**
 * 检查用户名是否符合规定
 */
function is_username($username) {
    $strlen = strlen($username);
    if (is_badword($username) || !preg_match("/^[a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+$/", $username)) {
        return false;
    } elseif (20 < $strlen || $strlen < 2) {
        return false;
    }
    return true;
}

/**
 * 检查密码长度是否符合规定
 */
function is_password($password) {
    $strlen = strlen($password);
    if ($strlen >= 6 && $strlen <= 20)
        return true;
    return false;
}

//提交
function Post($curlPost, $url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    $return_str = curl_exec($curl);
    curl_close($curl);
    return $return_str;
}

//xml转数组
function xml_to_array($xml) {
    $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
    if (preg_match_all($reg, $xml, $matches)) {
        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $subxml = $matches[2][$i];
            $key = $matches[1][$i];
            if (preg_match($reg, $subxml)) {
                $arr[$key] = xml_to_array($subxml);
            } else {
                $arr[$key] = $subxml;
            }
        }
    }
    return $arr;
}

//随机数
function random($length = 6, $numeric = 0) {
    PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
    if ($numeric) {
        $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
    } else {
        $hash = '';
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $hash .= $chars[mt_rand(0, $max)];
        }
    }
    return $hash;
}

function createRandNumberBySize($number) {
    $number = (int) $number;
    if ($number === 0) {
        return '';
    } else {
        $rankNumberString = "";
        for ($i = 0; $i < $number + 1; $i++) {
            if ($i !== 0 && $i % 2 === 0) {
                $rankNumberString .= mt_rand(11, 99);
            }
        }
        if ($number % 2 === 0) {
            return $rankNumberString;
        } else {
            return $rankNumberString . mt_rand(1, 9);
        }
    }
}

//发送注册短信
function SendSms($mobile, $mobile_code) {
    if (C('MOBILE_CODE') == 1) {
        $content = "您的确认码是：" . $mobile_code . "。该确认码将在3分钟后失效,请不要把确认码泄露给其他人。";
    } else if (C('MOBILE_CODE') == 2) {
        $content = "您的验证码是：" . $mobile_code . "。该验证码将在3分钟后失效,请不要把验证码泄露给其他人。";
    }
    $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
    
    $post_data = "account=cf_18945166&password=123456&mobile=" . $mobile . "&content=" . rawurlencode($content);
    
    //密码可以使用明文密码或使用32位MD5加密
    $gets = xml_to_array(Post($post_data, $target));

    if ($gets['SubmitResult']['code'] == 2) {
        $rst = 1;
    } else {
        $rst = $gets['SubmitResult']['msg'];
    }
    return $rst;
}

function json_exit($ary) {
    header("Content-type: text/html; charset=utf-8");
    echo json_encode($ary);
    exit;
}

/**
 * 生成二维码 　飞天 - Thu May 28 06:28:36 GMT 2015
 * @param string $value
 * @param string $id
 * @param string $portrait
 * @return void
 */
function setQRImg($value, $portrait = '', $level = 'L', $size = 5) {
    $limitTime = 21600;
    // Vendor('QR.phpqrcode', APP_PATH . 'Extend/Vendor/');
    vendor("QR.phpqrcode");
    $fileName = cryptCode($value . $portrait . $level . $size);
    $file = 'Uploads/QR/' . '_' . $fileName . '.png';
    if (!is_file($file) || filemtime($file) - NOW_TIME > $limitTime) {
        QRcode::png($value, $file, $level, $size, 2);
        if (!empty($portrait)) {
            $portrait = getThumbPath(realpath($portrait));
            if (is_file($portrait))
                QRcode::addPortrait($file, $portrait);
        }
    }

    Header("Content-type: image/png");
    Header('Content-Disposition: attachment; filename="' . $fileName . '.png"');
    die(file_get_contents($file));
}

/**
 * 生成加密后的字符串 　飞天 - Thu May 28 06:31:26 GMT 2015
 * @param string $str       需要加密的字符串
 * @param string $type      类型
 * @param array $other      其他加密方式需要用到的
 * @return  string
 */
function cryptCode($str, $type = 'md5', $other = array()) {
    switch ($type) {
        case 'sha1'://40位数
        case 'md5'://32位
            $str = $type($str . C('WEB_SITE_CRYPT_KEY'));
            break;
    }

    return $str;
}

//生成订单号
function build_order_no() {
    return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}

function xmlToArray($xml) {
    //将XML转为array        
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}

?>
