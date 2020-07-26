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
/*


  $config['upload_path'] = './uploads/';
  $config['allowed_types'] = 'gif|jpg|png';
  $config['max_size']	= '100';
  $config['max_width']  = '1024';
  $config['max_height']  = '768';

  $upload = new Upload($config);

  $upload->doUpload('field');


 */
class Upload
{

	public $max_size = 0;
	public $max_width = 0;
	public $max_height = 0;
	public $file_name_max_chars = 50;
	public $allowed_types = "";
	public $file_temp = "";
	public $file_name = "";
	public $orig_name = "";
	public $file_type = "";
	public $file_size = "";
	public $file_ext = "";
	public $upload_path = "";
	public $overwrite = false;
	public $encrypt_name = false;
	public $is_image = false;
	public $image_width = '';
	public $image_height = '';
	public $image_type = '';
	public $image_size_str = '';
	public $error_msg = array();
	public $remove_spaces = true;
	public $xss_clean = false;
	public $temp_prefix = "temp_file_";
	static private $mimes;

	/**
	 * Constructor
	 *
	 * @access  public
	 */
	function __construct($props = array())
	{
		if (count($props) > 0)
		{
			$this->initialize($props);
		}

		//log_debug("Upload Class Initialized");
	}

	public static function msg($key)
	{
		$lang['upload_userfile_not_set'] = __('Unable to find a post variable called userfile.');
		$lang['upload_file_exceeds_limit'] = __('The uploaded file exceeds the maximum allowed size in your PHP configuration file.');
		$lang['upload_file_exceeds_form_limit'] = __('The uploaded file exceeds the maximum size allowed by the submission form.');
		$lang['upload_file_partial'] = __('The file was only partially uploaded.');
		$lang['upload_no_temp_directory'] = __('The temporary folder is missing.');
		$lang['upload_unable_to_write_file'] = __('The file could not be written to disk.');
		$lang['upload_stopped_by_extension'] = __('The file upload was stopped by extension.');
		$lang['upload_no_file_selected'] = __('You did not select a file to upload.');
		$lang['upload_invalid_filetype'] = __('The filetype you are attempting to upload is not allowed.');
		$lang['upload_invalid_filesize'] = __('The file you are attempting to upload is larger than the permitted size.');
		$lang['upload_invalid_dimensions'] = __('The image you are attempting to upload exceedes the maximum height or width.');
		$lang['upload_destination_error'] = __('A problem was encountered while attempting to move the uploaded file to the final destination.');
		$lang['upload_no_filepath'] = __('The upload path does not appear to be valid.');
		$lang['upload_no_file_types'] = __('You have not specified any allowed file types.');
		$lang['upload_bad_filename'] = __('The file name you submitted already exists on the server.');
		$lang['upload_not_writable'] = __('The upload destination folder does not appear to be writable.');

		return $lang[$key] ? $lang[$key] : $key;
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize preferences
	 *
	 * @access  public
	 * @param   array
	 * @return  void
	 */
	function initialize($config = array())
	{
		$defaults = array(
			'max_size'		 => 0,
			'max_width'		 => 0,
			'max_height'	 => 0,
			'allowed_types'	 => "",
			'file_temp'		 => "",
			'file_name'		 => "",
			'orig_name'		 => "",
			'file_type'		 => "",
			'file_size'		 => "",
			'file_ext'		 => "",
			'upload_path'	 => "",
			'overwrite'		 => false,
			'encrypt_name'	 => false,
			'is_image'		 => false,
			'image_width'	 => '',
			'image_height'	 => '',
			'image_type'	 => '',
			'image_size_str' => '',
			'error_msg'		 => array(),
			'mimes'			 => array(),
			'remove_spaces'	 => true,
			'xss_clean'		 => false,
			'temp_prefix'	 => "temp_file_"
		);


		foreach ($defaults as $key => $val)
		{
			if (isset($config[$key]))
			{

				$method = 'set' . Inflector::camelize($key);


				if (method_exists($this, $method))
				{
					$this->$method($config[$key]);
				}
				else
				{
					$this->$key = $config[$key];
				}
			}
			else
			{
				$this->$key = $val;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Perform the file upload
	 *
	 * @access  public
	 * @return  bool
	 */
	function doUpload($field = 'userfile')
	{
		// Is $_FILES[$field] set? If not, no reason to continue.
		if (!isset($_FILES[$field]))
		{
			//log_error('upload_userfile_not_set');
			$this->setError('upload_no_file_selected');
			return false;
		}

		// Is the upload path valid?
		if (!$this->validateUploadPath())
		{
			return false;
		}

		// Was the file able to be uploaded? If not, determine the reason why.
		if (!is_uploaded_file($_FILES[$field]['tmp_name']))
		{
			$error = (!isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];

			switch ($error)
			{
				case 1:
					$this->setError('upload_file_exceeds_limit');
					break;
				case 3:
					$this->setError('upload_file_partial');
					break;
				case 4:
					$this->setError('upload_no_file_selected');
					break;
				default:
					$this->setError('upload_no_file_selected');
					break;
			}

			return false;
		}

		// Set the uploaded data as class variables
		$this->file_temp = $_FILES[$field]['tmp_name'];
		$this->orig_name = $_FILES[$field]['name'];
		$this->file_ext = $this->getExtension($this->orig_name);
		$this->file_name = $this->setCheckFilename($this->upload_path, $this->orig_name);
		if ($this->file_name === false)
		{
			return false;
		}
		$this->file_size = $_FILES[$field]['size'];
		$this->file_type = preg_replace("/^(.+?);.*$/", "\\1", $_FILES[$field]['type']);
		$this->file_type = strtolower($this->file_type);


		// Convert the file size to kilobytes
		if ($this->file_size > 0)
		{
			$this->file_size = round($this->file_size / 1024, 2);
		}

		// Is the file type allowed to be uploaded?
		if (!$this->isAllowedFiletype())
		{
			$this->setError('upload_invalid_filetype');
			return false;
		}

		// Is the file size within the allowed maximum?
		if (!$this->isAllowedFilesize())
		{
			$this->setError('upload_invalid_filesize');
			return false;
		}

		// Are the image dimensions within the allowed size?
		// Note: This can fail if the server has an open_basdir restriction.
		if (!$this->isAllowedDimensions())
		{
			$this->setError('upload_invalid_dimensions');
			return false;
		}



		/*
		 * Run the file through the XSS hacking filter
		 * This helps prevent malicious code from being
		 * embedded within a file. Scripts can easily
		 * be disguised as images or other file types.
		 */
		if ($this->xss_clean && $this->doXssClean() === FALSE)
		{
			$this->set_error('upload_unable_to_write_file');
			return FALSE;
		}



		/*
		 * Move the file to the final destination
		 * To deal with different server configurations
		 * we'll attempt to use copy() first.  If that fails
		 * we'll use move_uploaded_file().  One of the two should
		 * reliably work in most environments
		 */
		if (!@copy($this->file_temp, $this->upload_path . $this->file_name))
		{
			if (!@move_uploaded_file($this->file_temp, $this->upload_path . $this->file_name))
			{
				$this->setError('upload_destination_error');
				return false;
			}
		}



		/*
		 * Set the finalized image dimensions
		 * This sets the image width/height (assuming the
		 * file was an image).  We use this information
		 * in the "data" function.
		 */
		$this->setImageProperties($this->upload_path . $this->file_name);

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Finalized Data Array
	 *  
	 * Returns an associative array containing all of the information
	 * related to the upload, allowing the developer easy access in one array.
	 *
	 * @access  public
	 * @return  array
	 */
	function data()
	{
		return array(
			'file_name'		 => $this->file_name,
			'file_type'		 => $this->file_type,
			'file_path'		 => $this->upload_path,
			'full_path'		 => $this->upload_path . $this->file_name,
			'raw_name'		 => $this->getRawName($this->file_name),
			'orig_name'		 => $this->orig_name,
			'file_ext'		 => $this->file_ext,
			'file_size'		 => $this->file_size,
			'is_image'		 => $this->isImage(),
			'image_width'	 => $this->image_width,
			'image_height'	 => $this->image_height,
			'image_type'	 => $this->image_type,
			'image_size_str' => $this->image_size_str,
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Upload Path
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	function setUploadPath($path)
	{
		$this->upload_path = $path;
	}

	// --------------------------------------------------------------------

	/**
	 * Set the file name
	 *
	 * This function takes a filename/path as input and looks for the
	 * existence of a file with the same name. If found, it will append a
	 * number to the end of the filename to avoid overwriting a pre-existing file.
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @return  string
	 */
	function setCheckFilename($path, $filename)
	{
		// check if preferred name set
		if (strlen($this->file_name))
		{
			$filename = $this->file_name;

			// also update extension
			$this->file_ext = $this->getExtension($filename);
		}

		// define file_ext if not set 
		if (!strlen($this->file_ext))
		{
			$this->file_ext = $this->getExtension($filename);
		}


		if ($this->encrypt_name == true)
		{
			$filename = $this->_randomStr() . $this->file_ext;
		}
		else
		{
			// perform all actions with filename
			// -------------------
			// Sanitize the file name for security
			$filename = $this->cleanFileName($filename);

			// Remove white spaces in the name
			if ($this->remove_spaces == true)
			{
				$filename = preg_replace("/\s+/", "_", $filename);
			}


			// remove dots from name 
			$name = str_replace($this->file_ext, '', $filename);
			$name = str_replace('.', '', $name);
			$filename = $name . $this->file_ext;


			// Shorten toolong filenames to fit db table field
			if ($this->file_name_max_chars < strlen($filename))
			{
				$file_name_max_chars_len = $this->file_name_max_chars;
				if ($file_name_max_chars_len > 10)
				{
					// leave some space for extension
					$file_name_max_chars_len = $file_name_max_chars_len - strlen($this->file_ext);
				}
				if ($file_name_max_chars_len < 3)
				{
					$file_name_max_chars_len = 3;
				}
				// shorten filename
				$filename = trim(substr($this->getRawName($filename), 0, $file_name_max_chars_len), '.') . $this->file_ext;
			}
			//--------------------
		}

		// fix possible empty names
		if (!strlen($this->getRawName($filename)))
		{
			$filename = $this->_randomStr() . $this->file_ext;
		}


		// check if override 
		if ($this->overwrite === true || !file_exists($path . $filename))
		{
			return $filename;
		}



		// file must be unique in given folder        
		$raw_name = $this->getRawName($filename);

		$new_filename = '';
		for ($i = 1; $i < 100; $i++)
		{
			if (!file_exists($path . $raw_name . $i . $this->file_ext))
			{
				$new_filename = $raw_name . $i . $this->file_ext;
				break;
			}
		}


		// if not found unique filename then generate random name 
		if ($new_filename == '')
		{
			for ($i = 1; $i < 20; $i++)
			{
				$random_str = $this->_randomStr();
				if (!file_exists($path . $random_str . $this->file_ext))
				{
					$new_filename = $random_str . $this->file_ext;
					break;
				}
			}
		}


		if ($new_filename == '')
		{
			$this->setError('upload_bad_filename');
			return false;
		}
		else
		{
			return $new_filename;
		}
	}

	/**
	 * generate random id for filename
	 * @return type
	 */
	private function _randomStr()
	{

		// generate random id 
		mt_srand();
		$return = md5(uniqid(mt_rand()));
		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Maximum File Size
	 *
	 * @access  public
	 * @param   integer
	 * @return  void
	 */
	function setMaxSize($n)
	{
		$this->max_size = is_numeric($n) ? abs(intval($n)) : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Maximum Image Width
	 *
	 * @access  public
	 * @param   integer
	 * @return  void
	 */
	function setMaxWidth($n)
	{
		$this->max_width = is_numeric($n) ? abs(intval($n)) : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Maximum Image Height
	 *
	 * @access  public
	 * @param   integer
	 * @return  void
	 */
	function setMaxHeight($n)
	{
		$this->max_height = is_numeric($n) ? abs(intval($n)) : 0;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Allowed File Types
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	function setAllowedTypes($types)
	{
		$this->allowed_types = explode('|', $types);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Image Properties
	 *
	 * Uses GD to determine the width/height/type of image
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	function setImageProperties($path = '')
	{
		if (!$this->isImage())
		{
			return;
		}

		if (function_exists('getimagesize'))
		{
			if (false !== ($D = @getimagesize($path)))
			{
				$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');

				$this->image_width = $D['0'];
				$this->image_height = $D['1'];
				$this->image_type = (!isset($types[$D['2']])) ? 'unknown' : $types[$D['2']];
				$this->image_size_str = $D['3'];  // string containing height and width
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set XSS Clean
	 *
	 * Enables the XSS flag so that the file that was uploaded
	 * will be run through the XSS filter.
	 *
	 * @access  public
	 * @param   bool
	 * @return  void
	 */
	function setXssClean($flag = false)
	{
		$this->xss_clean = ($flag == true) ? true : false;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate the image
	 *
	 * @access  public
	 * @return  bool
	 */
	function isImage()
	{
		$img_mimes = array(
			'image/gif',
			'image/jpg',
			'image/jpe',
			'image/jpeg',
			'image/pjpeg',
			'image/png',
			'image/x-png'
		);


		return (in_array($this->file_type, $img_mimes, true)) ? true : false;
	}

	// --------------------------------------------------------------------

	/**
	 * Verify that the filetype is allowed
	 *
	 * @access  public
	 * @return  bool
	 */
	function isAllowedFiletype()
	{
		if (count($this->allowed_types) == 0)
		{
			$this->setError('upload_no_file_types');
			return false;
		}

		foreach ($this->allowed_types as $val)
		{
			$mime = self::mimesTypes(strtolower($val));

			if (is_array($mime))
			{
				if (in_array($this->file_type, $mime, true))
				{
					return true;
				}
			}
			else
			{
				if ($mime == $this->file_type)
				{
					return true;
				}
			}
		}

		return false;
	}

	// --------------------------------------------------------------------

	/**
	 * Verify that the file is within the allowed size
	 *
	 * @access  public
	 * @return  bool
	 */
	function isAllowedFilesize()
	{
		if ($this->max_size != 0 AND $this->file_size > $this->max_size)
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Verify that the image is within the allowed width/height
	 *
	 * @access  public
	 * @return  bool
	 */
	function isAllowedDimensions()
	{
		if (!$this->isImage())
		{
			return true;
		}

		if (function_exists('getimagesize'))
		{
			$D = @getimagesize($this->file_temp);

			if ($this->max_width > 0 AND $D['0'] > $this->max_width)
			{
				return false;
			}

			if ($this->max_height > 0 AND $D['1'] > $this->max_height)
			{
				return false;
			}

			return true;
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate Upload Path
	 *
	 * Verifies that it is a valid upload path with proper permissions.
	 *
	 *
	 * @access  public
	 * @return  bool
	 */
	function validateUploadPath()
	{
		if ($this->upload_path == '')
		{
			$this->setError('upload_no_filepath');
			return false;
		}

		if (function_exists('realpath') AND @ realpath($this->upload_path) !== false)
		{
			$this->upload_path = str_replace("\\", "/", realpath($this->upload_path));
		}

		if (!@is_dir($this->upload_path))
		{
			$this->setError('upload_no_filepath');
			return false;
		}

		if (!is_writable($this->upload_path))
		{
			$this->setError('upload_not_writable');
			return false;
		}

		$this->upload_path = preg_replace("/(.+?)\/*$/", "\\1/", $this->upload_path);
		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Extract the file extension
	 *
	 * @access  public
	 * @param   string
	 * @return  string
	 */
	function getExtension($filename)
	{
		if (strpos($filename, '.'))
		{
			$x = explode('.', $filename);
			return '.' . strtolower(end($x));
		}
		return '';
	}

	/**
	 * Extract the file extension
	 *
	 * @access  public
	 * @param   string
	 * @return  string
	 */
	function getRawName($filename)
	{
		if (strpos($filename, '.'))
		{
			$x = explode('.', $filename);
			array_pop($x);

			return implode('.', $x);
		}
		return $filename;
	}

	// --------------------------------------------------------------------

	/**
	 * Clean the file name for security
	 *
	 * @access  public
	 * @param   string
	 * @return  string
	 */
	function cleanFileName($filename)
	{
		$bad = array(
			"<!--",
			"-->",
			"'",
			"<",
			">",
			'"',
			'&',
			'$',
			'=',
			';',
			'?',
			'/',
			"%20",
			"%22",
			"%3c", // <
			"%253c", // <
			"%3e", // >
			"%0e", // >
			"%28", // (
			"%29", // )
			"%2528", // (
			"%26", // &
			"%24", // $
			"%3f", // ?
			"%3b", // ;
			"%3d" // =
		);

		foreach ($bad as $val)
		{
			$filename = str_replace($val, '', $filename);
		}

		return $filename;
	}

	// --------------------------------------------------------------------

	/**
	 * Runs the file through the XSS clean function
	 *
	 * This prevents people from embedding malicious code in their files.
	 * I'm not sure that it won't negatively affect certain files in unexpected ways,
	 * but so far I haven't found that it causes trouble.
	 *
	 * @return	string
	 */
	public function doXssClean()
	{
		$file = $this->file_temp;

		if (filesize($file) == 0)
		{
			return FALSE;
		}

		if (memory_get_usage() && ($memory_limit = ini_get('memory_limit')))
		{
			$memory_limit *= 1024 * 1024;

			// There was a bug/behavioural change in PHP 5.2, where numbers over one million get output
			// into scientific notation. number_format() ensures this number is an integer
			// http://bugs.php.net/bug.php?id=43053

			$memory_limit = number_format(ceil(filesize($file) + $memory_limit), 0, '.', '');

			ini_set('memory_limit', $memory_limit); // When an integer is used, the value is measured in bytes. - PHP.net
		}

		// If the file being uploaded is an image, then we should have no problem with XSS attacks (in theory), but
		// IE can be fooled into mime-type detecting a malformed image as an html file, thus executing an XSS attack on anyone
		// using IE who looks at the image. It does this by inspecting the first 255 bytes of an image. To get around this
		// CI will itself look at the first 255 bytes of an image to determine its relative safety. This can save a lot of
		// processor power and time if it is actually a clean image, as it will be in nearly all instances _except_ an
		// attempted XSS attack.

		if (function_exists('getimagesize') && @getimagesize($file) !== FALSE)
		{
			if (($file = @fopen($file, 'rb')) === FALSE) // "b" to force binary
			{
				return FALSE; // Couldn't open the file, return FALSE
			}

			//$opening_bytes = fread($file, 256);
			$opening_bytes = fread($file, 2048);
			fclose($file);

			// These are known to throw IE into mime-type detection chaos
			// <a, <body, <head, <html, <img, <plaintext, <pre, <script, <table, <title
			// title is basically just in SVG, but we filter it anyhow
			// if it's an image or no "triggers" detected in the first 256 bytes - we're good
			return !preg_match('/<(a|body|head|html|img|plaintext|pre|script|table|title)[\s>]/i', $opening_bytes);
		}

		if (($data = @file_get_contents($file)) === FALSE)
		{
			return FALSE;
		}

		return Input::getInstance()->xss_clean($data, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Set an error message
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	function setError($msg)
	{
		if (is_array($msg))
		{
			foreach ($msg as $val)
			{
				$this->error_msg[] = self::msg($msg);
				//log_error($msg);
			}
		}
		else
		{
			$this->error_msg[] = self::msg($msg);
			//log_error($msg);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Display the error message
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @return  string
	 */
	function displayErrors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach ($this->error_msg as $val)
		{
			$str .= $open . $val . $close;
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * List of Mime Types
	 *
	 * This is a list of mime types.  We use it to validate
	 * the "allowed types" set by the developer
	 *
	 * @access  public
	 * @param   string
	 * @return  string
	 */
	public static function mimesTypes($mime)
	{
		/*
		  | -------------------------------------------------------------------
		  | MIME TYPES
		  | -------------------------------------------------------------------
		  | This file contains an array of mime types.  It is used by the
		  | Upload class to help identify allowed file types.
		  |
		 */

		self::$mimes = array('hqx'	 => 'application/mac-binhex40',
			'cpt'	 => 'application/mac-compactpro',
			'csv'	 => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
			'bin'	 => 'application/macbinary',
			'dms'	 => 'application/octet-stream',
			'lha'	 => 'application/octet-stream',
			'lzh'	 => 'application/octet-stream',
			'exe'	 => 'application/octet-stream',
			'class'	 => 'application/octet-stream',
			'psd'	 => 'application/x-photoshop',
			'so'	 => 'application/octet-stream',
			'sea'	 => 'application/octet-stream',
			'dll'	 => 'application/octet-stream',
			'oda'	 => 'application/oda',
			'pdf'	 => array('application/pdf', 'application/x-download'),
			'ai'	 => 'application/postscript',
			'eps'	 => 'application/postscript',
			'ps'	 => 'application/postscript',
			'smi'	 => 'application/smil',
			'smil'	 => 'application/smil',
			'mif'	 => 'application/vnd.mif',
			'xls'	 => array('application/excel', 'application/vnd.ms-excel'),
			'ppt'	 => 'application/powerpoint',
			'wbxml'	 => 'application/wbxml',
			'wmlc'	 => 'application/wmlc',
			'dcr'	 => 'application/x-director',
			'dir'	 => 'application/x-director',
			'dxr'	 => 'application/x-director',
			'dvi'	 => 'application/x-dvi',
			'gtar'	 => 'application/x-gtar',
			'gz'	 => 'application/x-gzip',
			'php'	 => 'application/x-httpd-php',
			'php4'	 => 'application/x-httpd-php',
			'php3'	 => 'application/x-httpd-php',
			'phtml'	 => 'application/x-httpd-php',
			'phps'	 => 'application/x-httpd-php-source',
			'js'	 => 'application/x-javascript',
			'swf'	 => 'application/x-shockwave-flash',
			'sit'	 => 'application/x-stuffit',
			'tar'	 => 'application/x-tar',
			'tgz'	 => 'application/x-tar',
			'xhtml'	 => 'application/xhtml+xml',
			'xht'	 => 'application/xhtml+xml',
			'zip'	 => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
			'mid'	 => 'audio/midi',
			'midi'	 => 'audio/midi',
			'mpga'	 => 'audio/mpeg',
			'mp2'	 => 'audio/mpeg',
			'mp3'	 => 'audio/mpeg',
			'aif'	 => 'audio/x-aiff',
			'aiff'	 => 'audio/x-aiff',
			'aifc'	 => 'audio/x-aiff',
			'ram'	 => 'audio/x-pn-realaudio',
			'rm'	 => 'audio/x-pn-realaudio',
			'rpm'	 => 'audio/x-pn-realaudio-plugin',
			'ra'	 => 'audio/x-realaudio',
			'rv'	 => 'video/vnd.rn-realvideo',
			'wav'	 => 'audio/x-wav',
			'bmp'	 => 'image/bmp',
			'gif'	 => 'image/gif',
			'jpeg'	 => array('image/jpeg', 'image/pjpeg'),
			'jpg'	 => array('image/jpeg', 'image/pjpeg'),
			'jpe'	 => array('image/jpeg', 'image/pjpeg'),
			'png'	 => array('image/png', 'image/x-png'),
			'tiff'	 => 'image/tiff',
			'tif'	 => 'image/tiff',
			'css'	 => 'text/css',
			'html'	 => 'text/html',
			'htm'	 => 'text/html',
			'shtml'	 => 'text/html',
			'txt'	 => 'text/plain',
			'text'	 => 'text/plain',
			'log'	 => array('text/plain', 'text/x-log'),
			'rtx'	 => 'text/richtext',
			'rtf'	 => 'text/rtf',
			'xml'	 => 'text/xml',
			'xsl'	 => 'text/xml',
			'mpeg'	 => 'video/mpeg',
			'mpg'	 => 'video/mpeg',
			'mpe'	 => 'video/mpeg',
			'qt'	 => 'video/quicktime',
			'mov'	 => 'video/quicktime',
			'avi'	 => 'video/x-msvideo',
			'movie'	 => 'video/x-sgi-movie',
			'doc'	 => 'application/msword',
			'word'	 => array('application/msword', 'application/octet-stream'),
			'xl'	 => 'application/excel',
			'eml'	 => 'message/rfc822'
		);


		return (!isset(self::$mimes[$mime])) ? false : self::$mimes[$mime];
	}

}

// End Upload Class
