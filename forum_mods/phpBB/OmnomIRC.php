<?php
function getOircPages(){
	global $phpbb_root_path,$oirc_pagescache;
	if(isset($oirc_pagescache)){
		return $oirc_pagescache;
	}
	$dh = opendir($phpbb_root_path);
	$pages = array();
	while(($filename = readdir($dh)) !== false){
		if(preg_match('/^.+\.php$/',$filename)){
			$pages[] = substr($filename,0,-4);
		}
	}
	sort($pages);
	$oirc_pagescache = $pages;
	return $pages;
}
function getOircPostPages(){
	global $request;
	$pages = array();
	$post_pages = $request->variable('oirc_options_enabledTags',array('oirc_emtpy_asfd' => 'on'));
	unset($post_pages['oirc_emtpy_asfd']);
	foreach(getOircPages() as $p){
		$pages[$p] = !empty($post_pages[$p]);
	}
	return $pages;
}
function getOircPagePicker($disp_pages_tmp = false){ // oirc_disppages
	global $user;
	$user->add_lang_ext('omnimaga/OmnomIRC','oirc_pages');
	if(!$disp_pages_tmp){
		$disp_pages_tmp = array();
	}
	
	$disp_pages = array();
	foreach(getOircPages() as $p){
		$disp_pages[$p] = !isset($disp_pages_tmp[$p]) || $disp_pages_tmp[$p];
	}
	
	$simpleFields = array(
		$user->lang('OIRC_PAGES_INDEX') => array('index',array('index')),
		$user->lang('OIRC_PAGES_BOARDS') => array('forum',array('viewforum')),
		$user->lang('OIRC_PAGES_THREADS') => array('topic',array('viewtopic')),
		$user->lang('OIRC_PAGES_PROFILES') => array('profile',array('memberlist')),
		$user->lang('OIRC_PAGES_MODERATION') => array('settings',array('ucp','mcp'))
	);
	$miscarray = array_keys($disp_pages);
	foreach($simpleFields as $f){
		$miscarray = array_diff($miscarray,$f[1]);
	}
	$simpleFields[$user->lang('OIRC_PAGES_MISC')] = array('misc',array_values($miscarray));
	
	$html = '<div id="oirc_page_picker">
	<input type="hidden" name="oirc_options_changetags" value="1" />
	<fieldset style="border:1px solid black;margin-bottom:1em;padding:1em;"><legend>'.$user->lang('OIRC_PAGES').'</legend>
	<a href="#" id="oirc_switch_view">'.htmlentities($user->lang('OIRC_PAGES_SWITCH_ADVANCED')).'</a>
	<div id="oirc_simple">';
	foreach($simpleFields as $f => $a){
		$html .= '<label>'.$f.' <input type="checkbox" id="oirc_simple_'.$a[0].'" data-key="'.$f.'" /></label><br>';
	}
	$html .= '</div><div id="oirc_advanced" style="display:none;">';
	foreach($disp_pages as $p => $o){
		$html .= '<nobr style="padding-right:0.4em;display:inline-block;" class="oirc_actioninput"><input type="checkbox" name="oirc_options_enabledTags['.$p.']" id="oirc_options_'.$p.'" '.($o ? ' checked="checked"' : '').' /> <label for="oirc_options_'.$p.'">'.$p.'</label></nobr> ';
	}
	$html .= '<br /><label>'.htmlentities($user->lang('OIRC_PAGES_CHECK_ALL')).'<input type="checkbox" id="oirc_options_check_all" /></label>
	</div>
	</fieldset>
	</div>
	<script type="text/javascript">
		window.addEventListener("load",function(){
			var tags = document.getElementsByClassName("oirc_actioninput"),
				i,
				simpleFields = '.json_encode($simpleFields).',
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
					this.innerHTML = '.json_encode($user->lang('OIRC_PAGES_SWITCH_SIMPLE')).';
					updateAdvancedView();
				}else{
					document.getElementById("oirc_advanced").style.display = "none";
					document.getElementById("oirc_simple").style.display = "";
					this.innerHTML = '.json_encode($user->lang('OIRC_PAGES_SWITCH_ADVANCED')).';
					updateSimpleView(false);
				}
			},true);
			updateSimpleView(true);
		},false);
	</script>';
	return $html;
}
