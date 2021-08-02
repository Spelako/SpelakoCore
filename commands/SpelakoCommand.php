<?php
class SpelakoCommand {
	const USAGE = '/spelako ...';
	const ALIASES = [];
	const DESCRIPTION = '关于 Spelako';
	const COOLDOWN = false;

	public static function execute(array $args, $isStaff) {
		switch(true) {
			case $isStaff && ($args[1] == 'help'):
				return SpelakoUtils::buildString([
					'Spelako 管理员命令列表:',
					'/spelako help - 查看所有可用的管理员命令',
					'/spelako echo <文本> - 使机器人复读消息',
					'/spelako stats - 查看 Spelako 统计信息',
					'/spelako clean - 清理所有缓存文件.'
				]);
			case $isStaff && ($args[1] == 'echo'):
				if(isset($args[2])) return substr($args[0], 14);
				return SpelakoUtils::buildString([
					'那只敏捷的棕毛狐狸跃过了这条懒洋洋的狗.',
					'The quick brown fox jumps over the lazy dog.'
				]);
			case $isStaff && ($args[1] == 'stats'):
				$cacheFiles = FileSystem::directoryGetContents(SpelakoUtils::CACHE_DIRECTORY);
				$totalSize = 0;
				foreach($cacheFiles as $file) $totalSize += FileSystem::fileGetSize($file);
				return SpelakoUtils::buildString([
					'Spelako 统计信息:',
					'缓存用户冷却: %1$d',
					'缓存文件总数: %2$d',
					'缓存占用空间: %3$s',
					'欲清理所有缓存, 请使用此命令:',
					'/spelako clean'
				], [
					count(Spelako::getUserLastExecutions()),
					count($cacheFiles),
					SpelakoUtils::sizeFormat($totalSize)
				]);
			case $isStaff && ($args[1] == 'clean'):
				$cacheFiles = FileSystem::directoryGetContents(SpelakoUtils::CACHE_DIRECTORY);
				$totalSize = 0;
				foreach($cacheFiles as $file) {
					$totalSize += FileSystem::fileGetSize($file);
					FileSystem::fileRemove($file);
				}
				return sprintf(
					'已清理缓存文件 %s.',
					SpelakoUtils::sizeFormat($totalSize)
				);
			default:
				return SpelakoUtils::buildString([
					'关于 Spelako:',
					'版本: %1$s',
					'更新时间: %2$s',
					'开发: %3$s',
					'了解更多请访问 %4$s',
					$isStaff ? '使用 /spelako help 以查看管理员命令.' : ''
				], [
					Spelako::INFO['version'],
					Spelako::INFO['last_updated'],
					Spelako::INFO['dev'],
					Spelako::INFO['link']
				]);
		}
	}
}
?>