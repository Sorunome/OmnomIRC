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

import server,traceback,struct,re,oirc_include as oirc

name = 'CalcNet'
version = '1.0.0'


class Relay(oirc.OircRelay):
	def initRelay(self):
		self.server = server.Server(self.config['server'],self.config['port'],self.getHandle(CalculatorHandler))
	def startRelay(self):
		self.server.start()
	def getChanStuff(self):
		chans = {}
		defaultChan = ''
		for ch in config.json['channels']:
			if ch['enabled']:
				for c in ch['networks']:
					if c['id'] == self.id:
						chans[ch['id']] = c['name']
						if defaultChan == '':
							defaultChan = c['name']
						break
		return [chans,defaultChan]
	def updateRelay(self,cfg,chans):
		if self.config['server'] != cfg['server'] or self.config['port'] != cfg['port']:
			self.config = cfg
			self.stopRelay_wrap()
			self.startRelay_wrap()
		else:
			self.config = cfg
			defaultChan = next(iter(chans.values()))
			
			for calc in self.server.inputHandlers:
				calc.updateChans(chans,defaultChan)
		self.channels = chans
	def stopRelay(self):
		self.server.stop()
	def relayMessage(self,n1,n2,t,m,c,s,uid = -1,curline = 0):
		for calc in self.server.inputHandlers:
			try:
				if calc.connectedToIRC and (not (s==self.id and n1==calc.calcName)) and calc.idToChan(c).lower()==calc.chan.lower():
					calc.sendLine(n1,n2,t,m,c,s)
			except Exception as inst:
				print(inst)
				traceback.print_exc()
	def joinThread(self):
		self.server.join()



#gCn bridge
class CalculatorHandler(server.ServerHandler,oirc.OircRelayHandle):
	connectedToIRC=False
	def setup(self):
		self.chan = ''
		self.calcName = ''
		self.stopnow = False
		return True
	def userJoin(self):
		c = self.chanToId(self.chan)
		if c!=-1:
			self.handle.user.add(self.calcName,c,self.id)
	def userPart(self):
		c = self.chanToId(self.chan)
		if c!=-1:
			self.handle.user.remove(self.calcName,c,self.id)
	def close(self):
		self.debug('Giving signal to quit calculator...')
		try:
			if self.connectedToIRC:
				self.addLine('quit','')
				self.userPart()
		except:
			pass
		try:
			self.send(b'\xAD**** Server going down! ****')
		except:
			pass
		try:
			self.socket.close()
		except:
			pass
	def addLine(self,t,m):
		c = self.chanToId(self.chan)
		if c!=-1:
			self.handle.message.send(self.calcName,'',t,m,c,self.id)
	def sendLine(self,n1,n2,t,m,c,s): #name 1, name 2, type, message, channel, source
		c = self.idToChan(c)
		if c!='':
			m = oirc.stripIrcColors(m) # these will be glitched on the calc
			if t=='message':
				self.send(b'\xAD'+bytes('%s:%s' % (n1,m),'utf-8'))
			elif t=='action':
				self.send(b'\xAD'+bytes('*%s %s' % (n1,m),'utf-8'))
			elif t=='join':
				self.send(b'\xAD'+bytes('*%s has joined %s' % (n1,c),'utf-8'))
			elif t=='part':
				self.send(b'\xAD'+bytes('*%s has left %s (%s)' % (n1,c,m),'utf-8'))
			elif t=='quit':
				self.send(b'\xAD'+bytes('*%s has quit %s (%s)' % (n1,c,m),'utf-8'))
			elif t=='mode':
				self.send(b'\xAD'+bytes('*%s set %s mode %s' % (n1,c,m),'utf-8'))
			elif t=='kick':
				self.send(b'\xAD'+bytes('*%s has kicked %s from %s (%s)' % (n1,n2,c,m),'utf-8'))
			elif t=='topic':
				self.send(b'\xAD'+bytes('*%s has changed the topic to %s' % (n1,m),'utf-8'))
			elif t=='nick':
				self.send(b'\xAD'+bytes('*%s has changed nicks to %s' % (n1,n2),'utf-8'))
	def send(self,message):
		try:
			try:
				message = bytes(message,'utf-8')
			except:
				pass
			message = b'\xFF\x89\x00\x00\x00\x00\x00Omnom'+struct.pack('<H',len(message))+message
			message = struct.pack('<H',len(message)+1)+b'b'+message+b'*'
			self.socket.sendall(message)
		except Exception as inst:
			self.error(traceback.format_exc())
	def findchan(self,chan,chans):
		for key,value in chans.items():
			if chan.lower() == value.lower():
				return True
				break
		return False
	def updateChans(self,chans,default):
		self.defaultChan = default
		if not self.findchan(self.chan,chans):
			self.addLine('part','')
			self.userPart()
			self.send(b'\xAD**Channel '+bytes(self.chan,'utf-8')+b' is not more available!')
			self.chan = self.defaultChan
			self.send(b'\xAD**Now speeking in channel '+bytes(self.chan,'utf-8'))
			self.userJoin()
		self.channels = chans
	def setup(self):
		self.debug('New calculator')
		self.defaultChan = next(iter(self.channels.values()))
		return True
	def recieve(self):
		try:
			r_bytes = self.socket.recv(1024)
		except socket.timeout:
			return True
		except Exception as err:
			self.error(str(err))
			return False
		data = oirc.makeUnicode(r_bytes)
		if len(r_bytes) == 0: # eof
			self.debug('EOF recieved')
			return False
		try:
			printString = '';
			sendMessage = False
			if (r_bytes[2]==ord('j')):
				self.calcName=''
				self.chan=''
				for i in range(4, int(ord(data[3]))+4):
					self.chan=self.chan+data[i]
				for i in range(int(ord(data[3]))+5, int(ord(data[int(ord(data[3]))+4]))+int(ord(data[3]))+5):
					self.calcName=self.calcName+data[i]
				self.chan = self.chan.lower()
				printString+='Join-message recieved. Calc-Name:'+self.calcName+' Channel:'+self.chan+'\n'
				if not(self.findchan(self.chan,self.channels)):
					printString+='Invalid channel, defaulting to '+self.defaultChan+'\n'
					self.chan=self.defaultChan
			if (r_bytes[2]==ord('c')):
				calcId=oirc.makeUnicode(r_bytes[3:])
				self.log_prefix = '['+self.calcId+'] '
				printString+='Calc-message recieved. Calc-ID:'+calcId+'\n'
			if (r_bytes[2]==ord('b') or r_bytes[2]==ord('f')):
				if r_bytes[17]==171:
					self.send(b'\xABOmnomIRC')
					if not self.connectedToIRC:
						printString+=self.calcName+' has joined\n'
						self.connectedToIRC=True
						self.send(b'\xAD**Now speeking in channel '+bytes(self.chan,'utf-8'))
						self.userJoin()
				elif r_bytes[17]==172:
					if self.connectedToIRC:
						printString+=self.calcName+' has quit\n'
						self.connectedToIRC=False
						self.userPart()
						self.addLine('quit','')
				elif r_bytes[17]==173 and str(data[5:10])=='Omnom':
					printString+='msg ('+self.calcName+') '+oirc.makeUnicode(str(data[data.find(':',18)+1:-1]))+'\n'
					message=oirc.makeUnicode(str(data[data.find(':',18)+1:-1]))
					if message.split(' ')[0].lower()=='/join':
						if self.findchan(message[message.find(' ')+1:].lower(),self.channels):
							self.addLine('part','')
							self.userPart()
							self.chan=message[message.find(' ')+1:].lower()
							self.send(b'\xAD**Now speeking in channel '+bytes(self.chan,'utf-8'))
							self.userJoin()
						else:
							self.send(b'\xAD**Channel '+bytes(message[message.find(' ')+1:],'utf-8')+b' doesn\'t exist!')
					elif message.split(' ')[0].lower()=='/me':
						self.addLine('action',message[message.find(' ')+1:])
					else:
						self.addLine('message',message)
					
			if printString!='':
				parts = printString.split('\n')
				for p in parts:
					if p != '':
						self.debug(p)
		except Exception as inst:
			self.error(str(inst))
			self.error(traceback.format_exc())
		return True
