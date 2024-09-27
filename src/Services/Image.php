<?php

namespace O6\Framework\Services;

use cjrasmussen\Image\Resize;
use Ossobuffo\PhpIco\IcoConverter;
use ZipArchive;

/**
 * Class Image
 * @package O6\Framework\Services
 */
class Image
{
	/**
	 * Build a collection of favicons from a source image
	 *
	 * @param string $src_path
	 * @param string $tmp_path
	 * @param string $bg_color
	 * @param array|null $sizes
	 * @param bool|null $ico
	 * @param string|null $dst_file
	 * @param string|int|null $gutter
	 * @return array|bool
	 */
	public static function faviconCollection(
		string $src_path,
		string $tmp_path,
		string $bg_color,
		?array $sizes = [],
		?bool $ico = false,
		?string $dst_file = '',
		$gutter = 0
	) {
		if (!file_exists($src_path)) {
			return false;
		}

		$size_info = [
			57 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPhone(first generation or 2G), iPhone 3G, iPhone 3GS
			70 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'windows'],
			// windows phone small tile
			76 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPad and iPad mini @1x
			100 => ['use_bg' => 0, 'rounded' => 0, 'prefix' => 'favicon'],
			// social media
			114 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPhone something?
			120 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPhone 6/7, iPhone 6/7s, iPhone SE
			128 => ['use_bg' => 1, 'rounded' => 1, 'prefix' => 'favicon'],
			// Android Regular
			144 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPhone something?
			150 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'windows'],
			// windows phone medium tile
			152 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPad / iPad Mini
			167 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPad Pro
			180 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'apple-touch-icon'],
			// iPhone 6/7 Plus, iPhone 6/7s Plus
			192 => ['use_bg' => 1, 'rounded' => 1, 'prefix' => 'favicon'],
			// Android Hi-Res
			200 => ['use_bg' => 0, 'rounded' => 0, 'prefix' => 'favicon'],
			// social media
			310 => ['use_bg' => 1, 'rounded' => 0, 'prefix' => 'windows'],
			// windows phone large tile
		];

		$files = [];

		$bg_color = trim($bg_color, ' #;');

		foreach ($size_info AS $size => $info) {
			if (in_array($size, $sizes, false)) {
				// GENERATE NEW IMAGE
				$files[] = $info['prefix'] . '-' . str_pad($size, 3, '0', STR_PAD_LEFT) . '.png';
				$path = $tmp_path . $info['prefix'] . '-' . str_pad($size, 3, '0', STR_PAD_LEFT) . '.png';

				$img = Resize::resize($src_path, 'png', $size, $size, (($info['use_bg']) ? $bg_color : ''),
					$info['rounded'], $gutter);
				imagepng($img, $path);
				imagedestroy($img);
			}
		}

		if ($ico) {
			// MAKE A SQUARED-OFF VERSION OF THE SOURCE FILE
			$tmp_dst = $tmp_path . 'temp.png';
			[$width, $height] = getimagesize($src_path);
			$size = ($width > $height) ? $width : $height;

			$img = Resize::resize($src_path, 'png', $size, $size);
			imagepng($img, $tmp_dst);
			imagedestroy($img);

			// BUILD THE ICO FROM THE SQUARED FILE
			$path = $tmp_path . 'favicon.ico';
			$files[] = 'favicon.ico';

			$icoConverter = new IcoConverter($tmp_dst, [[16, 16], [32, 32], [48, 48], [64, 64], [128, 128]]);
			$icoConverter->saveIco($path);

			unlink($tmp_dst);
		}

		if (($dst_file) && (count($files))) {
			// EXPORT IMAGES TO ZIP
			chdir($tmp_path);

			if (file_exists($dst_file)) {
				unlink($dst_file);
			}

			$zip = new ZipArchive;
			$zip->open($dst_file, ZipArchive::CREATE);

			foreach ($files AS $file) {
				$zip->addFile($file);
			}

			$zip->close();

			chdir(__DIR__);

			// DELETE EXPORTED FILES
			foreach ($files AS $file) {
				unlink($tmp_path . $file);
			}

			return [$dst_file];
		}

		return $files;
	}
}
