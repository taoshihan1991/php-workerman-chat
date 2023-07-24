$(function () {
    // 打开顶部菜单
    $('#nav-icon2').click(function() {
        $(this).toggleClass('open');
        $('.aichat-nav-mobile').toggleClass('open');
    });
    // 返回顶部
    $(window).scroll(function() {
        if ($(window).scrollTop() >= 50) {
            $(".aichat-back-to-top").fadeIn();
        } else {
            $(".aichat-back-to-top").fadeOut();
        }
    });
    $(".aichat-back-to-top").click(function() {
        $('html,body').animate({
            scrollTop: 0
        }, 500);
    });

    $('.step-item').mouseover(function () {
        $('.step-num').removeClass('active')
        $(this).children('.step-num').addClass('active')

        // $(".step-item img").first().removeClass('hide'); 
        // $(".step-item img").last().addClass('hide'); 
        // 隐藏所有active
        $(".step-item .img2").addClass('hide');
        // 显示所有
        $(".step-item .img1").removeClass('hide');


        $(this).children('.img2').removeClass('hide')
        $(this).children('.img1').addClass('hide')
        // $(this).children('img').first().addClass('hide')


        // $(this).css({
        //     'backgroundColor':'#df0001',
        //     'color':'#fff'
        // });
    });

    // $('.pageBtn').mouseout(function(){
    //     $(this).css({
    //         'backgroundColor':'#fff',
    //         'color':'#000'
    //     });
    // });


    // 设置导航
    for (var i = 0; i < $('.header-navbar .nav-items li').length; i++) {
        if (location.href.indexOf($('.header-navbar .nav-items li').eq(i).find('a').attr('href')) > -1) {
            //$('.header-navbar .nav-items li').eq(i).addClass('active');
        }
    }


    $(".product_description .detail").mouseover(function () {
        $('.product_description .detail').not(this).removeClass('detail-active')
        $(this).addClass('detail-active')
        var src = $(this).data('src')
        $('.detail-img').prop('src', src)
    })


    // 初始化复制功能 
    var clipboard = new Clipboard('.copy');
    clipboard.on('success', function (e) {
        layer.msg('复制成功');
    });
    // 首页五大使用场景切换
    $('#usage_scenario_tab .tab').hover(function () {
        var index = $(this).index();
        $(this).addClass('active').siblings().removeClass('active');
        $('#usage_scenario_tab .tab-box').eq(index).css('display', 'block').siblings('.tab-box').css('display', 'none');
    });
    // 多六大核心客服功能展示
    $('#core_fun .fun-item').hover(function () {
        var index = $(this).index('#core_fun .fun-item');
        $('#core_fun .fun-item').removeClass('hover');
        $(this).addClass('hover');
        $('#core_fun .fun-img-box img').removeClass('show');
        $('#core_fun .fun-img-box img').eq(index).addClass('show');
    });
    // 选择接入方式
    $('.entrance-list-js').on('click', function () {
        var url = $(this).find('a').data('url');
        $(this).addClass('select').siblings('.entrance-list-js').removeClass('select');
        $('#target_url').val(url);
    });

    function getQueryString(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]);
        return null;
    }

    $('.entrance-next-js').on('click', function () {
        var promise = $('#promise').val();
        if (promise) {
            window.location.href = $('#target_url').val();
        } else {
            layer.alert('您可接入的小程序/公众号数量已达上限，请升级版本或购买接入名额后再试', {
                title: '提示',
                btn: ['去购买']
            }, function () {
                window.location.href = '/vip/price';
            })
        }
    });

    $('.yun-next-js').on('click', function () {
        if (!$('.pay-protocol-link input').prop('checked')) {
            layer.alert('请阅读并接受使用协议')
            return
        }
        $.ajax({
            type: "post",
            url: "/webApp/yunCheck",
            dataType: 'json',
            success: function (res) {
                if (res.res != 0) {
                    layer.alert(res.msg);
                    return;
                }
                window.location.href = $('#target_url').val();
            },
            error: function (err) {
                layer.alert('网络出错');
            }
        });

    });
    $('.plugin-next-js').on('click', function () {
        if (!$('.pay-protocol-link input').prop('checked')) {
            layer.alert('请阅读并接受使用协议')
            return
        }
        window.location.href = $('#target_url').val();
    });
    //抖音小程序
    $('.douyin-next-js').on('click', function () {
        var promise = $('#promise').val();
        if(!promise){
            return layer.alert('您可接入的小程序/公众号数量已达上限，请升级版本或购买接入名额后再试', {
                title: '提示',
                btn: ['去购买']
            }, function () {
                window.location.href = '/vip/price';
            })
        }
        window.location.href = $('#target_url').val();
    });
    var add_clock;
    // 新增小程序
    $('#submit-add-app-form').on('click', function () {
        if (add_clock) return
        var $btn = $(this);
        var app_name = $('#weapp-name').val();
        var category_id = $('#app-classify').val();
        var app_id = $('#app-id').val().trim();
        var app_secret = $('#app-secret').val();
        var auth_type = $('#auth_type').val();
        if (!app_name) {
            layer.alert(auth_type != 1 ? '请输入小程序名称' : '请输入公众号名称');
            return;
        }
        if (!category_id) {
            layer.alert('请选择分类');
            return;
        }
        if (!app_id) {
            layer.alert(auth_type != 1 ? '请输入小程序AppID' : '请输入公众号AppID');
            return;
        }
        if (!app_secret) {
            layer.alert(auth_type != 1 ? '请输入小程序AppSecret' : '请输入公众号AppSecret');
            return;
        }
        add_clock = true
        $btn.button('loading');
        if(auth_type==1){
            var app_type = 'official_account'
        }else if(auth_type==2){
            var app_type = 'mini_program'
        }else if(auth_type==3){
            var app_type = 'mini_program'
        }else if(auth_type==4){
            var app_type = 'dou_yin_mini'
        }
        $.ajax({
            type: "post",
            url: "/webApp/addApp",
            dataType: 'json',
            data: {
                app_name: app_name,
                category_id: category_id,
                app_id: app_id,
                auth_type: auth_type,
                app_category: auth_type == 1 ? 1 : 0,
                app_type:app_type,
                app_secret: app_secret,
            },
            success: function (res) {
                $btn.button('reset');
                if (res.res == 2) {
                    add_clock = false;
                    $('.tips').html(res.msg).show();
                    return false;
                } else if (res.res != 0) {
                    add_clock = false;
                    layer.alert(res.msg);
                    return;
                }
                layer.msg(res.msg);
                window.location.href = '/webApp/addAppConfigView?access_key=' + res.data.access_key + '&wechatapp_id=' + res.data.wechatapp_id + '&auth_type=' + auth_type;
            },
            error: function (err) {
                $btn.button('reset');
                add_clock = false;
                layer.alert('网络出错');
            }
        });
    });
    var weichatapp_id = $.cookie('gr_webapp_id') || $.cookie('webapp_id');
    if (weichatapp_id) {
        $.ajax({
            type: "post",
            url: "/message/getUnreadTotal",
            dataType: 'json',
            data: {
                wechatapp_id: weichatapp_id
            },
            success: function (res) {
                if (!res.res) {
                    var str = res.data.totalCount > 99 ? "99+" : res.data.totalCount;
                    var a = res.data.totalCount.toString().length;
                    if (a > 3) a = 3;
                    if (res.data.totalCount != 0) {
                        $(".hot-dot").html(str);
                        $(".hot-dot").addClass("hot-dot" + a);
                    } else {
                        $(".hot-dot").hide();
                    }

                } else {
                    //$(".hot-dot").html(res.msg);
                }
            },
            error: function (err) {
                //console.log(err);
            }
        });
        $("#kefuxiaoxi").attr("href", "/message/chat/#/?wechatapp_id=" + weichatapp_id + "&type=2")
    } else {
        $("#kefuxiaoxi").attr("href", "/webApp/index")

    }
    //section切换
    $(".detail-content-side").on('mouseenter',function(){
        //active样式切换
        $(".detail-content-side").removeClass('active');
        $(this).addClass('active');
        //section切换
        let index = $(this).data('index');
        $(".section").removeClass('active');
        $(`.section[data-index=${index}]`).addClass('active')
    })
    //产品介绍切换
    $(".new-detail-list").on('mouseenter','.pack-up',function(){
        if($(this).hasClass('show-up')){
            return false;
        }
        $(".new-detail-list .pack-up").removeClass('show-up')
        $(this).addClass('show-up')
        let index = $(this).data('index');
        let $content = $(`.new-detail-produce[data-index=${index}]`);
        $(".new-detail-produce").removeClass('active');
        $content.addClass('active')
    })
    //解决方案active
    $(".solution-panel").on('mouseenter',function(){
        $(".solution-panel").removeClass('active');
        $(".solution-panel-main").removeClass('active');
        $(this).addClass('active');
        $(this).find('.solution-panel-main').addClass('active');
    })
});

function freeTypeTip() {
    layer.alert('免费版本不能单独购买客服名额，请升级为付费版本', {
        title: '提示'
    });
}

function channelAdd(app_type, wechatapp_id = '') {

    var channel_name = $('#add_channel_name').val();
    var obj = document.getElementsByName("checkkf");
    var check_val = [];
    if(app_type == 'plugin_app'){
        var plugin_Add_name = $('#add_app_name')[0].nodeName==='INPUT'?$('#add_app_name').val():$('#add_app_name').text()
    }  
    if (app_type == 'plugin_app' && !plugin_Add_name) {
        layer.alert('请填写您的小程序名称');
        return;
    }
    for (k in obj) {
        if (obj[k].checked)
            check_val.push(obj[k].value);
    }
    if (!channel_name.trim()) {
        layer.alert('请输入渠道名');
        return;
    }
    if (check_val == false) {
        layer.alert('请至少选择一个客服');
        return;
    }

    let _data = {},
        _url = '',
        _redirUrl = '';
    if (app_type == 'plugin_app') {
        _url = '/pluginApp/PluginScene/SceneCreate'
        _data = {
            "channel_name": channel_name,
            "kefu_user_ids": check_val,
            'app_name': plugin_Add_name,
            "wechatapp_id": wechatapp_id ? wechatapp_id : WECHATAPPID
        }
        _redirUrl = '/pluginApp/pluginAdminView/PluginSceneManagement?action=scene&sln=PluginSceneManagement'
    } else {
        _url = '/web/manager/ChannelCreate'
        _data = {
            "channel_name": channel_name,
            "user_ids": JSON.stringify(check_val),
            "app_type": app_type,
            "yun_customer_domain": $('#channel_select_h5_url_layout').val(),
            "send_msg_subject_id": $('#channel_select_app_layout').val(),
        }
        _redirUrl = '/web/manager/channelSetting?action=channel&sln=channelSetting'
    }


    $.ajax({
        type: "post",
        url: _url,
        dataType: 'json',
        data: _data,
        success: function (res) {
            if (res.res == 1) {
                layer.alert(res.msg);
                return;
            }
            layer.msg(res.msg);
            window.location.href = _redirUrl + '&wechatapp_id=' + res.data.wechatapp_id + '&channel_id=' + res.data.channel_id;
        },
        error: function (err) {
            layer.alert('网络出错');
        }
    });
}

function selectChannel(wechatapp_id, channel_id, type) {
    let _url = type == 'plugin_app' ? '/pluginApp/pluginAdminView/SelectChannel' : '/web/manager/SelectChannel'
    $.ajax({
        type: "post",
        url: _url,
        dataType: 'json',
        data: {
            "wechatapp_id": wechatapp_id,
            "channel_id": channel_id,
            "url": window.location.href
        },
        success: function (res) {
            if (res.res == 1) {
                layer.alert(res.msg);
                return;
            }
            window.location.href = res.data;
        },
        error: function (err) {
            layer.alert('网络出错');
        }
    });
}

function storageSet(key, value) {
    localStorage.setItem(key, JSON.stringify(value))
}

function storageGet(key) {
    return JSON.parse(localStorage.getItem(key))
}

// 点击进入代理
$('#agent .bn-content .add_app-btn').click(function () {
    $.ajax({
        type: "POST",
        url: "/MXkfAgentUser/JoinAgent",
        data: {
            user_id: $('#agentUserid').val()
        },
        dataType: "json",
        success: function (res) {
           // console.log(res);
            if (res.res == 0) {
                storageSet('username', res.data.user_name)
                storageSet('password', res.data.password)
                window.location.href = '/MXkfAgentUser/MAgentHome/#/home?isnew=0'
            } else if (res.res == 1) {
                layer.alert('操作失败请稍后再试')
            } else if (res.res == 2) {
                storageSet('username', res.data.user_name)
                storageSet('password', res.data.password)
                window.location.href = '/MXkfAgentUser/MAgentHome/#/home?isnew=2'
            }
        },
        error: function () {
            // layer.alert('网络出错'); 
        }
    });
})
// 验证黑名单
if ($('#userIdName').length)
    $$.ajax('/security/checkBan').then(res => {}).catch(req => {
        layer.alert(req.msg, {
            icon: 1,
            closeBtn: 0
        }, function (index) {
            window.location.href = '/user/logout'
        })
    })

var clipboard = new Clipboard('.copy_btn');
clipboard.on('success', function (e) {
    layer.msg('复制成功');
});

// 风险用户区分  只在接入智能客服时进行
if ($('#insert_open_type').val() == 'need_verify') {
    $$.ajax('/security/addAppForRiskBefor').then(res => {
        // $('.entrance-list.is_accredit').remove()
        // $('.entrance-list ').eq(0).click()
    }).catch(req => {
        $('.entrance-list.is_accredit').remove()
        $('.entrance-list ').eq(0).click()
    })
}
