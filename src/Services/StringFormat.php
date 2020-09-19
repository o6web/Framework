<?php

namespace O6\Framework\Services;

class StringFormat
{
	/**
	 * Converts an IPv4 string to a full 15-character IPv4
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function ip4ToFull($ip): string
	{
		$parts = explode('.', $ip);

		$ip = '';
		foreach ($parts AS $part) {
			$ip .= str_pad($part, 3, '0', STR_PAD_LEFT) . '.';
		}

		return substr($ip, 0, -1);
	}

	/**
	 * Turns boolean response (1/0 or true/false) into yes/no
	 *
	 * @param int|bool $var
	 * @return string
	 */
	public static function booleanYesNo($var): string
	{
		if ($var) {
			return 'Yes';
		}

		return 'No';
	}
}
