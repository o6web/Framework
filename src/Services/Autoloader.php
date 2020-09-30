<?php

namespace O6\Framework\Services;

/**
 * Autoloader class
 */
class Autoloader
{
	/**
	 * Autoload Class File
	 *
	 * Autoloads O6 classes based on namespace.
	 *
	 * @param string $class
	 * @param string $local_prefix
	 * @param string $local_path
	 */
	public static function autoload($class, $local_prefix, $local_path): void
	{
		$parts = explode('\\', $class);
		$class_name = array_pop($parts);

		// GET THE REQUESTED PREFIX
		$prefix = array_shift($parts);

		if ($prefix !== trim($local_prefix, ' \\')) {
			// DOESN'T MATCH THE ONE WE WANT, GET OUT AND TRY A DIFFERENT AUTOLOADER
			return;
		}

		// SANITIZE THE LOCAL PATH
		$local_path = '/' . trim($local_path, ' /') . '/';

		$class_path = implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $class_name . '.php';
		$path = $local_path . $class_path;

		if (file_exists($path)) {
			require_once $path;
		}
	}

	/**
	 * Register Autoload
	 *
	 * @param string $local_prefix
	 * @param string $local_path
	 */
	public static function register($local_prefix, $local_path): void
	{
		spl_autoload_register(static function ($class) use ($local_prefix, $local_path) {
			self::autoload($class, $local_prefix, $local_path);
		}, null, true);
	}
}
