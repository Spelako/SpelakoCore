<?php
cli_set_process_title('Spelako CLI');

error_reporting(0);
require_once('Spelako.php');
Spelako::loadCommands();

while(true) {
	echo '> /';
	$msg = rtrim(fgets(STDIN));
	$result = Spelako::execute ('/'.$msg, '123456789');
	if($result) echo ($result.PHP_EOL);
}
