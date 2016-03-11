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
$news = $sql->query("SELECT UNIX_TIMESTAMP(time) as time,message FROM news ORDER BY time DESC");
echoJson(array('news' => $news));
?>