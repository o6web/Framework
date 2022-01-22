<?php

namespace O6\Framework\Services\PDO;

abstract class DatabaseNames
{
	protected array $data = [];

	abstract public function __construct();

	public function isDefined(string $name): bool
	{
		return in_array($name, $this->data, true);
	}
}