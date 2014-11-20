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

$net = $networks->get($you->getNetwork());
if(!$you->isLoggedIn() && $net['config']['guests'] == 0){
	$msg = 'You need to log in to be able to view chat!';
	if(isset($_GET['noLoginErrors'])){
		$json->add('message',$msg);
	}else{
		$json->addError($msg);
	}
	echo $json->get();
	exit;
}

if(isset($_GET['userinfo'])){
	$json->clear();
	if(isset($_GET['name']) && isset($_GET['chan']) && isset($_GET['online'])){
		$temp = $sql->query("SELECT `lastMsg` FROM `irc_users` WHERE username='%s' AND channel='%s' AND online=%d",base64_url_decode($_GET['name']),(preg_match('/^[0-9]+$/',$_GET['chan'])?$_GET['chan']:base64_url_decode($_GET['chan'])),(int)$_GET['online']);
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

$lines = $omnomirc->loadChannel($count);


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
	'chan' => $you->chan
);
$json->add('lines',$lines);
$users = Array();
$result = $sql->query("SELECT username,online,channel FROM `irc_users` WHERE `channel`='%s' AND `isOnline`=1",$channel);
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
