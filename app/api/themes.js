var files = fs.readdirSync('./www/data/themes/');
log('(function($o){');
for(var i in files){
	log('$o.register.theme("'+files[i]+'");');
}
log('})(OmnomIRC);');