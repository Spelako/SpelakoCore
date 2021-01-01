<?php
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
	if($errno != (2 || 8)) onException($errfile, $errline, $errstr, $errno);
}, E_ALL | E_STRICT);
set_exception_handler(function ($e) {
	onException($e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode());
});

function onException(string $path, int $line, string $str, int $no){
	$path = str_replace(getcwd(), '', $path); 
	$str = str_replace(getcwd(), '', $str); 
	$dump = (
		'Spelako 在运行时出现了一个致命的错误!'.PHP_EOL.
		'位置: '.$path.' - 第 '.$line.' 行'.PHP_EOL.
		'内容: '.$str.' ('.$no.')'
	);
	die($_GET['plain'] == 'true' ? $dump : json_encode(['success' => false, 'response' => $dump]));
}

set_time_limit(30);
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once('Main.php');

$result = onMessage($_GET['id'], $_GET['msg']);
echo($_GET['plain'] == 'true' ? $result : json_encode(['success' => true, 'response' => $result]));
?>