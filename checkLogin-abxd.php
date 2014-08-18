<?php
$encriptKeyToUse = 'key from Config.php (created while installation)';
$checkCookie = '__cfduid';
$oircUrl = 'http://omnomirc.www.omnimaga.org';

function base64_url_encode($input){
	return strtr(base64_encode($input),'+/=','-_,');
}

function base64_url_decode($input){
	return base64_decode(strtr($input,'-_,','+/=')); 
}
include('lib/common.php');

ob_start();
if(!isset($_GET['op'])){
	if(isset($_GET['txt'])){
		header('Content-type: text/plain');
	}elseif(!isset($_GET['textmode'])){
		header('Content-type: text/javascript');
	}
	if($loguser['name']=='' || $loguser['powerlevel']<0 || isIPBanned($_SERVER['REMOTE_ADDR'])){
		$nick = 'Guest';
		$signature = '';
		$uid = 0;
	}else{
		$nick = ($loguser['displayname']==''?$loguser['name']:$loguser['displayname']);
		$signature = base64_url_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$encriptKeyToUse,$nick,MCRYPT_MODE_ECB));
		$uid = $loguser['id'];
	}
}

ob_end_clean();
if(isset($_GET['op'])){
	header('Content-type: text/json');
	$id = $_GET['u'];
	$user = Fetch(Query("select * from {users} where id={0}", $id));
	$group = 'false';
	$n = ($user['displayname']==''?$user['name']:$user['displayname']);
	if(base64_url_decode($_GET['nick'])==$n && $user['powerlevel']>=1){
		$group = 'true';
	}
	header('Content-type: text/json');
	echo json_encode(Array(
		'group' => $group
	));
}else{
	if(isset($_GET['txt'])){
		echo $signature."\n".$nick."\n".$uid;
	}elseif(isset($_GET['textmode'])){
		header('Location: '.$oircUrl.'/textmode.php?login&nick='.urlencode($nick).'&signature='.urlencode($signature).'&id='.$uid);
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
