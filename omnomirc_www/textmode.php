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
session_start();
if(isset($_GET['login'])){
	$_SESSION['content'] = '';
}
$textmode = true;
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');


if(isset($_GET['message'])){
	echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /></head><body><form action='textmode.php?sendMessage&curline=".((int)$_GET['curline'])."&".$you->getUrlParams()."' method='post'><input type='text' name='message' autofocus autocomplete=\"off\" style='width:100%'><input type='Submit' value='Send'></form><a href=\"textmode.php?curline=".((int)$_GET['curline'])."&".$you->getUrlParams()."\">Cancle</a><table>".$_SESSION['content']."</table></body></html>";
}elseif (isset($_GET['sendMessage'])){
	header("Location: message.php?textmode&curline=".((int)$_GET['curline'])."&".$you->getUrlParams()."&message=".base64_url_encode($_POST['message']));
}else{
	$banned = false;
	if(isset($_GET['update']) && isset($_GET['curline']) && !(isset($_SESSION['content']) && $_SESSION['content']==='')){
		$curline = (int)$_GET['curline'];
		$query = $sql->query("SELECT * FROM `irc_lines` WHERE `line_number` > %s AND (`channel` = '%s' OR `channel` = '%s') ORDER BY `line_number` ASC",(int)$curline,$you->chan,$you->nick);
		$lines = $omnomirc->getLines($query);
	}else{
		$curline = 0;
		if($you->isBanned()){
			$banned = true;
			$liens = Array(
				Array(
						'curLine' => (int)$curMax,
						'type' => 'server',
						'network' => 0,
						'time' => time(),
						'name' => '',
						'message' => 'Banned',
						'name2' => '',
						'chan' => $you->chan
				)
			);
		}else{
			$lines = $omnomirc->loadChannel(25);
		}
		$_SESSION['content'] = '';
	}
	foreach($lines as $result){
		$line = "<tr>";
		$starBeginning = '<td>* '.htmlspecialchars($result['name']).' ';
		switch (strtolower($result['type'])){
			case 'pm':
			case 'message':
				$line .= '<td>&lt;'.htmlspecialchars($result['name']).'&gt; '.htmlspecialchars($result['message']).'</td>';
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
			case 'server':
				$line .= '<td>* '.htmlspecialchars($result['message']).'</td>';
				break;
		}
		if((int)$result['curLine'] > $curline){
			$curline = (int)$result['curLine'];
		}
		if(isset($_SESSION['content'])){
			$_SESSION['content'] = $line."</tr>".$_SESSION['content'];
		}else{
			$_SESSION['content'] = $line."</tr>";
		}
	}
	echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />".($banned?'':("<meta http-equiv=\"refresh\" content=\"5;url=textmode.php?update=".time()."&curline=".$curline."&".$you->getUrlParams()."\">"))."</head><body><a href=\"textmode.php?message&curline=".$curline."&".$you->getUrlParams()."\" autofocus>Click here to write a message</a><br>Channel: ".($you->chan)."<table>".$_SESSION['content']."</table></body></html>";
}
?>