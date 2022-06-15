<?php

namespace O6\Framework\Helpers;

class RequestHelper
{
	/**
	 * Determines if this is running in the context of a web request, as opposed to command-line
	 *
	 * @return bool
	 */
	public static function isWebRequest(): bool
	{
		return ((PHP_SAPI !== 'cli') && (array_key_exists('REQUEST_URI', $_SERVER)) && ($_SERVER['REQUEST_URI'] !== ''));
	}
}