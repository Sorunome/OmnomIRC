<?php
$encriptKeyToUse = 'key from Config.php (created while installation)';
$oircUrl = 'http://omnomirc.www.omnimaga.org';
$network = 1;

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
		header('Content-type: text/plain');
	}elseif(!isset($_GET['textmode'])){
		header('Content-type: text/javascript');
	}
	$ts = time();
	$key = htmlspecialchars(str_replace(";","%^%",$_GET['sid']));
	$keyParts = explode('|',$key);
	if(isset($keyParts[1]) && (int)$keyParts[1] < ($ts + 10) && (int)$keyParts[1] > ($ts - 10) && hash('sha512',$_SERVER['REMOTE_ADDR'].$encriptKeyToUse.$ts) == $keyParts[0]){
		if($user_info['name']=='' || $user_info['is_guest'] || is_not_banned()){
			$nick = '';
			$signature = '';
			$uid = 0;
		}else{
			$nick = $user_info['name'];
			$signature = hash('sha512',$network.$encriptKeyToUse.$nick);
			$uid = $context['user']['id'];
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
	loadMemberData($id, false, 'normal');
	loadMemberData($id);
	loadMemberContext($id);
	if(base64_decode(strtr($_GET['nick'],'-_,','+/='))==$memberContext[$id]['name']){
		$group = $memberContext[$id]['group'];
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
