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
$ssi_guest_access = true;
@require(dirname(__FILE__).'/SSI.php');


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
		if(isset($keyParts[1]) && (int)$keyParts[1] < ($ts + 60) && (int)$keyParts[1] > ($ts - 60) && hash('sha512',$_SERVER['REMOTE_ADDR'].$encriptKeyToUse.$keyParts[1]) == $keyParts[0]
					&& $user_info['name']!='' && !$user_info['is_guest'] && !is_not_banned()){
			$nick = $user_info['name'];
			$signature = hash('sha512',$network.$encriptKeyToUse.$nick);
			$uid = $context['user']['id'];
	}
}

ob_end_clean();
if(isset($_GET['op']) && !isset($_GET['time'])){
	header('Content-Type: text/json');
	$group = '';
	$id = $_GET['u'];
	loadMemberData($id, false, 'normal');
	loadMemberData($id);
	loadMemberContext($id);
	if(base64_decode(strtr($_GET['nick'],'-_,','+/='))==$memberContext[$id]['name']){
		$group = $memberContext[$id]['group'];
	}
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
	}elseif (isset($_GET['textmode'])){
		header('Location: '.$oircUrl.'/textmode.php?login&nick='.urlencode($nick).'&signature='.urlencode($signature).'&id='.$uid.(isset($_GET['network'])?'&network='.(int)$_GET['network']:''));
	}else{
		header('Content-Type: text/json');
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
