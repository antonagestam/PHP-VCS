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
			success:function(data){
				//console.log(data);
				$(this).append(data.output);
				$('#user').html(data.user+"&nbsp;");
				$('.input').val('').focus();
			}
			
		});
		return false;
	});
	
	$('html,body').click(function(){
		$('input').focus();
	});
});