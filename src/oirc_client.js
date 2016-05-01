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
var oirc;
(function(){
	oirc = OmnomIRC();
	var  indicator = (function(){
			var self = {
				interval:false,
				$elem:false,
				start:function(){
					if(self.interval===false){
						var pixels = [true,true,true,true,true,false,false,false];
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
						self.interval = setInterval(function(){
							self.$elem.empty().append(
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
					if(self.interval!==false){
						clearInterval(self.interval);
						self.interval = false;
						self.$elem.remove();
					}
				}
			};
			return {
				start:self.start,
				stop:self.stop
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
								show('Notifications Enabled!');
								options.set(7,true);
								document.location.reload();
							}
						});
					}else if(self.support()){
						Notification.requestPermission(function(status){
							if (Notification.permission !== status){
								Notification.permission = status;
							}
							if(status==='granted'){
								show('Notifications Enabled!');
								options.set(7,true);
								document.location.reload();
							}
						});
					}else{
						alert('Your browser doesn\'t support notifications');
					}
				},
				make:function(s,c){
					if(instant.current()){
						if(oirc.options.get('browserNotifications')){
							self.show(s);
						}
						if(oirc.options.get('ding')){
							$('#ding')[0].play();
						}
						if(c!=channels.current().handler){
							channels.highlight(c,true);
						}
					}else{
						if(c!=channels.current().handler){
							channels.highlight(c);
						}
					}
					self.startFlash();
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
							ls.set('stopFlash',Math.random().toString(36)+(new Date()).getTime().toString());
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
					});
				}
			};
			return {
				request:self.request,
				make:self.make,
				stopFlash:self.stopFlash,
				init:self.init
			};
		})(),
		channels = (function(){
			var self = {
				draw:function(){
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
								oirc.channels.setChans($.map($('.chanList'),function(chan,i){
									if($(chan).find('span').hasClass('curchan')){
										oirc.options.set('curChan',i);
										oirc.channels.current().setI(i);
									}
									return $(chan).data('json');
								}));
							}
						};
					$('#ChanList').empty().append(
						$.map(oirc.channels.getList(),function(c,i){
							if((c.ex && oirc.options.get('extraChans')) || !c.ex){
								return $('<div>')
									.data('json',c)
									.attr('id','chan'+i.toString())
									.addClass('chanList'+(c.high?' highlightChan':''))
									.append(
										$('<span>')
											.addClass('chan '+(oirc.channels.current().is(i)?' curchan':''))
											.append(
												(c.chan.substr(0,1)!='#'?
												$('<span>')
													.addClass('closeButton')
													.css({
														width:9,
														float:'left'
													})
													.mouseup(function(e){
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
				init:function(){
					self.draw();
				},
				join:function(i,fn){
					indicator.start();
					$('#message').attr('disabled','true');
					$('#MessageBox').empty();
					$('.chan').removeClass('curchan');
					oirc.channels.load(i,function(success,data){
						if(success){
							tab.load();
							oldMessages.read();
							
							if(oirc.settings.loggedIn()){
								$('#message').removeAttr('disabled');
							}
						}
						$('#chan'+i.toString()).removeClass('highlightChan').find('.chan').addClass('curchan');
						if(fn!==undefined){
							fn(success);
						}
						indicator.stop();
					});
				}
			};
			return {
				init:self.init,
				draw:self.draw,
				join:self.join
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
				getCurrentWord:function(){
					var messageVal = (!wysiwyg.support()?$('#message')[0].value:(self.node = window.getSelection().anchorNode).nodeValue);
					if(self.isInTab){
						return self.tabWord;
					}
					self.startPos = (!wysiwyg.support()?$('#message')[0].selectionStart:window.getSelection().anchorOffset);
					self.startChar = messageVal.charAt(self.startPos);
					while(self.startChar != ' ' && --self.startPos > 0){
						self.startChar = messageVal.charAt(self.startPos);
					}
					if(self.startChar == ' '){
						self.startPos++;
					}
					self.endPos = (!wysiwyg.support()?$('#message')[0].selectionStart:window.getSelection().anchorOffset);
					self.endChar = messageVal.charAt(self.endPos);
					while(self.endChar != ' ' && ++self.endPos <= messageVal.length){
						self.endChar = messageVal.charAt(self.endPos);
					}
					self.endPos0 = self.endPos;
					self.tabWord = messageVal.substr(self.startPos,self.endPos - self.startPos).trim();
					return self.tabWord;
				},
				getTabComplete:function(){
					var messageVal = (!wysiwyg.support()?$('#message')[0].value:self.node.nodeValue),
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
					if(!wysiwyg.support()){
						$('#message')[0].value = messageVal;
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
					$('#message')
						.keydown(function(e){
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
					self.searchArray = $.merge(oirc.users.getNames(),oirc.channels.getNames());
				}
			};
			return {
				init:self.init,
				load:self.load
			};
		})(),
		users = (function(){
			var self = {
				draw:function(users){
					$('#UserList').empty().append(
						$.map(users,function(u){
							var getInfo,
								ne = encodeURIComponent(u.nick),
								n = $('<span>').text(u.nick).html();
							return $('<span>')
								.attr('title',oirc.settings.getNetwork(u.network).name)
								.append(
									oirc.settings.getNetwork(u.network).userlist.split('NICKENCODE').join(ne).split('NICK').join(n),
									'<br>'
								)
								.mouseover(function(){
									getInfo = oirc.network.getJSON('misc.php?userinfo&name='+base64.encode(u.nick)+'&chan='+oirc.channels.current().handlerB64+'&online='+u.network.toString(),function(data){
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
									}catch(e){}
									$('#lastSeenCont').css('display','none');
								});
						}),
						'<br><br>'
					);
				}
			};
			return {
				draw:self.draw
			};
		})(),
		topic = (function(){
			var self = {
				set:function(t){
					$('#topic').empty().append(t);
				}
			};
			return {
				set:self.set
			};
		})(),
		scroll = (function(){
			var self = {
				isDown:false,
				is_touch:(('ontouchstart' in window) || (navigator.MaxTouchPoints > 0) || (navigator.msMaxTouchPoints > 0)),
				headerOffset:0,
				$mBox:false,
				$mBoxCont:false,
				touchScroll:function($elem,fn){
					var lastY = -1;
					$elem.bind('touchstart',function(e){
						if($(e.target).is('a')){
							return;
						}
						self.$mBox.css('transition','none');
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
						self.$mBox.css('transition','');
						e.preventDefault();
						lastY = -1;
					});
				},
				enableButtons:function(){
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
							if(self.is_touch){
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
				moveWindow:function(delta){
					var oldTop = -parseInt(self.$mBox[0].style.top,10),
						maxScroll = self.$mBox.height() - self.$mBoxCont.height(),
						newTop = Math.min(maxScroll,Math.max(0,oldTop - delta));
					self.isDown = false;
					self.$mBox.css('top',-newTop);
					if(newTop==maxScroll){
						self.isDown = true;
					}
					if(oirc.options.get('scrollBar')){
						self.reCalcBar();
					}
				},
				enableWheel:function(){
					self.$mBoxCont.bind('DOMMouseScroll mousewheel',function(e){
						var oldTop = self.$mBox[0].style.top;
						self.moveWindow((/Firefox/i.test(navigator.userAgent)?(e.originalEvent.detail*(-20)):(e.originalEvent.wheelDelta/2)));
						//if(oldTop != $mBox[0].style.top){
							e.preventDefault();
							e.stopPropagation();
							e.cancelBubble = true;
						//}
					});
					if(self.is_touch){
						self.touchScroll(self.$mBoxCont,function(d){
							self.moveWindow(d);
						});
					}
				},
				reCalcBar:function(){
					if($('#scrollBar').length!==0){
						if(self.$mBox.height() <= self.$mBoxCont.height()){
							$('#scrollBar').css('top',self.headerOffset);
						}else{
							$('#scrollBar').css('top',(parseInt(self.$mBox[0].style.top,10)/(self.$mBoxCont.height() - self.$mBox.height()))*($('body')[0].offsetHeight-$('#scrollBar')[0].offsetHeight-self.headerOffset)+self.headerOffset);
						}
					}
				},
				enableUserlist:function(){
					var moveUserList = function(delta,_self){
							$(_self).css('top',Math.min(0,Math.max(((/Opera/i.test(navigator.userAgent))?-30:0)+document.getElementById('UserListInnerCont').clientHeight-_self.scrollHeight,delta+parseInt($('#UserList').css('top'),10))));
						};
					$('#UserList')
						.css('top',0)
						.bind('DOMMouseScroll mousewheel',function(e){
							if(e.preventDefault){
								e.preventDefault();
							}
							e = e.originalEvent;
							moveUserList((/Firefox/i.test(navigator.userAgent)?(e.detail*(-20)):(e.wheelDelta/2)),this);
						});
					if(self.is_touch){
						self.touchScroll($('#UserList'),function(d){
							self.moveUserList(d,this);
						});
					}
				},
				showBar:function(){
					var mouseUpPos = false,
						mouseDownPos = false,
						mouseMoveFn = function(y){
							var newscrollbartop = 0;
							if($bar.data('isClicked')){
								if(!(mouseUpPos!==false && y <= mouseUpPos) && !(mouseDownPos!==false && y >= mouseDownPos)){
								
									newscrollbartop = parseInt($bar.css('top'),10)+(y-$bar.data('prevY'));
									
									self.$mBox.css('top',-((newscrollbartop-self.headerOffset)/($('body')[0].offsetHeight-$bar[0].offsetHeight-self.headerOffset))*(self.$mBox.height() - self.$mBoxCont.height()));
									self.isDown = false;
									if(newscrollbartop<self.headerOffset){
										if(mouseUpPos === false){
											mouseUpPos = y;
										}
										newscrollbartop = self.headerOffset;
										self.$mBox.css('top',0);
									}else{
										mouseUpPos = false;
									}
									if(newscrollbartop>($('body')[0].offsetHeight-$bar[0].offsetHeight)){
										if(mouseDownPos === false){
											mouseDownPos = y;
										}
										newscrollbartop = $('body')[0].offsetHeight-$bar[0].offsetHeight;
										self.$mBox.css('top',self.$mBoxCont.height() - self.$mBox.height());
										self.isDown = true;
									}else{
										mouseDownPos = false;
									}
									$bar.css('top',newscrollbartop);
								}
							}
							$bar.data('prevY',y);
						},
						mouseDownFn = function(){
							$bar.data('isClicked',true);
							$('#scrollArea').css('display','block');
							self.$mBox.css('transition','none');
							firstEnter = false;
							mouseUpPos = false;
							mouseDownPos = false;
						},
						mouseUpFn = function(){
							$bar.data('isClicked',false);
							$('#scrollArea').css('display','none');
							self.$mBox.css('transition','');
						},
						$bar = $('<div>').attr('id','scrollBar').data({prevY:0,isClicked:false}).appendTo('body')
							.mousemove(function(e){
								mouseMoveFn(e.clientY);
							})
							.mousedown(function(e){
								mouseDownFn();
							})
							.mouseup(function(){
								mouseUpFn();
							}),
						$tmp,
						firstEnter = false;
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
						.mouseenter(function(e){
							if(e.originalEvent.buttons == 0 && firstEnter){
								mouseUpFn();
							}
							firstEnter = true;
						});
					if(self.is_touch){
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
				showButtons:function(){
					var downIntM,
						upIntM,
						downIntMfn = function(){
							downIntM = setInterval(function(){
								self.moveWindow(9);
							},50);
						},
						upIntMfn = function(){
							upIntM = setInterval(function(){
								self.moveWindow(-9);
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
									height:'12pt',
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
									self.$mBox.css('transition','none');
									downIntMfn();
								})
								.mouseout(function(){
									self.$mBox.css('transition','');
									try{
										clearInterval(downIntM);
									}catch(e){}
								})
								.mouseup(function(){
									self.$mBox.css('transition','');
									try{
										clearInterval(downIntM);
									}catch(e){}
								})
						);
					if(self.is_touch){
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
									height:'12pt',
									bottom:'6pt',
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
									self.$mBox.css('transition','none');
									upIntMfn();
								})
								.mouseout(function(){
									self.$mBox.css('transition','');
									try{
										clearInterval(upIntM);
									}catch(e){}
								})
								.mouseup(function(){
									self.$mBox.css('transition','');
									try{
										clearInterval(upIntM);
									}catch(e){}
								})
						);
					if(self.is_touch){
						$tmp.bind('touchstart',function(){
							upIntMfn();
						}).bind('touchend touchcancel touchleave',function(e){
							try{
								clearInterval(upIntM);
							}catch(e){}
						});
					}
					$tmp.appendTo('body');
				},
				down:function(){
					self.$mBox.css('top',self.$mBoxCont.height() - self.$mBox.height()); // reverse direction on subtraction to gain negativity
					self.reCalcBar();
					self.isDown = true;
				},
				up:function(){
					self.$mBox.css('top',0);;
					self.reCalcBar();
					self.isDown = false;
				},
				slide:function(){
					if(self.isDown){
						self.down();
					}
				},
				init:function(){
					self.$mBox = $('#MessageBox');
					self.$mBoxCont = $('#mBoxCont');
					self.enableButtons();
					self.headerOffset = $('#header').height() - 2;
					if(oirc.options.get('scrollBar')){
						self.showBar();
					}else{
						self.showButtons();
					}
					if(oirc.options.get('scrollWheel')){
						self.enableWheel();
					}
					self.enableUserlist();
					$(document).add(window).add('body').add('html').scroll(function(e){
						e.preventDefault();
					});
				}
			};
			
			return {
				down:self.down,
				up:self.up,
				slide:self.slide,
				init:self.init,
				reCalcBar:self.reCalcBar
			};
		})(),
		wysiwyg = (function(){
			var self = {
				menuOpen:false,
				msgVal:'',
				hideMenu:function(){
					$('#textDecoForm').css('display','none');
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
						var dfg = args.match(/fg-(\d+)/)
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
							nodeRange = document.createRange();
						preCaretRange.selectNodeContents(elem);
						preCaretRange.setEnd(range.endContainer,range.endOffset);
						for(var node of preCaretRange.commonAncestorContainer.getElementsByTagName('img')){
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
					for(var node of el.childNodes){
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
					var $elem = $('#message');
					if(start === undefined){
						start = end = self.getCaretCharacterOffsetWithin($elem[0]);
					}
					
					self.msgVal = self.reverseParse($elem.html());
					$elem.html(oirc.parser.parse(self.msgVal));
					var range = document.createRange();
					
					self.setCaretCharacterOffsetWithin($elem[0],start,end-start);
				},
				init:function(){
					$('#message').on('input',function(e){
						self.updateContent(); // we need the first argument to be undefined
					});
					$('#message').mouseup(function(e){
						var sel = window.getSelection();
						if(sel.isCollapsed){
							return;
						}
						e.preventDefault();
						$('#textDecoForm').css({
							display:'block',
							left:Math.max(e.pageX-52,0)
						});
						self.menuOpen = true;
						
						
					});
					$(document).mousedown(function(e){
						if(!$(e.target).closest('#textDecoForm').length && self.menuOpen){
							self.hideMenu();
						}
					})
					$('#textDecoForm .colorbutton').click(function(e){
						e.preventDefault();
						var num = this.dataset.num,
							pos = self.getWholeSelection($('#message')[0]);
						self.surroundSelection('\x03'+num,'\x0f');
						self.updateContent(pos[0],pos[1]);
					});
					$('#textDecoFormBold').click(function(e){
						e.preventDefault();
						var pos = self.getWholeSelection($('#message')[0]);
						self.surroundSelection('\x02','\x02');
						self.updateContent(pos[0],pos[1]);
					});
					$('#textDecoFormItalic').click(function(e){
						e.preventDefault();
						var pos = self.getWholeSelection($('#message')[0]);
						self.surroundSelection('\x1d','\x1d');
						self.updateContent(pos[0],pos[1]);
					});
					$('#textDecoFormUnderline').click(function(e){
						e.preventDefault();
						var pos = self.getWholeSelection($('#message')[0]);
						self.surroundSelection('\x1f','\x1f');
						self.updateContent(pos[0],pos[1]);
					});
				},
				getMsg:function(){
					return self.msgVal;
				},
				support:function(){
					return (('contentEditable' in document.documentElement) && oirc.options.get('wysiwyg'));
				}
			};
			return {
				init:self.init,
				getMsg:self.getMsg,
				support:self.support,
				updateContent:self.updateContent
			}
		})(),
		smileys = (function(){
			var self = {
				init:function(){
					if(oirc.options.get('smileys')){
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
				},
				set:function(sm){
					$('#smileyselect').empty().append(
						$.map(sm,function(s){
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
										wysiwyg.updateContent();
									}
								})):''),' '];
								
						})
					);
				}
			};
			return {
				init:self.init,
				set:self.set
			}
		})(),
		page = (function(){
			var self = {
				mBoxContWidthOffset:90,
				hide_userlist:false,
				show_scrollbar:true,
				registerToggle:function(){
					$('#toggleButton').click(function(e){
						e.preventDefault();
						oirc.options.set('enable',!oirc.options.get('enable'));
						document.location.reload();
					});
				},
				isBlurred:true,
				calcResize:function(allowHeightChange){
					var htmlHeight = window.innerHeight,
						htmlWidth = window.innerWidth,
						headerHeight = $('#header').outerHeight(),
						footerHeight = $('#footer').outerHeight(),
						em = Number(getComputedStyle(document.body,'').fontSize.match(/(\d*(\.\d*)?)px/)[1]);
					if(allowHeightChange){
						$('#scrollBarLine').css('top',parseInt($('#header').outerHeight(),10));
						
						
						$('#mBoxCont').css('height',htmlHeight - footerHeight - headerHeight - 0.2*em);
						$('html,body').height(htmlHeight);
						
						$('#message').css('width',htmlWidth*(self.hide_userlist?1:0.91) - 12*em - ($('#loginForm').width()));
					}
					if(self.show_scrollbar){
						var widthOffset = (htmlWidth/100)*self.mBoxContWidthOffset;
						if(allowHeightChange){
							$('#mBoxCont').css('width',widthOffset-1.9*em);
						}
						$('#scrollBarLine').css('left',widthOffset - 1.4*em);
						if(allowHeightChange){
							$('#scrollBarLine').css('height',htmlHeight - headerHeight - 0.1*em);
						}
						$('#scrollBar').css('left',widthOffset - 1.5*em);
						scroll.reCalcBar();
					}
					scroll.down();
				},
				initGuestLogin:function(){
					var tryLogin = function(name,remember){
							settings.identGuest(name,function(data){
								if(!data.success){
									alert('ERROR'+(data.message?': '+data.message:''));
									loginFail();
								}else{
									settings.setIdent(name,data.signature,-1);
									request.identify();
									$('#pickUsernamePopup').hide();
									$('#message').removeAttr('disabled');
									send.val('');
									if(remember){
										ls.set('guestName',name);
										ls.set('guestSig',data.signature);
									}else{
										ls.set('guestName','');
									}
									$('#loginForm > button').hide();
									$('#guestName').text(name+' (guest) (').append(
										$('<a>').text('logout').click(function(e){
											e.preventDefault();
											if(confirm('Are you sure? You won\'t be able to take this nick for half an hour!')){
												ls.set('guestName','');
												ls.set('guestSig','');
												settings.setIdent('','',-1);
												request.identify();
												loginFail();
											}
										}),
										')'
									).show();
									$(window).trigger('resize');
								}
							});
						},
						loginFail = function(){
							send.val('You need to pick a username if you want to chat!');
							$('#message').attr('disabled',true);
							$('#loginForm > button').show();
							$('#guestName').hide();
							$(window).trigger('resize');
						}
					if(ls.get('guestName')){
						settings.setIdent(ls.get('guestName'),ls.get('guestSig'),-1);
						tryLogin(ls.get('guestName'),true);
					}else{
						loginFail();
					}
					$('#loginForm').show();
					$('#loginForm > button').click(function(e){
						e.preventDefault();
						$('#pickUsernamePopup').toggle();
					});
					$('#pickUsernamePopup > button').click(function(e){
						tryLogin($('#pickUsernamePopup > input[type="text"]').val(),$('#pickUsernamePopup > input[type="checkbox"]')[0].checked);
						
					});
				},
				init:function(){
					var nua = navigator.userAgent,
						is_android = ((nua.indexOf('Mozilla/5.0') > -1 && nua.indexOf('Android ') > -1 && nua.indexOf('AppleWebKit') > -1) && !(nua.indexOf('Chrome') > -1)),
						is_ios = (nua.match(/(iPod|iPhone|iPad)/i) && nua.match(/AppleWebKit/i)),
						is_mobile_webkit = (nua.match(/AppleWebKit/i) && nua.match(/Android/i));
					
					$('body').css('font-size',oirc.options.get('fontSize').toString(10)+'pt');
					self.hide_userlist = oirc.options.get('hideUserlist');
					self.show_scrollbar = oirc.options.get('scrollBar');
					page.changeLinks();
					if(!wysiwyg.support()){
						$('#message').replaceWith(
							$('<input>').attr({
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
					
					if(!oirc.settings.loggedIn()){
						send.val('You need to login if you want to chat!');
						if(settings.guestLevel() >= 2){ // display the stuff for login form
							self.initGuestLogin();
						}
					}
					if(self.hide_userlist){ // hide userlist is on
						self.mBoxContWidthOffset = 99;
						$('<style>')
							.append(
								'#mBoxCont{width:99%;}',
								'.arrowButtonHoriz2,.arrowButtonHoriz3 > div:nth-child(2){left:98%;left:calc(99% - 5px);left:-webkit-calc(99% - 5px);}',
								'#UserListContainer{left:99%;transition: left 0.5s 1s;-webkit-transition: left 0.5s 1s;-o-transition-property: left;-o-transition-duration: 0.5d;-o-transition-delay: ls;}',
								'#icons{right:95px;}'
							)
							.appendTo('head');
					}
					scroll.init();
					tab.init();
					
					notification.init();
					self.registerToggle();
					if(is_ios){
						self.calcResize(true);
					}
					$(window).resize(function(){
						self.calcResize(!is_ios);
					}).trigger('resize').blur(function(){
						self.isBlurred = true;
					}).focus(function(){
						self.isBlurred = false;
						notification.stopFlash();
					});
					$('#aboutButton').click(function(e){
						e.preventDefault();
						$('#about').toggle();
					});
					smileys.init();
					
					oldMessages.init();
					$('#sendMessage').submit(function(e){
						e.preventDefault();
						if(oirc.settings.loggedIn()){
							var val = '';
							if(!wysiwyg.support()){
								val = $('#message').val();
							}else{
								val = wysiwyg.getMsg();
							}
							oldMessages.add(val);
							
							if(!$('#message').attr('disabled') && val!==''){
								oirc.send.send(val,function(){
									if(!wysiwyg.support()){
										$('#message').val('');
									}else{
										$('#message').text('');
									}
								});
								$('#message').focus(); // fix IE not doing that automatically
							}
						}
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
				},
				maxLines:200,
				lastMessage:0,
				lineHigh:false,
				addLine:function(line,loadMode){
					if(line.type!='highlight' && line.chan.toString().toLowerCase()!=oirc.channels.current().handler.toLowerCase() && line.name2 !== oirc.settings.getPmIdent()){
						return;
					}
					var $mBox = $('#MessageBox'),
						name = oirc.parser.name(line.name,line.network,line.uid),
						message = oirc.parser.parse(line.message),
						tdName = '*',
						tdMessage = message,
						statusTxt = '',
						lineDate = new Date(line.time*1000);
					if(oirc.settings.nick() != '' && (['message','action','pm','pmaction'].indexOf(line.type)>=0) && line.name.toLowerCase() != '*' && message.toLowerCase().indexOf(oirc.settings.nick().toLowerCase().substr(0,oirc.options.get('charsHigh'))) >= 0){
						tdMessage = message = oirc.parser.highlight(message);
						if(!loadMode && page.isBlurred()){
							notification.make('('+channels.current().name+') <'+line.name+'> '+line.message,line.chan);
						}
					}
					switch(line.type){
						case 'reload':
							if(!loadMode){
								oirc.channels.current().reload();
							}
							return;
						case 'reload_userlist':
							if(!loadMode){
								oirc.channels.current().reloadUserlist();
							}
							return;
						case 'relog':
							if(!loadMode){
								oirc.settings.fetch(undefined,true);
							}
							return;
						case 'refresh':
							if(!loadMode){
								location.reload(true);
							}
							return;
						case 'join':
							tdMessage = [name,' has joined '+oirc.channels.current().name];
							if(!loadMode){
								oirc.users.add({
									nick:line.name,
									network:line.network
								});
							}
							if(oirc.settings.getNetwork(line.network).type==1 && !oirc.options.get('oircJoinPart')){
								return;
							}
							break;
						case 'part':
							tdMessage = [name,' has left '+oirc.channels.current().name+' (',message,')'];
							if(!loadMode){
								oirc.users.remove({
									nick:line.name,
									network:line.network
								});
							}
							if(oirc.settings.getNetwork(line.network).type==1 && !oirc.options.get('oircJoinPart')){
								return;
							}
							break;
						case 'quit':
							tdMessage = [name,' has quit IRC (',message,')'];
							if(!loadMode){
								oirc.users.remove({
									nick:line.name,
									network:line.network
								});
							}
							if(oirc.settings.getNetwork(line.network).type==1 && !oirc.options.get('oircJoinPart')){
								return;
							}
							break;
						case 'kick':
							tdMessage = [name,' has kicked ',oirc.parser.name(line.name2,line.network),' from '+oirc.channels.current().name+' (',message,')'];
							if(!loadMode){
								oirc.users.remove({
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
										message[i] = oirc.parser.name(v,line.network);
									}
								});
								message = message.join(' ');
							}
							tdMessage = [name,' set '+oirc.channels.current().name+' mode ',message];
							break;
						case 'nick':
							tdMessage = [name,' has changed nicks to ',oirc.parser.name(line.name2,line.network)];
							if(!loadMode){
								oirc.users.add({
									nick:line.name2,
									network:line.network
								});
								oirc.users.remove({
									nick:line.name,
									network:line.network
								});
							}
							break;
						case 'topic':
							topic.set(oirc.parser.parse(line.message,true));
							if(line.name ==='' && line.network === 0 && loadMode){
								return;
							}
							tdMessage = [name,' has changed the topic to ',oirc.parser.parse(line.message,true)];
							break;
						case 'pm':
							if(line.chan==oirc.channels.current().handler){
								tdName = name;
								line.type = 'message';
							}else{
								if(!loadMode){
									if(line.name.toLowerCase() == oirc.settings.nick().toLowerCase()){
										oirc.channels.openPm(line.chan,oirc.settings.getWholePmIdent(line.uid,line.network));
										return;
									}else{
										tdName = ['(PM)',name];
										oirc.channels.openPm(line.name,oirc.settings.getWholePmIdent(line.uid,line.network));
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
									if(line.name.toLowerCase() == oirc.settings.nick().toLowerCase()){
										oirc.channels.openPm(line.chan,oirc.settings.getWholePmIdent(line.uid,line.network));
										return;
									}else{
										tdMessage = ['(PM)',name,' ',message];
										oirc.channels.openPm(line.name,oirc.settings.getWholePmIdent(line.uid,line.network));
										notification.make('* (PM)'+line.name+' '+line.message,line.chan);
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
							tdMessage = line.message;
							break;
						case 'server':
							break;
						default:
							return;
					}
					if(($mBox.find('tr').length>self.maxLines) && loadMode!==true){
						$mBox.find('tr:first').remove();
					}
					
					
					if(tdName == '*'){
						statusTxt = '* ';
					}else{
						statusTxt = '<'+line.name+'> ';
					}
					if(oirc.options.get('times')){
						statusTxt = '['+lineDate.toLocaleTimeString()+'] '+statusTxt;
					}
					statusTxt += $('<span>').append(tdMessage).text();
					statusBar.set(statusTxt);
					if((new Date(self.lastMessage)).getDay()!=lineDate.getDay()){
						$mBox.append($('<tr>').addClass('dateSeperator').append($('<td>')
							.addClass((oirc.options.get('altLines') && (self.lineHigh = !self.lineHigh)?'lineHigh':''))
							.attr('colspan',oirc.options.get('times')?3:2)
							.text(lineDate.toLocaleDateString())
						));
					}
					var $tr = $('<tr>')
						.addClass((oirc.options.get('altLines') && (self.lineHigh = !self.lineHigh)?'lineHigh':''))
						.append(
							(oirc.options.get('times')?$('<td>')
								.addClass('irc-date')
								.append('['+lineDate.toLocaleTimeString()+']'):''),
							$('<td>')
								.addClass('name')
								.append(tdName),
							$('<td>')
								.addClass(line.type)
								.append(tdMessage)
						);
					$tr.find('img').load(function(e){
						scroll.slide();
					});
					$mBox.append($tr);
					scroll.slide();
					
					self.lastMessage = line.time*1000;
					
					return;
				}
			};
			return {
				init:self.init,
				isBlurred:function(){
					return self.isBlurred;
				},
				changeLinks:self.changeLinks,
				registerToggle:self.registerToggle,
				addLine:self.addLine
			};
		})(),
		statusBar = (function(){
			var self = {
				text:'',
				started:false,
				start:function(){
					if(!oirc.options.get('statusBar')){
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
		oldMessages = (function(){
			var self = {
				messages:[],
				counter:0,
				current:'',
				getMsg:function(){
					if(!wysiwyg.support()){
						return $('#message').val();
					}
					return $('#message').html();
				},
				init:function(){
					$('#message').keydown(function(e){
						if(e.keyCode==38 || e.keyCode==40){
							e.preventDefault();
							if(self.counter==self.messages.length){
								self.current = self.getMsg();
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
		})();


	oirc.onmessage = page.addLine;
	oirc.onerror = function(url,error){
		console.error(url);
		console.error(error);
	};
	oirc.onwarning = function(url,warning){
		console.info(url);
		console.info(warning);
	};
	oirc.onuserchange = users.draw;
	oirc.onchannelchange = channels.draw;
	indicator.start();
	oirc.connect(function(){
		if(oirc.options.get('enable')){
			page.init();
			channels.join(oirc.options.get('curChan'),function(success){
				if(!success){
					oirc.chanels.join(0);
				}
			});
		}else{
			oirc.disconnect();
			page.registerToggle();
			$('#mBoxCont').empty().append(
				'<br>',
				$('<a>')
					.css('font-size','20pt')
					.text('OmnomIRC is disabled. Click here to enable.')
					.click(function(e){
						e.preventDefault();
						oirc.options.set('enable',true);
						window.location.reload();
					})
			);
			$('#footer,#header').css('display','none');
			indicator.stop();
		}
	});
})();
var oirc2 = (function(){
	var OMNOMIRCSERVER = 'https://omnomirc.omnimaga.org',
		
		parser = (function(){
			var self = {
				addLine:function(line,loadMode){
					if(loadMode===undefined){
						loadMode = false;
					}
					request.setCurLine(line.curLine);
					if(
						line.name === null
						|| self.ignores.indexOf(line.name.toLowerCase()) > -1
						|| (line.type!='highlight' && line.chan.toString().toLowerCase()!=channels.current().handler.toLowerCase() && line.name2 !== settings.getPmIdent())
						){
						return true; // invalid line but we don't want to stop the new requests
					}
					var $mBox = $('#MessageBox'),
						name = self.parseName(line.name,line.network,line.uid),
						message = self.parseMessage(line.message),
						tdName = '*',
						tdMessage = message,
						statusTxt = '',
						lineDate = new Date(line.time*1000);
					if(line.network == -1){
						return true;
					}
					if(settings.nick() != '' && (['message','action','pm','pmaction'].indexOf(line.type)>=0) && line.name.toLowerCase() != '*' && message.toLowerCase().indexOf(settings.nick().toLowerCase().substr(0,options.get('charsHigh'))) >= 0){
						tdMessage = message = self.parseHighlight(message);
						if(!loadMode && page.isBlurred()){
							notification.make('('+channels.current().name+') <'+line.name+'> '+line.message,line.chan);
						}
					}
					switch(line.type){
						case 'reload':
							if(!loadMode){
								channels.current().reload();
							}
							return true;
						case 'reload_userlist':
							if(!loadMode){
								channels.current().reloadUserlist();
							}
							return true;
						case 'relog':
							if(!loadMode){
								settings.fetch(undefined,true);
							}
							return true;
						case 'refresh':
							if(!loadMode){
								location.reload(true);
							}
							return true;
							break;
						case 'join':
							tdMessage = [name,' has joined '+channels.current().name];
							if(!loadMode){
								users.add({
									nick:line.name,
									network:line.network
								});
							}
							if(settings.getNetwork(line.network).type==1 && !options.get('oircJoinPart')){
								return true;
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
								return true;
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
								return true;
							}
							break;
						case 'kick':
							tdMessage = [name,' has kicked ',self.parseName(line.name2,line.network),' from '+channels.current().name+' (',message,')'];
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
										message[i] = self.parseName(v,line.network);
									}
								});
								message = message.join(' ');
							}
							tdMessage = [name,' set '+channels.current().name+' mode ',message];
							break;
						case 'nick':
							tdMessage = [name,' has changed nicks to ',self.parseName(line.name2,line.network)];
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
							topic.set(self.parseMessage(line.message,true));
							if(line.name ==='' && line.network === 0 && loadMode){
								return true;
							}
							tdMessage = [name,' has changed the topic to ',self.parseMessage(line.message,true)];
							break;
						case 'pm':
							if(line.chan==channels.current().handler){
								tdName = name;
								line.type = 'message';
							}else{
								if(!loadMode){
									if(line.name.toLowerCase() == settings.nick().toLowerCase()){
										channels.openPm(line.chan,settings.getWholePmIdent(line.uid,line.network));
										return true;
									}else{
										tdName = ['(PM)',name];
										channels.openPm(line.name,settings.getWholePmIdent(line.uid,line.network));
										notification.make('(PM) <'+line.name+'> '+line.message,line.chan);
									}
								}else{
									return true;
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
										channels.openPm(line.chan,settings.getWholePmIdent(line.uid,line.network));
										return true;
									}else{
										tdMessage = ['(PM)',name,' ',message];
										channels.openPm(line.name,settings.getWholePmIdent(line.uid,line.network));
										notification.make('* (PM)'+line.name+' '+line.message,line.chan);
										line.type = 'pm';
									}
								}else{
									return true;
								}
							}
							break;
						case 'highlight':
							if(line.name.toLowerCase() != '*'){
								notification.make('('+line.chan+') <'+line.name+'> '+line.message,line.chan);
							}
							return true;
						case 'internal':
							tdMessage = line.message;
							break;
						case 'server':
							break;
						default:
							return true;
					}
					if(($mBox.find('tr').length>self.maxLines) && loadMode!==true){
						$mBox.find('tr:first').remove();
					}
					
					
					if(tdName == '*'){
						statusTxt = '* ';
					}else{
						statusTxt = '<'+line.name+'> ';
					}
					if(options.get('times')){
						statusTxt = '['+lineDate.toLocaleTimeString()+'] '+statusTxt;
					}
					statusTxt += $('<span>').append(tdMessage).text();
					statusBar.set(statusTxt);
					if((new Date(self.lastMessage)).getDay()!=lineDate.getDay()){
						$mBox.append($('<tr>').addClass('dateSeperator').append($('<td>')
							.addClass((options.get('altLines') && (self.lineHigh = !self.lineHigh)?'lineHigh':''))
							.attr('colspan',options.get('times')?3:2)
							.text(lineDate.toLocaleDateString())
						));
					}
					var $tr = $('<tr>')
						.addClass((options.get('altLines') && (self.lineHigh = !self.lineHigh)?'lineHigh':''))
						.append(
							(options.get('times')?$('<td>')
								.addClass('irc-date')
								.append('['+lineDate.toLocaleTimeString()+']'):''),
							$('<td>')
								.addClass('name')
								.append(tdName),
							$('<td>')
								.addClass(line.type)
								.append(tdMessage)
						);
					$tr.find('img').load(function(e){
						scroll.slide();
					});
					$mBox.append($tr);
					scroll.slide();
					
					self.lastMessage = line.time*1000;
					
					return true;
				},
				setSmileys:function(sm){
					self.smileys = [];
					$.each(sm,function(i,s){
						self.smileys.push({
							regex:s.regex,
							replace:s.replace.split('ADDSTUFF').join('data-code="'+s.code+'"').split('PIC').join(s.pic).split('ALT').join(s.alt),
						});
					});
				},
				setSpLinks:function(a){
					self.spLinks = a;
				},
				setIgnoreList:function(a){
					self.ignores = a;
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
				}
			};
			return {
				addLine:self.addLine,
				setSmileys:self.setSmileys,
				setSpLinks:self.setSpLinks,
				setIgnoreList:self.setIgnoreList,
				parseMessage:self.parseMessage,
				parseTextDecorations:self.parseTextDecorations
			};
		})();
	
})();
