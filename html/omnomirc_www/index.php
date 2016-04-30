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
namespace oirc;
error_reporting(E_ALL);
ini_set('display_errors','1');

$textmode = true; // else omnomirc.php will set json headers
include_once(realpath(dirname(__FILE__)).'/config.php');
if(!$config['info']['installed']){
	if(file_exists(realpath(dirname(__FILE__)).'/updater.php')){
		header('Location: updater.php');
		die();
	}else{
		die('OmnomIRC not installed');
	}
}
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');


function getPage($title,$head,$body,$page){
	global $config,$networks,$you;
	$theme = $networks->get($you->getNetwork());
	$theme = $theme['config']['theme'];
	return '<!DOCTYPE html>'.
			'<html>'.
			'<head>'.
				'<meta name="viewport" content="width=device-width, initial-scale=1.0">'.
				'<meta charset="utf-8" />'.
				'<link rel="icon" type="image/png" href="omni.png">'.
				'<link rel="stylesheet" type="text/css" href="style.css" />'.
				($theme!=-1?'<link rel="stylesheet" type="text/css" href="theme.php?theme='.$theme.'" />':'').
				'<script type="text/javascript" src="btoa.js"></script>'.
				'<script type="text/javascript" src="jquery-1.11.3.min.js"></script>'.
				'<script type="text/javascript" src="omnomirc'.(!isset($config['settings']['minified'])||$config['settings']['minified']?'.min':'').'.js"></script>'.
				($config['websockets']['use'] && $config['settings']['useBot']?'<script type="text/javascript" src="pooledwebsocket.min.js"></script>':'').
				'<title>'.$title.'</title>'.
				'<script type="text/javascript">document.domain="'.$config['settings']['hostname'].'";</script>'.
				$head.
			'</head>'.
			'<body page="'.$page.'">'.
				$body.
			'</body>'.
			'</html>';
}
if(strpos($_SERVER['HTTP_USER_AGENT'],'textmode;')!==false || isset($_GET['textmode'])){
	header('Location: '.getCheckLoginUrl().'&textmode&network='.($you->getNetwork()));
}elseif(isset($_GET['options'])){
/*
Options:
1 - highlight bold (highBold)
2 - highlight red (highRed)
3 - color names (colordNames)
4 - currentChannel (curChan)
5 - enabled (enable)
6 - alternating line highlight (altLines)
7 - enable chrome notifications (browserNotifications)
8 - ding on highlight (ding)
9 - show extra channels (extraChans)
10 - show timestamps (times)
11 - show updates in status bar (statusBar)
12 - show smileys (smileys)
13 - highlight number chars (charsHigh)
14 - hide userlist (hideUserlist)
15 - show scrollbar (scrollBar)
16 - enable main-window scrolling (scrollWheel)
17 - show omnomirc join/part messages (oircJoinPart)
18 - use wysiwyg edtior (wysiwyg)
19 - use simple text decorations (textDeco)
20 - font size (fontSize)
*/
echo getPage('OmnomIRC Options','
<style type="text/css">
body{
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
}
</style>
','
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
</div>
','options');
}elseif(isset($_GET['admin'])){
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
if($config['settings']['experimental']){
	$adminlinks['ex'] = 'Experimental';
}
$nav = '';
foreach($adminlinks as $l => $n){
	if($nav!=''){
		$nav .= ' | ';
	}
	$nav .= '<a data-page="'.$l.'">'.$n.'</a>';
}
echo getPage('OmnomIRC admin panel','','
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
</div>
','admin');
}else{
$hotlinksHTML = '';
$i = true;

foreach($vars->get('hotlinks') as $link){
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
echo getPage('OmnomIRC','
<noscript><meta http-equiv="refresh" content="0;url=index.php?textmode"></noscript>
','
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
<audio id="ding" src="beep.wav" hidden></audio>
','main');

}
