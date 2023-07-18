<?php

namespace O6\Framework\Services;

use cjrasmussen\String\Strings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSystem
{
	/**
	 * Get a recursive directory listing
	 *
	 * @param string $path
	 * @param string $search - must appear in the file name
	 * @return array
	 */
	public static function scanDirRecursive(string $path, $search = ''): array
	{
		$files = [];

		$dir = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($dir) AS $file) {
			$filename = $file->getPath() . '/' . $file->getFilename();

			if ((substr($filename, -1) === '.') OR (substr($filename, -1) === '/')) {
				// SKIP IF THE FILE ENDS IN A DOT OR A SLASH
				continue;
			}

			if (Strings::strContains($filename, $search)) {
				$files[] = $filename;
			}
		}

		return $files;
	}

	/**
	 * Put data to a file in a a directory that may not exist
	 *
	 * @param string $path
	 * @param string $data
	 * @return false|int
	 */
	public static function filePutContents(string $path, string $data)
	{
		if (file_exists($path)) {
			return file_put_contents($path, $data);
		}

		$parts = explode('/', $path);
		array_pop($parts);

		$new_path = '';
		foreach ($parts AS $folder) {
			$new_path .= '/' . $folder;
			if (!is_dir($new_path)) {
				mkdir($new_path);
			}
		}

		return file_put_contents($path, $data);
	}

	/**
	 * Deletes a file if it exists
	 *
	 * @param string $path
	 * @return void
	 */
	public static function deleteFile(string $path): void
	{
		if (file_exists($path)) {
			unlink($path);
		}
	}

	/**
	 * Delete any empty subdirectories under the provided path
	 *
	 * Taken from http://stackoverflow.com/questions/1833518/remove-empty-subfolders-with-php
	 *
	 * @param string $path
	 * @param bool $is_root
	 * @return bool
	 */
	public static function deleteEmptySubdirectories(string $path, bool $is_root = false): bool
	{
		$empty = true;
		foreach (glob($path . DIRECTORY_SEPARATOR . '*') AS $file) {
			if (is_dir($file)) {
				if (!self::deleteEmptySubdirectories($file)) {
					$empty = false;
				}
			} else {
				$empty = false;
			}
		}

		if (($empty) && (!$is_root)) {
			rmdir($path);
		}
		return $empty;
	}
}
