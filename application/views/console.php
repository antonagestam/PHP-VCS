<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> <html xmlns="http://www.w3.org/1999/xhtml"> 	<head>		<title>PVCS - Console</title>		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />		<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>static/console.css" />	</head>		<body>		<div id="output"><?php if(isset($out)) echo $out; ?></div>				<form action="" method="post" id="input">			<div id="newrow">				<span id="user"><?php echo $prompt."&nbsp;"; ?></span>				<input type="text" name="query" class="input" />			</div>			<input type="submit" class="hidden" />		</form>				<div id="status-bar">No transfer in progress</div>				<script type="text/javascript" language="javascript" src="http://code.jquery.com/jquery-1.4.2.min.js"></script>		<script type="text/javascript" language="javascript" src="<?php echo base_url(); ?>static/js.js"></script>	</body></html>