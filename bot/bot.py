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

import server,traceback,signal,json,time,datetime,pymysql,sys,re,os,argparse,requests,oirc_include as oirc

RSP_RESTART = 101
RSP_STARTFAIL = 2
try:
	import memcache
	memcached = memcache.Client(['127.0.0.1:11211'],debug=0)
except:
	print('no memcached available')
	class Memcached_fake:
		def get(self,str):
			return False
		def set(self,str,val,time=0):
			return False
		def delete(self,str):
			return False
	memcached = Memcached_fake()

#config handler
class Config:
	can_postload=False
	def __init__(self):
		self.readFile()
	def readFile(self):
		jsons = ''
		searchingJson = True
		f = open(oirc.DOCUMENTROOT+'/config.json.php')
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
	db = False
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
	def getDbCursor(self):
		try:
			return self.db.cursor()
		except:
			handle.log('sql','info','creating new SQL connection...')
			try:
				self.db = pymysql.connect(
					host=config.json['sql']['server'],
					user=config.json['sql']['user'],
					password=config.json['sql']['passwd'],
					db=config.json['sql']['db'],
					unix_socket='/var/run/mysqld/mysqld.sock',
					charset='utf8',
					cursorclass=pymysql.cursors.DictCursor)
			except:
				try:
					self.db = pymysql.connect(
						host=config.json['sql']['server'],
						user=config.json['sql']['user'],
						password=config.json['sql']['passwd'],
						db=config.json['sql']['db'],
						charset='utf8',
						cursorclass=pymysql.cursors.DictCursor)
				except:
					try:
						self.db = pymysql.connect(
							host=config.json['sql']['server'],
							user=config.json['sql']['user'],
							passwd=config.json['sql']['passwd'],
							db=config.json['sql']['db'],
							unix_socket='/var/run/mysqld/mysqld.sock',
							charset='utf8',
							cursorclass=pymysql.cursors.DictCursor)
					except:
						self.db = pymysql.connect(
							host=config.json['sql']['server'],
							user=config.json['sql']['user'],
							passwd=config.json['sql']['passwd'],
							db=config.json['sql']['db'],
							charset='utf8',
							cursorclass=pymysql.cursors.DictCursor)
			return self.db.cursor()
	def query(self,q,p = []):
		global config
		try:
			cur = self.getDbCursor()
			try:
				cur.execute(q.replace('{db_prefix}',config.json['sql']['prefix']),tuple(p))
			except:
				try:
					self.db.close()
				except:
					pass
				self.db = False
				cur = self.getDbCursor()
				cur.execute(q.replace('{db_prefix}',config.json['sql']['prefix']),tuple(p))
			self.db.commit()
			rows = []
			for row in cur:
				if row == None:
					break
				rows.append(row)
			cur.close()
			return rows
		except Exception as inst:
			handle.log('sql','error',str(inst))
			handle.log('sql','error',traceback.format_exc())
			return False
	def getVar(self,var):
		res = self.query('SELECT value,type FROM {db_prefix}vars WHERE name=%s',[var])
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
	def close(self):
		try:
			self.db.close()
		except:
			pass


#fetch lines off of OIRC
class OIRCLink(server.ServerHandler):
	readbuffer = ''
	def recieve(self):
		try:
			data = oirc.makeUnicode(self.socket.recv(1024))
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
				handle.log('oirc_link','info','>> '+str(data))
				
				if data['t'] == 'server_updateconfig':
					handle.updateConfig()
				elif data['t'] == 'server_delete_modebuffer':
					handle.deleteModeBuffer(data['c'])
				elif data['t'] == 'server_updaterelaytypes':
					handle.updateRelayTypes()
				elif data['t'] == 'server_restart':
					handle.quit(RSP_RESTART)
				elif data['t'] == 'server_getRelayTypes':
					s = json.dumps(handle.getRelayTypesData())+'\n'
					handle.log('oirc_link','info','Sending out network information... '+str(s))
					self.socket.sendall(bytes(s,'utf-8'))
				elif data['t'] == 'server_updateFile':
					try:
						if '..' in data['n2']:
							raise Exception('invalid local file path')
						r = requests.get('https://omnomirc.omnimaga.org/'+data['n1'])
						r.raise_for_status()
						with open(FILEROOT+'/'+data['n2'],'wb+') as f:
							f.write(r.content)
						self.socket.sendall(bytes('success\n','utf-8'))
					except:
						self.socket.sendall(bytes('error\n','utf-8'))
				else:
					handle.sendToOther(data['n1'],data['n2'],data['t'],data['m'],data['c'],data['s'],data['uid'],False)
			except:
				handle.log('oirc_link','error',traceback.format_exc())
		return True


#main handler
class Main():
	relayTypes = {}
	relays = []
	bots = []
	modeBuffer = {}
	chanIds = []
	live = True
	networksCache = {}
	def __init__(self):
		self.config = config
		self.sql = sql
		self.updateRelayTypes()
		self.updateChanIds()
	def log(self,id,level,message,prefix = ''):
		if isinstance(id,int):
			if not id in self.networksCache:
				for n in config.json['networks']:
					if n['id'] == id:
						self.networksCache[id] = n['name']
						break
				if not id in self.networksCache:
					self.networksCache[id] = str(id)
			id = self.networksCache[id]
		id = str(id)
		with open(args.logpath+'/omnomirc.'+level,'a+') as f:
			s = datetime.datetime.now().strftime('[%a, %d %b %Y %H:%M:%S.%f]')+' ['+id+'] '+prefix+message
			f.write(s+'\n')
			if args.verbose and level == 'info': # we want all the prefixes and stuff
				print(s)
		if level == 'error':
			self.log(id,'info',message,'ERROR: ')
	def log_info(self,s):
		self.log('handle','info',s)
	def log_error(self,s):
		self.log('handle','error',s)
	def updateRelayTypes(self):
		for f in os.listdir('.'):
			relay_file = re.match(r'^relay_(\w+)\.py$',f)
			if relay_file:
				relay_file = relay_file.group(1)
				try:
					self.log_info('Found relay "'+relay_file+'", importing...')
					relay = __import__('relay_'+relay_file)
					self.relayTypes[relay.relayType] = {
						'module':relay,
						'file':relay_file,
						'class':relay.Relay,
						'defaultCfg':relay.defaultCfg,
						'name':relay.name,
						'editPattern':relay.editPattern
					}
				except Exception as inst:
					self.log_error('Error importing relay "'+relay_file+'": '+str(inst))
					self.log_error(traceback.format_exc())
	def getRelayTypesData(self):
		a = []
		for i,v in self.relayTypes.items():
			if i!=-1:
				a.append({
					'id':i,
					'name':v['name'],
					'defaultCfg':v['defaultCfg'],
					'editPattern':v['editPattern']
				})
		return a
	def updateCurline(self):
		global config,sql
		try:
			f = open(config.json['settings']['curidFilePath'],'w')
			f.write(str(sql.query("SELECT MAX(line_number) AS max FROM {db_prefix}lines")[0]['max']))
			f.close()
		except Exception as inst:
			self.log_error('curline error: '+str(inst))
			self.log_error(traceback.format_exc())
	def addUser(self,u,c,i,uid=-1):
		temp = sql.query("SELECT usernum,isOnline FROM {db_prefix}users WHERE username=%s AND channel=%s AND online=%s",[u,c,int(i)])
		if(len(temp)==0):
			sql.query("INSERT INTO `{db_prefix}users` (`username`,`channel`,`online`,`uid`) VALUES (%s,%s,%s,%s)",[u,c,int(i),int(uid)])
			return True
		else:
			sql.query("UPDATE `{db_prefix}users` SET `isOnline`=1,`uid`=%s,`time`=0 WHERE `usernum`=%s",[int(uid),int(temp[0]['usernum'])])
			return temp[0]['isOnline'] == 0
	def removeUser(self,u,c,i):
		sql.query("UPDATE `{db_prefix}users` SET `isOnline`=0 WHERE `username` = %s AND `channel` = %s AND online=%s",[u,c,int(i)])
	def timeoutUser(self,u,c,i):
		sql.query("UPDATE `{db_prefix}users` SET `time` = UNIX_TIMESTAMP(CURRENT_TIMESTAMP) WHERE `username` = %s AND `channel` = %s AND online=%s",[u,c,int(i)])
	def removeAllUsersChan(self,c,i):
		sql.query("UPDATE `{db_prefix}users` SET `isOnline`=0 WHERE `channel` = %s AND online=%s",[c,int(i)])
	def removeAllUsersNetwork(self,i):
		sql.query("UPDATE `{db_prefix}users` SET `isOnline`=0 WHERE `online`=%s",[int(i)])
	def deleteModeBuffer(self,chan):
		self.modeBuffer.pop(chan,False)
	def getModeString(self,chan):
		if chan in self.modeBuffer:
			return self.modeBuffer[chan]
		res = sql.query("SELECT `modes` FROM `{db_prefix}channels` WHERE chan=LOWER(%s)",[oirc.makeUnicode(chan)])
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
		
		oircOnly = False
		try:
			c = int(c)
		except:
			oircOnly = True
		
		self.log_info('(relay) '+str({'name1':n1,'name2':n2,'type':t,'message':m,'channel':c,'source':s,'uid':uid}))
		
		if not oircOnly:
			oircOnly = (t in ('join','part','quit') and uid!=-1)
			if not str(c) in self.chanIds:
				self.log_error('(relay)')
				print('(handle) (relay) Invalid channel '+str(c)+', dropping message')
				return
		
		
		for r in self.relays:
			if (oircOnly and r.relayType==-1) or not oircOnly:
				try:
					r.relayMessage(n1,n2,t,m,c,s,uid)
				except:
					traceback.print_exc()
		
		if do_sql:
			c = oirc.makeUnicode(str(c))
			sql.query("INSERT INTO `{db_prefix}lines` (`name1`,`name2`,`message`,`type`,`channel`,`time`,`online`,`uid`) VALUES (%s,%s,%s,%s,%s,%s,%s,%s)",[n1,n2,m,t,c,str(int(time.time())),int(s),uid])
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
				temp = sql.query("SELECT channum FROM `{db_prefix}channels` WHERE chan=%s",[c.lower()])
				if len(temp)==0:
					sql.query("INSERT INTO `{db_prefix}channels` (chan,topic) VALUES(%s,%s)",[c.lower(),m])
				else:
					sql.query("UPDATE `{db_prefix}channels` SET topic=%s WHERE chan=%s",[m,c.lower()])
			if t=='action' or t=='message':
				sql.query("UPDATE `{db_prefix}users` SET lastMsg=UNIX_TIMESTAMP(CURRENT_TIMESTAMP) WHERE username=%s AND channel=%s AND online=%s",[n1,c,int(s)])
		self.updateCurline()
	def findRelay(self,id):
		for r in self.relays:
			if id == r.id:
				return r
		return False
	def addRelay(self,n):
		if n['type']==-1: # websocket, we only allow one of this type!
			found = False
			for r in self.relays:
				if r.id == -1:
					found = True
					break
			if not found:
				self.relays.append(self.relayTypes[-1]['class'](n,handle))
		elif n['type'] in self.relayTypes:
			self.relays.append(self.relayTypes[n['type']]['class'](n,handle))
		
	def get_net_cheat(self):
		net_cheat = config.json['networks'][:]
		for n in net_cheat:
			n['channels'] = {}
			for c in config.json['channels']:
				if c['enabled']:
					for cc in c['networks']:
						if cc['id'] == n['id']:
							n['channels'][c['id']] = cc['name']
		
		net_cheat = [{
			'id':-1,
			'type':-1,
			'enabled':config.json['websockets']['use'],
			'channels':{},
			'config':config.json['websockets']
		}] + net_cheat
		return net_cheat
	def updateChanIds(self):
		self.chanIds = []
		for c in config.json['channels']:
			self.chanIds.append(str(c['id']))
	def updateConfig(self):
		self.log_info('Got signal to update config!')
		oldInternalSock = config.json['settings']['botSocket']
		config.readFile()
		self.updateChanIds()
		if oldInternalSock != config.json['settings']['botSocket']:
			self.oircLink.stop()
			self.startOircLink()
		
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
		self.log_info('sigquit')
		self.quit()
	def sigterm(self,e,s):
		self.log_info('sigterm')
		self.quit()
	def sigint(self,e,s):
		self.log_info('sigint')
		self.quit()
	def startOircLink(self):
		sock = config.json['settings']['botSocket']
		if sock.startswith('unix:'):
			self.oircLink = server.Server(sock[5:],0,OIRCLink)
		else:
			res = re.match(r'^([\w\.]+):(\d+)',sock)
			if not res:
				self.log_error('ERROR: invalid internal socket '+sock)
				return False
			self.oircLink = server.Server(res.group(1),int(res.group(2)),OIRCLink)
		self.oircLink.start()
		return True
	def serve(self):
		signal.signal(signal.SIGQUIT,self.sigquit)
		signal.signal(signal.SIGTERM,self.sigterm)
		signal.signal(signal.SIGINT,self.sigint)
		
		self.calcNetwork = -1
		
		
		for n in self.get_net_cheat():
			if n['enabled']:
				self.addRelay(n)
		
		if not self.startOircLink():
			self.quit(RSP_STARTFAIL)
		try:
			for r in self.relays:
				r.startRelay_wrap()
			
			while self.live:
				time.sleep(30)
				r = oirc.execPhp('omnomirc.php',{'cleanUsers':''});
				if not r['success']:
					self.log_error('Something went wrong updating users...'+str(r))
		except KeyboardInterrupt:
			print('(handle) KeyboardInterrupt, exiting...')
			self.quit()
		except:
			traceback.print_exc()
		self.log_info('That\'s it, I\'m dying')
	def quit(self,code=0):
		global config
		self.live = False
		for r in self.relays:
			r.stopRelay_wrap()
		for r in self.relays:
			try:
				r.joinThread()
			except:
				pass
		
		sql.close()
		
		oirc.execPhp('admin.php',{'internalAction':'deactivateBot'})
		try:
			self.oircLink.stop()
			self.oircLink.join()
		except:
			os._exit(code) # we are within the thread!
			pass
		sys.exit(code)

if __name__ == "__main__":
	FILEROOT = os.path.dirname(os.path.realpath(__file__))
	
	parser = argparse.ArgumentParser()
	parser.prog = 'omnomirc.sh' # we want this to be run via the shellscript
	parser.add_argument('-v','--verbose',help='increase output verbosity',action='store_true')
	parser.add_argument('-l','--logpath',help='file log location')
	args = parser.parse_args()
	if args.logpath == None:
		args.logpath = FILEROOT+'/logs'
	
	print('Starting OmnomIRC bot...')
	if not os.path.exists(FILEROOT+'/logs'):
		os.makedirs(FILEROOT+'/logs')
	
	config = Config()
	oirc.execPhp('admin.php',{'internalAction':'activateBot'})
	sql = Sql()
	handle = Main()
	config.can_postload = True
	config.postLoad()
	handle.serve()