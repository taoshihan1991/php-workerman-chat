/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/16
 * Time: 8:19 PM
 */
// 目前在服务的用户池
var servicePool = [];
// 当前交流的客户的标识
var activeUser = 0;
// 当前交流的客户的名字
var activeName = '';
// 活跃用户头像
var activeAvatar = '';
// 活跃用户ip
var activeIP = '';
// 当前模式 0:接待在线的 1:接待历史的
var nowModel = 0;
// 当前交流的日志id
var logId = 0;
// 接待的用户
var careCustomer = {
    customer_id: 0,
    customer_name: '',
    customer_avatar: '',
    customer_ip: '',
    seller_code: seller
};
// 转接层
var layerIndex = '';
var wordIndex = '';
// 客服下班
var offWork = 0;
// 录音句柄
var audio_context;
var recorder;
// 关闭的访客数量
var closeUserInterval = 0;
// 提示断开连接层
var closeLayerIndex = '';
// 对话排序
var sort = localStorage.getItem("chat-sort");
if (!sort) {
    sort = 1;
}
// 结束是否保留
var offlineStatus = localStorage.getItem("offline-chat");
if (!offlineStatus) {
    offlineStatus = 1;
}
var faceIndex = null;

var socket = new ReconnectingWebSocket(socketUrl + "/websocket");
socket.debug = true;
socket.timeoutInterval = 5400;

socket.onopen = function (res) {

    socket.send(JSON.stringify({
        cmd: "init",
        data: {
            uid: kefuUser.uid
        }
    }));

    layui.use('layer', function () {
        var layer = layui.layer;
        layer.close(closeLayerIndex);
    });

    $.Toast("友情提示", "连接成功", "success", {
        stack: true,
        timeout: 3000,
        has_progress: true
    });

    $(".kefu-info").find('.status').removeClass('out').addClass('online');
    $(".kefu-info").find('.user-status').text('在线');
};

socket.onclose = function (err) {
    $(".kefu-info").find('.status').removeClass('online').addClass('out');
    $(".kefu-info").find('.user-status').text('离线');

    layui.use('layer', function () {
        var layer = layui.layer;

        closeLayerIndex = layer.alert("与服务器断开连接", {
            title : '友情提示',
            icon: 2
        });
    });
};

socket.onmessage = function (res) {
    callBackOnMessage(res.data);
};

// 收到各种消息的回调
function callBackOnMessage(res) {

    var data = eval("(" + res + ")");
    console.log(data);
    switch(data['cmd']) {

        // 访客接入
        case 'customerLink':
            ai_service.voice();
            addUser(data.data, 1);
            break;

        // 聊条消息
        case 'chatMessage':
            $("#typing-word").text('').hide();
            showMessage(data.data);

            if(document.hidden){
                showNotice(data.data.avatar, '您有新消息', data.data.content);
            }

            // 用户列表简略消息
            $("#l-" + data.data.id).find(".visitor-card-time").html(data.data.content);
            break;

        // 发送消息回调
        case 'afterSend':
            var input = data.data.msg;
            if (400 == data.data.code) {

                var msg = ai_service.send(input, kefuUser.avatar, data.data.data);
            } else if (0 == data.data.code) {
                var msg = ai_service.send(input, kefuUser.avatar, data.data.data);
            }

            if (0 == nowModel) {
                $("#ct-" + activeUser).append(msg);
                wordBottom(activeUser);
            } else if (1 == nowModel) {
                $("#hct-" + activeUser).append(msg);
                historyWordBottom(activeUser);
            }
            $("#textarea").val('');

            ai_service.showBigPic();

            rollBackMsg();
            break;

        // 访客离线
        case 'offline':
            console.log(data);
            $("#l-" + data.data.customer_id).find('img').addClass('visitor-gray');
            if (2 == offlineStatus) {
                closeUser(data.data.customer_id);
            } else {
                var _obj = $("#l-" + data.data.customer_id);
                var _vHtml2 = '<div class="visitor-card " id="l-' + data.data.customer_id +'" data-id="' + data.data.customer_id + '" ' +
                    ' data-name="' + _obj.attr('data-name') + '" data-log="' + _obj.attr('data-log') +
                    '" data-ip="' + _obj.attr('data-ip') + '" data-protocol="' + _obj.attr('data-protocol') + '">';
                $("#visitor-list").append(_vHtml2 + _obj.html() + '</div>');
                checkCustomer();
                _obj.remove();
            }
            break;

        // 接到转接
        case 'reLink':
            ai_service.voice();
            addUser(data.data, 1);
            break;

        // 标记已读
        case 'readMessage':
            var data = data.data;
            $("#ct-" + activeUser).find('.no-read').each(function () {
                var mid = data.mid.split(',');
                if (-1 != $.inArray($(this).attr('data-msg-id'), mid)) {
                    $(this).removeClass('no-read').addClass("already-read").text('已读');
                }
            });
            break;

        // 单点登录被挤下线
        case 'SSO':
            layui.use('layer', function () {
                var layer = layui.layer;

                layer.alert("您的账号已在别的地方登录。您即将退出...", {
                    title: '友情提示',
                    icon: 2,
                    closeBtn: 0,
                    shade: 0.6
                });

                setTimeout(function () {
                    window.location.href = '/service/login/ssoLoginOut';
                }, 2000);
            });
            break;

        // 动态删除访客列表
        case 'removeQueue':
            $("#queue-" + data.data.customer_id).remove();
            break;

        // 显示正在输入
        case 'typing':
            if (activeUser == data.data.id && data.data.content.length > 0) {
                $("#typing-word").text(data.data.content).show();
            } else {
                $("#typing-word").text('').hide();
            }
            break;

        // 接待访客回调
        case 'changeGroupCB':
            console.log(data);
            if (data.data.code == 0) {
                layer.msg('转接成功');
                removeCustomer(activeUser);
                $("#queue-" + activeUser).remove();
            } else {
                layer.msg(data.data.msg);
            }

            break;
        // 接待访客成功与否反馈
        case 'linkKFCB':
            var data = JSON.parse(res);
            if (0 == data.data.code) {
                layer.msg(data.data.msg);
                $("#queue-" + careCustomer.customer_id).remove();
                if($(this).hasClass('active')) {
                    addUser(data.data.data, 1);
                }
            } else {
                layui.use('layer', function () {
                    var layer = layui.layer;

                    layer.alert(data.data.msg);

                    $("#queue-" + careCustomer.customer_id).remove();
                });
            }
            break;

        // 发出客服评价回调
        case 'praiseKfCB':
            if (data.data.code == 0) {
                $(".chat-box").append(ai_service.showSystem("已发送评价"));
                wordBottom(activeUser);
            } else {
                layer.msg(data.data.msg);
            }

            break;
    }
}

// 判断页面是否激活
var hiddenProperty = 'hidden' in document ? 'hidden' :
    'webkitHidden' in document ? 'webkitHidden' :
        'mozHidden' in document ? 'mozHidden' :
            null;
var visibilityChangeEvent = hiddenProperty.replace(/hidden/i, 'visibilitychange');

var onVisibilityChange = function(){
    if (!document[hiddenProperty]) {
        stopFlash();

        // 处理未读
        handleNoRead(activeUser);
    }
};

document.addEventListener(visibilityChangeEvent, onVisibilityChange);

$(function () {

    initWork();

    checkVoiceEnv();

    getMyWord();

    // 发送
    $("#sendBtn").on('click', function () {
        sendMessage('');
        $("#sendBtn").removeClass('active');
    });

    // 输入监听
    $("#textarea").keyup(function () {
        var len = $(this).val().length;
        if(len == 0) {
            $("#sendBtn").removeClass('active');
        } else if(len >0 && !$("#sendBtn").hasClass('active')) {
            $("#sendBtn").addClass('active');
        }
    });

    // 点击表情
    var index;
    $("#face").on('click', function (e) {
        e.stopPropagation();
        layui.use(['layer'], function () {
            var layer = layui.layer;

            var isShow = $(".layui-ai_service-face").css('display');
            if ('block' == isShow) {
                layer.close(index);
                return;
            }
            var height = $("#chat-area").height() - 110;
            layer.ready(function () {
                faceIndex = layer.open({
                    type: 1,
                    offset: [height + 'px', parseInt($(".service-menu").width() + $(".visitor-list").width()) + 'px'],
                    shade: 0.1,
                    title: false,
                    shadeClose: true,
                    closeBtn: 0,
                    area: '395px',
                    content: ai_service.showFaces()
                });
            });
        });
    });

    // 点击切换
    $(".now-chat").click(function () {
        servicePool = [];
        $("#pre-link-box").hide();
        $("#history-list-box").hide();
        $("#now-chat-box").show();
        $("#chat-area").html('');
        $("#typing-word").text('').hide();

        $("#visitor-list").show();
        $("#queue-list").hide();
        $("#history-list").hide();

        initActiveCustomer(0);
        removeDetail();

        if(!$(this).hasClass('active')) {
            initWork();
            $(this).addClass('active');
        }

        nowModel = 0;
        $(".history-chat").removeClass('active');
        $(".pre-link").removeClass('active');
    });

    // 历史会话
    $(".history-chat").click(function () {
        servicePool = [];
        $("#pre-link-box").hide();
        $("#history-list-box").show();
        $("#now-chat-box").hide();
        $("#chat-area").html('');
        $("#typing-word").text('').hide();

        $("#visitor-list").hide();
        $("#queue-list").hide();
        $("#history-list").show();

        initActiveCustomer(0);
        removeDetail();

        if(!$(this).hasClass('active')) {
            initHistoryChat();
            $(this).addClass('active');
        }

        nowModel = 1;
        $(".now-chat").removeClass('active');
        $(".pre-link").removeClass('active');
        $("#chat-area").html('');
    });

    // 在线待接待访客
    $(".pre-link").click(function () {
        servicePool = [];
        $("#pre-link-box").show();
        $("#now-chat-box").hide();
        $("#history-list-box").hide();
        $("#typing-word").text('').hide();

        $("#visitor-list").hide();
        $("#history-list").hide();
        $("#queue-list").show();

        if(!$(this).hasClass('active')) {
            initQueue();
            $(this).addClass('active');
        }

        initActiveCustomer(0);
        removeDetail();

        $(".history-chat").removeClass('active');
        $(".now-chat").removeClass('active');
        $("#chat-area").html('');
    });

    // 接待队列中的访客
    $("#takeCare").click(function () {
        if(careCustomer.customer_id == 0) {
            layui.use('layer', function () {
                var layer = layui.layer;
                layer.tips('请选择要接待的访客！', '#takeCare', {
                    tips: [3, '#01AAED']
                });
            });
            return false;
        }

        var tkIndex = layer.confirm('您确定要接待 ' + careCustomer.customer_name + ' ？', {
            title: '警告',
            icon: '2',
            btn: ['确定', '再想想']
        }, function(){
            var takeData = careCustomer;
            takeData.kefu_code = kefuUser.uid;
            takeData.kefu_name = kefuUser.name;
            takeData.kefu_avatar = kefuUser.avatar;

            socket.send(JSON.stringify({
                cmd: "linkByKF",
                data: takeData
            }));

            layer.close(tkIndex);
        }, function(){

        });
    });

    // 访客转接
    $("#reLink").click(function() {

        if(activeUser == 0 || activeName == ''){
            layer.msg("请选择要转接的访客", {anim: 6});
            return false;
        }

        $.getJSON('/service/service/reLink/u/' + seller, function (res) {
            var _tab_html = '';
            var _user_html = '';

            if(0 == res.code && res.data.length > 0) {
                $.each(res.data, function (k, v) {
                    if(0 == k) {
                        _tab_html += '<li class="layui-this">' + v.group_name + '</li>';
                        _user_html += '<div class="layui-tab-item layui-show">';
                    }else {
                        _tab_html += '<li>' + v.group_name + '</li>';
                        _user_html += '<div class="layui-tab-item">';
                    }

                    if(v.users.length > 0) {
                        $.each(v.users, function (key, val) {
                            _user_html += '<div class="layui-row"><div class="layui-col-md12 group-users">';
                            _user_html += '<div class="user-info-left"><img src="' + val.kefu_avatar + '">';
                            _user_html += '<span class="user-name">' + val.kefu_name + '</span></div>';
                            _user_html += '<div class="user-info-left online-info"><i class="layui-icon">&#xe770;</i>';
                            _user_html += '<span class="online"> ' + val.service_num + ' / ' + val.max_service_num + ' </span></div>';
                            _user_html += '<div class="user-info-left online-info">';
                            _user_html += '<a class="layui-btn" href="javascript:;" onclick="doRelink(this)" data-id="' +
                                val.kefu_code + '" data-name="' + val.kefu_name + '" data-gid="' + val.group_id + '">转接</a>';
                            _user_html += '</div></div></div>';
                        });
                    }else {

                        _user_html += '<div style="text-align: center;margin-top: 50px"><i class="layui-icon" style="font-size: 200px;color: #e2e2e2">&#xe69c;</i></div>';
                        _user_html += ' <p style="text-align: center;margin-top: 50px;color: #e2e2e2">暂无在线客服</p>';
                    }

                    _user_html += '</div>';
                });

                $("#change-group-title").html(_tab_html);
                $("#relink-tab").html(_user_html);
            }

            layerIndex = layer.open({
                title: '',
                type: 1,
                area: ['50%', '50%'],
                content: $("#change-box")
            });
        });
    });

    // 主动关闭用户
    $("#closeChat").click(function () {

        if(activeUser == 0) {
            layui.use('layer', function () {
                var layer = layui.layer;
                layer.msg('请先选择要关闭的访客');
            });

            return false;
        }

        var showName = activeUser;
        if ($("#realName").val() != '') {
            showName = $("#realName").val();
        }
        var cIndex = layer.confirm('您确定要关闭 ' + showName + ' ？', {
            title: '警告',
            icon: '2',
            btn: ['确定', '再想想']
        }, function() {

            closeUser(activeUser);

            layer.close(cIndex);
        }, function(){

        });
    });

    // 常用语
    $("#showWord").click(function () {
        layui.use('layer', function () {

            var layer = layui.layer;

            wordIndex = layer.open({
                type: 1,
                title: '',
                skin: 'layui-layer-rim',
                area: ['60%', '60%'],
                content: $("#word").html()
            });
        });
    });

    // 客服退出
    $(".login-out").click(function () {

        layer.alert('正在关闭咨询的用户', {
            icon: 6,
            title: '',
            btn: false
        });

        if(servicePool.length == 0) {
            window.location.href = '/service/login/loginOut';
            return ;
        }

        $.each(servicePool, function (k, v) {
            closeUser(v, true);
        });

        offWork = 1;

        closeUserInterval = setTimeout(function () {
            window.location.href = '/service/login/loginOut';
        }, 1500);
    });

    // 监听快捷键发送
    document.getElementById('textarea').addEventListener('keydown', function (e) {
        if (e.keyCode != 13) return;
        e.preventDefault();  // 取消事件的默认动作
        sendMessage('');
    });

    // 自动主动接待
    if (1 == autoFlag) {

        autoInterval = parseInt(Math.random() * 10) + autoInterval;
        setInterval(function () {
            var obj = $('#queue-list .visitor-card:eq(0)');

            careCustomer.customer_id = obj.attr('data-id');
            careCustomer.customer_name = obj.attr('data-name');
            careCustomer.customer_avatar = obj.attr('data-avatar');
            careCustomer.customer_ip = obj.attr('data-ip');

            if (careCustomer.customer_id == undefined) return false;

            var takeData = careCustomer;
            takeData.kefu_code = kefuUser.uid;
            takeData.kefu_name = kefuUser.name;
            takeData.kefu_avatar = kefuUser.avatar;

            socket.send(JSON.stringify({
                cmd: "linkByKF",
                data: takeData
            }));

        }, autoInterval * 1000);
    }

    // 提示语
    $("#tips").mouseover(function () {
        layer.tips('截取的图片ctrl+v粘贴到输入框即可', '#tips', {
            tips: [1, '#3595CC']
        });
    });

    // 处理粘贴事件
    listenPaste();

    // 以下为监听更改用户信息
    $('#realName,#email,#phone,#remark').blur(function () {
        updateUserInfo();
    });

    // 黑名单
    $("#joinBlack").click(function () {

        layer.confirm('确定将该访客加入到黑名单？', {
            title: '友情提示',
            icon: 3,
            btn: ['确定', '取消']
        }, function(){

            if (0 == activeUser) {
                return layer.msg('请选择访客', {amin: 6});
            }

            $.post('/service/service/joinBlackList', {
                ip: $("#ipAddr").val(),
                customer_name: activeName,
                customer_id: activeUser,
                customer_real_name: $("#realName").val(),
                u: seller
            }, function(res) {
                if (0 == res.code) {

                    // 关闭访客
                    closeUser(activeUser);
                    layer.msg('操作成功');
                } else {
                    layer.msg('操作失败');
                }
            }, 'json');

        }, function(){

        });
    });

    // 录音发送
    $('#voice').click(function () {
        // notice 新的策略，必须是用户点击事件调用这个方法才能录音
        audio_context.resume().then(() => {
            console.log('Playback resumed successfully');
        });

        startRecording();

        var vindx = layer.open({
            type: 1
            ,title: false //不显示标题栏
            ,closeBtn: false
            ,area: '250px;'
            ,shade: 0.3
            ,id: 'LAY_layuipro' //设定一个id，防止重复弹出
            ,resize: false
            ,btn: ['完成发送', '放弃发送']
            ,btnAlign: 'c'
            ,moveType: 1 //拖拽模式，0或者1
            ,content: '<div style="padding: 20px; line-height: 22px; background-color: #393D49; color: #fff; font-weight: 300;text-align: center"><img src="/static/common/images/voice.gif" width="100px" height="100px"/><br/><p>正在录音请说话...</p></div>'
            ,yes: function(){
                stopRecording();
                layer.close(vindx);
            }
        });
    });

    // 评价
    $("#praise").on('click', function () {

        if(activeUser == 0) {
            layui.use('layer', function () {
                var layer = layui.layer;
                layer.msg('请选择访客');
            });

            return false;
        }

        socket.send(JSON.stringify({
            cmd: "praiseKf",
            data: {
                customer_id: activeUser,
                service_log_id: $('#l-' + activeUser).attr('data-log')
            }
        }));
    });

    // 修复声音故障
    $("#fix").click(function () {
        ai_service.voice();
    });

    // 检测当前的高度
    $(".word").hover(function () {
        $("#word-box").css("top", $(this).offset().top - 120).css("left", $('.service-menu').width()
            + $('.visitor-list').width() + $('.chat-body').width() - 200).show();

        $("#content").html(ai_service.replaceContent(ai_service.replaceEdit(($(this).attr("data-content")))));
    }, function () {
        $("#word-box").hide();
    });

    var chatIndex = '';
    // 会话设置
    $("#setChat").click(function () {

        layui.use(['layer', 'form'], function () {
            var layer = layui.layer;
            var form = layui.form;

            $("#sort-" + sort).attr('checked', 'checked');
            $("#status-" + offlineStatus).attr('checked', 'checked');
            form.render();

            chatIndex = layer.open({
                type: 1,
                title: '会话设置',
                area: ['260px', '280px'],
                content: $("#set-chat-box")
            });
        });
    });

    $("#set-chat").click(function () {
        sort = $("input[name='sort']:checked").val();
        offlineStatus = $("input[name='offlineStatus']:checked").val();

        layui.use('layer', function () {
            var layer = layui.layer;

            localStorage.setItem("chat-sort", sort);
            localStorage.setItem("offline-chat", offlineStatus);

            layer.msg('设置成功');
            layer.close(chatIndex);
        });
    });

    // 清理离线的访客
    $("#clean-chat").click(function () {
        $("#visitor-list").find('.visitor-card').each(function () {
            if ($(this).find('img').hasClass('visitor-gray')) {
                closeUser($(this).attr('data-id'));
            }
        });

        layer.msg('清理成功');
        layer.close(chatIndex);
    });

    // 客服状态切换
    /*$('.kefu-info').contextMenu('myStatus', {
        bindings: {
            'item_1': function(t) {
                alert('Trigger was ' + t.id + '\nAction was item_1');
            },
            'item_2': function(t) {
                alert('Trigger was ' + t.id + '\nAction was item_2');
            }
        }
    });*/

    // 输入联想
    var comWord = [];
    $.each(preWord, function (k, v) {
        comWord.push({
            value: ai_service.replaceEdit(v.value),
            data: v.data
        })
    });

    $('#textarea').autocomplete({
        lookup: comWord
    });

    // 增加我的常用语分类
    $("#add-my-cate").click(function () {

        layer.prompt({title: '请输入分类名称', formType: 3}, function(pass, index){
            layer.close(index);

            $.post('/service/cate/addKeFuCate', {cate_name: pass, u: seller}, function (res) {
                if (0 == res.code) {
                    layer.msg(res.msg);
                    getMyWord();
                } else {
                    layer.alert(res.msg, {icon: 2, title: '友情提示'});
                }
            }, 'json');
        });
    });

    // 忙碌
    var StatusBox = $(".status-menu-box"),CurrentStatus = 'online';
    $(".user-status").click(function() {
        StatusBox.show();
        $(document).mouseup(function (e) {
            if(!StatusBox.is(e.target) && StatusBox.has(e.target).length === 0) {
                StatusBox.hide();
            }
        })
    });

    // 切换状态
    $(".set-status").click(function() {
        var event = $(this).data('event');
        if (event != CurrentStatus) {
            $.post('/service/service/setStatus', {
                u: seller,
                status: event
            }, function(res) {
                if (0 == res.code) {
                    CurrentStatus = event;
                    if(event == 'online') {
                        $(".kefu-info").find('.status').removeClass('busy').addClass('online');
                        $(".kefu-info").find('.user-status').text('在线');
                    } else {
                        $(".kefu-info").find('.status').removeClass('online').addClass('busy');
                        $(".kefu-info").find('.user-status').text('忙碌');
                    }
                    
                    layer.msg('操作成功');
                } else {
                    layer.msg('操作失败');
                }
            }, 'json');
        }

        StatusBox.hide();
    });
});

// 主动关闭用户
function closeUser(userId, flag) {

    var protocol = $('#l-' + userId).attr('data-protocol');
    if ('ws' == protocol) {

        socket.send(JSON.stringify({
            cmd: "closeUser",
            data: {
                kefu_code: kefuUser.uid,
                customer_id: userId
            }
        }));

        if (!flag) {
            removeCustomer(userId);
        }
    } else if ('http' == protocol) {

        $.post('/index/api/closeUser', {
            cmd: 'closeUser',
            data: {
                kefu_code: kefuUser.uid,
                customer_id: userId
            }
        }, function (res) {

            if (0 == res.code) {
                if (!flag) {
                    removeCustomer(userId);
                }
            }
        }, 'json');
    }
}

// 发送常用语
function sendWord(obj, flag) {
    layui.use('layer', function () {

        var layer = layui.layer;
        layer.close(wordIndex);
    });

    if (flag === 1) {
        sendMessage(ai_service.replaceEdit($(obj).attr('data-content')));
    } else if (flag === 2) {
        $("#textarea").val(ai_service.replaceEdit($(obj).attr('data-content')));
    }

    $("#word-box").hide();
}

layui.use(['element', 'form'], function () {
    var element = layui.element;
    var form = layui.form;
});

// 处理转接
function doRelink(obj) {

    var protocol = $('#l-' + activeUser).attr('data-protocol');
    if ('ws' == protocol) {

        socket.send(JSON.stringify({
            cmd: "changeGroup",
            data: {
                customer_id: activeUser,
                customer_name: activeName,
                customer_avatar: activeAvatar,
                customer_ip: activeIP,
                from_kefu_id: kefuUser.uid,
                to_kefu_id: $(obj).attr('data-id'),
                to_kefu_name: $(obj).attr('data-name'),
                seller_code: seller
            }
        }));
    } else if ('http' == protocol) {

        $.post('/index/api/doRelink', {
            cmd: 'changeGroup',
            data: {
                customer_id: activeUser,
                customer_name: activeName,
                customer_avatar: activeAvatar,
                customer_ip: activeIP,
                from_kefu_id: kefuUser.uid,
                to_kefu_id: $(obj).attr('data-id'),
                to_kefu_name: $(obj).attr('data-name'),
                seller_code: seller
            }
        }, function (res) {

            if (0 == res.code) {

                removeCustomer(activeUser);
            }
        }, 'json');
    }

    layer.close(layerIndex);
    //layer.msg('转接成功');
}

// 移除访客信息
function removeCustomer(customerId) {

    initActiveCustomer(customerId);
    removeDetail();

    $.each(servicePool, function (k, v) {
        if(v == customerId) {
            servicePool.splice(k, 1);
        }
    });

    $("#l-" + customerId).remove();
    $("#ct-" + customerId).remove();
}

// 获取访客的列表
function initWork() {

    $('#visitor-list').html('');
    $("#chat-area").html();
    $.getJSON('/service/service/getNowServiceList/u/' + seller, function (res) {
        if(0 == res.code && res.data.length > 0) {
            $.each(res.data, function (k, v) {
                addUser(v);
            });
        }
    });
}

// 初始化历史对话列表
function initHistoryChat() {

    $('#history-list').html('');
    $("#chat-area").html();
    $.getJSON('/service/service/getHistoryChatList/u/' + seller, function (res) {
        if(0 == res.code && res.data.length > 0) {
            $.each(res.data, function (k, v) {
                addHistoryUser(v);
            });
        }
    });
}

// 初始化访客队列
function initQueue() {

    $('#queue-list').html('');
    $.getJSON('/service/service/getCustomerQueue/u/' + seller, function (res) {
        if(0 == res.code && res.data.length > 0) {
            var _html = '';
            $.each(res.data, function (k, customer) {
                _html += showQueueList(customer);
            });

            $('#queue-list').html(_html);

            checkTakeCare();
        }
    });
}

// 初始化当前会话用户
function initActiveCustomer(customerId) {
    if (0 == customerId || customerId == activeUser) {
        activeUser = 0;
    }

    activeName = '';
    activeAvatar = '';
    activeIP = '';
    logId = 0;
}

// 选择接待访客
function checkTakeCare() {

    $("#queue-list .visitor-card").click(function () {

        $(this).removeClass('active').addClass('active').siblings().removeClass('active');

        careCustomer.customer_id = $(this).attr('data-id');
        careCustomer.customer_name = $(this).attr('data-name');
        careCustomer.customer_avatar = $(this).attr('data-avatar');
        careCustomer.customer_ip = $(this).attr('data-ip');
    });
}

// 展示队列信息
function showQueueList(customer) {

    var listTpl = [
        '<div class="visitor-card" data-id="' + customer.customer_id + '" data-name="' + customer.customer_name
        + '" data-ip="' + customer.customer_ip + '" data-avatar="' + customer.customer_avatar + '" id="queue-' + customer.customer_id  + '">',
        '<img src="' + customer.customer_avatar + '" class="head-msg" />',
        '<div class="msg">',
        '<p>',
        '<span class="name">' + customer.customer_name + '</span>',
        '<span class="visitor-card-time">' + customer.create_time + '</span>',
        '</p>',
        '</div>',
        '</div>'
    ].join('');

    return listTpl;
}

// 展示聊天用户
function addUser(customer, newInFlag) {

    if(-1 != $.inArray(customer.customer_id, servicePool)) {
        $("#l-" + customer.customer_id).find('img').removeClass('visitor-gray');
        $("#l-" + customer.customer_id).attr('data-log', customer.log_id);
        $("#l-" + customer.customer_id).attr('data-ip', customer.customer_ip);
        return;
    }

    var style = '';
    if(0 == activeUser && 0 == nowModel) {
        activeUser = customer.customer_id;
        activeName = customer.customer_name;
        activeAvatar = customer.customer_avatar;
        activeIP = customer.customer_ip;

        style = 'active';
        logId = customer.log_id;

        getChatLog(activeUser, 1);
        showUserDetail(activeName, customer.customer_ip);
    }

    var gray = '';
    if(0 == customer.online_status) {
        gray = 'visitor-gray';
    }

    // 兼容客服标注
    var customerName = customer.customer_name;
    if (typeof customer.real_name != "undefined" && customer.real_name != '') {
        customerName = customer.real_name;
    }

    var listTpl =
        '<div class="visitor-card ' + style + '" id="l-' + customer.customer_id + '" data-id="' + customer.customer_id
        + '" data-name="' + customer.customer_name + '" data-log="' + customer.log_id + '" data-ip="' + customer.customer_ip + '" data-protocol="' + customer.protocol + '">'
        +  '<img src="' + customer.customer_avatar + '" class="head-msg ' + gray + '" />'
        +    '<div class="msg">'
        +       '<p>'
        +            '<span class="name">' + customerName + '</span>'
        +            '<span class="visitor-card-time">' + customer.create_time + '</span>'
        +        '</p>'
        +        '<p style="position: relative;top:-12px">';
    if (newInFlag) {
        listTpl += '<span class="count">new</span>';
    } else {
        listTpl += '<span class="count" style="display: none;">0</span>'
    }
    listTpl += '</p>'
        +    '</div>'
        +'</div>';

    servicePool.push(customer.customer_id);
    $('#visitor-list').append(listTpl);

    var display = 'style="display:none"';
    if(customer.customer_id == activeUser && 0 == nowModel) {
        display = 'style="display:block"'
    }

    var chatTpl = [
        '<div class="chat-box" id="ct-' + customer.customer_id + '" ' + display + '></div>'
    ].join('');

    $("#chat-area").append(chatTpl);

    checkCustomer();
}

// 选择聊天访客
function checkCustomer() {

    $("#visitor-list .visitor-card").unbind("click"); // 防止事件叠加

    $("#visitor-list .visitor-card").click(function () {
        $("#typing-word").text('').hide();
        $(this).removeClass('active').addClass('active').siblings().removeClass('active');
        activeUser = $(this).attr('data-id');
        activeName = $(this).attr('data-name');
        activeAvatar = $(this).find('.head-msg').attr('src');
        activeIP = $(this).attr('data-ip');

        logId = $(this).attr('data-log');

        $("#ct-" + activeUser).show().siblings().hide();

        $(this).find(".msg").find(".count").text(0).hide();

        getChatLog(activeUser, 1);
        showUserDetail(activeName, activeIP);
    });
}

// 选择历史访客
function checkHistoryCustomer() {

    $("#history-list .visitor-card").unbind("click"); // 防止事件叠加

    $("#history-list .visitor-card").click(function () {

        $(this).removeClass('active').addClass('active').siblings().removeClass('active');
        activeUser = $(this).attr('data-id');
        activeName = $(this).attr('data-name');
        activeAvatar = $(this).find('.head-msg').attr('src');
        activeIP = $(this).attr('data-ip');

        logId = $(this).attr('data-log');

        $("#hct-" + activeUser).show().siblings().hide();

        $(this).find(".msg").find(".count").text(0).hide();

        getHistoryChatLog(activeUser, 1);
        showUserDetail(activeName, activeIP);
    });
}

// 展示历史聊天用户
function addHistoryUser(customer) {

    var style = '';
    if(0 == activeUser) {
        activeUser = customer.customer_id;
        activeName = customer.customer_name;
        activeAvatar = customer.customer_avatar;
        activeIP = customer.customer_ip;

        style = 'active';
        logId = customer.log_id;

        getHistoryChatLog(activeUser, 1);
        showUserDetail(activeName, customer.customer_ip);
    }

    var gray = 'visitor-gray';
    // 兼容客服标注
    var customerName = customer.customer_name;
    if (typeof customer.real_name != "undefined" && customer.real_name != '') {
        customerName = customer.real_name;
    }

    var listTpl = [
        '<div class="visitor-card ' + style + '" id="hl-' + customer.customer_id + '" data-id="' + customer.customer_id
        + '" data-name="' + customer.customer_name + '" data-log="' + customer.log_id + '" data-ip="' + customer.customer_ip + '" data-protocol="' + customer.protocol + '">',
        '<img src="' + customer.customer_avatar + '" class="head-msg ' + gray + '" />',
        '<div class="msg">',
        '<p>',
        '<span class="name">' + customerName + '</span>',
        '<span class="visitor-card-time">' + customer.create_time + '</span>',
        '</p>',
        '<p style="position: relative;">',
        '<span class="count" style="display: none;">0</span>',
        '</p>',
        '</div>',
        '</div>'
    ].join('');

    $('#history-list').append(listTpl);

    var display = 'display:none';
    if(customer.customer_id == activeUser) {
        display = 'display:block'
    }

    var chatTpl = [
        '<div class="chat-box" id="hct-' + customer.customer_id + '" ' + display + '></div>'
    ].join('');

    $("#chat-area").append(chatTpl);

    checkHistoryCustomer();
}

// 展示消息并计数
function showMessage(data) {

    // 展示聊天信息
    var chatMsg = ai_service.showMessage(data);
    $("#ct-" + data.id).append(chatMsg);

    // 计未读数量
    if(data.id != activeUser) {

        var obj = $("#l-" + data.id).find(".msg").find(".count");
        if ('new' == obj.text()) {
            obj.text(1).show();
        } else {
            obj.text(parseInt(obj.text()) + 1).show();
        }

        if (2 == sort) {
            var _obj = $("#l-" + data.id);
            var _vHtml = '<div class="visitor-card " id="l-' + data.id +'" data-id="' + data.id + '" ' +
                ' data-name="' + _obj.attr('data-name') + '" data-log="' + _obj.attr('data-log') +
                '" data-ip="' + _obj.attr('data-ip') + '" data-protocol="' + _obj.attr('data-protocol') + '">';
            $("#visitor-list").prepend(_vHtml + _obj.html() + '</div>');
            _obj.remove();

            checkCustomer();
        }
    } else {

        // 闪烁标题
        flashTitle(data.content);

        if (!document.hidden) {
            stopFlash();
            // 处理未读
            handleNoRead(activeUser);
        }

        $("#l-" + activeUser).find('.count').text(0).hide();

        ai_service.showBigPic();
        wordBottom(data.id);
    }

    ai_service.voice();
}

// 发送消息
function sendMessage(inMsg) {
    var sendFlag = 1;
    if (2 == sendFlag) {
        return ;
    }
    sendFlag = 2;

    if('' == inMsg) {
        var input = $("#textarea").val();
    } else {
        var input = inMsg;
    }

    if(activeUser == 0 || input.length == 0) {
        return ;
    }

    if (0 == nowModel) {
        var protocol = $('#l-' + activeUser).attr('data-protocol');
    } else if (1 == nowModel) {
        var protocol = $('#hl-' + activeUser).attr('data-protocol');
    }

    if (input.substr(0, 4) != 'img[' && input.substr(0, 5) != 'file(') {
        input = ai_service.autoReplaceUrl(input);
    }

    if ('ws' == protocol) {

        socket.send(JSON.stringify({
            cmd: "chatMessage",
            data: {
                from_name: kefuUser.name,
                from_avatar: kefuUser.avatar,
                from_id: kefuUser.uid,
                to_id: activeUser,
                to_name: activeName,
                content: input,
                seller_code: seller
            }
        }));
    } else if ('http' == protocol) {

        $.post('/index/api/send2Customer', {
            data: {
                from_name: kefuUser.name,
                from_avatar: kefuUser.avatar,
                from_id: kefuUser.uid,
                to_id: activeUser,
                to_name: activeName,
                content: input,
                seller_code: seller
            }}, function (res) {

            var msg = ai_service.send(input, kefuUser.avatar, res.data);
            if (0 == nowModel) {
                $("#ct-" + activeUser).append(msg);

                $("#ct-" + activeUser).find('.no-read').each(function () {
                    var mid = [res.data];
                    if (-1 != $.inArray($(this).attr('data-msg-id'), mid)) {
                        $(this).removeClass('no-read').addClass("already-read").text('已读');
                    }
                });
                wordBottom(activeUser);
            } else if (1 == nowModel) {
                $("#hct-" + activeUser).append(msg);

                $("#hct-" + activeUser).find('.no-read').each(function () {
                    var mid = [res.data];
                    if (-1 != $.inArray($(this).attr('data-msg-id'), mid)) {
                        $(this).removeClass('no-read').addClass("already-read").text('已读');
                    }
                });
                historyWordBottom(activeUser);
            }

            $("#textarea").val('');
            ai_service.showBigPic();

            console.log(res);
        }, 'json');
    }

    sendFlag = 1;
    // 用户列表简略消息
    $("#l-" + activeUser).find(".visitor-card-time").html(input);
}

// 展示用户详情信息
function showUserDetail(name, ip) {
    $("#ipAddr").val(ip);

    $.getJSON('/service/service/getCity/u/' + seller, {ip: ip}, function (res) {
        if (0 == res.code) {
            $("#address").val(res.data);
        }
    });

    // 拉取访客详情
    $.getJSON('/service/service/getCustomerInfo/u/' + seller, {customer_id: activeUser}, function (res) {
        if (0 == res.code) {
            //if (res.data.length > 0) {
            $('#from').val(res.data.search_engines);
            if (null == res.data.real_name) {
                $('#realName').attr('placeholder', '点击输入').val('');
            } else {
                $('#realName').val(res.data.real_name);
                $('#l-' + activeUser).find('.name').text(res.data.real_name);
            }

            if (null == res.data.email) {
                $('#email').attr('placeholder', '点击输入').val('');
            } else {
                $('#email').val(res.data.email);
            }

            if (null == res.data.phone) {
                $('#phone').attr('placeholder', '点击输入').val('');
            } else {
                $('#phone').val(res.data.phone);
            }

            $('#remark').val(res.data.remark);
            var c_os = '';
            if (res.data.os == 'iPhone' || res.data.os == 'iPad') {
                c_os = 'layui-icon-ios';
            } else if (res.data.os == 'Android') {
                c_os = 'layui-icon-android';
            } else {
                c_os = 'layui-icon-windows';
            }
            $('#show-os').html('<i class="layui-icon ' + c_os + '" style="margin-left: 20px"></i> ' + res.data.os);
            $('#show-version').html(res.data.version);
            //}
        }
    });
}

// 清除详情
function removeDetail() {
    $("#ipAddr").val('');
    $("#address").val('');
    $('#from').val('');
    $('#realName').val('');
    $('#email').val('');
    $('#phone').val('');
    $('#remark').val('');
    $('#show-os').html('');
    $('#show-version').html('');
}

// 更新访客信息
function updateUserInfo() {

    $.post('/service/service/updateCustomerInfo', {
        customer_id: activeUser,
        real_name: $("#realName").val(),
        email: $("#email").val(),
        phone: $("#phone").val(),
        remark: $("#remark").val(),
        u: seller
    }, function(res) {
        if (0 == res.code) {
            $.Toast("友情提示", "记录成功", "success", {
                stack: true,
                timeout: 2000,
                has_progress: true
            });
            if ('' == $("#realName").val()) {
                return ;
            }
            $('#l-' + activeUser).find('.name').text($("#realName").val());
        } else {
            $.Toast("友情提示", "记录失败", "success", {
                stack: true,
                timeout: 2000,
                has_progress: true
            });
        }
    }, 'json');
}

// 获取聊天记录
function getChatLog(uid, page, flag, bottom) {

    $.getJSON('/service/service/getChatLog', {uid: uid, page: page, u: seller}, function(res){
        if(0 == res.code && res.data.length > 0){

            if(res.msg == res.total){
                var _html = '<div class="clearfloat"><div class="author-name"><small>没有更多了</small></div><div style="clear:both"></div></div>';
            }else{
                var _html = '<div class="clearfloat"><div class="author-name" data-page="' + parseInt(res.msg + 1)
                    + '" data-uid="' + uid + '" onclick="getMore(this)"><small class="chat-system">更多记录</small></div><div style="clear:both"></div></div>';
            }

            $.each(res.data, function (k, v) {
                if(v.type == 'mine') {

                    _html += ai_service.showMyChatLog(v);
                } else if(v.type == 'user'){

                    _html += ai_service.showMessage({time: v.create_time, avatar: v.from_avatar, content: v.content, chat_log_id: v.log_id, read_flag: v.read_flag});
                }
            });

            if(typeof flag == 'undefined'){
                $("#ct-" + uid).html(_html);
            }else{
                $("#ct-" + uid).prepend(_html);
            }

            ai_service.showBigPic();
            if(typeof bottom == 'undefined') {
                wordBottom(uid);
            }

            // 处理未读
            handleNoRead(uid);

            rollBackMsg();
        }
    });
}

// 获取更多的的记录
function getMore(obj) {
    $(obj).remove();

    var page = $(obj).attr('data-page');
    var uid = $(obj).attr('data-uid');

    getChatLog(uid, page, 1, 1);
}

// 获取历史聊天记录
function getHistoryChatLog(uid, page, flag, bottom) {

    $.getJSON('/service/service/getChatLog', {uid: uid, page: page, u: seller}, function(res){
        if(0 == res.code && res.data.length > 0){

            if(res.msg == res.total){
                var _html = '<div class="clearfloat"><div class="author-name"><small>没有更多了</small></div><div style="clear:both"></div></div>';
            }else{
                var _html = '<div class="clearfloat"><div class="author-name" data-page="' + parseInt(res.msg + 1)
                    + '" data-uid="' + uid + '" onclick="getHistoryMore(this)"><small class="chat-system">更多记录</small></div><div style="clear:both"></div></div>';
            }

            $.each(res.data, function (k, v) {
                if(v.type == 'mine') {

                    _html += ai_service.showMyChatLog(v);
                } else if(v.type == 'user'){

                    _html += ai_service.showMessage({time: v.create_time, avatar: v.from_avatar, content: v.content, chat_log_id: v.log_id, read_flag: v.read_flag});
                }
            });

            if(typeof flag == 'undefined'){
                $("#hct-" + uid).html(_html);
            }else{
                $("#hct-" + uid).prepend(_html);
            }

            ai_service.showBigPic();
            if(typeof bottom == 'undefined') {
                historyWordBottom(uid);
            }
        }
    });
}

// 获取更多的的记录
function getHistoryMore(obj) {
    $(obj).remove();

    var page = $(obj).attr('data-page');
    var uid = $(obj).attr('data-uid');

    getHistoryChatLog(uid, page, 1, 1);
}

// 滚动到最底端
function wordBottom(chatId) {
    var box = $("#ct-" + chatId);
    box.scrollTop(box[0].scrollHeight);
}

// 历史消息滚到底端
function historyWordBottom(chatId) {
    var box = $("#hct-" + chatId);
    box.scrollTop(box[0].scrollHeight);
}

// 图片上传
layui.use(['upload', 'layer'], function () {
    var upload = layui.upload;
    var layer = layui.layer;

    var index;
    upload.render({
        elem: '#image'
        , accept: 'images'
        , exts: 'jpg|jpeg|png|gif'
        , url: '/service/upload/uploadImg/u/' + seller
        , before: function () {
            index = layer.load(0, {shade: false});
        }
        , done: function (res) {
            layer.close(index);
            sendMessage('img[' + res.data.src + ']');
            ai_service.showBigPic();
        }
        , error: function () {
            // 请求异常回调
        }
    });

    upload.render({
        elem: '#file'
        , accept: 'file'
        , exts: 'zip|rar|txt|doc|docx|xls|xlsx'
        , url: '/service/upload/uploadFile/u/' + seller
        , before: function () {
            index = layer.load(0, {shade: false});
        }
        , done: function (res) {
            layer.close(index);
            sendMessage('file(' + res.data.src + ')[' + res.data.name + ']');
            ai_service.showBigPic();
        }
        , error: function () {
            // 请求异常回调
        }
    });
});

// 监听粘贴事件
function listenPaste() {
    // 监听粘贴事件
    document.getElementById('textarea').addEventListener('paste', function(e){
        $("#sendBtn").addClass('active');
        // 添加到事件对象中的访问系统剪贴板的接口
        var clipboardData = e.clipboardData,
            i = 0,
            items, item, types;

        if (clipboardData) {
            items = clipboardData.items;
            if (!items) {
                return;
            }
            item = items[0];
            // 保存在剪贴板中的数据类型
            types = clipboardData.types || [];
            for (; i < types.length; i++) {
                if (types[i] === 'Files') {
                    item = items[i];
                    break;
                }
            }

            // 判断是否为图片数据
            if (item && item.kind === 'file' && item.type.match(/^image\//i)) {

                var fileType = [
                    'image/jpg',
                    'image/png',
                    'image/jpeg',
                    'image/gif'
                ];

                if(-1 == $.inArray(item.type, fileType)){
                    layer.msg("只支持jpg,jpeg,png,gif");
                    return false;
                }

                var fileType = item.type.lastIndexOf('/');
                var suffix = item.type.substring(fileType+1, item.type.length);

                var blob = item.getAsFile();
                var fileName =  new Date().valueOf() + '.' + suffix;

                var formData = new FormData();
                formData.append('name', fileName);
                formData.append('file', blob);

                var request = new XMLHttpRequest();
                var uploading = null;
                request.upload.onprogress = function (event) {
                    uploading = layer.load(0, {shade: false});
                };

                request.onreadystatechange = function() {
                    if (request.readyState == 4 && request.status == 200) {
                        layer.close(uploading);
                        var res = eval('(' + request.response + ')');
                        if(res.code == 0) {
                            layer.photos({
                                photos: {
                                    data: [{
                                        "src": res.data.src
                                    }]
                                }
                                , area: ['500px', '500px']
                                , shade: 0.5
                                , closeBtn: 0
                                , btn: ['确定发送', '粘贴错了']
                                , anim: 0
                                , resize: false
                                , yes: function (index, layero) {
                                    $("#textarea").val('img['+ (res.data.src||'') +']');
                                    $("#sendBtn").addClass('active');
                                    layer.close(index);
                                }
                            });
                        } else {
                            layer.msg(res.msg||'粘贴失败');
                            $("#sendBtn").removeClass('active');
                        }
                    }
                };
                // upload error callback
                request.upload.onerror = function(error) {
                    layer.msg(res.msg||'粘贴失败');
                };
                // upload abort callback
                request.upload.onabort = function(error) {
                    layer.msg(res.msg||'粘贴失败');
                };

                request.open('POST', '/service/upload/uploadImg/u/' + seller);
                request.send(formData);

                //imgReader(item, data.id);
            }
        }

    });
};

// 检测录音环境
function checkVoiceEnv() {
    if (window.location.protocol != 'https:') {

        $.Toast("友情提示", "想发送语音必须使用https", "warning", {
            stack: true,
            timeout:3000,
            has_progress:true
        });


        return false;
    }

    try {
        // webkit shim
        window.AudioContext = window.AudioContext || window.webkitAudioContext;
        navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia ||
            navigator.mozGetUserMedia ||
            navigator.msGetUserMedia;
        window.URL = window.URL || window.webkitURL;

        audio_context = new AudioContext();
    } catch (e) {
        layui.use('layer', function () {
            var layer = layui.layer;
            layer.msg('当前浏览器不支持录音');
        });
        return false;
    }

    navigator.getUserMedia({audio: true}, startUserMedia, function(e) {
        layui.use('layer', function () {
            var layer = layui.layer;
            layer.msg('没有录音设备');
        });
        return false;
    });
}

function showNotice(head, title, msg) {
    var Notification = window.Notification || window.mozNotification || window.webkitNotification;
    if (Notification) {
        Notification.requestPermission(function (status) {
            //status默认值'default'等同于拒绝 'denied' 意味着用户不想要通知 'granted' 意味着用户同意启用通知
            if ("granted" != status) {
                return;
            } else {
                var tag = "sds" + Math.random();
                var notify = new Notification(
                    title,
                    {
                        dir: 'auto',
                        lang: 'zh-CN',
                        tag: tag,//实例化的notification的id
                        icon: head,//通知的缩略图,//icon 支持ico、png、jpg、jpeg格式
                        body: msg //通知的具体内容
                    }
                );

                notify.onclick = function () {
                    //如果通知消息被点击,通知窗口将被激活
                    window.focus();
                },
                    notify.onerror = function () {
                        console.log("HTML5桌面消息出错！！！");
                    };
                notify.onshow = function () {
                    setTimeout(function () {
                        notify.close();
                    }, 2000)
                };
                notify.onclose = function () {
                    console.log("HTML5桌面消息关闭！！！");
                };
            }
        });
    } else {
        console.log("您的浏览器不支持桌面消息");
    }
}

// 处理未读
function handleNoRead(customerId) {

    if ('http' == $('#l-' + customerId).attr('data-protocol')) {
        return false;
    }

    var noReadIds = [];
    // 检测全局未读
    $("#ct-" + customerId).find(".check-read").each(function () {
        if ($(this).attr('data-msg-id') != "undefined") {
            noReadIds.push($(this).attr('data-msg-id'));
        }
    });

    // 有未读的数据
    if (noReadIds.length > 0) {

        socket.send(JSON.stringify({
            cmd: "readMessage",
            data: {
                uid: customerId,
                mid: noReadIds.join(',')
            }
        }));

        $("#ct-" + customerId).find(".check-read").removeClass('check-read').addClass('complete-read');
    }
}

// 消息撤回
function rollBackMsg() {

    $('.right').contextMenu('myBack', {
        bindings: {
            'item_3': function(t) {
                var msgId = $(t).find('i').attr('data-msg-id');

                socket.send(JSON.stringify({
                    cmd: "rollBackMessage",
                    data: {
                        mid: msgId,
                        kid: kefuUser.uid,
                        uid: activeUser
                    }
                }));

                $(t).parent().remove();
            }
        }
    });
}

// 获取我的常用语
function getMyWord() {

    $.getJSON("/service/service/getMyWord", {u: seller}, function (res) {
        if (0 == res.code) {
            layui.use(['laytpl', 'element'], function(){
                var laytpl = layui.laytpl;
                var element = layui.element;

                var getTpl = myWordTpl.innerHTML
                    ,view = document.getElementById('my-word-box');
                laytpl(getTpl).render(res, function(html){
                    view.innerHTML = html;
                });

                element.render();
            });
        } else {
            layer.msg(res.msg);
        }
    });
}

// 编辑我的短语
function editMyWord(obj) {
    layer.prompt({title: '编辑分类名称', formType: 3, value: $(obj).attr('data-title')},
        function(pass, index){
            layer.close(index);

            $.post('/service/cate/editKeFuCate', {cate_name: pass, u: seller, cate_id: $(obj).attr('data-id')},
                function (res) {
                    if (0 == res.code) {
                        layer.msg(res.msg);
                        getMyWord();
                    } else {
                        layer.alert(res.msg, {icon: 2, title: '友情提示'});
                    }
                }, 'json');
        });
}

// 删除我的常用语
function delMyWord(obj) {
    layer.confirm('确定要删除该分类以及分类下的常用语？', {
        icon: 3,
        title: '友情提示',
        btn: ['确定', '取消']
    }, function() {

        $.post('/service/cate/delKeFuCate', {u: seller, cate_id: $(obj).attr('data-id')}, function (res) {
            if (0 == res.code) {
                layer.msg(res.msg);
                getMyWord();
            } else {
                layer.msg(res.msg);
            }
        }, 'json');
    }, function() {

    });
}

// 添加常用语
function addMyWord(obj) {

    layer.open({
        type: 2
        ,title: '添加常用语'
        ,content: "/service/word/addKeFuWord/u/" + seller + '/cate_id/' + $(obj).attr('data-id')
        ,area: ['50%', '70%']
    });
}

// 删除常用语
function delWord(obj) {

    layer.confirm('确定要删除该常用语？', {
        icon: 3,
        title: '友情提示',
        btn: ['确定', '取消']
    }, function() {

        $.post('/service/word/delKeFuWord', {u: seller, word_id: $(obj).attr('data-id')}, function (res) {
            if (0 == res.code) {
                layer.msg(res.msg);
                getMyWord();
            } else {
                layer.msg(res.msg);
            }
        }, 'json');
    }, function() {

    });
}

function notify(title, options, callback) {
    // 先检查浏览器是否支持
    if (!window.Notification || document.visibilityState != 'hidden') {
        return;
    }
    var notification;
    // 检查用户曾经是否同意接受通知
    if (Notification.permission === 'granted') {
        notification = new Notification(title, options); // 显示通知

    } else if (Notification.permission === 'default') {
        var promise = Notification.requestPermission();
    }

    if (notification && callback) {
        notification.onclick = function(event) {
            callback(notification, event);
        }
    }
}
if (window.Notification && Notification.permission === 'default') {
    var promise = Notification.requestPermission();
}

// 简单粗暴的心跳
timeInterval = setInterval(function () {
    socket.send(JSON.stringify({
        cmd: "ping"
    }))
}, 20000);