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
import chardet,subprocess,json

f = open('documentroot.cfg')
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

def execPhp(f,d = {}):
	s = []
	for key,value in d.items():
		s.append(str(key)+'='+str(value))
	res = subprocess.Popen(['php',DOCUMENTROOT+'/'+f] + s,stdout=subprocess.PIPE).communicate()
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
	return re.sub(r"(\x02|\x0F|\x16|\x1D|\x1F|\x03(\d{1,2}(,\d{1,2})?)?)",'',s)

class OircRelay:
	relayType = -1
	id = -1
	def __init__(self,n,handle):
		self.id = int(n['id'])
		self.config = n['config']
		self.handle = handle
		self.initRelay()
	def initRelay(self):
		return
	def startRelay_wrap(self):
		self.handle.removeAllUsersNetwork(self.id)
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
		self.handle.removeAllUsersNetwork(self.id)
		self.stopRelay()
	def stopRelay(self):
		return
	def relayMessage(self,n1,n2,t,m,c,s,uid):
		return
	def relayTopic(self,s,c,i):
		return
	def joinThread(self):
		return