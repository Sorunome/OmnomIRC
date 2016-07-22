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
if(isset($_GET['textmode'])){
	$textmode = true;
	session_start();
}
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');

function removeLinebrakes($s){
	return str_replace(Array("\0","\r","\n"),'',$s);
}

function strip_irc_colors($s){
	return preg_replace("/(\x02|\x0F|\x16|\x1D|\x1F|\x03(\d{1,2}(,\d{1,2})?)?)/",'',$s);
}


function getOtherPmHandler($channel){
	$youhandler = OIRC::$you->getPmHandler();
	$channel = ltrim($channel,'*');
	$parts = explode('][',$channel);
	if(sizeof($parts) != 2){
		return '';
	}
	$parts[0] .= ']';
	$parts[1] = '['.$parts[1];
	if($parts[0] == $youhandler){
		return $parts[1];
	}elseif($parts[1] == $youhandler){
		return $parts[0];
	}else{
		return '';
	}
}
$message = (isset($_GET['message'])?$_GET['message']:'');
if(strlen($message) < 4){
	Json::addError('Bad message');
}
if(Json::hasErrors() || Json::hasWarnings()){
	echo Json::get();
	die();
}
if(!OIRC::$you->isLoggedIn()){
	Json::addError('Not Logged in!');
	echo Json::get();
	die();
}
if(OIRC::$you->isBanned()){
	Json::add('banned',true);
	Json::addError('banned');
	echo Json::get();
	die();
}
$message = removeLinebrakes(base64_url_decode(str_replace(' ','+',$message)));
$type = 'message';
$parts = explode(' ',$message);

if(strlen($message) <= 0){
	Json::addError('Bad message');
}
if(strlen($message) > 256){
	Json::addError('Message too long');
}
if(Json::hasErrors() || Json::hasWarnings()){
	echo Json::get();
	die();
}

$nick = OIRC::$you->nick;
$channel = OIRC::$you->chan;
$sendNormal = true;
$reload = false;
$sendPm = false;

if(substr($parts[0],0,1)=='/'){
	switch(strtolower(substr($parts[0],1))) {
		case 'me':
			$type = 'action';
			$message = substr($message,4);
			break;
		case 'j':
		case 'join':
			$channel = substr($message,6);
			if(strpos($channel[0],'#&@') === false && !preg_match('/^\d+$/',$channel)){
				$channel = '@'.$channel;
			}
			OIRC::$you->setChan($channel);
			$_SESSION['content'] = '';
			$sendNormal = false;
			break;
		case 'q':
		case 'query':
			$sendNormal = false;
			$channel = '*'.OIRC::$you->getWholePmHandler($message);
			if($channel == '*'){
				$sendPm = true;
				$returnmessage = "\x034\x02ERROR:\x02 User not found";
			}else{
				OIRC::$you->setChan($channel);
				$_SESSION['content'] = '';
			}
			break;
		case 'msg':
		case 'pm':
			if(isset($parts[1]) && strlen($parts[1])>=1 && !preg_match('/^([0-9]+$|[#@*])/',$parts[1])){
				$channel = '*'.OIRC::$you->getWholePmHandler($parts[1]);
				if($channel == '*'){
					$sendNormal = false;
					$sendPm = true;
					$returnmessage = "\x034\x02ERROR:\x02 User not found";
				}else{
					$message = '';
					unset($parts[0]);
					unset($parts[1]);
					$message = implode(' ',$parts);
					$type = 'pm';
				}
			}else{
				$sendNormal = false;
				$sendPm = true;
				$returnmessage = "\x034\x02ERROR:\x02 can't PM a channel";
			}
			break;
		case 'ignore':
			$returnmessage = "";
			$sendNormal = false;
			$sendPm = true;
			if(OIRC::$you->getUid() == -1){
				$returnmessage = "\x034\x02ERROR:\x02 can't ignore as guest";
				break;
			}
			unset($parts[0]);
			$ignoreuser = trim(strtolower(implode(" ",$parts)));
			$userSql = OIRC::$you->info();
			if(strpos($userSql['ignores'],$ignoreuser."\n")===false){
				$userSql['ignores'].=$ignoreuser."\n";
				Sql::query("UPDATE `{db_prefix}userstuff` SET ignores=? WHERE name=LOWER(?) AND network=? AND uid=?",array($userSql["ignores"],$nick,OIRC::$you->getNetwork(),OIRC::$you->getUid()));
				$returnmessage = "\x033Now ignoring $ignoreuser.";
				$reload = true;
			}else{
				$returnmessage = "\x034\x02ERROR:\x02 couldn't ignore $ignoreuser: already ignoring.";
			}
			break;
		case 'unignore':
			$returnmessage = '';
			$sendNormal = false;
			$sendPm = true;
			if(OIRC::$you->getUid() == -1){
				$returnmessage = "\x034\x02ERROR:\x02 can't unignore as guest";
				break;
			}
			unset($parts[0]);
			$ignoreuser = trim(strtolower(implode(' ',$parts)));
			$userSql = OIRC::$you->info();
			$allIgnoreUsers = explode("\n","\n".$userSql['ignores']);
			$unignored = false;
			for($i=0;$i<sizeof($allIgnoreUsers);$i++){
				if($allIgnoreUsers[$i]==$ignoreuser){
					$unignored = true;
					unset($allIgnoreUsers[$i]);
				}
			}
			unset($allIgnoreUsers[0]); //whitespace bug
			$userSql['ignores'] = implode("\n",$allIgnoreUsers);
			if($ignoreuser=='*'){
				$userSql['ignores']='';
				$unignored=true;
			}
			if($unignored){
				$returnmessage = "\x033You are not more ignoring $ignoreuser";
				if($ignoreuser=='*'){
					$returnmessage = "\x033You are no longer ignoring anybody.";
				}
				Sql::query("UPDATE `{db_prefix}userstuff` SET ignores=? WHERE name=LOWER(?) AND network=? AND uid=?",array($userSql["ignores"],$nick,OIRC::$you->getNetwork(),OIRC::$you->getUid()));
				$reload = true;
			}else{
				$returnmessage = "\x034ERROR: You weren't ignoring $ignoreuser";
			}
			break;
		case 'ignorelist':
			$returnmessage = '';
			$sendNormal = false;
			$sendPm = true;
			$userSql = OIRC::$you->info();
			$returnmessage = "\x033Ignored users: ".str_replace("\n",",",$userSql["ignores"]);
			break;
		case 'position':
			$returnmessage = '';
			$sendNormal = false;
			$sendPm = true;
			if(OIRC::$you->isOp()){
				$returnmessage = "You are op and thus you just lost \x02THE GAME\x02";
			}else{
				$returnmessage = "You aren't op";
			}
			break;
		case 'topic':
			$sendNormal = false;
			if(OIRC::$you->isOp()){
				unset($parts[0]);
				$newTopic = implode(' ',$parts);
				Channels::setTopic($channel,$newTopic);
				Relay::sendLine($nick,'','topic',$newTopic);
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'mode':
			$sendNormal = false;
			if(OIRC::$you->isOp()){
				unset($parts[0]);
				$modeStr = trim(implode(' ',$parts));
				Channels::setMode($channel,$modeStr);
				$sendToBotBuffer .= trim(json_encode(array(
					't' => 'server_delete_modebuffer',
					'c' => $channel,
				)))."\n";
				Relay::sendLine($nick,'','mode',$modeStr);
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'modes':
			$sendNormal = false;
			$sendPm = true;
			$returnmessage = "\x033Channel modes:".Channels::getModes($channel);
			break;
		case 'op':
			$sendNormal = false;
			if(OIRC::$you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(' ',$parts));
				if(Channels::addOp($channel,$userToOp,OIRC::$you->getNetwork())){
					Relay::sendLine($nick,'','mode',"+o $userToOp");
				}else{
					$returnmessage = "\x034\x02ERROR:\x02 couldn't op $userToOp: already op or user not found.";
					$sendPm = true;
				}
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'deop':
			$sendNormal = false;
			if(OIRC::$you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(" ",$parts));
				if(Channels::remOp($channel,$userToOp,OIRC::$you->getNetwork())){
					Relay::sendLine($nick,'','mode',"-o $userToOp");
				}else{
					$returnmessage = "\x034\x02ERROR:\x02 couldn't deop $userToOp: no op or user not found.";
					$sendPm = true;
				}
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'ban':
			$sendNormal = false;
			if(OIRC::$you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(' ',$parts));
				if(Channels::addBan($channel,$userToOp,OIRC::$you->getNetwork())){
					Relay::sendLine($nick,'','mode',"+b $userToOp");
				}else{
					$returnmessage = "\x034\x02ERROR:\x02 couldn't ban $userToOp: already banned or user not found.";
					$sendPm = true;
				}
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'deban':
		case 'unban':
			$sendNormal = false;
			if(OIRC::$you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(' ',$parts));
				if(Channels::remBan($channel,$userToOp,OIRC::$you->getNetwork())){
					Relay::sendLine($nick,'','mode',"-b $userToOp");
				}else{
					$returnmessage = "\x034\x02ERROR:\x02 couldn't deban $userToOp: no ban or user not found.";
					$sendPm = true;
				}
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		
		default:
			if(substr($parts[0],0,2)=='//'){
				$message=substr($message,1);
			}else{
				$sendNormal = false;
				$sendPm = true;
				$returnmessage = "\x034\x02ERROR:\x02 Invalid command: ".$parts[0].' or did you mean /'.$parts[0].' ?';
			}
			break;
	}
}

if($channel[0] == '*'){
	if($type=='action'){
		$type = 'pmaction';
	}else{
		$type = 'pm';
	}
}

if($sendNormal){
	Sql::query("UPDATE `{db_prefix}users` SET lastMsg=? WHERE username=? AND channel=? AND online=?",array(time(),$nick,$channel,OIRC::$you->getNetwork()));
	if(Channels::isMode($channel,'c')){
		$message = strip_irc_colors($message);
	}
	Relay::sendLine($nick,in_array($type,array('pm','pmaction')) ? getOtherPmHandler($channel) : '',$type,$message,$channel);
}
$lines = array();
if($sendPm){
	$lines[] = array(
		'curline' => 0,
		'type' => 'server',
		'network' => OIRC::$you->getNetwork(),
		'time' => (int)time(),
		'name' => 'OmnomIRC',
		'message' => $returnmessage,
		'name2' => OIRC::$you->getPmHandler(),
		'chan' => $nick,
		'uid' => -1
	);
}
if($reload){
	$lines[] = array(
		'curline' => 0,
		'type' => 'reload',
		'network' => OIRC::$you->getNetwork(),
		'time' => (int)time(),
		'name' => 'OmnomIRC',
		'message' => 'THE GAME',
		'name2' => '',
		'chan' => OIRC::$you->chan,
		'uid' => -1
	);
}

Relay::commitBuffer();

if(isset($_GET['textmode'])){
	session_start();
	echo "<!DOCTYPE html><html><head><title>Sending...</title><meta http-equiv=\"refresh\" content=\"1;url=textmode.php?update=".time()."&curline=".((int)$_GET['curline'])."&".OIRC::$you->getUrlParams()."\"></head><body>Sending message...</body></html>";
}else{
	Json::add('success',true);
	Json::add('lines',$lines);
	echo Json::get();
}
