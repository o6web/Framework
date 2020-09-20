<?php
namespace O6\Framework\Services;

use chrisjean\PHP_ICO;
use cjrasmussen\Color\Convert;
use cjrasmussen\Color\General;
use O6\Logging\ErrorLogger;
use ZipArchive;

/**
 * Class Image
 * @package O6\Framework\Services
 */
class Image
{
	public const TextVerticalAlignNone = 0;
	public const TextVerticalAlignBottom = 1;
	public const TextVerticalAlignMiddle = 2;
	public const TextVerticalAlignTop = 3;
	public const TextHorizontalAlignNone = 0;
	public const TextHorizontalAlignLeft = 1;
	public const TextHorizontalAlignCenter = 2;
	public const TextHorizontalAlignRight = 3;

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
	public static function resize($path, $type, $dst_w, $dst_h, $bg_hex = '', $rounded = 0, $gutter = 0)
	{
		if (in_array($type, ['jpg', 'jpeg'])) {
			$src = imagecreatefromjpeg($path);
		} elseif ($type === 'gif') {
			$src = imagecreatefromgif($path);
		} else {
			$src = imagecreatefrompng($path);
		}

		if (!$src) {
			$e = new \RuntimeException('Could not create image resource');
			$detail = ['image_path' => $path, 'image_type' => $type];
			ErrorLogger::logError($_ENV['config']['db']['error_log'], $e, $detail);
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

		$src_w = imagesx($src);
		$src_h = imagesy($src);

		static::resizeFit($img, $src, $dst_w, $dst_h, $src_w, $src_h);

		imagedestroy($src);

		return $img;
	}

	/**
	 * Copy an image resource onto another image resource, resizing to fit
	 *
	 * @param resource $dst_image
	 * @param resource $src_image
	 * @param int $dst_w
	 * @param int $dst_h
	 * @param int $src_w
	 * @param int $src_h
	 * @param int $crop
	 */
	public static function resizeFit(&$dst_image, &$src_image, $dst_w, $dst_h, $src_w, $src_h, $crop = 0)
	{
		if ($crop) {
			if (($src_w / $src_h) > ($dst_w / $dst_h)) {
				// TOO WIDE
				$use_width = $dst_w * ($src_h / $dst_h);
				$use_height = $src_h;
				$src_x = ($src_w - $use_width) / 2;
				$src_y = 0;
			} elseif (($src_w / $src_h) < ($dst_w / $dst_h)) {
				// TOO TALL
				$use_width = $src_w;
				$use_height = $dst_h * ($src_w / $dst_w);
				$src_x = 0;
				$src_y = ($src_h - $use_height) / 2;
			} else {
				$src_x = 0;
				$src_y = 0;
				$use_width = $dst_w;
				$use_height = $dst_h;
			}

			$src_w = $use_width;
			$src_h = $use_height;
		} else {
			if (($src_w / $src_h) > ($dst_w / $dst_h)) {
				// TOO WIDE
				$use_width = $dst_w;
				$use_height = ($src_h / $src_w) * $dst_w;
				$dst_x = 0;
				$dst_y = ($dst_h - $use_height) / 2;
			} elseif (($src_w / $src_h) < ($dst_w / $dst_h)) {
				// TOO TALL
				$use_width = ($src_w / $src_h) * $dst_h;
				$use_height = $dst_h;
				$dst_x = ($dst_w - $use_width) / 2;
				$dst_y = 0;
			} else {
				$src_x = 0;
				$src_y = 0;
				$use_width = $dst_w;
				$use_height = $dst_h;
			}

			$dst_w = $use_width;
			$dst_h = $use_height;
		}

		imagecopyresampled($dst_image, $src_image, (int)$dst_x, (int)$dst_y, (int)$src_x, (int)$src_y,
			(int)$dst_w, (int)$dst_h, (int)$src_w, (int)$src_h);
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
	 * Get the width, height, and scale of an image resized to fit a box
	 *
	 * @param int $width
	 * @param int $height
	 * @param int $max_width
	 * @param int $max_height
	 * @return array
	 */
	public static function resizeMax($width, $height, $max_width, $max_height)
	{
		$horiz_scale = $max_width / $width;
		$vert_scale = $max_height / $height;

		if (($width > $max_width) AND ($height > $max_height)) {
			if ((abs($horiz_scale - 1)) > (abs($vert_scale - 1))) {
				$use_width = $max_width;
				$use_height = floor($height * $horiz_scale);
				$scale = floor($horiz_scale * 100);
			} else {
				$use_width = floor($width * $vert_scale);
				$use_height = $max_height;
				$scale = floor($vert_scale * 100);
			}
		} elseif ($width > $max_width) {
			$use_width = $max_width;
			$use_height = floor($height * $horiz_scale);
			$scale = floor($horiz_scale * 100);
		} else {
			$use_width = floor($width * $vert_scale);
			$use_height = $max_height;
			$scale = floor($vert_scale * 100);
		}

		if ($scale > 100) {
			$use_width = $width;
			$use_height = $height;
			$scale = 100;
		}

		return ['w' => $use_width, 'h' => $use_height, 's' => $scale];
	}

	/**
	 * Set the opacity of an image resource
	 *
	 * @param resource $img
	 * @param int $opacity
	 */
	public static function opacity(&$img, $opacity): void
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
	 * Add a block of text to an image resource
	 *
	 * @param resource $img
	 * @param string $text
	 * @param string $font
	 * @param int $size
	 * @param resource|string $color
	 * @param int $x
	 * @param int $y
	 * @param int $alignment_horizontal
	 * @param int $alignment_vertical
	 * @param int|null $max_width
	 * @param int $stroke
	 * @param resource|string|null $strokeColor
	 */
	public static function imageWriteText(
		&$img,
		$text,
		$font,
		$size,
		$color,
		$x,
		$y,
		$alignment_horizontal = self::TextHorizontalAlignLeft,
		$alignment_vertical = self::TextVerticalAlignTop,
		$max_width = null,
		$stroke = 0,
		$strokeColor = null
	): void {
		if (General::isHexColor($color)) {
			$rgb = Convert::hexToRgb($color);
			$color = imagecolorallocate($img, $rgb->R, $rgb->G, $rgb->B);
		}

		if (($strokeColor !== null) AND (General::isHexColor($strokeColor))) {
			$rgb = Convert::hexToRgb($strokeColor);
			$strokeColor = imagecolorallocate($img, $rgb->R, $rgb->G, $rgb->B);
		}

		$font_type = strrev(explode('.', strrev($font))[0]);
		$size_original = $size;
		$stroke_original = $stroke;

		$size++;
		do {
			$size--;
			$box_size = self::imageGetTextBoxSize($text, $font, $size);
			$stroke = round($stroke_original * ($size / $size_original));
			$actual_width = $box_size['width'] + ($stroke * 2);
		} while (($max_width !== null) AND ($actual_width > $max_width));

		if ($alignment_horizontal === self::TextHorizontalAlignCenter) {
			$x -= round($box_size['width'] / 2);
		} elseif ($alignment_horizontal === self::TextHorizontalAlignRight) {
			$x -= $box_size['width'];
		}

		if ($alignment_vertical === self::TextVerticalAlignMiddle) {
			$y += round($box_size['baseline'] / 2);
		} elseif ($alignment_vertical === self::TextVerticalAlignTop) {
			$y += $box_size['baseline'];
		} else {
			$y -= $box_size['height'] - $box_size['baseline'];
		}

		if (($stroke) AND ($strokeColor)) {
			if ($font_type === 'ttf') {
				self::imagettfstroketext($img, $size, 0, $x, $y, $color, $strokeColor, $font, $text, $stroke);
			} else {
				self::imageftstroketext($img, $size, 0, $x, $y, $color, $strokeColor, $font, $text, $stroke);
			}
		} elseif ($font_type === 'ttf') {
			imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
		} else {
			imagefttext($img, $size, 0, $x, $y, $color, $font, $text);
		}
	}

	/**
	 * Return the size of the box required for the given text
	 *
	 * @param string $text
	 * @param string $font
	 * @param int $size
	 * @return array
	 */
	private static function imageGetTextBoxSize($text, $font, $size): array
	{
		$font_type = strrev(explode('.', strrev($font))[0]);

		if ($font_type === 'ttf') {
			$box = imagettfbbox($size, 0, $font, $text);
		} else {
			$box = imageftbbox($size, 0, $font, $text);
		}

		$box_width = abs($box[0] - $box[2]);
		$box_height = abs($box[1] - $box[5]);

		$text = 'ABCDEF';
		if ($font_type === 'ttf') {
			$box = imagettfbbox($size, 0, $font, $text);
		} else {
			$box = imageftbbox($size, 0, $font, $text);
		}

		$default_height = abs($box[1] - $box[5]);

		return [
			'width' => $box_width,
			'height' => $box_height,
			'baseline' => $box_height - ($box_height - $default_height),
		];
	}

	/**
	 * Build a collection of favicons from a source image
	 *
	 * @param $src_path
	 * @param $tmp_path
	 * @param $bg_color
	 * @param array $sizes
	 * @param int $ico
	 * @param string $dst_file
	 * @param string|int $gutter
	 * @return array|bool
	 */
	public static function faviconCollection(
		$src_path,
		$tmp_path,
		$bg_color,
		$sizes = array(),
		$ico = 0,
		$dst_file = '',
		$gutter = 0
	): array {
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

			$ico = new PHP_ICO($tmp_dst, [[16, 16], [32, 32], [48, 48], [64, 64], [128, 128]]);
			$ico->save_ico($path);

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


	/**
	 * Writes the given text with a border into the image using TrueType fonts.
	 *
	 * @param resource $image
	 * @param int $size
	 * @param int $angle
	 * @param int $x
	 * @param int $y
	 * @param resource $textcolor
	 * @param resource $strokecolor
	 * @param string $fontfile
	 * @param string $text
	 * @param int $px
	 * @see http://johnciacia.com/2010/01/04/using-php-and-gd-to-add-border-to-text/
	 */
	private static function imagettfstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px): void
	{

		for ($c1 = ($x - abs($px)); $c1 <= ($x + abs($px)); $c1++) {
			for ($c2 = ($y - abs($px)); $c2 <= ($y + abs($px)); $c2++) {
				imagettftext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
			}
		}

		imagettftext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
	}

	/**
	 * Writes the given text with a border into the image
	 *
	 * @param resource $image
	 * @param int $size
	 * @param int $angle
	 * @param int $x
	 * @param int $y
	 * @param resource $textcolor
	 * @param resource $strokecolor
	 * @param string $fontfile
	 * @param string $text
	 * @param int $px
	 * @see http://johnciacia.com/2010/01/04/using-php-and-gd-to-add-border-to-text/
	 */
	private static function imageftstroketext(&$image, $size, $angle, $x, $y, &$textcolor, &$strokecolor, $fontfile, $text, $px): void
	{

		for ($c1 = ($x - abs($px)); $c1 <= ($x + abs($px)); $c1++) {
			for ($c2 = ($y - abs($px)); $c2 <= ($y + abs($px)); $c2++) {
				imagefttext($image, $size, $angle, $c1, $c2, $strokecolor, $fontfile, $text);
			}
		}

		imagefttext($image, $size, $angle, $x, $y, $textcolor, $fontfile, $text);
	}
}
