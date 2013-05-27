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
	},$i,$s,$h,$cl,$tl,hht,log=console.log,tabs=[],selectedTab=0;
	$.extend($o,{
		version: '0.1',
		send: function(msg){
			if(msg !== ''){
				$o.event('send',msg);
				$cl.append($('<li>').text(msg));
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
		},
		selectTab: function(id){
			$o.event('tab_select',id);
			if(id<tabs.length-1&&id>=0){
				selectedTab=id;
			}
			$tl.children('.clicked').removeClass('clicked');
			$($tl.children().get(id)).addClass('clicked');
			$('#title').text(tabs[id].title);
			$('#topic').text(tabs[id].topic);
		},
		tabDOM: function(id){
			
		},
		addTab: function(name,title){
			tabs.push({
				name: name,
				title: title
			});
			$tl.append($o.tabObj(tabs.length-1));
		},
		removeTab: function(id){
			tabs.splice(id,1);
			if(selectedTab==id&&selectedTab>0){
				selectedTab--;
			}
			$o.refreshTabs();
		},
		tabObj: function(id){
			if(typeof id !== 'undefined'){
				return $('<span>')
					.addClass('tab')
					.text(tabs[id].name)
					.click(function(){
						if($(this).data('id')!=selectedTab){
							$o.selectTab($(this).data('id'));
							return false;
						}
					})
					.append(
						$('<span>')
							.addClass('close-button')
							.click(function(){
								$o.removeTab(id);
								return false;
							})
							.html('&times;')
					)
					.data('id',id);
			}
		},
		refreshTabs: function(){
			$tl.html('');
			var i,tab;
			for(i in tabs){
				tab = $o.tabObj(i);
				if(i==selectedTab){
					tab.addClass('clicked');
					$('#title').text(tabs[i].title);
					$('#topic').text(tabs[i].topic);
				}
				$tl.append(tab);
			}
		}
	});
	$(document).ready(function(){
		$i = $('#input');
		$s = $('#send');
		$cl = $('#content-list');
		$tl = $('#tabs-list');
		$h = $('#head');
		$s.click(function(){
			if(!$s.hasClass('clicked')){
				$s.addClass('clicked');
				setTimeout(function(){
					$s.removeClass('clicked');
				},500);
			}
			$o.send($i.val());
			$i.val('');
		});
		$i.keypress(function(e){
			if(e.keyCode == 13){
				if(!$s.hasClass('clicked')){
					$s.addClass('clicked');
					setTimeout(function(){
						$s.removeClass('clicked');
					},500);
				}
				$o.send($i.val());
				$i.val('');
			}
		});
		$('#settings, #users').click(function(){
			$(this).addClass('open');
		}).hover(function(){
			$(this).addClass('hovered');
		},function(){
			$(this).removeClass('hovered');
		}).children('.close-button').click(function(){
			$(this).parent().removeClass('open');
			return false;
		});
		$('#users').hoverIntent({
			out: function(){
				$(this).removeClass('open');
			},
			timeout: 1000
		});
		$('#content').click(function(){
			$('#settings, #users, #head').removeClass('hovered').removeClass('open');
		});
		$h.hoverIntent({
			out: function(){
				$h.removeClass('hovered');
			},
			over: function(){},
			timeout: 1000
		}).hover(function(){
			hht = setTimeout(function(){
				$o.event('timeout','Head HoverIntent timeout');
				$('#head:hover').addClass('hovered');
			},1000);
		},function(){
			clearInterval(hht);
		}).click(function(){
			$(this).addClass('hovered');
		});
		$('.unselectable').attr('unselectable','on');
		//DEBUG
		for(var i=0;i<10;i++){
			tabs.push({
				name: 'Tab '+i,
				title: 'Tab '+i,
				topic: 'Topic for tab '+i
			});
		}
		//END DEBUG
		$o.refreshTabs();
		$o.event('ready');
		$h.addClass('hovered');
		setTimeout(function(){
			$h.removeClass('hovered');
		},1000);
	});
})(window,jQuery);