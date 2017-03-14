
//统计字数countsize()
  function countsize(){
    var content = $("#content").val();
    var leng = content.length;
    $("#leng").html(leng);
  }




//类似QQ空间一样到页面底部无刷新加载
//防止异步刷新慢出现异常
var ajax_flush =1;
$(function(){   
  $(window).scroll(function() { 
      //当内容滚动到底部时加载新的内容  
      if ($(this).scrollTop() + $(window).height() + 20 >= $(document).height() && $(this).scrollTop() > 20) {  
          //当前要加载的页码 
          if(ajax_flush ==1){ 
              ajax_flush  ==0; 
              LoadPage();
         }
      }
        
  });  
});
 
//评论页码
var content_page =1;
function LoadPage(){
  //防止无休止的刷新
  if(content_page==-1){
    return;
  }
  var css_js_addr = $("#css_js_add").val();
  var str ="";
  var page =content_page+1;
  $.post('?m=Person&a=contentPage',{"page":page},
  function(msg){
    content_page++;
    ajax_flush  ==1;
    var data =JSON.parse(msg);   
    //无数据后不再刷新
    if($.isEmptyObject(data))
      income_page =-1;
      $.each(data, function (entryindex, entry) {
         
        str +='<li><div class="jy_center_l_a"><p class="jy_center_l_ti">';
        str +='<span><img src="'+css_js_addr+'images/el'+entry.Score+'.png"></span>设备号：'+entry.washercode+'</p>';
        str +='<p>'+entry.Title+'</p>';
        str +='<p style="color:#ccc;">';
        if(entry.Title !="" && entry.reply==null){
           str +='<a href="index.php/index?m=Person&a=reply&id='+entry.ScoreId+'">回复</a>';
        }
        if(nulltoNone(entry.reply)!="")
          str +="<p>回复:"+nulltoNone(entry.reply)+'</p>';
        str +="</div></li>";
    });
    $("#content_id").append(str); 
  });
}
//时间戳转换时间格式1970年1月01日
function dateToString(nS){
  var myDate = new Date(nS*1000);
  var year = myDate.getFullYear();
  var month = myDate.getMonth() + 1;
  var day = myDate.getDate();
  return year + '年' + month + '月' + day + '日';
}

//将null转换为空值
function nulltoNone(str){
  if(str==null){
    return '';
  }
  else
    return str
}



