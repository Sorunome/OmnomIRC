hook('message',function(msg,from,room,source){
	if(msg =='funny'){
		$o.event('test','yay');
		$o.chat.send(source,room);
	}
});
hook('send',function(msg,room){
	return msg.toLowerCase()!='the game';
});
hook('start',function(){
	$('body').show();
});
hook('stop',function(){
	$('body').hide();
});