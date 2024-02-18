<?php

namespace O6\Framework\Services;

use RuntimeException;

class SiteConfig
{
	private array $siteConfigArray;

	public function __construct(array $config)
	{
		$this->siteConfigArray = $config;
	}

	/**
	 * Return the full array of configuration values
	 *
	 * @return array
	 */
	public function getConfigurationArray(): array
	{
		return $this->siteConfigArray;
	}

	/**
	 *
	 *
	 * @param string $path
	 * @return array|mixed|null
	 */
	public function getConfigurationValueByPath(string $path)
	{
		$keys = explode('/', $path);

		$return = $this->siteConfigArray;
		foreach ($keys AS $key) {
			if (empty($return[$key])) {
				return null;
			}

			$return = $return[$key];
		}

		return $return;
	}

	/**
	 * Implement the specified time zone or get the default time zone from the config and implement that
	 *
	 * @param string|null $timezone
	 * @return void
	 */
	public function implementConfiguredTimezone(?string $timezone = null): void
	{
		if (!$timezone) {
			$timezone = $this->siteConfigArray['timezone'];
		}

		if (!$timezone) {
			throw new RuntimeException('Time zone unavailable for setting.');
		}

		date_default_timezone_set($timezone);
	}
}