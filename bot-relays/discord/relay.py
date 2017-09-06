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

import oirc_include as oirc, asyncio, discord, threading, re

#loop = asyncio.get_event_loop()
class Relay(oirc.OircRelay):
	def initRelay(self):
		self.server = self.getHandle(DiscordHandle)(self.config['token'], self.config['server'])
	def startRelay(self):
		self.server.start()
	def updateRelay(self, cfg, chans):
		self.channels = chans
		self.server.channels = chans
		self.server.server = cfg['server']
		if self.config['token'] != cfg['token']:
			self.server.stop()
			self.server.join()
			self.server.token = cfg['token']
			self.server.start()
		self.config = cfg
	def stopRelay(self):
		self.server.stop()
	def relayMessage(self, n1, n2, t, m, c, s, uid = -1, curline = 0):
		if s != self.id:
			self.server.send(n1, n2, t, m, c, s, uid)
	def joinThread(self):
		self.server.join()

class DiscordHandle(oirc.OircRelayHandle, threading.Thread):
	def __init__(self, token, server):
		threading.Thread.__init__(self)
		self.token = token
		self.server = server
		self.loop = asyncio.new_event_loop()
		self.client = discord.Client(loop = self.loop)
		self.acceptMessages = False
		self.setClientFunctions()
	def chanToId(self, c):
		for i,ch in self.channels.items():
			if ch[0] == '#':
				ch = ch[1:]
			if c.name.lower() == ch.lower() or c.id == ch:
				return i
		return -1
	def idToChan(self, i):
		c = oirc.OircRelayHandle.idToChan(self, i)
		if c[0] == '#':
			c = c[1:]
		for server in self.client.servers:
			if server.name.lower() == self.server.lower() or server.id == self.server:
				for ch in server.channels:
					if ch.name.lower() == c.lower() or ch.id == c:
						return ch
		return None
	def setClientFunctions(self):
		self.bot_id = ''
		@self.client.event
		@asyncio.coroutine
		def on_ready():
			self.acceptMessages = True
			self.info('Logged in as ' + self.client.user.name)
			self.bot_id = self.client.user.id
		@self.client.event
		@asyncio.coroutine
		def on_message(message):
			if not message.server or not self.acceptMessages:
				return
			if message.author.id == self.bot_id:
				return
			if not (message.server.name.lower() == self.server.lower() or message.server.id == self.server):
				return
			nick = message.author.nick
			if not nick:
				nick = message.author.name
			msg = message.content.replace('\n', ' ').replace('\r', '')
			msg = re.sub(r'\*\*([^*]+)\*\*', '\x02\\1\x02', msg)
			msg = re.sub(r'\*([^*]+)\*', '\x1D\\1\x1D', msg)
			msg = re.sub(r'_([^_]+)_', '\x1D\\1\x1D', msg)
			
			self.addLine(nick, '', 'message', msg, message.channel)
	def send(self, n1, n2, t, m, c, s, uid):
		c = self.idToChan(c)
		if not c:
			return
		m = oirc.stripIrcColors(m)
		msg = ''
		if t == 'message':
			msg = '<{}> {}'.format(n1, m)
		elif t == 'action':
			msg = '* {} {}'.format(n1, m)
		elif t == 'join':
			msg = '* {} has joined'.format(n1)
		elif t == 'part':
			msg = '* {} has left'.format(n1)
		elif t == 'quit':
			msg = '* {} has quit ({})'.format(n1, m)
		elif t == 'mode':
			msg = '* {} set mode {}'.format(n1, m)
		elif t == 'kick':
			msg = '* {} has kicked {} ({})'.format(n1, n2, m)
		elif t == 'topic':
			msg = '* {} has changed the topic to {}'.format(n1, m)
		elif t == 'nick':
			msg = '* {} has changed nicks to {}'.format(n1, n2)
		if not msg:
			return
		prefix = self.getNetworkPrefix(s)
		asyncio.run_coroutine_threadsafe(self.client.send_message(c, prefix['prefix'] + msg), self.loop)
	def run(self):
		self.acceptMessages = False
		self.client.run(self.token)
	def stop(self):
		self.acceptMessages = False
		try:
			asyncio.run_coroutine_threadsafe(self.client.logout(), self.loop)
		except:
			pass
		try:
			asyncio.run_coroutine_threadsafe(self.client.close(), self.loop)
		except:
			pass
