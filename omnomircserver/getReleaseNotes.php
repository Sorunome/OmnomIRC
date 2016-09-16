<?php
include_once(realpath(dirname(__FILE__)).'/vars.php');
include_once(realpath(dirname(__FILE__)).'/sql.php');
header('Content-Type: application/json');


$resp = '<b>ERROR</b>: no release notes for this version';
if(isset($_GET['version'])){
	if(($res = $sql->query("SELECT notes FROM releasenotes WHERE version=?",[$_GET['version']],0)['notes'])!==NULL){
		$resp = $res;
	}
}
echoJson(array('notes' => $resp));
