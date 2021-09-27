<?php

namespace O6\Framework\Services;

use RuntimeException;

class WPSession
{
	private $wp_path;

	public function __construct($wp_path)
	{
		if (!is_dir($wp_path)) {
			throw new RuntimeException('Specified Wordpress path is not valid');
		}
		$this->wp_path = $wp_path;
	}

	/** @noinspection PhpUndefinedClassInspection */
	public function loadSessionUser(): ?\WP_User
	{
		/** @noinspection PhpIncludeInspection */
		require $this->wp_path . 'wp-load.php';
		/** @noinspection PhpUndefinedFunctionInspection */
		return wp_get_current_user();
	}
}
