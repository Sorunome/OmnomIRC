var files = fs.readdirSync('./www/data/plugins/');
log('(function($o){');
for(var i in files){
	log('$o.register.plugin("'+files[i]+'");');
	log('$o.plugin.start("'+files[i]+'");');
}
log('})(OmnomIRC);');