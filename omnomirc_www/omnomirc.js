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
	var settings = {
			hostname:'',
			nick:'',
			signature:'',
			numHigh:4,
			uid:0,
			checkLoginUrl:'',
			networks:[],
			fetch:function(fn){
				var self = this;
				$.getJSON('config.php?js',function(data){
					self.hostname = data.hostname;
					channels.channels = data.channels;
					parser.smileys = data.smileys;
					self.networks = data.networks;
					self.checkLoginUrl = data.checkLoginUrl;
					$.getJSON(self.checkLoginUrl+'&jsoncallback=?',function(data){
						self.nick = data.nick;
						self.signature = data.signature;
						self.uid = data.uid;
						if(fn!=undefined){
							fn();
						}
					});
				});
			},
			getUrlParams:function(){
				var self = this;
				return 'nick='+base64.encode(self.nick)+'&signature='+base64.encode(self.signature)+'&time='+(new Date).getTime().toString()+'&id='+self.uid;
			}
		},
		ls = {
			getCookie:function(c_name){
				var i,x,y,ARRcookies=document.cookie.split(";");
				for (i=0;i<ARRcookies.length;i++) {
					x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
					y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
					x=x.replace(/^\s+|\s+$/g,"");
					if (x==c_name) {
						return unescape(y);
						}
				}
			},
			setCookie:function(c_name,value,exdays) {
				var exdate=new Date();
				exdate.setDate(exdate.getDate() + exdays);
				var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
				document.cookie=c_name + "=" + c_value;
			},
			support:function(){
				try{
					return 'localStorage' in window && window['localStorage'] !== null;
				}catch(e){
					return false;
				}
			},
			get:function(name){
				if(this.support()){
					return localStorage.getItem(name);
				}
				return this.getCookie(name);
			},
			set:function(name,value){
				if(this.support()){
					localStorage.setItem(name,value);
				}else{
					this.setCookie(name,value,30);
				}
			}
		},
		options = {
			set:function(optionsNum,value){
				var self = this;
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
				self.refreshCache = true;
			},
			refreshCache:true,
			cache:'',
			get:function(optionsNum,defaultOption){
				var self = this,
					optionsString = (self.refreshCache?self.cache=ls.get('OmnomIRCSettings'):self.cache),
					result;
				self.refreshCache = false;
				if(optionsString==null){
					return defaultOption;
				}
				result = optionsString.charAt(optionsNum-1);
				if(result=='-'){
					return defaultOption;
				}
				return result;
			},
			options:[
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
					handler:function(self){
						return $('<td>')
							.attr('colspan',2)
							.css('border-right','none')
							.append($('<select>')
								.change(function(){
									self.set(13,this.value);
								})
								.append(
									$.map([0,1,2,3,4,5,6,7,8,9],function(i){
										return $('<option>')
											.attr((self.get(13,'3')==i?'selected':'false'),'selected')
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
				}
			],
			getHTML:function(){
				var self = this;
				return $.merge($.map([false,true],function(alternator){
						return $('<table>')
							.addClass('optionsTable')
							.append(
								$.map(self.options,function(o){
									return (alternator = !alternator?$('<tr>')
										.append(
											$.merge(
											[$('<td>')
												.text(o.disp)],
											(o.handler===undefined?[
											$('<td>')
												.addClass('option '+(self.get(o.id,o.defaultOption)=='T'?'selected':''))
												.text('Yes')
												.click(function(){
													if(self.get(o.id,o.defaultOption)=='F'){
														if((o.before!==undefined && o.before()) || o.before===undefined){
															self.set(o.id,'T');
															$(this).addClass('selected').next().removeClass('selected');
														}
													}
												}),
											$('<td>')
												.addClass('option '+(self.get(o.id,o.defaultOption)=='F'?'selected':''))
												.text('No')
												.click(function(){
													if(self.get(o.id,o.defaultOption)=='T'){
														self.set(o.id,'F');
														$(this).addClass('selected').prev().removeClass('selected');
													}
												})]:o.handler(self)))
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
		},
		instant = {
			id:'',
			init:function(){
				var self = this;
				self.id = Math.random().toString(36)+(new Date()).getTime().toString();
				ls.set('OmnomBrowserTab',self.Id);
				$(window)
					.focus(function(){
						ls.set('OmnomBrowserTab',self.Id);
					})
			},
			current:function(){
				var self = this;
				return self.id == ls.get('OmnomBrowserTab');
			}
		},
		indicator = {
			instant:false,
			$elem:false,
			pixels:[],
			start:function(){
				var self = this;
				if(!self.instant){
					self.pixels = [true,true,true,true,true,false,false,false];
					self.$elem = $('<div>')
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
					self.instant = setInterval(function(){
						self.$elem.empty().append(
							$.map(self.pixels,function(p){
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
						var temp = self.pixels[0];
						for(i=1;i<=7;i++){
							self.pixels[(i-1)] = self.pixels[i];
						}
						self.pixels[7] = temp;
					},50);
				}
			},
			stop:function(){
				var self = this;
				if(self.instant){
					clearInterval(self.intant);
					self.instant = false;
					self.$elem.remove();
				}
			}
		},
		notification = {
			support:function(){
				if((window.webkitNotifications!=undefined && window.webkitNotifications!=null && window.webkitNotifications) || (typeof Notification!=='undefined' && Notification && Notification.permission!=='denied')){
					return true;
				}
				return false;
			},
			request:function(){
				var self = this;
				if(window.webkitNotifications!=undefined && window.webkitNotifications!=null && window.webkitNotifications){
					window.webkitNotifications.requestPermission(function(){
						if (window.webkitNotifications.checkPermission() == 0){
							self.show('Notifications Enabled!');
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
							self.show('Notifications Enabled!');
							options.set(7,'T');
							document.location.reload();
						}
					});
				}else{
					alert('Your browser doesn\'t support notifications');
				}
			},
			show:function(s){
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
			},
			make:function(s,c){
				var self = this;
				if(instant.current()){
					if(options.get(7,'F')=='T'){
						self.show(s);
					}
					if(options.get(8,'F')=='T'){
						$('#ding')[0].play();
					}
				}
				if(c!=channels.current){
					channels.highlight(c);
				}
			}
		},
		request = {
			errorCount:0,
			curLine:0,
			inRequest:false,
			handler:false,
			cancle:function(){
				var self = this;
				if(self.inRequest){
					self.inRequest = false;
					self.handler.abort();
				}
			},
			send:function(){
				var self = this;
				self.inRequest = true;
				self.handler = $.getJSON('Update.php?high='+(options.get(13,'3')+1).toString()+'&channel='+base64.encode(channels.current)+'&lineNum='+self.curLine.toString()+'&'+settings.getUrlParams(),function(data){
					var newRequest = true;
					self.errorCount = 0;
					if(data.lines!==undefined){
						$.each(data.lines,function(i,line){
							return newRequest = parser.addLine(line);
						});
					}
					if(newRequest){
						setTimeout(function(){
							self.send();
						},100);
					}
				}).fail(function(){
					self.errorCount++;
					if(self.errorCount>=10){
						send.internal('<span style="color:#C73232;">OmnomIRC has lost connection to server. Please refresh to reconnect.</span>');
					}else if(!self.inRequest){
						self.errorCount = 0;
					}else{
						self.send();
					}
				});
			}
		},
		channels = {
			channels:[],
			current:'',
			highlight:function(c){
				var self = this;
				$.each(self.channels,function(i,ci){
					if(ci.chan==c){
						$('#chan'+i.toString()).addClass('highlightChan');
					}
				});
			},
			openChan:function(s){
				var self = this,
					addChan = true;
				s = s.trim();
				if(s.substr(0,1) != '@' && s.substr(0,1) != '#'){
					s = '@' + s;
				}
				s = s.toLowerCase();
				$.each(self.channels,function(i,c){
					if(c.chan==s){
						addChan = i;
					}
				});
				if(addChan===true){
					if(s.substr(0,1)=='#'){
						send.internal('<span style="color:#C73232;"> Join Error: Cannot join new channels starting with #.</span>');
						return;
					}
					self.channels.push({
						chan:s,
						high:false,
						ex:false
					});
					self.save();
					self.draw();
					self.join(self.channels.length-1);
				}else{
					self.join(addChan);
				}
			},
			openPm:function(s,join){
				var self = this,
					addChan = true;
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
				$.each(self.channels,function(i,c){
					if(c.chan==s){
						addChan = i;
					}
				});
				if(addChan===true){
					self.channels.push({
						chan:s,
						high:!join,
						ex:false
					});
					self.save();
					self.draw();
					if(join){
						self.join(self.channels.length-1);
					}
				}else{
					self.channels[addChan].high = !join;
					if(join){
						self.join(addChan);
					}
				}
			},
			part:function(i){
				var self = this,
					select = false;
				if(i===undefined){
					$.each(self.channels,function(ci,c){
						if(c.chan == self.current){
							i = ci;
						}
					});
				}
				if(isNaN(parseInt(i))){
					$.each(self.channels,function(ci,c){
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
				if(self.channels[i].chan.substr(0,1)=='#'){
					send.internal('<span style="color:#C73232;"> Part Error: I cannot part '+self.channels[i].chan+'. (IRC channel.)</span>');
					return;
				}
				if(self.channels[i].chan == self.current){
					select = true;
				}
				self.channels.splice(i,1);
				self.save();
				self.draw();
				if(select){
					self.join(i-1);
				}
			},
			save:function(){
				var self = this,
					chanList = '';
				$.each(self.channels,function(i,c){
					if(c.chan.substr(0,1) != '#'){
						chanList += base64.encode(c.chan)+'%';
					}
				});
				chanList = chanList.substr(0,chanList.length-1);
				ls.set('OmnomChannels',chanList);
			},
			load:function(){
				var self = this,
					chanList = ls.get('OmnomChannels');
				if(chanList!=null && chanList!=''){
					$.each(chanList.split('%'),function(i,c){
						self.channels.push({
							chan:base64.decode(c),
							high:false,
							ex:false
						});
					});
				}
			},
			draw:function(){
				var self = this;
				$('#ChanList').empty().append(
					$.map(self.channels,function(c,i){
						if((c.ex && options.get(9,'F')=='T') || !c.ex){
							return $('<div>')
								.attr('id','chan'+i.toString())
								.addClass('chanList'+(c.high?' highlightChan':''))
								.append(
									$('<span>')
										.addClass('chan '+(c.chan==self.current?' curchan':''))
										.append(
											(c.chan.substr(0,1)!='#'?
											$('<span>')
												.addClass('closeButton')
												.css({
													width:9,
													float:'left'
												})
												.click(function(){
													self.part(i);
												})
												.text('x')
											:''),
											$('<span>').text(c.chan)
										)
										.click(function(){
											var _self = this;
											self.join(i);
										})
								)
						}
					})
				);
			},
			inChan:false,
			join:function(i,fn,override){
				var self = this;
				if(self.channels[i]!==undefined && (self.inChan||override)){
					indicator.start();
					request.cancle();
					self.inChan = false;
					$('#MessageBox').empty();
					$('.chan').removeClass('curchan');
					$.getJSON('Load.php?count=125&channel='+base64.encode(self.channels[i].chan)+'&'+settings.getUrlParams(),function(data){
						self.current = self.channels[i].chan;
						oldMessages.read();
						options.set(4,String.fromCharCode(i+45));
						if(!data.banned){
							if(data.admin){
								$('#adminLink').css('display','block');
							}else{
								$('#adminLink').css('display','none');
							}
							users.users = data.users;
							users.draw();
							$.each(data.lines,function(i,line){
								parser.addLine(line);
							});
							request.send();
						}else{
							send.internal('<span style="color:#C73232;"><b>ERROR:</b> banned</banned>');
						}
						$('#chan'+i.toString()).removeClass('highlightChan').find('.chan').addClass('curchan');
						if(fn!==undefined){
							fn();
						}
						setTimeout(function(){
							scroll.down();
						},500);
						self.inChan = true;
						indicator.stop();
					});
				}
			}
		},
		tab = {
			tabWord:'',
			tabCount:0,
			isInTab:false,
			startPos:0,
			startChar:'',
			endPos:0,
			endChar:'',
			endPos0:0,
			tabAppendStr:' ',
			getCurrentWord:function(){
				var self = this,
					message = $('#message')[0];
				if(self.isInTab){
					return self.tabWord;
				}
				self.startPos = self.endPos = message.selectionStart;
				self.startChar = message.value.charAt(self.startPos)
				while(self.startChar != ' ' && --self.startPos > 0){
					self.startChar = message.value.charAt(self.startPos);
				}
				if(self.startChar == ' '){
					self.startPos++;
				}
				self.endChar = message.value.charAt(self.endPos);
				while(self.endChar != ' ' && ++self.endPos <= message.value.length){
					self.endChar = message.value.charAt(self.endPos);
				}
				self.endPos0 = self.endPos;
				return message.value.substr(self.startPos,self.endPos - self.startPos).trim();
			},
			getTabComplete:function(){
				var self = this,
					message = $('#message')[0],
					name;
				if(!self.isInTab){
					self.tabAppendStr = ' ';
					self.startPos = message.selectionStart;
					self.startChar = message.value.charAt(self.startPos);
					while(self.startChar != ' ' && --self.startPos > 0){
						self.startChar = message.value.charAt(self.startPos);
					}
					if(self.startChar == ' '){
						self.startChar+=2;
					}
					if(self.startPos==0){
						self.tabAppendStr = ': ';
					}
					self.endPos = message.selectionStart;
					self.endChar = message.value.charAt(self.endPos);
					while(self.endChar != ' ' && ++self.endPos <= message.value.length){
						self.endChar = message.value.charAt(self.endPos);
					}
					if(self.endChar == ' '){
						self.endChar-=2;
					}
				}
				name = users.search(self.getCurrentWord(),self.tabCount);
				if(name == self.getCurrentWord()){
					self.tabCount = 0;
					name = users.search(self.getCurrentWord(),self.tabCount);
				}
				message.value = message.value.substr(0,self.startPos)+name+self.tabAppendStr+message.value.substr(self.endPos+1);
				self.endPos = self.endPos0+name.length;
			},
			registerHook:function(){
				var self = this;
				$('#message')
					.keydown(function(e){
						if(e.keyCode == 9){
							if(e.preventDefault){
								e.preventDefault();
							}
							self.tabWord = self.getCurrentWord();
							self.getTabComplete();
							self.tabCount++;
							self.isInTab = true;
							setTimeout(1,1);
						}else{
							self.tabWord = '';
							self.tabCount = 0;
							self.isInTab = false;
						}
					})
			}
		},
		users = {
			users:[],
			search:function(start,startAt){
				var self = this,parts,
					res = false;
				if(!startAt){
					startAt = 0;
				}
				$.each(self.users,function(i,u){
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
				var self = this;
				if(channels.inChan){
					self.users.push(u);
					self.draw();
				}
			},
			remove:function(u){
				var self = this;
				if(channels.inChan){
					$.each(self.users,function(i,us){
						if(us.nick == u.nick && us.network == u.network){
							self.users.splice(i,1);
							return false;
						}
					});
					self.draw();
				}
			},
			draw:function(){
				var self = this;
				self.users.sort(function(a,b){
					var al=a.nick.toLowerCase(),bl=b.nick.toLowerCase();
					return al==bl?(a==b?0:a<b?-1:1):al<bl?-1:1;
				});
				$('#UserList').empty().append(
					$.map(self.users,function(u){
						var getInfo,
							ne = encodeURIComponent(u.nick),
							n = $('<span>').text(u.nick).html();
						return $('<span>')
							.attr('alt',(settings.networks[u.network]!==undefined?settings.networks[u.network].name:'Unknown Network'))
							.append(
								(settings.networks[u.network]!==undefined?settings.networks[u.network].userlist.split('NICKENCODE').join(ne).split('NICK').join(n):n),
								'<br>'
							)
							.mouseover(function(){
								getInfo = $.get('Load.php?userinfo&name='+base64.encode(u.nick)+'&chan='+base64.encode(channels.current)+'&online='+u.network.toString(),function(data){
									if(!isNaN(parseInt(data))){
										$('#lastSeenCont').text('Last Seen: '+(new Date(parseInt(data)*1000)).toLocaleString());
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
			}
		},
		topic = {
			current:'',
			set:function(t){
				var self = this;
				$('#topic').empty().append(t);
			}
		},
		scroll = {
			isDown:false,
			down:function(){
				var self = this;
				document.getElementById('mBoxCont').scrollTop = $('#mBoxCont').prop('scrollHeight');
				self.isDown = true;
			},
			slide:function(){
				var self = this;
				if(self.isDown){
					self.down();
				}
			},
			enableWheel:function(){
				var self = this;
				$('#mBoxCont').bind('DOMMouseScroll mousewheel',function(e){
					if(e.preventDefault){
						e.preventDefault();
					}
					self.isDown = false;
					document.getElementById('mBoxCont').scrollTop = Math.min(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight,Math.max(0,document.getElementById('mBoxCont').scrollTop-(/Firefox/i.test(navigator.userAgent)?(e.originalEvent.detail*(-20)):(e.originalEvent.wheelDelta/2))));
					if(document.getElementById('mBoxCont').scrollTop==(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight)){
						self.isDown = true;
					}
					if(options.get(15,'T')=='T'){
						self.reCalcBar();
					}
				});
			},
			reCalcBar:function(){
				if($('#scrollBar').length!=0){
					$('#scrollBar').css('top',(document.getElementById('mBoxCont').scrollTop/(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight))*($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight-38)+38);
				}
			},
			enableUserlist:function(){
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
			showBar:function(){
				var self = this,
					mouseMoveFn = function(e){
						var y = e.clientY;
						if($('#scrollBar').data('isClicked')){
							$('#scrollBar').css('top',parseInt($('#scrollBar').css('top'))+(y-$('#scrollBar').data('prevY')));
							document.getElementById('mBoxCont').scrollTop = ((parseInt($('#scrollBar').css('top'))-38)/($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight-38))*(document.getElementById('mBoxCont').scrollHeight-document.getElementById('mBoxCont').clientHeight);
							self.isDown = false;
							if(parseInt($('#scrollBar').css('top'))<38){
								$('#scrollBar').css('top',38);
								document.getElementById('mBoxCont').scrollTop = 0;
							}
							if(parseInt($('#scrollBar').css('top'))>($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight)){
								$('#scrollBar').css('top',$('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight);
								document.getElementById('mBoxCont').scrollTop =  $('#mBoxCont').prop('scrollHeight')-$('#mBoxCont')[0].clientHeight;
								self.isDown = true;
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
			showButtons:function(){
				var self = this,downIntM,upIntM;
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
									self.isDown = false;
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
										self.isDown = true;
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
			}
		},
		page = {
			initSmileys:function(){
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
					$.map(parser.smileys,function(s){
						return [$('<img>')
							.attr({
								src:s.pic,
								alt:s.alt,
								title:s.title
							})
							.click(function(){
								replaceText(' '+s.code,$('#message')[0]);
							}),' ']
							
					})
				);
			},
			mBoxContWidthOffset:99,
			init:function(){
				var self = this;
				$(window).resize(function(){
					$('#windowbg2').css('height',parseInt($('html').height()) - parseInt($('#message').height() + 14));
					$('#mBoxCont').css('height',parseInt($('#windowbg2').height()) - 42);
					if(options.get(15,'T')=='T'){
						$('#mBoxCont').css('width',((document.body.offsetWidth/100)*self.mBoxContWidthOffset)-22);
						scroll.reCalcBar();
					}
					scroll.down();
				}).trigger('resize');
				if(options.get(14,'F')!='T'){
					self.mBoxContWidthOffset = 90;
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
				if(options.get(15,'T')=='T'){
					scroll.showBar();
				}else{
					scroll.showButtons();
				}
				if(options.get(16,'F')=='T'){
					scroll.enableWheel();
				}
				scroll.enableUserlist();
				tab.registerHook();
				$('#toggleButton')
					.click(function(e){
						e.preventDefault();
						options.set(5,!(options.get(5,'T')=='T')?'T':'F');
						document.location.reload();
					})
			}
		},
		statusBar = {
			text:'',
			started:false,
			start:function(){
				var self = this;
				if(options.get(11,'T')!='T'){
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
				var self = this;
				self.text = s;
				if(!self.started){
					self.start();
				}
			}
		},
		commands = {
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
						if(parseInt(parameters) < channels.channels.length && parseInt(parameters) >= 0){
							channels.join(parseInt(parameters));
						}
						return true;
					case 'p':
					case 'part':
						channels.part((parameters!=''?parameters:undefined));
						return true;
					case 'help':
						send.internal('<span style="color:#2A8C2A;">For full help go here: <a href="http://ourl.ca/17329" target="_top">http://ourl.ca/17329</a></span>');
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
		},
		oldMessages = {
			messages:[],
			counter:0,
			current:'',
			add:function(s){
				var self = this;
				self.messages.push(s);
				if(self.messages.length>20){
					self.messages.shift();
				}
				self.counter = self.messages.length;
				ls.set('oldMessages-'+base64.encode(channels.current),self.messages.join("\n"));
			},
			read:function(){
				var self = this,
					temp = ls.get('oldMessages-'+base64.encode(channels.current));
					
				if(temp!=null){
					self.messages = temp.split("\n");
				}else{
					self.messages = [];
				}
				self.counter = self.messages.length;
			},
			registerHook:function(){
				var self = this;
				$('#message')
					.keydown(function(e){
						if(e.keyCode==38 || e.keyCode==40){
							e.preventDefault();
							if(self.counter==self.messages.length){
								self.current = $(this).val();
							}
							if(self.messages.length!=0){
								if(e.keyCode==38){ //up
									if(self.counter!=0){
										self.counter--;
									}
									$(this).val(self.messages[self.counter]);
								}else{ //down
									if(self.counter!=self.messages.length){
										self.counter++;
									}
									if(self.counter==self.messages.length){
										$(this).val(self.current);
									}else{
										$(this).val(self.messages[self.counter]);
									}
								}
							}
						}
					});
			}
		},
		send = {
			internal:function(s){
				parser.addLine({
					curLine:0,
					type:'internal',
					time:Math.floor((new Date()).getTime()/1000),
					name:'',
					message:s,
					name2:'',
					chan:channels.current
				});
			},
			send:function(s){
				oldMessages.add(s);
				if(s[0] == '/' && commands.parse(s.substr(1))){
					$('#message').val('');
				}else{
					$.get('message.php?message='+base64.encode(s)+'&channel='+base64.encode(channels.current)+'&'+settings.getUrlParams(),function(){
						$('#message').val('');
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
			},
			registerHook:function(){
				var self = this;
				if(settings.nick!='Guest'){
					$('#sendMessage')
						.submit(function(e){
							e.preventDefault();
							self.send(this.message.value);
						});
				}else{
					$('#message')
						.attr('disabled','true')
						.val('You need to login if you want to chat!');
				}
			}
		},
		parser = {
			smileys:[],
			maxLines:200,
			parseName:function(n,o){
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
				if(settings.networks[o]!==undefined){
					return settings.networks[o].normal.split('NICKENCODE').join(ne).split('NICK').join(cn);
				}
				return cn;
			},
			parseSmileys:function(s){
				var self = this,
					addStuff = '';
				if(!s){
					return '';
				}
				var addStuff = '';
				$.each(self.smileys,function(i,smiley){
					s = s.replace(RegExp(smiley.regex),smiley.replace.split('ADDSTUFF').join(addStuff).split('PIC').join(smiley.pic).split('ALT').join(smiley.alt));
				});
				return s;
			},
			parseLinks:function(text){
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
			parseColors:function(colorStr){
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
			parseMessage:function(s){
				var self = this;
				s = $('<span>').text(s).html();
				s = self.parseLinks(s);
				if(options.get(12,'T')=='T'){
					s = self.parseSmileys(s);
				}
				s = self.parseColors(s);
				return s;
			},
			parseHighlight:function(s){
				if(s.toLowerCase().indexOf(settings.nick.toLowerCase().substr(0,options.get(13,'3')+1)) >= 0 && settings.nick != "Guest"){
					var style = '';
					if(options.get(2,'T')!='T'){
						style += 'background:none;padding:none;border:none;';
					}
					if(options.get(1,'T')=='T'){
						style += '"font-weight:bold;';
					}
					return '<span class="highlight" style="'+style+'">'+s+'</span>';
				}
				return s;
			},
			lineHigh:false,
			addLine:function(line){
				var self = this,
					$mBox = $('#MessageBox'),
					name = self.parseName(line.name,line.network),
					message = self.parseMessage(line.message),
					tdName = '*',
					tdMessage = message,
					addLine = true,
					statusTxt = '';
				if((line.type == 'message' || line.type == 'action') && line.name.toLowerCase() != 'new'){
					message = self.parseHighlight(message,line);
				}
				if(line.curLine > request.curLine){
					request.curLine = line.curLine;
				}
				if($mBox.find('tr').length>self.maxLines){
					$mBox.find('tr').slice(1).remove();
				}
				switch(line.type){
					case 'reload':
						addLine = false;
						if(channels.inChan){
							var num;
							$.each(channels.channels,function(i,c){
								if(c.chan==channels.current){
									num = i;
									return false;
								}
							});
							channels.join(num);
							return false;
						}
						break;
					case 'join':
						tdMessage = [name,' has joined '+channels.current];
						users.add({
							nick:line.name,
							network:line.network
						});
						if(line.network==1){
							addLine = false;
						}
						break;
					case 'part':
						tdMessage = [name,' has left '+channels.current+' (',message,')'];
						users.remove({
							nick:line.name,
							network:line.network
						});
						if(line.network==1){
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
						tdMessage = [name,' has kicked ',self.parseName(line.name2,line.network),' from '+channels.current+' (',message,')'];
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
						tdMessage = [name,' set '+channels.current+' mode ',message];
						break;
					case 'nick':
						tdMessage = [name,' has changed nicks to ',self.parseName(line.name2,line.network)];
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
						if(channels.inChan){
							if(channels.current.toLowerCase() != '*'+line.nick.toLowerCase() && line.nick != settings.nick){
								tdName = ['(PM)',name];
								channels.openPm(line.nick);
								notification.make('(PM) <'+name+'> '+message,line.chan);
							}else{
								tdName = name;
							}
						}else{
							addLine = false;
						}
						break;
					case 'pmaction':
						if(channels.current.toLowerCase() != '*'+line.nick.toLowerCase() && line.nick != settings.nick){
							tdMessage = ['(PM)',name,' ',message];
							channels.openPm(line.nick);
							notification.make('* (PM)'+name+' '+message,line.chan);
						}else{
							tdMessage = [name,' ',message];
						}
					case 'highlight':
						if(line.name.toLowerCase() != 'new'){
							notification.make('('+line.chan+') <'+name+'> '+message,line.chan);
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
						statusTxt = '<'+line.nick+'> ';
					}
					if(options.get(10,'F')=='T'){
						statusTxt = '['+(new Date(line.time*1000)).toLocaleTimeString()+'] '+statusTxt;
					}
					statusTxt = statusTxt + $('<span>').append(tdMessage).text();
					statusBar.set(statusTxt);
					$mBox.append(
						$('<tr>')
							.css({
								width:'100%',
								height:1
							})
							.addClass((options.get(6,'T')=='T' && (self.lineHigh = !self.lineHigh)?'lineHigh':''))
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
					);
					scroll.slide();
				}
				return true;
			}
		};
	$(document).ready(function(){
		if($('#mBoxCont').length!=0){
			indicator.start();
			if(options.get(5,'T')=='T'){
				page.init();
				settings.fetch(function(){
					page.initSmileys();
					send.registerHook();
					oldMessages.registerHook();
					channels.load();
					channels.join(options.get(4,String.fromCharCode(45)).charCodeAt(0) - 45,function(){
						channels.draw();
					},true);
				});
			}else{
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
			}
		}else{
			$('#options').append(options.getHTML());
		}
	});
})(jQuery);