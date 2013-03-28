import threading
import SocketServer
import urllib2
import base64
import time
import struct
print "OmnomIRC Calcnet bridge by Sorunome"
print "Starting..."
PASSWD="AizohVoo8phiemai6goh"

HOST, PORT = "134.0.27.190", 4295
connectedCalcs = []
joinableChannels = ["#omnimaga","#omnimaga-anime","#omnimaga-de","#omnimaga-fr","#omnimaga-games","#omnimaga-nl","#omnimaga-radio","#omnimaga-spam","#nspire-lua","#prizm","#irp"]
def findelement(string, thelist):
	"""Return first item in sequence where f(item) == True."""
	for item in thelist:
		if string == item: 
			return True
			break
	return False
class checkingUpdates ( threading.Thread ):
	def __init__ ( self, channel, details ):
		self.stopnow = False
		threading.Thread.__init__(self)
	def stopThread(self):
		print "Giving signal to quit Updates..."
		self.stopnow = True
	def checkExit(self):
		if self.stopnow:
			print "exiting..."
			exit()
	def run (self):
		#try: 
		curline=urllib2.urlopen("http://omnomirc.www.omnimaga.org/Load.php?calc").read()
		while not self.stopnow:
			reply=urllib2.urlopen("http://omnomirc.www.omnimaga.org/Update.php?calc&lineNum="+curline).read()
			lines=reply.split("\n")
			for i in range(0,len(lines)):
				if lines[i].find(":")!=-1:
					curline=lines[i].split(":")[1]
					channel=lines[i].split(":")[2]
					message=""
					for j in range(0,len(lines[i].split(":"))-3):
						message=message+lines[i].split(":")[j+3]+":"
					message=message[:-1]
					print "New message from "+channel+": "+message
				
					for j in range(0,len(connectedCalcs)):
						if (connectedCalcs[j].connectedToIRC and not(lines[i].split(":")[0]==connectedCalcs[j].calcName) and channel.lower()==connectedCalcs[j].chan):
							connectedCalcs[j].send("\xAD"+message)
		#except Exception,err:
		#	print err
		print "Exiting update thread"

class ThreadedTCPRequestHandler(SocketServer.BaseRequestHandler):
	connectedToIRC=False
	chan=""
	calcName=""
	stopnow=False
	def stopThread(self):
		print "Giving signal to quit calculator..."
		self.stopnow = True
	def checkExit(self):
		if self.stopnow:
			print "exiting..."
			exit()
	def sendToIRC(self,message):
		urllib2.urlopen("http://omnomirc.www.omnimaga.org/message.php?calc&signature&nick="+base64.b64encode(self.calcName)+"&message="+base64.b64encode(message)+"&channel="+base64.b64encode(self.chan)+"&id=-1&passwd="+PASSWD)
	def send(self,message):
		message="\xFF\x89\x00\x00\x00\x00\x00\x4F\x6D\x6E\x6F\x6D"+struct.pack("<H",len(message))+message
		message=struct.pack("<H",len(message)+1)+"b"+message+"*"
		self.request.sendall(message)
	def handle(self):
		print threading.current_thread()
		print "New calculator\n"
		connectedCalcs.append(self)
		
		while not self.stopnow:
			#data = self.rfile.readline()
			time.sleep(0.0001)
			try:
				data = self.request.recv(1024)
			except Exception,err:
				print "Error:"+err
				break
			if not data:  # EOF
				break
			printString = "";
			sendMessage = False
			if (data[2]=='j'):
				self.calcName=""
				self.chan=""
				for i in range(4, int(ord(data[3]))+4):
					self.chan=self.chan+data[i]
				for i in range(int(ord(data[3]))+5, int(ord(data[int(ord(data[3]))+4]))+int(ord(data[3]))+5):
					self.calcName=self.calcName+data[i]
				self.chan = self.chan.lower()
				printString+="Join-message recieved. Calc-Name:"+self.calcName+" Channel:"+self.chan+"\n"
				if not(findelement(self.chan,joinableChannels)):
					printString+="Invalid channel, defaulting to #omnimaga\n"
					self.chan="#omnimaga"
			if (data[2]=='c'):
				calcId=data[3:]
				printString+="Calc-message recieved. Calc-ID:"+calcId+"\n"
				self.send("\xAD**Now speeking in channel "+self.chan)
			if (data[2]=='b' or data[2]=='f'):
				#print data[5:10]
				if ord(data[17])==171:
					if not self.connectedToIRC:
						printString+=self.calcName+" has joined\n"
						self.connectedToIRC=True
						sendMessage=True
						message = "/me has joined "+self.chan+" ("+calcId+")"
					self.request.sendall(data)
					self.send("\xABOmnomIRC")
				elif ord(data[17])==172:
					if self.connectedToIRC:
						printString+=self.calcName+" has quit\n"
						self.connectedToIRC=False
						sendMessage=True
						message = "/me has quit"
				elif (ord(data[17])==173 and data[5:10]=="Omnom"):
					printString+="msg ("+self.calcName+") "+data[data.find(":",18)+1:-1]+"\n"
					message=data[data.find(":",18)+1:-1]
					if message.split(" ")[0].lower()=="/join":
						sendMessage=False
						if findelement(message[message.find(" ")+1:].lower(),joinableChannels):
							self.sendToIRC("/me has part "+self.chan)
							self.chan=message[message.find(" ")+1:].lower()
							self.send("\xAD**Now speeking in channel "+self.chan)
							self.sendToIRC("/me has joined "+self.chan+" ("+calcId+")")
						else:
							self.send("\xAD**Channel "+message[message.find(" ")+1:]+" doesn't exist!")
					else:
						sendMessage=True
			if sendMessage:
				self.sendToIRC(message)
			if printString!="":
				print threading.current_thread()
				print printString
		if self.connectedToIRC:
			self.sendToIRC("/me has quit")
			
		print threading.current_thread()
		connectedCalcs.remove(self)
		print "Thread done\n"

class ThreadedTCPServer(SocketServer.ThreadingMixIn, SocketServer.TCPServer):
	allow_reuse_address = True
	pass


if __name__ == "__main__":
	# Port 0 means to select an arbitrary unused port

	server = ThreadedTCPServer((HOST, PORT), ThreadedTCPRequestHandler)
	ip, port = server.server_address

	# Start a thread with the server -- that thread will then start one
	# more thread for each request
	server_thread = threading.Thread(target=server.serve_forever)
	# Exit the server thread when the main thread terminates
	server_thread.daemon = True
	server_thread.start()
	checkingUpdatesHandle = checkingUpdates(None,"")
	checkingUpdatesHandle.start()
	print "Server loop running in thread:", server_thread.name
	
	print "Server started. Waiting for calculator connections.\n"
	
	try:
		server.serve_forever()
	except KeyboardInterrupt:
		print "Keyboard interrupt, shutting down...."
		server.shutdown()
		checkingUpdatesHandle.stopThread()
		for i in range(0,len(connectedCalcs)):
			connectedCalcs[i].stopThread()
		exit(0)