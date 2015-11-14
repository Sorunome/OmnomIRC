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
if(isset($_GET['textmode'])){
	$textmode = true;
	session_start();
}
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');

function removeLinebrakes($s){
	return str_replace(Array("\0","\r","\n"),'',$s);
}
$message = (isset($_GET['message'])?$_GET['message']:'');
if(strlen($message) < 4){
	$json->addError('Bad message');
}
if($json->hasErrors() || $json->hasWarnings()){
	echo $json->get();
	die();
}
if(!$you->isLoggedIn()){
	$json->addError('Not Logged in!');
	echo $json->get();
	die();
}
if($you->isBanned()){
	$json->add('banned',true);
	$json->addError('banned');
	echo $json->get();
	die();
}
$message = removeLinebrakes(base64_url_decode(str_replace(' ','+',$message)));
$type = 'message';
$parts = explode(' ',$message);

if(strlen($message) <= 0){
	$json->addError('Bad message');
}
if(strlen($message) > 256){
	$json->addError('Message too long');
}
if($json->hasErrors() || $json->hasWarnings()){
	echo $json->get();
	die();
}

$nick = $you->nick;
$channel = $you->chan;
$pm = false;
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
			if($channel[0]!='#' && $channel[0]!='&' && !preg_match('/^[0-9]+$/',$channel)){
				$channel = '@'.$channel;
			}
			$you->setChan($channel);
			$_SESSION['content'] = '';
			$sendNormal = false;
			break;
		case 'q':
		case 'query':
			$channel = '*'.substr($message,7);
			$you->setChan($channel);
			$_SESSION['content'] = '';
			$sendNormal = false;
			break;
		case 'msg':
		case 'pm':
			if(isset($parts[1]) && strlen($parts[1])>=1 && !preg_match('/^([0-9]+$|[#@*])/',$parts[1])){
				$channel = $parts[1];
				$pm=true;
				$message = '';
				unset($parts[0]);
				unset($parts[1]);
				$message = implode(' ',$parts);
				$type = 'pm';
			}else{
				$sendNormal = false;
				$sendPm = true;
				$returnmessage = "\x034ERROR: can't PM a channel";
			}
			break;
		case 'ignore':
			unset($parts[0]);
			$ignoreuser = trim(strtolower(implode(" ",$parts)));
			$returnmessage = "";
			$sendNormal = false;
			$sendPm = true;
			$userSql = $you->info();
			if(strpos($userSql['ignores'],$ignoreuser."\n")===false){
				$userSql['ignores'].=$ignoreuser."\n";
				$sql->query_prepare("UPDATE `irc_userstuff` SET ignores=? WHERE name=LOWER(?)",array($userSql["ignores"],$nick));
				$returnmessage = "\x033Now ignoring $ignoreuser.";
				$reload = true;
			}else{
				$returnmessage = "\x034ERROR: couldn't ignore $ignoreuser: already ignoring.";
			}
			break;
		case 'unignore':
			unset($parts[0]);
			$ignoreuser = trim(strtolower(implode(' ',$parts)));
			$returnmessage = '';
			$sendNormal = false;
			$sendPm = true;
			$userSql = $you->info();
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
				$sql->query_prepare("UPDATE `irc_userstuff` SET ignores=? WHERE name=LOWER(?)",array($userSql["ignores"],$nick));
				$reload = true;
			}else{
				$returnmessage = "\x034ERROR: You weren't ignoring $ignoreuser";
			}
			break;
		case 'ignorelist':
			$returnmessage = '';
			$sendNormal = false;
			$sendPm = true;
			$userSql = $you->info();
			$returnmessage = "\x033Ignored users: ".str_replace("\n",",",$userSql["ignores"]);
			break;
		case 'position':
			$returnmessage = '';
			$sendNormal = false;
			$sendPm = true;
			if($you->isOp()){
				$returnmessage = "You are op and thus you just lost \x02THE GAME\x02";
			}else{
				$returnmessage = "You aren't op";
			}
			break;
		case 'topic':
			$sendNormal = false;
			if($you->isOp()){
				unset($parts[0]);
				$newTopic = implode(' ',$parts);
				$channels->setTopic($channel,$newTopic);
				$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES (?,?,?,?,?,?)",array($newTopic,$nick,$channel,0,$you->getNetwork(),'topic'));
				$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES (?,?,?,?,?,?)",array($nick,$newTopic,"topic",$channel,time(),$you->getNetwork()));
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'mode':
			$sendNormal = false;
			if($you->isOp()){
				unset($parts[0]);
				$modeStr = trim(implode(' ',$parts));
				$channels->setMode($channel,$modeStr);
				$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES (?,?,?,?,?,?)",array($modeStr,$nick,$channel,0,$you->getNetwork(),'mode'));
				$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES (?,?,?,?,?,?)",array($nick,$modeStr,"mode",$channel,time(),$you->getNetwork()));
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'op':
			$sendNormal = false;
			if($you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(' ',$parts));
				if($channels->addOp($channel,$userToOp,$you->getNetwork())){
					$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES (?,?,?,?,?,?)",array("+o $userToOp",$nick,$channel,0,$you->getNetwork(),'mode'));
					$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES (?,?,?,?,?,?)",array($nick,"+o $userToOp","mode",$channel,time(),$you->getNetwork()));
				}else{
					$returnmessage = "\x034ERROR: couldn't op $userToOp: already op or user not found.";
					$sendPm = true;
				}
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'deop':
			$sendNormal = false;
			if($you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(" ",$parts));
				if($channels->remOp($channel,$userToOp,$you->getNetwork())){
					$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES (?,?,?,?,?,?)",array("-o $userToOp",$nick,$channel,0,$you->getNetwork(),'mode'));
					$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES (?,?,?,?,?,?)",array($nick,"-o $userToOp","mode",$channel,time(),$you->getNetwork()));
				}else{
					$returnmessage = "\x034ERROR: couldn't deop $userToOp: no op or user not found.";
					$sendPm = true;
				}
			}else{
				$returnmessage = "You aren't op";
				$sendPm = true;
			}
			break;
		case 'ban':
			$sendNormal = false;
			if($you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(' ',$parts));
				if($channels->addBan($channel,$userToOp,$you->getNetwork())){
					$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES (?,?,?,?,?,?)",array("+b $userToOp",$nick,$channel,0,$you->getNetwork(),'mode'));
					$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES (?,?,?,?,?,?)",array($nick,"+b $userToOp","mode",$channel,time(),$you->getNetwork()));
				}else{
					$returnmessage = "\x034ERROR: couldn't ban $userToOp: already banned or user not found.";
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
			if($you->isOp()){
				unset($parts[0]);
				$userToOp = trim(implode(' ',$parts));
				if($channels->remBan($channel,$userToOp,$you->getNetwork())){
					$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES (?,?,?,?,?,?)",array("-b $userToOp",$nick,$channel,0,$you->getNetwork(),'mode'));
					$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES (?,?,?,?,?,?)",array($nick,"-b $userToOp","mode",$channel,time(),$you->getNetwork()));
				}else{
					$returnmessage = "\x034ERROR: couldn't deban $userToOp: no ban or user not found.";
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
				$returnmessage = "\x02ERROR:\x02 Invalid command: ".$parts[0].' or did you mean /'.$parts[0].' ?';
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
	$channel = substr($channel,1);
}

if($sendNormal){
	$sql->query_prepare("UPDATE `irc_users` SET lastMsg=? WHERE username=? AND channel=? AND online=?",array(time(),$nick,$channel,$you->getNetwork()));
	$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type,uid) VALUES (?,?,?,?,?,?,?)",array($message,$nick,$channel,($type=="action")?1:0,$you->getNetwork(),$type,$you->getUid()));
	$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online,uid) VALUES (?,?,?,?,?,?,?)",array($nick,$message,$type,$channel,time(),$you->getNetwork(),$you->getUid()));
}
if($sendPm){
	$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,name2,online,uid) VALUES (?,?,?,?,?,?,?,?)",array("OmnomIRC",$returnmessage,"server",$nick,time(),$channel,$you->getNetwork(),$you->getUid()));
	if($config['websockets']['use']){
		$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type,uid) VALUES (?,?,?,?,?,?,?)",array($returnmessage,$channel,$nick,($type=="action")?1:0,$you->getNetwork(),'server',$you->getUid()));
	}
}
if($reload){
	$sql->query_prepare("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES (?,?,?,?,?,?)",array("OmnomIRC","THE GAME","reload",$nick,time(),$you->getNetwork()));
	if($config['websockets']['use']){
		$sql->query_prepare("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES (?,?,?,?,?,?)",array('THE GAME','OmnomIRC',$channel,0,$you->getNetwork(),'reload'));
	}
}
if(isset($_GET['textmode'])){
	session_start();
	echo "<!DOCTYPE html><html><head><title>Sending...</title><meta http-equiv=\"refresh\" content=\"1;url=textmode.php?update=".time()."&curline=".((int)$_GET['curline'])."&".$you->getUrlParams()."\"></head><body>Sending message...</body></html>";
}else{
	$json->add('success',true);
	echo $json->get();
}
$temp = $sql->query_prepare("SELECT MAX(line_number) AS max FROM irc_lines");
file_put_contents($config['settings']['curidFilePath'],$temp[0]['max']);
?>