<?PHP
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2015 Sorunome

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
		$temp = $sql->query_prepare("SELECT `lastMsg` FROM `irc_users` WHERE username=? AND channel=? AND online=?",array(base64_url_decode($_GET['name']),(preg_match('/^[0-9]+$/',$_GET['chan'])?$_GET['chan']:base64_url_decode($_GET['chan'])),(int)$_GET['online']));
		$json->add('last',(int)$temp[0]['lastMsg']);
		echo $json->get();
	}else{
		$json->addError('Bad parameters');
		echo $json->get();
	}
	exit;
}

$net = $networks->get($you->getNetwork());
if(!$you->isLoggedIn() && $net['config']['guests'] == 0){
	$json->add('message','You need to log in to be able to view chat!');
	echo $json->get();
	exit;
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
	$json->add('lines',array());
	$json->add('users',array());
	echo $json->get();
	die();
}
$json->add('banned',false);
$json->add('admin',$you->isGlobalOp());

$lines = $omnomirc->loadChannel($count);

array_push($lines,array(
	'curLine' => (int)$sql->query_prepare("SELECT MAX(`line_number`) AS `max` FROM `irc_lines`")[0]['max'],
	'type' => 'topic',
	'network' => -1,
	'time' => time(),
	'name' => '',
	'message' => $channels->getTopic($channel),
	'name2' => '',
	'chan' => $channel,
	'uid' => -1
));
$json->add('lines',$lines);
$users = $sql->query_prepare("SELECT `username` AS `nick`,`online` AS `network` FROM `irc_users` WHERE `channel`=? AND `isOnline`=1 AND `username` IS NOT NULL ORDER BY `username`",array($channel));
if($users[0]['nick'] == NULL){
	$users = Array();
}
$json->add('users',$users);
if($you->isLoggedIn()){
	$userSql = $you->info();
	$ignorelist = '';
	if($userSql['name']!=NULL) {
		$i = explode("\n",$userSql['ignores']);
		array_pop($i); // last element is always garbage
		$json->add('ignores',$i);
	}
}
echo $json->get();
?>
