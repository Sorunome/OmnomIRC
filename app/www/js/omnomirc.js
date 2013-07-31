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
(function(window,$,io,undefined){
	var $o = window.OmnomIRC = window.$o = function(){
			return 'Version: '+$o.version
		},
		event = function(msg,type){
			type=typeof type == 'undefined'?'event':type;
			switch(type){
				case 'ready':type='document_ready';break;
			}
			log('['+type.toUpperCase()+'] '+msg);
		},
		log = function(){
				console.log.apply(console,arguments);
		},
		exists = function(object){
			return typeof object != 'undefined';
		},
		prevent = function(e){
			e.stopImmediatePropagation();
			e.stopPropagation();
			e.preventDefault();
			return false;
		},
		selectedTab=0,
		settings = {
			colour: false,
			timestamp: false,
			server: location.origin,
			autojoin: [
				'#omnimaga',
				'#omnimaga-fr',
				'#irp'
			]
		},
		tabs = [],
		properties = {
			nick: 'User',
			sig: '',
			tabs: tabs
		},
		commands = [
			{
				cmd: 'names',
				fn: function(args){
					socket.emit('names',{
						name: tabs[selectedTab].name
					});
				}
			},
			{
				cmd: 'me',
				fn: function(args){
					var i,ret='';
					for(i=1;i<args.length;i++){
						ret += args[i];
					}
					socket.emit('message',{
						from: 0,
						message: properties.nick+' '+ret,
						room: tabs[selectedTab].name
					});
				}
			},
			{
				cmd: 'nick',
				fn: function(args){
					properties.nick = args[1];
					$o.auth();
				}
			},
			{
				cmd: 'help',
				fn: function(args){
					var m = 'Commands:',i;
					for(i in commands){
						m += ' '+commands[i].cmd;
					}
					$o.msg(m);
				}
			},
			{
				cmd: 'open',
				fn: function(args){
					tabs.push({
						name: args[1],
						title: args[2],
						topic: 'Topic for '+args[2]
					});
					$o.refreshTabs();
				}
			},
			{
				cmd: 'clear',
				fn: function(args){
					$cl.html('');
					tabs[selectedTab].body = document.createDocumentFragment();
				}
			},
			{
				cmd: 'close',
				fn: function(args){
					if(args.length > 1){
						$o.removeTab(args[1]);
					}else{
						$o.removeTab(selectedTab);
					}
				}
			},
			{
				cmd: 'tabs',
				fn: function(args){
					$o.msg('tabs:');
					for(var i in tabs){
						$o.msg('   ['+i+'] '+tabs[i].name);
					}
				}
			}
		],
		handles = [
			{
				on: 'names',
				fn: function(data){
					tabs[$o.tabIdForName(data.room)].names = data.names;
					if($o.tabIdForName(data.room) == selectedTab){
						$o.renderUsers();
					}
				}
			},
			{
				on: 'authorized',
				fn: function(data){
					properties.nick = data.nick;
					for(var i in settings.autojoin){
						socket.emit('join',{
							name: settings.autojoin[i]
						});
					}
				}
			},
			{
				on: 'join',
				fn: function(data){
					event('joined '+data.name);
					var flag = tabs.length == 0;
					$o.addTab(data.name,data.title);
					if(flag){
						$o.selectTab(0);
					}
				}
			},
			{
				on: 'reconnect',
				fn: function(data){
					event('reconnected');
					$o.auth();
				}
			},
			{
				on: 'message',
				fn: function(data){
					event('recieved message');
					var date = new Date(),
						string,
						time = date.getTime(),
						child,
						i,
						msg = function(msg){
							if(!settings.timestamp){
								string = '<span class="cell"></span>';
							}else{
								string = '<span class="cell">[<abbr class="date date_'+time+'" title="'+date.toISOString()+'"></abbr>]</span>';
							}
							child = $('<li>').html(string+'<span class="cell">'+msg.htmlentities()+'</span>');
							$o.msg({html:child},data.room);
						};
					if(data.from != 0){
						msg('	<'+data.from+'>	'+data.message);
					}else{
						msg('	* '+data.message);
					}
					abbrDate('abbr.date_'+time);
				}
			}
		],
		abbrDate = function(selector){
			if(settings.timestamp == 'fuzzy'){
				$(selector).timeago();
			}else{
				$(selector).each(function(){
					var timestamp = settings.timestamp,
						i,
						text='',
						date = new Date($(this).attr('title'));
					if(timestamp == 'exact'){
						timestamp = 'H:m:s t';
					}
					for(i=0;i<timestamp.length;i++){
						switch(timestamp[i]){
							case 'H':text+=((date.getHours()+11)%12)+1;break;
							case 'h':text+=date.getHours();break;
							case 'm':text+=date.getMinutes();break;
							case 's':text+=date.getSeconds();break;
							case 't':text+=(date.getHours()>11)?'pm':'am';break;
							default:text+=timestamp[i];
						}
					}
					$(selector).text(text);
				});
			}
		},
		socket,$i,$s,$h,$cl,$tl,hht;
	$.extend($o,{
		version: '3.0',
		connect: function(server){
			if($o.connected()){
				socket.disconnect();
				socket = undefined;
				delete $o.socket;
			}
			if(typeof server == 'undefined'){
				server = settings.server;
			}
			$o.socket = socket = io.connect(server);
			for(var i in handles){
				socket.on(handles[i].on,handles[i].fn);
			}
			$o.auth();
		},
		auth: function(){
			socket.emit('auth',{
				nick: properties.nick
				// TODO - send authorization info
			});
		},
		connected: function(){
			return typeof socket != 'undefined';
		},
		get: function(name,formatted){
			if(typeof formatted == 'undefined'){
				return exists(settings[name])?settings[name]:false;
			}else{
				var val = $o.get(name),
					type,
					values = false;
				switch(name){
					case 'autojoin':
						type = 'array';break;
					default:
						type = typeof val;
				}
				return {
					type: type,
					val: val,
					values: values,
					name: name
				};
			}
		},
		set: function(name,value,render){
			if(exists(settings[name])){
				settings[name] = value;
				$.localStorage('settings',JSON.stringify(settings));
				switch(name){
					case 'timestamp':abbrDate('abbr.date');break;
				}
				if(typeof render == 'undefined'){
					$o.renderSettings();
				}
				return true;
			}else{
				return false;
			}
		},
		renderSettings: function(){
			var name,setting,frag = document.createDocumentFragment(),item;
			for(name in settings){
				setting = $o.get(name,true);
				switch(setting.type){
					case 'array':
						item = $('<input>')
									.attr({
										type: 'text',
										id: 'setting_'+name
									})
									.val(setting.val)
									.change(function(){
										$o.set(this.id.substr(8),$(this).val().split(','),false);
									});
					break;
					case 'boolean':
						item = $('<input>')
									.attr({
										type: 'checkbox',
										id: 'setting_'+name
									})
									.change(function(){
										$o.set(this.id.substr(8),$(this).is(':checked'),false);
									});
						if(setting.val){
							item.attr('checked','checked');
						}
					break;
					case 'number':
					case 'string':default:
						item = $('<input>')
									.attr({
										type: 'text',
										id: 'setting_'+name
									})
									.val(setting.val)
									.change(function(){
										$o.set(this.id.substr(8),$(this).val(),false);
									});
				}
				$(frag).append(
					$('<li>')
						.addClass('row')
						.append(
							$('<span>')
								.text(name)
								.addClass('cell')
						)
						.append(
							$('<span>')
								.append(item)
								.addClass('cell')
						)
				);
			}
			$('#settings-list').html(frag);
		},
		renderUsers: function(){
			event('Rendering userlist');
			var $ul = $('#user-list').html(''),
				i,
				names = tabs[selectedTab].names;
			for(i in names){
				$ul.append(
					$('<li>').text(names[i])
				);
			}
		},
		selectedTab: function(){
			return selectedTab;
		},
		prop: function(name){
			return exists(properties[name])?properties[name]:null;
		},
		send: function(msg){
			if(msg !== ''){
				if(msg[0] == '/'){
					var args = msg.split(' '),
						cmd = args[0].substr(1),
						i;
					event(msg,'command');
					for(i in commands){
						if(commands[i].cmd == cmd){
							commands[i].fn(args);
							return;
						}
					}
					$o.msg(cmd+' is not a valid command.');
				}else{
					event(msg,'send');
					socket.emit('message',{
						message: msg,
						room: tabs[selectedTab].name,
						from: properties.nick
					});
				}
			}
		},
		msg: function(msg,tabName){
			var frag;
			if(typeof tabName == 'undefined' || tabName == tabs[selectedTab].name){
				frag = document.createDocumentFragment();
			}else{
				frag = tabs[$o.tabIdForName(tabName)].body;
			}
			switch(typeof msg){
				case 'string':
					$(frag).append($('<li>').html(msg.htmlentities()));
					break;
				case 'object':
					if(typeof msg.html == 'undefined'){
						$(frag).append($('<li>').html('&lt;'+msg.user+'&gt;&nbsp;'+msg.text.htmlentities()));
					}else{
						$(frag).append(msg.html);
					}
					break;
			}
			$(tabs[$o.tabIdForName(tabName) || selectedTab].body).append(frag);
			if(typeof tabName == 'undefined' || tabName == tabs[selectedTab].name){
				$o.selectTab(selectedTab);
			}
		},
		event: function(event_name,message){
			event(message,event_name);
		},
		selectTab: function(id){
			event(id+' '+tabs[id].name,'tab_select');
			if(id<tabs.length&&id>=0){
				selectedTab=id;
			}
			$tl.children('.clicked').removeClass('clicked');
			$($tl.children().get(id)).addClass('clicked');
			$('#title').text(tabs[id].title);
			$('#topic').text(tabs[id].topic);
			$cl.html($(tabs[id].body).clone());
			abbrDate('abbr.date');
			$o.renderUsers();
			$cl.scrollTop($cl[0].scrollHeight);
		},
		tabIdForName: function(name){
			for(var i in tabs){
				if(tabs[i].name == name){
					return i;
				}
			}
			return false;
		},
		tabDOM: function(id){
			return tabs[id].body;
		},
		addTab: function(name,title){
			event('Tab added: '+name+' with title '+title);
			if(!(function(){
				for(var i in tabs){
					if(name==tabs[i].name){
						return true;
					}
				}
				return false;
			})()){
				tabs.push({
					name: name,
					title: title,
					body: document.createDocumentFragment(),
					names: []
				});
				$tl.append($o.tabObj(tabs.length-1));
				$o.refreshTabs();
				$o.renderUsers();
			}else{
				event('Attempted to add an existing tab');
			}
		},
		removeTab: function(id){
			if(typeof tabs[id] != 'undefined'){
				event('Tab removed: '+tabs[id].name);
				socket.emit('part',{
					name: tabs[id].name
				});
				tabs.splice(id,1);
				if(selectedTab==id&&selectedTab>0){
					selectedTab--;
				}
			}
			$o.refreshTabs();
			$cl.html(tabs[selectedTab].body);
			$o.renderUsers();
		},
		tabObj: function(id){
			if(typeof id !== 'undefined'){
				return $('<div>')
					.addClass('tab')
					.text(tabs[id].title)
					.mouseup(function(e){
						switch(e.which){
							case 1:	// RMB
								if($(this).data('id')!=selectedTab){
									$o.selectTab($(this).data('id'));
									return prevent(e);
								}
								break;
							case 2:	// MMB
								$(this).children('span.close-button').click();
								return prevent(e);
								break;
							case 3:	// LMB
								return prevent(e);
								break;
							default:
								return prevent(e);
						}
					})
					.append(
						$('<span>')
							.addClass('close-button')
							.click(function(){
								$o.removeTab(id);
								return false;
							})
							.css({
								'position': 'absolute',
								'background-color': 'inherit',
								'top': 0,
								'right': 0
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
			if($tl.get(0).scrollHeight-20 != $tl.scrollTop()){
				$('#tabs-scroll-right').removeClass('disabled');
			}
			if($tl.scrollTop() != 0){
				$('#tabs-scroll-left').removeClass('disabled');
			}
		}
	});
	String.prototype.htmlentities = function(){
		return this.replace(/&/g, '&amp;').replace(/\s/g, '&nbsp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	};
	$(document).ready(function(){
		$.extend(settings,$.parseJSON($.localStorage('settings')));
		$.localStorage('settings',JSON.stringify(settings));
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
			$(this).children('.close-button').show();
		}).hover(function(){
			$(this).addClass('hovered');
		},function(){
			$(this).removeClass('hovered');
		}).children('.close-button').click(function(){
			$(this).parent().removeClass('open');
			$(this).hide();
			return false;
		}).hide();
		$('#users').hoverIntent({
			out: function(){
				$(this).removeClass('open');
				$(this).children('.close-button').hide();
			},
			timeout: 1000
		});
		$('#content').click(function(){
			$('#settings, #users, #head').removeClass('hovered').removeClass('open');
			$('#settings, #users').children('.close-button').hide()
		});
		$('.unselectable').attr('unselectable','on');
		$.contextMenu({
			selector: 'div.tab',
			items: {
				add: {
					name: 'New Tab',
					icon: 'add',
					callback: function(){
						$(this).contextMenu('hide');
						var title = prompt('Title');
						tabs.push({
							name: prompt('channel'),
							title: title,
							topic: 'Topic for '+title
						});
						$o.refreshTabs();
					}
				},
				s1: '',
				close: {
					name: 'Close',
					icon: 'delete',
					callback: function(){
						$(this).contextMenu('hide');
						$o.removeTab($(this).data('id'));
					}
				}
			},
			zIndex: 99999,
			trigger: 'right'
		});
		$.contextMenu({
			selector: '#tabs-list',
			items: {
				add: {
					name: 'New Tab',
					icon: 'add',
					callback: function(){
						$(this).contextMenu('hide');
						var title = prompt('Title');
						tabs.push({
							name: prompt('channel'),
							title: title,
							topic: 'Topic for '+title
						});
						$o.refreshTabs();
					}
				}
			},
			zIndex: 99999,
			trigger: 'right'
		});
		$('#tabs-scroll-right').click(function(){
			event('scroll right');
			$tl.scrollTop(($tl.scrollTop()||0)+20);
			if($tl.get(0).scrollHeight-20 == $tl.scrollTop()){
				$('#tabs-scroll-right').addClass('disabled');
			}
			$('#tabs-scroll-left').removeClass('disabled');
		});
		$('#tabs-scroll-left').click(function(){
			event('scroll left');
			$tl.scrollTop(($tl.scrollTop()||0)-20);
			if($tl.scrollTop() == 0){
				$('#tabs-scroll-left').addClass('disabled');
			}
			$('#tabs-scroll-right').removeClass('disabled');
		});
		(function scrollup(){
			$('#tabs-scroll-left').click();
			if($tl.scrollTop() != 0){
				setTimeout(scrollup,10);
			}
		})();
		//DEBUG
		/* for(var i=0;i<20;i++){
			tabs.push({
				name: '#Tab'+i,
				title: 'Tab '+i,
				topic: 'Topic for tab '+i
			});
		} */
		//END DEBUG
		event('Date '+new Date,'ready');
		$h.addClass('hovered');
		setTimeout(function(){
			$h.removeClass('hovered');
		},1000);
		$o.renderSettings();
		$o.connect();
	});
})(window,jQuery,io);
if (!Date.prototype.toISOString) {
    Date.prototype.toISOString = function() {
        function pad(n) { return n < 10 ? '0' + n : n }
        return this.getUTCFullYear() + '-'
            + pad(this.getUTCMonth() + 1) + '-'
            + pad(this.getUTCDate()) + 'T'
            + pad(this.getUTCHours()) + ':'
            + pad(this.getUTCMinutes()) + ':'
            + pad(this.getUTCSeconds()) + 'Z';
    };
}