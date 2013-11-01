<?php
include_once(realpath(dirname(__FILE__)).'/config.php');
if(strpos($_SERVER['HTTP_USER_AGENT'],'textmode;')===false && !isset($_GET['textmode'])){
?>
<!--
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
!-->
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>OmnomIRC V2</title>
<link rel="stylesheet" type="text/css" href="style.css" />
<link rel="stylesheet" type="text/css" href="http://www.omnimaga.org/OmnomIRCThemes.php" />
<script src="config.php?js"></script>
<script src="btoa.js"></script>
<script type="text/javascript">
	document.domain=HOSTNAME;
	
	function AJAXSend() {
		Message = document.getElementById("message").value;
		sendAJAXMessage(userName,Signature,Message,"#omnimaga",omnimagaUserId);
		oldMessages.push(Message);
		document.getElementById("message").value = "";
		document.getElementById("message").focus();
		if (oldMessages.length>20)
			oldMessages.shift();
		messageCounter = oldMessages.length;
		if (supports_html5_storage())
			localStorage.setItem("oldMessages-"+getChannelEn(),oldMessages.join("\n"));
		else
			setCookie("oldMessages-"+getChannelEn(),oldMessages.join("\n"),30);
	}
	
	function resize()
	{
		var offset = 42;
		if ("\v" != "v")
		{
			winbg2=document.getElementById("windowbg2");
			msg = document.getElementById("message");
			send = document.getElementById("send");
			newHeight = window.innerHeight - (msg.clientHeight + 14) + "px";
			winbg2.style.height = newHeight;

			messageBox.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.scrollTop = mBoxCont.scrollHeight;
			//msg.style.width = mBoxCont.clientWidth - send.clientWidth - "39" + "px";
			//msg.style.left = "0px";
		}
		else
		{
			page = document.getElementsByTagName("html")[0];
			winbg2=document.getElementById("windowbg2");
			msg = document.getElementById("message");
			send = document.getElementById("send");
			winbg2.style.height = page.clientHeight - msg.clientHeight - offset + "px";
			messageBox.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.style.height = winbg2.clientHeight - offset + "px";
			mBoxCont.scrollTop = mBoxCont.scrollHeight;
			//msg.style.width = mBoxCont.clientWidth - send.clientWidth - "39" + "px";
			//msg.style.left = "0px";
		}
	}
	window.onresize = resize;
</script>
</head>
<body style="overflow:hidden;margin:0px;padding:0px;*height:100%">
<div class="windowbg2" id="windowbg2">
<div id="Channels">
<div id="ChanListButtons">
	<span style="font-size:10pt;" class="arrowButton" onmousedown="menul=setInterval('document.getElementById(\'ChanListCont\').scrollLeft -= 9',50)" onmouseup="clearInterval(menul)" onmouseout="clearInterval(menul)">&#9668;</span>
	<span style="font-size:10pt;" class="arrowButton" onmousedown="menur=setInterval('document.getElementById(\'ChanListCont\').scrollLeft += 9',50)" onmouseup="clearInterval(menur)" onmouseout="clearInterval(menur)">&#9658;</span>
</div>

<div id="ChanListCont">
	<div id="ChanList"></div>
</div>
</div>
<div id="topicbox">
	<div id="TopicButtons">
		<span style="font-size:8pt;" class="arrowButton" onmousedown="menul=setInterval('document.getElementById(\'topicCont\').scrollLeft -= 9',50)" onmouseup="clearInterval(menul)" onmouseout="clearInterval(menul)">&#9668;</span>
		<span style="font-size:8pt;" class="arrowButton" onmousedown="menur=setInterval('document.getElementById(\'topicCont\').scrollLeft += 9',50)" onmouseup="clearInterval(menur)" onmouseout="clearInterval(menur)">&#9658;</span>
	</div>
	<div id="topicCont">
		<div id="topic" style="white-space:nowrap;"></div>
	</div>
</div>
<br/>
<br/>
<br/>
<div id="mboxCont"></div>
	<span class="arrowButtonHoriz3"><div style="font-size:12pt;width:12px;height:9pt;top:0;position:absolute;font-weight:bolder;margin-top:10pt;margin-left:-10pt;" class="arrowButtonHoriz2">&#9650;</div>
	<div style="font-size:12pt;width:12px;height:9pt;top:0;position:absolute;margin-top:10pt;margin-left:-10pt;" onmousedown="downIntM = setInterval('document.getElementById(\'mboxCont\').scrollTop -= 9;scrolledDown=false;',50);" onmouseout="clearInterval(downIntM);" onmouseup="clearInterval(downIntM);"></div></span>
	<span class="arrowButtonHoriz3"><div style="font-size:12pt;width:12px;height:9pt;bottom:9pt;position:absolute;margin-top:-10pt;margin-left:-10pt;font-weight:bolder;" class="arrowButtonHoriz2">&#9660;</div>
	<div style="font-size:12pt;width:12px;height:9pt;bottom:9pt;position:absolute;margin-top:-10pt;margin-left:-10pt;" onmousedown="upIntM = setInterval('document.getElementById(\'mboxCont\').scrollTop += 9;if (mBoxCont.scrollTop+mBoxCont.clientHeight==mBoxCont.scrollHeight)scrolledDown=true;',50);" onmouseout="clearInterval(upIntM);" onmouseup="clearInterval(upIntM);"></div></span>

<div id="UserListContainer">
	<span style="left:10%;position:relative;font-size:6pt;"><table id="hotlinks">
		<?php
		$i = true;
		foreach($hotlinks as $link){
			if($i){
				echo '<tr>';
			}
			echo '<td><a ';
			foreach($link as $key => $value)
				if($key!='inner')
					echo $key.'="'.$value.'" ';
			echo '>'.$link['inner'].'</a></td>';
			if(!$i){
				echo '</tr>';
			}
			$i = !$i;
		}
		if(!$i){
			echo '</tr>';
		}
		?>
	</table></span>
	<div id="UserList" style="position:relative;left:10%;top:1%;width:120%;font-family:verdana,sans-serif;overflow-x:hidden;overflow-y:scroll;">
	</div>
	<span class="arrowButtonHoriz3"><div style="width:12px;height:9pt;top:0pt;position:absolute;font-weight:bolder;margin-top:10pt;" class="arrowButtonHoriz2">&#9650;</div>
	<div style="width:12px;height:9pt;top:0pt;position:absolute;margin-top:10pt;" onmousedown="downInt = setInterval('userListDiv.scrollTop -= 9',50);" onmouseout="clearInterval(downInt);" onmouseup="clearInterval(downInt);"></div></span>
	<span class="arrowButtonHoriz3"><div style="width:12px;height:9pt;top:100%;position:absolute;margin-top:-10pt;font-weight:bolder;" class="arrowButtonHoriz2">&#9660;</div>
	<div style="width:12px;height:9pt;top:100%;position:absolute;margin-top:-10pt;" onmousedown="upInt = setInterval('userListDiv.scrollTop += 9',50);" onmouseout="clearInterval(upInt);" onmouseup="clearInterval(upInt);"></div></span>
	</div>
</div>
</div><img id="smileyMenuButton" src="smileys/smiley.gif" style="cursor:pointer;margin-left:2px;margin-right:2px;" onclick="if(showSmileys){if(document.getElementById('smileyselect').style.display==''){document.getElementById('smileyselect').style.display='none';this.src='smileys/smiley.gif';}else{document.getElementById('smileyselect').style.display='';this.src='smileys/tongue.gif';}}"><form style="Display:inline;" name="irc" action="javascript:void(0)" onSubmit="AJAXSend()"><input autocomplete="off" accesskey="i" type="text" name="message" id="message" size="128" maxlength="256" alt="OmnomIRC" title="OmnomIRC"/><input type="submit" value="Send" id="send" /></form>
<div id="about" style="display:none;"><div style="position: relative; left: -50%;"><span style="position:absolute;z-index:9002;top:1px;right:2px"><a onclick="document.getElementById('about').style.display='none';">Close</a></span>
	<div style="text-align:center;"><img src="omnomirc.png" alt="omnomirc"></div>
	<p>OmnomIRC is developed by <a href="http://www.omnimaga.org" alt="Omnimaga" target="_blank">Omnimaga</a></p>
	<h1>Programmers</h1>
	<ul><li><a href="http://netham45.org/" target="_blank">Netham45</a></li><li><a href="http://www.sorunome.de" target="_blank">Sorunome</a></li><li><a href="http://eeems.ca/" target="_blank">Eeems</a></li></ul>
	<h1>Style</h1>
	<ul><li><a href="http://www.omnimaga.org/index.php?action=profile;u=691" target="_blank">Darl181</a></li></ul>
	<a href="https://github.com/Sorunome/OmnomIRC2" target="_blank">GitHub</a>
</div></div>
<div id="smileyselect" style="display:none;">
	<img src="smileys/smiley.gif" alt="Smiley" title="Smiley" onclick="replaceText(' :)', document.forms.irc.message); return false;">
	<img src="smileys/wink.gif" alt="Wink" title="Wink" onclick="replaceText(' ;)', document.forms.irc.message); return false;">
	<img src="smileys/cheesy.gif" alt="Cheesy" title="Cheesy" onclick="replaceText(' :D', document.forms.irc.message); return false;">
	<img src="smileys/grin.gif" alt="Grin" title="Grin" onclick="replaceText(' ;D', document.forms.irc.message); return false;">
	<img src="smileys/angry.gif" alt="Angry" title="Angry" onclick="replaceText(' &gt;:(', document.forms.irc.message); return false;">
	<img src="smileys/sad.gif" alt="Sad" title="Sad" onclick="replaceText(' :(', document.forms.irc.message); return false;">
	<img src="smileys/shocked.gif" alt="Shocked" title="Shocked" onclick="replaceText(' :o', document.forms.irc.message); return false;">
	<img src="smileys/cool.gif" alt="Cool" title="Cool" onclick="replaceText(' 8)', document.forms.irc.message); return false;">
	<img src="smileys/huh.gif" alt="Huh?" title="Huh?" onclick="replaceText(' ???', document.forms.irc.message); return false;">
	<img src="smileys/rolleyes.gif" alt="Roll Eyes" title="Roll Eyes" onclick="replaceText(' ::)', document.forms.irc.message); return false;">
	<img src="smileys/tongue.gif" alt="Tongue" title="Tongue" onclick="replaceText(' :P', document.forms.irc.message); return false;">
	<img src="smileys/embarrassed.gif" alt="Embarrassed" title="Embarrassed" onclick="replaceText(' :-[', document.forms.irc.message); return false;">
	<img src="smileys/lipsrsealed.gif" alt="Lips Sealed" title="Lips Sealed" onclick="replaceText(' :-X', document.forms.irc.message); return false;">
	<img src="smileys/undecided.gif" alt="Undecided" title="Undecided" onclick="replaceText(' :-\\', document.forms.irc.message); return false;">
	<img src="smileys/kiss.gif" alt="Kiss" title="Kiss" onclick="replaceText(' :-*', document.forms.irc.message); return false;">
	<img src="smileys/cry.gif" alt="Cry" title="Cry" onclick="replaceText(' :\'(', document.forms.irc.message); return false;">
	<img src="smileys/thumbsupsmiley.gif" alt="Good job" title="Good job" onclick="replaceText(' :thumbsup:', document.forms.irc.message); return false;">
	<img src="smileys/evil.gif" alt="Evil" title="Evil" onclick="replaceText(' &gt;:D', document.forms.irc.message); return false;">
	<img src="smileys/shocked2.gif" alt="shocked" title="shocked" onclick="replaceText(' O.O', document.forms.irc.message); return false;">
	<img src="smileys/azn.gif" alt="Azn" title="Azn" onclick="replaceText(' ^-^', document.forms.irc.message); return false;">
	<img src="smileys/alien2.gif" alt="Alien" title="Alien" onclick="replaceText(' &gt;B)', document.forms.irc.message); return false;">
	<img src="smileys/banghead.gif" alt="Frustrated" title="Frustrated" onclick="replaceText(' :banghead:', document.forms.irc.message); return false;">
	<img src="smileys/ange.gif" alt="Angel" title="Angel" onclick="replaceText(' :angel:', document.forms.irc.message); return false;">
	<img src="smileys/blah.gif" alt="Blah" title="Blah" onclick="replaceText(' ._.', document.forms.irc.message); return false;">
	<img src="smileys/devil.gif" alt="Devil" title="Devil" onclick="replaceText(' :devil:', document.forms.irc.message); return false;">
	<img src="smileys/dry.gif" alt="&lt;_&lt;" title="&lt;_&lt;" onclick="replaceText(' &lt;_&lt;', document.forms.irc.message); return false;">
	<img src="smileys/evillaugh.gif" alt="Evil Laugh" title="Evil Laugh" onclick="replaceText(' :evillaugh:', document.forms.irc.message); return false;">
	<img src="smileys/fou.gif" alt="Crazy" title="Crazy" onclick="replaceText(' :crazy:', document.forms.irc.message); return false;">
	<img src="smileys/happy0075.gif" alt="You just lost the game" title="You just lost the game" onclick="replaceText(' :hyper:', document.forms.irc.message); return false;">
	<img src="smileys/love.gif" alt="Love" title="Love" onclick="replaceText(' :love:', document.forms.irc.message); return false;">
	<img src="smileys/mad.gif" alt="Mad" title="Mad" onclick="replaceText(' :mad:', document.forms.irc.message); return false;">
	<img src="smileys/smiley_woot.gif" alt="w00t" title="w00t" onclick="replaceText(' :w00t:', document.forms.irc.message); return false;">
	<img src="smileys/psychedelicO_O.gif" alt="I must have had too much radiation for breakfast..." title="I must have had too much radiation for breakfast..." onclick="replaceText(' *.*', document.forms.irc.message); return false;">
	<img src="smileys/bigfrown.gif" alt="Big frown" title="Big frown" onclick="replaceText(' D:', document.forms.irc.message); return false;">
	<img src="smileys/XD.gif" alt="Big smile" title="Big smile" onclick="replaceText(' XD', document.forms.irc.message); return false;">
	<img src="smileys/X_X.gif" alt="x.x" title="x.x" onclick="replaceText(' x.x', document.forms.irc.message); return false;">
	<img src="smileys/ninja.gif" alt="Get Ninja'd" title="Get Ninja'd" onclick="replaceText(' :ninja:', document.forms.irc.message); return false;">
</div>
<div id="indicator" style="position:absolute;z-index:44;margin:0;padding:0;top:0;right:0;"></div>
<div id="lastSeenCont" style="display:none;"></div>

<script src="Omnom_Options.js"></script>
<script src="Omnom_Parser.js"></script>
<script src="Omnom_Tab.js"></script>
<script src="Omnom_Misc.js"></script>

<script type="text/javascript">
	startIndicator();
	
	function signCallback(sig,nick,id) {
		Signature = sig;
		userName = nick;
		omnimagaUserId = id;
		load();
	}
	resize();
	var body= document.getElementsByTagName('body')[0];
	var script= document.createElement('script');
	script.type= 'text/javascript';
	script.src=<?php if(isset($_COOKIE[$securityCookie])) echo '"'.$checkLoginUrl.'?sid='.urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$securityCookie]))).'";'."\n"; ?>
	body.appendChild(script);
</script>
<audio id="ding" src="beep.wav" hidden></audio>
</body>
</html>
<?php
}else{
	if(isset($_COOKIE[$securityCookie]))
		header('Location: '.$checkLoginUrl.'?textmode&sid='.urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$securityCookie]))));
	else
		header('Location: '.$checkLoginUrl.'?textmode');
}
?>
