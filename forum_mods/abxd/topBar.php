<?php
if(
		isIPBanned($_SERVER['REMOTE_ADDR'])
		||
		(
			!Settings::pluginGet('oirc_view_guests')
			&&
			(
				$loguser['name'] == ''
				||
				($loguser['powerlevel'] <= 0 && Settings::pluginGet('oirc_view_posts') > $loguser['posts'])
			)
		)
	){
	return;
}

$oirc_userpages = unserialize(getSetting('oirc_disppages_user',false));
$oirc_globalpages = unserialize(Settings::pluginGet('oirc_disppages'));

if((isset($oirc_userpages[CURRENT_PAGE]) && $oirc_userpages[CURRENT_PAGE]) || (!isset($oirc_userpages[CURRENT_PAGE]) && isset($oirc_globalpages[CURRENT_PAGE]) && $oirc_globalpages[CURRENT_PAGE])){
	write('
	<table class="outline margin width100">
		<tr class="header1">
			<th>'.Settings::pluginGet('oirc_title').'</th>
		</tr>
		<tr class="cell1">
			<td style="text-align: center;">
				<iframe id="ircbox" src="'.Settings::pluginGet('oirc_frameurl').'" style="width:100%;height:'.Settings::pluginGet('oirc_height').'px;border-style:none;"></iframe>
			</td>
		</tr>
	</table>
	<script type="text/javascript">
	(function(){
		var doFlash = false,
			intervalHandler = false,
			originalTitle = "",
			startFlash = function(){
				if(!doFlash){
					var alternator = true;
					doFlash = true;
					originalTitle = document.title;
					intervalHandler = setInterval(function(){
						document.title = (alternator?"[ @] ":"[@ ] ")+originalTitle;
						alternator = !alternator;
					},500);
				}
			},
			stopFlash = function(){
				if(intervalHandler){
					clearInterval(intervalHandler);
					intervalHandler = false;
					document.title = originalTitle;
				}
				doFlash = false;
			};
		window.addEventListener("message",function(e){
			if(e.origin === "'.Settings::pluginGet('oirc_domain').'"){
				switch(e.data){
					case "startFlash":
						startFlash();
						break;
					case "stopFlash":
						stopFlash();
						break;
				}
			}
		},false);
	})();
	</script>
	');
}
