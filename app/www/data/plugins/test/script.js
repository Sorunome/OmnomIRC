hook('message',function(msg,from,room,origin){
	if(msg =='funny'){
		$o.event('test','yay');
		$o.chat.send(origin,room);
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