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

if(isset($_GET['offset'])){
	$offset = (int)$_GET['offset'];
}else{
	$offset = 0;
	Json::addWarning('Didn\'t set an offset, defaulting to zero.');
}


if(OIRC::$you->isBanned()){
	Json::add('banned',true);
	Json::add('admin',false);
	Json::add('lines',array());
	Json::add('users',array());
	echo Json::get();
	die();
}
Json::add('banned',false);
Json::add('admin',OIRC::$you->isGlobalOp());

if(isset($_GET['day'])){
	$t_low = (int)\DateTime::createFromFormat('Y-n-j H:i:s',base64_url_decode($_GET['day']).' 00:00:00')->getTimestamp();
}else{
	$t_low = (int)time();
	Json::addWarning('No day set, defaulting to today');
}
$t_high = $t_low + (3600 * 24);
$lines = array();
$table = '{db_prefix}lines_old';

$channel = OIRC::$sql_query_prepare("SELECT {db_prefix}getchanid(?) AS chan",array(OIRC::$you->chan));
$channel = $channel[0]['chan'];
// $table is NEVER user-defined, it is only {db_prefix}lines_old or {db_prefix}lines!!
while(true){
	$res = Sql::query("SELECT * FROM `$table`
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
	
	$lines = array_merge($lines,self::getLines($res,$table,true));
	
	if(count($lines)<1000 && $table == '{db_prefix}lines_old'){
		$table = '{db_prefix}lines';
		continue;
	}
	break;
}

Json::add('lines',$lines);


echo Json::get();
