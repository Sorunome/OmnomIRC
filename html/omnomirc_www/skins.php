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
class Skins {
	public static $handle;
	private static $hooks = array(
		'' => '\oirc\skins\lobster\getPage',
		'options' => '\oirc\skins\lobster\getOptions',
		'admin' => '\oirc\skins\lobster\getAdmin'
	);
	private static $themes = array();
	public static function theme($name,$function){
		self::$themes[$name] = $function;
	}
	public static function hook($action,$function){
		self::$hooks[$action] = $function;
	}
	public static function getTheme($name,$t){
		if(!preg_match('/^[\w]+$/',$name)){
			return '';
		}
		$path = realpath(dirname(__FILE__)).'/skins/'.$name;
		if(!file_exists($path.'/skin.php')){
			return '';
		}
		include_once($path.'/skin.php');
		if(!isset(self::$themes[$name])){
			return '';
		}
		$fn = self::$themes[$name];
		return $fn($t);
	}
	public static function parseScripts(&$page,$type,$include,$inline){
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
			$ext = true;
			if($js['type'] != 'inline' && substr($js['file'],-1-strlen($type)) != '.'.$type){ // nothing to do
				$ext = false;
			}
			if($js['type'] == 'file'){
				$file = $ext?substr($js['file'],0,-1-strlen($type)):$js['file'];
				if(!$js['absolute'] ){
					$file = $page['path'].'/'.$file;
				}
				if($ext){
					$include($page,$file.($js['minified'] && (!isset(OIRC::$config['settings']['minified'])||OIRC::$config['settings']['minified'])?'.min':'').'.'.$type);
				}else{
					$include($page,$file);
				}
			}else{
				$inline($page,$js['file']);
			}
		}
	}
	public static function getSkin($name){
		if(!preg_match('/^[\w]+$/',$name)){
			$name = 'lobster';
		}
		include_once(realpath(dirname(__FILE__)).'/skins/lobster/skin.php');
		$path = realpath(dirname(__FILE__)).'/skins/'.$name;
		if(file_exists($path.'/skin.php')){
			include_once($path.'/skin.php');
		}
		$page = false;
		foreach(self::$hooks as $action => $function){
			if(isset($_GET[$action])){
				$page = $function;
				break;
			}
		}
		if(!$page){
			$page = self::$hooks[''];
		}
		$page = $page();
		
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
		

		$theme = Networks::get(OIRC::$you->getNetwork());
		$theme = $theme['config']['theme'];
		if($theme != -1){
			$page['css'][] = array(
				'file' => 'theme.php?skin='.$name.'&theme='.$theme,
				'absolute' => true
			);
		}
		return $page;
	}

}
