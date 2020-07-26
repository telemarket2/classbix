<?php

/**
 * ClassiBase Classifieds Script
 *
 * ClassiBase Classifieds Script by Vepa Halliyev is licensed under a Creative Commons Attribution-Share Alike 3.0 License.
 *
 * @package		ClassiBase Classifieds Script
 * @author		Vepa Halliyev
 * @copyright	Copyright (c) 2009, Vepa Halliyev, veppa.com.
 * @license		http://classibase.com
 * @link		http://classibase.com
 * @since		Version 1.0
 * @filesource
 */

/**
 * class Adpics
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Adpics extends Record
{

	const TABLE_NAME = 'adpics';
	const IMAGES_MAX_WIDTH_STORE = 1200;
	const IMAGES_MAX_HEIGHT_STORE = 1200;
	const LAZY_URL_TYPE_ADPIC = 0;
	const LAZY_URL_TYPE_USER = 1;
	const LAZY_URL_TYPE_USER_ONLY = 2;

	/**
	 * 16 megapixel photo size
	 * http://web.forret.com/tools/megapixel.asp?width=4616&height=3464
	 * 
	 * Photo camera
	  4000 x 4000 (16,00) - aspect 1:1 (1,00 - square)
	  4472 x 3576 (15,99) - aspect 5:4 (1,25 - photo)
	  4512 x 3544 (15,99) - aspect 14:11 (1,27 - photo)
	  5096 x 3136 (15,98) - aspect 13:8 (1,63 - photo)
	  4616 x 3464 (15,99) - aspect 4:3 (1,33 - photo)
	  4736 x 3384 (16,03) - aspect 7:5 (1,40 - photo)
	  4896 x 3264 (15,98) - aspect 3:2 (1,50 - photo)
	  Screen
	  4616 x 3464 (15,99) - aspect 4:3 (1,33 - normal)
	  4824 x 3320 (16,02) - aspect 16:11 (1,45 - widescreen)
	  5056 x 3160 (15,98) - aspect 16:10 (1,60 - widescreen)
	  5336 x 3000 (16,01) - aspect 16:9 (1,78 - widescreen)
	  Video
	  4616 x 3464 (15,99) - aspect 4:3 (1,33 - SD)
	  5160 x 3096 (15,98) - aspect 5:3 (1,67 - Cinema)
	  5336 x 3000 (16,01) - aspect 16:9 (1,78 - HD)
	  5440 x 2944 (16,02) - aspect 1,85:1 (1,85 - Panavision)
	  5512 x 2904 (16,01) - aspect 19:10 (1,90 - Digital cinema)
	  6128 x 2608 (15,98) - aspect 2,35:1 (2,35 - old cinema)
	  6184 x 2584 (15,98) - aspect 2,39:1 (2,39 - cinemascope)
	 * 
	 * File size
	  screen
	  TYPE	FILESIZE	DESCRIPTION
	  RAW12	24,0 MB	(uncompressed 12 bits/pixel - Bayer mask)
	  RAW10	20,0 MB	(uncompressed 10 bits/pixel - Bayer mask)
	  RAW8	16,0 MB	(uncompressed 8 bits/pixel - Bayer mask)
	  BMP	48,0 MB	(uncompressed RGB 24bit/pixel)
	  TIFF	95,9 MB	(uncompressed CMYK 48bit/pixel)
	  OPENEXR	127,9 MB	(uncompressed RGBA 4 * 16bit float/pixel)
	  FILE COMPRESSION ESTIMATIONS
	  JPG100	3,0 MB	(100% quality - 24bit/pixel)
	  JPG90	1,5 MB	(90% quality - 24bit/pixel)
	  GIF	4,8 MB	(compressed - 8bit/pixel)
	  PNG	8,7 MB	(lossless compressed - 24bit/pixel)
	 * 
	 */
	// size in kb per image permitted to upload
	const IMAGES_MAX_FILESIZE = 2000;
	const IMAGES_MAX_WIDTH_UPLOAD = 5000;
	const IMAGES_MAX_HEIGHT_UPLOAD = 5000;

	static $_errors = array();
	private static $cols = array(
		'id'	 => 1,
		'ad_id'	 => 1,
		'img'	 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	function beforeDelete()
	{
		// delete img
		self::deleteImage($this->img);

		// delete record anyway 
		return true;
	}

	public static function deleteImage($img, $folder = 'adpics')
	{
		// TODO: also delete image cache 
		$file_full_name = UPLOAD_ROOT . '/' . $folder . '/' . $img;
		$return = true;
		if (is_file($file_full_name))
		{
			$return = @unlink($file_full_name);
			if (!$return)
			{
				$return = file_exists($file_full_name) ? false : true;
			}
		}

		return $return;
	}

	public static function deleteImagesByIds($ids = array(), $ad_id = 0)
	{
		// convert to quoted ids
		$ids_ = Ad::ids2quote($ids);

		if ($ids_)
		{
			//$ids = Record::checkMakeArray($ids);

			if ($ad_id)
			{
				$where_extra = 'ad_id=' . intval($ad_id) . ' AND ';
			}
			else
			{
				$where_extra = '';
			}

			$adpics = Adpics::findAllFrom('Adpics', $where_extra . 'id IN (' . implode(',', $ids_) . ')');
			foreach ($adpics as $adpic)
			{
				$adpic->delete();
			}
		}
	}

	public static function deleteUnlinkedAdImages($limit = 100)
	{
		// if some images not deleted then delete them 
		$delete_ids = array();

		$limit = intval($limit);
		if ($limit)
		{
			$limit_str = 'LIMIT ' . $limit;
		}

		// explain SELECT * FROM cb_adpics ap LEFT JOIN cb_ad a ON (ap.ad_id=a.id) WHERE a.id IS NULL LIMIT 100;
		$sql = "SELECT ap.* FROM " . self::tableNameFromClassName('Adpics') . " ap LEFT JOIN " . self::tableNameFromClassName('Ad') . " a ON (ap.ad_id=a.id) WHERE a.id IS NULL " . $limit_str;
		$records = self::query($sql, array());
		foreach ($records as $r)
		{
			self::deleteImage($r->img);
			$delete_ids[] = $r->id;
		}

		if ($delete_ids)
		{
			$ids_ = Ad::ids2quote($delete_ids);

			// delete records from db
			self::deleteWhere('Adpics', 'id IN (' . implode(',', $ids_) . ')');
		}

		return true;
	}

	/**
	 * Upload ad picture and return true or false on success. 
	 * resize uploaded image to desired size and delete original
	 * 
	 * @uses UPLOAD_ROOT
	 * @uses FileDir::checkMakeDir
	 * @uses Upload->doUpload
	 * @uses Image->resize 
	 * 
	 * @param string $field 
	 * @param string $folder  adpics, logo, site
	 * @param bool $use_random_folder  
	 * @return string image folder and name relative to UPLOAD_ROOT 
	 */
	public static function upload($field, $folder = 'adpics', $upload_config = array())
	{

		$use_random_folder = isset($upload_config['use_random_folder']) ? $upload_config['use_random_folder'] : false;

		// store images in 100 random folders to prevent 32000 file problem in one folder for linux servers.		
		list($upload_path, $random_folder) = self::buildUploadPath($folder, $use_random_folder, true);

		$upload_config['upload_path'] = $upload_path;
		$upload_config['overwrite'] = false;
		$upload_config['is_image'] = true;
		$upload_config['xss_clean'] = true;
		$upload_config['allowed_types'] = 'gif|jpg|png';
		// size in kb 
		$upload_config['max_size'] = self::getImagesMaxFilesize();
		$upload_config['max_width'] = self::IMAGES_MAX_WIDTH_UPLOAD;
		$upload_config['max_height'] = self::IMAGES_MAX_HEIGHT_UPLOAD;
		$upload_config['file_name_max_chars'] = 20;
		// use random name for ads to disable image cache in browser for images with common name
		$upload_config['encrypt_name'] = true;

		$upload = new Upload($upload_config);


		if ($upload->doUpload($field))
		{
			// uploaded. 
			// now resize image to thumb and medium size.
			$data = $upload->data();

			// img folder and file name to store in db
			$img = $random_folder . $data['file_name'];

			// return file name with random folder. store in db same way 
			return $img;
		}
		else
		{
			// set error messages to validation object
			// set errors 
			return self::_uploadError($upload);
		}
	}

	/**
	 * Crete random folder inside given folder and return "full_path" with "random_folder_name/" as array
	 * 
	 * @param string $folder
	 * @param bool $use_random_folder
	 * @param bool $create 
	 * @return array array("full_path", "random_folder_name/");
	 */
	static private function buildUploadPath($folder = 'adpics', $use_random_folder = true, $create = true)
	{
		if ($use_random_folder)
		{
			$random_folder = rand(1, 100) . '/';
		}
		else
		{
			$random_folder = '';
		}
		$upload_path = UPLOAD_ROOT . '/' . $folder . '/' . $random_folder;

		if ($create)
		{
			// create upload folder
			FileDir::checkMakeDir($upload_path);
		}

		return array($upload_path, $random_folder);
	}

	static private function _uploadError($obj)
	{
		// set errors 
		$error = trim($obj->displayErrors('', ' '));
		//echo '_uploadError:'.$error;
		return self::setError($error);
	}

	public static function getUploadErrors()
	{
		return implode(' ', self::$_errors);
	}

	/**
	 * add error to array and return false
	 * 
	 * @param string $message
	 * @return boolean
	 */
	public static function setError($message)
	{
		if (strlen(trim($message)))
		{
			self::$_errors[] = $message;
		}
		return false;
	}

	/**
	 * Resize from source to destination, source and destination should be absolute path
	 * 
	 * @param string $source absolute path
	 * @param string $destination absolute path
	 * @param string $size {width}x{height}x{crop}x{watermark}
	 * @return boolean 
	 */
	static function resizeImage($source, $destination, $size, $settings = array())
	{

		$watermark = isset($settings['watermark']) ? $settings['watermark'] : false;
		$thumbnail = isset($settings['thumbnail']) ? $settings['thumbnail'] : false;
		$force = isset($settings['force']) ? $settings['force'] : false;



		// create destination folder if not exists
		FileDir::checkMakeDir(dirname($destination));


		list($w, $h, $crop) = explode('x', $size);

		$crop = intval($crop);

		// load image to see if it is valid image, image properties read, image is not loaded here 
		$image = new SimpleImage($source);
		if (!$image)
		{
			return self::setError($image->displayErrors('', ''));
		}



		if ($w == $image->getWidth() && $h == $image->getHeight())
		{
			// do nothing because it is exact size we want
		}
		elseif ($crop == 1)
		{
			// crop to exact size
			$image->crop($w, $h);
		}
		elseif ($crop == 2)
		{
			// echo '[maxareafill]';
			// fill exact size with background color
			$image->maxareafill($w, $h, 'fff');
		}
		elseif ($w || $h)
		{
			// resize only if image is bigger than required size. it will not scale up image if it is already small
			$image->maxarea($w, $h);
		}

		if ($watermark)
		{
			// add watermark text to image
			$image->text($watermark);
		}

		if ($force)
		{
			// need to touch image to force type conversion, reduce filesize, destroy php injection if any
			$image->touch();
		}

		// if image is thumbnail, smaller than 200 (width) x 200 (height) then convert png to jpg
		// if destination is jpg then force type to jpg
		if (($thumbnail && $image->image_type == IMAGETYPE_PNG) || strcmp(FileDir::getExtension($destination), 'jpg') == 0)
		{
			// need to touch image to force type conversion
			$image->touch();

			// convert png to jpg to reduce size
			$return = $image->save($destination, IMAGETYPE_JPEG);
		}
		else
		{
			$return = $image->save($destination);
		}


		if (!$return)
		{
			// set errors
			self::setError(__('Error resizing image'));
		}

		return $return;
	}

	/**
	 * check if image already resized return image url 
	 * if not resized return custom url to resize when requested via GET request  
	 * 
	 * @param string $filename relative to UPLOAD_ROOT
	 * @param int $width
	 * @param int $height
	 * @param int $crop 0:no, 1:crop, 2:fill
	 * @param string $lazy_url_var return to resize if image is not already resized  
	 * @return string 
	 */
	public static function resizeImageLazy($filename, $width, $height, $crop = 0, $lazy_url_var = '')
	{
		$upload_root = UPLOAD_ROOT . '/';
		$upload_url = UPLOAD_URL . '/';

		$new_image = self::genCacheImgName($filename, $width, $height, $crop);
		if ($new_image === false)
		{
			// source file not found 
			return false;
		}

		if (!file_exists($upload_root . $new_image) || (file_exists($upload_root . $filename) && filemtime($upload_root . $filename) > filemtime($upload_root . $new_image)))
		{
			// return url that will resize image on request
			if (strlen($lazy_url_var))
			{
				// send $lazy_url_var = "type x id x ad_id x width x height x crop x thumb" 
				return Language::get_url('post/lazy/' . $lazy_url_var);
			}
		}

		if (file_exists($upload_root . $new_image))
		{
			// return already resized and cached image url
			return $upload_url . $new_image;
		}

		return false;
	}

	/**
	 * resize given image and return absolute url to image 
	 * 
	 * @param string $filename relative to UPLOAD_ROOT
	 * @param int $width
	 * @param int $height
	 * @param int $crop 0:no, 1:crop, 2:fill
	 * @param bool $thumbnail
	 * @return string 
	 */
	public static function resizeImageCache($filename, $width, $height, $crop = 0, $thumbnail = false)
	{
		$upload_root = UPLOAD_ROOT . '/';
		$upload_url = UPLOAD_URL . '/';

		$new_image = self::genCacheImgName($filename, $width, $height, $crop);
		if ($new_image === false)
		{
			// source file not found 
			return false;
		}
		if (!file_exists($upload_root . $new_image) || (filemtime($upload_root . $filename) > filemtime($upload_root . $new_image)))
		{
			// resize if not resized or source file updated
			$size = self::genCacheImgSizeStr($width, $height, $crop);
			$resized = self::resizeImage($upload_root . $filename, $upload_root . $new_image, $size, array('thumbnail' => $thumbnail));
			Benchmark::cp('resizeImageCache: ' . $upload_url . $new_image);
		}

		return $upload_url . $new_image;
	}

	public static function genCacheImgName($filename, $width, $height, $crop)
	{
		$upload_root = UPLOAD_ROOT . '/';
		if (!file_exists($upload_root . $filename) || !is_file($upload_root . $filename))
		{
			return false;
		}

		//$info = pathinfo($filename);
		//$extension = $info['extension'];

		$arr_filename = explode('.', $filename);
		$extension = array_pop($arr_filename);
		$name = implode('.', $arr_filename);

		$new_image = 'cache/' . str_replace("../", '', $name) . '-' . self::genCacheImgSizeStr($width, $height, $crop) . '.' . $extension;

		return $new_image;
	}

	public static function genCacheImgSizeStr($width, $height, $crop)
	{
		return $width . 'x' . $height . ($crop ? 'x' . $crop : '');
	}

	/**
	 * delete all files in cache folder. deletes ad images, dealer logos and any other files in cache folder
	 * 
	 * @return bool
	 */
	public static function clearCache()
	{
		Benchmark::cp('Adpics::clearCache()');
		$upload_root = UPLOAD_ROOT . '/';
		$cache_root = $upload_root . 'cache/';
		return FileDir::rmdirr($cache_root);
	}

	/**
	 * Generates watermark text. can add sitename, viewadshortlink
	 * 
	 * @param int $ad_id
	 * @return string 
	 */
	public static function watermarkText($ad_id)
	{
		$wt = Config::option('image_watermark_text');
		if (strlen($wt))
		{
			$arr_replace = array(
				'{@SITENAME}'		 => DOMAIN,
				'{@VIEWADLINKSHORT}' => Ad::permalinkShortById($ad_id)
			);
			$wt = str_replace(array_keys($arr_replace), $arr_replace, $wt);
		}

		return $wt;
	}

	/**
	 * Upload pictures to ad. Upload all submitted files matching $field_prefix. resizes them to max image size, saves to Adpics.
	 * if resize returns false then deletes image will not save Adpics. Returns 
	 * array(
	 * 	'num_files' => $num_files,
	  'num_uploaded' => $num_uploaded
	 * );
	 * 
	 * @param int $ad_id
	 * @param string $field_prefix
	 * @return array 
	 */
	public static function uploadToAd($ad_id, $field_prefix = 'adpic_', $image_token = '')
	{
		// number of files submitted
		$num_files = 0;
		// number of files uploaded
		$num_uploaded = 0;
		// max allowed images 
		$ad_image_num = Config::option('ad_image_num');

		// clean tmp from old files
		Adpics::tmpCleanOld();

		foreach ($_FILES as $field => $val)
		{
			$error = (!isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];

			if (strpos($field, $field_prefix) !== false && $error != 4 && $ad_image_num > $num_uploaded)
			{
				$num_files++;

				// upload to tmp
				$img = self::upload($field, 'tmp');
				if ($img !== false)
				{
					// image uploaded, now add it to db
					$src = UPLOAD_ROOT . '/tmp/' . $img;
					if (self::appendImgToAd($ad_id, $src))
					{
						$num_uploaded++;
					}
				}
			}
		}

		// check tmp for images 
		$tmp_images = Adpics::tmpImages($image_token);
		if ($tmp_images)
		{
			// process each file as they were uploaded
			// TODO move images and append to ad 
			foreach ($tmp_images as $tmp_img)
			{
				$num_files++;

				if ($ad_image_num > $num_uploaded)
				{
					if (self::appendImgToAd($ad_id, $tmp_img))
					{
						$num_uploaded++;
					}
				}
			}
		}

		// delete temp images after moving them
		Adpics::tmpDelete($image_token);

		$result = array(
			'num_files'		 => $num_files,
			'num_uploaded'	 => $num_uploaded
		);

		return $result;
	}

	/**
	 * Read tmp foilder for given token and return files as array
	 * 
	 * @param string $token
	 * @return array
	 */
	public static function tmpImages($token)
	{
		$return = array();

		// use slugify to sanitize token. we do not want ../ in tokens or any other no alpha chars
		$token = Inflector::slugify($token);

		if (strlen($token))
		{

			// check token for containing only alphanumerics. 
			$dir = UPLOAD_ROOT . '/tmp/' . $token . '/';

			if ($handle = @opendir($dir))
			{
				while (false !== ($entry = readdir($handle)))
				{
					if ($entry != "." && $entry != "..")
					{
						if (is_file($dir . $entry))
						{
							//$return[] = $dir . $entry;
							$return[$dir . $entry] = filemtime($dir . $entry);
						}
					}
				}
				closedir($handle);
			}
		}

		// sort by date modified
		if (count($return))
		{
			asort($return);
			$return = array_keys($return);
		}

		return $return;
	}

	/**
	 * delete folder with token from tmp. Use after using images 
	 * 
	 * @param string $token
	 * @return bool
	 */
	public static function tmpDelete($token)
	{
		// use slugify to sanitize token. we do not want ../ in tokens or any other no alpha chars
		$token = Inflector::slugify($token);

		if (strlen($token))
		{
			// delete token from tmp 
			return FileDir::rmdirr(UPLOAD_ROOT . '/tmp/' . $token . '/');
		}

		return true;
	}

	public static function tmpCleanOld()
	{
		// clear old uploads if they are not deleted
		if ((REQUEST_TIME % 15) == 1)
		{
			FileDir::deleteOlder(UPLOAD_ROOT . '/tmp/', 3600 * 3, 100);
		}
	}

	public static function uploadToTmp($image_token)
	{

		$image_token = Inflector::slugify(trim($image_token));
		$img_fodler = 'tmp/' . $image_token;
		$img = false;

		// clear old uploads if they are not deleted
		self::tmpCleanOld();


		if (!strlen($image_token))
		{
			return self::setError(__('Image token is not valid'));
		}


		foreach ($_FILES as $field => $val)
		{
			$error = (!isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];

			if ($error != 4)
			{
				// upload and get image filename
				$img = self::upload($field, $img_fodler);

				// resize image to check if it will resize without error
				if ($img)
				{
					// resize to max available size
					$img_path = UPLOAD_ROOT . '/' . $img_fodler . '/' . $img;
					$size = self::getImagesMaxWidth() . 'x' . self::getImagesMaxHeight();

					// resize image
					$resize = self::resizeImage($img_path, $img_path, $size);

					if (!$resize)
					{
						// delete image 
						self::deleteImage($img, $img_fodler);
						// not resized then delete image 
						return false;
					}
				}
			}
			// upload only one image 
			return $img;
		}

		return self::setError(__('No image selected for upload'));
	}

	/**
	 * delete image sent by dropzone
	 */
	public static function uploadToTmpRemove($image_token, $filename)
	{
		// append images 
		$image_token = Inflector::slugify(trim($image_token));

		// remove unsupported characters for security reason
		$filename = str_replace(array('/', '\\', '..'), '', $filename);

		// delete image from temp
		$filename_full = UPLOAD_ROOT . '/tmp/' . $image_token . '/' . $filename;
		if (is_file($filename_full))
		{
			@unlink($filename_full);
		}

		return true;
	}

	public static function uploadToAdByUrl($ad_id, $url)
	{
		// check extension
		$ext = '';
		$arr_ext = explode('.', $url);
		$ext = array_pop($arr_ext);
		$ext = strtolower($ext);
		if (!in_array($ext, array('jpg', 'jpeg', 'gif', 'png')))
		{
			// file extension is not supported
			//echo '[uploadToAdByUrl ext not valid:' . $ext . ']';
			return false;
		}

		$img_name = basename($url);

		// upload to temp then append to ad
		$dest_unique = FileDir::genFileName(UPLOAD_ROOT . '/tmp/' . $img_name);

		// image destination 
		$dest = $dest_unique['fullname'];

		// make file placeholder
		FileDir::checkMakeFile($dest);

		// upload from url and get image filename
		if (Curl::getFile($url, $dest))
		{
			// save file path to db
			$return = self::appendImgToAd($ad_id, $dest);
			return $return;
		}
		else
		{
			//echo '[file NOT uploaded:' . $url . ':' . $local_file . ']';
			return false;
		}
	}

	/**
	 * Generates unique name in random folder in adpics path and resize src. 
	 * If resized then adds record to databse. 
	 * Deletes source file in any case.
	 * 
	 * @param int $ad_id
	 * @param string $src full path to file, usually in tmp folder
	 * @return boolean
	 */
	public static function appendImgToAd($ad_id, $src)
	{
		if (is_file($src))
		{
			$basename = strtolower(basename($src));

			if (strlen($basename))
			{
				list($upload_path, $random_folder) = self::buildUploadPath('adpics', true, true);

				// adpics should be jpg or gif. if anything else then convert to jpg
				// png images from smartphones are too big for web use so convert them to jpg
				$ext = FileDir::getExtension($basename);
				if (strcmp($ext, 'jpg') != 0 && strcmp($ext, 'gif') != 0)
				{
					// set new extension to jpg
					$basename = FileDir::getName($basename) . '.jpg';
				}

				// make sure image is unique in destination folder 
				$dest_unique = FileDir::genFileName($upload_path . $basename);

				// image destination 
				$dest = $dest_unique['fullname'];
				// image name to be stored in db
				$img = $random_folder . $dest_unique['name'];


				// resize to max available size
				$size = self::getImagesMaxWidth() . 'x' . self::getImagesMaxHeight();
				$watermark = self::watermarkText($ad_id);

				// resize (force for security and reduce size) and add watermark 
				$resize = self::resizeImage($src, $dest, $size, array('watermark' => $watermark, 'force' => true));

				// delete source file usually located in temp
				@unlink($src);

				if ($resize && is_file($dest))
				{
					// resized save image
					// create new Adpics object 
					$adpics = new Adpics();
					$adpics->ad_id = $ad_id;
					$adpics->img = $img;

					if ($adpics->save('id'))
					{
						// imga resized and saved name in databse
						return true;
					}
					else
					{
						// couldnt save file. delete it from server
						@unlink($dest);
					}
				}
			}
		}

		// delete source file usually located in temp
		@unlink($src);

		return false;
	}

	/**
	 * return absolute url to ad
	 * 
	 * @param Adpics $adpic
	 * @return string 
	 */
	public static function img($adpic, $folder = 'adpics')
	{
		// return Adpics::resizeImageCache($folder . '/' . $adpic->img, $width, $height);		
		return UPLOAD_URL . '/' . $folder . '/' . $adpic->img;
	}

	/**
	 * resize image to thumb size. 
	 * set $placeholder='lazy' to lazy resize and lazy load thumb images, good to save server CPU and reduce page generation time
	 * 
	 * @param Adpics $adpic
	 * @param string $size  (width)x(height)x(crop)
	 * @param User $user used for displaying logo if no ad picture set
	 * @param bool|str $placeholder use placeholder default false|true|lazy (lazy resize and lazy load)
	 * @param Ad $ad used to get ad_id for lazy resizing and preenting oerloading server with resizinng by guessing params with algorithm
	 * @return image url or false
	 */
	public static function imgThumb($adpic, $size = '', $user = null, $placeholder = false, $ad = null)
	{
		// select first image if it is array of images 
		if (is_array($adpic))
		{
			$adpic = reset($adpic);
		}

		//pepare lazy url options 
		$options_lazy = array(
			'type'	 => self::LAZY_URL_TYPE_ADPIC,
			'id'	 => $adpic->id,
			'ad_id'	 => $ad->id,
			'width'	 => '',
			'height' => '',
			'crop'	 => 1,
			'thumb'	 => 1
		);

		$_use_logo = false;
		if ($user->logo && $user->level == User::PERMISSION_DEALER)
		{
			switch (Config::option('account_dealer_display_logo_listing'))
			{
				case 'always':
					$_use_logo = true;
					break;
				case 'no_ad_image':
					if (!$adpic)
					{
						$_use_logo = true;
					}
					break;
			}
		}

		if ($_use_logo)
		{
			$_img = User::FOLDER_LOGO . '/' . $user->logo;
			$options_lazy['type'] = self::LAZY_URL_TYPE_USER;
			$options_lazy['id'] = $user->id;
		}
		elseif (self::isImageExists($adpic, true))
		{
			$_img = 'adpics/' . $adpic->img;
		}

		// define size
		if (!strlen($size))
		{
			$width = Config::option('ad_thumbnail_width');
			$height = Config::option('ad_thumbnail_height');
			$crop = 1;
		}
		else
		{
			list($width, $height, $crop) = explode('x', $size);
		}

		// resize image
		if ($_img)
		{
			if ($_use_logo)
			{
				// if logo used then fill whitepspace
				$crop = 2;
			}

			if ($placeholder === 'lazy')
			{
				// prepare lazy url before checking file existence 
				$options_lazy['width'] = $width;
				$options_lazy['height'] = $height;
				$options_lazy['crop'] = $crop;
				$lazy_url_var = self::lazyUrlVar($options_lazy);

				return Adpics::resizeImageLazy($_img, $width, $height, intval($crop), $lazy_url_var);
			}
			else
			{
				// resize image and return url
				return Adpics::resizeImageCache($_img, $width, $height, intval($crop), true);
			}
		}

		// resize placeholder if no image returned already
		if ($placeholder && $placeholder !== 'lazy')
		{
			return self::imgPlaceholder($width, $height);
		}

		return false;
	}

	/**
	 * return url to resize this image on request if it is not already resized
	 * prepare lazy url before checking file existence 
	 * 
	 * @param array $options
	 * @return string
	 */
	public static function lazyUrlVar($options)
	{
		$options_default = array(
			'type'	 => self::LAZY_URL_TYPE_ADPIC,
			'id'	 => '',
			'ad_id'	 => '',
			'width'	 => '',
			'height' => '',
			'crop'	 => 0,
			'thumb'	 => 0
		);

		$options = array_merge($options_default, $options);

		// send $lazy_url_var = "type x id x ad_id x width x height x crop x thumb" 
		$lazy_url_var = $options['type'] . 'x'
				. intval($options['id']) . 'x'
				. intval($options['ad_id']) . 'x'
				. intval($options['width']) . 'x'
				. intval($options['height']) . 'x'
				. intval($options['crop']) . 'x'
				. intval($options['thumb']);

		return $lazy_url_var;
	}

	/**
	 * lazy resize image using url parameters $lazy_url_var = "type x id x ad_id x width x height x crop x thumb" 
	 * redirect to resized image or show empty error page
	 *  
	 * @param string $lazy_url_var
	 */
	public static function imgResizeLazy($lazy_url_var)
	{
		list($type, $id, $ad_id, $width, $height, $crop, $thumb) = explode('x', $lazy_url_var);
		$_img = '';

		$width = intval($width);
		$height = intval($height);
		$crop = intval($crop);
		$thumb = intval($thumb);

		if (!$width || !$height)
		{
			// size not set 
			header_404();
			exit('no size');
		}


		switch ($type)
		{
			case Adpics::LAZY_URL_TYPE_ADPIC:
				// get adpic 
				//$adpic = Adpics::findByIdFrom('Adpics', $id);
				$adpic = Adpics::findOneFrom('Adpics', 'id=? AND ad_id=?', array($id, $ad_id));
				if ($adpic)
				{
					if (self::isImageExists($adpic, true))
					{
						$_img = 'adpics/' . $adpic->img;
					}
				}
				break;
			case Adpics::LAZY_URL_TYPE_USER:
				$ad = Ad::findByIdFrom('Ad', $ad_id);
				if ($ad)
				{
					User::appendObject($ad, 'added_by', 'User');
					if ($ad->User->logo)
					{
						$_img = User::FOLDER_LOGO . '/' . $ad->User->logo;
					}
				}
				break;
			case Adpics::LAZY_URL_TYPE_USER_ONLY:
				$user = User::findOneFrom('User', "id=? AND added_at=?", array($id, $ad_id));
				if ($user && $user->logo)
				{
					$_img = User::FOLDER_LOGO . '/' . $user->logo;
				}
				break;
		}


		// resize image
		if ($_img)
		{
			// resize image and return url
			$img_url = Adpics::resizeImageCache($_img, $width, $height, $crop, $thumb);
			if ($img_url)
			{
				// redirect to image 
				redirect($img_url);
			}
		}

		// not resized
		header_404();
		exit('no source');
	}

	/**
	 * check if file exists on server, fix by removing record if required
	 * 
	 * @param Adpics $adpic
	 * @param boolean $fix 
	 * @return boolean
	 */
	public static function isImageExists($adpic, $fix = false)
	{
		if (!$adpic)
		{
			return false;
		}


		$filename = self::imgPath($adpic);
		$exists = file_exists($filename) && is_file($filename);

		if ($fix && !$exists)
		{
			// delete adpic 
			$adpic->delete();
		}

		return $exists;
	}

	/**
	 * Return full img location on server 
	 * 
	 * @param Adpic $adpic
	 * @return string
	 */
	public static function imgPath($adpic)
	{
		return UPLOAD_ROOT . '/adpics/' . $adpic->img;
	}

	/**
	 * return placeholder with given size
	 * 
	 * @param int $width
	 * @param int $height
	 * @return string
	 */
	public static function imgPlaceholder($width = '', $height = '')
	{
		if (!is_file(UPLOAD_ROOT . '/no-image.gif'))
		{
			copy(FROG_ROOT . '/public/images/no-image.gif', UPLOAD_ROOT . '/no-image.gif');
		}
		$_img = 'no-image.gif';

		if ($width || $height)
		{
			return Adpics::resizeImageCache($_img, $width, $height, 2);
		}

		return UPLOAD_URL . '/no-image.gif';
	}

	/**
	 * get image url resized or lazy resized.
	 * 
	 * @param type $adpic
	 * @param string $size
	 * @param int $lazy_ad_id if set then lazy url will be returned
	 * @return string
	 */
	public static function imgMed($adpic, $size = '', $lazy_ad_id = 0)
	{
		if (!strlen($size))
		{
			$width = Config::option('ad_image_width');
			$height = Config::option('ad_image_height');
			$crop = 0;
		}
		else
		{
			list($width, $height, $crop) = explode('x', $size);
		}
		$folder = 'adpics';

		if ($lazy_ad_id)
		{
			// prepare lazy url before checking file existence 
			$options_lazy = array(
				'type'	 => self::LAZY_URL_TYPE_ADPIC,
				'id'	 => $adpic->id,
				'ad_id'	 => $lazy_ad_id,
				'width'	 => $width,
				'height' => $height,
				'crop'	 => $crop,
				'thumb'	 => 0
			);

			$lazy_url_var = self::lazyUrlVar($options_lazy);

			return Adpics::resizeImageLazy($folder . '/' . $adpic->img, $width, $height, intval($crop), $lazy_url_var);
		}
		else
		{

			return Adpics::resizeImageCache($folder . '/' . $adpic->img, $width, $height, intval($crop));
		}
	}

	/**
	 * checks if in options account_dealer_display_logo_listing set to always, no_ad_image then returns true. if empty then false
	 * This is used to selectively append user to ads.
	 * 
	 * @return boolean
	 */
	public static function isUserLogoRequired()
	{
		return strlen(Config::option('account_dealer_display_logo_listing')) ? true : false;
	}

	/**
	 * return max image width to store on server
	 * 
	 * @return int
	 */
	public static function getImagesMaxWidth()
	{
		return Config::optionElseDefault('ad_image_max_width', self::IMAGES_MAX_WIDTH_STORE);
	}

	/**
	 * Return max image height to store on server
	 * 
	 * @return int
	 */
	public static function getImagesMaxHeight()
	{
		return Config::optionElseDefault('ad_image_max_height', self::IMAGES_MAX_HEIGHT_STORE);
	}

	/**
	 * Return max image height to store on server
	 * 
	 * @return int
	 */
	public static function getImagesMaxFilesize()
	{
		return Config::optionElseDefault('ad_image_max_filesize', self::IMAGES_MAX_FILESIZE);
	}

	/**
	 * render image upload fields 
	 * 
	 * @param array $settings
	 * @return string
	 */
	public static function renderUploadFields($settings = array())
	{
		$fields = '';
		$existing_images = '';
		$settings_default = array(
			'pattern'		 => '<tr><td>{TITLE}:{DESCRIPTION}</td><td>{FIELDS}</td></tr>',
			'title'			 => __('Add photos') . Config::markerRequired(Config::option('required_image')),
			'description'	 => '<br><span class="hint">' . __('Allowed {num} images, maximum size for each image {name}.', array(
				'{num}'	 => Config::option('ad_image_num'),
				'{name}' => Adpics::getImagesMaxFilesize() . 'KB'
			)) . '</span>',
			'ad_image_num'	 => Config::option('ad_image_num')
		);
		/**
		 * maxFilesize: in MB
		 * maxFiles: number of files to permit upload
		 * 
		 */
		$settings = array_merge($settings_default, $settings);

		$pattern = $settings['pattern'];
		$title = $settings['title'];
		$description = $settings['description'];
		$ad_image_num = $settings['ad_image_num'];
		$ad = $settings['ad'];
		$count_adpics = 0;


		// give in MB
		$maxFilesize = round(Adpics::getImagesMaxFilesize() / 1024, 2);
		$width = Adpics::getImagesMaxWidth();
		$height = Adpics::getImagesMaxHeight();
		$mockFiles = array();

		$image_token = '';
		if ($ad)
		{
			// use old token and show uploaded images 
			// slugify to remove ../ if exists in token
			$image_token = Inflector::slugify($ad->image_token);

			// populate existing files list 
			if ($ad->Adpics)
			{
				$count_adpics = count($ad->Adpics);

				foreach ($ad->Adpics as $ap)
				{
					$mockFiles[] = '{ name: "' . $ap->img . '", size: ' . filesize(self::imgPath($ap)) . ', imageUrl:"' . self::imgThumb($ap) . '",id:' . intval($ap->id) . '}';

					$existing_images .= '<li class="img_thumb">'
							. '<a href="' . Adpics::img($ap) . '" target="_blank">'
							. '<img src="' . Adpics::imgThumb($ap) . '"  /></a></br>'
							. '<label for="adpic_delete[' . $ap->id . ']">'
							. '<input type="checkbox" name="adpic_delete[' . $ap->id . ']" id="adpic_delete[' . $ap->id . ']"'
							. ' value="' . $ap->id . '" ' . ($_POST['adpic_delete'][$ap->id] ? 'checked="checked"' : '') . ' />'
							. __('Delete') . '</label>'
							. '</li>';
				}

				if ($existing_images)
				{
					$existing_images = '<ul class="thumb_grid clearfix">' . $existing_images . '</ul>';
				}
			}
		}

		$maxFiles = $ad_image_num - $count_adpics;

		if (strlen($image_token))
		{
			$tmp_images = Adpics::tmpImages($image_token);
			if ($tmp_images)
			{
				// show each uploaded file
				foreach ($tmp_images as $tmp_img)
				{
					$dir = UPLOAD_ROOT . '/tmp/' . $image_token . '/';
					$url = UPLOAD_URL . '/tmp/' . $image_token . '/';
					$name = str_replace($dir, '', $tmp_img);
					$tmp_img_url = $url . $name;
					$mockFiles[] = '{ name: "' . $name . '", size: ' . filesize($tmp_img) . ', imageUrl:"' . $tmp_img_url . '",id:0}';
				}
			}
		}
		else
		{
			// generate new token
			$image_token = Adpics::getImageToken();
		}


		/* if (Theme::versionSupport(Theme::VERSION_SUPPORT_JQDROPDOWN))
		  {
		  // also supports fa then render it
		  $addIcon = '<i class="fa fa-camera muted"><i>';
		  }
		  else
		  {
		  $addIcon = '+';
		  } */

		$addIcon = '+';

		$js = '<script>
					// messages 
					var dropzone_settings = {
						uploading: "' . View::escape(__('Please wait while images are being uploaded')) . '",
						/*maxFilesize:"' . $maxFilesize . '",*/
						maxFiles:"' . $ad_image_num . '",
						thumbnailWidth:90,
						thumbnailHeight:90,
						addIcon:"' . addslashes(Config::js_remove_spaces($addIcon)) . '",
						thumbnailMethod:"crop",
						resizeWidth:"' . $width . '",
						resizeHeight:"' . $height . '",
						resizeMethod:"contain",
						resizeMimeType:"image/jpeg",
						resizeQuality:0.75,
						acceptedFiles: ".jpeg,.jpg,.png,.gif",
						mockFiles:[' . implode(',', $mockFiles) . ']
					};
				</script>';

		// image upload fields
		if ($maxFiles > 0)
		{
			for ($i = 0; $i < $maxFiles; $i++)
			{
				$fields .= '<p><input type="file" name="adpic_' . $i . '" /></p>';
			}
		}// $ad_image_num>0
		// add template file upload field for dynamic adding 
		$fields = '<div class="file_upload_fields">' . $fields . '</div>'
				. '<div class="hidden file_upload_template"><p><input type="file" name="adpic_" /></p></div>';

		/**
		 * image upload dropzone integration
		 * 
		 */
		// set existing fields as fallback option 
		$fields = '<div id="myDropzone" class="dropzone">'
				. '<div class="fallback">' . $existing_images . $fields . '</div>'
				. '<input name="image_token" type="hidden" id="image_token" value="' . View::escape($image_token) . '" />'
				. $js
				. '</div>';

		$return = str_replace(array('{TITLE}', '{DESCRIPTION}', '{FIELDS}'), array($title, $description, $fields), $pattern);

		return $return;
	}

	/**
	 * get current image upload token or generate new one 
	 * 
	 * @return type
	 */
	public static function getImageToken()
	{
		// check if we returned back to submit form then use current token
		if ($_REQUEST['image_token'])
		{
			$image_token = $_REQUEST['image_token'];
		}
		else
		{
			// generate new token
			$image_token = self::genImageToken();
		}

		return $image_token;
	}

	/**
	 * Generate unique random image token for temp storing uploaded images 
	 * 
	 * @return string
	 */
	public static function genImageToken()
	{
		return User::genActivationCode('img{code}');
	}

	/**
	 * some images may be stored with empty name. fix them in batches
	 */
	public static function fixStoredEmptyImageNames()
	{
		//(SELECT * FROM `cb_adpics` WHERE `img` LIKE '%/jpg' LIMIT 1000;)
		$adpics = Adpics::findAllFrom('Adpics', 'img LIKE ? OR img LIKE ? LIMIT 1000', array('%/jpg', '%/jpg_0'));
		foreach ($adpics as $adpic)
		{
			// rename existing image 
			$old_path = UPLOAD_ROOT . '/adpics/' . $adpic->img;

			// set extension jpg because we select adpics jpgs in query
			$new_img_path = $old_path . '.jpg';
			$new_name_gen = FileDir::genFileName($new_img_path);
			$new_img_path = $new_name_gen['fullname'];

			// rename image file
			$renamed = @rename($old_path, $new_img_path);
			if ($renamed)
			{
				// save image 
				$adpic->img = str_replace('/jpg', '/', $adpic->img) . $new_name_gen['name'];
				$adpic->save();
			}
			else
			{
				// delete image
				$adpic->delete();
			}
		}
	}

}
