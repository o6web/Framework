<?php

namespace O6\Framework\Services;

use RuntimeException;

/**
 * Class DynamicAsset
 * @package O6\Framework\Services
 */
class DynamicAsset
{
	/**
	 * @var PDO
	 */
	private $_db;
	private $basePath;
	private $dbName;
	private $tableName;

	/**
	 * DynamicAsset constructor.
	 *
	 * @param string $base_path
	 * @param PDO $_db
	 * @param string $db_name
	 * @param string $table_name
	 */
	public function __construct($base_path, $_db, $db_name, $table_name = 'asset')
	{
		$this->basePath = $base_path;
		$this->_db = $_db;
		$this->dbName = $db_name;
		$this->tableName = $table_name;
	}

	/**
	 * Upload specified file to the assets management system
	 *
	 * @param string $source_path
	 * @param int|null $file_timestamp
	 * @param string|null $extension
	 * @return int
	 */
	public function uploadFile($source_path, $file_timestamp = null, $extension = null): int
	{
		if ((is_dir($source_path)) OR (!file_exists($source_path))) {
			throw new RuntimeException('Invalid source file specified');
		}

		if (!$extension) {
			$extension = $this->getFileExtension($source_path);
		}

		if (!$file_timestamp) {
			$file_timestamp = $this->getFileTimestamp($source_path);
		}

		$hash = sha1_file($source_path);

		$asset_id = $this->getExistingAssetIdFromHash($hash);
		if ($asset_id) {
			return $asset_id;
		}

		return $this->insertNewAsset($source_path, $extension, $hash, $file_timestamp);
	}

	/**
	 * Upload specified image file to the asset management system with the given dimensions
	 *
	 * @param string $source_path
	 * @param int|null $dst_w
	 * @param int|null $dst_h
	 * @param bool $fit
	 * @param int|null $file_timestamp
	 * @return int
	 */
	public function uploadResizedImage($source_path, $dst_w = null, $dst_h = null, $fit = false, $file_timestamp = null): int
	{
		$extension = $this->getFileExtension($source_path);

		if (!$file_timestamp) {
			$file_timestamp = $this->getFileTimestamp($source_path);
		}

		[$src_w, $src_h] = getimagesize($source_path);

		if ((($dst_w) AND ($dst_h)) AND (($src_w !== $dst_w) OR ($src_h !== $dst_h))) {
			// RESIZE AND SAVE THE IMAGE AND RESET THE SOURCE PATH IF WE PROVIDED DIMENSIONS AND THE IMAGE ISN'T ALREADY THAT SIZE

			if ($fit) {
				$dims = Image::resizeMax($src_w, $src_h, $dst_w, $dst_h);
				$dst_w = $dims['w'];
				$dst_h = $dims['h'];
			}

			$temp_path = $_ENV['config']['path']['transfer_absolute'] . md5(time() . $source_path . $_SERVER['REMOTE_ADDR']) . '.png';
			$img = Image::resize($source_path, $extension, $dst_w, $dst_h);
			imagepng($img, $temp_path);
			imagedestroy($img);

			$source_path = $temp_path;
		}

		return $this->uploadFile($source_path, $file_timestamp, 'png');
	}

	/**
	 * Get the file extension from a given file path
	 *
	 * @param string $source_path
	 * @return string
	 */
	private function getFileExtension($source_path): string
	{
		$extension = null;

		if (strpos($source_path, '.') > 0) {
			// TRY TO GET THE FILE EXTENSION IF WE DIDN'T PROVIDE ONE
			// AND THERE'S A DOT IN THE FILENAME THAT ISN'T THE FIRST CHARACTER
			$temp = explode('.', $source_path);
			$extension = end($temp);
		}

		if ($extension === null) {
			// FILE EXTENSION IS STILL NULL, RESORT TO SYSTEM COMMANDS
			exec("file -bi '" . $source_path . "'", $output);
			[$mime_type,] = explode(';', $output[0]);
			[,$extension] = explode('/', $mime_type);
		}

		return $extension;
	}

	/**
	 * Get the last modified timestamp from a given file
	 *
	 * @param string $source_path
	 * @return int
	 */
	private function getFileTimestamp($source_path): int
	{
		return filemtime($source_path) ?: time();
	}

	/**
	 * Get the existing asset_id for a given file hash, if available
	 *
	 * @param string $hash
	 * @return int|null
	 */
	private function getExistingAssetIdFromHash($hash): ?int
	{
		$query = '
			SELECT asset_id
			FROM ' . $this->tableName . '
			WHERE asset_hash = :asset_hash';
		$stmt = $this->_db->prepare($query, $this->dbName);
		$stmt->bindValue('asset_hash', $hash);
		return $stmt->execute()->fetch()['asset_id'];
	}

	/**
	 * Insert a file into the asset management system
	 *
	 * @param string $source_path
	 * @param string $extension
	 * @param string $hash
	 * @param int $file_timestamp
	 * @return bool|int
	 */
	private function insertNewAsset($source_path, $extension, $hash, $file_timestamp)
	{
		$temp = md5(microtime() . $source_path);
		$new_file = substr($temp, 0, 2) . DIRECTORY_SEPARATOR . substr($temp, 2) . '.' . $extension;

		$this->_db->beginTransaction();

		$query = '
			INSERT INTO ' . $this->tableName . '
			SET asset_path = :asset_path,
				asset_hash = :asset_hash,
				asset_timestamp = :timestamp';
		$stmt = $this->_db->prepare($query, $this->dbName);
		$stmt->bindValue('asset_path', $new_file);
		$stmt->bindValue('asset_hash', $hash);
		$stmt->bindValue('timestamp', $file_timestamp, PDO::PARAM_INT, PDO::NULL_OPT_DISALLOW);
		$stmt->execute();

		$asset_id = $this->_db->lastInsertId();

		if (!$asset_id) {
			$this->_db->rollBack();
			return false;
		}

		$new_path_parts = explode(DIRECTORY_SEPARATOR, $new_file);
		$new_dir_path = $this->basePath . $new_path_parts[0];
		$new_file_path = $new_dir_path . DIRECTORY_SEPARATOR . $new_path_parts[1];

		if ((!@mkdir($new_dir_path)) AND (!is_dir($new_dir_path))) {
			$this->_db->rollBack();
			throw new RuntimeException(sprintf('Directory "%s" was not created', $new_dir_path));
		}

		if (@copy($source_path, $new_file_path)) {
			$this->_db->commit();
		} else {
			$this->_db->rollBack();
			return false;
		}

		return (int)$asset_id;
	}
}
