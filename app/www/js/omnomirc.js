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
			return 'Version: '+version;
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
			timestamp: 'exact',
			server: location.origin,
			autoconnect: true,
			autojoin: [
				'#omnimaga',
				'#omnimaga-fr',
				'#irp'
			],
			scrollspeed: 100,
			theme: 'default',
			nick: 'User'
		},
		tabs = [],
		properties = {
			nick: 'User',
			sig: '',
			tabs: tabs,
			themes: [
				'default'
			]
		},
		commands = [
			{ // names
				cmd: 'names',
				fn: function(args){
					socket.emit('names',{
						name: tabs[selectedTab].name
					});
				}
			},
			{ // me
				cmd: 'me',
				help: 'Say something in third person',
				fn: function(args){
					var i,ret='';
					for(i=1;i<args.length;i++){
						ret += ' '+args[i];
					}
					socket.emit('message',{
						from: 0,
						message: properties.nick+' '+ret,
						room: tabs[selectedTab].name
					});
				}
			},
			{ // connect
				cmd: 'connect',
				fn: function(){
					if(!$o.chat.connected()){
						$o.chat.connect();
					}
				}
			},
			{ // disconnect
				cmd: 'disconnect',
				fn: function(){
					$o.disconnect();
				}
			},
			{ // nick
				cmd: 'nick',
				fn: function(args){
					$o.set('nick',args[1]);
				}
			},
			{ // help
				cmd: 'help',
				fn: function(args){
					if(typeof args[1] == 'undefined'){
						var m = 'Commands:',i;
						for(i in commands){
							m += ' '+commands[i].cmd;
						}
						$o.msg(m);
					}else{
						var i,cmd;
						for(i in commands){
							cmd = commands[i];
							if(cmd.cmd == args[1] && typeof cmd.help != 'undefined'){
								$o.msg('Command /'+cmd.cmd+': '+cmd.help);
								return;
							}
						}
						$o.send('/help');
					}
				}
			},
			{ // open
				cmd: 'open',
				fn: function(args){
					$o.ui.tabs.add(args[1]);
				}
			},
			{ // clear
				cmd: 'clear',
				fn: function(args){
					$cl.html('');
					tabs[selectedTab].body = document.createDocumentFragment();
					socket.emit('echo',{
						room: tabs[selectedTab].name,
						message: 'messages cleared',
						from: 0
					});
				}
			},
			{ // close
				cmd: 'close',
				fn: function(args){
					if(args.length > 1){
						$o.ui.tabs.remove(args[1]);
					}else{
						$o.ui.tabs.remove(selectedTab);
					}
				}
			},
			{ // tabs
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
			{ // names
				on: 'names',
				fn: function(data){
					var tab = tabs[$o.ui.tabs.idForName(data.room)],
						users = tab.users,
						i;
					tab.users = data.names;
					if($o.ui.tabs.idForName(data.room) == selectedTab){
						$o.ui.render.users();
					}
					$(users).each(function(i,v){
						if(-1 == $.inArray(tab.users,v) && v != null){
							socket.emit('echo',{
								room: tabs[selectedTab].name,
								message: v+' left the room',
								from: 0
							});
						}
					});
				}
			},
			{ // authorized
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
			{ // join
				on: 'join',
				fn: function(data){
					event('joined '+data.name);
					var flag = tabs.length == 0;
					$o.ui.tabs.add(data.name);
					if(flag){
						$o.ui.tabs.select(0);
					}
				}
			},
			{ // reconnect
				on: 'reconnect',
				fn: function(data){
					event('reconnected');
					$o.chat.auth();
				}
			},
			{ // message
				on: 'message',
				fn: function(data){
					event('recieved message');
					var date = new Date(),
						string,
						time = date.getTime(),
						child,
						i,
						msg = function(msg){
							string = '<span class="cell date_cell">[<abbr class="date date_'+time+'" title="'+date.toISOString()+'"></abbr>]</span>';
							child = $('<li>').html(string+'<span class="cell">'+
								msg
									.htmlentities()
									.replace(
										/(https?:\/\/(([-\w\.]+)+(:\d+)?(\/([\w/_\.]*(\?\S+)?)?)?))/g,
										"<a href=\"$1\" title=\"\">$1</a>"
									)
							+'</span>');
							$o.msg({html:child},data.room);
						};
					if(data.from != 0){
						msg('	<'+data.from+'>	'+data.message);
					}else{
						msg('	* '+data.message);
					}
					abbrDate('abbr.date_'+time);
					if(settings.timestamp == ''){
						$('.date_cell').css('visibility','hidden');
					}else{
						$('.date_cell').css('visibility','visible');
					}
					
				}
			}
		],
		hooks = [
			{
				type: 'style',
				hook: 'load',
				fn: function(){
					
				}
			}
		],
		version = '3.0',
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
							case 'm':text+=(date.getMinutes()>9?'':'0')+date.getMinutes();break;
							case 's':text+=(date.getSeconds()>9?'':'0')+date.getSeconds();break;
							case 't':text+=(date.getHours()>11)?'pm':'am';break;
							default:text+=timestamp[i];
						}
					}
					$(this).text(text);
				}).timeago('dispose');
			}
		},
		socket,$i,$s,$h,$cl,$tl,hht;
	$.extend($o,{
		version: function(){
			return version;
		},
		register: {
			theme: function(name){
				if(-1==$.inArray(properties.themes,name)){
					properties.themes.push(name);
					return true;
				}
				return false;
			},
			command: function(name,fn,help){
				if(-1==$.inArray(commands,name)){
					var o = {
						cmd: name,
						fn: fn
					};
					if(typeof help != 'undefined'){
						o.help = help;
					}
					commands.push(o);
					return true;
				}
				return false;
			},
			plugin: function(){
				// STUB
			},
			setting: function(name,defaultVal,validate,values){
				// STUB
			},
			hook: function(event,fn){
				
			}
		},
		hook: function(event,fn){
			$o.register.hook(event,fn);
		},
		ui: {
			render: {
				settings: function(){
					var name,setting,frag = document.createDocumentFragment(),item;
					for(name in settings){
						setting = $o.get(name,true);
						switch(setting.type){
							case 'select':
								item = $('<select>')
											.attr('id','setting_'+name)
											.change(function(){
												$o.set(this.id.substr(8),$(this).find(':selected').text(),false);
											});
								for(var i in setting.values){
									item.append(
										$('<option>')
											.text(setting.values[i])
									);
								}
								item.find(':contains('+setting.val+')').attr('selected','selected');
							break;
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
				users: function(){
					event('Rendering userlist');
					var $ul = $('#user-list').html(''),
						i,
						names = tabs[selectedTab].users;
					for(i in names){
						$ul.append(
							$('<li>').text(names[i])
						);
					}
				},
				tab: function(){
					$cl.html($(tabs[selectedTab].body).clone());
				},
				tablist: function(){
					$tl.html('');
					var i,tab;
					for(i in tabs){
						tab = $o.ui.tabs.obj(i);
						if(i==selectedTab){
							tab.addClass('clicked');
							$('#title').text(tabs[i].name);
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
			},
			tabs: {
				add: function(name){
					event('Tab added: '+name);
					if(!(function(){
						for(var i in tabs){
							if(name==tabs[i].name){
								return true;
							}
						}
						return false;
					})()){
						var scroll = $.localStorage('tabs'),
							i,
							frag = document.createDocumentFragment(),
							id = tabs.length;
						for(i in scroll){
							if(scroll[i].name == name){
								scroll[i].body = $(scroll[i].body).slice(-10);
								$(frag)
									.append(scroll[i].body)
									.append(
										$('<li>').html('<span class="to_remove">-- loaded old scrollback for '+scroll[i].date+' --</span>')
									)
									.children()
									.children('.remove')
									.remove();
								$(frag)
									.children()
									.children('.to_remove')
									.removeClass('to_remove')
									.addClass('remove');
								event('loading old tab scrollback for '+name+' last saved +'+scroll[i].date);
							}
						}
						tabs.push({
							name: name,
							body: frag,
							users: [],
							close: function(){
								$o.ui.tabs.remove(id);
							},
							select: function(){
								$o.ui.tabs.select(id);
							}
						});
						$tl.append($o.ui.tabs.obj(id));
						$o.ui.render.tablist();
						$o.ui.render.users();
					}else{
						event('Attempted to add an existing tab');
					}
				},
				remove: function(name){
					if(typeof name == 'number'){
						name = tabs[name].name;
					}
					for(var id=0;id<tabs.length;id++){
						if(tabs[id].name == name){
							event('Tab removed: '+tabs[id].name);
							socket.emit('part',{
								name: tabs[id].name
							});
							tabs.splice(id,1);
							if(selectedTab==id&&selectedTab>0){
								selectedTab--;
							}
							break;
						}
					}
					$o.ui.render.tablist();
					$cl.html(tabs[selectedTab].body);
					$o.ui.render.users();
				},
				selected: function(){
					return selectedTab;
				},
				idForName: function(name){
					for(var i in tabs){
						if(tabs[i].name == name){
							return i;
						}
					}
					return false;
				},
				tab: function(id){
					return typeof tabs[id] == 'undefined'?false:tabs[id];
				},
				dom: function(id){
					return typeof tabs[id] == 'undefined'?false:tabs[id].body;
				},
				obj: function(id){
					if(typeof id !== 'undefined'){
						return $('<div>')
							.addClass('tab')
							.text(tabs[id].name)
							.mouseup(function(e){
								switch(e.which){
									case 1:	// RMB
										if($(this).data('id')!=selectedTab){
											$o.ui.tabs.select($(this).data('id'));
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
										$o.ui.tabs.remove(id);
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
				select: function(id){
					event(id+' '+tabs[id].name,'tab_select');
					if(id<tabs.length&&id>=0){
						selectedTab=id;
					}
					$tl.children('.clicked').removeClass('clicked');
					$($tl.children().get(id)).addClass('clicked');
					$('#title').text(tabs[id].name);
					$('#topic').text(tabs[id].topic);
					$cl.html($(tabs[id].body).clone());
					abbrDate('abbr.date');
					$o.ui.render.users();
					setTimeout(function scrollContent(){
						if($c.scrollTop() < $c[0].scrollHeight){
							$c.scrollTop($c.scrollTop()+1);
							setTimeout(scrollContent,settings.scrollspeed);
						}else{
							event('scrolling stopped');
						}
					},settings.scrollspeed);
				}
			}
		},
		chat: {
			connect: function(server){
				if($o.chat.connected()){
					$o.disconnect();
				}
				if(typeof server == 'undefined'){
					server = settings.server;
				}
				socket = io.connect(server);
				for(var i in handles){
					socket.on(handles[i].on,handles[i].fn);
				}
				$o.chat.auth();
			},
			disconnect: function(){
				if($o.chat.connected()){
					socket.disconnect();
					socket = undefined;
				}
			},
			connected: function(){
				return typeof socket != 'undefined';
			},
			send: function(msg,room){
				if(typeof room == 'undefined'){
					room = tabs[selectedTab].name;
				}
				if(msg !== ''){
					if(msg[0] == '/' && msg[1] != '/'){
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
							room: room,
							from: properties.nick
						});
					}
				}
			},
			auth: function(){
				if(settings.nick == ''){
					$o.set('nick','User');
					return;
				}
				socket.emit('auth',{
					nick: settings.nick
					// TODO - send authorization info
				});				
			}
		},
		get: function(name,formatted){
			if(typeof formatted == 'undefined'){
				return exists(settings[name])?settings[name]:false;
			}else{
				var val = $o.get(name),
					type,
					values = false;
				switch(name){
					case 'theme':
						type = 'select';
						values = properties.themes;
						break;
					case 'autojoin':type = 'array';break;
					case 'timestamp':type = 'string';break;
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
					case 'timestamp':
						abbrDate('abbr.date');
						if(settings.timestamp == ''){
							$('.date_cell').css('visibility','hidden');
						}else{
							$('.date_cell').css('visibility','visible');
						}
					break;
					case 'nick':
						$o.chat.auth();
					break;
				}
				if(typeof render == 'undefined'){
					$o.ui.render.settings();
				}
				return true;
			}else{
				return false;
			}
		},
		prop: function(name){
			return exists(properties[name])?properties[name]:null;
		},
		send: function(msg){
			$o.chat.send(msg);
		},
		msg: function(msg,tabName){
			var frag;
			if(typeof tabName == 'undefined' || tabName == tabs[selectedTab].name){
				frag = document.createDocumentFragment();
			}else{
				frag = tabs[$o.ui.tabs.idForName(tabName)].body;
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
			$(tabs[$o.ui.tabs.idForName(tabName) || selectedTab].body).append(frag);
			var scroll = [],i,html;
			for(i in tabs){
				html = '';
				$(tabs[i].body).children().each(function(){
					html += this.outerHTML;
				});
				scroll.push({
					name: tabs[i].name,
					body: html,
					date: new Date().toString()
				});
			}
			$.localStorage('tabs',scroll);
			if(typeof tabName == 'undefined' || tabName == tabs[selectedTab].name){
				$o.ui.tabs.select(selectedTab);
			}
		},
		event: function(event_name,message){
			event(message,event_name);
		}
	});
	String.prototype.htmlentities = function(){
		return this
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/\n/g,'<br/>')
			.replace(/\t/g, '&nbsp;&nbsp;&nbsp;&nbsp;')
			.replace(/\s/g, '&nbsp;')
			.replace(/"/g, '&quot;');
	};
	$(document).ready(function(){
		$.extend(settings,$.parseJSON($.localStorage('settings')));
		$.localStorage('settings',JSON.stringify(settings));
		$i = $('#input');
		$s = $('#send');
		$cl = $('#content-list');
		$c = $('#content');
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
						$o.ui.tabs.add(prompt('Channel'));
					}
				},
				s1: '',
				close: {
					name: 'Close',
					icon: 'delete',
					callback: function(){
						$(this).contextMenu('hide');
						$o.ui.tabs.remove($(this).data('id'));
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
						$o.ui.tabs.add(prompt('channel'));
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
		$o.ui.render.settings();
		if(settings.autoconnect){
			$o.chat.connect();
		}
	});
	delete window.io;
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