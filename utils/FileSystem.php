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

class FileSystem {
	public static function fileRead($path) {
		if(file_exists($path)) return file_get_contents($path);
		return false;
	}

	public static function fileWrite($path, $contents) {
		if(!file_exists($path)) {
			$spl = explode('/', $path);
			$dir = implode('/', array_slice($spl, 0, count($spl) - 1));
			@mkdir($dir, recursive: true);
			@touch($path);
		};
		file_put_contents($path, $contents);
	}

	public static function fileRemove($path) {
		return file_exists($path) && unlink($path);
	}

	public static function fileExists($path) {
		return file_exists($path);
	}

	public static function fileLastModified($path) {
		return filemtime($path);
	}

	public static function fileGetSize($path) {
		return filesize($path);
	}

	public static function directoryGetContents($path) {
		return glob($path.'/*');
	}
}
?>
