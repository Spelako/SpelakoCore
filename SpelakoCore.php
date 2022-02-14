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

require __DIR__.'/utils/FileSystem.php';
require __DIR__.'/utils/SpelakoUtils.php';

class SpelakoCore {
	const VERSION = '22w07a';
	const LAST_UPDATED = '2022/2/14';
	const DEVELOPERS = 'github.com/orgs/Spelako/people';
	const WEBSITE = 'spelako.github.io';

	private array $commands = [];
	private array $resources = [];
	private array $staffs = [];
	private int $cooldown = 0;
	private string $cooldownMessage = '';
	private array $userLastExecutions = [];

	/**
	 * 创建一个 Spelako 对象
	 *
	 * @param mixed $configFile 配置文件的位置
	 */
	function __construct(string $configFile) {
		$config = json_decode(FileSystem::fileRead($configFile));
		foreach($config->commands as $command) {
			require_once('commands/'.$command->file);
			$class = basename($command->file, '.php');
			array_push($this->commands, new $class($this, $command?->config));
		}
		$this->staffs = $config->staffs;
		$this->cooldown = $config->cooldown;
		$this->cooldownMessage = $config->cooldown_message;
	}
	
	/**
	 * 加载一个 JSON 资源
	 *
	 * @param mixed 位于 resource 目录中的文件名
	 * @return bool 是否成功
	 */
	public function loadJsonResource($file) : bool {
		if(!FileSystem::fileExists(__DIR__.'/resources/'.$file)) return false;
		$this->resources[$file] = json_decode(FileSystem::fileRead(__DIR__.'/resources/'.$file));
		return true;
	}
	
	/**
	 * 获取已加载的 JSON 资源的值
	 *
	 * @param string $file 文件名
	 * @param string $path 键 (可通过 `.` 拼接)
	 * @return mixed 结果
	 */
	public function getJsonValue(string $file, string $path) : mixed {
		if(!str_contains($path, '.')) return @$this->resources[$file]->{$path};

		$keys = explode('.', $path);
		@$pointer = $this->resources[$file]->{$keys[0]};
		for($i = 1; $i < count($keys); $i ++) @$pointer = $pointer->{$keys[$i]};
		return $pointer;
	}
	
	/**
	 * 获取所有注册的命令
	 *
	 * @return array
	 */
	public function getCommands() : array {
		return $this->commands;
	}
	
	/**
	 * 获取所有用户冷却状态
	 *
	 * @return array 结果
	 */
	public function getUserLastExecutions() : array {
		return $this->userLastExecutions;
	}

	/**
	 * 执行一条命令
	 *
	 * @param string $text 命令全文
	 * @param string $user 执行者
	 * @return string|null 执行结果
	 */
	public function execute(string $text, string $user) : string|null {
		$args = array_values(array_filter(explode(' ', strtolower($text))));
		foreach($this->commands as $command) if(in_array($args[0], $command->getName())) {
			if(
				$command->hasCooldown()
				&& !in_array($user, $this->staffs)
				&& isset($this->userLastExecutions[$user])
				&& time() - $this->userLastExecutions[$user] < $this->cooldown
			) return $this->cooldownMessage;
			$args[0] = $text;
			$this->userLastExecutions[$user] = time();
			return $command->execute($args, in_array($user, $this->staffs));
		}
		return null;
	}
}
?>
