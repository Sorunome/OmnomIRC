
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45

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

document.domain=HOSTNAME;

messageList = Array();
UserListArr = Array();
curLine = 0;
//messageBox = document.getElementById("MessageBox");
messageBox = document.createElement("table");
messageBox.style.width="100%";
messageBox.style.height="100%";
messageBox.className='MessageBox';
mBoxCont = document.getElementById("mboxCont");
Userlist = Array();
scrolledDown = true;
//******************************
// Start Request Loop functions*
//******************************
	function startLoop()
	{
		xmlhttp=getAjaxObject();
		if (xmlhttp==null) { 
			alert ("Your browser does not support AJAX! Please update for OmnomIRC compatibility.");
			return;
		}
		xmlhttp.onreadystatechange=getIncomingLine;
		sendRequest();
	}
	inRequest = false;
	errorCount = 0;
	function cancelRequest()
	{
		xmlhttp.abort();
		inRequest = false;
	}
	function sendRequest()
	{
		if (inRequest)
			return;
		url = "Update.php?lineNum=" + curLine + "&channel=" + getChannelEn() + "&nick=" + base64.encode(userName) + "&signature=" + base64.encode(Signature) + "&high=" + numCharsHighlight.toString() + "&time=" + (new Date().getTime()).toString();
		xmlhttp.open("GET",url,true);
		if (isBlurred()){
			setTimeout("xmlhttp.send(null);",2500); //Only query every 2.5 seconds maximum if not foregrounded.
		}
		else
		{
			setTimeout("xmlhttp.send(null);",75); //Wait for everything to get parsed before requesting again.
		}
		inRequest = true;
	}
	
	function getIncomingLine()
	{
		if (xmlhttp.readyState==4 || xmlhttp.readyState=="complete") { 
			inRequest = false;
			if (xmlhttp.responseText == "Could not connect to SQL DB." || xmlhttp.status != 200) 
			{
				errorCount++;
				if (errorCount == 10)
				{
					OmnomIRC_Error("OmnomIRC has lost connection to server. Please refresh to reconnect.");
					return;
				}
				else
				{
					sendRequest();
					return;
				}
			}
			if (xmlhttp.status == 200){
				addLines(xmlhttp.responseText);
				errorCount = 0;
				sendRequest();
			}
		}
	}
	
	function getAjaxObject()
	{
		xmlhttp=new XMLHttpRequest(); //Decent Browsers
		if (!xmlhttp || xmlhttp == undefined || xmlhttp == null) xmlhttp=new ActiveXObject("Msxml2.XMLHTTP");  //IE7+
		if (!xmlhttp || xmlhttp == undefined || xmlhttp == null) xmlhttp=new ActiveXObject("Microsoft.XMLHTTP"); //IE6-
		return xmlhttp;
	}
	
//******************************
// End Request Loop functions  *
//******************************

//******************************
// Start Parser                *
//******************************
	function addLines(message){
		var lineParts = message.split("\n");
		for (var i=0;i<lineParts.length;i++){
			if (lineParts[i].length > 2){
				addLine(lineParts[i]);
			}
		}
	}
	function addLine(message)
	{
		if (!message || message == null || message == undefined)
			return;
		lnNum = parseInt(message.split(":")[0]);
		curLine = parseInt(curLine);
		if (lnNum > curLine)
			curLine = lnNum;
		doScroll = false;
		if (mBoxCont.clientHeight + mBoxCont.scrollTop > mBoxCont.scrollHeight - 50) doScroll = true;
		//messageBox = document.getElementById("MessageBox");
		/*
		if ("\v" != "v") //If IE, take the slow but sure route (This is enough of a performance hit in all browsers to use the optimized code if possible. Also, IE can go fuck itself.)
			mBoxCont.innerHTML = '<table style="width:100%" class="messageBox" id="MessageBox">' + messageBox.innerHTML + parseMessage(message) + '</table>';
		else //If not IE, yay!
			messageBox.innerHTML = messageBox.innerHTML + parseMessage(message);*/
		var row = parseMessage(message);
		if (row)
			messageBox.appendChild(row);
		if (doScroll && scrolledDown)mBoxCont.scrollTop = mBoxCont.scrollHeight + 50;
	}
	
	function parseMessage(message) //type of message
	{
		a = message;
		var parts = message.split(":");
		lnumber = parts[0];
		type = parts[1];
		online = parts[2];
		parsedMessage = "";
		
		for (i = 4;i < parts.length;i++)
			parts[i] = base64.decode(parts[i]);
		name = clickable_names(parts[4],online);
		var undefined;
		if (parts[5] == undefined || parts[5] == "")
			parts[5] = " ";
		if (parts[5] != undefined && parts[5] != null)
		{
			parsedMessage = parseColors(parts[5]);
			parsedMessage = parsedMessage.split("  ").join("&nbsp;&nbsp;");
			parsedMessage = parsedMessage.split("\t").join("&nbsp;&nbsp;&nbsp;&nbsp;");
			parsedMessage = parsedMessage.split("&nbsp; ").join("&nbsp;&nbsp;");
			if (parts[5].toLowerCase().indexOf(userName.toLowerCase().substr(0,4)) >= 0 && hasLoaded && notifications && parts[4].toLowerCase() != "new" && parts[4].toLowerCase() != "omnom")
			{
				showNotification("<" + parts[4] + "> " + parts[5]);
				if (highDing)
				{
					document.getElementById('ding').play();
				}
			}
		}
		if ((type == "message" || type == "action") && parts[4].toLowerCase() != "new" && parts[4].toLowerCase() != "omnom")
		{
			parsedMessage = parseHighlight(parsedMessage);
		}
		retval = "";
		displayMessage = true;
		tdTime = document.createElement('td');
		tdTime.className="irc-date";
		tdName = document.createElement('td');
		tdName.className="name";
		tdName.innerHTML = '*';
		tdMessage = document.createElement('td');
		tdMessage.className=type;
		switch (type)
		{
			case "reload":
				startIndicator();
				cancelRequest();
				hasLoaded = false;
				scrolledDown = true;
				curLine = 0;
				UserListArr = Array();
				userListDiv.innerHTML = "";
				drawChannels();
				var body= document.getElementsByTagName('body')[0];
				var script= document.createElement('script');
				script.type= 'text/javascript';
				script.src= 'Load.php?count=125&channel=' + getChannelEn() + "&nick=" + base64.encode(userName) + "&signature=" + base64.encode(Signature) + "&time=" + (new Date).getTime();;
				script.onload= function(){parseUsers();startLoop();mBoxCont.scrollTop = mBoxCont.scrollHeight;doHigh = !doHigh;hasLoaded = true;stopIndicator();};
				body.appendChild(script);
				displayMessage = false;
				break;
			case "join":
				tdMessage.innerHTML = name + " has joined "+getChannelDe();
				addUserJoin(parts[4],online);
				if (online == "1")
					return "";
				break;
			case "part":
				tdMessage.innerHTML = name + " has left "+getChannelDe()+" (" + parsedMessage + ")";
				removeUser(parts[4]);
				if (online == "1")
					return "";
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
				tdMessage.innerHTML = parsedMessage;break;
			case "action":
				tdMessage.innerHTML = name + " " + parsedMessage;break;
			case "mode":
				tdMessage.innerHTML = name + " set "+getChannelDe()+" mode " + parts[5];break;
			case "nick":
				tdMessage.innerHTML = name + " has changed his nick to " + parsedMessage;
				removeUser(parts[4]);
				addUserJoin(parts[5],online);
				break;
			case "topic":
				if (name!="" && name!="undefined" && name!=" " && (typeof name != 'undefined')) {
					tdMessage.innerHTML = name + " has changed the topic to " + parsedMessage;
				} else {
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
				if (getChannelDe().toLowerCase() != ("*" + parts[4]).toLowerCase() && parts[4] != userName)//Not in the PM window
				{
					if (!hasLoaded)
						return "";
					tdMessage.innerHTML = parsedMessage;
					tdName.innerHTML = "(PM)" + name;
					if (hasLoaded)
					{
						openPMWindow(parts[4]);
						if (notifications)
							showNotification("(PM) <" + parts[4] + "> " + parts[5]);
						if (highDing)
								document.getElementById('ding').play();
						document.getElementById("*" + parts[4]).style.color="#C22";
					}
				}
				else
				{
					tdMessage.className="message";
					tdMessage.innerHTML = parsedMessage; //In the PM window
					tdName.innerHTML = name;
				}
			break;
			case "curline":
				return "";
			break;
			case "highlight":
				if (parts[6].toLowerCase() == "new") return "";
				//document.getElementById(parts[4]).style.color="#C22"; //This call will fail if they aren't in the chan. Crude, but effective.
				if (notifications)
					showNotification("(" + parts[4] + ") <" + parts[6] + "> " + parts[7]);
				if (highDing)
					document.getElementById('ding').play();
				
				
				return "";
			break;
			case "default":
				return "";
			break;
		}
		var row = document.createElement("tr");
		
		//pretag = '<tr style="width:100%;">';
		doHigh = !doHigh;
		if (lineHigh && doHigh && displayMessage)
		{
			//pretag = '<tr style="width:100%;" class="linehigh">';
			row.className = "linehigh";
		}
		doLineHigh = !doLineHigh;
		if (type != "internal") d = new Date(parts[3]*1000);
		if (type == "internal") 
		{
			d = new Date();
		}
		tdTime.innerHTML = '[' + d.toLocaleTimeString() + ']';
		tdTime.style.height="1px";
		tdName.style.height="1px";
		tdMessage.style.height="1px";
		if (showTime) row.appendChild(tdTime);
		row.appendChild(tdName);
		row.appendChild(tdMessage);
		
		row.style.width="100%";
		row.style.height="1px";
		refreshThis(row);
		if (tdName.innerHTML == "*")
			statusTxt = tdName.innerHTML + " ";
		else
			statusTxt = "<" + StripHTML(tdName.innerHTML) + "> ";			
		if (showTime)
			statusTxt = "[" + d.toLocaleTimeString() + "] " + statusTxt;
			
		statusTxt = statusTxt + StripHTML(tdMessage.innerHTML);
		changeStatusBarText(statusTxt);
		if (displayMessage)
			return row;
		else
			return;
	}
	function fixMBoxContHeight() {
		mBoxCont.scrollTop = mBoxCont.scrollHeight;
	}
	function parseSmileys(s) { //smileys
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
	}
	
	function parseColors(colorStr) //colors
	{
		if (!colorStr || colorStr == null || colorStr == undefined) return;
		colorStr = clickable_links(colorStr);
		colorStr = parseSmileys(colorStr);
		//lcount = 0;
		//a = colorStr;
		var arrayResults = Array();
		colorStr+="\x0f";
		arrayResults = colorStr.split(RegExp("([\x02\x03\x0f\x16\x1d\x1f])"));
		colorStr="";
		var isBool = false;
		var numSpan = 0;
		var isItalic = false;
		var isUnderline = false;
		var s;
		var colorStrTemp = "1,0";
		for (var i=0;i<arrayResults.length;i++) {
			switch (arrayResults[i]) {
				case "\x03":
					for (var j=0;j<numSpan;j++)
						colorStr+="</span>";
					numSpan=1;
					i++;
					colorStrTemp = arrayResults[i];
					s=arrayResults[i].replace(/^([0-9]{1,2}),([0-9]{1,2})/g,"<span class=\"fg-$1\"><span class=\"bg-$2\">");
					if (s==arrayResults[i]) {
						s=arrayResults[i].replace(/^([0-9]{1,2})/g,"<span class=\"fg-$1\">");
					} else {
						numSpan++;
					}
					colorStr+=s;
					break;
				case "\x02":
					isBool = !isBool;
					if (isBool) {
						colorStr+="<b>";
					} else {
						colorStr+="</b>";
					}
					break;
				case "\x1d":
					isItalic = !isItalic;
					if (isItalic) {
						colorStr+="<i>";
					} else {
						colorStr+="</i>";
					}
					break;
				case "\x16":
					for (var j=0;j<numSpan;j++)
						colorStr+="</span>";
					numSpan=2;
					var stemp;
					s=colorStrTemp.replace(/^([0-9]{1,2}),([0-9]{1,2}).+/g,"<span class=\"fg-$2\"><span class=\"bg-$1\">");
					stemp=colorStrTemp.replace(/^([0-9]{1,2}),([0-9]{1,2}).+/g,"$2,$1");
					if (s==colorStrTemp) {
						s=colorStrTemp.replace(/^([0-9]{1,2}).+/g,"<span class=\"fg-0\"><span class=\"bg-$1\">");
						stemp=colorStrTemp.replace(/^([0-9]{1,2}).+/g,"0,$1");
					}
					colorStrTemp = stemp;
					colorStr+=s;
					break;
				case "\x1f":
					isUnderline = !isUnderline;
					if (isUnderline) {
						colorStr+="<u>";
					} else {
						colorStr+="</u>";
					}
					break;
				case "\x0f":
					if (isUnderline) {
						colorStr+="</u>";
						isUnderline=false;
					}
					if (isItalic) {
						colorStr+="</i>";
						isItalic=false;
					}
					if (isBool) {
						colorStr+="</b>"
						isBool = false;
					}
					for (var j=0;j<numSpan;j++)
						colorStr+="</span>";
					numSpan=0;
					break;
				default:
					colorStr+=arrayResults[i];
			}
		}
		/*Strip codes*/
		colorStr = colorStr.replace(/(\x03|\x02|\x1F|\x09|\x0F)/g,"");

		return(colorStr);
	}
	function parseHighlight(text) //highlight
	{
		if (text.toLowerCase().indexOf(userName.toLowerCase().substr(0,numCharsHighlight)) >= 0 && userName != "Guest")
		{
			style = "";
			if (!highRed)
				style = style + "background:none;padding:none;border:none;";
			if (highBold)
				style = style + "font-weight:bold;";
			return '<span class="highlight" style="' + style + '">' + text + "</span>";
		}
		return text;
	}
	function clickable_links(text) //urls
	{
		text = text.replace(/(\x01)/g,"");
		if (!text || text == null || text == undefined) return;
		//text = text.replace(/http:\/\/www\.omnimaga\.org\//g,"\x01www.omnimaga.org/");
		text = text.replace(/http:\/\/ourl\.ca\//g,"\x01ourl.ca/");
		text = text.replace(/((h111:\/\/(www\.omnimaga\.org\/|ourl\.ca))[-a-zA-Z0-9@:;%_+.~#?&//=]+)/, '<a target="_top" href="$1">$1</a>');
		text = text.replace(RegExp("(^|.)(((f|ht)(tp|tps):\/\/)[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="$2">$2</a>');
		text = text.replace(RegExp("(^|\\s)(www\.[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="http://$2">$2</a>');
		text = text.replace(RegExp("(^|.)\x01([^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_top" href="http://$2">http://$2</a>');
		return text;
	}
	function clickable_names(name,isOnline) //omnomirc names
	{
		if (isOnline == "1")
			return '<a target="_top" href="' + SEARCHNAMESURL + name + '">' + colored_names(name) + '</a>';
		if (isOnline == "2")
			return '<span style="color:#8A5D22">(C)</span> '+colored_names(name);
		return colored_names(name);
	}
	function colored_names(name) //colored neames (duh)
	{
		if (!coloredNames)
			return name;
		if (!name || name == null || name == undefined)
			return;
		rcolors = Array(19, 20, 22, 24, 25, 26, 27, 28, 29);
		sum = i = 0; 
		while (name[i])
			sum += name.charCodeAt(i++);
		sum %= 9;
		return '<span class="uName-'+rcolors[sum]+'">'+name+'</span>';
	}
	
	function refreshThis(elementOnShow){
	   var msie = 'Microsoft Internet Explorer';
	   var tmp = 0;
	   if (navigator.appName == msie){
		  tmp = elementOnShow.offsetTop  +  'px';
	   }else{
		  tmp = elementOnShow.offsetTop;
	   }
	}
	
//******************************
// End Parser                  *
//******************************

//******************************
// Userlist Start              *
//******************************
	userListContainer = document.getElementById("UserListArrContainer");
	userListDiv = document.getElementById("UserList");
	function addUser(user)
	{
		UserListArr.push(user);
	}
	
	function addUserJoin(user,online)
	{
		if(!hasLoaded) return;
		var userp = base64.encode(user) + ":" + online;
		UserListArr.push(userp);
		parseUsers();
	}
	
	function parseUsers()
	{
		if (!userListDiv || userListDiv == null)
		 userListDiv = document.getElementById("UserList");
		userText = "";
		i = 0;
		UserListArr.sort(function(a,b)
						{
							var al=base64.decode(a).toLowerCase(),bl=base64.decode(b).toLowerCase();
							return al==bl?(a==b?0:a<b?-1:1):al<bl?-1:1;
						});
		for (i=0;i<UserListArr.length;i++)
		{
			parts = UserListArr[i].split(":");
			if (parts[1] == "0") userText = userText + "#" + base64.decode(parts[0]) + "<br/>";
			if (parts[1] == "1") 
				userText = userText + '<a target="_top" href="'+SEARCHNAMESURL+base64.decode(parts[0]) + 
				'"><img src="omni.png" alt="Omnimaga User" title="Omnimaga User" border=0 width=8 height=8 />' + base64.decode(parts[0]) + '</a><br/>';
			if (parts[1] == "2") userText = userText + "!" + base64.decode(parts[0]) + "<br/>";
		}
		userText = userText + "<br/><br/>";
		userListDiv.innerHTML = userText;
	}
	
	function removeUser(user)
	{
		if(!hasLoaded) return;
		for (i in UserListArr)
		{
			parts = UserListArr[i].split(":");
			if (base64.decode(parts[0]) == user)
				UserListArr.splice(i,1);
		}
		parseUsers();
	}
	
	
//******************************
// Userlist End                *
//******************************
//******************************
// Load Start                  *
//******************************
	function load()
	{
		var body= document.getElementsByTagName('body')[0];
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
		numCharsHighlight = parseInt(getOption(13,"3"))+1;
		hideUserlist = getOption(14,"F") == "T";
		showScrollBar = getOption(15,"F") == "T";
		if (!hideUserlist) {
			var style = document.createElement("style");
			style.type="text/css";
			style.innerHTML = "#topicbox{width:88%;width:calc(88% - 5px);width:-webkit-calc(88% - 5px);}\
								#Channels{width:88%;}\
								input[type=text]{width:82%;width:calc(91% - 115px);width:-webkit-calc(91% - 115px);}\
								#mBoxCont{width:90%;}\
								.arrowButtonHoriz2,.arrowButtonHoriz3 > div:nth-child(2){left:89%;left:calc(90% - 5px);left:-webkit-calc(90% - 5px);}\
								#UserListContainer{left:90%;height:98%;transition:none;-webkit-transition:none;-o-transition-property:none;-o-transition-duration:none;-o-transition-delay:none;}";
			body.appendChild(style);
		}
		if (showScrollBar) {
			var scrollBar = document.createElement("div");
			scrollBar.id="scrollBar";
			scrollBar.style.width="8px";
			scrollBar.style.height="100px";
			scrollBar.style.borderRadius="4px";
			scrollBar.style.position="absolute";
			scrollBar.style.zIndex="42";
			scrollBar.style.left=String(((body.offsetWidth/100)*99)-17)+"px";
			scrollBar.style.cursor="pointer";
			body.appendChild(scrollBar);
			scrollBar.prevY=0;
			scrollBar.isClicked=false;
			scrollBar.onmousemove = function(e) {
				var y = e.clientY;
				var element=document.getElementById('scrollBar');
				if (element.isClicked) {
					element.style.top=String(parseInt(element.style.top)+(y-element.prevY))+"px";
					document.getElementById('mboxCont').scrollTop += (y - element.prevY)*((mBoxCont.scrollHeight-parseInt(mBoxCont.style.height))/(document.getElementsByTagName('body')[0].offsetHeight-parseInt(element.style.height)));
					if (mBoxCont.scrollTop + mBoxCont.clientHeight == mBoxCont.scrollHeight)
						scrolledDown = true;
					else
						scrolledDown = false;
					if (parseInt(element.style.top)<0)
						element.style.top="0px";
					if (parseInt(element.style.top)>(body.offsetHeight-scrollBar.offsetHeight))
						element.style.top=String(body.offsetHeight-scrollBar.offsetHeight)+"px";
				}
				element.prevY=y;};
			scrollBar.onmousedown = function() {
				document.getElementById('scrollBar').isClicked=true;
				document.getElementById('scrollArea').style.display="";
			};
			scrollBar.onmouseup = function() {
				document.getElementById('scrollBar').isClicked=false;
				document.getElementById('scrollArea').style.display="none";
			};
			/*scrollBar.onmouseout = function() {
				document.getElementById('scrollBar').isClicked=false;
			};*/
			scrollBar.style.top=String(body.offsetHeight-scrollBar.offsetHeight)+"px";
			var scrollArea = document.createElement("div");
			scrollArea.id="scrollArea";
			scrollArea.style.display="none";
			scrollArea.style.width="100%";
			scrollArea.style.height="100%";
			scrollArea.style.position="absolute";
			scrollArea.style.left="0px";
			scrollArea.style.top="0px";
			scrollArea.style.zIndex="100";
			scrollArea.style.cursor="pointer";
			body.appendChild(scrollArea);
			scrollArea.onmousemove = scrollBar.onmousemove;
			scrollArea.onmouseup = scrollBar.onmouseup;
			scrollArea.onmouseout = scrollBar.onmouseup;
			
			var line = document.createElement("div");
			line.style.width="1px";
			line.style.height="100%";
			line.style.border="none";
			line.style.backgroundColor="black";
			line.style.position="absolute";
			line.style.zIndex="41";
			line.style.top=0;
			line.style.left=String(((body.offsetWidth/100)*99)-13)+"px";
			body.appendChild(line);
			mboxCont.style.width=String(((body.offsetWidth/100)*99)-22)+"px";
			var style = document.createElement("style");
			style.type="text/css";
			style.innerHTML = ".arrowButtonHoriz3{display:none;}";
			body.appendChild(style);
			
			if (!hideUserlist) {
				scrollBar.style.left=String(((body.offsetWidth/100)*90)-17)+"px";
				line.style.left=String(((body.offsetWidth/100)*90)-13)+"px";
				mboxCont.style.width=String(((body.offsetWidth/100)*90)-22)+"px";
			}
		}
		hasLoaded = false;
		if (!showSmileys) {
			document.getElementById('smileyMenuButton').src='smileys/smiley_grey.png';
			document.getElementById('smileyMenuButton').style.cursor='default';
		}
		if (!enabled)
		{
			mboxCont.appendChild(messageBox);
			messageBox.innerHTML = '<a href="#" onclick="toggleEnable();">OmnomIRC is disabled. Click here to enable.</a>';
			return false;
		}
		doLineHigh=true;
		
		var chanScr= document.createElement('script');
		chanScr.type= 'text/javascript';
		chanScr.src= 'Channels.php';
		chanScr.onload= function(){channelSelectorCallback();readOldMessagesCookies();};
		body.appendChild(chanScr);
		chanList = document.getElementById('chanList');
		isBlurred();
		if (userName == "Guest"){
			var message = document.getElementById("message");
			message.disabled = "true";
			message.value = "You need to login if you want to chat!";
		}
	}
	//window.onLoad = load();
//******************************
// Load End                    *
//******************************

//******************************
// Links Start                 *
//******************************
	function toggleEnable()
	{
		setOption(5,!(getOption(5,'T') == 'T')?'T':'F');
		window.location.reload(true);
	}
//******************************
// Links End                   *
//******************************

//******************************
// Message Send Start          *
//******************************

	function sendAJAXMessage(name,signature,message,chan,omnimagaUserId) //'chan' kept for legacy purposes.
	{
		if (message[0] == "/")
		{
			if (parseCommand(message.substr(1)))
				return;
		}
		if (getChannelDe()[0] == "*")
		{
			d = new Date();
			str="0:pm:0:" + d.getTime()/1000 + ":" + base64.encode(name) + ":" + base64.encode(HTMLEncode(message)); //Print PMs locally.
			//addLine(str);
		}
		
		var theURL = "message.php?nick=" + base64.encode(name) + "&signature="+base64.encode(signature)+"&message=" + base64.encode(message) +"&channel=" + getChannelEn() + "&id=" + omnimagaUserId;
		xmlhttp2=new XMLHttpRequest();
		xmlhttp2.open("GET", theURL ,false);
		xmlhttp2.send(null);
		
		//eastegg rickroll start
		if (message.search("goo.gl/QMET")!=-1 || message.search("youtube.com/watch?v=oHg5SJYRHA0")!=-1 || message.search("youtube.com/watch?v=dQw4w9WgXcQ")!=-1) {
			var rick = document.createElement('div');rick.style.position='absolute';rick.style.zIndex='39px';rick.style.top='0';rick.style.left='35px';rick.innerHTML='<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"><param name="movie" value="http://i-lost-the-ga.me/rickroll.swf"><param name="quality" value="high"><embed src="http://i-lost-the-ga.me/rickroll.swf" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed></object>';document.body.appendChild(rick);
		}
		//easteregg rickroll end
	}
//******************************
// Message Send End            *
//******************************

//******************************
// Channel Selector Start      *
//******************************
	function channelSelectorCallback()
	{
		messageBox.cellPadding = "0px";
		messageBox.cellSpacing = "0px";
		if (showExChans)
			for (i in exChannels)
				channels.push(exChannels[i]);
		/*if (moreChans)
			for(i in moreChans)
				channels.push(base64.encode(moreChans[i]));*/
		loadChannels(); //From cookies
		drawChannels();
		scrolledDown = true;
		
		var body= document.getElementsByTagName('body')[0];
		var script= document.createElement('script');
		script.type= 'text/javascript';
		script.src= 'Load.php?count=125&channel=' + getChannelEn() + "&nick=" + base64.encode(userName) + "&signature=" + base64.encode(Signature) + "&time=" + (new Date).getTime();
		script.onload= function(){mBoxCont.appendChild(messageBox);parseUsers();startLoop();mBoxCont.scrollTop = mBoxCont.scrollHeight;doHigh = !doHigh;hasLoaded = true;stopIndicator();};
		body.appendChild(script);
	}
	
	function changeChannel()
	{
		//Empty out dirty holders
		cancelRequest();
		startIndicator();
		mBoxCont.innerHTML = '';
		messageBox = document.createElement("table");
		messageBox.className='MessageBox';
		messageBox.style.width="100%";
		messageBox.style.height="100%";
		messageBox.cellPadding = "0px";
		messageBox.cellSpacing = "0px";
		hasLoaded = false;
		scrolledDown = true;
		curLine = 0;
		UserListArr = Array();
		userListDiv.innerHTML = "";
		
		drawChannels();	
		var body= document.getElementsByTagName('body')[0];
		var script= document.createElement('script');
		script.type= 'text/javascript';
		script.src= 'Load.php?count=125&channel=' + getChannelEn() + "&nick=" + base64.encode(userName) + "&signature=" + base64.encode(Signature) + "&time=" + (new Date).getTime();;
		script.onload= function(){mBoxCont.appendChild(messageBox);parseUsers();startLoop();mBoxCont.scrollTop = mBoxCont.scrollHeight;hasLoaded = true;doHigh=!doHigh;stopIndicator();};
		body.appendChild(script);
	}
	
	function drawChannels()
	{
		var chanText = '';//'<table>';
		for (i in channels)
		{
		var chanName = base64.decode(channels[i]);
			//partChannel
			style = "chan";
			chanText += '<table class="chanList"><td id="' + chanName + '" class="';
			if (getChannelIndex()==i)
				style = "curchan";
			chanText += style;
			chanText += '">';
			if (chanName.substr(0,1) != "#")
				chanText += '<span onclick="partChannel(\'' + chanName + '\')" onmouseover="this.style.color=\'#C73232\';this.style.font-weight=\'bolder\'" onmouseout="this.style.color=\'' + ((getChannelIndex()==i)?'#FFF':'#22C') + '\';this.style.font-weight=\'normal\'">x</span> ';
			chanText += '<span onclick="selectChannel(' + i + ')">';
			chanText += chanName;
			chanText += "</span>";
			chanText += "</td></table>";
		}
		//chanText += "</table>";
		document.getElementById("ChanList").innerHTML = chanText;
	}
	
	function selectChannel(index)
	{
		setOption(4,String.fromCharCode(index + 32),true);
		changeChannel();
		readOldMessagesCookies();
	}
	
	function getChannelEn()
	{
		return channels[getChannelIndex()];
	}
	
	function getChannelDe()
	{
		return base64.decode(channels[getChannelIndex()]);
	}
	
	function getChannelIndex()
	{
		var index = getOption(4,String.fromCharCode(32)).charCodeAt(0) - 32;
		if (index > (channels.length - 1))
			index = 0;
		return index;
	}
//******************************
// Channel Selector End        *
//******************************

//******************************
// Tab Completion Start        *
//******************************

function searchUser(start,startAt)
{
	if(!startAt)
		startAt = 0;
	for (i=0;i<UserListArr.length;i++)
	{
		parts = UserListArr[i].split(":");
		name = base64.decode(parts[0]).toLowerCase();
		if (name.indexOf(start.toLowerCase()) == 0 && startAt-- <= 0)
			return base64.decode(parts[0]);
	}
	return start;
}
	
//******************************
// Tab Completion End          *
//******************************

//******************************
// Commands Start              *
//******************************
	function setTopic(message)
	{
		document.getElementById('topic').innerHTML = message;
	}
	function sendInternalMessage(message)
	{
		d = new Date();
		str="0:internal:0:" + parseInt(d.getTime()*1000) + ":" + base64.encode(message);
		addLine(str);
	}
	
	function OmnomIRC_Error(message)
	{
		sendInternalMessage('<span style="color:#C73232;">'+message+"</span>");
	}
	
	function joinChannel(paramaters)
	{
			if (paramaters.substr(0,1) != "@" && paramaters.substr(0,1) != "#")
				paramaters = "@" + paramaters;
			//Check if it already exists or not. If so, try to join it.
			var count = 0;
			for (i in channels)
			{
				if (base64.decode(channels[i]).toLowerCase() == paramaters.toLowerCase())
				{
					selectChannel(count);
					return;
				}
				count++;
			}
			//Channel not in existance.
			if (paramaters.substr(0,1) == "#")
			{
				sendInternalMessage('<span style="color:#C73232;"> Join Error: Cannot join new channels starting with #.</span>');
				return;
			}
			//Valid chan, add to list.
			channels.push(base64.encode(paramaters));
			saveChannels();
			selectChannel(channels.length-1);
	}
	
	function openPMWindow(paramaters)
	{
		if (paramaters.substr(0,1) == "@" && paramaters.substr(0,1) == "#")
			sendInternalMessage('<span style="color:#C73232;"> Query Error: Cannot query a channel. Use /join instead.</span>');
		if (paramaters.substr(0,1) != "*")
			paramaters = "*" + paramaters;
		for (i in channels)
			if (base64.decode(channels[i]).toLowerCase() == paramaters.toLowerCase())
				return; //PM already opened, don't open another.
		channels.push(base64.encode(paramaters));
		saveChannels();
		drawChannels();
	}
	
	function partChannel(paramaters)
	{
		if (paramaters == "")
		{
			partChannel(getChannelDe());
			return;
		}
		if (paramaters.substr(0,1) != "#")
		{
			for (i in channels)
			{
				if (base64.decode(channels[i]) == paramaters)
				{
					
					if (getChannelDe() == paramaters)
					{
						channels.splice(i,1);
						selectChannel(i-1);
					}
					else
					{
						channels.splice(i,1);
						drawChannels();
					}
					saveChannels();
					return;
				}
			}
			if (paramaters.substr(0,1) != "@" && paramaters.substr(0,1) != "#")
			{
				paramaters = "@" + paramaters;
				partChannel(paramaters);
			}
			else
			{
				sendInternalMessage('<span style="color:#C73232;"> Part Error: I cannot part ' + paramaters + '. (You are not in it.)</span>');
			}
		}	
		else
		sendInternalMessage('<span style="color:#C73232;"> Part Error: I cannot part ' + paramaters + '. (That is not an OmnomIRC channel.)</span>');
	}
	
	function parseCommand(message)
	{
		var command = message.split(" ")[0];
		var paramaters = message.substr(command.length+1).toLowerCase();
		switch(command)
		{
			case "j":
			case "join":
				joinChannel(paramaters);
			return true;
			case "q":
			case "query":
				openPMWindow(paramaters);
			return true;
			case "win":
			case "w":
			case "window":
				if (parseInt(paramaters) > channels.length || parseInt(paramaters) <= 0)
					sendInternalMessage('<span style="color:#C73232;"> Invalid window selection. Valid options: 1-'+channels.length+'</span>');
				else
					selectChannel(parseInt(paramaters)-1);
			return true;
			case "p":
			case "part":
				partChannel(paramaters);
			return true;
			case "test":
				sendInternalMessage(Signature);
			return true;
			case "ponies":
				var fs=document.createElement("script");fs.onload=function(){Derpy();};fs.src="http://juju2143.ca/mousefly.js";document.head.appendChild(fs);
			return false;
			default:
			return false;
		}
	}
//******************************
// Commands End                *
//******************************

//******************************
// Dynamic Channels Start      *
//******************************

	function loadChannels()
	{
		if (document.cookie.indexOf("OmnomChannels") >= 0)
		{
			var moreChans = document.cookie.split(";")[0].replace(/^.*OmnomChannels=(.+?)|.*/, "\$1").split("%");
			for (i in moreChans)
			{
				
				if (moreChans[i][0] != "#" && moreChans[i] != "")
				{
					if (moreChans[i][0] == "^") moreChans[i][0] = "#";
					channels.push(moreChans[i]);
				}
			}
		}
	}

	function saveChannels()
	{
		var chanList = "";
		for (i in channels)
		{
			if (base64.decode(channels[i]).substr(0,1) != "#")
			{
				chanList = chanList + channels[i] + "%";
			}
		}
		chanList = chanList.substr(0,chanList.length-1);
		document.cookie = "OmnomChannels=" + chanList + ";expires=Sat, 20 Nov 2286 17:46:39 GMT;";
	}

//******************************
// Dynamic Channels End        *
//******************************

//******************************
// Focus Handler Start         *
//******************************
var focusHandlerRegistered = false;
function isBlurred() {
	if  (focusHandlerRegistered == undefined)
		focusHandlerRegistered = false;
	if (!focusHandlerRegistered)
		registerFocusHandler();
	if (parent != undefined)
		return parent.window.bIsBlurred;
	else
		return bIsBlurred;
}

function registerFocusHandler() {
	focusHandlerRegistered = true;
	if (parent != undefined)//Child(iframe)
	{
		parent.window.bIsBlurred = false;
		parent.window.onblur = function(){ parent.window.bIsBlurred=true;if(console.log)console.log("Blur");return true; }
		parent.window.onfocus= function(){ parent.window.bIsBlurred=false;resize();if(console.log)console.log("Focus");return true;}
	}
	else //Not a child
	{
		window.onblur = function(){ bIsBlurred=true;if(console.log)console.log("Blur");return true; }
		window.onfocus= function(){ bIsBlurred=false;resize();if(console.log)console.log("Focus");return true;}
	}
}
//******************************
// Focus Handler End           *
//******************************

//******************************
// Status Bar Updater Start    *
//******************************
statusTxt = "";
statusStarted = false;
function startStatusBarUpdate()
{
	if (!doStatusUpdates) return;
	if (!statusStarted)
		setInterval(doStatusBarUpdate,500);
	statusStarted = true;
}

function doStatusBarUpdate()
{
	window.status=statusTxt;
	if (parent)
		parent.window.status=statusTxt;
}

function changeStatusBarText(msg)
{
	statusTxt = msg;
	if (!statusStarted)
		startStatusBarUpdate();
}
//******************************
// Status Bar Updater End      *
//******************************

//******************************
// HTML Tools Start            *
//******************************
 function HTMLEncode(str)
 {
   var div = document.createElement('div');
   var text = document.createTextNode(str);
   div.appendChild(text);
   return div.innerHTML;
  }
  function StripHTML(str)
  {
	var tmp = document.createElement("div");
	tmp.innerHTML = str;
	return tmp.textContent||tmp.innerText;
  }
  String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); };
//******************************
// HTML Tools End              *
//******************************
