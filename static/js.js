$(document).ready(function(){
	var rc = { // creates a remote console handler
		query:'',
		output:'',
		prompt:'',
		remote:{
			send:function(){
				$.ajax({
					cache:false,
					data:{query:rc.query},
					dataType:'json',
					type:'POST',
					//timeout:10000,
					success:function(data){
						//console.log(data); //debug
						rc.output = data.output;
						rc.prompt = data.prompt;
						rc.local.updatestatus(4);
					},
					complete:function()
					{
						rc.local.updatestatus(5);
						rc.local.updateconsole();
					},
					beforeSend:function()
					{
						rc.local.updatestatus(2);
					},
					error:function(xhr,status,error)
					{
						rc.local.updatestatus(3);
					}
				});
			},
			status:1
		},
		local:{
			updateconsole:function(){
				//console.log('local.updateconsole() received output: "'+rc.output+'"');//debug
				//console.log('local.updateconsole() received prompt: "'+rc.prompt+'"');//debug
				// Update the output field
				$('#output').append(rc.output);
				// Update the prompt field
				$('#user').html(rc.prompt+"&nbsp;");
				// Delete the old query from the query field and focus! :)
				$('.input').val('').focus();
			},
			updatestatus:function(status){
				if(status == 1)
				{
					status = 'Nothing sent yet';
				}
				else if(status == 2)
				{
					status = 'Initiated';
				}
				else if(status == 3)
				{
					status = 'An error occured';
				}
				else if(status == 4)
				{
					status = 'Success';
				}
				else if(status == 5)
				{
					status = 'Status is OK';
				}
				//console.log(status); //debug
				$('#status-bar').text(status);
			},
			getquery:function(){
				rc.query = $('.input').val();
				//console.log('collected query: "'+rc.query+'"'); //debug
			}
		},
		execute:function(){
			this.local.getquery();
			
			if(this.query == 'clear')
			{
				this.clear();
			}
			else
			{
				this.remote.send();
			}
		},
		clear:function(){}
	}
	
	var updateWidth = function()
	{
		var maxwidth = $('#newrow').width();
		maxwidth = maxwidth - $('#user').outerWidth();
		spaces = $('.input').outerWidth() - $('.input').width();
		newwidth = maxwidth - spaces;
		$('.input').width(newwidth);
		//console.log('resized to: '+newwidth);//debug
	}
	
	// Communicate with the server via the remote console handler
	// when the user submits a query
	$('#input').submit(function(){
		rc.execute();
		return false;
	});
	
	// Make sure that the query field is always focused
	$('body,html,#output,#input,#statusbar').click(function(){
		$('.input').focus();
	});
	
	// Make sure that the size of the query field always is as wide as possible :)
	updateWidth();
	$(window).resize(updateWidth);
});