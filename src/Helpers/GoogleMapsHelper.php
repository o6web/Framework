<?php

namespace O6\Framework\Helpers;

use O6\Framework\Services\WebRequest;

class GoogleMapsHelper
{
	/**
	 * Get the geolocation coordinates of a specified city
	 *
	 * @param string $city
	 * @param string $key
	 * @return string
	 * @throws \JsonException
	 */
	public static function getCityGeolocation(string $city, string $key = ''): string
	{
		$url_string = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($city) . '&sensor=false';
		if ($key) {
			$url_string .= '&key=' . $key;
		}
		$data = json_decode(WebRequest::getFileContents($url_string), false, 512, JSON_THROW_ON_ERROR);

		if (count($data->results)) {
			return $data->results[0]->geometry->location->lng . ',' . $data->results[0]->geometry->location->lat;
		}

		return '';
	}
}
