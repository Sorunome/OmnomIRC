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

header('Content-Type:application/json');
if(isset($_GET['ident'])){
	$json->add('loggedin',$you->isLoggedIn());
	$json->add('isglobalop',$you->isGlobalOp());
	$json->add('isglobalbanned',$you->isGlobalBanned());
	$banned = $you->isBanned();
	$json->add('isbanned',$banned);
	$json->add('mayview',!$banned);
	$json->add('channel',$you->chan);
	$json->add('network',$you->getNetwork());
}elseif(isset($_GET['getcurline'])){
	$json->clear();
	$json->add('curline',(int)file_get_contents($config['settings']['curidFilePath']));
}elseif(isset($_GET['cleanUsers'])){
	$users->clean();
	$relay->commitBuffer();
	$json->clear();
	$json->add('success',true);
}elseif(isset($_GET['userinfo'])){
	$json->clear();
	if($you->isBanned()){
		$json->addError('banned');
	}elseif(isset($_GET['name']) && isset($_GET['chan']) && isset($_GET['online'])){
		$temp = $sql->query_prepare("SELECT `lastMsg` FROM `{db_prefix}users` WHERE username=? AND channel=? AND online=?",array(base64_url_decode($_GET['name']),(preg_match('/^[0-9]+$/',$_GET['chan'])?$_GET['chan']:base64_url_decode($_GET['chan'])),(int)$_GET['online']));
		$json->add('last',(int)$temp[0]['lastMsg']);
	}else{
		$json->addError('Bad parameters');
	}
}elseif(isset($_GET['openpm'])){
	$json->clear();
	if($you->isBanned()){
		$json->addError('banned');
	}else{
		$temp = $sql->query_prepare("SELECT `name`,`network`,`uid` FROM `{db_prefix}userstuff` WHERE LOWER(`name`)=LOWER(?) AND `network`=?",array(base64_url_decode($_GET['openpm']),isset($_GET['checknet'])?$_GET['checknet']:$you->getNetwork()));
		$json->add('chanid','*'.$you->getWholePmHandler($temp[0]['name'],$temp[0]['network']));
		$json->add('channick',$temp[0]['name']);
	}
}elseif(isset($_GET['identName'])){
	$json->clear();
	$json->add('success',false);
	$name = base64_url_decode($_GET['identName']);
	$nets = $networks->getNetsarray();
	if(!isset($nets[$you->getNetwork()]['config']['guests']) || $nets[$you->getNetwork()]['config']['guests'] < 2){
		$json->add('message','feature not available');
	}elseif(!preg_match('/^[a-zA-Z0-9\\-_]{4,20}$/',$name)){
		$json->add('message','invalid nickname (chars and digits, 4-20)');
	}else{
		$res = $sql->query_prepare("SELECT `uid` FROM {db_prefix}users WHERE `username`=? AND `online`=? AND (`uid`<>-1 OR (`lastMsg` >= ? AND `isOnline`=0) OR `isOnline`=1) LIMIT 1",array($name,$you->getNetwork(),strtotime('-30 minutes')));
		$res = $res[0];
		if($res['uid'] === NULL || ($you->isLoggedIn() && $res['uid'] == -1 && $name == $you->nick /* network must be already correct */)){
			$json->add('success',true);
			$json->add('signature',$security->getGuestSig($name,$you->getNetwork()));
		}else{
			$json->add('message','nickname already taken');
		}
	}
}

echo $json->get();