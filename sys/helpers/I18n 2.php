<?php

defined('I18N_PATH') or define('I18N_PATH', APP_PATH . DIRECTORY_SEPARATOR . 'i18n');
define('DEFAULT_LOCALE', 'en');

/**
 * I18n : Internationalisation function and class
 *
 * @author Philippe Archambault <philippe.archambault@gmail.com>
 * @copyright 2007 Philippe Archambault
 * @package Frog
 * @version 0.1
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 */

/**
 * this function is the must permisive as possible, you cand chose your own pattern for vars in 
 * the string, it could be ':var_name', '#var_name', '{varname}', '%varname', '%varname%', 'VARNAME' ...
 *
 *
 * return = array('hello world!' => 'bonjour le monde!',
 *                'user ":user" is logged in' => 'l\'utilisateur ":user" est connecté',
 *                'Posted by %user% on %month% %day% %year% at %time%' => 'Publié par %user% le %day% %month% %year% à %time%'
 *               );
 *
 * __('hello world!'); // bonjour le monde!
 * __('user ":user" is logged in', array(':user' => $user)); // l'utilisateur "demo" est connecté
 * __('Posted by %user% on %month% %day% %year% at %time%', array(
 *      '%user%' => $user, 
 *      '%month%' => __($month), 
 *      '%day%' => $day, 
 *      '%year%' => $year, 
 *      '%time%' => $time)); // Publié par demo le 3 janvier 2006 à 19:30
 */
function __($string, $args = null, $catalog = 'message')
{
	if (I18n::getLocale() != DEFAULT_LOCALE || true)
	{
		$string = I18n::getText($string, $catalog);
	}

	if ($args === null)
	{
		return $string;
	}

	return strtr($string, $args);
}

class I18n
{

	private static $locale = DEFAULT_LOCALE;
	private static $locale_store = array();
	private static $catalogs = array();
	public static $arr_locale = array('en' => 0);
	public static $arr_locale_txt = array(0 => 'en');
	public static $arr_locale_long_txt = array('en' => 'English');

	public static function setLocale($locale, $setCookie = false)
	{
		if ($setCookie)
		{
			$val = Flash::getCookie('lng');
			if ($val != $locale)
			{
				Flash::setCookie('lng', $locale, REQUEST_TIME + 100000000);
			}
		}
		self::$locale = $locale;
	}

	public static function getLocale($int = false)
	{
		if ($int)
		{
			return self::$arr_locale[self::$locale];
		}
		else
		{
			return self::$locale;
		}
	}

	public static function getText($string, $catalog = 'message')
	{
		self::loadCatalog($catalog);

		$i18n = & self::$catalogs[self::$locale][$catalog];

		// TODO bos olunca bos gostermemeis icin gecici yama.
		// bunu ceviri bitince kaldir

		return (isset($i18n[$string]) && strlen($i18n[$string])) ? $i18n[$string] : $string;
	}

	public static function loadCatalog($catalog)
	{
		if (!isset(self::$catalogs[self::$locale][$catalog]))
		{
			Benchmark::cp();
			$catalog_file = I18n::getFilename(self::$locale, $catalog);

			// assign returned value of catalog file
			// file return a array (source => translation)
			if (file_exists($catalog_file))
			{
				self::$catalogs[self::$locale][$catalog] = include $catalog_file;
			}
			else
			{
				self::$catalogs[self::$locale][$catalog] = array();
			}
			Benchmark::cp('I18n::loadCatalog(' . View::escape($catalog) . '):' . self::$locale);
		}
	}

	/**
	 * Update loaded locale catalog with new data. This is used after translation updated in admin panel by admin
	 * If catalog not loaded then it will not update.
	 * 
	 * @param string $lng
	 * @param string $catalog
	 * @param array $data
	 */
	public static function updateCatalog($lng, $catalog, $data)
	{
		if (isset(self::$catalogs[$lng][$catalog]))
		{
			self::$catalogs[$lng][$catalog] = $data;
		}
	}

	/**
	 * Saves current locale to restore later
	 * @return unknown_type
	 */
	public static function saveLocale()
	{
		self::$locale_store[] = self::$locale;
	}

	/**
	 * Restore to the saved locale
	 * @return unknown_type
	 */
	public static function restoreLocale()
	{
		if (count(self::$locale_store))
		{
			self::$locale = array_pop(self::$locale_store);
		}
	}

	/**
	 * get loaded catalogs for current locale
	 * 
	 * @return array
	 */
	public static function getCatalogs()
	{
		return self::$catalogs[self::$locale];
	}

	/**
	 * filename to store translation for given locale and catalog
	 * 
	 * @param string $lng
	 * @param string $catalog
	 * @return string
	 */
	public static function getFilename($lng = '', $catalog = 'message')
	{
		return I18N_PATH . DIRECTORY_SEPARATOR . $lng . '-' . $catalog . '.php';
	}

}

// end I18n class
