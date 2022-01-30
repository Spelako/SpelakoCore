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
	
	/**
	 * 向指定 URL 发送 GET 请求 (带缓存机制)
	 *
	 * @param mixed $url 请求 URL, 不带 GET 参数
	 * @param mixed $query 请求 GET 参数 (数组, 默认为空)
	 * @param mixed $cacheExpiration 此请求的缓存有效期 (默认为 0)
	 * @param mixed &$httpStatus 用于保存请求状态码的变量
	 * @return string
	 */
	public static function getURL(string $url, array $query = [], int $cacheExpiration = 0, string &$httpStatus = '') : string|false {
		$fullURL = $url.'?'.http_build_query($query);

		$cacheFile = self::CACHE_DIRECTORY.'/'.hash('md5', $fullURL);
		if(FileSystem::fileExists($cacheFile) && (time() - FileSystem::fileLastModified($cacheFile)) <= $cacheExpiration){
			return FileSystem::fileRead($cacheFile);
		}

		$result = file_get_contents($fullURL);
		$httpStatus = $http_response_header[0];
		if(str_contains($httpStatus, '200')) {
			FileSystem::fileRemove($cacheFile);
			FileSystem::fileWrite($cacheFile, $result);
			return $result;
		}

		return false;
	}

	/**
	 * 构造一个字符串
	 *
	 * @param string|array $layout 字符串布局 (如果是 array, 将使用换行符拼接之)
	 * @param array $placeholders 用于替换 $layout 中的占位符的值 (可选, 如果 $layout 中没有占位符)
	 * @param bool $eol 是否在字符串末尾增加换行符
	 * @return string 结果
	 */
	public static function buildString(string|array $layout, array $placeholders = []) : string {
		if(is_array($layout)) return str_replace(PHP_EOL.PHP_EOL, PHP_EOL, vsprintf(implode(PHP_EOL, $layout), $placeholders));
		return vsprintf($layout, $placeholders);
	}

	/**
	 * 通过时间戳格式化一个时刻或时间间隔
	 *
	 * @param int|float $timestamp 时间戳
	 * @param bool $inSeconds 不使用毫秒制
	 * @param string $format 格式
	 * @param int|float $offset 偏移量 (秒)
	 * @return string 结果
	 */
	public static function formatTime(int|float $timestamp, bool $inSeconds = false, string $format = 'Y-m-d H:i', int|float $offset = 0) : string {
		return gmdate($format, $inSeconds ? $timestamp : round($timestamp / 1000) + $offset);
	}

	/**
	 * 格式化一个文件体积
	 *
	 * @param int $byte 字节数
	 * @return string 结果
	 */
	public static function sizeFormat(int $byte) : string {
		$a = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		$pos = 0;
		while($byte >= 1024) {
			$byte /= 1024;
			$pos ++;
		}
		return round($byte, 2).' '.$a[$pos];
	}
	
	/**
	 * 安全计算除法
	 *
	 * @param mixed $a 被除数
	 * @param mixed $b 除数
	 * @param mixed $round 保留小数点后的位数
	 * @return string
	 */
	public static function div(int|float|null $a, int|float|null $b, int $round = 3) : string {
		if(!($a && $b)) return 0;
		return round($a / $b, $round);
	}
}
?>
