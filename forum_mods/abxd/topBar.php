<?php
if(CURRENT_PAGE!='admin' && $loguser['powerlevel'] >= 0 && $loguserid && !getSetting("disableomnom", true))
{
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
	</table>');
}
