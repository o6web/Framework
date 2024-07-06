<?php

namespace O6\Framework\Helpers;

use cjrasmussen\Request\RequestData;
use O6\Framework\Services\SiteConfig;

class SortHandlerHelper
{
	private SiteConfig $siteConfig;
	private RequestData $requestData;
	private array $sessionData;
	private string $salt = '';

	public function __construct(SiteConfig $siteConfig, RequestData $requestData, array $session_data = [])
	{
		$this->siteConfig = $siteConfig;
		$this->requestData = $requestData;
		$this->sessionData = $session_data;
	}

	public function initialize(): self {
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
		$string = $this->siteConfig->getSiteName() . json_encode($this->sessionData, JSON_THROW_ON_ERROR) . $this->requestData->server->get('REMOTE_ADDR') . $this->siteConfig->getRootDomain() . $this->requestData->server->get('HTTP_USER_AGENT');
		return sha1($string);
	}
}
