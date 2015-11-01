<?php
// this file will be called always.
if(!function_exists('generateOircSigURL')){
	// we only want to run this block once
	function generateOircSigURL(){
		global $config,$only_include_oirc;
		$only_include_oirc = true;
		include_once(realpath(dirname(__FILE__)).'/checkLogin/index.php');
		$nick = '*';
		$time = (string)(time() - 60*60*24 + 60); // the sig key is only valid for one min!
		$uid = rand();
		$network = 0;
		$signature = $time.'|'.hash_hmac('sha512',$nick.$uid,$network.$config['sigKey'].$time);
		return 'nick='.base64_url_encode($nick).'&signature='.base64_url_encode($signature).'&time='.$time.'&network='.$network.'&id='.$uid.'&noLoginErrors&serverident';
	}

	function sendToOmnomIRC($message,$channel){
		global $config;
		$sigurl = generateOircSigURL();
		file_get_contents($config['oircUrl'].'/message.php?message='.base64_url_encode($message).'&channel='.$channel.'&serverident&'.$sigurl);
	}
	
	function getTopicName($tid){
		$t = Fetch(Query("select title from {threads} where id={0}",$tid));
		return $t['title'];
	}
	
	function getOircSendToChan($tid){
		$topic = Fetch(Query("select forum from {threads} where id={0}", $tid));
		if($topic && $topic['forum']){
			$power = Fetch(Query("select minpower from {forums} where id={0}", $topic['forum']));
			if($power !== NULL && $power['minpower'] >= 0 && $power['minpower'] <= 3){
				return Settings::pluginGet('oirc_notify_'.$power['minpower']);
			}
		}
		return -1;
	}
	function getOircPages(){
		global $pluginpages,$oirc_pagescache;
		if(isset($oirc_pagescache)){
			return $oirc_pagescache;
		}
		$dh = opendir('pages');
		$pages = array();
		while(($filename = readdir($dh)) !== false){
			if(preg_match('/^.+\.php$/',$filename)){
				$pages[] = substr($filename,0,-4);
			}
		}
		$pages = array_merge($pages,array_keys($pluginpages));
		sort($pages);
		$oirc_pagescache = $pages;
		return $pages;
	}
	function getOircPagePicker($hack_in_id,$disp_pages_tmp = false){ // oirc_disppages
		if(!$disp_pages_tmp){
			$disp_pages_tmp = array();
		}
		
		$disp_pages = array();
		foreach(getOircPages() as $p){
			$disp_pages[$p] = !isset($disp_pages_tmp[$p]) || $disp_pages_tmp[$p];
		}
		
		$simpleFields = array(
			'Index' => array('index',array('board')),
			'Boards' => array('forum',array('forum')),
			'Threads' => array('topic',array('thread')),
			'Profiles' => array('profile',array('profile','editprofile','private','sendprivate')),
			'Moderation' => array('settings',array('admin','pluginmanager','editsettings'))
		);
		$miscarray = array_keys($disp_pages);
		foreach($simpleFields as $f){
			$miscarray = array_diff($miscarray,$f[1]);
		}
		$simpleFields['Misc'] = array('misc',array_values($miscarray));
		
		echo '<div id="oirc_page_picker">
		<input type="hidden" name="oirc_options_changetags" value="1" />
		<fieldset><legend>Pages on which to show OmnomIRC</legend>
		<a href="#" id="oirc_switch_view">Switch to advanced settings</a>
		<div id="oirc_simple">';
		foreach($simpleFields as $f => $a){
			echo '<label>',$f,' <input type="checkbox" id="oirc_simple_',$a[0],'" data-key="',$f,'" /></label><br>';
		}
		echo '</div><div id="oirc_advanced" style="display:none;">';
		foreach($disp_pages as $p => $o){
			echo '<nobr style="padding-right:0.4em;display:inline-block;" class="oirc_actioninput"><input type="checkbox" name="oirc_options_enabledTags[',$p,']" id="oirc_options_',$p,'" ',$o ? ' checked="checked"' : '',' /> <label for="oirc_options_',$p,'">',$p,'</label></nobr> ';
		}
		echo '<br /><label>Check all:<input type="checkbox" id="oirc_options_check_all" /></label>
		</div>
		</fieldset>
		</div>
		<script type="text/javascript">
			window.addEventListener("load",function(){
				$("#',$hack_in_id,'").closest("tr").before(
					$("<tr>").addClass("cell0").append(
						$("<td>").attr("colspan",2).append(
							$("#oirc_page_picker")
						)
					)
				);
				var tags = document.getElementsByClassName("oirc_actioninput"),
					i,
					simpleFields = ',json_encode($simpleFields),',
					getState = function(a,id){
						console.log(id);
						var ormask = false,
							andmask = true;
						for(i = 0;i < a.length;i++){
							var elem = document.getElementById("oirc_options_"+a[i]);
							if(elem){
								ormask |= elem.checked;
								andmask &= elem.checked;
							}
						}
						console.log(ormask);
						console.log(andmask);
						if(ormask && !andmask){
							document.getElementById(id).indeterminate = true;
						}else{
							document.getElementById(id).checked = andmask;
						}
					},
					updateAdvancedView = function(){
						var max_width = 0,
							ormask = false,
							andmask = true;
						for(i = 0;i < tags.length;i++){
							var c = tags[i].getElementsByTagName("input")[0].checked;
							ormask |= c;
							andmask &= c;
							tags[i].style.width = "";
							if(tags[i].offsetWidth > max_width){
								max_width = tags[i].offsetWidth;
							}
						}
						for(i = 0;i < tags.length;i++){
							tags[i].style.width = max_width+"px";
						}
						if(ormask && !andmask){
							document.getElementById("oirc_options_check_all").indeterminate = true;
						}else{
							document.getElementById("oirc_options_check_all").checked = andmask;
						}
					},
					notrigger = true,
					updateSimpleView = function(first){
						notrigger = true;
						for(var key in simpleFields){
							if(simpleFields.hasOwnProperty(key)){
								var self = simpleFields[key];
								getState(self[1],"oirc_simple_"+self[0]);
								if(first){
									document.getElementById("oirc_simple_"+self[0]).addEventListener("change",function(){
										if(!notrigger){
											var boxes = simpleFields[this.dataset.key][1];
											for(i = 0;i < boxes.length;i++){
												var elem = document.getElementById("oirc_options_"+boxes[i]);
												if(elem){
													elem.checked = this.checked;
												}
											}
										}
									},false);
								}
							}
						}
						notrigger = false;
					};
				document.getElementById("oirc_options_check_all").addEventListener("change",function(){
					for(i = 0;i < tags.length;i++){
						tags[i].getElementsByTagName("input")[0].checked = this.checked;
					}
				},true);
				document.getElementById("oirc_switch_view").addEventListener("click",function(e){
					e.preventDefault();
					if(document.getElementById("oirc_advanced").style.display == "none"){
						document.getElementById("oirc_advanced").style.display = "";
						document.getElementById("oirc_simple").style.display = "none";
						this.innerHTML = "Switch to simple settings";
						updateAdvancedView();
					}else{
						document.getElementById("oirc_advanced").style.display = "none";
						document.getElementById("oirc_simple").style.display = "";
						this.innerHTML = "Switch to advanced settings";
						updateSimpleView(false);
					}
				},true);
				updateSimpleView(true);
			},false);
		</script>';
		//var_dump($pages);
	}
}

global $title;
$boardurl = 'http'.($_SERVER['SERVER_PORT'] == 443?'s':'').'://'.(isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME']).$_SERVER['SCRIPT_NAME'];
$settings = array(
	'oirc_title' => array(
		'type' => 'text',
		'name' => 'OmnomIRC title',
		'default' => 'OmnomIRC chat'
	),
	'oirc_height' => array(
		'type' => 'integer',
		'name' => 'OmnomIRC frame height',
		'default' => '280'
	),
	'oirc_topics' => array(
		'type' => 'boolean',
		'name' => 'Notify of new threads',
		'default' => true
	),
	'oirc_posts' => array(
		'type' => 'boolean',
		'name' => 'Notify of new replies',
		'default' => true
	),
	'oirc_edits' => array(
		'type' => 'boolean',
		'name' => 'Notify of edits',
		'default' => true
	),
	'oirc_topicnotification' => array(
		'type' => 'text',
		'name' => 'Notification message for new threads',
		'default' => '{COLOR}10New thread by {COLOR}03{NAME} {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'?page=thread&id={TOPICID}'
	),
	'oirc_postnotification' => array(
		'type' => 'text',
		'name' => 'Notification message for new replies',
		'default' => '{COLOR}10New reply by {COLOR}03{NAME} {COLOR}10in {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'?page=thread&id={TOPICID}#{POSTID}'
	),
	'oirc_editnotification' => array(
		'type' => 'text',
		'name' => 'Notification message for post edits',
		'default' => '{COLOR}10Edit by {COLOR}03{NAME} {COLOR}10on {COLOR}04{TOPIC} {COLOR}12'.$boardurl.'?page=thread&id={TOPICID}#{POSTID}'
	),
	'oirc_notify_0' => array(
		'type' => 'integer',
		'name' => 'Notification channel for Regular',
		'default' => '-1'
	),
	'oirc_notify_1' => array(
		'type' => 'integer',
		'name' => 'Notification channel for Local Mod',
		'default' => '-1'
	),
	'oirc_notify_2' => array(
		'type' => 'integer',
		'name' => 'Notification channel for Full Mod',
		'default' => '-1'
	),
	'oirc_notify_3' => array(
		'type' => 'integer',
		'name' => 'Notification channel for Administrator',
		'default' => '-1'
	),
	'oirc_frameurl' => array(
		'type' => 'text',
		'name' => 'DO NOT EDIT THIS!!!!',
		'default' => $config['oircUrl'].'/index.php?network='.$config['network']
	),
	'oirc_disppages' => array(
		'type' => 'text',
		'name' => 'DO NOT EDIT THIS!!!!',
		'default' => ''
	)
);

if($title != __("Edit settings") && !((isset($_GET['id']) && $_GET['id'] == 'OmnomIRC') || (isset($_POST['_plugin']) && $_POST['_plugin'] == 'OmnomIRC'))){
	return;
}

// starting here are the settings-specific things. It wouldn't hurt to run that always but in theory it's a performance decrease, so we don't.

global $config,$only_include_oirc;
if(isset($config['installed'])){

$channeloptions = array(
	-1 => 'No notifications'
);

$sigurl = generateOircSigURL();
$s = file_get_contents($config['oircUrl'].'/config.php?channels&'.$sigurl);

if($s != ''){
	$s = json_decode($s,true);
	if($s !== NULL && !empty($s['channels'])){
		foreach($s['channels'] as $i => $n){
			$channeloptions[$i] = $n;
		}
	}
}

foreach($settings as $k => &$v){
	if(preg_match('/oirc_notify_\d/',$k)){
		$v['type'] = 'options';
		$v['options'] = $channeloptions;
	}
}

$settings = array_merge($settings,array(
	'oirc_config_installed' => array(
		'type' => 'boolean',
		'name' => 'Installed',
		'default' => $config['installed']
	),
	'oirc_config_sigKey' => array(
		'type' => 'text',
		'name' => 'Signature key',
		'default' => $config['sigKey']
	),
	'oirc_config_network' => array(
		'type' => 'integer',
		'name' => 'Network ID',
		'default' => $config['network']
	),
	'oirc_config_oircUrl' => array(
		'type' => 'text',
		'name' => 'OmnomIRC url',
		'default' => $config['oircUrl']
	)
));

print '
<style type="text/css">
#oirc_frameurl,label[for="oirc_frameurl"],#oirc_disppages,label[for="oirc_disppages"]{
	visibility:hidden;
}
</style>
<table class="outline margin width75" id="oirc_admin_pannel">
	<tr class="header1">
		<th>'.Settings::pluginGet('oirc_title').' admin pannel</th>
	</tr>
	<tr class="cell1">
		<td style="text-align: center;">
			<iframe id="ircbox" src="'.Settings::pluginGet('oirc_frameurl').'&amp;admin" style="width:100%;height:'.Settings::pluginGet('oirc_height').'px;border-style:none;"></iframe>
		</td>
	</tr>
</table>
<script type="text/javascript">
window.addEventListener("load",function(){
	$("input[value=\\"OmnomIRC\\"]").parent().after(
		$("#oirc_admin_pannel")
	);
},false);
</script>
';
if(!empty($_POST['oirc_options_changetags'])){
	unset($_POST['oirc_options_changetags']);
	$pages = array();
	foreach(getOircPages() as $p){
		$pages[$p] = !empty($_POST['oirc_options_enabledTags'][$p]);
	}
	unset($_POST['oirc_options_enabledTags']);
	$_POST['oirc_disppages'] = serialize($pages);
	getOircPagePicker('oirc_disppages',unserialize($_POST['oirc_disppages']));
}else{
	getOircPagePicker('oirc_disppages',unserialize(Settings::pluginGet('oirc_disppages')));
}

if(isset($_POST['_plugin'])){
	// we are saving!
	if(isset($_POST['oirc_config_installed'])){
		$config['installed'] = $_POST['oirc_config_installed'] == 1;
	}
	if(isset($_POST['oirc_config_sigKey'])){
		$config['sigKey'] = $_POST['oirc_config_sigKey'];
	}
	if(isset($_POST['oirc_config_network'])){
		$config['network'] = (int)$_POST['oirc_config_network'];
	}
	if(isset($_POST['oirc_config_oircUrl'])){
		$config['oircUrl'] = $_POST['oirc_config_oircUrl'];
	}
	$_POST['oirc_frameurl'] = $config['oircUrl'].'/index.php?network='.$config['network'];
	if(!writeConfig()){
		Alert(__("Please make sure that the OmnomIRC config file is writable!"));
	}
	
	
}
}else{
	$only_include_oirc = true;
	include_once(realpath(dirname(__FILE__)).'/checkLogin/index.php');
}
?>