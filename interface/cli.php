<?php
$_GET['fromAccount'] = 'CLI';
$_GET['fromGroup'] = 'CLI';
$_GET['client'] = 'CLI';
unset($argv[0]);
$_GET['msg'] = implode(' ', $argv);
require_once('../index.php');
echo PHP_EOL.PHP_EOL;
?>