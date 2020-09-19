<?php

namespace O6\Framework\Helpers\Aws;

use Aws\Ec2\Ec2Client;
use Exception;
use InvalidArgumentException;
use O6\Framework\Services\PDO\ExceptionLogger;

/**
 * Class Ec2
 * @package O6\Framework\Helpers\Aws
 */
class Ec2Helper
{
	private $ec2;
	private $errorLog;

	/**
	 * Ec2Helper constructor.
	 * @param array|null $config
	 */
	public function __construct($config = null)
	{
		if (!$config) {
			throw new InvalidArgumentException('Configuration array must be provided.');
		}

		// TODO: FIND A BETTER WAY TO DO THIS?
		$this->errorLog = $config['db']['error_log'];

		try {
			$this->ec2 = Ec2Client::factory([
				'key' => $config['aws']['access_key'],
				'secret' => $config['aws']['secret_key'],
				'region' => $config['aws']['region']
			]);
		} catch (Exception $e) {
			ExceptionLogger::logError($e, $this->errorLog);
		}
	}

	/**
	 * Returns the status of a given EC2 instance
	 *
	 * @param string $instance
	 * @return bool|\Guzzle\Service\Resource\Model
	 */
	public function instanceStatus($instance)
	{
		try {
			return $this->ec2->describeInstances(['InstanceIDs' => [$instance], 'IncludeAllInstances' => true]);
		} catch (Exception $e) {
			ExceptionLogger::logError($e, $this->errorLog);
		}

		return false;
	}

	/**
	 * Starts a given EC2 instance
	 *
	 * @param string $instance
	 * @return bool|\Guzzle\Service\Resource\Model
	 */
	public function instanceStart($instance)
	{
		try {
			return $this->ec2->startInstances(['InstanceIds' => [$instance]]);
		} catch (Exception $e) {
			ExceptionLogger::logError($e, $this->errorLog);
		}

		return false;
	}

	/**
	 * Stops a given EC2 instance
	 *
	 * @param string $instance
	 * @return bool|\Guzzle\Service\Resource\Model
	 */
	public function instanceStop($instance)
	{
		try {
			return $this->ec2->stopInstances(['InstanceIds' => [$instance]]);
		} catch (Exception $e) {
			ExceptionLogger::logError($e, $this->errorLog);
		}

		return false;
	}
}
