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
			logger.debug('served static content for '+req.url);
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
	io = require('socket.io').listen(app)
	logger = io.log;
io.set('log level',2);
io.sockets.on('connection',function(socket){
	socket.on('join',function(data){
		socket.join(data.name);
		data.title = data.name;
		socket.emit('join',{
			name: data.name,
			title: data.title
		});
		socket.get('nick',function(e,nick){
			logger.debug(nick+' joined '+data.name);
			io.sockets.in(data.name).emit('message',{
				message: nick+' joined the channel',
				room: data.name,
				from: 0
			});
		});
	});
	socket.on('part',function(data){
		socket.leave(data.name);
		socket.get('nick',function(e,nick){
			logger.debug(nick+' left '+data.name);
			io.sockets.in(data.name).emit('message',{
				message: nick+' parted the channel',
				room: data.name,
				from: 0
			});
		});
	});
	socket.on('message',function(data){
		logger.debug('message sent to '+data.room);
		io.sockets.in(data.room).emit('message',data);
	});
	socket.on('auth',function(data){
		logger.info(data.nick+' registered');
		// TODO - authorize
		socket.set('nick',data.nick);
		socket.emit('authorized');
	});
});