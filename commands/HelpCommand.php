<?php
class HelpCommand {
	const USAGE = '/help';
	const ALIASES = [];
	const DESCRIPTION = '查看所有可用的命令';
	const COOLDOWN = false;

	public static function execute() {
		$placeholder = array();
		foreach(Spelako::getCommandList() as $v) {
			array_push($placeholder, sprintf(
				'%1$s - %2$s',
				$v['usage'],
				$v['description']
			));
		}
		return SpelakoUtils::buildString([
			'Spelako 命令列表:',
			'%1$s',
			'欲查看命令详细用法, 请访问 %2$s'
		], [
			SpelakoUtils::buildString($placeholder),
			Spelako::INFO['link']
		]);
	}
}
?>