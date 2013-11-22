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
	//error_reporting(E_ALL);
	//ini_set('display_errors','1');
	include_once(realpath(dirname(__FILE__)).'/Source/cachefix.php'); //This must be the first line in every file.
	include_once(realpath(dirname(__FILE__)).'/config.php');
	header('Content-type: text/javascript');
	if(!isset($_GET['userinfo'])){
		ob_start();
		if(isset($_GET['count']))
			$count = $_GET['count'];
		else
			$count = 1;

		$channel = $defaultChan;
		if(isset($_GET['channel']))
			$channel = base64_url_decode($_GET['channel']);
		$nick = "0";
		if(isset($_GET['nick'])){
			$nick = base64_url_decode($_GET['nick']);
			$signature = base64_url_decode($_GET['signature']);
			if(!checkSignature($nick,$signature))
				$nick = "0";
			$userSql = getUserstuffQuery($nick);
			if(strpos($userSql["bans"],base64_url_decode($_GET['channel'])."\n")!==false || $userSql['globalBan']=='1')
				die("addLine('999999999999999999999999999:server:0:0:T21ub21JUkM=:RVJST1IgLSBiYW5uZWQ=');");
			if(isset($_GET['id']) && isGlobalOp($nick,$signature,$_GET['id'])){
				echo "try{document.getElementById('adminLink').style.display='';}catch(err){};";
			}
		}
		if($channel[0]!="*" and $channel[0]!="#" and $channel[0]!="@" and $channel[0]!="&")
			$channel = "0";
		
		if($channel[0] == "*"){ // PM
			$sender = substr($channel,1);
			$channel = $nick;
			$res = $sql->query("SELECT x.* FROM (
														SELECT * FROM `irc_lines` 
														WHERE 
														((`channel` = '%s'
														AND `name1` = '%s')
														OR
														(`channel` = '%s'
														AND `name1` = '%s'))
															AND NOT ((`type` = 'join' OR `type` = 'part') AND `Online` = 1)
														ORDER BY `line_number` DESC 
														LIMIT %s
													) AS x
													ORDER BY `line_number` ASC",$channel,$sender,$sender,$channel,$count + 0);
		}else{
			$res = $sql->query("SELECT x.* FROM (
														SELECT * FROM `irc_lines` 
														WHERE (`type` != 'server' AND ((`channel` = '%s' OR `channel` = '%s')
															AND NOT ((`type` = 'join' OR `type` = 'part') AND `Online` = 1)))
															OR (`type` = 'server' AND channel='%s' AND name2='%s')
														ORDER BY `line_number` DESC 
														LIMIT %s
													) AS x
													ORDER BY `line_number` ASC",$channel,$nick,$nick,$channel,$count + 0);
		}
		echo "void('$nick');";
		$userSql = $sql->query("SELECT * FROM `irc_userstuff` WHERE name='%s' LIMIT 1",strtolower($nick));
		if(isset($userSql[0])){
			$userSql = $userSql[0];
		}else{
			$userSql = array('name' => NULL,'ignores' => '');
		}
		$ignorelist = '';
		if($userSql['name']!=NULL){
			$ignorelist = $userSql['ignores'];
		}
		foreach($res as $result){
			if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
				echo "addLine('";
				$lineBeginning = $result['line_number'].':'.$result['type'].':'.$result['Online'].':'.$result['time'].':'.base64_url_encode(htmlspecialchars($result['name1'])).':';
				switch(strtolower($result['type'])){
					case 'pm':
					case 'message':
					case 'action':
					case 'pmaction':
						echo $lineBeginning.base64_url_encode(htmlspecialchars($result['message'])).':'.base64_url_encode('0');
						break;
					case 'join':
						echo $lineBeginning;
						break;
					case 'kick':
						echo $lineBeginning.':'.base64_url_encode(htmlspecialchars($result['name2'])).':'.base64_url_encode(htmlspecialchars($result['message']));
						break;
					case 'quit':
					case 'mode':
					case 'server':
					case 'part':
					case "topic":
						echo $lineBeginning.base64_url_encode(htmlspecialchars(trim($result['message'])));
						break;
					case 'nick':
						echo $lineBeginning.base64_url_encode(htmlspecialchars($result['name2']));
						break;
				}
				echo "');";
			}
		}
		$temp = $sql->query("SELECT MAX(line_number) FROM `irc_lines`");
		$curMax = $temp[0]['MAX(line_number)'];
		echo "addLine('".$curMax.":curline');";
		$curtopic = $sql->query("SELECT topic FROM `irc_topics` WHERE `chan`='%s'",strtolower($channel));
		if(isset($curtopic[0])){
			$curtopic = $curtopic[0]['topic'];
		}else{
			$curtopic = '';
		}
		echo "addLine('".$curMax.':topic:0:'.time().'::'.base64_url_encode(htmlspecialchars($curtopic))."');";
		$users = Array();
		
		$result = $sql->query("SELECT username,online,channel FROM `irc_users` WHERE `channel`='%s' AND `isOnline`='1'",$channel);
		foreach($result as $user){
			$users[count($users)][0] = strtolower($user['username']);
			$users[count($users) - 1][1] = $user['username'];
			$users[count($users) - 1][2] = $user['online'];
			$users[count($users) - 1][3] = $user['channel'];
		}
		
		asort($users);
		foreach($users as $user){
			echo "addUser('" . base64_url_encode(htmlspecialchars($user[1])) . ":" . $user[2] . "');";
		}
		if(isset($_GET['calc'])){
			ob_end_clean();
			$temp = $sql->query("SELECT MAX(line_number) FROM `irc_lines`");
			echo $temp[0]['MAX(line_number)'];
		}else
			ob_end_flush();
	}else{
		if(isset($_GET['name']) && isset($_GET['chan']) && isset($_GET['online'])){
			$temp = $sql->query("SELECT `lastMsg` FROM `irc_users` WHERE username='%s' AND channel='%s' AND online='%s'",base64_url_decode($_GET['name']),base64_url_decode($_GET['chan']),$_GET['online']);
			echo $temp[0]['lastMsg'];
		}else{
			echo 'Bad parameters';
		}
	}
?>
