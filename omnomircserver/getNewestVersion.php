<?php
include_once(realpath(dirname(__FILE__)).'/vars.php');
include_once(realpath(dirname(__FILE__)).'/sql.php');
header('Content-Type: application/json');

function echoJson($data){
	if(isset($_GET['jsoncallback'])){
		echo $_GET['jsoncallback'].'('.json_encode($data).')';
	}else{
		echo json_encode($data);
	}
}

$resp = array(
	'version' => 0,
	'latest' => true
);
if(isset($_GET['version'])){
	$res = $sql->query("SELECT to_version FROM versionupgrade WHERE from_version=?".(!isset($_GET['experimental'])?" AND `release`=1":""),[$_GET['version']],0);
	if($res['to_version']!==NULL){
		$resp['version'] = $res['to_version'];
		$resp['latest'] = false;
	}
}
if($resp['version'] == 0){
	$resp['version'] = $sql->query("SELECT to_version FROM versionupgrade ORDER BY time DESC",[],0)['to_version'];
	$resp['latest'] = true;
}
if(!$resp['latest']){
	$resp['updater'] = '/'.$resp['version'].'/updater.php.s';
}

echoJson($resp);
?>