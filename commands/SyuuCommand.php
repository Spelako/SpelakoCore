<?php
/*
 * Copyright (C) 2020-2022 Spelako Project
 * 
 * This file is part of SpelakoCore. Permission is granted to use, modify and/or distribute this program under the terms of the GNU Affero General Public License version 3 (AGPLv3).
 * You should have received a copy of the license along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 * 
 * 本文件是 SpelakoCore 的一部分. 在 GNU 通用公共许可证第三版 (AGPLv3) 的约束下, 你有权使用, 修改, 复制和/或传播该程序.
 * 你理当随同本程序获得了此许可证的副本. 如果没有, 请查阅 <https://www.gnu.org/licenses/agpl-3.0.html>.
 * 
 */

class SyuuCommand {
	const API_BASE_URL = 'https://api.syuu.net';
	const WEB_BASE_URL = 'https://www.syuu.net';

	public function getUsage() {
		return '/syuu ...';
	}

	public function getAliases() {
		return [];
	}

	public function getDescription() {
		return '获取 SyuuNet 的玩家信息或排行榜';
	}

	public function hasCooldown() {
		return true;
	}

	public function execute(array $args) {
		switch($args[1]) {
			case 'player':
			case 'user':
				if(!isset($args[2])) return '正确用法: /syuu player <玩家>';
				$p = $this->fetchPlayerStats($args[2]);
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
				$lb = $this->fetchPracticeLeaderboards();
				if(!$lb) return '无法解析来自 SyuuNet 的数据.';
				$category = $this->getCategoryName($args[2]);
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

	private function fetchPracticeLeaderboards() {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/public/leader-boards/practice', cacheExpiration: 300);
		if($src && ($result = json_decode($src, true)['response'])) {
			return $result;
		}
		return false;
	}
	
	private function fetchPlayerStats($player) {
		if(strlen($player) > 16) return false;
		$src = SpelakoUtils::getURL(self::WEB_BASE_URL.'/user/'.$player, cacheExpiration: 300);
		if(!$src) return 'ERROR_REQUEST_FAILED';
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

	private function getCategoryName($category) {
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
