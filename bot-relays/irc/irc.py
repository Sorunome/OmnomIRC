#!/usr/bin/python3
## -*- coding: utf-8 -*-

# IRC Bot framework (c) Sorunome
# published under GPL 3 or later

import socket,threading,traceback,time,errno


def makeUnicode(s):
	try:
		return s.decode('utf-8')
	except:
		if s!='':
			try:
				return s.decode(chardet.detect(s)['encoding'])
			except:
				return s
		return ''

#irc bot
class Bot(threading.Thread):
	def __init__(self,server,port,nick,dssl = False,ns = '',chans = [],logstr = '',floodLimit = 5):
		threading.Thread.__init__(self)
		self.stopnow = False
		self.restart = False
		self.server = server
		self.port = port
		self.nick = nick
		self.nick_want = nick
		self.ns = ns
		self.chans = []
		for c in chans:
			self.chans.append(c.lower())
		self.colorAddingCache = {}
		self.ssl = dssl
		self.logstr = logstr
		
		self.floodLimit = floodLimit
		self.floodBuffer = []
		self.floodProtected = False
		self.floodNum = 0
		self.floodTime = int(time.time())
		
		self.lastTriedNick = time.time()
	def log(self,s,level='info'):
		if self.logstr != '':
			print(self.logstr+s)
	def updateChans(self,ch):
		chans = []
		for c in ch:
			chans.append(c.lower())
		updateChans = []
		for c in chans:
			if not c in self.chans:
				updateChans.append(c)
		removeChans = []
		for c in self.chans:
			if not c in chans:
				removeChans.append(c)
		for c in removeChans:
			self.send('PART %s' % c)
			self.chans.remove(c)
		for c in updateChans:
			self.chans.append(c)
			self.send('JOIN %s' % c)
	def stopThread(self):
		self.log(' Giving signal to quit irc bot...')
		self.quitMsg = 'Got signal to quit'
		#try:
		#	self.s.close()
		#except:
		#	traceback.print_exc()
		self.stopnow = True
	def _send(self,s,floodProtection = True):
		if self.floodLimit == 0 or not floodProtection:
			self.log('<< '+s.decode('utf-8'))
			self.s.sendall(s)
			return
		if self.floodProtected:
			self.floodBuffer.append(s)
			return
		t = int(time.time())
		if self.floodTime != t:
			self.floodTime = t
			self.floodNum = 0
		self.floodNum += 1
		if self.floodNum > self.floodLimit: # we need protection!
			self.log('Flood protection triggered')
			self.floodProtected = True
			self.floodBuffer.append(s)
			return
		self.log('<< '+s.decode('utf-8'))
		self.s.sendall(s)
	def send(self,s,overrideRestart = False,floodProtection = True):
		try:
			try:
				self._send(bytes('%s\r\n' % s,'utf-8'),floodProtection)
			except:
				self._send(bytes('%s\r\n' % s.encode('utf-8'),'utf-8'),floodProtection)
		except:
			self.log(traceback.format_exc(),'error')
			if not self.stopnow and not overrideRestart:
				self.restart = True
				self.stopThread()
	def connectToIRC(self):
		self.s = socket.socket()
		self.s.settimeout(60)
		if self.ssl:
			import ssl
			self.s = ssl.wrap_socket(self.s)
		self.s.connect((self.server,self.port))
		self.s.settimeout(1)
		self.send('USER %s %s %s :%s' % ('ircbot','host','server','ircbot'),floodProtection = False)
		self.send('NICK %s' % (self.nick),floodProtection = False)
	def handleNickTaken(self,line):
		for i in range(len(line)):
			line[i] = makeUnicode(line[i])
		if line[1]=='433' or line[1]=='436':
			self.nick += '_'
			self.send('NICK %s' % (self.nick))
		if self.nick != self.nick_want and self.lastTriedNick + 90 <= time.time(): # only try every now and then
			self.lastTriedNick = time.time()
			self.nick = self.nick_want
			self.send('NICK %s' % (self.nick))
	def ircloop(self,fn):
		self.quitLoop = False
		while not self.stopnow and not self.quitLoop:
			try:
				self.readbuffer += makeUnicode(self.s.recv(1024))
			except socket.error as e:
				if isinstance(e.args,tuple):
					if e == errno.EPIPE:
						self.stopnow = True
						self.restart = True
						self.quitMsg = 'Being stupid'
						self.log(' Restarting due to stupidness')
					elif e == errno.ECONNRESET:
						self.stopnow = True
						self.restart = True
						self.quitMsg = 'Being very stupid'
						self.log(' Restarting because connection being reset by peer')
				time.sleep(0.1)
				if self.lastPingTime+30 <= time.time():
					self.send('PING %s' % time.time())
					self.lastPingTime = time.time()
				if self.lastLineTime+90 <= time.time(): # allow up to 60 seconds lag
					self.stopnow = True
					self.restart = True
					self.lastLineTime = time.time()
					self.quitMsg = 'No pings (1)'
					self.log(' Restarting due to no pings')
			except Exception as inst:
				self.log(str(int),'error')
				self.log(traceback.format_exc(),'error')
				time.sleep(0.1)
				if self.lastLineTime+90 <= time.time(): # allow up to 60 seconds lag
					self.stopnow = True
					self.restart = True
					self.lastLineTime = time.time()
					self.quitMsg = 'No pings (2)'
					self.log(' Restarting due to no pings')
			temp=self.readbuffer.split('\n')
			self.readbuffer = temp.pop()
			if self.lastPingTime+90 <= time.time(): # allow up to 60 seconds lag
				self.stopnow = True
				self.restart = True
				self.lastLineTime = time.time()
				self.quitMsg = 'No pings(3)'
				continue
			if self.lastPingTime+30 <= time.time():
				self.send('PING %s' % time.time())
				self.lastPingTime = time.time()
			for line in temp:
				self.log('>> '+line)
				line=line.rstrip()
				line=line.split()
				try:
					self.lastLineTime = time.time()
					if(line[0]=='PING'):
						self.send('PONG %s' % line[1])
						continue
					if line[0]=='ERROR' and 'Closing Link' in line[1]:
						time.sleep(30)
						self.stopnow = True
						self.restart = True
						self.quitMsg = 'Closed link'
						self.log(' Error when connecting, restarting bot')
						break
					fn(line)
					self.handleNickTaken(line)
				except Exception as inst:
					self.log(' parse Error')
					self.log(str(inst))
					self.log(traceback.format_exc(),'error')
			
			# time to check for flood control buffer stuff
			if self.floodProtected:
				if len(self.floodBuffer) == 0:
					self.floodProtected = False
				else:
					s = self.floodBuffer.pop()
					self.s.sendall(s)
					self.log('< flood '+s.decode('utf-8'))
					if len(self.floodBuffer) == 0:
						self.floodProtected = False
		if self.stopnow:
			if self.quitMsg!='':
				self.send('QUIT :%s' % self.quitMsg,True, floodProtection=False)
				self.handleQuit(self.nick,self.quitMsg)
			try:
				self.s.settimeout(2) # wait for max 2 seconds for the server to quit on us
				self.s.recv(1)
			except:
				pass
			try:
				self.s.close() # just to make sure that the socket is actually closed
			except:
				pass
			self.afterStop()
	def afterStop(self):
		return
	def serveFn(self,line):
		return
	def getUsersInChan(self,c):
		self.send('WHO %s' % c)
	def joinChans(self):
		#ca = []
		for c in self.chans:
			self.send('JOIN %s' % c)
			#ca.append(c)
		#self.send('JOIN %s' % (','.join(ca)),True)
	def identServerFn(self,line):
		if line[1]=='376' or line[1]=='422':
			self.motdEnd = True
		if not self.identified and ((line[1] == 'NOTICE' and 'NickServ' in line[0])):
			if self.identifiedStep==0:
				self.send('PRIVMSG NickServ :IDENTIFY %s' % self.ns)
				self.identifiedStep = 1
			elif self.identifiedStep==1:
				self.identified = True
		if self.motdEnd and self.identified:
			self.quitLoop = True
	def run(self):
		self.restart = True
		while self.restart:
			self.restart = False
			self.stopnow = False
			self.identified = False
			self.motdEnd = False
			self.identifiedStep = 0
			self.quitMsg = ''
			self.lastPingTime = time.time()
			self.lastLineTime = time.time()
			self.log(' Starting bot...')
			
			self.connectToIRC()
			self.readbuffer = ''
			
			if self.ns=='':
				self.identified = True
			self.ircloop(self.identServerFn)
			
			if not self.stopnow:
				self.log(' Starting main loop...')
				self.joinChans()
				self.ircloop(self.serveFn)
			if self.restart:
				self.log(' Restarting bot')
				time.sleep(15)
		self.log(' Good bye from bot')
	def handleQuit(self,n,m):
		return
