<?php
// 配置部分
$version = '1.0.0-pre2'; // 机器人版本.
$staff = [ // 机器人管理员.
	'1244050218', // Peaksol
	'1151974902', // Seaksol
	'1265467620', // PVZer
	'1633946103' // Dian_Jiao
]; 
$maintenance = false; // 维护模式(打开后仅管理员可使用指令).

// 指令处理
$_GET['client'] != 'CLI' && header('content-type:text/plain;charset=utf-8');
require_once('utils/SpelakoUtils.php'); // Spelako 函数库.
if(isBlacklisted($_GET['fromGroup'], true) || isBlacklisted($_GET['fromAccount'])) exit();
if($maintenance && !in_array($_GET['fromAccount'], $staff)) exit('Spelako 处于维护模式, 仅机器人管理员可使用此指令!');

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

require_once('Main.php');
?>