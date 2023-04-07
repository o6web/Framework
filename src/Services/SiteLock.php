<?php

namespace O6\Framework\Services;

use InvalidArgumentException;
use cjrasmussen\Request\RequestHelpers;

class SiteLock
{
	private $config;

	/**
	 * @param array|null $config
	 */
	public function __construct($config = null)
	{
		if (!$config) {
			throw new InvalidArgumentException('Configuration array must be provided.');
		}

		$this->config = $config;
	}

	/**
	 * Take the required action based on the lock status
	 */
	public function lockAction(): void
	{
		global $argv;

		$locking_file = $this->config['path']['main_document'] . '__lock/lock.json';

		if (file_exists($locking_file)) {
			$ip_address = $_SERVER['REMOTE_ADDR'] ?: gethostbyname(parse_url($this->config['url']['home'], PHP_URL_HOST));

			$lock_data = json_decode(file_get_contents($locking_file), false);

			if ((!in_array($ip_address, $lock_data->allowed, true)) AND (!in_array('bypass_lock', $argv, true))) {
				if (RequestHelpers::isWebRequest()) {
					header(('Location: ' . $lock_data->redirect), true, (($lock_data->temporary) ? 301 : 307));
				}
				exit;
			}
		}
	}
}