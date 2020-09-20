<?php

namespace O6\Framework\Services;

use Exception;
use RuntimeException;

class Curl
{
	/**
	 * Gets an external file's contents using cURL
	 *
	 * 15-20 times faster than file_gets_contents()
	 *
	 * @param string $url
	 * @return string|bool
	 */
	public static function getFileContents($url)
	{
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_MAXREDIRS, 5);
		curl_setopt($c, CURLOPT_HEADER, 1);
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);

		$curl_error = curl_errno($c);
		if ($curl_error) {
			$msg = 'CURL Errno: ' . $curl_error . PHP_EOL;
			$msg .= 'CURL URL: ' . $url . PHP_EOL;
			$msg .= 'Timestamp: ' . date('Y-m-d g:i:s A') . PHP_EOL;
			throw new RuntimeException(trim($msg));
		}

		$response = curl_exec($c);
		$header_size = curl_getinfo($c, CURLINFO_HEADER_SIZE);
		curl_close($c);

		$header = trim(substr($response, 0, $header_size));
		$body = trim(substr($response, $header_size));
		$headers = explode("\r\n", $header);

		if (preg_match('|HTTP(.*)200(.*)|is', $headers[0])) {
			return trim($body);
		}

		$location = '';
		foreach ($headers AS $h) {
			if (strpos($h, 'Location: ') === 0) {
				$location = substr($h, 10);
				break;
			}
		}

		if ($location) {
			if (strpos($location, '/') === 0) {
				$url_parts = parse_url($url);
				$new_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $location;
				return self::getFileContents($new_url);
			}

			return self::getFileContents($location);
		}

		return false;
	}

	/**
	 * Gets the header for a given page request using cURL
	 *
	 * @param string $url
	 * @return array
	 */
	public static function getPageHeader($url): array
	{
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HEADER, true);
		curl_setopt($c, CURLOPT_NOBODY, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_exec($c);
		$header = curl_getinfo($c);
		curl_close($c);

		return $header;
	}

	/**
	 * Gets the URL a given URL redirects to based on header information
	 *
	 * @param string $url
	 * @return string
	 */
	public static function getUrlRedirect($url): string
	{
		$header = self::getPageHeader($url);
		return $header['redirect_url'];
	}

	/**
	 * Open a socket to effectively make an async post to a page
	 *
	 * @param string $url
	 * @param array $params
	 */
	public static function postAsync($url, $params): void
	{
		// SET UP PARAMETERS
		$post_string = '';
		foreach ($params AS $key => &$val) {
			if (is_array($val)) {
				$val = implode(',', $val);
			}
			$post_string .= $key . '=' . urlencode($val) . '&';
		}
		unset($val);

		if ('' !== $post_string) {
			$post_string = substr($post_string, 0, -1);
		}

		// OPEN THE SOCKET
		$url_parts = parse_url($url);
		$port = ($url_parts['port']) ?: (($url_parts['schema'] === 'https') ? 443 : 80);

		$fp = fsockopen($url_parts['host'], $port, $errno, $errstr, 60);

		if ($fp) {
			// SEND THE DATA
			$out = 'POST ' . $url_parts['path'] . ' HTTP/1.1' . "\r\n";
			$out .= 'Host: ' . $url_parts['host'] . "\r\n";
			$out .= 'Content-Type: application/x-www-form-urlencoded' . "\r\n";
			$out .= 'Content-Length: ' . strlen($post_string) . "\r\n";
			$out .= 'Connection: Close' . "\r\n\r\n";
			if ($post_string) {
				$out .= $post_string;
			}

			fwrite($fp, $out);
		}

		// CLOSE THE SOCKET
		fclose($fp);
	}

	/**
	 * Gets an external file's contents using cURL, first requesting a cookie from another page
	 *
	 * @return string
	 * @throws Exception
	 * @see https://stackoverflow.com/questions/895786/how-to-get-the-cookies-from-a-php-curl-into-a-variable
	 */
	public static function getFileContentsWithCookie($url, $cookie_source): string
	{
		// DO A REQUEST TO GET A SESSION COOKIE
		$c = curl_init($cookie_source);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_HEADER, 1);
		$result = curl_exec($c);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		$cookies = $cookie = [];
		foreach ($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies[] = $cookie;
		}
		$cookies = array_merge(...$cookies);

		sleep(random_int(3, 8));

		// GET THE NEW DATA
		$header = [];
		$header[] = 'Cookie: ' . http_build_query($cookies);
		$header[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36';
		$header[] = 'Accept: application/json, text/javascript, */*; q=0.01';
		$header[] = 'Accept-Language: en-US,en;q=0.9';

		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_REFERER, $cookie_source);
		curl_setopt($c, CURLOPT_MAXREDIRS, 5);
		curl_setopt($c, CURLOPT_HEADER, 0);
		curl_setopt($c, CURLOPT_HTTPHEADER, $header);
		$html = curl_exec($c);
		curl_close($c);

		return $html;
	}
}
