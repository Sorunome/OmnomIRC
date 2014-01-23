<?PHP

/*
ip 108.174.51.58

    OmnomIRC COPYRIGHT 2010,2011 Netham45

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

	error_reporting(E_ALL);
	ini_set('display_errors','1');
	include_once(realpath(dirname(__FILE__)).'/config.php');
	
	
	function removeLinebrakes($s){
		return str_replace("\0",'',str_replace("\r",'',str_replace("\n",'',$s)));
	}
	if(!isset($_GET['signature']) || !isset($_GET['nick']) || !isset($_GET['message']) || !isset($_GET['channel']) || !isset($_GET['id']))
		die('Missing Required Field');
	if(strlen($_GET['message']) < 4)
		die('Bad message');
	if(!(checkSignature($_GET['nick'],$_GET['signature'],true) || (isset($_GET['calc']) && $_GET['passwd']==$calcKey)))
		die('Bad signature');
	
	
	$message = removeLinebrakes(base64_url_decode(str_replace(' ','+',$_GET['message'])));
	$type = 'message';
	$message = str_replace(array("\r","\r\n","\n"),' ',$message);
	$parts = explode(" ",$message);
	if(strlen($message) <= 0)
		die('Bad message');
	$nick = html_entity_decode(removeLinebrakes(base64_url_decode($_GET['nick'])));
	$channel = base64_url_decode($_GET['channel']);
	$pm = false;
	$sendNormal = true;
	$reload = false;
	$sendPm = false;
	$userSql = getUserstuffQuery($nick);
		if(strpos($userSql['bans'],base64_url_decode($_GET['channel'])."\n")!==false)
			die('banned');
	
	if(substr($parts[0],0,1)=='/'){
		if(isset($_GET['calc']) && ($parts[0]!='/me' || substr($parts[0],0,2)=='//'))
			die('Sorry calculator, you can only do /me messages');
		switch(strtolower(substr($parts[0],1))) {
			case 'me':
				$type = 'action';
				$message = preg_replace('/\/me/i','',$message,1);
				break;
			case 'msg':
			case 'pm':
				$pm=true;
				$channel = $parts[1];
				$message = '';
				unset($parts[0]);
				unset($parts[1]);
				$message = implode(' ',$parts);
				$type = 'pm';
				break;
			case 'ignore':
				unset($parts[0]);
				$ignoreuser = strtolower(implode(" ",$parts));
				$returnmessage = "";
				$sendNormal = false;
				$sendPm = true;
				$userSql = getUserstuffQuery($nick);
				if(strpos($userSql['ignores'],$ignoreuser."\n")===false){
					$userSql['ignores'].=$ignoreuser."\n";
					$sql->query("UPDATE `irc_userstuff` SET ignores='%s' WHERE name='%s'",$userSql["ignores"],strtolower($nick));
					$returnmessage = "\x033Now ignoring $ignoreuser.";
					$reload = true;
				}else{
					$returnmessage = "\x034ERROR: couldn't ignore $ignoreuser: already ignoring.";
				}
				break;
			case 'unignore':
				unset($parts[0]);
				$ignoreuser = strtolower(implode(' ',$parts));
				$returnmessage = '';
				$sendNormal = false;
				$sendPm = true;
				$userSql = getUserstuffQuery($nick);
				$allIgnoreUsers = explode("\n","\n".$userSql['ignores']);
				$unignored = false;
				for($i;$i<sizeof($allIgnoreUsers);$i++){
					echo $allIgnoreUsers[$i].' '.$ignoreuser."\n";
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
					if($ignoreuser=='*')
						$returnmessage = "\x033You are no longer ignoring anybody.";
					mysqli_fetch_array($sql->query("UPDATE `irc_userstuff` SET ignores='%s' WHERE name='%s'",$userSql["ignores"],strtolower($nick)));
					$reload = true;
				}else{
					$returnmessage = "\x034ERROR: You weren't ignoring $ignoreuser";
				}
				break;
			case 'ignorelist':
				$returnmessage = '';
				$sendNormal = false;
				$sendPm = true;
				$userSql = getUserstuffQuery($nick);
				$returnmessage = "\x033Ignored users: ".str_replace("\n",",",$userSql["ignores"]);
				break;
			case 'position':
				$returnmessage = '';
				$sendNormal = false;
				$sendPm = true;
				if(isOp($nick,base64_url_decode($_GET['signature']),$_GET['id'],$channel))
					$returnmessage = "You are op and thus you just lost \x02THE GAME\x02";
				else
					$returnmessage = "You aren't op";
				break;
			case 'topic':
				$sendNormal = false;
				if(isOp($nick,base64_url_decode($_GET['signature']),$_GET['id'],$channel)){
					unset($parts[0]);
					$newTopic = implode(' ',$parts);
					$temp = $sql->query("SELECT * FROM `irc_topics` WHERE chan='%s'",strtolower($channel));
					$temp = $temp[0];
					if($temp['chan']==NULL){
						$sql->query("INSERT INTO `irc_topics` (chan,topic) VALUES('%s','')",strtolower($channel));
					}
					$sql->query("UPDATE `irc_topics` SET topic='%s' WHERE chan='%s'",$newTopic,strtolower($channel));
					$sql->query("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES('%s','%s','%s','%s','%s','%s')",$newTopic,$nick,$channel,'0','0','topic');
					$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s','%s')",$nick,$newTopic,"topic",$channel,time(),'1');
				}else{
					$returnmessage = "You aren't op";
					$sendPm = true;
				}
				break;
			case 'op':
				$sendNormal = false;
				if(isOp($nick,base64_url_decode($_GET['signature']),$_GET['id'],$channel)){
					unset($parts[0]);
					$userToOp = implode(' ',$parts);
					$userSql = getUserstuffQuery($userToOp);
					if (strpos($userSql['ops'],$channel."\n")===false) {
						$userSql['ops'].=$channel."\n";
						$sql->query("UPDATE `irc_userstuff` SET ops='%s' WHERE name='%s'",$userSql["ops"],strtolower($userToOp));
						$sql->query("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES('%s','%s','%s','%s','%s','%s')","+o $userToOp",$nick,$channel,'0','0','mode');
						$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s','%s')",$nick,"+o $userToOp","mode",$channel,time(),'1');
					}else{
						$returnmessage = "\x034ERROR: couldn't op $userToOp: already op.";
						$sendPm = true;
					}
				}else{
					$returnmessage = "You aren't op";
					$sendPm = true;
				}
				break;
			case 'deop':
				$sendNormal = false;
				if(isOp($nick,base64_url_decode($_GET['signature']),$_GET['id'],$channel)){
					unset($parts[0]);
					$userToOp = implode(" ",$parts);
					$userSql = getUserstuffQuery($userToOp);
					$allOpChans = explode("\n","\n".$userSql['ops']);
					$deoped = false;
					for($i;$i<sizeof($allOpChans);$i++){
						echo $allOpChans[$i]." ".$channel."\n";
						if ($allOpChans[$i]==$channel){
							$deoped = true;
							unset($allOpChans[$i]);
						}
					}
					unset($allOpChans[0]); //whitespace bug
					$userSql['ops'] = implode("\n",$allOpChans);
					if($deoped){
						$sql->query("UPDATE `irc_userstuff` SET ops='%s' WHERE name='%s'",$userSql["ops"],strtolower($userToOp));
						$sql->query("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES('%s','%s','%s','%s','%s','%s')","-o $userToOp",$nick,$channel,'0','0','mode');
						$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s','%s')",$nick,"-o $userToOp","mode",$channel,time(),'1');
					}else{
						$returnmessage = "\x034ERROR: couldn't deop $userToOp: no op.";
						$sendPm = true;
					}
				}else{
					$returnmessage = "You aren't op";
					$sendPm = true;
				}
				break;
			case 'ban':
				$sendNormal = false;
				if(isOp($nick,base64_url_decode($_GET['signature']),$_GET['id'],$channel)){
					unset($parts[0]);
					$userToOp = implode(' ',$parts);
					$userSql = getUserstuffQuery($userToOp);
					if(strpos($userSql['bans'],$channel."\n")===false){
						$userSql['bans'].=$channel."\n";
						$sql->query("UPDATE `irc_userstuff` SET bans='%s' WHERE name='%s'",$userSql["bans"],strtolower($userToOp));
						$sql->query("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES('%s','%s','%s','%s','%s','%s')","+b $userToOp",$nick,$channel,'0','0','mode');
						$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s','%s')",$nick,"+b $userToOp","mode",$channel,time(),'1');
					}else{
						$returnmessage = "\x034ERROR: couldn't ban $userToOp: already banned.";
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
				if(isOp($nick,base64_url_decode($_GET['signature']),$_GET['id'],$channel)){
					unset($parts[0]);
					$userToOp = implode(' ',$parts);
					$userSql = getUserstuffQuery($userToOp);
					$allOpChans = explode("\n","\n".$userSql['bans']);
					$deoped = false;
					for($i;$i<sizeof($allOpChans);$i++){
						echo $allOpChans[$i].' '.$channel."\n";
						if($allOpChans[$i]==$channel){
							$deoped = true;
							unset($allOpChans[$i]);
						}
					}
					unset($allOpChans[0]); //whitespace bug
					$userSql['bans'] = implode("\n",$allOpChans);
					if($deoped){
						$sql->query("UPDATE `irc_userstuff` SET bans='%s' WHERE name='%s'",$userSql["bans"],strtolower($userToOp));
						$sql->query("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES('%s','%s','%s','%s','%s','%s')","-b $userToOp",$nick,$channel,'0','0','mode');
						$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s','%s')",$nick,"-b $userToOp","mode",$channel,time(),'1');
					}else{
						$returnmessage = "\x034ERROR: couldn't deban $userToOp: no ban.";
						$sendPm = true;
					}
				}else{
					$returnmessage = "You aren't op";
					$sendPm = true;
				}
				break;
			
			default:
				if(substr($parts[0],0,2)=='//')
					$message=substr($message,1);
				else{
					$sendNormal = false;
					$sendPm = true;
					$returnmessage = "\x02ERROR:\x02 Invalid command.";
				}
				break;
		}
	}
	if($channel[0] == '*'){ //PM
		if($type=='action')
			$type = 'pmaction';
		else
			$type = 'pm';
		$channel = substr($channel,1);
	}
	
	if($sendNormal){
		$fromSource='0';
		$isOnline='1';
		if(isset($_GET['calc'])){
			$fromSource='1';
			$isOnline='2';
		}
		$sql->query("UPDATE `irc_users` SET lastMsg='%s' WHERE username='%s' AND channel='%s' AND online='1'",time(),$nick,$channel);
		$sql->query("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action,fromSource,type) VALUES('%s','%s','%s','%s','%s','%s')",$message,$nick,$channel,($type=="action")?'1':'0',$fromSource,"msg");
		$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s','%s')",$nick,$message,$type,$channel,time(),$isOnline);
	}
	if($sendPm){
		$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,name2,online) VALUES('%s','%s','%s','%s','%s','%s',1)","OmnomIRC",$returnmessage,"server",$nick,time(),$channel);
	}
	if($reload){
		$sql->query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s',1)","OmnomIRC","THE GAME","reload",$nick,time());
	}
	if(isset($_GET['textmode'])){
		session_start();
		echo "<html><head><meta http-equiv=\"refresh\" content=\"1;url=textmode.php?update=".$_SESSION['curline']."\"></head><body>Sending message...</body></html>";
	}
	$temp = $sql->query("SELECT MAX(line_number) FROM irc_lines");
	file_put_contents($curidFilePath,$temp[0]['MAX(line_number)']);
	
?>
