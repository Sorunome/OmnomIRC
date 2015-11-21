import hexchat,re,json

__module_name__ = 'OmnomIRC Bindings'
__module_version__ = '1.0'
__module_description__ = 'OmnomIRC Bindings for Hexchat'

OMNOMIRCNICK = ['^','OmnomIRC','SoruTestIRC']
TOPICBOTNICK = ['TopicBot']


OMNOMIDENTSTR = 'OmnomIRC'
OMNOMJOINIGNORE = 'OmnomIRC_ignore_join'

def addColor(s):
	s = hexchat.strip(s)
	rcolors = ['19','20','22','24','25','26','27','28','29']
	i = 0
	for c in s:
		i += ord(c)
	return '\x03'+rcolors[i % 9]+s+'\x0F'
def getNick(prefix,nick):
	if bool(hexchat.get_prefs('text_color_nicks')):
		nick = addColor(nick)
	else: # as omnomirc can color nicks on itself
		nick = hexchat.strip(nick)
	return '\x0F'+prefix+'\x0F\xA0'+nick
def doHighlight(msg):
	return hexchat.get_info('nick') in msg
def topicBinding(word,word_eol,userdata):
	s = hexchat.strip(word[0])
	if (s in TOPICBOTNICK) or (s in TOPICBOTNICK):
		return hexchat.EAT_ALL
	return hexchat.EAT_NONE

def modifyRawData(word,word_eol,userdata):
	try:
		nick = hexchat.strip(word[0].split(':')[1].split('!')[0])
	except:
		nick = ''
	if nick in OMNOMIRCNICK:
		try:
			msg = ' '.join(word[3:])[1:]
			chan = word[2]
		except:
			return hexchat.EAT_NONE
		if hexchat.nickcmp(chan,hexchat.get_info('nick'))==0:
			cmd = msg.split(' ')
			try:
				if cmd[0] == 'OIRCUSERS':
					chan = cmd[1]
					for nick in json.loads(' '.join(cmd[2:])):
						if hexchat.nickcmp(nick,hexchat.get_info('nick'))!=0:
							hexchat.command('RECV :'+nick+'!()\xA0'+nick+'@'+OMNOMJOINIGNORE+' JOIN :'+chan)
			except Exception as inst:
				print(__module_name__,': something unexpected happend, please report the following')
				print('Oirc PM exeption')
				print(inst)
			return hexchat.EAT_ALL
		res = re.match(r'^((\x03[0-9]{1,2}|\x02)?\([a-zA-Z0-9#!]+\)[\x0F\x02]?)',msg)
		if res:
			nick_prefix = res.group(1)
			nick = ''
			msg = msg[len(res.group(1)):]
			cmd = ''
			textEvent = ''
			args = []
			
			while True:
				res = re.match(r'<([^>]+)> (.*)',msg)
				if res: # normal msg
					if doHighlight(res.group(2)):
						textEvent = 'Channel Msg Hilight'
					else:
						textEvent = 'Channel Message'
					args = [res.group(2)]
					cmd = 'PRIVMSG '+chan+' :'+res.group(2)
					nick = res.group(1)
					break
				res = re.match(r'^\x036\* ([^ ]+) (.*)',msg)
				if res: # action
					if doHighlight(res.group(2)):
						textEvent = 'Channel Action Hilight'
					else:
						textEvent = 'Channel Action'
					args = [res.group(2)]
					cmd = 'PRIVMSG '+chan+' :\x01ACTION '+res.group(2)+'\x01'
					nick = res.group(1)
					break
				res = re.match(r'^\x032\* ([^ ]+) has left ([^ ]*) \((.*)\)',msg)
				if res: # part
					if res.group(3)!='':
						textEvent = 'Part with Reason'
					else:
						textEvent = 'Part'
					args = [res.group(1)+'@OmnomIRC',res.group(2),res.group(3)]
					cmd = 'PART '+chan+' :'+res.group(3)
					nick = res.group(1)
					break
				res = re.match(r'^\x033\* ([^ ]+) has joined ([^ ]*)',msg)
				if res: # join
					textEvent = 'Join'
					args = [res.group(2),res.group(1)+'@'+OMNOMIDENTSTR,'']
					cmd = 'JOIN :'+chan
					nick = res.group(1)
					break
				res = re.match(r'^\x032\* ([^ ]+) has quit [^ ]* \((.*)\)',msg)
				if res: # quit
					textEvent = 'Quit'
					args = [res.group(2)]
					cmd = 'QUIT :'+res.group(2)
					nick = res.group(1)
					break
				res = re.match(r'^\x033\* ([^ ]+) set ([^ ]*) mode (.*)',msg)
				if res: # mode
					textEvent = 'Channel Mode Generic'
					args = ['',res.group(3),res.group(2)]
					cmd = 'MODE '+chan+' '+res.group(3)
					nick = res.group(1)
					break
				res = re.match(r'^\x034\* ([^ ]+) has kicked ([^ ]*) from ([^ ]*) \((.*)\)',msg)
				if res: # kick
					textEvent = 'Kick'
					args = [res.group(2)+'@'+OMNOMIDENTSTR,res.group(3),res.group(4)]
					cmd = 'KICK '+chan+' '+hexchat.strip(res.group(2))+'\xA0 :'+res.group(4)
					nick = res.group(1)
					break
				res = re.match(r'^\x033\* ([^ ]+) has changed the topic to (.*)',msg)
				if res: # topic
					textEvent = 'Topic Change'
					args = [res.group(2)]
					cmd = 'TOPIC '+chan+' :'+res.group(2)
					nick = res.group(1)
					break
				res = re.match(r'^\x033\* (.+) has changed nicks to (.*)',msg)
				if res: # nick
					textEvent = 'Change Nick'
					args = [getNick(nick_prefix,res.group(2))]
					cmd = res.group(2)
					nick = res.group(1)
					break
				
				break
			
			
			if textEvent!='':
				if(len(word)>2):
					args.append(word[2])
				else:
					args.append('')
				nick_notext = nick = nick.replace(' ','\xA0')
				if not (textEvent in ['Part','Part with Reason','Quit','Join']):
					if '!' in nick_prefix:
						nick = '\x02'+nick+'\x02'
					else:
						nick = getNick(nick_prefix,nick)
				if hexchat.nickcmp(nick_notext,hexchat.get_info('nick'))==0 and textEvent in ['Part','Part with Reason','Quit','Join','Kick']:
					hexchat.emit_print(textEvent,getNick(nick_prefix,nick_notext),*args)
					if textEvent=='Kick':
						kicknick = args[0][:-len('@'+OMNOMIDENTSTR)].replace(' ','\xA0')
						if hexchat.nickcmp(kicknick,hexchat.get_info('nick'))!=0:
							hexchat.command('RECV :'+kicknick+'!'+getNick(nick_prefix,kicknick)+'@'+OMNOMJOINIGNORE+' PART '+chan)
					return hexchat.EAT_ALL
				if textEvent=='Change Nick':
					hexchat.emit_print(textEvent,getNick(nick_prefix,nick_notext),*args)
					hexchat.command('RECV :'+nick_notext+'!'+getNick(nick_prefix,nick_notext)+'@'+OMNOMJOINIGNORE+' PART '+chan)
					cmd = cmd.replace(' ','\xA0')
					hexchat.command('RECV :'+cmd+'!'+getNick(nick_prefix,cmd)+'@'+OMNOMJOINIGNORE+' JOIN :'+chan)
				else:
					hexchat.command('RECV :'+nick+'!'+getNick(nick_prefix,nick_notext)+'@'+OMNOMIDENTSTR+' '+cmd)
				return hexchat.EAT_ALL
		
		
		print(__module_name__,': something unexpected happend, please report the following')
		print(word)
		return hexchat.EAT_NONE
	elif hexchat.strip(word[0]) in TOPICBOTNICK:
		return hexchat.EAT_ALL
	return hexchat.EAT_NONE
def addExtraNicks_352(word,word_eol,userdata):
	chan = word[3]
	nick = word[7]
	if nick in OMNOMIRCNICK:
		hexchat.command('RAW PRIVMSG '+nick+' :GETUSERLIST '+chan)
def addExtraNicks_354(word,word_eol,userdata):
	chan = word[4]
	nick = word[8]
	if nick in OMNOMIRCNICK:
		hexchat.command('RAW PRIVMSG '+nick+' :GETUSERLIST '+chan)

def modifyJoinData(word,word_eol,userdata):
	if '\xA0' in word_eol[2]: # we need to modify this!
		if word[2][-len(OMNOMJOINIGNORE):] == OMNOMJOINIGNORE:
			return hexchat.EAT_ALL
		hexchat.emit_print('Join',word[2][:-len('@'+OMNOMIDENTSTR)],word[1],word[0]+'@'+OMNOMIDENTSTR)
		return hexchat.EAT_ALL
def modifyPartData(word,word_eol,userdata):
	if '\xA0' in word_eol[1]: # we need to modify this!
		if word[1][-len(OMNOMJOINIGNORE):] == OMNOMJOINIGNORE:
			return hexchat.EAT_ALL
		reason = ''
		if len(word) > 3:
			p_type = 'Part with Reason'
			reason = word[3]
		else:
			p_type = 'Part'
		hexchat.emit_print(p_type,word[1][:-len('@'+OMNOMIDENTSTR)],word[0]+'@'+OMNOMIDENTSTR,word[2],reason)
		return hexchat.EAT_ALL
def modifyKickData(word,word_eol,userdata):
	if word[1][-1:] == '\xA0': # we need to modify this!
		kicknick = word[1][:-1]
		hexchat.emit_print('Kick',word[0],kicknick+'@'+OMNOMIDENTSTR,word[2],word_eol[3])
		if hexchat.nickcmp(kicknick,hexchat.get_info('nick'))!=0:
			hexchat.command('RECV :'+kicknick+'!()\xA0'+kicknick+'@'+OMNOMJOINIGNORE+' PART '+word[2])
		return hexchat.EAT_ALL
def modifyQuitData(word,word_eol,userdata):
	if len(word) > 2 and '\xA0' in word[2]: # we need to modify this!
		if word[2][-len(OMNOMJOINIGNORE):] == OMNOMJOINIGNORE:
			return hexchat.EAT_ALL
		hexchat.emit_print('Quit',word[2][:-len('@'+OMNOMIDENTSTR)],word[1])
		return hexchat.EAT_ALL

hexchat.hook_server('352',addExtraNicks_352,priority=hexchat.PRI_HIGHEST)
hexchat.hook_server('354',addExtraNicks_354,priority=hexchat.PRI_HIGHEST)
hexchat.hook_server('PRIVMSG',modifyRawData,priority=hexchat.PRI_HIGHEST)
hexchat.hook_print('Join',modifyJoinData,priority=hexchat.PRI_HIGHEST)
hexchat.hook_print('Part',modifyPartData,priority=hexchat.PRI_HIGHEST)
hexchat.hook_print('Part with Reason',modifyPartData,priority=hexchat.PRI_HIGHEST)
hexchat.hook_print('Kick',modifyKickData,priority=hexchat.PRI_HIGHEST)
hexchat.hook_print('Quit',modifyQuitData,priority=hexchat.PRI_HIGHEST)
hexchat.hook_print('Topic',topicBinding,priority=hexchat.PRI_HIGHEST)

print(__module_name__, 'version', __module_version__, 'loaded.')
