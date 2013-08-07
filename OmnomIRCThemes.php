<?php
$ssi_guest_access = true;
@require(dirname(__FILE__) . '/SSI.php');
header('Content-type: text/css');
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
	echo "#UserListContainer,
	#smileyselect,
	#topicbox,
	#about > div,
	#scrollBar,
	td.curchan,
	.linehigh {
		background: #EBF1F9;
		border: 1px solid #DDDDFF;
	}
	
	#topicbox {
		border-top: none;
	}

	#Channels {
		box-shadow: inset 0 -1px #DDDDFF;
	}

	td.chan {
		border: 1px solid rgba(221, 221, 255, 0.4);
		border-top-color: rgba(221, 221, 255, 0.8);
	}

	td.curchan {
		border-bottom-color: #DDDDFF;
	}

	td.chan:hover {
		background: rgba(235, 241, 249, 0.4);
	}

	.linehigh {
		border: none;
	}";
	break;
case 4: //v3
	echo "#UserListContainer,
	#smileyselect,
	#topicbox,
	#about > div,
	#scrollBar,
	td.curchan,
	.linehigh {
		background: #A8A8A8;
		border: 1px solid #B0B0B0;
	}
	
	#topicbox {
		border-top: none;
	}

	#Channels {
		box-shadow: inset 0 -1px #B0B0B0;
	}

	td.chan {
		border: 1px solid rgba(176, 176, 176, 0.4);
		border-top-color: rgba(176, 176, 176, 0.8);
	}

	td.curchan {
		border-bottom-color: #B0B0B0;
	}

	td.chan:hover {
		background: rgba(168, 168, 168, 0.4);
	}

	.linehigh {
		border: none;
	}";
	break;
case 5: //v5 lite
	echo "";
	break;
case 6: //v2.5
	echo "#UserListContainer,
	#smileyselect,
	#topicbox,
	#about > div,
	#scrollBar,
	td.curchan,
	.linehigh {
		background: #EBEAEA;
		border: 1px solid #EFEFEF;
	}
	
	#topicbox {
		border-top: none;
	}

	#Channels {
		box-shadow: inset 0 -1px #EFEFEF;
	}

	td.chan {
		border: 1px solid rgba(239, 239, 239, 0.4);
		border-top-color: rgba(239, 239, 239, 0.8);
	}

	td.curchan {
		border-bottom-color: #EFEFEF;
	}

	td.chan:hover {
		background: rgba(235, 234, 234, 0.4);
	}

	.linehigh {
		border: none;
	}";
	break;
case 7: //v2
	echo "#UserListContainer,
	#smileyselect,
	#topicbox,
	#about > div,
	#scrollBar,
	td.curchan,
	.linehigh {
		background: #EBEBEB;
		border: 1px solid #FFFFFF;
	}
	
	#topicbox {
		border-top: none;
	}

	#Channels {
		box-shadow: inset 0 -1px #FFFFFF;
	}

	td.chan {
		border: 1px solid rgba(255, 255, 255, 0.4);
		border-top-color: rgba(255, 255, 255, 0.8);
	}

	td.curchan {
		border-bottom-color: #FFFFFF;
	}

	td.chan:hover {
		background: rgba(235, 235, 235, 0.4);
	}

	.linehigh {
		border: none;
	}";
	break;
case 8: //v5r
	echo "#UserListContainer,
	#smileyselect,
	#topicbox,
	#about > div,
	#scrollBar,
	td.curchan,
	.linehigh {
		background: #cacaca;
		border: 1px solid #999999;
	}
	
	#topicbox {
		border-top: none;
	}

	#Channels {
		box-shadow: inset 0 -1px #999999;
	}

	td.chan {
		border: 1px solid rgba(153, 153, 153, 0.4);
		border-top-color: rgba(153, 153, 153, 0.8);
	}

	td.curchan {
		border-bottom-color: #cacaca;
	}

	td.chan:hover {
		background: rgba(202, 202, 202, 0.4);
	}

	.linehigh {
		border: none;
	}";
	break;
}
?>