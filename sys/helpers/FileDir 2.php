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
class FileDir
{

	static $file_name_counter;

	/**
	 * Check if directory exists. Create if needed.
	 *
	 * @param string $dir
	 * @return bool
	 */
	public static function checkMakeDir($dir)
	{
		if(!is_dir($dir))
		{
			if(self::checkMakeDir(dirname($dir)))
			{
				// safe mode fails with a trailing slash under certain PHP versions.
				$dir = rtrim($dir, '/\\');
				// make dir 
				$return = mkdir($dir);
				@chmod($dir, 0777);
				return $return;
			}
		}
		else
		{
			return true;
		}

		return false;
	}

	/**
	 * Check if file exists. Create if needed.
	 *
	 * @param string $file
	 * @param string $content
	 * @return bool
	 */
	public static function checkMakeFile($file, $content = "")
	{
		//make dir
		$dir = dirname($file);
		if(self::checkMakeDir($dir))
		{
			//make file
			if($content)
			{
				//if content given then overwrite

				if(!$handle = fopen($file, 'w'))
				{
					//echo "Cannot open file ($file)";
					echo "Cannot open file";
					exit;
				}

				// Write $somecontent to our opened file.
				if(fwrite($handle, $content) === FALSE)
				{
					// echo "Cannot write to file ($file)";
					echo "Cannot write to file";
					exit;
				}

				//echo "Success, wrote ($somecontent) to file ($file)";

				fclose($handle);
				@chmod($file, 0777);
			}
			elseif(!is_file($file))
			{
				//if no content and no file then create emty file
				$handle = fopen($file, "w");
				fclose($handle);
				@chmod($file, 0777);
			}
		}
		else
		{
			//echo "Cannot create dir";
			//exit;
		}

		return is_file($file);
	}

	/**
	 * Remove directory recursive
	 *
	 * @param string $dir
	 * @return bool
	 */
	public static function rmdirr($dir, $use_sys = true)
	{
		//Benchmark::cp('rmdirr(' . $dir . ' ,$use_sys:' . ($use_sys ? 'true' : 'false') . ')');
		if($use_sys)
		{
			$ret = 1;
			$command = "rm -rf $dir";
			$last_line = @system($command, $ret);
			//Benchmark::cp('$command:@system(' . $command . ' , $ret:' . $ret . '):last_line:' . $last_line);
			if($ret == 0)
			{
				return true;
			}
			else
			{
				// couldnt delete. fallback to php version
				return self::rmdirr($dir, false);
			}
		}
		else
		{
			if(is_dir($dir))
			{
				if(function_exists('scandir'))
				{
					//Glob function doesn't return the hidden files, therefore scandir can be more useful
					$files = array_diff(scandir($dir), array('.', '..'));
					foreach($files as $file)
					{
						$obj = rtrim($dir, '/') . '/' . $file;
						self::rmdirr($obj, $use_sys);
					}
				}
				else
				{
					if($objs = glob(rtrim($dir, '/') . "/*"))
					{
						foreach($objs as $obj)
						{
							self::rmdirr($obj, $use_sys);
						}
					}
				}
				//system('rm -rf '.$dir);
				return @rmdir($dir);
			}
			elseif(is_file($dir))
			{
				return @unlink($dir);
			}
		}
		return true;
	}

	/**
	 * Delete children of given folder if they are older than given time
	 * 
	 * @param string $dir with / at the end
	 * @param int $time default 1 hour
	 * @param int $limit max number of files to delete
	 */
	public static function deleteOlder($dir, $time = 3600, $limit = null)
	{
		$dir = rtrim($dir, '/') . '/';
		if(is_null($limit))
		{
			$use_limit = false;
			$limit = 1;
		}
		else
		{
			$use_limit = true;
		}

		if($handle = opendir($dir))
		{
			while((false !== ($entry = readdir($handle))) && $limit > 0)
			{
				if($entry != "." && $entry != ".." && (REQUEST_TIME - filectime($dir . $entry)) > $time)
				{
					self::rmdirr($dir . $entry);
					if($use_limit)
					{
						$limit--;
					}
				}
			}
			closedir($handle);
		}
	}

	/**
	 * check if given directory writable
	 * 
	 * @param string $dir
	 * @return boolean|array of not writable files and folders
	 */
	public static function isWritable($dir)
	{
		$is_writable = array();
		if(is_dir($dir))
		{

			// check if dir writable
			if(!is_writable($dir))
			{
				$is_writable[] = $dir;
			}

			// check if each file writable
			if($objs = glob($dir . "/*"))
			{
				foreach($objs as $obj)
				{
					$_is_writable = self::isWritable($obj);
					if(is_array($_is_writable))
					{
						$is_writable = array_merge($is_writable, $_is_writable);
					}
				}
			}

			if(count($is_writable))
			{
				return $is_writable;
			}

			return true;
		}
		elseif(is_file($dir))
		{
			if(!is_writable($dir))
			{
				$is_writable[] = $dir;
				return $is_writable;
			}
			return true;
		}
	}

	/**
	 * Generates unique filename in given folder. creates directory if not exists.
	 *
	 * @param string $file
	 * @return array with keys: name,ext,fullname,url
	 */
	public static function genFileName($file)
	{
		$filename = strtolower(basename($file));
		$filename = str_replace(" ", "-", $filename);
		$filename = preg_replace("/[^a-z0-9.-]/i", "", $filename);
		// remove several dashes ---
		$filename = trim(preg_replace("|-+|", "-", $filename), '-');


		$ext = self::getExtension($filename);
		if(strlen($ext))
		{
			$ext = '.' . $ext;
		}

		$name = self::getName($filename);
		$path = dirname($file) . "/";

		if(!strlen($name))
		{
			$name = md5(uniqid(mt_rand()));
		}

		// make sure directory exists. create if required
		self::checkMakeDir($path);

		$i = isset(self::$file_name_counter[$file]) ? self::$file_name_counter[$file] : 0;
		$new_filename = $name . $ext;
		$new_path = $path . $new_filename;


		// check if name unique, if not add iteration
		$max_tries = 100;
		while(is_file($new_path) && $max_tries-- > 0)
		{
			$new_filename = $name . "_" . ($i++) . $ext;
			$new_path = $path . $new_filename;
		}
		self::$file_name_counter[$file] = $i;


		// check if file is unique, if not try random names
		$max_tries = 20;
		while(is_file($new_path) && $max_tries-- > 0)
		{
			$new_filename = md5(uniqid(mt_rand())) . $ext;
			$new_path = $path . $new_filename;
		}


		$return['name'] = $new_filename;
		$return['ext'] = trim($ext, '.');
		$return['fullname'] = $new_path;
		$return['url'] = URL_PUBLIC . str_replace(FROG_ROOT, "", $return['fullname']);

		return $return;
	}

	/**
	 * Generates unique directory in given folder. 
	 *
	 * @param string $dir
	 * @return array with keys: name,fullname,url
	 */
	public static function genDirName($dir = "./")
	{
		$dir_name = rand();
		$new_path = $dir . $dir_name;

		while(is_dir($new_path))
		{
			$dir_name = rand();
			$new_path = $dir . $dir_name;
		}

		// create directory
		self::checkMakeDir($new_path);

		$return['name'] = $dir_name;
		$return['fullname'] = $new_path;
		$return['url'] = URL_PUBLIC . str_replace(FROG_ROOT, "", $return['fullname']);

		return $return;
	}

	function forceDownload($filename, $name = '')
	{

		if(!$name)
		{
			$name = basename($filename);
		}
		//$filename = $_GET['file'];
		// required for IE, otherwise Content-disposition is ignored
		if(ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');

		// addition by Jorg Weske
		$file_extension = strtolower(substr(strrchr($filename, "."), 1));

		if($filename == "")
		{
			return false;
		}
		elseif(!file_exists($filename))
		{
			return false;
		}

		switch($file_extension)
		{
			case "pdf": $ctype = "application/pdf";
				break;
			case "exe": $ctype = "application/octet-stream";
				break;
			case "zip": $ctype = "application/zip";
				break;
			case "doc": $ctype = "application/msword";
				break;
			case "xls": $ctype = "application/vnd.ms-excel";
				break;
			case "ppt": $ctype = "application/vnd.ms-powerpoint";
				break;
			case "gif": $ctype = "image/gif";
				break;
			case "png": $ctype = "image/png";
				break;
			case "jpeg":
			case "jpg": $ctype = "image/jpg";
				break;
			default: $ctype = "application/force-download";
		}

		header("Pragma: public"); // required
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private", false); // required for certain browsers 
		header("Content-Type: $ctype");
		// change, added quotes to allow spaces in filenames, by Rajkumar Singh
		header("Content-Disposition: attachment; filename=\"" . $name . "\";");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . filesize($filename));
		readfile("$filename");
		exit();
	}

	/**
	 * move folder from source to destination, delete source if success
	 * 
	 * @param string $source directory
	 * @param string $dest directory
	 * @param bool $overwrite
	 * @param string $funcloc current location of folder, used for recursion 
	 * @return boolean
	 */
	public static function dirmv($source, $dest, $overwrite = false, $funcloc = NULL)
	{
		if(is_null($funcloc))
		{
			$dest .= '/' . strrev(substr(strrev($source), 0, strpos(strrev($source), '/')));
			$funcloc = '/';
		}

		// make subdirectory before subdirectory is copied
		if(!self::checkMakeDir($dest . $funcloc))
		{
			// destination dir is not found and cannot be created
			return false;
		}

		if($handle = opendir($source . $funcloc))
		{
			$return = true;
			// if the folder exploration is sucsessful, continue
			while(false !== ($file = readdir($handle)))
			{
				// as long as storing the next file to $file is successful, continue
				if($file != '.' && $file != '..')
				{
					$path = $source . $funcloc . $file;
					$path2 = $dest . $funcloc . $file;

					$path = str_replace('//', '/', $path);
					$path2 = str_replace('//', '/', $path2);

					if(is_file($path))
					{
						if(!is_file($path2))
						{
							if(!@rename($path, $path2))
							{
								echo '<font color="red">File (' . $path . ') could not be moved, likely a permissions problem.</font>';
								$return = false;
							}
						}
						elseif($overwrite)
						{
							if(!@unlink($path2))
							{
								echo 'Unable to overwrite file ("' . $path2 . '"), likely to be a permissions problem.';
								$return = false;
							}
							elseif(!@rename($path, $path2))
							{
								echo '<font color="red">File (' . $path . ') could not be moved while overwritting, likely a permissions problem.</font>';
								$return = false;
							}
						}
					}
					elseif(is_dir($path))
					{
						$return = $return && self::dirmv($source, $dest, $overwrite, $funcloc . $file . '/'); //recurse!
						// delete source if success
						if($return)
						{
							self::rmdirr($path);
						}
					}
				}
			}
			closedir($handle);

			return $return;
		}

		return false;
	}

	/**
	 * returns extenstion for given file 
	 * 
	 * @param string $file
	 * @return string
	 */
	public static function getExtension($file)
	{
		$filename = basename($file);
		if(strpos($filename, '.'))
		{
			$arr = explode('.', $filename);
			return end($arr);
		}

		return '';
	}

	/**
	 * returns file name without extension 
	 * 
	 * @param string $file
	 * @return string
	 */
	public static function getName($file)
	{
		$filename = basename($file);
		if(strpos($filename, '.'))
		{
			$arr = explode('.', $filename);
			array_pop($arr);
			$filename = implode('.', $arr);
		}

		return trim($filename, '.');
	}

// end of dirmv()
}
