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


include_once(realpath(dirname(__FILE__)).'/skin_lobster.php');

if(strpos($_SERVER['HTTP_USER_AGENT'],'textmode;')!==false || isset($_GET['textmode'])){
	header('Location: '.getCheckLoginUrl().'&textmode&network='.($you->getNetwork()));
	exit;
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
	$page = \oirc\skin\getOptions();
	if(!$page){
		include_once(realpath(dirname(__FILE__)).'/skin_lobster.php');
		$page = \oirc\skin\getOptions();
	}
}elseif(isset($_GET['admin'])){
	$page = \oirc\skin\getAdmin();
	if(!$page){
		include_once(realpath(dirname(__FILE__)).'/skin_lobster.php');
		$page = \oirc\skin\getAdmin();
	}
}else{
	$page = \oirc\skin\getPage();
	if(!$page){
		include_once(realpath(dirname(__FILE__)).'/skin_lobster.php');
		$page = \oirc\skin\getPage();
	}
}
// combind with default options to make sure they always exist
$page = array_merge(array(
	'html' => '',
	'title' => 'OmnomIRC',
	'head' => '',
	'favicon' => 'omni.png',
	'js' => array(),
	'css' => array()
),$page);

// now it's time to construct the page!

foreach($page['js'] as $js){
	if(!is_array($js)){
		$js = array(
			'file' => $js
		);
	}
	$js = array_merge(array(
		'type' => 'file',
		'file' => '',
		'minified' => false
	),$js);
	if($js['type'] != 'inline' && substr($js['file'],-3) != '.js'){ // nothing to do
		continue;
	}
	$file = substr($js['file'],0,-3);
	switch($js['type']){
		case 'file':
			$page['head'] .= '<script type="text/javascript" src="'.htmlentities($file).($js['minified'] && (!isset($config['settings']['minified'])||$config['settings']['minified'])?'.min':'').'.js"></script>';
			break;
		case 'inline':
			$page['head'] .= '<script type="text/javascript">'.$js['file'].'</script>';
	}
}
foreach($page['css'] as $css){
	if(!is_array($css)){
		$css = array(
			'file' => $css
		);
	}
	$css = array_merge(array(
		'type' => 'file',
		'file' => '',
		'minified' => false
	),$css);
	if($css['type'] != 'inline' && substr($css['file'],-4) != '.css'){ // nothing to do
		continue;
	}
	$file = substr($css['file'],0,-4);
	switch($css['type']){
		case 'file':
			$page['head'] .= '<link rel="stylesheet" type="text/css" href="'.htmlentities($file).($css['minified'] && (!isset($config['settings']['minified'])||$config['settings']['minified'])?'.min':'').'.css" />';
			break;
		case 'inline':
			$page['head'] .= '<style type="text/css">'.$css['file'].'</style>';
	}
}

$theme = $networks->get($you->getNetwork());
$theme = $theme['config']['theme'];
echo '<!DOCTYPE html><html><head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta charset="utf-8" />
	<link rel="icon" type="image/png" href="'.htmlentities($page['favicon']).'">
	<script type="text/javascript" src="btoa.js"></script>
	<script type="text/javascript" src="jquery-1.11.3.min.js"></script>
	<script type="text/javascript" src="omnomirc'.(!isset($config['settings']['minified'])||$config['settings']['minified']?'.min':'').'.js"></script>
	'.($config['websockets']['use'] && $config['settings']['useBot']?'<script type="text/javascript" src="pooledwebsocket.min.js"></script>':'').'
	<title>'.$page['title'].'</title>
	<script type="text/javascript">document.domain="'.$config['settings']['hostname'].'";</script>
	'.$page['head'].'
	'.($theme!=-1?'<link rel="stylesheet" type="text/css" href="theme.php?theme='.$theme.'" />':'').'
</head>
<body>'.$page['html'].'</body></html>';
