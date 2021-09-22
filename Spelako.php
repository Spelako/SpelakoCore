<?php
require 'utils/FileSystem.php';
require 'utils/SpelakoUtils.php';

class Spelako {
	const CONFIG = [
		'hypixel_api_key' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', // Hypixel API Key, 用于 Hypixel 统计信息查询
		'staffs' => ['123456789', '987654321'], // 管理员列表, 使用逗号分隔
		'cooldown' => 5, // 命令冷却, 适用于需要网络请求的命令, 单位为秒
		'timezone_offset' => 28800, // 当前时区相对于 UTC 的偏移时间, 单位为秒
	];

	const INFO = [
		'version' => '2.4.0',
		'last_updated' => '2021/9/22',
		'dev' => 'Peaksol, Dian_Jiao',
		'link' => 'spelako.github.io'
	];

	private static array $commands = [];
	private static array $userLastExecutions = [];

	public static function loadCommands() {
		foreach(glob('commands/*.php') as $file) {
			require_once($file);
			$classname = basename($file, '.php');
			$rootcmd = explode(' ', $classname::USAGE)[0];
			Spelako::$commands[$rootcmd] = [
				'class' => $classname,
				'usage' => $classname::USAGE,
				'description' => $classname::DESCRIPTION,
				'cooldown' => $classname::COOLDOWN
			];
			foreach($classname::ALIASES as $alias) {
				Spelako::$commands[$alias] = [
					'class' => $classname,
					'usage' => $alias,
					'description' => sprintf('命令 %s 的别名', $rootcmd),
					'cooldown' => $classname::COOLDOWN
				];
			}
		}
	}

	public static function getCommandList() {
		return self::$commands;
	}

	public static function getUserLastExecutions() {
		return self::$userLastExecutions;
	}

	public static function execute($command, $user) {
		$args = array_values(array_filter(explode(' ', strtolower($command))));
		$cmd = $args[0];
		$args[0] = $command;
		if(isset(self::$commands[$cmd])) {
			if(self::$commands[$cmd]['cooldown']) {
				if(isset(self::$userLastExecutions[$user]) && time() - self::$userLastExecutions[$user] < self::CONFIG['cooldown']) return '你使用命令的频率过高, 喝杯茶休息一下吧!';
				self::$userLastExecutions[$user] = time();
			}
			
			return self::$commands[$cmd]['class']::execute($args, in_array($user, self::CONFIG['staffs']));
		}
		else {
			foreach(array_keys(self::$commands) as $contrast) {
				similar_text($cmd, $contrast, $percent);
				if($percent > 70) {
					return SpelakoUtils::buildString([
						'你可能想输入此命令: %1$s',
						'但是你输入的是: %2$s'
					], [
						$contrast,
						$cmd
					]);
				}
			}
			return null;
		}
	}
}
?>