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
	include("Source/cachefix.php"); //This must be the first line in every file.
	include("Source/sql.php");
	include("Source/sign.php");
	include("Source/userlist.php");
	
	$curLine = $_GET['lineNum'];
	$channel = "#omnimaga";
	if (isset($_GET['channel']))
		$channel = base64_url_decode($_GET['channel']);
	$nick = "0";
	if (isset($_GET['nick']))
	{
		$nick = base64_url_decode($_GET['nick']);
		$signature = base64_url_decode($_GET['signature']);
		if (!checkSignature($nick,$signature))
			$nick = "0";
	}
	$pm = false;
	if ($channel[0] == "*") //PM
	{
		$sender = substr($channel,1);
		$channel = $nick;
		$pm = true;
	}
	$countBeforeQuit = 0;
	while (true)
	{
		if ($countBeforeQuit++ == 50)//Timeout after 25 seconds.
			die();
		if ($nick != "0")
			UpdateUser($nick,$channel);
		CleanOfflineUsers(); //This gets called often enough. We can't have have constant presence in the matrix without a helper app, this is the closest we'll get.
		$query = sql_query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND (`channel` = '%s' OR `channel` = '%s' OR (`channel` = '%s' AND `name1` = '%s'))",$curLine + 0,$channel,$nick,$pm?$sender:"0", $nick);
		$result = mysql_fetch_array($query);
		//Sorunome edit START
		$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_ignorelist` WHERE name='%s'",strtolower($nick)));
		$ignorelist = "";
		if ($userSql["name"]!=NULL) {
			$ignorelist = $userSql["ignores"];
		}
		//Sorunome edit END
		if (!isset($result[0]))
		{
			$result = mysql_fetch_array(sql_query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND locate('%s',`message`) != 0 AND NOT ((`type` = 'pm' AND `name1` <> '%s') OR (`type` = 'server'))",$curLine + 0,substr($nick,0,4), $nick));
			if (!isset($result[0])) {usleep(500000); continue;}
			if (strpos($userSql["ignores"],strtolower($result["name1"])."\n")===false) { //Sorunome edit
				echo $result['line_number'] . ":highlight:0:0:". base64_url_encode($result['channel']) . "::" . base64_url_encode($result['name1']) . ":" . base64_url_encode($result['message']);
			} else { //Sorunome edit START
				echo $result['line_number'].":curline:0:0:";
			} //Sorunome edit END
			die; //That's it, folks!
		}
		do {
			if (!isset($result['time'])) $result['time'] = time();
			if (strpos($userSql["ignores"],strtolower($result["name1"])."\n")===false) { //sorunome edit
				switch (strtolower($result['type']))
				{
					case "pm":
					case "message":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result['message'])) . ':' . base64_url_encode(htmlspecialchars("0"));break;
					case "action":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result['message'])) . ':' . base64_url_encode(htmlspecialchars("0"));break;
					case "join":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1']));break;
					case "part":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
					case "kick":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["name2"])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
					case "quit":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
					case "mode":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
					case "nick":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["name2"]));break;
					case "topic":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
					case "reload":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . "::";break;
					case "server":
						echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
					case "topic":
					echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
				}
				echo "\n";
			} else { //Sorunome edit START
				echo $result['line_number'].":curline:0:0:";
			} //Sorunome edit END
		} while ($result = mysql_fetch_array($query));
		break;
	}
?>
