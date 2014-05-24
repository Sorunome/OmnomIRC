<?PHP
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
include_once(realpath(dirname(__FILE__)).'/omnomirc.php');
if(isset($_GET['userinfo'])){
	$json->clear();
	if(isset($_GET['name']) && isset($_GET['chan']) && isset($_GET['online'])){
		$temp = $sql->query("SELECT `lastMsg` FROM `irc_users` WHERE username='%s' AND channel='%s' AND online='%s'",base64_url_decode($_GET['name']),base64_url_decode($_GET['chan']),$_GET['online']);
		$json->add('last',(int)$temp[0]['lastMsg']);
		echo $json->get();
	}else{
		$json->addError('Bad parameters');
		echo $json->get();
	}
	die();
}
if(isset($_GET['count'])){
	$count = (int)$_GET['count'];
	if($count > 200){
		$count = 200;
		$json->addWarning('Exceeded max count (200)');
	}
}else{
	$count = 1;
	$json->addWarning('Didn\'t set a count, defaulting to one.');
}
$channel = $you->chan;
if($you->isBanned()){
	$json->add('banned',true);
	$json->add('admin',false);
	$json->add('lines',Array());
	$json->add('users',Array());
	echo $json->get();
	die();
}
$json->add('banned',false);
$json->add('admin',$you->isGlobalOp());



if($channel[0] == "*"){ // PM
	$sender = substr($channel,1);
	$channel = $you->nick;
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
											ORDER BY `line_number` ASC",$channel,$you->nick,$you->nick,$channel,$count + 0);
}
$userSql = $you->info();
if($userSql['name']!=NULL){
	$ignorelist = $userSql['ignores'];
}
$lines = Array();
foreach($res as $result){
	if(strpos($userSql['ignores'],strtolower($result['name1'])."\n")===false){
		$lines[] = Array(
			'curLine' => (int)$result['line_number'],
			'type' => $result['type'],
			'network' => (int)$result['Online'],
			'time' => (int)$result['time'],
			'name' => $result['name1'],
			'message' => $result['message'],
			'name2' => $result['name2'],
			'chan' => $result['channel']
		);
	}
}
$temp = $sql->query("SELECT MAX(line_number) AS max FROM `irc_lines`");
$curMax = $temp[0]['max'];
$curtopic = $sql->query("SELECT topic FROM `irc_topics` WHERE `chan`='%s'",strtolower($channel));
if($curtopic[0]['topic']!==NULL){
	$curtopic = $curtopic[0]['topic'];
}else{
	$curtopic = '';
}
$lines[] = Array(
	'curLine' => (int)$curMax,
	'type' => 'topic',
	'network' => -1,
	'time' => time(),
	'name' => '',
	'message' => $curtopic,
	'name2' => '',
	'chan' => $result['channel']
);
$json->add('lines',$lines);
$users = Array();
$result = $sql->query("SELECT username,online,channel FROM `irc_users` WHERE `channel`='%s' AND `isOnline`='1'",$channel);
foreach($result as $user){
	if($user['username']!=NULL){
		$users[count($users)][0] = strtolower($user['username']);
		$users[count($users) - 1][1] = $user['username'];
		$users[count($users) - 1][2] = $user['online'];
		$users[count($users) - 1][3] = $user['channel'];
	}
}
asort($users);
$realUsers = Array();
foreach($users as $user){
	$realUsers[] = Array(
		'nick' => $user[1],
		'network' => (int)$user[2]
	);
}
$json->add('users',$realUsers);
echo $json->get();
?>
