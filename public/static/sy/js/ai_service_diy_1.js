
            /**
             *
             * 自定义版 客服咨询js
             * @return {[type]} [description]
             */
                var head = document.getElementsByTagName('head')[0];
                var link = document.createElement('link');
                    link.type='text/css';
                    link.rel = 'stylesheet';
                    link.href ='https://kefu.aosene.com/assets/style1/css/chatStyle.css';
                    head.appendChild(link);

                var blzx ={
                visiter_id:(typeof ai_service=='undefined' || typeof ai_service.visiter_id == 'undefined')?'':ai_service.visiter_id,
                     visiter_name:(typeof ai_service=='undefined' || typeof ai_service.visiter_name == 'undefined')?'':ai_service.visiter_name,
                     avatar:(typeof ai_service=='undefined' || typeof ai_service.avatar == 'undefined')?'':ai_service.avatar,
                     product:(typeof ai_service=='undefined' || typeof ai_service.product == 'undefined')?'{}':ai_service.product,
                     open:function(){
                        var d =document.getElementById('blzxMinChatWindowDiv');
                        if(!d){
                            var div =document.createElement('div');
                            div.id ="blzxMinChatWindowDiv";
                            document.body.appendChild(div);
                            var w =document.getElementById('blzxMinChatWindowDiv');
                            w.classList.add('testt');
                            w.innerHTML='<div id="minblzxmsgtitlecontainer"><img id="minblzxWinlogo" src="https://kefu.aosene.com/assets/style1/img/wechatLogo.png"><div id="minblzxmsgtitlecontainerlabel" class="" onclick="blzx.connenct(0)">在线咨询</div><img id="minblzxmsgtitlecontainerclosebutton" class="" onclick="blzx.closeMinChatWindow(\'blzxMinChatWindowDiv\');" src="https://kefu.aosene.com/assets/style1/img/closewin.png"><img id="minblzxNewBigWin"  class="" onclick="blzx.connenct(0)" src="https://kefu.aosene.com/assets/style1/img/up_arrow.png"></div>';
                            document.getElementById('minblzxmsgtitlecontainer').style.backgroundColor='#1f6aff';
                        }
                     },
                     connenct:function(groupid){
                     document.getElementById('blzxMinChatWindowDiv').style.display="none";
                      var id =groupid;
                      var web =encodeURI('https://kefu.aosene.com/layer?theme=1f6aff&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=1&groupid='+groupid+'&product='+blzx.product);
                      
                      var moblieweb = encodeURI('https://kefu.aosene.com/mobile/index/home?theme=1f6aff&visiter_id='+blzx.visiter_id+'&visiter_name='+blzx.visiter_name+'&avatar='+blzx.avatar+'&business_id=1&groupid='+groupid+'&product='+blzx.product);
                       var s =document.getElementById('wolive-talk');
                        
                       if(!s){

                            var div = document.createElement('div');
                            div.id ="wolive-talk";
                            div.name=id;
                            if(blzx.isMobile()){
                               div.style.width='100%';
                               
                           }
                            document.body.appendChild(div);
                            div.innerHTML='<i class="blzx-close" onclick="blzx.closeMinChatWindow(\'wolive-talk\')"></i><iframe id="wolive-iframe" allow="camera;microphone" src="'+web+'" onload="pageOk()"></iframe><div id="loading"><img src="https://kefu.aosene.com/assets/images/platform/loading-2.svg"></div>'
                          
                        }else{
                           
                            var title =s.name;
                            if(title == groupid){
                                s.style.display ='block';
                            }else{
                                s.parentNode.removeChild(s);
                                blzx.connenct(groupid); 
                            }
                        }
                      
                     },closeMinChatWindow:function(id){
                        document.getElementById(id).style.display="none";
                        if(id==='wolive-talk'){
                            document.getElementById('blzxMinChatWindowDiv').style.display="block";
                        }
                    },isMobile:function(){
                        if ((navigator.userAgent.match(/(phone|pad|pod|iPhone|iPod|ios|iPad|Android|Mobile|BlackBerry|IEMobile|MQQBrowser|JUC|Fennec|wOSBrowser|BrowserNG|WebOS|Symbian|Windows Phone)/i))) {
                            return true;
                        }else{
                            return false;
                        }
                    }
                };
                
                function pageOk(){
                    document.getElementById('loading').style.display='none';
                }

                window.onload =blzx.open();
                setTimeout(function () {
                    blzx.connenct(0);
                },0);

        