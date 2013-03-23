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
	include("Source/cachefix.php"); //This must be the first line in every file.
	include("Source/sql.php");
	include("Source/sign.php");

	if (!isset($_GET['signature']) || !isset($_GET['nick']) || !isset($_GET['message']) || !isset($_GET['channel']) || !isset($_GET['id'])) die("Missing Required Field");
	if (strlen($_GET['message']) < 4) die("Bad message");
	if (!checkSignature($_GET['nick'],$_GET['signature'],true)) die("Bad signature");
	
	
	$message = base64_url_decode(str_replace(" ","+",$_GET['message']));
	$type = "message";
	$message = str_replace(array("\r", "\r\n", "\n"), ' ', $message);
	$parts = explode(" ",$message);
	if ($parts[0] == "/me")
	{
		$type = "action";
		$message = preg_replace("/\/me/","",$message,1);
	}
	if (strlen($message) <= 0) die("Bad message");
	$nick = html_entity_decode(base64_url_decode($_GET['nick']));
	$channel = base64_url_decode($_GET['channel']);
	$pm = false;
	if ($parts[0] == "/msg" || $parts[0] == "/pm")
	{
		$pm=true;
		$channel = $parts[1];
		$message = "";
		unset($parts[0]);
		unset($parts[1]);
		$message = implode(" ",$parts);
		$type = "pm";
	}
	
	//Sorunome edit START
	$sendNormal = true;
	$reload = false;
	$sendPm = false;
	if ($parts[0] == "/ignore") {
		unset($parts[0]);
		$ignoreuser = strtolower(implode(" ",$parts));
		$returnmessage = "";
		$sendNormal = false;
		$sendPm = true;
		$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_ignorelist` WHERE name='%s'",strtolower($nick)));
		if ($userSql["name"]==NULL) {
			sql_query("INSERT INTO `irc_ignorelist` (name,ignores) VALUES('%s','')",strtolower($nick));
			$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_ignorelist` WHERE name='%s'",strtolower($nick)));
		}
		if (strpos($userSql["ignores"],$ignoreuser."\n")===false) {
			$userSql["ignores"].=$ignoreuser."\n";
			sql_query("UPDATE `irc_ignorelist` SET ignores='%s' WHERE name='%s'",$userSql["ignores"],strtolower($nick));
			$returnmessage = "\x033Now ignoring $ignoreuser.";
			$reload = true;
		} else {
			$returnmessage = "\x034ERROR: couldn't ignore $ignoreuser: already ignoring.";
		}
	}
	if ($parts[0] == "/unignore") {
		unset($parts[0]);
		$ignoreuser = strtolower(implode(" ",$parts));
		$returnmessage = "";
		$sendNormal = false;
		$sendPm = true;
		$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_ignorelist` WHERE name='%s'",strtolower($nick)));
		if ($userSql["name"]==NULL) {
			sql_query("INSERT INTO `irc_ignorelist` (name,ignores) VALUES('%s','')",strtolower($nick));
			$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_ignorelist` WHERE name='%s'",strtolower($nick)));
		}
		$allIgnoreUsers = explode("\n","\n".$userSql["ignores"]);
		$unignored = false;
		for ($i;$i<sizeof($allIgnoreUsers);$i++) {
			echo $allIgnoreUsers[$i]." ".$ignoreuser."\n";
			if ($allIgnoreUsers[$i]==$ignoreuser) {
				$unignored = true;
				unset($allIgnoreUsers[$i]);
			}
		}
		unset($allIgnoreUsers[0]); //whitespace bug
		$userSql["ignores"] = implode("\n",$allIgnoreUsers);
		if ($ignoreuser=="*") {$userSql["ignores"]="";$unignored=true;} //unignore all
		if ($unignored) {
			$returnmessage = "\x033You are not more ignoring $ignoreuser";
			if ($ignoreuser=="*") $returnmessage = "\x033You are no longer ignoring anybody.";
			mysql_fetch_array(sql_query("UPDATE `irc_ignorelist` SET ignores='%s' WHERE name='%s'",$userSql["ignores"],strtolower($nick)));
			$reload = true;
		} else {
			$returnmessage = "\x034ERROR: You weren't ignoring $ignoreuser";
		}
	}
	if ($parts[0] == "/ignorelist") {
		$returnmessage = "";
		$sendNormal = false;
		$sendPm = true;
		$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_ignorelist` WHERE name='%s'",strtolower($nick)));
		if ($userSql["name"]==NULL) {
			sql_query("INSERT INTO `irc_ignorelist` (name,ignores) VALUES('%s','')",strtolower($nick));
			$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_ignorelist` WHERE name='%s'",strtolower($nick)));
		}
		$returnmessage = "\x033Ignored users: ".str_replace("\n",",",$userSql["ignores"]);
	}
	if ($parts[0] == "/reload" || $parts[0] == "/refresh") {$reload = true;$sendNormal=false;}
	if ($parts[0] == "/position") {
		$returnmessage = "";
		$sendNormal = false;
		$sendPm = true;
		$returnmessage = file_get_contents("http://www.omnimaga.org/checkLogin.php?op&u=".$_GET['id']."&nick=".$_GET['nick']);
	}
	//Sorunome edit END
	if ($channel[0] == "*") //PM
	{
		$type = "pm";
		$channel = substr($channel,1);
	}
	
	if ($sendNormal) {//sorunome edit
		sql_query("INSERT INTO `irc_outgoing_messages` (message,nick,channel,action) VALUES('%s','%s','%s','%s')",$message,$nick,$channel,($type=="action")?'1':'0');
		sql_query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s',1)",$nick,$message,$type,$channel,time());
	}
	if ($sendPm) {//sorunome edit START
		sql_query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s',1)","OmnomIRC",$returnmessage,"server",$nick,time());
	}
	if ($reload) {
		sql_query("INSERT INTO `irc_lines` (name1,message,type,channel,time,online) VALUES('%s','%s','%s','%s','%s',1)","OmnomIRC","THE GAME","reload",$nick,time());
	}
	if (isset($_GET['textmode'])) {
		session_start();
		echo "<html><head><meta http-equiv=\"refresh\" content=\"1;url=textmode.php?update=".$_SESSION['curline']."\"></head><body>Sending message...</body></html>";
	}
	//sorunome edit END
?>
