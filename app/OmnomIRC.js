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
io.set('log level',3);
io.sockets.on('connection',function(socket){
	socket.on('join',function(data){
		socket.join(data.name);
		data.title = data.name;
		socket.emit('join',{
			name: data.name,
			title: data.title
		});
		sendUserList(data.name);
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
			sendUserList(data.name);
		});
	});
	socket.on('disconnect',function(data){
		var rooms = io.sockets.manager.rooms,
			i,
			room;
		for(i in rooms){
			if(rooms[i] != '' && typeof rooms[i] == 'string'){
				try{
					room = rooms[i].substr(1);
				}catch(e){}
				sendUserList(names);
			}
		}
	});
	socket.on('message',function(data){
		logger.debug('message sent to '+data.room);
		io.sockets.in(data.room).emit('message',data);
	});
	socket.on('names',function(data){
		var sockets = io.sockets.clients(data.name),
			i;
		socket.emit('message',{
			message: data.name+' users:',
			room: data.name,
			from: 0
		});
		for(i in sockets){
			sockets[i].get('nick',function(e,nick){
				socket.emit('message',{
					message: '	'+nick,
					room: data.name,
					from: 0
				});
			});
		}
		sendUserList(data.name);
	});
	socket.on('auth',function(data){
		logger.info(data.nick+' registered');
		// TODO - authorize
		socket.set('nick',data.nick.substr(0,12));
		socket.emit('authorized',{
			nick: data.nick.substr(0,12)
		});
	});
	var usersInRoom = function(room){
			var sockets = io.sockets.clients(room),
				i,
				ret = [];
			for(i in sockets){
				sockets[i].get('nick',function(e,nick){
					ret.push(nick);
				});
			}
			return ret;
		},
		sendUserList = function(room){
			if(typeof room != 'undefined'){
				io.sockets.in(room).emit('names',{
					room: room,
					names: usersInRoom(room)
				});
			}
		};
});