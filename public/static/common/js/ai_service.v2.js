/**
 * Created by PhpStorm.
 * User: 1609123282
 * Email: 123456789@qq.com
 * Date: 2020/04/19
 * Time: 22:19 PM
 */
;!function (win, doc) {
    "use strict";

    var ai_service = function () {
            this.v = '2.1';
        }
        // 转义聊天内容中的特殊字符
        , replaceContent = function(content) {
            // 支持的html标签
            var html = function (end) {
                return new RegExp('\\n*\\[' + (end || '') + '(pre|div|span|img|br|a|em|font|strong|p|table|thead|th|tbody|tr|td|ul|li|ol|li|dl|dt|dd|h2|h3|h4|h5)([\\s\\S]*?)\\]\\n*', 'g');
            };
            content = (content || '').replace(/&(?!#?[a-zA-Z0-9]+;)/g, '&amp;')
                .replace(/@(\S+)(\s+?|$)/g, '@<a href="javascript:;">$1</a>$2') // 转义@

                .replace(/face\[([^\s\[\]]+?)\]/g, function (face) {  // 转义表情
                    var alt = face.replace(/^face/g, '');
                    return '<img alt="' + alt + '" title="' + alt + '" src="' + faces[alt] + '">';
                })
                .replace(/img\[([^\s]+?)\]/g, function (img) {  // 转义图片
                    return '<img class="layui-ai_service-photos" src="' + img.replace(/(^img\[)|(\]$)/g, '') + '" style="max-width: 100%;width: 100%;height: 150px">';
                })
                .replace(/file\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { // 转义文件
                    var href = (str.match(/file\(([\s\S]+?)\)\[/) || [])[1];
                    var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
                    if (!href) return str;
                    return '<a class="layui-ai_service-file" href="' + href + '" download target="_blank"><i class="layui-icon">&#xe61e;</i><cite>' + (text || href) + '</cite></a>';
                })
                .replace(/audio\[([^\s]+?)\]/g, function(audio){  //转义音频
                    return '<audio src="' + audio.replace(/(^audio\[)|(\]$)/g, '') + '" controls="controls" style="width: 200px;height: 20px"></audio>';
                })
                .replace(/a\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { // 转义链接
                    var href = (str.match(/a\(([\s\S]+?)\)\[/) || [])[1];
                    var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
                    if (!href) return str;
                    return '<a href="' + href + '" target="_blank" style="color:#1E9FFF">' + (text || href) + '</a>';
                }).replace(html(), '\<$1 $2\>').replace(html('/'), '\</$1\>') // 转移HTML代码
                .replace(/\n/g, '<br>');// 转义换行

            return content;
        }
        // 转义富媒体
        , replaceEdit = function(content) {
            var html = function (end) {
                return new RegExp('\\n*\\<' + (end || '') + '(pre|img|div|span|br|a|em|font|strong|p|table|thead|th|tbody|tr|td|ul|li|ol|li|dl|dt|dd|h2|h3|h4|h5)([\\s\\S]*?)\\>\\n*', 'g');
            };

            return content.replace(/style\s*=\s*('[^']*'|"[^"]*")/g, function(style) {
                return style.replace(/\s+/g,"").replace(/\"/g, "");
            })
                .replace(/src\s*=\s*('[^']*'|"[^"]*")/g, function(src) {
                    return src.replace(/\s+/g,"").replace(/\"/g, "")+ ' class=layui-ai_service-photos style=max-width:100%;width:100%;height:150px';
                })
                .replace(/class\s*=\s*('[^']*'|"[^"]*")/g, function(cls) {
                    return cls.replace(/\s+/g,"").replace(/\"/g, "");
                })
                .replace(/href\s*=\s*('[^']*'|"[^"]*")/g, function(href) {
                    return href.replace(/\s+/g,"").replace(/\"/g, "");
                })
                .replace(/target\s*=\s*('[^']*'|"[^"]*")/g, function(tgt) {
                    return tgt.replace(/\s+/g,"").replace(/\"/g, "");
                })
                .replace(/title\s*=\s*('[^']*'|"[^"]*")/g, function(title) {
                    return title.replace(/\s+/g,"").replace(/\"/g, "");
                })
                .replace(html(), '\[$1$2\]').replace(html('/'), '\[/$1\]');
        }
        // 表情对应数组
        , getFacesIcon = function () {
            return ["[微笑]", "[嘻嘻]", "[哈哈]", "[可爱]", "[可怜]", "[挖鼻]", "[吃惊]", "[害羞]", "[挤眼]", "[闭嘴]", "[鄙视]",
                "[爱你]", "[泪]", "[偷笑]", "[亲亲]", "[生病]", "[太开心]", "[白眼]", "[右哼哼]", "[左哼哼]", "[嘘]", "[衰]",
                "[委屈]", "[吐]", "[哈欠]", "[抱抱]", "[怒]", "[疑问]", "[馋嘴]", "[拜拜]", "[思考]", "[汗]", "[困]", "[睡]",
                "[钱]", "[失望]", "[酷]", "[色]", "[哼]", "[鼓掌]", "[晕]", "[悲伤]", "[抓狂]", "[黑线]", "[阴险]", "[怒骂]",
                "[互粉]", "[心]", "[伤心]", "[猪头]", "[熊猫]", "[兔子]", "[ok]", "[耶]", "[good]", "[NO]", "[赞]", "[来]",
                "[弱]", "[草泥马]", "[神马]", "[囧]", "[浮云]", "[给力]", "[围观]", "[威武]", "[奥特曼]", "[礼物]", "[钟]",
                "[话筒]", "[蜡烛]", "[蛋糕]"]
        }
        // 表情替换
        , faces = function () {
            var alt = getFacesIcon(), arr = {};
            $.each(alt, function (index, item) {
                arr[item] = '/static/common/images/face/' + index + '.gif';
            });
            return arr;
        }()
        // 展示表情
        , showFaces = function () {
            var alt = getFacesIcon();
            var _html = '<div class="layui-ai_service-face"><ul class="layui-clear ai_service-face-list">';
            $.each(alt, function (index, item) {
                _html += '<li title="' + item + '" onclick="ai_service.checkFace(this)"><img src="/static/common/images/face/' + index + '.gif" /></li>';
            });
            _html += '</ul></div>';

            return _html;
        };

    // 格式化时间
    Date.prototype.format = function(fmt) {
        var o = {
            "M+": this.getMonth()+1,                 // 月份
            "d+": this.getDate(),                    // 日
            "h+": this.getHours(),                   // 小时
            "m+": this.getMinutes(),                 // 分
            "s+": this.getSeconds(),                 // 秒
            "q+": Math.floor((this.getMonth()+3)/3), // 季度
            "S": this.getMilliseconds()             // 毫秒
        };

        if(/(y+)/.test(fmt)) {
            fmt = fmt.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
        }

        for(var k in o) {
            if(new RegExp("("+ k +")").test(fmt)){
                fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));
            }
        }

        return fmt;
    };

    ai_service.prototype.init = function (conf) {

    };

    // 发送消息
    ai_service.prototype.send = function (content, avatar, flag) {

        return [
            '<div class="clearfloat ">'
            ,'<div class="author-name">'
            ,'<small class="chat-date">' + this.getCurrDate() + '</small>'
            ,'</div>'
            ,'<div class="right">'
            ,'<i class="layui-icon read-flag no-read" data-msg-id="' + flag + '">未读</i>'
            ,'<div class="chat-message">' + replaceContent(content) + '</div>'
            ,'<div class="chat-avatars">'
            ,'<img src="' + avatar + '">'
            ,'</div>'
            ,'</div>'
            ,'</div>'
            ,'<div style="clear:both"></div>'
        ].join('');
    };

    // 显示消息
    ai_service.prototype.showMessage = function (data) {
        var readFlag = '<div class="chat-message check-read" data-msg-id="' + data.chat_log_id + '">' + replaceContent(data.content) + '</div>';
        if (2 == data.read_flag) {
            readFlag = '<div class="chat-message complete-read" data-msg-id="' + data.chat_log_id + '">' + replaceContent(data.content) + '</div>';
        }
        return [
            '<div class="clearfloat ">'
            ,'<div class="author-name">'
            ,'<small class="chat-date">' + data.time + '</small>'
            ,'</div>'
            ,'<div class="left">'
            ,'<div class="chat-avatars">'
            ,'<img src="' + data.avatar + '">'
            ,'</div>'
            ,readFlag
            ,'</div>'
            ,'</div>'
            ,'<div style="clear:both"></div>'
        ].join('');
    };

    // 发送已读dom
    ai_service.prototype.completeReadSend = function (content, avatar, flag) {

        return [
            '<div class="clearfloat ">'
            ,'<div class="author-name">'
            ,'<small class="chat-date">' + this.getCurrDate() + '</small>'
            ,'</div>'
            ,'<div class="right">'
            ,'<i class="layui-icon read-flag already-read" data-msg-id="' + flag + '">已读</i>'
            ,'<div class="chat-message">' + replaceContent(content) + '</div>'
            ,'<div class="chat-avatars">'
            ,'<img src="' + avatar + '">'
            ,'</div>'
            ,'</div>'
            ,'</div>'
            ,'<div style="clear:both"></div>'
        ].join('');
    };

    // 显示聊天信息中，我发送的消息
    ai_service.prototype.showMyChatLog = function (data) {

        var readFlag = '<i class="layui-icon read-flag no-read" data-msg-id="' + data.log_id + '">未读</i>';
        if (2 == data.read_flag) {
            readFlag = '<i class="layui-icon read-flag already-read" data-msg-id="' + data.log_id + '">已读</i>'
        }

        if (data.valid == 0) {
            var backHtml = '<i class="layui-icon read-flag no-read" style="color: red;margin-right: 10px">此消息被撤回</i>';
            readFlag = backHtml + readFlag;
        }

        return [
            '<div class="clearfloat ">'
            ,'<div class="author-name">'
            ,'<small class="chat-date">' + data.create_time + '</small>'
            ,'</div>'
            ,'<div class="right">'
            ,readFlag
            ,'<div class="chat-message">' + replaceContent(data.content) + '</div>'
            ,'<div class="chat-avatars">'
            ,'<img src="' + data.from_avatar + '">'
            ,'</div>'
            ,'</div>'
            ,'</div>'
            ,'<div style="clear:both"></div>'
        ].join('');
    };

    // 显示系统消息
    ai_service.prototype.showSystem = function (msg) {
        return [
            '<div class="clearfloat ">'
            ,'<div class="author-name">'
            ,'<small class="chat-system">' + msg + '</small>'
            ,'</div>'
            ,'</div>'
            ,'<div style="clear:both"></div>'
        ].join('');
    };

    // 获取当前时间
    ai_service.prototype.getCurrDate = function () {
        return new Date().format("yyyy-MM-dd hh:mm:ss");
    };

    // 展示表情
    ai_service.prototype.showFaces = function () {
        return showFaces();
    };

    // 选择表情
    ai_service.prototype.checkFace = function (obj) {
        var word = $("#textarea").val() + ' face' + $(obj).attr('title') + ' ';
        $("#textarea").val(word).focus();

        $(".layui-ai_service-face").hide();
        $(".send-input").addClass('active');

        layui.use('layer', function () {
            var layer = layui.layer;

            layer.close(faceIndex);
        });
    };

    // 展示大图
    ai_service.prototype.showBigPic = function () {
        $(".layui-ai_service-photos").on('click', function () {
            var src = this.src;
            layer.photos({
                photos: {
                    data: [{
                        "alt": "大图模式",
                        "src": src
                    }]
                }
                , shade: 0.5
                , closeBtn: 2
                , anim: 0
                , resize: false
                , success: function (layero, index) {

                }
            });
        });
    };

    // 消息声音提醒
    ai_service.prototype.voice = function () {
        $("#ai_service-index-audio").get(0).play();
    };

    // 内容替换
    ai_service.prototype.replaceContent = function (text) {
        return replaceContent(text);
    };

    // 格式化标签
    ai_service.prototype.replaceEdit = function(text) {
        return replaceEdit(text);
    };

    // 自动识别url连接
    ai_service.prototype.autoReplaceUrl = function(text) {

        /*var reg = /(http[s]?:\/\/(www\.)?|ftp:\/\/(www\.)?|(www\.)?){1}([0-9A-Za-z-\.@:%_\+~#=]+)+((\.[a-zA-Z]{2,3})+)(\/(.)*)?(\?(.)*)?/g;
        text = text.replace(reg, function (href) {
            var regx = /^https?:\/\//i;
            if (!regx.test(href)) {
                return "a(http://" + href + ")[" + href + "]";
            } else {
                return "a(" + href + ")[" + href + "]";
            }
        });*/
        return text;
    };

    console.log(
        [
            "%c                                                                            ",
            "                                                                            ",
            "                                                                            ",
            "                               %c FBI WARNING %c                                ",
            "                                                                            ",
            "                                                                            ",
            "%c        请不要试图阅读本demo的源码，不然你会去想这么丑的代码是谁写的        ",
            "              本文件包括附属的js文件的代码是经过长时间积累出来的            ",
            "              代码虽然已经分层/分开写了，也许者作者也已经不认识了           ",
            "                                                                            ",
            "              如果想要快速入门，请阅读github项目内首页README文档            ",
            "                     参考文档内的快速使用部分，简单快捷高效                 ",
            "                                                                            ",
            "                请不要试图阅读本demo的源码，正常情况下意义不大              ",
            "                                                                            ",
            "                                                                            ",
            "",
        ].join("\n"),
        "background: #000; font-size: 18px; font-family: monospace",
        "background: #f33; font-size: 18px; font-family: monospace; color: #eee; text-shadow:0 0 1px #fff",
        "background: #000; font-size: 18px; font-family: monospace",
        "background: #000; font-size: 18px; font-family: monospace; color: #ddd; text-shadow:0 0 2px #fff"
    );
    win.ai_service = new ai_service();
}(window, document);