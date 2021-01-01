<?php
$id = 'cli-user';
require_once('Main.php');

while(true) {
	echo '> /';
	$msg = rtrim(fgets(STDIN));
	$result = onMessage($id, '/'.$msg);
	if($result) echo ($result.PHP_EOL);
}