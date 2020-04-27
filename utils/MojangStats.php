<?php
function mojang_getstats(string $arg){
	if(strlen($arg) > 16){
		$src = file_get_contents('https://api.mojang.com/user/profiles/'.$arg.'/names', false, stream_context_create($GLOBALS['stream_opts']));
		$history = json_decode($src, true);
		if($history){
			$result['method'] = 'UUID';
			$result['uuid'] = $arg;
			$result['names'] = array_reverse($history);
		}
		else {
			$result = false;
		}
	}
	else if (strlen($arg) > 0){
		$src = file_get_contents('https://api.mojang.com/users/profiles/minecraft/'.$arg, false, stream_context_create($GLOBALS['stream_opts']));
		$decoded = json_decode($src, true);
		if($decoded){
			$historysrc = file_get_contents('https://api.mojang.com/user/profiles/'.$decoded['id'].'/names', false, stream_context_create($GLOBALS['stream_opts']));
			$history = json_decode($historysrc, true);
			$result['method'] = 'ID';
			$result['uuid'] = $decoded['id'];
			$result['names'] = array_reverse($history);
		}
		else {
			$result = false;
		}
	}
	else {
		$result = false;
	}
	return $result;
}
?>