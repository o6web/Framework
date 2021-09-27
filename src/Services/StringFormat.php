<?php

namespace O6\Framework\Services;

class StringFormat
{
	/**
	 * Converts an abbreviated IP address string to the full IP address string
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function ipToFull(string $ip): string
	{
		if (strpos($ip, ':') !== false) {
			return self::ip6ToFull($ip);
		}

		return self::ip4ToFull($ip);
	}

	/**
	 * Converts an IPv4 string to a full 15-character IPv4
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function ip4ToFull(string $ip): string
	{
		$parts = explode('.', $ip);

		$ip = '';
		foreach ($parts AS $part) {
			$ip .= str_pad($part, 3, '0', STR_PAD_LEFT) . '.';
		}

		return substr($ip, 0, -1);
	}

	/**
	 * Converts an IPv6 string to a full 39-character IPv6
	 *
	 * @param string $ip
	 * @return string
	 */
	public static function ip6ToFull(string $ip): string
	{
		$parts = explode(':', $ip);

		$parts_final = [];
		foreach ($parts AS $key => $part) {
			if ($part) {
				$parts_final[] = str_pad($part, 4, '0', STR_PAD_LEFT);
			} elseif ($parts[$key + 1]) {
				$parts_final[] = '0000';
			}
		}

		for ($n = 0; $n <= 8; $n++) {
			if (!$parts_final[$n]) {
				$parts_final[$n] = '0000';
			}
		}

		return implode(':', $parts_final);
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
