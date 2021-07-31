<?php
class SyuuCommand {
	const USAGE = '/syuu ...';
	const ALIASES = [];
	const DESCRIPTION = '获取 SyuuNet 的玩家信息或排行榜';
	const COOLDOWN = true;

	const API_BASE_URL = 'https://api.syuu.net';
	const WEB_BASE_URL = 'https://www.syuu.net';

	public static function execute(array $args) {
		switch($args[1]) {
			case 'player':
			case 'user':
				if(!isset($args[2])) return '正确用法: /syuu player <玩家>';
				$p = self::fetchPlayerStats($args[2]);
				if($p == 'ERROR_REQUEST_FAILED') return '查询请求发送失败, 请稍后再试.';
				if($p == 'ERROR_RANKED_DATA_NOT_FOUND') return '此玩家没有排位数据.';
				$placeholder = array();
				foreach($p['RankedData'] as $k => $v) {
					array_push($placeholder, sprintf(
						'[%1$s] Elo: %2$d | 胜: %3$d | 败: %4$d',
						$k,
						$v['Elo'],
						$v['Win'],
						$v['Lose']
					));
				}
				return SpelakoUtils::buildString([
					'%1$s 的 SyuuNet 排位信息:',
					'%2$s'
				], [
					$p['name'],
					SpelakoUtils::buildString($placeholder)
				]);
			case 'leaderboards':
			case 'leaderboard':
			case 'lb':
				if(!isset($args[2])) {
					return SpelakoUtils::buildString([
						'正确用法: /syuu lb <分类>',
						'"分类" 可以是下列之一:',
						'- sharp2prot2, s2p2',
						'- sharp4prot3, s4p3',
						'- archer, bow',
						'- nodelay, combo',
						'- builduhc, buhc',
						'- sumo',
						'- finaluhc',
						'... 欲查看完整列表, 请访问帮助文档.'
					]);
				}
				$lb = self::fetchPracticeLeaderboards();
				if(!$lb) return '无法解析来自 SyuuNet 的数据.';
				$category = self::getCategoryName($args[2]);
				$invalidCategory = false;
				if(!$category) {
					$category = 'Sharp2Prot2';
					$invalidCategory = true;
				}
				$placeholder = array();
				foreach($lb[$category] as $k => $v) {
					array_push($placeholder, sprintf(
						'%1$d. %2$s - %3$d',
						$k + 1,
						$v['lastknownname'],
						$v['rankedelo']
					));
				}
				return SpelakoUtils::buildString([
					$invalidCategory ? '未知的分类, 已跳转至默认分类.' : '',
					'SyuuNet 的 %1$s 排行榜:',
					'%2$s', //body
				], [
					$category,
					SpelakoUtils::buildString($placeholder)
				]);
			default:
				return '正确用法: /syuu player <玩家> 或 /syuu lb <分类>';
		}
	}

	private static function fetchPracticeLeaderboards() {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/public/leader-boards/practice', cacheExpiration: 300, cachePath: Spelako::CONFIG['cache_directory']);
		if($src && ($result = json_decode($src, true)['response'])) {
			return $result;
		}
		return false;
	}
	
	private static function fetchPlayerStats($player) {
		if(strlen($player) > 16) return false;
		$src = SpelakoUtils::getURL(self::WEB_BASE_URL.'/user/'.$player, cacheExpiration: 300, cachePath: Spelako::CONFIG['cache_directory']);
		if(!$src) return 'ERROR_ERQUEST_FAILED';
		$regex = '#<td class="text-left">(.+)</td>\n<td class="text-left">(.+)<a /></td>\n<td class="text-left">(.+)<a /></td>\n<td class="text-left">(.+)<a /></td>#';
		preg_match_all($regex, $src, $matches);
		$stats = array();
		foreach($matches[1] as $k => $v) {
			$stats['RankedData'][$v]['Elo'] = $matches[2][$k];
			$stats['RankedData'][$v]['Win'] = $matches[3][$k];
			$stats['RankedData'][$v]['Lose'] = $matches[4][$k];
		}
		if(!$stats) return 'ERROR_RANKED_DATA_NOT_FOUND';
		$stats['name'] = substr($src, strpos($src, '<p>') + 3, strlen($player));
		return $stats;
	}

	private static function getCategoryName($category) {
		return match($category) {
			'sharp2prot2', 's2p2' => 'Sharp2Prot2',
			'mcsg', 'sg' => 'MCSG',
			'octc' => 'OCTC',
			'gapple', 'goldenapple' => 'Gapple',
			'archer', 'bow' => 'Archer',
			'combo', 'nodelay' => 'NoDelay',
			'soup' => 'Soup',
			'builduhc', 'buhc' => 'BuildUHC',
			'debuff' => 'Debuff',
			'sharp4prot3', 's4p3' => 'Sharp4Prot3',
			'sumo' => 'Sumo',
			'axe' => 'Axe',
			'spleef' => 'Spleef',
			'finaluhc' => 'FinalUHC',
			'bridge' => 'Bridge',
			'mlgrush', 'mlg', 'rush' => 'MLGRush',
			default => false
		};
	}
}
?>