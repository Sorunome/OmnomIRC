if(typeof $.fn.selectRange !-'undefined'){
	$.fn.selectRange = function(start, end) {
		return this.each(function() {
			if(this.setSelectionRange) {
				this.focus();
				this.setSelectionRange(start, end);
			} else if(this.createTextRange) {
				var range = this.createTextRange();
				range.collapse(true);
				range.moveEnd('character', end);
				range.moveStart('character', start);
				range.select();
			}
		});
	};

}
$("#input").keydown(function(e){
	var oldMessages = [],
		room = room = $o.ui.tabs.current().name,
		temp = $.localStorage('oldMessages-'+room);
	if (temp!=null){
		oldMessages = temp.split("\n");
	}
	if ($('#input').data('oldMessageCounter')==oldMessages.length){
		$('#input').data('currentMessage',$('#input').val());
	}
	if (oldMessages.length!=0){
		switch(e.which){
			case 38:
				if ($('#input').data('oldMessageCounter')!=0){
					$('#input').data('oldMessageCounter',$('#input').data('oldMessageCounter')-1);
				}
				$('#input').val(oldMessages[$('#input').data('oldMessageCounter')]);
			break;
			case 40:
				if ($('#input').data('oldMessageCounter')!=oldMessages.length){
					$('#input').data('oldMessageCounter',$('#input').data('oldMessageCounter')+1);
				}
				if ($('#input').data('oldMessageCounter')==oldMessages.length){
					$('#input').val($('#input').data('currentMessage'));
				}else{
					$('#input').val(oldMessages[$('#input').data('oldMessageCounter')]);
				}
			break;
		}
		temp = $('#input').val().length;
		$('#input').selectRange(temp,temp);
	}
});
$('#input').data({
	'oldMessageCounter':1,
	'currentMessage':''
});
hook("tabswitch",function(newT,oldT){
	var oldMessages = [],
		room = newT.name,
		temp = $.localStorage('oldMessages-'+room);
	if (temp!=null){
		oldMessages = temp.split("\n");
	}
	$('#input').data('oldMessageCounter',oldMessages.length);
});
hook("load",function(){
	var oldMessages = [],
		room = $o.ui.tabs.current().name,
		temp = $.localStorage('oldMessages-'+room);
	if (temp!=null){
		oldMessages = temp.split("\n");
	}
	$('#input').data('oldMessageCounter',oldMessages.length);
});
hook("send",function(msg,room){
	var oldMessages = [],
		temp = $.localStorage('oldMessages-'+room);
	if (temp!=null){
		oldMessages = temp.split("\n");
	}
	oldMessages.push(msg);
	if (oldMessages.length>20){
		oldMessages.shift();
	}
	$.localStorage('oldMessages-'+room,oldMessages.join("\n"));
	$('#input').data('oldMessageCounter',oldMessages.length);
});

// $('#myel').data('key','val');