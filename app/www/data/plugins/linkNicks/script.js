hook('message',function(msg,from,room,origin){
	var origins = function(name){
		for(var i in $o.prop('origins')){
			if(name == $o.prop('origins')[i][1]){
				return i;
			}
		}
		return 1;
	};
	if(origins('OmnomIRC')==origin){
		var parseMsg = $($('#content-list').children().last().children("span")[1]).html(),
			nick,
			before,
			after,
			newHTML;
		if(parseMsg.substring(24,28)=="&lt;"){
			nick = parseMsg.substring(28,parseMsg.indexOf("&gt;",29));
			before = parseMsg.substring(0,28);
			after = parseMsg.substring(parseMsg.indexOf("&gt;",29));
		}else if(parseMsg.substring(24,25)=="*"){
			nick = parseMsg.substring(31,parseMsg.indexOf("&nbsp;",31));
			before = parseMsg.substring(0,31);
			after = parseMsg.substring(parseMsg.indexOf("&nbsp;",31));
		}
		newHTML = $(fragment())
			.append(before)
			.append(
				$('<a>')
						.attr('href','http:/'+'/www.omnimaga.org/index.php?action=ezportal;sa=page;p=13&userSearch='+nick)
						.text(nick)
			).append(after);
		$o.event('links',newHTML.html());
		//$($('#content-list').children().last().children("span").get(1)).html(newHTML);
		//$($($o.ui.tabs.current().body).children().last().children().get(1)).html(newHTML);
		//$('#content-list > li:nth-last-child(1) > span:nth-last-child(1)').html(newHTML);
		$($o.ui.tabs.current().body).children().last().children().slice(1,2)
			.add('#content-list > li:nth-last-child(1) > span:nth-last-child(1)').html(newHTML);
	}
});