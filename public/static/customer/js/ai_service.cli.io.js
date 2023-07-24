/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/2/16
 * Time: 8:19 PM
 */
// 服务的客服标识
var kefuCode = 0;
// 服务的客服名称
var kefuName = '';
// 是否已经连接过
var isConnect = 0;
// 访客基础信息
var customer = {
    uid: 0,
    name: '',
    avatar: '',
    seller: seller,
    tk: '',
    t: ''
};
// 常见问题
var comQuestion = '';
// 欢迎语
var helloWord = '';
// 是否打开
var isOpen = 0;
// 重连客服计时句柄
var reConnectInterval = 0;
// 重新接入计时句柄
var reInInterval = 0;
// 最大重新连接次数
var nowRetryNum = 1;
var maxRetryNum = 3;
// 最大重新进入次数
var nowRetryInNum = 1;
var maxRetryInNum = 3;
// 评价星级
var praiseStar = 5;
var praiseLogId = 0;
// 断网标识
var isBreak = 1;
// 会否评价断开
var isPraise = 0;
// 是否打开声音
var isOpenVoice = 1;
// 选择了转人工
var chooseService = localStorage.getItem('staff_service');
if (0 == robot_open) {
    chooseService = 1;
}
if (null == chooseService) {
    chooseService = 0;
} else if (1 == chooseService) {
    $('.staff-service').hide();
}

// 统一客服不在线，留言提示语
var leaveMsg = '当前无在线客服，您可以咨询机器人或者点击a(/index/index/leaveMsg/s/' + customer.seller + ')[留言]，进行留言。';

var socket = io(window.location.hostname + ':' + port);

socket.on("disconnect", function () {
    console.log("断开连接");
    isConnect = 0;
    kefuCode = 0;
    kefuName = 0;
    isBreak = 0;

    if (0 == isPraise) {
        $.showLoading("重连中");
    }
});

socket.on("connect", function () {
    console.log("链接成功");

    // 兼容断网
    if (0 == isConnect && 0 == isBreak) {
        tryReIn();

        if (1 == isOpen || 2 == type) {
            tryToConnect();
        }
    }
});

// 聊天消息
socket.on("chatMessage", function (data) {

    var chatMsg = ai_service.showMessage(data.data);
    $(".chat-box").append(chatMsg);
    wordBottom();
    ai_service.showBigPic();

    if (1 == type) {

        if (0 == isOpen) {
            top.postMessage("show_chat", '*');
        }

        // 处理未读
        handleNoRead();
    } else if (2 == type) {

        if (!document.hidden) {
            handleNoRead();
        }

        ai_service.voice();
    }
});

// 常见问题
socket.on("comQuestion", function (data) {
    comQuestion = ai_service.showMessage(data.data);
});

// 问候语
socket.on("hello", function (data) {

    helloWord = ai_service.showMessage(data.data);
});

// 被关闭
socket.on("isClose", function (data) {
    kefuCode = 0;
    kefuName = '';
    // $(".chat-box").append(ai_service.showSystem(data.data.msg));
    isConnect = 0;
    if (0 == robot_open) {

        window.location.href = '/index/index/leaveMsg/s/' + customer.seller;
    } else {
        showRobotService();
        showRobotServiceNotice('');
    }
});

// 处理转接
socket.on("relink", function (data) {
    kefuCode = data.data.kefu_code;
    kefuName = data.data.kefu_name;

    $('.chat-header-title').text(kefuName);

    $(".chat-box").append(ai_service.showSystem(data.data.msg));
    wordBottom();
});

// 被主动接待
socket.on("linkByKF", function (data) {
    isConnect = 1;
    kefuCode = data.kefu_code;
    kefuName = data.kefu_name;

    $('.chat-header-title').text(kefuName);
    // 关闭机器人服务描述
    chooseService = 1;
    localStorage.setItem('staff_service', '1');
    $('.staff-service').hide();

    if (0 == isOpen) {
        isConnect = 1;
        top.postMessage("show_chat", '*');
    }
    getChatLog(customer.uid, 1);
});

// 评价客服
socket.on("praiseKf", function (data) {
    if (0 == isOpen) {
        isConnect = 1;
        top.postMessage("show_chat", '*');
    }

    getChatLog(customer.uid, 1);
    showPraise(data.data.service_log_id);
    // 设为机器人服务
    localStorage.setItem('staff_service', '0');
});

// 标记已读
socket.on("readMessage", function (data) {

    if (1 == type && 1 == isOpen) {

        $('.chat-box').find('.no-read').each(function () {
            var mid = data.mid.split(',');
            if (-1 != $.inArray($(this).attr('data-msg-id'), mid)) {
                $(this).removeClass('no-read').addClass("already-read").text('已读');
            }
        });
    } else if (2 == type) {

        $('.chat-box').find('.no-read').each(function () {
            var mid = data.mid.split(',');
            if (-1 != $.inArray($(this).attr('data-msg-id'), mid)) {
                $(this).removeClass('no-read').addClass("already-read").text('已读');
            }
        });
    }
});

// 发送消息对方未收到
socket.on('receiveFail', function (data) {
    console.log(data);
});

// 收到心跳
socket.on("pong", function (data) {
    socket.emit("ping", JSON.stringify({
        data: "ping"
    }));
});

// 判断页面是否激活
var hiddenProperty = 'hidden' in document ? 'hidden' :
    'webkitHidden' in document ? 'webkitHidden' :
        'mozHidden' in document ? 'mozHidden' :
            null;
var visibilityChangeEvent = hiddenProperty.replace(/hidden/i, 'visibilitychange');

var onVisibilityChange = function(){
    if (!document[hiddenProperty]) {

        // 处理未读
        handleNoRead();
    }
};

document.addEventListener(visibilityChangeEvent, onVisibilityChange);

// 尝试连接客服
function tryToConnect() {

    customer.tk = tk;
    customer.t = t;
    var delay = 0;
    if (type == 2) {
        $.showLoading("连接中...");
        delay = 500;
    }

    setTimeout(function () {
        socket.emit("userInit", JSON.stringify(customer), function (data) {
            $.hideLoading();
            var data = eval("(" + data + ")");
            if (400 == data.code) {
                clearInterval(reConnectInterval);
                if (nowRetryNum < maxRetryNum) {

                    reConnectInterval = setInterval(function () {
                        tryToConnect();
                        nowRetryNum++;
                    }, 2000);
                } else {

                    isConnect = 0;
                    if (0 == robot_open) {

                        window.location.href = '/index/index/leaveMsg/s/' + customer.seller;
                    } else {
                        showRobotService();
                        showRobotServiceNotice('');
                    }
                }
            } else if(0 == data.code) {

                clearInterval(reConnectInterval);
                isConnect = 1;
                isOpen = 1;

                kefuCode = data.data.kefu_code;
                kefuName = data.data.kefu_name;

                $('.chat-header-title').text(kefuName);

                getChatLog(customer.uid, 1);
                pushCustomerReferrer(customer.uid);
            } else if(201 == data.code) {

                clearInterval(reConnectInterval);
                isConnect = 0;

                if (0 == robot_open) {

                    window.location.href = '/index/index/leaveMsg/s/' + customer.seller;
                } else {
                    showRobotService();
                    showRobotServiceNotice('');
                }

            } else if(202 == data.code || 500 == data.code) {

                clearInterval(reConnectInterval);
                $(".chat-box").append(ai_service.showSystem(data.msg));
                isConnect = 0;
            } else if(204 == data.code) {
                $.alert(data.msg);
                isConnect = 0;
            }
        });
    }, delay);
}

// 尝试直接连接指定客服
function tryDirectLinkKeFu() {

    customer.tk = tk;
    customer.t = t;
    customer.kefu_code = direct_kefu;
    $.showLoading()
    setTimeout(function () {
        $.hideLoading();
        socket.emit("directLinkKF", JSON.stringify(customer), function (data) {

            var data = eval("(" + data + ")");
            if (400 == data.code) {
                clearInterval(reConnectInterval);
                if (nowRetryNum < maxRetryNum) {

                    reConnectInterval = setInterval(function () {
                        tryDirectLinkKeFu();
                        nowRetryNum++;
                    }, 2000);
                } else {

                    isConnect = 0;

                    if (0 == robot_open) {

                        window.location.href = '/index/index/leaveMsg/s/' + customer.seller;
                    } else {
                        showRobotService();
                        showRobotServiceNotice('');
                    }
                }
            } else if(0 == data.code) {

                clearInterval(reConnectInterval);
                isConnect = 1;
                isOpen = 1;

                kefuCode = data.data.kefu_code;
                kefuName = data.data.kefu_name;

                $('.chat-header-title').text(kefuName);

                getChatLog(customer.uid, 1);
                pushCustomerReferrer(customer.uid);
            } else if(201 == data.code) {

                clearInterval(reConnectInterval);
                isConnect = 0;

                if (0 == robot_open) {

                    window.location.href = '/index/index/leaveMsg/s/' + customer.seller;
                } else {
                    showRobotService();
                    showRobotServiceNotice('');
                }
            } else if(202 == data.code || 500 == data.code) {

                clearInterval(reConnectInterval);
                $(".chat-box").append(ai_service.showSystem(data.msg));
                isConnect = 0;
            }
        });
    }, 500);
}

// 尝试接入
function tryReIn() {
    if (!customer.uid) {
        $.alert("您的浏览器开启了无痕模式，建议关闭后再咨询！");
        return false;
    }

    socket.emit("customerIn", JSON.stringify({
        data: {
            customer_id: customer.uid,
            customer_name: customer.name,
            customer_avatar: customer.avatar,
            seller_code: seller,
            tk: tk,
            t: t
        }
    }), function (data) {
        $.hideLoading();
        var data = eval("(" + data + ")");
        if (400 == data.code) {
            clearInterval(reInInterval);
            if (nowRetryInNum < maxRetryInNum) {

                reInInterval = setInterval(function () {
                    tryReIn();
                    nowRetryInNum++;
                }, 1000);
            } else {

                isConnect = 0;

                if (0 == robot_open) {

                    window.location.href = '/index/index/leaveMsg/s/' + customer.seller;
                } else {
                    showRobotService();
                    showRobotServiceNotice('');
                }
            }
        } else if(0 == data.code) {

            clearInterval(reInInterval);
        } else if(201 == data.code) {
            isConnect = 0;

            if (0 == robot_open) {

                window.location.href = '/index/index/leaveMsg/s/' + customer.seller;
            } else {
                showRobotService();
                showRobotServiceNotice('');
            }
        } else if(204 == data.code) {
            $.alert(data.msg);
        }
    });
}

// 已选择人工客服
function staffService () {
    // 直连接入
    if (2 == type) {

        // 若指定了用户信息
        if (customerId.length > 0) {

            localStorage.setItem('l-uid', customerId);

            customer.uid = customerId;
            if (cusotmerName.length > 0) {
                customer.name = cusotmerName;
            } else {
                customer.name = '访客' + customerId;
            }

            if (avatar.length > 0) {
                customer.avatar = avatar;
            } else {
                customer.avatar = window.location.origin + '/static/common/images/customer.png';
            }

        } else {

            var tmpUid = localStorage.getItem('l-uid');

            if (tmpUid == null) {
                tmpUid = Number(Math.random().toString().substr(3, 4) + Date.now()).toString(36);
                localStorage.setItem('l-uid', tmpUid);
            }

            customer.uid = tmpUid;
            customer.name = '访客' + tmpUid;
            customer.avatar = window.location.origin + '/static/common/images/customer.png';
        }

        customer.type = 2;
        localStorage.setItem('ai_service_referrer', document.referrer);

        // 固定连接
        if(0 == isConnect) {
            if (direct_kefu != '') {
                tryDirectLinkKeFu();
            } else {
                tryToConnect();
            }
        }

    } else if (1 == type) {
        // 弹层接入
        window.addEventListener('message', function(event){
            var msg = JSON.parse(event.data);
            if('open_chat' == msg.cmd) {
                isOpen = 1;
                if(0 == isConnect) {
                    tryToConnect();
                }
            } else if('c_info' == msg.cmd) {
                customer.uid = msg.data.uid;
                customer.name = msg.data.uName;
                customer.avatar = msg.data.avatar;

                if (msg.data.uid == null) {
                    var tmpUid = Number(Math.random().toString().substr(3, 4) + Date.now()).toString(36);
                    customer.uid = tmpUid;
                    customer.name = '访客' + tmpUid;
                    customer.avatar = window.location.origin + '/static/common/images/customer.png';
                }

                localStorage.setItem('ai_service_referrer', msg.data.referrer);

                // 访客进入
                tryReIn();
            }
        }, false);
    }
}

// 主动点击人工客服
function staffServiceHandle () {
    // 直连接入
    if (2 == type) {

        var tmpUid = localStorage.getItem('l-uid');

        if (tmpUid == null) {
            tmpUid = Number(Math.random().toString().substr(3, 4) + Date.now()).toString(36);
            localStorage.setItem('l-uid', tmpUid);
        }

        if (cusotmerName.length > 0) {
            customer.name = cusotmerName;
        } else {
            customer.name = '访客' + tmpUid;
        }

        if (avatar.length > 0) {
            customer.avatar = avatar;
        } else {
            customer.avatar = window.location.origin + '/static/common/images/customer.png';
        }

        customer.type = 2;
        localStorage.setItem('ai_service_referrer', document.referrer);

        // 固定连接
        if(0 == isConnect) {
            if (direct_kefu != '') {
                tryDirectLinkKeFu();
            } else {
                tryToConnect();
            }
        }

    } else if (1 == type) {
        // 弹层接入
        if(0 == isConnect) {
            tryToConnect();
        }
    }
}

// 机器人服务
function robotService () {

    if (2 == type) {
        // 若指定了用户信息
        if (customerId.length > 0) {

            localStorage.setItem('l-uid', customerId);

            customer.uid = customerId;
            if (cusotmerName.length > 0) {
                customer.name = cusotmerName;
            } else {
                customer.name = '访客' + customerId;
            }

            if (avatar.length > 0) {
                customer.avatar = avatar;
            } else {
                customer.avatar = window.location.origin + '/static/common/images/customer.png';
            }

        } else {

            var tmpUid = localStorage.getItem('l-uid');

            if (tmpUid == null) {
                tmpUid = Number(Math.random().toString().substr(3, 4) + Date.now()).toString(36);
                localStorage.setItem('l-uid', tmpUid);
            }

            customer.uid = tmpUid;
            customer.name = '访客' + tmpUid;
            customer.avatar = window.location.origin + '/static/common/images/customer.png';
        }

        customer.type = 2;
        localStorage.setItem('ai_service_referrer', document.referrer);

        var robotHello = ai_service.showMessage({
            read_flag: 2,
            chat_log_id: 0,
            content: robot_hello,
            time: ai_service.getCurrDate(),
            avatar: '/static/common/images/robot.jpg'
        });

        $(".chat-box").append(robotHello);
        getChatLog(customer.uid, 1);
    } else {

        window.addEventListener('message', function(event){
            var msg = JSON.parse(event.data);
            if('open_chat' == msg.cmd) {
                isOpen = 1;

                var robotHello = ai_service.showMessage({
                    read_flag: 2,
                    chat_log_id: 0,
                    content: robot_hello,
                    time: ai_service.getCurrDate(),
                    avatar: '/static/common/images/robot.jpg'
                });

                $(".chat-box").append(robotHello);
                getChatLog(customer.uid, 1);
            } else if('c_info' == msg.cmd) {

                customer.uid = msg.data.uid;
                customer.name = msg.data.uName;
                customer.avatar = msg.data.avatar;

                if (msg.data.uid == null) {
                    var tmpUid = Number(Math.random().toString().substr(3, 4) + Date.now()).toString(36);
                    customer.uid = tmpUid;
                    customer.name = '访客' + tmpUid;
                    customer.avatar = window.location.origin + '/static/common/images/customer.png';
                }

                localStorage.setItem('ai_service_referrer', msg.data.referrer);

                // 访客进入
                tryReIn();
            }
        }, false);
    }
}

// 展示机器人服务消息
function showRobotServiceNotice (content) {

    if ('' == content) {
        content = leaveMsg;
    }

    var robotHello = ai_service.showMessage({
        read_flag: 2,
        chat_log_id: 0,
        content: content,
        time: ai_service.getCurrDate(),
        avatar: '/static/common/images/robot.jpg'
    });

    $(".chat-box").append(robotHello);
    wordBottom();
}

// 机器人问答
function robotAnswer () {

    $("#sendBtn").removeClass('active');

    var input = $("#textarea").val();
    if (input.replace(/^\s*|\s*$/g,"") == '') {
        return false;
    }

    var msg = ai_service.completeReadSend(input, customer.avatar, 0);
    $(".chat-box").append(msg);
    $("#textarea").val('');
    wordBottom();

    $.post('/index/robot/service', {
        seller_id: seller_id,
        q: input,
        seller_code: customer.seller,
        from_id: customer.uid,
        from_name: customer.name,
        from_avatar: customer.avatar
    }, function (res) {

        var robotHello = ai_service.showMessage({
            read_flag: 2,
            chat_log_id: 0,
            content: res.msg,
            time: ai_service.getCurrDate(),
            avatar: '/static/common/images/robot.jpg'
        });

        $(".chat-box").append(robotHello);
        wordBottom();
    }, 'json');
}

// 显示机器人服务
function showRobotService () {

    chooseService = 0;
    localStorage.setItem('staff_service', "0");
    $('.chat-header-title').text(robot_title);

    var robotHello = ai_service.showMessage({
        read_flag: 2,
        chat_log_id: 0,
        content: robot_hello,
        time: ai_service.getCurrDate(),
        avatar: '/static/common/images/robot.jpg'
    });

    $(".chat-box").append(robotHello);
    wordBottom();

    $(".staff-service").show();
}

$(function () {

    // 处理粘贴事件
    listenPaste();

    // 人工客服和机器人客服切换
    if (0 == chooseService && 1 == robot_open) {
        robotService();
    } else {
        staffService();
    }

    // 最小化
    $("#closeBtn").on('click', function () {
        isOpen = 0;
        top.postMessage("hide_chat", '*');
    });

    // 发送
    if (os == 'm') {
        var sendObj = document.getElementById("sendBtn");
        sendObj.addEventListener('touchend', function(e) {
            sendMessage('');
            $("#sendBtn").removeClass('active');
        });
    } else {
        $("#sendBtn").on('click', function () {
            sendMessage('');
            $("#sendBtn").removeClass('active');
        });
    }

    // 输入监听
    $("#textarea").keyup(function () {
        var len = $(this).val().length;
        if(len == 0) {
            $("#sendBtn").removeClass('active');
        } else if(len >0 && !$("#sendBtn").hasClass('active')) {
            $("#sendBtn").addClass('active');

        }
    });

    // 实时发送自己的输入，供客服预览
    if (pre_see) {
        $("#textarea").bind("input propertychange",function(event){
            var inputWord = $(this).val();
            socket.emit('typing',
                JSON.stringify({
                    from_name: customer.name,
                    from_avatar: customer.avatar,
                    from_id: customer.uid,
                    to_id: kefuCode,
                    to_name: kefuName,
                    content: inputWord,
                    seller_code: seller
                }));
        });
    }

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
            var height = $(".chat-body").height() - 140;
            layer.ready(function () {
                index = layer.open({
                    type: 1,
                    offset: [height + 'px', '0px'],
                    shade: false,
                    title: false,
                    closeBtn: 0,
                    area: '395px',
                    content: ai_service.showFaces()
                });
            });
        });
    });

    // 监听快捷键发送
    document.getElementById('textarea').addEventListener('keydown', function (e) {
        if (e.keyCode != 13) return;
        e.preventDefault();  // 取消事件的默认动作
        sendMessage('');
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
            ,title: false // 不显示标题栏
            ,closeBtn: false
            ,area: '250px;'
            ,shade: 0.3
            ,id: 'LAY_layuipro' // 设定一个id，防止重复弹出
            ,resize: false
            ,btn: ['完成发送', '放弃发送']
            ,btnAlign: 'c'
            ,moveType: 1 // 拖拽模式，0或者1
            ,content: '<div style="padding: 50px; line-height: 22px; background-color: #393D49; color: #fff; font-weight: 300;">录音中，请说话......</div>'
            ,yes: function(){
                stopRecording();
                layer.close(vindx);
            }
        });
    });

    // 开启关闭声音
    $("#operatorVoice").click(function () {
        if (0 == isOpenVoice) {
            $("#openVoice").show();
            $("#closeVoice").hide();
            isOpenVoice = 1;
            ai_service.voice();
        } else {
            $("#openVoice").hide();
            $("#closeVoice").show();
            isOpenVoice = 0;
        }
    });

    // 转人工服务
    $(".staff-service").click(function () {

        staffServiceHandle();
        chooseService = 1; // 人工服务
        localStorage.setItem('staff_service', "1");
        $(this).hide();
    });
});

// 发送消息
function sendMessage(inMsg) {

    // 机器人服务
    if (0 == chooseService) {

        robotAnswer();
        return false;
    }

    if(kefuCode == 0) {

        layui.use('layer', function () {
            var layer = layui.layer;
            layer.msg('暂无客服提供服务', {anim: 6});
        });

        return ;
    }

    if('' == inMsg) {
        var input = $("#textarea").val();
    } else {
        var input = inMsg;
    }

    if(input.length == 0 || input.replace(/^\s*|\s*$/g,"") == '') {
        return ;
    }

    socket.emit('chatMessage',
        JSON.stringify({
            from_name: customer.name,
            from_avatar: customer.avatar,
            from_id: customer.uid,
            to_id: kefuCode,
            to_name: kefuName,
            content: input,
            seller_code: seller
        }), function (data) {

            var data = JSON.parse(data); // 发送成功或者失败回调

            if (400 == data.code) {

                var msg = ai_service.send(input, customer.avatar, data.data);
            } else if (0 == data.code) {
                var msg = ai_service.send(input, customer.avatar, data.data);
            }

            $(".chat-box").append(msg);
            $("#textarea").val('');

            $(this).removeClass('active');

            wordBottom();
            ai_service.showBigPic();
        });
}

// 获取聊天记录
function getChatLog(uid, page, flag, bottom) {

    $.getJSON('/index/index/getChatLog', {uid: uid, page: page, tk: tk, t: t, u: seller}, function(res){
        if(0 == res.code && res.data.length > 0) {

            if(res.msg == res.total){
                var _html = '<div class="clearfloat"><div class="author-name"><small>没有更多了</small></div><div style="clear:both"></div></div>';
            }else{
                var _html = '<div class="clearfloat"><div class="author-name" data-page="' + parseInt(res.msg + 1)
                    + '" onclick="getMore(this)"><small class="chat-system">更多记录</small></div><div style="clear:both"></div></div>';
            }

            $.each(res.data, function (k, v) {
                if(v.type == 'mine') {

                    _html += ai_service.showMyChatLog(v);
                } else if(v.type == 'user'){

                    _html += ai_service.showMessage({time: v.create_time, avatar: v.from_avatar, content: v.content, chat_log_id: v.log_id, read_flag: v.read_flag});
                }
            });

            if(typeof flag == 'undefined'){
                $(".chat-box").html(_html);
            }else{
                $(".chat-box").prepend(_html);
            }

            ai_service.showBigPic();

            if (helloWord != '') {
                $(".chat-box").append(helloWord);
            }

            if (comQuestion != '') {
                $(".chat-box").append(comQuestion);
            }

            if(typeof bottom == 'undefined') {
                wordBottom();
            }
        } else if (0 == res.code && res.data.length == '') {

            if (helloWord != '') {
                $(".chat-box").append(helloWord);
            }

            if (comQuestion != '') {
                $(".chat-box").append(comQuestion);
            }

            wordBottom();
        }

        handleNoRead();
    });
}

// 发送来路信息
function pushCustomerReferrer(customerId) {
    $.getJSON('/index/index/updateUserInfo', {
        customer_id: customerId,
        seller_code: seller,
        referrer: localStorage.getItem('ai_service_referrer')
    }, function (res) {});
}

// 获取更多的的记录
function getMore(obj) {
    $(obj).remove();

    var page = $(obj).attr('data-page');

    getChatLog(customer.uid, page, 1, 1);
}

// 滚动到最底端
function wordBottom() {
    var box = $(".chat-box");

    box.scrollTop(box[0].scrollHeight);
}

// 图片 文件上传
layui.use(['upload', 'layer'], function () {
    var upload = layui.upload;
    var layer = layui.layer;

    var index;
    upload.render({
        elem: '#image'
        , accept: 'images'
        , exts: 'jpg|jpeg|png|gif'
        , url: '/index/upload/uploadImg'
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
        , url: '/index/upload/uploadFile'
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

// 点击常见问题
function autoAnswer (obj) {
    var questionId = $(obj).attr('data-id');
    var question = $(obj).text();

    socket.emit("comQuestion", JSON.stringify({
        data: {
            question_id: questionId,
            seller_code: seller
        }
    }), function (data) {

        var data = JSON.parse(data); // 发送成功或者失败回调
        if (400 == data.code) {

            var msg = ai_service.completeReadSend(question, customer.avatar, 1);
            $(".chat-box").append(msg);
        } else if (0 == data.code) {
            var msg = ai_service.completeReadSend(question, customer.avatar, 0);
            $(".chat-box").append(msg);

            var chatMsg = ai_service.showMessage(data.data);
            $(".chat-box").append(chatMsg);
            wordBottom();
            ai_service.showBigPic();
            if (0 == isOpen) {
                top.postMessage("show_chat", '*');
            }
        }
    });
}

// 机器人自动回答
function robotAutoAnswer (obj) {
    var questionId = $(obj).attr('data-id');
    var question = $(obj).text();

    $.post('/index/robot/autoAnswer', {
        id: questionId,
        sid: seller_id,
        seller_code: customer.seller,
        from_id: customer.uid,
        from_name: customer.name,
        from_avatar: customer.avatar
    }, function (res) {

        var msg = ai_service.completeReadSend(question, customer.avatar, 1);
        $(".chat-box").append(msg);

        var msg = ai_service.showMessage({
            read_flag: 2,
            chat_log_id: 0,
            content: res.msg,
            time: ai_service.getCurrDate(),
            avatar: '/static/common/images/robot.jpg'
        });
        $(".chat-box").append(msg);

        wordBottom();
        ai_service.showBigPic();
    }, 'json');
}

// 展示评价
function showPraise(log_id) {

    layui.use(['rate', 'layer'], function(){
        var rate = layui.rate;
        var layer = layui.layer;

        var ins1 = rate.render({
            elem: '#praise_star'
            ,setText: function(value){
                var arrs = {
                    '1': '非常不满意'
                    ,'2': '不满意'
                    ,'3': '一般'
                    ,'4': '满意'
                    ,'5': '非常满意'
                };
                this.span.text(arrs[value] || ( value + "星"));

                praiseStar = value;
            }
            ,value: praiseStar
            ,text: true
        });

        layer.open({
            type: 1,
            title: '',
            closeBtn: false,
            area: ['250px', '180px'],
            content: $("#praise_box"),
            btn: ['确定'],
            yes: function(index, layero){

                $.post('/index/index/praise', {
                    customer_id: customer.uid,
                    kefu_code: kefuCode,
                    seller_code: customer.seller,
                    service_log_id: log_id,
                    star: praiseStar
                }, function (res) {

                    $(".chat-box").append(ai_service.showSystem(res.msg));
                    isPraise = 1;
                    socket.close();
                    wordBottom();
                    kefuCode = 0;
                    kefuName = '';

                    layer.close(index);
                }, 'json');
            }
        });
    });
}

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

                request.onreadystatechange = function() {
                    if (request.readyState == 4 && request.status == 200) {
                        var res = eval('(' + request.response + ')');
                        if(res.code == 0){
                            $("#textarea").val('img['+ (res.data.src||'') +']');
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

                request.open('POST', '/index/upload/uploadImg/');
                request.send(formData);

                //imgReader(item, data.id);
            }
        }
    });
}

// 处理未读
function handleNoRead() {

    var noReadIds = [];
    // 检测全局未读
    $('.chat-box').find(".check-read").each(function () {
        if ($(this).attr('data-msg-id') != "undefined") {
            noReadIds.push($(this).attr('data-msg-id'));
        }
    });

    // 有未读的数据
    if (noReadIds.length > 0) {

        socket.emit("readMessage", JSON.stringify({
            uid: kefuCode,
            mid: noReadIds.join(',')
        }), function (data) {

            var data = JSON.parse(data); // 发送成功或者失败回调
            if (0 == data.code) {
                $('.chat-box').find(".check-read").removeClass('check-read').addClass('complete-read');
            }
        });
    }
}