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
namespace oirc\skins\lobster;
function getPage(){
	$hotlinksHTML = '';
	$i = true;

	foreach(\oirc\Vars::get('hotlinks') as $link){
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
	<div id="lastSeenCont" class="popup"></div>';
	return array(
		'html' => $html,
		'head' => '<noscript><meta http-equiv="refresh" content="0;url=index.php?textmode"></noscript>',
		'js' => array(
			array(
				'file' => 'options.js',
				'minified' => true
			),
			array(
				'file' => 'client.js',
				'minified' => true
			)
		),
		'css' => array(
			array(
				'file' => 'style.css',
				'minified' => true
			)
		)
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
			$("#options").append($.map([0,1],function(mod){
				var j = 0;
				return $("<table>").addClass("optionsTable").append(
					$.map(oirc.options.getAll(true),function(o,i){
						if(o.hidden){
							return;
						}
						return ((j++%2)==mod?$("<tr>").append(
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
				'file' => 'options.js',
				'minified' => true
			),
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
			array(
				'file' => 'style.css',
				'minified' => true
			)
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
				'file' => 'options.js',
				'minified' => true
			),
			array(
				'file' => 'admin.js',
				'minified' => true
			)
		),
		'css' => array(
			array(
				'file' => 'style.css',
				'minified' => true
			)
		)
	);
}

function getTheme($t){
	$bg = \oirc\getColor($t['colors']['bg']);
	$bg2 = \oirc\getColor($t['colors']['bg2']);
	$border = \oirc\getColor($t['colors']['border']);
	$text = \oirc\getColor($t['colors']['text']);
	$link = \oirc\getColor($t['colors']['link']);
	$tablink = \oirc\getColor($t['colors']['tablink'],$t['usetablink'],$link);
	$btn = \oirc\getColor($t['colors']['btn'],$t['usebtn'],$bg2);
	$btnhover = \oirc\getColor($t['colors']['btnhover'],$t['usebtnhover'],$bg2);
	$form = \oirc\getColor($t['colors']['form'],$t['useform'],$bg);
	$popupbg = \oirc\getColor($t['colors']['popupbg'],$t['usepopupbg'],$bg2);
	$popupborder = \oirc\getColor($t['colors']['popupborder'],$t['usepopupborder'],$border);
	$css = '';
	if(isset($t['sheet']) && $t['sheet'] != ''){
		$css .= '@import url("'.$t['sheet'].'");';
	}
	$css .= "
	#UserListContainer,
	#topicbox,
	#scrollBar,
	td.curchan,
	#scrollBarLine,
	#UserListInnerCont,
	#logsHeader,
	#textDecoForm,
	#pickUsernamePopup,
	#logDatePicker,
	.lineHigh {
		background: $bg2;
		border-color: $border;
		color: $text;
	}
	.dateSeperator > td {
		border-color: $border;
	}
	.popup {
		background: $popupbg;
		border-color: $popupborder;
		color: $text;
	}
	#scrollBar:active{
		box-shadow: 0 0 4px $border;
	}

	#Channels {
		box-shadow: inset 0 -1px $border;
	}

	.chan {
		border: 1px solid ".\oirc\hex2rgba($border,0.4).";
		border-top-color: ".\oirc\hex2rgba($border,0.8).";
		color: $tablink;
	}

	.chan.curchan {
		background: $bg2;
		border-color: ".\oirc\hex2rgba($border,0.8).";
		border-bottom-color: $bg2;
		color: $tablink;
	}

	.chan.curchan:hover {
		background: $bg2;
	}

	.chan:hover {
		background: ".\oirc\hex2rgba($bg2,0.5).";
	}
	#scrollbar:active{
		box-shadow: 0 0 4px $border;
	}
	body,
	#scrollBar:hover,
	#scrollBar:active,
	#UserListInnerCont:hover{
		background: $bg;
	}
	body,
	#UserListInnerCont,
	.irc-date,
	td.curchan,
	.highlight,
	.optionsTable .option.selected{
		color:$text;
	}
	a,a:link,a:visited,a:hover,a:active{
		border-color:$link;
		color:$link;
	}
	a:hover{
		text-shadow:0 0 4px ".\oirc\hex2rgba($link,0.8).";
	}
	span#message,input#message,input,select{
		background:$form;
		color:$text;
		border:1px solid $border;
	}
	button,#send{
		background:$btn;
		color:$text;
		border:1px solid $border;
	}
	.logDatePickerDay.current{
		background:$btn;
	}
	button:hover,#send:hover,.logDatePickerDay:hover{
		background:$btnhover;
	}
	.optionsTable .option{
		color:$link;
		border-right-color:$text;
	}
	.optionsTable .option:hover{
		text-shadow:0 0 4px ".\oirc\hex2rgba($link,0.8).";
	}";
	return $css;
}
\oirc\Skins::theme('lobster','\oirc\skins\lobster\getTheme');
