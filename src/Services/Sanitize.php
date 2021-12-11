<?php

namespace O6\Framework\Services;

use RuntimeException;

class Sanitize
{
	/**
	 * Sanitize a string for use in SQL
	 *
	 * @param string $text
	 * @return string
	 */
	public static function sql(string $text): string
	{
		$text = stripslashes($text);
		$text = preg_replace('|;([ ]*)--|', '', $text);
		$text = addslashes($text);

		return $text;
	}

	/**
	 * Take a string list of hashtags and make sure they're all formatted properly
	 *
	 * @param string $text
	 * @param string $separator
	 * @return string
	 */
	public static function hashtagList(string $text, string $separator = ' '): string
	{
		$valid_separators = [' ', ',', ';', '.'];

		if (!in_array($separator, $valid_separators, true)) {
			$msg = 'Separator `' . $separator . '` is not valid';
			throw new RuntimeException($msg);
		}

		$text = str_replace($valid_separators, $separator, $text);
		$tags = explode(' ', trim($text));
		$output = [];

		foreach ($tags AS $tag) {
			$tag = preg_replace('/[^[[:alnum:]]/i', '', $tag);
			if ($tag !== '') {
				$output[] = '#' . $tag;
			}
		}

		return implode($separator, $output);
	}
}
