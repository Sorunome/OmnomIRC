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

$net = OIRC::$networks->get(OIRC::$you->getNetwork());
if(!OIRC::$you->isLoggedIn() && $net['config']['guests'] == 0){
	$msg = 'You need to log in to be able to view chat!';
	if(isset($_GET['noLoginErrors'])){
		OIRC::$json->add('message',$msg);
	}else{
		OIRC::$json->addError($msg);
	}
	echo OIRC::$json->get();
	die();
}

if(isset($_GET['offset'])){
	$offset = (int)$_GET['offset'];
}else{
	$offset = 0;
	OIRC::$json->addWarning('Didn\'t set an offset, defaulting to zero.');
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

if(isset($_GET['day'])){
	$t_low = (int)DateTime::createFromFormat('Y-n-j H:i:s',base64_url_decode($_GET['day']).' 00:00:00')->getTimestamp();
}else{
	$t_low = (int)time();
	OIRC::$json->addWarning('No day set, defaulting to today');
}
$t_high = $t_low + (3600 * 24);
$lines = array();
$table = '{db_prefix}lines_old';
// $table is NEVER user-defined, it is only {db_prefix}lines_old or {db_prefix}lines!!
while(true){
	$res = OIRC::$sql->query_prepare("SELECT * FROM `$table`
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
			'curline' => ($table=='{db_prefix}lines'?(int)$result['line_number']:0),
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

OIRC::$json->add('lines',$lines);


echo OIRC::$json->get();
