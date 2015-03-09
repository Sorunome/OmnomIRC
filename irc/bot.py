#!/usr/bin/python2
## -*- coding: utf-8 -*-


#    OmnomIRC COPYRIGHT 2010,2011 Netham45
#                       2012-2015 Sorunome
#
#    This file is part of OmnomIRC.
#
#    OmnomIRC is free software: you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    OmnomIRC is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
import threading,socket,string,time,sys,json,MySQLdb,traceback,errno,chardet,SocketServer,struct,signal
print('Starting OmnomIRC bot...')
DOCUMENTROOT = '/usr/share/nginx/html/oirc'


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


#config handler
class Config:
	def __init__(self):
		self.readFile()
	def readFile(self):
		jsons = ''
		searchingJson = True
		f = open(DOCUMENTROOT+'/config.php')
		lines = f.readlines()
		f.close()
		for l in lines:
			if searchingJson:
				if l.strip()=='//JSONSTART':
					searchingJson = False
			else:
				if l.strip()=='//JSONEND':
					break
				jsons += l[2:] + "\n"
		self.json = json.loads(jsons[:-1])

#sql handler
class Sql():
	def __init__(self):
		global config
	def fetchOneAssoc(self,cur):
		data = cur.fetchone()
		if data == None:
			return None
		desc = cur.description
		dict = {}
		for (name,value) in zip(desc,data):
			dict[name[0]] = value
		return dict
	def query(self,q,p = []):
		global config
		try:
			db = MySQLdb.connect(config.json['sql']['server'],config.json['sql']['user'],config.json['sql']['passwd'],config.json['sql']['db'],charset='utf8')
			cur = db.cursor()
			for i in range(len(p)):
				try:
					try:
						p[i] = p[i].decode('utf-8').encode('utf-8')
					except:
						if p[i]!='':
							p[i] = p[i].decode(chardet.detect(p[i])['encoding']).encode('utf-8')
					p[i] = db.escape_string(p[i])
				except:
					if isinstance(p[i],str):
						p[i] = db.escape_string(p[i])
			cur.execute(q % tuple(p))
			rows = []
			while True:
				row = self.fetchOneAssoc(cur)
				if row == None:
					break
				rows.append(row)
			cur.close()
			db.commit()
			db.close()
			return rows
		except Exception as inst:
			print('(sql) Error')
			print(inst)
			traceback.print_exc()
			return False

#irc bot
class Bot(threading.Thread):
	def __init__(self,server,port,nick,ns,main,passwd,i,tbe,mn,tn):
		threading.Thread.__init__(self)
		self.stopnow = False
		self.restart = False
		self.topicbotExists = tbe
		self.server = server
		self.port = port
		self.nick = nick
		self.ns = ns
		self.main = main
		self.passwd = passwd
		self.i = i
		self.userlist = {}
		self.chans = {}
		self.mainNick = mn
		self.topicNick = tn
		for ch in config.json['channels']:
			if ch['enabled']:
				for c in ch['networks']:
					if c['id'] == i:
						self.chans[ch['id']] = c['name']
						break
		if self.main:
			self.recieveStr = '>>'
			self.sendStr = '<<'
		else:
			self.recieveStr = 'T>'
			self.sendStr = 'T<'
	def idToChan(self,i):
		if i in self.chans:
			return self.chans[i]
		return -1
	def chanToId(self,c):
		for i,ch in self.chans.iteritems():
			if c.lower() == ch.lower():
				return i
		return -1
	def stopThread(self):
		if self.main:
			add = ')'
		else:
			add = 'T)'
		print('Giving signal to quit irc bot... ('+str(self.i)+add)
		self.s.close()
		self.stopnow = True
	def sendTopic(self,s,c):
		c = self.idToChan(c)
		if c != -1 and (self.topicbotExists ^ self.main):
			self.s.sendall('TOPIC %s :%s\r\n' % (c,s))
			print('('+str(self.i)+')'+self.sendStr+' '+c+' '+s)
	def send(self,s,override = False,overrideRestart = False):
		try:
			if self.main or override:
				try:
					self.s.sendall('%s\r\n' % s)
					print('('+str(self.i)+')'+self.sendStr+' '+s)
				except:
					self.s.sendall('%s\r\n' % s.encode('utf-8'))
					print('('+str(self.i)+')'+self.sendStr+' '+s.encode('utf-8'))
		except:
			traceback.print_exc()
			if not self.stopnow and not overrideRestart:
				self.restart = True
				self.stopThread()
	def connectToIRC(self):
		self.s = socket.socket()
		self.s.settimeout(5)
		self.s.connect((self.server,self.port))
		self.send('USER %s %s %s :%s' % ('OmnomIRC','host','server',self.nick),True)
		self.send('NICK %s' % (self.nick),True)
	def sendLine(self,n1,n2,t,m,c,s): #name 1, name 2, type, message, channel, source
		global config
		c = self.idToChan(c)
		if c != -1:
			colorAdding = ''
			for n in config.json['networks']:
				if n['id'] == s:
					if n['irc']['color']==-2:
						colorAdding = '\x02'+n['irc']['prefix']+'\x02'
					elif n['irc']['color']==-1:
						colorAdding = n['irc']['prefix']
					else:
						colorAdding = '\x03'+str(n['irc']['color'])+n['irc']['prefix']+'\x0F'
					break
			if colorAdding!='':
				if t=='message':
					self.send('PRIVMSG %s :%s<%s> %s' % (c,colorAdding,n1,m))
				elif t=='action':
					self.send('PRIVMSG %s :%s\x036* %s %s' % (c,colorAdding,n1,m))
				elif t=='join':
					self.send('PRIVMSG %s :%s\x033* %s has joined %s' % (c,colorAdding,n1,c))
				elif t=='part':
					self.send('PRIVMSG %s :%s\x032* %s has left %s (%s)' % (c,colorAdding,n1,c,m))
				elif t=='quit':
					self.send('PRIVMSG %s :%s\x032* %s has quit %s (%s)' % (c,colorAdding,n1,c,m))
				elif t=='mode':
					self.send('PRIVMSG %s :%s\x033* %s set %s mode %s' % (c,colorAdding,n1,c,m))
				elif t=='kick':
					self.send('PRIVMSG %s :%s\x034* %s has kicked %s from %s (%s)' % (c,colorAdding,n1,n2,c,m))
				elif t=='topic':
					self.send('PRIVMSG %s :%s\x033* %s has changed the topic to %s' % (c,colorAdding,n1,m))
				elif t=='nick':
					self.send('PRIVMSG %s :%s\x033* %s has changed nicks to %s' % (c,colorAdding,n1,n2))
	def addLine(self,n1,n2,t,m,c,sendToOther):
		global sql,handle
		c = self.chanToId(c)
		if c != -1:
			c = makeUnicode(str(c))
			if sendToOther:
				handle.sendToOther(n1,n2,t,m,c,self.i)
			print '(1)<< ',{'name1':n1,'name2':n2,'type':t,'message':m,'channel':c}
			sql.query("INSERT INTO `irc_lines` (`name1`,`name2`,`message`,`type`,`channel`,`time`,`online`) VALUES ('%s','%s','%s','%s','%s','%s',%d)",[n1,n2,m,t,c,str(int(time.time())),int(self.i)])
			if t=='topic':
				temp = sql.query("SELECT channum FROM `irc_topics` WHERE chan='%s'",[c.lower()])
				if len(temp)==0:
					sql.query("INSERT INTO `irc_topics` (chan,topic) VALUES('%s','%s')",[c.lower(),m])
				else:
					sql.query("UPDATE `irc_topics` SET topic='%s' WHERE chan='%s'",[m,c.lower()])
			if t=='action' or t=='message':
				sql.query("UPDATE `irc_users` SET lastMsg='%s' WHERE username='%s' AND channel='%s' AND online=%d",[str(int(time.time())),n1,c,int(self.i)])
			handle.updateCurline()
	def addUser(self,u,c):
		c = self.chanToId(c)
		if c != -1:
			if self.userlist.has_key(c):
				self.userlist[c].append(u)
			else:
				self.userlist[c] = [u]
			handle.addUser(u,c,self.i)
	def removeUser(self,u,c):
		global sql
		c = self.chanToId(c)
		if c != -1:
			if self.userlist.has_key(c):
				self.userlist[c].remove(u)
			handle.removeUser(u,c,self.i)
	def handleQuit(self,n,m):
		global handle
		for c,us in self.userlist.iteritems():
			removedUsers = []
			for u in us:
				if u==n:
					c = self.idToChan(c) # userlist array uses ids
					if c != -1:
						self.removeUser(n,c)
						if not n in removedUsers:
							self.addLine(n,'','quit',m,c,True)
							removedUsers.append(n);
	def handleNickChange(self,old,new):
		global handle
		for c,us in self.userlist.iteritems():
			changedNicks = []
			for u in us:
				if u==old:
					c = self.idToChan(c) # userlist array uses ids
					if c != -1:
						self.removeUser(old,c)
						self.addUser(new,c)
						if not new in changedNicks:
							self.addLine(old,new,'nick','',c,True)
							changedNicks.append(new)
		return False
	def doMain(self,line):
		global handle,config
		
		for i in range(len(line)):
			line[i] = makeUnicode(line[i])
			
		message = ' '.join(line[3:])[1:]
		
		nick = line[0].split('!')[0][1:]
		chan = line[2]
		if chan[0]!='#':
			chan = chan[1:]
		if line[1]=='PRIVMSG':
			if line[2][0]!='#' and line[3] == ':DOTHIS' and line[4] == self.passwd:
				self.send(' '.join(line[5:]))
			elif line[2][0]=='#':
				if line[3]==':\x01ACTION' and message[-1:]=='\x01':
					self.addLine(nick,'','action',message[8:-1],chan,True)
				else:
					self.addLine(nick,'','message',message,chan,True)
		elif line[1]=='JOIN':
			self.addLine(nick,'','join','',chan,True)
			self.addUser(nick,chan)
			if nick.lower()==self.nick.lower():
				self.getUsersInChan(chan)
		elif line[1]=='PART':
			self.addLine(nick,'','part',message,chan,True)
			self.removeUser(nick,chan)
			if nick.lower()==self.nick.lower():
				self.delUsersInChan(chan)
		elif line[1]=='QUIT':
			self.handleQuit(nick,' '.join(line[2:])[1:])
		elif line[1]=='MODE':
			self.addLine(nick,'','mode',' '.join(line[3:]),chan,True)
		elif line[1]=='KICK':
			self.addLine(nick,line[3],'kick',' '.join(line[4:])[1:],chan,True)
			self.removeUser(line[3],chan)
		elif line[1]=='TOPIC':
			if nick.lower()!=self.mainNick.lower() and nick.lower()!=self.topicNick.lower():
				self.addLine(nick,'','topic',message,chan,True)
				handle.sendTopicToOther(message,self.chanToId(chan),self.i)
		elif line[1]=='NICK':
			self.handleNickChange(nick,line[2][1:])
		elif line[1]=='352':
			self.addUser(line[7],line[3])
		elif line[1]=='315':
			self.addLine('OmnomIRC','','reload','THE GAME',line[3],False)
			handle.updateCurline() # others do this automatically
	def ircloop(self,fn):
		global sql
		if self.main:
			add = ')'
		else:
			add = 'T)'
		self.quitLoop = False
		while not self.stopnow and not self.quitLoop:
			try:
				self.readbuffer += self.s.recv(1024)
			except socket.error as e:
				if isinstance(e.args,tuple):
					if e == errno.EPIPE:
						self.stopnow = True
						self.restart = True
						self.quitMsg = 'Being stupid'
						print('Restarting due to stupidness ('+str(self.i)+add)
					elif e == errno.ECONNRESET:
						self.stopnow = True
						self.restart = True
						self.quitMsg = 'Being very stupid'
						print('Restarting because connection being reset by peer')
				time.sleep(0.1)
				if self.lastLineTime+90 <= time.time(): # allow up to 60 seconds lag
					self.stopnow = True
					self.restart = True
					self.lastLineTime = time.time()
					self.quitMsg = 'No pings (1)'
					print('Restarting due to no pings ('+str(self.i)+add)
			except Exception as inst:
				print(inst)
				traceback.print_exc()
				time.sleep(0.1)
				if self.lastLineTime+90 <= time.time(): # allow up to 60 seconds lag
					self.stopnow = True
					self.restart = True
					self.lastLineTime = time.time()
					self.quitMsg = 'No pings (2)'
					print('Restarting due to no pings ('+str(self.i)+add)
			temp=string.split(self.readbuffer,'\n')
			self.readbuffer = temp.pop()
			if self.lastPingTime+90 <= time.time(): # allow up to 60 seconds lag
				self.stopnow = True
				self.restart = True
				self.lastLineTime = time.time()
				self.quitMsg = 'No pings(3)'
				return
			if self.lastPingTime+30 <= time.time():
				self.send('PING %s' % time.time(),True)
				self.lastPingTime = time.time()
			for line in temp:
				print('('+str(self.i)+')'+self.recieveStr+' '+line)
				line=string.rstrip(line)
				line=string.split(line)
				try:
					self.lastLineTime = time.time()
					if(line[0]=='PING'):
						self.send('PONG %s' % line[1],True)
						continue
					if line[0]=='ERROR' and 'Closing Link' in line[1]:
						time.sleep(30)
						self.stopnow = True
						self.restart = True
						self.quitMsg = 'Closed link'
						print('Error when connecting, restarting bot ('+str(self.i)+add)
						return
					fn(line)
				except Exception as inst:
					print('('+str(self.i)+') parse Error')
					print(inst)
					traceback.print_exc()
		if self.stopnow:
			if self.quitMsg!='':
				self.send('QUIT :%s' % self.quitMsg,True,False)
				self.handleQuit(self.nick,self.quitMsg)
			if self.main:
				sql.query('DELETE FROM `irc_users` WHERE online = %d',[int(self.i)])
				handle.updateCurline() # others do this automatically
			try:
				self.s.close()
			except:
				pass
	def serveFn(self,line):
		if self.main:
			self.doMain(line)
	def delUsersInChan(self,c):
		c = self.chanToId(c)
		if c != -1:
			sql.query("DELETE FROM `irc_users` WHERE online = %d AND channel = '%s'",[int(self.i),c])
	def getUsersInChan(self,c):
		self.delUsersInChan(c)
		self.send('WHO %s' % c)
	def joinChans(self):
		#ca = []
		for i,c in self.chans.iteritems():
			self.send('JOIN %s' % c,True)
			#ca.append(c)
		#self.send('JOIN %s' % (','.join(ca)),True)
	def identServerFn(self,line):
		if line[1]=='376':
			self.motdEnd = True
		if not self.identified and ((line[1] == 'NOTICE' and 'NickServ' in line[0])):
			if self.identifiedStep==0:
				self.send('PRIVMSG NickServ :IDENTIFY %s' % self.ns,True)
				self.identifiedStep = 1
			elif self.identifiedStep==1:
				self.identified = True
		if self.motdEnd and self.identified:
			self.quitLoop = True
	def run(self):
		global sql
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
			if self.main:
				add = ')'
			else:
				add = 'T)'
			print('Starting bot... ('+str(self.i)+add)
			if self.main:
				sql.query('DELETE FROM `irc_users` WHERE online = %d',[int(self.i)])
			self.connectToIRC()
			self.readbuffer = ''
			
			if self.ns=='':
				self.identified = True
			self.ircloop(self.identServerFn)
			
			if not self.stopnow:
				print('Starting main loop... ('+str(self.i)+add)
				self.joinChans()
				self.ircloop(self.serveFn)
			if self.restart:
				print('Restarting bot ('+str(self.i)+add)
				time.sleep(15)
		print('Good bye from bot ('+str(self.i)+add)

#fetch lines off of OIRC
class OIRCLink(threading.Thread):
	def __init__(self):
		threading.Thread.__init__(self)
		self.stopnow = False
	def stopThread(self):
		print('Giving signal to quit OmnomIRC link...')
		self.stopnow = True
	def run(self):
		global sql,handle,config
		curline = 0
		while not self.stopnow:
			try:
				temp = handle.getCurline()
				if temp>curline:
					curline = temp
					res = sql.query('SELECT fromSource,channel,type,action,prikey,nick,message FROM irc_outgoing_messages')
					if len(res) > 0:
						print('(1)>> '+str(res))
						lastline = 0
						for row in res:
							try:
								for i in ['nick','type','message','channel']:
									row[i] = makeUnicode(row[i])
								try:
									int(row['channel'])
								except ValueError:
									continue
								handle.sendToOther(row['nick'],'',row['type'],row['message'],int(row['channel']),row['fromSource'])
								
								if row['type']=='topic':
									handle.sendTopicToOther(row['message'],int(row['channel']),row['fromSource'])
							except Exception as inst:
								print(inst)
								traceback.print_exc()
							lastline = row['prikey']
						sql.query('DELETE FROM `irc_outgoing_messages` WHERE prikey < %d',[int(lastline)+1])
			except Exception as inst:
				print(inst)
				traceback.print_exc()
			time.sleep(0.2)

#gCn bridge
connectedCalcs = []
class CalculatorThread(SocketServer.BaseRequestHandler):
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
	def stopThread(self):
		print('Giving signal to quit calculator...')
		self.stopnow = True
		self.send('\xAD'+('**** Server going down! ****').encode('ASCII'))
		self.request.close()
	def checkExit(self):
		if self.stopnow:
			print('exiting...')
			exit()
	def sendToIRC(self,t,m):
		c = self.chanToId(self.chan)
		if c!=-1:
			handle.sendToOther(self.calcName,'',t,m,c,self.i)
			sql.query("INSERT INTO `irc_lines` (`name1`,`name2`,`message`,`type`,`channel`,`time`,`online`) VALUES ('%s','%s','%s','%s','%s','%s',%d)",[self.calcName,'',m,t,c,str(int(time.time())),int(self.i)])
			handle.updateCurline()
	def sendLine(self,n1,n2,t,m,c,s): #name 1, name 2, type, message, channel, source
		c = self.idToChan(c)
		if c!=-1:
			if t=='message':
				self.send('\xAD'+'%s:%s' % (n1,m))
			elif t=='action':
				self.send('\xAD'+'*%s %s' % (n1,m))
			elif t=='join':
				self.send('\xAD'+'*%s has joined %s' % (n1,c))
			elif t=='part':
				self.send('\xAD'+'*%s has left %s (%s)' % (n1,c,m))
			elif t=='quit':
				self.send('\xAD'+'*%s has quit %s (%s)' % (n1,c,m))
			elif t=='mode':
				self.send('\xAD'+'*%s set %s mode %s' % (n1,c,m))
			elif t=='kick':
				self.send('\xAD'+'*%s has kicked %s from %s (%s)' % (n1,n2,c,m))
			elif t=='topic':
				self.send('\xAD'+'*%s has changed the topic to %s' % (n1,m))
			elif t=='nick':
				self.send('\xAD'+'*%s has changed nicks to %s' % (n1,n2))
	def send(self,message):
		message = '\xFF\x89\x00\x00\x00\x00\x00'+('Omnom').encode('ASCII')+struct.pack('<H',len(message))+message
		message = struct.pack('<H',len(message)+1)+('b').encode('ASCII')+message+('*').encode('ASCII')
		self.request.sendall(message)
	def idToChan(self,i):
		if i in self.chans:
			return self.chans[i]
		return -1
	def chanToId(self,c):
		for i,ch in self.chans.iteritems():
			if c.lower() == ch.lower():
				return i
		return -1
	def findchan(self,chan):
		for key,value in self.chans.iteritems():
			if chan.lower() == value.lower():
				return True
				break
		return False
	def handle(self):
		global config
		print(threading.current_thread())
		print('New calculator\n')
		self.i = handle.getCalcNetworkId()
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
		
		try:
			self.request.settimeout(10)
			connectedCalcs.append(self)
			while not self.stopnow:
				#data = self.rfile.readline()
				time.sleep(0.0001)
				try:
					data = self.request.recv(1024)
				except socket.timeout:
					continue
				except Exception as err:
					print('Error:')
					print(err)
					break
				if not data:  # EOF
					break
				printString = '';
				sendMessage = False
				if (data[2]=='j'):
					self.calcName=''
					self.chan=''
					for i in range(4, int(ord(data[3]))+4):
						self.chan=self.chan+data[i]
					for i in range(int(ord(data[3]))+5, int(ord(data[int(ord(data[3]))+4]))+int(ord(data[3]))+5):
						self.calcName=self.calcName+data[i]
					self.chan = self.chan.lower()
					printString+='Join-message recieved. Calc-Name:'+self.calcName+' Channel:'+self.chan+'\n'
					if not(self.findchan(self.chan)):
						printString+='Invalid channel, defaulting to '+self.defaultChan+'\n'
						self.chan=self.defaultChan
				if (data[2]=='c'):
					calcId=data[3:]
					printString+='Calc-message recieved. Calc-ID:'+calcId+'\n'
				if (data[2]=='b' or data[2]=='f'):
					if ord(data[17])==171:
						self.send('\xAB'+('OmnomIRC').encode('ASCII'))
						if not self.connectedToIRC:
							printString+=self.calcName+' has joined\n'
							self.connectedToIRC=True
							self.send('\xAD'+('**Now speeking in channel '+self.chan).encode('ASCII'))
							self.sendToIRC('join','')
							self.userJoin()
					elif ord(data[17])==172:
						if self.connectedToIRC:
							printString+=self.calcName+' has quit\n'
							self.connectedToIRC=False
							self.userPart()
							self.sendToIRC('quit','')
					elif ord(data[17])==173 and data[5:10]=='Omnom':
						printString+='msg ('+self.calcName+') '+data[data.find(':',18)+1:-1]+'\n'
						message=data[data.find(":",18)+1:-1]
						if message.split(' ')[0].lower()=='/join':
							if self.findchan(message[message.find(' ')+1:].lower()):
								self.sendToIRC('part','')
								self.userPart()
								self.chan=message[message.find(' ')+1:].lower()
								self.send('\xAD'+('**Now speeking in channel '+self.chan).encode('ASCII'))
								self.sendToIRC('join','')
								self.userJoin()
							else:
								self.send('\xAD'+('**Channel '+message[message.find(' ')+1:]+' doesn\'t exist!').encode('ASCII'))
						elif message.split(' ')[0].lower()=='/me':
							self.sendToIRC('action',message[message.find(' ')+1:])
						else:
							self.sendToIRC('message',message)
				
				if printString!='':
					print(threading.current_thread())
					print(printString)
			if self.connectedToIRC:
				self.userPart()
				self.sendToIRC('quit','client disconnect')
				
		except:
			traceback.print_exc()
			self.sendToIRC('quit','server error')
			if self.connectedToIRC:
				self.userPart()
		print(threading.current_thread())
		connectedCalcs.remove(self)
		print('Thread done\n')
class ThreadedTCPServer(SocketServer.ThreadingMixIn, SocketServer.TCPServer):
	allow_reuse_address = True
	pass

#main handler
class Main():
	def __init__(self):
		global config
		self.bots = []
	def updateCurline(self):
		global config,sql
		try:
			f = open(config.json['settings']['curidFilePath'],'w')
			f.write(str(sql.query("SELECT MAX(line_number) AS max FROM irc_lines")[0]['max']))
			f.close()
		except Exception as inst:
			print('curline error')
			print(inst)
			traceback.print_exc()
	def addUser(self,u,c,i):
		temp = sql.query("SELECT usernum FROM irc_users WHERE username='%s' AND channel='%s' AND online=%d",[u,c,int(i)])
		if(len(temp)==0):
			sql.query("INSERT INTO `irc_users` (`username`,`channel`,`online`) VALUES ('%s','%s',%d)",[u,c,int(i)])
		else:
			sql.query("UPDATE `irc_users` SET `isOnline`=1 WHERE `usernum`=%d",[int(temp[0]['usernum'])])
	def removeUser(self,u,c,i):
		sql.query("UPDATE `irc_users` SET `isOnline`=0 WHERE `username` = '%s' AND `channel` = '%s' AND online=%d",[u,c,int(i)])
	def getCurline(self):
		global config
		f = open(config.json['settings']['curidFilePath'])
		lines = f.readlines()
		f.close()
		if len(lines)>=1:
			return int(lines[0])
		return 0
	def getCalcNetworkId(self):
		return self.calcNetwork['id']
	def sendTopicToOther(self,s,c,i):
		for b in self.bots:
			if i != b.i:
				if b.topicbotExists ^ b.main:
					b.sendTopic(s,c)
	def sendToOtherRaw(self,s,ib):
		for b in self.bots:
			if ib != b.i and b.main:
				b.send(s)
	def sendToOther(self,n1,n2,t,m,c,s):
		for b in self.bots:
			try:
				if s != b.i and b.main:
					b.sendLine(n1,n2,t,m,c,s)
			except Exception as inst:
				print(inst)
				traceback.print_exc()
		if self.calcNetwork != -1:
			for calc in connectedCalcs:
				try:
					if calc.connectedToIRC and (not (s==self.calcNetwork['id'] and n1==calc.calcName)) and calc.idToChan(c).lower()==calc.chan.lower():
						calc.sendLine(n1,n2,t,m,c,s)
				except Exception as inst:
					print(inst)
					traceback.print_exc()
	def sigquit(self):
		print('sigquit')
	def serve(self):
		signal.signal(signal.SIGQUIT,self.sigquit)
		self.calcNetwork = -1
		for n in config.json['networks']:
			if n['enabled']:
				if n['type']==3: # irc
					haveTopicBot = n['config']['topic']['nick'] != ''
					self.bots.append(Bot(n['config']['main']['server'],n['config']['main']['port'],n['config']['main']['nick'],n['config']['main']['nickserv'],True,config.json['security']['ircPwd'],int(n['id']),haveTopicBot,n['config']['main']['nick'],n['config']['topic']['nick']))
					self.bots[len(self.bots)-1].start()
					if haveTopicBot:
						self.bots.append(Bot(n['config']['topic']['server'],n['config']['topic']['port'],n['config']['topic']['nick'],n['config']['topic']['nickserv'],False,config.json['security']['ircPwd'],int(n['id']),haveTopicBot,n['config']['main']['nick'],n['config']['topic']['nick']))
						self.bots[len(self.bots)-1].start()
				elif n['type']==2: # calc
					self.calcNetwork = n
		self.oircLink = OIRCLink()
		self.oircLink.start()
		try:
			if self.calcNetwork!=-1:
				sql.query('DELETE FROM `irc_users` WHERE online = %d',[self.calcNetwork['id']])
				self.gCn_server = ThreadedTCPServer((self.calcNetwork['config']['server'],self.calcNetwork['config']['port']),CalculatorThread)
				gCn_thread = threading.Thread(target=self.gCn_server.serve_forever)
				gCn_thread.daemon = True
				gCn_thread.start()
				self.gCn_server.serve_forever()
			else:
				while True:
					time.sleep(60)
		except KeyboardInterrupt:
			print('KeyboardInterrupt, exiting...')
			self.quit()
		except:
			traceback.print_exc()
	def quit(self,code=1):
		global connectedCalcs
		for b in self.bots:
			b.stopThread()
		self.oircLink.stopThread()
		if self.calcNetwork != -1:
			self.gCn_server.shutdown()
			for i in connectedCalcs:
				i.stopThread()
		sys.exit(code)
config = Config()
sql = Sql()
sql.query('DELETE FROM `irc_outgoing_messages`')
handle = Main()
handle.serve()