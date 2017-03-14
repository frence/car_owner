function bindGetAjaxData(bindtype, url){
	$(inputId).bind(
	bindtype,
	function (){
		var inputStr = $.trim($(inputId).val());
		if(inputStr){
			$.get(
			url,
			{data:inputStr},
			function(jsonData){
				set_company_empty();
				if(jsonData.status == 1){
					$(outputId).show();
					$.each(jsonData.content, function(i){
						$(outputId).append('<li data-filter-camera-type="' + jsonData.content[i].id + '"><a data-toggle="tab" onclick="set_company_val(' + jsonData.content[i].id + ');" href="javascript:void(0)">' + jsonData.content[i].company_name + '</a></li>');
					});
				}

			})
		}
		else {
			set_company_empty();
			$(inputHId).val('');
		}

	});
}


function set_company_val(id){
	$(inputHId).val(id);
	$(inputId).val($('li[data-filter-camera-type="' + id + '"]').text());
	set_company_empty();
}


function set_company_empty(){
	$(outputId).hide();
	$(outputId).empty();
}

