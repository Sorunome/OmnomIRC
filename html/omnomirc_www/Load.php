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
header('Content-Type:application/json');

$net = OIRC::$networks->get(OIRC::$you->getNetwork());
if(!OIRC::$you->isLoggedIn() && $net['config']['guests'] == 0){
	OIRC::$json->add('message','You need to log in to be able to view chat!');
	echo OIRC::$json->get();
	exit;
}

if(isset($_GET['userlist'])){
	$count = 0;
}elseif(isset($_GET['count'])){
	$count = (int)$_GET['count'];
	if($count > 200){
		$count = 200;
		OIRC::$json->addWarning('Exceeded max count (200)');
	}
}else{
	$count = 1;
	OIRC::$json->addWarning('Didn\'t set a count, defaulting to one.');
}
$channel = OIRC::$you->chan;
if(OIRC::$you->isBanned()){
	OIRC::$json->add('banned',true);
	OIRC::$json->add('admin',false);
	OIRC::$json->add('lines',array());
	OIRC::$json->add('users',array());
	echo OIRC::$json->get();
	die();
}
OIRC::$json->add('banned',false);
OIRC::$json->add('admin',OIRC::$you->isGlobalOp());

$lines = OIRC::loadChannel($count);
if(!isset($_GET['userlist'])){
	array_push($lines,array(
		'curline' => (int)file_get_contents(OIRC::$config['settings']['curidFilePath']),
		'type' => 'topic',
		'network' => 0,
		'time' => time(),
		'name' => '',
		'message' => OIRC::$channels->getTopic($channel),
		'name2' => '',
		'chan' => $channel,
		'uid' => -1
	));
}
OIRC::$json->add('lines',$lines);
$users = OIRC::$sql->query_prepare("SELECT `username` AS `nick`,`online` AS `network` FROM `{db_prefix}users` WHERE `channel`=? AND `isOnline`=1 AND `username` IS NOT NULL ORDER BY `username`",array((string)$channel)); // cast to string else (int)0 will be intepreted wrong by mysql
if($users[0]['nick'] === NULL){
	$users = array();
}

OIRC::$json->add('users',$users);
if(OIRC::$you->isLoggedIn()){
	$userSql = OIRC::$you->info();
	$ignorelist = '';
	if($userSql['name']!==NULL) {
		$i = explode("\n",$userSql['ignores']);
		array_pop($i); // last element is always garbage
		OIRC::$json->add('ignores',$i);
	}
}
echo OIRC::$json->get();
