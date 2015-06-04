<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2015 Sorunome

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

function getConfig(){
	$cfg = explode("\n",file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'));
	$searchingJson = true;
	$json = "";
	foreach($cfg as $line){
		if($searchingJson){
			if(trim($line)=='?>'){
				$searchingJson = false;
			}
		}else{
			$json .= "\n".$line;
		}
	}
	return json_decode($json,true);
}
$config = getConfig();

function getCheckLoginUrl(){
	global $you,$networks,$config;
	$net = $networks->get($you->getNetwork());
	$cl = $net['config']['checkLogin'];
	$ts = (string)time();
	$clsid = urlencode(htmlspecialchars(str_replace(';','%^%',hash_hmac('sha512',(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'THE GAME'),$config['security']['sigKey'].$ts.$you->getNetwork()).'|'.$ts)));
	if(isset($_SERVER['HTTP_REFERER'])){
		$urlhost = parse_url($_SERVER['HTTP_REFERER']);
		if($urlhost['host'] != $_SERVER['SERVER_NAME']){
			$clsid = '';
		}
	}
	$cl .= '?sid='.$clsid;
	return $cl;
}

if(isset($_GET['js'])){
	include_once(realpath(dirname(__FILE__)).'/omnomirc.php');
	header('Content-type: application/json');
	
	$cl = getCheckLoginUrl();
	if(isset($_GET['clonly'])){
		echo json_encode(Array(
			'checkLoginUrl' => $cl
		));
		exit;
	}
	
	$channels = Array();
	foreach($config['channels'] as $chan){
		if($chan['enabled']){
			foreach($chan['networks'] as $cn){
				if($cn['id'] == $you->getNetwork()){
					$channels[] = Array(
						'chan' => $cn['name'],
						'high' => false,
						'ex' => $cn['hidden'],
						'id' => $chan['id'],
						'order' => $cn['order']
					);
				}
			}
		}
	}
	usort($channels,function($a,$b){
		if($a['order'] == $b['order']){
			return 0;
		}
		return (($a['order'] < $b['order'])?-1:1);
	});
	$net = $networks->get($you->getNetwork());
	
	$defaults = $net['config']['defaults'];
	
	$net = $networks->getNetworkId();
	
	$extraChanMsg = '';
	
	$dispNetworks = Array();
	foreach($config['networks'] as $n){
		$addNet = array(
			'id' => $n['id'],
			'normal' => $n['normal'],
			'userlist' => $n['userlist'],
			'name' => $n['name'],
			'type' => $n['type']
		);
		if($addNet['type'] == 1){
			$addNet['checkLogin'] = $n['config']['checkLogin'];
		}
		$dispNetworks[] = $addNet;
		if($n['id'] == $net){
			$msg = $vars->get('extra_chan_msg_'.(string)$n['id']);
			if($msg!==NULL){
				$extraChanMsg = $msg;
			}
		}
	}
	echo json_encode(Array(
		'hostname' => $config['settings']['hostname'],
		'channels' => $channels,
		'smileys' => $vars->get('smileys'),
		'networks' => $dispNetworks,
		'network' => $you->getNetwork(),
		'checkLoginUrl' => $cl,
		'defaults' => $defaults,
		'websockets' => Array(
			'use' => $config['websockets']['use'],
			'host' => $config['websockets']['host'],
			'port' => $config['websockets']['port'],
			'ssl' => $config['websockets']['ssl']
		),
		'extraChanMsg' => $extraChanMsg
	));
}
?>
