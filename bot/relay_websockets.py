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

relayType = -1
defaultCfg = False
name = 'The Game'
editPattern = False

class Relay(oirc.OircRelay):
	def log_info(self,s):
		self.handle.log('websockets','info',s)
	def log_error(self,s):
		self.handle.log('websockets','error',s)
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
			self.log_info('Port in use, trying different port...')
			self.config['port'] += 1
			if self.config['port'] > 65535 or self.config['port'] < 10000:
				self.config['port'] = 10000
			oirc.execPhp('admin.php',{'internalAction':'setWsPort','port':self.config['port']})
			self.initRelay()
			self.startRelay()
	def initRelay(self):
		self.relayType = relayType
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
	def relayMessage(self,n1,n2,t,m,c,s,uid): #self.server.inputHandlers
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
							client.sendLine(n1,n2,t,m,c,s,uid)
						elif client.identified and t!='pm' and t!='pmaction' and client.charsHigh in m_lower:
							client.sendLine(n1,n2,'highlight',m,c,s,uid)
				except Exception as inst:
					self.log_error(inst)
					self.log_error(traceback.format_exc())
	def joinThread(self):
		self.server.join()




def b64encode_wrap(s):
	return oirc.makeUnicode(b64encode(bytes(s,'utf-8')))

# websockethandler skeleton from https://gist.github.com/jkp/3136208
class WebSocketsHandler(server.ServerHandler,oirc.OircRelayHandle):
	magic = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'
	sig = ''
	network = -1
	uid = -1
	nick = ''
	identified = False
	globalop = False
	banned = True
	charsHigh = ''
	chans = {}
	msgStack = []
	pmHandler = '**'
	firstRun = True
	def setCharsHigh(self,i):
		self.charsHigh = self.nick[:i].lower()
		if self.charsHigh == '':
			self.charsHigh = self.nick.lower()
	def get_log_prefix(self):
		return '['+str(self.client_address)+'] [Network: '+str(self.network)+'] [Uid: '+str(self.uid)+'] '
	def setup(self):
		self.log_prefix = self.get_log_prefix()
		self.log_info('connection established, new web-client')
		self.handshake_done = False
		return True
	def recieve(self):
		if not self.handshake_done:
			return self.handshake()
		else:
			return self.read_next_message()
	def read_next_message(self):
		try:
			b1,b2 = self.socket.recv(2)
		except:
			self.log_info('nothing to recieve')
			return False
		if not self.firstRun:
			if b1 & 0x0F == 0x8:
				self.log_info('Client asked to close connection')
				return False
			if not b1 & 0x80:
				self.log_info('Client must always be masked')
				return False
		self.firstRun = False
		length = b2 & 127
		if length == 126:
			length = struct.unpack(">H", self.socket.recv(2))[0]
		elif length == 127:
			length = struct.unpack(">Q", self.socket.recv(8))[0]
		masks = self.socket.recv(4)
		decoded = b""
		for char in self.socket.recv(length):
			decoded += bytes([char ^ masks[len(decoded) % 4]])
		try:
			return self.on_message(json.loads(oirc.makeUnicode(decoded)))
		except Exception as inst:
			self.log_error(traceback.format_exc())
			return True
	def send_message(self, message):
		try:
			header = bytearray()
			message = oirc.makeUnicode(message)
			
			length = len(message)
			self.socket.send(bytes([129]))
			if length <= 125:
				self.socket.send(bytes([length]))
			elif length >= 126 and length <= 65535:
				self.socket.send(bytes([126]))
				self.socket.send(struct.pack(">H",length))
			else:
				self.socket.send(bytes([127]))
				self.socket.send(struct.pack(">Q",length))
			self.socket.send(bytes(message,'utf-8'))
		except IOError as e:
			self.log_error(traceback.format_exc())
			if e.errno == errno.EPIPE:
				return self.close()
		return True
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
			self.log_info('<< '+str({'chan':c,'nick':self.nick,'message':m,'type':t}))
			self.handle.sendToOther(self.nick,n2,t,m,c,self.network,self.uid)
	def join(self,c): # updates nick in userlist
		try:
			c = int(c)
		except:
			pass
		if isinstance(c,str):
			if self.uid == -1 and not (c[0] in '*@'):
				self.log_info('Tried to join invalid channel: '+str(c))
				return
		elif not (self.network in self.netChans) or not (c in self.netChans[self.network]):
			self.log_info('Tried to join invalid channel: '+str(c))
			return
		c = str(c)
		if c in self.chans:
			self.chans[c] += 1
		else:
			self.chans[c] = 1
		if self.nick == '' or (isinstance(c,str) and len(c) > 0 and c[0]=='*'): # no userlist on PMs
			return
		self.handle.addUser(self.nick,str(c),self.network,self.uid)
	def part(self,c):
		c = str(c)
		if not (c in self.chans):
			return
		self.chans[c] -= 1
		if self.chans[c]<=0:
			self.chans.pop(c,None)
			self.handle.timeoutUser(self.nick,str(c),self.network)
	def sendLine(self,n1,n2,t,m,c,s,uid): #name 1, name 2, type, message, channel, source
		if self.banned:
			return False
		s = json.dumps({'line':{
			'curline':0,
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
	def handshake(self):
		data = ''
		buf = ''
		while True:
			buf = oirc.makeUnicode(self.socket.recv(1024)).strip()
			data += buf
			if len(buf) < 1024:
				break
		self.log_info('Handshaking...')
		self.log_info("\r\n\r\n" in data)
		self.log_info("\n\n" in data)
		self.log_info(data)
		key = re.search('\n[sS]ec-[wW]eb[sS]ocket-[kK]ey[\s]*:[\s]*(.*)\r?\n?',data)
		if key:
			key = key.group(1).strip()
		else:
			self.log_info('Missing Key!')
			return False
		digest = b64encode(sha1((key + self.magic).encode('latin_1')).digest()).strip().decode('latin_1')
		response = 'HTTP/1.1 101 Switching Protocols\r\n'
		response += 'Upgrade: websocket\r\n'
		response += 'Connection: Upgrade\r\n'
		protocol = re.search('\n[sS]ec-[wW]eb[sS]ocket-[pP]rotocol[\s]*:[\s]*(.*)\r?\n?',data)
		if protocol:
			response += 'Sec-WebSocket-Protocol: %s\r\n' % protocol.group(1).strip()
		response += 'Sec-WebSocket-Accept: %s\r\n\r\n' % digest
		self.handshake_done = self.socket.send(bytes(response,'latin_1'))
		return True
	def checkRelog(self,r,m):
		if 'relog' in r:
			self.send_message(json.dumps({'relog':r['relog']}))
			if r['relog']==2:
				self.log_info('Pushing message to msg stack')
				self.msgStack.append(m)
	def on_message(self,m):
		try:
			if 'action' in m:
				self.log_info('>> '+str(m))
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
						self.log_error(traceback.format_exc())
						self.identified = False
						self.send_message(json.dumps({'relog':3}))
						return True
					try:
						self.log_info('ident callback: '+str(r))
						self.banned = r['isglobalbanned']
						self.network = r['network']
						if r['loggedin']:
							self.identified = True
							self.nick = m['nick']
							self.sig = m['signature']
							self.uid = m['id']
							self.pmHandler = '['+str(self.network)+','+str(self.uid)+']'
							self.log_prefix = self.get_log_prefix()
							self.log_info('Identified as user')
						else:
							self.identified = False
							self.nick = ''
							self.pmHandler = '**'
							self.log_prefix = self.get_log_prefix()
							self.log_info('Identified as guest')
						
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
							self.log_info('tried to join channel '+c)
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
		except:
			self.log_error(traceback.format_exc())
		return True
	def close(self):
		self.log_info('connection closed')
		try:
			for c in self.chans:
				self.chans[c] = 1 # we want to quit right away
				self.part(c)
			self.socket.close()
		except:
			pass
		return False
