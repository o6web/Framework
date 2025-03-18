<?php

namespace O6\Framework\Services;

use Exception;
use O6\Framework\Services\PDO\DatabaseName;
use O6\Framework\Services\PDO\Statement;
use Monolog\Logger;
use PDOException;
use RuntimeException;

/**
 * O6 PDO database connection class
 * @package O6\Framework\Services
 */
class PDO extends \PDO
{
	public const NULL_OPT_NONE = 0;
	public const NULL_OPT_FORCE = 1;
	public const NULL_OPT_DISALLOW = 2;

	public int $hasError = 0;
	public ?Logger $log;
	protected int $_transactionDepth = 0;
	private string $_database = '';
	private array $_param = [];
	private array $prepareCache = [];
	public $names;

	/**
	 * O6\Framework\Services\PDO constructor
	 *
	 * @param string $dsn
	 * @param string $username
	 * @param string $passwd
	 * @param array $options
	 */
	public function __construct(string $dsn, string $username, string $passwd, array $options = [])
	{
		parent::__construct($dsn, $username, $passwd, $options);
		$this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [Statement::class, [$this]]);

		// PULL THE INITIAL DB OUT OF THE DSN, IF THERE IS ONE
		$dsn_parts = $this->getDsnParts($dsn);

		if (array_key_exists('dbname', $dsn_parts)) {
			$this->useDatabase($dsn_parts['dbname']);
		}
	}

	public function setLogger(Logger $log): void
	{
		$this->log = $log;
	}

	/**
	 * Set the database names
	 *
	 * @param $names
	 * @return void
	 */
	public function setNames($names): void
	{
		if (!is_a($names, DatabaseName::class)) {
			throw new RuntimeException('Invalid DatabaseNames provided to setNames()');
		}

		$this->names = $names;
	}

	/**
	 * Start a new transaction or adjust savepoint level as needed
	 *
	 * @return bool|void
	 */
	public function beginTransaction()
	{
		if ($this->_transactionDepth === 0) {
			$return = parent::beginTransaction();
			if ($return) {
				$this->_transactionDepth++;
			}
			return $return;
		}

		$this->exec('SAVEPOINT LEVEL' . $this->_transactionDepth);
		$this->_transactionDepth++;
	}

	/**
	 * Commit the transaction if there was no error, otherwise rollback
	 *
	 * @return bool|void
	 */
	public function commit()
	{
		$this->_transactionDepth--;

		if ($this->_transactionDepth === 0) {
			if (($this->hasError) && ($this->inTransaction())) {
				$this->hasError = 0;
				$this->rollBack();
				return false;
			}

			return parent::commit();
		}

		$this->exec('RELEASE SAVEPOINT LEVEL' . $this->_transactionDepth);
	}

	/**
	 * Execute a rollback if the transaction depth is 0, otherwise manipulate savepoint level
	 *
	 * @return bool|void
	 */
	public function rollBack()
	{
		if ($this->_transactionDepth === 0) {
			throw new PDOException('Rollback error : There is no transaction started');
		}

		$this->_transactionDepth--;

		if ($this->_transactionDepth === 0) {
			return parent::rollBack();
		}

		$this->exec('ROLLBACK TO SAVEPOINT LEVEL' . $this->_transactionDepth);
	}

	/**
	 * Execute a statement with error logging
	 *
	 * @param string $statement
	 * @return int|false
	 */
	public function exec($statement)
	{
		try {
			return parent::exec($statement);
		} catch (Exception $e) {
			$this->hasError = 1;

			if ($this->log) {
				$this->log->addException('Statement could not be executed', [
					'statement' => $statement,
					'exception' => $e,
				]);
			}
		}

		return false;
	}

	/**
	 * Prepare a statement with included error logging
	 *
	 * @param string $query
	 * @param array|null $options
	 * @return Statement|false
	 */
	public function prepare($query, $options = null)
	{
		if (!is_array($options)) {
			$options = [];
		}

		// PARSE THE EXPECTED MATCHES
		$expected = [];
		preg_match_all('/:[^%]\\D\\w*/', $query, $matches);

		foreach ($matches[0] AS $match) {
			$key = substr($match, 1);
			if (array_key_exists($key, $expected)) {
				$expected[$key]++;
			} else {
				$expected[$key] = 1;
			}
		}

		foreach ($expected AS $key => $count) {
			if ($count === 1) {
				continue;
			}

			for ($n = 1; $n <= $count; $n++) {
				$query = preg_replace(('/:' . $key . '\b/'), (':' . $key . $n), $query, 1);
			}
		}

		try {
			// CHECK TO SEE IF WE'VE ALREADY PREPARED THIS AND, IF SO, USE IT
			$cache_key = md5(var_export($query, true) . var_export($options, true) . var_export($this->_database, true));
			if (array_key_exists($cache_key, $this->prepareCache)) {
				/* @var PDO\Statement $stmt */
				$stmt = $this->prepareCache[$cache_key];
			} else {
				/* @var PDO\Statement $stmt */
				$stmt = $this->prepareCache[$cache_key] = parent::prepare($query, $options);
			}

			$stmt->setExpectedParameters($expected);

			foreach ($expected AS $key => $count) {
				if (array_key_exists($key, $this->_param)) {
					$stmt->bindValue($key, $this->_param[$key]->value, $this->_param[$key]->data_type,
						$this->_param[$key]->null_opt);
				}
			}

			return $stmt;
		} catch (Exception $e) {
			$this->hasError = 1;

			if ($this->log) {
				$this->log->addException('Statement could not be prepared', [
					'statement' => $stmt,
					'exception' => $e,
				]);
			}
		}

		return false;
	}

	/**
	 * Run a query with included error logging
	 *
	 * @param string $statement
	 * @param int $mode
	 * @param int|string|object|null $arg3
	 * @param array|null $ctorargs
	 * @return Statement|false
	 */
	public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, array $ctorargs = [])
	{
		try {
			return parent::query($statement, $mode, $arg3, $ctorargs);
		} catch (Exception $e) {
			$this->hasError = 1;
			if ($this->log) {
				$this->log->addException('Statement could not be executed', [
					'statement' => $statement,
					'exception' => $e,
				]);
			}
		}

		return false;
	}

	/**
	 * Run a simple query with included error logging
	 *
	 * This is intended just for fetch assoc/num/both because they don't require the third and fourth parameters from
	 * PDO::query and there's something weird when you supply null for those so extending it gets weird
	 *
	 * @param string $statement
	 * @param int $mode
	 * @return Statement|false
	 */
	public function run(string $statement, int $mode = self::FETCH_ASSOC)
	{
		if (!in_array($mode, [self::FETCH_ASSOC, self::FETCH_NUM, self::FETCH_BOTH], true)) {
			if ($this->log) {
				$this->log->addError('Invalid fetch mode defined', [
					'mode' => $mode,
				]);
			}

			return false;
		}

		try {
			return parent::query($statement, $mode);
		} catch (Exception $e) {
			$this->hasError = 1;

			if ($this->log) {
				$this->log->addException('Statement could not be executed', [
					'statement' => $statement,
					'exception' => $e,
				]);
			}
		}

		return false;
	}

	/**
	 * Bind a value to the connection, allowing it to be used across all statements
	 *
	 * @param int|string $parameter
	 * @param int|string|null $value
	 * @param int $data_type
	 * @param int $null_opt
	 */
	public function bindValue(
		$parameter,
		$value,
		int $data_type = self::PARAM_STR,
		int $null_opt = self::NULL_OPT_NONE
	): void {
		$this->_param[$parameter] = (object)[
			'value' => $value,
			'data_type' => $data_type,
			'null_opt' => $null_opt
		];
	}

	/**
	 * Unbind a parameter value from the connection
	 *
	 * @param int|string $parameter
	 * @return bool
	 */
	public function unBindValue($parameter): bool
	{
		unset($this->_param[$parameter]);
		return true;
	}

	/**
	 * Sets the database to use for future queries
	 *
	 * @param string $database
	 * @return self
	 */
	public function useDatabase(string $database): self
	{
		if (($this->names !== null) && (!$this->names->isDefined($database))) {
			throw new RuntimeException('Database `' . $database . '` is not configured');
		}

		if ((($database !== $this->_database) && ($database)) && $this->exec('USE ' . $database) !== false) {
			$this->_database = $database;
		}

		return $this;
	}

	/**
	 * Split up the component parts of a DSN string
	 *
	 * @param string $dsn
	 * @return array
	 */
	private function getDsnParts(string $dsn): array
	{
		return array_map(static function ($v) {
			return explode('=', $v);
		}, explode(';', $dsn));
	}

	/**
	 * Build a string of placeholders for use as parameters
	 *
	 * @param int|array $count
	 * @param string|null $key
	 * @return string
	 */
	public function buildInString($count, ?string $key = null): string
	{
		if (is_array($count)) {
			$count = count($count);
		}

		if (!$count) {
			return "''";
		}

		if ($key) {
			$key = ':' . $key;
		} else {
			$key = '?';
		}

		return substr(str_repeat(($key . ','), $count), 0, -1);
	}
}
