    
    function postpas(){
        var oldpas = $("#oldpas").val();
        var username = $("#username").val();
        var pas = $("#newpas").val();
        var repas =$("#repas").val();   
        if(check(oldpas,pas,repas)){
            $.post('?m=Person&a=passwordbyverify',
                {"oldpas":oldpas,"username":username,"pas":pas,"repas":repas},
                function(msg){ 
                    var k = parseInt(msg);
                    var str ;
                    switch(k){
                        case 1: str ="true";break;
                        case 2: str = prompt_pass_update_fail;break;
                        case 3: str = prompt_pass_repas_same;break;
                        case 4: str = prompt_pass_old_error;break;
                    }
                    if(str !="true"){
                        showmodel(str);
                    }else{
                        showmodel(prompt_pass_success);
                        setTimeout(function(){
                         location.href="?m=Public&a=login"},1500);
                    }
                });
        }
    }


    //记录光标位置
   var mouse_add="";
    function check(oldpas,pas,repas){
        if(oldpas.length<6){
            //alert("请输入验证码");
            mouse_add="oldpas";
            showmodel(prompt_pass_old_less);
            return false;
        }
        if(pas.length<6){
            //alert("密码长度至少六位");
            showmodel(prompt_pass_new_less);
            mouse_add="newpas";
            return false;
        }
        if(repas.length<6){
            showmodel(prompt_pass_repas_less);
            mouse_add="repas";
            return false;
        }
        if(pas!=repas){
            //alert("两次密码输入不一致");
            mouse_add="newpas";
            showmodel(prompt_pass_repas_same);
            $("#repas").val("");
            $("#newpas").val("");
            return false;
        }
        return true;
    }
     

    //关闭弹出层
    function closemodel(){
        $(".nb_wrap,.nb").hide(); 
        $("#"+mouse_add).focus();
        
    }
    //弹出弹出层
    function showmodel(str){
        $(".nb_wrap,.nb_1").html(str);
        $(".nb_wrap,.nb").show(); 
    }