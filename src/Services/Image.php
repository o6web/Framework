<?php
namespace O6\Framework\Services;

use cjrasmussen\Color\Convert;
use cjrasmussen\Image\Resize;
use O6\Logging\Logger;
use O6\Logging\TraceHandler;
use Ossobuffo\PhpIco\IcoConverter;
use RuntimeException;
use ZipArchive;

/**
 * Class Image
 * @package O6\Framework\Services
 */
class Image
{
	/**
	 * Take an image file and return an image resource resized to the specified dimensions
	 *
	 * @param string $path
	 * @param string $type
	 * @param int $dst_w
	 * @param int $dst_h
	 * @param string $bg_hex
	 * @param int $rounded
	 * @param int|string $gutter
	 * @return resource
	 */
	public static function resize(string $path, string $type, int $dst_w, int $dst_h, string $bg_hex = '', int $rounded = 0, int $gutter = 0)
	{
		if (in_array($type, ['jpg', 'jpeg'])) {
			$src = imagecreatefromjpeg($path);
		} elseif ($type === 'gif') {
			$src = imagecreatefromgif($path);
		} else {
			$src = imagecreatefrompng($path);
		}

		if (!$src) {
			$e = new RuntimeException('Could not create image resource');
			$detail = ['image_path' => $path, 'image_type' => $type];
			(new Logger((new TraceHandler()), $_ENV['config']['db']['error_log']))->logError($e, $detail);
			throw $e;
		}

		if ($gutter) {
			$src = self::addGutter($src, $gutter);
		}

		$img = imagecreatetruecolor($dst_w, $dst_h);
		imagesavealpha($img, true);

		// START WITH TRANSPARENT BACKGROUND
		$bg_color = imagecolorallocatealpha($img, 0, 0, 0, 127);
		imagefill($img, 0, 0, $bg_color);

		if ($bg_hex) {
			// ADD A DEFINED BACKGROUND COLOR
			$color = Convert::hexToRgb($bg_hex);
			$bg_color = imagecolorallocate($img, $color->R, $color->G, $color->B);

			if ($rounded) {
				$bg_w = $dst_w * 10;
				$bg_h = $dst_h * 10;

				// MAKE BACKGROUND IMAGE
				$bg_img = imagecreatetruecolor($bg_w, $bg_h);
				imagesavealpha($bg_img, true);

				$trans = imagecolorallocatealpha($bg_img, 0, 0, 0, 127);
				imagefill($bg_img, 0, 0, $trans);

				// MAKE A ROUNDED SHAPE TO FILL
				$diameter = round((($bg_w > $bg_h) ? $bg_w : $bg_h) * .26);
				$radius = round($diameter / 2);

				// FOUR CORNERS
				imagearc($bg_img, $radius, $radius, $diameter, $diameter, 180, 270, $bg_color);
				imagearc($bg_img, ($bg_w - $radius), $radius, $diameter, $diameter, 270, 0, $bg_color);
				imagearc($bg_img, ($bg_w - $radius), ($bg_h - $radius), $diameter, $diameter, 0, 90, $bg_color);
				imagearc($bg_img, $radius, ($bg_h - $radius), $diameter, $diameter, 90, 180, $bg_color);

				// FOUR LINES
				imageline($bg_img, 0, $radius, 0, ($bg_w - $radius), $bg_color);
				imageline($bg_img, $radius, 0, ($bg_h - $radius), 0, $bg_color);
				imageline($bg_img, ($bg_h - 1), $radius, ($bg_h - 1), ($bg_w - $radius), $bg_color);
				imageline($bg_img, $radius, ($bg_w - 1), ($bg_h - $radius), ($bg_w - 1), $bg_color);

				// FILL THE ENTIRE SPACE FROM THE MIDDLE
				imagefill($bg_img, round($bg_w / 2), round($bg_h / 2), $bg_color);

				// COPY BACKGROUND TO IMAGE
				imagecopyresampled($img, $bg_img, 0, 0, 0, 0, $dst_w, $dst_h, $bg_w, $bg_h);

				imagedestroy($bg_img);
			} else {
				// FILL THE ENTIRE SPACE
				imagefill($img, 0, 0, $bg_color);
			}
		}

		Resize::fitToImage($img, $src);

		imagedestroy($src);

		return $img;
	}

	/**
	 * Add a gutter to an image resource
	 *
	 * @param resource $img
	 * @param string|int $gutter - Number of pixels or percentage of source dimensions
	 * @return false|resource
	 */
	public static function addGutter($img, $gutter = '10%')
	{
		$src_w = imagesx($img);
		$src_h = imagesy($img);

		if (false !== strpos($gutter, '%')) {
			$gutter = (int)$gutter;
			$gutter_x = round($src_w * ($gutter / 100));
			$gutter_y = round($src_h * ($gutter / 100));
		} else {
			$gutter = (int)$gutter;
			$gutter_x = $src_w + $gutter;
			$gutter_y = $src_h + $gutter;
		}

		$dst_w = $src_w + ($gutter_x * 2);
		$dst_h = $src_h + ($gutter_y * 2);

		$dst = imagecreatetruecolor($dst_w, $dst_h);
		imagesavealpha($img, true);

		$bg_color = imagecolorallocatealpha($dst, 0, 0, 0, 127);
		imagefill($dst, 0, 0, $bg_color);

		imagecopyresampled($dst, $img, $gutter_x, $gutter_y, 0, 0, $src_w, $src_h, $src_w, $src_h);

		imagedestroy($img);
		return $dst;
	}

	/**
	 * Set the opacity of an image resource
	 *
	 * @param resource $img
	 * @param int $opacity
	 */
	public static function opacity(&$img, int $opacity): void
	{
		$w = imagesx($img);
		$h = imagesy($img);
		imagealphablending($img, false);

		for ($x = 0; $x < $w; $x++) {
			for ($y = 0; $y < $h; $y++) {
				$color = imagecolorsforindex($img, imagecolorat($img, $x, $y));
				$color['alpha'] = (127 - round((127 - $color['alpha']) * ($opacity / 100)));
				imagesetpixel($img, $x, $y,
					imagecolorallocatealpha($img, $color['red'], $color['green'], $color['blue'], $color['alpha']));
			}
		}
	}

	/**
	 * Build a collection of favicons from a source image
	 *
	 * @param string $src_path
	 * @param string $tmp_path
	 * @param string $bg_color
	 * @param array $sizes
	 * @param int $ico
	 * @param string $dst_file
	 * @param string|int $gutter
	 * @return array|bool
	 */
	public static function faviconCollection(
		string $src_path,
		string $tmp_path,
		string $bg_color,
		array $sizes = [],
		int $ico = 0,
		string $dst_file = '',
		int $gutter = 0
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

				$img = static::resize($src_path, 'png', $size, $size, (($info['use_bg']) ? $bg_color : ''),
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

			$img = static::resize($src_path, 'png', $size, $size);
			imagepng($img, $tmp_dst);
			imagedestroy($img);

			// BUILD THE ICO FROM THE SQUARED FILE
			$path = $tmp_path . 'favicon.ico';
			$files[] = 'favicon.ico';

			$icoConverter = new IcoConverter($tmp_dst, [[16, 16], [32, 32], [48, 48], [64, 64], [128, 128]]);
			$icoConverter->saveIco($path);

			unlink($tmp_dst);
		}

		if ((count($files)) AND ($dst_file)) {
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
