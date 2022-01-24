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

class SpelakoUtils {
	const CACHE_DIRECTORY = 'cache';

	// Sends a GET request to a specific URL and return with the request result
	// Cache system is supported: default expiration is 0 second
	public static function getURL($url, array $query = [], $cacheExpiration = 0, $cachePath = self::CACHE_DIRECTORY) {
		$fullURL = $url.'?'.http_build_query($query);
		//echo $fullURL;
		$cacheFile = $cachePath.'/'.hash('md5', $fullURL);
		if(file_exists($cacheFile) && (time() - filemtime($cacheFile)) <= $cacheExpiration) {
			return FileSystem::fileRead($cacheFile);
		}

		$result = file_get_contents($fullURL);
		if(str_contains($http_response_header[0], '200')) {
			if(!is_dir($cachePath)) mkdir($cachePath);
			FileSystem::fileRemove($cacheFile);
			FileSystem::fileWrite($cacheFile, $result);
			return $result;
		}
		else if(str_contains($http_response_header[0], 'Too Many Requests')) return -1;
		return false;
	}

	// Joins the first array with line breaks and get the placeholders replaced with the values in the second array
	public static function buildString(array $lines, array $replacements = [], $eol = false) {
		return vsprintf(implode(PHP_EOL, array_filter($lines)), $replacements).($eol ? PHP_EOL : '');
	}

	// Converts timestamp to a human-readable format, regarding the default timezone set in the config
	public static function convertTime($timestamp, $second = false, $format = 'Y-m-d H:i', $timezone_offset = 0) {
		if(!$timestamp) return '?';
		return date($format, $timestamp / ($second ? 1 : 1000) + $timezone_offset);
	}

	// Convert the file size to a human-readable format
	public static function sizeFormat($byte) {
		$a = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		$pos = 0;
		while ($byte >= 1024) {
			$byte /= 1024;
			$pos ++;
		}
		return round($byte, 2).' '.$a[$pos];
	}

	public static function div($a, $b, $round = 3) {
		if($b == 0) return 0;
		return round($a / $b, $round);
	}
}
?>
