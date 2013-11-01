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
	include_once(realpath(dirname(__FILE__)).'/Source/cachefix.php'); //This must be the first line in every file.
	include_once(realpath(dirname(__FILE__)).'/config.php');
	
	
	$curLine = $_GET['lineNum'];
	$channel = $defaultChan;
	if (isset($_GET['channel']))
		$channel = base64_url_decode($_GET['channel']);
	if ($channel[0]!='*' and $channel[0]!='#' and $channel[0]!='@' and $channel[0]!='&')
		$channel = '0';
	$nick = '0';
	if(isset($_GET['nick'])){
		$nick = base64_url_decode($_GET['nick']);
		$signature = base64_url_decode($_GET['signature']);
		if (!checkSignature($nick,$signature))
			$nick = '0';
		if ($nick=='0') {
			$userSql = getUserstuffQuery($nick);
			if (strpos($userSql['bans'],base64_url_decode($_GET['channel'])."\n")!==false) {
				sleep(30);
				die();
			}
		}
	}
	if(isset($_GET['high'])){
		$numCharsHighlight = (int)$_GET['high'];
	}else{
		$numCharsHighlight = 4;
	}
	$pm = false;
	if($channel[0] == '*'){ //PM
		$sender = substr($channel,1);
		$channel = $nick;
		$pm = true;
	}
	$countBeforeQuit = 0;
	while(true){
		if($countBeforeQuit++ == 50){//Timeout after 25 seconds.
			if(isset($_GET['calc']))
				UpdateUser('OmnomIRC',$defaultChan,'2');
			elseif($nick != '0')
				UpdateUser($nick,$channel,'1');
			CleanOfflineUsers();
			die();
		}
		if(file_get_contents($curidFilePath)<=$curLine) {
			usleep(500000);
			continue;
		}
		if(isset($_GET['calc']))
			UpdateUser('OmnomIRC',$defaultChan,'2');
		elseif($nick != '0')
			UpdateUser($nick,$channel,'1');
		CleanOfflineUsers();
		if(!isset($_GET['calc']) and $nick!='0')
			$query = sql_query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND (((`channel` = '%s' OR `channel` = '%s' OR (`channel` = '%s' AND `name1` = '%s')) AND `type`!='server') OR (`type` = 'server' AND channel='%s' AND name2='%s'))",$curLine + 0,$channel,$nick,$pm?$sender:"0",$nick,$nick,$channel);
		else
			$query = sql_query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND (`channel` LIKE '%s')",$curLine + 0,"%#%");
		
		$result = mysql_fetch_array($query);
		if(!isset($_GET['calc'])) {
			$userSql = mysql_fetch_array(sql_query("SELECT * FROM `irc_userstuff` WHERE name='%s'",strtolower($nick)));
			$ignorelist = '';
			if($userSql['name']!=NULL) {
				$ignorelist = $userSql['ignores'];
			}
		}
		if (!isset($result[0])){
			if (!isset($_GET['calc']))
				$result = mysql_fetch_array(sql_query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND locate('%s',`message`) != 0 AND NOT (((`type` = 'pm' OR `type` = 'pmaction') AND `name1` <> '%s') OR (`type` = 'server'))",$curLine + 0,substr($nick,0,4), $nick));
			else
				$result = mysql_fetch_array(sql_query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND (`channel` LIKE '%s')",$curLine + 0,"%#%"));
			if(!isset($result[0])){
				$temp = mysql_fetch_array(sql_query("SELECT MAX(line_number) FROM `irc_lines`"));
				$curLine = $temp[0];
				usleep(500000);
				continue;
			}
			if(!isset($_GET['calc'])){
				if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
					echo $result['line_number'].':highlight:0:0:'.base64_url_encode($result['channel']).'::'.base64_url_encode($result['name1']).':'.base64_url_encode($result['message']);
				}else{
					echo $result['line_number'].':curline:0:0:';
				}
				die; //That's it, folks!
			}
		}
		do{
			if(!isset($_GET['calc'])){
				if(!isset($result['time']))
					$result['time'] = time();
				if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
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
					echo "\n";
				}else{
					echo $result['line_number'].':curline:0:0:';
				}
			}else{
				if($result['Online']=='2')
					$addStr=$result['name1'].':';
				else
					$addStr=':';
				$addStr .= $result['line_number'].':'.htmlspecialchars($result['channel']).':';
				switch (strtolower($result['type'])) {
					case 'pm':
					case 'message':
						echo $addStr.htmlspecialchars($result['name1']).':'.htmlspecialchars($result['message']);
						break;
					case 'action':
					case 'pmaction':
						echo $addStr.'*'.htmlspecialchars($result['name1'])." ".htmlspecialchars($result['message']);
						break;
					case 'join':
						if($result['Online']!='1')
							echo $addStr.'*'.htmlspecialchars($result['name1']).' has joined '.htmlspecialchars($result['channel']);
						else
							echo $addStr;
						break;
					case 'part':
						if($result['Online']!='1')
							echo $addStr.'*'.htmlspecialchars($result['name1']).' has parted '.htmlspecialchars($result['channel']);
						else
							echo $addStr;
						break;
					case 'quit':
						if($result['Online']!='1')
							echo $addStr.'*'.$result['name1'].' has quit ('.$result['message'].')';
						else
							echo $addStr;
						break;
					case 'mode':
						echo $addStr.'*'.htmlspecialchars($result['name1']).' has set mode '.htmlspecialchars($result['message']);
					break;
					case 'nick':
						echo $addStr.'*'.htmlspecialchars($result['name1']).' has changed nick to '.htmlspecialchars($result['name2']);
						break;
					case 'topic':
						echo $addStr.'*'.htmlspecialchars($result['name1']).' has set topic to '.htmlspecialchars($result['message']);
						break;
					default:
						echo $addStr;
						break;
				}
				echo "\n";
			}
		}while($result = mysql_fetch_array($query));
		break;
	}
?>