<?php
class HypixelCommand {
	const USAGE = '/hypixel <玩家> [分类] ...';
	const ALIASES = ['/hyp'];
	const DESCRIPTION = '获取指定玩家的 Hypixel 统计信息';
	const COOLDOWN = true;

	const API_KEY = Spelako::CONFIG['hypixel_api_key'];
	const API_BASE_URL = 'https://api.hypixel.net';
	const SKYBLOCK_SKILLS = ['taming', 'farming', 'mining', 'combat', 'foraging', 'fishing', 'enchanting', 'alchemy', 'carpentry', 'runecrafting'];
	const TIMEZONE_OFFSET = Spelako::CONFIG['timezone_offset'];

	public static function execute(array $args) {
		if(!isset($args[1])) return SpelakoUtils::buildString([
			'正确用法: %s',
			'"分类" 可以是下列之一:',
			'- recent, r',
			'- guild, g',
			'- bedwars, bw',
			'- skywars, sw',
			'- uhc',
			'- megawalls, mw',
			'- blitzsg, bsg, hungergames',
			'- zombies, zb',
			'- skyblock, sb',
		], [self::USAGE]);
		$p = self::fetchGeneralStats($args[1]);
		if($p == 'ERROR_REQUEST_FAILED') return '查询请求发送失败, 请稍后再试.';
		if($p == 'ERROR_PLAYER_NOT_FOUND') return '找不到此玩家, 请确认拼写无误.';
		switch($args[2]) {
			case 'guild':
			case 'g':
				$g = self::fetchGuild($p['uuid']);
				if($g == 'ERROR_REQUEST_FAILED') return '查询请求发送失败, 请稍后再试.';
				if($g == 'ERROR_GUILD_NOT_FOUND') return sprintf(
					'玩家 %s 没有加入任何公会.',
					$p['displayname']
				);
				return SpelakoUtils::buildString([
					'%1$s 的公会信息:',
					'公会名称: %2$s',
					'创立时间: %3$s',
					'等级: %4$.3f | 标签: [%5$s]',
					'成员: %6$d | 最高在线: %7$d'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$g['name'],
					SpelakoUtils::convertTime($g['created'], timezone_offset: self::TIMEZONE_OFFSET),
					self::getGuildLevel($g['exp']),
					self::getPlainString($g['tag']),
					count($g['members']),
					$g['achievements']['ONLINE_PLAYERS']
				]);
			case 'blitzsg':
			case 'bsg':
			case 'hungergames':
				return SpelakoUtils::buildString([
					'%1$s 的闪电饥饿游戏统计信息:',
					'游玩次数: %2$d | 硬币: %3$d | 开箱数: %4$d',
					'击杀: %5$d | 死亡: %6$d | K/D: %7$.3f'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$p['stats']['HungerGames']['games_played'],
					$p['stats']['HungerGames']['coins'],
					$p['stats']['HungerGames']['chests_opened'],
					$p['stats']['HungerGames']['kills'],
					$p['stats']['HungerGames']['deaths'],
					SpelakoUtils::div($p['stats']['HungerGames']['kills'], $p['stats']['HungerGames']['deaths'])
				]);
			case 'uhc':
				return SpelakoUtils::buildString([
					'%1$s 的极限生存冠军统计信息:',
					'分数: %2$d | 硬币: %3$d | 胜场: %4$d',
					'击杀: %5$d | 死亡: %6$d | K/D: %7$.3f'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$p['stats']['UHC']['score'],
					$p['stats']['UHC']['coins'],
					$p['stats']['UHC']['wins'],
					$p['stats']['UHC']['kills'],
					$p['stats']['UHC']['deaths'],
					SpelakoUtils::div($p['stats']['UHC']['kills'], $p['stats']['UHC']['deaths'])
				]);
			case 'megawalls':
			case 'mw':
				return SpelakoUtils::buildString([
					'%1$s 的超级战墙统计信息:',
					'凋零伤害: %2$d | 职业: %3$d | 硬币: %4$d',
					'击杀: %5$d | 助攻: %6$d | 死亡: %7$d | K/D: %8$.3f',
					'终杀: %9$d | 终助: %10$d | 终死: %11$d | FKDR: %12$.3f',
					'胜场: %13$d | 败场: %14$d | W/L: %15$.3f'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$p['stats']['Walls3']['wither_damage'],
					$p['stats']['Walls3']['chosen_class'],
					$p['stats']['Walls3']['coins'],
					$p['stats']['Walls3']['kills'],
					$p['stats']['Walls3']['assists'],
					$p['stats']['Walls3']['deaths'],
					SpelakoUtils::div($p['stats']['Walls3']['kills'], $p['stats']['Walls3']['deaths']),
					$p['stats']['Walls3']['final_kills'],
					$p['stats']['Walls3']['final_assists'],
					$p['stats']['Walls3']['final_deaths'],
					SpelakoUtils::div($p['stats']['Walls3']['final_kills'], $p['stats']['Walls3']['final_deaths']),
					$p['stats']['Walls3']['wins'],
					$p['stats']['Walls3']['losses'],
					SpelakoUtils::div($p['stats']['Walls3']['wins'], $p['stats']['Walls3']['losses'])
				]);
			case 'skywars':
			case 'sw':
				return SpelakoUtils::buildString([
					'%1$s 的空岛战争统计信息:',
					'等级: %2$s | 硬币: %3$d | 助攻: %4$d',
					'击杀: %5$d | 死亡: %6$d | K/D: %7$.3f',
					'胜场: %8$d | 败场: %9$d | W/L: %10$.3f',
				], [
					self::getNetworkRank($p).$p['displayname'],
					self::getPlainString($p['stats']['SkyWars']['levelFormatted']),
					$p['stats']['SkyWars']['coins'],
					$p['stats']['SkyWars']['assists'],
					$p['stats']['SkyWars']['kills'],
					$p['stats']['SkyWars']['deaths'],
					SpelakoUtils::div($p['stats']['SkyWars']['kills'], $p['stats']['SkyWars']['deaths']),
					$p['stats']['SkyWars']['wins'],
					$p['stats']['SkyWars']['losses'],
					SpelakoUtils::div($p['stats']['SkyWars']['wins'], $p['stats']['SkyWars']['losses'])
				]);
			case 'bedwars':
			case 'bw':
				return SpelakoUtils::buildString([
					'%1$s 的起床战争统计信息:',
					'等级: %2$d | 硬币: %3$d | 拆床: %4$d',
					'胜场: %5$d | 败场: %6$d | W/L: %7$.3f',
					'击杀: %8$d | 死亡: %9$d | K/D: %10$.3f',
					'终杀: %11$d | 终死: %12$d | FKDR: %13$.3f'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$p['achievements']['bedwars_level'],
					$p['stats']['Bedwars']['coins'],
					$p['stats']['Bedwars']['beds_broken_bedwars'],
					$p['stats']['Bedwars']['wins_bedwars'],
					$p['stats']['Bedwars']['losses_bedwars'],
					SpelakoUtils::div($p['stats']['Bedwars']['wins_bedwars'], $p['stats']['Bedwars']['losses_bedwars']),
					$p['stats']['Bedwars']['kills_bedwars'],
					$p['stats']['Bedwars']['deaths_bedwars'],
					SpelakoUtils::div($p['stats']['Bedwars']['kills_bedwars'], $p['stats']['Bedwars']['deaths_bedwars']),
					$p['stats']['Bedwars']['final_kills_bedwars'],
					$p['stats']['Bedwars']['final_deaths_bedwars'],
					SpelakoUtils::div($p['stats']['Bedwars']['final_kills_bedwars'], $p['stats']['Bedwars']['final_deaths_bedwars'])
				]);
			case 'zombies':
			case 'zombie':
			case 'zb':
				$map = match($args[3]) { // [keySuffix, displayName]
					'deadend', 'de' => ['_deadend', '穷途末路'],
					'badblood', 'bb' => ['_badblood', '坏血之宫'],
					'alienarcadium', 'aa' => ['_alienarcadium', '外星游乐园'],
					default => ['', '全部']
				};
				$difficulty = match($args[4]) { // [keySuffix, displayName]
					'normal', 'norm' => ['_normal', '普通'],
					'hard' => ['_hard', '困难'],
					'rip' => ['_rip', '安息'],
					default => ['', '全部']
				};
				return SpelakoUtils::buildString([
					'%1$s 的僵尸末日%2$s地图%3$s难度统计信息:',
					'生存总回合数: %4$d | 胜场: %5$d | 最佳回合: %6$d',
					'僵尸击杀数: %7$d | 复活玩家数: %8$d | 开门数: %9$d',
					'窗户修复数: %10$d | 被击倒次数: %11$d | 死亡数: %12$d',
					'此命令详细用法可在此处查看: %13$s/#help'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$map[1],
					$difficulty[1],
					$p['stats']['Arcade']['total_rounds_survived_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['wins_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['best_round_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['zombie_kills_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['players_revived_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['doors_opened_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['windows_repaired_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['times_knocked_down_zombies'.$map[0].$difficulty[0]],
					$p['stats']['Arcade']['deaths_zombies'.$map[0].$difficulty[0]],
					Spelako::INFO['link']
				]);
			case 'skyblock':
			case 'sb':
				$profiles = $p['stats']['SkyBlock']['profiles'];
				switch($args[3]) {
					case 'auctions':
					case 'auction':
					case 'au':
					case 'a':
						$profile_id = self::getSkyblockProfileID($profiles, $args[4] ? : 1);
						if(!$profile_id) return sprintf('无法找到玩家 $s 的此空岛生存存档.', $p['displayname']);
						$placeholder = array();
						$auctions = self::fetchSkyblockAuction($profile_id);
						if(!$auctions) return '无法获取玩家 %1$s 的空岛生存 %2$s 存档物品拍卖信息';
						$items = array_values(array_filter($auctions, function($current) {return !$current['claimed'];}));
						foreach($auctions as $index => $item) {
							if($index >= 5) {
								array_push($placeholder, sprintf(
									'... 等共 %d 件正在拍卖的物品.',
									count($items)
								));
								break;
							}
							array_push($placeholder, $item['bin']
								? SpelakoUtils::buildString([
									'# %1$s',
									'	最高出价: %2$s | 起拍价: %3$s',
									'	结束时间: %4$s'
								], [
									$item['item_name'],
									number_format($item['highest_bid_amount']),
									number_format($item['starting_bid']),
									SpelakoUtils::convertTime($item['end'], timezone_offset: self::TIMEZONE_OFFSET)
								])
								: SpelakoUtils::buildString([
									'# %1$s',
									'	一口价: %2$s',
									'	结束时间: %3$s'
								], [
									$item['item_name'],
									number_format($item['starting_bid']),
									SpelakoUtils::convertTime($item['end'], timezone_offset: self::TIMEZONE_OFFSET)
								])
							);
						}
						return SpelakoUtils::buildString([
							'%1$s 的空岛生存 %2$s 存档物品拍卖信息:',
							'%2$s', // Body placeholder
							], [
								self::getNetworkRank($p).$p['displayname'],
								$profiles[$profile_id]['cute_name'],
								$placeholder ? SpelakoUtils::buildString($placeholder) : '此存档没有正在拍卖的物品.'.((count($profiles) > 1) ? ' 你可以尝试查询此玩家的其他存档.' : '')
							]
						);
					case 'skills':
					case 'skill':
					case 'sk':
					case 's':
						$profile_id = self::getSkyblockProfileID($profiles, $args[4] ? : 1);
						if(!$profile_id) return sprintf('无法找到玩家 $s 的此空岛生存存档.', $p['displayname']);
						$profile = self::fetchSkyblockProfile($profile_id);
						$member = $profile['members'][$p['uuid']];
						// If possible (allowed by player), access Skyblock Profile API insdead of Player API.
						if(self::isSkyblockProfileAccessible($member)) {
							$profileAccessible = true;
							foreach(self::SKYBLOCK_SKILLS as $skill) {
								$skillLevels[$skill] = self::getSkyblockLevel($member['experience_skill_'.$skill], $skill == 'runecrafting');
							}
						}
						else {
							$profileAccessible = false;
							$skillLevels['taming'] = $p['achievements']['skyblock_domesticator'];
							$skillLevels['farming'] = $p['achievements']['skyblock_harvester'];
							$skillLevels['mining'] = $p['achievements']['skyblock_excavator'];
							$skillLevels['combat'] = $p['achievements']['skyblock_combat'];
							$skillLevels['foraging'] = $p['achievements']['skyblock_gatherer'];
							$skillLevels['fishing'] = $p['achievements']['skyblock_angler'];
							$skillLevels['enchanting'] = $p['achievements']['skyblock_augmentation'];
							$skillLevels['alchemy'] = $p['achievements']['skyblock_concoctor'];
						}
						return SpelakoUtils::buildString([
							$profileAccessible ? '%1$s 的空岛生存 %2$s 存档技能信息:' : '%1$s 的空岛生存技能信息:',
							'驯养: %3$d | 农业: %4$d',
							'挖矿: %5$d | 战斗: %6$d',
							'林业: %7$d | 钓鱼: %8$d',
							'附魔: %9$d | 酿造: %10$d',
							$profileAccessible ? '木工: %11$d | 符文合成: %12$d' : '注意: 无法访问玩家技能 API, 已显示各存档的最高等级.'
						], [
							self::getNetworkRank($p).$p['displayname'],
							$profiles[$profile_id]['cute_name'],
							$skillLevels['taming'],
							$skillLevels['farming'],
							$skillLevels['mining'],
							$skillLevels['combat'],
							$skillLevels['foraging'],
							$skillLevels['fishing'],
							$skillLevels['enchanting'],
							$skillLevels['alchemy'],
							$skillLevels['carpentry'],
							$skillLevels['runecrafting']
						]);
						break;
					default:
						$placeholder = array();
						foreach(array_keys($profiles) as $k => $v) {
							array_push($placeholder, sprintf(
								'%1$d. %2$s',
								($k + 1),
								$profiles[$v]['cute_name']
							));
						}
						return SpelakoUtils::buildString([
							'%1$s 的 %2$d 个空岛生存存档 (序号 - 存档名):',
							'%3$s',
							'欲查询其空岛生存信息, 请使用此命令:',
							'/hypixel %4$s sb <分类> [存档名/序号]',
							'"分类" 可以是下列之一:',
							'- skills, skill, sk, s',
							'- auctions, auction, au, a'
						], [
							self::getNetworkRank($p).$p['displayname'],
							count($profiles),
							SpelakoUtils::buildString($placeholder),
							$p['displayname']
						]);
				}
			case 'r':
			case 'recent':
				$r = self::fetchRecentGames($p['uuid']);
				if ($r == 'ERROR_REQUEST_FAILED') return '查询请求发送失败, 请稍后再试.';
				if ($r == 'ERROR_RECENT_GAMES_NOT_FOUND') return sprintf('玩家 %s 没有最近的游戏, 或在 API 设置中禁止了此请求.', $p['displayname']);
				$placeholder = array();
				$total = count($r);
				for($i = 0; $i < ($total < 10 ? $total : 10); $i ++) {
					array_push($placeholder, SpelakoUtils::buildString([
						'# %1$s%2$s%3$s',
						'	开始时间: %4$s',
						$r[$i]['ended'] ? '	结束时间: %5$s' : '	● 正在游戏中...'
					], [
						self::getGameName($r[$i]['gameType']),
						self::getModeName($r[$i]['mode']),
						($statusMap = self::getMapName($r[$i]['map'])) != '' ? $statusMap.'地图' : '',
						SpelakoUtils::convertTime($r[$i]['date'], timezone_offset: self::TIMEZONE_OFFSET),
						$r[$i]['ended'] ? SpelakoUtils::convertTime($r[$i]['ended'], timezone_offset: self::TIMEZONE_OFFSET) : ''
					]));
				}
				return SpelakoUtils::buildString([
					'%1$s 的最近游玩的游戏:',
					'%2$s'
				], [
					self::getNetworkRank($p).$p['displayname'],
					SpelakoUtils::buildString($placeholder)
				]);
			default:
				$online = isset($p['lastLogout']) && ($p['lastLogout'] < $p['lastLogin']);
				$s = $online ? self::fetchStatus($p['uuid']) : false;
				$statusAvailable = ($s && $s['online'] == true);
				return SpelakoUtils::buildString([
					isset($args[2]) ? '未知的分类, 已跳转至默认分类.' : '',
					'%1$s 的 Hypixel 信息:',
					'等级: %2$.3f | 人品: %3$d',
					'成就点数: %4$d',
					'最近常玩: %5$s',
					'首次登录: %6$s',
					'上次登录: %7$s',
					$online ? '● 此玩家在线了 %8$s, '.($s ? ($statusAvailable ? '当前在%9$s%10$s%11$s中.' : '该玩家在 API 设置中阻止了获取当前游戏的请求. ' ) : '获取当前游戏时出错.') : '上次退出: %8$s'
				], [
					self::getNetworkRank($p).$p['displayname'],
					self::getNetworkLevel($p['networkExp']),
					$p['karma'],
					$p['achievementPoints'],
					self::getGameName($p['mostRecentGameType']),
					SpelakoUtils::convertTime($p['firstLogin'], timezone_offset: self::TIMEZONE_OFFSET),
					SpelakoUtils::convertTime($p['lastLogin'], timezone_offset: self::TIMEZONE_OFFSET),
					$online ? SpelakoUtils::convertTime(time() - $p['lastLogin'] / 1000, true, 'H:i:s') : SpelakoUtils::convertTime($p['lastLogout'], timezone_offset: self::TIMEZONE_OFFSET),
					$statusAvailable ? self::getGameName($s['gameType']) : '',
					$statusAvailable ? self::getModeName($s['mode']) : '',
					$statusAvailable ? (($statusMap = self::getMapName($s['map'])) != '' ? $statusMap.'地图' : '') : '',
				]);
		}
	}

	private static function fetchGeneralStats($player) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/player', ['key' => self::API_KEY, 'name' => $player], 300);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		if(($result = json_decode($src, true)['player']) == null) return 'ERROR_PLAYER_NOT_FOUND';
		return $result;
	}

	private static function fetchGuild($playerUuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/guild', ['key' => self::API_KEY, 'player' => $playerUuid], 300);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		if(($result = json_decode($src, true)['guild']) == null) return 'ERROR_GUILD_NOT_FOUND';
		return $result;
	}
	
	private static function fetchRecentGames($playerUuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/recentgames', ['key' => self::API_KEY, 'uuid' => $playerUuid], 10);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		if(($result = json_decode($src, true)['games']) == null) return 'ERROR_RECENT_GAMES_NOT_FOUND';
		return $result;
	}

	private static function fetchStatus($playerUuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/status', ['key' => self::API_KEY, 'uuid' => $playerUuid], 10);
		if($src) {
			return json_decode($src, true)['session'];
		}
		return false;
	}
	
	private static function fetchSkyblockAuction($profile) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/skyblock/auction', ['key' => self::API_KEY, 'profile' => $profile], 10);
		if($src && (($result = json_decode($src, true)['auctions']) != null)) {
			return $result;
		}
		return false;
	}

	private static function fetchSkyblockProfile($profile) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/skyblock/profile', ['key' => self::API_KEY, 'profile' => $profile], 300);
		if($src && (($result = json_decode($src, true)['profile'])) != null) {
			return $result;
		}
		return false;
	}

	// Searches player.stats.Skyblock.profile[] for Skyblock Profile ID that matches the query provided as index (int) or name (string)
	private static function getSkyblockProfileID($profiles, $query) {
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

	// Gets Skyblock skill level by exp
	private static function getSkyblockLevel($exp, $runecrafting = false) {
		if($runecrafting) {
			$levelingLadder = array(0, 50, 150, 275, 435, 635, 885, 1200, 1600, 2100, 2725, 3510, 4510, 5760, 7325, 9325, 11825, 14950, 18950, 23950, 30200, 38050, 47850, 60100, 75400, 94450);
		}
		else {
			$levelingLadder = array(0, 50, 175, 375, 675, 1175, 1925, 2925, 4425, 6425, 9925, 14925, 22425, 32425, 47425, 67425, 97425, 147425, 222425, 322425, 522425, 822425, 1222425, 1722425, 2322425, 3022425, 3822425, 4722425, 5722425, 6822425, 8022425, 9322425, 10722425, 12222425, 13822425, 15522425, 17322425, 19222425, 21222425, 23322425, 25522425, 27822425, 30222425, 32722425, 35322425, 38072425, 40972425, 44072425, 47472425, 51172425, 55172425);
		}
		foreach($levelingLadder as $lv => $required) {
			if($exp < $required) return ($lv - 1);
		}
	}

	// Searches player[] for possible rank
	private static function getNetworkRank($player) {
		if(isset($player['rank']) && $player['rank'] != 'NONE' && $player['rank'] != 'NORMAL') {
			return ('['.$player['rank'].'] ');
		}
		if(isset($player['monthlyPackageRank']) && $player['monthlyPackageRank'] != 'NONE') {
			return ('[MVP++] ');
		}
		if(isset($player['newPackageRank']) && $player['newPackageRank'] != 'NONE') {
			return ('['.str_replace('_PLUS', '+', $player['newPackageRank']).'] ');
		}
	}

	// Gets network level by exp
	private static function getNetworkLevel($exp) {
		$REVERSE_PQ_PREFIX = -3.5;
		$REVERSE_CONST = 12.25;
		$GROWTH_DIVIDES_2 = 0.0008;
		return $exp < 0 ? 1 : 1 + $REVERSE_PQ_PREFIX + sqrt($REVERSE_CONST + $GROWTH_DIVIDES_2 * $exp);
	}

	// Gets guild level by exp
	private static function getGuildLevel($exp) {
		$guildLevelTables = [100000, 150000, 250000, 500000, 750000, 1000000, 1250000, 1500000, 2000000, 2500000, 2500000, 2500000, 2500000, 2500000, 3000000];
		$level = 0;
		for ($i = 0; ; $i++) {
			$need = $i >= sizeof($guildLevelTables) ? $guildLevelTables[sizeof($guildLevelTables) - 1] : $guildLevelTables[$i];
			$exp -= $need;
			if ($exp < 0) return $level + 1 + $exp/$need;
			else $level++;
		}
		return -1;
	}


	private static function getGameName($typeName) {
		return match($typeName) {
			'QUAKECRAFT' => '未来射击',
			'WALLS' => '战墙',
			'PAINTBALL' => '彩弹射击',
			'SURVIVAL_GAMES' => '闪电饥饿游戏',
			'TNTGAMES' => '掘战游戏',
			'VAMPIREZ' => '吸血鬼',
			'WALLS3' => '超级战墙',
			'ARCADE' => '街机游戏',
			'ARENA' => '竞技场',
			'UHC' => '极限生存冠军',
			'MCGO' => '警匪大战',
			'BATTLEGROUND' => '战争领主',
			'SUPER_SMASH' => '星碎英雄',
			'GINGERBREAD' => '方块赛车',
			'HOUSING' => '家园世界',
			'SKYWARS' => '空岛战争',
			'TRUE_COMBAT' => '疯狂战墙',
			'SPEED_UHC' => '速战极限生存',
			'SKYCLASH' => '空岛竞技场',
			'LEGACY' => '经典游戏',
			'PROTOTYPE' => '游戏实验室',
			'BEDWARS' => '起床战争',
			'MURDER_MYSTERY' => '密室杀手',
			'BUILD_BATTLE' => '建筑大师',
			'DUELS' => '决斗游戏',
			'SKYBLOCK' => '空岛生存',
			'PIT' => '天坑乱斗',
			'REPLAY' => '回放系统',
			'SMP' => 'Hypixel SMP',
			default => $typeName
		};
		
	}
	
	private static function getMapName($mapName) {
		return match($mapName) {
			//Arcade Start
			'Dead End' => '穷途末路',
			'Bad Blood' => '坏血之宫',
			'Alien Arcadium' => '外星游乐园',
			//ARCADE End
			
			// Bedwars Start
			'Amazon' => '亚马逊雨林',
			'Waterfall' => '瀑布',
			'Airshow' => '热气球展览会',
			'Aquarium' => '水族馆',
			'Archway' => '拱形廊道',
			'Ashore' => '海滩',
			'Boletum' => '蘑菇岛',
			'Chained' => '铁索连环',
			'Crypt' => '地窖',
			'Eastwood' => '东方客栈',
			'Glacier' => '冰天雪地',
			'Hollow' => '中空岛屿',
			'Invasion' => '全盘入侵',
			'Lectus' => '罗马竞技场',
			'Lighthouse' => '灯塔',
			'Lotus' => '莲花岛',
			'Pernicious' => '不毛之地',
			'Playground' => '游乐场',
			'Rooftop' => '屋顶',
			'Speedway' => '赛道',
			'Stonekeep' => '石砖要塞',
			'Swashbuckle' => '海盗船',
			'Treenan' => '参天松树',
			'Ivory Castle' => '象牙城池',
			// Bedwars End
			
			// Skywars Start
			'Nomad' => '流浪之地',
			'Memorial' => '纪念碑岛',
			'Palette' => '调色世界',
			'Winterhelm' => '冰盔岛',
			'Villa' => '别墅之岛',
			'Chronos' => '车轮之环',
			'Mothership' => '航空母舰',
			'Oasis' => '绿洲之岛',
			'Onionring' => '洋葱圈岛',
			'Aegis' => '盾之神殿',
			'Agni Temple' => '阿格尼神庙',
			'Anchored' => '水底之境',
			'Aquarius' => '水瓶座圣殿',
			'Aqueduct' => '运河大桥',
			'Arx Citadel' => '阿克斯城堡',
			'Clearing' => '荒野木屋',
			'Coherence' => '林间',
			'Crumble' => '崩溃之地',
			'Desserted Islands' => '甜点之岛',
			'Dwarf Fortress' => '矮人堡垒',
			'Dwarven' => '十字交叉',
			'Dynasty' => '落樱亭台',
			'Eden' => '伊甸园',
			'Eirene' => '穹顶',
			'Elven' => '巨城',
			'Elven Towers' => '精灵巨城',
			'Entangled' => '金矿遗迹',
			'Equinox' => '四季秘境',
			'Firelink Shrine' => '遗迹之城',
			'Frostbite' => '冰封之岛',
			'Fortress' => '要塞',
			'Fossil' => '树海秘境',
			'Foundation' => '建筑工地',
			'Fungi' => '蘑菇岛',
			'Hontori' => '樱扬桥亭',
			'Humidity' => '荒漠',
			'Jinzhou' => '樱花岛屿',
			'LongIsland' => '狭长岛屿',
			'Maereen' => '金字塔',
			'Magnolite' => '熔岩巨山',
			'Marooned' => '放逐之地',
			'Martian' => '火星基地',
			'MegaShire' => '希雷',
			'Meteor' => '流星',
			'Onionring 2' => '洋葱环 v2',
			'Onset' => '角斗场',
			'Overfall' => '高地之湖',
			'Pitfall' => '中空岛屿',
			'Plateau' => '高原',
			'Railroad' => '铁路',
			'Reef' => '暗礁之地',
			'Rocky' => '乱石崚峋',
			'Sanctuary' => '圣所',
			'Sanctum' => '圣地',
			'Sandbox' => '沙盒',
			'Sawmill' => '锯木厂',
			'Siege' => '浮空城',
			'Sentinel' => '前哨基地',
			'Shire' => '营地',
			'Shrooms' => '蘑菇之境',
			'Skychurch' => '天空教堂',
			'Strata' => '升空之岛',
			'Submerged' => '深海秘境',
			'Templar' => '圣堂',
			'Towers' => '塔楼基地',
			'Tribal' => '林间树屋',
			'Tribute' => '决战之岩',
			'Tundra' => '苔原',
			'Frost Bound' => '冰霜巨城',
			'Twisted Grove' => '环合密林',
			//Skywanrs End
			null => '',
			default => ' '.$mapName.' '
		};
	}
	
	private static function getModeName($modeName) {
		return match($modeName) {
			'LOBBY' => '大厅',

			// 有遗漏的欢迎补充
			// 翻译和整理模式名翻译得累死我了
			// 模式名参见 https://hypixel.net/threads/guide-play-commands-useful-tools-mods-more-updated-12-25-20.1025608/
			// Warlords Start
			'ctf_mini' => '夺旗模式',
			'domination' => '抢点战模式',
			'team_deathmatch' => '团队死亡竞赛模式',
			// Warlords End

			// Mega Walls Start
			'standard' => '标准模式',
			'face_off' => '对决模式',
			// Mega Walls End

			// Blitz Survival Games / Speed UHC / Smash Heroes Start
			//'solo_normal' => '单挑模式',
			//'teams_normal' => '团队模式',
			'2v2_normal' => ' 2v2 模式',
			//'teams_normal' => ' 2v2v2 模式',
			'1v1_normal' => ' 1v1 模式',
			'friends_normal' => ' Friends 1v1v1v1 模式',
			// Blitz Survival Games / Speed UHC / Smash Heroes End

			// Skyblock Start
			'dynamic' => '私人岛屿',
			'hub' => '主岛屿',
			'farming_1' => '农场岛屿',
			'combat_1' => '蜘蛛巢穴',
			'combat_2' => '烈焰堡垒',
			'combat_3' => '末地',
			'mining_1' => '黄金矿区',
			'mining_2' => '深层矿洞',
			'mining_3' => ' Dwarven Mine ',
			'mining_4' => ' Crystal Hollows ',
			'foraging_1' => '公园',
			'dungeon_hub' => '地牢大厅',
			// Skyblock End

			// Skywars Start
			'ranked_normal' => '排位模式',
			'solo_normal' => '单挑普通模式',
			'solo_insane' => '单挑疯狂模式',
			'teams_normal' => '双人普通模式',
			'teams_insane' => '双人疯狂模式',
			'mega_normal' => ' mega normal ',
			'mega_doubles' => ' mega doubles ',
			'solo_insane_tnt_madness' => '单挑疯狂 TNT 模式',
			'teams_insane_tnt_madness' => '双人疯狂 TNT 模式',
			'solo rush' => '单挑疾速模式',
			'teams_insane_rush' => '双人疾速模式',
			'solo_insane_slime' => '单挑史莱姆模式',
			'teams_insane_slime' => '双人史莱姆模式',
			'solo_insane_lucky' => '单挑幸运方块模式',
			'teams_insane_lucky' => '双人幸运方块模式',
			'solo_insane_hunters_vs_beasts' => '狩猎对决模式',
			// Skywars End

			//TNT Games Start
			'TNTRUN' => '方块掘战',
			'PVPRUN' => 'PVP 方块掘战',
			'BOWSPLEEF' => '掘一死箭',
			'TNTAG' => '烫手 TNT ',
			'CAPTURE' => '法师掘战',
			//TNT Games End

			// Bedwars Start
			'BEDWARS_EIGHT_ONE' => '单挑模式',
			'BEDWARS_EIGHT_TWO' => '双人模式',
			'BEDWARS_FOUR_THREE' => ' 3v3v3v3 模式',
			'BEDWARS_FOUR_FOUR' => ' 4v4v4v4 模式',
			'BEDWARS_CAPTURE' => '据点模式',	// E 这模式已经没了，不过还是写进来吧；据点模式是官方翻译，其实我更赞同夺床模式 XD
			'BEDWARS_EIGHT_TWO_RUSH' => '双人疾速模式',
			'BEDWARS_FOUR_FOUR_RUSH' => ' 4v4v4v4 疾速模式',
			'BEDWARS_EIGHT_TWO_ULTIMATE' => '双人超能力模式',
			'BEDWARS_FOUR_FOUR_ULTIMATE' => ' 4v4v4v4 超能力模式',
			'BEDWARS_CASTLE' => '40V40城池攻防战模式',
			'BEDWARS_TWO_FOUR' => '4V4 模式',
			'BEDWARS_EIGHT_TWO_VOIDLESS' => '双人无虚空模式',
			'BEDWARS_FOUR_FOUR_VOIDLESS' => ' 4v4v4v4 无虚空模式',
			'BEDWARS_EIGHT_TWO_ARMED' => '双人枪械模式',
			'BEDWARS_FOUR_FOUR_ARMED' => ' 4v4v4v4 枪械模式',
			'BEDWARS_EIGHT TWO_LUCKY' => '双人幸运方块模式',
			'BEDWARS_FOUR_FOUR_LUCKY' => ' 4v4v4v4 幸运方块模式',
			'BEDWARS_PRACTICE' => '练习模式',
			// Bedwars End

			// Arcade Start
			'HOLE_IN_THE_WALL' => '人体打印机',
			'SOCCER' => '足球',
			'BOUNTY_HUNTERS' => '赏金猎人',
			'PIXEL_PAINTERS' => '像素画师',
			'DRAGON_WARS' => '龙之战',
			'ENDER_SPLEEF' => '末影掘战',
			'STARWARS' => '星际战争',
			'THROW_OUT' => '乱棍之战',
			'CREEPER_ATTACK' => '进击的苦力怕',
			'PARTY_GAMES_1' => '派对游戏',
			'FARM_HUNT' => '农场躲猫猫',
			'ZOMBIES_DEAD_END' , 'ZOMBIES_BAD_BLOOD', 'ZOMBIES_ALIEN_ARCADIUM' => '僵尸末日',
			'HIDE_AND_SEEK_PROP_HUNT' => '道具躲猫猫',
			'HIDE_AND_SEEK_PARTY_POOPER' => '派对躲猫猫',
			'SIMON_SAYS' => '我说你做',
			'SANTA_SAYS' => '圣诞老人说你做',
			'MINI_WALLS' => '迷你战墙',
			'DAYONE' => '行尸走肉',
			'PVP_CTW' => '捕捉羊毛大作战',
			// Arcade End

			// Cops and Crims Start
			'normal' => '爆破模式',
			'deatmatch' => '团队死亡竞赛模式',
			'NORMAL_PARTY' => '挑战模式 - 爆破模式',
			'DEATHMATCH_PARTY' => '挑战模式 - 团队死亡竞赛模式',
			// Cops and Crims End

			// Build Battle Start
			'BUILD_BATTLE_SOLO_NORMAL' => '单人模式',
			'BUILD_BATTLE_TEAMS_NORMAL' => '团队模式',
			'BUILD_BATTLE_SOLO_PRO' => '高手模式',
			'BUILD_BATTLE_GUESS_THE_BUILD' => '建筑猜猜乐',
			// Build battle End

			// UHC start
			'SOLO' => '单挑模式',
			'TEAMS' => '组队模式',
			'EVENTS' => '活动模式',
			// UHC End

			// Duels start
			'DUELS_CLASSIC_DUEL' => '经典决斗',
			'DUELS_SW_DUEL' => '空岛战争决斗',
			'DUELS_SW_DOUBLES' => '空岛战争决斗双人决斗',
			'DUELS_BOW_DUEL' => '弓箭决斗',
			'DUELS_UHC_DUEL' => '极限生存决斗',
			'DUELS_UHC_DOUBLES' => '极限生存冠军双人决斗',
			'DUELS_UHC_FOUR' => '极限生存冠军四人决斗',
			'DUELS_UHC_MEETUP' => '极限生存冠军死亡竞赛决斗',
			'DUELS_POTION_DUEL' => '药水决斗',
			'DUELS_COMBO_DUEL' => '连击决斗',
			'DUELS_OP_DUEL' => '高手决斗',
			'DUELS_OP_DOUBLES' => '高手双人决斗',
			'DUELS_MW_DUEL' => '超级战墙决斗',
			'DUELS_MW_DOUBLES' => '超级战墙双人决斗',
			'DUELS_SUMO_DUEL' => '相扑决斗',
			'DUELS_BLITZ_DUEL' => '商店街游戏决斗',
			'DUELS_BOWSPLEEF_DUEL' => '掘一死箭决斗',
			'DUELS_BRIDGE_DUEL' => '战桥决斗',
			'DUELS_BRIDGE_DOUBLES' => '战桥双人决斗',
			'DUELS_BRIDGE_FOUR' => '战桥四人决斗',
			'DUELS_BRIDGE_2V2V2V2' => '战桥 2v2v2v2 决斗',
			'DUELS_BRIDGE_3V3V3V3' => '战桥 4v4v4v4 决斗',
			// Duels end

			// Murder Mystery Start
			'MURDER_CLASSIC' => '经典模式',
			'MURDER_DOUBLE_UP' => '双倍模式',
			'MURDER_ASSASSINS' => '刺客模式',
			'MURDER_INFECTION' => '感染模式',
			// Murder Mystery End

			// TowerWars Start
			'TOWERWARS_SOLO' => '单挑模式',
			'TOWERWARS_TEAM_OF_TWO' => '双人模式',
			// TowerWars End

			null => '',
			default => ' '.$modeName.' '
		};
	}
	
	// Clears the Minecraft formatting string of a file
	private static function getPlainString($formattedStr) {
		$plainStr = str_replace(
			array('§0', '§1', '§2', '§3', '§4', '§5', '§6', '§7', '§8', '§9', '§a', '§b', '§c', '§d', '§e', '§f', '§k', '§l', '§m', '§n', '§o', '§r')
		, '', $formattedStr);
		return $plainStr;
	}
	
	
	// Gets the accessibility of the Skyblock Profile API of player
	private static function isSkyblockProfileAccessible($profile) {
		foreach(self::SKYBLOCK_SKILLS as $skill) {
			if(isset($profile['experience_skill_'.$skill])) return true;
		}
		return false;
	}
}
?>