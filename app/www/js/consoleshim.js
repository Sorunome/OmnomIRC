(function(window,undefined){
    "use strict";
	var deepCopy = function(p,c) {
			c = c||{};
			for (var i in p) {
				if (typeof p[i] === 'object') {
					c[i] = (p[i].constructor === Array)?[]:{};
					deepCopy(p[i],c[i]);
				}else{
					c[i] = p[i];
				}
			}
			return c;
		},
		oC = typeof console != 'undefined' ? deepCopy(console) : {},
		i,
		console = window.console,
        noop = function(){},
		a = ['log','info','debug','error','warn'];
	console._call = function(index,name,args){
		console._old[index].apply(window,args);
		var d = $('#console-log'),
		msg = name+': '+Array.prototype.slice.call(args,0).join("\t");
		if(d.children().length === 0){
			d.css({
				margin: 0,
				padding: 0
			}).append($('<pre>').attr('id','console-log-pre'));
		}
		d.children('pre').each(function(){
			$(this).html($(this).html()+""+msg+"<br/>");
		});
		return msg;
	};
	console._old = [];
	for(i=0;i<a.length;i++){
		var name = a[i],
			index = i;
		console._old.push(oC[name] || noop);
		if(a[i] != 'error'){
			console[name] = function(){
				return console._call(index,name.toUpperCase(),arguments)
			}
		}else{
			console[name] = function(){
				var args;
				for(var i in arguments){
					args.push(arguments[i].message);
				}
				return console._call(index,name.toUpperCase(),args)
			}
		}
	}
})(window);