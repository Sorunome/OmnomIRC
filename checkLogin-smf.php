<?PHP
$encriptKeyToUse = "key from Config.php (created while installation)";
function base64_url_encode($input) {
	return strtr(base64_encode($input), '+/=', '-_,');
}

function base64_url_decode($input) {
	return base64_decode(strtr($input, '-_,', '+/=')); 
}
$ssi_guest_access = true;
@require(dirname(__FILE__) . '/SSI.php');


ob_start();
if(!isset($_GET['op'])) {
	if(isset($_GET['txt']))
		header('Content-type: text/plain');
	elseif (!isset($_GET['textmode']))
		header('Content-type: text/javascript');
	if ($user_info['is_guest'] || is_not_banned()) {
		$nick = "Guest";
		$signature = "";
	} else {
		$nick = $user_info['name'];
		$signature = base64_url_encode(mcrypt_encrypt ( MCRYPT_RIJNDAEL_256 , $encriptKeyToUse , $nick , MCRYPT_MODE_ECB));
	}
}

ob_end_clean();
if(isset($_GET['op'])) {
	header('Content-type: text/plain');
	$id = $_GET['u'];
	loadMemberData($id, false, 'normal');
	loadMemberData($id);
	loadMemberContext($id);
	if (base64_decode(strtr($_GET['nick'], '-_,', '+/='))==$memberContext[$id]['name'])
		echo $memberContext[$id]['group'];
} else {
	if(isset($_GET['txt']))
		echo $signature."\n".$nick."\n".$context['user']['id'];
	elseif (isset($_GET['textmode']))
		header('Location: http://omnomirc.www.omnimaga.org/textmode.php?login&nick='.urlencode($nick).'&sig='.urlencode($signature).'&id='.$context['user']['id']);
	else
		echo "signCallback('$signature','$nick','".$context['user']['id']."');";
}
?>
