<?php

namespace O6\Framework\Helpers;

class SortHandlerHelper
{
	private $salt;

	public function __construct()
	{
		$this->salt = $this->generateSalt();
	}

	/**
	 * Generate an obfuscated string of data for use in a URL query string
	 *
	 * @param array $data
	 * @return string
	 */
	public function dataEncode($data): string
	{
		$string = json_encode($data);
		$string = $this->salt . $string . $this->salt;
		return base64_encode($string);
	}

	/**
	 * Decode an obfuscated string of data from a URL query string value
	 *
	 * @param string $string
	 * @return array
	 */
	public function dataDecode($string): array
	{
		$salt_length = strlen($this->salt);
		$string = base64_decode($string);
		$string = substr($string, $salt_length, ($salt_length * -1));
		return json_decode($string, true);
	}

	/**
	 * Generate a salt string from available environmental and global variables
	 *
	 * If none of the O6-specific stuff is available, it will at least base the salt on IP and user agent
	 *
	 * @return string string
	 */
	private function generateSalt(): string
	{
		$string = $_ENV['config']['sitename'] . json_encode($GLOBALS['session']) . $_SERVER['REMOTE_ADDR'] . $_ENV['config']['root_domain'] . $_SERVER['HTTP_USER_AGENT'];
		return sha1($string);
	}
}
