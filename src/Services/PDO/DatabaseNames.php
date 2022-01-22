<?php

namespace O6\Framework\Services\PDO;

class DatabaseNames
{
	protected array $data = [];

	public function isDefined(string $name): bool
	{
		return in_array($name, $this->data, true);
	}
}