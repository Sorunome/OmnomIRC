<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2014 Sorunome

    This file is part of OmnomIRC.

    OmnomIRC is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OmnomIRC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
*/
	function sign($name){
		global $config;
		return mcrypt_encrypt ( MCRYPT_RIJNDAEL_256 , $config['security']['sigKey'] , $name , MCRYPT_MODE_ECB); //Okay, okay, this isn't a signature, it's a cypher. 
	}																				   //It still gives the same result every time, and I can use it with a key.
	
	function checkSignature($name,$signature,$deBase64=false){
		if ($deBase64){
			$name = base64_url_decode($name);
			$signature = base64_url_decode($signature);
		}
		return $signature == base64_url_encode(sign($name));
	}
	
	function base64_url_encode($input){
		return strtr(base64_encode($input),'+/=','-_,');
	}

	function base64_url_decode($input){
		return base64_decode(strtr($input,'-_,','+/=')); 
	}
	
?>
