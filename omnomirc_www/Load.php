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
	#error_reporting(E_ALL);
	#ini_set('display_errors', '1');
	include("Source/cachefix.php"); //This must be the first line in every file.
	header('Content-type: text/javascript');
	include("Source/sql.php");
	include("Source/sign.php");
	include("Source/userlist.php");
	function getUserstuffQuery($nick) {
		$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($nick)));
		if ($userSql["name"]==NULL) {
			sql_query("INSERT INTO `irc_userstuff` (name) VALUES('%s')",strtolower($nick));
			$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($nick)));
		}
		return $userSql;
	}
	ob_start();
	$count = $_GET['count'];
	$channel = $defaultChan;
	if (isset($_GET['channel']))
		$channel = base64_url_decode($_GET['channel']);
	$nick = "0";
	if (isset($_GET['nick']))
	{
		$nick = base64_url_decode($_GET['nick']);
		$signature = base64_url_decode($_GET['signature']);
		if ($signature != base64_url_encode(sign($nick)))
			$nick = "0";
		$userSql = getUserstuffQuery($nick);
		if (strpos($userSql["bans"],base64_url_decode($_GET['channel'])."\n")!==false)
			die("addLine('999999999999999999999999999:server:0:0:T21ub21JUkM=:RVJST1IgLSBiYW5uZWQ=');");
	}
	
	
	if ($channel[0] == "*") //PM
	{
		$sender = substr($channel,1);
		$channel = $nick;
		$res = sql_query("SELECT x.* FROM (
													SELECT * FROM `irc_lines` 
													WHERE 
													(`channel` = '%s'
													AND `name1` = '%s')
													OR
													(`channel` = '%s'
													AND `name1` = '%s')
													ORDER BY `line_number` DESC 
													LIMIT %s
												) AS x
												ORDER BY `line_number` ASC",$channel,$sender,$sender,$channel,$count + 0);
	}
	else
	{
		$res = sql_query("SELECT x.* FROM (
													SELECT * FROM `irc_lines` 
													WHERE `channel` = '%s' OR `channel` = '%s'
													ORDER BY `line_number` DESC 
													LIMIT %s
												) AS x
												ORDER BY `line_number` ASC",$channel,$nick,$count + 0);
	}
	echo "void('$nick');";
	while ($result = mysql_fetch_array($res))
	{
		//Sorunome edit START
		$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($nick)));
		$ignorelist = "";
		if ($userSql["name"]!=NULL) {
			$ignorelist = $userSql["ignores"];
		}
		//Sorunome edit END
		if (strpos($userSql["ignores"],strtolower($result["name1"])."\n")===false) { //Sorunome edit
			echo "addLine('";
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
				case "server":
					echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
				case "topic":
					echo $result['line_number'] . ":" . $result['type'] . ":" . $result['Online'] . ":" . $result['time'] . ":" . base64_url_encode(htmlspecialchars($result['name1'])) . ":" . base64_url_encode(htmlspecialchars($result["message"]));break;
			}
			echo "');";
		} //Sorunome edit
	}
	
	$curMax = mysql_fetch_array(sql_query("SELECT MAX(`line_number`) FROM `irc_lines`"));
	echo "addLine('" . $curMax[0] . ":curline');";
	//Sorunome edit START
	$curtopic = mysql_fetch_array(sql_query("SELECT * FROM `irc_topics` WHERE `chan`='%s'",strtolower($channel)));
	echo "addLine('" . $curMax[0] . ":topic:0:" . time() . "::" . base64_url_encode(htmlspecialchars($curtopic["topic"])) . "');";
	//Sorunome edit END
	$users = Array();
	
	$result = sql_query("SELECT * FROM `irc_users` WHERE `channel`='%s'",$channel);
	while ($user = mysql_fetch_array($result))
	{
		$users[count($users)][0] = strtolower($user['username']);
		$users[count($users) - 1][1] = $user['username'];
		$users[count($users) - 1][2] = $user['online'];
		$users[count($users) - 1][3] = $user['channel'];
	}
	
	asort($users);
	foreach ($users as $user)
	{
		echo "addUser('" . base64_url_encode(htmlspecialchars($user[1])) . ":" . $user[2] . "');";
	}
	if (isset($_GET['calc'])) {
		ob_end_clean();
		$temp = mysql_fetch_array(sql_query("SELECT MAX(line_number) FROM `irc_lines`"));
		echo $temp[0];
	} else
		ob_end_flush();
?>
