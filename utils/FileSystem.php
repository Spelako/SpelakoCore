<?php
/*
	The class FileSystem provides a UNIFIED way for interacting with the local file system.
	Do not use any methods other than here to access local files or directories.
*/
class FileSystem {
	// Gets the contents of a file
	public static function fileRead($path) {
		if(file_exists($path)) return file_get_contents($path);
		return false;
	}

	// Writes something into the specific file
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

	public static function fileGetSize($path) {
		return filesize($path);
	}

	public static function directoryGetContents($path) {
		return glob($path.'/*');
	}
}
?>