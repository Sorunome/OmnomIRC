<?php
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
	$encriptKeyToUse = "key from Config.php (created while installation)";
	define("IN_MYBB", 1);
	define("NO_ONLINE", 1);
	require_once "./global.php";
	ob_start();
	if(!isset($_GET['op'])){
		if(isset($_GET['txt'])){
			header('Content-type: text/plain');
		}elseif(!isset($_GET['textmode'])){
			header('Content-type: text/javascript');
		}
		if($mybb->user['username']=="" || $mybb->user['isbannedgroup']){
			$nick = "Guest";
			$signature = "";
		}else{
			$nick = $mybb->user['username'];
			$signature = strtr(base64_encode((mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$encriptKeyToUse,$nick,MCRYPT_MODE_ECB))),'+/=','-_,');
		}
	}
	ob_end_clean();
	if(isset($_GET['op'])){
		header('Content-type: text/plain');
		$id = $_GET['u'];
		$user = get_user((int) $id);
		if (base64_decode(strtr(($_GET['nick'],'-_,','+/='))==$user['username']){
			echo $user['usergroup'];
		}
	}else{
		if(isset($_GET['txt'])){
			echo $signature."\n".$nick."\n".$mybb->user['uid'];
		}elseif(isset($_GET['textmode'])){
			header('Location: http://chat.forum.acr.victorz.ca/textmode.php?login&nick='.urlencode($nick).'&sig='.urlencode($signature).'&id='.$mybb->user['uid']);
		}else{
			echo "signCallback('$signature','$nick','".$mybb->user['uid']."');";
		}
	}
?>
