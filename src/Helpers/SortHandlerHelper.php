<?php

namespace O6\Framework\Helpers;

class SortHandlerHelper
{
	private array $saltItems;
	private string $salt = '';

	public function __construct(array $saltItems = [])
	{
		$this->saltItems = $saltItems;
	}

	public function initialize(): self {
		$this->saltItems[uniqid(self::class, true)] = time();
		$this->salt = $this->generateSalt();
		return $this;
	}

	/**
	 * Generate an obfuscated string of data for use in a URL query string
	 *
	 * @param array $data
	 * @return string
	 */
	public function dataEncode(array $data): string
	{
		$string = json_encode($data, JSON_THROW_ON_ERROR);
		$string = base64_encode($string);
		$string = $this->salt . $string . $this->salt;
		return base64_encode($string);
	}

	/**
	 * Decode an obfuscated string of data from a URL query string value
	 *
	 * @param string $string
	 * @return array
	 */
	public function dataDecode(string $string): array
	{
		$salt_length = strlen($this->salt);
		$string = base64_decode($string);
		$string = substr($string, $salt_length, ($salt_length * -1));
		$string = base64_decode($string);
		return json_decode($string, true, 512, JSON_THROW_ON_ERROR);
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
		$string = json_encode($this->saltItems, JSON_THROW_ON_ERROR);
		return sha1($string);
	}
}
