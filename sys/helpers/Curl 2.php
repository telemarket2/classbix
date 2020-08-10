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
class Curl
{
	static function get($url)
	{
		Benchmark::cp();
		//echo '[get('.$url.')]';
		if (!self::isAvailable())
		{
			$timeout = stream_context_create(array('http' => array('timeout' => 5)));
			$content = file_get_contents($url, 0, $timeout); // if you don't have curl, try this instead
			//echo '[file_get_contents]';
		}
		else
		{
			$content = self::getCurl($url);
			//echo '[getCurl]';
		}
		Benchmark::cp('Curl::get(' . View::escape($url) . ')');
		return $content;
	}

	/**
	 * Send GET/POST request to remote server and get response
	 * 
	 * 
	 * @param string $url
	 * @param array $post array('field'=>'value');
	 * @return string
	 */
	public static function getCurl($url, $post = null)
	{
		//curl kullanarak external dosya sorgulama ve post gondermek icin kullanilir
		//$post is an array=array('field'=>'value');
		$ch = curl_init();
		$timeout = 5; // set to zero for no timeout
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

		// CURLOPT_SSL_VERIFYPEER => false     // Disabled SSL Cert checks
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// follow redirects 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);

		if ($post)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		$file_contents = curl_exec($ch);
		curl_close($ch);

		// display file
		//echo "file_contents<br>".$file_contents;
		return $file_contents;
	}

	static function getFile($url, $local_file)
	{
		if (!function_exists('curl_exec'))
		{
			$timeout = stream_context_create(array('http' => array('timeout' => 5)));
			$content = file_get_contents($url, 0, $timeout); // if you don't have curl, try this instead
			return FileDir::checkMakeFile($local_file, $content);
		}
		else
		{
			return self::getFileCurl($url, $local_file);
		}
		return false;
	}

	static function getFileCurl($url, $local_file)
	{
		// echo "<br>Attempting message download for $url<br>";
		$out = fopen($local_file, 'wb');
		if ($out == FALSE)
		{
			//print "File not opened<br>";
			return false;
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_FILE, $out);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL, $url);

		// follow redirects 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);

		curl_exec($ch);
		//echo "<br>Error is : ".curl_error ( $ch);

		curl_close($ch);
		//fclose($handle);

		return true;
	}

	static function containsUrl($url, $find)
	{
		$arr = explode('/', $url);
		$last = array_pop($arr);
		list($name, ) = explode('.', $last);

		return ($name === $find);
	}

	public static function isAvailable()
	{
		return function_exists('curl_exec');
	}

}
