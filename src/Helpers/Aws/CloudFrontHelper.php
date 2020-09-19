<?php

namespace O6\Framework\Helpers\Aws;

use Aws\CloudFront\CloudFrontClient;
use Exception;
use InvalidArgumentException;
use O6\Framework\Services\PDO\ExceptionLogger;

class CloudFrontHelper
{
	private $cloudfront;
	private $errorLog;

	/**
	 * CloudFrontHelper constructor.
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
			$this->cloudfront = CloudFrontClient::factory([
				'key' => $config['aws']['access_key'],
				'secret' => $config['aws']['secret_key'],
				'region' => $config['aws']['region']
			]);
		} catch (Exception $e) {
			ExceptionLogger::logError($e, $this->errorLog);
		}
	}

	/**
	 * Invalidate a path or paths
	 *
	 * @param string $distribution_id
	 * @param string|array $paths
	 */
	public function invalidate($distribution_id, $paths = []): void
	{
		if (!is_array($paths)) {
			$paths = [$paths];
		}

		$this->cloudfront->createInvalidation([
			'DistributionId' => $distribution_id,
			'Paths' => ['Quantity' => count($paths), 'Items' => $paths],
			'CallerReference' => md5(time() . json_encode($paths) . $distribution_id)
		]);
	}
}
