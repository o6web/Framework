<?php

namespace o6web\Framework\Services;

use RuntimeException;

abstract class SiteConfig
{
	protected array $siteConfigArray;

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
	 * Add an element to the configuration array
	 *
	 * @param string $key
	 * @param int|array|string $value
	 * @return void
	 */
	public function setConfigurationValue(string $key, int|array|string $value): void
	{
		$this->siteConfigArray[$key] = $value;
	}

	/**
	 * Add an array of elements to the configuration array
	 *
	 * @param array $values
	 * @return void
	 */
	public function mergeConfigurationValues(array $values): void
	{
		foreach ($values AS $key => $value) {
			$this->setConfigurationValue($key, $value);
		}
	}

	/**
	 *
	 *
	 * @param string $path
	 * @return array|mixed|null
	 */
	public function getConfigurationValueByPath(string $path): mixed
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

	/**
	 * Get the globally-configured root path for sites
	 *
	 * @return string
	 */
	public function getPathSiteRoot(): string
	{
		return $this->siteConfigArray['path']['sites_root'];
	}

	/**
	 * Get the globally-configured backup path
	 *
	 * @return string
	 */
	public function getPathBackups(): string
	{
		return $this->siteConfigArray['path']['backup_directory'];
	}

	/**
	 * Get the globally-configured temporary file path
	 *
	 * @return string
	 */
	public function getPathTemporary(): string
	{
		return $this->siteConfigArray['path']['transfer_absolute'];
	}

	/**
	 * Get the globally-configured path to the PHP CLI
	 *
	 * @return string
	 */
	public function getPathPhpCli(): string
	{
		return $this->siteConfigArray['path']['php_cli'];
	}

	/**
	 * Get the globally-configured database host
	 *
	 * @return string
	 */
	public function getDatabaseHost(): string
	{
		return $this->siteConfigArray['db']['host'];
	}

	/**
	 * Get the globally-configured DSN
	 *
	 * @return string
	 */
	public function getDatabaseDsn(): string
	{
		return $this->siteConfigArray['db']['dsn'];
	}

	/**
	 * Get the globally-configured database connection options
	 *
	 * @return array
	 */
	public function getDatabaseConnectionOpts(): array
	{
		return $this->siteConfigArray['db']['opts'];
	}

	/**
	 * Get the globally-configured dashboard URL
	 *
	 * @return string
	 */
	public function getUrlDashboard(): string
	{
		return $this->siteConfigArray['url']['o6_dashboard'];
	}

	/**
	 * Get the globally-configured time zone
	 *
	 * @return string
	 */
	public function getTimezone(): string
	{
		return $this->siteConfigArray['timezone'];
	}

	/**
	 * Get the globally-configured options for debug trace handling
	 *
	 * @return object
	 */
	public function getDebugTraceHandlingOptions(): object
	{
		return (object)$this->siteConfigArray['trace_handling'];
	}

	abstract public function getSiteName(): string;

	abstract public function getUrlHome(): string;

	abstract public function getPathMainDocument(): string;

	abstract public function getRootDomain(): string;
}