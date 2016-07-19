<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2016 Sorunome

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
namespace oirc\skins\bar;
function getPage(){
	$html = '<div id="omnomirc_bar">View chats</div>
	<div id="omnomirc_cont">
		<div id="omnomirc_messagebox"></div>
		<input id="omnomirc_send">
	</div>';
	return array(
		'html' => $html,
		'js' => array(
			array(
				'file' => 'client.js',
				'minified' => true
			)
		),
		'css' => array(
			array(
				'file' => 'style.css',
				'minified' => true
			)
		)
	);
}
\oirc\Skins::hook('','\oirc\skins\bar\getPage');
