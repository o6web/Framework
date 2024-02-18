<?php

namespace O6\Framework\Services;

use RuntimeException;

class SiteConfig
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
	protected function getConfigurationArray(): array
	{
		return $this->siteConfigArray;
	}

	/**
	 *
	 *
	 * @param string $path
	 * @return array|mixed|null
	 */
	protected function getConfigurationValueByPath(string $path)
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
	protected function implementConfiguredTimezone(?string $timezone = null): void
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
	protected function getPathSiteRoot(): string
	{
		return $this->siteConfigArray['path']['sites_root'];
	}

	/**
	 * Get the globally-configured backup path
	 *
	 * @return string
	 */
	protected function getPathBackups(): string
	{
		return $this->siteConfigArray['path']['backup_directory'];
	}

	/**
	 * Get the globally-configured temporary file path
	 *
	 * @return string
	 */
	protected function getPathTemporary(): string
	{
		return $this->siteConfigArray['path']['transfer_absolute'];
	}

	/**
	 * Get the globally-configured path to the PHP CLI
	 *
	 * @return string
	 */
	protected function getPathPhpCli(): string
	{
		return $this->siteConfigArray['path']['php_cli'];
	}

	/**
	 * Get the globally-configured database host
	 *
	 * @return string
	 */
	protected function getDatabaseHost(): string
	{
		return $this->siteConfigArray['db']['host'];
	}

	/**
	 * Get the globally-configured DSN
	 *
	 * @return string
	 */
	protected function getDatabaseDsn(): string
	{
		return $this->siteConfigArray['db']['dsn'];
	}

	/**
	 * Get the globally-configured database connection options
	 *
	 * @return array
	 */
	protected function getDatabaseConnectionOpts(): array
	{
		return $this->siteConfigArray['db']['opts'];
	}

	/**
	 * Get the globally-configured dashboard URL
	 *
	 * @return string
	 */
	protected function getUrlDashboard(): string
	{
		return $this->siteConfigArray['url']['o6_dashboard'];
	}

	/**
	 * Get the globally-configured time zone
	 *
	 * @return string
	 */
	protected function getTimezone(): string
	{
		return $this->siteConfigArray['timezone'];
	}

	/**
	 * Get the globally-configured options for debug trace handling
	 *
	 * @return object
	 */
	protected function getDebugTraceHandlingOptions(): object
	{
		return (object)$this->siteConfigArray['trace_handling'];
	}
}