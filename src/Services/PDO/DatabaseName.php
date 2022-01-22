<?php

namespace O6\Framework\Services\PDO;

use RuntimeException;

class DatabaseName
{
	private ?array $data;

	public function __construct(?array $names = null)
	{
		$this->data = $names;
	}

	/**
	 * Add a single new database name
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	protected function addName(string $key, string $value): void
	{
		if ($this->data[$key]) {
			throw new RuntimeException('Database `' . $key . '` is already defined');
		}

		$this->data[$key] = $value;
	}

	/**
	 * Is the specified database name in the list of defined names
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isDefined(string $name): bool
	{
		return in_array($name, $this->data, true);
	}

	/**
	 * Get the defined database name for the specified key
	 *
	 * @param string $key
	 * @return string
	 */
	protected function getName(string $key): string
	{
		if (!array_key_exists($key, $this->data)) {
			throw new RuntimeException('The database `' . $key . '` is not defined');
		}

		return $this->data[$key];
	}
}