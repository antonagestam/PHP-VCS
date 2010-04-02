$(document).ready(function(){
	var rc = {
		query:'',
		output:'',
		prompt:'',
		remote:{
			send:function(){
				$.ajax({
					cache:false,
					data:{query:this.query},
					dataType:'json',
					type:'POST',
					timeout:10000,
					success:function(data){
						// console.log(data); //debug
						rc.output = data.output;
						rc.prompt = data.prompt;
						rc.remote.status = 4;
					},
					complete:function()
					{
						rc.remote.status = 5;
					},
					beforeSend:function()
					{
						rc.remote.status = 2;
					},
					error:function(xhr,status,error)
					{
						rc.remote.status = 3;
					}
				});
			},
			status:1
		},
		local:{
			updateconsole:function(){},
			updatestate:function(){
				var status = rc.remote.status;
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
				
				$('#status-bar').text(status);
			},
			getquery:function(){
				rc.query = $('.input').val();
				console.log('collected query: "'+rc.query+'"'); //debug
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
				this.local.updateconsole();
				this.local.updatestate();
			}
		},
		clear:function(){}
	}
	
	$('#input').submit(function(){
		rc.execute();
		return false;
	});
});