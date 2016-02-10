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

import server,traceback,re,struct,json,time,bot,errno,oirc_include as oirc
from base64 import b64encode
from hashlib import sha1

relayType = -1
defaultCfg = False
name = 'The Game'
editPattern = False

class Relay(oirc.OircRelay):
	relayType = 1
	def initRelay(self):
		self.relayType = relayType
		ws_handler = type('WebSocketsHandler_anon',(WebSocketsHandler,),{'handle':self.handle})
		if self.config['ssl']:
			self.server = server.SSLServer(self.config['host'],self.config['port'],ws_handler)
		else:
			self.server = server.Server(self.config['host'],self.config['port'],ws_handler)
	def startRelay(self):
		self.server.start()
	def updateRelay(self,cfg):
		if self.config['host'] != cfg['host'] or self.config['port'] != cfg['port'] or self.config['ssl'] != cfg['ssl']:
			self.config = cfg
			self.stopRelay_wrap()
			self.initRelay()
			self.startRelay_wrap()
		else:
			self.config = cfg
	def stopRelay(self):
		if hasattr(self.server,'inputHandlers'):
			for client in self.server.inputHandlers:
				client.sendLine('OmnomIRC','','reload_userlist','THE GAME',client.nick,0,-1)
		self.server.stop()
	def relayMessage(self,n1,n2,t,m,c,s,uid): #self.server.inputHandlers
		c = str(c)
		if hasattr(self.server,'inputHandlers'):
			for client in self.server.inputHandlers:
				try:
					if ((not client.banned) and
						(
							(
								c in client.chans
								and
								t!='server'
							)
							or
							str(n2) == client.pmHandler
						)):
						client.sendLine(n1,n2,t,m,c,s,uid)
				except Exception as inst:
					print(inst)
					traceback.print_exc()
	def joinThread(self):
		self.server.join()




def b64encode_wrap(s):
	return oirc.makeUnicode(b64encode(bytes(s,'utf-8')))

# websockethandler skeleton from https://gist.github.com/jkp/3136208
class WebSocketsHandler(server.ServerHandler):
	magic = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'
	sig = ''
	network = -1
	uid = -1
	nick = ''
	identified = False
	globalop = False
	banned = True
	chans = {}
	msgStack = []
	pmHandler = '**'
	def setup(self):
		print('(websockets) connection established'+str(self.client_address))
		self.handshake_done = False
		print('(websockets) New Web-Client')
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
			print('(websockets) nothing to recieve')
			return False
		#if not b1 & 0x80:
		#	print('(websockets) Client closed connection')
		#	return False
		#if b1 & 0x0F == 0x8:
		#	print('(websockets) Client asked to close connection')
		#	return False
		#if not b1 & 0x80:
		#	print('(websockets) Client must always be masked')
		#	return False
		length = b2 & 127
		if length == 126:
			length = struct.unpack(">H", self.socket.recv(2))[0]
		elif length == 127:
			length = struct.unpack(">Q", self.socket.recv(8))[0]
		masks = self.socket.recv(4)
		decoded = ""
		for char in self.socket.recv(length):
			decoded += chr(char ^ masks[len(decoded) % 4])
		try:
			return self.on_message(json.loads(oirc.makeUnicode(decoded)))
		except Exception as inst:
			traceback.print_exc()
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
			traceback.print_exc()
			if e.errno == errno.EPIPE:
				return self.close()
		return True
	def addLine(self,t,m,c):
		if isinstance(c,str) and len(c) > 0 and c[0]=='*':
			c = c[1:]
			if t=='message':
				t = 'pm'
			elif t=='action':
				t = 'pmaction'
			else:
				return
		if c!='':
			print('(websockets) ('+str(self.network)+')>> '+str({'chan':c,'nick':self.nick,'message':m,'type':t}))
			self.handle.sendToOther(self.nick,'',t,m,c,self.network,self.uid)
	def join(self,c): # updates nick in userlist
		c = str(c)
		if c in self.chans:
			self.chans[c] += 1
			return # no need to add to the userlist
		self.chans[c] = 1
		if isinstance(c,str) and len(c) > 0 and c[0]=='*': # no userlist on PMs
			return
		if self.handle.addUser(self.nick,str(c),self.network,self.uid):
			self.handle.sendToOther(self.nick,'','join','',str(c),self.network,self.uid)
	def part(self,c):
		c = str(c)
		if not c in self.chans:
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
		data = oirc.makeUnicode(self.socket.recv(1024)).strip()
		
		print('(websockets) Handshaking...')
		key = re.search('\n[sS]ec-[wW]eb[sS]ocket-[kK]ey[\s]*:[\s]*(.*)\r?\n?',data)
		if key:
			key = key.group(1).strip()
		else:
			print('(websockets) Missing Key!')
			return False
		digest = b64encode(sha1((key + self.magic).encode('latin_1')).digest()).strip().decode('latin_1')
		response = 'HTTP/1.1 101 Switching Protocols\r\n'
		response += 'Upgrade: websocket\r\n'
		response += 'Connection: Upgrade\r\n'
		protocol = re.search('\n[sS]ec-[wW]eb[sS]ocket-[pP]rotocol[\s]*:[\s]*(.*)\r?\n?',data)
		print(data)
		if protocol:
			response += 'Sec-WebSocket-Protocol: %s\r\n' % protocol.group(1).strip()
		response += 'Sec-WebSocket-Accept: %s\r\n\r\n' % digest
		self.handshake_done = self.socket.send(bytes(response,'latin_1'))
		return True
	def checkRelog(self,r,m):
		if 'relog' in r:
			self.send_message(json.dumps({'relog':r['relog']}))
			if r['relog']==2:
				self.msgStack.append(m)
	def on_message(self,m):
		try:
			if 'action' in m:
				if m['action'] == 'ident':
					try:
						r = oirc.execPhp('omnomirc.php',{
							'ident':'',
							'nick':b64encode_wrap(m['nick']),
							'signature':b64encode_wrap(m['signature']),
							'time':m['time'],
							'id':m['id'],
							'network':m['network']
						})
					except:
						traceback.print_exc()
						self.identified = False
						self.send_message(json.dumps({'relog':3}))
						return True
					try:
						self.banned = r['isbanned']
						if r['loggedin']:
							self.identified = True
							self.nick = m['nick']
							self.sig = m['signature']
							self.uid = m['id']
							self.network = m['network']
							self.pmHandler = '['+str(self.network)+','+str(self.uid)+']'
							for a in self.msgStack: # let's pop the whole stack!
								self.on_message(a)
							self.msgStack = []
						else:
							self.identified = False
							self.nick = ''
							self.pmHandler = '**'
						if 'relog' in r:
							self.send_message(json.dumps({'relog':r['relog']}))
					except:
						self.identified = False
						self.pmHandler = '**'
						self.nick = ''
				elif m['action'] == 'joinchan':
					c = m['chan']
					try:
						c = str(int(c))
					except:
						c = b64encode_wrap(c)
					r = oirc.execPhp('omnomirc.php',{
						'ident':'',
						'nick':b64encode_wrap(self.nick),
						'signature':b64encode_wrap(self.sig),
						'time':str(int(time.time())),
						'id':self.uid,
						'network':self.network,
						'channel':c
					})
					self.checkRelog(r,m)
					if not r['isbanned'] and self.identified:
						self.join(r['channel'])
				elif m['action'] == 'partchan':
					self.part(m['chan'])
				elif self.identified:
					print(m)
					print(self.chans)
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
							else: # normal message
								self.addLine('message',msg,m['channel'])
		except:
			traceback.print_exc()
		return True
	def close(self):
		print('(websockets) connection closed')
		try:
			for c in self.chans:
				self.chans[c] = 1 # we want to quit right away
				self.part(c)
			self.socket.close()
		except:
			pass
		return False

