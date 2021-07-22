<?php
function hypixel_getstats($apiKey, $player) {
	$player = strtolower($player);

	if (!isOutdated('cache/hypixel/player/'.$player.'.json', 120)) {
		$src = rfile('cache/hypixel/player/'.$player.'.json');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://api.hypixel.net/player?key='.$apiKey.'&name='.$player, false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	$result = json_decode($src, true);
	if($result['success'] && $result['player'] != null) {
		if(!$usingCache) {
			unlink('cache/hypixel/player/'.$player.'.json');
			wfile('cache/hypixel/player/'.$player.'.json', $src);
		}
		return $result;
	}
	else return false;
}

function hypixel_getguild($apikey, $playerUuid) {
	if (!isOutdated('cache/hypixel/guild/'.$playerUuid.'.json', 120)) {
		$src = rfile('cache/hypixel/guild/'.$playerUuid.'.json');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://api.hypixel.net/guild?key='.$apikey.'&player='.$playerUuid, false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	$result = json_decode($src, true);
	if($result['success'] && $result['guild'] != null){
		if(!$usingCache) {
			unlink('cache/hypixel/guild/'.$playerUuid.'.json');
			wfile('cache/hypixel/guild/'.$playerUuid.'.json', $src);
		}
		return $result;
	}
	else return false;
}

function hypixel_skyblock_auction($apiKey, $profile) {
	if (!isOutdated('cache/hypixel/skyblock/auction/'.$profile.'.json', 120)) {
		$src = rfile('cache/hypixel/skyblock/auction/'.$profile.'.json');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://api.hypixel.net/skyblock/auction?key='.$apiKey.'&profile='.$profile, false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	$result = json_decode($src, true);
	if($result['success'] && $result['auctions'] != null){
		if(!$usingCache) {
			unlink('cache/hypixel/skyblock/auction/'.$profile.'.json');
			wfile('cache/hypixel/skyblock/auction/'.$profile.'.json', $src);
		}
		return $result;
	}
	else return false;
}

function hypixel_skyblock_profile($apikey, $profile) {
	if (!isOutdated('cache/hypixel/skyblock/profile/'.$profile.'.json', 120)) {
		$src = rfile('cache/hypixel/skyblock/profile/'.$profile.'.json');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://api.hypixel.net/skyblock/profile?key='.$apikey.'&profile='.$profile, false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	$result = json_decode($src, true);
	if($result['success'] && $result['profile'] != null){
		if(!$usingCache) {
			unlink('cache/hypixel/skyblock/profile/'.$profile.'.json');
			wfile('cache/hypixel/skyblock/profile/'.$profile.'.json', $src);
		}
		return $result;
	}
	else return false;
}

// Skyblock
function queryProfileSB($profiles, $query) {
	// 在 player.stats.Skyblock.profiles 提供的存档列表中, 根据参数二的条件查找 profile_id. 参数二可以是存档序号(int, 从 1 开始)或存档名(string).
	if(is_numeric($query)) {
		$profile_id = array_keys($profiles)[(int)$query - 1];
	}
	else {
		foreach($profiles as $k => $v) {
			if(strcasecmp($v['cute_name'], $query) == 0) {
				$profile_id = $v['profile_id'];
			}
		}
	}
	return $profile_id;
}

function getLevelSB($exp, $runecrafting = false) {
	if($runecrafting) {
		$levelingLadder = array(0,50,150,275,435,635,885,1200,1600,2100,2725,3510,4510,5760,7325,9325,11825,14950,18950,23950,30200,38050,47850,60100,75400,94450);
	}
	else {
		$levelingLadder = array(0,50,175,375,675,1175,1925,2925,4425,6425,9925,14925,22425,32425,47425,67425,97425,147425,222425,322425,522425,822425,1222425,1722425,2322425,3022425,3822425,4722425,5722425,6822425,8022425,9322425,10722425,12222425,13822425,15522425,17322425,19222425,21222425,23322425,25522425,27822425,30222425,32722425,35322425,38072425,40972425,44072425,47472425,51172425,55172425);
	}
	foreach($levelingLadder as $lv => $required) {
		if($exp < $required) return ($lv - 1);
	}
}

// Network Rank
function getRank($player) {
	if (isset($player['player']['rank']) && $player['player']['rank'] != 'NONE' && $player['player']['rank'] != 'NORMAL') {
		return ('['.$player['player']['rank'].'] ');
	}
	if (isset($player['player']['monthlyPackageRank']) && $player['player']['monthlyPackageRank'] != 'NONE') {
		return ('[MVP++] ');
	}
	if (isset($player['player']['newPackageRank']) && $player['player']['newPackageRank'] != 'NONE') {
		return ('['.str_replace('_PLUS', '+', $player['player']['newPackageRank']).'] ');
	}
}

// Network 等级
function getLevel($exp) {
	$REVERSE_PQ_PREFIX = -3.5;
	$REVERSE_CONST = 12.25;
	$GROWTH_DIVIDES_2 = 0.0008;
	return $exp < 0 ? 1 : floor(1 + $REVERSE_PQ_PREFIX + sqrt($REVERSE_CONST + $GROWTH_DIVIDES_2 * $exp));
}

// 公会等级
function getLevelGuild($exp) {
	static $guildLevelTables = [100000, 150000, 250000, 500000, 750000, 1000000, 1250000, 1500000, 2000000, 2500000, 2500000, 2500000, 2500000, 2500000, 3000000];
	$level = 0;
	for ($i = 0; ; $i++) {
		$need = $i >= sizeof($guildLevelTables) ? $guildLevelTables[sizeof($guildLevelTables) - 1] : $guildLevelTables[$i];
		$exp -= $need;
		if ($exp < 0) {
			return $level;
		} else {
			$level++;
		}
	}
	return -1;
}

// 其他
function clearColorFormat($formatStr) {
	$filter = str_replace(
		array('§0', '§1', '§2', '§3', '§4', '§5', '§6', '§7', '§8', '§9', '§a', '§b', '§c', '§d', '§e', '§f', '§k', '§l', '§m', '§n', '§o', '§r')
	, '', $formatStr);
	return $filter;
}
?>
