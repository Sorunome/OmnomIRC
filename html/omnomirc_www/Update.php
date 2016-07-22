<?PHP
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2016 Sorunome

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
namespace oirc;
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');

$net = Networks::get(OIRC::$you->getNetwork());
if(!OIRC::$you->isLoggedIn() && $net['config']['guests'] == 0){
	$msg = 'You need to log in to be able to view chat!';
	if(isset($_GET['noLoginErrors'])){
		Json::add('message',$msg);
	}else{
		Json::addError($msg);
	}
	echo Json::get();
	die();
}

if(isset($_GET['lineNum'])){
	$curline = (int)$_GET['lineNum'];
	if($curline < Oirc::getCurid()-200){
		Json::addWarning('lineNum too far in the past, giving only 200');
		$curline = Oirc::getCurid()-200;
	}
}else{
	$curline = Oirc::getCurid();
	Json::addWarning('lineNum not set, defaulting to newest one ('.$curline.')');
}
if(OIRC::$you->isBanned()){
	Json::add('banned',true);
	Json::addError('banned');
	echo Json::get();
	die();
}
if(isset($_GET['high'])){
	$numCharsHighlight = (int)$_GET['high'];
}else{
	Json::addWarning('Not set num chars to highlight, defaulting to 4');
	$numCharsHighlight = 4;
}

$nick = OIRC::$you->nick;

$countBeforeQuit = 0;
OIRC::$you->update();
$channel = Sql::query("SELECT {db_prefix}getchanid(?) AS chan",array(OIRC::$you->chan));
$channel = $channel[0]['chan'];

while(true){
	if($countBeforeQuit++ >= 50){//Timeout after 25 seconds.
		OIRC::$you->update();
		echo Json::get();
		die();
	}
	if(Oirc::getCurid()<=$curline){
		if(isset($_GET['nopoll']) && $_GET['nopoll']){
			Json::add('lines',array());
			echo Json::get();
			exit;
		}
		usleep(500000);
		continue;
	}
	OIRC::$you->update();
	if($nick){
		$query = Sql::query("SELECT `line_number`,`name1`,`name2`,`message`,`type`,`channel`,`time`,`Online`,`uid` FROM `{db_prefix}lines`
			WHERE
				`line_number` > ?
			AND
			(
				`channel` = ?
				OR
				`name2`=?
		)",array($curline,$channel,OIRC::$you->getPmHandler()));
	}else{
		$query = Sql::query("SELECT `line_number`,`name1`,`name2`,`message`,`type`,`channel`,`time`,`Online`,`uid` FROM `{db_prefix}lines` WHERE `line_number` > ? AND `channel` = ?",array($curline,$channel));
	}
	$result = $query[0];
	$userSql = OIRC::$you->info();
	$ignorelist = '';
	if($userSql['name']!=NULL) {
		$ignorelist = $userSql['ignores'];
	}
	$lines = Array();
	if($nick===false){
		$lines[] = Array(
			'curline' => 0,
			'type' => 'relog',
			'network' => 0,
			'time' => time(),
			'name' => 'OmnomIRC',
			'message' => 'Time to relog!',
			'name2' => '',
			'chan' => '',
			'uid' => -1
		);
	}
	if($result['line_number'] === NULL){ // no new lines, check for highlights
		$temp = Sql::query("SELECT `line_number`,`name1`,`name2`,`message`,`type`,c.`chan` AS `channel`,`time`,`Online`,`uid` FROM `{db_prefix}lines` l
			INNER JOIN {db_prefix}channels c ON l.`channel` = c.`channum`
			WHERE
				`line_number` > ?
				AND
				locate(?,`message`) != 0
				AND NOT (
					((`type` = 'pm' OR `type` = 'pmaction') AND `name1` <> ? AND `online` = ?)
				)",array($curline,substr($nick,0,$numCharsHighlight),$nick,OIRC::$you->getNetwork()));
		$result = $temp[0];
		if($result['line_number'] === NULL){
			$temp = Sql::query("SELECT MAX(line_number) AS max FROM `{db_prefix}lines`");
			$curline = (int)$temp[0]['max'];
			usleep(500000);
			continue;
		}
		if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
			$lines[] = Array(
				'curline' => (int)$result['line_number'],
				'type' => 'highlight',
				'network' => (int)$result['Online'],
				'time' => (int)$result['time'],
				'name' => $result['name1'],
				'message' => $result['message'],
				'name2' => $result['name2'],
				'chan' => $result['channel'],
				'uid' => (int)$result['uid']
			);
		}else{
			$lines[] = Array(
				'curline' => (int)$result['line_number'],
				'type' => 'curline',
				'network' => 0,
				'time' => time(),
				'name' => '',
				'message' => '',
				'name2' => '',
				'chan' => '',
				'uid' => -1
			);
		}
		Json::add('lines',$lines);
		echo Json::get();
		exit;
	}
	foreach($query as $result){
		if(!isset($result['time'])){
			$result['time'] = time();
		}
		if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
			$lines[] = Array(
				'curline' => (int)$result['line_number'],
				'type' => $result['type'],
				'network' => (int)$result['Online'],
				'time' => (int)$result['time'],
				'name' => $result['name1'],
				'message' => $result['message'],
				'name2' => $result['name2'],
				'chan' => OIRC::$you->chan,
				'uid' => (int)$result['uid']
			);
		}else{
			$lines[] = Array(
				'curline' => (int)$result['line_number'],
				'type' => 'curline',
				'network' => 0,
				'time' => time(),
				'name' => '',
				'message' => '',
				'name2' => '',
				'chan' => '',
				'uid' => -1
			);
		}
	}
	Json::add('lines',$lines);
	echo Json::get();
	break;
}
