$(function() {
	$(document).on('click','.show_hide_list',function(){
		var currentShow = $(this).attr('data-id');		
		if( $('#'+currentShow).length > 0 ) {			
			$('#'+currentShow).find('li').css( 'display','block');
		}
		$(this).hide();
	});
	
	$(document).on('click','.bookmark-profile',function(e) {
		e.preventDefault();
		jQuery("#bookmark").html('').hide();  
		jQuery("#bookmark_error").html('').hide('');
		
		var currentShow = $(this);		
		if( $(this).hasClass('saved') ){
			var ajaxUrl = window.getBookmarkRemoveUrl;
			var data = {
				'_token': window._token,
				'bookmark_id': currentShow.attr('data-bookmarkId')				
				};
		} else {			
			var ajaxUrl = window.getBookmarkSaveProfileUrl;	
			var data = {
				'_token': window._token,				
				'profile_id': currentShow.attr('data-profileId'),
				'user_id': currentShow.attr('data-userId'),
			};
		}	
		showLoader();
		jQuery.ajax({
        url: ajaxUrl,
        method: 'post',
        dataType: 'JSON',
        data: data,
        success: function (response) {
			if (response.success == true) {
				currentShow.toggleClass('saved');
            }
			if( response.bookmark_id != '' )
				currentShow.attr('data-bookmarkId',response.bookmark_id );
			else
				currentShow.attr('data-bookmarkId','' );
			hideLoader();			
			jQuery("#bookmark").html( response.message ).show('d-none');  
			setTimeout(function () { jQuery("#bookmark").hide(200); },4000);            
        },error:function(){
			hideLoader();
			jQuery("#bookmark_error").html('Issue in bookmark. Please try again later').show('d-none'); 
		}
		});
	});
	
	$(document).on('click','.bookmark-btn',function(e) {
		jQuery("#bookmark").html('').hide();  
		jQuery("#bookmark_error").html('').hide('');
		e.preventDefault();
		var currentShow = $(this);		
		if( $(this).hasClass('saved') ){
			var ajaxUrl = window.getBookmarkRemoveUrl;				        			
			var data = {
				'_token': window._token,
				'bookmark_id': currentShow.attr('data-bookmarkId'),			
			}
		} else {			
			var ajaxUrl = window.getBookmarkSaveUrl;			
			var data = {
				'_token': window._token,				
				'video_id': currentShow.attr('data-videoId'),
				'user_id': currentShow.attr('data-userId'),
			};
		}		
		
		showLoader();
		jQuery.ajax({
        url: ajaxUrl,
        method: 'post',
        dataType: 'JSON',
        data: data,
        success: function (response) {
			if (response.success == true) {
				currentShow.toggleClass('saved');
            }
			if( response.bookmark_id != '' )
				currentShow.attr('data-bookmarkId',response.bookmark_id );
			else
				currentShow.attr('data-bookmarkId','' );
			hideLoader();
			jQuery("#bookmark").html( response.message ).show('d-none');  
			if( $('#student_bookmark').length > 0 )
				window.location.reload();
			setTimeout(function () { jQuery("#bookmark").hide(200); },4000);
        },error:function(){
			hideLoader();
			jQuery("#bookmark_error").html('Issue in bookmark. Please try again later').show('d-none');  
			// setTimeout(function () { window.location.reload() },4000);
		}
		});
	});
	
	
	$(document).on('click','#show_hide_bio',function(){
		$('.short_bio').remove();
		$('.full_bio').removeClass('d-none');
		$(this).hide();
	});
	
	if( $('#player').length > 0 ) {
		var player = new Plyr('#player'); 
		player.on('playing', function() {			
			jQuery.ajax({
				url: window.saveVideoViews,
				method: 'post',
				dataType: 'JSON',
				data: {
					'_token': window._token,
					'video_id': $('#current_view_video').val()
				},
				success: function (response) {	
					if( response.total_views > 0 )
						$(".videoview_"+$('#current_view_video').val()).html( response.total_views );
				}
			});			
		});
	}
	/* $(document).on('click','.showVideo',function() {
		src = $(this).attr("data-url");	
		if( src != '' ) {
			poster = $(this).attr("data-image")||""; 
			player.source = {
				type: 'video',
				title: 'Example title',
				sources: [{
				  src: src,              
				  size: 720
				}],
				poster: poster
			};     
			$('#videopreview').modal('show');
			$('#current_view_video').val( $(this).attr("data-videoId") );
		}
	});
	
	$('#videopreview').on('hidden.bs.modal', function (e) {
		player.stop();
		//player.pause();
	}); */
	
	
	$(document).on('change','#sort_video',function() {		
		showLoader();
		jQuery.ajax({
			url: window.getFilterVideos,
			method: 'post',
			dataType: 'JSON',
			data: {
					'_token': window._token,
					'category_id': $('#currentCategory').val(),
					'sort_by': $(this).val(),
					'video_title': $('#video_title_search').val(),
			},
			success: function (response) {
				$('.video_filter_html').html( response.html );
				hideLoader();            
			}
		});
	});
	
});

/* Hide Loader*/
function hideLoader(){
    jQuery("div#loader").hide();
}

/* show Loader */
function showLoader(){
    jQuery("div#loader").show();
}