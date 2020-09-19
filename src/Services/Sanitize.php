<?php

namespace O6\Framework\Services;

class Sanitize
{
	/**
	 * Sanitize a string for use in SQL
	 *
	 * @param string $text
	 * @return string
	 */
	public static function sql($text): string
	{
		$text = stripslashes($text);
		$text = preg_replace('|;([ ]*)--|', '', $text);
		$text = addslashes($text);

		return $text;
	}
}
