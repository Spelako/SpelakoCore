<?php
/*
 * Copyright (C) 2020-2022 Spelako Project
 * 
 * Permission is granted to use, modify and/or distribute this program under the terms of the GNU Affero General Public License version 3 (AGPLv3).
 * You should have received a copy of the license along with this program. If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 * 
 * 在 GNU 通用公共许可证第三版 (AGPLv3) 的约束下, 你有权使用, 修改, 复制和/或传播该软件.
 * 你理当随同本程序获得了此许可证的副本. 如果没有, 请查阅 <https://www.gnu.org/licenses/agpl-3.0.html>.
 * 
 */

require __DIR__.'/utils/FileSystem.php';
require __DIR__.'/utils/SpelakoUtils.php';

class SpelakoCore {
	const CONFIG_FILE = 'config.json';
	const VERSION = '3.0.0';
	const LAST_UPDATED = '2022/1/24';
	const DEVELOPERS = 'github.com/orgs/Spelako/people';
	const WEBSITE = 'spelako.github.io';

	private array $resources = [];
	private array $commands = [];
	private array $userLastExecutions = []; // TO UPDATE

	function __construct() {
		if($this->loadJsonResource(self::CONFIG_FILE) == false)
		die('配置文件加载失败.');
	}

	public function loadJsonResource($file) : bool {
		if(!FileSystem::fileExists(__DIR__.'/resources/'.$file)) return false;
		$this->resources[$file] = json_decode(FileSystem::fileRead($this->getcwd().'/resources/'.$file));
		return true;
	}

	public function getJsonValue($file, $path) { // getValue('help.json', 'messages.layout.0')
		if(!str_contains($path, '.')) return $this->resources[$file]->{$path};
		$keys = explode('.', $path);
		$pointer = $this->resources[$file]->{$keys[0]};
		for($i = 1; $i < count($keys); $i ++) {
			$pointer = $pointer->{$keys[$i]};
		}
		return $pointer;
	}

	public function getcwd() {
		return __DIR__;
	}

	public function loadCommand($class) {
		array_push($this->commands, new $class($this));
	}

	public function getCommands() {
		return $this->commands;
	}

	public function getUserLastExecutions() {
		return $this->userLastExecutions;
	}

	public function execute($text, $user) {
		$args = array_values(array_filter(explode(' ', strtolower($text))));
		foreach($this->commands as $command) if(explode(' ', $command->getUsage())[0] == $args[0]) {
			if(!$command->hasCooldown()) {
				$args[0] = $text;
				return $command->execute($args, in_array($user, $this->getJsonValue(self::CONFIG_FILE, 'staffs')));
			}
			if(
				!in_array($user, $this->getJsonValue(self::CONFIG_FILE, 'staffs'))
				&& isset($this->userLastExecutions[$user])
				&& time() - $this->userLastExecutions[$user] < $this->getJsonValue(self::CONFIG_FILE, 'cooldown')
			) return '你使用命令的频率过高, 喝杯茶休息一下吧!';
			$this->userLastExecutions[$user] = time();
		}
	}
}
?>
