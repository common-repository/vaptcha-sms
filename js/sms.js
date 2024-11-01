jQuery(function ($) {

    // 设置页获取短信id
    $('#vaptcha_unit_key').blur(function () {
        var ajaxUrl = $("#vaptcha-hidden-url").data("ajax-url");
        var vid = $('#vaptcha_unit_vid').val()
        var key = $('#vaptcha_unit_key').val()
        $('.automatic').show()
        $.ajax({
            type: "get",
            dataType: "json",
            url: ajaxUrl,
            data: {
                action: 'get_smsid',
                id: vid,
                key: key
            },
            success: function (response) {
                if (response.data.code === 200) {
                    $('#vaptcha_smsid').val(response.data.data.smsid)
                    $('#vaptcha_smskey').val(response.data.data.smskey)
                } else {
                    alert(response.data.msg)
                }
                $('.automatic').hide()
            }
        })
    })

    // 后台设置国别码
    $('#get_code').focus(function () {
        $('#code_menu').show()
    })
    $('#admin_phone').focus(function () {
        $('#code_menu').hide()
    })

    $('#more_icon').click(function () {
        if ($("#code_menu").is(":hidden")) {
            $('#code_menu').show()
        } else {
            $('#code_menu').hide()
        }
    })

    //选择国别号下拉点击
    $("#code_menu").children("li").click(function (e) {
        $(this).addClass("active").siblings("li").removeClass("active");
        var code = $(this).data('value');//点击时 当前li的值
        $('#get_code').val(code)
        $('#code_menu').hide()
    })

    // 加载vaptcha
    var operaticon = 'deviceLogin';
    var obj;
    var ajaxUrl = document.getElementById('vaptcha-hidden-url').getAttribute('data-ajax-url')
    var type = document.getElementById('vaptcha-hidden-type').getAttribute('data-vaptcha-type')
    var vid = document.getElementById('hidden-vaptcha-vid').getAttribute('data-vaptcha-vid')
    var smsid = document.getElementById('hidden-vaptcha-smsid').getAttribute('data-vaptcha-smsid')
    var modelid = document.getElementById('hidden-vaptcha-modelid').getAttribute('data-vaptcha-modelid')
    console.log(type)
    vaptcha({
        vid: vid, // 验证单元id
        type: 'invisible', // 显示类型 隐藏式
        scene: 0, // 场景值 默认0
        offline_server: '', //离线模式服务端地址，若尚未配置离线模式，请填写任意地址即可。
        area: 'auto',
    }).then(function (vaptchaObj) {
        $('#get_sms_code').show();
        obj = vaptchaObj //将VAPTCHA验证实例保存到局部变量中
        vaptchaObj.listen('pass', function () {
            // 验证成功进行后续操作
            if (operaticon === 'register') {
                sendSmsCode(vaptchaObj.getServerToken(), 'register')
            }
            if (operaticon === 'deviceLogin') {
                deviceLogin(vaptchaObj.getServerToken())
            }
            if (operaticon === 'smsLogin') {
                addDevice(vaptchaObj.getServerToken())
            }
            if (operaticon === 'getSmsCode') {
                sendSmsCode(vaptchaObj.getServerToken(), 'login')
            }
            vaptchaObj.reset() //重置验证码
        })
        //关闭验证弹窗时触发
        vaptchaObj.listen('close', function () {
            //验证弹窗关闭触发
        })
    })

    var smsTokenServer = '';
    //发送验证码
    function sendSmsCode(token, key) {
        $.ajax({
            type: 'post',
            url: ajaxUrl,
            dataType: "json",
            data: {
                action: 'send_smscode',
                phone: $('#user_phone').val(),
                token: token.token,
                server: token.server,
                smsid: smsid,
                countrycode: $('#country_code').val(),
                templateid: modelid,
            },
            success: function (res) {
                if (res.data === 200) {
                    smsTokenServer = token
                    key === 'register' ? sendCountdown() : loginSendCountdown()
                } else {
                    alert(res.data)
                }
            }
        })
    }

    //设备登录
    function deviceLogin(token) {
        $.ajax({
            type: 'get',
            url: ajaxUrl,
            dataType: "json",
            data: {
                action: 'device_login',
                phone: $('#user_phone').val(),
                token: token.token,
                server: token.server
            },
            success: function (res) {
                if (res.data.code === 444) {
                    //   $('#loginform').before('<p id="ajax-error-tips" class="message" style="border-left-color:red;display: none;">该手机号为未绑定已注册用户 <button id="to-bind-user" style="margin-top: -5px;" type="button" class="button button-primary">绑定用户</button><br> </p>');
                    alert(res.data.msg)
                } else if (res.data.code === 200) {
                    $('#loginform').submit()
                } else if (res.data.code === 400) {
                    $('#sms-code').show()
                    $('#submit_form_button').hide()
                    $('#sms_login_button').show()
                    operaticon = 'smsLogin'
                    sendSmsCode(token, 'login')
                }
                // }
            }
        })
    }


    //验证注册表单
    $('#registerform').before('<p id="have_null" class="message" style="border-left-color:#e00101;display: none"><strong>错误：</strong>请将信息填写完整。</p>');
    $('#registerform').before('<p id="wrong_phone" class="message" style="border-left-color:#e00101;display: none"><strong>错误：</strong>请填写正确的手机号码。</p>');
    $('#registerform').before('<p id="wrong_email" class="message" style="border-left-color:#e00101;display: none"><strong>错误：</strong>请填写正确的手机号码。</p>');
    $('#registerform').before('<p id="exist_name" class="message" style="border-left-color:#e00101;margin-bottom: 0;padding: 5px 12px;display: none"><strong>错误：</strong>该用户名已被注册。</p>');
    $('#registerform').before('<p id="exist_email" class="message" style="border-left-color:#e00101;margin-bottom: 0;padding: 5px 12px;display: none"><strong>错误：</strong>该邮箱地址已被注册。</p>');
    $('#registerform').before('<p id="exist_phone" class="message" style="border-left-color:#e00101;margin-bottom: 0;padding: 5px 12px;display: none"><strong>错误：</strong>该手机号已绑定其他用户。</p>');
    $('#registerform').before('<p id="wrong_code" class="message" style="border-left-color:#e00101;margin-bottom: 0;padding: 5px 12px;display: none"><strong>错误：</strong>短信验证码错误。</p>');

    $('#submit-btn').click(function () {
        var username = $('#user_login').val()
        var email = $('#user_email').val()
        var phone = $('#user_phone').val()
        var smscode = $('#register_sms_code').val()
        if (username == '' || smscode == '' || email == '' || phone == '') {
            $('#have_null').show()
            return
        } else {
            $('#have_null').hide()
        }
        var pattern = /^([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|\_|\.]?)*[a-zA-Z0-9]+\.[a-zA-Z]{2,3}$/;
        strEmail = pattern.test(email);
        if (!strEmail) {
            $('#wrong_email').show()
            return
        } else {
            $('#wrong_email').hide()
        }
        if (phone === '' || !phone.match(/^1[3-9]\d{9}$/)) {
            $('#wrong_phone').show()
            return
        } else {
            $('#wrong_phone').hide()
        }
        if (smscode.length < 6 || smscode.length > 6) {
            $('#wrong_code').show()
            return;
        } else {
            $('#wrong_code').hide()
        }
        $.ajax({
            type: 'post',
            url: ajaxUrl,
            dataType: "json",
            data: {
                action: 'submit_register',
                username: username,
                email: email,
                phone: phone,
                smscode: smscode
            },
            success: function (res) {
                if (res.success === true) {
                    $('#registerform').submit()
                } else if (res.success === false) {
                    if (res.data.name != null) {
                        $('#exist_name').show()
                    } else {
                        $('#exist_name').hide()
                    }
                    if (res.data.email != null) {
                        $('#exist_email').show()
                    } else {
                        $('#exist_email').hide()
                    }
                    if (res.data.phone != null) {
                        $('#exist_phone').show()
                    } else {
                        $('#exist_phone').hide()
                    }
                    if (res.data.smscode != null) {
                        $('#wrong_code').show()
                    } else {
                        $('#wrong_code').hide()
                    }
                }
            }
        })
    })

    // 添加登录设备
    function addDevice(token) {
        $.ajax({
            type: 'post',
            url: ajaxUrl,
            dataType: "json",
            data: {
                action: 'add_device',
                phone: $('#user_phone').val(),
                token: token.token,
                server:token.server,
                smsid: smsid,
                code: $('#sms_code').val()
            },
            success: function (res) {
                if (res.data.code === 200) {
                    $('#loginform').submit()
                } else {
                    alert(res.data.msg)
                }
            }
        })
    }

    // 重新获取验证码短信时间间隔
    var waitTime = 60;
    function sendCountdown() {
        if (waitTime > 0) {
            $('#get_sms_code').text(waitTime + 's后再次发送').attr("disabled", true);
            waitTime--;
            setTimeout(sendCountdown, 1000);
        } else {
            $('#get_sms_code').text('发送验证码').attr("disabled", false).fadeTo("slow", 1);
            waitTime = 60;
        }
    }
    function loginSendCountdown() {
        if (waitTime > 0) {
            $('#get_login_sms_code').text(waitTime + 's后再次发送').attr("disabled", true);
            waitTime--;
            setTimeout(loginSendCountdown, 1000);
        } else {
            $('#get_login_sms_code').text('发送验证码').attr("disabled", false).fadeTo("slow", 1);
            waitTime = 60;
        }
    }

    //切换账号密码登录
    $('#name-or-email-login').click(function () {
        $(this).hide();
        $('#phone-login').show();
        $('#phone-verify-fields').hide();
        $('#sms_login_button').hide();
        $('#sms-code').hide();
        $('#user_login').parent().show();
        $('#user_login').parent().find('label').text('用户名、手机号或电子邮箱地址');
        $('#user_pass').parent().show();
        $('.user-pass-wrap').show(
        );
        $('#user_pass').attr('disabled', false)

        $('#wp-submit').val('登录').css({"display": "block", "margin-right": "0"});
        $('#select_phone').hide();
        $('#submit_form_button').hide();
    })

    //切换免密登录
    $('#phone-login').click(function () {
        $(this).hide();
        $('#name-or-email-login').show();
        $('#phone-verify-fields').show();
        $('#user_login').parent().hide();
        $('#user_pass').parent().hide();
        $('.user-pass-wrap').hide();
        $('#wp-submit').val('登录').css({"display": "none", "margin-right": "0"});
        $('#select_phone').show();
        $('#submit_form_button').show();
    })

    // 注册获取短信验证码
    $('#get_sms_code').click(function () {
        operaticon = 'register';
        var phone = $("#user_phone").val();
        if (phone === '' || !phone.match(/^1[3-9]\d{9}$/)) {
            alert('手机号格式错误');
            $("#phone").focus();
            return;
        }
        obj.validate()
    })

    //登录获取短信验证码
    $('#get_login_sms_code').click(function () {
        operaticon = 'getSmsCode';
        var phone = $("#user_phone").val();
        if (phone === '' || !phone.match(/^1[3-9]\d{9}$/)) {
            alert('手机号格式错误');
            $("#phone").focus();
            return;
        }
        obj.validate()
    })

    //免密登录按钮
    $('#submit_form_button').click(function () {
        var phone = $("#user_phone").val();
        if (phone === '' || !phone.match(/^1[3-9]\d{9}$/)) {
            alert('手机号格式错误');
            $("#phone").focus();
            return;
        }
        obj.validate()
    })

    //验证码 登录按钮
    $('#sms_login_button').click(function () {
        operaticon = 'smsLogin';
        addDevice(smsTokenServer)
        // obj.validate()
    })

    //输入国别号弹出下拉
    $('#country_code').focus(function () {
        $('#dropdown-menu').show()
    })
    $('#country_code').blur(function () {
        // $('#dropdown-menu').hide()
    })


    //选择国别号弹出下拉
    $('#btn-down').click(function () {
        if ($("#dropdown-menu").is(":hidden")) {
            $('#dropdown-menu').show()
        } else {
            $('#dropdown-menu').hide()
        }
    })
    //选择国别号下拉点击
    $("#dropdown-menu").children("li").click(function (e) {
        $(this).addClass("active").siblings("li").removeClass("active");
        var code = $(this).data('value');//点击时 当前li的值
        $('#country_code').val(code)
        $('#dropdown-menu').hide()
    })


});