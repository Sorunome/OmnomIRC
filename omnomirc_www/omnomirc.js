/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
    OmnomIRC JavaScript Client rewrite COPYRIGHT 2013
                                        Nathaniel 'Eeems' van Diepen

    This file is part of OmnomIRC.

    OmnomIRC is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OmnomIRC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
*/
(function(window,undefined){
     var OmnomIRC = function(){
            var ret = {
                    options: "----------------------------------------|", //40 for future expansion!(and 40 bytes isn't much.) Pipe is a terminator.
                    cookieLoad: proto('cookieLoad'),
                    getOption: proto('getOption'),
                    setOption: proto('setOption'),
                    clearCookies: proto('clearCookies'),
                    getHTMLToggle: proto('getHTMLToggle'),
                    setAllowNotification: proto('setAllowNotification'),
                    startIndicator: proto('startIndicator'),
                    stopIndicator: proto('stopIndicator')
                };
            if(message.addEventListener ){
                message.addEventListener("keydown",proto('keyHandler').call(ret),false);
            }else if(message.attachEvent ){
                message.attachEvent("onkeydown",proto('keyHandler').call(ret));
            }
            window.onLoad = this.cookieLoad();
            return ret;
        },
        proto = function(fn){
            return function(){
                try{
                    return _proto[fn].apply(this,arguments);
                }catch(e){
                    return null;
                }
            };
        },
        _proto = {
            cookieLoad: function() {
                if (document.cookie.indexOf("OmnomIRC") >= 0) {
                    this.options = document.cookie.replace(/^.*OmnomIRC=(.+?)|.*/, "\$1");
                }else{
                    document.cookie = "OmnomIRC=" + this.options + ";expires=Sat, 20 Nov 2286 17:46:39 GMT;";
                }
            },
            getOption: function(Option,def) { //Returns what 'Option' is. Option must be a number 1-40. def is what to return if it is not set(equal to -)
                if (Option < 1 || Option > 40){
                    return 0;
                }
                var result = this.options.charAt(Option - 1);
                if (result == '-'){
                    return def;
                }
                return result;
            },
            setOption: function(Option, value,noRefresh) { //Sets 'Option' to 'value'. Value must be a single char. Option must be a number 1-40.
                if (Option < 1 || Option > 40){
                    return;
                }
                this.options = this.options.substring(0, Option - 1) + value + this.options.substring(Option);
                document.cookie = "OmnomIRC=" + this.options + ";expires=Sat, 20 Nov 2286 17:46:39 GMT;";
                if (!noRefresh){
                    document.location.reload();
                }
            },
            clearCookies: function(){
                document.cookie = "OmnomIRC=a;expires=Thu, 01-Jan-1970 00:00:01 GMT;";
                document.cookie = "OmnomChannels=a;expires=Thu, 01-Jan-1970 00:00:01 GMT;";
                document.location.reload();
            },
            permissionGranted: function(){
                if (window.webkitNotifications.checkPermission() === 0){
                    proto('showNotification').call(this,"Notifications Enabled!");
                    this.setOption(7,'T');
                    window.location.refresh(true);
                }
            },
            getHTMLToggle: function(State, StateOn, StateOff,StateOnFunc,StateOffFunc){
                var result = "";
                if (State){
                    result += "<b>";
                    result += StateOn;
                    result += "</b>";
                }else{
                    result += '<a href="#" onclick="'+StateOnFunc+'">';
                    result += StateOn;
                    result += '</a>';
                }
                result += "</td><td>";
                if(!State){
                    result += "<b>";
                    result += StateOff;
                    result += "</b>";
                }else{
                    result += '<a href="#" onclick="'+StateOffFunc+'">';
                    result += StateOff;
                    result += '</a>';
                }
                return result;
            },
            setAllowNotification: function(){
                if (window.webkitNotifications === undefined || window.webkitNotifications === null || !window.webkitNotifications){
                    alert("This feature only works in chrome.");
                    return;
                }
                window.webkitNotifications.requestPermission(proto('permissionGranted').call(this));
            },
            showNotification: function(message){
                if (window.webkitNotifications === undefined || window.webkitNotifications === null || !window.webkitNotifications){
                 return 0;
                }
                if (window.webkitNotifications.checkPermission() !== 0){
                    return 0;
                }
                var n;
                n = window.webkitNotifications.createNotification('http://www.omnimaga.org/favicon.ico', 'OmnomIRC Highlight', message);
                n.show();
            },
            keyHandler: function(e){
                var getCurrentWord = proto('getCurrentWord').call(this),
                    TABKEY = 9;
                if (getCurrentWord() === ""){
                    return true;
                }
                if(e.keyCode == TABKEY){
                    if(e.preventDefault) {
                        e.preventDefault();
                    }
                    tabWord = getCurrentWord();
                    getTabComplete();
                    tabCount++;
                    isInTab = true;
                    //setTimeout(1,1); //Who woulda thought that a bogus call makes it not parse it in FF4?
                    return false;
                }else{
                    tabWord = "";
                    tabCount = 0;
                    isInTab = false;
                }
            },
            getCurrentWord: function(){
                if (isInTab){
                    return tabWord;
                }
                startPos = message.selectionStart;
                endPos = message.selectionStart;
                var startChar = message.value.charAt(startPos);
                while (startChar != " " && --startPos > 0){
                startChar = message.value.charAt(startPos);
                }
                if (startChar == " "){
                    startPos++;
                }
                var endChar = message.value.charAt(endPos);
                while (endChar != " " && ++endPos <= message.value.length){
                    endChar = message.value.charAt(endPos);
                }
                endPosO = endPos;
                return message.value.substr(startPos,endPos - startPos).trim();
            },
            getTabComplete: function(){
                var getCurrentWord = proto('getCurrentWord').call(this),
                    name = searchUser(getCurrentWord(),tabCount);
                if (!isInTab){
                    startPos = message.selectionStart;
                    var startChar = message.value.charAt(startPos);
                    while (startChar != " " && --startPos > 0){
                        startChar = message.value.charAt(startPos);
                    }
                    if (startChar == " "){
                        startChar+=2;
                    }
                    endPos = message.selectionStart;
                    var endChar = message.value.charAt(endPos);
                    while (endChar != " " && ++endPos <= message.value.length){
                      endChar = message.value.charAt(endPos);
                    }
                    if (endChar == " "){
                        endChar-=2;
                    }
                }
                if (name == getCurrentWord()){
                    tabCount = 0;
                    name = searchUser(getCurrentWord(),tabCount);
                }
                message.value = message.value.substr(0,startPos) + name + message.value.substr(endPos + 1);
                endPos = endPosO + name.length;
            },
            startIndicator: function(){
                if(!indicatorTimer){
                    indicatorTimer = setInterval(proto('updateIndicator').call(this),50);
                    indicatorPixels = Array(true,true,true,true,true,false,false,false);
                }
            },
            stopIndicator: function() {
                clearInterval(indicatorTimer);
                document.getElementById('indicator').innerHTML = '';
                indicatorTimer = false;
            },
            updateIndicator: function() {
                var indicator = document.getElementById('indicator'),
                    div,
                    temp = indicatorPixels[7];
                    indicator.innerHTML = "";
                for (var i=0;i<8;i++){
                    div = document.createElement('div');
                    div.style.padding = 0;
                    div.style.margin = 0;
                    div.style.width = '3px';
                    div.style.height = '3px';
                    if (indicatorPixels[i]){
                        div.style.backgroundColor = 'black';
                    }
                    indicator.appendChild(div);
                }
                for(i=6;i>=0;i--){
                    indicatorPixels[(i+1)] = indicatorPixels[i];
                }
                indicatorPixels[0] = temp;
            },
            readOldMessagesCookies: function() {
                var oldMessages = [],
                    temp = getCookie("oldMessages-"+getChannelEn());
                if (temp!==null){
                    oldMessages = temp.split("\n");
                }
                messageCounter = oldMessages.length;
            }
        },
        message = document.getElementById("message"),
        isInTab = false,
        tabWord = "",
        tabCount = 0,
        startPos = 0,
        endPos = 0,
        endPosO = 0,
        indicatorTimer = false,
        oldMessages = [],
		messageCounter = 1,
		currentMessage;
    window.addEventListener('keydown',function(e){
        if(document.activeElement.id=="message"){
			var messageBoxElement = document.getElementById("message");
			if(messageCounter==oldMessages.length){
                currentMessage=messageBoxElement.value;
			}
			if(oldMessages.length!==0) {
				if (e.keyCode==38) { //up
					if(messageCounter!==0){
                        messageCounter--;
					}
					messageBoxElement.value = oldMessages[messageCounter];
				}else if(e.keyCode==40){ //down
					if (messageCounter!=oldMessages.length){
                        messageCounter++;
					}
					if (messageCounter==oldMessages.length){
						messageBoxElement.value = currentMessage;
					}else{
						messageBoxElement.value = oldMessages[messageCounter];
					}
				}
			}
		}
	}, false);
})(window);