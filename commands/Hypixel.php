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

class Hypixel {
	const API_BASE_URL = 'https://api.hypixel.net';

	const SKYBLOCK_SKILLS = ['taming', 'farming', 'mining', 'combat', 'foraging', 'fishing', 'enchanting', 'alchemy', 'carpentry', 'runecrafting'];
	const PARKOUR_LOBBY_CODE = ['mainLobby2017', 'Bedwars', 'Skywars', 'SkywarsAug2017', 'ArcadeGames', 'MurderMystery', 'TNT', 'uhc', 'SpeedUHC', 'Prototype', 'BuildBattle', 'Housing', 'TruePVPParkour', 'MegaWalls', 'BlitzLobby', 'Warlords', 'SuperSmash', 'CopsnCrims', 'Duels', 'Legacy', 'SkyClash', 'Tourney'];
	const PARKOUR_LOBBY_ATTRIB = ['main', 'bw', 'sw', 'sw2017.8', 'arcade', 'mm', 'tnt', 'uhc', 'SpeedUHC', 'Prototype', 'BuildBattle', 'Housing', 'TruePVPParkour', 'mw', 'BlitzLobby', 'Warlords', 'SuperSmash', 'CopsnCrims', 'Duels', 'Legacy', 'SkyClash', 'Tourney'];
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

		if(!$src) return $this->getMessage('info.request_failed');
		if(str_contains($status, '429')) return $this->getMessage('info.rate_limit_reached');
		$result = json_decode($src, true);
		if($result['success'] != true) return $this->getMessage('info.incomplete_json');
		if($result['player'] == null) return $this->getMessage('info.player_not_found');
		$p = $result['player'];
		// TO FIX: see whether an "unset($result)" is necessary

		$rank = '';
		if(isset($p['rank']) && $p['rank'] != 'NONE' && $p['rank'] != 'NORMAL') $rank = '['.$p['rank'].'] ';
		if(isset($p['monthlyPackageRank']) && $p['monthlyPackageRank'] != 'NONE') $rank = '[MVP++] ';
		if(isset($p['newPackageRank']) && $p['newPackageRank'] != 'NONE') $rank = '['.str_replace('_PLUS', '+', $p['newPackageRank']).'] ';

		$footer = SpelakoUtils::buildString(
			$this->getMessage('info.learn_more'),
			[
				$this->core::WEBSITE
			]
		);

		switch($args[2]) {
			case 'guild':
			case 'g':
				$src = SpelakoUtils::getURL(self::API_BASE_URL.'/guild', ['key' => $this->config->api_key, 'player' => $p['uuid']], 300);
				if(!$src) return $this->getMessage('info.request_failed');
				$result = json_decode($src, true);
				if($result['success'] != true) return $this->getMessage('info.incomplete_json');
				if($result['guild'] == null) return SpelakoUtils::buildString($this->getMessage('info.guild.guild_not_found'), [$p['displayname']]);
				$g = $result['guild'];

				return SpelakoUtils::buildString(
					$this->getMessage('guild.layout'),
					[
						$rank.$p['displayname'],
						$g['name'],
						SpelakoUtils::formatTime($g['created'], offset: $this->config->timezone_offset),
						$this->getGuildLevel($g['exp']),
						$this->getPlainString($g['tag']),
						count($g['members']),
						$g['achievements']['ONLINE_PLAYERS']
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
					$modeName = $this->getMessage('modes.general');
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
						isset($args[3]) ? $this->getMessage('modes.duels'.$keyAsSuffix) : $this->getMessage('modes.general'),
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
						$this->getPlainString($p['stats']['SkyWars']['levelFormatted']),
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
			case 'bedwars':
			case 'bw':
				if(empty($args[3])) {
					$keyAsPrefix = '';
					$modeName = $this->getMessage('modes.general');
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
					$this->getModeName($mode[1]),
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
					$this->getMapName($map[1]),
					number_format($p['stats']['MurderMystery']['kills_as_infected'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['kills_as_survivor'.$map[0].$mode[0]])
				]);
			case 'zombies':
			case 'zb':
				$map = match(isset($args[3]) ? $args[3] : 'all') {
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
					default => null
				};
				if(is_null($map)) return $this->getMessage('zb.info.unknown_map');

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
			case 'r':
			case 'recent':
				$r = $this->fetchRecentGames($p['uuid']);
				if ($r == 'ERROR_REQUEST_FAILED') return '查询请求发送超时或失败, 请稍后再试.';
				if ($r == 'ERROR_INCOMPLETE_JSON') return '查询结果接收失败, 请稍后再试.';
				if ($r == 'ERROR_RECENT_GAMES_NOT_FOUND') return sprintf('玩家 %s 没有最近的游戏, 或在 API 设置中禁止了此请求.', $p['displayname']);
				$placeholder = array();
				$total = count($r);
				$totPages = ceil ($total / 5);
				$curPage = ($args[3] > $totPages) ? $totPages : (int)$args[3];
				if($curPage < 1) $curPage = 1;
				for($i = ($curPage - 1) * 5; $i < $curPage * 5 && $i < $total; $i ++) {
					array_push($placeholder, SpelakoUtils::buildString([
						'# %1$s%2$s%3$s',
						'	开始时间: %4$s',
						$r[$i]['ended'] ? '	结束时间: %5$s' : '	● 游戏进行中...'
					], [
						$this->getGameName($r[$i]['gameType']),
						$this->getModeName($r[$i]['mode']),
						($statusMap = $this->getMapName($r[$i]['map'])) != '' ? $statusMap.'地图' : '',
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
				if ($lobby == null || $lobby == ' '){
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
				else if($lobby >=1 && $lobby <=22) {
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
				if(isset($args[2])) return SpelakoUtils::buildString([
					'未知的分类.',
					'用法: %s',
					'目前支持的分类可以是下列之一:',
					'- recent, r',
					'- guild, g',
					'- bedwars, bw',
					'- skywars, sw',
					'- murdermystery, mm',
					'- duels, duel',
					'- uhc',
					'- megawalls, mw',
					'- blitzsg, bsg, hungergames',
					'- zombies, zb',
					'- skyblock, sb',
					'- parkour, p',
					'更多分类正在开发中...'
				], [
					$this->getUsage()
				]);
				$online = isset($p['lastLogout']) && ($p['lastLogout'] < $p['lastLogin']);
				$s = $online ? $this->fetchStatus($p['uuid']) : false;
				$statusAvailable = ($s != false);
				return SpelakoUtils::buildString([
					'%1$s 的 Hypixel 信息:',
					'等级: %2$.3f | 人品: %3$s',
					'成就点数: %4$s | 小游戏胜场: %5$s',
					'完成任务: %6$s | 完成挑战: %7$s',
					'获得硬币: %8$s | 使用语言: %17$s',
					$p['mostRecentGameType'] != null ?'最近游玩: %9$s':'',
					'首次登录: %10$s',
					$p['lastLogin'] != null ? '上次登录: %11$s' : '',
					$online ? '● 此玩家在线了 %12$s, '.($statusAvailable ? '当前在%13$s%14$s%15$s中.' : '获取当前游戏时出错 ' ) :($p['lastLogin'] ? '上次退出: %12$s' : ''),
					$p['mostRecentGameType'] == 0 ? '该玩家在 API 设置中阻止了最近游戏请求, 或者最近没有游玩.' : '',
					$p['lastLogin'] == 0 ? '该玩家在 API 设置中阻止了在线状态请求.' : '',
					'此命令详细用法可在此处查看: %16$s/#help'
				], [
					$rank.$p['displayname'],
					$this->getNetworkLevel($p['networkExp']),
					number_format($p['karma']),
					number_format($p['achievementPoints']),
					number_format($p['achievements']['general_wins']),
					number_format($p['achievements']['general_quest_master']),
					number_format($p['achievements']['general_challenger']),
					number_format($p['achievements']['general_coins']),
					$this->getGameName($p['mostRecentGameType']),
					SpelakoUtils::formatTime($p['firstLogin'], format:'Y-m-d H:i:s'),
					SpelakoUtils::formatTime($p['lastLogin'], format:'Y-m-d H:i:s'),
					$online ? SpelakoUtils::formatTime(time() - $p['lastLogin'] / 1000, true, 'H:i:s') : SpelakoUtils::formatTime($p['lastLogin'], format:'Y-m-d H:i:s'),
					$statusAvailable ? $this->getGameName($s['gameType']) : '',
					$statusAvailable ? $this->getModeName($s['mode']) : '',
					$statusAvailable ? (($statusMap = $this->getMapName($s['map'])) != '' ? $statusMap.'地图' : '') : '',
					$footer,
					$this->getLanguageName($p['userLanguage'])
				]);
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

	private function fetchStatus($playerUuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/status', ['key' => $this->config->api_key, 'uuid' => $playerUuid], 10);
		if($src && ($result = json_decode($src, true)['session'])) return $result;
		return false;
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

	// Gets network level by exp
	private function getNetworkLevel($exp) {
		return $exp < 0 ? 1 : sqrt(12.25 + 0.0008 * $exp) - 2.5;
	}

	// Gets guild level by exp
	private function getGuildLevel($exp) {
		$guildLevelTables = [100000, 150000, 250000, 500000, 750000, 1000000, 1250000, 1500000, 2000000, 2500000, 2500000, 2500000, 2500000, 2500000, 3000000];
		$level = 0;
		for ($i = 0; ; $i++) {
			$need = $i >= sizeof($guildLevelTables) ? $guildLevelTables[sizeof($guildLevelTables) - 1] : $guildLevelTables[$i];
			$exp -= $need;
			if ($exp < 0) return $level + 1 + $exp / $need;
			else $level ++;
		}
		return -1;
	}

	private function getLanguageName($typeName) {
		return match($typeName) {
			'ENGLISH' => '英语',
			'GERMAN' => '德语',
			'FRENCH' => '法语',
			'CHINESE_SIMPLIFIED' => '简体中文',
			'CHINESE_TRADITIONAL' => '繁体中文',
			'PORTUGUESE_BR' => '葡萄牙语_巴西',
			'RUSSIAN' => '俄语',
			'KOREAN' => '韩语/朝鲜语',
			'POLISH' => '波兰语',
			'JANPANESE' => '日本语',
			'PIRATE' => '海盗语',
			'PORTUGUESE_PT' => '葡萄牙语_葡萄牙',
			'TURKISH' => '土耳其',
			'CZECH' => '捷克语',
			'FINNISH' => '芬兰语',
			'GREEK' => '希腊语',
			'' => '获取被阻止',
			default => $typeName
		};
		
	}

	private function getGameName($typeName) {
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
			'MAIN' => '主',
			default => $typeName
		};
		
	}
	
	private function getMapName($mapName) {
		return match($mapName) {
			// Arcade Start
			'Dead End' => '穷途末路',
			'Bad Blood' => '坏血之宫',
			'Alien Arcadium' => '外星游乐园',
			// ARCADE End
			
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
			'Cauldron' => '炼药锅',		// 这个好像是万圣节地图还是什么地图来自的（？）也是没有单独的字符串，游戏内挂载的就是炼药锅
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
			
			// Murder Mystery start
			'Ancient Tomb' => '古墓',
			'Towerfall' => '高坠塔',
			'Transport' => '运输塔',
			'Archives' => '档案馆',
			'Hypixel World' => ' Hypixel 游乐园',
			'Headquarters' => '总部',
			'Library' => '图书馆',
			'Gold Rush' => '淘金热',
			'Cruise Ship' => '游轮',
			'Hollywood' => '好莱坞',
			'Archives Top Floor' => '档案馆顶层',
			'Widow\'s Den' => '寡妇的书房',
			'Aquarium' => '水族馆',
			'Snowglobe' => '雪景球',	// 这个没有单独字符串，游戏里面直接挂载了雪景球这个翻译
			// Murder Mystery End
			
			// Duels Start
			'Arena' => '竞技场',	// 没有单独字符串
			'Sumo' => '相扑',
			'Aquatica' => '水生',
			
			// 决斗游戏地图名太难找了 www，不翻了
			
			// Duels End
			
			// Warlords Start
			'Thornhill' => '生灵之山',
			'The Rift' => '征召峡谷',
			'Stormwind' => '风暴之境',
			'Scorched' => '暗黑圣地',
			'Ruins' => '遗弃古堡',
			'Neolithic' => '大漠之丘',
			'Gorge' => '雪原城池',
			'Falstad Gate' => '石城之门',
			'Doriven Basin' => '荒郊盆地',
			'Death Valley' => '死亡山谷',
			'Crossfire' => '交火王城',
			'Black Temple' => '黑岩神寺',
			'Atherrough Valley' => '神魔之城',
			'Arches' => '失落山谷',
			'Arathi' => '远郊乡村',
			// Warlords End
			
			'all' => '全部',
			null => '',
			default => ' '.$mapName.' '
		};
	}
	
	private function getModeName($modeName) {
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
			'dungeon' => '地牢',
			// Skyblock End

			// Skywars Start
			'ranked_normal' => '排位模式',
			'solo_normal' => '单挑普通模式',
			'solo_insane' => '单挑疯狂模式',
			'teams_normal' => '双人普通模式',
			'teams_insane' => '双人疯狂模式',
			'mega_normal' => ' 超级普通模式',
			'mega_doubles' => '超级 Doubles 模式',
			'solo_insane_tnt_madness' => '单挑疯狂 TNT 模式',
			'teams_insane_tnt_madness' => '双人疯狂 TNT 模式',
			'solo_rush' => '单挑疾速模式',
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
			'BEDWARS_CASTLE' => ' 40v40 城池攻防战模式',
			'BEDWARS_TWO_FOUR' => '4V4 模式',
			'BEDWARS_EIGHT_TWO_VOIDLESS' => '双人无虚空模式',
			'BEDWARS_FOUR_FOUR_VOIDLESS' => ' 4v4v4v4 无虚空模式',
			'BEDWARS_EIGHT_TWO_ARMED' => '双人枪械模式',
			'BEDWARS_FOUR_FOUR_ARMED' => ' 4v4v4v4 枪械模式',
			'BEDWARS_EIGHT TWO_LUCKY' => '双人幸运方块模式',
			'BEDWARS_FOUR_FOUR_LUCKY' => ' 4v4v4v4 幸运方块模式',
			'BEDWARS_EIGHT TWO_UNDERWORLD' => '双人 Under World 模式',
			'BEDWARS_FOUR_FOUR_UNDERWORLD' => ' 4v4v4v4 Under World 模式',
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
			'DEFENDER' => '进击的苦力怕',	// 这个改了 = =
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
			'HALLOWEEN_SIMULATOR' => '万圣夜模拟器',
			'GRINCH_SIMULATOR' => '圣诞怪杰模拟器',
			'EASTER_SIMULATOR' => '复活节模拟器',
			'SCUBA_SIMULATOR' => ' Scuba Simulator ', // 可以说是潜水模拟器的 但是官方不给翻译

			// Arcade End

			// Cops and Crims Start
			'normal' => '爆破模式',
			'deathmatch' => '团队死亡竞赛模式',
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
			'DUELS_BLITZ_DUEL' => '闪电饥饿游戏决斗',
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
			'TOWERWARS_SOLO' => '塔防战争单挑模式',
			'TOWERWARS_TEAM_OF_TWO' => '塔防战争双人模式',
			
			// TowerWars End
			'PIXEL_PARTY' => ' Pixel Party ',
			'PIT', null => '',
			'all' => '全局',
			default => ' '.$modeName.' '
		};
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
