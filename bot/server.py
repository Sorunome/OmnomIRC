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

import threading,socket,select,traceback

class ServerHandler:
	def __init__(self,s,address):
		self.socket = s
		self.client_address = address
	def setup(self):
		return True
	def recieve(self):
		data = self.socket.recv(1024)
		return True
	def close(self):
		try:
			self.socket.close()
		except:
			pass
	def isHandler(self,s):
		return s == self.socket
	def getSocket(self):
		return self.socket

class Server(threading.Thread):
	host = ''
	port = 0
	backlog = 5
	stopnow = False
	def __init__(self,host,port,handler):
		threading.Thread.__init__(self)
		self.host = host
		self.port = port
		self.handler = handler
	def getHandler(self,client,address):
		return self.handler(client,address)
	def getInputHandler(self,s):
		for i in self.inputHandlers:
			if i.isHandler(s):
				return i
		return False
	def getSocket(self):
		return socket.socket(socket.AF_INET,socket.SOCK_STREAM)
	def run(self):
		server = self.getSocket()
		server.bind((self.host,self.port))
		server.listen(self.backlog)
		server.settimeout(5)
		input = [server]
		self.inputHandlers = []
		while not self.stopnow:
			inputready,outputready,exceptready = select.select(input,[],[],5)
			for s in inputready:
				if s == server:
					# handle incoming socket connections
					client, address = server.accept()
					client.settimeout(0.1)
					handler = self.getHandler(client,address)
					if handler.setup():
						self.inputHandlers.append(handler)
						input.append(client)
					else:
						handler.close()
				else:
					# handle other socket connections
					i = self.getInputHandler(s)
					if i:
						try:
							if not i.recieve():
								try:
									i.close()
								except:
									pass
								try:
									s.close()
								except:
									pass
								input.remove(s)
								self.inputHandlers.remove(i)
						except socket.timeout:
							pass
						except Exception as err:
							print(err)
							traceback.print_exc()
							try:
								i.close()
							except:
								pass
							try:
								s.close()
							except:
								pass
							input.remove(s)
							self.inputHandlers.remove(i)
							break
					else:
						s.close()
						input.remove(s)
		for s in input:
			try:
				s.close()
			except:
				pass
		for i in self.inputHandlers:
			try:
				i.close()
			except:
				pass
	def stop(self):
		self.stopnow = True

class SSLServer(Server):
	def getSocket(self):
		dir = os.path.dirname(__file__)
		key_file = os.path.join(dir,'server.key')
		cert_file = os.path.join(dir,'server.crt')
		import ssl
		s = socket.socket(socket.AF_INET,socket.SOCK_STREAM)
		return ssl.wrap_socket(s, keyfile=key_file, certfile=cert_file, cert_reqs=ssl.CERT_NONE)
