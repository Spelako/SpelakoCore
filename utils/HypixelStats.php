<?php
function hypixel_getstats($apiKey, $player) {
	$player = strtolower($player);

	if (!isOutdated('cache/hypixel/player/'.$player.'.json', 30)) {
		$src = rfile('cache/hypixel/player/'.$player.'.json');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://api.hypixel.net/player?key='.$apiKey.'&name='.$player, false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	$result = json_decode($src, true);
	if($result['success'] && $result['player'] != null) {
		if(!$usingCache) wfile('cache/hypixel/player/'.$player.'.json', $src);
		return $result;
	}
	else return false;
}

function hypixel_getguild($apikey, $playerUuid) {
	if (!isOutdated('cache/hypixel/guild/'.$playerUuid.'.json', 30)) {
		$src = rfile('cache/hypixel/guild/'.$playerUuid.'.json');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://api.hypixel.net/guild?key='.$apikey.'&player='.$playerUuid, false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	$result = json_decode($src, true);
	if($result['success'] && $result['guild'] != null){
		if(!$usingCache) wfile('cache/hypixel/guild/'.$playerUuid.'.json', $src);
		return $result;
	}
	else return false;
}

// Skywars 等级
function getLevelSW($formatStr) {
	$filter = str_replace(
		array('§0', '§1', '§2', '§3', '§4', '§5', '§6', '§7', '§8', '§9', '§a', '§b', '§c', '§d', '§e', '§f', '§k', '§l', '§m', '§n', '§o', '§r')
	, '', $formatStr);
	return $filter;
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
?>