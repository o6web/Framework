<?php

namespace O6\Framework\Services\PDO;

use cjrasmussen\SlackApi\SlackApi;
use Exception;
use O6\Framework\Services\ErrorLogger;

/**
 * O6 Database Error Logging class
 * @package O6\Framework\Services\PDO
 */
class ExceptionLogger
{
	/**
	 * Determine how to log the Exception
	 *
	 * @param Exception $e
	 * @param string $error_log
	 * @param string $statement_data
	 * @param array $parameters
	 * @return void
	 */
	public static function logError($e, $error_log, $statement_data = '', array $parameters = []): void
	{
		if (strpos($error_log, 'https://hooks.slack.com') === 0) {
			self::logErrorToSlack($error_log, $e, $statement_data, $parameters);
		} elseif ($error_log) {
			self::logErrorToJsonFile($e, $error_log, $statement_data, $parameters);
		} else {
			self::logErrorToScreen($e, $statement_data, $parameters);
		}
	}

	/**
	 * Display the exception on the screen
	 *
	 * @param Exception $e
	 * @param string $statement_data
	 * @param array $parameters
	 * @return void
	 */
	private static function logErrorToScreen($e, $statement_data = '', array $parameters = []): void
	{
		echo '<b>Database Error:</b> ' . $e->getMessage() . "<br />\n";
		echo '<b>Error Code:</b> ' . $e->getCode() . "<br />\n";
		if ($statement_data) {
			echo '<b>Statement Data:</b> ' . $statement_data . "<br />\n";
		}
		if (count($parameters)) {
			ob_start();
			var_dump($parameters);
			$text = ob_get_clean();

			echo '<b>Parameters:</b> ' . $text . "<br />\n";
		}
		echo '<pre>' . print_r($e->getTrace(), true) . "</pre><br />\n";
	}

	/**
	 * Send error details through a Slack webhook
	 *
	 * @param string $slack_url
	 * @param Exception $e
	 * @param string $statement_data
	 * @param array $parameters
	 * @return void
	 */
	private static function logErrorToSlack($slack_url, $e, $statement_data = '', array $parameters = []): void
	{
		ob_start();
		echo 'Database Error: ' . $e->getMessage() . PHP_EOL;
		echo 'Error Code: ' . $e->getCode() . PHP_EOL;
		if ($statement_data) {
			echo 'Statement Data: ' . $statement_data . PHP_EOL;
		}
		if (count($parameters)) {
			echo 'Parameters: ' . var_export($parameters, true) . PHP_EOL;
		}
		echo print_r($e->getTrace(), true) . PHP_EOL;

		$text = ob_get_clean();

		[$url, $channel] = explode('#', $slack_url);

		$msg = ['text' => $text, 'username' => 'DB Error Logger'];
		if ($channel) {
			$msg['channel'] = '#' . $channel;
		}

		(new SlackApi($url))->sendMessage($msg);
		exit;
	}

	/**
	 * Log the exception to a JSON file
	 *
	 * @param Exception $e
	 * @param string $error_log
	 * @param string $statement_data
	 * @param array $parameters
	 * @return void
	 */
	private static function logErrorToJsonFile($e, $error_log, $statement_data = '', array $parameters = []): void
	{
		$data = [
			'mysql_statement' => $statement_data,
			'mysql_parameters' => $parameters,
			'mysql_errorno' => $e->getCode(),
		];

		ErrorLogger::logError($error_log, $e, $data);
	}
}
