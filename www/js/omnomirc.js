/*
	OmnomIRC COPYRIGHT 2010,2011 Netham45
	OmnomIRC3 rewrite COPYRIGHT 2013 Nathaniel 'Eeems' van Diepen

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
(function(window,$,undefined){
	var $o = window.OmnomIRC = window.$o = function(){
		return 'Version: '+$o.version
	},$i,log=console.log;
	$.extend($o,{
		version: '0.1',
		send: function(msg){
			if(msg !== ''){
				$o.event('send',msg);
			}
		},
		event: function(event_name,message){
			switch(event_name){
				case 'ready':
					log('[DOCUMENT_READY]');
					break;
				default:
					log('['+event_name.toUpperCase()+'] '+message);
			}
		}
	});
	$(document).ready(function(){
		$i = $('#input');
		$('#send').click(function(){
			$o.send($i.val());
			$i.val('');
		});
		$i.keypress(function(e){
			if(e.keyCode == 13){
				$o.send($i.val());
				$i.val('');
			}
		});
		$o.event('ready');
	});
})(window,jQuery);