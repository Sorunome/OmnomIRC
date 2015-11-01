<?php
if($loguser['powerlevel'] >= 0 && $loguserid && !getSetting("disableomnom", true))
{
write("        <table class=\"outline margin width100\">
                <tr class=\"cell1\">
                        <td style=\"text-align: center;\">
                                <iframe id=\"ircbox\" src=\"".Settings::pluginGet('omnomircurl')."\" style=\"width:100%;height:300px;border-style:none;\"></iframe>
                        </td>
                </tr>
        </table>");
}
