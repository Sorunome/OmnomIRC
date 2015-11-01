<?php
function template_omnomirc_action_settings($context_offset,$r)
{
	global $context, $txt;
	loadLanguage('oirc');
	if ($r)
		echo '<select name="default_options_master[oirc_actions]">
				<option value="0" selected="selected">', $txt['themeadmin_reset_options_none'], '</option>
				<option value="1">', $txt['themeadmin_reset_options_change'], '</option>
				<option value="2">', $txt['themeadmin_reset_options_remove'], '</option>
			</select>';
	echo '<fieldset><legend>',$txt['oirc_actions_legend'],'</legend>';
	$simpleFields = array(
		'Index' => array('index',array('index','forum')),
		'Boards' => array('board',array('board')),
		'Topics' => array('topic',array('topic')),
		'Profiles' => array('profile',array('profile')),
		'Moderation' => array('settings',array('admin','moderate'))
	);
	$miscarray = array_keys($context[$context_offset]);
	foreach($simpleFields as $f)
	{
		$miscarray = array_diff($miscarray,$f[1]);
	}
	$simpleFields['Misc'] = array('misc',array_values($miscarray));
	$ormask = false;
	$andmask = true;
	echo '
	<a href="#" id="oirc_switch_view">',$txt['oirc_switch_advanced'],'</a>
	<div id="oirc_simple">';
	foreach($simpleFields as $f => $a)
	{
		echo '<label>',$f,' <input type="checkbox" id="oirc_simple_',$a[0],'" data-key="',$f,'" /></label><br>';
	}
	echo '</div><div id="oirc_advanced" style="display:none;">';
	foreach($context[$context_offset] as $a => $o)
	{
		$ormask |= $o;
		$andmask &= $o;
		echo '<nobr style="padding-right:0.4em;display:inline-block;" class="oirc_actioninput"><input type="checkbox" name="oirc_options_enabledTags[',$a,']" id="oirc_options_', $a, '" ', $o ? ' checked="checked"' : '', ' class="input_check" /> <label for="oirc_options_', $a, '">', $a, '</label></nobr> ';
	}
	// $ormask is false if everything is unchecked, of $ormask is true and $andmask is false then the checkall is in the indeterminate state.
	echo '<br /><label>',$txt['oirc_check_all_actions'],'<input type="checkbox" id="oirc_options_check_all" />
	</div>
	</label></fieldset>
	<input type="hidden" value="1" name="oirc_options_changetags" />
	<script type="text/javascript">
		(function(){
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
					this.innerHTML = ',json_encode($txt['oirc_switch_simple']),';
					updateAdvancedView();
				}else{
					document.getElementById("oirc_advanced").style.display = "none";
					document.getElementById("oirc_simple").style.display = "";
					this.innerHTML = ',json_encode($txt['oirc_switch_advanced']),';
					updateSimpleView(false);
				}
			},true);
			updateSimpleView(true);
		})();
	</script>';
}
?>