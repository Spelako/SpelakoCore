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

class HelpCommand {
	private SpelakoCore $core;

	const CONFIG_FILE = 'help.json';

	function __construct(SpelakoCore $core) {
		$this->core = $core;
		$core->loadJsonResource(self::CONFIG_FILE);
	}

	public function getUsage() {
		return $this->core->getJsonValue(self::CONFIG_FILE, 'usage');
	}

	public function getAliases() {
		return [];
	}

	public function getDescription() {
		return $this->core->getJsonValue(self::CONFIG_FILE, 'description');
	}

	public function hasCooldown() {
		return false;
	}

	private function getMessage($key) {
		return $this->core->getJsonValue(self::CONFIG_FILE, 'messages.'.$key);
	}

	public function execute() {
		$placeholder = array();
		foreach($this->core->getCommands() as $command) {
			array_push($placeholder, sprintf(
				$this->getMessage('placeholders.command'),
				$command->getUsage(),
				$command->getDescription()
			));
			foreach($command->getAliases() as $alias) {
				array_push($placeholder, sprintf(
					$this->getMessage('placeholders.command_alias'),
					$alias,
					explode(' ', $command->getUsage())[0]
				));
			}
		}
		return SpelakoUtils::buildString(
			$this->getMessage('layout'),
			[
				SpelakoUtils::buildString($placeholder),
				$this->core::WEBSITE
			]
		);
	}
}
?>
