<?php

namespace o6web\Framework\Services;

use RuntimeException;

class SiteConfigLoader
{
	private const string ROOT_DIR = '/_sites/';
	private const string CONFIG_FILE_PATH = '/_includes/config/config.php';

	private string $globalSiteRootPath;
	private string $globalConfigPath;
	private string $localSiteFolder;
	private array $config;

	public function __construct()
	{
		$request_path = debug_backtrace()[0]['file'];
		$dir_parts = explode(self::ROOT_DIR, $request_path);

		$this->globalSiteRootPath = $dir_parts[0] . self::ROOT_DIR ;
		$this->globalConfigPath = $this->globalSiteRootPath . 'config.php';
		$this->localSiteFolder = explode('/', $dir_parts[1])[0];
	}

	/**
	 * Load the config for the local site or a specified site
	 *
	 * @param string|null $site_folder
	 * @param string|null $config_file_path
	 * @return array
	 */
	public function loadConfig(?string $site_folder = null, ?string $config_file_path = null): array
	{
		if (!$this->config[$site_folder]) {
			if (!$site_folder) {
				$site_folder = $this->localSiteFolder;
			}

			if ($config_file_path) {
				$config_file_path = '.' . trim($config_file_path, ' /');
			}

			$local_config_path = $this->globalSiteRootPath . $site_folder . ($config_file_path ?: self::CONFIG_FILE_PATH);

			$global = $this->loadGlobalConfig();
			$config = $this->loadLocalConfig($local_config_path);

			$this->mergeConfigArrays($global, $config);
			$this->config[$site_folder] = $config;
		}

		return $this->config[$site_folder];
	}

	public function setTimezone($timezone = null): void
	{
		if (!$timezone) {
			$timezone = $this->config[$this->localSiteFolder]['timezone'];
		}

		if (!$timezone) {
			throw new RuntimeException('Time zone unavailable for setting.');
		}

		date_default_timezone_set($timezone);
	}

	/**
	 * Load the global config values
	 *
	 * @return array
	 */
	private function loadGlobalConfig(): array
	{
		if (file_exists($this->globalConfigPath)) {
			$global = [];

			/** @noinspection PhpIncludeInspection */
			require $this->globalConfigPath;

			return $global;
		}

		return [];
	}

	/**
	 * Load the local config values
	 *
	 * @param string $local_config_path
	 * @param array|null $global
	 * @return array
	 */
	private function loadLocalConfig(string $local_config_path, ?array $global = null): array
	{
		if (!is_array($global)) {
			// GET THE GLOBAL VALUES SO THAT THEY'RE AVAILABLE WHEN LOADING LOCAL VALUES
			/** @noinspection PhpUnusedLocalVariableInspection */
			$global = $this->loadGlobalConfig();
		}

		if (file_exists($local_config_path)) {
			$config = [];

			/** @noinspection PhpIncludeInspection */
			require $local_config_path;

			return $config;
		}

		return [];
	}

	/**
	 * Merge values from one config array into another
	 *
	 * @param array $new
	 * @param array $existing
	 */
	public function mergeConfigArrays(array $new, array &$existing): void
	{
		foreach ($new AS $key => $value) {
			if ((is_array($value)) && (is_array($existing[$key]))) {
				$this->mergeConfigArrays($value, $existing[$key]);
			} elseif (($value) && (!$existing[$key])) {
				$existing[$key] = $value;
			}
		}
	}
}
