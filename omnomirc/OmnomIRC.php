<?PHP
/*
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

//Here's the config for the bot.
$documentRoot = "/var/www/omnomirc.www.omnimaga.org";

$IRCBOT=true;
include($documentRoot."/config.php");

$pings = Array();

include($documentRoot."/Channels.php");

$sockets = Array();

$userList = Array();

$fragment = false;
$fragLine = "";

function sql_query()
{
	global $sqlConnection,$sql_server,$sql_user,$sql_password,$sql_db;
	$sqlConnection = mysql_pconnect($sql_server,$sql_user,$sql_password);
	if (!$sqlConnection) 
		die("Could not connect to SQL DB.\n");
	if (!mysql_select_db($sql_db,$sqlConnection)) die('Invalid query: ' . mysql_error());
	$params = func_get_args();
	$query = $params[0];
	$args = Array();
	for ($i=1;$i<count($params);$i++)
		$args[$i-1] = mysql_real_escape_string(str_replace("\r","",str_replace("\n","",$params[$i])),$sqlConnection);
	$result = mysql_query(vsprintf($query,$args),$sqlConnection);
	if (!$result) 
		echo "\n\nSQL ERROR!" . mysql_error() . "Query: " . vsprintf($query,$args). "\n\n\n";
	return $result;
}

function sendLine($line,$socketToMatch,$exclude = true)
{
	global $sockets;
	$line = trim($line) . "\n";
	if ($exclude)
	{
		if (!isset($socketToMatch))
			$socketToMatch="None";
		
		foreach ($sockets as $socket)
			if ($socket != $socketToMatch)
				socket_write($socket,$line);
	}
	else
	{
		socket_write($socketToMatch,$line);
	}
	echo "<<" . $line;
}

function getMessage($parts,$start,$trim)
{
	if($trim)
		$message = substr($parts[$start++],1);
	for ($i = $start; $i < count($parts);$i++)
		$message = $message . " " . $parts[$i];
	
	//$message = trim($message);
	return $message;
}

function parseMsg($allMessage,$callingSocket)
{

	global $socket,$hasIdent,$ident,$chanStr,$userList,$fragment,$fragLine,$botPasswd,$topicBotNick;
	for ($i=0;$i<count($sockets);$i++)
				if ($sockets[i] == $callingSocket)
					$pings[i] = time();
	if ($fragment)
	{
		$allMessage = $fragLine . $allMessage;
		$fragment = false;
	}
		
	$lines = explode("\n",$allMessage);
	
	if (substr($allMessage,-1) != "\n")
	{
		$fragment = true;
		$fragLine = $lines[count($lines) - 1];
		unset($lines[count($lines) - 1]);
	}
	
	foreach ($lines as $Message)
	{
		$parts = explode(" ",$Message);
		preg_match("/:(.*)!(.*)@(.*)/",$parts[0],$info);
		$channel = strtolower($parts[2]);
		$isChan = (substr($channel,0,1)=="#");
		if (strtolower($parts[0]) == "ping")
		{
			sendLine("PONG " . trim($parts[1]) . "\n");
		}
		switch(strtolower($parts[1]))
		{
			case "privmsg":
				
				if ($parts[3] == ":DOTHIS".$botPasswd) sendLine(getMessage($parts,4,false));
				if ($parts[3] == ":PRINTUSERLIST") print_r($userList);
				if ($parts[3] == ":UPDATEUSERLIST") updateUserList();
				if ($parts[3] == ":UPDATECHANS")
				{
					echo "Updating chans";
					sendLine("join #,0");
					$IRCBOT = true;
					include($documentRoot."/Channels.php"); //Update channels array/strings
					sendLine("JOIN $chanStr\n");
					updateUserList();
				}
				if (!$isChan) break;
				
				$message = getMessage($parts,3,true);
				if (preg_match("/^ACTION (.*)/",$message,$messageA))
				{
					addLine($info[1],'','action',$messageA[1],$channel);
					sendLine("PRIVMSG $channel :(#)6* $info[1] $messageA[1]",$callingSocket); //Send to other servers
				}
				else
				{
					addLine($info[1],'','message',$message,$channel);
					sendLine("PRIVMSG $channel :(#)<$info[1]> ".getMessage($parts,3,true),$callingSocket); //Send to other servers
				}
			break;
			case "join":
				$channel = trim(substr($channel,1));
				addLine($info[1],'','join','',$channel);
				userJoin($info[1],$channel);
				sendLine("PRIVMSG $channel :(#)* $info[1] has joined $channel",$callingSocket);
			break;
			case "part":
				$channel = trim($channel);
				$message = getMessage($parts,3,true);
				addLine($info[1],'','part',$message,$channel);
				userLeave($info[1],$channel);
				sendLine("PRIVMSG $channel :(#)* $info[1] has left $channel(".getMessage($parts,3,true).")",$callingSocket);
			break;
			case "mode":
				if (!$isChan) break;
				$message = getMessage($parts,3,false);
				addLine($info[1],'','mode',$message,$channel);
			break;
			case "kick":
				$message = getMessage($parts,4,true);
				addLine($info[1],$parts[3],"kick",$message,$channel);
				userLeave($info[1],$channel);
				sendLine("PRIVMSG $channel :(#)* $info[1] has been kicked from $channel by $parts[3] (".getMessage($parts,4,true).")",$callingSocket);
			break;
			case "quit":
				$message = getMessage($parts,2,true);
				userQuit($info[1],$message,$callingSocket);
			break;
			case "topic":
				$message = getMessage($parts,3,true);
				if ($info[1]!="OmnomIRC" && $info[1]!=$topicBotNick) {
					addLine($info[1],'',"topic",$message,$channel);
					sendLine("PRIVMSG $topicBotNick : $channel $message\n",$callingSocket);
					sendLine("PRIVMSG $channel :(#)3* ".$info[1]." has changed the topic to ".$message,$callingSocket);
				}
			break;
			case "nick":
				$message = getMessage($parts,2,true);
				userNick($info[1],$message,$callingSocket);
			break;
			case "376": //End of MOTD
				sendLine("JOIN $chanStr\n",$callingSocket,false);
				updateUserList($callingSocket);
			break;
			case "352": //Whois response
				userJoin($parts[7],$parts[3]);
			break;
			case "451":
				$ident = "PASS none\nUSER OmnomIRC OmnomIRC OmnomIRC :OmnomIRC\nNICK OmnomIRC\n";
				sendLine($ident,$callingSocket,false);
			break;
		}
	}
}

function addLine($name1,$name2,$type,$message,$channel)
{
	global $socket,$curidFilePath;
	$curPosArr = mysql_fetch_array(sql_query("SELECT MAX('line_number') FROM `irc_lines`"));
	$curPos =  $curPosArr[0]+ 1;
	sql_query("INSERT INTO `irc_lines` (`name1`,`name2`,`message`,`type`,`channel`,`time`) VALUES ('%s','%s','%s','%s','%s','%s')",$name1,$name2,$message,$type,$channel,time());
	if ($type=="topic") {
		$temp = mysql_fetch_array(sql_query("SELECT * FROM `irc_topics` WHERE chan='%s'",strtolower($channel)));
		if ($temp["chan"]==NULL) {
			sql_query("INSERT INTO `irc_topics` (chan,topic) VALUES('%s','')",strtolower($channel));
		}
		sql_query("UPDATE `irc_topics` SET topic='%s' WHERE chan='%s'",$message,strtolower($channel));
	}
	$temp = mysql_fetch_array(sql_query("SELECT MAX(line_number) FROM irc_lines"));
	file_put_contents($curidFilePath,$temp[0]);
}

function processMessages()
{
	global $topicBotNick;
	$res = sql_query("SELECT * FROM irc_outgoing_messages");
	$lastline = 0;
	while ($row = mysql_fetch_array($res)) 
	{
		$colorAdding="12(O)";
		if ($row['fromSource']=='1')
			$colorAdding="7(C)";
		if ($row['channel'][0] != "#")
			continue;
		switch ($row['type']) {
		default:
		case "msg":
			if ($row['action'] == 0)
				sendLine("PRIVMSG $row[channel] :$colorAdding<$row[nick]> $row[message]");
			if ($row['action'] == 1)
				sendLine("PRIVMSG $row[channel] :$colorAdding6* ".$row['nick'].$row['message']);
			break;
		case "topic":
			sendLine("PRIVMSG $row[channel] :$colorAdding3* ".$row['nick']." has changed the topic to ".$row['message']);
			sendLine("PRIVMSG $topicBotNick : $row[channel] ".$row['message']);
			break;
		case "mode":
			sendLine("PRIVMSG $row[channel] :$colorAdding3* ".$row['nick']." set $row[channel] mode ".$row['message']);
			break;
		}
		
		
		$lastline = $row['prikey'];
	}
	if ($lastline != 0)
		sql_query("DELETE FROM `irc_outgoing_messages` WHERE prikey < %s",$lastline + 1);
}


function updateUserList($socket)
{
	global $channels;
	clearUserList();
	foreach($channels as $chan)
		if (isset($socket))
			sendLine("who $chan[0]",$socket,false); //Spam who's! \o/
		else
			sendLine("who $chan[0]"); //Spam who's! \o/
}

function clearUserList()
{
	global $userList;
	$userList=array();
	sql_query("DELETE FROM `irc_users`");
}

function userLeave($username,$channel)
{
	global $userList;
	$pos = array_search($username,$userList[$channel]);
	if ($pos)
	{
		unset($userList[$channel][$pos]);
	}
	sql_query("DELETE FROM `irc_users` WHERE `username` = '%s' AND `channel` = '%s' AND online='0'",$username,$channel);
}

function userQuit($username,$message,$socketToExclude)
{
	global $userList;
	foreach($userList as $chanName => $channel)
	{
		$pos = array_search($username,$channel);
		if ($pos)
		{
			sendLine("PRIVMSG $chanName :(#) *$username has quit $chanName($message)",$socketToExclude);
			addLine($username,'',"quit",$message,$chanName);
			unset($userList[$chanName][$pos]);
		}
	}
	addLine($username,'',"quit",$message,'');
	sql_query("DELETE FROM `irc_users` WHERE `username` = '%s' AND online='0'",$username);
}
function userNick($oldNick,$newNick,$socketToExclude)
{
	global $userList;
	foreach($userList as $chanName => $channel)
	{
		$pos = array_search($oldNick,$channel);
		if ($pos)
		{
			sendLine("PRIVMSG $chanName :(#) *$oldNick has changed nicks to $newNick",$socketToExclude);
			addLine($oldNick,$newNick,"nick",'',$chanName);
			$userList[$chanName][$pos] = $newNick;
		}
	}
	addLine($oldNick,$newNick,"nick",'','');
	sql_query("UPDATE `irc_users` SET `username`='%s' WHERE `username`='%s'",$newNick, $oldNick);
}

function userJoin($username,$channel)
{
	global $userList;
	$channel = str_replace(':', '', $channel);
	if (!isset($userList[$channel])) $userList[$channel] = Array();
	array_push($userList[$channel], $username);
	$tempSql = mysql_fetch_array(sql_query("SELECT * FROM irc_users WHERE username='%s' AND channel='%s' AND online='0'",$username,$channel));
	if ($tempSql["username"]==NULL)
		sql_query("INSERT INTO `irc_users` (`username`,`channel`) VALUES('%s','%s')",$username,$channel);
}


	error_reporting(0);
	foreach ($servers as $server)
	{
		$sockets[] = $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!socket_connect($socket,$server[0],$server[1]))
			die("Could not connect. Error: " . socket_last_error());
		socket_set_nonblock($socket);
	}

	sql_query("DELETE FROM `irc_outgoing_messages`");
	sql_query("DELETE FROM `irc_users`");
	sleep(8);
	
	sendLine($ident[0],$sockets[0],false);
	sendLine($ident[1],$sockets[1],false);
	$connected = true;
	while ($connected)
	{		
		foreach ($sockets as $socket)
		{
			if ($recBuf = socket_read($socket,1024))
			{
				echo ">>" . $recBuf;
				parseMsg($recBuf,$socket);
			}
			$errorcode = socket_last_error();
			socket_clear_error();
			if (!$recBuf && $errorcode == 0)
			{
				die("Connection lost!\n");
			}
		}
		foreach ($pings as $ping)
		{
			if ((time() - $ping) > 180)
			{
				sendLine("quit :Local ping timeout -- I am restarting");
				die("Ping timeout!\n");
			}
		}
		processMessages();
		usleep(2000);
	}
	foreach ($sockets as $socket) //Close all sockets on close
		socket_close($socket);
?>
