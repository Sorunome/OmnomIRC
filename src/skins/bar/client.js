/**
 * @license
 * OmnomIRC COPYRIGHT 2010,2011 Netham45
 *                    2012-2016 Sorunome
 *
 *  This file is part of OmnomIRC.
 *
 *  OmnomIRC is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OmnomIRC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
 */
'use strict';
$(function(){
	var oirc = new OmnomIRC(),
		$mbox = $('#omnomirc_messagebox'),
		page = (function(){
			var self = {
				addLine:function(tdName,tdMessage,line){
					console.log(tdName);
					console.log(line);
					if(tdName != '*' && line.network != 0){
						$mbox.append(
							'<br>',
							$('<div>').addClass('oirc_line').append($('<span>').append(tdName).addClass('oirc_name'),$('<span>').append(tdMessage).addClass('oirc_message'))
						);
					}else{
						$mbox.append(
							'<br>',
							$('<div>').addClass('oirc_linenotice').append(tdMessage)
						)
					}
					$mbox.animate({scrollTop:$mbox[0].scrollHeight},50);
				}
			};
			return {
				addLine:self.addLine
			}
		})(),
		channels = (function(){
			var self = {
				join:function(i,fn){
					oirc.channels.load(i,function(success,data){
						if(fn !== undefined){
							fn(success);
						}
					});
				}
			};
			return {
				join:self.join
			}
		})();
	$('#omnomirc_bar').click(function(e){
		e.preventDefault();
		$('#omnomirc_cont').toggle();
	});
	$('#omnomirc_cont').hide();
	oirc.onmessage = page.addLine;
	oirc.setClassPrefix('oirc_');
	oirc.initinput($('#omnomirc_send'));
	$('#omnomirc_send').keypress(function(e){
		if(e.which == 13){ // we hit enter
			oirc.send.send(function(){
				oirc.send.val('');
				$('#omnomirc_send').focus();
			})
		}
	})
	oirc.connect(function(){
		channels.join(oirc.options.get('curChan'),function(success){
			if(!success){
				channels.join(0);
			}
		});
	});
});
