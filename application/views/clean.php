<?php
	$array = array(
		'user' => trim($user),
		'output' => $message
	);
	
	echo json_encode($array);