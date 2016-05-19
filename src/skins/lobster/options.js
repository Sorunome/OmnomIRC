OmnomIRC.prototype.setOptions = function(){
	var self = this,
		options = this.options.getAll(true);
	$.extend(options,{
		altLines:{
			disp:'Alternating Line Highlight',
			default:true
		},
		enable:{
			disp:'Enable OmnomIRC',
			default:true
		},
		times:{
			disp:'Show Timestamps',
			default:true
		},
		hideUserlist:{
			disp:'Hide Userlist',
			default:false
		},
		scrollBar:{
			disp:'Show Scrollbar',
			default:true
		},
		scrollWheel:{
			disp:'Enable Scrollwheel',
			default:true
		},
		wysiwyg:{
			disp:'Use WYSIWYG editor',
			default:false
		},
		fontSize:{
			disp:'Font Size',
			default:9,
			handler:function(){
				return $('<td>')
					.attr('colspan',2)
					.css('border-right','none')
					.append($('<input>')
						.attr({
							type:'number',
							step:1,
							min:1,
							max:42
						})
						.css('width','3em')
						.val(self.options.get('fontSize'))
						.change(function(){
							self.options.set('fontSize',parseInt(this.value,10));
							$('body').css('font-size',this.value+'pt');
						})
					)
			}
		}
	});
	this.options.setAll(options);
};
