<?php
$encriptKeyToUse = 'key from Config.php (created while installation)';
$oircUrl = 'http://omnomirc.www.omnimaga.org';
$network = 1;

date_default_timezone_set('UTC');
function base64_url_encode($input) {
	return strtr(base64_encode($input),'+/=','-_,');
}

function base64_url_decode($input){
	return base64_decode(strtr($input,'-_,','+/=')); 
}

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);

ob_start();
if(!isset($_GET['op'])){
	if(isset($_GET['txt'])){
		header('Content-type: text/plain');
	}elseif(!isset($_GET['textmode'])){
		header('Content-type: text/javascript');
	}
	$ts = time();
	$key = htmlspecialchars(str_replace(';','%^%',$_GET['sid']));
	$keyParts = explode('|',$key);
	if(isset($keyParts[1]) && (int)$keyParts[1] < ($ts + 10) && (int)$keyParts[1] > ($ts - 10) && hash('sha512',$_SERVER['REMOTE_ADDR'].$encriptKeyToUse.$keyParts[1]) == $keyParts[0]){
		if($user->data['is_registered'] != 1){
			$nick = '';
			$signature = '';
			$uid = 0;
		}else{
			$nick = $user->data['username'];
			$signature = hash('sha512',$network.$encriptKeyToUse.$nick);
			$uid = (int)$user->data['user_id'];
		}
	}else{
		$nick = '';
		$signature = '';
		$uid = 0;
	}
}

ob_end_clean();
if(isset($_GET['op'])) {
	header('Content-type: text/json');
	$group = '';
	$id = $_GET['u'];
	if(base64_decode(strtr($_GET['nick'],'-_,','+/='))==$row['username']){
		//$group = $row['group_id']==5;
		$group = 'false'; // lol
	}
	echo json_encode(Array(
		'group' => $group
	));
}else{
	if(isset($_GET['txt'])){
		echo $signature."\n".$nick."\n".$uid;
	}elseif (isset($_GET['textmode'])){
		header('Location: '.$oircUrl.'/textmode.php?login&nick='.urlencode($nick).'&signature='.urlencode($signature).'&id='.$uid.(isset($_GET['network'])?'&network='.(int)$_GET['network']:''));
	}else{
		header('Content-type: text/json');
		$json = json_encode(Array(
			'nick' => $nick,
			'signature' => $signature,
			'uid' => $uid
		));
		if(isset($_GET['jsoncallback'])){
			echo $_GET['jsoncallback'].'('.$json.')';
		}else{
			echo $json;
		}
	}
}
?>
