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
	const PARKOUR_LOBBY_CODE = ['mainLobby2017', 'Bedwars', 'Skywars', 'SkywarsAug2017', 'ArcadeGames', 'MurderMystery', 'TNT', 'uhc', 'SpeedUHC', 'Prototype', 'BuildBattle', 'Housing', 'TruePVPParkour', 'MegaWalls', 'BlitzLobby', 'Warlords', 'SuperSmash', 'CopsnCrims', 'Duels', 'Legacy', 'SkyClash', 'Tourney'];
	const PARKOUR_LOBBY_ATTRIB = ['main', 'bw', 'sw', 'sw2017.8', 'arcade', 'mm', 'tnt', 'uhc', 'SpeedUHC', 'Prototype', 'BuildBattle', 'Housing', 'TruePVPParkour', 'mw', 'BlitzLobby', 'Warlords', 'SuperSmash', 'CopsnCrims', 'Duels', 'Legacy', 'SkyClash', 'Tourney'];
	const PARKOUR_LOBBY_NAME = ['主大厅 2017', '起床战争', '空岛战争', '空岛战争 2017.8', '街机游戏', '密室杀手', '掘战游戏', '极限生存冠军', '速战极限生存', '游戏实验室', '建筑大师', '家园世界', 'True PVP Parkour', '超级战墙', '闪电饥饿游戏' ,'战争领主', '星碎英雄', '警匪大战' ,'决斗游戏', '经典游戏', '空岛竞技场', '竞赛殿堂'];
	const PARKOUR_LOBBY_CHECKPOINT = [2, 3, -1, 3, 6, 3, 3, 2, 1, 4, 3, 7, -1, 3, 0, 2, -1, 4, 3, 2, -1, 3];


	public static function execute(array $args) {
		if(!isset($args[1])) return SpelakoUtils::buildString([
			'用法: %s',
			'目前支持的分类可以是下列之一:',
			'- recent, r',
			'- guild, g',
			'- bedwars, bw',
			'- skywars, sw',
			'- murdermystery, mm',
			'- uhc',
			'- megawalls, mw',
			'- blitzsg, bsg, hungergames',
			'- zombies, zb',
			'- skyblock, sb',
			'- parkour, p',
			'更多分类正在开发中...',
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
					'游玩次数: %2$s | 硬币: %3$s | 开箱数: %4$s',
					'击杀: %5$s | 死亡: %6$s | K/D: %7$.3f'
				], [
					self::getNetworkRank($p).$p['displayname'],
					number_format($p['stats']['HungerGames']['games_played']),
					number_format($p['stats']['HungerGames']['coins']),
					number_format($p['stats']['HungerGames']['chests_opened']),
					number_format($p['stats']['HungerGames']['kills']),
					number_format($p['stats']['HungerGames']['deaths']),
					SpelakoUtils::div($p['stats']['HungerGames']['kills'], $p['stats']['HungerGames']['deaths'])
				]);
			case 'uhc':
				return SpelakoUtils::buildString([
					'%1$s 的极限生存冠军统计信息:',
					'分数: %2$s | 硬币: %3$s | 胜场: %4$s',
					'击杀: %5$s | 死亡: %6$s | K/D: %7$.3f'
				], [
					self::getNetworkRank($p).$p['displayname'],
					number_format($p['stats']['UHC']['score']),
					number_format($p['stats']['UHC']['coins']),
					number_format($p['stats']['UHC']['wins']),
					number_format($p['stats']['UHC']['kills']),
					number_format($p['stats']['UHC']['deaths']),
					SpelakoUtils::div($p['stats']['UHC']['kills'], $p['stats']['UHC']['deaths'])
				]);
			case 'megawalls':
			case 'mw':
				return SpelakoUtils::buildString([
					'%1$s 的超级战墙统计信息:',
					'凋零伤害: %2$s | 职业: %3$s | 硬币: %4$s',
					'击杀: %5$s | 助攻: %6$s | 死亡: %7$s | K/D: %8$.3f',
					'终杀: %9$s | 终助: %10$s | 终死: %11$s | FKDR: %12$.3f',
					'胜场: %13$s | 败场: %14$s | W/L: %15$.3f'
				], [
					self::getNetworkRank($p).$p['displayname'],
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
				]);
			case 'skywars':
			case 'sw':
				return SpelakoUtils::buildString([
					'%1$s 的空岛战争统计信息:',
					'等级: %2$s | 硬币: %3$s | 助攻: %4$s',
					'击杀: %5$s | 死亡: %6$s | K/D: %7$.3f',
					'胜场: %8$s | 败场: %9$s | W/L: %10$.3f',
				], [
					self::getNetworkRank($p).$p['displayname'],
					self::getPlainString($p['stats']['SkyWars']['levelFormatted']),
					number_format($p['stats']['SkyWars']['coins']),
					number_format($p['stats']['SkyWars']['assists']),
					number_format($p['stats']['SkyWars']['kills']),
					number_format($p['stats']['SkyWars']['deaths']),
					SpelakoUtils::div($p['stats']['SkyWars']['kills'], $p['stats']['SkyWars']['deaths']),
					number_format($p['stats']['SkyWars']['wins']),
					number_format($p['stats']['SkyWars']['losses']),
					SpelakoUtils::div($p['stats']['SkyWars']['wins'], $p['stats']['SkyWars']['losses'])
				]);
			case 'bedwars':
			case 'bw':
				$mode = match($args[3]) { // [keyPrefix, displayName]
					'eight_one', '8_1', 'solo' => ['eight_one_', '单挑模式'],
					'eight_two', '8_2', 'doubles', => ['eight_two_', '双人模式'],
					'four_three', '4_3', '3v3v3v3' => ['four_three_', ' 3v3v3v3 模式'],
					'four_four', '4_4', '4v4v4v4' => ['four_four_', ' 4v4v4v4 模式'],
					'two_four', '2_4', ' 4v4' => ['two_four_', '4v4 模式'],
					'eight_two_rush', '8_2_rush', 'doubles_rush' => ['eight_two_rush_', '双人疾速模式'],
					'four_four_rush', '4v4v4v4_rush' => ['four_four_rush_', '4v4v4v4 疾速模式'],
					'eight_two_ultimate', 'eight_two_ult', '8_2_ult', 'doubles_ultimate', 'double_ult' => ['eight_two_ultimate_', '双人超能力模式'],
					'four_four_ultimate', 'four_four_ult','4_4_ult', '4v4v4v4_ultimate', '4v4v4v4_ult' => ['four_four_ultimate_', ' 4v4v4v4 超能力模式'],
					'castle' => ['castle_', '40v40 城池攻防战模式'],
					'eight_two_voidless', 'eight_two_void', '8_2_void', 'doubles_voidless', 'double_void' => ['eight_two_voidless_', '双人无虚空模式'],
					'four_four_voidless', 'four_four_void','4_4_void', '4v4v4v4_voidless', '4v4v4v4_void' => ['four_four_voidless_', ' 4v4v4v4 无虚空模式'],
					'eight_two_armed', '8_2_armed', 'doubles_armed' => ['eight_two_armed_', '双人枪械模式'],
					'four_four_armed', '4_4_armed', '4v4v4v4_armed' => ['four_four_armed_', ' 4v4v4v4 枪械模式'],
					'eight_two_lucky', '8_2_lucky', 'doubles_lucky', 'duo_lucky' => ['eight_two_lucky_', '双人幸运方块模式'],
					'four_four_lucky', '4_4_lucky', '4v4v4v4_lucky' => ['four_four_lucky_', ' 4v4v4v4 幸运方块模式'],
					null => ['', '全局'],
					default => 'ERROR'
				};
				if($mode == 'ERROR') return SpelakoUtils::buildString([
					'未知的模式.',
					'目前支持的模式可以是下列之一:',
					'- eight_one, 8_1, solo',
					'- eight_two, 8_2, doubles',
					'- four_three, 4_3, 3v3v3v3',
					'- four_four, 4_4, 4v4v4v4',
					'- two_four, 2_4, 4v4',
					'- eight_two_rush, 8_2_rush, doubles_rush',
					'- four_four_rush, 4v4v4v4_rush',
					'- eight_two_ultimate, eight_two_ult, 8_2_ult, doubles_ultimate, doubles_ult',
					'- four_four_ultimate, four_four_ult, 4_4_ult, 4v4v4v4_ultimate, 4v4v4v4_ult',
					'- castle',
					'- eight_two_voidless, eight_two_void, 8_2_void, doubles_voidless, double_void',
					'- four_four_voidless, four_four_void, 4_4_void, 4v4v4v4_voidless, 4v4v4v4_void',
					'- eight_two_armed, 8_2_armed, doubles_armed',
					'- four_four_armed, 4_4_armed, 4v4v4v4_armed',
					'- eight_two_lucky, 8_2_lucky, doubles_lucky',
					'- four_four_lucky, 4_4_lucky, 4v4v4v4_lucky'
				]);
				return SpelakoUtils::buildString([
					'%1$s 的起床战争%2$s统计信息:',
					$mode[0] == '' ? '等级: %3$s | 硬币: %4$s' : '',
					'拆床: %5$s | 被拆床: %6$s | 连胜: %7$s',
					'胜场: %8$s | 败场: %9$s | W/L: %10$.3f',
					'击杀: %11$s | 死亡: %12$s | K/D: %13$.3f',
					'终杀: %14$s | 终死: %15$s | FKDR: %16$.3f',
					'铁锭收集: %17$s | 金锭收集: %18$s',
					'钻石收集: %19$s | 绿宝石收集: %20$s',
					'此命令详细用法可在此处查看: %21$s/#help'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$mode[1],
					number_format($p['achievements']['bedwars_level']),
					number_format($p['stats']['Bedwars']['coins']),
					number_format($p['stats']['Bedwars'][$mode[0].'beds_broken_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'beds_lost_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'winstreak']),
					number_format($p['stats']['Bedwars'][$mode[0].'wins_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'losses_bedwars']),
					SpelakoUtils::div($p['stats']['Bedwars'][$mode[0].'wins_bedwars'], $p['stats']['Bedwars'][$mode[0].'losses_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'kills_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'deaths_bedwars']),
					SpelakoUtils::div($p['stats']['Bedwars'][$mode[0].'kills_bedwars'], $p['stats']['Bedwars'][$mode[0].'deaths_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'final_kills_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'final_deaths_bedwars']),
					SpelakoUtils::div($p['stats']['Bedwars'][$mode[0].'final_kills_bedwars'], $p['stats']['Bedwars'][$mode[0].'final_deaths_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'iron_resources_collected_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'gold_resources_collected_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'diamond_resources_collected_bedwars']),
					number_format($p['stats']['Bedwars'][$mode[0].'emerald_resources_collected_bedwars']),
					Spelako::INFO['link']
				]);
			case 'murdermystery':
			case 'mm':
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
					return SpelakoUtils::buildString([
						'未知的格式.',
						'正确格式 /hyp %1$s mm [模式序号 / 地图序号 / 模式+地图序号]',
						'模式序号可以是下列之一 (序号 - 模式名):',
						'1. 经典模式',
						'2. 双倍模式',
						'3. 刺客模式',
						'4. 感染模式',
						'地图序号和模式+地图序号详细用法可在此处查看:',
						'%2$s/#help',
					],[
						$p['displayname'],
						Spelako::INFO['link']
					]);
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
					($mode[0] != '_MURDER_ASSASSINS' && $mode[0] != '_MURDER_INFECTION') ? '侦探最快胜利: %22$s s | 杀手最快胜利: %23$s s' : '',
					($mode[0] == '' || $mode[0] == '_MURDER_INFECTION') ? '作为感染者击杀: %26$s | 作为幸存者击杀: %27$s' : '',
					($mode[0] == '' || $mode[0] == '_MURDER_INFECTION') ? '幸存者总存活: %9$s | 幸存者最久存活: %10$ss' : '',
					'此命令详细用法可在此处查看: %24$s/#help'
				], [
					self::getNetworkRank($p).$p['displayname'],
					self::getModeName($mode[1]),
					number_format($p['stats']['MurderMystery']['coins']),
					number_format($p['stats']['MurderMystery']['murderer_chance']),
					number_format($p['stats']['MurderMystery']['detective_chance']),
					number_format($p['stats']['MurderMystery']['wins'.$map[0].$mode[0]]),
					(SpelakoUtils::div($p['stats']['MurderMystery']['wins'.$map[0].$mode[0]]*100, $p['stats']['MurderMystery']['games'.$map[0].$mode[0]])),
					number_format($p['stats']['MurderMystery']['coins_pickedup'.$map[0].$mode[0]]),
					($survived_time = $p['stats']['MurderMystery']['total_time_survived_seconds'.$map[0].$mode[0]]*1000) != 0 ? SpelakoUtils::convertTime ($survived_time, false, 'i:s') : '00:00',
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
					Spelako::INFO['link'],
					self::getMapName($map[1]),
					number_format($p['stats']['MurderMystery']['kills_as_infected'.$map[0].$mode[0]]),
					number_format($p['stats']['MurderMystery']['kills_as_survivor'.$map[0].$mode[0]])
				]);
			case 'zombies':
			case 'zb':
				$map = match($args[3]) {
					'deadend', 'de' => [
						'mapIndex' => 0,
						'keySuffix' => '_deadend',
						'displayName' => '穷途末路',
						'bossKeys' => ['tnt', 'inferno', 'broodmother'],
						'bossDisplayNames' => ['炸弹僵尸', '炼狱', '巢穴之母']
					],
					'badblood', 'bb' => [
						'mapIndex' => 1,
						'keySuffix' => '_badblood',
						'displayName' => '坏血之宫',
						'bossKeys' => ['king_slime', 'wither', 'herobrine'],
						'bossDisplayNames' => ['史莱姆王', '凋零', 'HIM ']
					],
					'alienarcadium', 'aa' => [
						'mapIndex' => 2,
						'keySuffix' => '_alienarcadium',
						'displayName' => '外星游乐园',
						'bossKeys' => ['giant', 'the_old_one', 'giant_rainbow'], // No need to store the 4th boss here at this map
						'bossDisplayNames' => ['巨人', '长者', '彩虹巨人']
					],
					null => [
						'keySuffix' => '',
						'displayName' => '全部'
					],
					default => 'ERROR'
				};
				if($map == 'ERROR') return SpelakoUtils::buildString([
					'未知的地图.',
					'目前支持的地图可以是下列之一:',
					'- deadend, de',
					'- badblood, bb',
					'- alienarcadium, aa',
				]);
				if($map['mapIndex'] == 2) $args[4] = 'normal';
				$difficulty = match($args[4]) {
					'normal', 'norm' => [
						'keySuffix' => '_normal',
						'displayName' => '普通难度'
					],
					'hard' => [
						'keySuffix' => '_hard',
						'displayName' => '困难难度'
					],
					'rip' => [
						'keySuffix' => '_rip',
						'displayName' => '安息难度'
					],
					null => [
						'keySuffix' => '',
						'displayName' => '全局'
					],
					default => 'ERROR'
				};
				if($difficulty == 'ERROR') return SpelakoUtils::buildString([
					'未知的难度.',
					'目前支持的难度可以是下列之一:',
					'- normal, norm',
					'- hard',
					'- rip',
				]);
				return SpelakoUtils::buildString([
					'%1$s 的僵尸末日%2$s地图%3$s统计信息:',
					'生存总回合数: %4$s | 胜场: %5$s | 最佳回合: %6$s',
					'僵尸击杀数: %7$s | 复活玩家数: %8$s | 开门数: %9$s',
					'窗户修复数: %10$s | 被击倒次数: %11$s | 死亡数: %12$s',
					$difficulty['keySuffix'] ? '最快完成 10 回合: %13$s' : '', // This shows only when map and difficulty are set
					$difficulty['keySuffix'] ? '最快完成 20 回合: %14$s' : '',
					$difficulty['keySuffix'] ? '最快通关: %15$s' : '',
					!$map['keySuffix'] ? '射击: %16$s | 命中: %17$s | 爆头: %18$s' : ($map['mapIndex'] == 2 || !$difficulty['keySuffix'] ? '%19$s击杀: %20$s | %21$s击杀: %22$s' : ''),
					!$map['keySuffix'] ? '命中率: %23$.1f%% | 爆头率 %24$.1f%%' : ($map['mapIndex'] == 2 || !$difficulty['keySuffix'] ? '%25$s击杀: %26$s'.($map['mapIndex'] == 2 ? ' | 世界毁灭者击杀: %27$s' : '') : ''),
					'此命令详细用法可在此处查看: %28$s/#help'
				], [
					self::getNetworkRank($p).$p['displayname'],
					$map['displayName'],
					$difficulty['displayName'],
					number_format($p['stats']['Arcade']['total_rounds_survived_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['wins_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['best_round_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['zombie_kills_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['players_revived_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['doors_opened_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['windows_repaired_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['times_knocked_down_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					number_format($p['stats']['Arcade']['deaths_zombies'.$map['keySuffix'].$difficulty['keySuffix']]),
					SpelakoUtils::convertTime($p['stats']['Arcade']['fastest_time_10_zombies'.$map['keySuffix'].$difficulty['keySuffix']], true, 'H:i:s'),
					SpelakoUtils::convertTime($p['stats']['Arcade']['fastest_time_20_zombies'.$map['keySuffix'].$difficulty['keySuffix']], true, 'H:i:s'),
					SpelakoUtils::convertTime($p['stats']['Arcade']['fastest_time_30_zombies'.$map['keySuffix'].$difficulty['keySuffix']], true, 'H:i:s'),
					number_format($p['stats']['Arcade']['bullets_shot_zombies']),
					number_format($p['stats']['Arcade']['bullets_hit_zombies']),
					number_format($p['stats']['Arcade']['headshots_zombies']),
					$map['bossDisplayNames'][0],
					number_format($p['stats']['Arcade'][$map['bossKeys'][0].'_zombie_kills_zombies']),
					$map['bossDisplayNames'][1],
					number_format($p['stats']['Arcade'][$map['bossKeys'][1].'_zombie_kills_zombies']),
					100 * SpelakoUtils::div($p['stats']['Arcade']['bullets_hit_zombies'], $p['stats']['Arcade']['bullets_shot_zombies']),
					100 * SpelakoUtils::div($p['stats']['Arcade']['headshots_zombies'], $p['stats']['Arcade']['bullets_hit_zombies']),
					$map['bossDisplayNames'][2],
					number_format($p['stats']['Arcade'][$map['bossKeys'][2].'_zombie_kills_zombies']),
					$map['mapIndex'] == 2 ? number_format($p['stats']['Arcade']['world_ender_zombie_kills_zombies']) : '',
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
						$auctions = self::fetchSkyblockAuction($profile_id);
						if($auctions == 'ERROR_REQUEST_FAILED') return '查询请求发送失败, 请稍后再试.';
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
									SpelakoUtils::convertTime($item['end'], timezone_offset: self::TIMEZONE_OFFSET),
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
									SpelakoUtils::convertTime($item['end'], timezone_offset: self::TIMEZONE_OFFSET),
									time() < $item['end'] / 1000 ? '进行中' : '已结束'
								])
							);
						}
						return SpelakoUtils::buildString([
							'%1$s 的空岛生存 %2$s 存档物品拍卖信息:',
							'%3$s', // Body placeholder
							'当前展示 %4$d/%5$d 页.',
							'使用 /hyp %6$s sb a %7$s <页数> 来查看具体页数的拍卖信息.', 
							], [
								self::getNetworkRank($p).$p['displayname'],
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
						$profile_id = self::getSkyblockProfileID($profiles, $args[4] ? : 1);
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
							self::getNetworkRank($p).$p['displayname'],
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
				$r = self::fetchRecentGames($p['uuid']);
				if ($r == 'ERROR_REQUEST_FAILED') return '查询请求发送失败, 请稍后再试.';
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
						self::getGameName($r[$i]['gameType']),
						self::getModeName($r[$i]['mode']),
						($statusMap = self::getMapName($r[$i]['map'])) != '' ? $statusMap.'地图' : '',
						SpelakoUtils::convertTime($r[$i]['date'], format:'Y-m-d H:i:s', timezone_offset: self::TIMEZONE_OFFSET),
						$r[$i]['ended'] ? SpelakoUtils::convertTime($r[$i]['ended'], format:'Y-m-d H:i:s', timezone_offset: self::TIMEZONE_OFFSET) : ''
					]));
				}
				return SpelakoUtils::buildString([
					'%1$s 的最近游玩的游戏:',
					'%2$s',
					'当前展示 %3$d/%4$d 页.',
					'使用 /hyp %5$s r <页数> 来查看具体页数的游戏数据.'
				], [
					self::getNetworkRank($p).$p['displayname'],
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
							($parkourTime = $p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$i]][0]['timeTook']) != null ? SpelakoUtils::convertTime($parkourTime, false, 'i:s').'.'. sprintf('%03s', $parkourTime % 1000) : '未' . ($p['parkourCheckpointBests'][self::PARKOUR_LOBBY_CODE[$i]][0] != null ? '完全' : '') . '完成'
						]));
					}
					return SpelakoUtils::buildString([
						'%1$s 的跑酷信息(序号 - 中文名):',
						'%2$s',
						'使用 /hyp %3$s p <序号> 来查看包括每个存档点的纪录和总纪录的创立时间的详细信息.'
					], [
						self::getNetworkRank($p).$p['displayname'],
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
							$checkPointTime != null ? SpelakoUtils::convertTime($checkPointTime, false, 'i:s').'.'. sprintf('%03s', $checkPointTime % 1000) : '未完成'
						]));
					}
					return SpelakoUtils::buildString([
						self::PARKOUR_LOBBY_CHECKPOINT[$lobby] != -1 ? '%1$s 的%2$s跑酷每个存档点最佳记录:':'%1$s 的%2$s跑酷详细信息 (该跑酷无存档点):',
						'%3$s',
						'完成跑酷用时: %4$s',
						$p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeTook'] != null ? '记录创建于: %5$s' : null
					], [
						self::getNetworkRank($p).$p['displayname'],
						self::PARKOUR_LOBBY_NAME[$lobby],
						SpelakoUtils::buildString($placeholder),
						($parkourTime = $p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeTook']) != null ? SpelakoUtils::convertTime($parkourTime, false, 'i:s').'.'. sprintf('%03s', $parkourTime % 1000) : '未完成' ,
						SpelakoUtils::convertTime($p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeTook']+$p['parkourCompletions'][self::PARKOUR_LOBBY_CODE[$lobby]][0]['timeStart'], format:'Y-m-d H:i:s', timezone_offset: self::TIMEZONE_OFFSET)
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
					'- uhc',
					'- megawalls, mw',
					'- blitzsg, bsg, hungergames',
					'- zombies, zb',
					'- skyblock, sb',
					'- parkour, p',
					'更多分类正在开发中...'
				], [
					self::USAGE
				]);
				$online = isset($p['lastLogout']) && ($p['lastLogout'] < $p['lastLogin']);
				$s = $online ? self::fetchStatus($p['uuid']) : false;
				$statusAvailable = ($s && $s['online'] == true);
				return SpelakoUtils::buildString([
					'%1$s 的 Hypixel 信息:',
					'等级: %2$.3f | 人品: %3$s',
					'成就点数: %4$s | 小游戏胜场: %5$s',
					'完成任务: %6$s | 完成挑战: %7$s',
					'获得硬币: %8$s',
					'最近游玩: %9$s',
					'首次登录: %10$s',
					'上次登录: %11$s',
					($online ? '● 此玩家在线了 %12$s, '.($s ? ($statusAvailable ? '当前在%13$s%14$s%15$s中.' : '该玩家在 API 设置中阻止了获取当前游戏的请求. ' ) : '获取当前游戏时出错.') : '上次退出: %12$s'),
					'此命令详细用法可在此处查看: %16$s/#help'
				], [
					self::getNetworkRank($p).$p['displayname'],
					self::getNetworkLevel($p['networkExp']),
					number_format($p['karma']),
					number_format($p['achievementPoints']),
					number_format($p['achievements']['general_wins']),
					number_format($p['achievements']['general_quest_master']),
					number_format($p['achievements']['general_challenger']),
					number_format($p['achievements']['general_coins']),
					$p['mostRecentGameType'] != null ? self::getGameName($p['mostRecentGameType']) : '未知',
					$p['firstLogin'] != null ? SpelakoUtils::convertTime($p['firstLogin'], format:'Y-m-d H:i:s', timezone_offset: self::TIMEZONE_OFFSET) : '未知',
					$p['lastLogin'] != null ? SpelakoUtils::convertTime($p['lastLogin'], format:'Y-m-d H:i:s', timezone_offset: self::TIMEZONE_OFFSET) : '未知',
					$online ? SpelakoUtils::convertTime(time() - $p['lastLogin'] / 1000, true, 'H:i:s') : ($p['lastLogout'] != null ? SpelakoUtils::convertTime($p['lastLogout'], format:'Y-m-d H:i:s', timezone_offset: self::TIMEZONE_OFFSET) : '未知'),
					$statusAvailable ? self::getGameName($s['gameType']) : '',
					$statusAvailable ? self::getModeName($s['mode']) : '',
					$statusAvailable ? (($statusMap = self::getMapName($s['map'])) != '' ? $statusMap.'地图' : '') : '',
					Spelako::INFO['link']
				]);
		}
	}

	private static function fetchGeneralStats($player) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/player', ['key' => self::API_KEY, 'name' => $player], 300);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		if(($result = json_decode($src, true)['player']) == null) {
			return 'ERROR_PLAYER_NOT_FOUND';
		}
		return $result;
	}

	private static function fetchGuild($playerUuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/guild', ['key' => self::API_KEY, 'player' => $playerUuid], 300);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		if(($result = json_decode($src, true)['guild']) == null) {
			return 'ERROR_GUILD_NOT_FOUND';
		}
		return $result;
	}
	
	private static function fetchRecentGames($playerUuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/recentgames', ['key' => self::API_KEY, 'uuid' => $playerUuid], 45);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		if(($result = json_decode($src, true)['games']) == null) {
			return 'ERROR_RECENT_GAMES_NOT_FOUND';
		}
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
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/skyblock/auction', ['key' => self::API_KEY, 'profile' => $profile], 300);
		if(!$src) return 'ERROR_REQUEST_FAILED';
		if(($result = json_decode($src, true)['auctions']) == null) return 'ERROR_AUCTIONS_NOT_FOUND';
		return array_reverse($result);
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
		if($runecrafting)
			$levelingLadder = [0, 50, 150, 275, 435, 635, 885, 1200, 1600, 2100, 2725, 3510, 4510, 5760, 7325, 9325, 11825, 14950, 18950, 23950, 30200, 38050, 47850, 60100, 75400, 94450];
		else
			$levelingLadder = [0, 50, 175, 375, 675, 1175, 1925, 2925, 4425, 6425, 9925, 14925, 22425, 32425, 47425, 67425, 97425, 147425, 222425, 322425, 522425, 822425, 1222425, 1722425, 2322425, 3022425, 3822425, 4722425, 5722425, 6822425, 8022425, 9322425, 10722425, 12222425, 13822425, 15522425, 17322425, 19222425, 21222425, 23322425, 25522425, 27822425, 30222425, 32722425, 35322425, 38072425, 40972425, 44072425, 47472425, 51172425, 55172425, 59472425, 64072425, 68972425, 74172425, 79672425, 85472425, 91572425, 91972425, 104672425, 111672425];
		foreach($levelingLadder as $curlevel => $required)
			if($exp > $required) $level = $curlevel;
		return $level;
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
			if ($exp < 0) return $level + 1 + $exp / $need;
			else $level ++;
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
			'MAIN' => '主',
			default => $typeName
		};
		
	}
	
	private static function getMapName($mapName) {
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
			'BEDWARS_CASTLE' => '40v40城池攻防战模式',
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

			'PIT', null => '',
			'all' => '全局',
			default => ' '.$modeName.' '
		};
	}
	
	// Clears the Minecraft formatting string of a string
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
