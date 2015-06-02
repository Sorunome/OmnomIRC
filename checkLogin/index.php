<?php
function getConfig(){
	$cfg = explode("\n",file_get_contents(realpath(dirname(__FILE__)).'/config.json.php'));
	$searchingJson = true;
	$json = "";
	foreach($cfg as $line){
		if($searchingJson){
			if(trim($line)=='//JSONSTART'){
				$searchingJson = false;
			}
		}else{
			if(trim($line)=='//JSONEND'){
				break;
			}
			$json .= "\n".$line;
		}
	}
	$json = implode("\n",explode("\n//",$json));
	return json_decode($json,true);
}
function writeConfig(){
	global $config;
	$file = '<?php
/* This is a automatically generated config-file by OmnomIRC, please use the admin pannel to edit it! */
header("Location:index.php");
//JSONSTART
//'.json_encode($config).'
//JSONEND
?>';
	if(file_put_contents(realpath(dirname(__FILE__)).'/config.json.php',$file)){
		return true;
	}else{
		return false;
	}
}
$config = getConfig();
date_default_timezone_set('UTC');
function base64_url_encode($input) {
	return strtr(base64_encode($input),'+/=','-_,');
}

function base64_url_decode($input){
	return base64_decode(strtr($input,'-_,','+/=')); 
}
if(!isset($_GET['server'])){
	(@include_once(realpath(dirname(__FILE__)).'/hook-'.$config['hook'].'.php')) or die('Failed to include hook file!');
}

if(isset($_GET['op'])){
	header('Content-Type: application/json');
	$group = '';
	$id = $_GET['u'];
	if($id == (int)$id){
		$group = hook_get_group((int)$id);
	}
	echo json_encode(array(
		'group' => $group
	));
}elseif(isset($_GET['c'])){
	header('Content-Type: application/json');
	echo json_encode(array(
		'nick' => hook_get_color_nick($_GET['n'],(int)$_GET['c'])
	));
}elseif(isset($_GET['time'])){
	header('Content-Type: text/json');
	echo json_encode(Array(
		'time' => time()
	));
}elseif(isset($_GET['server'])){
	header('Content-Type: application/json');
	$key = htmlspecialchars(str_replace(';','%^%',$_GET['server']));
	$keyParts = explode('|',$key);
	$ts = time();
	if(sizeof($keyParts) >= 3 && (int)$keyParts[1] < ($ts + 60) && (int)$keyParts[1] > ($ts - 60)
				&& hash_hmac('sha512',$keyParts[2],$config['sigKey'].$keyParts[1].$config['network']) == $keyParts[0]){
		// we are now a verified server
		switch($_GET['action']){
			case 'getHooks':
				$a = array();
				foreach(scandir('.') as $f){
					if(preg_match('/^hook-([a-zA-Z0-9-\.,+_]+)\.php$/',$f,$match)){
						$a[] = $match[1];
					}
				}
				echo json_encode($a);
				break;
			case 'set':
				$val = $_GET['val'];
				switch($_GET['var']){
					case 'installed':
						$config['installed'] = ($val=='true');
						break;
					case 'sigKey':
						$config['sigKey'] = base64_url_decode($val);
						break;
					case 'hook':
						$config['hook'] = base64_url_decode($val);
						break;
					case 'network':
						$config['network'] = (int)$val;
				}
				echo json_encode(array(
					'success' => writeConfig()
				));
				break;
		}
	}else{
		echo json_encode(array(
			'auth' => false
		));
	}
}else{
	$nick = '';
	$signature = '';
	$uid = -1;
	if(isset($_GET['sid']) && isset($_GET['network']) && $_GET['network'] == $config['network']){
		$ts = time();
		$key = htmlspecialchars(str_replace(';','%^%',$_GET['sid']));
		$keyParts = explode('|',$key);
		if(isset($keyParts[1]) && (int)$keyParts[1] < ($ts + 60) && (int)$keyParts[1] > ($ts - 60)
				&& hash_hmac('sha512',(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'THE GAME'),$config['sigKey'].$keyParts[1].$config['network']) == $keyParts[0]
				&& hook_may_chat()){
			$a = hook_get_login();
			$nick = $a['nick'];
			$time = (string)time();
			$uid = $a['uid'];
			$signature = $time.'|'.hash_hmac('sha512',$nick.$uid,$config['network'].$config['sigKey'].$time);
		}
	}
	if(isset($_GET['txt'])){
		header('Content-Type: text/plain');
		echo $signature."\n".$nick."\n".$uid;
	}elseif (isset($_GET['textmode'])){
		header('Location: '.$oircUrl.'/textmode.php?login&nick='.base64_url_encode($nick).'&signature='.base64_url_encode($signature).'&id='.$uid.(isset($_GET['network'])?'&network='.(int)$_GET['network']:''));
	}else{
		header('Content-Type: application/json');
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
