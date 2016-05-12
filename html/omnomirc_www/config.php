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

if(isset($_GET['js'])){
	header('Content-type: application/json');
	
	$cl = OIRC::getCheckLoginUrl();
	if(isset($_GET['clonly'])){
		echo json_encode(array(
			'checkLoginUrl' => $cl
		));
		exit;
	}
	
	$channels = array();
	foreach(OIRC::$config['channels'] as $chan){
		if($chan['enabled']){
			foreach($chan['networks'] as $cn){
				if($cn['id'] == OIRC::$you->getNetwork()){
					$channels[] = array(
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
	$net = Networks::get(OIRC::$you->getNetwork());
	
	$defaults = $net['config']['defaults'];
	
	$net = Networks::getNetworkId();
	
	$spLinks = array();
	
	$dispNetworks = array();
	$guests = 0;
	foreach(Networks::getNetsarray() as $n){
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
		if($n['id'] == OIRC::$you->getNetwork()){
			$spLinks = $n['config']['spLinks'];
			$guests = $n['config']['guests'];
		}
	}
	
	$v = Vars::get(array('extra_chan_msg_'.(string)$net,'defaults_'.(string)$net,'smileys'));
	
	echo json_encode(array(
		'hostname' => OIRC::$config['settings']['hostname'],
		'channels' => $channels,
		'smileys' => $v['smileys'],
		'networks' => $dispNetworks,
		'network' => OIRC::$you->getNetwork(),
		'checkLoginUrl' => $cl,
		'defaults' => $v['defaults_'.(string)$net]?$v['defaults_'.(string)$net]:array(),
		'spLinks' => $spLinks,
		'guests' => $guests,
		'websockets' => array(
			'use' => OIRC::$config['websockets']['use'] && OIRC::$config['settings']['useBot'],
			'host' => OIRC::$config['websockets']['host']?OIRC::$config['websockets']['host']:OIRC::$config['settings']['hostname'],
			'port' => OIRC::$config['websockets']['port'],
			'ssl' => OIRC::$config['websockets']['ssl'] || OIRC::$config['websockets']['fssl']
		),
		'extraChanMsg' => $v['extra_chan_msg_'.(string)$net]?$v['extra_chan_msg_'.(string)$net]:''
	));
}elseif(isset($_GET['admincfg'])){
	header('Content-type: application/json');
	if(OIRC::$you->isGlobalOp()){
		echo json_encode(array(
			'betaUpdates' => isset(OIRC::$config['settings']['betaUpdates'])&&OIRC::$config['settings']['betaUpdates']
		));
	}else{
		Json::addError('permission denied');
		echo Json::get();
	}
}elseif(isset($_GET['channels'])){
	header('Content-type: application/json');
	if(OIRC::$you->getNetwork()==0 && OIRC::$you->isLoggedIn()){
		$dispChans = array();
		foreach(OIRC::$config['channels'] as $chan){
			$dispChans[$chan['id']] = $chan['alias'];
		}
		echo json_encode(array(
			'channels' => $dispChans
		),JSON_FORCE_OBJECT);
	}else{
		Json::addError('permission denied');
		echo Json::get();
	}
}
