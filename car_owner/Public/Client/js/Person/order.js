
//类似QQ空间一样到页面底部无刷新加载
$(function(){   
  $(window).scroll(function() { 
      //当内容滚动到底部时加载新的内容  
      if ($(this).scrollTop() + $(window).height() + 20 >= $(document).height() && $(this).scrollTop() > 20) {  
          //当前要加载的页码  
           LoadContents();

      }
        
  });  
});

//订单处理//
//记录完成订单/失败订单页数
var order_complete_sum =1;
var order_fail_sum =1;

//加载订单内容
function LoadContents(){
  //type订单类型；page页数;url地址,str添加的内容
  var type;
  var page;
  var url;
  var str ="";
  var css_js_addr=$("#css_js_adr").val();
  //判断订单类型
  if($("#complete").hasClass("active")){
    type = 1;
    url = '?m=Person&a=orderOwncomplete';
    page = order_complete_sum +1;
  }else{
    type = 2;
    url ='?m=Person&a=orderOwnfail';
    page = order_fail_sum +1;
  }
  //防止恶意刷新--无数据后不再刷新
  if((order_complete_sum==-1&&type==1)){
    return;
  }
  if((order_fail_sum==-1&&type==2)){
    return ;
  }
  //刷新内容
  $.post(url,{"page":page},
    function(msg){
    //订单完成
      var data =JSON.parse(msg); 
      if(type ==1){
        order_complete_sum ++;
        if($.isEmptyObject(data))
          order_complete_sum =-1;
        $.each(data, function (entryindex, entry) {
          str +='<li><div class="jy_center_l"><p class="jy_center_l_ti">'+entry.ParkName+'</p>';
          str +='<p>用户：'+entry.mobile+'</p><p>评价：<img src="'+css_js_addr+'images/el'+entry.Score+'.png"></p>';
          str +='<p>评价内容：'+entry.Title+'</p><p class="jy_center_l_time">'+dateToString(entry.addtime)+'</p></div>';
          str +='<div class="jy_center_r"><p class="jy_center_r_m">￥'+entry.cost+'元</p>';
          str +='<p>服务模式：'+entry.servicetype+'</p><p class="jy_center_r_color">交易完成</p>';
          if(entry.Title !="暂无评价"){
            str +='<p class="jy_center_r_btn"><button id="reply" onclick="replay(this)" replyid="'+entry.ScoreId;
            str +='">回复</button></p></div></li>';
          }else{
            str +='</div></li>';
          }
          
        });
        $("#content_compl").append(str);  
        //订单失败
      }else{
        order_fail_sum ++; 
        if($.isEmptyObject(data))
          order_fail_sum =-1;
        $.each(data, function (entryindex, entry) {
          str +='<li><div class="jy_center_l"><p class="jy_center_l_ti">'+entry.ParkName;
          str +='</p><p>用户：'+entry.mobile+'</p>';
          str +='<p class="jy_center_l_time">'+dateToString(entry.addtime);
          str +='</p></div><div class="jy_center_r"><p class="jy_center_r_m">￥'+entry.cost+'元</p>';
          str +='<p>服务模式：'+entry.servicetype+'</p><p class="jy_center_r_color">交易失败</p>';
          str +='</div></li>';
           
        });
        $("#content_fail").append(str);
      }
  });
}
/*订单结束*/