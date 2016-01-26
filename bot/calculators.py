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

import server,traceback

#gCn bridge
class CalculatorHandler(server.ServerHandler):
	connectedToIRC=False
	chan=''
	calcName=''
	stopnow=False
	def userJoin(self):
		c = self.chanToId(self.chan)
		if c!=-1:
			handle.addUser(self.calcName,c,self.i)
	def userPart(self):
		c = self.chanToId(self.chan)
		if c!=-1:
			handle.removeUser(self.calcName,c,self.i)
	def close(self):
		print('(calcnet) ('+str(self.i)+') Giving signal to quit calculator...')
		try:
			if self.connectedToIRC:
				self.sendToIRC('quit','')
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
	def sendToIRC(self,t,m):
		c = self.chanToId(self.chan)
		if c!=-1:
			handle.sendToOther(self.calcName,'',t,m,c,self.i)
	def sendLine(self,n1,n2,t,m,c,s): #name 1, name 2, type, message, channel, source
		c = self.idToChan(c)
		if c!=-1:
			m = stripIrcColors(m) # these will be glitched on the calc
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
			traceback.print_exc()
	def idToChan(self,i):
		if i in self.chans:
			return self.chans[i]
		return ''
	def chanToId(self,c):
		for i,ch in self.chans.items():
			if c.lower() == ch.lower():
				return i
		return -1
	def findchan(self,chan,chans):
		for key,value in chans.items():
			if chan.lower() == value.lower():
				return True
				break
		return False
	def updateChans(self,chans,default):
		print(chans)
		print(self.chans)
		self.defaultChan = default
		if not self.findchan(self.chan,chans):
			self.sendToIRC('part','')
			self.userPart()
			self.send(b'\xAD**Channel '+bytes(self.chan,'utf-8')+b' is not more available!')
			self.chan = self.defaultChan
			self.send(b'\xAD**Now speeking in channel '+bytes(self.chan,'utf-8'))
			self.sendToIRC('join','')
			self.userJoin()
		self.chans = chans
	def setup(self):
		global config
		print('(calcnet) ('+str(self.i)+') New calculator')
		self.chans = {}
		self.defaultChan = ''
		for ch in config.json['channels']:
			if ch['enabled']:
				for c in ch['networks']:
					if c['id'] == self.i:
						self.chans[ch['id']] = c['name']
						if self.defaultChan == '':
							self.defaultChan = c['name']
						break
		return True
	def recieve(self):
		try:
			r_bytes = self.socket.recv(1024)
		except socket.timeout:
			return True
		except Exception as err:
			print('(calcnet) ('+str(self.i)+') Error:',err)
			return False
		data = makeUnicode(r_bytes)
		if len(r_bytes) == 0: # eof
			print('(calcnet) ('+str(self.i)+') EOF recieved')
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
				if not(self.findchan(self.chan,self.chans)):
					printString+='Invalid channel, defaulting to '+self.defaultChan+'\n'
					self.chan=self.defaultChan
			if (r_bytes[2]==ord('c')):
				calcId=makeUnicode(r_bytes[3:])
				printString+='Calc-message recieved. Calc-ID:'+calcId+'\n'
			if (r_bytes[2]==ord('b') or r_bytes[2]==ord('f')):
				if r_bytes[17]==171:
					self.send(b'\xABOmnomIRC')
					if not self.connectedToIRC:
						printString+=self.calcName+' has joined\n'
						self.connectedToIRC=True
						self.send(b'\xAD**Now speeking in channel '+bytes(self.chan,'utf-8'))
						self.sendToIRC('join','')
						self.userJoin()
				elif r_bytes[17]==172:
					if self.connectedToIRC:
						printString+=self.calcName+' has quit\n'
						self.connectedToIRC=False
						self.userPart()
						self.sendToIRC('quit','')
				elif r_bytes[17]==173 and data[5:10]=='Omnom':
					printString+='msg ('+self.calcName+') '+data[data.find(':',18)+1:-1]+'\n'
					message=data[data.find(":",18)+1:-1]
					if message.split(' ')[0].lower()=='/join':
						if self.findchan(message[message.find(' ')+1:].lower(),self.chans):
							self.sendToIRC('part','')
							self.userPart()
							self.chan=message[message.find(' ')+1:].lower()
							self.send(b'\xAD**Now speeking in channel '+bytes(self.chan,'utf-8'))
							self.sendToIRC('join','')
							self.userJoin()
						else:
							self.send(b'\xAD**Channel '+bytes(message[message.find(' ')+1:],'utf-8')+b' doesn\'t exist!')
					elif message.split(' ')[0].lower()=='/me':
						self.sendToIRC('action',message[message.find(' ')+1:])
					else:
						self.sendToIRC('message',message)
					
			if printString!='':
				parts = printString.split('\n')
				for p in parts:
					if p != '':
						print('(calcnet) ('+str(self.i)+')',p)
		except Exception as inst:
			print('(calcnet) ('+str(self.i)+')',inst)
			traceback.print_exc()
		return True

