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
$servers = Array();
$servers[] = Array("<first irc server>",6667);
$servers[] = Array("<second irc server>",6667);
$pings = Array();


$IRCBOT=true; //Don't print JS from channels.php
include("/path/to/omnomirc/Channels.php");

$sockets = Array();

$fragment = false;
$fragLine = "";
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
	
	$message = trim($message);
	return $message;
}

function parseMsg($allMessage,$callingSocket)
{

	global $socket,$hasIdent,$ident,$chanStr,$userList,$fragment,$fragLine,$sockets;
	for ($i=0;$i<count($sockets);$i++)
				if ($sockets[$i] == $callingSocket)
					$pings[$i] = time();
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
				
				if ($parts[3] == ":UPDATECHANS")
				{
					echo "Updating chans";
					sendLine("join #,0");
					$IRCBOT = true;
					include("/path/to/omnomirc/Channels.php"); //Update channels array/strings
					sendLine("JOIN $chanStr\n");
					updateUserList();
				}
				if ($isChan) break;
				if ($parts[0]==":OmnomIRC!<ident on first irc network>" || $parts[0]==":OmnomIRC!<ident on second irc network>") {
					$topic = getMessage($parts,5,false);
					sendLine("TOPIC ".$parts[4]." :".$topic,$callingSocket,false);
				}
			break;
			case "376": //End of MOTD
				sendLine("JOIN $chanStr\n",$callingSocket,false);
			break;
			case "451":
				$ident = "PASS none\nUSER TopicBot TopicBot TopicBot :TopicBot\nNICK TopicBot\n";
				sendLine($ident,$callingSocket,false);
			break;
		}
	}
}



	error_reporting(0);
	foreach ($servers as $server)
	{
		$sockets[] = $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!socket_connect($socket,$server[0],$server[1]))
			die("Could not connect. Error: " . socket_last_error());
		socket_set_nonblock($socket);
	}
	
	sleep(8);
	$ident[] = "PASS NONE\nUSER TopicBot TopicBot TopicBot :TopicBot\nNICK TopicBot\n";
	$ident[] = "PASS NONE\nUSER TopicBot TopicBot TopicBot :TopicBot\nNICK TopicBot\n";
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
		usleep(2000);
	}
	foreach ($sockets as $socket) //Close all sockets on close
		socket_close($socket);
?>