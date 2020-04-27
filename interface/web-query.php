<?php
header("Access-Control-Allow-Origin: *");
$_GET['fromAccount'] = $_SERVER['REMOTE_ADDR'];
$_GET['fromGroup'] = 'web';
$_GET['client'] = 'Web';
require_once('../index.php');
?>