<?php
$encriptKeyToUse = 'key from Config.php (created while installation)';
$checkCookie = '__cfduid';
$oircUrl = 'http://omnomirc.www.omnimaga.org';

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
	if($user_info['name']=='' || $user_info['is_guest'] || is_not_banned() || (isset($_GET['sid']) && htmlspecialchars(str_replace(";","%^%",$_COOKIE[$checkCookie]))!=$_GET['sid']) || !isset($_GET['sid'])){
		$nick = 'Guest';
		$signature = '';
		$uid = 0;
	}else{
		$nick = $user_info['name'];
		$signature = base64_url_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256,$encriptKeyToUse,$nick,MCRYPT_MODE_ECB));
		$uid = $context['user']['id'];
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
