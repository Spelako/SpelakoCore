<?php
/*
 * Copyright (C) 2020-2022 Spelako Project
 * 
 * This file is part of SpelakoCore.
 * Permission is granted to use, modify and/or distribute this program 
 * under the terms of the GNU Affero General Public License version 3.
 * You should have received a copy of the license along with this program.
 * If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 * 
 * 此文件是 SpelakoCore 的一部分.
 * 在 GNU Affero 通用公共许可证第三版的约束下,
 * 你有权使用, 修改, 复制和/或传播该软件.
 * 你理当随同本程序获得了此许可证的副本.
 * 如果没有, 请查阅 <https://www.gnu.org/licenses/agpl-3.0.html>.
 * 
 */

class Hypixel {
	const API_BASE_URL = 'https://api.hypixel.net';

	const SKYBLOCK_SKILLS = ['taming', 'farming', 'mining', 'combat', 'foraging', 'fishing', 'enchanting', 'alchemy', 'carpentry', 'runecrafting'];
	const PARKOUR_LOBBY_CODE = ['mainLobby2017', 'Bedwars', 'Skywars', 'SkywarsAug2017', 'ArcadeGames', 'MurderMystery', 'TNT', 'uhc', 'SpeedUHC', 'Prototype', 'BuildBattle', 'Housing', 'TruePVPParkour', 'MegaWalls', 'BlitzLobby', 'Warlords', 'SuperSmash', 'CopsnCrims', 'Duels', 'Legacy', 'SkyClash', 'Tourney'];
	const PARKOUR_LOBBY_NAME = ['主大厅 2017', '起床战争', '空岛战争', '空岛战争 2017.8', '街机游戏', '密室杀手', '掘战游戏', '极限生存冠军', '速战极限生存', '游戏实验室', '建筑大师', '家园世界', 'True PVP Parkour', '超级战墙', '闪电饥饿游戏' ,'战争领主', '星碎英雄', '警匪大战' ,'决斗游戏', '经典游戏', '空岛竞技场', '竞赛殿堂'];
	const PARKOUR_LOBBY_CHECKPOINT = [2, 3, -1, 3, 6, 3, 3, 2, 1, 4, 3, 7, -1, 3, 0, 2, -1, 4, 3, 2, -1, 3];

	function __construct(private SpelakoCore $core, private $config) {
		$core->loadJsonResource($config->resource);
	}

	public function getName() {
		return ['/hypixel', '/hyp'];
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
		if(empty($args[1])) return $this->getUsage();

		$status = '';
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/player', ['key' => $this->config->api_key, 'name' => $args[1]], 300, $status);

		if(str_contains($status, '429')) return $this->getMessage('info.rate_limit_reached');
		if(!$src) return $this->getMessage('info.request_failed');
		$result = json_decode($src, true);
		if($result['success'] != true) return $this->getMessage('info.incomplete_json');
		if($result['player'] == null) return $this->getMessage('info.player_not_found');
		$p = $result['player'];
		// TO FIX: see whether an "unset($result)" is necessary

		if(isset($p['rank']) && $p['rank'] != 'NONE' && $p['rank'] != 'NORMAL') $rank = '['.$p['rank'].'] ';
		else if(isset($p['monthlyPackageRank']) && $p['monthlyPackageRank'] != 'NONE') $rank = '[MVP++] ';
		else if(isset($p['newPackageRank']) && $p['newPackageRank'] != 'NONE') $rank = '['.str_replace('_PLUS', '+', $p['newPackageRank']).'] ';
		else $rank = '';

		$footer = SpelakoUtils::buildString(
			$this->getMessage('info.learn_more'),
			[
				$this->core::WEBSITE
			]
		);

		switch(isset($args[2]) ? $args[2] : 'general') {
			case 'guild':
			case 'g':
				$src = SpelakoUtils::getURL(self::API_BASE_URL.'/guild', ['key' => $this->config->api_key, 'player' => $p['uuid']], 300);
				if(!$src) return $this->getMessage('info.request_failed');
				$result = json_decode($src, true);
				if($result['success'] != true) return $this->getMessage('info.incomplete_json');
				if($result['guild'] == null) return SpelakoUtils::buildString($this->getMessage('info.guild.guild_not_found'), [$p['displayname']]);
				$g = $result['guild'];

				$guildLevelTables = [100000, 150000, 250000, 500000, 750000, 1000000, 1250000, 1500000, 2000000, 2500000, 2500000, 2500000, 2500000, 2500000, 3000000];
				$level = 0;
				do {
					if($level >= 15) break;
					else $need = $guildLevelTables[$level];
					$g['exp'] -= $need;
					$level ++;
				} while($g['exp'] >= 0);
				$level += $g['exp'] / $need;

				$prefGames = $this->getMessage('guild.placeholders.none');
				if($g['preferredGames']) {
					foreach($g['preferredGames'] as $k => $game) $g['preferredGames'][$k] = $this->getMessage('games.'.$game) ?? (' '.$game.' ');
					$prefGames = implode(', ', $g['preferredGames']);
				}

				return SpelakoUtils::buildString(
					$this->getMessage('guild.layout'),
					[
						$rank.$p['displayname'],
						$g['name'],
						SpelakoUtils::formatTime($g['created'], offset: $this->config->timezone_offset),
						$level,
						$this->getPlainString($g['tag']),
						count($g['members']),
						$g['achievements']['ONLINE_PLAYERS'],
						$prefGames
					]
				);
			case 'blitzsg':
			case 'bsg':
			case 'hungergames':
				return SpelakoUtils::buildString(
					$this->getMessage('bsg.layout'),
					[
						$rank.$p['displayname'],
						number_format($p['stats']['HungerGames']['games_played']),
						number_format($p['stats']['HungerGames']['coins']),
						number_format($p['stats']['HungerGames']['chests_opened']),
						number_format($p['stats']['HungerGames']['kills']),
						number_format($p['stats']['HungerGames']['deaths']),
						SpelakoUtils::div($p['stats']['HungerGames']['kills'], $p['stats']['HungerGames']['deaths'])
					]
				);
			case 'duels':
			case 'duel':
				if(empty($args[3])) {
					$keyAsPrefix = $keyAsSuffix = '';
					$modeName = $this->getMessage('modes.all');
				}
				else if(
					$modeName = $this->getMessage('modes.duels_'.$args[3])
					|| $modeName = $this->getMessage('modes.duels_'.($args[3] .= '_duel'))
				) {
					$keyAsPrefix = $args[3].'_';
					$keyAsSuffix = '_'.$args[3];
				}
				else return SpelakoUtils::buildString($this->getMessage('duels.info.usage'), [$p['displayname']]);

				return SpelakoUtils::buildString(
					$this->getMessage('duels.layout'),
					[
						$rank.$p['displayname'],
						isset($args[3]) ? $this->getMessage('modes.duels'.$keyAsSuffix) : $this->getMessage('modes.all'),
						isset($args[3]) ? '' : SpelakoUtils::buildString(
							$this->getMessage('duels.placeholders.general_stats'),
							[
								number_format($p['stats']['Duels']['coins']),
								number_format($p['stats']['Duels']['pingPreference']),
								number_format($p['stats']['Duels'][$keyAsPrefix.'games_played_duels'] - $p['stats']['Duels'][$keyAsPrefix.'wins'] - $p['stats']['Duels'][$keyAsPrefix.'losses']),
							]
						),
						$p['stats']['Duels'][$keyAsPrefix.'rounds_played'],
						number_format($p['stats']['Duels'][$keyAsPrefix.'wins']),
						number_format($p['stats']['Duels'][$keyAsPrefix.'losses']),
						SpelakoUtils::div($p['stats']['Duels'][$keyAsPrefix.'wins'], $p['stats']['Duels'][$keyAsPrefix.'losses']),
						$p['stats']['Duels']['best_overall_winstreak'] === null && $p['stats']['Duels'][$keyAsPrefix.'wins'] != 0 ? $this->getMessage('duels.placeholders.win_strikes_no_access') : SpelakoUtils::buildString(
							$this->getMessage('duels.placeholders.win_strikes'),
							[
								number_format(isset($args[3]) ? $p['stats']['Duels']['current_winstreak_mode'.$keyAsSuffix] : $p['stats']['Duels']['current_winstreak']),
								number_format(isset($args[3]) ? $p['stats']['Duels']['best_winstreak_mode'.$keyAsSuffix] : $p['stats']['Duels']['best_overall_winstreak'])
							]
						),
						number_format($p['stats']['Duels'][$keyAsPrefix.(str_contains($keyAsPrefix, 'bridge') ? 'bridge_kills' : 'kills')]), // Hypixel API is messy
						number_format($p['stats']['Duels'][$keyAsPrefix.(str_contains($keyAsPrefix, 'bridge') ? 'bridge_deaths' : 'deaths')]),
						SpelakoUtils::div($p['stats']['Duels'][$keyAsPrefix.(str_contains($keyAsPrefix, 'bridge') ? 'bridge_kills' : 'kills')], $p['stats']['Duels'][$keyAsPrefix.(str_contains($keyAsPrefix, 'bridge') ? 'bridge_deaths' : 'deaths')]),
						(str_contains($keyAsPrefix, 'sumo') || str_contains($keyAsPrefix, 'classics') || str_contains($keyAsPrefix, 'potion')) ? '' : SpelakoUtils::buildString(
							$this->getMessage('duels.placeholders.bow_stats'),
							[
								number_format($p['stats']['Duels'][$keyAsPrefix.'bow_shots']),
								number_format($p['stats']['Duels'][$keyAsPrefix.'bow_hits']),
								100 * SpelakoUtils::div($p['stats']['Duels'][$keyAsPrefix.'bow_hits'], $p['stats']['Duels'][$keyAsPrefix.'bow_hits'] + $p['stats']['Duels'][$keyAsPrefix.'bow_shots']),
							]
						),
						number_format($p['stats']['Duels'][$keyAsPrefix.'melee_swings']),
						number_format($p['stats']['Duels'][$keyAsPrefix.'melee_hits']),
						100 * SpelakoUtils::div($p['stats']['Duels'][$keyAsPrefix.'melee_hits'], $p['stats']['Duels'][$keyAsPrefix.'melee_hits'] + $p['stats']['Duels'][$keyAsPrefix.'melee_swings']),
						(isset($args[3]) || !str_contains($keyAsPrefix, 'uhc') || !str_contains($keyAsPrefix, 'combo')) ? '' : SpelakoUtils::buildString(
							$this->getMessage('duels.placeholders.gapples_eaten'),
							[
								number_format($p['stats']['Duels'][$keyAsPrefix.'golden_apples_eaten']),
							]
						),
						number_format($p['stats']['Duels'][$keyAsPrefix.'health_regenerated']),
						number_format($p['stats']['Duels'][$keyAsPrefix.'damage_dealt']),
						(isset($args[3]) || !str_contains($keyAsPrefix, 'bridge') || !str_contains($keyAsPrefix, 'uhc') || !str_contains($keyAsPrefix, 'mw')) ? '' : SpelakoUtils::buildString(
							$this->getMessage('duels.placeholders.blocks_placed'),
							[
								number_format($p['stats']['Duels'][$keyAsPrefix.'blocks_placed']),
							]
						),
						$footer
					]
				);
			case 'uhc':
				return SpelakoUtils::buildString(
					$this->getMessage('uhc.layout'),
					[
						$rank.$p['displayname'],
						number_format($p['stats']['UHC']['score']),
						number_format($p['stats']['UHC']['coins']),
						number_format($p['stats']['UHC']['wins']),
						number_format($p['stats']['UHC']['kills']),
						number_format($p['stats']['UHC']['deaths']),
						SpelakoUtils::div($p['stats']['UHC']['kills'], $p['stats']['UHC']['deaths'])
					]
				);
			case 'megawalls':
			case 'mw':
				return SpelakoUtils::buildString(
					$this->getMessage('mw.layout'),
					[
						$rank.$p['displayname'],
						number_format($p['stats']['Walls3']['wither_damage']),
						$p['stats']['Walls3']['chosen_class'],
						number_format($p['stats']['Walls3']['coins']),
						number_format($p['stats']['Walls3']['kills']),
						number_format($p['stats']['Walls3']['assists']),
						number_format($p['stats']['Walls3']['deaths']),
						SpelakoUtils::div($p['stats']['Walls3']['kills'], $p['stats']['Walls3']['deaths']),
						number_format($p['stats']['Walls3']['final_kills']),
						number_format($p['stats']['Walls3']['final_assists']),
						number_format($p['stats']['Walls3']['final_deaths']),
						SpelakoUtils::div($p['stats']['Walls3']['final_kills'], $p['stats']['Walls3']['final_deaths']),
						number_format($p['stats']['Walls3']['wins']),
						number_format($p['stats']['Walls3']['losses']),
						SpelakoUtils::div($p['stats']['Walls3']['wins'], $p['stats']['Walls3']['losses'])
					]
				);
			case 'skywars':
			case 'sw':
				return SpelakoUtils::buildString(
					$this->getMessage('sw.layout'),
					[
						$rank.$p['displayname'],
						$this->getPlainString($p['stats']['SkyWars']['levelFormatted']) ?? 1,
						number_format($p['stats']['SkyWars']['coins']),
						number_format($p['stats']['SkyWars']['assists']),
						number_format($p['stats']['SkyWars']['kills']),
						number_format($p['stats']['SkyWars']['deaths']),
						SpelakoUtils::div($p['stats']['SkyWars']['kills'], $p['stats']['SkyWars']['deaths']),
						number_format($p['stats']['SkyWars']['wins']),
						number_format($p['stats']['SkyWars']['losses']),
						SpelakoUtils::div($p['stats']['SkyWars']['wins'], $p['stats']['SkyWars']['losses'])
					]
				);
			/*
			// 不想写了 想睡觉
			case 'buildbattle':	
			case 'bb':
				return SpelakoUtils::buildString(
					$this->getMessage('bb.layout'),
					[
						$rank.$p['displayname'],
						number_format($p['stats']['UHC']['score']),
						number_format($p['stats']['UHC']['coins']),
						number_format($p['stats']['UHC']['wins']),
						number_format($p['stats']['UHC']['kills']),
						number_format($p['stats']['UHC']['deaths']),
						SpelakoUtils::div($p['stats']['UHC']['kills'], $p['stats']['UHC']['deaths'])
					]
				);*/
			case 'bedwars':
			case 'bw':
				if(empty($args[3])) {
					$keyAsPrefix = '';
					$modeName = $this->getMessage('modes.all');
				}
				else if(
					($modeName = $this->getMessage('modes.bedwars_'.$args[3]))
					|| $modeName = $this->getMessage(
						'modes.bedwars_'.($args[3] = str_replace(
							['solo', 'doubles', '3v3v3v3', '4v4v4v4', '4v4', '1', '2', '3', '4', '8'],
							['eight_one', 'eight_two', 'four_three', 'four_four', 'two_four', 'one', 'two', 'three', 'four', 'eight'],
							$args[3]
						))
					)
				) $keyAsPrefix = $args[3].'_';
				else return SpelakoUtils::buildString($this->getMessage('bw.info.usage'), [$p['displayname']]);

				return SpelakoUtils::buildString(
					$this->getMessage('bw.layout'),
					[
						$rank.$p['displayname'],
						$modeName,
						isset($args[3]) ? '' : SpelakoUtils::buildString(
							$this->getMessage('bw.placeholders.general_stats'),
							[
								number_format($p['achievements']['bedwars_level']),
								number_format($p['stats']['Bedwars']['coins']),
							]
						),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'beds_broken_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'beds_lost_bedwars']),
						$p['stats']['Bedwars'][$keyAsPrefix.'winstreak'] === null && $p['stats']['Bedwars'][$keyAsPrefix.'wins_bedwars'] != 0 ? $this->getMessage('bw.placeholders.win_strikes_no_access') : number_format($p['stats']['Bedwars'][$keyAsPrefix.'winstreak']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'wins_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'losses_bedwars']),
						SpelakoUtils::div($p['stats']['Bedwars'][$keyAsPrefix.'wins_bedwars'], $p['stats']['Bedwars'][$keyAsPrefix.'losses_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'kills_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'deaths_bedwars']),
						SpelakoUtils::div($p['stats']['Bedwars'][$keyAsPrefix.'kills_bedwars'], $p['stats']['Bedwars'][$keyAsPrefix.'deaths_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'final_kills_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'final_deaths_bedwars']),
						SpelakoUtils::div($p['stats']['Bedwars'][$keyAsPrefix.'final_kills_bedwars'], $p['stats']['Bedwars'][$keyAsPrefix.'final_deaths_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'iron_resources_collected_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'gold_resources_collected_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'diamond_resources_collected_bedwars']),
						number_format($p['stats']['Bedwars'][$keyAsPrefix.'emerald_resources_collected_bedwars']),
						$footer
					]
				);
			case 'murdermystery':
			case 'mm':
				if(isset($args[3][0])) {
					
				}
				if($args[3][0] >= 1 && $args[3][0] <= 4 && $args[3][1] == null) $modeCode = $args[3];
				else if(ord($args[3][0]) >= ord('a') && ord($args[3][0]) <= ord('u') && $args[3][1] == null) $mapCode = ord($args[3]);
				else if($args[3][0] >= 1 && $args[3][0] <= 4 && ord($args[3][1]) >= ord('a') && ord($args[3][1]) <= ord('u') && $args[3][2] == null) {
					$modeCode = $args[3][0];
					$mapCode = ord($args[3][1]);
				}
				else if($args[3] == null)  {
					$modeCode = null;
					$mapCode = null;
				}
				else {
					return SpelakoUtils::buildString(
						$this->getMessage('mm.info.usage'),
						[
							$p['displayname'],
							$footer
						]
					);
				}

				$mode = [ // [keyPrefix, displayName]
					1 => ['_MURDER_CLASSIC', 'MURDER_CLASSIC'],
					['_MURDER_DOUBLE_UP','MURDER_DOUBLE_UP'],
					['_MURDER_ASSASSINS','MURDER_ASSASSINS'],
					['_MURDER_INFECTION','MURDER_INFECTION'],
					null => ['', 'all'],
				][$modeCode];
				
				$map = [ // [keyPrefix, displayName]
					97 => ['_ancient_tomb', 'Ancient Tomb'],
					['_aquarium', 'Aquarium'],
					['_archives', 'Archives'],
					['_archives_top_floor', 'Archives Top Floor'],
					['_cruise_ship', 'Cruise Ship'],
					['_darkfall', 'Darkfall'],
					['_gold_rush', 'Gold Rush'],
					['_headquarters', 'Headquarters'],
					['_hollywood', 'Hollywood'],
					['_hypixel_world', 'Hypixel World'],
					['_library', 'Library'],
					['_mountain', 'Mountain'],
					['_san_peratico_v2', 'San Peratico v3'],
					['_skyway_pier', 'Skyway Pier'],
					['_snowfall', 'Snowfall'],
					['_snowglobe', 'Snowglobe'],
					['_subway', 'Subway'],
					['_towerfall', 'Towerfall'],
					['_transport', 'Transport'],
					['_vilia', 'Vilia'],
					['_widow\'s_den', 'Widow\'s den'],
					null => ['', 'all']
				][$mapCode];
				
				return SpelakoUtils::buildString([
					'%1$s 的密室杀手%25$s地图%2$s统计信息:',
					$mode[0] == '' ? '硬币: %3$s | 当前 %4$s%% 杀手, %5$s%% 侦探' : '',
					'胜场: %6$s | 胜率: %7$.3lf%% | 金锭收集: %8$s',
					($mode[0] != '_MURDER_ASSASSINS' && $mode[0] != '_MURDER_INFECTION') ? '侦探胜场: %12$s | 杀手胜场: %13$s' : '',
					'死亡: %16$s',
					$mode[0] != '_MURDER_INFECTION' ? '击杀: %15$s | 陷阱击杀: %20$s ' : '',
					$mode[0] != '_MURDER_INFECTION' ? '近战击杀: %17$s | 飞刀击杀: %18$s | 弓箭击杀: %19$s' : '',
					($mode[0] != '_MURDER_ASSASSINS' && $mode[0] != '_MURDER_INFECTION') ? '作为杀手击杀: %14$s | 英雄: %21$s' : '',
					($mode[0] != '_MURDER_ASSASSINS' && $mode[0] != '_MURDER_INFECTION') ? '侦探最快胜利: %22$ss | 杀手最快胜利: %23$ss' : '',
					($mode[0] == '' || $mode[0] == '_MURDER_INFECTION') ? '作为感染者击杀: %26$s | 作为幸存者击杀: %27$s' : '',
					($mode[0] == '' || $mode[0] == '_MURDER_INFECTION') ? '幸存者总存活: %9$s | 幸存者最久存活: %10$ss' : '',
					'此命令详细用法可在此处查看: %24$s/#help'
				], [
					$rank.$p['displayname'],
					$this->getMessage('modes.'.$mode[1]),
					number_format($p['stats']['MurderMystery']['coins']),
					number_format($p['stats']['MurderMystery']['murderer_chance']),
					number_format($p['stats']['MurderMystery']['detective_chance']),
					number_format($p['stats']['MurderMystery']['wins'.$map[0].$mode[0]]),
					(SpelakoUtils::div($p['stats']['MurderMystery']['wins'.$map[0].$mode[0]]*100, $p['stats']['MurderMystery']['games'.$map[0].$mode[0]])),
					number_format($p['stats']['MurderMystery']['coins_pickedup'.$map[0].$mode[0]]),
					($survived_time = $p['stats']['MurderMystery']['total_time_survived_seconds'.$map[0].$mode[0]]*1000) != 0 ? SpelakoUtils::formatTime($survived_time, false, 'i:s') : '00:00',
					number_format($p['stats']['MurderMystery']['longest_time_as_survivor_seconds'.$map[0].$mode[0]]),
					null, //number_format($p['stats']['MurderMystery']['coins_pickedup'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['detective_wins'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['murderer_wins'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['kills_as_murderer'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['kills'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['deaths'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['knife_kills'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['thrown_knife_kills'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['bow_kills'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['trap_kills'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['was_hero'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['quickest_detective_win_time_seconds'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['quickest_murderer_win_time_seconds'.$map[0].$mode[0]]),
					$footer,
					$this->getMessage('maps.'.$map[1]),
					number_format($p['stats']['MurderMystery']['kills_as_infected'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['kills_as_survivor'.$map[0].$mode[0]])
				]);
			case 'zombies':
			case 'zb':
				$map = match(isset($args[3]) ? $args[3] : 'fastView') {
					'deadend', 'de' => [
						'mapKeys' => ['_deadend', 'Dead End'], // 0: key for stats; 1: key for map display name
						'bossKeys' => ['tnt', 'inferno', 'broodmother'],
					],
					'badblood', 'bb' => [
						'mapKeys' => ['_badblood', 'Bad Blood'],
						'bossKeys' => ['king_slime', 'wither', 'herobrine']
					],
					'alienarcadium', 'aa' => [
						'mapKeys' => ['_alienarcadium', 'Alien Arcadium'],
						'bossKeys' => ['giant', 'the_old_one', 'giant_rainbow', 'world_ender']
					],
					'all' => [
						'mapKeys' => ['', 'all']
					],
					'fastView' => [
						'mapKeys' => ['', 'fastView']
					],
					default => null
				};
				if(is_null($map)) return $this->getMessage('zb.info.unknown_map');
				if($map['mapKeys'][1] == 'fastView')
					return SpelakoUtils::buildString(
					$this->getMessage('zb.layout_fastView'),
					[
						$rank.$p['displayname'],
						number_format($p['stats']['Arcade']['total_rounds_survived_zombies']),
						number_format($p['stats']['Arcade']['wins_zombies']),
						100 * SpelakoUtils::div($p['stats']['Arcade']['bullets_hit_zombies'], $p['stats']['Arcade']['bullets_shot_zombies']),
						100 * SpelakoUtils::div($p['stats']['Arcade']['headshots_zombies'], $p['stats']['Arcade']['bullets_hit_zombies']),
						$p['stats']['Arcade']['wins_zombies_deadend_normal'] > 0 ? number_format($p['stats']['Arcade']['wins_zombies_deadend_normal']) : ($p['stats']['Arcade']['total_rounds_survived_zombies_deadend_normal'] == 0? '-':'['.number_format($p['stats']['Arcade']['best_round_zombies_deadend_normal']).']'),
						$p['stats']['Arcade']['wins_zombies_deadend_hard'] > 0 ? number_format($p['stats']['Arcade']['wins_zombies_deadend_hard']) : ($p['stats']['Arcade']['total_rounds_survived_zombies_deadend_hard'] == 0? '-':'['.number_format($p['stats']['Arcade']['best_round_zombies_deadend_hard']).']'),
						$p['stats']['Arcade']['wins_zombies_deadend_rip'] > 0 ? number_format($p['stats']['Arcade']['wins_zombies_deadend_rip']) : ($p['stats']['Arcade']['total_rounds_survived_zombies_deadend_rip'] == 0? '-':'['.number_format($p['stats']['Arcade']['best_round_zombies_deadend_rip']).']'),
						$p['stats']['Arcade']['wins_zombies_badblood_normal'] > 0 ? number_format($p['stats']['Arcade']['wins_zombies_badblood_normal']) : ($p['stats']['Arcade']['total_rounds_survived_zombies_badblood_normal'] == 0? '-':'['.number_format($p['stats']['Arcade']['best_round_zombies_badblood_normal']).']'),
						$p['stats']['Arcade']['wins_zombies_badblood_hard'] > 0 ? number_format($p['stats']['Arcade']['wins_zombies_badblood_hard']) : ($p['stats']['Arcade']['total_rounds_survived_zombies_badblood_hard'] == 0? '-':'['.number_format($p['stats']['Arcade']['best_round_zombies_badblood_hard']).']'),
						$p['stats']['Arcade']['wins_zombies_badblood_rip'] > 0 ? number_format($p['stats']['Arcade']['wins_zombies_badblood_rip']) : ($p['stats']['Arcade']['total_rounds_survived_zombies_badblood_rip'] == 0? '-':'['.number_format($p['stats']['Arcade']['best_round_zombies_badblood_rip']).']'),
						$p['stats']['Arcade']['wins_zombies_alienarcadium_normal'] > 0 ? number_format($p['stats']['Arcade']['wins_zombies_alienarcadium_normal']) : ($p['stats']['Arcade']['total_rounds_survived_zombies_alienarcadium_normal'] == 0? '-':'['.number_format($p['stats']['Arcade']['best_round_zombies_alienarcadium_normal']).']'),
						$footer
					]
				);

				$difficulty = match($map['mapKeys'][0] == '_alienarcadium' ? 'normal' : (isset($args[4]) ? $args[4] : 'general')) {
					'normal', 'norm' => ['_normal', 'normal'], // 0: key for stats; 1: key for map display name
					'hard' => ['_hard', 'hard'],
					'rip' => ['_rip', 'rip'],
					'general' => ['', 'general'],
					default => null
				};
				if(is_null($difficulty)) return $this->getMessage('zb.info.unknown_difficulty');

				return SpelakoUtils::buildString(
					$this->getMessage('zb.layout'),
					[
						$rank.$p['displayname'],
						$this->getMessage('maps.'.$map['mapKeys'][1]),
						$this->getMessage('zb.placeholders.difficulties'.$difficulty[1]),
						number_format($p['stats']['Arcade']['total_rounds_survived_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['wins_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['best_round_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['zombie_kills_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['players_revived_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['doors_opened_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['windows_repaired_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['times_knocked_down_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						number_format($p['stats']['Arcade']['deaths_zombies'.$map['mapKeys'][0].$difficulty[0]]),
						$difficulty[1] == 'general' ? '' : SpelakoUtils::buildString(
							$this->getMessage('zb.placeholders.records'), // 指定地图和难度时, 显示 zb.placeholders.records
							[
								SpelakoUtils::formatTime($p['stats']['Arcade']['fastest_time_10_zombies'.$map['mapKeys'][0].$difficulty[0]], true, 'H:i:s'),
								SpelakoUtils::formatTime($p['stats']['Arcade']['fastest_time_20_zombies'.$map['mapKeys'][0].$difficulty[0]], true, 'H:i:s'),
								SpelakoUtils::formatTime($p['stats']['Arcade']['fastest_time_30_zombies'.$map['mapKeys'][0].$difficulty[0]], true, 'H:i:s'),
							]
						),
						$map['mapKeys'][1] == 'all' ? (// 查询所有地图时, 显示 zb.placeholders.shots
							SpelakoUtils::buildString(
								$this->getMessage('zb.placeholders.shots'),
								[
									number_format($p['stats']['Arcade']['bullets_shot_zombies']),
									number_format($p['stats']['Arcade']['bullets_hit_zombies']),
									number_format($p['stats']['Arcade']['headshots_zombies']),
									100 * SpelakoUtils::div($p['stats']['Arcade']['bullets_hit_zombies'], $p['stats']['Arcade']['bullets_shot_zombies']),
									100 * SpelakoUtils::div($p['stats']['Arcade']['headshots_zombies'], $p['stats']['Arcade']['bullets_hit_zombies'])
								]
							)
						)
						: ( // 查询指定地图全部难度 (aa 地图只有一个难度) 时, 显示 zb.placeholders.boss_kills
							($map['mapKeys'][0] != '_alienarcadium' && $difficulty[1] != 'general') ? '' : SpelakoUtils::buildString(
								$this->getMessage($map['mapKeys'][0] == '_alienarcadium' ? 'zb.placeholders.boss_kills_aa' : 'zb.placeholders.boss_kills'),
								[
									$this->getMessage('zb.placeholders.bosses.'.$map['bossKeys'][0]),
									number_format($p['stats']['Arcade'][$map['bossKeys'][0].'_zombie_kills_zombies']),
									$this->getMessage('zb.placeholders.bosses.'.$map['bossKeys'][1]),
									number_format($p['stats']['Arcade'][$map['bossKeys'][1].'_zombie_kills_zombies']),
									$this->getMessage('zb.placeholders.bosses.'.$map['bossKeys'][2]),
									number_format($p['stats']['Arcade'][$map['bossKeys'][2].'_zombie_kills_zombies']),
									$map['mapKeys'][0] == '_alienarcadium' ? $this->getMessage('zb.placeholders.bosses.'.$map['bossKeys'][3]) : '',
									$map['mapKeys'][0] == '_alienarcadium' ? number_format($p['stats']['Arcade'][$map['bossKeys'][3].'_zombie_kills_zombies']) : '',
								]
							)
						),
						$footer
					]
				);
			case 'skyblock':
			case 'sb':
				$profiles = $p['stats']['SkyBlock']['profiles'];
				switch($args[3]) {
					case 'auctions':
					case 'auction':
					case 'au':
					case 'a':
						$profile_id = $this->getSkyblockProfileID($profiles, $args[4] ? : 1);
						if(!$profile_id) {
							$placeholder = array();
							foreach(array_keys($profiles) as $k => $v) {
								array_push($placeholder, sprintf(
									'%1$d. %2$s',
									($k + 1),
									$profiles[$v]['cute_name']
								));
							}
							return SpelakoUtils::buildString([
								'无法找到玩家 %1$s 的此空岛生存存档.',
								'此玩家的 %2$d 个空岛生存存档 (序号 - 存档名):',
								'%3$s',
							], [
								$p['displayname'],
								count($profiles),
								SpelakoUtils::buildString($placeholder),
							]);
						}
						$placeholder = array();
						$auctions = $this->fetchSkyblockAuction($profile_id);
						if($auctions == 'ERROR_REQUEST_FAILED') return '查询请求发送超时或失败, 请稍后再试.';
						if($auctions == 'ERROR_INCOMPLETE_JSON') return '查询结果接收失败, 请稍后再试.';

						
						if($auctions == 'ERROR_AUCTIONS_NOT_FOUND') return sprintf('找不到玩家 %1$s 的空岛生存 %2$s 存档中的物品拍卖信息.', $p['displayname'], $profiles[$profile_id]['cute_name']).((count($profiles) > 1) ? ' 你可以尝试查询此玩家的其他存档.' : '');
						$total = count($auctions);
						$totPages = ceil($total / 5);
						$curPage = ($args[5] > $totPages) ? $totPages : (int)$args[5];
						if($curPage < 1) $curPage = 1;
						for($i = ($curPage - 1) * 5; $i < $curPage * 5 && $i < $total; $i ++) {
							$item = $auctions[$i];
							array_push($placeholder, $item['bin']
								? SpelakoUtils::buildString([
									'# %1$s (%2$s)',
									'	一口价: %3$s',
									'	结束时间: %4$s',
									'	状态: %5$s'
								], [
									$item['item_name'],
									$item['tier'],
									number_format($item['starting_bid']),
									SpelakoUtils::formatTime($item['end']),
									$item['claimed_bidders'] ? '已被购买' : (time() < $item['end'] / 1000 ? '进行中' : '已结束, 无买主')
								])
								: SpelakoUtils::buildString([
									'# %1$s (%2$s)',
									time() < $item['end'] / 1000 ? '	最高出价: %3$s' : '	成交价: %3$s',
									'	出价数: %4$d',
									'	起拍价: %5$s',
									'	结束时间: %6$s',
									'	状态: %7$s'
								], [
									$item['item_name'],
									$item['tier'],
									number_format($item['highest_bid_amount']),
									count($item['bids']),
									number_format($item['starting_bid']),
									SpelakoUtils::formatTime($item['end']),
									time() < $item['end'] / 1000 ? '进行中' : '已结束'
								])
							);
						}
						return SpelakoUtils::buildString([
							'%1$s 的空岛生存 %2$s 存档物品拍卖信息:',
							'%3$s',
							'当前展示 %4$d/%5$d 页.',
							$totPages > 1 ?'使用 /hyp %6$s sb a %7$s <页数> 来查看具体页数的拍卖信息.' : '', 
							], [
								$rank.$p['displayname'],
								$profiles[$profile_id]['cute_name'],
								SpelakoUtils::buildString($placeholder),
								$curPage,
								$totPages,
								$p['displayname'],
								$profiles[$profile_id]['cute_name']
							]
						);
					case 'skills':
					case 'skill':
					case 'sk':
					case 's':
						$profile_id = $this->getSkyblockProfileID($profiles, $args[4] ? : 1);
						if(!$profile_id) {
							$placeholder = array();
							foreach(array_keys($profiles) as $k => $v) {
								array_push($placeholder, sprintf(
									'%1$d. %2$s',
									($k + 1),
									$profiles[$v]['cute_name']
								));
							}
							return SpelakoUtils::buildString([
								'无法找到玩家 %1$s 的此空岛生存存档.',
								'此玩家的 %2$d 个空岛生存存档 (序号 - 存档名):',
								'%3$s',
							], [
								$p['displayname'],
								count($profiles),
								SpelakoUtils::buildString($placeholder),
							]);
						}
						$profile = $this->fetchSkyblockProfile($profile_id);
						$member = $profile['members'][$p['uuid']];
						// If possible (allowed by player), access Skyblock Profile API insdead of Player API.
						
						if($this->isSkyblockProfileAccessible($member) && $profile != -1) {
							$profileAccessible = true;
							foreach(self::SKYBLOCK_SKILLS as $skill) {
								$skillLevels[$skill] = $this->getSkyblockLevel($member['experience_skill_'.$skill], $skill == 'runecrafting');
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
							$profileAccessible ? '木工: %11$d | 符文合成: %12$d' : ( $profile == -1 ? '注意: 访问该玩家技能 API 时超时或失败, ' : '注意: 该玩家技能信息被玩家在 API 设置中被阻止, ').'已显示为跨存档的最高等级.'
						], [
							$rank.$p['displayname'],
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
					case null:
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
							'/hyp %4$s sb <分类> [存档名/序号]',
							'目前支持的分类可以是下列之一:',
							'- skills, skill, sk, s',
							'- auctions, auction, au, a'
						], [
							$rank.$p['displayname'],
							count($profiles),
							SpelakoUtils::buildString($placeholder),
							$p['displayname']
						]);
					default:
						return SpelakoUtils::buildString([
							'未知的分类.',
							'目前支持的分类可以是下列之一:',
							'- skills, skill, sk, s',
							'- auctions, auction, au, a'
						]);
						
				}
			case 'pit':
			case 'thepit': 
				return SpelakoUtils::buildString(
					$this->getMessage('pit.layout'),
					[
						$rank.$p['displayname'],
						number_format($p['stats']['Pit']['profile']['xp']),
						count($p['stats']['Pit']['profile']['prestiges'] ?? []),
						number_format($p['stats']['Pit']['pit_stats_ptl']['max_streak']),
						number_format($p['stats']['Pit']['pit_stats_ptl']['kills']),
						number_format($p['stats']['Pit']['pit_stats_ptl']['assists']),
						number_format($p['stats']['Pit']['pit_stats_ptl']['deaths']),
						SpelakoUtils::div($p['stats']['Pit']['pit_stats_ptl']['kills'], $p['stats']['Pit']['pit_stats_ptl']['deaths']),
						SpelakoUtils::div($p['stats']['Pit']['pit_stats_ptl']['kills'] + $p['stats']['Pit']['pit_stats_ptl']['assists'], $p['stats']['Pit']['pit_stats_ptl']['deaths'])
					]
				);
			case 'r':
			case 'recent':
				$r = $this->fetchRecentGames($p['uuid']);
				if ($r == 'ERROR_REQUEST_FAILED') return '查询请求发送超时或失败, 请稍后再试.';
				if ($r == 'ERROR_INCOMPLETE_JSON') return '查询结果接收失败, 请稍后再试.';
				if ($r == 'ERROR_RECENT_GAMES_NOT_FOUND') return sprintf('玩家 %s 没有最近的游戏, 或在 API 设置中禁止了此请求.', $p['displayname']);
				$placeholder = array();
				$total = count($r);
				$totPages = ceil($total / 5);
				$curPage = min($totPages, intval($args[3] ?? 1));
				if($curPage < 1) $curPage = 1;
				for($i = ($curPage - 1) * 5; $i < $curPage * 5 && $i < $total; $i ++) {
					array_push($placeholder, SpelakoUtils::buildString([
						'# %1$s%2$s%3$s',
						'	开始时间: %4$s',
						$r[$i]['ended'] ? '	结束时间: %5$s' : '	● 游戏进行中...'
					], [
						$this->getMessage('games.'.$r[$i]['gameType']) ?? (' '.$r[$i]['gameType'].' '),
						$this->getMessage('modes.'.$r[$i]['mode']) ?? (' '.$r[$i]['mode'].' '),
						$this->getMessage('maps.'.$r[$i]['map']) ?? (' '.$r[$i]['map'].' '),
						SpelakoUtils::formatTime($r[$i]['date'], format:'Y-m-d H:i:s'),
						$r[$i]['ended'] ? SpelakoUtils::formatTime($r[$i]['ended'], format:'Y-m-d H:i:s') : ''
					]));
				}
				return SpelakoUtils::buildString([
					'%1$s 的最近游玩的游戏:',
					'%2$s',
					'当前展示 %3$d/%4$d 页.',
					$totPages > 1 ? '使用 /hyp %5$s r <页数> 来查看具体页数的游戏数据.' : ''
				], [
					$rank.$p['displayname'],
					SpelakoUtils::buildString($placeholder),
					$curPage,
					$totPages,
					$p['displayname'],
				]);
			case 'p':
			case 'parkour':
				$lobby = $args[3];
				if ($lobby == null || $lobby == ' ') {
					$placeholder = array();
					for ($i = 0; $i < 22; $i ++) {
						array_push($placeholder, SpelakoUtils::buildString([
							'%1$d. %2$s: %3$s',
						], [
							$i + 1,
							self::PARKOUR_LOBBY_NAME[$i],
							($parkourTime = $p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$i]][0]['timeTook']) != null ? SpelakoUtils::formatTime($parkourTime, false, 'i:s').'.'. sprintf('%03s', $parkourTime % 1000) : '未' . ($p['parkourCheckpointBests'][self::PARKOUR_LOBBY_CODE[$i]][0] != null ? '完全' : '') . '完成'
						]));
					}
					return SpelakoUtils::buildString([
						'%1$s 的跑酷信息(序号 - 中文名):',
						'%2$s',
						'使用 /hyp %3$s p <序号> 来查看包括每个存档点的纪录和总纪录的创立时间的详细信息.'
					], [
						$rank.$p['displayname'],
						SpelakoUtils::buildString($placeholder),
						$p['displayname']
					]);
				}
				else if($lobby >= 1 && $lobby <= 22) {
					$lobby--;
					$placeholder = array();
					for ($i = 0; $i <= self::PARKOUR_LOBBY_CHECKPOINT[$lobby]; $i ++) {
						$checkPointTime = $p['parkourCheckpointBests'][self::PARKOUR_LOBBY_CODE[$lobby]][$i];
						// if ($checkPointTime == null) break;
						array_push($placeholder, SpelakoUtils::buildString([
						'%1$d. %2$s',
						], [
							$i+1,
							$checkPointTime != null ? SpelakoUtils::formatTime($checkPointTime, false, 'i:s').'.'. sprintf('%03s', $checkPointTime % 1000) : '未完成'
						]));
					}
					return SpelakoUtils::buildString([
						self::PARKOUR_LOBBY_CHECKPOINT[$lobby] != -1 ? '%1$s 的%2$s跑酷每个存档点最佳记录:' : '%1$s 的%2$s跑酷详细信息 (该跑酷无存档点):',
						'%3$s',
						'完成跑酷用时: %4$s',
						$p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeTook'] != null ? '记录创建于: %5$s' : null
					], [
						$rank.$p['displayname'],
						self::PARKOUR_LOBBY_NAME[$lobby],
						SpelakoUtils::buildString($placeholder),
						($parkourTime = $p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeTook']) != null ? SpelakoUtils::formatTime($parkourTime, false, 'i:s').'.'. sprintf('%03s', $parkourTime % 1000) : '未完成' ,
						SpelakoUtils::formatTime($p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeTook']+$p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeStart'], format:'Y-m-d H:i:s')
					]);
				}
				else {
					$placeholder = array();
					for ($i = 0; $i < 22; $i ++) 
						array_push($placeholder, SpelakoUtils::buildString([
							'- %1$d. %2$s',
						], [
							$i + 1,
							self::PARKOUR_LOBBY_NAME[$i],
						]));
					return SpelakoUtils::buildString([
						'未知的序号:',
						'目前支持的序号可以为下列之一(序号 - 中文名):',
						'%1$s',
					], [
						SpelakoUtils::buildString($placeholder),
					]);
				}
			default:
				if(isset($args[2])) return SpelakoUtils::buildString($this->getUsage());

				$online = isset($p['lastLogout']) && ($p['lastLogout'] < $p['lastLogin']);
				if($online) {
					$src = SpelakoUtils::getURL(self::API_BASE_URL.'/status', ['key' => $this->config->api_key, 'uuid' => $p['uuid']], 10);
					if($src) $status = json_decode($src, true)['session'];
				}

				return SpelakoUtils::buildString(
					$this->getMessage('general.layout'),
					[
						$rank.$p['displayname'],
						$p['networkExp'] < 0 ? 1 : sqrt(12.25 + 0.0008 * $p['networkExp']) - 2.5,
						number_format($p['karma']),
						number_format($p['achievementPoints']),
						number_format($p['achievements']['general_wins']),
						number_format($p['achievements']['general_quest_master']),
						number_format($p['achievements']['general_challenger']),
						number_format($p['achievements']['general_coins']),
						empty($p['userLanguage']) ? $this->getMessage('general.placeholders.no_access') : ($this->getMessage('languages.'.$p['userLanguage']) ?? (' '.$p['userLanguage'].' ')),
						empty($p['mostRecentGameType']) ? $this->getMessage('general.placeholders.no_access_or_data') : ($this->getMessage('games.'.$p['mostRecentGameType']) ?? (' '.$p['mostRecentGameType'].' ')),
						// TO TEST OUT THIS
						SpelakoUtils::formatTime($p['firstLogin'], format:'Y-m-d H:i:s'),
						empty($p['lastLogin']) ? $this->getMessage('general.placeholders.no_access') : SpelakoUtils::formatTime($p['lastLogin'], format:'Y-m-d H:i:s'),
						$online ? (
							SpelakoUtils::buildString(
								$this->getMessage('general.placeholders.online'),
								[
									SpelakoUtils::formatTime(time() - $p['lastLogin'] / 1000, true, 'H:i:s')
								]
							)
						)
						: (
							SpelakoUtils::buildString(
								$this->getMessage('general.placeholders.last_logout'),
								[
									empty($p['lastLogin']) ? $this->getMessage('general.placeholders.no_access') : SpelakoUtils::formatTime($p['lastLogout'], format:'Y-m-d H:i:s')
								]
							)
						),
						!$online ? '' : SpelakoUtils::buildString(
							$this->getMessage('general.placeholders.status'),
							[
								$status ? ($this->getMessage('games.'.$status['gameType']) ?? (' '.$status['gameType'].' ')) : $this->getMessage('general.placeholders.no_access'),
								$status ? ($this->getMessage('modes.'.($status['mode'])) ?? (' '.$status['mode'].' ')) : '',
								$status ? ($this->getMessage('maps.'.($status['map'] ?? 'none')) ?? (' '.$status['map'].' ')) : ''
							]
						),
						$footer,
						number_format($p['rewardScore'])
					]
				);
		}
	}

	private function fetchRecentGames($playerUuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/recentgames', ['key' => $this->config->api_key, 'uuid' => $playerUuid], 45);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		$result = json_decode($src, true);
		if($result['success'] != true) return 'ERROR_INCOMPLETE_JSON';
		if($result['games'] == null) return 'ERROR_RECENT_GAMES_NOT_FOUND';
		return $result['games'];
	}
	
	private function fetchSkyblockAuction($profile) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/skyblock/auction', ['key' => $this->config->api_key, 'profile' => $profile], 300);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		$result = json_decode($src, true);
		if($result['success'] != true) return 'ERROR_INCOMPLETE_JSON';
		if($result['auctions'] == null) return 'ERROR_AUCTIONS_NOT_FOUND';
		return array_reverse($result['auctions']);
	}

	private function fetchSkyblockProfile($profile) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/skyblock/profile', ['key' => $this->config->api_key, 'profile' => $profile], 300);
		if(!$src) return false;
		$result = json_decode($src, true);
		if($result['success'] != true || $result['profile'] == null) return false;
		return $result['profile'];
	}

	// Searches player.stats.Skyblock.profile[] for Skyblock Profile ID that matches the query provided as index (int) or name (string)
	private function getSkyblockProfileID($profiles, $query) {
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
	private function getSkyblockLevel($exp, $runecrafting = false) {
		if($runecrafting)
			$levelingLadder = [0, 50, 150, 275, 435, 635, 885, 1200, 1600, 2100, 2725, 3510, 4510, 5760, 7325, 9325, 11825, 14950, 18950, 23950, 30200, 38050, 47850, 60100, 75400, 94450];
		else
			$levelingLadder = [0, 50, 175, 375, 675, 1175, 1925, 2925, 4425, 6425, 9925, 14925, 22425, 32425, 47425, 67425, 97425, 147425, 222425, 322425, 522425, 822425, 1222425, 1722425, 2322425, 3022425, 3822425, 4722425, 5722425, 6822425, 8022425, 9322425, 10722425, 12222425, 13822425, 15522425, 17322425, 19222425, 21222425, 23322425, 25522425, 27822425, 30222425, 32722425, 35322425, 38072425, 40972425, 44072425, 47472425, 51172425, 55172425, 59472425, 64072425, 68972425, 74172425, 79672425, 85472425, 91572425, 91972425, 104672425, 111672425];
		foreach($levelingLadder as $curlevel => $required)
			if($exp > $required) $level = $curlevel;
		return $level;
	}

	// Clears the Minecraft formatting string of a string
	private function getPlainString($formattedStr) {
		$plainStr = str_replace(
			array('§0', '§1', '§2', '§3', '§4', '§5', '§6', '§7', '§8', '§9', '§a', '§b', '§c', '§d', '§e', '§f', '§k', '§l', '§m', '§n', '§o', '§r')
		, '', $formattedStr);
		return $plainStr;
	}

	// Gets the accessibility of the Skyblock Profile API of player
	private function isSkyblockProfileAccessible($profile) {
		foreach(self::SKYBLOCK_SKILLS as $skill) {
			if(isset($profile['experience_skill_'.$skill])) return true;
		}
		return false;
	}
}
?>
