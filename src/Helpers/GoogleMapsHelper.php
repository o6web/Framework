<?php

namespace O6\Framework\Helpers;

use O6\Framework\Services\Curl;

class GoogleMapsHelper
{
	/**
	 * Get the geolocation coordinates of a specified city
	 *
	 * @param string $city
	 * @param string $key
	 * @return string
	 */
	public static function getCityGeolocation($city, $key = '')
	{
		$url_string = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($city) . '&sensor=false';
		if ($key) {
			$url_string .= '&key=' . $key;
		}
		$data = json_decode(Curl::getFileContents($url_string));

		if (count($data->results)) {
			return $data->results[0]->geometry->location->lng . ',' . $data->results[0]->geometry->location->lat;
		}

		return '';
	}
}
