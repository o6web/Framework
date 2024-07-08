<?php

namespace O6\Framework\Services;

use cjrasmussen\Request\RequestData;

class Redirect
{
	private RequestData $requestData;
	private ?string $url = null;
	private int $httpCode = 301;

	public function __construct(RequestData $requestData)
	{
		$this->requestData = $requestData;
	}

	public function execute(): void
	{
		if ($this->url) {
			header(('Location: ' . $this->url), false, $this->httpCode);
			exit;
		}
	}

	/**
	 * Redirects to a URL with a cleaner query string
	 *
	 * Clears empty values from a query string and converts dates from "day1=1&month1=1&year1=2000" to "date1="2000-01-01"
	 *
	 * @return self
	 */
	public function cleanQueryString(): self
	{
		$get = $this->requestData->query->all();

		if ((!$get['clean']) || (!count($get))) {
			// NOT CLEANABLE, GET OUT
			return $this;
		}

		$query_string = '';

		if ($get['date'] && (($get['date1']) || ($get['date2']))) {
			if ($get['date_type'] === 'range') {
				unset($get['date']);
			} else {
				unset($get['date1']);
				unset($get['date2']);
			}
		}

		$ignore = [
			'date_type',
		];

		if ($get['arr']) {
			[$array_name, $array_items] = explode('_', $get['arr']);

			$array_str = '';
			$array_used = 0;

			for ($n = 1; $n <= $array_items; $n++) {
				$field = $array_name . $n;
				$ignore[] = $field;

				if ($get[$field]) {
					$array_str .= $array_name . '[]=' . $get[$field] . '&';
					$array_used++;
				}
			}

			if ($array_used < $array_items) {
				$query_string .= $array_str;
			}

			$ignore[] = 'arr';
		}

		foreach ($get AS $key => $val) {
			if ($val !== null) {
				$val = trim($val);
			}

			if (($val !== null) && ($val !== '') && (!in_array($key, $ignore, true))) {
				if (false !== strpos($key, 'month')) {
					if ($key === 'month') {
						if ($get['year']) {
							$date = $get['year'] . '-' . $get['month'] . '-' . $get['day'];
							$ignore[] = 'year';
							$ignore[] = 'month';
							$ignore[] = 'day';

							$query_string .= 'date=' . $date . '&';
						} else {
							$query_string .= $key . '=' . $val . '&';
						}
					} else {
						$date1 = $date2 = null;

						if ($get['year1']) {
							$date1 = $get['year1'] . '-' . $get['month1'] . '-' . $get['day1'];
							$ignore[] = 'year1';
							$ignore[] = 'month1';
							$ignore[] = 'day1';
						}

						if ($get['year2']) {
							$date2 = $get['year2'] . '-' . $get['month2'] . '-' . $get['day2'];
							$ignore[] = 'year2';
							$ignore[] = 'month2';
							$ignore[] = 'day2';
						}

						if (($date1) && ($date2)) {
							if ($date1 === $date2) {
								$query_string .= 'date=' . $date1 . '&';
							} else {
								$query_string .= 'date1=' . $date1 . '&';
								$query_string .= 'date2=' . $date2 . '&';
							}
						} else {
							$query_string .= $key . '=' . $val . '&';
						}
					}
				} elseif (false !== strpos($key, 'date')) {
					if (($get['date1']) && ($get['date2'])) {
						if ($get['date1'] === $get['date2']) {
							$query_string .= 'date=' . $get['date1'] . '&';
						} else {
							$query_string .= 'date1=' . $get['date1'] . '&';
							$query_string .= 'date2=' . $get['date2'] . '&';
						}

						$ignore[] = 'date1';
						$ignore[] = 'date2';
					} elseif (($get['date1']) || ($get['date2'])) {
						if ($get['date1']) {
							$query_string .= 'date=' . $get['date1'] . '&';
						} else {
							$query_string .= 'date=' . $get['date2'] . '&';
						}

						$ignore[] = 'date1';
						$ignore[] = 'date2';
					} else {
						$query_string .= $key . '=' . $val . '&';
					}
				} elseif ($key !== 'clean') {
					$query_string .= $key . '=' . stripslashes($val) . '&';
				}
			}
		}

		if (substr($query_string, -1) === '&') {
			$query_string = substr($query_string, 0, -1);
		}

		$redirect = $this->requestData->getRequestedUrl(false);

		if ($query_string) {
			$redirect .= '?' . $query_string;
		}

		$this->url = $redirect;
		$this->httpCode = 301;

		return $this;
	}

}