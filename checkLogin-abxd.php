<?php
$encryptKeyToUse = 'key from Config.php (created while installation)';
$oircUrl = 'http://omnomirc.www.omnimaga.org';
$network = 1;

date_default_timezone_set('UTC');
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
		header('Content-Type: text/plain');
	}elseif(!isset($_GET['textmode'])){
		header('Content-Type: text/javascript');
	}
	$nick = '';
	$signature = '';
	$uid = 0;
	if(isset($_GET['sid']) && isset($_GET['network']) && $_GET['network'] == $network){
		$ts = time();
		$key = htmlspecialchars(str_replace(';','%^%',$_GET['sid']));
		$keyParts = explode('|',$key);
		if(isset($keyParts[1]) && (int)$keyParts[1] < ($ts + 60) && (int)$keyParts[1] > ($ts - 60) && hash_hmac('sha512',(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'THE GAME'),$encryptKeyToUse.$keyParts[1].$network) == $keyParts[0]
					&& $loguser['name']!='' && $loguser['powerlevel']>=0 && !isIPBanned($_SERVER['REMOTE_ADDR'])){
			
			$nick = ($loguser['displayname']==''?$loguser['name']:$loguser['displayname']);
			$time = (string)time();
			$uid = $loguser['id'];
			$signature = $time.'|'.hash_hmac('sha512',$nick.$uid,$network.$encryptKeyToUse.$time);
		}
	}
}

ob_end_clean();
if(isset($_GET['op']) && !isset($_GET['time'])){
	header('Content-Type: text/json');
	$id = $_GET['u'];
	$user = Fetch(Query("select * from {users} where id={0}", $id));
	$group = 'false';
	$n = ($user['displayname']==''?$user['name']:$user['displayname']);
	if(base64_url_decode($_GET['nick'])==$n && $user['powerlevel']>=1){
		$group = 'true';
	}
	header('Content-Type: text/json');
	echo json_encode(Array(
		'group' => $group
	));
}elseif(isset($_GET['time'])){
	header('Content-Type: text/json');
	echo json_encode(Array(
		'time' => time()
	));
}else{
	if(isset($_GET['txt'])){
		echo $signature."\n".$nick."\n".$uid;
	}elseif(isset($_GET['textmode'])){
		header('Location: '.$oircUrl.'/textmode.php?login&nick='.base64_url_encode($nick).'&signature='.base64_url_encode($signature).'&id='.$uid.(isset($_GET['network'])?'&network='.(int)$_GET['network']:''));
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
