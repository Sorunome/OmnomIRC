hook('message',function(msg,from,room){
	if(msg =='funny'){
		$o.chat.send('Not funny',room);
	}
});