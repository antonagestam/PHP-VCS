<?php
	$array = array(
		'prompt' => trim($prompt),
		'output' => $out
	);
	
	echo json_encode($array);