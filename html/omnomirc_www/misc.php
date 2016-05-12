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
	Json::add('loggedin',OIRC::$you->isLoggedIn());
	Json::add('isglobalop',OIRC::$you->isGlobalOp());
	Json::add('isglobalbanned',OIRC::$you->isGlobalBanned());
	$banned = OIRC::$you->isBanned();
	Json::add('isbanned',$banned);
	Json::add('mayview',!$banned);
	Json::add('channel',OIRC::$you->chan);
	Json::add('network',OIRC::$you->getNetwork());
}elseif(isset($_GET['getcurline'])){
	Json::clear();
	Json::add('curline',(int)file_get_contents(OIRC::$config['settings']['curidFilePath']));
}elseif(isset($_GET['cleanUsers'])){
	Users::clean();
	Relay::commitBuffer();
	Json::clear();
	Json::add('success',true);
}elseif(isset($_GET['userinfo'])){
	Json::clear();
	if(OIRC::$you->isBanned()){
		Json::addError('banned');
	}elseif(isset($_GET['name']) && isset($_GET['chan']) && isset($_GET['online'])){
		$temp = Sql::query("SELECT `lastMsg` FROM `{db_prefix}users` WHERE username=? AND channel=? AND online=?",array(base64_url_decode($_GET['name']),(preg_match('/^[0-9]+$/',$_GET['chan'])?$_GET['chan']:base64_url_decode($_GET['chan'])),(int)$_GET['online']));
		Json::add('last',(int)$temp[0]['lastMsg']);
	}else{
		Json::addError('Bad parameters');
	}
}elseif(isset($_GET['openpm'])){
	Json::clear();
	if(OIRC::$you->isBanned()){
		Json::addError('banned');
	}else{
		$temp = Sql::query("SELECT `name`,`network`,`uid` FROM `{db_prefix}userstuff` WHERE LOWER(`name`)=LOWER(?) AND `network`=?",array(base64_url_decode($_GET['openpm']),isset($_GET['checknet'])?$_GET['checknet']:OIRC::$you->getNetwork()));
		Json::add('chanid','*'.OIRC::$you->getWholePmHandler($temp[0]['name'],$temp[0]['network']));
		Json::add('channick',$temp[0]['name']);
	}
}elseif(isset($_GET['identName'])){
	Json::clear();
	Json::add('success',false);
	$name = base64_url_decode($_GET['identName']);
	$nets = Networks::getNetsarray();
	if(!isset($nets[OIRC::$you->getNetwork()]['config']['guests']) || $nets[OIRC::$you->getNetwork()]['config']['guests'] < 2){
		Json::add('message','feature not available');
	}elseif(!preg_match('/^[a-zA-Z0-9\\-_]{4,20}$/',$name)){
		Json::add('message','invalid nickname (chars and digits, 4-20)');
	}else{
		$res = Sql::query("SELECT `uid` FROM {db_prefix}users WHERE `username`=? AND `online`=? AND (`uid`<>-1 OR (`lastMsg` >= ? AND `isOnline`=0) OR `isOnline`=1) LIMIT 1",array($name,OIRC::$you->getNetwork(),strtotime('-30 minutes')));
		$res = $res[0];
		if($res['uid'] === NULL || (OIRC::$you->isLoggedIn() && $res['uid'] == -1 && $name == OIRC::$you->nick /* network must be already correct */)){
			Json::add('success',true);
			Json::add('signature',Security::getGuestSig($name,OIRC::$you->getNetwork()));
		}else{
			Json::add('message','nickname already taken');
		}
	}
}

echo Json::get();
