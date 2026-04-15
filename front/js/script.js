$(function() {

    $('#toggleVisibility').click(function() {
        var currentType = $(this).prev('input').attr('type');
        $(this).toggleClass('active');
        if (currentType == 'text') {
            $(this).prev('input').attr('type', 'password');
        } else if (currentType == 'password') {
            $(this).prev('input').attr('type', 'text');
        }
    });

    $('#menuToggle').click(function() {
        $('.site-main-nav').addClass('show');
    });
    $('#menuClose').click(function() {
        $('.site-main-nav').removeClass('show');
    });


    $('#shareToggle').click(function() {
        $(this).next('.dropdown').toggleClass('show');
    });

    /* $('.bookmark-btn,.bookmark-profile').click(function() {
        $(this).toggleClass('saved');
    }); */

    $(window).resize(function() {
        if ($(this).width() > 992) {
            if ($('.site-main-nav').hasClass('show')) {
                $('.site-main-nav').removeClass('show');
            }
        }
        if ($(this).width() > 768) {
            $('.overlay').removeClass('show');
        }
    });

    $('.search-toggle').click(function() {
        $('.search-form').css({
            'visibility': 'visible'
        });
    });

     $('.search-close').click(function() {
        $('.search-form').css({
            'visibility': ''
        });
    });
	
	if( $('.front-user-menu').length > 0 ) {
		$( ".dropdown-menu" ).hide();
		$( ".front-user-menu .showdropdownmenu" ).click(function() {
			$( ".dropdown-menu" ).toggle(300);
		});
	}
});