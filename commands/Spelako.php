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

class Spelako {
	function __construct(private SpelakoCore $core, private $config) {
		$core->loadJsonResource($config->resource);
	}

	public function getName() {
		return ['/spelako'];
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

	public function execute(array $args, $isStaff) {
		if($isStaff && isset($args[1])) switch($args[1]) {
			case 'help':
				return SpelakoUtils::buildString($this->getMessage('help.layout'));
			case 'echo':
				if(isset($args[2])) return substr($args[0], 14);
				return SpelakoUtils::buildString($this->getMessage('echo.layout'));
			case 'stats':
				$cacheFiles = FileSystem::directoryGetContents(SpelakoUtils::CACHE_DIRECTORY);
				$totalSize = 0;
				foreach($cacheFiles as $file) $totalSize += FileSystem::fileGetSize($file);
				return SpelakoUtils::buildString(
					$this->getMessage('stats.layout'),
					[
						count($this->core->getUserLastExecutions()),
						count($cacheFiles),
						SpelakoUtils::sizeFormat($totalSize)
					]
				);
			case 'clean':
				$cacheFiles = FileSystem::directoryGetContents(SpelakoUtils::CACHE_DIRECTORY);
				$totalSize = 0;
				foreach($cacheFiles as $file) {
					$totalSize += FileSystem::fileGetSize($file);
					FileSystem::fileRemove($file);
				}
				return SpelakoUtils::buildString(
					$this->getMessage('clean.layout'),
					[
						SpelakoUtils::sizeFormat($totalSize)
					]
				);
		}
		return SpelakoUtils::buildString(
			$this->getMessage('default.layout'),
			[
				$this->core::VERSION,
				$this->core::LAST_UPDATED,
				$this->core::DEVELOPERS,
				$this->core::WEBSITE,
				$isStaff ? $this->getMessage('default.placeholder') : ''
			]
		);
	}
}
?>
