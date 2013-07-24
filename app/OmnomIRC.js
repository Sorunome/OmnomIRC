var static = require('node-static'),
	io = require('socket.io').listen(9000);
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