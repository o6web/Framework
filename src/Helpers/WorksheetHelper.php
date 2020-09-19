<?php

namespace O6\Framework\Helpers;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorksheetHelper
{
	/**
	 * @param Worksheet $worksheet
	 * @param int $column
	 * @param int $row
	 * @param array $functions
	 * @return mixed|null
	 */
	public static function getTransformedCellValue(Worksheet $worksheet, $column, $row, $functions = ['trim'])
	{
		$value = null;

		$cell = $worksheet->getCellByColumnAndRow($column, $row);
		if ($cell) {
			$value = $cell->getValue();

			foreach ($functions AS $function) {
				$value = $function($value);
			}
		}

		return $value;
	}
}