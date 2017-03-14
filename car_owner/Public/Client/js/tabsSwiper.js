var tabsSwiper = new Swiper('.swiper-container',{
	speed:500,
	onSlideChangeStart: function(){
		$(".tabs .active").removeClass('active');
		$(".tabs a").eq(tabsSwiper.activeIndex).addClass('active');
		$('body,html').animate({scrollTop:0},0);
	}
});

$(".tabs a").on('touchstart mousedown',function(e){
	e.preventDefault()
	$(".tabs .active").removeClass('active');
	$(this).addClass('active');
	tabsSwiper.swipeTo($(this).index());
});

$(".tabs a").click(function(e){
	e.preventDefault();
});
$(".je_jt").click(function () { $(".account_zs_je").toggle();});
	$(".account_zs_je > ul > li").click(function(){
	 var s = $(this).html();
	 $(".je_jt").html(s);
	$(".account_zs_je").hide();
	$(".je_jt").css("background","none");
	});