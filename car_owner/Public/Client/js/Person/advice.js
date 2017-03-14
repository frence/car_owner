//统计字数countsize()
  function countsize(){
    var content = $("#content").val();
    var leng = content.length; 
    $("#leng").html(leng);
  }
  function postadvice(){
      var content = $("#content").val();
      layer.load();
      if(check(content)){
          $.post('?m=Person&a=advice',{"content":content},
            function(msg){
                layer.closeAll('loading');
                var k = parseInt(msg);
                if(k==1){
                  showmodel(prompt_advice_success);
                  setTimeout(function(){
                  location.href="?m=Person&a=index"},1500);
                }else{
                  showmodel(prompt_advice_fail);
                }
            });
      }
  }

   //验证
  function check(content){
   
  	if(Trim(content,'g') ==''){
  		showmodel(prompt_advice_less);
  		return false;
  	}
    return true;
  }
  //重置
  function reset(){
      $("#content").val("");
      $("#leng").html('0');
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