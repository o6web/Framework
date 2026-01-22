<?php

namespace o6web\Framework\Models;

use Exception;
use ReflectionClass;
use ReflectionException;

abstract class BaseModel
{
	protected array $propertyNotHydrated = [];
	protected array $propertyHydrationIgnored = [
		'propertyNotHydrated',
		'propertyHydrationIgnored'
	];

	/**
	 * Set whether a property of a model is hydrated
	 *
	 * @param string $property
	 * @param bool $hydrated
	 * @return $this
	 */
	protected function setPropertyHydration(string $property, bool $hydrated = false): self
	{
		if ($hydrated) {
			if (($key = array_search($property, $this->propertyNotHydrated, true)) !== false) {
				unset($this->propertyNotHydrated[$key]);
			}
		} else {
			$this->propertyNotHydrated[] = $property;
		}
		return $this;
	}

	/**
	 * Get the properties of a model that aren't hydrated
	 *
	 * @return array
	 */
	protected function getPropertyNotHydrated(): array
	{
		return $this->propertyNotHydrated;
	}

	/**
	 * Get the properties of a model that should be ignored for the purposes of hydration
	 *
	 * @return array
	 */
	protected function getPropertyHydrationIgnored(): array
	{
		return $this->propertyHydrationIgnored;
	}

	/**
	 * Determine whether a model is fully hydrated
	 *
	 * @return bool
	 */
	protected function isFullyHydrated(): bool
	{
		return (count($this->propertyNotHydrated) === 0);
	}

	/**
	 * Build an instance of a model from data in an associative array
	 *
	 * @param array $data
	 * @param $class
	 * @param array|null $map
	 * @param bool $ignoreTypeMismatch
	 * @return mixed
	 * @throws Exception
	 */
	protected static function buildFromArray(array $data, $class, ?array $map = [], bool $ignoreTypeMismatch = false)
	{
		$model = new $class;
		self::setModelPropertiesNotHydrated($model);

		foreach ($data AS $key => $value) {
			$method = self::getModelMethodNameByKey($model, $key);
			if ($method) {
				try {
					$model->$method($value);
				} catch (Exception $e) {
					if (!$ignoreTypeMismatch) {
						throw $e;
					}
				}
			}
		}

		foreach ($map AS $source => $dest) {
			$method = self::getModelMethodNameByKey($model, $dest);
			if ($method) {
				try {
					$model->$method($data[$source]);
				} catch (Exception $e) {
					if (!$ignoreTypeMismatch) {
						throw $e;
					}
				}
			}
		}

		return $model;
	}

	/**
	 * Add the not-ignored properties of the model to the list of not hydrated properties
	 *
	 * @param $model
	 * @return void
	 * @throws ReflectionException
	 */
	private static function setModelPropertiesNotHydrated($model): void
	{
		$reflection = new ReflectionClass($model);

		$properties = $reflection->getProperties();
		$ignoredProperties = $model->getPropertyHydrationIgnored();

		foreach ($properties AS $property) {
			$propertyName = $property->getName();
			if (!in_array($propertyName, $ignoredProperties, true)) {
				$model->setPropertyHydration($propertyName, false);
			}
		}
	}

	/**
	 * Get the setter method for a given model/property combination if it exists
	 *
	 * @param $model
	 * @param string $property
	 * @return string|null
	 */
	private static function getModelMethodNameByKey($model, string $property): ?string
	{
		$method = 'set' . $property;
		if (method_exists($model, $method)) {
			return $method;
		}

		$method = 'set' . str_replace('_', '', $property);
		if (method_exists($model, $method)) {
			return $method;
		}

		return null;
	}
}