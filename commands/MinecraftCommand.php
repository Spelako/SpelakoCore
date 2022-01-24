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

class MinecraftCommand {
	const API_BASE_URL = 'https://api.mojang.com';

	public function getUsage() {
		return '/minecraft <玩家>';
	}

	public function getAliases() {
		return ['/mc'];
	}

	public function getDescription() {
		return '获取指定玩家的 Minecraft 账户信息';
	}

	public function hasCooldown() {
		return true;
	}

	public function execute(array $args) {
		if(!isset($args[1])) return sprintf('正确用法: %s', $this->getUsage());
		if(strlen($args[1]) <= 16) {
			if($uuid = $this->fetchUuidById($args[1])) $usingId = true;
			else return '无法通过此 ID 获取玩家信息.';
		}
		else if(strlen($args[1]) == 32 || strlen($args[1]) == 36) {
			$uuid = $args[1];
			$usingId = false;
		}
		else return '提供的参数不是有效的 ID 或 UUID.';
	
		$profile = $this->fetchProfileByUuid($uuid);
		if(count($profile) == 1) $placeholder = $profile[0]['name'];
		else {
			$placeholder = array();
			foreach($profile as $k => $v) {
				array_push($placeholder, sprintf('%1$d. %2$s', $k + 1, $v['name']));
			}
		}

		return SpelakoUtils::buildString([
			$usingId ? '根据提供的 ID 查找到的 Minecraft 账户信息:' : '根据提供的 UUID 查找到的 Minecraft 账户信息:',
			'UUID: %1$s',
			$profile ? '名称: %2$s' : '玩家名称查询请求发送失败. 请稍后再试.'
		], [
			$uuid,
			is_array($placeholder) ? SpelakoUtils::buildString($placeholder) : $placeholder
		]);
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
