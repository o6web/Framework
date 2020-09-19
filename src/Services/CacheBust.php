<?php

namespace O6\Framework\Services;

class CacheBust
{
	private $config_path;
	private $config;
	private $changed = false;

	public function __construct($path)
	{
		$this->config_path = $path;
		$this->config = $this->loadConfig();
	}

	/**
	 * Get the value for the specified key
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->config[$key];
	}

	/**
	 * Set a new value for the specified key
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 */
	public function set($key, $value): self
	{
		if ($this->config[$key] !== $value) {
			$this->config[$key] = $value;
			$this->changed = true;
		}

		return $this;
	}

	/**
	 * Save the current configuration to the config file
	 */
	public function save(): void
	{
		if ($this->changed) {
			file_put_contents($this->config_path, json_encode($this->config));
			$this->changed = false;
		}
	}

	/**
	 * Load the current config file
	 * 
	 * @return array
	 */
	private function loadConfig(): array
	{
		$config = [];

		if (!file_exists($this->config_path)) {
			return $config;
		}

		$data = file_get_contents($this->config_path);
		if ($data) {
			$config = json_decode($data, true);
		}

		return $config;
	}
}