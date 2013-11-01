<?php
$ssi_guest_access = true;
@require(dirname(__FILE__) . '/SSI.php');
header('Content-type: text/css');
/* convert function source: http://mekshq.com/how-to-convert-hexadecimal-color-code-to-rgb-or-rgba-using-php/ */
function hex2rgba($color, $opacity = false) {
	$default = 'rgb(0,0,0)';
	//Return default if no color provided
	if(empty($color))
		return $default; 
	//Sanitize $color if "#" is provided 
	if ($color[0] == '#' ) {
		$color = substr( $color, 1 );
	}
	//Check if color has 6 or 3 characters and get values
	if (strlen($color) == 6) {
		$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
	} elseif ( strlen( $color ) == 3 ) {
		$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
	} else {
		return $default;
	}
	//Convert hexadec to rgb
	$rgb =  array_map('hexdec', $hex);
	//Check if opacity is set(rgba or rgb)
	if($opacity){
		if(abs($opacity) > 1)
			$opacity = 1.0;
		$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
	} else {
		$output = 'rgb('.implode(",",$rgb).')';
	}
	//Return rgb(a) color string
	return $output;
}
function echoNewStyle($c1,$c2,$c3){
	echo "#UserListContainer,
	#smileyselect,
	#lastSeenCont,
	#topicbox,
	#about > div,
	#scrollBar,
	td.curchan,
	#scrollBarLine,
	#UserList,
	.linehigh {
		background: $c2;
		border-color: $c1;
	}
	
	#topicbox {
		border-top: none;
	}

	#Channels {
		box-shadow: inset 0 -1px $c1;
	}

	td.chan {
		border: 1px solid ".hex2rgba($c1,0.4).";
		border-top-color: ".hex2rgba($c1,0.8).";
	}

	td.curchan {
		border-bottom-color: $c1;
	}

	td.chan:hover {
		background: ".hex2rgba($c2,0.8).";
	}

	.linehigh {
		border: none;
	}
	#scrollbar:active{
		box-shadow: 0 0 4px $c1;
	}
	body,
	#scrollbar:hover,
	#scrollbar:active,
	#UserList:hover{
		background-color: $c3;
	}";
}
switch($user_info["theme"]) {
case 0: //default
	echo "";
	break;
case 1:
	echo "";
	break;
case 2:
	echo "";
	break;
case 3: //v4
	echoNewStyle("#DDDDFF","#EBF1F9","#FFFFFF");
	break;
case 4: //v3
	echoNewStyle("#B0B0B0","#A8A8A8","#BABABA");
	break;
case 5: //v5 lite
	echo "";
	break;
case 6: //v2.5
	echoNewStyle("#EFEFEF","#EBEAEA","#FFFFFF");
	break;
case 7: //v2
	echoNewStyle("#FFFFFF","#EBEBEB","#F2F2F2");
	break;
case 8: //v5r
	echoNewStyle("#999999","#CACACA","#DFDFDF");
	break;
}
?>