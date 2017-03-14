<?php

class MapAction extends CommonAction {

    //地图
    public function map() {
        header("Content-type: text/html; charset=utf-8");
        $city = $_GET['city'];
        $M = M('portinfo');
        //$where = "AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%')  and  Status >=0 and     port_type_id <> 10  ";
        $where = "AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%')  and  Status >=0 and     port_type_id <> 10   and  is_online = 1  ";
        $list = $M->where($where)->select(); 
         
  
        foreach ($list as $key => $value) {
            $list[$key]['priceself'] = (int) $value['price_self'] / 100;
            $list[$key]['pricehelp'] = (int) $value['price_help'] / 100;
        }
      
        $this->assign("list", $list);
        $this->assign("city", $_GET['city']);
        $this->assign("lng", $_GET['lng']);
        $this->assign("lat", $_GET['lat']);
        $this->display();
    }

    //地图详情页
    public function mapDetail() {
        header("Content-type: text/html; charset=utf-8");
        $ParkId = $_GET['ParkId'];
        $M = M('portinfo');
        $where = "ParkId =".$ParkId;
        $list = $M->where($where)->find(); 
         
  
        //foreach ($list as $key => $value) {
        $list['priceself'] = (int) $list['price_self'] / 100;
        $list['pricehelp'] = (int) $list['price_help'] / 100;
        //}
      
        $this->assign("v1", $list);
       
        $this->assign("lng", $list['ParkX']);
        $this->assign("lat", $list['ParkY']);
        $this->display();
    }

    //单独导航页
    public function Navigate() {
        header("Content-type: text/html; charset=utf-8");
        $ParkId = $_GET['ParkId'];
        $M = M('portinfo');
        $where = "ParkId = '" . $ParkId . "'";
        $list = $M->where($where)->find();
        $this->assign("list", $list);
        $this->initWechatJS();
        $this->assign('signPackage', $this->wechatJS->GetSignPackage());

        $this->display();
    }

    //关注洗车点
    public function attention() {
        header("Content-type: text/html; charset=utf-8");
        $ParkId = $_POST['ParkId'];
        $wechatOpenid = Session('wechatOpenid');
        if (!empty($wechatOpenid)) {
            $M = M('customer');
            $where = "openid = '" . $wechatOpenid . "'";
            $info = $M->field('customerid')->where($where)->limit(1)->select();
            $customerid = $info[0]['customerid'];


            $M1 = M('customerpark');
            $where1 = "customerid = '" . $customerid . "' and ParkId = '" . $ParkId . "'";
            $info1 = $M1->where($where1)->count();
            if ($info1 != 0) {
                echo 2;
                die;
            } else {
                $M = M('customerpark');
                $M->startTrans();
                $map['customerid'] = $customerid;
                $map['ParkId'] = $ParkId;
                $map['addtime'] = time();
                $rst = $M->add($map);
                if ($rst > 0) {
                    $M->commit();
                    echo 1;
                } else {
                    $M->rollback();
                    echo 0;
                }
            }
        } else {
            echo -1;
            die;
        }
    }

    //洗车场详细信息
    public function cardetails() {
        $ParkId = $_GET['ParkId'];
        //查询洗车场信息
        $M = M('portinfo');
        $M1 = M('washer');
        $M2 = M('port_income_day');
        $M_pm = M('port_msg');
        $M3 = M('score');
        $M_cus = M('customer');
        $M_service = M("service_module");

        $list = $M->where(array('ParkId' => $ParkId))->select();
        foreach ($list as $k => $v) {//数据处理
            $list[$k]['free'] = $M1->where(array('CurrentParkId' => $v['ParkId'], 'isservice' => 0))->count();

            // $washnum = $M2->where(array('ParkId' =>$v['ParkId']))->avg('washnum');
            // $list[$k]['washnum'] = (int)$washnum;
            // $list[$k]['price_self'] = (int)$v['price_self']/100;
            // $list[$k]['price_help'] = (int)$v['price_help']/100;
        }
        //服务模式
        $service_modl = $M1->field('service_module_id')->where(array('CurrentParkId' => $v['ParkId']))->limit(1)->select();
        $condition = 'service_module_id =' . $service_modl[0]['service_module_id'];
        $temp = $M_service->where($condition)->find();

        //数据改善
        $tet = $temp['remark'];
        $temp['remark'] = str_replace(",", "<br />", $tet);
        $this->assign('service_modl', $temp);

        //显示优惠信息
        $res_pm = $M_pm->where(array('ParkId' => $ParkId))->select();
        $this->assign("list_benefit", $res_pm);

        //显示评价

        $list3 = $M3->where(array('CurrentParkId' => $ParkId, "UpScoreId" => 0))->order('addtime desc')->select();
        foreach ($list3 as $key => $value) {
            //用户号码
            $res_tel = $M_cus->field("mobile")->where(array('customerid' => $value['customerid']))->find();
            $temp = substr($res_tel['mobile'], 0, 3) . "****" . substr($res_tel['mobile'], 7);
            //客户回复信息
            $res_find = $M3->field("Title")->where(array('UpScoreId' => $value['ScoreId']))->limit(1)->select();
            $list3[$key]['reply'] = $res_find[0]['Title'];
            $list3[$key]['tel'] = $temp;
        }

        $ParkName = $list[0]['ParkName'];
        $this->assign('ParkName', $ParkName);
        $this->assign('list', $list[0]);

        if (!empty($list3)) {
            $this->assign('list3', $list3);
            $this->display();
        } else {
            $message = 'null';
            $this->assign('message', $message);
            $this->display();
        }
    }
  
    //附近设备显示首页
    public function portinfolist() {
        header("Content-type: text/html; charset=utf-8");
        $wechatOpenid = Session('wechatOpenid');
        //入参
        $parkType = trim($_REQUEST['parkType']);
        
        $city = trim($_REQUEST['city']);
        $ParkName = trim($_REQUEST['ParkName']);
        $ParkY = trim($_GET['lng']);
        $ParkX = trim($_GET['lat']);
        $list_arr1 = array();
        $list_arr2 = array();
        //去掉市
        $lastword = mb_substr($city, mb_strlen($city) - 1, 1);
        if ($lastword == "市") {
            $city = mb_substr($city, 0, mb_strlen($city) - 1);
        } else {
            $city = $city;
        }

        $list = "";
        $M = M("portinfo");
        $M1 = M('washer');
        $M_service = M("service_module"); //服务模式
        //查询客户ID
        $M2 = M('customer');
        $M3 = M("customerpark");
        $where2 = "openid = '" . $wechatOpenid . "'";
        $info2 = $M2->field('customerid,wallet,allmoney,othermoney')->where($where2)->find();
        $customerid = $info2['customerid'];

        //先搜索附近的洗车点
        //设置查找范围
        $SeachX = '0.05';
        $SeachY = '0.05';
        $minx = $ParkX - $SeachX;
        $maxx = $ParkX + $SeachX;
        $miny = $ParkY - $SeachY;
        $maxy = $ParkY + $SeachY;
        //$where = "ParkX >'".$minx."' and ParkX <'".$maxx."' and ParkY > '".$miny."' and ParkY < '".$maxy."' and ServiceType = 0 and Status >=0";
        //$where = "ParkX >'" . $minx . "' and ParkX <'" . $maxx . "' and ParkY > '" . $miny . "' and ParkY < '" . $maxy . "'    and  port_type_id <> 10    and Status = 1  ";
        $where = "ParkX >'" . $minx . "' and ParkX <'" . $maxx . "' and ParkY > '" . $miny . "' and ParkY < '" . $maxy . "'    and  port_type_id <> 10    and Status = 1  and is_online = 1";
        $near = $M->where($where)->select();

        $this->assign('lng', $ParkX);
        $this->assign('lat', $ParkY);

        //存储距离用来排序

        foreach ($near as $key => $value) {
            $distance = getDistance($value['ParkX'], $value['ParkY'], $ParkX, $ParkY);
            $near[$key]['distance'] = $distance;

            $arrDistance[] = $distance;
        }
        array_multisort($arrDistance, SORT_DESC, $near);

         
        //根据类型显示
        if ($parkType==1 || $parkType==2) {
            if ($parkType == 1) {
                
                
                $where = "  port_type_id <> 10  and is_online = 1  and Status >=0    and  AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%') and  show_pos =1 ";

                if($ParkName!=""){//搜索洗车点
                    $where .=" and ParkName like '%" . $ParkName . "%'  ";
                    $this->assign("ParkName",$ParkName);
                }  
            } else {
                $where = " port_type_id<>10 and is_online = 1 and Status >=0    and  AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%') and  show_pos =2 ";
                if($ParkName!=""){//搜索洗车点
                    $where .=" and ParkName like '%" . $ParkName . "%'  ";
                    $this->assign("ParkName",$ParkName);
                }  
                 
                 
            }
            $list = $M->where($where)->select();



            // $this->assign("parkType", $type);
        //根据洗车点
        }else if ($ParkName) {
            $parkType = 1;
            
            //$sql = "select * from car_portinfo where ParkName like '%".$ParkName."%'  and ServiceType = 0 and Status >=0";
               
            //$sql = "select * from car_portinfo where ParkName like '%" . $ParkName . "%'    and Status >=0 and     port_type_id <> 10  and AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%') and service_module_id =1 ";
            $sql = "select * from car_portinfo where ParkName like '%" . $ParkName . "%' and Status >=0 and  is_online = 1  and     port_type_id <> 10  and AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%') and show_pos =1 ";
            $list = $M->query($sql);
             
            $this->assign("ParkName",$ParkName);
             
        //根据城市    
        }else if ($city) {
            $parkType = 1;
            //$where = "AreaNo in (select AreaNo from car_area where AreaName like '%".$city."%')  and ServiceType = 0 and Status >=0";
            //$where = "AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%')  and Status >=0 and     port_type_id <> 10 and service_module_id =1  ";
            $where = "AreaNo in (select AreaNo from car_area where AreaName like '%" . $city . "%')  and Status >=0  and  is_online = 1 and     port_type_id <> 10 and show_pos =1  ";
            $list = $M->where($where)->select();
            //根据洗车点名称
             
        } 
        $this->assign("parkType", $parkType);
        //数据进行处理
        if ($list) {
            //将附近的洗车点放在前面
            if (count($near) > 0) {
                foreach ($near as $key => $value) {
                    array_unshift($list, $near[$key]);
                }
            }
            //去重
            $list = unique_arr_by_key($list, 'ParkId');
            foreach ($list as $k => $v) {
                // $washer_count = $M1->where('CurrentParkId='.$v['ParkId'].' and status <>-1')->count();
                //    if($washer_count <1){
                //      	unset($list[$k]);
                //    }else{
                if ($list[$k]['WasherNum'] == 0) {
                    $list[$k]['WasherNum'] = 1;
                }

                $list[$k]['free'] = $M1->where(array('CurrentParkId' => $v['ParkId'], 'isservice' => 0))->count();
                $M3list = $M3->where(array('ParkId' => $v['ParkId'], 'status' => 1, 'customerid' => $customerid))->find();
                //服务模式，洗车点无此属性，从洗车机获取
               // $where_service = "service_module_id in(select service_module_id from car_washer where CurrentParkId ='" . $v['ParkId'] . "')";
               // $service_modltype = $M_service->field("OP")->where($where_service)->find();
               // $list[$k]['service_modltype'] = $service_modltype['OP'];

                if ($M3list) {
                    $list[$k]['customerparkid'] = $M3list['customerparkid'];
                } else {
                    $list[$k]['customerparkid'] = 0;
                }
                //分割正常运行和暂停服务洗车点
                if ($v['Status'] == 1) {
                    array_push($list_arr1, $list[$k]);
                } elseif ($v['Status'] != 1) {
                    array_push($list_arr2, $list[$k]);
                }
                // }
            }
            $message = 'ok';
        } else {
            $message = 'null';            
        }
        $this->assign('message', $message);
         
        $list = '';
        $list = array_merge($list_arr1, $list_arr2); //重新排列

        $this->assign("parkType", $parkType);
        $this->assign("city", $city);
        $count = count($list);
        $this->assign("list", $list);
        $this->assign("count", $count);
        $this->display();
    }

    //附近洗车点
    public function portinfoNear() {
        $ParkX = $_GET['lat'];
        $ParkY = $_GET['lng'];

        $M = M("portinfo");
        $M1 = M('washer');
        //查询客户ID
        $M2 = M('customer');
        $M3 = M("customerpark");
        $where2 = "openid = '" . $wechatOpenid . "'";
        $info2 = $M2->field('customerid,wallet,allmoney,othermoney')->where($where2)->find();
        $customerid = $info2['customerid'];

        //设置查找范围
        $SeachX = '0.05';
        $SeachY = '0.05';
        $minx = $ParkX - $SeachX;
        $maxx = $ParkX + $SeachX;
        $miny = $ParkY - $SeachY;
        $maxy = $ParkY + $SeachY;
        //$where = "ParkX >'" . $minx . "' and ParkX <'" . $maxx . "' and ParkY > '" . $miny . "' and ParkY < '" . $maxy . "' and ServiceType = 0 and Status =1";
        $where = "ParkX >'" . $minx . "' and ParkX <'" . $maxx . "' and ParkY > '" . $miny . "' and ParkY < '" . $maxy . "' and ServiceType = 0 and Status =1 and  is_online =1";
        $list = $M->where($where)->select();

        //数据进行处理
        if ($list) {
            foreach ($list as $k => $v) {
                $washer_count = $M1->where('CurrentParkId=' . $v['ParkId'] . ' and status <>-1')->count();
                if ($washer_count < 1) {
                    unset($list[$k]);
                } else {


                    $list[$k]['free'] = $M1->where(array('CurrentParkId' => $v['ParkId'], 'isservice' => 0))->count();
                    $M3list = $M3->where(array('ParkId' => $v['ParkId'], 'status' => 1, 'customerid' => $customerid))->find();
                    if ($M3list) {
                        $list[$k]['customerparkid'] = $M3list['customerparkid'];
                    } else {
                        $list[$k]['customerparkid'] = 0;
                    }
                }
            }
        }
        $count = count($list);
        if ($count > 0) {
            $this->assign('count', $count);
            $this->assign('list', $list);
        } else {
            $message = 'null';

            $this->assign('message', $message);
        }
        $this->display();
    }

    //附近设备首页，用来获取坐标和定位城市
    public function portinfoindex() {
        header("Content-type: text/html; charset=utf-8");
        $this->display();
    }

    //地区选择页面
    public function mapcity() {
        header("Content-type: text/html; charset=utf-8");
        $M = M("area");
        $where = "AreaLevel = 2 and is_open = 1 ";
        $list = $M->field('AreaNo,AreaName')->where($where)->select();
        $keys = 0;
        foreach ($list as $key => $value) {
            if ($value['AreaName'] == '南京市') {
                $keys = $key;
                break;
            }
        }
        //将南京市排在第一位
        $temp = $list[0];
        $list[0] = $list[$keys];
        $list[$keys] = $temp;

        $this->assign("list", $list);
        $this->display();
    }

}
