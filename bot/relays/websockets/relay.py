#!/usr/bin/python3
## -*- coding: utf-8 -*-


#	OmnomIRC COPYRIGHT 2010,2011 Netham45
#					   2012-2016 Sorunome
#
#	This file is part of OmnomIRC.
#
#	OmnomIRC is free software: you can redistribute it and/or modify
#	it under the terms of the GNU General Public License as published by
#	the Free Software Foundation, either version 3 of the License, or
#	(at your option) any later version.
#
#	OmnomIRC is distributed in the hope that it will be useful,
#	but WITHOUT ANY WARRANTY; without even the implied warranty of
#	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#	GNU General Public License for more details.
#
#	You should have received a copy of the GNU General Public License
#	along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.

import server,traceback,re,struct,json,time,errno,oirc_include as oirc
from base64 import b64encode
from hashlib import sha1


name = 'Websocket relay'
version = '1.0.0'

class Relay(oirc.OircRelay):
	def getNetChans(self):
		retn = {}
		for n in self.handle.config.json['networks']:
			if n['enabled']:
				chans = []
				for c in self.handle.config.json['channels']:
					if c['enabled']:
						for cc in c['networks']:
							if cc['id'] == n['id']:
								chans.append(c['id'])
				retn[n['id']] = chans
		return retn
	def errhandler(self):
		if self.config['portpoking']:
			self.info('Port in use, trying different port...')
			self.config['port'] += 1
			if self.config['port'] > 65535 or self.config['port'] < 10000:
				self.config['port'] = 10000
			oirc.execPhp('admin.php',{'internalAction':'setWsPort','port':self.config['port']})
			self.initRelay()
			self.startRelay()
	def initRelay(self):
		port = self.config['intport']
		host = self.config['host']
		if host == '':
			host = self.handle.config.json['settings']['hostname']
		if self.config['portpoking']:
			port = self.config['port']
		else:
			host = 'localhost'
			if port.startswith('unix:'):
				host = port[5:]
				port = 0
			port = int(port)
		c = self.getHandle(WebSocketsHandler)
		c.id = 'websockets'
		c.netChans = self.getNetChans()
		if self.config['ssl']:
			self.server = server.SSLServer(host,port,c,errhandler = self.errhandler,certfile = self.config['certfile'],keyfile = self.config['keyfile'])
		else:
			self.server = server.Server(host,port,c,errhandler = self.errhandler)
	def startRelay(self):
		self.server.start()
	def updateRelay(self,cfg,chans):
		if self.config['host'] != cfg['host'] or self.config['port'] != cfg['port'] or self.config['ssl'] != cfg['ssl']:
			self.config = cfg
			self.channels = chans
			self.stopRelay_wrap()
			self.initRelay()
			self.startRelay_wrap()
		else:
			self.config = cfg
		
		self.channels = chans
		nc = self.getNetChans()
		for client in self.server.inputHandlers:
			client.netChans = nc
	def stopRelay(self):
		if hasattr(self.server,'inputHandlers'):
			for client in self.server.inputHandlers:
				for c in client.chans:
					client.sendLine('OmnomIRC',client.pmHandler,'reload_userlist','THE GAME',c,0,-1)
		self.server.stop()
	def relayMessage(self,n1,n2,t,m,c,s,uid,curline = 0): #self.server.inputHandlers
		c = str(c)
		if hasattr(self.server,'inputHandlers'):
			m_lower = m.lower()
			for client in self.server.inputHandlers:
				try:
					if not client.banned:
						if (
								(
									c in client.chans
									and
									t!='server'
								)
								or
								str(n2) == client.pmHandler
							):
							client.sendLine(n1,n2,t,m,c,s,uid,curline)
						elif client.identified and t!='pm' and t!='pmaction' and client.charsHigh in m_lower:
							client.sendLine(n1,n2,'highlight',m,c,s,uid,curline)
				except Exception as inst:
					self.error(inst)
					self.error(traceback.format_exc())
	def joinThread(self):
		self.server.join()




def b64encode_wrap(s):
	return oirc.makeUnicode(b64encode(bytes(s,'utf-8')))

# websockethandler skeleton from https://gist.github.com/jkp/3136208
class WebSocketsHandler(server.WebSocketHandler,oirc.OircRelayHandle):
	def log(self,s):
		self.debug(s)
	def setup_extra(self):
		self.sig = ''
		self.network = -1
		self.uid = -1
		self.nick = ''
		self.identified = False
		self.globalop = False
		self.banned = True
		self.charsHigh = ''
		self.chans = {}
		self.pmHandler = '**'
		self.msgStack = []
		
		self.log_prefix = self.get_log_prefix()
		return
	def setCharsHigh(self,i):
		self.charsHigh = self.nick[:i].lower()
		if self.charsHigh == '':
			self.charsHigh = self.nick.lower()
	def get_log_prefix(self):
		return '['+str(self.client_address)+'] [Network: '+str(self.network)+'] [Uid: '+str(self.uid)+'] '
	
	def addLine(self,t,m,c):
		n2 = ''
		if isinstance(c,str) and len(c) > 0 and c[0]=='*':
			if t=='message':
				t = 'pm'
			elif t=='action':
				t = 'pmaction'
			else:
				return
			n2 = c[1:].replace(self.pmHandler,'')
			
		if c!='':
			self.debug('<< '+str({'chan':c,'nick':self.nick,'message':m,'type':t}))
			self.handle.message.send(self.nick,n2,t,m,c,self.network,self.uid)
	def join(self,c): # updates nick in userlist
		try:
			c = int(c)
		except:
			pass
		if isinstance(c,str):
			if self.uid == -1 and not (c[0] in '*@'):
				self.debug('Tried to join invalid channel: '+str(c))
				return
		elif not (self.network in self.netChans) or not (c in self.netChans[self.network]):
			self.debug('Tried to join invalid channel: '+str(c))
			return
		c = str(c)
		if c in self.chans:
			self.chans[c] += 1
		else:
			self.chans[c] = 1
		if self.nick == '' or (isinstance(c,str) and len(c) > 0 and c[0]=='*'): # no userlist on PMs
			return
		self.handle.user.add(self.nick,str(c),self.network,self.uid)
	def part(self,c):
		c = str(c)
		if not (c in self.chans):
			return
		self.chans[c] -= 1
		if self.chans[c]<=0:
			self.chans.pop(c,None)
			self.handle.user.timeout(self.nick,str(c),self.network)
	def sendLine(self,n1,n2,t,m,c,s,uid,curline = 0): #name 1, name 2, type, message, channel, source
		if self.banned:
			return False
		s = json.dumps({'line':{
			'curline':curline,
			'type':t,
			'network':s,
			'time':int(time.time()),
			'name':n1,
			'message':m,
			'name2':n2,
			'chan':c,
			'uid':uid
		}})
		self.send_message(s)
	def checkRelog(self,r,m):
		if 'relog' in r:
			self.send_message(json.dumps({'relog':r['relog']}))
			if r['relog']==2:
				self.debug('Pushing message to msg stack')
				self.msgStack.append(m)
	def on_message(self,m):
		try:
			if 'action' in m:
				self.debug('>> '+str(m))
				if m['action'] == 'ident':
					try:
						r = oirc.execPhp('misc.php',{
							'ident':'',
							'nick':b64encode_wrap(m['nick']),
							'signature':b64encode_wrap(m['signature']),
							'time':m['time'],
							'id':m['id'],
							'network':m['network']
						})
					except:
						self.error(traceback.format_exc())
						self.identified = False
						self.send_message(json.dumps({'relog':3}))
						return True
					try:
						self.debug('ident callback: '+str(r))
						self.banned = r['isglobalbanned']
						self.network = r['network']
						if r['loggedin']:
							self.identified = True
							self.nick = m['nick']
							self.sig = m['signature']
							self.uid = m['id']
							self.pmHandler = '['+str(self.network)+','+str(self.uid)+']'
							self.log_prefix = self.get_log_prefix()
							self.debug('Identified as user')
						else:
							self.identified = False
							self.nick = ''
							self.pmHandler = '**'
							self.log_prefix = self.get_log_prefix()
							self.debug('Identified as guest')
						
						for a in self.msgStack: # let's pop the whole stack!
							self.on_message(a)
						self.msgStack = []
						if 'relog' in r:
							self.send_message(json.dumps({'relog':r['relog']}))
					except:
						self.identified = False
						self.pmHandler = '**'
						self.nick = ''
					self.setCharsHigh(4)
				elif not self.banned:
					if m['action'] == 'joinchan':
						c = m['chan']
						try:
							c = str(int(c))
						except:
							c = b64encode_wrap(c)
						r = oirc.execPhp('misc.php',{
							'ident':'',
							'nick':b64encode_wrap(self.nick),
							'signature':b64encode_wrap(self.sig),
							'time':str(int(time.time())),
							'id':self.uid,
							'network':self.network,
							'channel':c
						})
						self.checkRelog(r,m)
						try:
							if r['mayview'] and r['channel'] == m['chan']:
								self.join(r['channel'])
						except:
							self.debug('tried to join channel '+c)
					elif m['action'] == 'partchan':
						self.part(m['chan'])
					elif self.identified:
						if m['action'] == 'message' and m['channel'] in self.chans and not self.banned:
							msg = m['message']
							if len(msg) <= 256 and len(msg) > 0:
								if msg[0] == '/':
									if len(msg) > 1 and msg[1]=='/': # normal message, erase trailing /
										self.addLine('message',msg[1:],m['channel'])
									else:
										if len(msg) > 4 and msg[0:4].lower() == '/me ': # /me message
											self.addLine('action',msg[4:],m['channel'])
										else:
											c = m['channel']
											try:
												c = str(int(c))
											except:
												c = b64encode_wrap(c)
											
											r = oirc.execPhp('message.php',{
												'message':b64encode_wrap(msg),
												'channel':c,
												'nick':b64encode_wrap(self.nick),
												'signature':b64encode_wrap(self.sig),
												'time':str(int(time.time())),
												'id':self.uid,
												'network':self.network
											})
											self.checkRelog(r,m)
											if 'lines' in r:
												for l in r['lines']:
													self.sendLine(l['name'],l['name2'],l['type'],l['message'],l['chan'],l['network'],l['uid'])
								else: # normal message
									self.addLine('message',msg,m['channel'])
						elif m['action'] == 'charsHigh':
							self.setCharsHigh(int(m['chars']))
						elif m['action'] == 'postfetch':
							c = m['channel']
							try:
								c = str(int(c))
							except:
								c = b64encode_wrap(c)
							if m['curline'] < self.handle.message.curline: # we only want to actually do stuff if we are behind
								r = oirc.execPhp('Update.php',{
									'high':len(self.charsHigh),
									'lineNum':m['curline'],
									'channel':c,
									'nick':b64encode_wrap(self.nick),
									'signature':b64encode_wrap(self.sig),
									'id':self.uid,
									'network':self.network,
									'nopoll':1
								})
								if 'lines' in r:
									for l in r['lines']:
										self.sendLine(l['name'],l['name2'],l['type'],l['message'],l['chan'],l['network'],l['uid'],l['curline'])
								if 'users' in r:
									self.send_message(json.dumps({
										'users':r['users']
									}))
		except:
			self.error(traceback.format_exc())
		return True
	def close(self):
		self.debug('connection closed')
		try:
			for c in self.chans:
				self.chans[c] = 1 # we want to quit right away
				self.part(c)
			self.socket.close()
		except:
			pass
		return False
