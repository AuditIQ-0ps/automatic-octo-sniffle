if (document.querySelector(".app-screens-carousel")) {
    new Swiper(".app-screens-carousel", {
        slidesPerView: 1, spaceBetween: 20, autoplay: {
            delay: 3000,
        }, loop: true, breakpoints: {
            576: {
                slidesPerView: 2,
            }, 768: {
                slidesPerView: 3,
            }, 992: {
                slidesPerView: 4, spaceBetween: 0,
            },
        },
    });
}

if (document.querySelector(".testimonials-carousel")) {
    new Swiper(".testimonials-carousel", {
        slidesPerView: 1, loop: true, pagination: {
            el: ".testimonials-carousel .swiper-pagination", type: "bullets", clickable: true,
        },
    });
}

$('.submit-btn').click(function () {
    var value = $('#email').val();
    if (!value) {
        $('.msg-display').html('<small class="text-danger">Please Enter Email.</small>')
    }
    $.ajax({
        url: "sendmail.php", type: "post", data: {email: value}, success: function (html) {
            html = JSON.parse(html);
            if (html.status) {
                $('.msg-display').html('<small class="text-success">' + html.message + '</small>')
                $('#email').val("");
            } else {
                $('.msg-display').html('<small class="text-danger">' + html.message + '</small>')
            }
        }
    });
});

$('.submit-btn-2').click(function () {
    var value = $('#user_email').val();
    if (!value) {
        $('.msg-display1').html('<small class="text-danger">Please Enter Email.</small>')
    }
    $.ajax({
        url: "sendmail.php", type: "post", data: {email: value}, success: function (html) {
            html = JSON.parse(html);
            if (html.status) {
                $('.msg-display1').html('<small class="text-success">' + html.message + '</small>')
                $('#user_email').val("");
            } else {
                $('.msg-display1').html('<small class="text-danger">' + html.message + '</small>')
            }
        }
    });
});

function blockUI() {
    $.blockUI({
        message: '<img src="'+ baseURL +'public/landing/images/loader.gif" style="height:75px" />  '
    });
}

function unBlockUI() {
    $.unblockUI();
}

$('#form').submit(function (e) {

    e.preventDefault()
    $('#error').empty()
    data = $(this).serialize();
    console.log(data)
    $(document).find('.error').removeClass('error');
    $.ajax({
        url: 'contact.php',
        method: 'post',
        data: data,
        beforeSend: function () {
            blockUI();
        },
        success: function (data) {
            if (JSON.parse(data).email_set) {
                $('#email').val(JSON.parse(data).email_set)
            }
            if (JSON.parse(data).msg) {
                $(document).find("#form")[0].reset();
                $('#error').append('<div class="alert alert-success" role="alert">\n' +
                    JSON.parse(data).msg +
                    '</div>')
                $("#form")[0].reset();
                unBlockUI();
                return
            }
            // data=JSON.parse(data)
            if (JSON.parse(data).email != '') {
                $(document).find('[name=email]').addClass("error");
                $('#error').append(JSON.parse(data).email + "</br>")
            }
            if (JSON.parse(data).first_name != '') {
                $(document).find('[name=first_name]').addClass("error");
                $('#error').append(JSON.parse(data).first_name + "</br>")
            }
            if (JSON.parse(data).last_name != '') {
                $(document).find('[name=last_name]').addClass("error");
                $('#error').append(JSON.parse(data).last_name + "</br>")
            }
            if (JSON.parse(data).phone_no != '') {
                $(document).find('[name=phone_no]').addClass("error");
                $('#error').append(JSON.parse(data).phone_no + "</br>")
            }
            if (JSON.parse(data).company_name != '') {
                $(document).find('[name=company_name]').addClass("error");
                $('#error').append(JSON.parse(data).company_name + "</br>")
            }
            if (JSON.parse(data).information != '') {
                $(document).find('[name=information]').addClass("error");
                $('#error').append(JSON.parse(data).information + "</br>")
            }
            if (JSON.parse(data).size != '') {
                $(document).find('[name=size]').addClass("error");
                $('#error').append(JSON.parse(data).size + "</br>")
            }
            unBlockUI();
        }
    })
})
