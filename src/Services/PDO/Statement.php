<?php

namespace O6\Framework\Services\PDO;

use Exception;
use O6\Framework\Services\PDO;
use O6\Logging\ExceptionLogger;
use PDOStatement;

/**
 * O6 PDO Statement class
 * @package O6\Framework\Services\PDO
 */
class Statement extends PDOStatement
{
	protected $pdo;
	private $_inputParameters = [];
	private $_expectedParameters = [];
	private $executeResponse;

	/**
	 * o6PDOStatement constructor.
	 * @param PDO $pdo
	 */
	protected function __construct($pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * Set the expected parameters
	 *
	 * @param $expectedParameters
	 */
	public function setExpectedParameters($expectedParameters): void
	{
		$this->_expectedParameters = $expectedParameters;
	}

	/**
	 * Return success or failure of the most recent execution of the statement
	 *
	 * @return bool
	 */
	public function getExecuteResponse(): bool
	{
		return $this->executeResponse;
	}

	/**
	 * Bind a value to the statement, stripping slashes and forcing NULLs for strings
	 *
	 * @param mixed $parameter
	 * @param mixed $value
	 * @param int $data_type
	 * @param int $null_opt
	 * @param int $length
	 * @return bool
	 */
	public function bindValue(
		$parameter,
		$value,
		$data_type = PDO::PARAM_STR,
		$null_opt = PDO::NULL_OPT_NONE,
		$length = null
	): ?bool {
		if ((is_array($value)) AND (count($value) === 1)) {
			$value = $value[0];
		}
		// IF WE'RE SENDING IN AN ARRAY, LOOP THROUGH THEM
		if (is_array($value)) {
			$n = 1;
			do {
				foreach ($value AS $val) {
					if (is_numeric($parameter)) {
						$field = $parameter + $n - 1;
					} else {
						$field = $parameter . $n;
					}

					$this->bindValue($field, $val, $data_type, $null_opt, $length);
					$n++;
				}
			} while ($n <= $this->_expectedParameters[$parameter]);

			return $n;
		}

		// IF WE EXPECT MORE THAN ONE, LOOP THROUGH TO ASSIGN THE VALUE EVERYWHERE NEEDED
		if ($this->_expectedParameters[$parameter] > 1) {
			$result = false;
			for ($n = 1; $n <= $this->_expectedParameters[$parameter]; $n++) {
				$result = $this->bindValue(($parameter . $n), $value, $data_type, $null_opt, $length);
			}

			return $result;
		}

		if ($data_type === PDO::PARAM_INT) {
			if (((int)$value === 0) AND ($null_opt === PDO::NULL_OPT_FORCE)) {
				$value = null;
				$data_type = PDO::PARAM_NULL;
			} elseif (($value === null) AND ($null_opt === PDO::NULL_OPT_DISALLOW)) {
				$value = 0;
			} elseif ($value === '') {
				$value = 0;
			} elseif ($value !== '') {
				$value = (int)$value;
			}
		} elseif ($data_type === PDO::PARAM_STR) {
			if (($null_opt === PDO::NULL_OPT_FORCE) AND ($value === '')) {
				$value = null;
			} elseif (($null_opt === PDO::NULL_OPT_DISALLOW) AND ($value === null)) {
				$value = '';
			} elseif (($length) AND ($value !== null)) {
				$value = substr($value, 0, $length);
			}

			if ($value === null) {
				$data_type = PDO::PARAM_NULL;
			} else {
				// WORDPRESS REQUIRES THIS
				$value = stripslashes($value);
			}
		}

		$this->_inputParameters[$parameter] = ['value' => $value, 'type' => $data_type];

		try {
			return parent::bindValue($parameter, $value, $data_type);
		} catch (Exception $e) {
			$this->pdo->hasError = 1;
			ExceptionLogger::logError($e, $this->pdo->errorLog, $this->debugDumpParams());
			return false;
		}
	}

	/**
	 * Execute an o6PDOStatement and log any errors, returning the o6PDOStatement
	 *
	 * @param null $inputParameters
	 * @return self|bool
	 */
	public function execute($inputParameters = null)
	{
		try {
			$this->executeResponse = parent::execute($inputParameters);
		} catch (Exception $e) {
			$this->pdo->hasError = 1;
			ExceptionLogger::logError($e, $this->pdo->errorLog, $this->debugDumpParams(),
				$this->_inputParameters);
			return false;
		}

		if (!$this->executeResponse) {
			$msg = 'Prepared statement could not be executed' . "\n";
			$msg .= print_r($this->errorInfo(), true);
			ExceptionLogger::logError(new Exception(trim($msg)), $this->pdo->errorLog, $this->debugDumpParams(),
				$this->_inputParameters);
		}

		return $this;
	}

	/**
	 * Return all of the rows of a single column as an array
	 *
	 * @param string|int $key
	 * @param array $output
	 * @return array
	 */
	public function fetchColumnAll($key = null, $output = null): array
	{
		if (!is_array($output)) {
			$output = [];
		}

		while ($record = $this->fetch(PDO::FETCH_ASSOC)) {
			if ($key === null) {
				$output[] = reset($record);
			} else {
				$output[] = $record[$key];
			}
		}
		return $output;
	}

	/**
	 * Return an array of all rows with the value of the specified field(s) as key(s)
	 *
	 * @param string|integer|array $key_field
	 * @param bool $remove_key
	 * @param bool $return_array
	 * @param string|integer $value_field
	 * @return array
	 */
	public function fetchAllKeyed($key_field, $remove_key = false, $return_array = true, $value_field = ''): array
	{
		if (!is_array($key_field)) {
			$key_field = [$key_field];
		}

		$build_output = static function (&$out, $keys, $value, $array_out, $val_field) use (&$build_output) {
			if (count($keys)) {
				$key = array_shift($keys);

				if (!is_array($out[$key])) {
					$out[$key] = [];
				}

				$build_output($out[$key], $keys, $value, $array_out, $val_field);
			} else {
				if ($val_field) {
					// WE'VE SPECIFIED A FIELD TO USE AS VALUE
					$value = $value[$val_field];
				}

				if ($array_out) {
					// WE'VE SPECIFIED TO APPEND NEW VALUES TO AN ARRAY
					$out[] = $value;
				} else {
					$out = $value;
				}
			}
		};

		$output = [];
		while ($record = $this->fetch(PDO::FETCH_ASSOC)) {
			$keys = [];

			foreach ($key_field AS $key) {
				$keys[] = $record[$key];

				if ($remove_key) {
					unset($record[$key]);
				}
			}

			$build_output($output, $keys, $record, $return_array, $value_field);
		}

		return $output;
	}

	/**
	 * Override the debugDumpParams methos to return a string
	 *
	 * @return string
	 */
	public function debugDumpParams(): string
	{
		ob_start();
		parent::debugDumpParams();
		return ob_get_clean();
	}

	public function debug($exit = false): void
	{
		$debug = [
			'_inputParameters' => $this->_inputParameters,
			'_expectedParameters' => $this->_expectedParameters,
			'queryString' => $this->queryString,
		];
		var_dump($debug);

		if ($exit) {
			exit;
		}
	}
}
