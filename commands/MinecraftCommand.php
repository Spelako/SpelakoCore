<?php
class MinecraftCommand{
	const USAGE = '/minecraft <玩家>';
	const ALIASES = ['/mc'];
	const DESCRIPTION = '获取指定玩家的 Minecraft 账户信息';
	const COOLDOWN = true;

	const API_BASE_URL = 'https://api.mojang.com';

	public static function execute(array $args) {
		if(!isset($args[1])) return sprintf('正确用法: %s', self::USAGE);
		if(strlen($args[1]) <= 16) {
			if($uuid = self::fetchUuidById($args[1])) $usingId = true;
			else return '无法通过此 ID 获取玩家信息.';
		}
		else if(strlen($args[1]) == 32 || strlen($args[1]) == 36) {
			$uuid = $args[1];
			$usingId = false;
		}
		else return '提供的参数不是有效的 ID 或 UUID.';
	
		$profile = self::fetchProfileByUuid($uuid);
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
			SpelakoUtils::buildString($placeholder)
		]);
	}

	private static function fetchUuidById($id) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/users/profiles/minecraft/'.$id, cacheExpiration: 300);
		if($src && ($result = json_decode($src, true)['id'])) {
			return $result;
		}
		return false;
	}

	private static function fetchProfileByUuid($uuid) {
		$src = SpelakoUtils::getURL(self::API_BASE_URL.'/user/profiles/'.$uuid.'/names', cacheExpiration: 300);
		if($src && ($result = json_decode($src, true))) {
			$result = array_reverse($result, true);
			return $result;
		}
		return false;
	}
}
?>