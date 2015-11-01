$(document).ready(function(){
	$('select.perpage').on('change', function(){
		var url = window.location.href.split('?'),queries=url.length>1?url[1].split('&'):[],query = '';
		for (var i = queries.length - 1; i >= 0; i--) 
			if (queries[i] && !/^limit=/i.test(queries[i]))
				query += (query?'&':'')+queries[i];
		query += '&limit='+$(this).val();
		window.location.href = url[0]+'?'+query;
	});
	$('form.form-horizontal').each(function(){
		$(this).find(':input:visible:enabled:first').focus();
		if ($(this).find('label.required').each(function(){
			$(this).next().find(':input[name="'+$(this).prop('for')+'"]').prop('required',true);
			$(this).html(function(i,v){
				return v+' <sup style="color:red">*</sup>';
			});
		}).length > 0) {
			$(this).find('div.form-group:last-child').clone().
				find('div[class*=col]').html('<span style="color:red">*</span>) required field').
				appendTo(this);
		}
	});
	if (typeof $.fn.summernote !== 'undefined')
		$('.use-summernote').summernote({
			height: 200,
			toolbar: [
			    ['style', ['style']],
			    ['font', ['bold', 'italic', 'underline', 'clear']],
			    ['fontname', ['fontname']],
			    ['color', ['color']],
			    ['para', ['ul', 'ol', 'paragraph']],
			    ['height', ['height']],
			    ['table', ['table']],
			    ['insert', ['link', 'hr']],
			    ['view', ['fullscreen', 'codeview']],
			    ['help', ['help']]
			  ]
		});
	$('.use-datepicker').each(function(){
		$(this).prop('placeholder','format TAHUN-BULAN-TANGGAL, contoh: 2015-10-10').datepicker({format:'yyyy-mm-dd'});
	});
});