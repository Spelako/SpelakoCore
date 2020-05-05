<?php
// 指令处理
header('content-type:text/plain;charset=utf-8');
require_once('utils/SpelakoUtils.php'); // Spelako 函数库.
if(isBlacklisted($_GET['fromGroup'], true) || isBlacklisted($_GET['fromAccount'])) exit();

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
	$errno != (2 || 8) &&
displayError($errfile, $errline, $errstr, $errno);},E_ALL | E_STRICT);

set_exception_handler(function ($e) {displayError($e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode());});

function displayError(string $path, int $line, string $str, int $no){
	$path = str_replace(getcwd(), '', $path); 
	$str = str_replace(getcwd(), '', $str); 
	echo
	'Spelako 在运行时出现了一个致命的错误!'.PHP_EOL.
	'位置: '.$path.' - 第 '.$line.' 行'.PHP_EOL.
	'内容: '.$str.' ('.$no.')';
	die();
}

require_once('Commands.php');
onMessage($_GET['fromAccount'], $_GET['fromGroup'], $_GET['msg']);
?>