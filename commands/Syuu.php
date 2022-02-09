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

class Syuu {
	const API_BASE_URL = 'https://api.syuu.net';
	const WEB_BASE_URL = 'https://www.syuu.net';

	function __construct(private SpelakoCore $core, private $config) {
		$core->loadJsonResource($this->config->resource);
	}

	public function getName() {
		return ['/syuu'];
	}

	public function getUsage() {
		return SpelakoUtils::buildString($this->core->getJsonValue($this->config->resource, 'usage'));
	}

	public function getDescription() {
		return $this->core->getJsonValue($this->config->resource, 'description');
	}

	public function hasCooldown() {
		return true;
	}

	private function getMessage($key) {
		return $this->core->getJsonValue($this->config->resource, 'messages.'.$key);
	}

	public function execute(array $args) {
		if(empty($args[1])) return $this->getMessage('default.layout');
		switch($args[1]) {
			case 'player':
			case 'user':
				if(empty($args[2])) return $this->getMessage('user.info.usage');
				$p = $this->fetchPlayerStats($args[2]);
				if($p == 'ERROR_REQUEST_FAILED') return $this->getMessage('user.info.request_failed');
				if($p == 'ERROR_RANKED_DATA_NOT_FOUND') return $this->getMessage('user.info.ranked_data_not_found');
				$placeholder = array();
				foreach($p['RankedData'] as $k => $v) array_push($placeholder, SpelakoUtils::buildString(
					$this->getMessage('user.placeholder'),
					[
						$k,
						$v['Elo'],
						$v['Win'],
						$v['Lose']
					]
				));
				return SpelakoUtils::buildString(
					$this->getMessage('user.layout'),
					[
						$p['name'],
						SpelakoUtils::buildString($placeholder),
						$this->core::WEBSITE
					]
				);
			case 'leaderboards':
			case 'leaderboard':
			case 'lb':
				if(empty($args[2]) || !($category = $this->getCategoryName($args[2]))) return SpelakoUtils::buildString($this->getMessage('leaderboard.info.usage'));
				if(!($lb = $this->fetchPracticeLeaderboards())) return $this->getMessage('leaderboard.info.failed_to_parse');
				$placeholder = array();
				foreach($lb[$category] as $k => $v) {
					array_push($placeholder, SpelakoUtils::buildString(
						$this->getMessage('leaderboard.placeholder'),
						[
							$k + 1,
							$v['lastknownname'],
							$v['rankedelo']
						]
					));
				}
				return SpelakoUtils::buildString(
					$this->getMessage('leaderboard.layout'),
					[
						$category,
						SpelakoUtils::buildString($placeholder),
						$this->core::WEBSITE
					]
				);
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
