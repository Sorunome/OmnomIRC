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

import server,traceback,json,signal,time,pymysql,sys,socket

try:
	import memcache
	memcached = memcache.Client(['127.0.0.1:11211'],debug=0)
except:
	class Memcached_fake:
		def get(self,str):
			return False
		def set(self,str,val,time=0):
			return False
		def delete(self,str):
			return False
	memcached = Memcached_fake()

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

def stripIrcColors(s):
	return re.sub(r"(\x02|\x0F|\x16|\x1D|\x1F|\x03(\d{1,2}(,\d{1,2})?)?)",'',s)



#config handler
class Config:
	can_postload=False
	def __init__(self):
		self.readFile()
	def readFile(self):
		jsons = ''
		searchingJson = True
		f = open(DOCUMENTROOT+'/config.json.php')
		lines = f.readlines()
		f.close()
		for l in lines:
			if searchingJson:
				if l.strip()=='?>':
					searchingJson = False
			else:
				jsons += l + "\n"
		self.json = json.loads(jsons[:-1])
		if self.can_postload:
			self.postLoad()
	def postLoad(self):
		for i in range(len(self.json['networks'])):
			if self.json['networks'][i]['config'] == True:
				self.json['networks'][i]['config'] = sql.getVar('net_config_'+str(self.json['networks'][i]['id']))

#sql handler
class Sql:
	def __init__(self):
		global config
	def fetchOneAssoc(self,cur):
		data = cur.fetchone()
		if data == None:
			return None
		desc = cur.description
		ret = {}
		for (name,value) in zip(desc,data):
			ret[name[0]] = value
		print(ret)
		return ret
	def query(self,q,p = []):
		global config
		try:
			try:
				db = pymysql.connect(
					host=config.json['sql']['server'],
					user=config.json['sql']['user'],
					password=config.json['sql']['passwd'],
					db=config.json['sql']['db'],
					charset='utf8',
					cursorclass=pymysql.cursors.DictCursor)
			except:
				db = pymysql.connect(
					host=config.json['sql']['server'],
					user=config.json['sql']['user'],
					password=config.json['sql']['passwd'],
					db=config.json['sql']['db'],
					unix_socket='/var/run/mysqld/mysqld.sock',
					charset='utf8',
					cursorclass=pymysql.cursors.DictCursor)
			cur = db.cursor()
			
			cur.execute(q,tuple(p))
			db.commit()
			rows = []
			for row in cur:
				if row == None:
					break
				rows.append(row)
			cur.close()
			db.close()
			return rows
		except Exception as inst:
			print(config.json['sql'])
			print('(sql) Error',inst)
			traceback.print_exc()
			return False
	def getVar(self,var):
		res = self.query('SELECT value,type FROM irc_vars WHERE name=%s',[var])
		if isinstance(res,list) and len(res) > 0:
			res = res[0]
			if res['type'] == 0:
				return str(res['value'])
			if res['type'] == 1:
				return int(res['value'])
			if res['type'] == 2:
				return float(res['value'])
			if res['type'] == 3:
				try:
					return json.loads(res['value'])
				except:
					return False
			if res['type'] == 4:
				return bool(res['value'])
			if res['type'] == 5:
				try:
					return json.loads(res['value'])
				except:
					return False
			return False
		return False

class OircRelay:
	relayType = -1
	id = -1
	def __init__(self,n):
		self.id = int(n['id'])
		self.config = n['config']
		self.initRelay()
	def initRelay(self):
		return
	def startRelay_wrap(self):
		handle.removeAllUsersNetwork(self.id)
		self.startRelay()
	def startRelay(self):
		return
	def updateRelay_wrap(self,cfg):
		if self.id == int(cfg['id']):
			self.updateRelay(cfg['config'])
	def updateRelay(self,cfg):
		self.stopRelay_wrap()
		self.config = cfg
		self.startRelay_wrap()
	def stopRelay_wrap(self):
		handle.removeAllUsersNetwork(self.id)
		self.stopRelay()
	def stopRelay(self):
		return
	def relayMessage(self,n1,n2,t,m,c,s,uid):
		return
	def relayTopic(self,s,c,i):
		return



class RelayIRC(OircRelay):
	relayType = 3
	bot = False
	topicBot = False
	def getColorCache(self):
		colorAddingCache = {}
		for n in config.json['networks']:
			colorAdding = ''
			if n['irc']['color']==-2:
				colorAdding = '\x02'+n['irc']['prefix']+'\x02'
			elif n['irc']['color']==-1:
				colorAdding = n['irc']['prefix']
			else:
				colorAdding = '\x03'+str(n['irc']['color'])+n['irc']['prefix']+'\x0F'
			colorAddingCache[n['id']] = colorAdding
		return colorAddingCache
	def getIdChans(self):
		idchans = {}
		for ch in config.json['channels']:
			if ch['enabled']:
				for c in ch['networks']:
					if c['id'] == self.id:
						idchans[ch['id']] = c['name']
						break
		return idchans
	def newBot(self,t,cfg):
		import irc_wrap
		bot_class = type('Bot_OIRC_anon',(irc_wrap.Bot_OIRC,),{'handle':handle,'sql':sql})
		return bot_class(cfg[t]['server'],cfg[t]['port'],cfg[t]['nick'],cfg[t]['nickserv'],cfg[t]['ssl'],self.getIdChans(),t=='main',self.id,
						self.haveTopicBot,cfg['topic']['nick'],cfg['colornicks'],self.getColorCache())
	def initRelay(self):
		self.haveTopicBot = self.config['topic']['nick'] != ''
		self.bot = self.newBot('main',self.config)
		if self.haveTopicBot:
			self.topicBot = self.newBot('topic',self.config)
	def startRelay(self):
		self.bot.start()
		if self.haveTopicBot:
			self.topicBot.start()
	def updateRelay(self,cfg):
		haveTopicBot = cfg['topic']['nick'] != ''
		haveTopicBot_old = self.haveTopicBot
		self.haveTopicBot = haveTopicBot
		for bot_ref,t in [['bot','main'],['topicBot','topic']]:
			bot = getattr(self,bot_ref)
			if bot:
				if bot.ssl != cfg[t]['ssl'] or bot.server != cfg[t]['server'] or bot.port != cfg[t]['port']:
					bot.stopThread()
					setattr(self,bot_ref,self.newBot(t,cfg))
					getattr(self,bot_ref).start()
					continue
				bot.updateConfig(cfg[t]['nick'],cfg[t]['nickserv'],self.haveTopicBot,cfg['topic']['nick'],cfg['colornicks'],self.getIdChans(),self.getColorCache())
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

class RelayWebsockets(OircRelay):
	relayType = 1
	def initRelay(self):
		import websockets
		ws_handler = type('WebSocketsHandler_anon',(websockets.WebSocketsHandler,),{'handle':handle})
		if config.json['websockets']['ssl']:
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
		oircOnly = False
		try:
			c = int(c)
		except:
			oircOnly = True
		if hasattr(self.server,'inputHandlers'):
			for client in self.server.inputHandlers:
				try:
					if ((not client.banned) and
						(
							(
								(
									(not oircOnly) or c[0]=='@'
								)
								and
								c == client.chan
								and t!='server'
							)
							or
							(
								t=='server'
								and
								c==client.nick
								and
								n2==str(client.chan)
								and
								client.identified
							)
						)):
						client.sendLine(n1,n2,t,m,c,s,uid)
				except Exception as inst:
					print(inst)
					traceback.print_exc()


class RelayCalcnet(OircRelay):
	relayType = 2
	def initRelay(self):
		import calculators
		self.server = server.Server(self.config['server'],self.config['port'],type('CalculatorHandler_anon',(calculators.CalculatorHandler,),{'i':self.id}))
	def startRelay(self):
		self.server.start()
	def updateRelay(self,cfg):
		if self.config['server'] != cfg['server'] or self.config['port'] != cfg['port']:
			self.config = cfg
			self.stopRelay_wrap()
			self.startRelay_wrap()
		else:
			self.config = cfg
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
			for calc in self.server.inputHandlers:
				calc.updateChans(chans,defaultChan)
	def stopRelay(self):
		self.server.stop()
	def relayMessage(self,n1,n2,t,m,c,s,uid = -1):
		for calc in self.server.inputHandlers:
			try:
				if calc.connectedToIRC and (not (s==self.id and n1==calc.calcName)) and calc.idToChan(c).lower()==calc.chan.lower():
					calc.sendLine(n1,n2,t,m,c,s)
			except Exception as inst:
				print(inst)
				traceback.print_exc()




#fetch lines off of OIRC
class OIRCLink(server.ServerHandler):
	readbuffer = ''
	def setup(self):
		return socket.gethostbyname('localhost') == self.client_address[0]
	def recieve(self):
		try:
			data = makeUnicode(self.socket.recv(1024))
			if not data: # EOF
				return False
			self.readbuffer += data
		except:
			traceback.print_exc()
			return False
		temp = self.readbuffer.split('\n')
		self.readbuffer = temp.pop()
		for line in temp:
			try:
				data = json.loads(line)
				if data['t'] == 'server_updateconfig':
					handle.updateConfig()
				elif data['t'] == 'server_delete_modebuffer':
					handle.deleteModeBuffer(data['c'])
				else:
					handle.sendToOther(data['n1'],data['n2'],data['t'],data['m'],data['c'],data['s'],data['uid'],False)
					print('(oirc)>> '+str(data))
			except:
				traceback.print_exc()
		return True


#main handler
class Main():
	relays = []
	bots = []
	modeBuffer = {}
	def updateCurline(self):
		global config,sql
		try:
			f = open(config.json['settings']['curidFilePath'],'w')
			f.write(str(sql.query("SELECT MAX(line_number) AS max FROM irc_lines")[0]['max']))
			f.close()
		except Exception as inst:
			print('(handle) curline error',inst)
			traceback.print_exc()
	def addUser(self,u,c,i,uid=-1):
		temp = sql.query("SELECT usernum,isOnline FROM irc_users WHERE username=%s AND channel=%s AND online=%s",[u,c,int(i)])
		if(len(temp)==0):
			sql.query("INSERT INTO `irc_users` (`username`,`channel`,`online`,`uid`) VALUES (%s,%s,%s,%s)",[u,c,int(i),int(uid)])
			return True
		else:
			sql.query("UPDATE `irc_users` SET `isOnline`=1,`uid`=%s,`time`=0 WHERE `usernum`=%s",[int(uid),int(temp[0]['usernum'])])
			return temp[0]['isOnline'] == 0
	def removeUser(self,u,c,i):
		sql.query("UPDATE `irc_users` SET `isOnline`=0 WHERE `username` = %s AND `channel` = %s AND online=%s",[u,c,int(i)])
	def removeAllUsersChan(self,c,i):
		sql.query("UPDATE `irc_users` SET `isOnline`=0 WHERE `channel` = %s AND online=%s",[c,int(i)])
	def removeAllUsersNetwork(self,i):
		sql.query("UPDATE `irc_users` SET `isOnline`=0 WHERE `online`=%s",[int(i)])
	def getCurline(self):
		global config
		f = open(config.json['settings']['curidFilePath'])
		lines = f.readlines()
		f.close()
		if len(lines)>=1:
			return int(lines[0])
		return 0
	def deleteModeBuffer(self,chan):
		self.modeBuffer.pop(chan,False)
	def getModeString(self,chan):
		if chan in self.modeBuffer:
			return self.modeBuffer[chan]
		res = sql.query("SELECT `modes` FROM `irc_channels` WHERE chan=LOWER(%s)",[makeUnicode(chan)])
		if len(res)==0:
			self.modeBuffer[chan] = '+-'
			return '+-'
		self.modeBuffer[chan] = res[0]['modes']
		return res[0]['modes']
	def isChanOfMode(self,chan,char,default = False):
		res = self.getModeString(chan)
		try:
			char = res.index(char)
			minus = res.index('-')
		except:
			return default
		return char < minus
	def sendTopicToOther(self,s,c,i):
		oircOnly = False
		try:
			int(c)
		except:
			oircOnly = True
		for r in self.relays:
			if (oircOnly and r.relayType==1) or not oircOnly:
				r.relayTopic(s,c,i)
	def sendToOther(self,n1,n2,t,m,c,s,uid = -1,do_sql = True):
		if self.isChanOfMode(c,'c'):
			m = stripIrcColors(m)
		
		oircOnly = (t in ('join','part','quit') and uid!=-1)
		try:
			c = int(c)
		except:
			oircOnly = True
		for r in self.relays:
			if (oircOnly and r.relayType==1) or not oircOnly:
				try:
					r.relayMessage(n1,n2,t,m,c,s,uid)
				except:
					traceback.print_exc()
		
		print('(handle) (relay) '+str({'name1':n1,'name2':n2,'type':t,'message':m,'channel':c,'source':s,'uid':uid}))
		if do_sql:
			c = makeUnicode(str(c))
			sql.query("INSERT INTO `irc_lines` (`name1`,`name2`,`message`,`type`,`channel`,`time`,`online`,`uid`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s)",[n1,n2,m,t,c,str(int(time.time())),int(s),uid])
			try:
				lines_cached = memcached.get('oirc_lines_'+c)
				if lines_cached:
					try:
						lines_cached = json.loads(lines_cached)
						if len(lines_cached) > 200:
							lines_cached.pop(0)
						lines_cached.append({
							'curLine': 0,
							'type': t,
							'network': int(s),
							'time': int(time.time()),
							'name': n1,
							'message': m,
							'name2': n2,
							'chan': c,
							'uid': uid
						})
						memcached.set('oirc_lines_'+c,json.dumps(lines_cached,separators=(',',':')),int(time.time())+(60*60*24*3))
					except:
						traceback.print_exc()
						memcached.delete('oirc_lines_'+c)
			except Exception as inst:
				print('(handle) (relay) ERROR: couldn\'t update memcached: ',inst)
			if t=='topic':
				memcached.set('oirc_topic_'+c,m)
				temp = sql.query("SELECT channum FROM `irc_channels` WHERE chan=%s",[c.lower()])
				if len(temp)==0:
					sql.query("INSERT INTO `irc_channels` (chan,topic) VALUES(%s,%s)",[c.lower(),m])
				else:
					sql.query("UPDATE `irc_channels` SET topic=%s WHERE chan=%s",[m,c.lower()])
			if t=='action' or t=='message':
				sql.query("UPDATE `irc_users` SET lastMsg=%s WHERE username=%s AND channel=%s AND online=%s",[str(int(time.time())),n1,c,int(s)])
		self.updateCurline()
	def findRelay(self,id):
		for r in self.relays:
			if id == r.id:
				return r
		return False
	def addRelay(self,n):
		if n['type']==3: # irc
			self.relays.append(RelayIRC(n))
		elif n['type']==2: # calc
			self.relays.append(RelayCalcnet(n))
		elif n['type']==-1: # websocket, we only allow one of this type!
			found = False
			for r in self.relays:
				if r.id == -1:
					found = True
					break
			if not found:
				self.relays.append(RelayWebsockets(n))
	def get_net_cheat(self):
		net_cheat = config.json['networks'][:]
		net_cheat = [{
			'id':-1,
			'type':-1,
			'enabled':config.json['websockets']['use'],
			'config':config.json['websockets']
		}] + net_cheat
		return net_cheat
	def updateConfig(self):
		print('(handle) Got signal to update config!')
		config.readFile()
		
		for n in self.get_net_cheat():
			r = self.findRelay(n['id'])
			if not n['enabled'] and r:
				r.stopRelay_wrap()
				self.relays.remove(r)
				continue
			if n['enabled']:
				if r:
					r.updateRelay_wrap(n)
				else:
					size_before = len(self.relays)
					self.addRelay(n)
					if size_before < len(self.relays):
						self.relays[len(self.relays)-1].startRelay_wrap() # start it straigt away!
	def sigquit(self,e,s):
		print('(handle) sigquit')
		self.quit()
	def serve(self):
		signal.signal(signal.SIGQUIT,self.sigquit)
		self.calcNetwork = -1
		
		
		for n in self.get_net_cheat():
			if n['enabled']:
				self.addRelay(n)
		
		
		self.oircLink = server.Server('localhost',config.json['settings']['botPort'],OIRCLink)
		self.oircLink.start()
		
		try:
			for r in self.relays:
				r.startRelay_wrap()
			
			while True:
				time.sleep(30)
		except KeyboardInterrupt:
			print('(handle) KeyboardInterrupt, exiting...')
			self.quit()
		except:
			traceback.print_exc()
	def quit(self,code=1):
		global config
		for r in self.relays:
			r.stopRelay_wrap()
		self.oircLink.stop()
		
		sys.exit(code)

if __name__ == "__main__":
	print('Starting OmnomIRC bot...')
	config = Config()
	sql = Sql()
	config.can_postload = True
	config.postLoad()
	handle = Main()
	handle.serve()