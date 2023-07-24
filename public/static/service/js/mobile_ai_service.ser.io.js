/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2019/4/9
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
// 当前交流的日志id
var logId = 0;
// 上一次发送时间
var lastedTime = 0;
// 客服下班
var offWork = 0;
// 是否点击显示表情的标志
var flag = 1;
// 主动接待的访客
var careCustomer = {
    customer_id: 0,
    customer_name: '',
    customer_avatar: '',
    customer_ip: '',
    seller_code: seller
};

var socket = io(window.location.hostname + ':' + port);

socket.on("connect", function () {

    socket.emit("init", JSON.stringify({
        uid: kefuUser.uid
    }), function (data) {
        layui.use('layer', function () {

            var layer = layui.layer;
            layer.ready(function () {
                layer.msg('连接成功');
            });
        });
    });
});

// 访客链接
socket.on("customerLink", function (msg) {
    addUser(msg);
});

// 聊天
socket.on("chatMessage", function (data) {
    showMessage(data.data);
});

// 访客离线
socket.on("offline", function (data) {
    console.log(data);
    $('#c-' + data.data.customer_id).parent().addClass('layim-list-gray');
});

// 接到转接
socket.on("reLink", function (data) {
    console.log(data);
    addUser(data.data);
});

// 标记已读
socket.on("readMessage", function (data) {
    console.log(data);
    $("#customer-" + activeUser).find('.no-read').each(function () {
        var mid = data.mid.split(',');
        if (-1 != $.inArray($(this).attr('data-msg-id'), mid)) {
            $(this).removeClass('no-read').addClass("already-read").text('已读');
        }
    });
});

// 动态删除访客列表
socket.on("removeQueue", function (data) {
    console.log(data);
    $("#h-" + data.customer_id).parent().remove();
});

// 收到心跳
socket.on("pong", function (data) {
    socket.emit("ping", JSON.stringify({
        data: "ping"
    }));
});

$(function () {

    initWork();

    // 修复IOS下输入法遮挡问题
    $('#msg').on('focus', function () {

        setTimeout(function(){
            document.getElementsByTagName('body')[0].style.height = (window.innerHeight + 500) + 'px';
            document.body.scrollTop = 500;
        }, 300);
    });

    $('#msg').on('blur', function () {
        document.getElementsByTagName('body')[0].style.height = window.innerHeight + 'px';
    });

    // 监听输入改变发送按钮
    $("#msg").bind('input porpertychange', function(){

        if($("#msg").val().length > 0){
            $(".layim-send").removeClass('layui-disabled');
        }else{
            $(".layim-send").addClass('layui-disabled');
        }
    });

    // 点击发送
    $("#send").click(function(){
        sendMessage('');
    });

    // 点击表情
    $('#up-face').click(function(e){
        e.stopPropagation();

        if(1 == flag){
            showFaces();
            $('#face-box').show();
            flag = 2;
        }else{
            $('#face-box').hide();
            flag = 1;
        }
    });

    // 监听点击旁边关闭表情
    document.addEventListener("click", function(){
        if(2 == flag){
            $('#face-box').hide();
            flag = 1;
        }
    });

    // 客服退出
    $("#loginOut").click(function () {

        layer.alert('正在关闭咨询的用户', {
            icon: 6,
            title: '',
            closeBtn: 0,
            btn: false
        });

        if(servicePool.length == 0) {
            window.location.href = '/service/login/loginOut';
            return ;
        }

        $.each(servicePool, function (k, v) {
            closeUser(v);
        });

        offWork = 1;
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

        socket.emit("praiseKf", JSON.stringify({
            data: {
                customer_id: activeUser,
                service_log_id: $('#c-' + activeUser).attr('data-log')
            }
        }), function (res) {
            var data = JSON.parse(res);
            if (0 == data.code) {

                layui.use('layer', function () {
                    var layer = layui.layer;

                    layer.msg("已发送评价");
                });
            } else {
                layui.use('layer', function () {
                    var layer = layui.layer;

                    layer.msg("无法发送评价");
                });
            }
        });
    });

    // 工具栏切换
    $("#tool-bar li").click(function () {
        $(this).removeClass('layim-this').addClass('layim-this').siblings().removeClass('layim-this');

        var index = $(this).index();
        if (index == 0) {
            $("#now-chat").show();
            $("#prepare-chat").hide();
            $("#history-chat").hide();
            $("#mine-info").hide();
            servicePool = [];
            initWork();
        } else if (index == 1) {
            $("#now-chat").hide();
            $("#prepare-chat").show();
            $("#history-chat").hide();
            $("#mine-info").hide();

            initQueue();
        } else if (index == 2) {
            $("#now-chat").hide();
            $("#prepare-chat").hide();
            $("#history-chat").show();
            $("#mine-info").hide();

            initHistoryChat();
        } else if (index == 3) {
            $("#now-chat").hide();
            $("#prepare-chat").hide();
            $("#history-chat").hide();
            $("#mine-info").show();

            $(".avatar").attr('style', 'background:url(' + kefuUser.avatar + ') no-repeat;background-size: 100px 100px;');
            $(".user-name").text('您好：' + kefuUser.name);

            $.getJSON("/service/service/census/u/" + seller, function (res) {
               if (0 == res.code) {
                   $("#t-s").text(res.data.totalNum);
                   $("#g-p").text(res.data.goodPercent);
                   $("#n-s").text(res.data.nowNum);
               }
            });
        }
    });
});

// 展示表情数据
function showFaces(){
    var alt = getFacesIcon();
    var _html = '<ul class="layui-layim-face">';
    var len = alt.length;
    for(var index = 0; index < len; index++){
        _html += '<li title="' + alt[index] + '" onclick="checkFace(this)"><img src="/static/common/images/face/'+ index + '.gif" /></li>';
    }
    _html += '</ul>';

    document.getElementById('face-box').innerHTML = _html;
}

function getFacesIcon() {
    return ["[微笑]", "[嘻嘻]", "[哈哈]", "[可爱]", "[可怜]", "[挖鼻]", "[吃惊]", "[害羞]", "[挤眼]", "[闭嘴]", "[鄙视]",
        "[爱你]", "[泪]", "[偷笑]", "[亲亲]", "[生病]", "[太开心]", "[白眼]", "[右哼哼]", "[左哼哼]", "[嘘]", "[衰]",
        "[委屈]", "[吐]", "[哈欠]", "[抱抱]", "[怒]", "[疑问]", "[馋嘴]", "[拜拜]", "[思考]", "[汗]", "[困]", "[睡]",
        "[钱]", "[失望]", "[酷]", "[色]", "[哼]", "[鼓掌]", "[晕]", "[悲伤]", "[抓狂]", "[黑线]", "[阴险]", "[怒骂]",
        "[互粉]", "[心]", "[伤心]", "[猪头]", "[熊猫]", "[兔子]", "[ok]", "[耶]", "[good]", "[NO]", "[赞]", "[来]",
        "[弱]", "[草泥马]", "[神马]", "[囧]", "[浮云]", "[给力]", "[围观]", "[威武]", "[奥特曼]", "[礼物]", "[钟]",
        "[话筒]", "[蜡烛]", "[蛋糕]"]
}

// 选择表情
function checkFace(obj){
    var msg = document.getElementById('msg').value;
    document.getElementById('msg').value = 	msg + ' face' + obj.title + ' ';
    document.getElementById('face-box').style.display = 'none';
    $(".layim-send").removeClass('layui-disabled');
    flag = 1;
}

// 点击关闭访客
function ckCloseUser(userId) {
    event.stopPropagation();
    var protocol = $('#c-' + userId).attr('data-protocol');
    if ('ws' == protocol) {

        socket.emit("closeUser", JSON.stringify({
            data: {
                kefu_code: kefuUser.uid,
                customer_id: userId
            }
        }), function (res) {
            var data = JSON.parse(res);
            console.log(data);
            if (0 == data.code) {
                $.each(servicePool, function (k, v) {
                    if(v == userId) {
                        servicePool.splice(k, 1);
                    }
                });
                $('#c-' + userId).parent().remove();
            } else {
                layui.use('layer', function () {
                    var layer = layui.layer;

                    layer.alert("关闭失败");
                });
            }
        });
    } else if ('http' == protocol) {

        $.post('/index/api/closeUser', {
            cmd: 'closeUser',
            data: {
                kefu_code: kefuUser.uid,
                customer_id: userId
            }
        }, function (res) {

            if (0 == res.code) {
                removeCustomer(userId)
            }
        }, 'json');
    }
}

// 主动关闭用户
function closeUser(userId) {

    var protocol = $('#c-' + userId).attr('data-protocol');
    if ('ws' == protocol) {

        socket.emit("closeUser", JSON.stringify({
            data: {
                kefu_code: kefuUser.uid,
                customer_id: userId
            }
        }), function (res) {
            var data = JSON.parse(res);
            console.log(data);
            if (0 == data.code) {
                //removeCustomer(userId);
                window.location.href = '/service/login/loginOut';
            } else {
                layui.use('layer', function () {
                    var layer = layui.layer;

                    layer.alert("关闭失败");
                });
            }
        });
    } else if ('http' == protocol) {

        $.post('/index/api/closeUser', {
            cmd: 'closeUser',
            data: {
                kefu_code: kefuUser.uid,
                customer_id: userId
            }
        }, function (res) {

            if (0 == res.code) {
                removeCustomer(userId)
            }
        }, 'json');
    }
}

// 展示聊天用户
function addUser(customer) {

    if(-1 != $.inArray(customer.customer_id, servicePool)) {

        $('#c-' + customer.customer_id).parent().removeClass('layim-list-gray');
        $("#c-" + customer.customer_id).attr('data-log', customer.log_id);
        $("#c-" + customer.customer_id).attr('data-ip', customer.customer_ip);
        return;
    }

    // 聊天用户
    var _html = '<li class="item" onclick="showChatBox(this)"><div class="info" id="c-' + customer.customer_id + '" data-id="'
        + customer.customer_id +'" data-name="' + customer.customer_name + '" data-log="' + customer.log_id +
        '" data-ip="' + customer.customer_ip + '" data-protocol="' + customer.protocol + '">';
    _html += '<div><img src="' + customer.customer_avatar + '"></div>';
    _html += '<span>' + customer.customer_name + '</span>';
    _html += '<p>' + customer.customer_ip + ' ' + customer.province + customer.city + '</p>';
    _html += '<span class="layim-msg-status">new</span></div><span class="delbtn" onclick="ckCloseUser(\'' + customer.customer_id + '\')">删除</span></li>';

    $('#chat-list').prepend(_html);
    slide();
    servicePool.push(customer.customer_id);

    // 添加聊天区域
    var chat_box = '<ul id="customer-' + customer.customer_id + '" style="display: none"></ul>';
    $("#boxes").append(chat_box);
}

// 展示聊天框
function  showChatBox(obj) {

    $("#chat-boxes").show();
    $("#customer").text($(obj).find(".info").attr('data-name'));
    $("#customer-" + $(obj).find(".info").attr('data-id')).show().siblings().hide();

    activeUser = $(obj).find(".info").attr('data-id');
    activeName = $(obj).find(".info").attr('data-name');
    activeAvatar = $(obj).find(".info").attr('data-avatar');

    hideNew($(obj).find(".info").attr('data-id'));

    getChatLog($(obj).find(".info").attr('data-id'), 1);
}

// 隐藏聊天框
function hideBox() {

    $("#chat-boxes").hide();
    $("#boxes ul").hide();
}

// 显示有新消息到
function showNew(id) {
    if($("#customer-" + id).css('display') == "none") {
        $('#c-' + id).find('.layim-msg-status').addClass('layui-show');
    }
}

// 移除新消息
function hideNew(id) {
    $('#c-' + id).find('.layim-msg-status').removeClass('layui-show');
}

// 对话框定位到最底端
function wordBottom() {
    // 滚动条自动定位到最底端
    var box = $(".layim-chat-main");
    box.scrollTop(box[0].scrollHeight);
}

// 获取时间
function getTime(){
    var myDate = new Date();
    var year = myDate.getFullYear();
    var month = myDate.getMonth();
    var day = myDate.getDay();
    var hour = myDate.getHours();
    var minute = myDate.getMinutes();
    var second = myDate.getSeconds();

    if(month < 10) month = '0' + month;
    if(day < 10) day = '0' + day;
    if(hour < 10) hour = '0' + hour;
    if(minute < 10) minute = '0' + minute;
    if(second < 10) second = '0' + second;

    return year + '-' + month + '-' + day + ' ' + hour + ':' + minute + ':' + second;
}

// 发送消息
function sendMessage(inMsg) {
    if('' == inMsg) {
        var input = $("#msg").val();
    } else {
        var input = inMsg;
    }

    if(activeUser == 0 || input.length == 0) {
        return ;
    }

    var _html = $("#customer-" + activeUser).html();
    var time = getTime();
    var content = ai_service.replaceContent(input);

    var nowTime =  new Date().getTime();
    if(nowTime - (lastedTime||0) > 60*1000){
        _html += '<li class="layim-chat-system"><span>' + time + '</span></li>';
        lastedTime = nowTime;
    }

    var protocol = $('#c-' + activeUser).attr('data-protocol');
    if ('ws' == protocol) {

        socket.emit('chatMessage', JSON.stringify({
            from_name: kefuUser.name,
            from_avatar: kefuUser.avatar,
            from_id: kefuUser.uid,
            to_id: activeUser,
            to_name: activeName,
            content: input,
            seller_code: seller
        }), function (data) {

            var data = JSON.parse(data); // 发送成功或者失败回调
            console.log(data);

            _html += '<li class="layim-chat-li layim-chat-mine">';
            _html += '<div class="layim-chat-user">';
            _html += '<img src="' + kefuUser.avatar + '"><cite>我</cite></div>';
            _html += '<div class="layim-chat-text">' + content + ' </div>';
            _html += '<p class="read-flag no-read" data-msg-id="' + data.data + '">未读</p></li>';

            $("#customer-" + activeUser).html(_html);

            $('#msg').val('');
            $(".layim-send").addClass('layui-disabled');

            $(this).removeClass('active');

            wordBottom(activeUser);
            ai_service.showBigPic();
        });
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

            console.log(res);
        }, 'json');
    }
}

// 展示聊天消息
function showMessage(info) {

    var _html = $("#customer-" + info.id).html();
    var content = ai_service.replaceContent(info.content);

    var nowTime =  new Date().getTime();
    if(nowTime - (lastedTime||0) > 60*1000) {
        _html += '<li class="layim-chat-system"><span>' + info.time + '</span></li>';
        lastedTime = nowTime;
    }

    var readFlag = '<div class="layim-chat-text check-read" data-msg-id="' + info.chat_log_id + '">' + content + '</div>';
    if (2 == info.read_flag) {
        readFlag = '<div class="layim-chat-text check-read" data-msg-id="' + info.chat_log_id + '">' + content + '</div>';
    }

    _html += '<li class="layim-chat-li">';
    _html += '<div class="layim-chat-user">';
    _html += '<img src="' + info.avatar + '"><cite>' + info.name + '</cite></div>';
    _html +=  readFlag + '</li>';

    $("#customer-" + info.id).html(_html);

    // 处理未读
    if ($("#customer-" + info.id).css('display') == "block") {
        handleNoRead(info.id)
    }

    showNew(info.id);

    // 滚动条自动定位到最底端
    wordBottom();

    // 声音提醒
    ai_service.voice();

    showBigPic();
}

// 显示大图
function showBigPic() {

    $(".layui-ai_service-photos").on('click', function () {
        var src = this.src;

        layer.open({
            type: 1
            , title: '大图模式'
            ,content: '<img src="' + src + '" width="100%" height="100%">'
            ,anim: 'up'
            ,style: 'position:fixed; left:0; top:30%; width:100%; height:10%; border: none; -webkit-animation-duration: .5s; animation-duration: .5s;'
        });
    });
}

// 获取聊天记录
function getChatLog(uid, page, flag) {

    $.getJSON('/service/service/getChatLog', {uid: uid, page: page, u: seller}, function(res){
        if(0 == res.code && res.data.length > 0){

            if(res.msg == res.total){
                var _html = '<div class="layui-flow-more">没有更多了</div>';
            }else{
                var _html = '<div class="layui-flow-more"><a href="javascript:;" data-page="' + parseInt(res.msg + 1)
                    + '" onclick="getMore(this)"><cite>更多记录</cite></a></div>';
            }

            var len = res.data.length;

            for(var i = 0; i < len; i++){
                var item = res.data[i];

                if(0 == i) {
                    _html += '<li class="layim-chat-system"><span>' + item.create_time + '</span></li>';
                }

                if('mine' == item.type){
                    var ifRead = '<p class="read-flag no-read" data-msg-id="' + item.log_id + '">未读</p>';
                    if (2 == item.read_flag) {
                        ifRead = '<p class="read-flag already-read" data-msg-id="' + item.log_id + '">已读</p>';
                    }

                    _html += '<li class="layim-chat-li layim-chat-mine">';
                    _html += '<div class="layim-chat-user">';
                    _html += '<img src="' + item.from_avatar + '"><cite>我</cite></div>';
                    _html += '<div class="layim-chat-text">' + ai_service.replaceContent(item.content) + ' </div>' + ifRead + '</li>';

                }else {

                    var readFlag = '<div class="layim-chat-text check-read" data-msg-id="' + item.log_id + '">' + ai_service.replaceContent(item.content) + '</div>';
                    if (2 == item.read_flag) {
                        readFlag = '<div class="layim-chat-text check-read" data-msg-id="' + item.log_id + '">' + ai_service.replaceContent(item.content) + '</div>';
                    }

                    _html += '<li class="layim-chat-li">';
                    _html += '<div class="layim-chat-user">';
                    _html += '<img src="' + item.from_avatar + '"><cite>' + item.from_name + '</cite></div>';
                    _html += readFlag + '</li>';
                }
            }

            if(typeof flag == 'undefined'){
                $('#customer-' + uid).html(_html);
                wordBottom();
            }else{
                $('#customer-' + uid).prepend(_html);
            }

            showBigPic();

            // 处理未读
            handleNoRead(uid);
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

// 图片上传
layui.use(['upload', 'layer'], function () {
    var upload = layui.upload;
    var layer = layui.layer;

    var index;
    upload.render({
        elem: '#up-image'
        , accept: 'images'
        , exts: 'jpg|jpeg|png|gif'
        , url: '/service/upload/uploadImg/u/' + seller
        , before: function () {
            index = layer.load(0, {shade: false});
        }
        , done: function (res) {
            layer.close(index);
            sendMessage('img[' + res.data.src + ']');
        }
        , error: function () {
            // 请求异常回调
        }
    });
});

// 处理未读
function handleNoRead(customerId) {

    var noReadIds = [];
    // 检测全局未读
    $("#customer-" + customerId).find(".check-read").each(function () {
        if ($(this).attr('data-msg-id') != "undefined") {
            noReadIds.push($(this).attr('data-msg-id'));
        }
    });

    // 有未读的数据
    if (noReadIds.length > 0) {

        socket.emit("readMessage", JSON.stringify({
            uid: customerId,
            mid: noReadIds.join(',')
        }), function (data) {

            var data = JSON.parse(data); // 发送成功或者失败回调
            if (0 == data.code) {
                $("#customer-" + customerId).find(".check-read").removeClass('check-read').addClass('complete-read');
            }
        });
    }
}

// 获取访客的列表
function initWork() {

    $('#chat-list').html('');
    $.getJSON('/service/service/getNowServiceList/u/' + seller, function (res) {
        if(0 == res.code && res.data.length > 0) {
            $.each(res.data, function (k, v) {
                addUser(v);
            });
        }
    });
}

// 初始化访客队列
function initQueue() {

    $('#prepare-chat-list').html('');
    $.getJSON('/service/service/getCustomerQueue/u/' + seller, function (res) {
        if(0 == res.code && res.data.length > 0) {
            var _html = '';
            $.each(res.data, function (k, customer) {
                _html += showQueueList(customer);
            });

            $('#prepare-chat-list').html(_html);
        }
    });
}

// 展示队列信息
function showQueueList(customer) {
    // 聊天用户
    var _html = '<li class="item" onclick="takeCare(this)"><div class="info" id="h-' + customer.customer_id + '" data-id="' + customer.customer_id + '" data-name="' +
        customer.customer_name + '" data-avatar="' + customer.customer_avatar + '" data-ip="' + customer.customer_ip + '">';
    _html += '<div><img src="' + customer.customer_avatar + '"></div>';
    _html += '<span>' + customer.customer_name + '</span>';
    _html += '<p>' + customer.customer_ip + ' ' + customer.province + customer.city + '</p>';
    _html += '</div></li>';

    return _html;
}

// 接待访客
function takeCare(obj) {

    careCustomer.customer_id = $(obj).find('.info').attr('data-id');
    careCustomer.customer_name = $(obj).find('.info').attr('data-name');
    careCustomer.customer_avatar = $(obj).find('.info').attr('data-avatar');
    careCustomer.customer_ip = $(obj).find('.info').attr('data-ip');

    $("#conform").show();
}

// 选择确定接待
function ckYes() {

    var takeData = {
        data: careCustomer
    };
    takeData.data.kefu_code = kefuUser.uid;
    takeData.data.kefu_name = kefuUser.name;
    takeData.data.kefu_avatar = kefuUser.avatar;

    socket.emit("linkByKF", JSON.stringify(takeData), function (res) {
        var data = JSON.parse(res);
        if (0 == data.code) {
            layer.msg("接待成功");
            $('#h-' + careCustomer.customer_id).parent().remove();
        } else {
            layer.alert(data.msg);
            $('#h-' + careCustomer.customer_id).parent().remove();
        }
        $("#conform").hide();
    });
}

// 选择取消接待
function ckNo() {
    $("#conform").hide();
}

// 初始化历史对话列表
function initHistoryChat() {

    $('#history-chat-list').html('');
    $("#boxes").html();
    $.getJSON('/service/service/getHistoryChatList/u/' + seller, function (res) {
        if(0 == res.code && res.data.length > 0) {
            $.each(res.data, function (k, v) {
                addHistoryUser(v);
            });
        }
    });
}

// 展示历史访客
function addHistoryUser(customer) {

    // 聊天用户
    var _html = '<li class="item layim-list-gray" onclick="showChatBox(this)"><div class="info" id="c-' + customer.customer_id + '" data-id="'
        + customer.customer_id +'" data-name="' + customer.customer_name + '" data-log="' + customer.log_id +
        '" data-ip="' + customer.customer_ip + '" data-protocol="' + customer.protocol + '">';
    _html += '<div><img src="' + customer.customer_avatar + '"></div>';
    _html += '<span>' + customer.customer_name + '</span>';
    _html += '<p>' + customer.customer_ip + ' ' + customer.province + customer.city + '</p>';
    _html += '<span class="layim-msg-status">new</span></div><span class="delbtn" onclick="ckCloseUser(\'' + customer.customer_id + '\')">删除</span></li>';

    $('#history-chat-list').append(_html);
    slide();

    // 添加聊天区域
    var chat_box = '<ul id="customer-' + customer.customer_id + '" style="display: none"></ul>';
    $("#boxes").append(chat_box);
}

// 滑动
function slide() {
    // 将DomList转化为数组,以便使用forEach方法遍历dom
    var itembox = Array.prototype.slice.call(document.querySelectorAll("#chat-list li"),0);

    // 使用forEach方法遍历dom
    itembox.forEach(function(item, index ,arr){

        //小左边滑动
        var startX, endX, movebox = item;
        //触摸开始
        function boxTouchStart(e) {
            var touch = e.touches[0]; //获取触摸对象
            startX = touch.pageX; //获取触摸坐标
        }
        // 触摸移动
        function boxTouchMove(e) {
            var touch = e.touches[0];
            endX = touch.pageX; //手指水平方向移动的距离
        }
        // 触摸结束
        function boxTouchEnd(e) {
            console.log(startX+"start")
            console.log(endX+"end")
            // 手指向左滑动
            if (startX - endX >= 60) {
                this.classList.add("active");
                // 手指向右滑动
            } else {
                this.classList.remove("active");
            }
        }

        // 滑动对象事件绑定
        movebox.addEventListener("touchstart", boxTouchStart, false);
        movebox.addEventListener("touchmove", boxTouchMove, false);
        movebox.addEventListener("touchend", boxTouchEnd, false);
    });
}