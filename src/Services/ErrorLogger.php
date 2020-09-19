<?php

namespace O6\Framework\Services;

class ErrorLogger
{
	/**
	 * Log an error to a JSON file
	 *
	 * @param string $logPath
	 * @param string|\Exception $error
	 * @param array $extra
	 * @param int|null $timestamp
	 * @param string|null $file
	 */
	public static function logError($logPath, $error, array $extra = [], $timestamp = null, $file = null)
	{
		$data = [
			'meta' => [
				'host' => $_SERVER['HTTP_HOST'],
				'uri' => $_SERVER['REQUEST_URI'],
				'query' => $_SERVER['QUERY_STRING'],
				'ip' => $_SERVER['REMOTE_ADDR'],
				'referer' => $_SERVER['HTTP_REFERER'],
				'agent' => $_SERVER['HTTP_USER_AGENT'],
				'timestamp' => time(),
				'backtrace' => debug_backtrace(),
			],
			'extra' => $extra
		];

		if ($timestamp) {
			$data['meta']['timestamp'] = $timestamp;
		}

		if ($file) {
			$data['meta']['uri'] = $file;
			$data['meta']['backtrace'] = null;
		}

		if ($error instanceof \Exception) {
			$data['meta']['message'] = $error->getMessage();

			$file = $error->getFile();
			if ($file) {
				$data['meta']['uri'] = $file;
			}

			$backtrace = $error->getTrace();
			if ($backtrace) {
				$data['meta']['backtrace'] = $backtrace;
			}
		} else {
			$data['meta']['message'] = $error;
		}

		if ($logPath) {
			$text = json_encode($data);

			$path = $logPath . md5($text) . '.json';
			file_put_contents($path, $text);
		} else {
			var_dump($data);
		}
	}
}
