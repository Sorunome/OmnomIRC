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

import irc,json,oirc_include as oirc

relayType = 3
defaultCfg = {
	'main':{
		'nick':'OmnomIRC',
		'server':'irc server',
		'port':6667,
		'nickserv':'',
		'ssl':False
	},
	'topic':{
		'nick':'',
		'server':'irc server',
		'port':6667,
		'nickserv':'',
		'ssl':False
	},
	'colornicks':False
}
name = 'IRC'
editPattern = [
	{
		'name':'Color Nicks',
		'type':'checkbox',
		'var':'colornicks'
	},
	{
		'type':'newline'
	},
	{
		'name':'Nick',
		'type':'text',
		'var':'main/nick'
	},
	{
		'name':'Server',
		'type':'text',
		'var':'main/server'
	},
	{
		'name':'Port',
		'type':'number',
		'var':'main/port'
	},
	{
		'name':'SSL',
		'type':'checkbox',
		'var':'main/ssl'
	},
	{
		'name':'Nickserv (leave emtpy for none)',
		'type':'text',
		'var':'main/nickserv'
	},
	{
		'name':'Advanced settings',
		'type':'more',
		'pattern':[
			{
				'name':'Topicbot',
				'type':'info'
			},
			{
				'name':'Nick (leave emtpy for no seperate topic bot)',
				'type':'text',
				'var':'topic/nick'
			},
			{
				'name':'Server',
				'type':'text',
				'var':'topic/server'
			},
			{
				'name':'Port',
				'type':'number',
				'var':'topic/port'
			},
			{
				'name':'SSL',
				'type':'checkbox',
				'var':'topic/ssl'
			},
			{
				'name':'Nickserv (leave emtpy for none)',
				'type':'text',
				'var':'topic/nickserv'
			}
		]
	}
]

class Relay(oirc.OircRelay):
	relayType = -42
	bot = False
	topicBot = False
	def getColorCache(self):
		colorAddingCache = {}
		for n in self.handle.config.json['networks']:
			colorAdding = ''
			if n['irc']['color']==-2:
				colorAdding = '\x02'+n['irc']['prefix']+'\x02'
			elif n['irc']['color']==-1:
				colorAdding = n['irc']['prefix']
			else:
				colorAdding = '\x03'+str(n['irc']['color'])+n['irc']['prefix']+'\x0F'
			colorAddingCache[n['id']] = colorAdding
		return colorAddingCache
	def newBot(self,t,cfg):
		bot_class = self.getHandle(Bot_OIRC)
		return bot_class(cfg[t]['server'],cfg[t]['port'],cfg[t]['nick'],cfg[t]['nickserv'],cfg[t]['ssl'],t=='main',
						self.haveTopicBot,cfg['topic']['nick'],cfg['colornicks'],self.getColorCache())
	def initRelay(self):
		self.relayType = relayType
		self.haveTopicBot = self.config['topic']['nick'] != ''
		self.bot = self.newBot('main',self.config)
		if self.haveTopicBot:
			self.topicBot = self.newBot('topic',self.config)
	def startRelay(self):
		self.bot.start()
		if self.haveTopicBot:
			self.topicBot.start()
	def updateRelay(self,cfg,chans):
		haveTopicBot = cfg['topic']['nick'] != ''
		haveTopicBot_old = self.haveTopicBot
		self.haveTopicBot = haveTopicBot
		self.channels = chans
		for bot_ref,t in [['bot','main'],['topicBot','topic']]:
			bot = getattr(self,bot_ref)
			if bot:
				if bot.ssl != cfg[t]['ssl'] or bot.server != cfg[t]['server'] or bot.port != cfg[t]['port']:
					bot.stopThread()
					setattr(self,bot_ref,self.newBot(t,cfg))
					getattr(self,bot_ref).start()
					continue
				bot.updateConfig(cfg[t]['nick'],cfg[t]['nickserv'],self.haveTopicBot,cfg['topic']['nick'],cfg['colornicks'],self.channels,self.getColorCache())
		if haveTopicBot_old != haveTopicBot:
			self.bot.topicbotExists = self.haveTopicBot
			if self.haveTopicBot: # we need to generate a new bot!
				self.topicBot = self.newBot('topic',cfg)
				self.topicBot.start()
			else: # we need to remove a bot!
				self.topicBot.stopThread()
				self.topicBot = False
		self.config = cfg
	def stopRelay(self):
		self.bot.stopThread()
		if self.haveTopicBot:
			self.topicBot.stopThread()
	def relayMessage(self,n1,n2,t,m,c,s,uid):
		try:
			if s != self.id:
				self.bot.sendLine(n1,n2,t,m,c,s)
		except Exception as inst:
			print(inst)
			traceback.print_exc()
	def relayTopic(self,s,c,i):
		if i != self.id:
			if self.haveTopicBot:
				self.topicBot.sendTopic(s,c)
			else:
				self.bot.sendTopic(s,c)
	def joinThread(self):
		try:
			self.bot.join()
		except:
			pass
		try:
			self.topicBot.join()
		except:
			pass


#irc bot
class Bot_OIRC(irc.Bot,oirc.OircRelayHandle):
	def __init__(self,server,port,nick,ns,dssl,main,tbe,tn,colornicks,colorCache):
		chans = []
		for ii,c in self.channels.items():
			chans.append(c.lower())
		
		irc.Bot.__init__(self,server,port,nick,dssl,ns,chans)
		
		self.topicbotExists = tbe
		self.topicNick = tn
		self.colornicks = colornicks
		self.colorAddingCache = colorCache
		self.userlist = {}
		self.main = main
		if not main:
			self.log_prefix = '[Topicbot] '
	def log(self,s,level='info'): # re-write the bot handler
		if level=='error':
			self.log_error(s)
		else:
			self.log_info(s)
	def send_safe(self,s):
		if self.main:
			self.send(s)
	def updateConfig(self,nick,ns,tbe,tn,colornicks,idchans,colorCache):
		self.topicbotExists = tbe
		self.topicNick = tn
		self.colornicks = colornicks
		
		if self.ns != ns:
			self.ns = ns
			if ns == '':
				self.send('PRIVMSG NickServ :LOGOUT')
			else:
				self.send('PRIVMSG NickServ :IDENTIFY %s' % (ns))
		
		if self.nick != nick:
			self.nick = nick
			self.nick_want = nick
			self.send('NICK %s' % (nick),True)
		
		self.colorAddingCache = colorCache
		self.channels = idchans
		chans = []
		for i,c in self.channels.items():
			chans.append(c.lower())
		self.updateChans(chans)
	def sendTopic(self,s,c):
		c = self.idToChan(c)
		if c != '' and (self.topicbotExists ^ self.main):
			self.s.sendall(bytes('TOPIC %s :%s\r\n' % (c,s),'utf-8'))
			self.log_info('>> '+c+' '+s)
	def colorizeNick(self,n):
		rcolors = ['03','04','06','08','09','10','11','12','13']
		i = 0
		for c in n:
			i += ord(c)
		return '\x03'+rcolors[i % 9]+n+'\x0F'
	def sendLine(self,n1,n2,t,m,c,s): #name 1, name 2, type, message, channel, source
		c = self.idToChan(c)
		if c != '':
			colorAdding = ''
			if s in self.colorAddingCache:
				colorAdding = self.colorAddingCache[s]
			if self.colornicks:
				if n1 != '':
					n1 = self.colorizeNick(n1)
				if n2 != '':
					n2 = self.colorizeNick(n2)
			if colorAdding!='':
				if t=='message':
					self.send_safe('PRIVMSG %s :%s<%s> %s' % (c,colorAdding,n1,m))
				elif t=='action':
					self.send_safe('PRIVMSG %s :%s\x036* %s %s' % (c,colorAdding,n1,m))
				elif t=='join':
					self.send_safe('PRIVMSG %s :%s\x033* %s has joined %s' % (c,colorAdding,n1,c))
				elif t=='part':
					self.send_safe('PRIVMSG %s :%s\x032* %s has left %s (%s)' % (c,colorAdding,n1,c,m))
				elif t=='quit':
					self.send_safe('PRIVMSG %s :%s\x032* %s has quit %s (%s)' % (c,colorAdding,n1,c,m))
				elif t=='mode':
					self.send_safe('PRIVMSG %s :%s\x033* %s set %s mode %s' % (c,colorAdding,n1,c,m))
				elif t=='kick':
					self.send_safe('PRIVMSG %s :%s\x034* %s has kicked %s from %s (%s)' % (c,colorAdding,n1,n2,c,m))
				elif t=='topic':
					self.send_safe('PRIVMSG %s :%s\x033* %s has changed the topic to %s' % (c,colorAdding,n1,m))
				elif t=='nick':
					self.send_safe('PRIVMSG %s :%s\x033* %s has changed nicks to %s' % (c,colorAdding,n1,n2))
	def addLine(self,n1,n2,t,m,c):
		c = self.chanToId(c)
		if c != -1:
			self.handle.sendToOther(n1,n2,t,m,c,self.id)
	def addUser(self,u,c):
		c = self.chanToId(c)
		if c != -1:
			if c in self.userlist:
				self.userlist[c].append(u)
			else:
				self.userlist[c] = [u]
			self.handle.addUser(u,c,self.id)
	def removeUser(self,u,c):
		c = self.chanToId(c)
		if c != -1:
			if c in self.userlist:
				self.userlist[c].remove(u)
			self.handle.removeUser(u,c,self.id)
	def handleQuit(self,n,m):
		for c,us in self.userlist.items():
			removedUsers = []
			for u in us:
				if u==n:
					c = self.idToChan(c) # userlist array uses ids
					if c != '':
						self.removeUser(n,c)
						if not n in removedUsers:
							self.addLine(n,'','quit',m,c)
							removedUsers.append(n);
	def handleNickChange(self,old,new):
		global handle
		for c,us in self.userlist.items():
			changedNicks = []
			for u in us:
				if u==old:
					c = self.idToChan(c) # userlist array uses ids
					if c != '':
						self.removeUser(old,c)
						self.addUser(new,c)
						if not new in changedNicks:
							self.addLine(old,new,'nick','',c)
							changedNicks.append(new)
		return False
	def sendUserList(self,nick,chan):
		try:
			cid = self.chanToId(chan)
			if cid == -1:
				return
			if not (cid in self.userlist and nick in self.userlist[cid]):
				return
			users = self.handle.sql.query("SELECT `username` FROM `{db_prefix}users` WHERE `channel`=%s AND `isOnline`=1 AND `online`<>%s AND `username` IS NOT NULL",[cid,self.id])
			userchunks = []
			chunk = []
			for u in users:
				chunk.append(u['username'])
				if len(chunk) >= 5:
					userchunks.append(json.dumps(chunk,separators=(',',':')))
					chunk = []
			if len(chunk) > 0:
				userchunks.append(json.dumps(chunk,separators=(',',':')))
			for c in userchunks:
				self.send_safe('PRIVMSG '+nick+' :OIRCUSERS '+chan+' '+c)
		except:
			self.log_error(traceback.format_exc())
			return
	def doMain(self,line):
		for i in range(len(line)):
			line[i] = oirc.makeUnicode(line[i])
			
		message = ' '.join(line[3:])[1:]
		
		nick = line[0].split('!')[0][1:]
		chan = line[2]
		if chan[0]!='#':
			chan = chan[1:]
		if line[1]=='PRIVMSG':
			if line[2][0]!='#':
				if line[3] == ':DOTHIS' and line[4] == self.handle.config.json['security']['ircPwd']:
					self.send_safe(' '.join(line[5:]))
				elif line[3] == ':GETUSERLIST' and len(line) > 4:
					self.sendUserList(nick,line[4])
			elif line[2][0]=='#':
				if line[3]==':\x01ACTION' and message[-1:]=='\x01':
					self.addLine(nick,'','action',message[8:-1],chan)
				else:
					self.addLine(nick,'','message',message,chan)
		elif line[1]=='JOIN':
			self.addLine(nick,'','join','',chan)
			self.addUser(nick,chan)
			if nick.lower()==self.nick.lower():
				self.getUsersInChan(chan)
		elif line[1]=='PART':
			self.addLine(nick,'','part',message,chan)
			self.removeUser(nick,chan)
			if nick.lower()==self.nick.lower():
				self.delUsersInChan(chan)
		elif line[1]=='QUIT' and nick.lower().rstrip('_')!=self.topicNick.lower().rstrip('_'): # topicbot has its own quit messages
			self.handleQuit(nick,' '.join(line[2:])[1:])
		elif line[1]=='MODE':
			self.addLine(nick,'','mode',' '.join(line[3:]),chan)
		elif line[1]=='KICK':
			self.addLine(nick,line[3],'kick',' '.join(line[4:])[1:],chan)
			self.removeUser(line[3],chan)
		elif line[1]=='TOPIC':
			if nick.lower()!=self.nick.lower() and nick.lower().rstrip('_')!=self.topicNick.lower().rstrip('_'):
				self.addLine(nick,'','topic',message,chan)
				self.handle.sendTopicToOther(message,self.chanToId(chan),self.id)
		elif line[1]=='NICK':
			self.handleNickChange(nick,line[2][1:])
		elif line[1]=='352':
			self.addUser(line[7],line[3])
		elif line[1]=='315':
			self.addLine('OmnomIRC','','reload_userlist','THE GAME',line[3])
	def serveFn(self,line):
		if self.main:
			self.doMain(line)
	def delUsersInChan(self,c):
		c = self.chanToId(c)
		if c != -1:
			self.handle.removeAllUsersChan(c,self.id)
	def getUsersInChan(self,c):
		self.delUsersInChan(c)
		self.send_safe('WHO %s' % c)
