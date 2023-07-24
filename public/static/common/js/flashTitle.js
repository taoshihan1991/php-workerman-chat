var flashStep = 0;
var flashInterval = null;
var flashTitle = function() {
    var reTitle = document.title.replace("【　　　　　　　　】", "").replace("【收到一条新消息！】", "");
    flashInterval = setTimeout(function() {
            flashStep++;
            flashTitle();
            (flashStep % 2 == 0) ? document.title = "【收到一条新消息！】" + reTitle : document.title = "【　　　　　　　　】" + reTitle
    },
    600)
};

var stopFlash = function() {
    clearTimeout(flashInterval);
    document.title = document.title.replace("【　　　　　　　　】", "").replace("【收到一条新消息！】", "")
};