#!node
var fileServer = (new require('node-static')).Server('./www'),
	app = require('http').createServer(function(req,res){
		req.addListener('end',function(){
			console.log('request made for '+req.url);
			fileServer.serve(req,res);
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