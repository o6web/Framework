<?php
namespace O6\Framework\Helpers;

use O6\Framework\Helpers\Aws\S3Helper;

class S3CmdHelper
{
	/**
	 * Sync a specified assets folder to S3
	 *
	 * Requires S3 configuration
	 *
	 * @param string $folder_path
	 * @param array|null $config
	 */
	public static function syncAssets($folder_path, $config = null): void
	{
		// IF WE DIDN'T SUPPLY A CONFIG, GET IT FROM THE ENVIRONMENTAL VARIABLE
		if (!$config) {
			$config = $_ENV['config'];
		}

		// ONLY DO IT IF WE HAVE A BUCKET
		if ($config['s3']['assets_bucket']) {
			// DO THE ACTUAL SYNC
			$s3_folder = str_replace($config['assets']['root_folder'], '/', $folder_path);

			$cmd = 's3cmd --access_key=' . escapeshellarg($config['aws']['access_key']) . ' --secret_key=' . escapeshellarg($config['aws']['secret_key']) . ' --region=' . escapeshellarg($config['aws']['region']) . ' --guess-mime-type sync ' . $folder_path . ' s3://' . $config['s3']['assets_bucket'] . $s3_folder;
			exec($cmd);

			// CLEANUP MIME TYPES THAT S3CMD BREAKS
			foreach (['css', 'js'] AS $type) {
				(new S3Helper($config))->resetBucketMimeType($config['s3']['assets_bucket']. $type, $s3_folder);
			}
		}
	}
}