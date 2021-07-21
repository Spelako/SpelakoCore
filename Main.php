<?php
require_once('utils/SpelakoUtils.php');

function onMessage($fromAccount, $msg) {
	if(isBlacklisted($fromAccount)) return;
	$args_nofilter = explode(' ', $msg);
	$args = array_values(array_filter($args_nofilter));

	$args[0] = strtolower($args[0]);

	switch($args[0]) {
		// 以下为普通指令
		case '/help':
			return(
				'Spelako 指令列表:'.PHP_EOL.
				'/help - 查看此指令列表'.PHP_EOL.
				'/spelako - 显示机器相关信息'.PHP_EOL.
				'/mojang <玩家/UUID> - 获取指定玩家的 Mojang 账号信息'.PHP_EOL.
				'/hypixel <玩家> [分类] - 获取指定玩家的 Hypixel 信息'.PHP_EOL.
				'/syuu ... - 获取 SyuuNet 的玩家信息或排行榜'.PHP_EOL.
				'欲查看指令详细用法, 请前往 spelako.github.io/#help'
			);
		case '/mojang':
			if(!getAvailability($fromAccount)) return '你查询的频率过高, 喝杯茶休息一下吧!';
			if(isset($args[1])){
				userExecute($fromAccount);
				require_once('utils/MojangStats.php');
				$p = mojang_getstats($args[1]);
				if($p){
					$buffer .= (
						'根据提供的 '.$p['method'].' 所查找到的 Mojang 账号信息:'.PHP_EOL.
						'UUID: '.$p['uuid'].PHP_EOL.
						'名称: '
					);
					if(count($p['names']) > 1){
						foreach ($p['names'] as $key => $value) {
							$num = count($p['names']) - $key;
							$buffer .= ($num.'. '.$value['name']);
							if($key != count($p['names']) - 1) $buffer .= PHP_EOL;
						}
					} else $buffer .= $p['names'][0]['name'];
					return $buffer;
				}
				else {
					return '无法获取此玩家的信息.';
				}
			}
			else {
				return '正确用法: /mojang <玩家>';
			}
		case '/hypixel':
			if(!getAvailability($fromAccount)) return('你查询的频率过高, 喝杯茶休息一下吧!');
			if(isset($args[1])){
				userExecute($fromAccount);
				require_once('utils/HypixelStats.php');
				
				$apiKey = '[在此输入 Hypixel API Key]';
				$p = hypixel_getstats($apiKey, $args[1]);
				if($p){
					switch(strtolower($args[2])){
						case 'guild':
						case 'g':
							$g = hypixel_getguild($apiKey, $p['player']['uuid']);
							if($g) {
								return(
									getRank($p).$p['player']['displayname'].' 的 公会 信息:'.PHP_EOL.
									'公会名称: '.$g['guild']['name'].PHP_EOL.
									'创立时间: '.toDate($g['guild']['created']).PHP_EOL.
									'等级: '.getLevelGuild($g['guild']['exp']).' | 标签: ['.$g['guild']['tag'].']'.PHP_EOL.
									'成员: '.count($g['guild']['members']).' | 最高在线: '.$g['guild']['achievements']['ONLINE_PLAYERS']
								);
							}
							else {
								return '无法找到玩家 '.$p['player']['displayname'].' 的公会信息.';
							}
						case 'blitzsg':
						case 'bsg':
						case 'hungergames':
							return(
								getRank($p).$p['player']['displayname'].' 的 Biltz SG 信息:'.PHP_EOL.
								'游玩次数: '.$p['player']['stats']['HungerGames']['games_played'].' | 硬币: '.$p['player']['stats']['HungerGames']['coins'].' | 开箱数: '.$p['player']['stats']['HungerGames']['chests_opened'].PHP_EOL.
								'击杀: '.$p['player']['stats']['HungerGames']['kills'].' | 死亡: '.$p['player']['stats']['HungerGames']['deaths'].' | K/D: '.round($p['player']['stats']['HungerGames']['kills']/$p['player']['stats']['HungerGames']['deaths'], 3)
							);
						case 'uhc':
							return(
								getRank($p).$p['player']['displayname'].' 的 UHC 信息:'.PHP_EOL.
								'分数: '.$p['player']['stats']['UHC']['score'].' | 硬币: '.$p['player']['stats']['UHC']['coins'].' | 胜场: '.$p['player']['stats']['UHC']['wins'].PHP_EOL.
								'击杀: '.$p['player']['stats']['UHC']['kills'].' | 死亡: '.$p['player']['stats']['UHC']['deaths'].' | K/D: '.round($p['player']['stats']['UHC']['kills']/$p['player']['stats']['UHC']['deaths'], 3)
							);
						case 'megawalls':
						case 'mw':
							return(
								getRank($p).$p['player']['displayname'].' 的 Mega Walls 信息:'.PHP_EOL.
								'凋零伤害: '.$p['player']['stats']['Walls3']['wither_damage'].' | 职业: '.$p['player']['stats']['Walls3']['chosen_class'].' | 硬币: '.$p['player']['stats']['Walls3']['coins'].PHP_EOL.
								'击杀: '.$p['player']['stats']['Walls3']['kills'].' | 助攻: '.$p['player']['stats']['Walls3']['assists'].' | 死亡: '.$p['player']['stats']['Walls3']['deaths'].' | K/D: '.round($p['player']['stats']['Walls3']['kills']/$p['player']['stats']['Walls3']['deaths'], 3).PHP_EOL.
								'终杀: '.$p['player']['stats']['Walls3']['final_kills'].' | 终助: '.$p['player']['stats']['Walls3']['final_assists'].' | 终死: '.$p['player']['stats']['Walls3']['final_deaths'].' | FKDR: '.round($p['player']['stats']['Walls3']['final_kills']/$p['player']['stats']['Walls3']['final_deaths'], 3).PHP_EOL.
								'胜场: '.$p['player']['stats']['Walls3']['wins'].' | 败场: '.$p['player']['stats']['Walls3']['losses'].' | W/L: '.round($p['player']['stats']['Walls3']['wins']/$p['player']['stats']['Walls3']['losses'], 3)
							);
						case 'skywars':
						case 'sw':
							return(
								getRank($p).$p['player']['displayname'].' 的 Skywars 信息:'.PHP_EOL.
								'等级: '.clearColorFormat($p['player']['stats']['SkyWars']['levelFormatted']).' | 硬币: '.$p['player']['stats']['SkyWars']['coins'].' | 助攻: '.$p['player']['stats']['SkyWars']['assists'].PHP_EOL.
								'击杀: '.$p['player']['stats']['SkyWars']['kills'].' | 死亡: '.$p['player']['stats']['SkyWars']['deaths'].' | K/D: '.round($p['player']['stats']['SkyWars']['kills']/$p['player']['stats']['SkyWars']['deaths'], 3).PHP_EOL.
								'胜场: '.$p['player']['stats']['SkyWars']['wins'].' | 败场: '.$p['player']['stats']['SkyWars']['losses'].' | W/L: '.round($p['player']['stats']['SkyWars']['wins']/$p['player']['stats']['SkyWars']['losses'], 3)
							);
						case 'bedwars':
						case 'bw':
							return(
								getRank($p).$p['player']['displayname'].' 的 Bedwars 信息:'.PHP_EOL.
								'等级: '.$p['player']['achievements']['bedwars_level'].' | 硬币: '.$p['player']['stats']['Bedwars']['coins'].' | 拆床: '.$p['player']['stats']['Bedwars']['beds_broken_bedwars'].PHP_EOL.
								'胜场: '.$p['player']['stats']['Bedwars']['wins_bedwars']. ' | 败场: '.$p['player']['stats']['Bedwars']['losses_bedwars'].' | W/L: '.round($p['player']['stats']['Bedwars']['wins_bedwars']/$p['player']['stats']['Bedwars']['losses_bedwars'], 3).PHP_EOL.
								'击杀: '.$p['player']['stats']['Bedwars']['kills_bedwars']. ' | 死亡: '.$p['player']['stats']['Bedwars']['deaths_bedwars'].' | K/D: '.round($p['player']['stats']['Bedwars']['kills_bedwars']/$p['player']['stats']['Bedwars']['deaths_bedwars'], 3).PHP_EOL.
								'终杀: '.$p['player']['stats']['Bedwars']['final_kills_bedwars']. ' | 终死: '.$p['player']['stats']['Bedwars']['final_deaths_bedwars'].' | FKDR: '.round($p['player']['stats']['Bedwars']['final_kills_bedwars']/$p['player']['stats']['Bedwars']['final_deaths_bedwars'], 3)
							);
						case 'zombies':
						case 'zombie':
						case 'zb':
							switch($args[3]) {
								case 'de':
								case 'deadend':
									$statsAdd .= '_deadend';
									$map = '穷途末路地图';
									break;
								case 'bb':
								case 'badblood':
									$statsAdd .= '_badblood';
									$map = '坏血之宫地图';
									break;
								case 'aa':
								case 'alienarcadium':
									$statsAdd .= '_alienarcadium';
									$map = '外星游乐园地图';
									break;
								default:
									$map = '全部地图';
							}
							switch($args[4]) {
								case 'norm':
								case 'normal':
									$statsAdd .= '_normal';
									$difficulty = '普通难度';
									break;
								case 'hard':
									$statsAdd.='_hard';
									$difficulty = '困难难度';
									break;
								case 'rip':
									$statsAdd.='_rip';
									$difficulty = '安息难度';
									break;
								default:
									$difficulty = '全局';
								
							}
							$returnString =(
								getRank($p).$p['player']['displayname'].' 的 Zombies '.$map.$difficulty.'统计信息:'.PHP_EOL.
								'生存总回合数: '.$p['player']['stats']['Arcade']['total_rounds_survived_zombies'.$statsAdd].' | 胜场: '.$p['player']['stats']['Arcade']['wins_zombies'.$statsAdd].' | 最佳波数: '.$p['player']['stats']['Arcade']['best_round_zombies'.$statsAdd].PHP_EOL.
								'僵尸击杀数: '.$p['player']['stats']['Arcade']['zombie_kills_zombies'.$statsAdd]. ' | 复活玩家数: '.$p['player']['stats']['Arcade']['players_revived_zombies'.$statsAdd].' | 开门数: '.$p['player']['stats']['Arcade']['doors_opened_zombies'.$statsAdd].PHP_EOL.
								'窗户修复数: '.$p['player']['stats']['Arcade']['windows_repaired_zombies'.$statsAdd]. ' | 被击倒次数: '.$p['player']['stats']['Arcade']['times_knocked_down_zombies'.$statsAdd].' | 死亡数: '.$p['player']['stats']['Arcade']['deaths_zombies'.$statsAdd]
								);
							if($statsAdd == '')
								$returnString.=(
									PHP_EOL.'欲查询玩家 Zombies 各地图的详细信息, 请使用此指令:'.PHP_EOL.
									'/hypixel <玩家> <分类> <地图名> [难度]'.PHP_EOL.
									'"分类"可以是下列之一: '.PHP_EOL.
									'- zombies, zb'.PHP_EOL.
									'"地图名" 可以是下列之一: '.PHP_EOL.
									'- deadEnd, de'.PHP_EOL.
									'- badBlood, bb'.PHP_EOL.
									'- alienArcadium, aa'.PHP_EOL.
									'"难度" 可以是下列之一: '.PHP_EOL.
									'- normal, norm'.PHP_EOL.
									'- hard'.PHP_EOL.
									'- rip'
								);
							return($returnString);
						case 'skyblock':
						case 'sb':
							$profiles = $p['player']['stats']['SkyBlock']['profiles'];
							$buffer .= (
								'欲查询玩家 Skyblock 信息, 请使用此指令:'.PHP_EOL.
								'/hypixel <玩家> <分类> [存档名/序号]'.PHP_EOL.
								'"分类" 可以是下列之一: '.PHP_EOL.
								'- skyblockSkills, sbs'.PHP_EOL.
								'- skyblockAuction, sba'.PHP_EOL.
								'此玩家有 '.count($profiles).'个存档 (序号 - 存档名):'
							);
							foreach(array_keys($profiles) as $k => $v) {
								$buffer .= PHP_EOL.($k + 1).' - '.$profiles[$v]['cute_name'];
							}
							return $buffer;
						case 'skyblockskills':
						case 'sbs':
							$profiles = $p['player']['stats']['SkyBlock']['profiles'];
							$profile_id = queryProfileSB($profiles, isset($args[3])? $args[3] : 1);
							if($profile_id) {
								$profile = hypixel_skyblock_profile($apiKey, $profile_id);
								$member = $profile['profile']['members'][$p['player']['uuid']];

								$taming = $member['experience_skill_taming'];
								$farming = $member['experience_skill_farming'];
								$mining = $member['experience_skill_mining'];
								$combat = $member['experience_skill_combat'];
								$foraging = $member['experience_skill_foraging'];
								$fishing = $member['experience_skill_fishing'];
								$enchanting = $member['experience_skill_enchanting'];
								$alchemy = $member['experience_skill_alchemy'];
								$carpentry = $member['experience_skill_carpentry'];
								$runecrafting = $member['experience_skill_runecrafting'];

								// 这里判断玩家是否允许了 API 访问, 是则使用 skyblock profile api 里的数据, 否则使用 player api 里的数据.
								// 这里的判断类似于 https://github.com/LeaPhant/skyblock-stats/blob/5591a32/src/lib.js 的第 1121 行至 1215 行.
								if(isset($taming)
								|| isset($farming)
								|| isset($mining)
								|| isset($combat)
								|| isset($foraging)
								|| isset($fishing)
								|| isset($enchanting)
								|| isset($alchemy)
								|| isset($carpentry)
								|| isset($runecrafting)) {
									return(
										getRank($p).$p['player']['displayname'].' 的 SkyBlock 技能信息:'.PHP_EOL.
										'Taming: '.getLevelSB($taming).' | Farming: '.getLevelSB($farming).PHP_EOL.
										'Mining: '.getLevelSB($mining).' | Combat: '.getLevelSB($combat).PHP_EOL.
										'Foraging: '.getLevelSB($foraging).' | Fishing: '.getLevelSB($fishing).PHP_EOL.
										'Enchanting: '.getLevelSB($enchanting).' | Alchemy: '.getLevelSB($alchemy).PHP_EOL.
										'Carpentry: '.getLevelSB($carpentry).' | Runecrafting: '.getLevelSB($runecrafting, true).PHP_EOL.
										'当前存档: '.$profiles[$profile_id]['cute_name']
									);
								}
								else {
									return(
										getRank($p).$p['player']['displayname'].' 的 SkyBlock 技能信息:'.PHP_EOL.
										'Taming: '.$p['player']['achievements']['skyblock_domesticator'].' | Farming: '.$p['player']['achievements']['skyblock_harvester'].PHP_EOL.
										'Mining: '.$p['player']['achievements']['skyblock_excavator'].' | Combat: '.$p['player']['achievements']['skyblock_combat'].PHP_EOL.
										'Foraging: '.$p['player']['achievements']['skyblock_gatherer'].' | Fishing: '.$p['player']['achievements']['skyblock_angler'].PHP_EOL.
										'Enchanting: '.$p['player']['achievements']['skyblock_augmentation'].' | Alchemy: '.$p['player']['achievements']['skyblock_concoctor'].PHP_EOL.
										'当前存档: '.$profiles[$profile_id]['cute_name'].PHP_EOL.
										'注意: 无法访问玩家技能 API, 信息可能不准确.'
									);
								}
							}
							else {
								return '无效的存档名/序号.';
							}
						case 'skyblockauction':
						case 'sba':
							$profiles = $p['player']['stats']['SkyBlock']['profiles'];
							$profile_id = queryProfileSB($profiles, isset($args[3])? $args[3] : 1);
							if($profile_id) {
								$auctions = hypixel_skyblock_auction($apiKey, $profile_id)['auctions'];

								if($auctions) {
									$items = array();
									foreach($auctions as $item) if(!$item['claimed']) array_push($items, $item);
									/*
										草, 我是憨批吧, 我当时拼接字符串的时候是怎么把 ".=" 打成 "=+" 的? 要是打成 "+=" 倒还能原谅.
										关键是这个错误的 ".+" 自从被我写下以来, 硬是坐等了一个多月才被我发现!
										我说查询拍卖的时候怎么一直报错呢, 还以为是 Hypixel API 改格式了. 现在修了下, 跑起来了.
										下次写新功能一定要好好测试再发布了! 真鸡儿丢人(这是最基本的错误吧)...
									*/
									$buffer .= getRank($p).$p['player']['displayname'].' 的 SkyBlock 物品拍卖信息:'.PHP_EOL;
									if(count($items)) {
										foreach($items as $k => $v) {
											if($k >= 5) {
												$buffer .= '...等共 '.count($items).' 件正在拍卖的物品'.PHP_EOL;
												break;
											}
											$buffer .= (
												'===> '.$v['item_name'].' <==='.PHP_EOL.
												($v['bin'] ? '一口价: ' : ('最高出价: '.($v['highest_bid_amount'] ? : '无').' | 起拍价: ')).$v['starting_bid'].PHP_EOL.
												'结束时间: '.toDate($v['end']).PHP_EOL
											);
										}
										return $buffer;
									}
									else {
										return '此存档没有正在拍卖的物品.'.((count($profiles) > 1) ? PHP_EOL.'你可以尝试查询此玩家的其他存档.' : '').PHP_EOL;
									}
									return '当前存档: '.$p['player']['stats']['SkyBlock']['profiles'][$profile_id]['cute_name'];
								}
								else {
									return(
										getRank($p).$p['player']['displayname'].' 的 SkyBlock 物品拍卖信息:'.PHP_EOL.
										'此存档没有正在拍卖的物品.'.((count($profiles) > 1) ? PHP_EOL.'你可以尝试查询此玩家的其他存档.' : '').PHP_EOL.
										'当前存档: '.$p['player']['stats']['SkyBlock']['profiles'][$profile_id]['cute_name']
									);
								}
							}
							else {
								return '无效的存档名/序号.';
							}
						default:
							if(isset($args[2])) $buffer .= '未知的分类, 已跳转至默认分类.'.PHP_EOL;
							$buffer .= (
								getRank($p).$p['player']['displayname'].' 的 Hypixel 信息:'.PHP_EOL.
								'等级: '.getLevel($p['player']['networkExp']).' | 人品: '.$p['player']['karma'].PHP_EOL.
								'首次登录: '.toDate($p['player']['firstLogin']).PHP_EOL.
								'上次登录: '.toDate($p['player']['lastLogin']).PHP_EOL.
								(
									(isset($p['player']['lastLogout']) && ($p['player']['lastLogout'] < $p['player']['lastLogin'])) ?
									'● 此玩家当前在线, 游玩了 '.date('H:i:s', time() - 28800 - ($p['player']['lastLogin']) / 1000) :
									'上次退出: '.toDate($p['player']['lastLogout'])
								)
							);
							return $buffer;
					}
				}
				else {
					return '无法获取此玩家的信息.';
				}
			}
			else {
				return (
					'正确用法: /hypixel <玩家> [分类]'.PHP_EOL.
					'"分类" 可以是下列之一:'.PHP_EOL.
					'- guild, g'.PHP_EOL.
					'- bedwars, bw'.PHP_EOL.
					'- skywars, sw'.PHP_EOL.
					'- uhc'.PHP_EOL.
					'- megawalls, mw'.PHP_EOL.
					'- blitzsg, bsg, hungergames'.PHP_EOL.
					'- zombies, zb'.PHP_EOL.
					'- skyblock, sb'
				);
			}
		case '/syuu':
			if(!getAvailability($fromAccount)) return '你查询的频率过高, 喝杯茶休息一下吧!';
			require_once('utils/SyuuStats.php');

			switch($args[1]) {
				case 'user':
					if(isset($args[2])) {
						userExecute($fromAccount);
						$p = syuu_getparsedplayerstats($args[2]);
						if($p) {
							$buffer .= $args[2].' 的 SyuuNet 排位信息:';
							foreach($p['RankedData'] as $k => $v) {
								$buffer .= PHP_EOL.'['.$k.'] Elo: '.$v['Elo'].' | 胜: '.$v['Win'].' | 败: '.$v['Lose'];
							}
							return $buffer;
						}
						else {
							return '无法获取此玩家的信息.';
						}
					}
					else {
						return '正确用法: /syuu user <玩家>';
					}
				case 'lb':
					if(isset($args[2])) {
						userExecute($fromAccount);
						$lb = syuu_getleaderboard_practice();
						if($lb) {
							$category = syuu_getcategoryname($args[2]);
							if(!$category) {
								$category = 'Sharp2Prot2';
								$buffer .= '未知的分类, 已跳转至 Sharp2Prot2 分类.'.PHP_EOL;
							}
							$buffer .= 'SyuuNet 的 '.$category.' 排行榜:'.PHP_EOL;
							foreach($lb[$category] as $k => $v) {
								$buffer .= ($k + 1).'. '.$v['lastknownname'].' - '.$v['rankedelo'];
								if($k != count($lb[$category]) -1) $buffer .= PHP_EOL;
							}
							return $buffer;
						}
						else {
							return '无法解析来自 SyuuNet 的数据.';
						}
					}
					else {
						return(
							'正确用法: /syuu lb <分类>'.PHP_EOL.
							'"分类" 可以是下列之一:'.PHP_EOL.
							'- sharp2prot2, s2p2'.PHP_EOL.
							'- sharp4prot3, s4p3'.PHP_EOL.
							'- archer, bow'.PHP_EOL.
							'- nodelay, combo'.PHP_EOL.
							'- builduhc, buhc'.PHP_EOL.
							'- sumo'.PHP_EOL.
							'- finaluhc'.PHP_EOL.
							'... 欲查看完整列表, 请访问帮助文档.'
						);
					}
				default:
					return '正确用法: /syuu user <玩家> 或 /syuu lb <分类>';
			}
		case '/spelako':
			return(
				'General:'.PHP_EOL.
				'	Dev: Peaksol'.PHP_EOL.
				'	Version: 1.5.0'.$version.PHP_EOL.
				'	First Created: 2019/6/18'.PHP_EOL.
				'Credits:'.PHP_EOL.
				'	Hypixel Stats API by Hypixel'.PHP_EOL.
				'	Mojang Player Profile API by Mojang'.PHP_EOL.
				'For more information, please visit:'.PHP_EOL.
				'	https://spelako.github.io/'
			);
		// 以下为机器人管理员指令
		case '/admin':
			if(!isStaff($fromAccount)) return;
			return(
				'Spelako 管理员指令列表:'.PHP_EOL.
				'/admin - 查看此列表'.PHP_EOL.
				'/echo <文本> - 使机器人复读消息'.PHP_EOL.
				'/botstats - 获取机器人的相关统计数据'.PHP_EOL.
				'/clearcache - 清理所有 cache 目录下的缓存数据'.PHP_EOL.
				'/ignore <QQ> - 屏蔽指定用户的指令'.PHP_EOL.
				'/unignore <QQ> - 解除对指定用户的指令的屏蔽'
			);
		case '/echo':
			if(!isStaff($fromAccount)) return;
			if(isset($args_nofilter[1])){
				unset($args_nofilter[0]);
				return implode(' ', $args_nofilter);
			}
			else{
				return(
				'那只敏捷的棕毛狐狸跃过了这条懒洋洋的狗.'.PHP_EOL.
				'The quick brown fox jumps over the lazy dog.'
				);
			}
		case '/botstats':
			if(!isStaff($fromAccount)) return;
			return(
				'目前 Spelako 缓存了 '.count(getCooldowns()).' 人的使用记录,'.PHP_EOL.
				'缓存文件共 '.dcount('cache/').' 个, 占用存储空间 '.dsize('cache/').'.'.PHP_EOL.
				'有 '.count(getBlacklist()).' 个用户被列入黑名单.'
			);
		case '/clearcache':
			if(!isStaff($fromAccount)) return;
			deldir('cache/');
			return '已清除所有缓存文件.';
		case '/ignore':
			if(!isStaff($fromAccount)) return;
			if(isset($args[1])) {
				if(blacklistAdd($args[1]))
					return 'Spelako 将不再处理用户 '.$args[1].' 的指令.';
				else
					return '该用户已经被 Spelako 屏蔽.';
			}
			else {
				return '正确用法: /ignore <QQ号>';
			}
		case '/unignore':
			if(!isStaff($fromAccount)) return;
			if(isset($args[1])) {
				if(blacklistRemove($args[1]))
					return 'Spelako 将继续处理用户 '.$args[1].' 的指令.';
				else
					return '该用户没有被 Spelako 屏蔽.';
			}
			else {
				return '正确用法: /unignore user <QQ号>';
			}
		default:
			$sCmd = similarCommand($args[0], array('help', 'mojang', 'hypixel', 'syuu', 'spelako', 'skyblock', 'lv', 'stats'));
			if($sCmd) {
				return(
					'你可能想输入此指令: /'.$sCmd.PHP_EOL.
					'但是你将其输入为了 "'.$args[0].'"'
				);
			}
	}
}
?>