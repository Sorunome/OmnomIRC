#!node
var fs = require('fs'),
	url = require('url'),
	path = require('path'),
	vm = require('vm'),
	toobusy = function(){return false;},//require('toobusy'),
	cluster = require('cluster'),
	options = (function(){
		var defaults = {
				port: 80,
				loglevel: 3,
				redis: {
					port: 6379,
					host: 'localhost'
				}
			},
			options;
		try{
			options = JSON.parse(fs.readFileSync('./options.json'));
			for(var i in options){
				defaults[i] = options[i];
			}
		}catch(e){
			console.warn('Using default settings. Please create options.js');
		}
		return defaults;
	})();
if(typeof fs.existsSync == 'undefined') fs.existsSync = path.existsSync; // legacy support
if(cluster.isMaster){
	for(var i=0;i<require('os').cpus().length;i++){
		cluster.fork();
	}
	cluster.on('exit', function(worker, code, signal) {
		console.log('worker ' + worker.process.pid + ' died');
	});
}else{
	var RedisStore = require('socket.io/lib/stores/redis'),
		redis  = require('socket.io/node_modules/redis'),
		pub    = redis.createClient(options.redis.port,options.redis.host),
		sub    = redis.createClient(options.redis.port,options.redis.host),
		client = redis.createClient(options.redis.port,options.redis.host),
		mimeTypes = {
			'html': 'text/html',
			'js': 'text/javascript',
			'css': 'text/css',
			'png': 'image/png',
			'jpg': 'image/jpeg'
		},
		app = require('http').createServer(function(req,res){
			if(toobusy()){
				res.writeHead(503,{
					'Content-type': 'text/plain'
				});
				res.write('503 Server busy.\n');
				res.end();
				return;
			}
			req.addListener('end',function(){
				logger.debug('served static content for '+req.url);
				var uri = url.parse(req.url).pathname,
					serveFile = function(filename,req,res){
						try{
							stats = fs.lstatSync(filename);
						}catch(e){
							res.writeHead(404,{
								'Content-type': 'text/plain'
							});
							res.write('404 Not Found.\n');
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
							if(fs.existsSync(path.join(filename,'index.html'))){
								serveFile(path.join(filename,'index.html'),req,res);
							}else if(fs.existsSync(path.join(filename,'index.htm'))){
								serveFile(path.join(filename,'index.htm'),req,res);
							}else if(fs.existsSync(path.join(filename,'index.txt'))){
								serveFile(path.join(filename,'index.txt'),req,res);
							}else{
								res.writeHead(200,{
									'Content-Type': 'text/plain'
								});
								res.write('Index of '+url+'\n');
								res.write('TODO, show index');
								res.end();
							}
						}else{
							res.writeHead(500,{
								'Content-Type': 'text/plain'
							});
							res.write('500 Internal server error\n');
							res.end();
						}
					},
					filepath = unescape(uri);
				if(filepath.substr(0,5) == '/api/'){
					filepath = path.join('./api/',filepath.substr(5));
					logger.debug('Attempting to run api script '+filepath);
					if(fs.existsSync(filepath)){
						fs.readFile(filepath,function(e,data){
							if(e){
								logger.error(e);
								res.end('null;');
							}else{
								var output = '',
									sandbox = {
										log: function(text){
											output += text;
										},
										error: function(msg){
											logger.error(msg);
										},
										info: function(msg){
											logger.info(msg);
										},
										debug: function(msg){
											logger.debug(msg);
										},
										head: {
											'Content-Type': 'text/javascript'
										},
										returnCode: 200,
										vm: vm,
										fs: fs
									};
								vm.runInNewContext(data,sandbox,filepath);
								res.writeHead(sandbox.returnCode,sandbox.head);
								res.end(output);
							}
						});
					}else{
						res.writeHead(404,{
							'Content-Type': 'text/javascript'
						});
						res.end('null;');
					}
				}else{
					serveFile(path.join('./www/',filepath),req,res);
				}
			}).resume();
		}).listen(options.port),
		io = require('socket.io').listen(app)
		logger = io.log;
	io.set('log level',options.loglevel);
	if(typeof options.redis.password != 'undefined'){
		var eh = function(e){
			throw e;
		};
		pub.auth(options.redis.ppassword,eh);
		sub.auth(options.redis.ppassword,eh);
		client.auth(options.redis.ppassword,eh);
	}
	io.set('store', new RedisStore({
		redisPub : pub,
		redisSub : sub,
		redisClient : client
	}));
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
					sendUserList(room);
				}
			}
		});
		socket.on('message',function(data){
			logger.debug('message sent to '+data.room);
			io.sockets.in(data.room).emit('message',data);
		});
		socket.on('echo',function(data){
			logger.debug('echoing to '+data.room);
			socket.emit('message',data);
		});
		socket.on('names',function(data){
			var sockets = io.sockets.clients(data.name),
				i;
			runWithUserList(data.name,function(users){
				socket.emit('message',{
					message: data.name+" users:\n\t\t"+users.filter(function(n){return n}).join("\n\t\t"),
					room: data.name,
					from: 0
				});
				sendUserList(data.name);
			});
		});
		socket.on('auth',function(data){
			logger.info(data.nick+' registered');
			// TODO - authorize
			socket.set('nick',data.nick.substr(0,12));
			socket.emit('authorized',{
				nick: data.nick.substr(0,12)
			});
		});
		var runWithUserList = function(room,callback){
				var sockets = io.sockets.clients(room),
					i = 0,
					ret = [],
					getNext = function(){
						if(i < sockets.length){
							sockets[i].get('nick',function(e,nick){
								if(e){
									logger.error(e);
									ret.push('');
								}else{
									logger.debug(room+' '+nick);
									ret.push(nick);
								}
								i++;
								getNext();
							});
						}else{
							callback(ret);
						}
					};
				getNext();
			},
			sendUserList = function(room){
				if(typeof room != 'undefined'){
					runWithUserList(room,function(users){
						io.sockets.in(room).emit('names',{
							room: room,
							names: users
						});
					});
				}
			};
	});
}
process.on('uncaughtException',function(e){
	if(typeof logger != 'undefined'){
		logger.error(e);
	}else{
		console.error(e);
	}
});