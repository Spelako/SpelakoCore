<?php
global $stream_opts;
$GLOBALS['stream_opts'] = [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false
	],
	'http' => [
		'timeout' => 20
	]
];
date_default_timezone_set('PRC');

// 文件操作模块
function rfile($path) { // 读文件
	if(file_exists($path))
		$f = file_get_contents($path);
	else
		$f = NULL;
	return $f;
}

function wfile($path, $contents) { // 写文件
	if(!file_exists($path)){
		$spl = explode('/', $path);
		$dir = implode('/', array_slice($spl, 0, count($spl) - 1));
		//echo '| dir: '.$dir.', full: '.$path.' |';
		mkdir($dir, NULL, true);
		touch($path);
	};
	file_put_contents($path, $contents);
}

function format_size($byte) { // 格式化大小
	$a = array('字节', 'KB', 'MB', 'GB', 'TB', 'PB');
	$pos = 0;
	while ($byte >= 1024) {
		$byte /= 1024;
		$pos ++;
	}
	return round($byte, 2).' '.$a[$pos];
}

function fsize($path) { // 取格式化的文件大小
	if(file_exists($path)) {
		$size = filesize($path);
		return format_size($size);
	}
	else return '文件不存在';
}

function dsize($path, $noformat = false) { // 取目录大小
	if(is_dir($path)) {
		$size = 0;
		$handle = opendir($path);
		while (($item = readdir($handle)) !== false) {
			if ($item == '.' || $item == '..') continue;
			$_path = $path . '/' . $item;
			if (is_file($_path)) $size += filesize($_path);
			if (is_dir($_path)) $size += dsize($_path, true);
		}
		closedir($handle);
		return $noformat? $size : format_size($size);
	}
	else return '目录不存在';
}

function dcount($path) { // 取目录文件数 
		$num=0;
		$arr = glob($path);
		foreach ($arr as $v) {
			if(is_file($v)) {
				$num++;
			}
			else {
				$num += dcount($v."/*");
			}
		}
		return $num;
}

function deldir($path){ // 清空目录
	if(is_dir($path)) {
		$p = scandir($path);
		foreach($p as $val) {
			if($val != '.' && $val != '..') {
				if(is_dir($path.$val)) {
					deldir($path.$val.'/');
					@rmdir($path.$val.'/');
				}
				else{
					unlink($path.$val);
				}
			}
		}
	}
}

function isOutdated($path, $timeout) {
	if(file_exists($path)) {
		return ((time() - filemtime($path)) > $timeout);
	}
	else return true;
}

// 黑名单模块
function getBlacklist($isGroup = false){
	$contents = rfile($isGroup ? 'saves/blacklist/group.txt' : 'saves/blacklist/user.txt');
	$list = explode(PHP_EOL, $contents);
	$list = array_filter($list);
	return $list;
}
function saveBlacklist($list, $isGroup = false){
	$contents = implode(PHP_EOL, array_filter($list));
	wfile($isGroup ? 'saves/blacklist/group.txt' : 'saves/blacklist/user.txt', $contents);
}
function isBlacklisted(string $number, bool $isGroup = false){
	return in_array($number, getBlacklist($isGroup));
}
function blacklistAdd(string $number, bool $isGroup = false){
	$list = getBlacklist($isGroup);
	if(in_array($number, $list)){
		return false;
	}
	else {
		array_push($list, $number);
		saveBlacklist($list, $isGroup);
		return true;
	}
}
function blacklistRemove(string $number, bool $isGroup = false){
	$list = getBlacklist($isGroup);
	if(in_array($number, $list)){
		$list = array_diff($list, [$number]);
		saveBlacklist($list, $isGroup);
		return true;
	}
	else {
		return false;
	}
}

// 管理员模块
function getStaffs(){
	$contents = rfile('saves/staff.txt');
	$list = explode(PHP_EOL, $contents);
	$list = array_filter($list);
	return $list;
}
function isStaff($account){
	return in_array($account, getStaffs());
}


// 冷却模块
function getCooldowns(){
	$contents = rfile('cache/cooldown.json');
	$arr = json_decode($contents, true);
	return $arr;
}
function saveCooldowns($list){
	wfile('cache/cooldown.json', json_encode($list));
}
function userExecute(string $user){
	$cd = getCooldowns();
	$cd[$user] = time();
	saveCooldowns($cd);
}
function getAvailability(string $user){
	$cd = getCooldowns();
	return (time() - $cd[$user] > 6);
}

function toDate($unix, $second = false) {
	if($unix == null)
		return '未知';
	else
		return date('Y-m-d H:i', $unix / ($second ? 1 : 1000)).' CST';
}

// 纠错模块
function similarCommand($findBy, array $cmdList) {
	$findBy = substr($findBy, 1);
	foreach ($cmdList as $v) {
		similar_text($findBy, $v, $percent);
		$sCmdList[$v] = $percent;
	}
	$bestValue = max($sCmdList);
	$bestMatch = array_search($bestValue, $sCmdList);
	return ($bestValue > 70 && $bestValue != 100) ? $bestMatch : false;
}

// 其他
function getBinArr($dec, $length) {
	$bin = decbin($dec);
	if(strlen($bin) > $length)
		return false;
	else
		return str_split(str_pad($bin, $length, '0'));
}
?>