#!node
process.chdir(__dirname);
var fs = require('fs'),
	url = require('url'),
	path = require('path'),
	vm = require('vm'),
	toobusy = function(){return false;},//require('toobusy'),
	noop = function(){},
	cluster = require('cluster'),
	ircClient = require('node-irc'),
	logger = {
		log: function(msg){
			if(options.loglevel > 2){
				console.log(msg);
			}
		},
		debug: function(msg){
			if(options.loglevel > 2){
				console.log('DEBUG - '+msg);
			}
		},
		warn: function(msg){
			if(options.loglevel > 1){
				console.log('WARN - '+msg);
			}
		},
		info: function(msg){
			if(options.loglevel > 1){
				console.log('INFO - '+msg);
			}
		},
		error: function(msg){
			if(options.loglevel > 0){
				console.error(msg);
			}
		}
	},
	options = global.options = (function(){
		var defaults = {
				port: 80,
				loglevel: 3,
				threads: require('os').cpus().length,
				redis: {
					port: 6379,
					host: 'localhost'
				},
				debug: false,
				paths: {
					www: './www/',
					api: './api/',
					plugins: './plugins/'
				},
				irc: {
					host: 'irp.irc.omnimaga.org',
					port: 6667,
					nick: 'oirc3',
					name: 'OmnomIRC3',
					channels: [
						'#omnimaga'
					],
					messages: {
						quit: 'Server closed'
					}
				},
				origins: [
					['O','OmnomIRC'],
					['#','IRC']
				]
			},
			i,
			options;
		try{
			options = JSON.parse(fs.readFileSync('./options.json'));
			defaults = (function merge(options,defaults){
				for(var i in options){
					if(typeof defaults[i] != 'object' || defaults[i] instanceof Array){
						defaults[i] = options[i];
					}else{
						defaults[i] = merge(options[i],defaults[i]);
					}
				}
				return defaults
			})(options,defaults);
		}catch(e){
			console.warn('Using default settings. Please create options.json');
			console.error(e);
		}
		defaults.origins.unshift(['S','Server'],['?','Unknown']);
		options = {};
		for(i in  defaults){
			Object.defineProperty(options,i,{
				value: defaults[i],
				enumerable: true,
				writable: false
			});
		}
		return options;
	})(),
	origin = function(name){
		for(var i in options.origins){
			if(options.origins[i][1] == name){
				return i;
			}
		}
		return 1;
	};
if(typeof fs.existsSync == 'undefined') fs.existsSync = path.existsSync; // legacy support
if(cluster.isMaster){
	var iWorker;
	cluster.on('exit', function(worker, code, signal) {
		console.log('worker ' + worker.process.pid + ' died');
	});
	iWorker = global.iw = cluster.fork();
	iWorker.on('online',function(){
		logger.info('First worker online');
		iWorker.send('S');
	});
	for(var i=1;i<options.threads;i++){
		cluster.fork().on('online',function(){
			logger.info('Child socket worker online');
		});
	}
	for(i in cluster.workers){
		var worker = cluster.workers[i];
		worker.on('message',function(msg){
			var c = msg[0];
			msg = msg.substr(1);
			logger.debug('Parent recieved command '+c+' with message '+msg);
			switch(c){
				case 'M':
					iWorker.send('M'+msg);
				break;
			}
		});
	}
	if(options.debug){
		require('repl').start({
			prompt: '> ',
			useGlobal: true
		}).on('exit',function(){
			for(var i in cluster.workers){
				cluster.workers[i].send('Q');
			}
			process.exit();
		});
	}
}else{
	process.on('message',function(msg){
		var c = msg[0];
		msg = msg.substr(1);
		
		switch(c){
			case 'Q':
				if(typeof app != 'undefined' && typeof irc == 'undefined'){
					app.close();
				}else if(typeof irc != 'undefined'){
					irc.quit(options.irc.messages.quit);
				}
			break;
			case 'M':
				if(typeof irc != 'undefined'){
					msg = JSON.parse(msg);
					if(typeof msg.message != 'udefined'){
						irc.say(msg.room,'('+options.origins[msg.origin][0]+')'+'<'+msg.from+'> '+msg.message);
					}
				}
			break;
			case 'S':
				logger.info('Child starting irc');
				irc = new ircClient(options.irc.host,options.irc.port,options.irc.nick,options.irc.name);
				irc.on('ready',function(){
					logger.info('Connected to IRC');
					for(var i in options.irc.channels){
						irc.join(options.irc.channels[i]);
						//irc.client.send('WHO %s\n',options.irc.channels[i]);
					}
				});
				irc.on('CHANMSG',function(d){
					console.log(d);
					message(d.reciever,d.sender,d.message,origin('IRC'));
				});
				// Beginnings of names handler
				/*irc.on('names',function(chan,nicks){
					for(var i in nicks){
						logger.debug('[NICKS] Channel '+chan+' '+nicks[i]);
					}
				});*/
				irc.connect();
				logger.debug('Connecting to IRC');	
			break;
		}
	});
	logger.info('Child starting socket.io');
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
		message = function(room,from,message,origin,socket){
			if(typeof socket == 'undefined'){
				socket = io.sockets.in(room);
			}
			socket.emit('message',{
				message: message,
				room: room,
				from: from,
				origin: origin
			})
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
					filepath = path.join(options.paths.api,filepath.substr(5));
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
										fs: fs,
										options: options
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
					serveFile(path.join(options.paths.www,filepath),req,res);
				}
			}).resume();
		}).listen(options.port),
		io = require('socket.io').listen(app);
	io.set('log level',options.loglevel);
	io.log = logger;
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
				name: data.name
			});
			sendUserList(data.name);
			socket.get('nick',function(e,nick){
				logger.debug(nick+' joined '+data.name);
				fromServer(data.name,nick+' joined the channel');
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
			process.send('M'+JSON.stringify(data));
		});
		socket.on('echo',function(data){
			logger.debug('echoing to '+data.room);
			socket.emit('message',data);
		});
		socket.on('names',function(data){
			var sockets = io.sockets.clients(data.name),
				i;
			runWithUserList(data.name,function(users){
				var temp = [],i;
				for(i in users) i && i != null && temp.push(users[i]);
				users = temp;
				fromServer(data.name,data.name+" users:\n\t\t"+users.join("\n\t\t"),socket);
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
								}else if(!inArray(ret,nick)){
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
			inArray = function(arr,val){
				for(var i in arr){
					if(arr[i] == val){
						return true;
					}
				}
				return false;
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
			},
			fromServer = function(room,message,socket){
				if(typeof socket == 'undefined'){
					socket = io.sockets.in(room);
				}
				socket.emit('message',{
					message: message,
					room: room,
					from: 0,
					origin: 2
				});
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