<?php
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
function adminWriteConfig($output=true){
	global $config;
	$configFile = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
include_once(realpath(dirname(__FILE__)).\'/Source/sql.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/sign.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/userlist.php\');
include_once(realpath(dirname(__FILE__)).\'/Source/cachefix.php\');

function getConfig(){
	$cfg = explode("\n",file_get_contents(realpath(dirname(__FILE__)).\'/config.php\'));
	$searchingJson = true;
	$json = \'\';
	foreach($cfg as $line){
		if($searchingJson){
			if(trim($line)==\'JSONSTART\'){
				$searchingJson = false;
			}
		}else{
			if(trim($line)==\'JSONEND\'){
				break;
			}
			$json .= $line;
		}
	}
	return json_decode($json,true);
}
$config = getConfig();
/*
JSONSTART
'.json_encode($config).'
JSONEND
*/
$defaultChan = $config[\'channels\'][0][\'chan\'];
if(isset($_GET[\'js\'])){
	header(\'Content-type: text/json\');
	$channels = Array();
	foreach($config["channels"] as $chan){
		$channels[] = Array(
			"chan" => $chan["chan"],
			"high" => false,
			"ex" => !$chan["visible"]
		);
	}
	echo json_encode(Array(
		"hostname" => $config[\'settings\'][\'hostname\'],
		"channels" => $channels,
		"smileys" => $config["smileys"],
		"networks" => $config["networks"],
		"checkLoginUrl" => (isset($_COOKIE[$config["security"]["cookie"]])?$config["settings"]["checkLoginUrl"]."?sid=".urlencode(htmlspecialchars(str_replace(";","%^%",$_COOKIE[$config["security"]["cookie"]]))):$config["settings"]["checkLoginUrl"]."?sid=THEGAME")
	));
}
?>';
	if(file_put_contents(realpath(dirname(__FILE__)).'/../config.php',$configFile)){
		if($output)
			echo 'Config written';
		return true;
	}
	if($output)
		echo 'Couldn\'t write config';
	return false;
}
function getRandKey(){
	$randKey = rand(100,9999).'-'.Rand(10000,999999);
	$randKey = md5($randKey);
	$randKey = base64_encode($randKey);
	return md5($randKey);
}
?>