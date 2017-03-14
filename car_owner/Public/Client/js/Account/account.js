

    //查询消费明细
    function consumer(){

      var startdate = $("#startdate").val(); 
      var enddate   = $("#enddate").val();
      if(startdate == ''&& enddate ==''){
          alert("请选择日期");
          return;
      }


      $.post('index.php?m=Account&a=coupon',{startdate:startdate,enddate:enddate},
      function (msg){
           var str="";
           //先清空原有数据
           $("#zhmx").empty();

           if(msg == -1){
              alert("Openid获取出错，请重新登录！");
              return;
           }else if(msg == 0){
              alert("无相关消费记录！");
              return;
           }else{
               var data =JSON.parse(msg); 
               alert(data);
               $.each(data, function (entryindex, entry){

                //填充内容
                str += '<li><div class="zhmx_l">';        
                str += '<p>'+entry.ParkName+'</p>';            
                str += '<p>洗车-'+entry.servicetypename+'</p>';
                str += '<p>'+entry.tradedates+'<span>'+entry.tradetimes+'</span></p></div>';    
                str += '<div class="zhmx_r">';
                str += '<p class="red">-'+entry.cost+'元</p>';
                str += '<p><button class="'+entry.classtype+'" ';
                str += 'onclick = "carscore()">评价</button></p></div></li>';





                // str += "<li><div class='account_xf_mx_b_l'></div>";
                // str += "<div class='account_xf_mx_b_r'><span>"+entry.cost;
                // str += "</span>"+entry.ParkName;
                // str += "</div></li>";
                });
               $("#zhmx").append(str);
           }
        });
    }
    //用户评价
    function carscore(){
      var parkname = $("#parkname").val(); 
      var currentparkid   = $("#currentparkid").val();
      var currentownerid = $("#currentownerid").val();
      var washerid = $("#washerid").val();
      var washrecordid = $("#washrecordid").val();
      location.href="index.php?m=Account&a=carscore&parkname="+parkname+"&currentparkid="+currentparkid+"&washerid="+washerid+"&washrecordid="+washrecordid+"&currentownerid="+currentownerid;
    }



