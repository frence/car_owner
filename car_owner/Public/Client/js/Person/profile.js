   self.opener.location.reload();
	//关闭弹出层
    function closemodel(){
        $(".nb_wrap,.nb").hide(); 
         
        
    }
    //弹出弹出层confirm
    function showmodel(str){
        $("#confirm_html").html(str);
        $(".nb_wrap").show();
        $("#confirm").show(); 
         
    }
    function isloginout(){
    	showmodel("确定要注销账户吗");
    }

    function onsure(){
        winClose()
    	 //window.location.href='?m=Person&a=logout';
    }

    function winClose(){
      $.post('?m=Person&a=logout',{},
        function(msg){
            WeixinJSBridge.call('closeWindow');
        });
        
    }

