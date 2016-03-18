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
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');

function hex2rgba($color, $opacity = false) {
	$default = 'rgb(0,0,0)';
	//Return default if no color provided
	if(empty($color))
		return $default; 
	//Sanitize $color if "#" is provided 
	if ($color[0] == '#' ) {
		$color = substr( $color, 1 );
	}
	//Check if color has 6 or 3 characters and get values
	if (strlen($color) == 6) {
		$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
	} elseif ( strlen( $color ) == 3 ) {
		$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
	} else {
		return $default;
	}
	//Convert hexadec to rgb
	$rgb =  array_map('hexdec', $hex);
	//Check if opacity is set(rgba or rgb)
	if($opacity){
		if(abs($opacity) > 1)
			$opacity = 1.0;
		$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
	} else {
		$output = 'rgb('.implode(",",$rgb).')';
	}
	//Return rgb(a) color string
	return $output;
}
function getColor($c,$condition = true,$default = '#000000'){
	return ($c!==NULL && $condition?$c:$default);
}

if(isset($_GET['theme']) && ($themes = $vars->get('themes')) && isset($themes[$_GET['theme']])){
	$t = $themes[$_GET['theme']];
	header('Content-Type: text/css');
	$lastModified = $t['lastModified'];
	$ifModifiedSince = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false);
	header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lastModified).' GMT');
	header('Cache-Control: public');
	if(strtolower($_SERVER['HTTP_CACHE_CONTROL']) != 'no-cache' && (@strtotime($ifModifiedSince) == $lastModified)){
		header('HTTP/1.1 304 Not Modified');
		exit;
	}
	$bg = getColor($t['colors']['bg']);
	$bg2 = getColor($t['colors']['bg2']);
	$border = getColor($t['colors']['border']);
	$text = getColor($t['colors']['text']);
	$link = getColor($t['colors']['link']);
	$tablink = getColor($t['colors']['tablink'],$t['usetablink'],$link);
	$btn = getColor($t['colors']['btn'],$t['usebtn'],$bg2);
	$btnhover = getColor($t['colors']['btnhover'],$t['usebtnhover'],$bg2);
	$form = getColor($t['colors']['form'],$t['useform'],$bg);
	$popupbg = getColor($t['colors']['popupbg'],$t['usepopupbg'],$bg2);
	$popupborder = getColor($t['colors']['popupborder'],$t['usepopupborder'],$border);
	if(isset($t['sheet']) && $t['sheet'] != ''){
		echo '@import url("'.$t['sheet'].'");';
	}
	echo "
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
		border: 1px solid ".hex2rgba($border,0.4).";
		border-top-color: ".hex2rgba($border,0.8).";
		color: $tablink;
	}

	.chan.curchan {
		background: $bg2;
		border-color: ".hex2rgba($border,0.8).";
		border-bottom-color: $bg2;
		color: $tablink;
	}

	.chan.curchan:hover {
		background: $bg2;
	}

	.chan:hover {
		background: ".hex2rgba($bg2,0.5).";
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
		text-shadow:0 0 4px ".hex2rgba($link,0.8).";
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
		text-shadow:0 0 4px ".hex2rgba($link,0.8).";
	}";
}
