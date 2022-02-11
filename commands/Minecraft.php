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

class Minecraft {
	const API_BASE_URL = 'https://api.mojang.com';

	function __construct(private SpelakoCore $core, private $config) {
		$core->loadJsonResource($config->resource);
	}

	public function getName() {
		return ['/minecraft', '/mc'];
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
		if(empty($args[1])) return SpelakoUtils::buildString($this->getUsage());

		if(strlen($args[1]) <= 16) {
			if($uuid = $this->fetchUuidById($args[1])) $usingId = true;
			else return $this->getMessage('info.uuid_request_failed');
		}
		else if(strlen($args[1]) == 32 || strlen($args[1]) == 36) {
			$uuid = $args[1];
			$usingId = false;
		}
		else return $this->getMessage('info.syntax_error');
	
		$profile = $this->fetchProfileByUuid($uuid);
		if(!$profile) return $this->getMessage('info.name_history_request_failed');
	
		if(count($profile) == 1) $placeholder = SpelakoUtils::buildString(
			$this->getMessage('placeholders.name'),
			[
				$profile[0]['name']
			]
		);
		else {
			$lines = array();
			foreach($profile as $k => $v) {
				array_push($lines, SpelakoUtils::buildString(
					$this->getMessage('placeholders.name_sorted'),
					[
						$k + 1,
						$v['name']
					]
				));
			}
			$placeholder = SpelakoUtils::buildString($lines);
		}

		return SpelakoUtils::buildString(
			$this->getMessage('layout'),
			[
				$usingId ? 'ID' : 'UUID',
				$uuid,
				$placeholder
			]
		);
	}

	private function fetchUuidById($id) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/users/profiles/minecraft/'.$id, cacheExpiration: 300);
		if($src && ($result = json_decode($src, true)['id'])) {
			return $result;
		}
		return false;
	}

	private function fetchProfileByUuid($uuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/user/profiles/'.$uuid.'/names', cacheExpiration: 300);
		if($src && ($result = json_decode($src, true))) {
			$result = array_reverse($result, true);
			return $result;
		}
		return false;
	}
}
?>
