<?php
function syuu_getleaderboard_practice() {
	if (!isOutdated('cache/syuu/leaderboard.json', 300)) {
		$src = rfile('cache/syuu/leaderboard.json');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://api.syuu.net/public/leader-boards/practice', false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	$result = json_decode($src, true)['response'];
	if($result) {
		if(!$usingCache) wfile('cache/syuu/leaderboard.json', $src);
		return $result;
	}
	else return false;
}

function syuu_getparsedplayerstats($player) {
	if (!isOutdated('cache/syuu/user/'.$player.'.html', 120)) {
		$src = rfile('cache/syuu/user/'.$player.'.html');
		$usingCache = true;
	}
	else {
		$src = file_get_contents('https://www.syuu.net/user/'.$player, false, stream_context_create($GLOBALS['stream_opts']));
		$usingCache = false;
	}

	if($src) {
		if(!$usingCache) wfile('cache/syuu/user/'.$player.'.html', $src);

		$regex = '#<td class="text-left">(.+)</td>\n<td class="text-left">(.+)<a /></td>\n<td class="text-left">(.+)<a /></td>\n<td class="text-left">(.+)<a /></td>#';
		preg_match_all($regex, $src, $matches);
		foreach($matches[1] as $k => $v) {
			$stats['RankedData'][$v]['Elo'] = $matches[2][$k];
			$stats['RankedData'][$v]['Win'] = $matches[3][$k];
			$stats['RankedData'][$v]['Lose'] = $matches[4][$k];
		}
		return $stats;
	}
	else return false;
}

function syuu_getcategoryname($category) {
	$categories = [
		'Sharp2Prot2' => 's2p2',
		'Archer' => 'bow',
		'NoDelay' => 'combo',
		'BuildUHC' => 'buhc',
		'Sumo' => 'sumo',
		'Spleef' => 'spleef'
	];

	foreach($categories as $k => $v) {
		if(strcasecmp($category, $k) == 0 || strcasecmp($category, $v) == 0) {
			return $k;
		}
	}
}

global $stream_opts;
global $stream_opts_2;

$GLOBALS['stream_opts'] = [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false
	],
];

function curl_file_get_contents($durl){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $durl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$r = curl_exec($ch);
	curl_close($ch);
	return $r;
}
?>