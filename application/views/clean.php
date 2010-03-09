<?php
	$array = array(
		'prompt' => trim($user),
		'output' => $out
	);
	
	echo json_encode($array);