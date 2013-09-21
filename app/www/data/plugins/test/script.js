hook('message',function(msg,from,room){
	if(msg =='funny'){
		$o.chat.send('Not funny',room);
	}
});
hook('send',function(msg,room){
	return msg.toLowerCase()!='the game';
});