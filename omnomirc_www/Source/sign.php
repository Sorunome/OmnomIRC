<?PHP

	include_once("config.php");
	
	function sign($name)
	{
		global $signature_key;
		return mcrypt_encrypt ( MCRYPT_RIJNDAEL_256 , $signature_key , $name , MCRYPT_MODE_ECB); //Okay, okay, this isn't a signature, it's a cypher. 
	}																				   //It still gives the same result every time, and I can use it with a key.
	
	function checkSignature($name,$signature,$deBase64=false)
	{
		if ($deBase64)
		{
			$name = base64_url_decode($name);
			$signature = base64_url_decode($signature);
		}
		return $signature == base64_url_encode(sign($name));
	}
	
	function base64_url_encode($input)
	{
		return strtr(base64_encode($input), '+/=', '-_,');
	}

	function base64_url_decode($input)
	{
		return base64_decode(strtr($input, '-_,', '+/=')); 
	}
	
?>
