$(document).ready(function(){
	// Fetch all elements :)
	var inputfield = $('.input');
	var inputform = $('#input');
	var output = $('#output');
	var prompt = $('#user');
	var statusbar = $('#status-bar');
	
	var rc = { // create a remote console handler
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
					timeout:10000,
					success:function(data,ts){
						//console.log(data); //debug
						//console.log(ts); //debug
						if(data == null)
						{
							rc.local.updatestatus(3,ts);
						}
						else
						{
							rc.output = data.output;
							rc.prompt = data.prompt;
							rc.local.updatestatus(4);
						}
					},
					complete:function(xhr,textstatus)
					{
						if(textstatus == 'success')
						{
							rc.local.updatestatus(5);
							rc.local.updateconsole();
						}
						else
						{
							rc.local.updatestatus(3,textstatus);
						}
						//console.log(xhr);//debug
						//console.log(textstatus);//debug
					},
					beforeSend:function()
					{
						rc.local.updatestatus(2);
					},
					error:function(xhr,status,error)
					{
						rc.local.updatestatus(3);
						//alert('error says hi!');//debug
						//console.log(status);//debug
						//console.log(error);//debug
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
				output.append(rc.output);
				// Update the prompt field
				prompt.html(rc.prompt+"&nbsp;");
				// Delete the old query from the query field and focus! :)
				inputfield.val('').focus();
				
				updateWidth();
			},
			updatestatus:function(status,message){
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
					if(message != null)
					{
						status += ': '+message;
					}
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
				statusbar.text(status);
			},
			getquery:function(){
				rc.query = inputfield.val();
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
		clear:function(){
			output.text('');
			inputfield.val('');
		}
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