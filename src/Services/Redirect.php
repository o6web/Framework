<?php

namespace O6\Framework\Services;

use cjrasmussen\Request\RequestHelpers;

class Redirect
{
	private $url;
	private $http_code = 301;

	public function execute(): void
	{
		if ($this->url) {
			header(('Location: ' . $this->url), false, $this->http_code);
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
		if ((!$_GET['clean']) OR (!count($_GET))) {
			// NOT CLEANABLE, GET OUT
			return $this;
		}

		$query_string = '';

		if ($_GET['date'] AND (($_GET['date1']) OR ($_GET['date2']))) {
			if ($_GET['date_type'] === 'range') {
				unset($_GET['date']);
			} else {
				unset($_GET['date1']);
				unset($_GET['date2']);
			}
		}

		$ignore = [
			'date_type',
		];

		if ($_GET['arr']) {
			[$array_name, $array_items] = explode('_', $_GET['arr']);

			$array_str = '';
			$array_used = 0;

			for ($n = 1; $n <= $array_items; $n++) {
				$field = $array_name . $n;
				$ignore[] = $field;

				if ($_GET[$field]) {
					$array_str .= $array_name . '[]=' . $_GET[$field] . '&';
					$array_used++;
				}
			}

			if ($array_used < $array_items) {
				$query_string .= $array_str;
			}

			$ignore[] = 'arr';
		}

		foreach ($_GET AS $key => $val) {
			$val = trim($val);

			if (($val !== null) AND ($val !== '') AND (!in_array($key, $ignore, true))) {
				if (false !== strpos($key, 'month')) {
					if ($key === 'month') {
						if ($_GET['year']) {
							$date = $_GET['year'] . '-' . $_GET['month'] . '-' . $_GET['day'];
							$ignore[] = 'year';
							$ignore[] = 'month';
							$ignore[] = 'day';

							$query_string .= 'date=' . $date . '&';
						} else {
							$query_string .= $key . '=' . $val . '&';
						}
					} else {
						if ($_GET['year1']) {
							$date1 = $_GET['year1'] . '-' . $_GET['month1'] . '-' . $_GET['day1'];
							$ignore[] = 'year1';
							$ignore[] = 'month1';
							$ignore[] = 'day1';
						}

						if ($_GET['year2']) {
							$date2 = $_GET['year2'] . '-' . $_GET['month2'] . '-' . $_GET['day2'];
							$ignore[] = 'year2';
							$ignore[] = 'month2';
							$ignore[] = 'day2';
						}

						if (($date1) AND ($date2)) {
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
					if (($_GET['date1']) AND ($_GET['date2'])) {
						if ($_GET['date1'] === $_GET['date2']) {
							$query_string .= 'date=' . $_GET['date1'] . '&';
						} else {
							$query_string .= 'date1=' . $_GET['date1'] . '&';
							$query_string .= 'date2=' . $_GET['date2'] . '&';
						}

						$ignore[] = 'date1';
						$ignore[] = 'date2';
					} elseif (($_GET['date1']) OR ($_GET['date2'])) {
						if ($_GET['date1']) {
							$query_string .= 'date=' . $_GET['date1'] . '&';
						} else {
							$query_string .= 'date=' . $_GET['date2'] . '&';
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

		$redirect = RequestHelpers::getRequestedUrl(false);

		if ($query_string) {
			$redirect .= '?' . $query_string;
		}

		$this->url = $redirect;
		$this->http_code = 301;

		return $this;
	}

}