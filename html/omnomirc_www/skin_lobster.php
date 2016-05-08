<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2016 Sorunome

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
namespace oirc\skin;
function getPage(){
	$hotlinksHTML = '';
	$i = true;

	foreach(\oirc\OIRC::$vars->get('hotlinks') as $link){
		if($i){
			$hotlinksHTML .= '<tr>';
		}
		$hotlinksHTML .= '<td><a ';
		foreach($link as $key => $value)
			if($key!='inner')
				$hotlinksHTML .= $key.'="'.$value.'" ';
		$hotlinksHTML .= '>'.$link['inner'].'</a></td>';
		if(!$i){
			$hotlinksHTML .= '</tr>';
		}
		$i = !$i;
	}
	if($i){
		$hotlinksHTML .= '<tr style="display:none;" id="adminLink"><td><a href="?admin">Admin</a></td>';
	}else{
		$hotlinksHTML .= '<td style="display:none;" id="adminLink"><a href="?admin">Admin</a></td>';
	}
	$hotlinksHTML .= '</tr>';
	
	$colorButtonsHTML = '<br>';
	for($i = 0;$i < 16;$i++){
		$colorButtonsHTML .= '<span class="colorbutton bg-'.$i.'" data-num="'.$i.'"></span>';
		if(($i+1) % 4 == 0){
			$colorButtonsHTML .= '<br>';
		}
	}
	
	$html = '
	<div id="header">
		<div id="chattingHeader">
			<div id="Channels">
				<div id="ChanListButtons">
					<span style="font-size:1.1em;" class="arrowButton" id="arrowLeftChan">&#9668;</span>
					<span style="font-size:1.1em;" class="arrowButton" id="arrowRightChan">&#9658;</span>
				</div>
				<div id="ChanListCont">
					<div id="ChanList"></div>
				</div>
			</div>
			<div id="topicbox">
				<div id="TopicButtons">
					<span style="font-size:0.8em;" class="arrowButton" id="arrowLeftTopic">&#9668;</span>
					<span style="font-size:0.8em;" class="arrowButton" id="arrowRightTopic">&#9658;</span>
				</div>
				<div id="topicCont">
					<div id="topic" style="white-space:nowrap;"></div>
				</div>
			</div>
		</div>
		<div id="logsHeader" style="display:none;">
			<b>Log viewer</b> (<a id="logCloseButton">Close</a>)<br>
			Channel: <span id="logChanIndicator"></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Day: <span id="logDateCont">
				<input type="text" id="logDate" readonly="readonly">
				<div id="logDatePicker" style="display:none;"></div>
			</span> <a id="logGoButton">Go</a>
		</div>
	</div>
	
	<div id="mBoxCont">
		<table id="MessageBox" cellpadding="0px" cellspacing="0px">
		</table>
	</div>
	<div id="UserListContainer">
		<table id="hotlinks">
			'.$hotlinksHTML.'
		</table>
		<div id="UserListInnerCont"><div id="UserList"></div></div>
	</div>
	
	<div id="footer">
		<img id="smileyMenuButton" src="smileys/smiley.gif" style="margin-left:2px;margin-right:2px;">
		<div id="textDecoForm" style="display:none;">
			<button id="textDecoFormBold" style="font-weight:bold;">B</button>
			<button id="textDecoFormItalic" style="font-style:italic;">I</button>
			<button id="textDecoFormUnderline" style="text-decoration:underline;">U</button><br>
			'.$colorButtonsHTML.'
		</div>
		<div id="loginForm" style="display:none;">
			<button style="display:none;">Pick Username</button>
			<div id="pickUsernamePopup" style="display:none;">
				Username: <input type="text"> <button>Go</button><br>Remember: <input type="checkbox" checked="checked">
			</div>
			<span id="guestName" style="display:none;"></span>
		</div>
		<form style="Display:inline;" name="irc" action="javascript:void(0)" id="sendMessage">
			<span contenteditable="true" accesskey="i" id="message"></span>
			<input type="submit" value="Send" id="send" />
		</form>
		<div id="icons">
			<span id="warnings" style="display:none;">
			<span class="count">0</span><span class="icon"></span>
			</span>
			<span id="errors" style="display:none;">
			&nbsp;<span class="count">0</span><span class="icon"></span>
			</span>
		</div>
	</div>
	<div id="about"><div class="popup"><span style="position:absolute;z-index:9002;top:1px;right:2px"><a onclick="document.getElementById(\'about\').style.display=\'none\';">Close</a></span>
		<div style="text-align:center;"><a href="https://omnomirc.omnimaga.org/" target="_blank"><img src="omnomirc.png" alt="OmnomIRC"></a></div>
		<p><a href="https://omnomirc.omnimaga.org/" target="_blank">OmnomIRC</a> is developed by <a href="https://www.omnimaga.org" alt="Omnimaga" target="_blank">Omnimaga</a></p>
		<p>Found an issue/bug? <a href="https://github.com/Sorunome/OmnomIRC/issues" target="_blank">Report it!</a></p>
		<h1>Programmers</h1>
		<ul><li><a href="http://netham45.org/" target="_blank">Netham45</a></li><li><a href="https://www.sorunome.de" target="_blank">Sorunome</a></li><li><a href="http://eeems.ca/" target="_blank">Eeems</a></li></ul>
		<h1>Style</h1>
		<ul><li><a href="https://www.omnimaga.org/profile/Darl181" target="_blank">Darl181</a></li></ul>
		<a href="https://omnomirc.omnimaga.org/" target="_blank">Homepage</a> | <a href="https://github.com/Sorunome/OmnomIRC" target="_blank">GitHub</a>
	</div></div>
	<div id="smileyselect" class="popup">
	</div>
	<div id="lastSeenCont" class="popup"></div>
	<audio id="ding" src="beep.wav" hidden></audio>';
	return array(
		'html' => $html,
		'head' => '<noscript><meta http-equiv="refresh" content="0;url=index.php?textmode"></noscript>',
		'js' => array(
			array(
				'file' => 'oirc_client.js',
				'minified' => true
			)
		),
		'css' => array('style.css')
	);
}

function getOptions(){
	$html = '
	<div style="font-size:1.8em;font-weight:bold;margin-top:0.3em;">OmnomIRC Options</div>
	<div id="options"></div>
	<div style="top:100%;margin-top:-2.5em;position:absolute;"><a href="index.php"><span style="font-size:3em;">&#8592;</span><span style="font-size:2.1em;top:-0.1em;position:relative;">Back<span></a></div>
	<div id="icons" style="right:5px;">
		<span id="warnings" style="display:none;">
		<span class="count">0</span><span class="icon"></span>
		</span>
		<span id="errors" style="display:none;">
		&nbsp;<span class="count">0</span><span class="icon"></span>
		</span>
	</div>';
	$css = 'body{
		font-size: 9pt;
		font-family:verdana,sans-serif;
	}
	td
	{
		width:auto;
		height:1.2em;
		white-space: inherit;
		word-wrap: inherit;
		line-height:1.2em;
	}
	table
	{
		height:auto;
	}
	tr td:nth-child(4) {
		padding-left:1em;
	}
	tr td:nth-child(2) {
		border-right:0.1em solid;
	}
	tr td:nth-child(5) {
		border-right:0.1em solid;
	}
	#options {
		overflow-y:auto;
	}';
	$js = '
	$(function(){
		var oirc = new OmnomIRC();
		oirc.settings.fetch(function(){
			oirc.page.changeLinks();
			$("#options").height($(window).height() - 75);
			$(window).resize(function(){
				if(!(navigator.userAgent.match(/(iPod|iPhone|iPad)/i) && navigator.userAgent.match(/AppleWebKit/i))){
					$("#options").height($(window).height() - 75);
				}
			});
			$("body").css("font-size",oirc.options.get("fontSize").toString(10)+"pt");
			$("#options").append($.map([true,false],function(alternator){
				return $("<table>").addClass("optionsTable").append(
					$.map(oirc.options.getAll(true),function(o,i){
						if(o.hidden){
							return;
						}
						return ((alternator = !alternator)?$("<tr>").append(
							$.merge(
							[$("<td>")
								.text(o.disp)],
							(o.handler===undefined?[
							$("<td>")
								.addClass("option "+(oirc.options.get(i)?"selected":""))
								.text("Yes")
								.click(function(){
									if(!oirc.options.get(i)){
										if((o.before!==undefined && o.before()) || o.before===undefined){
											oirc.options.set(i,true);
											$(this).addClass("selected").next().removeClass("selected");
										}
									}
								}),
							$("<td>")
								.addClass("option "+(!oirc.options.get(i)?"selected":""))
								.text("No")
								.click(function(){
									if(oirc.options.get(i)){
										oirc.options.set(i,false);
										$(this).addClass("selected").prev().removeClass("selected");
									}
								})]:o.handler()))
						):"");
					})
				);
			}));
		});
	});
	';
	return array(
		'html' => $html,
		'title' => 'OmnomIRC Options',
		'js' => array(
			array(
				'type' => 'inline',
				'file' => $js
			)
		),
		'css' => array(
			array(
				'type' => 'inline',
				'file' => $css
			),
			'style.css'
		)
	);
}

function getAdmin(){
	$adminlinks = array(
		'index' => 'Index',
		'themes' => 'Theme',
		'channels' => 'Channels',
		'hotlinks' => 'Hotlinks',
		'smileys' => 'Smileys',
		'networks' => 'Networks',
		'sql' => 'SQL',
		'ws' => 'WebSockets',
		'misc' => 'Misc'
	);
	if(\oirc\OIRC::$config['settings']['experimental']){
		$adminlinks['ex'] = 'Experimental';
	}
	$nav = '';
	foreach($adminlinks as $l => $n){
		if($nav!=''){
			$nav .= ' | ';
		}
		$nav .= '<a data-page="'.$l.'">'.$n.'</a>';
	}
	$html = '
	<div id="container">
	<div style="font-weight:bold;">OmnomIRC Admin Panel</div>
	<div id="adminNav">'.$nav.'</div>
	<div id="adminContent" style="overflow-y:auto;">Loading...</div>
	</div>
	<div id="adminFooter"><a href="index.php">Back to OmnomIRC</a></div>

	<div id="icons" style="right:5px;">
		<span id="warnings" style="display:none;">
		<span class="count">0</span><span class="icon"></span>
		</span>
		<span id="errors" style="display:none;">
		&nbsp;<span class="count">0</span><span class="icon"></span>
		</span>
	</div>';
	return array(
		'html' => $html,
		'title' => 'OmnomIRC admin panel',
		'js' => array(
			array(
				'file' => 'admin.js',
				'minified' => true
			)
		),
		'css' => array('style.css')
	);
}