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

if(isset($_GET['offset'])){
	$offset = (int)$_GET['offset'];
}else{
	$offset = 0;
	$json->addWarning('Didn\'t set an offset, defaulting to zero.');
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

if(isset($_GET['day'])){
	$t_low = (int)DateTime::createFromFormat('Y-n-j H:i:s',base64_url_decode($_GET['day']).' 00:00:00')->getTimestamp();
}else{
	$t_low = (int)time();
	$json->addWarning('No day set, defaulting to today');
}
$t_high = $t_low + (3600 * 24);
$lines = Array();
$table = 'irc_lines_old';
// $table is NEVER user-defined, it is only irc_lines_old or irc_lines!!
while(true){
	if($channel[0] == "*"){ // PM
		$res = $sql->query_prepare("SELECT * FROM `$table` 
								WHERE (
									((`channel` = ?
									AND `name1` = ?)
									OR
									(`channel` = ?
									AND `name1` = ?))
								) AND
									`time` >= ?
										AND
									`time` <= ?
										AND
									`online` = ?
								ORDER BY `line_number` ASC 
								LIMIT ?,1000
							",array(substr($channel,1),$you->nick,$you->nick,substr($channel,1),$t_low,$t_high,$you->getNetwork(),(int)$offset));
	}else{
		$res = $sql->query_prepare("SELECT * FROM `$table` 
									WHERE (
											(`type` != 'server' AND ((`channel` = ? OR `channel` = ?)
											)
											)
											OR (`type` = 'server' AND channel=? AND name2=?)
										) AND
											`time` >= ?
										AND
											`time` <= ?
									ORDER BY `line_number` ASC 
									LIMIT ?,1000
										",array($channel,$you->nick,$you->nick,$channel,$t_low,$t_high,(int)$offset));
	}
	
	foreach($res as $result){
		$lines[] = Array(
			'curLine' => ($table=='irc_lines'?(int)$result['line_number']:0),
			'type' => $result['type'],
			'network' => (int)$result['Online'],
			'time' => (int)$result['time'],
			'name' => $result['name1'],
			'message' => $result['message'],
			'name2' => $result['name2'],
			'chan' => $result['channel']
		);
	}
	if(count($lines)<1000 && $table == 'irc_lines_old'){
		$table = 'irc_lines';
		continue;
	}
	break;
}

$json->add('lines',$lines);


echo $json->get();
?>
