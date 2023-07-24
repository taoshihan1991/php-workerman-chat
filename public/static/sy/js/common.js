;
(function (w, u) {
    "use strict";
    var _window = "";
    var $$ = {
        //ajax公共方法
        ajax: function (url, data) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    headers: {
                        Accept: "application/*; charset=utf-8",
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    url: url,
                    type: data == null ? 'GET' : 'POST',
                    dataType: "json",
                    data: data == null ? '' : data,
                    // async: true,
                    success: function (res) {
                        if (res.res != 0) {
                            layer.close(layer.load(2));
                            if (res.res == '1002') {
                                noAuthority(res.msg);
                            } else if (res.res == 1 && res.msg && url != '/CommonApi/MenuSave' && url != '/security/addAppForRiskBefor' && url!='/webApp/SaveAdminCustomerInfoFieldConf') {
                                layer.msg(res.msg);
                            }
                            reject(res);
                            return false;
                        }
                        resolve(res)
                    },
                    error: function (res, textStatus, errorThrown) {
                        layer.msg('网络出错');
                        layer.close(layer.load(2));
                        reject(res)
                    }
                });
            })
        },
        //获取地址后带有的参数
        getUrlParam: function (value) {
            var url = decodeURI(window.location.search); //获取url中"?"符后的字串
            var theRequest = new Object();
            if (url.indexOf("?") != -1) {
                var str = url.substr(1);
                var strs = str.split("&");
                for (var i = 0; i < strs.length; i++) {
                    theRequest[strs[i].split("=")[0]] = (strs[i].split("=")[1]);
                }
            }
            return theRequest[value];
        },
        //根据id得到对象
        obj$: function () {
            return document.getElementByIdx(id);
        },
        //根据id得到对象的值
        val$: function (id) {
            var obj = document.getElementByIdx(id);
            if (obj !== null) {
                return obj.value;
            }
            return null;
        },
        //删除左边和右边空格
        trim: function (str) {
            return str.replace(/(^\s*)|(\s*$)/g, '');
        },
        //判断是否电子邮件
        isEmail: function (str) {
            if (/^[\w-]+(\.[\w-]+)*@[\w-]+(\.[\w-]+)+$/.test(str)) {
                return true
            }
            return false;
        },
        //判断是否是手机号
        isMobile: function (str) {
            if (/^[1][1,2,3,4,5,6,7,8,9][0-9]{9}$/.test(str)) {
                return true;
            }
            return false;

        },
        is_url(url){
            var reg = /(http|https):\/\/([\w.]+\/?)\S*/
            if (reg.test(url)) {
                return true;
            }
            return false;
        },
        //判断是否是正确ip
        isIP: function (str) {
            var reg = /^(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])$/;
            if (reg.test(str)) {
                return true;
            }
            return false;
        },
        //判断是否是正确身份证号
        isCardNo: function (card) {
            // 身份证号码为15位或者18位，15位时全为数字，18位前17位为数字，最后一位是校验位，可能为数字或字符X
            var reg = /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
            if (reg.test(card)) {
                return true;
            }
            return false;
        },
        getObjectURL: function (file) {
            var url = null;
            if (window.createObjectURL != undefined) { // basic
                url = window.createObjectURL(file);
            } else if (window.URL != undefined) { // mozilla(firefox)
                url = window.URL.createObjectURL(file);
            } else if (window.webkitURL != undefined) { // webkit or chrome
                url = window.webkitURL.createObjectURL(file);
            }
            return url;
        },
        stroage: {
            set(key, value) {
                localStorage.setItem(key, JSON.stringify(value))
            },
            get(key) {
                return JSON.parse(localStorage.getItem(key))
            },
            remove(key) {
                localStorage.removeItem(key)
            }
        },
        replaceString(str) {
            str = str.replace(/,/g, '/')
            let $a = str
            let $b = str
            let $aone = $a.substr(0, 1)
            let $twice = $b.substr($b.length - 1, $b.length)
            if ($aone == '/')
                str = str.substr(1, str.length - 1)
            else if ($twice == '/')
                str = str.substr(0, str.length - 1)
            return str
        },
        // 时间戳转换
        formatDate(date, formatStr) {
            date = date*1000       
            date = new Date(date);  
            var arrWeek = ['日', '一', '二', '三', '四', '五', '六'],
                str = formatStr.replace(/yyyy|YYYY/, date.getFullYear()).replace(/yy|YY/, this.$addZero(date.getFullYear() % 100, 2)).replace(/mm|MM/, this.$addZero(date.getMonth() + 1, 2)).replace(/m|M/g, date.getMonth() + 1).replace(/dd|DD/, this.$addZero(date.getDate(), 2)).replace(/d|D/g, date.getDate()).replace(/hh|HH/, this.$addZero(date.getHours(), 2)).replace(/h|H/g, date.getHours()).replace(/ii|II/, this.$addZero(date.getMinutes(), 2)).replace(/i|I/g, date.getMinutes()).replace(/ss|SS/, this.$addZero(date.getSeconds(), 2)).replace(/s|S/g, date.getSeconds()).replace(/w/g, date.getDay()).replace(/W/g, arrWeek[date.getDay()]);
            return str;
        },
        $addZero(v, size) {
            for (var i = 0, len = size - (v + "").length; i < len; i++) {
                v = "0" + v;
            };
            return v + "";

        },
        //前台打包csv文件
        tableToExcel(str,jsonData,fieds,name){
            //jsonData要导出的json数据
            //str列标题，逗号隔开，每一个逗号就是隔开一个单元格
            //增加\t为了不让表格显示科学计数法或者其他格式
            for(let i = 0 ; i < jsonData.length ; i++ ){
                for(let item of fieds){
                    str+=`"${jsonData[i][item] + '\t'}",`;     
                }
                str+='\n';
            }
            //encodeURIComponent解决中文乱码
            let uri = 'data:text/csv;charset=utf-8,\ufeff' + encodeURIComponent(str);
            //通过创建a标签实现
            let link = document.createElement("a");
            link.href = uri;
            //对下载的文件命名
            link.download =  name;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        //初始化form
        initForm(form,options){
			//默认参数
			var defaults = {
				jsonValue:"",
				isDebug:false	//是否需要调试，这个用于开发阶段，发布阶段请将设置为false，默认为false,true将会把name value打印出来
			}
			//设置参数
			var setting = $.extend({}, defaults, options);
			let jsonValue = setting.jsonValue;
			//如果传入的json字符串，将转为json对象
			if($.type(setting.jsonValue) === "string"){
				jsonValue = $.parseJSON(jsonValue);
			}
			//如果传入的json对象为空，则不做任何操作
			if(!$.isEmptyObject(jsonValue)){
				var debugInfo = "";
				$.each(jsonValue,function(key,value){
					//是否开启调试，开启将会把name value打印出来
					if(setting.isDebug){
						alert("name:"+key+"; value:"+value);
						debugInfo += "name:"+key+"; value:"+value+" || ";
					}
					var formField = form.find("[name='"+key+"']");
					if($.type(formField[0]) === "undefined"){
						if(setting.isDebug){
							alert("can not find name:["+key+"] in form!!!");	//没找到指定name的表单
						}
					} else {
						var fieldTagName = formField[0].tagName.toLowerCase();
						if(fieldTagName == "input"){
							if(formField.attr("type") == "radio"){
								$("input:radio[name='"+key+"'][value='"+value+"']").attr("checked","checked");
							} else {
								formField.val(value);
							}
						} else if(fieldTagName == "select"){
							//do something special
							formField.val(value);
						} else if(fieldTagName == "textarea"){
							//do something special
							formField.val(value);
						} else {
							formField.val(value);
						}
					}
				})
				if(setting.isDebug){
					alert(debugInfo);
				}
			}
			return form;	//返回对象，提供链式操作
        },
        //版本限制
        versionLimit(version,success=()=>{console.log('通过')},fail=()=>{console.log('失败')}){
            switch(version){
                case '免费版' :success();break;
                case '标准版' : {
                    if(ACCOUNTTYPE==0){            
                        layer.confirm('该功能为标准版版功能，请购买标准版版本后使用！', {
                            title:'提示',
                            btn: ['取消', '去购买'] //按钮
                        }, (index)=>{
                            layer.close(index);
                            
                        },(index)=>{
                            layer.close(index);
                            window.open("/vip/price");
                        })
                        fail();
                    } else{
                        success()
                    }
                    break;    
                }
                case '专业版':{
                    if(ACCOUNTTYPE==0 || ACCOUNTTYPE==3){            
                        layer.confirm('该功能为专业版版功能，请购买专业版版本后使用！', {
                            title:'提示',
                            btn: ['取消', '去购买'] //按钮
                        }, (index)=>{
                            layer.close(index);
                            
                        },(index)=>{
                            layer.close(index);
                            window.open("/vip/price");
                        })
                        fail();
                    } else{
                        success()
                    }
                    break; 
                }
                case '企业版':{
                    if(ACCOUNTTYPE==0 || ACCOUNTTYPE==3 || ACCOUNTTYPE==1){            
                        layer.confirm('该功能为企业版功能，请购买企业版版本后使用！', {
                            title:'提示',
                            btn: ['取消', '去购买'] //按钮
                        }, (index)=>{
                            layer.close(index);         
                        },(index)=>{
                            layer.close(index);
                            window.open("/vip/price")
                        })
                        fail();
                    } else{
                        success()
                    } 
                    break; 
                }
            }
        }

    }
    _window = (function () {
        return this || (0, eval)('this');
    }());
    !('$$' in _window) && (_window.$$ = $$);
})(window);