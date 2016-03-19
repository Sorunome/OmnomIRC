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

$net = $networks->get($you->getNetwork());
if(!$you->isLoggedIn() && $net['config']['guests'] == 0){
	$msg = 'You need to log in to be able to view chat!';
	if(isset($_GET['noLoginErrors'])){
		$json->add('message',$msg);
	}else{
		$json->addError($msg);
	}
	echo $json->get();
	die();
}

if(isset($_GET['lineNum'])){
	$curline = (int)$_GET['lineNum'];
	if($curline < (int)file_get_contents($config['settings']['curidFilePath'])-200){
		$json->addWarning('lineNum too far in the past, giving only 200');
		$curline = (int)file_get_contents($config['settings']['curidFilePath'])-200;
	}
}else{
	$curline = (int)file_get_contents($config['settings']['curidFilePath']);
	$json->addWarning('lineNum not set, defaulting to newest one ('.$curline.')');
}
if($you->isBanned()){
	$json->add('banned',true);
	$json->addError('banned');
	echo $json->get();
	die();
}
if(isset($_GET['high'])){
	$numCharsHighlight = (int)$_GET['high'];
}else{
	$json->addWarning('Not set num chars to highlight, defaulting to 4');
	$numCharsHighlight = 4;
}
$channel = $you->chan;
$nick = $you->nick;

$countBeforeQuit = 0;
$you->update();

while(true){
	if($countBeforeQuit++ >= 50){//Timeout after 25 seconds.
		$you->update();
		echo $json->get();
		die();
	}
	if((int)file_get_contents($config['settings']['curidFilePath'])<=$curline){
		usleep(500000);
		continue;
	}
	$you->update();
	if($nick){
		$query = $sql->query_prepare("SELECT * FROM `{db_prefix}lines`
			WHERE
				`line_number` > ?
			AND
			(
				(
					`channel` = ?
					AND
					`type`!='server'
				)
				OR
				name2=?
		)",array($curline,$channel,$you->getPmHandler()));
	}else{
		$query = $sql->query_prepare("SELECT * FROM `{db_prefix}lines` WHERE `line_number` > ? AND `channel` = ? AND `type`!='server'",array($curline,$channel));
	}
	$result = $query[0];
	$userSql = $you->info();
	$ignorelist = '';
	if($userSql['name']!=NULL) {
		$ignorelist = $userSql['ignores'];
	}
	$lines = Array();
	if($nick===false){
		$lines[] = Array(
			'curLine' => 0,
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
	if($result['line_number'] === NULL){
		$temp = $sql->query_prepare("SELECT * FROM `{db_prefix}lines` WHERE `line_number` > ? AND locate(?,`message`) != 0 AND NOT (((`type` = 'pm' OR `type` = 'pmaction') AND `name1` <> ? AND `online` = ?) OR (`type` = 'server'))",array($curline,substr($nick,0,4),$nick,$you->getNetwork()));
		$result = $temp[0];
		if($result['line_number'] === NULL){
			$temp = $sql->query_prepare("SELECT MAX(line_number) AS max FROM `{db_prefix}lines`");
			$curline = (int)$temp[0]['max'];
			usleep(500000);
			continue;
		}
		if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
			$lines[] = Array(
				'curLine' => (int)$result['line_number'],
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
				'curLine' => (int)$result['line_number'],
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
		$json->add('lines',$lines);
		echo $json->get();
		exit;
	}
	foreach($query as $result){
		if(!isset($result['time'])){
			$result['time'] = time();
		}
		if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
			$lines[] = Array(
				'curLine' => (int)$result['line_number'],
				'type' => $result['type'],
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
				'curLine' => (int)$result['line_number'],
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
	$json->add('lines',$lines);
	echo $json->get();
	break;
}
