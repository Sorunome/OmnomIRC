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
function parseScripts(&$page,$type,$include,$inline){
	foreach($page[$type] as $js){
		if(!is_array($js)){
			$js = array(
				'file' => $js
			);
		}
		$js = array_merge(array(
			'type' => 'file',
			'file' => '',
			'minified' => false,
			'absolute' => false,
		),$js);
		if($js['type'] != 'inline' && substr($js['file'],-1-strlen($type)) != '.'.$type){ // nothing to do
			continue;
		}
		if($js['type'] == 'file'){
			$file = substr($js['file'],0,-1-strlen($type));
			if(!$js['absolute'] ){
				$file = $page['path'].'/'.$file;
			}
			$include($page,$file.($js['minified'] && (!isset(OIRC::$config['settings']['minified'])||OIRC::$config['settings']['minified'])?'.min':'').'.'.$type);
		}else{
			$inline($page,$js['file']);
		}
	}
}
function getSkin($name){
	$path = realpath(dirname(__FILE__)).'/skins/'.$name;
	if(file_exists($path.'/skin.php')){
		include_once($path.'/skin.php');
		if(isset($_GET['options'])){
			$page = \oirc\skin\getOptions();
		}elseif(isset($_GET['admin'])){
			$page = \oirc\skin\getAdmin();
		}else{
			$page = \oirc\skin\getPage();
		}
		if(!$page){
			return getSkin('lobster');
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
		$page['path'] = 'skins/'.$name;
		if(OIRC::$config['websockets']['use'] && OIRC::$config['settings']['useBot']){
			array_unshift($page['js'],array(
				'file' => 'pooledwebsocket.min.js',
				'absolute' => true
			));
		}
		array_unshift($page['js'],array(
			'file' => 'btoa.js',
			'absolute' => true
		),array(
			'file' => 'jquery-1.11.3.min.js',
			'absolute' => true
		),array(
			'file' => 'omnomirc.js',
			'minified' => true,
			'absolute' => true
		));
		return $page;
	}
	return getSkin('lobster');
}
