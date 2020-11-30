#!/usr/bin/python3
## -*- coding: utf-8 -*-


#	OmnomIRC COPYRIGHT 2010,2011 Netham45
#					   2012-2017 Sorunome
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

import server,traceback,signal,json,time,datetime,pymysql,sys,re,os,argparse,requests,oirc_include as oirc,importlib
from multiprocessing.pool import ThreadPool

RSP_RESTART = 101
RSP_STARTFAIL = 2
try:
	import memcache
	memcached = memcache.Client(['127.0.0.1:11211'], debug = 0)
except:
	try:
		import bmemcached
		class BMemcached__wrap:
			def __init__(self):
				self.memcached = bmemcached.Client(['127.0.0.1:11211'], compression = None)
			def get(self, s):
				return self.memcached.get(s)
			def set(self, s, val, time=0):
				return self.memcached.set(s, val, compress_level = 0)
			def delete(self,s):
				return self.memcached.delete(s)
				
		memcached = BMemcached__wrap()
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


FILEROOT = os.path.dirname(os.path.realpath(__file__))

#config handler
class Config:
	def __init__(self,parent):
		self.parent = parent
		self.can_postload=False
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
				self.json['networks'][i]['config'] = self.parent.sql.getVar('net_config_'+str(self.json['networks'][i]['id']))


#sql handler
class Sql:
	db = False
	lastRowId = -1
	def __init__(self,parent):
		self.parent = parent
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
			self.parent.log('sql','info','creating new SQL connection...')
			try:
				self.db = pymysql.connect(
					host=self.parent.config.json['sql']['server'],
					user=self.parent.config.json['sql']['user'],
					password=self.parent.config.json['sql']['passwd'],
					db=self.parent.config.json['sql']['db'],
					unix_socket='/var/run/mysqld/mysqld.sock',
					charset='utf8mb4',
					cursorclass=pymysql.cursors.DictCursor)
			except:
				try:
					self.db = pymysql.connect(
						host=self.parent.config.json['sql']['server'],
						user=self.parent.config.json['sql']['user'],
						password=self.parent.config.json['sql']['passwd'],
						db=self.parent.config.json['sql']['db'],
						charset='utf8mb4',
						cursorclass=pymysql.cursors.DictCursor)
				except:
					try:
						self.db = pymysql.connect(
							host=self.parent.config.json['sql']['server'],
							user=self.parent.config.json['sql']['user'],
							passwd=self.parent.config.json['sql']['passwd'],
							db=self.parent.config.json['sql']['db'],
							unix_socket='/var/run/mysqld/mysqld.sock',
							charset='utf8mb4',
							cursorclass=pymysql.cursors.DictCursor)
					except:
						self.db = pymysql.connect(
							host=self.parent.config.json['sql']['server'],
							user=self.parent.config.json['sql']['user'],
							passwd=self.parent.config.json['sql']['passwd'],
							db=self.parent.config.json['sql']['db'],
							charset='utf8mb4',
							cursorclass=pymysql.cursors.DictCursor)
			return self.db.cursor()
	def query(self,q,p = []):
		global config
		try:
			cur = self.getDbCursor()
			try:
				cur.execute(q.replace('{db_prefix}',self.parent.config.json['sql']['prefix']),tuple(p))
				self.db.commit()
			except:
				try:
					self.db.close()
				except:
					pass
				self.db = False
				cur = self.getDbCursor()
				cur.execute(q.replace('{db_prefix}',self.parent.config.json['sql']['prefix']),tuple(p))
				self.db.commit()
			self.lastRowId = cur.lastrowid
			rows = []
			for row in cur:
				if row == None:
					break
				rows.append(row)
			cur.close()
			return rows
		except Exception as inst:
			self.parent.log('sql','error',str(inst))
			self.parent.log('sql','error',traceback.format_exc())
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
	def insertId(self):
		return self.lastRowId


#fetch lines off of OIRC
class OIRCLinkServer(server.Server):
	def __init__(self,parent,host,port,handler,errhandler = False):
		self.parent = parent
		server.Server.__init__(self,host,port,handler,errhandler)
	def getHandler(self,client,address):
		return self.handler(self.parent,client,address)
class OIRCLink(server.ServerHandler):
	def __init__(self,parent,s,address):
		server.ServerHandler.__init__(self,s,address)
		self.readbuffer = ''
		self.parent = parent
	def recieve(self):
		try:
			data = oirc.makeUnicode(self.socket.recv(1024))
			if not data: # EOF
				return False
			self.readbuffer += data
		except:
			self.error(traceback.format_exc())
			return False
		temp = self.readbuffer.split('\n')
		self.readbuffer = temp.pop()
		for line in temp:
			try:
				data = json.loads(line)
				self.debug('>> '+str(data))
				
				if data['t'] == 'server_updateconfig':
					self.parent.update()
				elif data['t'] == 'server_delete_modebuffer':
					self.parent.mode.delBuffer(data['c'])
				elif data['t'] == 'server_updaterelaytypes':
					self.parent.relay.update()
				elif data['t'] == 'server_restart':
					self.parent.quit(RSP_RESTART)
				elif data['t'] == 'server_getRelayTypes':
					s = json.dumps(self.parent.relay.data())+'\n'
					self.info('Sending out network information... '+str(s))
					self.socket.sendall(bytes(s,'utf-8'))
				elif data['t'] == 'server_restartNetwork':
					i = int(data['nid'])
					self.parent.network.restart(i)
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
					self.parent.message.send(data['n1'],data['n2'],data['t'],data['m'],data['c'],data['s'],data['uid'],data['curline'],False)
			except:
				self.error(traceback.format_exc())
		return True
	def debug(self,s):
		self.parent.log('oirc link','debug',s)
	def info(self,s):
		self.parent.log('oirc link','info',s)
	def error(self,s):
		self.parent.log('oirc link','error',s)

# relay types handler
class Relay:
	relay_dir = 'relays'
	def __init__(self,parent):
		self.parent = parent
		self.types = {}
		self.update()
	def path(self,s,f):
		return FILEROOT+'/'+self.relay_dir+'/'+s+'/'+f
	def versioncompare(self,s1,s2):
		def vertuple(s):
			return tuple([int(x) for x in s.split('.') if x.isdigit()])
		t1 = vertuple(s1)
		t2 = vertuple(s2)
		return (t1 < t2) - (t1 > t2)
	def update(self):
		self.debug('updating relay types...')
		for s in os.listdir(self.relay_dir):
			# ok let's check if this is actually a valid relay...
			self.debug('Found potential relay type "'+s+'"')
			if not os.path.isfile(self.path(s,'relay.py')):
				# no file to include
				self.error('"'+s+'": missing relay.py file')
				continue
			
			# config.json needs to exist and be valid json
			f = self.path(s,'config.json')
			if not os.path.isfile(f):
				self.error('"'+s+'": missing config.json file')
				continue
			with open(f,'r') as ff:
				try:
					config = json.load(ff)
					if not 'name' in config:
						self.error('"'+s+'" config.json is missing name')
					
					if not 'version' in config:
						self.error('"'+s+'" config.json is missing version')
					
					if not 'defaultCfg' in config:
						self.error('"'+s+'" config.json is missing defaultCfg')
					
					if not 'editPattern' in config:
						self.error('"'+s+'" config.json is missing editPattern')
				except ValueError:
					self.error('"'+s+'" config.json is invalid JSON')
					continue
			
			
			# OK, we have a valid relay type! Let's try to import it
			sys.path.insert(0,self.path(s,''))
			reloading = False
			firstImport = True
			
			try:
				if s in self.types:
					self.debug('"'+s+'" has already been imported, reloading...')
					reloading = True
					firstImport = False
					r = importlib.reload(self.types[s]['module'])
				else:
					r = importlib.import_module('relay')
					r = importlib.reload(r) # python bug?
			except:
				traceback.print_exc()
				self.error('"'+s+'": couldn\'t import')
				continue
			finally:
				sys.path.pop(0) # remove that element again
			# ok, we didn't have a python error....let's check if our class and stuff is present
			if not hasattr(r,'Relay'):
				self.error('"'+s+'": relay doesn\'t have the Relay-class!')
				continue
			
			if config['version'] == '0.0.0':
				reloading = True
			elif reloading and self.versioncompare(self.types[s]['version'],r.version) < 1:
				self.debug('"'+s+'" is outdated, skipping it')
				continue
			
			
			self.types[s] = {
				'module':r,
				'name':config['name'],
				'class':r.Relay,
				'version':config['version']
			}
			if reloading and not firstImport:
				self.info('Reloaded relay type "'+s+'"')
				self.parent.network.restartType(s)
			else:
				self.info('Found relay type "'+s+'"')
	def new(self,n):
		if not n['type'] in self.types:
			return False
		return self.types[n['type']]['class'](n,self.parent)
	def data(self):
		a = []
		for i,v in self.types.items():
			if i!='websockets':
				with open(self.path(i,'config.json')) as f:
					ff = json.load(f)
					a.append({
						'id':i,
						'name':v['name'],
						'defaultCfg':ff['defaultCfg'],
						'editPattern':ff['editPattern']
					})
		return a
	def debug(self,s):
		self.parent.log('relay types','debug',s)
	def info(self,s):
		self.parent.log('relay types','info',s)
	def error(self,s):
		self.parent.log('relay types','error',s)

# network handler
class Network:
	def __init__(self,parent):
		self.parent = parent
		self.nets = []
		
		for n in self.get_all():
			self.add(n)
	def add(self,n):
		if not n['enabled']:
			return False
		if n['type'] == 'websockets': # we only allow one of this type!
			found = False
			for r in self.nets:
				if r.id == -1:
					found = True
					break
			if found:
				return False
		r = self.parent.relay.new(n)
		if not r:
			return False
		
		self.info('Added new network "'+n['name']+'" of type "'+n['type']+'"')
		self.nets.append(r)
		return r
	def get_all(self):
		net_cheat = self.parent.config.json['networks'][:]
		for n in net_cheat:
			n['channels'] = {}
			for c in self.parent.config.json['channels']:
				if c['enabled']:
					for cc in c['networks']:
						if cc['id'] == n['id']:
							n['channels'][c['id']] = cc['name']
		net_cheat = [{
			'id':-1,
			'type':'websockets',
			'name':'Websocket link',
			'enabled':self.parent.config.json['websockets']['use'],
			'channels':{},
			'config':self.parent.config.json['websockets']
		}] + net_cheat
		return net_cheat
	def find(self,i):
		for n in self.nets:
			if i == n.id:
				return n
		return False
	def update(self):
		# now let's loop 
		for n in self.get_all():
			r = self.find(n['id'])
			if not n['enabled'] and r:
				# we need to stop the network!
				r.stopRelay_wrap()
				self.nets.remove(r)
			if n['enabled']:
				if r:
					# let's tell the network to update itself
					r.updateRelay_wrap(n)
				else:
					# we need to make a new one
					r = self.add(n)
					if r:
						r.startRelay_wrap()
	@oirc.async
	def restart(self,i):
		n = i
		if type(n) == int:
			for nn in self.nets:
				if nn.id == i:
					n = nn
					break
		if type(n) == int: # network not found
			return
		n.stopRelay_wrap()
		n.joinThread()
		n = self.parent.relay.new(n.net)
		n.startRelay_wrap()
	def restartType(self,s):
		# we use three seperate for-loops as this might increase performance
		for n in self.nets:
			if n.type == s:
				self.restart(n)
	def debug(self,s):
		self.parent.log('network','debug',s)
	def info(self,s):
		self.parent.log('network','info',s)
	def error(self,s):
		self.parent.log('network','error',s)

# mode handler
class Mode:
	def __init__(self,parent):
		self.parent = parent
		self.buffer = {}
	def delBuffer(self,c):
		self.buffer.pop(c,False)
	def getString(self,c):
		if c in self.buffer:
			return self.buffer[c]
		res = self.parent.sql.query("SELECT `modes` FROM `{db_prefix}channels` WHERE chan=LOWER(%s)",[oirc.makeUnicode(c)])
		if len(res)==0:
			self.buffer[c] = '+-'
			return '+-'
		self.buffer[c] = res[0]['modes']
		return res[0]['modes']
	def get(self,chan,char,default = False):
		res = self.getString(chan)
		try:
			char = res.index(char)
			minus = res.index('-')
		except:
			return default
		return char < minus

# message handler
class Message:
	def __init__(self,parent):
		self.parent = parent
		self.curline = 0
		self.chanIds = []
		self.updateChanIds()
	def updateChanIds(self):
		self.chanIds = []
		for c in self.parent.config.json['channels']:
			self.chanIds.append(str(c['id']))
	def setCurline(self,curline = -1):
		try:
			fp = self.parent.config.json['settings']['curidFilePath']
			if fp[:1] != '/':
				fp = oirc.DOCUMENTROOT + '/' + fp
			if curline == -1:
				curline = int(self.parent.sql.query("SELECT MAX(line_number) AS max FROM `{db_prefix}lines`")[0]['max'])
			if curline > self.curline:
				self.curline = curline
				with open(fp,'w') as f:
					f.write(str(curline))
		except Exception as inst:
			self.error('curline error: '+str(inst))
			self.error(traceback.format_exc())
	def sendTopic(self,s,c,i):
		oircOnly = False
		try:
			int(c)
		except:
			oircOnly = True
		for n in self.parent.network.nets:
			if (oircOnly and n.type == 'websockets') or not oircOnly:
				n.relayTopic_wrap(s,c,i)
	def send(self,n1,n2,t,m,c,s,uid = -1,curline = -1,do_sql = True):
		if self.parent.mode.get(c,'c'):
			m = oirc.stripIrcColors(m)
		
		oircOnly = False
		try:
			c = int(c)
		except:
			oircOnly = True
		self.info({'name1':n1,'name2':n2,'type':t,'message':m,'channel':c,'source':s,'uid':uid})
		
		if not oircOnly:
			oircOnly = (t in ('join','part','quit') and uid!=-1)
			if not str(c) in self.chanIds:
				self.info('Invalid channel '+str(c)+', dropping message')
				return
		if do_sql:
			c = oirc.makeUnicode(str(c))
			self.parent.sql.query("INSERT INTO `{db_prefix}lines` (`name1`,`name2`,`message`,`type`,`channel`,`time`,`online`,`uid`) VALUES (%s,%s,%s,%s,{db_prefix}getchanid(%s),%s,%s,%s)",[n1,n2,m,t,c,str(int(time.time())),int(s),uid])
			curline = int(self.parent.sql.insertId())
			try:
				lines_cached = memcached.get('oirc_lines_'+c)
				if lines_cached:
					try:
						lines_cached = json.loads(lines_cached)
						if len(lines_cached) > 200:
							lines_cached.pop(0)
						lines_cached.append({
							'curline': curline,
							'type': t,
							'network': int(s),
							'time': int(time.time()),
							'name': n1,
							'message': m,
							'name2': n2,
							'chan': c,
							'uid': uid
						})
						memcached.set('oirc_lines_'+c, json.dumps(lines_cached,separators=(',',':')),int(time.time())+(60*60*24*3))
					except:
						traceback.print_exc()
						memcached.delete('oirc_lines_'+c)
			except Exception as inst:
				self.error('Couldn\'t update memcached: '+str(inst))
		
		if t == 'topic':
			memcached.set('oirc_topic_'+c, m, compress_level = 0)
			self.parent.sql.query("UPDATE `{db_prefix}channels` SET topic=%s WHERE channum={db_prefix}getchanid(%s)",[m,c])
		if t == 'action' or t == 'message':
			self.parent.sql.query("UPDATE `{db_prefix}users` SET lastMsg=UNIX_TIMESTAMP(CURRENT_TIMESTAMP) WHERE username=%s AND channel=%s AND online=%s",[n1,c,int(s)])
		self.setCurline(curline)
		
		for n in self.parent.network.nets:
			if ((oircOnly and n.type == 'websockets') or not oircOnly):
				try:
					n.relayMessage_wrap(n1,n2,t,m,c,s,uid,curline)
				except:
					self.error(traceback.format_exc())
	def debug(self,s):
		self.parent.log('message','debug',s)
	def info(self,s):
		self.parent.log('message','info',s)
	def error(self,s):
		self.parent.log('message','error',s)

# user handler
class User:
	def __init__(self,parent):
		self.parent = parent
	def add(self,u,c,i,uid = -1,donotify = True):
		temp = self.parent.sql.query("SELECT `usernum`,`isOnline` FROM `{db_prefix}users` WHERE `username`=%s AND `channel`=%s AND `online`=%s",[u,c,int(i)])
		notify = True
		if len(temp) == 0:
			self.parent.sql.query("INSERT INTO `{db_prefix}users` (`username`,`channel`,`online`,`time`,`uid`) VALUES (%s,%s,%s,0,%s)",[u,c,int(i),int(uid)])
		else:
			self.parent.sql.query("UPDATE `{db_prefix}users` SET `isOnline`=1,`uid`=%s,`time`=0 WHERE `usernum`=%s",[int(uid),int(temp[0]['usernum'])])
			notify = (temp[0]['isOnline'] == 0)
		if notify and donotify:
			self.parent.message.send(u,'','join','',c,i,uid)
	def remove(self,u,c,i):
		self.parent.sql.query("UPDATE `{db_prefix}users` SET `isOnline`=0 WHERE `username` = %s AND `channel` = %s AND online=%s",[u,c,int(i)])
	def timeout(self,u,c,i):
		self.parent.sql.query("UPDATE `{db_prefix}users` SET `time` = UNIX_TIMESTAMP(CURRENT_TIMESTAMP) WHERE `username` = %s AND `channel` = %s AND online=%s",[u,c,int(i)])
	def removeAllChan(self,c,i):
		self.parent.sql.query("UPDATE `{db_prefix}users` SET `isOnline`=0 WHERE `channel` = %s AND online=%s",[c,int(i)])
	def removeAllNetwork(self,i):
		self.parent.sql.query("UPDATE `{db_prefix}users` SET `isOnline`=0 WHERE `online`=%s",[int(i)])

# main handler
class Main:
	def __init__(self,args):
		self.live = True
		self.args = args
		
		self.config = Config(self)
		self.sql = Sql(self)
		self.config.can_postload = True
		self.config.postLoad()
		
		self.pool = ThreadPool()
		
		# we need the relay types before we can add the networks
		self.relay = Relay(self)
		self.network = Network(self)
		
		self.mode = Mode(self)
		self.message = Message(self)
		self.user = User(self)
		
		self.logCache = {}
		self.oircLink = None
	def log(self, id, level, message, prefix = '', doPrint = True):
		message = str(message)
		if isinstance(id, int):
			if not id in self.logCache:
				for n in self.config.json['networks']:
					if n['id'] == id:
						self.logCache[id] = n['name']
						break
				if not id in self.logCache:
					self.logCache[id] = str(id)
			id = self.logCache[id]
		id = str(id)
		s = datetime.datetime.now().strftime('[%a, %d %b %Y %H:%M:%S.%f]') + ' [' + id + '] ' + prefix + message
		if (self.args.loglevel and (
				(self.args.loglevel > 0 and level == 'info') or
				(self.args.loglevel > 1)
			)) or level == 'error':
			with open(self.args.logpath + '/omnomirc.' + level, 'a+') as f:
				f.write(s + '\n')
		
		if (doPrint and self.args.verbose and (
				(self.args.verbose == None and level == 'error') or
				(self.args.verbose == 0 and level == 'info') or
				(self.args.verbose >= 1 and level == 'debug')
			)):
			doPrint = False
			print(s)
		if level == 'error':
			self.log(id, 'info', message, 'ERROR: ', doPrint)
		if level == 'info':
			if not prefix:
				prefix = 'INFO: '
			self.log(id, 'debug', message, prefix, doPrint)
	def startLink(self):
		sock = self.config.json['settings']['botSocket']
		if sock.startswith('unix:'):
			self.oircLink = OIRCLinkServer(self,sock[5:],0,OIRCLink)
		else:
			res = re.match(r'^([\w\.]+):(\d+)',sock)
			if not res:
				self.error('ERROR: invalid internal socket '+sock)
				return False
			self.oircLink = OIRCLinkServer(self,res.group(1),int(res.group(2)),OIRCLink)
		self.oircLink.start()
		return True
	@oirc.async
	def update(self):
		self.info('Got signal to update config!')
		
		# save the old botsocket for later
		oldInternalSock = self.config.json['settings']['botSocket']
		
		# let's re-fetch all the config
		self.config.readFile()
		self.message.updateChanIds()
		
		# no check if we need to re-start the oirc link
		if oldInternalSock != self.config.json['settings']['botSocket']:
			self.oircLink.stop()
			self.startLink()
		
		self.network.update()
	def sigquit(self,e,s):
		self.info('sigquit')
		self.quit()
	def sigterm(self,e,s):
		self.info('sigterm')
		self.quit()
	def sigint(self,e,s):
		self.info('sigint')
		self.quit()
	def attachSigHandlers(self):
		signal.signal(signal.SIGQUIT,self.sigquit)
		signal.signal(signal.SIGTERM,self.sigterm)
		signal.signal(signal.SIGINT,self.sigint)
	def serve(self):
		if not self.startLink():
			self.quit(RSP_STARTFAIL)
		
		oirc.execPhp('admin.php',{'internalAction':'activateBot'})
		
		try:
			for n in self.network.nets:
				n.startRelay_wrap()
			while self.live:
				time.sleep(30)
				r = oirc.execPhp('misc.php',{'cleanUsers':''})
				if not r['success']:
					self.log('Something went wrong updating users...'+str(r))
		except KeyboardInterrupt:
			self.error('KeyboardInterrupt, exiting...')
			self.quit()
		except:
			self.error(traceback.format_exc())
		self.info('That\'s it, I\'m dying')
	def quit(self,code = 0):
		self.live = False # teill the main loop we want to quit
		for n in self.network.nets:
			n.stopRelay_wrap()
		for n in self.network.nets:
			try:
				n.joinThread()
			except:
				pass
		self.sql.close()
		oirc.execPhp('admin.php',{'internalAction':'deactivateBot'})
		
		try:
			self.oircLink.stop()
			self.oircLink.join()
		except:
			os._exit(code) # we are within the thread!
			pass
		try:
			sys.exit(code)
		except Exception as inst:
			self.error(inst)
			self.error(traceback.format_exc())
	def debug(self,s):
		self.log('handle','debug',s)
	def info(self,s):
		self.log('handle','info',s)
	def error(self,s):
		self.log('handle','error',s)

if __name__ == "__main__":
	
	parser = argparse.ArgumentParser()
	parser.prog = 'omnomirc.sh' # we want this to be run via the shellscript
	parser.add_argument('-v','--verbose',help='increase output verbosity',action='count')
	parser.add_argument('-l','--logpath',help='file log location')
	parser.add_argument('-L','--loglevel',help='increase log level to logfiles',action='count')
	args = parser.parse_args()
	if args.logpath == None:
		args.logpath = FILEROOT+'/logs'
	
	print('Starting OmnomIRC bot...')
	if not os.path.exists(FILEROOT+'/logs'):
		os.makedirs(FILEROOT+'/logs')
	
	
	
	
	handle = Main(args)
	handle.attachSigHandlers()
	handle.serve()
