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
	"use strict";
	var $o = window.OmnomIRC = window.$o = function(){
			return 'Version: '+version;
		},
		event = function(msg,type){
			if(settings.debug){
				type=exists(type)?type:'event';
				switch(type){
					case 'ready':type='document_ready';break;
				}
				log('['+type.toUpperCase()+'] '+msg);
			}
		},
		emit = window.emit = function(type,data){
			if($o.chat.connected()){
				socket.emit.apply(socket,arguments);
			}else{
				if(tabs.length > 0){
					$o.msg('Disconnected, cannot do anything');
				}
			}
		},
		noop = function(){},
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
		settings = {},
		settingsConf = {},
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
					emit('names',{
						name: $o.ui.tabs.current().name
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
					emit('message',{
						from: 0,
						message: properties.nick+' '+ret,
						room: $o.ui.tabs.current().name
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
					if(exists(args[1])){
						var m = 'Commands:',i;
						for(i in commands){
							m += ' '+commands[i].cmd;
						}
						$o.msg(m);
					}else{
						var i,cmd;
						for(i in commands){
							cmd = commands[i];
							if(cmd.cmd == args[1] && exists(cmd.help)){
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
					$o.ui.tabs.current().clear();
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
					var tab = $o.ui.tabs.tab(data.room),
						users = tab.users,
						i;
					tab.users = data.names;
					if($o.ui.tabs.idForName(data.room) == selectedTab){
						$o.ui.render.users();
					}
					$(users).each(function(i,v){
						if(v != null){
							if(tab.users.indexOf(v.trim()) == -1){
								emit('echo',{
									room: data.room,
									message: v+' left the room',
									from: 0
								});
								runHook('part',[
									v,
									data.room
								]);
							}
						}
					});
					$(tab.users).each(function(i,v){
						if(v != null){
							if(users.indexOf(v.trim()) == -1){
								runHook('join',[
									v,
									data.room
								]);
							}
						}
					});
				}
			},
			{ // authorized
				on: 'authorized',
				fn: function(data){
					properties.nick = data.nick;
					for(var i in settings.autojoin){
						emit('join',{
							name: settings.autojoin[i]
						});
					}
					runHook('authorized');
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
					properties.connected = true;
					$o.chat.auth();
					runHook('reconnect');
					emit('echo',{
						room: $o.ui.tabs.current().name,
						from: 0,
						message: 'reconnected'
					});
				}
			},
			{ // connect
				on: 'connect',
				fn: function(data){
					event('connected');
					properties.connected = true;
					$o.chat.auth();
					runHook('connect');
					emit('echo',{
						room: $o.ui.tabs.current().name,
						from: 0,
						message: 'connected'
					});
				}
			},
			{ // disconnect
				on: 'disconnect',
				fn: function(data){
					event('disconnected');
					properties.connected = false;
					runHook('disconnected');
					$o.msg('* disconnected');
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
					runHook('message',[
						data.message,
						data.from,
						data.room
					]);
				}
			}
		],
		hooks = [
			{
				type: '',
				hook: 'setting',
				fn: function(name){
					return name != 'colour';
				}
			},
			{	// load - style
				type: 'style',
				hook: 'load',
				fn: function(){
					// STUB
					event('testing == '+testing,'debug');
				}
			}
		],
		currentPlugin = 0,
		runHook = function(name,args){
			var i,r=true,hook,fn,sandbox = {
				testing: 'test'
			};
			args=exists(args)?args:[];
			for(i in hooks){
				hook = hooks[i];
				if(hook.hook == name){
					fn = (hook.fn+'').replace(/\/\/.+?(?=\n|\r|$)|\/\*[\s\S]+?\*\//g,'').replace(/\"/g,'\\"').replace(/\n/g,'').replace(/\r/g,'');
					fn = 'var ret = true;eval("with(this){ret = ('+fn+').apply(this,arguments);}");return ret;';
					try{
						r = (new Function(fn)).apply(sandbox,args);
					}catch(e){
						event('Hook failed to run: '+e+"\nFunction that ran: "+fn,'hook_error');
					}
				}
				if(r == false){
					break;
				}
			}
			return r;
		},
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
		socket,$i,$s,$h,$cl,$c,$tl,hht;
	$.extend($o,{
		version: function(){
			return version;
		},
		register: {
			theme: function(name){
				if(-1==$.inArray(properties.themes,name)){
					properties.themes.push(name);
					runHook('theme',[name]);
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
					if(exists(help)){
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
			setting: function(name,type,val,validate,values,callback){
				if(!exists(settings[name])){
					validate = exists(validate)?validate:function(){};
					values = exists(values)?values:false;
					callback = exists(callback)?callback:function(){};
					settings[name] = val;
					settingsConf[name] = {
						validate: validate,
						callback: callback,
						type: type,
						values: values,
						default: val,
						name: name
					}
					return true;
				}else{
					return false;
				}
			},
			hook: function(event,fn){
				hooks.push({
					hook: event,
					fn: fn
				})
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
					if(settings.debug){
						$(frag).append(
							$('<li>')
								.addClass('row')
								.attr('id','console-log-controls')
								.append(
									$('<span>')
										.text('Debug Log')
										.addClass('cell')
								)
								.append(
									$('<span>')
										.addClass('cell')
										.append(
											$('<button>')
												.text('Show')
												.click(function(){
													$(this).text($('#console-log').is(':visible')?'Show':'Hide');
													$('#console-log').toggle();
													$('#console-log-clear').toggle();
													$('#content').toggle();
												})
										)
										.append(
											$('<button>')
												.text('Clear')
												.attr('id','console-log-clear')
												.hide()
												.click(function(){
													$('#console-log-pre').html('');
												})
										)
								)
						);
					}else{
						$('#console-log-pre').html('');
						$('#console-log').hide();
						$('#content').show();
					}
					$('#settings-list').html(frag);
				},
				users: function(){
					event('Rendering userlist');
					var $ul = $('#user-list').html(''),
						i,
						names = $o.ui.tabs.current().users;
					for(i in names){
						$ul.append(
							$('<li>').text(names[i])
						);
					}
				},
				tab: function(){
					$cl.html($($o.ui.tabs.current().body).clone());
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
							date: new Date(),
							send: function(msg){
								$o.chat.send(msg,$o.ui.tabs.tab(id).name);
							},
							close: function(){
								$o.ui.tabs.remove(id);
							},
							users: [],
							names: function(){
								emit('names',{
									name: $o.ui.tabs.tab(id).name
								});
							},
							select: function(){
								$o.ui.tabs.select(id);
							},
							clear: function(){
								$cl.html('');
								$o.ui.tabs.tab(id).body = document.createDocumentFragment();
								emit('echo',{
									room: $o.ui.tabs.tab(id).name,
									message: 'messages cleared',
									from: 0
								});
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
						if($o.ui.tabs.tab(id).name == name){
							event('Tab removed: '+$o.ui.tabs.tab(id).name);
							emit('part',{
								name: $o.ui.tabs.tab(id).name
							});
							tabs.splice(id,1);
							if(selectedTab==id&&selectedTab>0){
								selectedTab--;
							}
							break;
						}
					}
					$o.ui.render.tablist();
					$cl.html($o.ui.tabs.current().body);
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
					if(typeof id == 'string' && !id.isNumber()){
						id = $o.ui.tabs.idForName(id);
						if(!id) return false;
					}
					return exists(tabs[id])?tabs[id]:false;
				},
				dom: function(id){
					if(typeof id == 'string' && !id.isNumber()){
						id = $o.ui.tabs.idForName(id);
						if(!id) return false;
					}
					return typeof tabs[id] == 'undefined'?false:tabs[id].body;
				},
				obj: function(id){
					if(exists(id)){
						if(typeof id == 'string' && !id.isNumber()){
							id = $o.ui.tabs.idForName(id);
							if(!id) return;
						}
						return $('<div>')
							.addClass('tab')
							.text($o.ui.tabs.tab(id).name)
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
					if(typeof id == 'string' && !id.isNumber()){
						id = $o.ui.tabs.idForName(id);
						if(!id) return false;
					}
					event(id+' '+$o.ui.tabs.tab(id).name,'tab_select');
					if(id<tabs.length&&id>=0){
						selectedTab=id;
					}
					$tl.children('.clicked').removeClass('clicked');
					$($tl.children().get(id)).addClass('clicked');
					$('#title').text($o.ui.tabs.tab(id).name);
					$('#topic').text($o.ui.tabs.tab(id).topic);
					$cl.html($($o.ui.tabs.tab(id).body).clone());
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
				},
				current: function(){
					if(tabs.length > 0 && tabs.length > selectedTab){
						return tabs[selectedTab];
					}else{
						return {
							name: '',
							body: document.createDocumentFragment(),
							date: new Date(),
							send: noop,
							close: noop,
							users: [],
							names: noop,
							select: noop,
							clear: noop
						}
					}
				}
			},
		},
		chat: {
			connect: function(server){
				if($o.chat.connected()){
					$o.disconnect();
				}
				if(!exists(server)){
					server = settings.server;
				}
				socket = window.socket =  io.connect(server);
				for(var i in handles){
					socket.on(handles[i].on,handles[i].fn);
				}
				$o.chat.auth();
			},
			disconnect: function(){
				if($o.chat.connected()){
					socket.disconnect();
				}
			},
			connected: function(){
				return exists(socket)?properties.connected:false;
			},
			send: function(msg,room){
				if(!exists(room)){
					room = $o.ui.tabs.current().name;
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
						emit('message',{
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
				emit('auth',{
					nick: settings.nick
					// TODO - send authorization info
				});				
			}
		},
		get: function(name,formatted){
			if(!exists(formatted)){
				return exists(settings[name])?settings[name]:false;
			}else{
				if(exists(settingsConf[name]) && exists(settings[name])){
					var r = $.extend({},settingsConf[name]);
					r.val = settings[name];
					r.validate = undefined;
					r.callback = undefined;
					delete r['validate'];
					delete r['callback'];
					return r;
				}else{
					return false;
				}
			}
		},
		set: function(name,value,render){
			if(exists(settings[name])){
				var setting;
				if(exists(settingsConf[name])){
					setting = $.extend({},settingsConf[name]);
				}else{
					setting = {
						val: setting[name],
						callback: function(){},
						validate: function(){},
						values: undefined,
						type: typeof setting[name]
					}
				}
				if(setting.validate(setting[name],value,setting.values,name) == false){
					return false;
				}
				if(!runHook('setting',[
					name,
					settings[name],
					value,
					$o.get(name,true).values
				])){
					if(exists(render)){
						$o.ui.render.settings();
					}
					return false;
				}
				settings[name] = value;
				$.localStorage('settings',JSON.stringify(settings));
				setting.callback(value,name,exists(render));
				if(exists(render)){
					$o.ui.render.settings();
				}
				return true;
			}else{
				if(exists(render)){
					$o.ui.render.settings();
				}
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
			if(!exists(tabName) || tabName == $o.ui.tabs.current().name || !exists($o.ui.tabs.tab(tabName).body)){
				frag = document.createDocumentFragment();
			}else{
				frag = $o.ui.tabs.tab(tabName).body;
			}
			try{
				switch(typeof msg){
					case 'string':
						$(frag).append($('<li>').html(msg.htmlentities()));
						break;
					case 'object':
						if(!exists(msg.html)){
							$(frag).append($('<li>').html('&lt;'+msg.user+'&gt;&nbsp;'+msg.text.htmlentities()));
						}else{
							$(frag).append(msg.html);
						}
						break;
				}
			}catch(e){event('Failed to add message','error')}
			if(tabs.length > 0){
				$($o.ui.tabs.tab(tabName).body || $o.ui.tabs.current().body).append(frag);
			}
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
			if(!exists(tabName) || tabName == $o.ui.tabs.current().name){
				$o.ui.tabs.select(selectedTab);
			}
		},
		event: function(event_name,message){
			event(message,event_name);
		}
	});
	(function(settings){
		var i,s;
		for(i in settings){
			s = settings[i];
			$o.register.setting(i,s.type,s.val,s['validate'],s['values'],s['callback']);
		}
	})({
		colour: {
			type: 'boolean',
			val: false
		},
		debug: {
			type: 'boolean',
			val: false,
			callback: function(v,s,r){
				if(r){
					$o.ui.render.settings();
				}
			}
		},
		timestamp: {
			type: 'string',
			val: 'exact',
			callback: function(v,s){
				abbrDate('abbr.date');
				if(v == ''){
					$('.date_cell').css('visibility','hidden');
				}else{
					$('.date_cell').css('visibility','visible');
				}
			}
		},
		server: {
			type: 'string',
			val: location.origin
		},
		autoconnect: {
			type: 'boolean',
			val: true
		},
		autojoin: {
			type: 'array',
			val: [
				'#omnimaga',
				'#omnimaga-fr',
				'#irp'
			]
		},
		scrollspeed: {
			type: 'number',
			val: 100
		},
		theme: {
			type: 'select',
			val: 'default',
			values: properties.themes
		},
		nick: {
			type: 'string',
			val: 'User',
			callback: function(){
				$o.chat.auth();
			}
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
		event('Date '+new Date,'ready');
		$h.addClass('hovered');
		setTimeout(function(){
			$h.removeClass('hovered');
		},1000);
		$o.ui.render.settings();
		if(settings.autoconnect){
			$o.chat.connect();
		}
		// Check for script updates and update if required
		(function checkScripts(){
			for(var i in document.scripts){
				(function(el,src){
					if(exists(src) && el.innerHTML == ''){
						$.ajax(src,{
							success: function(source){
								if(exists($(el).data('source')) && $(el).data('source') != source){
									event('Reloading','update');
									location.reload();
								}
								$(el).data('source',source);
							},
							dataType: 'text'
						});
					}
				})(document.scripts[i],document.scripts[i].src);
			}
			setTimeout(checkScripts,1000);
		})();
	});
	window.io = null;
	runHook('load');
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
if(!String.prototype.isNumber){
	String.prototype.isNumber = function(){return /^\d+$/.test(this);}
}