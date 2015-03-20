import hexchat,re

__module_name__ = 'OmnomIRC Bindings'
__module_version__ = '0.9'
__module_description__ = 'OmnomIRC Bindings for Hexchat'

OMNOMIRCNICK = ['^','OmnomIRC']
TOPICBOTNICK = ['TopicBot']

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
	return '\x0F'+prefix+'\x0F '+nick
def doHighlight(msg):
	return hexchat.get_info('nick') in msg
def topicBinding(word,word_eol,userdata):
	s = hexchat.strip(word[0])
	if s in TOPICBOTNICK or s in TOPICBOTNICK:
		return hexchat.EAT_ALL
	return hexchat.EAT_NONE
def binding(word,word_eol,userdata):
	if hexchat.strip(word[0]) in OMNOMIRCNICK:
		msg = word[1]
		res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))<([^>]+)> (.*)',msg)
		textEvent = ''
		args = []
		while True:
			if res: # normal msg
				if doHighlight(res.group(3)):
					textEvent = 'Channel Msg Hilight'
				else:
					textEvent = 'Channel Message'
				args = [res.group(3)]
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x036\* ([^ ]+) (.*)',msg)
			if res: # action
				if doHighlight(res.group(3)):
					textEvent = 'Channel Action Hilight'
				else:
					textEvent = 'Channel Action'
				args = [res.group(3)]
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x032\* ([^ ]+) has left ([^ ]*) \((.*)\)',msg)
			if res: # part
				if res.group(4)!='':
					textEvent = 'Part with Reason'
				else:
					textEvent = 'Part'
				args = [res.group(2)+'@OmnomIRC',res.group(3),res.group(4)]
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x033\* ([^ ]+) has joined ([^ ]*)',msg)
			if res: # join
				textEvent = 'Join'
				args = [res.group(3),res.group(2)+'@OmnomIRC','']
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x032\* ([^ ]+) has quit [^ ]* \((.*)\)',msg)
			if res: # quit
				textEvent = 'Quit'
				args = [res.group(3)]
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x033\* ([^ ]+) set ([^ ]*) mode (.*)',msg)
			if res: # mode
				textEvent = 'Channel Mode Generic'
				args = ['',res.group(4),res.group(3)]
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x034\* ([^ ]+) has kicked ([^ ]*) from ([^ ]*) \((.*)\)',msg)
			if res: # kick
				textEvent = 'Kick'
				args = [res.group(3)+'@OmnomIRC'+res.group(1),res.group(4),res.group(5)]
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x033\* ([^ ]+) has changed the topic to (.*)',msg)
			if res: # topic
				textEvent = 'Topic Change'
				args = [res.group(3)]
				break
			res = re.match(r'^(\x03[0-9]{1,2}\([a-zA-Z]+\)\x0F|\(#\))\x033\* (.+) has changed nicks to (.*)',msg)
			if res: # nick
				textEvent = 'Change Nick'
				args = [getNick(res.group(1),res.group(3))]
				break;
			
			break
		
		if textEvent!='':
			if(len(word)>2):
				args.append(word[2])
			else:
				args.append('')
			hexchat.emit_print(textEvent,getNick(res.group(1),res.group(2)),*args)
			return hexchat.EAT_ALL
		
		
		print(__module_name__,': something unexpected happend, please report the following')
		print(word)
		return hexchat.EAT_NONE
	elif hexchat.strip(word[0]) in TOPICBOTNICK:
		return hexchat.EAT_ALL
	return hexchat.EAT_NONE
hexchat.hook_print('Channel Message',binding)
hexchat.hook_print('Channel Msg Hilight',binding)
hexchat.hook_print('Topic Change',topicBinding)

print(__module_name__, 'version', __module_version__, 'loaded.')