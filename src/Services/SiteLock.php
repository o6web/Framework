<?php

namespace O6\Framework\Services;

use cjrasmussen\Request\RequestData;

class SiteLock
{
	private SiteConfig $siteConfig;
	private RequestData $requestData;

	public function __construct(SiteConfig $siteConfig, RequestData $requestData)
	{
		$this->siteConfig = $siteConfig;
		$this->requestData = $requestData;
	}

	/**
	 * Take the required action based on the lock status
	 */
	public function lockAction(): void
	{
		global $argv;

		$locking_file = $this->siteConfig->getPathMainDocument() . '__lock/lock.json';

		if (file_exists($locking_file)) {
			$ip_address = $this->requestData->server->has('REMOTE_ADDR') ? $this->requestData->server->get('REMOTE_ADDR') : gethostbyname(parse_url($this->siteConfig->getUrlHome(), PHP_URL_HOST));

			$lock_data = json_decode(file_get_contents($locking_file), false, 512, JSON_THROW_ON_ERROR);

			if ((!in_array($ip_address, $lock_data->allowed, true)) && (!in_array('bypass_lock', $argv, true))) {
				if ($this->requestData->isWebRequest()) {
					header(('Location: ' . $lock_data->redirect), true, (($lock_data->temporary) ? 301 : 307));
				}
				exit;
			}
		}
	}
}