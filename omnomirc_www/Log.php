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
	$t_low = (int)DateTime::createFromFormat('j-n-Y H:i:s',base64_url_decode($_GET['day']).' 00:00:00')->getTimestamp();
}else{
	$t_low = (int)time();
	$json->addWarning('No day set, defaulting to today');
}
$t_high = $t_low + (3600 * 24);

if($channel[0] == "*"){ // PM
	$sender = substr($channel,1);
	$channel = $you->nick;
	$res = $sql->query("SELECT * FROM `irc_lines` 
							WHERE (
								((`channel` = '%s'
								AND `name1` = '%s')
								OR
								(`channel` = '%s'
								AND `name1` = '%s'))
									AND NOT ((`type` = 'join' OR `type` = 'part') AND `Online` = 1)
							) AND
								`time` >= %d
									AND
								`time` <= %d
							ORDER BY `line_number` ASC 
							LIMIT %d,300
						",$channel,$sender,$sender,$channel,$t_low,$t_high,$offset);
}else{
	$res = $sql->query("SELECT * FROM `irc_lines` 
								WHERE (
										(`type` != 'server' AND ((`channel` = '%s' OR `channel` = '%s')
										AND NOT ((`type` = 'join' OR `type` = 'part') AND `Online` = 1)))
										OR (`type` = 'server' AND channel='%s' AND name2='%s')
									) AND
										`time` >= %d
									AND
										`time` <= %d
								ORDER BY `line_number` ASC 
								LIMIT %d,300
									",$channel,$you->nick,$you->nick,$channel,$t_low,$t_high,$offset);
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

$json->add('lines',$lines);


echo $json->get();
?>
