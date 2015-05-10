<?php
$encryptKeyToUse = 'key from Config.php (created while installation)';
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
		if(isset($keyParts[1]) && (int)$keyParts[1] < ($ts + 60) && (int)$keyParts[1] > ($ts - 60) && hash_hmac('sha512',(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'THE GAME'),$encryptKeyToUse.$keyParts[1].$network) == $keyParts[0]
					&& $user_info['name']!='' && !$user_info['is_guest'] && !is_not_banned()){
			$nick = html_entity_decode($user_info['name']);
			$time = (string)time();
			$uid = $context['user']['id'];
			$signature = $time.'|'.hash_hmac('sha512',$nick.$uid,$network.$encryptKeyToUse.$time);
		}
	}
}

ob_end_clean();
if(isset($_GET['op']) && !isset($_GET['time'])){
	header('Content-Type: text/json');
	$group = '';
	$id = $_GET['u'];
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
}elseif(isset($_GET['c'])){
	header('Content-Type: text/json');
	
	$request = $smcFunc['db_query']('',"SELECT id_member FROM {db_prefix}members WHERE id_member = {int:id_member} LIMIT 1",array('id_member' => $_GET['c']) );
	$res = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	
	$cnick = $_GET['n'];
	if($res){
		$id = (int)$res['id_member'];
		loadMemberData($id);
		loadMemberContext($id);
		$cnick = '<span style="color:'.$memberContext[$id]['group_color'].';">'.$memberContext[$id]['link'].'</span>';
	}
	echo json_encode(array(
		'nick' => $cnick
	));
}elseif(isset($_GET['ul'])){
	$request = $smcFunc['db_query']('',"SELECT id_member FROM {db_prefix}members WHERE real_name = {string:real_name} LIMIT 1",array('real_name' => $_GET['ul']) );
	$res = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	
	if(!$res){
		header('Location: /index.php?action=profile;u=-1');
	}else{
		header('Location: /index.php?action=profile;u='.$res['id_member']);
	}
}else{
	if(isset($_GET['txt'])){
		echo $signature."\n".$nick."\n".$uid;
	}elseif (isset($_GET['textmode'])){
		header('Location: '.$oircUrl.'/textmode.php?login&nick='.base64_url_encode($nick).'&signature='.base64_url_encode($signature).'&id='.$uid.(isset($_GET['network'])?'&network='.(int)$_GET['network']:''));
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
