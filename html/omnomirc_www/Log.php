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
	$json->add('lines',array());
	$json->add('users',array());
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
$lines = array();
$table = '{db_prefix}lines_old';
// $table is NEVER user-defined, it is only {db_prefix}lines_old or {db_prefix}lines!!
while(true){
	$res = $sql->query_prepare("SELECT * FROM `$table`
		WHERE
				`channel` = ?
			AND
				`type` != 'server'
			AND
				`time` >= ?
			AND
				`time` <= ?
			ORDER BY `line_number` ASC
			LIMIT ?,1000",array($channel,$t_low,$t_high,(int)$offset));
	
	foreach($res as $result){
		$lines[] = array(
			'curLine' => ($table=='{db_prefix}lines'?(int)$result['line_number']:0),
			'type' => $result['type'],
			'network' => (int)$result['Online'],
			'time' => (int)$result['time'],
			'name' => $result['name1'],
			'message' => $result['message'],
			'name2' => $result['name2'],
			'chan' => $result['channel']
		);
	}
	if(count($lines)<1000 && $table == '{db_prefix}lines_old'){
		$table = '{db_prefix}lines';
		continue;
	}
	break;
}

$json->add('lines',$lines);


echo $json->get();
?>
