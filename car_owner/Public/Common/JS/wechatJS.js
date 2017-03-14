wx.ready(function() {
	wx.onMenuShareAppMessage({
		title : wethatData.title,
		desc : wethatData.desc,
		link : wethatData.link,
		imgUrl : wethatData.imgUrl,
		success : function(res) {
			upApiShareData('onMenuShareAppMessage', res);
		}
	});

	wx.onMenuShareTimeline({
		title : wethatData.title,
		link : wethatData.link,
		imgUrl : wethatData.imgUrl,
		success : function(res) {

			upApiShareData('onMenuShareTimeline', res);
		}
	});
	
	// 2.3 监听“分享到QQ”按钮点击、自定义分享内容及分享结果接口
	wx.onMenuShareQQ({
		title : wethatData.title,
		desc : wethatData.desc,
		link : wethatData.link,
		imgUrl : wethatData.imgUrl,
		success : function(res) {
			upApiShareData('onMenuShareQQ', res);
		}
	});

	// 2.4 监听“分享到微博”按钮点击、自定义分享内容及分享结果接口
	wx.onMenuShareWeibo({
		title : wethatData.title,
		desc : wethatData.desc,
		link : wethatData.link,
		imgUrl : wethatData.imgUrl,
		success : function(res) {
			upApiShareData('onMenuShareWeibo', res);
		}
	});

	wx.onMenuShareQZone({
		title : wethatData.title,
		desc : wethatData.desc,
		link : wethatData.link,
		imgUrl : wethatData.imgUrl,
		success : function(res) {
			upApiShareData('onMenuShareQZone', res);
		}
	});

	upApiShareData = function(type, data) {
		wethat_data = $.param(wethatData);
		if (!data)
			data = [];
		else
			data = $.param(data)
		$.post(shareApiUrl, {
			type : type,
			data : data,
			wethatData : wethat_data
		}, function(res) {
			var sjon_data = $.parseJSON(res);

			if (sjon_data.message) {
				alert(sjon_data.message);
			}
		});
	}

	/*
	 * wx.getNetworkType({ success : function(res) { var networkType =
	 * res.networkType; // 返回网络类型2g，3g，4g，wifi } });
	 */

});

wx.error(function(res) {
	alert(res.errMsg);
});