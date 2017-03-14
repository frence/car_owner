//统计字数countsize()
  function countsize(){
    var content = $("#content").val();
    var leng = content.length;
    $("#leng").html(leng);
  }
  //发送回复
  function postreply(){
      var content = $("#content").val();
      if(check(content)){
          $.post('?m=Person&a=reply',{"content":content},
            function(msg){
                var k = parseInt(msg);
                if(k==1){
                  showmodel(prompt_reply_success);
                  setTimeout(function(){
                   location.href="?m=Person&a=index";},1500);
                }else{
                  showmodel(prompt_reply_fail);
                }
            });
      }
  }

  //验证
  function check(content){
  	
  	if(content ==''){
  		showmodel(prompt_reply_less);
  		return false;
  	}
  	return true; 
  }

    function Trim(str,is_global)
    {
        var result;
        result = str.replace(/(^\s+)|(\s+$)/g,"");
        if(is_global.toLowerCase()=="g")
        {
            result = result.replace(/\s/g,"");
        }
        return result;
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