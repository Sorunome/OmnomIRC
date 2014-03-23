#    OmnomIRC COPYRIGHT 2010,2011 Netham45
#                       2012-2014 Sorunome
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
import threading,socket,string,time,sys,json,MySQLdb,traceback
print 'Starting OmnomIRC bot...'
DOCUMENTROOT = '/usr/share/nginx/html/oirc'
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
				if l.strip()=='JSONSTART':
					searchingJson = False
			else:
				if l.strip()=='JSONEND':
					break
				jsons += l + "\n"
		self.json = json.loads(jsons[:-1])
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
			db = MySQLdb.connect(config.json['sql']['server'],config.json['sql']['user'],config.json['sql']['passwd'],config.json['sql']['db'])
			cur = db.cursor()
			for i in range(len(p)):
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
			print '(sql) Error'
			print inst
			traceback.print_exc()
			return False
class Bot(threading.Thread):
	def __init__(self,server,port,nick,ns,chans,main,passwd,i):
		threading.Thread.__init__(self)
		self.stopnow = False
		self.server = server
		self.port = port
		self.nick = nick
		self.ns = ns
		self.chans = chans
		self.main = main
		self.passwd = passwd
		self.i = i
		self.userlist = {}
		if self.main:
			self.recieveStr = '>>'
			self.sendStr = '<<'
		else:
			self.recieveStr = 'T>'
			self.sendStr = 'T<'
	def stopThread(self):
		if self.main:
			add = ')'
		else:
			add = 'T)'
		print 'Giving signal to quit irc bot... ('+str(self.i)+add
		self.stopnow = True
	def sendTopic(self,s,c):
		self.s.sendall('TOPIC %s :%s\r\n' % (c,s))
		print '('+str(self.i)+')'+self.sendStr+' '+c+' '+s
	def send(self,s,override = False):
		if self.main or override:
			self.s.sendall('%s\r\n' % s)
			print '('+str(self.i)+')'+self.sendStr+' '+s
	def connectToIRC(self):
		self.s = socket.socket()
		self.s.settimeout(60)
		self.s.connect((self.server,self.port))
		self.send('USER %s %s %s :%s' % (self.nick,self.nick,self.nick,self.nick),True)
		self.send('NICK %s' % (self.nick),True)
	def addLine(self,n1,n2,t,m,c):
		global sql,handle
		print '(1)<< ',{'name1':n1,'name2':n2,'type':t,'message':m,'channel':c}
		sql.query("INSERT INTO `irc_lines` (`name1`,`name2`,`message`,`type`,`channel`,`time`,`online`) VALUES ('%s','%s','%s','%s','%s','%s',%d)",[str(n1),str(n2),str(m),str(t),str(c),str(int(time.time())),int(self.i)])
		if t=='topic':
			temp = sql.query("SELECT channum FROM `irc_topics` WHERE chan='%s'",[str(c).lower()])
			if len(temp)==0:
				sql.query("INSERT INTO `irc_topics` (chan,topic) VALUES('%s','%s')",[str(c).lower(),str(m)])
			else:
				sql.query("UPDATE `irc_topics` SET topic='%s' WHERE chan='%s'",[str(m),str(c).lower()])
		if t=='action' or t=='message':
			sql.query("UPDATE `irc_users` SET lastMsg='%s' WHERE username='%s' AND channel='%s' AND online=%d",[str(int(time.time())),str(n1),str(c),int(self.i)])
		handle.updateCurline()
	def addUser(self,u,c):
		if self.userlist.has_key(c):
			self.userlist[c].append(u)
		else:
			self.userlist[c] = [u]
		temp = sql.query("SELECT usernum FROM irc_users WHERE username='%s' AND channel='%s' AND online=%d",[str(u),str(c),int(self.i)])
		if(len(temp)==0):
			sql.query("INSERT INTO `irc_users` (`username`,`channel`,`online`) VALUES ('%s','%s',%d)",[str(u),str(c),int(self.i)])
		else:
			sql.query("UPDATE `irc_users` SET `isOnline`=1 WHERE `usernum`=%d",[int(temp[0]['usernum'])])
	def removeUser(self,u,c):
		global sql
		if self.userlist.has_key(c):
			self.userlist[c].remove(u)
		sql.query("UPDATE `irc_users` SET `isOnline`=0 WHERE `username` = '%s' AND `channel` = '%s' AND online=%d",[str(u),str(c),int(self.i)])
	def handleQuit(self,n,m):
		global handle
		for c,us in self.userlist.iteritems():
			for u in us:
				if u==n:
					self.removeUser(n,c)
					handle.sendToOther('PRIVMSG %s :(#)\x032* %s has quit %s (%s)' % (c,n,c,m),self.i)
					self.addLine(n,'','quit',m,c)
	def handleNickChange(self,old,new):
		global handle
		for c,us in self.userlist.iteritems():
			for u in us:
				if u==old:
					self.removeUser(old,c)
					self.addUser(new,c)
					handle.sendToOther('PRIVMSG %s :(#)\x033* %s has changed nicks to %s' % (c,old,new),self.i)
					self.addLine(old,new,'nick','',c)
	def doMain(self,line):
		global handle,config
		message = ' '.join(line[3:])[1:]
		nick = line[0].split('!')[0][1:]
		chan = line[2]
		if line[1]=='PRIVMSG':
			if line[2][0]!='#' and line[3] == ':DOTHIS' and line[4] == self.passwd:
				self.send(' '.join(line[5:]))
			elif line[2][0]=='#':
				if line[3]==':\x01ACTION' and message[-1:]=='\x01':
					handle.sendToOther('PRIVMSG %s :(#)\x036* %s %s' % (chan,nick,message[8:-1]),self.i)
					self.addLine(nick,'','action',message[8:-1],chan)
				else:
					handle.sendToOther('PRIVMSG %s :(#)<%s> %s' % (chan,nick,message),self.i)
					self.addLine(nick,'','message',message,chan)
		elif line[1]=='JOIN':
			handle.sendToOther('PRIVMSG %s :(#)\x033* %s has joined %s' % (chan[1:],nick,chan[1:]),self.i)
			self.addLine(nick,'','join','',chan[1:])
			self.addUser(nick,chan[1:])
		elif line[1]=='PART':
			handle.sendToOther('PRIVMSG %s :(#)\x032* %s has left %s (%s)' % (chan,nick,chan,message),self.i)
			self.addLine(nick,'','part',message,chan)
			self.removeUser(nick,chan)
		elif line[1]=='QUIT':
			self.handleQuit(nick,' '.join(line[2:])[1:])
		elif line[1]=='MODE':
			handle.sendToOther('PRIVMSG %s :(#)\x033* %s set %s mode %s' % (chan,nick,chan,' '.join(line[3:])),self.i)
			self.addLine(nick,'','mode',' '.join(line[3:]),chan)
		elif line[1]=='KICK':
			handle.sendToOther('PRIVMSG %s :(#)\x034 %s has kicked %s from %s (%s)' % (chan,nick,line[3],chan,' '.join(line[4:])[1:]),self.i)
			self.addLine(nick,line[3],'kick',' '.join(line[4:])[1:],chan)
			self.removeUser(line[3],chan)
		elif line[1]=='TOPIC':
			if nick.lower()!=config.json['irc']['topic']['nick'].lower() and nick.lower()!=config.json['irc']['main']['nick'].lower():
				handle.sendToOther('PRIVMSG %s :(#)\x033* %s has changed the topic to %s' % (chan,nick,message),self.i)
				self.addLine(nick,'','topic',message,chan)
				if config.json['irc']['topic']['nick']=='':
					handle.sendToOther('TOPIC %s :%s' % (chan,message),self.i)
				else:
					handle.sendTopicToOther(message,chan,self.i)
		elif line[1]=='NICK':
			self.handleNickChange(nick,line[2][1:])
		elif line[1]=='352':
			self.addUser(line[7],line[3])
		elif line[1]=='315':
			self.addLine('OmnomIRC','','reload','THE GAME',line[3])
			handle.updateCurline()
	def serve(self):
		global sql
		if self.main:
			add = ')'
		else:
			add = 'T)'
		print 'Entering main loop ('+str(self.i)+add
		while not self.stopnow:
			try:
				self.readbuffer += self.s.recv(1024)
			except Exception as inst:
				print inst
				traceback.print_exc()
			temp=string.split(self.readbuffer,'\n')
			self.readbuffer=temp.pop()
			for line in temp:
				print '('+str(self.i)+')'+self.recieveStr+' '+line
				line=string.rstrip(line)
				line=string.split(line)
				try:
					if(line[0]=='PING'):
						self.send('PONG %s' % line[1],True)
						continue
					if self.main:
						self.doMain(line)
				except Exception as inst:
					print '('+str(self.i)+') parse Error'
					print inst
					traceback.print_exc()
	def joinChans(self):
		for c in self.chans:
			self.send('JOIN %s' % c['chan'],True)
			if self.main:
				self.send('WHO %s' % c['chan'])
	def run(self):
		global sql
		if self.main:
			add = ')'
		else:
			add = 'T)'
		print 'Starting bot... ('+str(self.i)+add
		if self.main:
			sql.query('DELETE FROM `irc_users` WHERE online = %d',[int(self.i)])
		self.connectToIRC()
		self.readbuffer = ''
		motdEnd = False
		identified = False
		identifiedStep = 0
		if self.ns=='':
			identified = True
		while not self.stopnow and not (motdEnd and identified):
			try:
				self.readbuffer += self.s.recv(1024)
			except Exception as inst:
				print inst
				traceback.print_exc()
			temp=string.split(self.readbuffer,'\n')
			self.readbuffer=temp.pop()
			for line in temp:
				print '('+str(self.i)+')'+self.recieveStr+' '+line
				line=string.rstrip(line)
				line=string.split(line)
				try:
					if line[0]=='PING':
						self.send('PONG %s' % line[1],True)
						continue
					if line[1]=='376':
						motdEnd = True
					if not identified and ((line[1] == 'NOTICE' and 'NickServ' in line[0])):
						if identifiedStep==0:
							self.send('PRIVMSG NickServ :IDENTIFY %s' % self.ns,True)
							identifiedStep = 1
						elif identifiedStep==1:
							identified = True
				except Exception as inst:
					print inst
					traceback.print_exc()
		self.joinChans()
		self.serve()
class OIRCLink(threading.Thread):
	def __init__(self):
		threading.Thread.__init__(self)
		self.stopnow = False
	def stopThread(self):
		print 'Giving signal to quit OmnomIRC link...'
		self.stopnow = True
	def run(self):
		global sql,handle
		curline = 0
		while not self.stopnow:
			try:
				temp = handle.getCurline()
				if temp>curline:
					curline = temp
					res = sql.query('SELECT fromSource,channel,type,action,prikey,nick,message FROM irc_outgoing_messages')
					if len(res) > 0:
						print '(1)>> ',res
						lastline = 0
						for row in res:
							try:
								colorAdding = '\x0312(O)'
								if row['fromSource']==2:
									colorAdding = '\x037(C)'
								if row['channel'][0] != '#':
									continue
								if row['type']=='topic':
									handle.sendToOther('PRIVMSG %s :%s\x033 %s has changed the topic to %s' % (row['channel'],colorAdding,row['nick'],row['message']),1)
									if config.json['irc']['topic']['nick']=='':
										handle.sendToOther('TOPIC %s :%s' % (chan,message),1)
									else:
										handle.sendTopicToOther(message,chan,1)
								elif row['type']=='mode':
									handle.sendToOther('PRIVMSG %s :%s\x033 %s set %s mode %s' % (row['channel'],colorAdding,row['nick'],row['channel'],row['message']),1)
								else:
									if int(row['action']) == 0:
										handle.sendToOther('PRIVMSG %s :%s\x0f<%s> %s' % (row['channel'],colorAdding,row['nick'],row['message']),1)
									elif int(row['action']) == 1:
										handle.sendToOther('PRIVMSG %s :%s\x036* %s %s' % (row['channel'],colorAdding,row['nick'],row['message'][1:]),1)
							except Exception as inst:
								print inst
								traceback.print_exc()
							lastline = row['prikey']
						sql.query('DELETE FROM `irc_outgoing_messages` WHERE prikey < %d',[int(lastline)+1])
			except Exception as inst:
				print inst
				traceback.print_exc()
			time.sleep(0.2)
class Main():
	def __init__(self):
		global config
		self.mainBots = []
		self.topicBots = []
	def updateCurline(self):
		global config,sql
		try:
			f = open(config.json['settings']['curidFilePath'],'w')
			f.write(str(sql.query("SELECT MAX(line_number) AS max FROM irc_lines")[0]['max']))
			f.close()
		except Exception as inst:
			print 'curline error'
			print inst
			traceback.print_exc()
	def getCurline(self):
		global config
		f = open(config.json['settings']['curidFilePath'])
		lines = f.readlines()
		f.close()
		return int(lines[0])
	def sendTopicToOther(self,s,c,i):
		for b in self.topicBots:
			if i != b.i and not b.main:
				b.sendTopic(s,c)
	def sendToOther(self,s,ib):
		for b in self.mainBots:
			if ib != b.i and b.main:
				b.send(s)
	def serve(self):
		for s in config.json['irc']['main']['servers']:
			self.mainBots.append(Bot(s['server'],s['port'],config.json['irc']['main']['nick'],s['nickserv'],config.json['channels']+config.json['exChans'],True,config.json['irc']['password'],int(s['network'])))
			self.mainBots[len(self.mainBots)-1].start()
		self.oircLink = OIRCLink()
		self.oircLink.start()
		if config.json['irc']['topic']['nick']!='':
			for s in config.json['irc']['topic']['servers']:
				self.topicBots.append(Bot(s['server'],s['port'],config.json['irc']['topic']['nick'],s['nickserv'],config.json['channels']+config.json['exChans'],False,config.json['irc']['password'],int(s['network'])))
				self.topicBots[len(self.topicBots)-1].start()
		try:
			while True:
				time.sleep(60)
		except KeyboardInterrupt:
			print 'KeyboardInterrupt, exiting...'
			self.quit()
	def quit(self,code=1):
		for b in self.mainBots:
			b.stopThread()
		for b in self.topicBots:
			b.stopThread()
		self.oircLink.stopThread()
		sys.exit(code)
config = Config()
sql = Sql()
sql.query('DELETE FROM `irc_outgoing_messages`')
handle = Main()
handle.serve()