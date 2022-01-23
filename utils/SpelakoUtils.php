<?php
/*
	The class SpelakoUtils provides the most common methods
	that would of great use for creating commands for Spelako.
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
	public static function buildString(array $lines, array $replacements = []) {
		return vsprintf(implode(PHP_EOL, array_filter($lines)), $replacements);
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