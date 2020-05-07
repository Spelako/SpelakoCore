<?php
function onMessage($fromAccount = 'unknown', $fromGroup = 'unknown', $msg = '/help') {

if(isBlacklisted($fromGroup, true) || isBlacklisted($fromAccount)) exit();
$args_nofilter = explode(' ', $msg);
$args = array_values(array_filter($args_nofilter));

$args[0] = strtolower($args[0]);

switch($args[0]) {
	// 以下为普通指令
	case '/help':
		echo
		'Spelako 指令列表:'.PHP_EOL.
		'/help - 查看此指令列表'.PHP_EOL.
		'/spelako - 显示机器相关信息'.PHP_EOL.
		'/virus [省份] - 查询新型肺炎的实时动态'.PHP_EOL.
		'/mojang <玩家/UUID> - 获取指定玩家的 Mojang 账号信息'.PHP_EOL.
		'/server <地址> [端口] - 获取指定地址的 Minecraft 服务器信息'.PHP_EOL.
		'/hypixel <玩家> [分类] - 获取指定玩家的 Hypixel 信息';
		break;
	case '/mojang':
		if(!getAvailability($fromAccount)) exit('你查询的频率过高, 喝杯茶休息一下吧!');
		if(isset($args[1])){
			userExecute($fromAccount);
			require_once('utils/MojangStats.php');
			$p = mojang_getstats($args[1]);
			if($p){
				echo
				'根据提供的 '.$p['method'].' 所查找到的 Mojang 账号信息:'.PHP_EOL.
				'UUID: '.$p['uuid'].PHP_EOL.
				'名称: ';
				if(count($p['names']) > 1){
					foreach ($p['names'] as $key => $value) {
						$num = count($p['names']) - $key;
						echo ($num.'. '.$value['name']);
						if($key != count($p['names']) - 1) echo PHP_EOL;
					}
				} else echo $p['names'][0]['name'];
			}
			else {
				echo('无效的玩家名.');
			}
		}
		else {
			echo('正确用法: /mojang <玩家>');
		}
		break;
	case '/server':
		if(!getAvailability($fromAccount)) exit('你查询的频率过高, 喝杯茶休息一下吧!');
		if(isset($args[1])){
			userExecute($fromAccount);
			require_once('utils/MCPing.php');
			!isset($args[2]) && $args[2] = 25565;
			$p = new MCPing();
			$putils = new MCPingUtils();
			$p = ($p->GetStatus($args[1], (int)$args[2])->Response());
			if($p['online']) {
				echo
				'IP 地址: '.$p['address'].':'.$p['port'].PHP_EOL.
				'版本: '.$p['version'].' (协议 '.$p['protocol'].')'.PHP_EOL.
				'人数: '.$p['players'].'/'.$p['max_players'].PHP_EOL.
				'延迟: '.$p['ping'].'ms'.PHP_EOL.
				'MOTD:'.PHP_EOL.
				$putils->ClearMotd($p['motd']);
			}
			else {
				echo('无法连接到服务器! ('.$p['error'].')');
			}
		}
		else {
			echo('正确用法: /server <地址> [端口]');
		}
		break;
	case '/hypixel':
		if(!getAvailability($fromAccount)) exit('你查询的频率过高, 喝杯茶休息一下吧!');
		if(isset($args[1])){
			userExecute($fromAccount);
			require_once('utils/HypixelStats.php');
			$apiKey = 'a765d546-4697-4e47-9b90-7e9c27b001b7';
			$p = hypixel_getstats($apiKey, $args[1]);
			if($p){
				switch(strtolower($args[2])){
					case 'blitzsg':
					case 'bsg':
					case 'hungergames':
						echo
						getRank($p).$p['player']['displayname'].' 的 Biltz SG 信息:'.PHP_EOL.
						'游玩次数: '.$p['player']['stats']['HungerGames']['games_played'].' | 硬币: '.$p['player']['stats']['HungerGames']['coins'].' | 开箱数: '.$p['player']['stats']['HungerGames']['chests_opened'].PHP_EOL.
						'击杀: '.$p['player']['stats']['HungerGames']['kills'].' | 死亡: '.$p['player']['stats']['HungerGames']['deaths'].' | K/D: '.round($p['player']['stats']['HungerGames']['kills']/$p['player']['stats']['HungerGames']['deaths'], 3);
						break;
					case 'uhc':
						echo
						getRank($p).$p['player']['displayname'].' 的 UHC 信息:'.PHP_EOL.
						'分数: '.$p['player']['stats']['UHC']['score'].' | 硬币: '.$p['player']['stats']['UHC']['coins'].' | 胜场: '.$p['player']['stats']['UHC']['wins'].PHP_EOL.
						'击杀: '.$p['player']['stats']['UHC']['kills'].' | 死亡: '.$p['player']['stats']['UHC']['deaths'].' | K/D: '.round($p['player']['stats']['UHC']['kills']/$p['player']['stats']['UHC']['deaths'], 3);
						break;
					case 'megawalls':
					case 'mw':
						echo
						getRank($p).$p['player']['displayname'].' 的 Mega Walls 信息:'.PHP_EOL.
						'凋零伤害: '.$p['player']['stats']['Walls3']['wither_damage'].' | 职业: '.$p['player']['stats']['Walls3']['chosen_class'].' | 硬币: '.$p['player']['stats']['Walls3']['coins'].PHP_EOL.
						'击杀: '.$p['player']['stats']['Walls3']['kills'].' | 助攻: '.$p['player']['stats']['Walls3']['assists'].' | 死亡: '.$p['player']['stats']['Walls3']['deaths'].' | K/D: '.round($p['player']['stats']['Walls3']['kills']/$p['player']['stats']['Walls3']['deaths'], 3).PHP_EOL.
						'终杀: '.$p['player']['stats']['Walls3']['final_kills'].' | 终助: '.$p['player']['stats']['Walls3']['final_assists'].' | 终死: '.$p['player']['stats']['Walls3']['final_deaths'].' | FKDR: '.round($p['player']['stats']['Walls3']['final_kills']/$p['player']['stats']['Walls3']['final_deaths'], 3).PHP_EOL.
						'胜场: '.$p['player']['stats']['Walls3']['wins'].' | 败场: '.$p['player']['stats']['Walls3']['losses'].' | W/L: '.round($p['player']['stats']['Walls3']['wins']/$p['player']['stats']['Walls3']['losses'], 3);
						break;
					case 'skywars':
					case 'sw':
						echo
						getRank($p).$p['player']['displayname'].' 的 Skywars 信息:'.PHP_EOL.
						'等级: '.getLevelSW($p['player']['stats']['SkyWars']['levelFormatted']).' | 硬币: '.$p['player']['stats']['SkyWars']['coins'].' | 助攻: '.$p['player']['stats']['SkyWars']['assists'].PHP_EOL.
						'击杀: '.$p['player']['stats']['SkyWars']['kills'].' | 死亡: '.$p['player']['stats']['SkyWars']['deaths'].' | K/D: '.round($p['player']['stats']['SkyWars']['kills']/$p['player']['stats']['SkyWars']['deaths'], 3).PHP_EOL.
						'胜场: '.$p['player']['stats']['SkyWars']['wins'].' | 败场: '.$p['player']['stats']['SkyWars']['losses'].' | W/L: '.round($p['player']['stats']['SkyWars']['wins']/$p['player']['stats']['SkyWars']['losses'], 3);
						break;
					case 'bedwars':
					case 'bw':
						echo
						getRank($p).$p['player']['displayname'].' 的 Bedwars 信息:'.PHP_EOL.
						'等级: '.$p['player']['achievements']['bedwars_level'].' | 硬币: '.$p['player']['stats']['Bedwars']['coins'].' | 拆床: '.$p['player']['stats']['Bedwars']['beds_broken_bedwars'].PHP_EOL.
						'胜场: '.$p['player']['stats']['Bedwars']['wins_bedwars']. ' | 败场: '.$p['player']['stats']['Bedwars']['losses_bedwars'].' | W/L: '.round($p['player']['stats']['Bedwars']['wins_bedwars']/$p['player']['stats']['Bedwars']['losses_bedwars'], 3).PHP_EOL.
						'击杀: '.$p['player']['stats']['Bedwars']['kills_bedwars']. ' | 死亡: '.$p['player']['stats']['Bedwars']['deaths_bedwars'].' | K/D: '.round($p['player']['stats']['Bedwars']['kills_bedwars']/$p['player']['stats']['Bedwars']['deaths_bedwars'], 3).PHP_EOL.
						'终杀: '.$p['player']['stats']['Bedwars']['final_kills_bedwars']. ' | 终死: '.$p['player']['stats']['Bedwars']['final_deaths_bedwars'].' | FKDR: '.round($p['player']['stats']['Bedwars']['final_kills_bedwars']/$p['player']['stats']['Bedwars']['final_deaths_bedwars'], 3);
						break;
					case 'guild':
					case 'g':
						$g = hypixel_getguild($apiKey, $p['player']['uuid']);
						if($g) {
							echo
							getRank($p).$p['player']['displayname'].' 的 公会 信息:'.PHP_EOL.
							'公会名称: '.$g['guild']['name'].PHP_EOL.
							'创立时间: '.toDate($g['guild']['created']).PHP_EOL.
							'等级: '.getLevelGuild($g['guild']['exp']).' | 标签: ['.$g['guild']['tag'].']'.PHP_EOL.
							'成员: '.count($g['guild']['members']).' | 最高在线: '.$g['guild']['achievements']['ONLINE_PLAYERS'];
						}
						else {
							echo('无法找到玩家 '.$p['player']['displayname'].' 的公会信息.');
						}
						break;
					default:
						if(isset($args[2])) echo '未知的分类, 已跳转至默认分类.'.PHP_EOL;
						echo
						getRank($p).$p['player']['displayname'].' 的 Hypixel 信息:'.PHP_EOL.
						'等级: '.getLevel($p['player']['networkExp']).' | 人品: '.$p['player']['karma'].PHP_EOL.
						'首次登录: '.toDate($p['player']['firstLogin']).PHP_EOL.
						'上次登录: '.toDate($p['player']['lastLogin']).PHP_EOL.
						(
							(isset($p['player']['lastLogout']) && ($p['player']['lastLogout'] < $p['player']['lastLogin'])) ?
							'● 此玩家当前在线, 游玩了 '.date('H:i:s', time() - 28800 - ($p['player']['lastLogin']) / 1000) :
							'上次退出: '.toDate($p['player']['lastLogout'])
						);
				}
			}
			else {
				echo('无效的玩家名.');
			}
		}
		else {
			echo
			'正确用法: /hypixel <玩家> [分类]'.PHP_EOL.
			'"分类" 可以是下列之一:'.PHP_EOL.
			'- bedwars, bw'.PHP_EOL.
			'- skywars, sw'.PHP_EOL.
			'- uhc'.PHP_EOL.
			'- megawalls, mw'.PHP_EOL.
			'- blitzsg, bsg, hungergames'.PHP_EOL.
			'- guild, g';
		}
		break;
	case '/virus':
		if(!getAvailability($fromAccount)) exit('你查询的频率过高, 喝杯茶休息一下吧!');
		userExecute($fromAccount);
		require_once('utils/VirusTrack.php');
		if(isset($args[1])){
			$province = virus_getProvinceStats($args[1]);
			if($province)
				echo
				'目前, '.$province['provinceName'].'新冠肺炎统计信息如下:'.PHP_EOL.
				'确诊病例: '.$province['confirmedCount'].' | 疑似病例: '.$province['suspectedCount'].PHP_EOL.
				'治愈人数: '.$province['curedCount'].' |  死亡人数: '.$province['deadCount'];
			else
				echo('无法查找到指定省份的疫情信息.');
		}
		else {
			$virus = virus_overall();
			if($virus)
				echo
				'目前, 全国新冠肺炎统计信息如下:'.PHP_EOL.
				'全国确诊: '.$virus['confirmedCount'].' | 疑似病例: '.$virus['suspectedCount'].PHP_EOL.
				'治愈人数: '.$virus['curedCount'].' |  死亡人数: '.$virus['deadCount'];
			else
				echo('无法获取相关疫情信息, 可能是由于 API 的变动或失效.');
		}	
		break;
	case '/spelako':
		echo
		'General:'.PHP_EOL.
		'	Dev: Peaksol'.PHP_EOL.
		'	Version: 1.0.0'.$version.PHP_EOL.
		'	First Created: 2019/6/18'.PHP_EOL.
		'Credits:'.PHP_EOL.
		'	Hypixel Stats API by Hypixel'.PHP_EOL.
		'	Mojang Player Stats API by Mojang'.PHP_EOL.
		'	Minecraft Server Status by Lukasss93'.PHP_EOL.
		'	DXY-2019-nCoV-Crawler by BlankerL';
		break;
	// 以下为机器人管理员指令
	case '/admin':
		if(!isStaff($fromAccount)) exit();
		echo
		'Spelako 管理员指令列表:'.PHP_EOL.
		'/admin - 查看此列表'.PHP_EOL.
		'/echo <文本> - 使机器人复读消息'.PHP_EOL.
		'/botstats - 获取机器人的相关统计数据'.PHP_EOL.
		'/ignore ... - 屏蔽指定用户/指定(或当前)群的指令'.PHP_EOL.
		'/unignore ... - 解除对指定用户/指定群的指令的屏蔽';
		break;
	case '/echo':
		if(!isStaff($fromAccount)) exit();
		if(isset($args_nofilter[1])){
			unset($args_nofilter[0]);
			echo implode(' ', $args_nofilter);
		}
		else{
			echo
			'那只敏捷的棕毛狐狸跃过了这条懒洋洋的狗.'.PHP_EOL.
			'The quick brown fox jumps over the lazy dog.';
		}
		break;
	case '/botstats':
		if(!isStaff($fromAccount)) exit();
		echo
		'目前 Spelako 缓存了 '.count(getCooldowns()).' 人的使用记录,'.PHP_EOL.
		'缓存文件共 '.'XXX'.' 个, 占用存储空间 '.dsize('cache/').PHP_EOL.
		'有 '.count(getBlacklist()).' 个用户及 '.count(getBlacklist(true)).' 个群聊被列入黑名单.';
		break;
	case '/ignore':
		if(!isStaff($fromAccount)) exit();
		switch($args[1]){
			case 'user':
				if(isset($args[2]))
					if(blacklistAdd($args[2], false))
						echo ('Spelako 将不再处理用户 '.$args[2].' 的指令.');
					else
						echo ('该用户已经被 Spelako 屏蔽.');
				else
					echo ('正确用法: /ignore user <QQ号>');
				break;
			case 'group':
				if(isset($args[2]))
					if(blacklistAdd($args[2], true))
						echo ('Spelako 将不再处理群 '.$args[2].' 的指令.');
					else
						echo ('该群已经被 Spelako 屏蔽.');
				else
					if(blacklistAdd($fromGroup, true))
						echo ('Spelako 将不再处理当前群('.$fromGroup.')的指令.');
					else
						echo ('当前群已经被 Spelako 屏蔽.');
					break;
			default:
				echo('正确用法: /ignore <user/group> ...');
				break;
		}
		break;
	case '/unignore':
		if(!isStaff($fromAccount)) exit();
		switch($args[1]){
			case 'user':
				if(isset($args[2]))
					if(blacklistRemove($args[2], false))
						echo('Spelako 将继续处理用户 '.$args[2].' 的指令.');
					else
						echo('该用户没有被 Spelako 屏蔽.');
				else
					echo('正确用法: /unignore user <QQ号>');
				break;
			case 'group':
				if(isset($args[2]))
					if(blacklistRemove($args[2], true))
						echo('Spelako 将继续处理群 '.$args[2].' 的指令.');
					else
						echo('该群没有被 Spelako 屏蔽.');
				else
					echo('正确用法: /unignore group <群号>');
				break;
			default: 
				echo('正确用法: /unignore <user/group> ...');
				break;
		}
		break;
	default:
		$sCmd = similarCommand($args[0], array('help', 'mojang', 'server', 'hypixel', 'virus', 'spelako', 'skyblock', 'lv', 'stats'));
		if($sCmd) {
			echo
			'你可能想输入此命令: /'.$sCmd.PHP_EOL.
			'但是你将其输入为了 "'.$args[0].'"';
		}
}

}
?>