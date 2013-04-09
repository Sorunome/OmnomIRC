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
	document.domain=HOSTNAME;
	var OmnomIRC = window.OmnomIRC = (function(){
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
				},
				_fn = function(fn,args){
					if(args === undefined){
						args = [];
					}
					return proto(fn).call(ret,args);
				};
			if(message.addEventListener ){
				message.addEventListener("keydown",_fn('keyHandler'),false);
			}else if(message.attachEvent ){
				message.attachEvent("onkeydown",_fn('keyHandler'));
			}
			window.onLoad = this.cookieLoad();
			return ret;
		})(),
		proto = function(fn){
			return function(){
				try{
					return _proto[fn].apply(OmnomIRC,arguments);
				}catch(e){
					return null;
				}
			};
		},
		run = function(fn,args,scope){
			if(scope === undefined){
				scope = this;
			}
			if(args === undefined){
				args = [];
			}else if(!args instanceof Array){
				args = [args];
			}
			return proto(fn).apply(scope,args);
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
					run('showNotification',"Notifications Enabled");
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
				window.webkitNotifications.requestPermission(run('permissionGranted'));
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
				var getCurrentWord = run('getCurrentWord'),
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
				var getCurrentWord = run('getCurrentWord'),
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
					indicatorTimer = setInterval(run('updateIndicator'),50);
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
			},
			startLoop: function(){
				xmlhttp=getAjaxObject();
				if (xmlhttp===null) { 
					alert ("Your browser does not support AJAX! Please update for OmnomIRC compatibility.");
					return;
				}
				xmlhttp.onreadystatechange=getIncomingLine;
				run('sendRequest');
			},
			cancelRequest: function(){
				xmlhttp.abort();
				inRequest = false;
			},
			sendRequest: function(){
				if(inRequest){
					return;
				}
				var url = "Update.php?lineNum=" + curLine + "&channel=" + getChannelEn() + "&nick=" + base64.encode(userName) + "&signature=" + base64.encode(Signature);
				xmlhttp.open("GET",url,true);
				if(isBlurred()){
					setTimeout(function(){
						xmlhttp.send(null);
					},2500); //Only query every 2.5 seconds maximum if not foregrounded.
				}else{
					setTimeout(function(){
						xmlhttp.send(null);
					},75); //Wait for everything to get parsed before requesting again.
				}
				inRequest = true;
			},
			getIncomingLine: function(){
				if(xmlhttp.readyState==4 || xmlhttp.readyState=="complete"){ 
					inRequest = false;
					if(xmlhttp.responseText == "Could not connect to SQL DB." || xmlhttp.status != 200){
						errorCount++;
						if(errorCount == 10){
							OmnomIRC_Error("OmnomIRC has lost connection to server. Please refresh to reconnect.");
							return;
						}else{
							sendRequest();
							return;
						}
					}
					if(xmlhttp.status == 200){
						run('addLines',xmlhttp.responseText); //Filter out 500s from timeouts
					}
					errorCount = 0;
					sendRequest();
				}
			},
			getAjaxObject: function(){
				xmlhttp=new XMLHttpRequest(); //Decent Browsers
				if(!xmlhttp || xmlhttp === undefined || xmlhttp === null){
					xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");  //IE7+
				}
				if(!xmlhttp || xmlhttp === undefined || xmlhttp === null){
					xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); //IE6-
				}
				return xmlhttp;
			},
			addLines: function(message){
				var parts = message.split("\n");
				for (var i=0;i<parts.length;i++){
					if (parts[i].length > 2){
						run('addLine',parts[i]);
					}
				}
			},
			addLine: function(message){
				if(!message || message === null || message === undefined){
					return;
				}
				var lnNum = parseInt(message.split(":")[0]);
				curLine = parseInt(curLine);
				if (lnNum > curLine){
					curLine = lnNum;
				}
				var doScroll = false;
				if(mBoxCont.clientHeight + mBoxCont.scrollTop > mBoxCont.scrollHeight - 50){
					doScroll = true;
				}
				//messageBox = document.getElementById("MessageBox");
				/*
				if ("\v" != "v") //If IE, take the slow but sure route (This is enough of a performance hit in all browsers to use the optimized code if possible. Also, IE can go fuck itself.)
					mBoxCont.innerHTML = '<table style="width:100%" class="messageBox" id="MessageBox">' + messageBox.innerHTML + parseMessage(message) + '</table>';
				else //If not IE, yay!
					messageBox.innerHTML = messageBox.innerHTML + parseMessage(message);*/
				var row = parseMessage(message);
				if(row){
					messageBox.appendChild(row);
				}
				if(doScroll){
					mBoxCont.scrollTop = mBoxCont.scrollHeight + 50;
				}
			},
			parseMessage: function(message){ //type of message
				var //a = message,
					parts = message.split(":"),
					//lnumber = parts[0],
					type = parts[1],
					online = parts[2],
					parsedMessage = "",
					i;
				for(i = 4;i < parts.length;i++){
					parts[i] = base64.decode(parts[i]);
				}
				name = clickable_names(parts[4],online);
				var undefined;
				if(parts[5] === undefined || parts[5] === ""){
					parts[5] = " ";
				}
				if(parts[5] !== undefined && parts[5] !== null){
					parsedMessage = parseColors(parts[5]);
					if(parts[5].toLowerCase().indexOf(userName.toLowerCase().substr(0,4)) >= 0 && hasLoaded && notifications && parts[4].toLowerCase() != "new"){
						showNotification("<" + parts[4] + "> " + parts[5]);
						if(highDing){
							document.getElementById('ding').play();
						}
					}
				}
				if((type == "message" || type == "action") && parts[4].toLowerCase() != "new"){
					parsedMessage = parseHighlight(parsedMessage);
				}
				retval = "";
				displayMessage = true;
				var tdTime = document.createElement('td');
				tdTime.className="irc-date";
				var tdName = document.createElement('td');
				tdName.className="name";
				tdName.innerHTML = '*';
				var tdMessage = document.createElement('td');
				tdMessage.className=type;
				switch(type){
					case "reload":
						startIndicator();
						cancelRequest();
						hasLoaded = false;
						scrolledDown = true;
						curLine = 0;
						UserListArr = [];
						userListDiv.innerHTML = "";
						drawChannels();
						var body= document.getElementsByTagName('body')[0],
							script= document.createElement('script');
						script.type= 'text/javascript';
						script.src= 'Load.php?count=125&channel=' + getChannelEn() + "&nick=" + base64.encode(userName) + "&signature=" + base64.encode(Signature) + "&time=" + (new Date).getTime();;
						script.onload= function(){
							parseUsers();
							startLoop();
							mBoxCont.scrollTop = mBoxCont.scrollHeight;
							hasLoaded = true;
							stopIndicator();
						};
						body.appendChild(script);
						displayMessage = false;
						break;
					case "join":
						tdMessage.innerHTML = name + " has joined "+getChannelDe();
						addUserJoin(parts[4],online);
						if (online == "1"){
							return "";
						}
						break;
					case "part":
						tdMessage.innerHTML = name + " has left "+getChannelDe()+" (" + parsedMessage + ")";
						removeUser(parts[4]);
						if (online == "1"){
							return "";
						}
						break;
					case "quit":
						tdMessage.innerHTML = name + " has quit IRC (" + parsedMessage + ")";
						removeUser(parts[4]);
						break;
					case "kick":
						tdMessage.innerHTML = name + " has kicked " + parts[5] + " from "+getChannelDe()+" (" + parts[6] + ")";
						removeUser(parts[4]);
						break;
					case "message":
						tdName.innerHTML = name;
						tdMessage.innerHTML = parsedMessage;
						break;
					case "action":
						tdMessage.innerHTML = name + " " + parsedMessage;
						break;
					case "mode":
						tdMessage.innerHTML = name + " set "+getChannelDe()+" mode " + parts[5];
						break;
					case "nick":
						tdMessage.innerHTML = name + " has changed his nick to " + parsedMessage;
						removeUser(parts[4]);
						addUserJoin(parts[5],online);
						break;
					case "topic":
						if(name!=="" && name!="undefined" && name!=" " && (typeof name != 'undefined')){
							tdMessage.innerHTML = name + " has changed the topic to " + parsedMessage;
						}else{
							displayMessage = false;
						}
						setTopic(parsedMessage);
						break;
					case "internal":
						tdMessage.innerHTML = parts[4];
					break;
					case "server":
						tdMessage.innerHTML = parsedMessage;
						break;
					case "pm":
						if (getChannelDe().toLowerCase() != ("*" + parts[4]).toLowerCase() && parts[4] != userName){//Not in the PM window
							if (!hasLoaded){
								return "";
							}
							tdMessage.innerHTML = parsedMessage;
							tdName.innerHTML = "(PM)" + name;
							if (hasLoaded){
								openPMWindow(parts[4]);
								if(notifications){
									showNotification("(PM) <" + parts[4] + "> " + parts[5]);
								}
								if(highDing){
									document.getElementById('ding').play();
								}
								document.getElementById("*" + parts[4]).style.color="#C22";
							}
						}else{
							tdMessage.className="message";
							tdMessage.innerHTML = parsedMessage; //In the PM window
							tdName.innerHTML = name;
						}
						break;
					case "curline":
						return "";
					case "highlight":
						if (parts[6].toLowerCase() == "new") return "";
						//document.getElementById(parts[4]).style.color="#C22"; //This call will fail if they aren't in the chan. Crude, but effective.
						if (notifications)
							showNotification("(" + parts[4] + ") <" + parts[6] + "> " + parts[7]);
						if (highDing)
							document.getElementById('ding').play();
						
						
						return "";
					case "default":
						return "";
				}
				var row = document.createElement("tr");
				//pretag = '<tr style="width:100%;">';
				doHigh = !doHigh;
				if (lineHigh && doHigh && displayMessage){
					//pretag = '<tr style="width:100%;" class="linehigh">';
					row.className = "linehigh";
				}
				doLineHigh = !doLineHigh;
				if (type != "internal"){
					d = new Date(parts[3]*1000);
				}
				if (type == "internal"){
					d = new Date();
				}
				tdTime.innerHTML = '[' + d.toLocaleTimeString() + ']';
				tdTime.style.height="1px";
				tdName.style.height="1px";
				tdMessage.style.height="1px";
				if(showTime){
					row.appendChild(tdTime);
				}
				row.appendChild(tdName);
				row.appendChild(tdMessage);
				row.style.width="100%";
				row.style.height="1px";
				refreshThis(row);
				if(tdName.innerHTML == "*"){
					statusTxt = tdName.innerHTML + " ";
				}else{
					statusTxt = "<" + StripHTML(tdName.innerHTML) + "> ";			
				}
				if (showTime){
					statusTxt = "[" + d.toLocaleTimeString() + "] " + statusTxt;
				}
				statusTxt = statusTxt + StripHTML(tdMessage.innerHTML);
				changeStatusBarText(statusTxt);
				if(displayMessage){
					return row;
				}else{
					return;
				}
			},
			fixMBoxContHeight: function(){
				mBoxCont.scrollTop = mBoxCont.scrollHeight;
			},
			parseSmileys: function(s){ //smileys
				if (showSmileys) {
					var addStuff = "";
					if (scrolledDown)
						addStuff = "onload='fixMBoxContHeight();'";
					s = s.replace(/(^| )(::\)|::-\))/g,"$1<img src='smileys/rolleyes.gif' alt='Roll Eyes' "+addStuff+">");
					s = s.replace(/(^| )(:\)|:-\))/g,"$1<img src='smileys/smiley.gif' alt='smiley' "+addStuff+">");
					s = s.replace(/(^| )(;\)|;-\))/g,"$1<img src='smileys/wink.gif' alt='Wink' "+addStuff+">");
					s = s.replace(/(^| )(&gt;:D|&gt;:-D)/g,"$1<img src='smileys/evil.gif' alt='Evil' "+addStuff+">");
					s = s.replace(/(^| )(:D|:-D)/g,"$1<img src='smileys/cheesy.gif' alt='Cheesy' "+addStuff+">");
					s = s.replace(/(^| )(;D|;-D)/g,"$1<img src='smileys/grin.gif' alt='Grin' "+addStuff+">");
					s = s.replace(/(^| )(&gt;:\(|&gt;:-\()/g,"$1<img src='smileys/angry.gif' alt='Angry' "+addStuff+">");
					s = s.replace(/(^| )(:\(|:-\()/g,"$1<img src='smileys/sad.gif' alt='Sad' "+addStuff+">");
					s = s.replace(/(^| )(:o|:O|:-o|:-O)/g,"$1<img src='smileys/shocked.gif' alt='Shocked' "+addStuff+">");
					s = s.replace(/(^| )(8\))/g,"$1<img src='smileys/cool.gif' alt='Cool' "+addStuff+">");
					s = s.replace(/(^| )\?\?\?/g,"$1<img src='smileys/huh.gif' alt='Huh' "+addStuff+">");
					s = s.replace(/(^| )(:P|:-P|:p|:-p)/g,"$1<img src='smileys/tongue.gif' alt='Tongue' "+addStuff+">");
					s = s.replace(/(^| )(:\[|:-\[)/g,"$1<img src='smileys/embarrassed.gif' alt='Embarrassed' "+addStuff+">");
					s = s.replace(/(^| )(:x|:-x|:X|:-X)/g,"$1<img src='smileys/lipsrsealed.gif' alt='Lips Sealed' "+addStuff+">");
					s = s.replace(/(^| )(:\\|:-\\)/g,"$1<img src='smileys/undecided.gif' alt='Undecided' "+addStuff+">");
					s = s.replace(/(^| ):-\*/g,"$1<img src='smileys/kiss.gif' alt='Kiss' "+addStuff+">");
					s = s.replace(/(^| )(:'\(|:'-\()/g,"$1<img src='smileys/cry.gif' alt='Cry' "+addStuff+">");
					s = s.replace(/:thumbsup:/g,"<img src='smileys/thumbsupsmiley.gif' alt='Thumbs Up' "+addStuff+">");
					s = s.replace(/(^| )O\.O/g,"$1<img src='smileys/shocked2.gif' alt='Shocked' "+addStuff+">");
					s = s.replace(/(^| )\^-\^/g,"$1<img src='smileys/azn.gif' alt='Azn' "+addStuff+">");
					s = s.replace(/(^| )&gt;B\)/g,"$1<img src='smileys/alien2.gif' alt='Alien' "+addStuff+">");
					s = s.replace(/(:banghead:|:headbang:)/g,"<img src='smileys/banghead.gif' alt='Bandhead' "+addStuff+">");
					s = s.replace(/:angel:/g,"<img src='smileys/ange.gif' alt='Angel' "+addStuff+">");
					s = s.replace(/(^| )\._\./g,"$1<img src='smileys/blah.gif' alt='Blah' "+addStuff+">");
					s = s.replace(/:devil:/g,"<img src='smileys/devil.gif' alt='Devil' "+addStuff+">");
					s = s.replace(/(^| )&lt;_&lt;/g,"$1<img src='smileys/dry.gif' alt='Dry' "+addStuff+">");
					s = s.replace(/:evillaugh:/g,"<img src='smileys/evillaugh.gif' alt='Evil Laugh' "+addStuff+">");
					s = s.replace(/:crazy:/g,"<img src='smileys/fou.gif' alt='Crazy' "+addStuff+">");
					s = s.replace(/:hyper:/g,"<img src='smileys/happy0075.gif' alt='Hyper' "+addStuff+">");
					s = s.replace(/:love:/g,"<img src='smileys/love.gif' alt='Love' "+addStuff+">");
					s = s.replace(/:mad:/g,"<img src='smileys/mad.gif' alt='Mad' "+addStuff+">");
					s = s.replace(/:w00t:/g,"<img src='smileys/smiley_woot.gif' alt='w00t' "+addStuff+">");
					s = s.replace(/(^| )\*\.\*/g,"$1<img src='smileys/psychedelicO_O.gif' alt='O.O.O' "+addStuff+">");
					s = s.replace(/(^| )D:/g,"$1<img src='smileys/bigfrown.gif' alt='Big Frown' "+addStuff+">");
					s = s.replace(/(^| )(XD|xD)/g,"$1<img src='smileys/XD.gif' alt='XD' "+addStuff+">");
					s = s.replace(/(^| )x\.x/g,"$1<img src='smileys/X_X.gif' alt='x.x' "+addStuff+">");
					s = s.replace(/:ninja:/g,"<img src='smileys/ninja.gif' alt='Ninja' "+addStuff+">");
				}
				return s;
			},
			parseColors: function(colorStr){ //colors
				if (!colorStr || colorStr === null || colorStr === undefined){
					return;
				}
				colorStr = clickable_links(colorStr);
				colorStr = parseSmileys(colorStr);
				//lcount = 0;
				//a = colorStr;
				var arrayResults = [],
					isBool = false,
					numSpan = 0,
					isItalic = false,
					isUnderline = false,
					s,
					colorStrTemp = "1,0";
				colorStr+="\x0f";
				arrayResults = colorStr.split(RegExp("([\x02\x03\x0f\x16\x1d\x1f])"));
				colorStr="";
				for(var i=0;i<arrayResults.length;i++){
					switch (arrayResults[i]){
						case "\x03":
							for(var j=0;j<numSpan;j++){
								colorStr+="</span>";
							}
							numSpan=1;
							i++;
							colorStrTemp = arrayResults[i];
							s=arrayResults[i].replace(/^([0-9]{1,2}),([0-9]{1,2})/g,"<span class=\"fg-$1\"><span class=\"bg-$2\">");
							if(s==arrayResults[i]){
								s=arrayResults[i].replace(/^([0-9]{1,2})/g,"<span class=\"fg-$1\">");
							}else{
								numSpan++;
							}
							colorStr+=s;
							break;
						case "\x02":
							isBool = !isBool;
							if(isBool){
								colorStr+="<b>";
							}else{
								colorStr+="</b>";
							}
							break;
						case "\x1d":
							isItalic = !isItalic;
							if(isItalic){
								colorStr+="<i>";
							}else{
								colorStr+="</i>";
							}
							break;
						case "\x16":
							for(j=0;j<numSpan;j++){
								colorStr+="</span>";
							}
							numSpan=2;
							var stemp;
							s=colorStrTemp.replace(/^([0-9]{1,2}),([0-9]{1,2}).+/g,"<span class=\"fg-$2\"><span class=\"bg-$1\">");
							stemp=colorStrTemp.replace(/^([0-9]{1,2}),([0-9]{1,2}).+/g,"$2,$1");
							if(s==colorStrTemp){
								s=colorStrTemp.replace(/^([0-9]{1,2}).+/g,"<span class=\"fg-0\"><span class=\"bg-$1\">");
								stemp=colorStrTemp.replace(/^([0-9]{1,2}).+/g,"0,$1");
							}
							colorStrTemp = stemp;
							colorStr+=s;
							break;
						case "\x1f":
							isUnderline = !isUnderline;
							if(isUnderline){
								colorStr+="<u>";
							}else{
								colorStr+="</u>";
							}
							break;
						case "\x0f":
							if(isUnderline){
								colorStr+="</u>";
								isUnderline=false;
							}
							if(isItalic){
								colorStr+="</i>";
								isItalic=false;
							}
							if(isBool){
								colorStr+="</b>";
								isBool = false;
							}
							for(j=0;j<numSpan;j++){
								colorStr+="</span>";
							}
							numSpan=0;
							break;
						default:
							colorStr+=arrayResults[i];
					}
				}
				/*Strip codes*/
				colorStr = colorStr.replace(/(\x03|\x02|\x1F|\x09|\x0F)/g,"");
				return(colorStr);
			},
			parseHighlight: function(text){ //highlight
				if (text.toLowerCase().indexOf(userName.toLowerCase().substr(0,4)) >= 0){
					var style = "";
					if(highRed){
						style = style + "color:#C73232;";
					}
					if(highBold){
						style = style + "font-weight:bold;";
					}
					return '<span class="highlight" style="' + style + '">' + text + "</span>";
				}
				return text;
			},
			clickable_links: function(text){ //urls
				if (!text || text === null || text === undefined){
					return;
				}
				//text = text.replace(/http:\/\/www\.omnimaga\.org\//g,"h111://www.omnimaga.org/");
				//text = text.replace(/http:\/\/ourl\.ca\//g,"h111://ourl.ca/");
				//text = text.replace(/((h111:\/\/(www\.omnimaga\.org\/|ourl\.ca))[-a-zA-Z0-9@:;%_+.~#?&//=]+)/, '<a target="_top" href="$1">$1</a>');
				text = text.replace(RegExp("(^|.)(((f|ht)(tp|tps):\/\/)[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="$2">$2</a>');
				text = text.replace(RegExp("(^|\\s)(www\.[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="http://$2">$2</a>');
				//text = text.replace(/h111/g,"http");
				return text;
			},
			clickable_names: function(name,isOnline){ //omnomirc names
				if (isOnline == "1"){
					return '<a target="_top" href="http://www.omnimaga.org/index.php?action=ezportal;sa=page;p=13&userSearch=' + name + '">' + colored_names(name) + '</a>';
				}
				return colored_names(name);
			},
			colored_names: function(name){ //colored neames (duh)
				if (!coloredNames){
					return name;
				}
				if (!name || name === null || name === undefined){
					return;
				}
				var rcolors = Array(19, 20, 22, 24, 25, 26, 27, 28, 29),
					sum = 0,
					i = 0; 
				while (name[i]){
					sum += name.charCodeAt(i++);
				}
				sum %= 9;
				return '<span class="uName-'+rcolors[sum]+'">'+name+'</span>';
			},
			refreshThis: function(elementOnShow){
				var msie = 'Microsoft Internet Explorer';
				var tmp = 0;
				if (navigator.appName == msie){
					tmp = elementOnShow.offsetTop  +  'px';
				}else{
					tmp = elementOnShow.offsetTop;
				}
			},
			addUser: function(user){
				UserListArr.push(user);
			},
			addUserJoin: function(user,online){
				if(!hasLoaded) return;
				var userp = base64.encode(user) + ":" + online;
				UserListArr.push(userp);
				parseUsers();
			},
			parseUsers: function(){
				if (!userListDiv || userListDiv == null){
					userListDiv = document.getElementById("UserList");
				}
				userText = "";
				i = 0;
				UserListArr.sort(
					function(a,b){
						var al = base64.decode(a).toLowerCase(),
							bl = base64.decode(b).toLowerCase();
						return al==bl?(a==b?0:a<b?-1:1):al<bl?-1:1;
					}
				);
				for (i=0;i<UserListArr.length;i++){
					parts = UserListArr[i].split(":");
					if (parts[1] == "0"){
						userText = userText + "#" + base64.decode(parts[0]) + "<br/>";
					}
					if(parts[1] == "1"){
						userText = userText + '<a target="_top" href="http://www.omnimaga.org/index.php?action=ezportal;sa=page;p=13&userSearch=' +base64.decode(parts[0]) + 
						'"><img src="http://omnomirc.www.omnimaga.org/omni.png" alt="Omnimaga User" title="Omnimaga User" border=0 width=8 height=8 />' + base64.decode(parts[0]) + '</a><br/>';
					}
					if(parts[1] == "2"){
						userText = userText + "!" + base64.decode(parts[0]) + "<br/>";
					}
				}
				userText = userText + "<br/><br/>";
				userListDiv.innerHTML = userText;
			},
			removeUser: function(user){
				if(!hasLoaded) return;
				for (var i in UserListArr){
					var parts = UserListArr[i].split(":");
					if (base64.decode(parts[0]) == user){
						UserListArr.splice(i,1);
					}
				}
				parseUsers();
			},
			load: function(){
				cookieLoad();
				lineHigh = getOption(6,"T") == "T";
				doHigh = false;
				coloredNames = getOption(3,"F") == "T";
				highRed = getOption(2,"T") == "T";
				highBold = getOption(1,"T") == "T";
				enabled = getOption(5,"T") == "T";
				notifications = getOption(7,"F") == "T";
				highDing = getOption(8,"F") == "T";
				showExChans = getOption(9,"F") == "T";
				showTime = getOption(10,"F") == "T";
				doStatusUpdates = getOption(11,"T") == "T";
				showSmileys = getOption(12,"T") == "T";
				hasLoaded = false;
				if (!showSmileys){
					document.getElementById('smileyMenuButton').src='smileys/smiley_grey.png';
					document.getElementById('smileyMenuButton').style.cursor='default';
				}
				if (!enabled){
					mboxCont.appendChild(messageBox);
					messageBox.innerHTML = '<a href="#" onclick="toggleEnable();">OmnomIRC is disabled. Click here to enable.</a>';
					return false;
				}
				doLineHigh=true;
				var body= document.getElementsByTagName('body')[0];
				var chanScr= document.createElement('script');
				chanScr.type= 'text/javascript';
				chanScr.src= 'Channels.php';
				chanScr.onload= function(){
					channelSelectorCallback();
					readOldMessagesCookies();
				};
				body.appendChild(chanScr);
				chanList = document.getElementById('chanList');
				isBlurred();
				if (userName == "Guest"){
					var message = document.getElementById("message");
					message.disabled = "true";
					message.value = "You need to login if you want to chat!";
				}
			},
			toggleEnable: function(){
				setOption(5,!(getOption(5,'T') == 'T')?'T':'F');
				window.location.reload(true);
			},
			sendAJAXMessage: function(name,signature,message,chan){ //'chan' kept for legacy purposes.
				if (message[0] == "/"){
					if (parseCommand(message.substr(1)))
						return;
				}
				if (getChannelDe()[0] == "*"){
					var d = new Date(),
						str="0:pm:0:" + d.getTime()/1000 + ":" + base64.encode(name) + ":" + base64.encode(HTMLEncode(message)); //Print PMs locally.
					//addLine(str);
				}
				var xmlhttp2=new XMLHttpRequest();
				xmlhttp2.onreadyStateChange = function(){
					console.log(xmlhttp2.readyState,xmlhttp2.responseText);
				};
				xmlhttp2.open(
					"GET",
					"message.php?nick=" + base64.encode(name) + "&signature="+base64.encode(signature)+"&message=" + base64.encode(message) +"&channel=" + getChannelEn(),
					false
				);
				xmlhttp2.send(null);
			},
			
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
		currentMessage,
		messageList = [],
		UserListArr = [],
		curLine = 0,
		messageBox = window.messageBox = document.createElement("table"),
		mBoxCont = window.mBoxCont = document.getElementById("mboxCont"),
		Userlist = [],
		scrolledDown = true,
		statusTxt = "",
		statusStarted = false,
		focusHandlerRegistered = false,
		userListContainer = document.getElementById("UserListArrContainer"),
		userListDiv = document.getElementById("UserList"),
		xmlhttp,
		inRequest = false,
		errorCount = 0;
	messageBox.style.width="100%";
	messageBox.style.height="100%";
	messageBox.className='MessageBox';
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