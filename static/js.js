$(document).ready(function(){
	$('.input').focus();
	$('#input').submit(function(){
		var querystring = $('.input').val();
		
		if(querystring == 'clear')
		{
			$('#output').text('');
		}
		
		$.ajax({
			cache:false,
			context:$('#output'),
			data:{query:querystring},
			dataType:'json',
			type:'POST',
			timeout:10000,
			success:function(data){
				// console.log(data); //debug
				$(this).append(data.output);
				$('#user').html(data.prompt+"&nbsp;");
				$('.input').val('').focus();
				// Update status field
				$('#status-bar').text('Transfer succeeded');
			},
			complete:function()
			{
				$('#status-bar').text('Status is OK');
			},
			beforeSend:function()
			{
				$('#status-bar').text('Transfer initiated');
			},
			error:function(xhr,status,error)
			{
				// console.log(status); //debug
				// console.log(error); //debug
				$('#status-bar').text('An error occured: '+status);
			}
		});
		return false;
	});
	
	$('html,body').click(function(){
		$('input').focus();
	});});