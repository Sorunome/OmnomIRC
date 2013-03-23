
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

messageList = Array();
UserListArr = Array();
curLine = 0;
messageBox = document.getElementById("MessageBox");
mBoxCont = document.getElementById("mBoxCont");
Userlist = Array();
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
	
	function sendRequest()
	{
		url = "Update.php?lineNum=" + curLine + "&channel=" + getChannelEn() + "&nick=" + base64.encode(parent.userName) + "&signature=" + base64.encode(parent.Signature);
		xmlhttp.open("GET",url,true);
		xmlhttp.send(null);
	}
	
	function getIncomingLine()
	{
		if (xmlhttp.readyState==4 || xmlhttp.readyState=="complete") { 
			if (xmlhttp.status == 200) addLine(xmlhttp.responseText); //Filter out 500s from timeouts
			sendRequest();
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
	function addLine(message)
	{
		if (!message || message == null || message == undefined)
			return;
		doScroll = false;
		if (mBoxCont.clientHeight + mBoxCont.scrollTop > mBoxCont.scrollHeight - 50) doScroll = true;
		messageBox = document.getElementById("MessageBox");
		if ("\v" != "v") //If IE, take the slow but sure route (This is enough of a performance hit in all browsers to use the optimized code if possible. Also, IE can go fuck itself.)
			mBoxCont.innerHTML = '<table style="width:100%" class="messageBox" id="MessageBox">' + messageBox.innerHTML + parseMessage(message) + '</table>';
		else //If not IE, yay!
			messageBox.innerHTML = messageBox.innerHTML + parseMessage(message);
		if (doScroll)mBoxCont.scrollTop = mBoxCont.scrollHeight + 50;
		lnNum = message.split(":")[0];
		if (lnNum > curLine)
			curLine = lnNum;
	}
	
	function parseMessage(message)
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
			if (parts[5].toLowerCase().indexOf(parent.userName.toLowerCase().substr(0,4)) >= 0 && hasLoaded && notifications && parts[4].toLowerCase() != "spybot45")
			{
				showNotification("<" + parts[4] + "> " + parts[5]);
				if (highDing)
				{
					document.getElementById('ding').play();
				}
			}
		}
		if ((type == "message" || type == "action") && parts[4].toLowerCase() != "spybot45")
		{
			parsedMessage = parseHighlight(parsedMessage);
		}
		retval = "";
		if (type != "message" && type != "pm")
			retVal='<td class="name">';
		switch (type)
		{
			case "join":
				retval += '<td class="name"> </td><td class="join"> * ' + name + " has joined "+getChannelDe() + "</td>";
				addUserJoin(parts[4],online);
				if (online == "1")
					return "";
				break;
			case "part":
				retval += '<td class="name"> </td><td class="part"> * ' + name + " has left "+getChannelDe()+" (" + parsedMessage + ")" + "</td>";
				removeUser(parts[4]);
				if (online == "1")
					return "";
				break;
			case "quit":
				retval += '<td class="name"> </td><td class="quit"> * ' + name + " has quit IRC (" + parsedMessage + ")" + "</td>";
				removeUser(parts[4]);
				break;
			case "kick":
				retval += '<td class="name"> </td><td class="kick"> * ' + name + " has kicked " + parts[5] + " from "+getChannelDe()+" (" + parts[6] + ")" + "</td>";
				removeUser(parts[4]);
				break;
			case "message":	
				retval = '<span class="message"><td class="name">' + name + "</td><td>" + parsedMessage + "</td></span>";break;
			case "action":
				retval += '<td class="name"> </td><td class="action"> * ' + name + " " + parsedMessage + "</td>";break;
			case "mode":
				retval += '<td class="name"> </td><td class="mode"> * ' + name + " set "+getChannelDe()+" mode " + parts[5] + "</td>";break;
			case "nick":
				retval += '<td class="name"> </td><td class="nick"> * ' + name + " has changed his nick to " + parsedMessage + "</td>";
				removeUser(parts[4]);
				addUserJoin(parts[5],online);
				break;
			case "topic":
				retval += '<td class="name"> </td><td class="topic"> * ' + name + " has changed the topic to " + parsedMessage + "</td>";break;
			case "internal":
				retval += parts[4];
			break;
			case "pm":
				if (getChannelDe().toLowerCase() != ("*" + parts[4]).toLowerCase() && parts[4] != parent.userName)//Not in the PM window
				{
					retval = '<span class="pm"><td class="name">(PM)' + name + "</td><td>" + parsedMessage + "</td></span>";
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
					retval = '<td class="name">' + name + "</td><td>" + parsedMessage + "</td></span>"; //In the PM window
				}
			break;
			case "curline":
				return "";
			break;
			case "highlight":
				if (parts[6].toLowerCase() == "spybot45") return "";
				document.getElementById(parts[4]).style.color="#C22"; //This call will fail if they aren't in the chan. Crude, but effective.
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
		if (type != "message" && type != "pm")
			retVal+="</td>";
		pretag = '<tr style="width:100%;">';
		doHigh = !doHigh;
		if (lineHigh && doHigh)
		{
			pretag = '<tr style="width:100%;" class="linehigh">';
		}
		doLineHigh = !doLineHigh;
		
		d = new Date(parts[3]*1000);
		retval = pretag + '<td class="irc-date">[' + d.toLocaleTimeString() + ']</td>'  + retval + "</tr>";
		
		return retval;
	}
	
	function parseColors(colorStr)
	{
		if (!colorStr || colorStr == null || colorStr == undefined) return;
		colorStr = clickable_links(colorStr);
		lcount = 0;
		a = colorStr;
		arrayResults = Array();
		arrayResults2 = Array();
		while(colorStr.indexOf("\x03") >= 0)
		{
			arrayResults = colorStr.match("(^.*)\x03([0-9]{1,2}),([0-9]{1,2})(.*)");
			arrayResults2 = colorStr.match("(^.*)\x03([0-9]{1,2})(.*)");
			arrayResults3 = colorStr.match("(^.*)\x03([0-9]{1,2})(.*)");
			if(arrayResults != null && arrayResults.length > 4) //FG & BG
			{
				b = arrayResults;
				colorStr = arrayResults[1]+'<span class="fg-'+arrayResults[2]*1 +'"><span class="bg-'+arrayResults[3]*1 +'">'+arrayResults[4]+"</span></span>";
				lcount+=2;
			}
			else if(arrayResults2 != null && arrayResults2.length > 3) //FG Only
			{
				colorStr = arrayResults2[1]+'<span class="fg-' + arrayResults2[2]*1 + '">'+arrayResults2[3]+"</span></span>";
				lcount++;
			}
			else //We have a color control character w/o a color, most clients interperet this as clear colors.
			{
				for(lcount;lcount;lcount--)
					colorStr=colorStr+"</span>";
				colorStr = colorStr.replace(/\x03/,"");
				lcount--;
			}
			lcount++;
		}
		for(;lcount>=0;lcount--)
			colorStr=colorStr+"</span>";
		colorStr=colorStr+"</span>";
		
		/*Strip codes*/
		colorStr = colorStr.replace(/(\x03|\x02|\x1F|\x09|\x0F)/g,"");

		return(colorStr);
	}
	function parseHighlight(text)
	{
		if (text.toLowerCase().indexOf(parent.userName.toLowerCase().substr(0,4)) >= 0)
		{
			style = "";
			if (highRed)
				style = style + "color:#C73232;";
			if (highBold)
				style = style + "font-weight:bold;";
			return '<span class="highlight" style="' + style + '">' + text + "</span>";
		}
		return text;
	}
	function clickable_links(text) 
	{
		if (!text || text == null || text == undefined) return;
		text = text.replace(/http:\/\/www.omnimaga.org\//g,"h111://www.omnimaga.org/");
		text = text.replace(/http:\/\/omniurl.tk\//g,"h111://omniurl.tk/");
		text = text.replace(/((h111:\/\/(www.omnimaga.org\/|omniurl.tk))[-a-zA-Z0-9@:;%_+.~#?&//=]+)/, '<a target="_TOP" href="$1">$1</a>');
		text = text.replace(/(((f|ht)(tp|tps):\/\/)[-a-zA-Z0-9@:;%_+.~#?&\/\/=]+)/g,'<a target="_blank" href="$1">$1</a>');
		text = text.replace(/([[ ]|[{}])(www.[-a-zA-Z0-9@:;%_+.~#?&//=]+)/,'$1 <a target="_blank" href="http://$2">$2</a>');
		text = text.replace(/h111/g,"http");
		return text;
	}
	function clickable_names(name,isOnline)
	{
		if (isOnline == "1")
			return '<a target="_TOP" href="http://www.omnimaga.org/index.php?action=ezportal;sa=page;p=13&userSearch=' + name + '">' + colored_names(name) + '</a>';
		return colored_names(name);
	}
	function colored_names(name)
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
				userText = userText + '<a target="_TOP" href="http://www.omnimaga.org/index.php?action=ezportal;sa=page;p=13&userSearch=' +base64.decode(parts[0]) + 
				'"><img src="http://netham45.org/irc/efnet/ouser.png" alt="Omnimaga User" title="Omnimaga User" border=0 width=8 height=8 />' + base64.decode(parts[0]) + '</a><br/>';
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
		hasLoaded = false;
		if (!enabled)
		{
			messageBox.innerHTML = '<a href="#" onclick="toggleEnable();">OmnomIRC is disabled. Click here to enable.</a>';
			return false;
		}
		doLineHigh=true;
		var body= document.getElementsByTagName('body')[0];
		var chanScr= document.createElement('script');
		chanScr.type= 'text/javascript';
		chanScr.src= 'Channels.php';
		chanScr.onload= function(){channelSelectorCallback();};
		body.appendChild(chanScr);
		chanList = document.getElementById('chanList');

	}
	window.onLoad = load();
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

	function sendAJAXMessage(name,signature,message,chan) //'chan' kept for legacy purposes.
	{
		if (message[0] == "/")
		{
			if (parseCommand(message.substr(1)))
				return;
		}
		if (getChannelDe()[0] == "*")
		{
			d = new Date();
			str="0:pm:0:" + base64.encode(d.getTime()) + ":" + base64.encode(name) + ":" + base64.encode(HTMLEncode(message)); //Print PMs locally.
			addLine(str);
		}
		var theURL = "message.php?nick=" + base64.encode(name) + "&signature="+base64.encode(signature)+"&message=" + base64.encode(message) +"&channel=" + getChannelEn();
		xmlhttp2=new XMLHttpRequest();
		xmlhttp2.open("GET", theURL ,false);
		xmlhttp2.send(null);
	}
//******************************
// Message Send End            *
//******************************

//******************************
// Channel Selector Start      *
//******************************
	function channelSelectorCallback()
	{
		
		if (showExChans)
			for (i in exChannels)
				channels.push(exChannels[i]);
		/*if (parent.moreChans)
			for(i in parent.moreChans)
				channels.push(base64.encode(parent.moreChans[i]));*/
		loadChannels(); //From cookies
		drawChannels();
		
		
		var body= document.getElementsByTagName('body')[0];
		var script= document.createElement('script');
		script.type= 'text/javascript';
		script.src= 'Load.php?count=50&channel=' + getChannelEn() + "&nick=" + base64.encode(parent.userName) + "&signature=" + base64.encode(parent.Signature) + "&time=" + (new Date).getTime();;
		script.onload= function(){parseUsers();startLoop();mBoxCont.scrollTop = mBoxCont.scrollHeight;hasLoaded = true;};
		body.appendChild(script);
	}
	
	function changeChannel()
	{
		//Empty out dirty holders
		mBoxCont.innerHTML = '<table style="width:100%" class="messageBox" id="MessageBox"><tr><td>OmnomIRC</td></tr></table>';
		hasLoaded = false;
		curLine = 0;
		UserListArr = Array();
		userListDiv.innerHTML = "";
		
		drawChannels();	
		var body= document.getElementsByTagName('body')[0];
		var script= document.createElement('script');
		script.type= 'text/javascript';
		script.src= 'Load.php?count=50&channel=' + getChannelEn() + "&nick=" + base64.encode(parent.userName) + "&signature=" + base64.encode(parent.Signature) + "&time=" + (new Date).getTime();;
		script.onload= function(){parseUsers();startLoop();mBoxCont.scrollTop = mBoxCont.scrollHeight;hasLoaded = true;};
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
	function sendInternalMessage(message)
	{
		d = new Date();
		str="0:internal:0:" + base64.encode(d.getTime()) + ":" + base64.encode(message);
		addLine(str);
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
				sendInternalMessage(parent.Signature);
			return true;
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
				if (moreChans[i][0] != "#" && moreChans[i] != "")
					channels.push(moreChans[i]);
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
 function HTMLEncode(str) {
   var div = document.createElement('div');
   var text = document.createTextNode(str);
   div.appendChild(text);
   return div.innerHTML;
  }
  String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); };