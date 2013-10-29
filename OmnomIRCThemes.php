<?php
$ssi_guest_access = true;
@require(dirname(__FILE__) . '/SSI.php');
header('Content-type: text/css');
function echoNewStyle($c1,$c2,$c3){
	echo "#UserListContainer,
	#smileyselect,
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
		border: 1px solid rgba(221, 221, 255, 0.4);
		border-top-color: rgba(221, 221, 255, 0.8);
	}

	td.curchan {
		border-bottom-color: $c1;
	}

	td.chan:hover {
		background: rgba(235, 241, 249, 0.4);
	}

	.linehigh {
		border: none;
	}
	#scrollbar:active{
		box-shadow: 0 0 4px $c1;
	}
	body,
	#scrollbar:hover,
	#scrollbar:active{
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