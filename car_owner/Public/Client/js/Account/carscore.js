//统计字数countsize()
  function countsize(){
    var content = $("#content").val();
    var leng = content.length;
    $("#leng").html(leng);
  }
  function postscore(){
      var score = $("#score").val();
      var content = $("#content").val();
      var currentparkid = $("#currentparkid").val();
      var currentownerid = $("#currentownerid").val();
      var washerid = $("#washerid").val();
      var washrecordid = $("#washrecordid").val();
      var parkname = $("#parkname").val();
       
       layer.load();
      if(check(content,score)){
          $.post('index.php?m=Account&a=carscore',{"textarea":content,"score":score,"currentparkid":currentparkid,
             "currentownerid":currentownerid,"washerid":washerid,"washrecordid":washrecordid,"parkname":parkname},
            function(msg){
                var k = parseInt(msg);
                layer.closeAll('loading');
                if(k==1){
                  showmodel(prompt_advice_success);
                  setTimeout(function(){
                  javascript:history.go(-1);},1500);
                }else if(k==2){
                  showmodel(prompt_advice_tune);
                  location.reload(true);
                }else{
                  showmodel(prompt_advice_fail);
                }
            });
      }
  }

   //验证
  function check(content,score){
  	// var content = $("#content").val();
  	if(Trim(content,'g') ==''){
  		showmodel(prompt_advice_less);
  		return false;
  	}else if(score ==''){
      showmodel(prompt_advice_score);
      return false;
    }
    return true;
  }
  //重置
  function reset(){
      $("#content").val("");
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