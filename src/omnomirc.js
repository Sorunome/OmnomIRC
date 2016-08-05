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
function OmnomIRC(){
	var OMNOMIRCSERVER = 'https://omnomirc.omnimaga.org',
		CLASSPREFIX = '',
		$input = false,
		getURLParam = function(name){
			return document.URL.split(name+'=')[1]!==undefined?document.URL.split(name+'=')[1].split('&')[0].split('#')[0]:'';
		},
		eventOnMessage = function(line,loadMode){
			if(loadMode === undefined){
				loadMode = false;
			}
			if(line.name === null || line.network == -1 || (!loadMode && !request.isNew(line.curline))){
				request.setCurline(line.curline);
				return;
			}
			request.setCurline(line.curline);
			
			if(oirc.onmessageraw !== false){
				oirc.onmessageraw(line,loadMode);
			}else{
				parser.addLine(line,loadMode);
			}
			if(!loadMode && line.chan == ss.get('chan')){
				var lines = ss.get('lines');
				lines.push(line);
				if(lines.length > 200){
					lines.shift();
				}
				ss.set('lines',lines);
				
				if(['part','quit','join','nick'].indexOf(line.type) != -1){
					ss.set('users',users.getUsers());
				}
			}
			
		},
		settings = (function(){
			var self = {
				hostname:'',
				nick:'',
				signature:'',
				numHigh:4,
				uid:-1,
				net:'',
				networks:{},
				pmIdent:false,
				guestLevel:0,
				isGuest:true,
				identGuest:function(name,sig,remember,fn){
					if(fn === undefined){
						fn = remember;
						remember = false;
					}
					console.log('trying login');
					network.getJSON('misc.php?identName='+base64.encode(name)+'&nick='+base64.encode(name)+'&signature='+base64.encode(sig)+'&id=-1&network='+self.net,function(data){
						if(data.success){
							if(remember){
								ls.set('guestName',name);
								ls.set('guestSig',data.signature);
							}else{
								ls.set('guestName','');
								ls.set('guestSig','');
							}
							self.setIdent(name,data.signature,-1);
							request.identify();
						}
						if(fn!==undefined){
							fn(data);
						}
					},true,false);
				},
				logout:function(){
					self.setIdent('','',-1);
					ls.remove('guestName');
					ls.remove('guestSig');
					ss.remove('config');
					ss.remove('checkLogin');
					request.identify();
				},
				login:function(nick,sig,uid){
					if(uid === undefined){
						uid = -1;
					}
					self.setIdent(nick,sig,uid);
					request.identify();
				},
				fetch:function(fn,clOnly){
					var callback = function(data){
						if(!clOnly){
							try{
								sessionStorage.setItem('defaultNetwork',data.defaultNetwork);
							}catch(e){}
							ss.set('config',data);
							
							self.hostname = data.hostname;
							
							channels.setChans(data.channels);
							parser.setSmileys(data.smileys);
							oirc.onsmileychange(data.smileys);
							parser.setSpLinks(data.spLinks);
							
							self.guestLevel = data.guests;
							self.networks = {};
							$.each(data.networks,function(i,n){
								self.networks[n.id] = n;
							});
							self.net = data.network;
							ls.setPrefix(self.net);
							options.setDefaults(data.defaults);
							options.setExtraChanMsg(data.extraChanMsg);
							request.setData(data.websockets.use,data.websockets.host,data.websockets.port,data.websockets.ssl);
						}
						var clData = ss.get('checkLogin');
						if(clData && !clOnly){
							self.nick = clData.nick;
							self.signature = clData.signature;
							self.uid = clData.uid;
							self.pmIdent = '['+self.net.toString()+','+self.uid.toString()+']';
							self.isGuest = data.signature === '';
							var url_uid = getURLParam('uid');
							if(url_uid !== '' && url_uid != self.uid){ // if we dictate the UID then we better use that!
								ss.remove('config');
								ss.remove('checkLogin');
								self.fetch(fn,clOnly);
								return;
							}
							
							if(fn !== undefined){
								fn();
							}
							return;
						}
						if(self.loggedIn() && self.isGuest){
							self.identGuest(self.nick,function(data){
								if(data.success){
									self.signature = data.signature;
								}
								if(fn !== undefined){
									fn();
								}
							});
						}else{
							network.getJSON(data.checkLoginUrl+'&network='+self.net.toString()+'&jsoncallback=?',function(data){
								ss.set('checkLogin',{
									nick:data.nick,
									signature:data.signature,
									uid:data.uid
								});
								self.nick = data.nick;
								self.signature = data.signature;
								self.uid = data.uid;
								self.pmIdent = '['+self.net.toString()+','+self.uid.toString()+']';
								self.isGuest = data.signature === '';
								
								if(fn!==undefined){
									fn();
								}
							},true,false);
						}
					};
					if(clOnly===undefined){
						clOnly = false;
					}
					if(clOnly && self.loggedIn() && self.isGuest){
						console.log('i am silly');
						self.identGuest(self.nick,function(data){
							if(data.success){
								self.signature = data.signature;
							}
							if(fn !== undefined){
								fn();
							}
						})
						return;
					}
					if(!clOnly){
						var data = ss.get('config');
						if(data){
							callback(data);
							return;
						}
					}
					ss.determinePrefix();
					network.getJSON('config.php?js&network='+getURLParam('network')+(clOnly?'&clonly':''),callback,true,false);
				},
				setIdent:function(nick,sig,uid){
					self.nick = nick;
					self.signature = sig;
					self.uid = uid;
					ss.set('checkLogin',{
						nick:nick,
						signature:sig,
						uid:uid
					});
				},
				getUrlParams:function(){
					return 'nick='+base64.encode(self.nick)+'&signature='+base64.encode(self.signature)+'&time='+(+new Date()).toString()+'&id='+self.uid+'&network='+self.net+'&noLoginErrors';
				},
				getNetwork:function(i){
					if(self.networks[i]!==undefined){
						return self.networks[i];
					}
					return {
						id:-1,
						normal:'NICK',
						userlist:'NICK',
						name:'Invalid network',
						type:-1
					};
				},
				getIdentParams:function(){
					return {
						nick:self.nick,
						signature:self.signature,
						time:(+new Date()).toString(),
						id:self.uid,
						network:self.net
					};
				},
				loggedIn:function(){
					return self.signature !== '';
				},
				getWholePmIdent:function(uid,net){
					var otherhandler = '['+net.toString()+','+uid.toString()+']';
					if(net < self.net){
						return otherhandler+self.pmIdent;
					}else if(self.net < net){
						return self.pmIdent+otherhandler;
					}else if(uid < self.uid){
						return otherhandler+self.pmIdent;
					}else{
						return self.pmIdent+otherhandler;
					}
				}
			};
			return {
				identGuest:self.identGuest,
				fetch:self.fetch,
				getUrlParams:self.getUrlParams,
				getIdentParams:self.getIdentParams,
				getNetwork:self.getNetwork,
				nick:function(){
					return self.nick;
				},
				net:function(){
					return self.net;
				},
				loggedIn:self.loggedIn,
				guestLevel:function(){
					return self.guestLevel;
				},
				getPmIdent:function(){
					return self.pmIdent;
				},
				isGuest:function(){
					return self.isGuest;
				},
				getWholePmIdent:self.getWholePmIdent,
				logout:self.logout,
				login:self.login
			};
		})(),
		ls = (function(){
			var self = {
				prefix:getURLParam('network'),
				setPrefix:function(p){
					if(!p){
						self.prefix = '';
						return;
					}
					self.prefix = p.toString();
				},
				getCookie:function(c_name){
					var i,x,y,ARRcookies=document.cookie.split(";");
					for(i=0;i<ARRcookies.length;i++){
						x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
						y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
						x=x.replace(/^\s+|\s+$/g,"");
						if(x==c_name){
							return unescape(y);
						}
					}
				},
				setCookie:function(c_name,value,exdays){
					var exdate = new Date(),
						c_value = escape(value);
					exdate.setDate(exdate.getDate() + exdays);
					c_value += ((exdays===null) ? '' : '; expires='+exdate.toUTCString());
					document.cookie=c_name + '=' + c_value;
				},
				haveSupport:null,
				support:function(){
					if(self.haveSupport===null){
						try{
							localStorage.setItem('test',1);
							self.haveSupport = (localStorage.getItem('test') == 1);
							localStorage.removeItem('test');
							self.haveSupport = true;
						}catch(e){
							self.haveSupport = false;
						}
					}
					return self.haveSupport;
				},
				get:function(name){
					var s;
					name = self.prefix+name;
					if(self.support()){
						s = localStorage.getItem(name);
					}else{
						s = self.getCookie(name);
					}
					try{
						return JSON.parse(s);
					}catch(e){
						return undefined;
					}
				},
				set:function(name,value){
					name = self.prefix+name;
					value = JSON.stringify(value);
					if(self.support()){
						localStorage.setItem(name,value);
					}else{
						self.setCookie(name,value);
					}
				},
				remove:function(name,value){
					name = self.prefix+name;
					if(self.support()){
						localStorage.removeItem(name);
					}else{
						self.setCookie(name,'',-1);
					}
				}
			};
			return {
				setPrefix:self.setPrefix,
				get:self.get,
				set:self.set,
				remove:self.remove
			};
		})(),
		ss = (function(){
			var self = {
				prefix:getURLParam('network'),
				setPrefix:function(p){
					if(!p){
						return;
					}
					self.prefix = p.toString();
				},
				determinePrefix:function(){
					self.haveSupport = null;
				},
				haveSupport:null,
				support:function(){
					if(self.haveSupport===null){
						try{
							sessionStorage.setItem('test',1);
							self.haveSupport = (sessionStorage.getItem('test') == 1);
							sessionStorage.removeItem('test');
							if(self.haveSupport && self.prefix == ''){
								self.setPrefix(sessionStorage.getItem('defaultNetwork'));
							}
						}catch(e){
							self.haveSupport = false;
						}
					}
					return self.haveSupport;
				},
				get:function(name){
					if(self.support()){
						try{
							return JSON.parse(sessionStorage.getItem(self.prefix+name));
						}catch(e){
							return undefined;
						}
					}
					return undefined;
				},
				set:function(name,value){
					if(self.support()){
						sessionStorage.setItem(self.prefix+name,JSON.stringify(value));
					}
				},
				remove:function(name){
					if(self.support()){
						sessionStorage.removeItem(self.prefix+name);
					}
				}
			};
			return {
				determinePrefix:self.determinePrefix,
				get:self.get,
				set:self.set,
				remove:self.remove
			}
		})(),
		network = (function(){
			var self = {
				didRelog:false,
				removeSig:function(s){
					try{
						var parts = s.split('signature='),
							moreParts = parts[1].split('&');
						moreParts[0] = '---';
						parts[1] = moreParts.join('&');
						return parts.join('signature=');
					}catch(e){
						if(s.indexOf('signature')!==-1){
							return 'omited due to security reasons';
						}
						return s;
					}
				},
				addError:function(s,e){
					s = self.removeSig(s);
					oirc.onerror(s,e);
				},
				addWarning:function(s,e){
					s = self.removeSig(s);
					oirc.onwarning(s,e);
				},
				checkCallback:function(data,fn,recall,url){
					if(data.relog!=2){
						if(data.errors!==undefined){
							$.map(data.errors,function(e){
								if(e.type!==undefined){
									self.addError(url,e);
								}else{
									self.addError(url,{
										type:'misc',
										message:e
									});
								}
							});
						}
						if(data.warnings!==undefined){
							$.map(data.warnings,function(w){
								if(w.type!==undefined){
									self.addWarning(url,w);
								}else{
									self.addWarning(url,{
										type:'misc',
										message:w
									});
								}
							});
						}
					}
					if(data.relog!==undefined && data.relog!=0){
						if(data.relog==1){
							settings.fetch(undefined,true); // everything is still fine, no need to block the rest of the thread
							fn(data);
						}else if(data.relog==2 && !self.didRelog){
							self.didRelog = true;
							settings.fetch(function(){
								recall();
							},true);
						}else{ // that's it, we'r out
							fn(data);
						}
					}else{
						if(data.relog!==undefined){
							self.didRelog = false; // relog successfull, new try!
						}
						fn(data);
					}
				},
				getJSON:function(s,fn,async,urlparams){
					if(async==undefined){
						async = true;
					}
					if(urlparams==undefined){
						urlparams = true;
					}
					if(s.indexOf('?') == -1 && urlparams){
						s += '?';
					}
					var url = s+(urlparams?'&'+settings.getUrlParams():'');
					return $.ajax({
							url:url,
							dataType:'json',
							async:async
						})
						.done(function(data){
							self.checkCallback(data,fn,function(){
								self.getJSON(s,fn,async,urlparams);
							},url);
						});
				},
				post:function(s,pdata,fn,async,urlparams){
					if(async==undefined){
						async = true;
					}
					if(urlparams==undefined){
						urlparams = true;
					}
					if(s.indexOf('?') == -1 && urlparams){
						s += '?';
					}
					var url = s+(urlparams?'&'+settings.getUrlParams():'');
					return $.ajax({
							type:'POST',
							url:url,
							async:async,
							data:pdata
						})
						.done(function(data){
							self.checkCallback(data,fn,function(){
								self.post(s,pdata,fn,async,urlparams);
							},url);
						});
				}
			};
			return {
				getJSON:self.getJSON,
				post:self.post
			};
		})(),
		options = (function(){
			var self = {
				extraChanMsg:'',
				allOptions:{
					highBold:{
						disp:'Highlight Bold',
						default:true
					},
					highRed:{
						disp:'Highlight Red',
						default:true
					},
					colordNames:{
						disp:'Colored Names',
						default:0,
						handler:function(){
							return $('<td>')
								.attr('colspan',2)
								.css('border-right','none')
								.append($('<select>')
									.change(function(){
										self.set('colordNames',this.value);
									})
									.append(
										$.map(['none','calc','server'],function(v,i){
											return $('<option>')
												.attr((self.get('colordNames')==i?'selected':'false'),'selected')
												.val(i)
												.text(v);
										})
									)
								)
						}
					},
					curChan:{
						hidden:true,
						default:0
					},
					extraChans:{
						disp:'Show extra Channels',
						default:false,
						before:function(){
							if(self.extraChanMsg!==''){
								alert(self.extraChanMsg);
							}
							return true;
						}
					},
					ding:{
						disp:'Ding on Highlight',
						default:false
					},
					smileys:{
						disp:'Show Smileys',
						default:true
					},
					charsHigh:{
						disp:'Number chars for Highlighting',
						default:4,
						handler:function(){
							return $('<td>')
								.attr('colspan',2)
								.css('border-right','none')
								.append($('<select>')
									.change(function(){
										self.set('charsHigh',this.value);
									})
									.append(
										$.map([1,2,3,4,5,6,7,8,9,10],function(i){
											return $('<option>')
												.attr((self.get('charsHigh')==i?'selected':'false'),'selected')
												.val(i)
												.text(i);
										})
									)
								);
						}
					},
					oircJoinPart:{
						disp:'Show OmnomIRC join/part messages',
						default:false
					},
					textDeco:{
						disp:'Enable simple text decorations',
						default:false
					},
					statusBar:{
						disp:'Show Updates in Browser Status Bar',
						default:true
					},
					browserNotifications:{
						disp:'Browser Notifications',
						default:false,
						before:function(){
							notification.request();
							return false;
						}
					},
					wysiwyg:{
						disp:'Use WYSIWYG editor',
						default:false
					}
				},
				set:function(s,v){
					if(self.allOptions[s]===undefined){
						return;
					}
					self.allOptions[s].value = v;
					var opts = ls.get('options') || {};
					opts[s] = v;
					ls.set('options',opts);
				},
				get:function(s){
					if(self.allOptions[s]!==undefined){
						return self.allOptions[s].value;
					}
				},
				setExtraChanMsg:function(s){
					self.extraChanMsg = s;
				},
				setDefaults:function(def){
					var opts = ls.get('options');
					$.each(self.allOptions,function(i,v){
						var val = v.default;
						if(opts && opts[i]!==undefined){
							val = opts[i];
						}else if(def && def[i]!==undefined){
							val = def[i];
						}
						self.allOptions[i].value = val;
					});
				},
				getAll:function(raw){
					if(raw){
						return self.allOptions;
					}
					var a = {};
					$.each(self.allOptions,function(i,v){
						if(v.hidden===undefined || !v.hidden){
							a[i] = v.value;
						}
					});
					return a;
				},
				setAll:function(o){
					self.allOptions = o;
				}
			};
			return {
				set:self.set,
				get:self.get,
				setDefaults:self.setDefaults,
				setExtraChanMsg:self.setExtraChanMsg,
				getAll:self.getAll,
				setAll:self.setAll
			};
		})(),
		instant = (function(){
			var self = {
				id:Math.random().toString(36)+(new Date()).getTime().toString(),
				update:function(){
					ls.set('browserTab',self.id);
					ls.set('newInstant',false);
				},
				init:function(){
					ls.set('browserTab',self.id);
					$(window).focus(function(){
						self.update();
					}).unload(function(){
						self.kill();
					});
				},
				current:function(){
					if(ls.get('newInstant')){
						self.update();
					}
					return self.id == ls.get('browserTab');
				},
				kill:function(){
					ls.set('newInstant',true);
					$(window).off('focus').off('unload');
				}
			};
			return {
				init:self.init,
				current:self.current,
				kill:self.kill
			};
		}()),
		request = (function(){
			var self = {
				ws:{
					socket:false,
					connected:false,
					use:true,
					sendBuffer:[],
					allowLines:false,
					enabled:false,
					host:'',
					port:0,
					ssl:true,
					tryFallback:true,
					didRelog:false,
					fallback:function(){
						console.log('trying fallback...');
						if(self.ws.tryFallback){
							try{
								self.ws.tryFallback = false;
								self.ws.socket.close();
							}catch(e){}
							
							self.ws.use = false;
							self.old.lastSuccess = (new Date).getTime();
							self.old.start();
						}
					},
					identify:function(){
						self.ws.send($.extend({action:'ident'},settings.getIdentParams()));
						self.ws.send({action:'charsHigh','chars':options.get('charsHigh')});
					},
					init:function(){
						if(!("WebSocket" in window) || !self.ws.enabled){
							self.ws.use = false;
							return false;
						}
						
						try{
							var path = window.location.pathname.split('/');
							path.pop();
							if(path.length > 1 && path[path.length-1] === ''){
								path.pop();
							}
							path.push('ws');
							path.push(settings.net());
							self.ws.socket = new PooledWebSocket((self.ws.ssl?'wss://':'ws://')+self.ws.host+':'+self.ws.port.toString()+path.join('/'));
						}catch(e){
							console.log(self.ws.socket);
							console.log((self.ws.ssl?'wss://':'ws://')+self.ws.host+':'+self.ws.port.toString()+path.join('/'));
							console.log(e);
							self.ws.fallback();
							return;
						}
						self.ws.socket.onopen = function(e){
							self.ws.connected = true;
							for(var i = 0;i < self.ws.sendBuffer.length;i++){
								self.ws.send(self.ws.sendBuffer[i]);
							}
							self.ws.sendBuffer = [];
						};
						self.ws.socket.onmessage = function(e){
							try{
								var data = JSON.parse(e.data);
								console.log(data);
								if(self.ws.allowLines){
									if(data.line !== undefined){
										if(eventOnMessage(data.line)){
											self.ws.tryFallback = false;
											delete self.ws.socket;
										}
									}
									if(data.users !== undefined){
										users.setUsers(data.users);
									}
								}
								
								if(data.relog!==undefined && data.relog!=0 && data.relog < 3){
									if(!self.ws.didRelog){
										self.ws.didRelog = true;
										settings.fetch(function(){
											if(settings.loggedIn()){
												self.ws.identify();
												self.ws.didRelog = false;
											}
										},true);
									}
								}
							}catch(e){};
						};
						self.ws.socket.onclose = function(e){
							console.log('CLOOOOOOOOOSE');
							console.log(e);
							self.ws.use = false;
							self.ws.fallback();
						};
						self.ws.socket.onerror = function(e){
							console.log('ERRRORRRR');
							console.log(e);
							delete self.ws.socket;
							self.ws.use = false;
							self.ws.fallback();
						};
						
						self.ws.identify();
						
						$(window).on('beforeunload',function(){
							self.partChan(channels.current().handler);
							delete self.ws.socket;
						});
						
						return true;
					},
					send:function(msg){
						if(self.ws.connected){
							self.ws.socket.send(JSON.stringify(msg));
						}else{
							self.ws.sendBuffer.push(msg);
						}
					}
				},
				old:{
					inRequest:false,
					handler:false,
					lastSuccess:(new Date).getTime(),
					fnCallback:undefined,
					chan:'',
					sendRequest:function(){
						if(!self.old.chan){
							return;
						}
						self.old.handler = network.getJSON(
								'Update.php?high='+
								options.get('charsHigh').toString()+
								'&channel='+self.old.chan+
								'&lineNum='+self.curline.toString(),
							function(data){
								self.old.handler = false;
								self.old.lastSuccess = (new Date).getTime();
								if(data.lines!==undefined){
									$.map(data.lines,function(line){
										eventOnMessage(line);
									});
								}
								if(data.errors!=undefined && data.errors.length >= 1){
									return;
								}
								if(data.banned !== true){
									self.old.setTimer();
								}
							})
							.fail(function(){
								self.old.handler = false;
								if(self.fnCallback!=undefined){
									self.fnCallback();
									self.fnCallback = undefined;
									return;
								}
								if((new Date).getTime() >= self.old.lastSuccess + 300000){
									send.internal('<span style="color:#C73232;">OmnomIRC has lost connection to server. Please refresh to reconnect.</span>');
								}else if(!self.old.inRequest){
									self.old.lastSuccess = (new Date).getTime();
								}else{
									self.old.setTimer();
								}
							});
					},
					setTimer:function(){
						if(self.old.inRequest && channels.current().loaded && self.old.handler===false){
							setTimeout(function(){
								self.old.sendRequest();
							},page.isBlurred()?2500:200);
						}else{
							self.old.stop();
						}
					},
					start:function(){
						if(!self.old.inRequest){
							self.old.inRequest = true;
							self.old.setTimer();
						}
					},
					stop:function(fn){
						if(self.old.inRequest){
							self.old.inRequest = false;
							if(self.old.handler){
								self.fnCallback = fn;
								self.old.handler.abort();
							}else if(fn!==undefined){
								fn();
							}
						}else if(fn!==undefined){
							fn();
						}
					},
					send:function(s,chan,fn){
						network.getJSON('message.php?message='+base64.encode(s)+'&channel='+chan,function(data){
							if(fn!==undefined){
								fn(data);
							}
						});
					}
				},
				curline:0,
				joinChan:function(c){
					if(self.ws.use){
						self.ws.send({
							action:'joinchan',
							chan:c
						});
					}
					
					if(parseInt(c,10) != c){
						c = base64.encode(c);
					}
					self.old.chan = c;
				},
				partChan:function(c){
					if(self.ws.use){
						self.ws.send({
							action:'partchan',
							chan:c
						});
					}
				},
				stop:function(fn){
					if(self.ws.use){
						self.ws.allowLines = false;
						fn();
					}else{
						self.old.stop(fn);
					}
				},
				kill:function(){
					if(self.ws.use){
						self.ws.socket.onclose = function(){}; // ignore the close event
						self.ws.socket.close();
					}else{
						self.old.stop();
					}
				},
				start:function(){
					if(self.ws.use){
						self.ws.allowLines = true;
					}else{
						self.old.start();
					}
				},
				setCurline:function(c){
					if(c > self.curline){
						self.curline = c;
					}
				},
				send:function(s,chan,fn){
					if(self.ws.use){
						self.ws.send({
							action:'message',
							channel:chan,
							message:s
						});
						if(fn!==undefined){
							fn();
						}
					}else{
						if(parseInt(chan,10)!=chan){
							chan = base64.encode(chan);
						}
						self.old.send(s,chan,fn);
					}
				},
				setData:function(enabled,host,port,ssl){
					self.ws.enabled = enabled;
					self.ws.host = host;
					self.ws.port = port;
					self.ws.ssl = ssl;
				},
				init:function(){
					if(self.ws.enabled){
						self.ws.init();
					}else{
						self.ws.use = false;
						self.old.lastSuccess = (new Date).getTime();
					}
				},
				identify:function(){
					if(self.ws.enabled){
						self.ws.identify();
					}
				},
				postfetch:function(){
					if(self.ws.use){
						self.ws.send({
							action:'postfetch',
							channel:channels.current().handler,
							curline:self.curline
						});
					}
				},
				isNew:function(cid){
					return cid == 0 || cid > self.curline;
				}
			};
			return {
				joinChan:self.joinChan,
				partChan:self.partChan,
				start:self.start,
				stop:self.stop,
				kill:self.kill,
				setCurline:self.setCurline,
				send:self.send,
				setData:self.setData,
				init:self.init,
				identify:self.identify,
				postfetch:self.postfetch,
				isNew:self.isNew
			};
		})(),
		channels = (function(){
			var Channel = function(i){
				var exists = self.chans[i]!==undefined,
					_self = {
						i:i,
						name:exists?self.chans[i].chan.toLowerCase():'',
						handler:exists?self.getHandler(i):-1,
						handlerB64:exists?self.getHandler(i,true):-1,
						loaded:false,
						load:function(data){
							if(data.lines === undefined){ // something went wrong....
								if(data.message){
									send.internal(data.message);
								}else{
									send.internal('<span style="color:#C73232;"><b>ERROR:</b> couldn\'t join channel</span>');
								}
								return false;
							}
							options.set('curChan',i);
							if(data.banned){
								send.internal('<span style="color:#C73232;"><b>ERROR:</b> banned</span>');
								return false;
							}
							
							users.setUsers(data.users);
							
							$.each(data.lines,function(i,line){
								eventOnMessage(line,true);
							});
							_self.loaded = true;
							return true;
						},
						unload:function(){
							if(_self.loaded){
								request.partChan(_self.handler);
							}
						},
						part:function(){
							self.part(self.i);
						},
						reloadUserlist:function(){
							if(!exists){
								return;
							}
							network.getJSON('Load.php?userlist&channel='+_self.handlerB64,function(data){
								if(!data.banned){
									if(data.users){
										users.setUsers(data.users);
									}
								}else{
									send.internal('<span style="color:#C73232;"><b>ERROR:</b> banned</span>');
								}
							})
						},
						reload:function(){
							oirc.onchanneljoin(_self.name);
						},
						setI:function(i){
							_self.i = i;
						},
						is:function(i){
							return i == _self.i;
						}
					};
				return {
					name:_self.name,
					handler:_self.handler,
					handlerB64:_self.handlerB64,
					load:_self.load,
					part:_self.part,
					reload:_self.reload,
					unload:_self.unload,
					loaded:function(){
						return _self.loaded;
					},
					setI:_self.setI,
					is:_self.is,
					reloadUserlist:_self.reloadUserlist
				};
			},
			self = {
				current:false,
				chans:[],
				save:function(){
					ls.set('channels',self.chans);
				},
				loadSettings:function(){
					try{
						var chanList = ls.get('channels'),
							exChans = $.map(self.chans,function(ch){
								if((ch.ex && options.get('extraChans')) || !ch.ex){
									return ch;
								}
								return undefined;
							}),
							exChansInUse = [];
						if(chanList!==null && chanList!=[]){
							self.chans = $.merge(
									$.map(chanList,function(v){
										if(v.id != -1 && v.id.toString().substr(0,1)!='*'){
											var valid = false;
											$.each(self.chans,function(i,vc){
												if(vc.id == v.id){
													exChansInUse.push(v);
													valid = true;
													v.chan = vc.chan;
													return false;
												}
											});
											if(!valid){
												return undefined;
											}
										}
										return v;
									}),
									$.map(exChans,function(v){
										var oldChan = false;
										$.each(exChansInUse,function(i,vc){
											if(vc.id == v.id){
												oldChan = true;
												v.chan = vc.chan;
												return false
											}
										});
										if(oldChan){
											return undefined;
										}
										return v;
									})
								);
							save();
						}
					}catch(e){}
					oirc.onchannelchange(self.chans);
				},
				init:function(){
					self.current = Channel(-1);
					self.loadSettings();
				},
				addChan:function(s,chanid){
					var addChan = true;
					if(chanid === undefined){
						chanid = -1;
					}
					s = s.toLowerCase();
					$.each(self.chans,function(i,c){
						if(c.chan==s){
							addChan = i;
						}
					});
					if(addChan===true){
						if(s[0] == '#'){
							send.internal('<span style="color:#C73232;">Join Error: Cannot join new channels starting with #.</span>');
							return -1;
						}
						self.chans.push({
							chan:s,
							high:false,
							ex:false,
							id:chanid,
							order:-1
						});
						self.save();
						oirc.onchannelchange(self.chans);
						return self.chans.length-1;
					}
					return addChan;
				},
				getHandler:function(i,b64){
					if(self.chans[i].id!=-1){
						if((typeof self.chans[i].id)!='number' && b64){
							return base64.encode(self.chans[i].id);
						}
						return self.chans[i].id.toString();
					}
					if(b64){
						return base64.encode(self.chans[i].chan);
					}
					return self.chans[i].chan;
				},
				requestHandler:false,
				load:function(i,fn){
					var callback = function(data){
						if(self.current.load(data)){
							self.chans[i].high = false;
							self.save();
							request.joinChan(self.getHandler(i));
							request.start();
							tab.load();
							oldMessages.read();
							request.postfetch();
							fn(true,data);
							return;
						}
						fn(false,{});
					};
					if(fn === undefined){
						fn = function(){};
					}
					if(self.chans[i] === undefined){
						fn(false,{});
						return;
					}
					request.stop(function(){
						if(self.requestHandler!==false){
							self.requestHandler.abort();
						}
						if(ss.get('chan') == self.getHandler(i)){
							options.set('curChan',i);
							self.current = Channel(i);
							var data = {},
								attrs = ['chan','banned','admin','users','ignores','lines'];
							for(var j = 0; j < attrs.length; j++){
								data[attrs[j]] = ss.get(attrs[j]);
							}
							callback(data);
							return;
						}
						self.requestHandler = network.getJSON('Load.php?count=125&channel='+self.getHandler(i,true),function(data){
							options.set('curChan',i);
							self.current = Channel(i);
							ss.set('chan',self.current.handler); // because session storage is per-tab and options is tab-synced
							ss.set('banned',data.banned);
							ss.set('admin',data.admin)
							ss.set('users',data.users);
							ss.set('ignores',data.ignores);
							ss.set('lines',data.lines);
							callback(data);
						});
					});
				},
				join:function(s){
					var addChan = true;
					s = s.trim();
					if(!s){
						return -1;
					}
					if(s[0] != '@' && s[0] != '#'){
						s = '@' + s;
					}
					// s will now be either prefixed with # or with @
					return self.addChan(s);
				},
				joinPm:function(s,n,fn){
					if(fn === undefined){
						fn = function(){};
					}
					var addChan = true,
						callback = function(nick,id){
							nick = nick.trim();
							if(nick.substr(0,1)!='*'){
								nick = '*'+s;
							}
							id = id.trim();
							if(id.substr(0,1)!='*'){
								id = '*'+id;
							}
							// s will now be prefixed with *
							fn(self.addChan(nick,id));
						}
					if(n == ''){
						if(s.substr(0,1)=='@' || s.substr(0,1)=='#'){
							send.internal('<span style="color:#C73232;">Query Error: Cannot query a channel. Use /join instead.</span>');
							fn(-1);
							return;
						}
						network.getJSON('misc.php?openpm='+base64.encode(s),function(data){
							if(data.channick){
								callback(data.channick,data.chanid);
							}else{
								send.internal('<span style="color:#C73232;">Query Error: User not found.</span>');
								fn(-1);
							}
						});
					}else{
						callback(s,n);
					}
				},
				part:function(i){
					if(parseInt(i,10) == i){ // we convert it to a number so that we don't have to deal with it
						i = parseInt(i,10);
					}
					if((typeof i)!='number'){
						// a string was passed, we need to get the correct i
						if(i && i[0] != '@' && i[0] != '#' && i[0] != '*'){
							i = '@'+i;
						}
						$.each(self.chans,function(ci,c){
							if(c.chan == i){
								i = ci;
								return false; // break the loop
							}
						});
					}
					if((typeof i)!='number' || self.chans[i] === undefined){ // we aren#t in the channel
						send.internal('<span style="color:#C73232;"> Part Error: can\'t part '+i+'. (You are not in it.)</span>');
						return false;
					}
					if(self.chans[i].chan[0]=='#'){
						send.internal('<span style="color:#C73232;"> Part Error: can\'t part '+self.chans[i].chan+'. (IRC channel.)</span>');
						return false;
					}
					var chanId = self.chans[i].chanId,
						selected = false;
					if(self.current.is(i)){
						selected = true;
					}
					if(chanId == -1){
						chanId = self.chans[i];
					}
					request.partChan(self.getHandler(i));
					self.chans.splice(i,1);
					self.save();
					oirc.onchannelchange(self.chans);
					if(selected){
						oirc.onchanneljoin(self.chans[i-1].chan);
					}
					return true;
				},
				getList:function(){
					return self.chans;
				},
				setChans:function(c){
					self.chans = c;
					if(self.current){
						self.save();
						oirc.onchannelchange(self.chans);
					}
				},
				getNames:function(){
					return $.map(self.chans,function(c){
						return c.chan;
					});
				},
				highlight:function(c){
					$.each(self.chans,function(i,ci){
						if(c == ci.id || ci.chan.toLowerCase()==c.toString().toLowerCase()){
							self.chans[i].high = true;
							return false; // break the loop!
						}
					});
					self.save();
					oirc.onchannelchange(self.chans);
				}
			};
			return {
				init:self.init,
				load:self.load,
				join:self.join,
				joinPm:self.joinPm,
				part:self.part,
				getList:self.getList,
				getHandler:self.getHandler,
				current:function(){
					return self.current;
				},
				setChans:self.setChans,
				getNames:self.getNames,
				highlight:self.highlight
			};
		})(),
		users = (function(){
			var self = {
				users:[],
				draw:function(){
					self.users.sort(function(a,b){
						var al = a.nick.toLowerCase(),
							bl = b.nick.toLowerCase();
						return al==bl?(a==b?0:a<b?-1:1):al<bl?-1:1;
					});
					oirc.onuserchange(self.users);
				},
				exists:function(e){
					var result = false;
					$.each(self.users,function(i,us){
						if(us.nick.toLowerCase() == u.nick.toLowerCase() && us.network == u.network){
							result = true;
							return false;
						}
					});
					return result;
				},
				add:function(u){
					if(channels.current().handler!==''){
						var add = true;
						$.each(self.users,function(i,us){
							if(us.nick == u.nick && us.network == u.network){
								add = false;
								return false;
							}
						});
						if(add){
							self.users.push(u);
							self.draw();
						}
					}
				},
				remove:function(u){
					if(channels.current().handler!==''){
						$.each(self.users,function(i,us){
							if(us.nick == u.nick && us.network == u.network){
								self.users.splice(i,1);
								return false;
							}
						});
						self.draw();
					}
				},
				setUsers:function(u){
					self.users = u;
					ss.set('users',self.users);
					self.draw();
				},
				getNames:function(){
					return $.map(self.users,function(u){
						return u.nick;
					});
				}
			};
			return {
				add:self.add,
				remove:self.remove,
				setUsers:self.setUsers,
				getNames:self.getNames,
				getUsers:function(u){
					return self.users;
				}
			};
		})(),
		commands = (function(){
			var self = {
				parse:function(s){
					var command = s.split(' ')[0].toLowerCase(),
						parameters = s.substr(command.length+1).toLowerCase().trim();
					switch(command){
						case 'j':
						case 'join':
							if(settings.isGuest()){
								send.internal('<span style="color:#C73232;"><b>ERROR:</b> can\'t join as guest</span>');
							}else{
								oirc.onchanneljoin(parameters);
							}
							return true;
						case 'q':
						case 'query':
							if(settings.isGuest()){
								send.internal('<span style="color:#C73232;"><b>ERROR:</b> can\'t query as guest</span>');
							}else{
								oirc.onchanneljoin('*'+parameters);
							}
							return true;
						case 'win':
						case 'w':
						case 'window':
							var c = channels.getList()[parseInt(parameters,10)];
							if(c!==undefined){
								oirc.onchanneljoin(c.chan);
							}
							return true;
						case 'p':
						case 'part':
							if(parameters !== ''){
								oirc.onchannelpart(parameters);
							}else{
								oirc.onchannelpart(channels.current().name);
							}
							return true;
						case 'help':
							send.internal('<span style="color:#2A8C2A;">Commands: me, ignore, unignore, ignorelist, join, part, query, msg, window</span>');
							send.internal('<span style="color:#2A8C2A;">For full help go here: <a href="http://ourl.ca/19926" target="_top">http://ourl.ca/19926</a></span>');
							return true;
						case 'ponies':
							$.getScript('https://juju2143.ca/mousefly.js',function(){
								Derpy();
							});
							return true;
						case 'minty':
							$.getJSON(OMNOMIRCSERVER+'/minty.php').done(function(data){
								send.internal('<span style="font-size:5px;line-height:0;font-family:monospace;">'+data.minty+'</span>');
							});
							return true;
						default:
							return false;
					}
				}
			};
			return {
				parse:self.parse
			};
		})(),
		send = (function(){
			var self = {
				sending:false,
				send:function(fn,s,chan){
					if(typeof fn === 'string'){
						s = fn;
						fn = undefined;
					}
					if(fn === undefined){
						fn = function(){};
					}
					if(s === undefined){
						s = send.val();
					}
					if(!s){
						fn();
						return;
					}
					oldMessages.add(s);
					if(s !== '' && options.get('textDeco')){
						if(s[0] == '>'){
							s = '\x033'+s;
						}
						s = s.replace(/((^|\s)\*[^\*]+\*($|\s))/g,'\x02$1\x02')
								.replace(/((^|\s)\/[^\/]+\/($|\s))/g,'\x1d$1\x1d')
								.replace(/((^|\s)_[^_]+_($|\s))/g,'\x1f$1\x1f');
					}
					if(chan === undefined){
						chan = channels.current().handler;
					}
					if(s[0] == '/' && commands.parse(s.substr(1))){
						fn();
					}else{
						if(!self.sending){
							self.sending = true;
							request.send(s,chan,function(data){
								if(data !== undefined && data.lines !== undefined){
									$.map(data.lines,function(l){
										eventOnMessage(l);
									})
								}
								self.sending = false;
								fn();
							});
						}
					}
				},
				internal:function(s){
					eventOnMessage({
						curline:0,
						type:'internal',
						time:Math.floor((new Date()).getTime()/1000),
						name:'',
						message:s,
						name2:'',
						chan:channels.current().handler
					});
				},
				val:function(s){
					if(s===undefined){
						if(oirc.ongetval===false){
							if($input.is('input')){
								return $input.val();
							}
							return $input.text();
						}
						return oirc.ongetval();
					}
					if(oirc.onsetval===false){
						if($input.is('input')){
							$input.val(s);
						}else{
							$input.text(s);
						}
					}else{
						oirc.onsetval(s);
					}
					
				}
			};
			return {
				internal:self.internal,
				send:self.send,
				val:self.val
			};
		})(),
		tab = (function(){
			var self = {
				tabWord:'',
				tabCount:0,
				isInTab:false,
				startPos:0,
				startChar:'',
				endPos:0,
				endChar:'',
				endPos0:0,
				tabAppendStr:'',
				searchArray:[],
				node:false,
				wysiwyg:false,
				getCurrentWord:function(){
					var messageVal = (!self.wysiwyg?$input[0].value:(self.node = window.getSelection().anchorNode).nodeValue);
					if(self.isInTab){
						return self.tabWord;
					}
					self.startPos = (!self.wysiwyg?$input[0].selectionStart:window.getSelection().anchorOffset);
					self.startChar = messageVal.charAt(self.startPos);
					while(self.startChar != ' ' && --self.startPos > 0){
						self.startChar = messageVal.charAt(self.startPos);
					}
					if(self.startChar == ' '){
						self.startPos++;
					}
					self.endPos = (!self.wysiwyg?$input[0].selectionStart:window.getSelection().anchorOffset);
					self.endChar = messageVal.charAt(self.endPos);
					while(self.endChar != ' ' && ++self.endPos <= messageVal.length){
						self.endChar = messageVal.charAt(self.endPos);
					}
					self.endPos0 = self.endPos;
					self.tabWord = messageVal.substr(self.startPos,self.endPos - self.startPos).trim();
					return self.tabWord;
				},
				getTabComplete:function(){
					var messageVal = (!self.wysiwyg?$input[0].value:self.node.nodeValue),
						name;
					if(messageVal === null){
						return;
					}
					name = self.search(self.getCurrentWord(),self.tabCount);
					if(!self.isInTab){
						self.tabAppendStr = ' ';
						if(self.startPos===0){
							self.tabAppendStr = ': ';
						}
					}
					if(name == self.getCurrentWord()){
						self.tabCount = 0;
						name = self.search(self.getCurrentWord(),self.tabCount);
					}
					messageVal = messageVal.substr(0,self.startPos)+name+self.tabAppendStr+messageVal.substr(self.endPos+1);
					if(!self.wysiwyg){
						$input[0].value = messageVal;
					}else{
						window.getSelection().anchorNode.nodeValue = messageVal;
						window.getSelection().getRangeAt(0).setEnd(self.node,self.startPos+name.length+self.tabAppendStr.length);
						window.getSelection().getRangeAt(0).setStart(self.node,self.startPos+name.length+self.tabAppendStr.length);
					}
					self.endPos = self.endPos0+name.length+self.tabAppendStr.length;
				},
				search:function(start,startAt){
					var res = false;
					if(!startAt){
						startAt = 0;
					}
					$.each(self.searchArray,function(i,u){
						if(u.toLowerCase().indexOf(start.toLowerCase()) === 0 && startAt-- <= 0 && res === false){
							res = u;
						}
					});
					if(res!==false){
						return res;
					}
					return start;
				},
				init:function(){
					self.wysiwyg = !$input.is('input');
					$input.keydown(function(e){
						if(e.keyCode == 9){
							if(!e.ctrlKey){
								e.preventDefault();
								
								self.getTabComplete();
								self.isInTab = true;
								self.tabCount++;
								setTimeout(1,1);
							}
						}else{
							self.tabWord = '';
							self.tabCount = 0;
							self.isInTab = false;
						}
					});
				},
				load:function(){
					self.searchArray = $.merge(users.getNames(),channels.getNames());
				}
			};
			return {
				init:self.init,
				load:self.load
			};
		})(),
		oldMessages = (function(){
			var self = {
				messages:[],
				counter:0,
				current:'',
				init:function(){
					$input.keydown(function(e){
						if(e.keyCode==38 || e.keyCode==40){
							e.preventDefault();
							if(self.counter==self.messages.length){
								self.current = send.val();
							}
							if(self.messages.length!==0){
								if(e.keyCode==38){ //up
									if(self.counter!==0){
										self.counter--;
									}
									send.val(self.messages[self.counter]);
								}else{ //down
									if(self.counter!=self.messages.length){
										self.counter++;
									}
									if(self.counter==self.messages.length){
										send.val(self.current);
									}else{
										send.val(self.messages[self.counter]);
									}
								}
							}
						}
					});
				},
				add:function(s){
					self.messages.push(s);
					if(self.messages.length>20){
						self.messages.shift();
					}
					self.counter = self.messages.length;
					oirc.ls.set('oldMessages-'+oirc.channels.current().handlerB64,self.messages);
				},
				read:function(){
					self.messages = oirc.ls.get('oldMessages-'+oirc.channels.current().handlerB64);
					if(!self.messages){
						self.messages = [];
					}
					console.log(self.messages);
					self.counter = self.messages.length;
				}
			};
			return {
				init:self.init,
				add:self.add,
				read:self.read
			};
		})(),
		wysiwyg = (function(){
			var self = {
				menuOpen:false,
				msgVal:'',
				$textDecoForm:false,
				hideMenu:function(){
					self.$textDecoForm.css('display','none');
					self.menuOpen = false;
				},
				reverseParse:function(s){
					if(s.indexOf('<') === -1){ // no tags, nothing to do!
						return $('<span>').html(s).text();
					}
					var bold = false,
						italic = false,
						underline = false,
						fg = '-1',
						bg = '-1';
					s = s.replace(/<img([^>]*?)>/gi,function(match,args){
						var m = args.match(/data-code="([^"]*)"/);
						if(m){
							return m[1];
						}
						return '';
					});
					s = s.replace(/<span([^>]*?)>([^<]*?)<\/span>/gi,function(match,args,str){
						if(!args){
							return str;
						}
						
						s = '';
						if((args.indexOf('bold')==-1) == bold){
							bold = !bold;
							s += '\x02';
						}
						if((args.indexOf('italic')==-1) == italic){
							italic = !italic;
							s += '\x1d';
						}
						if((args.indexOf('underline')==-1) == underline){
							underline = !underline;
							s += '\x1f';
						}
						var dfg = args.match(/fg-(\d+)/),
							dbg = args.match(/bg-(\d+)/);
						if(!dfg){
							dfg = '-1';
						}
						if(!dbg){
							dbg = '-1';
						}
						
						if(dbg != bg || dfg != fg){
							if((fg!='-1' && dfg == '-1') || (bg!='-1' && dbg == '-1')){
								s = '\x0f';
								if(bold){
									s += '\x02';
								}
								if(italic){
									s += '\x1d';
								}
								if(underline){
									s += '\x1f';
								}
							}
							s += '\x03';
							fg = dfg;
							if(fg != '-1'){
								s += fg;
							}
							bg = dbg;
							if(bg != '-1'){
								s += ','+bg;
							}
						}
						
						return s+str;
					});
					return $('<span>').html(s).text();
				},
				getCaretCharacterOffsetWithin:function(elem){
					var sel = window.getSelection();
					if(sel.rangeCount > 0){
						var range = sel.getRangeAt(0),
							preCaretRange = range.cloneRange(),
							extra = 0,
							nodeRange = document.createRange(),
							nodes;
						preCaretRange.selectNodeContents(elem);
						preCaretRange.setEnd(range.endContainer,range.endOffset);
						nodes = preCaretRange.commonAncestorContainer.getElementsByTagName('img');
						for(var i = 0;i < nodes.length;i++){
							var node = nodes[i];
							if(node.dataset.code){
								nodeRange.selectNode(node);
								if(preCaretRange.compareBoundaryPoints(Range.START_TO_START,nodeRange) != 1 && preCaretRange.compareBoundaryPoints(Range.END_TO_END,nodeRange) != -1){
									extra += node.dataset.code.length;
								}
							}
						}
						return preCaretRange.toString().length + extra;
					}
					return 0;
				},
				getWholeSelection:function(elem){
					var sel = window.getSelection(),
						start,
						range = sel.getRangeAt(0),
						range_copy = range.cloneRange(),
						end = self.getCaretCharacterOffsetWithin(elem);
					range.collapse(true);
					sel.removeAllRanges();
					sel.addRange(range);
					start = self.getCaretCharacterOffsetWithin(elem);
					sel.removeAllRanges();
					sel.addRange(range_copy);
					return [start,end];
				},
				setCaretCharacterOffsetWithin:function(el,pos,length,range,haveStart){
					if(length === undefined){
						length = 0;
					}
					if(range === undefined){
						range = document.createRange();
					}
					if(haveStart === undefined){
						haveStart = false;
					}
					// Loop through all child nodes
					for(var i = 0;i < el.childNodes.length;i++){
						var node = el.childNodes[i];
						if(node.nodeType == 3){ // we have a text node
							if(node.length >= pos){
								// finally add our range
								var sel = window.getSelection();
								if(!haveStart){
									range.setStart(node,pos);
									haveStart = true;
									pos += length;
								}
								if(haveStart && (node.length >= pos)){ // outside as if length is same we just do our stuff
									range.setEnd(node,pos);
									sel.removeAllRanges();
									sel.addRange(range);
									return -1; // we are done
								}
							}
							pos -= node.length;
						}else if(node.nodeType == 1){ // we might have an image!
							if(node.tagName == 'IMG' && node.dataset.code){
								if(node.dataset.code.length >= pos){
									// finally add our range
									var sel = window.getSelection();
									if(!haveStart){
										range.setStartAfter(node);
										haveStart = true;
										pos += length;
									}
									if(haveStart && (node.dataset.code.length >= pos)){ // outside as if length is same we just do our stuff
										range.setEndAfter(node);
										sel.removeAllRanges();
										sel.addRange(range);
										return -1; // we are done
									}
								}
								pos -= node.dataset.code.length;
							}else{
								pos = self.setCaretCharacterOffsetWithin(node,pos,length,range,haveStart);
								if(pos < 0){
									return -1; // no need to finish the for loop
								}
							}
						}else{
							pos = self.setCaretCharacterOffsetWithin(node,pos,length,range,haveStart);
							if(pos < 0){
								return -1; // no need to finish the for loop
							}
						}
					}
					return pos; // needed because of recursion stuff
				},
				surroundSelection:function(before,after){
					var sel = window.getSelection();
					if(sel.rangeCount > 0){
						var range = sel.getRangeAt(0),
							startNode = range.startContainer,
							startOffset = range.startOffset,
							startTextNode = document.createTextNode(before),
							endTextNode = document.createTextNode(after),
							boundaryRange = range.cloneRange();
						boundaryRange.collapse(false);
						boundaryRange.insertNode(endTextNode);
						boundaryRange.setStart(startNode,startOffset);
						boundaryRange.collapse(true);
						boundaryRange.insertNode(startTextNode);
						
						range.setStartAfter(startTextNode);
						range.setEndBefore(endTextNode);
						sel.removeAllRanges();
						sel.addRange(range);
					}
				},
				updateContent:function(start,end){
					if(start === undefined){
						start = end = self.getCaretCharacterOffsetWithin($input[0]);
					}
					
					self.msgVal = self.reverseParse($input.html());
					$input.html(parser.parse(self.msgVal));
					var range = document.createRange();
					
					self.setCaretCharacterOffsetWithin($input[0],start,end-start);
				},
				init:function(){
					if(!self.support()){
						return;
					}
					var id = $input.attr('id'),
						$newElem = $('<span>').attr({
							contenteditable:'true',
							accesskey:'i',
							id:id
						}).css({
							display:'inline-block',
							height:'1em',
							padding:'0.3em 0.2em 0.2em 0.3em',
							fontSize:'1.2em',
							verticalAlign:'top',
							whiteSpace:'nowrap',
							backgroundColor:$input.css('backgroundColor')
						});
					for(var i in ['Top','Bottom','Left','Right']){
						for(var j in ['Style','Width','Color']){
							$newElem.css('border'+i+j,$input.css('border'+i+j));
						}
					}
					$input.replaceWith($newElem);
					$input = $newElem;
					
					self.$textDecoForm = $('<div>').css({
						position:'absolute',
						bottom:'3em'
					}).attr('id',CLASSPREFIX+'textDecoForm').append(
						$('<button>').css('font-weight','bold').text('B').click(function(e){
							e.preventDefault();
							var pos = self.getWholeSelection($input[0]);
							self.surroundSelection('\x02','\x02');
							self.updateContent(pos[0],pos[1]);
						}),'&nbsp;',
						$('<button>').css('font-style','italic').text('I').click(function(e){
							e.preventDefault();
							var pos = self.getWholeSelection($input[0]);
							self.surroundSelection('\x1d','\x1d');
							self.updateContent(pos[0],pos[1]);
						}),'&nbsp;',
						$('<button>').css('text-decoration','underline').text('U').click(function(e){
							e.preventDefault();
							var pos = self.getWholeSelection($input[0]);
							self.surroundSelection('\x1f','\x1f');
							self.updateContent(pos[0],pos[1]);
						}),'<br>',
						$.map([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15],function(i){
							return [(i%4 == 0)?'<br>':'',$('<span>').css({
								display:'inline-block',
								height:'1em',
								width:'2em'
							}).addClass(CLASSPREFIX+'colorbutton').addClass(CLASSPREFIX+'bg-'+i).click(function(e){
								e.preventDefault();
								var pos = self.getWholeSelection($input[0]);
								self.surroundSelection('\x03'+i,'\x0f');
								self.updateContent(pos[0],pos[1]);
							})];
						})
					).hide().insertBefore($input);
					
					oirc.onsetval = function(s){
						self.msgVal = s;
						$input.html(parser.parse(s));
					};
					oirc.ongetval = function(){
						return self.msgVal;
					}
					$input.on('input',function(e){
						self.updateContent(); // we need the first argument to be undefined
					});
					$input.mouseup(function(e){
						var sel = window.getSelection();
						if(sel.isCollapsed){
							return;
						}
						e.preventDefault();
						self.$textDecoForm.show().css('left',Math.max(e.pageX-52,0));
						self.menuOpen = true;
						
						
					});
					$(document).mousedown(function(e){
						if(!$(e.target).closest(self.$textDecoForm).length && self.menuOpen){
							self.hideMenu();
						}
					});
					
					
				},
				support:function(){
					return (('contentEditable' in document.documentElement) && options.get('wysiwyg'));
				}
			};
			return {
				init:self.init,
				getMsg:self.getMsg,
				support:self.support,
				updateContent:self.updateContent
			}
		})(),
		statusBar = (function(){
			var self = {
				text:'',
				started:false,
				start:function(){
					if(!options.get('statusBar')){
						self.started = true; // no need to performthe check 9001 times
						return;
					}
					if(!self.started){
						setInterval(function(){
							window.status = self.text;
							if(parent){
								try{
									parent.window.status = self.text;
								}catch(e){}
							}
						},500);
						self.started = true;
					}
				},
				set:function(s){
					self.text = s;
					if(!self.started){
						self.start();
					}
				}
			};
			return {
				set:self.set
			};
		})(),
		notification = (function(){
			var self = {
				support_webkit:window.webkitNotifications!==undefined && window.webkitNotifications!==null && window.webkitNotifications,
				support:function(){
					return typeof Notification!='undefined' && Notification && Notification.permission!='denied';
				},
				show:function(s){
					var n;
					if(self.support_webkit){
						n = window.webkitNotifications.createNotification('omni.png','OmnomIRC Highlight',s);
						n.show();
					}else if(self.support()){
						n = new Notification('OmnomIRC Highlight',{
							icon:'omni.png',
							body:s
						});
						n.onshow = function(){
							setTimeout(n.close,30000);
						};
					}
				},
				request:function(){
					if(self.support_webkit){
						window.webkitNotifications.requestPermission(function(){
							if (window.webkitNotifications.checkPermission() === 0){
								self.show('Notifications Enabled!');
								oirc.options.set('browserNotifications',true);
								document.location.reload();
							}
						});
					}else if(self.support()){
						Notification.requestPermission(function(status){
							if (Notification.permission !== status){
								Notification.permission = status;
							}
							if(status==='granted'){
								self.show('Notifications Enabled!');
								oirc.options.set('browserNotifications',true);
								document.location.reload();
							}
						});
					}else{
						alert('Your browser doesn\'t support notifications');
					}
				},
				sound:false,
				make:function(s,c){
					var cur = instant.current();
					if(cur){
						if(options.get('browserNotifications')){
							self.show(s);
						}
						if(options.get('ding') && self.sound){
							self.sound.play();
						}
					}
					self.startFlash();
					oirc.onnotification(s,c,cur);
				},
				doFlash:false,
				intervalHandler:false,
				originalTitle:'',
				target:top.postMessage?top:(top.document.postMessage?top.document:undefined),
				startFlash:function(){
					if(self.doFlash){
						return;
					}
					self.doFlash = true;
					if(top == window){ // no firame, we have to do the flashing manually
						var alternator = false;
						self.originalTitle = document.title;
						self.intervalHandler = setInterval(function(){
							document.title = (alternator?'[ @] ':'[@ ] ')+self.originalTitle;
							alternator = !alternator;
						},500);
					}else{ // let the forum mod do the flashing!
						if(self.target!==undefined){
							self.target.postMessage('startFlash','*');
						}
					}
				},
				stopFlash:function(noTransmit){
					if(noTransmit === undefined){
						noTransmit = false;
					}
					if(self.doFlash){
						if(top == window){ // no firame, we have to do the flashing manually
							if(self.intervalHandler){
								clearInterval(self.intervalHandler);
								self.intervalHandler = false;
								document.title = self.originalTitle;
							}
						}else{ // let the forum mod do the flashing!
							if(self.target!==undefined){
								self.target.postMessage('stopFlash','*');
							}
						}
						self.doFlash = false;
						if(!noTransmit){
							oirc.ls.set('stopFlash',Math.random().toString(36)+(new Date()).getTime().toString());
						}
					}
				},
				init:function(){
					$(window).on('storage',function(e){
						if(e.originalEvent.key == settings.net()+'stopFlash'){
							self.stopFlash(true);
						}
					}).unload(function(){
						self.stopFlash(true); // don't message the other tabs as they might still be open
					}).focus(function(){
						self.stopFlash();
					});
					if(!self.sound){
						self.sound = new Audio('beep.mp3');
					}
				}
			};
			return {
				request:self.request,
				make:self.make,
				init:self.init
			};
		})(),
		parser = (function(){
			var self = {
				smileys_a:[],
				cacheServerNicks:{},
				spLinks:[],
				name:function(n,o,uid){
					if(uid === undefined){
						uid = -1;
					}
					n = (n=='\x00'?'':n); //fix 0-string bug
					var ne = encodeURIComponent(n);
					n = $('<span>').text(n).html();
					var rcolors = [19,20,22,24,25,26,27,28,29],
						sum = 0,
						i = 0,
						cn = n,
						net = settings.getNetwork(o),
						addLink = true;
					switch(options.get('colordNames')){
						case '1': // calc
							while(n[i]){
								sum += n.charCodeAt(i++);
							}
							cn = $('<span>').append($('<span>').addClass(CLASSPREFIX+'uName-'+rcolors[sum % 9].toString()).html(n)).html();
							break;
						case '2': //server
							if(net!==undefined && net.checkLogin!==undefined && uid!=-1){
								cn = ss.get('nick'+o.toString()+':'+uid.toString());
								if(!cn){
									addLink = false;
									if(self.cacheServerNicks[o.toString()+':'+uid.toString()]===undefined){
										network.getJSON(net.checkLogin+'?c='+uid.toString(10)+'&n='+ne,function(data){
											self.cacheServerNicks[o.toString()+':'+uid.toString()] = data.nick;
										},false,false);
									}
									cn = self.cacheServerNicks[o.toString()+':'+uid.toString()];
									ss.set('nick'+o.toString()+':'+uid.toString(),cn);
								}
							}else{
								cn = n;
							}
							break;
						default: // none
							cn = n;
							break;
					}
					if(net!==undefined && addLink){
						cn = net.normal.split('NICKENCODE').join(ne).split('NICK').join(cn).split('USERID').join(uid.toString(10));
					}
					if(net!==undefined){
						return '<span title="'+net.name+'">'+cn+'</span>';
					}
					return '<span title="Unknown Network">'+cn+'</span>';
				},
				smileys:function(s){
					if(!s){
						return '';
					}
					if(/^[\w\s\d\.,!?]*$/.test(s)){
						return s;
					}
					$.each(self.smileys_a,function(i,smiley){
						s = s.replace(RegExp(smiley.regex,'g'),smiley.replace);
					});
					return s;
				},
				links:function(text){
					if (!text || text === null || text === undefined){
						return '';
					}
					var ier = "[^\\s\x01\x04<\"]"; // url end regex
					text = text.replace(RegExp("(\x01|\x04)","g"),"");
					$.map(self.spLinks,function(url){
						url = url.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
						// we have > in that regex as it markes the end of <span>
						text = text.replace(RegExp("(^|[\\s>])((?:(?:f|ht)tps?:\/\/(?:www\\.)?)"+url+ier+"*)","g"),'$1\x01$2')
									.replace(RegExp("(^|[\\s>])("+url+ier+"*)","g"),'$1\x04$2');
					});
					return text.replace(RegExp("(^|[^a-zA-Z0-9_\x01\x04\"])((?:(?:f|ht)tps?:\/\/)"+ier+"+)","g"),'$1<a target="_blank" href="$2">$2</a>')
							.replace(RegExp("(^|[^a-zA-Z0-9_\x01\x04/])(www\\."+ier+"+)","g"),'$1<a target="_blank" href="http://$2">$2</a>')
							.replace(RegExp("(^|.)\x01("+ier+"+)","g"),'$1<a target="_top" href="$2">$2</a>')
							.replace(RegExp("(^|.)\x04("+ier+"+)","g"),'$1<a target="_top" href="http://$2">$2</a>');
				},
				colors:function(colorStr){
					var arrayResults = [],
						s,
						textDecoration = {
							fg:'-1',
							bg:'-1',
							underline:false,
							bold:false,
							italic:false
						},
						i,didChange;
					if(!colorStr){
						return '';
					}
					arrayResults = colorStr.split(RegExp('([\x02\x03\x0f\x16\x1d\x1f])'));
					colorStr='<span>';
					for(i=0;i<arrayResults.length;i++){
						didChange = true;
						switch(arrayResults[i]){
							case '\x03': // color
								s = arrayResults[i+1].replace(/^([0-9]{1,2}),([0-9]{1,2})(.*)/,'$1:$2');
								if(s == arrayResults[i+1]){ // we didn't change background
									s = arrayResults[i+1].replace(/^([0-9]{1,2}).*/,'$1:');
									if(s != arrayResults[i+1]){
										textDecoration.fg = s.split(':')[0];
										arrayResults[i+1] = arrayResults[i+1].substr(s.length-1); // -1 due to added colon
									}else{
										textDecoration.fg = '-1';
										textDecoration.bg = '-1';
									}
								}else{ // we also changed background
									textDecoration.fg = s.split(':')[0];
									textDecoration.bg = s.split(':')[1];
									if(s == arrayResults[i+1]){
										arrayResults[i+1] = '';
									}else{
										arrayResults[i+1] = arrayResults[i+1].substr(s.length);
									}
								}
								break;
							case '\x02': // bold
								textDecoration.bold = !textDecoration.bold;
								break;
							case '\x1d': // italic
								textDecoration.italic = !textDecoration.italic;
								break;
							case '\x16': // swap fg and bg
								s = textDecoration.fg;
								textDecoration.fg = textDecoration.bg;
								textDecoration.bg = s;
								if(textDecoration.fg=='-1'){
									textDecoration.fg = '0';
								}
								if(textDecoration.bg=='-1'){
									textDecoration.bg = '1';
								}
								break;
							case '\x1f': // underline
								textDecoration.underline = !textDecoration.underline;
								break;
							case '\x0f': // reset
								textDecoration = {
									fg:'-1',
									bg:'-1',
									underline:false,
									bold:false,
									italic:false
								}
								break;
							default:
								didChange = false;
						}
						if(didChange){
							colorStr += '</span>'+
									'<span class="'+CLASSPREFIX+'fg-'+textDecoration.fg+' '+CLASSPREFIX+'bg-'+textDecoration.bg+'" style="'+(textDecoration.bold?'font-weight:bold;':'')+(textDecoration.underline?'text-decoration:underline;':'')+(textDecoration.italic?'font-style:italic;':'')+'">';
						}else{
							colorStr+=arrayResults[i];
						}
					}
					colorStr += '</span>';
					// Strip codes
					colorStr = colorStr.replace(/(\x03|\x02|\x1F|\x09|\x0F)/g,'');
					return colorStr;
				},
				highlight:function(s){
					var style = '';
					if(!options.get('highRed')){
						style += 'background:none;padding:none;border:none;';
					}
					if(options.get('highBold')){
						style += 'font-weight:bold;';
					}
					return '<span class="highlight" style="'+style+'">'+s+'</span>';
				},
				parse:function(s,noSmileys){
					if(noSmileys==undefined || !noSmileys){
						noSmileys = false;
					}
					s = (s=="\x00"?'':s); //fix 0-string bug
					s = $('<span>').text(s).html(); // html escape
					if(options.get('smileys') && noSmileys===false){
						s = self.smileys(s);
					}
					s = self.colors(s);
					s = self.links(s);
					return s;
				},
				setSmileys:function(sm){
					self.smileys_a = [];
					$.each(sm,function(i,s){
						self.smileys_a.push({
							regex:s.regex,
							replace:s.replace.split('ADDSTUFF').join('data-code="'+s.code+'"').split('PIC').join(s.pic).split('ALT').join(s.alt),
						});
					});
				},
				setSpLinks:function(a){
					self.spLinks = a;
				},
				parseTextDecorations:function(s){
					if(s !== '' && options.get('textDeco')){
						if(s[0] == '>'){
							s = '\x033'+s;
						}
						s = s.replace(/((^|\s)\*[^\*]+\*($|\s))/g,'\x02$1\x02')
								.replace(/((^|\s)\/[^\/]+\/($|\s))/g,'\x1d$1\x1d')
								.replace(/((^|\s)_[^_]+_($|\s))/g,'\x1f$1\x1f');
					}
					return s;
				},
				addLine:function(line,loadMode){
					if(line.type!='highlight' && line.chan.toString().toLowerCase()!=channels.current().handler.toLowerCase() && line.name2 !== settings.getPmIdent()){
						return;
					}
					var name = self.name(line.name,line.network,line.uid),
						message = self.parse(line.message),
						tdName = '*',
						tdMessage = message;
					if(settings.nick() != '' && (['message','action','pm','pmaction'].indexOf(line.type)>=0) && line.name.toLowerCase() != '*' && message.toLowerCase().indexOf(settings.nick().toLowerCase().substr(0,options.get('charsHigh'))) >= 0){
						tdMessage = message = parser.highlight(message);
						if(!loadMode && page.isBlurred()){
							notification.make('('+channels.current().name+') <'+line.name+'> '+line.message,line.chan);
						}
					}
					switch(line.type){
						case 'reload':
							if(!loadMode){
								channels.current().reload();
							}
							return;
						case 'reload_userlist':
							if(!loadMode){
								channels.current().reloadUserlist();
							}
							return;
						case 'relog':
							if(!loadMode){
								settings.fetch(undefined,true);
							}
							return;
						case 'refresh':
							if(!loadMode){
								location.reload(true);
							}
							return;
						case 'join':
							tdMessage = [name,' has joined '+channels.current().name];
							if(!loadMode){
								users.add({
									nick:line.name,
									network:line.network
								});
							}
							if(settings.getNetwork(line.network).type==1 && !options.get('oircJoinPart')){
								return;
							}
							break;
						case 'part':
							tdMessage = [name,' has left '+channels.current().name+' (',message,')'];
							if(!loadMode){
								users.remove({
									nick:line.name,
									network:line.network
								});
							}
							if(settings.getNetwork(line.network).type==1 && !options.get('oircJoinPart')){
								return;
							}
							break;
						case 'quit':
							tdMessage = [name,' has quit IRC (',message,')'];
							if(!loadMode){
								users.remove({
									nick:line.name,
									network:line.network
								});
							}
							if(settings.getNetwork(line.network).type==1 && !options.get('oircJoinPart')){
								return;
							}
							break;
						case 'kick':
							tdMessage = [name,' has kicked ',parser.name(line.name2,line.network),' from '+channels.current().name+' (',message,')'];
							if(!loadMode){
								users.remove({
									nick:line.name2,
									network:line.network
								});
							}
							break;
						case 'message':
							tdName = name;
							break;
						case 'action':
							tdMessage = [name,' ',message];
							break;
						case 'mode':
							if(typeof(message)=='string'){
								message = line.message.split(' ');
								$.each(message,function(i,v){
									var n = $('<span>').html(v).text();
									if(n.indexOf('+')==-1 && n.indexOf('-')==-1){
										message[i] = parser.name(v,line.network);
									}
								});
								message = message.join(' ');
							}
							tdMessage = [name,' set '+channels.current().name+' mode ',message];
							break;
						case 'nick':
							tdMessage = [name,' has changed nicks to ',parser.name(line.name2,line.network)];
							if(!loadMode){
								users.add({
									nick:line.name2,
									network:line.network
								});
								users.remove({
									nick:line.name,
									network:line.network
								});
							}
							break;
						case 'topic':
							oirc.ontopicchange(parser.parse(line.message,true));
							
							if(line.name ==='' && line.network === 0 && loadMode){
								return;
							}
							tdMessage = [name,' has changed the topic to ',parser.parse(line.message,true)];
							break;
						case 'pm':
							if(line.chan==channels.current().handler){
								tdName = name;
								line.type = 'message';
							}else{
								if(!loadMode){
									if(line.name.toLowerCase() == settings.nick().toLowerCase()){
										channels.joinPm(line.chan,settings.getWholePmIdent(line.uid,line.network));
										return;
									}else{
										tdName = ['(PM)',name];
										channels.joinPm(line.name,settings.getWholePmIdent(line.uid,line.network));
										notification.make('(PM) <'+line.name+'> '+line.message,line.chan);
									}
								}else{
									return;
								}
							}
							break;
						case 'pmaction':
							if(line.chan == channels.current().handler){
								tdMessage = [name,' ',message];
								line.type = 'action';
							}else{
								if(!loadMode){
									if(line.name.toLowerCase() == settings.nick().toLowerCase()){
										channels.joinPm(line.chan,settings.getWholePmIdent(line.uid,line.network));
										return;
									}else{
										tdMessage = ['(PM)',name,' ',message];
										channels.joinPm(line.name,settings.getWholePmIdent(line.uid,line.network));
										notofication.make('* (PM)'+line.name+' '+line.message,line.chan);
										line.type = 'pm';
									}
								}else{
									return;
								}
							}
							break;
						case 'highlight':
							if(line.name.toLowerCase() != '*'){
								notification.make('('+line.chan+') <'+line.name+'> '+line.message,line.chan);
							}
							return;
						case 'internal':
							tdMessage = line.message; // override html escaping
							break;
						case 'server':
							break;
						default:
							return;
					}
					oirc.onmessage(tdName,tdMessage,line,loadMode);
				}
			};
			return {
				setSmileys:self.setSmileys,
				setSpLinks:self.setSpLinks,
				parse:self.parse,
				parseTextDecorations:self.parseTextDecorations,
				name:self.name,
				highlight:self.highlight,
				addLine:self.addLine
			};
		})(),
		error = (function(){
			var self = {
				$errors:false,
				$warnings:false,
				errors:[],
				warnings:[],
				$errorsPopup:false,
				$warningsPopup:false,
				getSinglePopupEntry:function(e){
					return $('<div>')
						.css('border-bottom','1px solid black')
						.append(
							'Time: ',
							(new Date(e.time)).toLocaleTimeString(),
							'<br>File: ',
							$('<span>').text(e.file).html(),
							$.map(e.content,function(val,i){
								return ['<br>',$('<span>').text(i).html(),': ',$('<span>').text(val).html()];
							})
						);
				},
				makePopup:function(type,data,fn){
					return $('<div>')
						.addClass('errorPopup')
						.addClass(type.toLowerCase())
						.append(
							$('<a>')
								.text('Close')
								.click(function(e){
									e.preventDefault();
									$(this).parent().remove();
									fn();
								}),
							'&nbsp;',
							$('<b>')
								.text(type),
							$('<div>')
								.addClass('errorPopupCont')
								.append(
									$.map(data,function(e){
										return self.getSinglePopupEntry(e);
									})
								)
						)
						.appendTo('body');
				},
				init:function($e,$w){
					if($e === undefined){
						$e = $('#errors');
					}
					if($w === undefined){
						$w = $('#warnings');
					}
					$e.click(function(){
						if(!self.$errorsPopup){
							self.$errorsPopup = self.makePopup('Errors',self.errors,function(){
								self.$errorsPopup = false;
							});
						}
					});
					$w.click(function(){
						if(!self.$warningsPopup){
							self.$warningsPopup = self.makePopup('Warnings',self.warnings,function(){
								self.$warningsPopup = false;
							});
						}
					});
					self.$errors = $e;
					self.$warnings = $w;
				},
				addError:function(s,e){
					self.errors.push({
						time:(new Date().getTime()),
						file:s,
						content:e
					});
					self.$errors.css('display','').find('.count').text(self.errors.length);
					if(self.$errorsPopup){
						self.$errorsPopup.children('div').append(self.getSinglePopupEntry(self.errors[self.errors.length - 1]));
					}
				},
				addWarning:function(s,e){
					self.warnings.push({
						time:(new Date().getTime()),
						file:s,
						content:e
					});
					self.$warnings.css('display','').find('.count').text(self.warnings.length)
					if(self.$warningsPopup){
						self.$warningsPopup.children('div').append(self.getSinglePopupEntry(self.warnings[self.warnings.length - 1]));
					}
				}
			};
			return {
				addError:self.addError,
				addWarning:self.addWarning,
				init:self.init
			};
		})(),
		page = (function(){
			var self = {
				isBlurred:true,
				init:function(){
					$(window).blur(function(){
						self.isBlurred = true;
					}).focus(function(){
						self.isBlurred = false;
					});
				},
				changeLinks:function(){
					// change links to add network
					$('#adminLink a,a[href="."],a[href="?options"],a[href="index.php"]').each(function(){
						if($(this).attr('href').split('?')[1] !== undefined){
							$(this).attr('href',$(this).attr('href')+'&network='+oirc.settings.net());
						}else{
							$(this).attr('href',$(this).attr('href')+'?network='+oirc.settings.net());
						}
					});
				}
			};
			return {
				init:self.init,
				changeLinks:self.changeLinks,
				isBlurred:function(){
					return self.isBlurred;
				}
			};
		})(),
		logs = (function(){
			var self = {
				isOpen:false,
				open:function(fn){
					if(self.isOpen){
						return;
					}
					self.isOpen = true;
					request.stop(fn);
				},
				close:function(){
					if(!self.isOpen){
						return;
					}
					self.isOpen = false;
					channels.current().reload();
				},
				fetch:function(day,fn,n){
					if(!self.isOpen){
						return;
					}
					if(n === undefined){
						n = 0;
					}
					network.getJSON('Log.php?day='+day+'&offset='+parseInt(n,10)+'&channel='+channels.current().handlerB64,function(data){
						if(!data.banned){
							$.map(data.lines,function(line){
								eventOnMessage(line,true);
							});
							if(data.lines.length >= 1000){
								self.fetch(day,fn,n+1000);
							}else if(fn!==undefined){
								fn(true);
							}
						}else{
							send.internal('<span style="color:#C73232;"><b>ERROR:</b> banned</span>');
							fn(false);
						}
					});
				}
			};
			return {
				fetch:self.fetch,
				open:self.open,
				close:self.close
			};
		})(),
		oirc;
	
	this.OMNOMIRCSERVER = OMNOMIRCSERVER;
	this.settings = {
		loggedIn:settings.loggedIn,
		fetch:settings.fetch,
		net:settings.net,
		getNetwork:settings.getNetwork,
		getPmIdent:settings.getPmIdent,
		getWholePmIdent:settings.getWholePmIdent,
		nick:settings.nick,
		guestLevel:settings.guestLevel,
		identGuest:settings.identGuest,
		logout:settings.logout
	};
	this.ls = {
		get:ls.get,
		set:ls.set
	};
	this.network = {
		getJSON:network.getJSON,
		post:network.post
	};
	this.options = {
		get:options.get,
		set:options.set,
		getAll:options.getAll
	};
	this.instant = {
		current:instant.current
	};
	this.channels = {
		load:channels.load,
		join:channels.join,
		joinPm:channels.joinPm,
		part:channels.part,
		getList:channels.getList,
		getHandler:channels.getHandler,
		current:channels.current,
		setChans:channels.setChans,
		highlight:channels.highlight
	};
	this.users = {
		add:users.add,
		remove:users.remove,
		setUsers:users.setUsers
	};
	this.send = {
		send:send.send,
		internal:send.internal,
		val:send.val
	};
	this.parser = {
		parse:parser.parse,
		name:parser.name,
		highlight:parser.highlight
	};
	this.error = {
		addWarning:error.addWarning,
		addError:error.addError,
		init:error.init
	};
	this.page = {
		changeLinks:page.changeLinks,
		isBlurred:page.isBlurred
	};
	this.logs = {
		fetch:logs.fetch,
		open:logs.open,
		close:logs.close
	};
	this.statusBar = {
		set:statusBar.set
	}
	this.initinput = function($elem,doWysiwyg){
		if($elem === undefined){
			$elem = $('#message');
		}
		$input = $elem;
		if(doWysiwyg){
			wysiwyg.init();
		}
		tab.init();
		oldMessages.init();
		
		$input.keypress(function(e){
			if(e.which == 13){ // we hit enter
				e.preventDefault();
				if(!$input.attr('disabled')){
					send.send(function(){
						send.val('');
						$input.focus();
					});
				}
			}
		});
	};
	this.connect = function(callback){
		page.init();
		notification.init();
		settings.fetch(function(){
			instant.init();
			request.init();
			channels.init();
			callback();
		});
	};
	this.disconnect = function(){
		instant.kill();
		request.kill();
	};
	this.setClassPrefix = function(prefix){
		CLASSPREFIX = prefix;
	};
	
	this.onerror = function(){};
	this.onwarning = function(){};
	this.onmessage = function(){};
	this.onmessageraw = false;
	this.onuserchange = function(){};
	this.onchannelchange = function(){};
	this.onchanneljoin = function(){};
	this.onchannelpart = function(){};
	this.onsmileychange = function(){};
	this.onsetval = false;
	this.ongetval = false;
	this.ontopicchange = function(){};
	this.onnotification = function(){};
	oirc = this;
	if(this.setOptions !== undefined){
		this.options.setAll = options.setAll;
		this.setOptions();
		delete this.options.setAll;
	}
};
