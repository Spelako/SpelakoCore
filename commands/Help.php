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

class Help {
	function __construct(private SpelakoCore $core, private $config) {
		$core->loadJsonResource($config->resource);
	}

	public function getName() {
		return ['/help'];
	}

	public function getUsage() {
		return SpelakoUtils::buildString($this->core->getJsonValue($this->config->resource, 'usage'));
	}

	public function getDescription() {
		return $this->core->getJsonValue($this->config->resource, 'description');
	}

	public function hasCooldown() {
		return false;
	}

	private function getMessage($key) {
		return $this->core->getJsonValue($this->config->resource, 'messages.'.$key);
	}

	public function execute(array $args) {
		if(isset($args[1])) {
			$targetCommand = '';
			foreach($this->core->getCommands() as $command)
				foreach($command->getName() as $k => $name)
					if($name == $args[1] || $name == '/'.$args[1])
						$targetCommand = $command;
			if(!$targetCommand) return $this->getMessage('info.unknown_command');
			return $targetCommand->getUsage();
		}

		$placeholder = array();
		foreach($this->core->getCommands() as $command) foreach($command->getName() as $k => $name) {
			array_push($placeholder, SpelakoUtils::buildString(
				$this->getMessage($k == 0 ? 'placeholders.command' : 'placeholders.command_alias'),
				[
					$name,
					$k == 0 ? $command->getDescription() : $command->getName()[0]
				]
			));
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
