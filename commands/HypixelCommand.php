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
					'等级: %4$d | 标签: [%5$s]',
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
					'分数: %2$d | 硬币: %3$d| 胜场: %4$d',
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
						break;
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
			default:
				$online = isset($p['lastLogout']) && ($p['lastLogout'] < $p['lastLogin']);
				return SpelakoUtils::buildString([
					isset($args[2]) ? '未知的分类, 已跳转至默认分类.' : '',
					'%1$s 的 Hypixel 信息:',
					'等级: %2$d | 人品: %3$d',
					'首次登录: %4$s',
					'上次登录: %5$s',
					$online ? '● 此玩家当前在线, 游玩了 %6$s' : '上次退出: %6$s'
				], [
					self::getNetworkRank($p).$p['displayname'],
					self::getNetworkLevel($p['networkExp']),
					$p['karma'],
					SpelakoUtils::convertTime($p['firstLogin'], timezone_offset: self::TIMEZONE_OFFSET),
					SpelakoUtils::convertTime($p['lastLogin'], timezone_offset: self::TIMEZONE_OFFSET),
					$online ? SpelakoUtils::convertTime(time() - $p['lastLogout'] / 1000, true, 'H:i:s') : SpelakoUtils::convertTime($p['lastLogout'], timezone_offset: self::TIMEZONE_OFFSET)
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

	private static function fetchSkyblockAuction($profile) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/skyblock/auction', ['key' => self::API_KEY, 'profile' => $profile], 300);
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
		return $exp < 0 ? 1 : floor(1 + $REVERSE_PQ_PREFIX + sqrt($REVERSE_CONST + $GROWTH_DIVIDES_2 * $exp));
	}

	// Gets guild level by exp
	private static function getGuildLevel($exp) {
		$guildLevelTables = [100000, 150000, 250000, 500000, 750000, 1000000, 1250000, 1500000, 2000000, 2500000, 2500000, 2500000, 2500000, 2500000, 3000000];
		$level = 0;
		for ($i = 0; ; $i++) {
			$need = $i >= sizeof($guildLevelTables) ? $guildLevelTables[sizeof($guildLevelTables) - 1] : $guildLevelTables[$i];
			$exp -= $need;
			if ($exp < 0) return $level;
			else $level++;
		}
		return -1;
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