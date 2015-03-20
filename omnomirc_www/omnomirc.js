/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2015 Sorunome

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
oirc = (function(){
	var OMNOMIRCSERVER = 'https://omnomirc.omnimaga.org',
		settings = (function(){
			var hostname = '',
				nick = '',
				signature = '',
				numHigh = 4,
				uid = 0,
				checkLoginUrl = '',
				net = '',
				networks = [];
			return {
				fetch:function(fn,clOnly){
					if(clOnly===undefined){
						clOnly = false;
					}
					network.getJSON('config.php?js'+(document.URL.split('network=')[1]!==undefined?'&network='+document.URL.split('network=')[1].split('&')[0].split('#')[0]:'')+(clOnly?'&clonly':''),function(data){
						var set;
						if(!clOnly){
							hostname = data.hostname;
							channels.setChans(data.channels);
							parser.setSmileys(data.smileys);
							networks = data.networks;
							net = data.network;
							options.setDefaults(data.defaults);
							options.setExtraChanMsg(data.extraChanMsg);
							ws.set(data.websockets.use,data.websockets.host,data.websockets.port,data.websockets.ssl);
						}
						
						checkLoginUrl = data.checkLoginUrl;
						
						set = ls.get('OmnomIRCCL'+settings.net());
						if(set===null || set=='' || !set || clOnly){
							network.getJSON(checkLoginUrl+'&network='+net.toString()+'&jsoncallback=?',function(data){
								nick = data.nick;
								signature = data.signature;
								uid = data.uid;
								ls.set('OmnomIRCCL'+settings.net(),JSON.stringify({
									nick:nick,
									signature:signature,
									uid:uid
								}));
								if(fn!==undefined){
									fn();
								}
							},!clOnly,false);
						}else{
							set = JSON.parse(set);
							nick = set.nick;
							signature = set.signature;
							uid = set.uid;
							if(fn!==undefined){
								fn();
							}
						}
					},!clOnly,false);
				},
				getUrlParams:function(){
					return 'nick='+base64.encode(nick)+'&signature='+base64.encode(signature)+'&time='+(+new Date()).toString()+'&id='+uid+'&network='+net+(nick!=''?'&noLoginErrors':'');
				},
				getIdentParams:function(){
					return {
						nick:nick,
						signature:signature,
						time:(+new Date()).toString(),
						id:uid,
						network:net
					};
				},
				networks:function(){
					return networks;
				},
				nick:function(){
					return nick;
				},
				net:function(){
					return net;
				},
				loggedIn:function(){
					return signature !== '';
				}
			};
		})(),
		ls = (function(){
			var getCookie = function(c_name){
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
				setCookie = function(c_name,value,exdays){
					var exdate = new Date(),
						c_value = escape(value);
					exdate.setDate(exdate.getDate() + exdays);
					c_value += ((exdays===null) ? '' : '; expires='+exdate.toUTCString());
					document.cookie=c_name + '=' + c_value;
				},
				support = function(){
					try{
						return 'localStorage' in window && window['localStorage'] !== null;
					}catch(e){
						return false;
					}
				};
			return {
				get:function(name){
					if(support()){
						return localStorage.getItem(name);
					}
					return getCookie(name);
				},
				set:function(name,value){
					if(support()){
						localStorage.setItem(name,value);
					}else{
						setCookie(name,value,30);
					}
				}
			};
		})(),
		network = (function(){
			var errors = [],
				warnings = [],
				didRelog = false,
				removeSig = function(s){
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
				addError = function(s,e){
					s = removeSig(s);
					errors.push({
						time:(new Date().getTime()),
						file:s,
						content:e
					});
					$('#errors')
						.css('display','')
						.find('.count')
						.text(errors.length);
				},
				addWarning = function(s,e){
					s = removeSig(s);
					warnings.push({
						time:(new Date().getTime()),
						file:s,
						content:e
					});
					$('#warnings')
						.css('display','')
						.find('.count')
						.text(warnings.length);
				},
				checkCallback = function(data,fn,recall){
					if(data.relog!==undefined && data.relog!=2){
						if(data.errors!==undefined){
							$.each(data.errors,function(i,e){
								if(e.type!==undefined){
									addError(s,e);
								}else{
									addError(s,{
										type:'misc',
										message:e
									});
								}
							});
						}
						if(data.warnings!==undefined){
							$.each(data.warnings,function(i,w){
								if(w.type!==undefined){
									addWarning(s,w);
								}else{
									addWarning(s,{
										type:'misc',
										message:w
									});
								}
							});
						}
					}
					if(data.relog!==undefined && data.relog!=0){
						if(data.relog==1){
							settings.fetch(undefined,true);
							fn(data);
						}else if(data.relog==2){
							settings.fetch(function(){
								recall();
							},true);
						}else if(data.relog==3){
							if(didRelog){
								fn(data);
							}else{
								settings.fetch(function(){
									recall();
								},true);
							}
						}
						didRelog = true;
					}else{
						fn(data);
					}
				};
			return {
				getJSON:function(s,fn,async,urlparams){
					if(async==undefined){
						async = true;
					}
					if(urlparams==undefined){
						urlparams = true;
					}
					return $.ajax({
							url:s+(urlparams?'&'+settings.getUrlParams():''),
							dataType:'json',
							async:async
						})
						.done(function(data){
							checkCallback(data,fn,function(){
								network.getJSON(s,fn,async,urlparams);
							});
							
						});
				},
				post:function(s,pdata,fn,async,urlparams){
					if(async==undefined){
						async = true;
					}
					if(urlparams==undefined){
						urlparams = true;
					}
					return $.ajax({
							type:'POST',
							url:s+(urlparams?'&'+settings.getUrlParams():''),
							async:async,
							data:pdata
						})
						.done(function(data){
							checkCallback(data,fn,function(){
								network.post(s,pdata,fn,async,urlparams);
							});
						});
				},
				init:function(){
					var makePopup = function(type,data){
						return $('<div>')
								.addClass('errorPopup')
								.append(
									$('<a>')
										.text('Close')
										.click(function(e){
											e.preventDefault();
											$(this).parent().remove();
										}),
									'&nbsp;',
									$('<b>')
										.text(type),
									$('<div>')
										.append(
											$.map(data,function(e){
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
											})
										)
								)
								.appendTo('body');
					};
					$('#errors > .icon')
						.click(function(){
							makePopup('Errors',errors);
						});
					$('#warnings > .icon')
						.click(function(){
							makePopup('Warnings',warnings);
						});
				}
			};
		})(),
		admin = (function(){
			return {
				init:function(){
					$.getScript('admin.js');
				}
			}
		})(),
		options = (function(){
			var defaults = '',
				refreshCache = true,
				cache = '',
				optionMenu = [
					{
						disp:'Highlight Bold',
						id:1,
						defaultOption:'T'
					},
					{
						disp:'Highlight Red',
						id:2,
						defaultOption:'T'
					},
					{
						disp:'Colored Names',
						id:3,
						defaultOption:'F'
					},
					{
						disp:'Show extra Channels',
						id:9,
						defaultOption:'F',
						before:function(){
							if(extraChanMsg!==''){
								alert(extraChanMsg);
							}
							return true;
						}
					},
					{
						disp:'Alternating Line Highlight',
						id:6,
						defaultOption:'T'
					},
					{
						disp:'Enable',
						id:5,
						defaultOption:'T'
					},
					{
						disp:'Ding on Highlight',
						id:8,
						defaultOption:'F'
					},
					{
						disp:'Show Timestamps',
						id:10,
						defaultOption:'F'
					},
					{
						disp:'Show Updates in Browser Status Bar',
						id:11,
						defaultOption:'T'
					},
					{
						disp:'Show Smileys',
						id:12,
						defaultOption:'T'
					},
					{
						disp:'Hide Userlist',
						id:14,
						defaultOption:'F'
					},
					{
						disp:'Number chars for Highlighting',
						id:13,
						defaultOption:'3',
						handler:function(){
							return $('<td>')
								.attr('colspan',2)
								.css('border-right','none')
								.append($('<select>')
									.change(function(){
										options.set(13,this.value);
									})
									.append(
										$.map([0,1,2,3,4,5,6,7,8,9],function(i){
											return $('<option>')
												.attr((options.get(13,'3')==i?'selected':'false'),'selected')
												.val(i)
												.text(i+1);
										})
									)
								);
						}
					},
					{
						disp:'Show Scrollbar',
						id:15,
						defaultOption:'T'
					},
					{
						disp:'Enable Scrollwheel',
						id:16,
						defaultOption:'F'
					},
					{
						disp:'Browser Notifications',
						id:7,
						defaultOption:'F',
						before:function(){
							notification.request();
							return false;
						}
					},
					{
						disp:'Show OmnomIRC join/part messages',
						id:17,
						defaultOption:'F'
					},
					{
						disp:'Use WYSIWYG editor (experimental)',
						id:18,
						defaultOption:'F'
					}
				],
				extraChanMsg = '';
			return {
				setDefaults:function(d){
					defaults = d;
				},
				set:function(optionsNum,value){
					if(optionsNum < 1 || optionsNum > 40){
						return;
					}
					var optionsString = ls.get('OmnomIRCSettings'+settings.net());
					if(optionsString===null){
						ls.set('OmnomIRCSettings'+settings.net(),'----------------------------------------');
						optionsString = ls.get('OmnomIRCSettings'+settings.net());
					}
					optionsString = optionsString.substring(0,optionsNum-1)+value+optionsString.substring(optionsNum);
					ls.set('OmnomIRCSettings'+settings.net(),optionsString);
					refreshCache = true;
				},
				get:function(optionsNum,defaultOption){
					var optionsString = (refreshCache?(cache=ls.get('OmnomIRCSettings'+settings.net())):cache),
						result;
					refreshCache = false;
					if(optionsString===null){
						return defaultOption;
					}
					result = optionsString.charAt(optionsNum-1);
					if(result=='-'){
						return (defaults.charAt(optionsNum-1)!=='' && defaults.charAt(optionsNum-1)!='-'?defaults.charAt(optionsNum-1):defaultOption);
					}
					return result;
				},
				setExtraChanMsg:function(s){
					extraChanMsg = s;
				},
				getFullOptionsString:function(){
					var optionsString = (refreshCache?(cache=ls.get('OmnomIRCSettings'+settings.net())):cache),
						res = '';
					for(var i = 0;defaults.charAt(i)!='' && optionsString.charAt(i)!='';i++){
						res += (optionsString.charAt(i)!='-' && optionsString.charAt(i)!=''?optionsString.charAt(i):(defaults.charAt(i)!=''?defaults.charAt(i):'-'));
					}
					return res;
				},
				getHTML:function(){
					return $.merge($.map([false,true],function(alternator){
							return $('<table>')
								.addClass('optionsTable')
								.append(
									$.map(optionMenu,function(o){
										return ((alternator = !alternator)?$('<tr>')
											.append(
												$.merge(
												[$('<td>')
													.text(o.disp)],
												(o.handler===undefined?[
												$('<td>')
													.addClass('option '+(options.get(o.id,o.defaultOption)=='T'?'selected':''))
													.text('Yes')
													.click(function(){
														if(options.get(o.id,o.defaultOption)=='F'){
															if((o.before!==undefined && o.before()) || o.before===undefined){
																options.set(o.id,'T');
																$(this).addClass('selected').next().removeClass('selected');
															}
														}
													}),
												$('<td>')
													.addClass('option '+(options.get(o.id,o.defaultOption)=='F'?'selected':''))
													.text('No')
													.click(function(){
														if(options.get(o.id,o.defaultOption)=='T'){
															options.set(o.id,'F');
															$(this).addClass('selected').prev().removeClass('selected');
														}
													})]:o.handler()))
											):'');
									})
								);
					}),
					$('<div>').append(
						'&nbsp;',
						$('<a>')
							.text('Reset Defaults')
							.click(function(e){
								e.preventDefault();
								ls.set('OmnomIRCSettings'+settings.net(),'----------------------------------------');
								ls.set('OmnomIRCChannels'+settings.net(),'');
								ls.set('OmnomIRCCL'+settings.net(),'');
								document.location.reload();
							})
					));
				}
			};
		})(),
		instant = (function(){
			var id = '',
				update = function(){
					ls.set('OmnomBrowserTab',id);
					ls.set('OmnomNewInstant','false');
				};
			return {
				init:function(){
					id = Math.random().toString(36)+(new Date()).getTime().toString();
					ls.set('OmnomBrowserTab',id);
					$(window)
						.focus(function(){
							update();
						})
						.unload(function(){
							ls.set('OmnomNewInstant','true');
						});
				},
				current:function(){
					if(ls.get('OmnomNewInstant')=='true'){
						update();
					}
					return id == ls.get('OmnomBrowserTab');
				}
			};
		}()),
		indicator = (function(){
			var interval = false,
				$elem = false,
				pixels = [];
			return {
				start:function(){
					if(interval===false){
						pixels = [true,true,true,true,true,false,false,false];
						$elem = $('<div>')
							.attr('id','indicator')
							.css({
								position:'absolute',
								zIndex:44,
								margin:0,
								padding:0,
								top:0,
								right:0
							})
							.appendTo('body');
						interval = setInterval(function(){
							$elem.empty().append(
								$.map(pixels,function(p){
									return $('<div>')
										.css({
											padding:0,
											margin:0,
											width:3,
											height:3,
											backgroundColor:(p?'black':'')
										});
								})
							);
							var temp = pixels[0],
								i;
							for(i=1;i<=7;i++){
								pixels[(i-1)] = pixels[i];
							}
							pixels[7] = temp;
						},50);
					}
				},
				stop:function(){
					if(interval!==false){
						clearInterval(interval);
						interval = false;
						$elem.remove();
					}
				}
			};
		})(),
		notification = (function(){
			var notification_support = window.webkitNotifications!==undefined && window.webkitNotifications!==null && window.webkitNotifications,
				support = function(){
					if(notification_support || (typeof Notification!='undefined' && Notification && Notification.permission!='denied')){
						return true;
					}
					return false;
				},
				show = function(s){
					var n;
					if(notification_support && window.webkitNotifications.checkPermission() === 0){
						n = window.webkitNotifications.createNotification('http://www.omnimaga.org/favicon.ico','OmnomIRC Highlight',s);
						n.show();
					}else if(typeof Notification!='undefined' && Notification && Notification.permission=='granted'){
						n = new Notification('OmnomIRC Highlight',{
							icon:'http://www.omnimaga.org/favicon.ico',
							body:s
						});
						n.onshow = function(){
							setTimeout(n.close,30000);
						};
					}
				};
			return {
				request:function(){
					if(notification_support){
						window.webkitNotifications.requestPermission(function(){
							if (window.webkitNotifications.checkPermission() === 0){
								show('Notifications Enabled!');
								options.set(7,'T');
								document.location.reload();
							}
						});
					}else if(typeof Notification!=='undefined' && Notification && Notification.permission!=='denied'){
						Notification.requestPermission(function(status){
							if (Notification.permission !== status){
								Notification.permission = status;
							}
							if(status==='granted'){
								show('Notifications Enabled!');
								options.set(7,'T');
								document.location.reload();
							}
						});
					}else{
						alert('Your browser doesn\'t support notifications');
					}
				},
				make:function(s,c){
					if(instant.current()){
						if(options.get(7,'F')=='T'){
							show(s);
						}
						if(options.get(8,'F')=='T'){
							$('#ding')[0].play();
						}
						if(c!=channels.getCurrentName()){
							channels.highlight(c,true);
						}
					}else{
						if(c!=channels.getCurrentName()){
							channels.highlight(c);
						}
					}
				}
			};
		})(),
		ws = (function(){
			var socket = false,
				connected = false,
				use = true,
				sendBuffer = [],
				allowLines = false,
				enabled = false,
				host = '',
				port = 0,
				ssl = true,
				fallback = function(){
					network.getJSON('omnomirc.php?getcurline&noLoginErrors',function(data){
						request.setCurLine(data.curline);
						request.start();
					},undefined,false);
				},
				identify = function(){
					ws.send($.extend({action:'ident'},settings.getIdentParams()));
				};
			return {
				init:function(){
					if(!("WebSocket" in window) || !enabled){
						use = false;
						return false;
					}
					try{
						socket = new WebSocket((ssl?'wss://':'ws://')+host+':'+port.toString(10));
					}catch(e){
						fallback();
					}
					socket.onopen = function(e){
						connected = true;
						for(var i = 0;i < sendBuffer.length;i++){
							ws.send(sendBuffer[i]);
						}
					};
					socket.onmessage = function(e){
						try{
							var data = JSON.parse(e.data);
							if(allowLines && data.line!==undefined){
								parser.addLine(data.line);
							}
							if(data.relog!==undefined && data.relog!=0){
								settings.fetch(function(){
									identify();
								},true);
							}
						}catch(e){};
					};
					socket.onclose = function(e){
						use = false;
						fallback();
					};
					socket.onerror = function(e){
						socket.close();
						use = false;
						fallback();
					};
					
					identify();
					
					$(window).on('beforeunload',function(){
						socket.close();
					});
					return true;
				},
				set:function(enabledd,hostt,portt,ssll){
					enabled = enabledd;
					host = hostt;
					port = portt;
					ssl = ssll;
				},
				send:function(msg){
					if(connected){
						socket.send(JSON.stringify(msg));
					}else{
						sendBuffer.push(msg);
					}
				},
				setChan:function(c){
					ws.send({
						action:'chan',
						chan:c
					});
				},
				use:function(){
					return use;
				},
				allowRecLines:function(){
					allowLines = true;
				},
				dissallowRecLines:function(){
					allowLines = false;
				}
			};
		})(),
		request = (function(){
			var lastSuccess = (new Date).getTime(),
				curLine = 0,
				inRequest = false,
				handler = false,
				send = function(){
					if(channels.getCurrent()==='' || ws.use()){
						return;
					}
					handler = network.getJSON(
							'Update.php?high='+
							(parseInt(options.get(13,'3'),10)+1).toString()+
							'&channel='+channels.getCurrent(false,true)+
							'&lineNum='+curLine.toString(),
						function(data){
							var newRequest = true;
							if(channels.getCurrent()===''){
								return;
							}
							handler = false;
							lastSuccess = (new Date).getTime();
							if(data.lines!==undefined){
								$.each(data.lines,function(i,line){
									return newRequest = parser.addLine(line);
								});
							}
							if(newRequest){
								setTimer();
							}
						})
						.fail(function(){
							handler = false;
							if((new Date).getTime() >= lastSuccess + 300000  ){
								send.internal('<span style="color:#C73232;">OmnomIRC has lost connection to server. Please refresh to reconnect.</span>');
							}else if(!inRequest){
								lastSuccess = (new Date).getTime();
							}else{
								setTimer();
							}
						});
				},
				setTimer = function(){
					if(channels.getCurrent()!=='' && handler===false && !ws.use()){
						setTimeout(function(){
							send();
						},(page.isBlurred()?2500:200));
					}else{
						request.cancel();
					}
				};
			return {
				cancel:function(){
					if(inRequest){
						inRequest = false;
						try{
							handler.abort();
						}catch(e){}
					}
				},
				start:function(){
					if(!(inRequest || ws.use())){
						inRequest = true;
						setTimer();
					}
				},
				setCurLine:function(c){
					curLine = c;
				},
				getCurLine:function(){
					return curLine;
				}
			};
		})(),
		channels = (function(){
			var chans = [],
				current = '',
				currentb64 = '',
				currentName = '',
				save = function(){
					ls.set('OmnomIRCChannels'+settings.net(),JSON.stringify(chans));
				},
				load = function(){
					try{
						var chanList = JSON.parse(ls.get('OmnomIRCChannels'+settings.net())),
							exChans = $.map(chans,function(ch){
								if((ch.ex && options.get(9,'F')=='T') || !ch.ex){
									return ch;
								}
								return undefined;
							}),
							exChansInUse = [];
						if(chanList!==null && chanList!=[]){
							chans = $.merge(
									$.map(chanList,function(v){
										if(v.id != -1){
											var valid = false;
											$.each(chans,function(i,vc){
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
				},
				draw = function(){
					$('#ChanList').empty().append(
						$.map(chans,function(c,i){
							if((c.ex && options.get(9,'F')=='T') || !c.ex){
								var mouseX = 0, // new closur as in map
									startX = 0,
									initDrag = false,
									offsetX = 0,
									canClick = false,
									width = 0,
									startDrag = function(elem){
										width = $(elem).width();
										canClick = false;
										$(elem).css({
												'position':'absolute',
												'z-index':100,
												'left':mouseX - offsetX
											})
											.after(
												$('<div>')
													.attr('id','topicDragPlaceHolder')
													.css({
														'display':'inline-block',
														'width':width
													})
											)
											.addClass('dragging')
											.find('div').css('display','block').focus();
										initDrag = false;
									},
									mousedownFn = function(e,elem){
										e.preventDefault();
										startX = e.clientX;
										offsetX = startX - $(elem).position().left;
										initDrag = true;
									},
									mousemoveFn = function(e,elem){
										mouseX = e.clientX;
										if(initDrag && Math.abs(mouseX - startX) >= 4){
											initDrag = false;
											startDrag(elem);
											e.preventDefault();
										}else if($(elem).hasClass('dragging')){
											var newX = mouseX - offsetX;
											$(elem).css('left',newX);
											$ne = $('#topicDragPlaceHolder').next('.chanList');
											$pe = $('#topicDragPlaceHolder').prev('.chanList');
											if($ne.length > 0 && ($ne.position().left) < (newX + (width/2))){
												$ne.after($('#topicDragPlaceHolder').remove());
											}else if($pe.length > 0){
												if($pe.attr('id') == $(elem).attr('id')){ // we selected our own element!
													$pe = $pe.prev();
												}
												if($pe.length > 0 && $pe.position().left > newX){
													$pe.before($('#topicDragPlaceHolder').remove());
												}
											}
										}
									},
									mouseupFn = function(e,elem){
										if(initDrag){
											initDrag = false;
										}else{
											$(elem).find('div').css('display','none');
											$('#topicDragPlaceHolder').replaceWith(elem);
											chans = $.map($('.chanList'),function(chan,i){
												if($(chan).find('span').hasClass('curchan')){
													options.set(4,String.fromCharCode(i+45));
												}
												return $(chan).data('json');
											});
											save();
											draw();
										}
									};
								return $('<div>')
									.data('json',c)
									.attr('id','chan'+i.toString())
									.addClass('chanList'+(c.high?' highlightChan':''))
									.append(
										$('<span>')
											.addClass('chan '+(getHandler(i)==current?' curchan':''))
											.append(
												(c.chan.substr(0,1)!='#'?
												$('<span>')
													.addClass('closeButton')
													.css({
														width:9,
														float:'left'
													})
													.mouseup(function(){
														if(canClick){
															channels.part(i);
														}
													})
													.text('x')
												:''),
												$('<span>').text(c.chan)
											)
											.mouseup(function(){
												if(canClick){
													channels.join(i);
												}
											}),
										$('<div>')
											.css({
												'position':'fixed',
												'width':'100%',
												'height':'100%',
												'z-index':101,
												'top':0,
												'left':0,
												'display':'none'
											})
											.mousemove(function(e){
												mousemoveFn(e,$(this).parent());
											})
											.mouseup(function(e){
												mouseupFn(e,$(this).parent());
											})
											.mouseout(function(e){
												mouseupFn(e,$(this).parent());
											})
									)
									.mousedown(function(e){
										canClick = true;
										mousedownFn(e,this);
									})
									.mousemove(function(e){
										mousemoveFn(e,this);
									})
									.mouseout(function(e){
										if(initDrag){
											startDrag(this);
										}
									})
									.mouseup(function(e){
										mouseupFn(e,this);
									});
							}
						})
					);
				},
				requestHandler = false,
				getHandler = function(i,b64){
					if(chans[i].id!=-1){
						return chans[i].id;
					}
					if(b64){
						return base64.encode(chans[i].chan);
					}
					return chans[i].chan;
				};
			return {
				highlight:function(c,doSave){
					$.each(chans,function(i,ci){
						if(ci.chan.toLowerCase()==c.toLowerCase() || c == ci.id){
							$('#chan'+i.toString()).addClass('highlightChan');
							chans[i].high = true;
						}
					});
					if(doSave!==undefined && doSave){
						save();
					}
				},
				openChan:function(s){
					var addChan = true;
					s = s.trim();
					if(s.substr(0,1) != '@' && s.substr(0,1) != '#'){
						s = '@' + s;
					}
					s = s.toLowerCase();
					$.each(chans,function(i,c){
						if(c.chan==s){
							addChan = i;
						}
					});
					if(addChan===true){
						if(s.substr(0,1)=='#'){
							send.internal('<span style="color:#C73232;"> Join Error: Cannot join new channels starting with #.</span>');
							return;
						}
						chans.push({
							chan:s,
							high:false,
							ex:false,
							id:-1,
							order:-1
						});
						save();
						draw();
						channels.join(chans.length-1);
					}else{
						channels.join(addChan);
					}
					tab.load();
				},
				openPm:function(s,join){
					var addChan = true;
					if(join===undefined){
						join = false;
					}
					s = s.trim();
					if(s.substr(0,1)=='@' || s.substr(0,1)=='#'){
						send.internal('<span style="color:#C73232;"> Query Error: Cannot query a channel. Use /join instead.</span>');
						return;
					}
					s = s.toLowerCase();
					if(s.substr(0,1)!='*'){
						s = '*'+s;
					}
					$.each(chans,function(i,c){
						if(c.chan==s){
							addChan = i;
						}
					});
					if(addChan===true){
						chans.push({
							chan:s,
							high:!join,
							ex:false,
							id:-1,
							order:-1
						});
						save();
						draw();
						if(join){
							channels.join(chans.length-1);
						}
					}else{
						chans[addChan].high = !join;
						if(join){
							channels.join(addChan);
						}
					}
					tab.load();
				},
				part:function(i){
					var select = false;
					if(i===undefined){
						$.each(chans,function(ci,c){
							if(c.chan == current || c.id == current){
								i = ci;
							}
						});
					}
					if(isNaN(parseInt(i,10))){
						$.each(chans,function(ci,c){
							if(c.chan == i){
								i = ci;
							}
						});
					}
					if(isNaN(parseInt(i,10)) || i===undefined){
						send.internal('<span style="color:#C73232;"> Part Error: I cannot part '+i+'. (You are not in it.)</span>');
						return;
					}
					i = parseInt(i,10);
					if(chans[i].chan.substr(0,1)=='#'){
						send.internal('<span style="color:#C73232;"> Part Error: I cannot part '+chans[i].chan+'. (IRC channel.)</span>');
						return;
					}
					if(getHandler(i) == current){
						select = true;
					}
					chans.splice(i,1);
					save();
					draw();
					if(select){
						channels.join(i-1);
					}
				},
				join:function(i,fn){
					if(chans[i]!==undefined){
						indicator.start();
						request.cancel();
						ws.dissallowRecLines();
						$('#message').attr('disabled','true');
						$('#MessageBox').empty();
						$('.chan').removeClass('curchan');
						if(requestHandler!==false){
							requestHandler.abort();
						}
						requestHandler = network.getJSON('Load.php?count=125&channel='+getHandler(i,true),function(data){
							if(data.lines === undefined){
								if(data.message){
									send.internal(data.message);
								}else{
									send.internal('<span style="color:#C73232;"><b>ERROR:</b> couldn\'t join channel</span>');
								}
								indicator.stop();
								return;
							}
							current = getHandler(i);
							currentb64 = getHandler(i,true);
							currentName = chans[i].chan;
							oldMessages.read();
							options.set(4,String.fromCharCode(i+45));
							if(!data.banned){
								if(data.admin){
									$('#adminLink').css('display','');
								}else{
									$('#adminLink').css('display','none');
								}
								if(data.ignores!==undefined){
									parser.setIgnoreList(data.ignores);
								}
								users.setUsers(data.users);
								users.draw();
								$.each(data.lines,function(i,line){
									parser.addLine(line);
								});
								scroll.down();
								requestHandler = false;
								ws.setChan(getHandler(i));
								ws.allowRecLines();
								request.start();
							}else{
								send.internal('<span style="color:#C73232;"><b>ERROR:</b> banned</span>');
								requestHandler = false;
							}
							$('#chan'+i.toString()).removeClass('highlightChan').find('.chan').addClass('curchan');
							chans[i].high = false;
							save();
							tab.load();
							if(fn!==undefined){
								fn();
							}
							if(settings.loggedIn()){
								$('#message').removeAttr('disabled');
							}
							indicator.stop();
						});
					}
				},
				getCurrent:function(override,b64){
					if(requestHandler===false || override){
						return (b64?currentb64:current);
					}
					return '';
				},
				getCurrentName:function(override){
					if(requestHandler===false || override){
						return currentName;
					}
					return '';
				},
				getNames:function(){
					return $.map(chans,function(c){
						return c.chan;
					});
				},
				init:function(){
					load();
					draw();
				},
				setChans:function(c){
					chans = c;
				},
				getChans:function(){
					return chans;
				}
			};
		})(),
		tab = (function(){
			var tabWord = '',
				tabCount = 0,
				isInTab = false,
				startPos = 0,
				startChar = '',
				endPos = 0,
				endChar = '',
				endPos0 = 0,
				tabAppendStr = ' ',
				searchArray = [],
				node;
				getCurrentWord = function(){
					var messageVal = (!wysiwyg.support()?$('#message')[0].value:(node = window.getSelection().anchorNode).nodeValue);
					if(isInTab){
						return tabWord;
					}
					startPos = (!wysiwyg.support()?$('#message')[0].selectionStart:window.getSelection().anchorOffset);
					startChar = messageVal.charAt(startPos);
					while(startChar != ' ' && --startPos > 0){
						startChar = messageVal.charAt(startPos);
					}
					if(startChar == ' '){
						startPos++;
					}
					endChar = messageVal.charAt(endPos);
					while(endChar != ' ' && ++endPos <= messageVal.length){
						endChar = messageVal.charAt(endPos);
					}
					endPos0 = endPos;
					return messageVal.substr(startPos,endPos - startPos).trim();
				},
				getTabComplete = function(){
					var messageVal = (!wysiwyg.support()?$('#message')[0].value:node.nodeValue),
						name;
					if(messageVal === null){
						return;
					}
					if(!isInTab){
						tabAppendStr = ' ';
						startPos = (!wysiwyg.support()?$('#message')[0].selectionStart:window.getSelection().anchorOffset);
						startChar = messageVal.charAt(startPos);
						while(startChar != ' ' && --startPos > 0){
							startChar = messageVal.charAt(startPos);
						}
						if(startChar == ' '){
							startChar+=2;
						}
						if(startPos===0){
							tabAppendStr = ': ';
						}
						endPos = (!wysiwyg.support()?$('#message')[0].selectionStart:window.getSelection().anchorOffset);
						endChar = messageVal.charAt(endPos);
						while(endChar != ' ' && ++endPos <= messageVal.length){
							endChar = messageVal.charAt(endPos);
						}
						if(endChar == ' '){
							endChar-=2;
						}
					}
					name = search(getCurrentWord(),tabCount);
					if(name == getCurrentWord()){
						tabCount = 0;
						name = search(getCurrentWord(),tabCount);
					}
					messageVal = messageVal.substr(0,startPos)+name+tabAppendStr+messageVal.substr(endPos+1);
					if(!wysiwyg.support()){
						$('#message')[0].value = messageVal;
					}else{
						window.getSelection().anchorNode.nodeValue = messageVal;
						window.getSelection().getRangeAt(0).setEnd(node,startPos+name.length+tabAppendStr.length);
						window.getSelection().getRangeAt(0).setStart(node,startPos+name.length+tabAppendStr.length);
					}
					endPos = endPos0+name.length+tabAppendStr.length;
				},
				search = function(start,startAt){
					var res = false;
					if(!startAt){
						startAt = 0;
					}
					$.each(searchArray,function(i,u){
						if(u.toLowerCase().indexOf(start.toLowerCase()) === 0 && startAt-- <= 0 && res === false){
							res = u;
						}
					});
					if(res!==false){
						return res;
					}
					return start;
				};
			return {
				init:function(){
					$('#message')
						.keydown(function(e){
							if(e.keyCode == 9){
								if(!e.ctrlKey){
									e.preventDefault();
									
									tabWord = getCurrentWord();
									getTabComplete();
									tabCount++;
									isInTab = true;
									setTimeout(1,1);
								}
							}else{
								tabWord = '';
								tabCount = 0;
								isInTab = false;
							}
						});
				},
				load:function(){
					searchArray = $.merge(users.getNames(),channels.getNames());
				}
			};
		})(),
		users = (function(){
			var usrs = [],
				exists = function(u){
					var result = false;
					$.each(usrs,function(i,us){
						if(us.nick.toLowerCase() == u.nick.toLowerCase() && us.network == u.network){
							result = true;
							return false;
						}
					});
					return result;
				};
			return {
				add:function(u){
					if(channels.getCurrent()!==''){
						usrs.push(u);
						users.draw();
					}
				},
				remove:function(u){
					if(channels.getCurrent()!==''){
						$.each(usrs,function(i,us){
							if(us.nick == u.nick && us.network == u.network){
								usrs.splice(i,1);
								return false;
							}
						});
						users.draw();
					}
				},
				draw:function(){
					usrs.sort(function(a,b){
						var al=a.nick.toLowerCase(),bl=b.nick.toLowerCase();
						return al==bl?(a==b?0:a<b?-1:1):al<bl?-1:1;
					});
					$('#UserList').empty().append(
						$.map(usrs,function(u){
							var getInfo,
								ne = encodeURIComponent(u.nick),
								n = $('<span>').text(u.nick).html();
							return $('<span>')
								.attr('title',(settings.networks()[u.network]!==undefined?settings.networks()[u.network].name:'Unknown Network'))
								.append(
									(settings.networks()[u.network]!==undefined?settings.networks()[u.network].userlist.split('NICKENCODE').join(ne).split('NICK').join(n):n),
									'<br>'
								)
								.mouseover(function(){
									getInfo = network.getJSON('Load.php?userinfo&name='+base64.encode(u.nick)+'&chan='+channels.getCurrent(false,true)+'&online='+u.network.toString(),function(data){
										if(data.last){
											$('#lastSeenCont').text('Last Seen: '+(new Date(data.last*1000)).toLocaleString());
										}else{
											$('#lastSeenCont').text('Last Seen: never');
										}
										$('#lastSeenCont').css('display','block');
									},undefined,false);
								})
								.mouseout(function(){
									try{
										getInfo.abort();
									}catch(e){}
									$('#lastSeenCont').css('display','none');
								});
						}),
						'<br><br>'
					);
				},
				setUsers:function(u){
					usrs = u;
				},
				getNames:function(){
					return $.map(usrs,function(u){
						return u.nick;
					});
				}
			};
		})(),
		topic = (function(){
			var current = '';
			return {
				set:function(t){
					$('#topic').empty().append(t);
					current = t;
				}
			};
		})(),
		scroll = (function(){
			var isDown = false,
				is_touch = (('ontouchstart' in window) || (navigator.MaxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0)),
				touchScroll = function($elem,fn){
					var lastY = -1;
					$elem.bind('touchstart',function(e){
						if($(e.target).is('a')){
							return;
						}
						e.preventDefault();
						lastY = e.originalEvent.touches[0].clientY;
					}).bind('touchmove',function(e){
						if($(e.target).is('a')){
							return;
						}
						e.preventDefault();
						if(lastY == -1){
							return;
						}
						var y = e.originalEvent.changedTouches[0].clientY;
						fn(y - lastY);
						lastY = y;
					}).bind('touchend touchcancel touchleave',function(e){
						if($(e.target).is('a')){
							return;
						}
						e.preventDefault();
						lastY = -1;
					});
				},
				enableButtons = function(){
					var addHook = function(elem,effect,inc){
							var interval;
							$(elem)
								.mousedown(function(){
									interval = setInterval(function(){
										document.getElementById(effect).scrollLeft += inc;
									},50);
								})
								.mouseup(function(){
									try{
										clearInterval(interval);
									}catch(e){}
								})
								.mouseout(function(){
									try{
										clearInterval(interval);
									}catch(e){}
								});
							if(is_touch){
								$(elem).bind('touchstart',function(){
									interval = setInterval(function(){
										document.getElementById(effect).scrollLeft += inc;
									},50);
								}).bind('touchend touchcancel touchleave',function(e){
									try{
										clearInterval(interval);
									}catch(e){}
								});
							}
						};
					addHook('#arrowLeftChan','ChanListCont',-9);
					addHook('#arrowRightChan','ChanListCont',9);
					
					addHook('#arrowLeftTopic','topicCont',-9);
					addHook('#arrowRightTopic','topicCont',9);
				},
				enableWheel = function(){
					var moveWindow = function(delta){
							isDown = false;
							document.getElementById('mBoxCont').scrollTop = Math.min(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight,Math.max(0,document.getElementById('mBoxCont').scrollTop-delta));
							if(document.getElementById('mBoxCont').scrollTop==(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight)){
								isDown = true;
							}
							if(options.get(15,'T')=='T'){
								reCalcBar();
							}
						};
					$('#mBoxCont').bind('DOMMouseScroll mousewheel',function(e){
						e.preventDefault();
						e.stopPropagation();
						e.cancelBubble = true;
						moveWindow((/Firefox/i.test(navigator.userAgent)?(e.originalEvent.detail*(-20)):(e.originalEvent.wheelDelta/2)));
					});
					if(is_touch){
						touchScroll($('#mBoxCont'),function(d){
							moveWindow(d);
						});
					}
				},
				reCalcBar = function(){
					if($('#scrollBar').length!==0){
						$('#scrollBar').css('top',(document.getElementById('mBoxCont').scrollTop/(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight))*($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight-38)+38);
					}
				},
				enableUserlist = function(){
					var moveUserList = function(delta){
							$(this).css('top',Math.min(0,Math.max(((/Opera/i.test(navigator.userAgent))?-30:0)+document.getElementById('UserListInnerCont').clientHeight-this.scrollHeight,parseInt(this.style.top,10)+delta)));
						};
					$('#UserList')
						.css('top',0)
						.bind('DOMMouseScroll mousewheel',function(e){
							if(e.preventDefault){
								e.preventDefault();
							}
							e = e.originalEvent;
							moveUserList((/Firefox/i.test(navigator.userAgent)?(e.detail*(-20)):(e.wheelDelta/2)));
						});
					if(is_touch){
						touchScroll($('#UserList'),function(d){
							moveUserList(d);
						});
					}
				},
				showBar = function(){
					var mouseMoveFn = function(y){
							var newscrollbartop = 0;
							if($bar.data('isClicked')){
								newscrollbartop = parseInt($bar.css('top'),10)+(y-$bar.data('prevY'));
								document.getElementById('mBoxCont').scrollTop = ((newscrollbartop-38)/($('body')[0].offsetHeight-$bar[0].offsetHeight-38))*(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight);
								isDown = false;
								if(newscrollbartop<38){
									newscrollbartop = 38;
									document.getElementById('mBoxCont').scrollTop = 0;
								}
								if(newscrollbartop>($('body')[0].offsetHeight-$bar[0].offsetHeight)){
									newscrollbartop = $('body')[0].offsetHeight-$bar[0].offsetHeight;
									document.getElementById('mBoxCont').scrollTop =  $('#mBoxCont').prop('scrollHeight')-$('#mBoxCont')[0].clientHeight;
									isDown = true;
								}
								$bar.css('top',newscrollbartop);
							}
							$bar.data('prevY',y);
						},
						mouseDownFn = function(){
							$bar.data('isClicked',true);
							$('#scrollArea').css('display','block');
						},
						mouseUpFn = function(){
							$bar.data('isClicked',false);
							$('#scrollArea').css('display','none');
						},
						$bar = $('<div>').attr('id','scrollBar').data({prevY:0,isClicked:false}).appendTo('body')
							.mousemove(function(e){
								mouseMoveFn(e.clientY);
							})
							.mousedown(function(){
								mouseDownFn();
							})
							.mouseup(function(){
								mouseUpFn();
							}),
						$tmp;
					$bar.css('top',$('body')[0].offsetHeight-$bar[0].offsetHeight);
					$tmp = $('<div>')
						.attr('id','scrollArea')
						.css({
							display:'none',
							width:'100%',
							height:'100%',
							position:'absolute',
							cursor:'move',
							left:0,
							top:0,
							zIndex:100
						})
						.mousemove(function(e){
							mouseMoveFn(e.clientY);
						})
						.mouseup(function(){
							mouseUpFn();
						})
						.mouseout(function(){
							mouseUpFn();
						});
					if(is_touch){
						$tmp.bind('touchend touchcancel touchleave',function(e){
							mouseUpFn();
						}).bind('touchmove',function(e){
							e.preventDefault();
							mouseMoveFn(e.originalEvent.changedTouches[0].clientY);
						});
						$bar.bind('touchstart',function(e){
							e.preventDefault();
							mouseDownFn();
						}).bind('touchmove',function(e){
							e.preventDefault();
							mouseMoveFn(e.originalEvent.changedTouches[0].clientY);
						}).bind('touchend touchcancel touchleave',function(e){
							e.preventDefault();
							mouseUpFn(e);
						});
					}
					$tmp.appendTo('body');
					$('<div>')
						.attr('id','scrollBarLine')
						.appendTo('body');
					$(window).trigger('resize');
				},
				showButtons = function(){
					var downIntM,
						upIntM,
						downIntMfn = function(){
							downIntM = setInterval(function(){
								document.getElementById('mBoxCont').scrollTop -= 9;
								isDown = false;
							},50);
						},
						upIntMfn = function(){
							upIntM = setInterval(function(){
								document.getElementById('mBoxCont').scrollTop += 9;
								if(document.getElementById('mBoxCont').scrollTop+document.getElementById('mBoxCont').clientHeight==document.getElementById('mBoxCont').scrollHeight){
									isDown = true;
								}
							},50);
						},
						$tmp;
					$tmp = $('<span>')
						.addClass('arrowButtonHoriz3')
						.append(
							$('<div>')
								.css({
									fontSize:'12pt',
									width:12,
									height:'9pt',
									top:0,
									position:'absolute',
									fontWeight:'bolder',
									marginTop:'10pt',
									marginLeft:'-10pt'
								})
								.addClass('arrowButtonHoriz2')
								.html('&#9650;'),
							$('<div>')
								.css({
									fontSize:'12pt',
									width:12,
									height:'9pt',
									top:0,
									position:'absolute',
									marginTop:'10pt',
									marginLeft:'-10pt'
								})
								.mousedown(function(){
									downIntMfn();
								})
								.mouseout(function(){
									try{
										clearInterval(downIntM);
									}catch(e){}
								})
								.mouseup(function(){
									try{
										clearInterval(downIntM);
									}catch(e){}
								})
						);
					if(is_touch){
						$tmp.bind('touchstart',function(){
							downIntMfn();
						}).bind('touchend touchcancel touchleave',function(e){
							try{
								clearInterval(downIntM);
							}catch(e){}
						});
					}
					$tmp.appendTo('body');
					
					$tmp = $('<span>')
						.addClass('arrowButtonHoriz3')
						.append(
							$('<div>')
								.css({
									fontSize:'12pt',
									width:12,
									height:'9pt',
									bottom:'9pt',
									position:'absolute',
									fontWeight:'bolder',
									marginTop:'-10pt',
									marginLeft:'-10pt'
								})
								.addClass('arrowButtonHoriz2')
								.html('&#9660;'),
							$('<div>')
								.css({
									fontSize:'12pt',
									width:12,
									height:'9pt',
									bottom:'9pt',
									position:'absolute',
									marginTop:'-10pt',
									marginLeft:'-10pt'
								})
								.mousedown(function(){
									upIntMfn();
								})
								.mouseout(function(){
									try{
										clearInterval(upIntM);
									}catch(e){}
								})
								.mouseup(function(){
									try{
										clearInterval(upIntM);
									}catch(e){}
								})
						);
					if(is_touch){
						$tmp.bind('touchstart',function(){
							upIntMfn();
						}).bind('touchend touchcancel touchleave',function(e){
							try{
								clearInterval(upIntM);
							}catch(e){}
						});
					}
					$tmp.appendTo('body');
				};
			return {
				down:function(){
					document.getElementById('mBoxCont').scrollTop = $('#mBoxCont').prop('scrollHeight');
					reCalcBar();
					isDown = true;
				},
				up:function(){
					document.getElementById('mBoxCont').scrollTop = 0;
					reCalcBar();
					isDown = false;
				},
				slide:function(){
					if(isDown){
						scroll.down();
					}
				},
				init:function(){
					enableButtons();
					if(options.get(15,'T')=='T'){
						showBar();
					}else{
						showButtons();
					}
					if(options.get(16,'F')=='T'){
						enableWheel();
					}
					enableUserlist();
					$('#mBoxCont').scroll(function(e){
						if($('#scrollBar').length !==0 && !$('#scrollBar').data('isClicked')){
							reCalcBar();
						}
					});
					$(document).add(window).add('body').add('html').scroll(function(e){
						e.preventDefault();
					});
				},
				reCalcBar:reCalcBar
			};
		})(),
		wysiwyg = (function(){
			var sel,
				menuOpen = false,
				hideMenu = function(){
					$('#textDecoForm').css('display','none');
					menuOpen = false;
				 };
			return {
				init:function(){
					$('#message').mouseup(function(e){
						sel = window.getSelection();
						if(sel.isCollapsed){
							return;
						}
						e.preventDefault();
						$('#textDecoForm').css({
							display:'block',
							left:Math.max(e.pageX-52,0)
						});
						menuOpen = true;
						
						
					});
					$(document).mousedown(function(e){
						if(!$(e.target).closest('#textDecoForm').length && menuOpen){
							hideMenu();
						}
					})
					$('#textDecoFormBold').click(function(){
						sel.getRangeAt(0).surroundContents($('<b>')[0]);
					});
					$('#textDecoFormItalic').click(function(){
						sel.getRangeAt(0).surroundContents($('<i>')[0]);
					});
					$('#textDecoFormUnderline').click(function(){
						sel.getRangeAt(0).surroundContents($('<u>')[0]);
					});
				},
				getMsg:function(){
					var msg = $('#message').html();
					msg = msg.split('<b>').join('\x02').split('</b>').join('\x02');
					msg = msg.split('<i>').join('\x1d').split('</i>').join('\x1d');
					msg = msg.split('<u>').join('\x1f').split('</u>').join('\x1f');
					msg = msg.split('&nbsp;').join(' ');
					msg = $('<span>').html(msg).text();
					return msg;
				},
				support:function(){
					return (('contentEditable' in document.documentElement) && options.get(18,'F')=='T');
				}
			}
		})(),
		page = (function(){
			var initSmileys = function(){
					if(options.get(12,'T')=='T'){
						$('#smileyMenuButton')
							.css('cursor','pointer')
							.click(function(){
									if($('#smileyselect').css('display')=='block'){
										$('#smileyselect').css('display','none');
										$(this).attr('src','smileys/smiley.gif');
									}else{
										$('#smileyselect').css('display','block');
										$(this).attr('src','smileys/tongue.gif');
									}
							});
					}else{
						$('#smileyMenuButton')
							.attr('src','smileys/smiley_grey.png');
					}
					$('#smileyselect').append(
						$.map(parser.getSmileys(),function(s){
							return [(s.inMenu?($('<img>')
								.attr({
									src:s.pic,
									alt:s.alt,
									title:s.title
								})
								.click(function(){
									if(!wysiwyg.support()){
										replaceText(' '+s.code,$('#message')[0]);
									}else{
										var range = window.getSelection().getRangeAt(0);
										range.deleteContents();
										range.insertNode(document.createTextNode(' '+s.code));
									}
								})):''),' '];
								
						})
					);
				},
				mBoxContWidthOffset = 90,
				registerToggle = function(){
					$('#toggleButton')
						.click(function(e){
							e.preventDefault();
							options.set(5,!(options.get(5,'T')=='T')?'T':'F');
							document.location.reload();
						});
				},
				isBlurred = false,
				init = function(){
					var nua = navigator.userAgent,
						is_android = ((nua.indexOf('Mozilla/5.0') > -1 && nua.indexOf('Android ') > -1 && nua.indexOf('AppleWebKit') > -1) && !(nua.indexOf('Chrome') > -1)),
						is_ios = (nua.match(/(iPod|iPhone|iPad)/i) && nua.match(/AppleWebKit/i)),
						is_mobile_webkit = (nua.match(/AppleWebKit/i) && nua.match(/Android/i)),
						hide_userlist = options.get(14,'F')=='T';
					page.changeLinks();
					if(!wysiwyg.support()){
						$('#message').replaceWith(
							$('<input>')
								.attr({
									'type':'text',
									'id':'message',
									'accesskey':'i',
									'maxlen':'256',
									'autocomplete':'off'
								})
						);
					}else{
						$('#message').keydown(function(e){
							if(e.keyCode==13){
								e.preventDefault();
								$('#sendMessage').trigger('submit');
							}
						});
						wysiwyg.init();
					}
					$('#windowbg2').css('height',parseInt($('html').height(),10) - parseInt($('#message').height() + 14,10));
					$('#mBoxCont').css('height',parseInt($('#windowbg2').height(),10) - 42);
					$(window).resize(function(){
						if(!is_ios){
							var htmlHeight = window.innerHeight,
								htmlWidth = window.innerWidth,
								windowsbg2Height = htmlHeight - parseInt($('#message').height() + 14,10);
							$('#windowbg2').css('height',windowsbg2Height);
							$('#mBoxCont').css('height',windowsbg2Height - 42);
							$('html,body').height(htmlHeight);
							
							$('input#message,span#message').css('width',htmlWidth*(hide_userlist?1:0.91) - 121);
						}
						if(options.get(15,'T')=='T'){
							var widthOffset = (htmlWidth/100)*mBoxContWidthOffset;
							$('#mBoxCont').css('width',widthOffset-22);
							if(is_mobile_webkit){
								$('#scrollBarLine').css('left',widthOffset - 16);
								$('#scrollBar').css('left',widthOffset - 17);
								$('#UserListContainer').css('left',widthOffset);
							}
							scroll.reCalcBar();
						}
						scroll.down();
					}).trigger('resize').blur(function(){
						isBlurred = true;
					}).focus(function(){
						isBlurred = false;
					});
					if(hide_userlist){ // hide userlist is on
						mBoxContWidthOffset = 99;
						$('<style>')
							.append(
								'#scrollBar{left:98%;left:calc(99% - 17px);left:-webkit-calc(99% - 17px);}',
								'#scrollBarLine{left:98%;left:calc(99% - 16px);left:-webkit-calc(99% - 16px);}',
								'input#message,span#message{width:93%;width:calc(100% - 121px);width:-webkit-calc(100% - 121px);}',
								'#mBoxCont{width:99%;}',
								'.arrowButtonHoriz2,.arrowButtonHoriz3 > div:nth-child(2){left:98%;left:calc(99% - 5px);left:-webkit-calc(99% - 5px);}',
								'#UserListContainer{left:99%;transition: left 0.5s 1s;-webkit-transition: left 0.5s 1s;-o-transition-property: left;-o-transition-duration: 0.5d;-o-transition-delay: ls;}',
								'#icons{right:95px;}'
							)
							.appendTo('head');
					}
					scroll.init();
					tab.init();
					instant.init();
					logs.init();
					registerToggle();
					$('#aboutButton').click(function(e){
						e.preventDefault();
						$('#about').toggle();
					});
				};
			return {
				load:function(){
					indicator.start();
					settings.fetch(function(){
						if(options.get(5,'T')=='T'){
							init();
							initSmileys();
							send.init();
							oldMessages.init();
							channels.init();
							ws.init()
							channels.join(options.get(4,String.fromCharCode(45)).charCodeAt(0) - 45);
						}else{
							registerToggle();
							$('#windowbg2').css('height',parseInt($('html').height(),10) - parseInt($('#message').height() + 14,10));
							$('#mBoxCont').css('height',parseInt($('#windowbg2').height(),10) - 42).empty().append(
								'<br>',
								$('<a>')
									.css('font-size',20)
									.text('OmnomIRC is disabled. Click here to enable.')
									.click(function(e){
										e.preventDefault();
										options.set(5,'T');
										window.location.reload();
									})
							);
							indicator.stop();
						}
					});
				},
				isBlurred:function(){
					return isBlurred;
				},
				changeLinks:function(){
					// change links to add network
					$('#adminLink a,a[href="."],a[href="?options"],a[href="index.php"]').each(function(){
						if($(this).attr('href').split('?')[1] !== undefined){
							$(this).attr('href',$(this).attr('href')+'&network='+settings.net());
						}else{
							$(this).attr('href',$(this).attr('href')+'?network='+settings.net());
						}
					});
				}
			};
		})(),
		statusBar = (function(){
			var text = '',
				started = false,
				start = function(){
					if(options.get(11,'T')!='T'){
						return;
					}
					if(!started){
						setInterval(function(){
							window.status = text;
							if(parent){
								try{
									parent.window.status = text;
								}catch(e){}
							}
						},500);
						started = true;
					}
				};
			return {
				set:function(s){
					text = s;
					if(!started){
						start();
					}
				}
			};
		})(),
		commands = (function(){
			return {
				parse:function(s){
					var command = s.split(' ')[0].toLowerCase(),
						parameters = s.substr(command.length+1).toLowerCase().trim();
					switch(command){
						case 'j':
						case 'join':
							channels.openChan(parameters);
							return true;
						case 'q':
						case 'query':
							channels.openPm(parameters,true);
							return true;
						case 'win':
						case 'w':
						case 'window':
							if(parseInt(parameters,10) < channels.getChans().length && parseInt(parameters,10) >= 0){
								channels.join(parseInt(parameters,10));
							}
							return true;
						case 'p':
						case 'part':
							channels.part((parameters!==''?parameters:undefined));
							return true;
						case 'help':
							send.internal('<span style="color:#2A8C2A;">Commands: me, ignore, unignore, ignorelist, join, part, query, msg, window</span>');
							send.internal('<span style="color:#2A8C2A;">For full help go here: <a href="http://ourl.ca/19926" target="_top">http://ourl.ca/19926</a></span>');
							return true;
						case 'ponies':
							var fs=document.createElement("script");
							fs.onload=function(){
								Derpy();
							};
							fs.src="https://juju2143.ca/mousefly.js";
							document.head.appendChild(fs);
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
		})(),
		oldMessages = (function(){
			var messages = [],
				counter = 0,
				current = '',
				setMsg = function(s){
					if(!wysiwyg.support()){
						$('#message').val(s);
					}else{
						$('#message').html(s);
					}
				},
				getMsg = function(){
					if(!wysiwyg.support()){
						return $('#message').val();
					}
					return $('#message').html();
				};
			return {
				init:function(){
					$('#message')
						.keydown(function(e){
							if(e.keyCode==38 || e.keyCode==40){
								e.preventDefault();
								if(counter==messages.length){
									current = getMsg();
								}
								if(messages.length!==0){
									if(e.keyCode==38){ //up
										if(counter!==0){
											counter--;
										}
										setMsg(messages[counter]);
									}else{ //down
										if(counter!=messages.length){
											counter++;
										}
										if(counter==messages.length){
											setMsg(current);
										}else{
											setMsg(messages[counter]);
										}
									}
								}
							}
						});
				},
				add:function(s){
					messages.push(s);
					if(messages.length>20){
						messages.shift();
					}
					counter = messages.length;
					ls.set('oldMessages-'+channels.getCurrent(true,true),messages.join('\n'));
				},
				read:function(){
					var temp = ls.get('oldMessages-'+channels.getCurrent(true,true));
					if(temp!==null){
						messages = temp.split("\n");
					}else{
						messages = [];
					}
					counter = messages.length;
				}
			};
		})(),
		send = (function(){
			var sending = false,
				sendMessage = function(s){
					if(s[0] == '/' && commands.parse(s.substr(1))){
						if(!wysiwyg.support()){
							$('#message').val('');
						}else{
							$('#message').html('');
						}
					}else{
						if(!sending){
							if(ws.use()){
								ws.send({
									action:'message',
									message:s
								})
								if(!wysiwyg.support()){
									$('#message').val('');
								}else{
									$('#message').html('');
								}
							}else{
								sending = true;
								request.cancel();
								network.getJSON('message.php?message='+base64.encode(s)+'&channel='+channels.getCurrent(false,true),function(){
									if(!wysiwyg.support()){
										$('#message').val('');
									}else{
										$('#message').html('');
									}
									request.start();
									sending = false;
								});
							}
							if(s.search('goo.gl/QMET')!=-1 || s.search('oHg5SJYRHA0')!=-1 || s.search('dQw4w9WgXcQ')!=-1){
								$('<div>')
									.css({
										position:'absolute',
										zIndex:39,
										top:39,
										left:0
									})
									.html('<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"><param name="movie" value="http://134.0.27.190/juju/i-lost-the-ga.me/rickroll.swf"><param name="quality" value="high"><embed src="http://134.0.27.190/juju/i-lost-the-ga.me/rickroll.swf" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash"></embed></object>')
									.appendTo('body');
							}
						}
					}
				};
			return {
				internal:function(s){
					parser.addLine({
						curLine:0,
						type:'internal',
						time:Math.floor((new Date()).getTime()/1000),
						name:'',
						message:s,
						name2:'',
						chan:channels.getCurrent()
					});
				},
				init:function(){
					if(settings.loggedIn()){
						$('#sendMessage')
							.submit(function(e){
								var val = '';
								if(!wysiwyg.support()){
									val = $('#message').val();
									
									oldMessages.add(val);
								}else{
									oldMessages.add($('#message').html());
									val = wysiwyg.getMsg();
								}
								e.preventDefault();
								if(!$('#message').attr('disabled') && val!==''){
									sendMessage(val);
								}
							});
					}else{
						$('#message')
							.attr('disabled','true')
							.val('You need to login if you want to chat!');
					}
				}
			};
		})(),
		logs = (function(){
			var isOpen = false,
				year = 0,
				month = 0,
				day = 0,
				months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
				getLogUrlParam = function(){
					return base64.encode(year.toString(10)+'-'+month.toString(10)+'-'+day.toString(10));
				},
				updateInputVal = function(){
					$('#logDate').val(months[month-1]+' '+day.toString(10)+' '+year.toString(10));
				},
				displayDatePicker = function(){
					var d = new Date(year,month,day),
						week = ['Sun','Mon','Tue','Wen','Thu','Fri','Sat'],
						days = (new Date(year,month,0)).getDate(),
						firstDayOfWeek = (new Date(year,month-1,1)).getDay(),
						i = 0;
					if(day > days){
						day = days;
					}
					updateInputVal();
					$('#logDatePicker').empty().append(
						$('<a>').text('<').click(function(e){
							e.preventDefault();
							e.stopPropagation();
							year--;
							displayDatePicker();
						}),' ',year.toString(10),' ',
						$('<a>').text('>').click(function(e){
							e.preventDefault();
							e.stopPropagation();
							year++;
							displayDatePicker();
						}),'<br>',
						$('<a>').text('<').click(function(e){
							e.preventDefault();
							e.stopPropagation();
							month--;
							if(month < 1){
								month = 12;
								year--;
							}
							displayDatePicker();
						}),' ',months[month-1],' ',
						$('<a>').text('>').click(function(e){
							e.preventDefault();
							e.stopPropagation();
							month++;
							if(month > 12){
								month = 1;
								year++;
							}
							displayDatePicker();
						}),'<br>',
						$('<table>').append(
							$('<tr>').append(
								$.map(week,function(v){
									return $('<th>').text(v);
								})
							),
							$.map([0,1,2,3,4,5],function(){
								if(i >= days){
									return;
								}
								return $('<tr>').append(
									$.map([0,1,2,3,4,5,6],function(v){
										if((i == 0 && v!=firstDayOfWeek) || i >= days){
											return $('<td>').text(' ');
										}
										i++;
										return $('<td>').text(i).addClass('logDatePickerDay').addClass(i==day?'current':'').data('day',i).click(function(){
											$('.logDatePickerDay.current').removeClass('current');
											day = $(this).addClass('current').data('day');
											updateInputVal();
										});
									})
								);
							})
						)
					);
					$('#logDatePicker').css('display','block');
				},
				open = function(){
					var d = new Date();
					
					indicator.start();
					request.cancel();
					ws.dissallowRecLines();
					
					$('#message').attr('disabled','true');
					users.setUsers([]); //empty userlist
					users.draw();
					$('#chattingHeader').css('display','none');
					$('#logDatePicker').css('display','none');
					$('#logsHeader').css('display','block');
					
					$('#logChanIndicator').text(channels.getCurrentName());
					
					year = parseInt(d.getFullYear(),10);
					month = parseInt(d.getMonth()+1,10);
					day = parseInt(d.getDate(),10);
					updateInputVal();
					
					isOpen = true;
					fetch();
				},
				close = function(){
					var num;
					
					ws.allowRecLines()
					
					$('#chattingHeader').css('display','block');
					$('#logsHeader').css('display','none');
					$.each(channels.getChans(),function(i,c){
						if(c.chan==channels.getCurrent() || c.id==channels.getCurrent()){
							num = i;
							return false;
						}
					});
					channels.join(num);
					isOpen = false;
				},
				fetchPart = function(n){
					network.getJSON('Log.php?day='+getLogUrlParam()+'&offset='+parseInt(n,10)+'&channel='+channels.getCurrent(false,true),function(data){
						if(!data.banned){
							if(data.lines.length>=1000){
								fetchPart(n+1000);
							}
							$.each(data.lines,function(i,line){
								parser.addLine(line,true);
							});
							scroll.up();
							if(data.lines.length<1000){
								indicator.stop();
							}
						}else{
							send.internal('<span style="color:#C73232;"><b>ERROR:</b> banned</banned>');
						}
					});
				},
				fetch = function(){
					indicator.start();
					
					$('#MessageBox').empty();
					
					fetchPart(0);
				},
				toggle = function(){
					if(isOpen){
						close();
					}else{
						open();
					}
				};
			return {
				init:function(){
					$('#logCloseButton')
						.click(function(e){
							e.preventDefault();
							close();
						});
					$('#logGoButton')
						.click(function(e){
							e.preventDefault();
							fetch();
						});
					$('#logsButton').click(function(e){
						e.preventDefault();
						toggle();
					});
					$('#logDate').click(function(e){
						e.preventDefault();
						$(this).focusout();
						if($('#logDatePicker').css('display')!='block'){
							displayDatePicker();
							e.stopPropagation();
						}
					});
					$(document).click(function(e){
						if(isOpen){
							var $cont = $('#logDatePicker');
							if(!$cont.is(e.target) && $cont.has(e.target).length === 0){
								$cont.css('display','none');
							}
						}
					});
				}
			};
		})();
		parser = (function(){
			var smileys = [],
				maxLines = 200,
				lastMessage = 0,
				ignores = [],
				parseName = function(n,o){
					n = (n=='\x00'?'':n); //fix 0-string bug
					var ne = encodeURIComponent(n);
					n = $('<span>').text(n).html();
					var rcolors = [19,20,22,24,25,26,27,28,29],
						sum = 0,
						i = 0,
						cn = n;
					if(options.get(3,'F')=='T'){
						while(n[i]){
							sum += n.charCodeAt(i++);
						}
						cn = $('<span>').append($('<span>').addClass('uName-'+rcolors[sum %= 9].toString()).html(n)).html();
					}else{
						cn = n;
					}
					if(settings.networks()[o]!==undefined){
						return '<span title="'+settings.networks()[o].name+'">'+settings.networks()[o].normal.split('NICKENCODE').join(ne).split('NICK').join(cn)+'</span>';
					}
					return '<span title="Unknown Network">'+cn+'</span>';
				},
				parseSmileys = function(s){
					var addStuff = '';
					if(!s){
						return '';
					}
					$.each(smileys,function(i,smiley){
						s = s.replace(RegExp(smiley.regex,'g'),smiley.replace.split('ADDSTUFF').join(addStuff).split('PIC').join(smiley.pic).split('ALT').join(smiley.alt));
					});
					return s;
				},
				parseLinks = function(text){
					if (!text || text === null || text === undefined){
						return '';
					}
					//text = text.replace(/http:\/\/www\.omnimaga\.org\//g,"\x01www.omnimaga.org/");
					return text.replace(/(\x01)/g,"")
							.replace(/http:\/\/ourl\.ca\//g,"\x01ourl.ca/")
							.replace(/((h111:\/\/(www\.omnimaga\.org\/|ourl\.ca))[-a-zA-Z0-9@:;%_+.~#?&//=]+)/, '<a target="_top" href="$1">$1</a>')
							.replace(RegExp("(^|.)(((f|ht)(tp|tps):\/\/)[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="$2">$2</a>')
							.replace(RegExp("(^|\\s)(www\\.[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="http://$2">$2</a>')
							.replace(RegExp("(^|.)\x01([^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_top" href="http://$2">http://$2</a>');
				},
				parseColors = function(colorStr){
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
									s = arrayResults[i+1].replace(/^([0-9]{1,2}).*/,'$1');
									textDecoration.fg = s;
									if(s == arrayResults[i+1]){
										arrayResults[i+1] = '';
									}else{
										arrayResults[i+1] = arrayResults[i+1].substr(s.length);
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
									'<span class="fg-'+textDecoration.fg+' bg-'+textDecoration.bg+'" style="'+(textDecoration.bold?'font-weight:bold;':'')+(textDecoration.underline?'text-decoration:underline;':'')+(textDecoration.italic?'font-style:italic;':'')+'">';
						}else{
							colorStr+=arrayResults[i];
						}
					}
					colorStr += '</span>';
					/*Strip codes*/
					colorStr = colorStr.replace(/(\x03|\x02|\x1F|\x09|\x0F)/g,'');
					return colorStr;
				},
				parseHighlight = function(s){
					if(s.toLowerCase().indexOf(settings.nick().toLowerCase().substr(0,parseInt(options.get(13,'3'),10)+1)) >= 0 && settings.nick() != ''){
						var style = '';
						if(options.get(2,'T')!='T'){
							style += 'background:none;padding:none;border:none;';
						}
						if(options.get(1,'T')=='T'){
							style += 'font-weight:bold;';
						}
						return '<span class="highlight" style="'+style+'">'+s+'</span>';
					}
					return s;
				},
				parseMessage = function(s,noSmileys){
					if(noSmileys==undefined || !noSmileys){
						noSmileys = false;
					}
					s = (s=="\x00"?'':s); //fix 0-string bug
					s = $('<span>').text(s).html();
					s = parseLinks(s);
					if(options.get(12,'T')=='T' && noSmileys===false){
						s = parseSmileys(s);
					}
					s = parseColors(s);
					return s;
				},
				lineHigh = false;
			return {
				addLine:function(line,logMode){
					if(line.name === null || line.name === undefined || line.type === null || ignores.indexOf(line.name.toLowerCase()) > -1){
						return false;
					}
					var $mBox = $('#MessageBox'),
						name = parseName(line.name,line.network),
						message = parseMessage(line.message),
						tdName = '*',
						tdMessage = message,
						addLine = true,
						statusTxt = '';
					if(line.network == -1){
						addLine = false;
					}
					if((line.type == 'message' || line.type == 'action') && line.name.toLowerCase() != 'new'){
						tdMessage = message = parseHighlight(message);
					}
					if(line.curLine > request.getCurLine()){
						request.setCurLine(line.curLine);
					}
					switch(line.type){
						case 'reload':
							addLine = false;
							if(logMode!==true && channels.getCurrent()!==''){
								var num;
								$.each(channels.getChans(),function(i,c){
									if(c.chan==channels.getCurrent() || c.id==channels.getCurrent()){
										num = i;
										return false;
									}
								});
								channels.join(num);
								return false;
							}
							break;
						case 'relog':
							settings.fetch(undefined,true);
							return false;
							break;
						case 'refresh':
							location.reload();
							return false;
							break;
						case 'join':
							tdMessage = [name,' has joined '+channels.getCurrentName()];
							if(logMode!==true){
								users.add({
									nick:line.name,
									network:line.network
								});
							}
							if(addLine && settings.networks()[line.network].type==1 && options.get(17,'F')=='F'){
								addLine = false;
							}
							break;
						case 'part':
							tdMessage = [name,' has left '+channels.getCurrentName()+' (',message,')'];
							if(logMode!==true){
								users.remove({
									nick:line.name,
									network:line.network
								});
							}
							if(addLine && settings.networks()[line.network].type==1 && options.get(17,'F')=='F'){
								addLine = false;
							}
							break;
						case 'quit':
							tdMessage = [name,' has quit IRC (',message,')'];
							if(logMode!==true){
								users.remove({
									nick:line.name,
									network:line.network
								});
							}
							if(line.network==1){
								addLine = false;
							}
							break;
						case 'kick':
							tdMessage = [name,' has kicked ',parseName(line.name2,line.network),' from '+channels.getCurrentName()+' (',message,')'];
							if(logMode!==true){
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
										message[i] = parseName(v,line.network);
									}
								});
								message = message.join(' ');
							}
							tdMessage = [name,' set '+channels.getCurrentName()+' mode ',message];
							break;
						case 'nick':
							tdMessage = [name,' has changed nicks to ',parseName(line.name2,line.network)];
							if(logMode!==true){
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
							topic.set(parseMessage(line.message,true));
							tdMessage = [name,' has changed the topic to ',parseMessage(line.message,true)];
							break;
						case 'pm':
							if(channels.getCurrentName(true).toLowerCase() == '*'+line.name.toLowerCase() || channels.getCurrentName(true).toLowerCase() == '*'+line.chan.toLowerCase()){
								tdName = name;
								line.type = 'message';
							}else{
								if(channels.getCurrent()!=='' && logMode!==true){
									if(line.name.toLowerCase() == settings.nick().toLowerCase()){
										addLine = false;
										channels.openPm(line.chan);
									}else{
										tdName = ['(PM)',name];
										channels.openPm(line.name);
										notification.make('(PM) <'+line.name+'> '+line.message,line.chan);
									}
								}else{
									addLine = false;
								}
							}
							break;
						case 'pmaction':
							if(channels.getCurrentName(true).toLowerCase() == '*'+line.name.toLowerCase() || channels.getCurrentName(true).toLowerCase() == '*'+line.chan.toLowerCase()){
								tdMessage = [name,' ',message];
								line.type = 'action';
							}else{
								if(channels.getCurrent()!=='' && logMode!==true){
									if(line.name.toLowerCase() == settings.nick().toLowerCase()){
										addLine = false;
										channels.openPm(line.chan);
									}else{
										tdMessage = ['(PM)',name,' ',message];
										channels.openPm(line.name);
										notification.make('* (PM)'+line.name+' '+line.message,line.chan);
										line.type = 'pm';
									}
								}else{
									addLine = false;
								}
							}
							break;
						case 'highlight':
							if(line.name.toLowerCase() != 'new'){
								notification.make('('+line.chan+') <'+line.name+'> '+line.message,line.chan);
							}
							addLine = false;
							break;
						case 'internal':
							tdMessage = line.message;
							break;
						case 'server':
							break;
						default:
							addLine = false;
					}
					if(addLine){
						if(($mBox.find('tr').length>maxLines) && logMode!==true){
							$mBox.find('tr:first').remove();
						}
						
						if($('<span>').append(tdName).text() == '*'){
							statusTxt = $('<span>').append(tdName).text()+' ';
						}else{
							statusTxt = '<'+line.name+'> ';
						}
						if(options.get(10,'F')=='T'){
							statusTxt = '['+(new Date(line.time*1000)).toLocaleTimeString()+'] '+statusTxt;
						}
						statusTxt += $('<span>').append(tdMessage).text();
						statusBar.set(statusTxt);
						$mBox.append(
							$('<tr>')
								.css({
									width:'100%',
									height:1
								})
								.addClass((options.get(6,'T')=='T' && (lineHigh = !lineHigh)?'lineHigh':''))
								.addClass(((new Date(lastMessage)).getDay()!=(new Date(line.time*1000)).getDay())?'seperator':'') //new day indicator
								.append(
									(options.get(10,'F')=='T'?$('<td>')
										.addClass('irc-date')
										.append('['+(new Date(line.time*1000)).toLocaleTimeString()+']'):''),
									$('<td>')
										.addClass('name')
										.append(tdName),
									$('<td>')
										.addClass(line.type)
										.append(tdMessage)
								)
						).find('img').load(function(e){
							scroll.slide();
						});
						scroll.slide();
						
						lastMessage = line.time*1000;
					}
					return true;
				},
				setSmileys:function(s){
					smileys = s;
				},
				getSmileys:function(){
					return smileys;
				},
				setIgnoreList:function(a){
					ignores = a;
				}
			};
		})();
	$(document).ready(function(){
		network.init();
		switch($('body').attr('page')){
			case 'options':
				settings.fetch(function(){
					page.changeLinks();
					$('#options').height($(window).height() - 75);
					$(window).resize(function(){
						if(!(navigator.userAgent.match(/(iPod|iPhone|iPad)/i) && navigator.userAgent.match(/AppleWebKit/i))){
							$('#options').height($(window).height() - 75);
						}
					});
					$('#options').append(options.getHTML());
				});
				break;
			case 'admin':
				admin.init();
				break;
			//case 'main': // no need, already caught by default.
			default:
				page.load();
		}
	});
	return {
		OMNOMIRCSERVER:OMNOMIRCSERVER,
		page:{
			changeLinks:function(){
				page.changeLinks();
			}
		},
		settings:{
			fetch:function(fn){
				settings.fetch(fn);
			}
		},
		indicator:{
			start:function(){
				indicator.start();
			},
			stop:function(){
				indicator.stop();
			}
		},
		network:{
			getJSON:function(s,fn,async,urlparams){
				network.getJSON(s,fn,async,urlparams);
			},
			post:function(s,data,fn,async,urlparams){
				network.post(s,data,fn,async,urlparams);
			}
		},
		options:{
			getFullOptionsString:function(){
				return options.getFullOptionsString();
			}
		}
	}
})();