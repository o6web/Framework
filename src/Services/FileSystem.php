<?php

namespace O6\Framework\Services;

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
	public static function scanDirRecursive($path, $search = '')
	{
		$files = [];

		$dir = new RecursiveDirectoryIterator($path);
		foreach (new RecursiveIteratorIterator($dir) AS $file) {
			$filename = $file->getPath() . '/' . $file->getFilename();

			if ($search) {
				if (false !== strpos($filename, $search)) {
					$files[] = $filename;
				}
			} elseif ((substr($filename, -1) !== '.') AND (substr($filename, -1) !== '/')) {
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
	public static function filePutContents($path, $data): ?int
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

}