<?php

namespace O6\Framework\Controllers;

use O6\Framework\Services\PDO;

abstract class BaseController
{
	protected PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}
}
