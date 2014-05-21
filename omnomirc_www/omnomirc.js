/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2014 Sorunome

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
(function($){
	var settings = (function(){
			var hostname = '',
				nick = '',
				signature = '',
				numHigh = 4,
				uid = 0,
				checkLoginUrl = '',
				networks = [];
			return {
				fetch:function(fn){
					$.getJSON('config.php?js',function(data){
						hostname = hostname;
						channels.setChans(data.channels);
						parser.setSmileys(data.smileys);
						networks = data.networks;
						checkLoginUrl = data.checkLoginUrl;
						options.setDefaults(data.defaults);
						$.getJSON(checkLoginUrl+'&jsoncallback=?',function(data){
							nick = data.nick;
							signature = data.signature;
							uid = data.uid;
							if(fn!=undefined){
								fn();
							}
						});
					});
				},
				getUrlParams:function(){
					return 'nick='+base64.encode(nick)+'&signature='+base64.encode(signature)+'&time='+(new Date).getTime().toString()+'&id='+uid;
				},
				networks:function(){
					return networks;
				},
				nick:function(){
					return nick;
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
					var exdate=new Date();
					exdate.setDate(exdate.getDate() + exdays);
					var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
					document.cookie=c_name + "=" + c_value;
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
		admin = (function(){
			var sendEdit = function(page,json){
					$.post('admin.php?set='+page+'&'+settings.getUrlParams(),{data:JSON.stringify(json)},function(data){
						var alertStr = '';
						if(data.errors!==undefined){
							$.map(data.errors,function(error){
								alertStr += 'ERROR: '+error+"\n";
							});
						}else{
							alertStr = data.message;
						}
						alert(alertStr);
					});
				},
				getInputBoxSettings = function(p,name,data){
					$('#adminContent').append(
						'<div style="font-weight:bold">'+name+' Settings</div>',
						$.map(data,function(d,i){
							if(i!=='warnings'){
								return $('<div>')
									.append(
										i,
										': ',
										$('<input>')
											.attr({
												type:'text',
												name:i
											})
											.val(d)
									);
							}
						}),
						$('<button>')
							.text('submit')
							.click(function(){
								var json = {};
								$('input').each(function(i,v){
									json[$(v).attr('name')] = $(v).val();
								});
								sendEdit(p,json);
							})
					);
				},
				getJSONEditSettings = function(p,name,data){
					var json = data;
					$('#adminContent').append(
						'<div style="font-weight:bold">'+name+' Settings</div>',
						$('<div>').addClass('json-editor').jsonEditor(json,{
							change:function(data){
								json = data;
							}
						}),
						$('<button>')
							.text('submit')
							.click(function(){
								sendEdit(p,json);
							})
					);
				},
				loadPage = function(p){
					indicator.start();
					$('#adminContent').text('Loading...');
					$.getJSON('admin.php?get='+encodeURIComponent(p)+'&'+settings.getUrlParams(),function(data){
						$('#adminContent').empty();
						if(data.errors!==undefined){
							$('#adminContent').append(
								$.map(data.errors,function(error){
									return '<b>ERROR:</b> '+error;
								})
							);
						}else{
							switch(p){
								case 'index':
									if(!data.installed){
										$('#adminContent').append('<span class="highlight">Warning: You are currently in instalation mode!</span><br>',
											$('<button>')
												.text('Install')
												.click(function(){
													sendEdit('install',{});
												})
											,'<br>');
									}
									$('#adminContent').append(
										'OmnomIRC Version: '+data.version+'<br>',
										$('<button>')
											.text('Back up config')
											.click(function(){
												sendEdit('backupConfig',{});
											})
									);
									break;
								case 'channels':
									getJSONEditSettings(p,'Channel',data.channels);
									break;
								case 'hotlinks':
									getJSONEditSettings(p,'Hotlink',data.hotlinks);
									break;
								case 'smileys':
									getJSONEditSettings(p,'Smiley',data.smileys);
									break;
								case 'networks':
									getJSONEditSettings(p,'Network',data.networks);
									break;
								case 'sql':
									$.extend(data,{
										passwd:''
									});
									getInputBoxSettings(p,'SQL',data);
									break;
								case 'op':
									getJSONEditSettings(p,'OP',data.opGroups);
									break;
								case 'irc':
									getJSONEditSettings(p,'IRC',data.irc);
									break;
								case 'misc':
									getInputBoxSettings(p,'Misc',data);
									break;
							}
						}
						indicator.stop();
					});
				};
			return {
				init:function(){
					$('#adminNav a').click(function(e){
						e.preventDefault();
						loadPage($(this).attr('page'));
					});
					settings.fetch(function(){
						loadPage('index');
					});
					$(window).resize(function(){
						$('#adminContent').height($(window).height() - 50);
					}).trigger('resize');
				}
			};
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
							alert("-READ THIS-\nNot all extra channels are owned and controlled by Omnimaga. We cannot be held liable for the content of them.\n\nBy using them, you agree to be governed by the rules inside them.\n\nOmnimaga rules still apply for OmnomIRC communication.");
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
												.text(i+1)
										})
									)
								 )
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
					}
				];
			return {
				setDefaults:function(d){
					defaults = d;
				},
				set:function(optionsNum,value){
					if(optionsNum < 1 || optionsNum > 40){
						return;
					}
					var optionsString = ls.get('OmnomIRCSettings');
					if(optionsString==null){
						ls.set('OmnomIRCSettings','----------------------------------------');
						optionsString = ls.get('OmnomIRCSettings');
					}
					optionsString = optionsString.substring(0,optionsNum-1)+value+optionsString.substring(optionsNum);
					ls.set('OmnomIRCSettings',optionsString);
					refreshCache = true;
				},
				get:function(optionsNum,defaultOption){
					var optionsString = (refreshCache?cache=ls.get('OmnomIRCSettings'):cache),
						result;
					refreshCache = false;
					if(optionsString==null){
						return defaultOption;
					}
					result = optionsString.charAt(optionsNum-1);
					if(result=='-'){
						return (defaults.charAt(optionsNum-1)!=''?defaults.charAt(optionsNum-1):defaultOption);
					}
					return result;
				},
				getHTML:function(){
					return $.merge($.map([false,true],function(alternator){
							return $('<table>')
								.addClass('optionsTable')
								.append(
									$.map(optionMenu,function(o){
										return (alternator = !alternator?$('<tr>')
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
											):'')
									})
								)
					}),
					$('<div>').append(
						'&nbsp;',
						$('<a>')
							.text('Reset Defaults')
							.click(function(e){
								e.preventDefault();
								ls.set('OmnomIRCSettings','----------------------------------------');
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
										})
								})
							);
							var temp = pixels[0];
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
			var support = function(){
					if((window.webkitNotifications!=undefined && window.webkitNotifications!=null && window.webkitNotifications) || (typeof Notification!=='undefined' && Notification && Notification.permission!=='denied')){
						return true;
					}
					return false;
				},
				show = function(s){
					if(window.webkitNotifications!=undefined && window.webkitNotifications!=null && window.webkitNotifications && window.webkitNotifications.checkPermission() == 0){
						var n = window.webkitNotifications.createNotification('http://www.omnimaga.org/favicon.ico','OmnomIRC Highlight',s);
						n.show();
					}else if(typeof Notification!=='undefined' && Notification && Notification.permission==='granted'){
						var n = new Notification('OmnomIRC Highlight',{
							icon:'http://www.omnimaga.org/favicon.ico',
							body:s
						});
						n.onshow = function(){ 
							setTimeout(n.close,30000); 
						}
					}
				};
			return {
				request:function(){
					if(window.webkitNotifications!=undefined && window.webkitNotifications!=null && window.webkitNotifications){
						window.webkitNotifications.requestPermission(function(){
							if (window.webkitNotifications.checkPermission() == 0){
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
					}
					if(c!=channels.getCurrent()){
						channels.highlight(c);
					}
				}
			};
		})(),
		request = (function(){
			var errorCount = 0,
				curLine = 0,
				inRequest = false,
				handler = false;
			return {
				cancle:function(){
					if(inRequest){
						inRequest = false;
						handler.abort();
					}
				},
				send:function(){
					inRequest = true;
					handler = $.getJSON('Update.php?high='+(parseInt(options.get(13,'3'))+1).toString()+'&channel='+base64.encode(channels.getCurrent())+'&lineNum='+curLine.toString()+'&'+settings.getUrlParams(),function(data){
						var newRequest = true;
						errorCount = 0;
						if(data.lines!==undefined){
							$.each(data.lines,function(i,line){
								return newRequest = parser.addLine(line);
							});
						}
						if(newRequest){
							setTimeout(function(){
								request.send();
							},(page.isBlurred()?2500:200));
						}
					}).fail(function(){
						errorCount++;
						if(errorCount>=10){
							send.internal('<span style="color:#C73232;">OmnomIRC has lost connection to server. Please refresh to reconnect.</span>');
						}else if(!inRequest){
							errorCount = 0;
						}else{
							setTimeout(function(){
								request.send();
							},(page.isBlurred()?2500:200));
						}
					});
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
				save = function(){
					var chanList = '';
					$.each(chans,function(i,c){
						if(c.chan.substr(0,1) != '#'){
							chanList += base64.encode(c.chan)+'%';
						}
					});
					chanList = chanList.substr(0,chanList.length-1);
					ls.set('OmnomChannels',chanList);
				},
				draw = function(){
					$('#ChanList').empty().append(
						$.map(chans,function(c,i){
							if((c.ex && options.get(9,'F')=='T') || !c.ex){
								return $('<div>')
									.attr('id','chan'+i.toString())
									.addClass('chanList'+(c.high?' highlightChan':''))
									.append(
										$('<span>')
											.addClass('chan '+(c.chan==current?' curchan':''))
											.append(
												(c.chan.substr(0,1)!='#'?
												$('<span>')
													.addClass('closeButton')
													.css({
														width:9,
														float:'left'
													})
													.click(function(){
														channels.part(i);
													})
													.text('x')
												:''),
												$('<span>').text(c.chan)
											)
											.click(function(){
												channels.join(i);
											})
									)
							}
						})
					);
				},
				requestHandler = false;
			return {
				highlight:function(c){
					$.each(chans,function(i,ci){
						if(ci.chan==c){
							$('#chan'+i.toString()).addClass('highlightChan');
						}
					});
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
							ex:false
						});
						save();
						draw();
						channels.join(chans.length-1);
					}else{
						channels.join(addChan);
					}
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
							ex:false
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
				},
				part:function(i){
					var select = false;
					if(i===undefined){
						$.each(chans,function(ci,c){
							if(c.chan == current){
								i = ci;
							}
						});
					}
					if(isNaN(parseInt(i))){
						$.each(chans,function(ci,c){
							if(c.chan == i){
								i = ci;
							}
						});
					}
					if(isNaN(parseInt(i)) || i===undefined){
						send.internal('<span style="color:#C73232;"> Part Error: I cannot part '+i+'. (You are not in it.)</span>');
						return;
					}
					i = parseInt(i);
					if(chans[i].chan.substr(0,1)=='#'){
						send.internal('<span style="color:#C73232;"> Part Error: I cannot part '+chans[i].chan+'. (IRC channel.)</span>');
						return;
					}
					if(chans[i].chan == current){
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
						request.cancle();
						$('#message').attr('disabled','true');
						$('#MessageBox').empty();
						$('.chan').removeClass('curchan');
						if(requestHandler!==false){
							requestHandler.abort();
						}
						requestHandler = $.getJSON('Load.php?count=125&channel='+base64.encode(chans[i].chan)+'&'+settings.getUrlParams(),function(data){
							current = chans[i].chan;
							oldMessages.read();
							options.set(4,String.fromCharCode(i+45));
							if(!data.banned){
								if(data.admin){
									$('#adminLink').css('display','block');
								}else{
									$('#adminLink').css('display','none');
								}
								users.setUsers(data.users);
								users.draw();
								$.each(data.lines,function(i,line){
									parser.addLine(line);
								});
								requestHandler = false;
								request.send();
							}else{
								send.internal('<span style="color:#C73232;"><b>ERROR:</b> banned</banned>');
								requestHandler = false;
							}
							$('#chan'+i.toString()).removeClass('highlightChan').find('.chan').addClass('curchan');
							if(fn!==undefined){
								fn();
							}
							if(settings.nick()!='Guest'){
								$('#message').removeAttr('disabled');
							}
							indicator.stop();
						});
					}
				},
				getCurrent:function(override){
					if(requestHandler===false || override){
						return current;
					}
					return '';
				},
				init:function(){
					var chanList = ls.get('OmnomChannels');
					if(chanList!=null && chanList!=''){
						$.each(chanList.split('%'),function(i,c){
							chans.push({
								chan:base64.decode(c),
								high:false,
								ex:false
							});
						});
					}
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
				getCurrentWord = function(){
					var message = $('#message')[0];
					if(isInTab){
						return tabWord;
					}
					startPos = endPos = message.selectionStart;
					startChar = message.value.charAt(startPos)
					while(startChar != ' ' && --startPos > 0){
						startChar = message.value.charAt(startPos);
					}
					if(startChar == ' '){
						startPos++;
					}
					endChar = message.value.charAt(endPos);
					while(endChar != ' ' && ++endPos <= message.value.length){
						endChar = message.value.charAt(endPos);
					}
					endPos0 = endPos;
					return message.value.substr(startPos,endPos - startPos).trim();
				},
				getTabComplete = function(){
					var message = $('#message')[0],
						name;
					if(!isInTab){
						tabAppendStr = ' ';
						startPos = message.selectionStart;
						startChar = message.value.charAt(startPos);
						while(startChar != ' ' && --startPos > 0){
							startChar = message.value.charAt(startPos);
						}
						if(startChar == ' '){
							startChar+=2;
						}
						if(startPos==0){
							tabAppendStr = ': ';
						}
						endPos = message.selectionStart;
						endChar = message.value.charAt(endPos);
						while(endChar != ' ' && ++endPos <= message.value.length){
							endChar = message.value.charAt(endPos);
						}
						if(endChar == ' '){
							endChar-=2;
						}
					}
					name = users.search(getCurrentWord(),tabCount);
					if(name == getCurrentWord()){
						tabCount = 0;
						name = users.search(getCurrentWord(),tabCount);
					}
					message.value = message.value.substr(0,startPos)+name+tabAppendStr+message.value.substr(endPos+1);
					endPos = endPos0+name.length;
				};
			return {
				init:function(){
					$('#message')
						.keydown(function(e){
							if(e.keyCode == 9){
								if(e.preventDefault){
									e.preventDefault();
								}
								tabWord = getCurrentWord();
								getTabComplete();
								tabCount++;
								isInTab = true;
								setTimeout(1,1);
							}else{
								tabWord = '';
								tabCount = 0;
								isInTab = false;
							}
						});
				}
			}
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
				search:function(start,startAt){
					var res = false;
					if(!startAt){
						startAt = 0;
					}
					$.each(usrs,function(i,u){
						if(u.nick.toLowerCase().indexOf(start.toLowerCase()) == 0 && startAt-- <= 0 && res === false){
							res = u.nick;
						}
					});
					if(res!==false){
						return res;
					}
					return start;
				},
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
									getInfo = $.getJSON('Load.php?userinfo&name='+base64.encode(u.nick)+'&chan='+base64.encode(channels.getCurrent())+'&online='+u.network.toString(),function(data){
										if(data.last){
											$('#lastSeenCont').text('Last Seen: '+(new Date(data.last*1000)).toLocaleString());
										}else{
											$('#lastSeenCont').text('Last Seen: never');
										}
										$('#lastSeenCont').css('display','block');
									});
								})
								.mouseout(function(){
									try{
										getInfo.abort();
									}catch(e){};
									$('#lastSeenCont').css('display','none');
								})
						}),
						'<br><br>'
					);
				},
				setUsers:function(u){
					usrs = u;
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
				enableWheel = function(){
					$('#mBoxCont').bind('DOMMouseScroll mousewheel',function(e){
						e.preventDefault();
						e.stopPropagation();
						e.cancleBubble = true;
						isDown = false;
						document.getElementById('mBoxCont').scrollTop = Math.min(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight,Math.max(0,document.getElementById('mBoxCont').scrollTop-(/Firefox/i.test(navigator.userAgent)?(e.originalEvent.detail*(-20)):(e.originalEvent.wheelDelta/2))));
						if(document.getElementById('mBoxCont').scrollTop==(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight)){
							isDown = true;
						}
						if(options.get(15,'T')=='T'){
							reCalcBar();
						}
					});
				},
				reCalcBar = function(){
					if($('#scrollBar').length!=0){
						$('#scrollBar').css('top',(document.getElementById('mBoxCont').scrollTop/(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight))*($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight-38)+38);
					}
				},
				enableUserlist = function(){
					$('#UserList')
						.css('top',0)
						.bind('DOMMouseScroll mousewheel',function(e){
							if(e.preventDefault){
								e.preventDefault();
							}
							e = e.originalEvent;
							$(this).css('top',Math.min(0,Math.max(((/Opera/i.test(navigator.userAgent))?-30:0)+document.getElementById('UserListInnerCont').clientHeight-this.scrollHeight,parseInt(this.style.top)+(/Firefox/i.test(navigator.userAgent)?(e.detail*(-20)):(e.wheelDelta/2)))));
						})
						
				},
				showBar = function(){
					var mouseMoveFn = function(e){
							var y = e.clientY;
							if($('#scrollBar').data('isClicked')){
								$('#scrollBar').css('top',parseInt($('#scrollBar').css('top'))+(y-$('#scrollBar').data('prevY')));
								document.getElementById('mBoxCont').scrollTop = ((parseInt($('#scrollBar').css('top'))-38)/($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight-38))*(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight);
								isDown = false;
								if(parseInt($('#scrollBar').css('top'))<38){
									$('#scrollBar').css('top',38);
									document.getElementById('mBoxCont').scrollTop = 0;
								}
								if(parseInt($('#scrollBar').css('top'))>($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight)){
									$('#scrollBar').css('top',$('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight);
									document.getElementById('mBoxCont').scrollTop =  $('#mBoxCont').prop('scrollHeight')-$('#mBoxCont')[0].clientHeight;
									isDown = true;
								}
							}
							$('#scrollBar').data('prevY',y);
						},
						mouseDownFn = function(){
							$('#scrollBar').data('isClicked',true);
							$('#scrollArea').css('display','block');
						},
						mouseUpFn = function(){
							$('#scrollBar').data('isClicked',false);
							$('#scrollArea').css('display','none');
						},
						$bar = $('<div>').attr('id','scrollBar').data({prevY:0,isClicked:false}).appendTo('body')
							.mousemove(function(e){
								mouseMoveFn(e);
							})
							.mousedown(function(){
								mouseDownFn();
							})
							.mouseup(function(){
								mouseUpFn();
							});
					$bar.css('top',$('body')[0].offsetHeight-$bar[0].offsetHeight);
					$('<div>')
						.attr('id','scrollArea')
						.css({
							display:'none',
							width:'100%',
							height:'100%',
							position:'absolute',
							left:0,
							top:0,
							zIndex:100
						})
						.mousemove(function(e){
							mouseMoveFn(e);
						})
						.mouseup(function(){
							mouseUpFn();
						})
						.mouseout(function(){
							mouseUpFn();
						})
						.appendTo('body');
					$('<div>')
						.attr('id','scrollBarLine')
						.appendTo('body');
					$(window).trigger('resize');
				},
				showButtons = function(){
					var downIntM,upIntM;
					$('<span>')
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
									downIntM = setInterval(function(){
										document.getElementById('mBoxCont').scrollTop -= 9;
										isDown = false;
									},50);
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
						).appendTo('body');
					$('<span>')
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
									upIntM = setInterval(function(){
										document.getElementById('mBoxCont').scrollTop += 9;
										if(document.getElementById('mBoxCont').scrollTop+document.getElementById('mBoxCont').clientHeight==document.getElementById('mBoxCont').scrollHeight){
											isDown = true;
										}
									},50);
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
						).appendTo('body');
				};
			return {
				down:function(){
					document.getElementById('mBoxCont').scrollTop = $('#mBoxCont').prop('scrollHeight');
					isDown = true;
				},
				slide:function(){
					if(isDown){
						scroll.down();
					}
				},
				init:function(){
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
						reCalcBar();
					});
					$(document).add(window).add('body').add('html').scroll(function(e){
						e.preventDefault();
					});
				},
				reCalcBar:reCalcBar
			};
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
									replaceText(' '+s.code,$('#message')[0]);
								})):''),' ']
								
						})
					);
				},
				mBoxContWidthOffset = 99,
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
					$(window).resize(function(){
						$('#windowbg2').css('height',parseInt($('html').height()) - parseInt($('#message').height() + 14));
						$('#mBoxCont').css('height',parseInt($('#windowbg2').height()) - 42);
						if(options.get(15,'T')=='T'){
							$('#mBoxCont').css('width',((document.body.offsetWidth/100)*mBoxContWidthOffset)-22);
							scroll.reCalcBar();
						}
						scroll.down();
					}).trigger('resize').blur(function(){
						isBlurred = true;
					}).focus(function(){
						isBlurred = false;
					});
					if(options.get(14,'F')!='T'){
						mBoxContWidthOffset = 90;
						$('<style>')
							.append(
								'#scrollBar{left:89%;left:calc(90% - 17px);}',
								'#scrollBarLine{left:89%;left:calc(90% - 16px);}',
								'#message{width:82%;width:calc(91% - 115px);width:-webkit-calc(91% - 115px);}',
								'#mBoxCont{width:90%;}',
								'.arrowButtonHoriz2,.arrowButtonHoriz3 > div:nth-child(2){left:89%;left:calc(90% - 5px);left:-webkit-calc(90% - 5px);}',
								'#UserListContainer{left:90%;height:100%;transition:none;-webkit-transition:none;-o-transition-property:none;-o-transition-duration:none;-o-transition-delay:none;}'
							)
							.appendTo('head');
					}
					scroll.init();
					tab.init();
					instant.init();
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
							channels.join(options.get(4,String.fromCharCode(45)).charCodeAt(0) - 45);
						}else{
							registerToggle();
							$('#windowbg2').css('height',parseInt($('html').height()) - parseInt($('#message').height() + 14));
							$('#mBoxCont').css('height',parseInt($('#windowbg2').height()) - 42).empty().append(
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
				}
			}
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
							if(parseInt(parameters) < channels.getChans().length && parseInt(parameters) >= 0){
								channels.join(parseInt(parameters));
							}
							return true;
						case 'p':
						case 'part':
							channels.part((parameters!=''?parameters:undefined));
							return true;
						case 'help':
							send.internal('<span style="color:#2A8C2A;">Commands: me, ignore, unignore, ignorelist, join, part, query, msg, window</span>');
							send.internal('<span style="color:#2A8C2A;">For full help go here: <a href="http://ourl.ca/19926" target="_top">http://ourl.ca/19926</a></span>');
							return true;
						case 'ponies':
							var fs=document.createElement("script");fs.onload=function(){
								Derpy();
							};
							fs.src="http://juju2143.ca/mousefly.js";
							document.head.appendChild(fs);
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
				current = '';
			return {
				init:function(){
					$('#message')
						.keydown(function(e){
							if(e.keyCode==38 || e.keyCode==40){
								e.preventDefault();
								if(counter==messages.length){
									current = $(this).val();
								}
								if(messages.length!=0){
									if(e.keyCode==38){ //up
										if(counter!=0){
											counter--;
										}
										$(this).val(messages[counter]);
									}else{ //down
										if(counter!=messages.length){
											counter++;
										}
										if(counter==messages.length){
											$(this).val(current);
										}else{
											$(this).val(messages[counter]);
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
					ls.set('oldMessages-'+base64.encode(channels.getCurrent()),messages.join("\n"));
				},
				read:function(){
					var temp = ls.get('oldMessages-'+base64.encode(channels.getCurrent(true)));
					if(temp!=null){
						messages = temp.split("\n");
					}else{
						messages = [];
					}
					counter = messages.length;
				}
			}
		})(),
		send = (function(){
			var sending = false,
				sendMessage = function(s){
					oldMessages.add(s);
					if(s[0] == '/' && commands.parse(s.substr(1))){
						$('#message').val('');
					}else{
						if(!sending){
							sending = true;
							request.cancle();
							$.getJSON('message.php?message='+base64.encode(s)+'&channel='+base64.encode(channels.getCurrent())+'&'+settings.getUrlParams(),function(){
								$('#message').val('');
								request.send();
								sending = false;
							});
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
					if(settings.nick()!='Guest'){
						$('#sendMessage')
							.submit(function(e){
								e.preventDefault();
								if(!$('#message').attr('disabled') && $('#message').val()!=''){
									sendMessage(this.message.value);
								}
							});
					}else{
						$('#message')
							.attr('disabled','true')
							.val('You need to login if you want to chat!');
					}
				}
			}
		})(),
		parser = (function(){
			var smileys = [],
				maxLines = 200,
				parseName = function(n,o){
					var ne = encodeURIComponent(n);
					n = $('<span>').text(n).html();
					var rcolors = [19,20,22,24,25,26,27,28,29],
						sum = i = 0,
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
					var addStuff = '';
					$.each(smileys,function(i,smiley){
						s = s.replace(RegExp(smiley.regex,'g'),smiley.replace.split('ADDSTUFF').join(addStuff).split('PIC').join(smiley.pic).split('ALT').join(smiley.alt));
					});
					return s;
				},
				parseLinks = function(text){
					text = text.replace(/(\x01)/g,"");
					if (!text || text == null || text == undefined){
						return;
					}
					//text = text.replace(/http:\/\/www\.omnimaga\.org\//g,"\x01www.omnimaga.org/");
					text = text.replace(/http:\/\/ourl\.ca\//g,"\x01ourl.ca/");
					text = text.replace(/((h111:\/\/(www\.omnimaga\.org\/|ourl\.ca))[-a-zA-Z0-9@:;%_+.~#?&//=]+)/, '<a target="_top" href="$1">$1</a>');
					text = text.replace(RegExp("(^|.)(((f|ht)(tp|tps):\/\/)[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="$2">$2</a>');
					text = text.replace(RegExp("(^|\\s)(www\\.[^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_blank" href="http://$2">$2</a>');
					text = text.replace(RegExp("(^|.)\x01([^\\s\x02\x03\x0f\x16\x1d\x1f]*)","g"),'$1<a target="_top" href="http://$2">http://$2</a>');
					return text;
				},
				parseColors = function(colorStr){
					var arrayResults = [],
						isBool = false,
						numSpan = 0,
						isItalic = false,
						isUnderline = false,
						s,
						colorStrTemp = '1,0';
					if(!colorStr){
						return '';
					}
					colorStr = colorStr.split("\x16\x16").join('')+"\x0f";
					arrayResults = colorStr.split(RegExp("([\x02\x03\x0f\x16\x1d\x1f])"));
					colorStr='';
					for(var i=0;i<arrayResults.length;i++){
						switch(arrayResults[i]){
							case "\x03":
								for (var j=0;j<numSpan;j++)
									colorStr+="</span>";
								numSpan=1;
								i++;
								colorStrTemp = arrayResults[i];
								s=arrayResults[i].replace(/^([0-9]{1,2}),([0-9]{1,2})/g,"<span class=\"fg-$1\"><span class=\"bg-$2\">");
								if(s==arrayResults[i]){
									s=arrayResults[i].replace(/^([0-9]{1,2})/g,"<span class=\"fg-$1\">");
								}else{
									numSpan++;
								}
								colorStr+=s;
								break;
							case "\x02":
								isBool = !isBool;
								if (isBool){
									colorStr+="<b>";
								}else{
									colorStr+="</b>";
								}
								break;
							case "\x1d":
								isItalic = !isItalic;
								if(isItalic){
									colorStr+="<i>";
								}else{
									colorStr+="</i>";
								}
								break;
							case "\x16":
								for(var j=0;j<numSpan;j++)
									colorStr+="</span>";
								numSpan=2;
								var stemp;
								s=colorStrTemp.replace(/^([0-9]{1,2}),([0-9]{1,2}).+/g,"<span class=\"fg-$2\"><span class=\"bg-$1\">");
								stemp=colorStrTemp.replace(/^([0-9]{1,2}),([0-9]{1,2}).+/g,"$2,$1");
								if(s==colorStrTemp){
									s=colorStrTemp.replace(/^([0-9]{1,2}).+/g,"<span class=\"fg-0\"><span class=\"bg-$1\">");
									stemp=colorStrTemp.replace(/^([0-9]{1,2}).+/g,"0,$1");
								}
								colorStrTemp = stemp;
								colorStr+=s;
								break;
							case "\x1f":
								isUnderline = !isUnderline;
								if(isUnderline){
									colorStr+="<u>";
								}else{
									colorStr+="</u>";
								}
								break;
							case "\x0f":
								if(isUnderline){
									colorStr+="</u>";
									isUnderline=false;
								}
								if(isItalic){
									colorStr+="</i>";
									isItalic=false;
								}
								if(isBool){
									colorStr+="</b>"
									isBool = false;
								}
								for(var j=0;j<numSpan;j++)
									colorStr+="</span>";
								numSpan=0;
								break;
							default:
								colorStr+=arrayResults[i];
						}
					}
					/*Strip codes*/
					colorStr = colorStr.replace(/(\x03|\x02|\x1F|\x09|\x0F)/g,"");
					return colorStr;
				},
				parseHighlight = function(s){
					if(s.toLowerCase().indexOf(settings.nick().toLowerCase().substr(0,parseInt(options.get(13,'3'))+1)) >= 0 && settings.nick() != "Guest"){
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
				parseMessage = function(s){
					s = $('<span>').text(s).html();
					s = parseLinks(s);
					if(options.get(12,'T')=='T'){
						s = parseSmileys(s);
					}
					s = parseColors(s);
					return s;
				},
				lineHigh = false;
			return {
				addLine:function(line){
					var $mBox = $('#MessageBox'),
						name = parseName(line.name,line.network),
						message = parseMessage(line.message),
						tdName = '*',
						tdMessage = message,
						addLine = true,
						statusTxt = '';
					if((line.type == 'message' || line.type == 'action') && line.name.toLowerCase() != 'new'){
						tdMessage = message = parseHighlight(message);
					}
					if(line.curLine > request.getCurLine()){
						request.setCurLine(line.curLine);
					}
					if($mBox.find('tr').length>maxLines){
						$mBox.find('tr:first').remove();
					}
					switch(line.type){
						case 'reload':
							addLine = false;
							if(channels.getCurrent()!==''){
								var num;
								$.each(channels.getChans(),function(i,c){
									if(c.chan==channels.getCurrent()){
										num = i;
										return false;
									}
								});
								channels.join(num);
								return false;
							}
							break;
						case 'join':
							tdMessage = [name,' has joined '+channels.getCurrent()];
							users.add({
								nick:line.name,
								network:line.network
							});
							if(line.network==1 && options.get(17,'F')=='F'){
								addLine = false;
							}
							break;
						case 'part':
							tdMessage = [name,' has left '+channels.getCurrent()+' (',message,')'];
							users.remove({
								nick:line.name,
								network:line.network
							});
							if(line.network==1 && options.get(17,'F')=='F'){
								addLine = false;
							}
							break;
						case 'quit':
							tdMessage = [name,' has quit IRC (',message,')'];
							users.remove({
								nick:line.name,
								network:line.network
							});
							if(line.network==1){
								addLine = false;
							}
							break;
						case 'kick':
							tdMessage = [name,' has kicked ',parseName(line.name2,line.network),' from '+channels.getCurrent()+' (',message,')'];
							users.remove({
								nick:line.name2,
								network:line.network
							});
							break;
						case 'message':
							tdName = name;
							break;
						case 'action':
							tdMessage = [name,' ',message];
							break;
						case 'mode':
							if(typeof(message)=='string'){
								message = message.split(' ');
								$.each(message,function(i,v){
									var n = $('<span>').html(v).text();
									if(n.indexOf('+')==-1 && n.indexOf('-')==-1){
										message[i] = parseName(v,line.network);
									}
								});
								message = message.join(' ');
							}
							tdMessage = [name,' set '+channels.getCurrent()+' mode ',message];
							break;
						case 'nick':
							tdMessage = [name,' has changed nicks to ',parseName(line.name2,line.network)];
							users.add({
								nick:line.name2,
								network:line.network
							});
							users.remove({
								nick:line.name,
								network:line.network
							});
							break;
						case 'topic':
							topic.set(message);
							tdMessage = [name,' has changed the topic to ',message];
							if(line.network==-1){
								addLine = false;
							}
							break;
						case 'pm':
							if(channels.getCurrent().toLowerCase() != '*'+line.name.toLowerCase() && line.name != settings.nick()){
								if(channels.getCurrent()!==''){
									tdName = ['(PM)',name];
									channels.openPm(line.name);
									notification.make('(PM) <'+line.name+'> '+line.message,line.chan);
								}else{
									addLine = false;
								}
							}else{
								tdName = name;
								line.type = 'message';
							}
							break;
						case 'pmaction':
							if(channels.getCurrent().toLowerCase() != '*'+line.name.toLowerCase() && line.name != settings.nick()){
								if(channels.getCurrent()!==''){
									tdMessage = ['(PM)',name,' ',message];
									channels.openPm(line.name);
									notification.make('* (PM)'+line.name+' '+line.message,line.chan);
									line.type = 'pm';
								}else{
									addLine = false;
								}
							}else{
								tdMessage = [name,' ',message];
								line.type = 'message';
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
					}
					return true;
				},
				setSmileys:function(s){
					smileys = s;
				},
				getSmileys:function(){
					return smileys;
				}
			};
		})();
	$(document).ready(function(){
		switch($('body').attr('page')){
			case 'options':
				settings.fetch(function(){
					$('#options').append(options.getHTML());
				});
				break;
			case 'admin':
				admin.init();
				break;
			case 'main':
			default:
				page.load();
		}
	});
})(jQuery);