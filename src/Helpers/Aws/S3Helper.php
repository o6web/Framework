<?php

namespace O6\Framework\Helpers\Aws;

use Aws\S3\S3Client;
use Exception;
use InvalidArgumentException;
use O6\Framework\Services\PDO\ExceptionLogger;

/**
 * Class S3
 * @package O6\Framework\Helpers\Aws
 */
class S3Helper
{
	private $s3;
	private $errorLog;

	/**
	 * S3Helper constructor.
	 * @param array|null $config
	 */
	public function __construct($config = null)
	{
		if (!$config) {
			throw new InvalidArgumentException('Configuration array must be provided.');
		}

		// TODO: FIND A BETTER WAY TO DO THIS?
		$this->errorLog = $config['db']['error_log'];

		try {
			$this->s3 = S3Client::factory([
				'key' => $config['aws']['access_key'],
				'secret' => $config['aws']['secret_key'],
				'region' => $config['aws']['region']
			]);
		} catch (Exception $e) {
			ExceptionLogger::logError($e, $this->errorLog);
		}
	}


	/**
	 * Copy a specified file to an S3 bucket/path
	 * 
	 * @param string $bucket
	 * @param string $path
	 * @param string $file
	 * @param bool $allow_overwrite
	 * @param bool $remove_source
	 */
	public function createObjectFromFile($bucket, $path, $file, $allow_overwrite = true, $remove_source = false): void
	{
		if ((!$this->s3->doesObjectExist($bucket, $path)) OR ($allow_overwrite)) {
			$this->s3->putObject(['Bucket' => $bucket, 'Key' => $path, 'SourceFile' => $file]);
		}

		if ($remove_source) {
			@unlink($file);
		}
	}

	/**
	 * Delete a specified object (or array of objects) from a specified S3 bucket
	 * 
	 * @param string $bucket
	 * @param string|array $files
	 */
	public function deleteObject($bucket, $files): void
	{
		if (!is_array($files)) {
			$files = [$files];
		}

		foreach ($files AS $file) {
			if ($this->s3->doesObjectExist($bucket, $file)) {
				$this->s3->deleteObject(['Bucket' => $bucket, 'Key' => $file]);
			}
		}
	}

	/**
	 * Reset MIME types fro a given file format in a given bucket
	 *
	 * @param string $bucket
	 * @param string $extension
	 * @param string $prefix
	 */
	public function resetBucketMimeType($bucket, $extension, $prefix = ''): void
	{
		if (strpos($prefix, '/') === 0) {
			$prefix = substr($prefix, 1);
		}

		if ($extension[0] === '.') {
			$extension = substr($extension, 1);
		}

		$mime_type = self::getFilenameMimeType($extension);

		if ($mime_type) {
			$extension = '.' . $extension;

			$result = $this->s3->getIterator('ListObjects', ['Bucket' => $bucket, 'Prefix' => (string)$prefix]);
			foreach ($result AS $obj) {
				if (substr($obj['Key'], (strlen($extension) * -1)) === $extension) {
					$this->s3->copyObject([
						'Bucket' => $bucket,
						'CopySource' => ($bucket . '/' . $obj['Key']),
						'Key' => $obj['Key'],
						'ContentType' => $mime_type,
						'MetadataDirective' => 'REPLACE'
					]);
				}
			}
		}
	}

	/**
	 * Get the mime type of a file based on its filename
	 *
	 * @param string $filename
	 * @return string
	 */
	public static function getFilenameMimeType($filename): string
	{
		$parts = explode('.', $filename);
		$ext = $parts[(count($parts) - 1)];

		$types = [];
		$types['3dm'] = 'x-world/x-3dmf';
		$types['3dmf'] = 'x-world/x-3dmf';
		$types['a'] = 'application/octet-stream';
		$types['aab'] = 'application/x-authorware-bin';
		$types['aam'] = 'application/x-authorware-map';
		$types['aas'] = 'application/x-authorware-seg';
		$types['abc'] = 'text/vnd.abc';
		$types['acgi'] = 'text/html';
		$types['afl'] = 'video/animaflex';
		$types['ai'] = 'application/postscript';
		$types['aif'] = 'audio/aiff';
		$types['aifc'] = 'audio/aiff';
		$types['aiff'] = 'audio/aiff';
		$types['aim'] = 'application/x-aim';
		$types['aip'] = 'text/x-audiosoft-intra';
		$types['ani'] = 'application/x-navi-animation';
		$types['aos'] = 'application/x-nokia-9000-communicator-add-on-software';
		$types['aps'] = 'application/mime';
		$types['arc'] = 'application/octet-stream';
		$types['arj'] = 'application/arj';
		$types['art'] = 'image/x-jg';
		$types['asf'] = 'video/x-ms-asf';
		$types['asm'] = 'text/x-asm';
		$types['asp'] = 'text/asp';
		$types['asx'] = 'application/x-mplayer2';
		$types['au'] = 'audio/basic';
		$types['avi'] = 'video/avi';
		$types['avs'] = 'video/avs-video';
		$types['bcpio'] = 'application/x-bcpio';
		$types['bin'] = 'application/octet-stream';
		$types['bm'] = 'image/bmp';
		$types['bmp'] = 'image/bmp';
		$types['boo'] = 'application/book';
		$types['book'] = 'application/book';
		$types['boz'] = 'application/x-bzip2';
		$types['bsh'] = 'application/x-bsh';
		$types['bz'] = 'application/x-bzip';
		$types['bz2'] = 'application/x-bzip2';
		$types['c'] = 'text/x-c';
		$types['c++'] = 'text/plain';
		$types['cat'] = 'application/vnd.ms-pki.seccat';
		$types['cc'] = 'text/x-c';
		$types['ccad'] = 'application/clariscad';
		$types['cco'] = 'application/x-cocoa';
		$types['cdf'] = 'application/cdf';
		$types['cer'] = 'application/pkix-cert';
		$types['cha'] = 'application/x-chat';
		$types['chat'] = 'application/x-chat';
		$types['class'] = 'application/java';
		$types['com'] = 'application/octet-stream';
		$types['conf'] = 'text/plain';
		$types['cpio'] = 'application/x-cpio';
		$types['cpp'] = 'text/x-c';
		$types['cpt'] = 'application/mac-compactpro';
		$types['crl'] = 'application/pkcs-crl';
		$types['crt'] = 'application/pkix-cert';
		$types['csh'] = 'application/x-csh';
		$types['css'] = 'text/css';
		$types['cxx'] = 'text/plain';
		$types['dcr'] = 'application/x-director';
		$types['deepv'] = 'application/x-deepv';
		$types['def'] = 'text/plain';
		$types['der'] = 'application/x-x509-ca-cert';
		$types['dif'] = 'video/x-dv';
		$types['dir'] = 'application/x-director';
		$types['dl'] = 'video/dl';
		$types['doc'] = 'application/msword';
		$types['dot'] = 'application/msword';
		$types['dp'] = 'application/commonground';
		$types['drw'] = 'application/drafting';
		$types['dump'] = 'application/octet-stream';
		$types['dv'] = 'video/x-dv';
		$types['dvi'] = 'application/x-dvi';
		$types['dwf'] = 'model/vnd.dwf';
		$types['dwg'] = 'image/x-dwg';
		$types['dxf'] = 'application/dxf';
		$types['dxr'] = 'application/x-director';
		$types['el'] = 'text/x-script.elisp';
		$types['elc'] = 'application/x-elc';
		$types['env'] = 'application/x-envoy';
		$types['eps'] = 'application/postscript';
		$types['es'] = 'application/x-esrehber';
		$types['etx'] = 'text/x-setext';
		$types['evy'] = 'application/envoy';
		$types['exe'] = 'application/octet-stream';
		$types['f'] = 'text/x-fortran';
		$types['f77'] = 'text/x-fortran';
		$types['f90'] = 'text/x-fortran';
		$types['fdf'] = 'application/vnd.fdf';
		$types['fif'] = 'application/fractals';
		$types['fli'] = 'video/fli';
		$types['flo'] = 'image/florian';
		$types['flx'] = 'text/vnd.fmi.flexstor';
		$types['fmf'] = 'video/x-atomic3d-feature';
		$types['for'] = 'text/plain';
		$types['fpx'] = 'image/vnd.fpx';
		$types['frl'] = 'application/freeloader';
		$types['funk'] = 'audio/make';
		$types['g'] = 'text/plain';
		$types['g3'] = 'image/g3fax';
		$types['gif'] = 'image/gif';
		$types['gl'] = 'video/gl';
		$types['gsd'] = 'audio/x-gsm';
		$types['gsm'] = 'audio/x-gsm';
		$types['gsp'] = 'application/x-gsp';
		$types['gss'] = 'application/x-gss';
		$types['gtar'] = 'application/x-gtar';
		$types['gz'] = 'application/x-gzip';
		$types['gzip'] = 'application/x-gzip';
		$types['h'] = 'text/x-h';
		$types['hdf'] = 'application/x-hdf';
		$types['help'] = 'application/x-helpfile';
		$types['hgl'] = 'application/vnd.hp-hpgl';
		$types['hh'] = 'text/x-h';
		$types['hlb'] = 'text/x-script';
		$types['hlp'] = 'application/x-helpfile';
		$types['hpg'] = 'application/vnd.hp-hpgl';
		$types['hpgl'] = 'application/vnd.hp-hpgl';
		$types['hqx'] = 'application/binhex';
		$types['hta'] = 'application/hta';
		$types['htc'] = 'text/x-component';
		$types['htm'] = 'text/html';
		$types['html'] = 'text/html';
		$types['htmls'] = 'text/html';
		$types['htt'] = 'text/webviewhtml';
		$types['htx'] = 'text/html';
		$types['ice'] = 'x-conference/x-cooltalk';
		$types['ico'] = 'image/x-icon';
		$types['idc'] = 'text/plain';
		$types['ief'] = 'image/ief';
		$types['iefs'] = 'image/ief';
		$types['iges'] = 'model/iges';
		$types['igs'] = 'model/iges';
		$types['ima'] = 'application/x-ima';
		$types['imap'] = 'application/x-httpd-imap';
		$types['inf'] = 'application/inf';
		$types['ins'] = 'application/x-internett-signup';
		$types['ip'] = 'application/x-ip2';
		$types['isu'] = 'video/x-isvideo';
		$types['it'] = 'audio/it';
		$types['iv'] = 'application/x-inventor';
		$types['ivr'] = 'i-world/i-vrml';
		$types['ivy'] = 'application/x-livescreen';
		$types['jam'] = 'audio/x-jam';
		$types['jav'] = 'text/x-java-source';
		$types['java'] = 'text/x-java-source';
		$types['jcm'] = 'application/x-java-commerce';
		$types['jfif'] = 'image/jpeg';
		$types['jfif-tbnl'] = 'image/jpeg';
		$types['jpe'] = 'image/jpeg';
		$types['jpeg'] = 'image/jpeg';
		$types['jpg'] = 'image/jpeg';
		$types['jps'] = 'image/x-jps';
		$types['js'] = 'application/javascript';
		$types['jut'] = 'image/jutvision';
		$types['kar'] = 'music/x-karaoke';
		$types['ksh'] = 'text/x-script.ksh';
		$types['la'] = 'audio/nspaudio';
		$types['lam'] = 'audio/x-liveaudio';
		$types['latex'] = 'application/x-latex';
		$types['lha'] = 'application/lha';
		$types['lhx'] = 'application/octet-stream';
		$types['list'] = 'text/plain';
		$types['lma'] = 'audio/nspaudio';
		$types['log'] = 'text/plain';
		$types['lsp'] = 'application/x-lisp';
		$types['lst'] = 'text/plain';
		$types['lsx'] = 'text/x-la-asf';
		$types['ltx'] = 'application/x-latex';
		$types['lzh'] = 'application/x-lzh';
		$types['lzx'] = 'application/lzx';
		$types['m'] = 'text/x-m';
		$types['m1v'] = 'video/mpeg';
		$types['m2a'] = 'audio/mpeg';
		$types['m2v'] = 'video/mpeg';
		$types['m3u'] = 'audio/x-mpequrl';
		$types['man'] = 'application/x-troff-man';
		$types['map'] = 'application/x-navimap';
		$types['mar'] = 'text/plain';
		$types['mbd'] = 'application/mbedlet';
		$types['mc$'] = 'application/x-magic-cap-package-1.0';
		$types['mcd'] = 'application/mcad';
		$types['mcf'] = 'image/vasa';
		$types['mcp'] = 'application/netmc';
		$types['me'] = 'application/x-troff-me';
		$types['mht'] = 'message/rfc822';
		$types['mhtml'] = 'message/rfc822';
		$types['mid'] = 'audio/midi';
		$types['midi'] = 'audio/midi';
		$types['mif'] = 'application/x-mif';
		$types['mime'] = 'www/mime';
		$types['mjf'] = 'audio/x-vnd.audioexplosion.mjuicemediafile';
		$types['mjpg'] = 'video/x-motion-jpeg';
		$types['mm'] = 'application/base64';
		$types['mme'] = 'application/base64';
		$types['mod'] = 'audio/mod';
		$types['moov'] = 'video/quicktime';
		$types['mov'] = 'video/quicktime';
		$types['movie'] = 'video/x-sgi-movie';
		$types['mp2'] = 'audio/mpeg';
		$types['mp3'] = 'audio/mpeg3';
		$types['mpa'] = 'audio/mpeg';
		$types['mpc'] = 'application/x-project';
		$types['mpe'] = 'video/mpeg';
		$types['mpeg'] = 'video/mpeg';
		$types['mpg'] = 'video/mpeg';
		$types['mpga'] = 'audio/mpeg';
		$types['mpp'] = 'application/vnd.ms-project';
		$types['mpt'] = 'application/x-project';
		$types['mpv'] = 'application/x-project';
		$types['mpx'] = 'application/x-project';
		$types['mrc'] = 'application/marc';
		$types['ms'] = 'application/x-troff-ms';
		$types['mv'] = 'video/x-sgi-movie';
		$types['my'] = 'audio/make';
		$types['mzz'] = 'application/x-vnd.audioexplosion.mzz';
		$types['nap'] = 'image/naplps';
		$types['naplps'] = 'image/naplps';
		$types['nc'] = 'application/x-netcdf';
		$types['ncm'] = 'application/vnd.nokia.configuration-message';
		$types['nif'] = 'image/x-niff';
		$types['niff'] = 'image/x-niff';
		$types['nix'] = 'application/x-mix-transfer';
		$types['nsc'] = 'application/x-conference';
		$types['nvd'] = 'application/x-navidoc';
		$types['o'] = 'application/octet-stream';
		$types['oda'] = 'application/oda';
		$types['omc'] = 'application/x-omc';
		$types['omcd'] = 'application/x-omcdatamaker';
		$types['omcr'] = 'application/x-omcregerator';
		$types['p'] = 'text/x-pascal';
		$types['p10'] = 'application/pkcs10';
		$types['p12'] = 'application/pkcs-12';
		$types['p7a'] = 'application/x-pkcs7-signature';
		$types['p7c'] = 'application/pkcs7-mime';
		$types['p7m'] = 'application/pkcs7-mime';
		$types['p7r'] = 'application/x-pkcs7-certreqresp';
		$types['p7s'] = 'application/pkcs7-signature';
		$types['part'] = 'application/pro_eng';
		$types['pas'] = 'text/pascal';
		$types['pbm'] = 'image/x-portable-bitmap';
		$types['pcl'] = 'application/vnd.hp-pcl';
		$types['pct'] = 'image/x-pict';
		$types['pcx'] = 'image/x-pcx';
		$types['pdb'] = 'chemical/x-pdb';
		$types['pdf'] = 'application/pdf';
		$types['pfunk'] = 'audio/make';
		$types['pgm'] = 'image/x-portable-graymap';
		$types['pic'] = 'image/pict';
		$types['pict'] = 'image/pict';
		$types['pkg'] = 'application/x-newton-compatible-pkg';
		$types['pko'] = 'application/vnd.ms-pki.pko';
		$types['pl'] = 'text/plain';
		$types['plx'] = 'application/x-pixclscript';
		$types['pm'] = 'text/x-script.perl-module';
		$types['pm4'] = 'application/x-pagemaker';
		$types['pm5'] = 'application/x-pagemaker';
		$types['png'] = 'image/png';
		$types['pnm'] = 'image/x-portable-anymap';
		$types['pot'] = 'application/mspowerpoint';
		$types['pov'] = 'model/x-pov';
		$types['ppa'] = 'application/vnd.ms-powerpoint';
		$types['ppm'] = 'image/x-portable-pixmap';
		$types['pps'] = 'application/vnd.ms-powerpoint';
		$types['ppt'] = 'application/vnd.ms-powerpoint';
		$types['ppz'] = 'application/mspowerpoint';
		$types['pre'] = 'application/x-freelance';
		$types['prt'] = 'application/pro_eng';
		$types['ps'] = 'application/postscript';
		$types['psd'] = 'application/octet-stream';
		$types['pvu'] = 'paleovu/x-pv';
		$types['pwz'] = 'application/vnd.ms-powerpoint';
		$types['py'] = 'text/x-script.phyton';
		$types['pyc'] = 'applicaiton/x-bytecode.python';
		$types['qcp'] = 'audio/vnd.qcelp';
		$types['qd3'] = 'x-world/x-3dmf';
		$types['qd3d'] = 'x-world/x-3dmf';
		$types['qif'] = 'image/x-quicktime';
		$types['qt'] = 'video/quicktime';
		$types['qtc'] = 'video/x-qtc';
		$types['qti'] = 'image/x-quicktime';
		$types['qtif'] = 'image/x-quicktime';
		$types['ra'] = 'audio/x-realaudio';
		$types['ram'] = 'audio/x-pn-realaudio';
		$types['ras'] = 'image/cmu-raster';
		$types['rast'] = 'image/cmu-raster';
		$types['rexx'] = 'text/x-script.rexx';
		$types['rf'] = 'image/vnd.rn-realflash';
		$types['rgb'] = 'image/x-rgb';
		$types['rm'] = 'application/vnd.rn-realmedia';
		$types['rmi'] = 'audio/mid';
		$types['rmm'] = 'audio/x-pn-realaudio';
		$types['rmp'] = 'audio/x-pn-realaudio';
		$types['rng'] = 'application/vnd.nokia.ringing-tone';
		$types['rnx'] = 'application/vnd.rn-realplayer';
		$types['roff'] = 'application/x-troff';
		$types['rp'] = 'image/vnd.rn-realpix';
		$types['rpm'] = 'audio/x-pn-realaudio-plugin';
		$types['rt'] = 'text/vnd.rn-realtext';
		$types['rtf'] = 'text/richtext';
		$types['rtx'] = 'text/richtext';
		$types['rv'] = 'video/vnd.rn-realvideo';
		$types['s'] = 'text/x-asm';
		$types['s3m'] = 'audio/s3m';
		$types['saveme'] = 'application/octet-stream';
		$types['sbk'] = 'application/x-tbook';
		$types['scm'] = 'application/x-lotusscreencam';
		$types['sdml'] = 'text/plain';
		$types['sdp'] = 'application/sdp';
		$types['sdr'] = 'application/sounder';
		$types['sea'] = 'application/sea';
		$types['set'] = 'application/set';
		$types['sgm'] = 'text/sgml';
		$types['sgml'] = 'text/sgml';
		$types['sh'] = 'application/x-bsh';
		$types['shar'] = 'application/x-bsh';
		$types['shtml'] = 'text/x-server-parsed-html';
		$types['sid'] = 'audio/x-psid';
		$types['sit'] = 'application/x-sit';
		$types['skd'] = 'application/x-koan';
		$types['skm'] = 'application/x-koan';
		$types['skp'] = 'application/x-koan';
		$types['skt'] = 'application/x-koan';
		$types['sl'] = 'application/x-seelogo';
		$types['smi'] = 'application/smil';
		$types['smil'] = 'application/smil';
		$types['snd'] = 'audio/basic';
		$types['sol'] = 'application/solids';
		$types['spc'] = 'application/x-pkcs7-certificates';
		$types['spl'] = 'application/futuresplash';
		$types['spr'] = 'application/x-sprite';
		$types['sprite'] = 'application/x-sprite';
		$types['src'] = 'application/x-wais-source';
		$types['ssi'] = 'text/x-server-parsed-html';
		$types['ssm'] = 'application/streamingmedia';
		$types['sst'] = 'application/vnd.ms-pki.certstore';
		$types['step'] = 'application/step';
		$types['stl'] = 'application/vnd.ms-pki.stl';
		$types['stp'] = 'application/step';
		$types['sv4cpio'] = 'application/x-sv4cpio';
		$types['sv4crc'] = 'application/x-sv4crc';
		$types['svf'] = 'image/vnd.dwg';
		$types['swf'] = 'application/x-shockwave-flash';
		$types['t'] = 'application/x-troff';
		$types['talk'] = 'text/x-speech';
		$types['tar'] = 'application/x-tar';
		$types['tbk'] = 'application/toolbook';
		$types['tcl'] = 'application/x-tcl';
		$types['tcsh'] = 'text/x-script.tcsh';
		$types['tex'] = 'application/x-tex';
		$types['texi'] = 'application/x-texinfo';
		$types['texinfo'] = 'application/x-texinfo';
		$types['text'] = 'text/plain';
		$types['tgz'] = 'application/gnutar';
		$types['tif'] = 'image/tiff';
		$types['tiff'] = 'image/tiff';
		$types['tr'] = 'application/x-troff';
		$types['tsi'] = 'audio/tsp-audio';
		$types['tsp'] = 'audio/tsplayer';
		$types['tsv'] = 'text/tab-separated-values';
		$types['turbot'] = 'image/florian';
		$types['txt'] = 'text/plain';
		$types['uil'] = 'text/x-uil';
		$types['uni'] = 'text/uri-list';
		$types['unis'] = 'text/uri-list';
		$types['unv'] = 'application/i-deas';
		$types['uri'] = 'text/uri-list';
		$types['uris'] = 'text/uri-list';
		$types['ustar'] = 'application/x-ustar';
		$types['uu'] = 'text/x-uuencode';
		$types['uue'] = 'text/x-uuencode';
		$types['vcd'] = 'application/x-cdlink';
		$types['vcs'] = 'text/x-vcalendar';
		$types['vda'] = 'application/vda';
		$types['vdo'] = 'video/vdo';
		$types['vew'] = 'application/groupwise';
		$types['viv'] = 'video/vnd.vivo';
		$types['vivo'] = 'video/vnd.vivo';
		$types['vmd'] = 'application/vocaltec-media-desc';
		$types['vmf'] = 'application/vocaltec-media-file';
		$types['voc'] = 'audio/voc';
		$types['vos'] = 'video/vosaic';
		$types['vox'] = 'audio/voxware';
		$types['vqe'] = 'audio/x-twinvq-plugin';
		$types['vqf'] = 'audio/x-twinvq';
		$types['vql'] = 'audio/x-twinvq-plugin';
		$types['vrml'] = 'application/x-vrml';
		$types['vrt'] = 'x-world/x-vrt';
		$types['vsd'] = 'application/x-visio';
		$types['vst'] = 'application/x-visio';
		$types['vsw'] = 'application/x-visio';
		$types['w60'] = 'application/wordperfect6.0';
		$types['w61'] = 'application/wordperfect6.1';
		$types['w6w'] = 'application/msword';
		$types['wav'] = 'audio/wav';
		$types['wb1'] = 'application/x-qpro';
		$types['wbmp'] = 'image/vnd.wap.wbmp';
		$types['web'] = 'application/vnd.xara';
		$types['wiz'] = 'application/msword';
		$types['wk1'] = 'application/x-123';
		$types['wmf'] = 'windows/metafile';
		$types['wml'] = 'text/vnd.wap.wml';
		$types['wmlc'] = 'application/vnd.wap.wmlc';
		$types['wmls'] = 'text/vnd.wap.wmlscript';
		$types['wmlsc'] = 'application/vnd.wap.wmlscriptc';
		$types['word'] = 'application/msword';
		$types['wp'] = 'application/wordperfect';
		$types['wp5'] = 'application/wordperfect';
		$types['wp6'] = 'application/wordperfect';
		$types['wpd'] = 'application/wordperfect';
		$types['wq1'] = 'application/x-lotus';
		$types['wri'] = 'application/mswrite';
		$types['wrl'] = 'model/vrml';
		$types['wrz'] = 'model/vrml';
		$types['wsc'] = 'text/scriplet';
		$types['wsrc'] = 'application/x-wais-source';
		$types['wtk'] = 'application/x-wintalk';
		$types['xbm'] = 'image/xbm';
		$types['xdr'] = 'video/x-amt-demorun';
		$types['xgz'] = 'xgl/drawing';
		$types['xif'] = 'image/vnd.xiff';
		$types['xl'] = 'application/excel';
		$types['xla'] = 'application/excel';
		$types['xlb'] = 'application/vnd.ms-excel';
		$types['xlc'] = 'application/vnd.ms-excel';
		$types['xld'] = 'application/excel';
		$types['xlk'] = 'application/excel';
		$types['xll'] = 'application/vnd.ms-excel';
		$types['xlm'] = 'application/vnd.ms-excel';
		$types['xls'] = 'application/vnd.ms-excel';
		$types['xlt'] = 'application/excel';
		$types['xlv'] = 'application/excel';
		$types['xlw'] = 'application/vnd.ms-excel';
		$types['xm'] = 'audio/xm';
		$types['xml'] = 'text/xml';
		$types['xmz'] = 'xgl/movie';
		$types['xpix'] = 'application/x-vnd.ls-xpix';
		$types['xpm'] = 'image/xpm';
		$types['x-png'] = 'image/png';
		$types['xsr'] = 'video/x-amt-showrun';
		$types['xwd'] = 'image/x-xwd';
		$types['xyz'] = 'chemical/x-pdb';
		$types['z'] = 'application/x-compressed';
		$types['zip'] = 'application/x-compressed';
		$types['zoo'] = 'application/octet-stream';
		$types['zsh'] = 'text/x-script.zsh';

		return $types[$ext];
	}
}
