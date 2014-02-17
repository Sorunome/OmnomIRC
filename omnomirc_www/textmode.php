<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2014 Sorunome

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
//session_start();
//error_reporting(E_ALL);
session_start();

ini_set('display_errors', '0');
include_once(realpath(dirname(__FILE__)).'/config.php');
$count = '25';
$channel = $defaultChan;
$nick = '0';
if(isset($_GET['login'])){
	//session_destroy();
	$_SESSION['sig'] = $_GET['sig'];
	$_SESSION['nick'] = $_GET['nick'];
	$_SESSION['id'] = $_GET['id'];
}
if(isset($_GET['message'])){
	echo "<html><body><form action='textmode.php?sendMessage' method='post'><input type='text' name='message' autofocus style='width:100%'><input type='Submit' value='Send'></form><table>".$_SESSION['content']."</table></body></html>";
}elseif (isset($_GET['sendMessage'])){
	header("Location: message.php?textmode&nick=".base64_url_encode($_SESSION['nick'])."&signature=".base64_url_encode($_SESSION['sig'])."&message=".base64_url_encode($_POST['message'])."&channel=I29tbmltYWdh&id=".$_SESSION['id']);
}else{
	
	if(isset($_GET['update']) && isset($_SESSION['curline'])){
		$pm = false;
		$query = $sql->query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND (`channel` = '%s' OR `channel` = '%s' OR (`channel` = '%s' AND `name1` = '%s'))",$_SESSION['curline'] + 0,$channel,$nick,$pm?$sender:"0", $nick);
	}else{
		$_SESSION['curline'] = 0;
		$query = $sql->query("SELECT x.* FROM (
													SELECT * FROM `irc_lines` 
													WHERE `channel` = '%s' OR `channel` = '%s'
													ORDER BY `line_number` DESC 
													LIMIT %s
												) AS x
												ORDER BY `line_number` ASC",$channel,$nick,$count + 0);
		$_SESSION['content'] = "";
	}
	foreach($query as $result){
		$line = "<tr>";
		$starBeginning = '<td>* '.htmlspecialchars($result['name1']).' ';
		switch (strtolower($result['type'])){
			case 'pm':
			case 'message':
				$line .= '<td>&lt;'.htmlspecialchars($result['name1']).'&gt; '.htmlspecialchars($result['message']).'</td>';
				break;
			case 'pmaction':
			case 'action':
				$line .= $starBeginning.htmlspecialchars($result['message']).'</td>';
				break;
			case 'join':
				if ($result['Online']=='0')
					$line .= $starBeginning."joined $channel</td>";
				break;
			case 'part':
				if ($result['Online']=='0')
					$line .= $starBeginning."part $channel (" . htmlspecialchars($result['message']).')</td>';
				break;
			case 'kick':
				$line .= $starBeginning.'kicked '.htmlspecialchars($result['name2']).' ('.htmlspecialchars($result['message']).')</td>';
				break;
			case 'quit':
				if ($result['Online']=='0')
					$line .= $starBeginning.'quit ('.htmlspecialchars($result['message']).')</td>';
				break;
			case 'mode':
				$line .= $starBeginning.'has set '.htmlspecialchars($result['message']).'</td>';
				break;
			case 'nick':
				$line .= $starBeginning.'changed nick to '.htmlspecialchars($result['name2']).'</td>';
				break;
			case 'topic':
				$line .= $starBeginning.'changed topic to '.htmlspecialchars($result['message']).'</td>';
				break;
		}
		$_SESSION['curline'] = $result['line_number'];
		if(isset($_SESSION['content']))
			$_SESSION['content'] = $line."</tr>".$_SESSION['content'];
		else
			$_SESSION = $line."</tr>";
	}
	echo "<html><head><meta http-equiv=\"refresh\" content=\"5;url=textmode.php?update=".$_SESSION['curline']."\"></head><body><a href=\"textmode.php?message=".$_SESSION['curline']."\" autofocus>Click here to write a message</a><table>".$_SESSION['content']."</table></body></html>";
}
?>