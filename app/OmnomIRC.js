#!node
var fs = require('fs'),
	url = require('url'),
	path = require('path'),
	mimeTypes = {
		'html': 'text/html',
		'js': 'text/javascript',
		'css': 'text/css',
		'png': 'image/png',
		'jpg': 'image/jpeg'
	},
	app = require('http').createServer(function(req,res){
		req.addListener('end',function(){
			console.log('request made for '+req.url);
			var uri = url.parse(req.url).pathname,
				filename = path.join('./www/',unescape(uri)),
				stats;
				try{
					stats = fs.lstatSync(filename);
				}catch(e){
					res.writeHead(404,{
						'Content-type': 'text/plain'
					});
					res.write('404 Not Found\n');
					res.end();
					return;
				}
				if(stats.isFile()){
					var fileStream,
						mimetype = mimeTypes[path.extname(filename).split('.')[1]];
					res.writeHead(200,{
						'Content-Type': mimetype
					});
					fileStream = fs.createReadStream(filename);
					fileStream.pipe(res);
				}else if(stats.isDirectory()){
					res.writeHead(200,{
						'Content-Type': 'text/plain'
					});
					res.write('Index of '+url+'\n');
					res.write('TODO, show index');
					res.end();
				}else{
					res.writeHead(500,{
						'Content-Type': 'text/plain'
					});
					res.write('500 Internal server error\n');
					res.end();
				}
		}).resume();
	}).listen(80),
	io = require('socket.io').listen(app);
io.sockets.on('connection',function(socket){
	socket.on('join',function(data){
		socket.join(data.name);
		io.sockets.in(data.name).emit('message',{
			message: ' joined the channel',
			room: data.name,
			from: 0
		});
	});
	socket.on('part',function(data){
		socket.leave(data.name);
		io.sockets.in(data.name).emit('message',{
			message: ' parted the channel',
			room: data.name,
			from: 0
		});
	});
});