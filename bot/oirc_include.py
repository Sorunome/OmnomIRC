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
import chardet, subprocess, json, re, os

f = open(os.path.dirname(os.path.realpath(__file__)) + '/documentroot.cfg')
DOCUMENTROOT = f.readlines()[0].strip()
f.close()


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

def getSigKey():
	f = open('sigkey.cfg')
	s = f.readlines()[0].strip()
	f.close()
	return s

def execPhp(f, d = {}):
	s = []
	for key, value in d.items():
		s.append(str(key) + '=' + str(value))
	res = subprocess.Popen(['php', DOCUMENTROOT+'/'+f] + s, stdout=subprocess.PIPE).communicate()
	try:
		return json.loads(makeUnicode(res[0]))
	except:
		try:
			return makeUnicode(res[0])
		except:
			try:
				return res[0]
			except:
				return res

def stripIrcColors(s):
	return re.sub(r"(\x02|\x0F|\x16|\x1D|\x1F|\x03(\d{1,2}(,\d{1,2})?)?)", '', s)

def apply_async(func):
	def func_wrapper(*args):
		self = args[0]
		if hasattr(self, 'parent'):
			self = self.parent
		if hasattr(self, 'handle'):
			self = self.handle
		return self.pool.apply_async(func, args)
	return func_wrapper
class OircRelay:
	relayAll = False
	def __init__(self,n,handle):
		self.id = int(n['id'])
		self.net = n
		self.config = n['config']
		self.channels = n['channels']
		self.type = n['type']
		self.handle = handle
		self.initRelay()
	def initRelay(self):
		return
	@apply_async
	def startRelay_wrap(self):
		self.handle.user.removeAllNetwork(self.id)
		self.startRelay()
	def startRelay(self):
		return
	@apply_async
	def updateRelay_wrap(self, cfg):
		if self.id == int(cfg['id']):
			self.updateRelay(cfg['config'], cfg['channels'])
	def updateRelay(self, cfg):
		self.stopRelay_wrap()
		self.config = cfg
		self.startRelay_wrap()
	def stopRelay_wrap(self):
		self.handle.user.removeAllNetwork(self.id)
		self.stopRelay()
	def stopRelay(self):
		return
	@apply_async
	def relayMessage_wrap(self, n1, n2, t, m, c, s, uid, curline):
		if self.relayAll or s != self.id:
			self.relayMessage(n1, n2, t, m, c, s, uid, curline)
	def relayMessage(self, n1, n2, t, m, c, s, uid, curline):
		return
	@apply_async
	def relayTopic_wrap(self, s, c, i):
		self.relayTopic(self, s, c, i)
	def relayTopic(self, s, c, i):
		return
	def joinThread(self):
		return
	def debug(self, s):
		self.handle.log(self.id, 'debug', s)
	def info(self, s):
		self.handle.log(self.id, 'info', s)
	def error(self, s):
		self.handle.log(self.id, 'error', s)
	def getHandle(self, c):
		c.handle = self.handle
		return type(str(self.id)+'_class_anon', (c,), {'id':self.id,'channels':self.channels})

class OircRelayHandle:
	id = -1
	handle = False
	channels = False
	log_prefix = ''
	def idToChan(self, i):
		if i in self.channels:
			return self.channels[i]
		try:
			if int(i) in self.channels:
				return self.channels[int(i)]
		except:
			return ''
		return ''
	def chanToId(self, c):
		for i, ch in self.channels.items():
			if c.lower() == ch.lower():
				return i
		return -1
	def getNetworkPrefix(self, i):
		for n in self.handle.config.json['networks']:
			if n['id'] == i:
				return {
					'prefix': n['irc']['prefix'],
					'color': n['irc']['color']
				}
		return {
			'prefix': '',
			'color': -1
		}
	def debug(self, s):
		self.handle.log(self.id,'debug',self.log_prefix+str(s))
	def info(self, s):
		self.handle.log(self.id,'info',self.log_prefix+str(s))
	def error(self, s):
		self.handle.log(self.id,'error',self.log_prefix+str(s))
	def addLine(self, n1, n2, t, m, c):
		c = self.chanToId(c)
		if c != -1:
			self.handle.message.send(n1, n2, t, m, c, self.id)
