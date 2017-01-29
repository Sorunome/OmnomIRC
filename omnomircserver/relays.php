<?php
include_once(realpath(dirname(__FILE__)).'/vars.php');
include_once(realpath(dirname(__FILE__)).'/sql.php');
header('Content-Type: application/json');

if(isset($_GET['getall'])){
	$res = $sql->query("SELECT `name`,`disp_name`,`info`,`version` FROM `relays` WHERE 1");
	echo json_encode($res);
	exit;
}elseif(isset($_GET['get'])){
	$res = $sql->query("SELECT `name`,`disp_name`,`info`,`version` FROM `relays` WHERE `name`=?",[$_GET['get']],0);
	echo json_encode($res);
	exit;
}
