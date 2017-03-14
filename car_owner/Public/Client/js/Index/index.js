//检查登录
function checkindex(){
	var username =$("#username").val();
	var verify = $("#verify").val();
    var lat = $("#reg_x").val();
    var lng = $("#reg_y").val();
    var city = $("#reg_city").val();
    var province = $("#reg_province").val();
    var checkbox = document.getElementById('checkid').checked;
	if(check(username,verify,checkbox)){
        $.post('index.php?m=Index&a=CheckLogin',{"username":username,"verify":verify,"lat":lat,"lng":lng,"city":city,"province":province},
	        function(msg){ 
		        var k = parseInt(msg);
                var str =""; 
                switch(k){
                    case 1:str='true';break;

                    case 2:str= prompt_data_error;break;

                    case 3:str= prompt_moblie_fail;break;

                    case 6:str= prompt_varify_error;break;

                    case 7:str= prompt_varify_less;break;
                }
                if(str!="true"){
                    showmodel(str);
                }else{
                    location.href="index.php?m=Public&a=index";
                }
	    });
     }
}

	//记录光标位置
   var mouse_add="";
   //验证数据
    function check(username,verify,checkbox){ 
      
        re = /^0?1[3|4|5|7|8][0-9]\d{8}$/;
        if(username==""){
            mouse_add ="username";
            showmodel(prompt_tel_less); 
            return false;
        }else if(!re.test(username)){
            mouse_add ="username";
            showmodel(prompt_tel_less); 
            return false;
        }
        if(verify.length<6){
            mouse_add ="verify";
            showmodel(prompt_varify_less);
            return false;
        }
        if(checkbox == false){
            showmodel(prompt_varify_checked);
            return false;
        }
        return true;
     
    }



    //发送验证码
    function get_mobile_code(){
        document.getElementById('zphone').disabled = true;
        var tel=$("#username").val();
        if(tel==""){
            mouse_add="username";
            showmodel(prompt_tel_less);          
            return false;
        }
        var  re = /^0?1[3|4|5|7|8][0-9]\d{8}$/;
        if(re.test(tel)){
            $("#zphone").attr("class","yzm");
            RemainTime();
            $.post('?m=Index&a=SendSMS', {"mobile":jQuery.trim($('#username').val()),"send_code":jQuery.trim($('#send_code').val())}, function(msg) {
                    if(msg==1){
                        showmodel("短信发送成功<br />请留意您的手机短信" );
                    }else if(msg ==-1){
                        $("#zphone").attr("class","yzm");
                        clearTimeout(Account);
                        document.getElementById('zphone').disabled = false;
                        document.getElementById('zphone').value ='获取验证码';
                        showmodel("当日发送条数(5)已发完");
                        
                    }else if(msg ==3){
                        $("#zphone").attr("class","yzm");
                        clearTimeout(Account);
                        document.getElementById('zphone').disabled = false;
                        document.getElementById('zphone').value ='获取验证码';
                        showmodel(prompt_moblie_fail);
                        mouse_add="username";

                    }else{
                        $("#zphone").attr("class","yzm");
                        clearTimeout(Account);
                        document.getElementById('zphone').disabled = false;
                        document.getElementById('zphone').value ='获取验证码';
                        showmodel(prompt_varify_gain_fail);
                    }
            });
        }else{
            mouse_add="username";
            showmodel(prompt_tel_less);
        }
    };
    var iTime = 59;
    var Account;
    function RemainTime(){
        document.getElementById('zphone').disabled = true;
        var iSecond,sSecond="",sTime="";
        if (iTime >= 0){
            iSecond = parseInt(iTime%60);
            iMinute = parseInt(iTime/60)
            if (iSecond >= 0){
                if(iMinute>0){
                    sSecond = iMinute + "分" + iSecond + "秒";
                }else{
                    sSecond = iSecond + "秒";
                }
            }
            sTime=sSecond;
            if(iTime==0){
                clearTimeout(Account);
                $("#zphone").attr("class","yzm");
                sTime='获取验证码';
                iTime = 59;
                document.getElementById('zphone').disabled = false;
            }else{
                Account = setTimeout("RemainTime()",1000);
                iTime=iTime-1;
            }
        }else{
            sTime='没有倒计时';
        }
        document.getElementById('zphone').value = sTime;
    }









	//关闭弹出层
    function closemodel(){
        $(".nb_wrap,.nb").hide(); 
        $("#"+mouse_add).val("");
        $("#"+mouse_add).focus();
        mouse_add='';
        
    }
    //弹出弹出层
    function showmodel(str){
        $(".nb_wrap,.nb_1").html(str);
        $(".nb_wrap,.nb").show(); 
    }


    