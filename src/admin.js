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

(function(){
	var adminconfig = [],
		sendEdit = function(page,json,fn){
			oirc.network.post('admin.php?set='+page,{data:JSON.stringify(json)},function(data){
				var alertStr = '';
				if(data.errors.length>0){
					$.map(data.errors,function(error){
						alertStr += 'ERROR: '+error+"\n";
					});
				}else{
					alertStr = data.message;
				}
				alert(alertStr);
				if(fn!==undefined){
					fn();
				}
			});
		},
		getInputBoxSettings = function(p,name,data){
			$('#adminContent').append(
				'<div style="font-weight:bold">'+name+' Settings</div>',
				$.map(data,function(d,i){
					var $input = $('<input>').attr('name',i),
						name = i,
						val = d;
					if((typeof d).toLowerCase() == 'object'){
						name = d[0];
						if(name===false){
							return '<br>';
						}
						val = d[1];
					}
					if(d === null){ // typeof bug
						d = undefined;
					}
					switch((typeof val).toLowerCase()){
						case 'string':
							$input.attr('type','text').val(val);
							break;
						case 'number':
							$input.attr('type','number').val(val);
							break;
						case 'boolean':
							$input.attr('type','checkbox').attr((val?'checked':'false'),'checked');
							break;
						default:
							$input.attr('type','hidden').data('data',val);
					}
					return $('<div>')
						.append(
							name,
							': ',$input
						);
				}),'<br>',
				$('<button>')
					.text('submit')
					.click(function(){
						var json = {};
						$('input').each(function(i,v){
							var val = undefined;
							switch($(v).attr('type')){
								case 'text':
									val = $(v).val();
									break;
								case 'number':
									val = parseInt($(v).val(),10);
									break;
								case 'checkbox':
									val = $(v)[0].checked;
									break;
								case 'hidden':
									val = $(v).data('data');
									break;
							}
							if(val!==undefined){
								json[$(v).attr('name')] = val;
							}
						});
						sendEdit(p,json);
					})
			);
		},
		getLiveInputSettings = function(data,pattern){
			var getVal = function(s,d){
					if(d === undefined){
						d = data;
					}
					s = s.split('/');
					if(s.length == 1){
						return d[s[0]]
					}
					var p = s.shift();
					return getVal(s.join('/'),d[p]);
				},
				setVal = function(s,v,d){
					if(d === undefined){
						d = data;
					}
					s = s.split('/');
					if(s.length == 1){
						d[s[0]] = v;
						return;
					}
					var p = s.shift();
					setVal(s.join('/'),v,d[p]);
				};
			return $('<span>').css('display','inline-block').append(
				$.map(pattern,function(prop){
					$input = $('<span>');
					console.log(prop);
					switch(prop.type){
						case 'text':
							$input = $('<input>').attr('type','text').val(getVal(prop.var)).change(function(){setVal(prop.var,this.value);});
							break;
						case 'number':
							$input = $('<input>').attr('type','number').val(getVal(prop.var)).change(function(){setVal(prop.var,parseInt(this.value,10));});
							break;
						case 'checkbox':
							$input = $('<input>').attr('type','checkbox').change(function(){setVal(prop.var,this.checked);});
							$input[0].checked = getVal(prop.var);
							break;
						case 'newline':
							return '<br>';
						case 'info':
							return [$('<b>').text(prop.name),'<br>'];
						case 'more':
							var $more = getLiveInputSettings(data,prop.pattern).css({
								border:'1px solid black',
								padding:5
							}).hide();
							return [$('<b>').text(prop.name),'&nbsp;',$('<a>').text('show/hide').click(function(e){
								e.preventDefault();
								$more.toggle();
							}),'<br>',$more,'<br>']
					}
					return [prop.name+':&nbsp;',$input,'<br>'];
				})
			);
		}
		makeThemesPage = function(themes){
			$('#adminContent').append(
				'<div style="font-weight:bold">Theme Settings</div>',
				$('<span>').append(
					$.map(themes,function(t,i){
						return [$('<span>').text(t.name),' ',$('<a>').text('edit').click(function(e){
							e.preventDefault();
							themes[i].lastModified = -1;
							$(this).parent().replaceWith(
								$('<span>').append(
									$('<a>').text('Back').click(function(e){
										e.preventDefault();
										$('#adminContent').empty();
										makeThemesPage(themes);
									}),'<br><br>Name:',
									$('<input>').attr('type','text').val(t.name).change(function(){themes[i].name = this.value;}),
									$.map([{name:'Background',type:'bg'},
											{name:'Alternative background',type:'bg2'},
											{name:'Border',type:'border'},
											{name:'Text',type:'text'},
											{name:'Links',type:'link'},
											{name:'Tab links',type:'tablink',use:'Links'},
											{name:'Buttons',type:'btn',use:'Alternative background'},
											{name:'Button Hover',type:'btnhover',use:'Alternative background'},
											{name:'Input bar',type:'form',use:'Background'},
											{name:'Popup background',type:'popupbg',use:'Alternative background'},
											{name:'Popup border',type:'popupborder',use:'Border'}],function(c){
										return ['<br>',(c.use!==undefined?
											$('<span>').append(
												'Use instead of ',c.use,
												$('<input>').attr('type','checkbox').attr((t['use'+c.type]?'checked':'false'),'checked').change(function(){
													themes[i]['use'+c.type] = this.checked;
												})
											):''),c.name,':',$('<input>').attr('type','color').val(t.colors[c.type]).change(function(){
											if(themes[i].colors instanceof Array){
												themes[i].colors = {}
											}
											themes[i].colors[c.type] = this.value;
										})];
									}),
									'<br>Extra style sheet:',
									$('<input>').attr('type','text').val(t.sheet).change(function(){themes[i].sheet = this.value;})
								)
							);
						}),'<br>'];
					}),
					'<br>',
					$('<a>').text('add Theme').click(function(e){
						e.preventDefault();
						var name = prompt('new theme name');
						if(name!=='' && name!==null){
							themes.push({
								name:name,
								colors:{},
								lastModified:-1
							});
							$('#adminContent').empty();
							makeThemesPage(themes);
						}
					})
				),
				'<br>',
				$('<button>').text('submit').click(function(){
					sendEdit('themes',themes);
				})
			);
		},
		makeChannelsPage = function(chans,nets){
			var makeAdvancedChanEditingForm = function(chan,i,elem){
					$(elem).empty().append(
						$.map(chan.networks,function(net,ni){
							return [$('<div>').css({
									'display':'inline-block',
									'border':'1px solid black',
									'margin':5
								})
								.append(
									$('<b>').text(nets[net.id].name),
									' ',
									$('<a>').text('remove').click(function(e){
										e.preventDefault();
										chans[i].networks.splice(ni,1);
										chan = chans[i];
										makeAdvancedChanEditingForm(chan,i,$(this).parent().parent());
									}),
									'<br>Name:',
									$('<input>').attr('type','text').val(net.name).change(function(){chans[i].networks[ni].name = this.value;}),
									(nets[net.id].type==1?[
										'<br>Hidden:',
										$('<input>').attr('type','checkbox').attr((net.hidden?'checked':'false'),'checked').change(function(){chans[i].networks[ni].hidden = this.checked;}),
										'<br>Order:',
										$('<input>').attr('type','number').val(net.order).change(function(){chans[i].networks[ni].order = parseInt($(this).val(),10);})
									]:'')
								),
								'<br>'
								];
						}),
						$('<select>').append(
								$('<option>').text('Add to network...').val(-1),
								$.map(nets,function(n){
									if(n.type===0){
										return undefined;
									}
									return $('<option>').text(n.name).val(n.id);
								})
							)
							.change(function(){
								var netId = parseInt($(this).val(),10),
									maxOrder = 0;
								$.each(chans,function(chani,ch){
									$.each(ch.networks,function(neti,nt){
										if(nt.id==netId &&  nt.order>maxOrder && maxOrder!=-1){
											maxOrder = nt.order;
										}
										if(nt.id==netId && chani==i){
											maxOrder = -1;
										}
									})
								});
								if(maxOrder==-1){
									alert('Network already exists!');
									$(this).val(-1);
									return;
								}
								chans[i].networks.push({
									'id':netId,
									'name':chan.networks.length > 0?chan.networks[0].name:chan.alias,
									'hidden':false,
									'order':maxOrder+1
								})
								chan = chans[i];
								makeAdvancedChanEditingForm(chan,i,$(this).parent());
							})
					);
				};
			$('#adminContent').append(
					'<div style="font-weight:bold">Channel Settings</div>',
					$('<div>').append(
							$.map(chans,function(chan,i){
								return $('<div>').css({
										'display':'inline-block',
										'border':'1px solid black',
										'vertical-align':'top'
									})
									.append(
										$('<b>').text(chan.alias),
										'<br>Enabled:',
										$('<input>').attr('type','checkbox').attr((chan.enabled?'checked':'false'),'checked').change(function(){chans[i].enabled = this.checked;}),
										'<br>',
										$('<div>').css({
												'display':'inline-block',
												'border':'1px solid black'
											})
											.append(
												$('<input>').attr('type','text').val(chan.networks.length > 0?chan.networks[0].name:chan.alias).change(function(){
														var _this = this;
														$.each(chan.networks,function(netI){
															chans[i].networks[netI].name = $(_this).val();
														});
													}),
												'<br>',
												$('<a>').text('Show advanced settings').click(function(e){
													e.preventDefault();
													makeAdvancedChanEditingForm(chan,i,$(this).parent());
												})
											)
									)
							}),
							$('<button>').text('New Channel').click(function(e){
									e.preventDefault();
									var alias = prompt('New Channel alias'),
										netsToAdd = [],
										maxId = 0;
									if(alias!=='' && alias!==null){
										netsToAdd = $.map(nets,function(n){
												var maxOrder = 0;
												if(n.type==1){
													$.each(chans,function(chani,ch){
														$.each(ch.networks,function(neti,nt){
															if(nt.id==n.id &&  nt.order>maxOrder && maxOrder!=-1){
																maxOrder = nt.order;
															}
														});
													});
												}
												if(n.type===0){
													return undefined;
												}
												return {
														'id':n.id,
														'name':'',
														'hidden':false,
														'order':maxOrder+1
													};
											});
										$.each(chans,function(chani,c){
											if(c.id>maxId){
												maxId = c.id;
											}
										});
										chans.push({
												'id':maxId+1,
												'alias':alias,
												'enabled':true,
												'networks':netsToAdd
											});
										$('#adminContent').empty();
										makeChannelsPage(chans,nets);
									}
								})
						),'<br>',
				$('<button>')
					.text('submit')
					.click(function(){
						sendEdit('channels',chans);
					})
				);
		},
		makeHotlinksPage = function(hotlinks){
			var drawAttrSettings = function($elem,i){
					$elem.empty().append(
						$.map(hotlinks[i],function(a,j){
							if(j=='inner'){
								return;
							}
							return ['<br>',
								$('<span>').text(j).html(),':',$('<input>').attr('type','text').css('width',120).val(hotlinks[i][j]).change(function(){hotlinks[i][j] = this.value;}),
								$('<a>').text('x').click(function(e){
									e.preventDefault();
									if(j == 'inner'){
										return;
									}
									delete hotlinks[i][j];
									drawAttrSettings($elem,i);
								})];
						}),'<br>',
						$('<button>').text('New Attribute').click(function(e){
							e.preventDefault();
							var name = prompt('new attribute name');
							if(name!=='' && name!==null){
								hotlinks[i][name] = '';
								drawAttrSettings($elem,i);
							}
						})
					);
				};
			$('#adminContent').append(
				'<div style="font-weight:bold">Hotlinks Settings</div>',
				$.map(hotlinks,function(h,i){
					$attrSettings = $('<div>');
					drawAttrSettings($attrSettings,i);
					return $('<div>').css({
							'display':'inline-block',
							'border':'1px solid black',
							'vertical-align':'top'
						}).append(
							$('<input>').attr('type','text').css('width',160).val(hotlinks[i].inner).change(function(){hotlinks[i].inner = this.value;}),
							'<br>',
							$('<a>').text('<').click(function(e){
								e.preventDefault();
								if(i == 0){
									return;
								}
								var tmp = hotlinks[i-1];
								hotlinks[i-1] = hotlinks[i];
								hotlinks[i] = tmp;
								$('#adminContent').empty();
								makeHotlinksPage(hotlinks);
							}),'&nbsp;&nbsp;',
							$('<a>').text('x').click(function(e){
								e.preventDefault();
								if(confirm('Are you sure you want to remove this hotlink?')){
									hotlinks.splice(i,1);
									$('#adminContent').empty();
									makeHotlinksPage(hotlinks);
								}
							}),'&nbsp;&nbsp;',
							$('<a>').text('>').click(function(e){
								e.preventDefault();
								if(i == hotlinks.length-1){
									return;
								}
								var tmp = hotlinks[i+1];
								hotlinks[i+1] = hotlinks[i];
								hotlinks[i] = tmp;
								$('#adminContent').empty();
								makeHotlinksPage(hotlinks);
							}),
							'<br>',
							$attrSettings
						);
				}),
				$('<button>').text('New Hotlink').click(function(e){
					e.preventDefault();
					var name = prompt('new hotlink name');
					if(name!=='' && name!==null){
						hotlinks.push({
							inner:name
						});
						$('#adminContent').empty();
						makeHotlinksPage(hotlinks);
					}
				}),'<br><br>',
				$('<button>').text('submit').click(function(){
					sendEdit('hotlinks',hotlinks);
				})
			);
		},
		makeSmileysPage = function(smileys){
			var getSmileyRegex = function(s){
					return s.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
				},
				$smileyEditCont = $('<div>'),
				$smileyList = $('<div>').append(
					$.map(smileys,function(s,i){
						var $img = $('<img>'),prevcode = smileys[i].code;
						return $img.attr('src',s.pic).css({'margin-left':5,'cursor':'pointer'}).click(function(){
							var advanced = false;
							$smileyEditCont.empty().append(
								$('<a>').text('Remove this smiley').click(function(e){
									e.preventDefault();
									if(confirm('Are you sure you want to remove this smiley?')){
										smileys.splice(i,1);
										$('#adminContent').empty();
										makeSmileysPage(smileys);
									}
								}),
								'<br>',
								'Picture URL:',$('<input>').attr('type','text').val(smileys[i].pic).change(function(){smileys[i].pic = this.value;$img.attr('src',this.value)}),
								'<br>',
								'Code:',$('<input>').attr('type','text').val(smileys[i].code).change(function(e){
									smileys[i].code = this.value;
									if(!advanced){
										if(window.getSelection){ // remove bug where text is selected on confirm box
											window.getSelection().removeAllRanges();
										}else{
											document.selection.empty();
										}
										if((smileys[i].regex != getSmileyRegex(prevcode) || smileys[i].replace != '<img src="PIC" alt="ALT" title="ALT" ADDSTUFF>') && !confirm('Also change regex/replace? They have been manually changed in the advanced settings!')){
											return;
										}
										smileys[i].regex = getSmileyRegex(this.value);
										smileys[i].replace = '<img src="PIC" alt="ALT" title="ALT" ADDSTUFF>';
										prevcode = this.value;
									}
								}),
								'<br>',
								'Show in menu:',$('<input>').attr('type','checkbox').attr((smileys[i].inMenu?'checked':'false'),'checked').change(function(){smileys[i].inMenu = this.checked;}),
								'<br>',
								'Alt (mouseover):',$('<input>').attr('type','text').val(smileys[i].alt).change(function(){smileys[i].alt = this.value;}),
								'<br>',
								$('<a>').text('Show advanced settings').click(function(e){
									e.preventDefault();
									advanced = true;
									$(this).replaceWith($('<span>').append(
										'Regex:',$('<input>').attr('type','text').val(smileys[i].regex).change(function(e){smileys[i].regex = this.value}),
										'<br>',
										'Replace:',$('<input>').attr('type','text').val(smileys[i].replace).change(function(e){smileys[i].replace = this.value})
									));
								})
							);
						});
					})
				);
			$('#adminContent').append(
				'<div style="font-weight:bold">Smiley Settings</div>',
				$smileyEditCont,'<br>',$smileyList,
				'<br>',
				$('<button>').text('New Smiley').click(function(e){
					e.preventDefault();
					var url = prompt('New image location'),
						code = prompt('New smiley code'),
						regex = '';
					if(url!=='' && url!==null && code!=='' && code!==null){
						smileys.push({
							pic:url,
							alt:code,
							code:code,
							inMenu:true,
							regex:getSmileyRegex(code),
							replace:'<img src="PIC" alt="ALT" title="ALT" ADDSTUFF>'
						});
						$('#adminContent').empty();
						makeSmileysPage(smileys);
					}
				}),
				'<br><br>',
				$('<button>').text('submit').click(function(){
					sendEdit('smileys',smileys);
				})
			);
		},
		makeNetworksPage = function(nets,netTypes){
			$('#adminContent').append(
				'<div style="font-weight:bold">Network Settings</div>',
				$('<div>').append(
						$.map(nets,function(net,i){
							if(net.type===0){ // no server networks displaying
								return undefined;
							}
							return [$('<span>').text(net.name),' ',$('<a>').text('edit').click(function(e){
								e.preventDefault();
								var $netSpecific = $('<b>').text('Unkown network type');
								switch(net.type){
									case 0:
										$netSpecific.text('Server Network');
										break;
									case 1:
										$netSpecific = $('<span>').append(
												$('<b>').text('OmnomIRC network'),
												'<br>checkLogin:',
												$('<input>').attr('type','text').val(net.config.checkLogin).change(function(){net.config.checkLogin = this.value;}),
												'<br>checkLogin hook:',
												$('<span>').text('loading...').on('now',function(){
													var _self = this;
													oirc.network.getJSON('admin.php?get=checkLogin&i='+i.toString(10),function(data){
														var s = '';
														if(data.success === false){
															s = 'ERROR: couldn\'t reach checkLogin server, perhaps the changed URL needs to be saved?';
														}else if(data.auth === false){
															s = 'ERROR: couldn\'t identify with checkLogin server, perhaps sigKey is false?';
														}
														if(s!==''){
															$(_self).replaceWith($('<span>').append(s));
															return;
														}
														$(_self).replaceWith(
															$('<select>').append(
																$.map(data.checkLogin.hooks,function(v){
																	return $('<option>').val(v).text(v);
																})
															).val(data.checkLogin.hook).change(function(){
																net.config.checkLoginHook = this.value;
															})
														);
													});
												}).trigger('now'),
												'<br>Theme:',
												$('<span>').text('loading...').on('now',function(){
													var _self = this;
													oirc.network.getJSON('admin.php?get=themes',function(data){
														$(_self).replaceWith(
															$('<select>').append(
																$('<option>').val(-1).text('default'),
																$.map(data.themes,function(v,i){
																	return $('<option>').val(i).text(v.name);
																})
															).val(net.config.theme?net.config.theme:0).change(function(){net.config.theme = parseInt(this.value,10);})
														);
													});
												}).trigger('now'),
												'<br>',
												$('<button>').text('Use Current settings as defaults').click(function(){
														net.config.defaults = oirc.options.getFullOptionsString();
													}),
												'<br>',
												$('<select>').append(
													$('<option>').val(0).text('Deny Guest Access'),
													$('<option>').val(1).text('Guests are read-only')
												).val(net.config.guests?net.config.guests:0).change(function(e){
													net.config.guests = parseInt(this.value,10);
												}),
												'<br>Extra Channels Message:<br>',
												$('<textarea>').text(net.config.extraChanMsg?net.config.extraChanMsg:'').change(function(){net.config.extraChanMsg = this.value;})
											);
										break;
									default:
										if(netTypes[net.type]){
											$netSpecific = $('<span>').append(
												$('<b>').text(netTypes[net.type].name + ' Network'),'<br>',
												getLiveInputSettings(net.config,netTypes[net.type].editPattern)
											);
										}
										break;
								}
								$(this).parent().replaceWith(
									$('<div>')
									.append(
										$('<a>').text('back').click(function(e){
											$('#adminContent').empty();
											makeNetworksPage(nets,netTypes);
										}),'<br><br>',
										$('<span>').append(
											$('<span>').css('font-weight','bold').text(net.name),
											'&nbsp;',
											$('<a>').text('edit').click(function(e){
												e.preventDefault();
												$(this).parent().replaceWith(
													$('<input>').attr('type','text').val(net.name).change(function(){net.name = this.value;})
												);
											})
										),
										'<br>Enabled:',
										$('<input>').attr('type','checkbox').attr((net.enabled?'checked':'false'),'checked').change(function(){net.enabled = this.checked;}),
										'<br>Normal:',
										$('<input>').attr('type','text').val(net.normal).change(function(){nets[i].normal = this.value;}),
										'<br>Userlist:',
										$('<input>').attr('type','text').val(net.userlist).change(function(){nets[i].userlist = this.value;}),
										'<br>IRC:',
										$('<input>').attr('type','number').val(net.irc.color).css('width',50).change(function(){net.irc.color = parseInt($(this).val(),10);}),
										$('<input>').attr('type','text').val(net.irc.prefix).css('width',50).change(function(){net.irc.prefix = this.value;}),
										'<br>',
										$netSpecific
									)
								);
							}),'<br>'];
						}),
						$('<select>').append(
								$('<option>').val(-1).text('Add Network...'),
								$.map(netTypes,function(n){
									return $('<option>').val(n.id).text(n.name);
								})
							)
							.change(function(){
								var name = prompt('New Network Name'),
									newNet = null,
									specificConfig,
									maxId;
								if(name!=='' && name!==null){
									var netType = parseInt($(this).val(),10);
									switch(netType){
										case 1: // omnomirc
											specificConfig = {
												'checkLogin':'link to checkLogin file',
												'theme':-1,
												'defaults':'',
												'opGroups':[],
												'guests':0
											};
											break;
										default:
											if(netTypes[netType]){
												specificConfig = netTypes[netType].defaultCfg
											}else{
												specificConfig = false;
											}
									}
									if(specificConfig){
										maxId = 0;
										$.each(nets,function(i,v){
											if(v.id > maxId){
												maxId = v.id;
											}
										});
										newNet = {
											'enabled':true,
											'id':maxId+1,
											'type':netType,
											'normal':'NICK',
											'userlist':'('+name[0].toUpperCase()+')NICK',
											'irc':{
												'color':-1,
												'prefix':'('+name[0].toUpperCase()+')'
											},
											'name':name,
											'config':specificConfig
										}
										nets.push(newNet);
										$('#adminContent').empty();
										makeNetworksPage(nets,netTypes);
									}
								}else{
									$(this).val(-1);
								}
							})
					),'<br>',
				$('<button>')
					.text('submit')
					.click(function(){
						sendEdit('networks',nets);
					})
			);
		},
		makeIndexPage = function(info){
			$('#adminContent').append(
				'OmnomIRC Version: '+info.version+'<br>',
				$('<span>').attr('id','fetchingUpdates').text('checking for updates...'),
				'<br>',
				$('<button>')
					.text('Back up config')
					.click(function(){
						sendEdit('backupConfig',{});
					}),
				$('<div>').attr('id','fetchingNews').text('fetching news...')
			);
			$.getJSON(oirc.OMNOMIRCSERVER+'/getNewestVersion.php?version='+info.version+(adminconfig.betaUpdates?'&experimental':'')+'&jsoncallback=?').done(function(data){
				if(data.latest){
					$('#fetchingUpdates').empty().text('No new updates available');
				}else{
					$('#fetchingUpdates').empty().append(
						'New updates available! ('+data.version+') ',
						(!info.updaterReady?
							$('<a>').text('Click here to download the updater script').click(function(e){
								e.preventDefault();
								sendEdit('getUpdater',{path:oirc.OMNOMIRCSERVER+data.updater},function(){
									loadPage('index');
								});
							})
						:
							$('<a>').text('Click here to apply the update').attr('href','updater.php'+(document.URL.split('network=')[1]!==undefined?'?network='+document.URL.split('network=')[1].split('&')[0].split('#')[0]:''))
						)
					);
				}
			});
			$.getJSON(oirc.OMNOMIRCSERVER+'/getNews.php?jsoncallback=?').done(function(data){
				$('#fetchingNews').empty().css({'height':300,'border':'1px solid black','overflow':'auto','width':'70%'}).append(
					$('<table>').css('width','100%').append(
						$('<tr>').append(
							$('<th>').css('width',150).text('date'),
							$('<th>').text('news')
						),
						$.map(data.news,function(n){
							return $('<tr>').append(
									$('<td>').css({'width':'auto','border':'1px solid black'}).text((new Date(n.time*1000)).toLocaleString()),
									$('<td>').css({'width':'auto','border':'1px solid black'}).html(n.message)
								)
						})
					)
				);
			});
		},
		loadPage = function(p){
			oirc.indicator.start();
			$('#adminContent').text('Loading...');
			oirc.network.getJSON('admin.php?get='+encodeURIComponent(p),function(data){
				$('#adminContent').empty();
				if(data.denied !== undefined && data.denied){
					$('#adminContent').append($('<b>').text('ERROR: Permission denied'));
					return;
				}
				switch(p){
					case 'index':
						makeIndexPage(data);
						break;
					case 'themes':
						makeThemesPage(data.themes);
						break;
					case 'channels':
						makeChannelsPage(data.channels,data.nets);
						break;
					case 'hotlinks':
						makeHotlinksPage(data.hotlinks);
						break;
					case 'smileys':
						makeSmileysPage(data.smileys);
						break;
					case 'networks':
						makeNetworksPage(data.networks,data.networkTypes);
						break;
					case 'sql':
						$.extend(data.sql,{
							passwd:''
						});
						getInputBoxSettings(p,'SQL',data.sql);
						break;
					case 'ws':
						getInputBoxSettings(p,'WebSockets',data.websockets);
						break;
					case 'misc':
						getInputBoxSettings(p,'Misc',data.misc);
						break;
					case 'ex':
						getInputBoxSettings(p,'Experimental',data.ex);
						break;
					case 'releaseNotes':
						$('#adminContent').append(
							$('<h2>').text('Release notes version '+data.version),
							$('<span>').attr('id','releaseNotes').text('Loading...')
						);
						$.getJSON(oirc.OMNOMIRCSERVER+'/getReleaseNotes.php?version='+data.version+'&jsoncallback=?').done(function(notes){
							$('#releaseNotes').html(notes.notes);
						});
						break;
				}
				window.location.hash = '#'+p;
				oirc.indicator.stop();
			});
		};
		
		
		$('#adminNav a').click(function(e){
			e.preventDefault();
			loadPage($(this).data('page'));
		});
		oirc.settings.fetch(function(){
			oirc.network.getJSON('config.php?nologinerrors&admincfg',function(data){
				adminconfig = data;
				var hash = window.location.hash;
				if(hash.split('#')[1] !== undefined){
					hash = hash.split('#')[1];
				}else if(hash == ''){
					hash = 'index';
				}
				oirc.page.changeLinks();
				loadPage(hash);
			});
		});
		$('#adminContent').height($(window).height() - 50);
		$(window).resize(function(){
			if(!(navigator.userAgent.match(/(iPod|iPhone|iPad)/i) && navigator.userAgent.match(/AppleWebKit/i))){
				$('#adminContent').height($(window).height() - 50);
			}
		});
})();