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
 * class AdField
 * These are fields that can be attached to all ads by default or by category and location
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Language extends Record
{

	const TABLE_NAME = 'language';
	const STATUS_ENABLED = 'enabled';
	const STATUS_ALL = 'all';

	static $languages = array();
	static $imgs = null;
	static $arr_lngObj = null;
	static $current_url = null;
	static $current_url_no_locale = null;
	private static $cols = array(
		'id'		 => 1, // tr,en,ru
		'name'		 => 1,
		'enabled'	 => 1,
		'pos'		 => 1,
		'img'		 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	function beforeInsert()
	{
		// set initial position 
		$this->pos = self::getLastPosition($this->parent_id) + 1;

		return true;
	}

	function beforeUpdate()
	{
		if ($this->old_id != $this->id)
		{
			// change old id first 
			$sql = "UPDATE " . self::tableNameFromClassName('Language') . "
				 SET id=" . self::escape($this->id) . " 
				 WHERE id=" . self::escape($this->old_id);
			self::query($sql);


			// update all related tables 
			$data = array('language_id' => $this->id);
			$where = 'language_id=?';
			$vals = array($this->old_id);
			LocationDescription::update('LocationDescription', $data, $where, $vals);
			CategoryDescription::update('CategoryDescription', $data, $where, $vals);
			CategoryFieldGroupDescription::update('CategoryFieldGroupDescription', $data, $where, $vals);
			AdFieldDescription::update('AdFieldDescription', $data, $where, $vals);
			AdFieldValueDescription::update('AdFieldValueDescription', $data, $where, $vals);
			MailTemplate::update('MailTemplate', $data, $where, $vals);
			Widget::changeLanguageId($this->old_id, $this->id);
		}

		return true;
	}

	function afterInsert()
	{
		// TODO : add new language values to all relaed tables.
		// category, location,adfield ...
		// all do same when deleting language 
		// id marked as default then make it default language
		self::updateRelatedTables($this->id);


		if ($this->default && $this->enabled)
		{
			Config::optionSet('default_language', $this->id);
		}

		// if for any reason there is not vald default then fix it 
		$this->fixDefault();

		// update messages from backup if exists 
		I18nBuilder::updateFromBackup($this->id);

		SimpleCache::delete('languages');

		return true;
	}

	function afterUpdate()
	{
		if ($this->default && $this->enabled)
		{
			Config::optionSet('default_language', $this->id);
		}

		// if for any reason there is not vald default then fix it 
		$this->fixDefault();

		// FIXME : if has old value then update all related description records

		SimpleCache::delete('languages');

		return true;
	}

	function beforeDelete()
	{
		return self::canDelete();
	}

	function afterDelete()
	{
		$this->fixDefault();

		// delete language descriptions
		CategoryDescription::deleteWhere('CategoryDescription', 'language_id=?', array($this->id));
		LocationDescription::deleteWhere('LocationDescription', 'language_id=?', array($this->id));
		CategoryFieldGroupDescription::deleteWhere('CategoryFieldGroupDescription', 'language_id=?', array($this->id));
		AdFieldDescription::deleteWhere('AdFieldDescription', 'language_id=?', array($this->id));
		AdFieldValueDescription::deleteWhere('AdFieldValueDescription', 'language_id=?', array($this->id));
		MailTemplate::deleteWhere('MailTemplate', 'language_id=?', array($this->id));
		PageDescription::deleteWhere('PageDescription', 'language_id=?', array($this->id));

		// no need to delete all cache, not used cahce will be deleted on regular bases
		SimpleCache::delete('languages');


		// fix currently active language
		$current_lng = I18n::getLocale();
		if (!self::setLocale($current_lng))
		{
			self::setLocale(Language::getDefault());
		}

		return true;
	}

	/**
	 * check id stored default is valid enabled language
	 * if not then make first enabled as default
	 * if none enabled then set first language enabled and default 
	 */
	function fixDefault()
	{
		$default_id = Language::getDefault();
		$has_valid_default = Language::findOneFrom('Language', 'id=? AND enabled=1', array($default_id));

		if (!$has_valid_default)
		{
			// set first available language as default
			$get_first_available = Language::findOneFrom('Language', 'enabled=1', array($default_id));
			if (!$get_first_available)
			{
				// get first language and make it enabled and default
				$get_first_available = Language::findOneFrom('Language');

				if ($get_first_available)
				{
					$get_first_available->enabled = 1;
					$get_first_available->save();
				}
			}

			Config::optionSet('default_language', $get_first_available->id);
		}
	}

	public static function isDefault($id)
	{
		return (strcmp(Language::getDefault(), $id) == 0);
	}

	public static function canDelete()
	{
		// if this is last language then cannot delete it 
		if (self::countFrom('Language') < 2)
		{
			return false;
		}
		return true;
	}

	/**
	 * get languages 
	 * @param $all true:enabled and disabled,false:only enabled
	 * @return array of object
	 */
	public static function getLanguages($filter = 'all')
	{
		if (!isset(self::$languages[$filter]))
		{

			self::$languages = SimpleCache::get('languages');
			if (self::$languages === false)
			{
				self::$languages[self::STATUS_ALL] = Language::findAllFrom('Language', '1=1 ORDER BY pos,id');
				//self::$languages[self::STATUS_ALL] = Record::cleanObject(self::$languages[self::STATUS_ALL]);
				self::$languages[self::STATUS_ENABLED] = array();
				foreach (self::$languages[self::STATUS_ALL] as $lng)
				{
					if ($lng->enabled)
					{
						self::$languages[self::STATUS_ENABLED][] = $lng;
					}
				}

				SimpleCache::set('languages', self::$languages, 86400); //24 hours
			}
		}
		return self::$languages[$filter];
	}

	public static function setLocaleFromUrl()
	{
		$url = self::getCurrentUrl();
		// parse url 
		$arr_url = explode('/', $url);
		$lng = $arr_url[0];

		if (self::setLocale($lng, true))
		{
			array_shift($arr_url);
			$url = implode('/', $arr_url);


			// redirect to correct url without languege if it is default language 
			if ($lng === Language::getDefault())
			{
				// do not do permanent redirect because it will be cached by user browser without updating cookie 
				redirect(Language::get_url($url));
			}

			return true;
		}

		// language not set check cookie
		// TODO cookie setting is problematic. when viewing site in default locale language is not detected by url
		// then checks for cookie which is last locale. so problem occures switching to default locale.
		if (!self::setLocale(Flash::getCookie('lng')))
		{
			// not set then set default language
			self::setLocale(Language::getDefault());
		}
	}

	public static function setLocale($lng = '', $setCookie = false)
	{
		// check if it is available language
		// set default lng cookie becuase deleting is not working always and user keeps returning to previous language 
		if ($setCookie)
		{
			if (strcmp($lng, Language::getDefault()) == 0)
			{
				// remove language cookie because it is default language
				Flash::clearCookie('lng');
				$setCookie = false;
			}
		}

		if (self::isAvailableLocale($lng))
		{
			I18n::setLocale($lng, $setCookie);
			return true;
		}

		return false;
	}

	/**
	 * Check if language defined and available to current user 
	 * 
	 * @param string $lng
	 * @return boolean
	 */
	static public function isAvailableLocale($lng = '')
	{
		if (strlen($lng) > 0)
		{
			$languages = self::getLanguages();
			foreach ($languages as $l)
			{
				if ($l->id === $lng)
				{
					// this is possible language
					// only admin can view disabled languages
					if ($l->enabled || AuthUser::hasPermission(User::PERMISSION_ADMIN))
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	static private function updateRelatedTables($lng)
	{
		// get default lng to copy from 
		$default_lng = Language::getDefault();

		// cupy category description
		$sql = "INSERT IGNORE INTO " . CategoryDescription::tableNameFromClassName('CategoryDescription') . " 
					SELECT category_id,?,name,description 
						FROM " . CategoryDescription::tableNameFromClassName('CategoryDescription') . " 
						WHERE language_id=?";
		self::query($sql, array($lng, $default_lng));


		// copy location description
		$sql = "INSERT IGNORE INTO " . LocationDescription::tableNameFromClassName('LocationDescription') . " 
					SELECT location_id,?,name,description 
						FROM " . LocationDescription::tableNameFromClassName('LocationDescription') . " 
						WHERE language_id=?";
		self::query($sql, array($lng, $default_lng));

		// copy page description
		$sql = "INSERT IGNORE INTO " . PageDescription::tableNameFromClassName('PageDescription') . " 
					SELECT page_id,?,name,description 
						FROM " . PageDescription::tableNameFromClassName('PageDescription') . " 
						WHERE language_id=?";
		self::query($sql, array($lng, $default_lng));


		// copy category group description
		$sql = "INSERT IGNORE INTO " . CategoryFieldGroupDescription::tableNameFromClassName('CategoryFieldGroupDescription') . " 
					SELECT cfg_id,?,name 
						FROM " . CategoryFieldGroupDescription::tableNameFromClassName('CategoryFieldGroupDescription') . " 
						WHERE language_id=?";
		self::query($sql, array($lng, $default_lng));

		// copy ad field description
		$sql = "INSERT IGNORE INTO " . AdFieldDescription::tableNameFromClassName('AdFieldDescription') . " 
					SELECT af_id,?,name,val 
						FROM " . AdFieldDescription::tableNameFromClassName('AdFieldDescription') . " 
						WHERE language_id=?";
		self::query($sql, array($lng, $default_lng));

		// copy ad field value description
		$sql = "INSERT IGNORE INTO " . AdFieldValueDescription::tableNameFromClassName('AdFieldValueDescription') . " 
					SELECT afv_id,?,name 
						FROM " . AdFieldValueDescription::tableNameFromClassName('AdFieldValueDescription') . " 
						WHERE language_id=?";
		self::query($sql, array($lng, $default_lng));

		// copy email template
		$sql = "INSERT IGNORE INTO " . MailTemplate::tableNameFromClassName('MailTemplate') . " 
					SELECT id,?,subject,body 
						FROM " . MailTemplate::tableNameFromClassName('MailTemplate') . " 
						WHERE language_id=?";
		self::query($sql, array($lng, $default_lng));


		// set title and description from default
		$site_title = Config::option('site_title', null, true);
		$site_title[$lng] = $site_title[$default_lng];
		Config::optionSet('site_title', $site_title);

		$site_description = Config::option('site_description', null, true);
		$site_description[$lng] = $site_description[$default_lng];
		Config::optionSet('site_description', $site_description);
	}

	public static function get_url($url = '', $lng = null)
	{
		$url = ltrim($url, '/');

		if (is_null($lng))
		{
			$lng = I18n::getLocale();
		}

		$lng_cookie = Flash::getCookie('lng');
		$current_lng = I18n::getLocale();

		if (!Language::isDefault($lng))
		{
			$url = $lng . '/' . $url;
		}
		elseif ($current_lng != $lng)
		{
			// set if there is language cookie then set default lng to reset website to default url 
			$url = $lng . '/' . $url;
		}

		// fix several ? marks if urlrewrite disabled
		if (!USE_MOD_REWRITE)
		{
			if (strpos($url, '?') !== false)
			{
				$url = str_replace('?', '&', $url);
			}
		}


		return get_url($url);
	}

	public static function htmlLanguage($url = '', $pattern = array())
	{
		$return = '';

		// define default pattern
		$pattern_default = array(
			'wrap'		 => '<div class="top language">{LINK_ALL}</div>',
			'link_all'	 => '<a href="{URL}" class="lng_{ID}{CLASS_SEL}">{TITLE}</a>',
			'link_other' => '<a href="{URL}" class="lng_{ID}{CLASS_SEL}">{TITLE}</a>',
			'link_sel'	 => '<a href="{URL}" class="lng_{ID}{CLASS_SEL}">{TITLE}</a>'
		);

		// substitute missing parts from default pattern
		$pattern = array_merge($pattern_default, $pattern);

		// get lngObj with url, title, id, sel values
		if (is_null(self::$arr_lngObj))
		{
			self::htmlLanguageBuild($url);
		}

		// now format output
		if (self::$arr_lngObj)
		{
			$arr_link_all = array();
			$arr_link_other = array();
			$arr_link_sel = array();


			foreach (self::$arr_lngObj as $lngObj)
			{
				$arr_link_all[] = str_replace(array(
					'{URL}',
					'{ID}',
					'{CLASS_SEL}',
					'{TITLE}'
						), array(
					$lngObj->url,
					$lngObj->id,
					$lngObj->sel ? ' sel' : '',
					$lngObj->title
						), $pattern['link_all']);

				if ($lngObj->sel)
				{
					$arr_link_sel[] = str_replace(array(
						'{URL}',
						'{ID}',
						'{CLASS_SEL}',
						'{TITLE}'
							), array(
						$lngObj->url,
						$lngObj->id,
						$lngObj->sel ? ' sel' : '',
						$lngObj->title
							), $pattern['link_sel']);
				}
				else
				{
					$arr_link_other[] = str_replace(array(
						'{URL}',
						'{ID}',
						'{CLASS_SEL}',
						'{TITLE}'
							), array(
						$lngObj->url,
						$lngObj->id,
						$lngObj->sel ? ' sel' : '',
						$lngObj->title
							), $pattern['link_other']);
				}
			}


			// now populate pattern
			$return = str_replace(array(
				'{LINK_ALL}',
				'{LINK_OTHER}',
				'{LINK_SEL}'
					), array(
				implode(' ', $arr_link_all),
				implode(' ', $arr_link_other),
				implode(' ', $arr_link_sel)
					), $pattern['wrap']);
		}

		return $return;
	}

	public static function htmlLanguageBuild($obj = '', $type = 'general', $fresh = false)
	{
		if ($fresh || !isset(self::$arr_lngObj))
		{
			// if admin then can view all languages with prompt that is it disabled language 
			$return = array();

			$language = self::getLanguages();
			$current_language = I18n::getLocale();
			if ($language)
			{
				foreach ($language as $l)
				{

					$disabled = '';

					if (!$l->enabled)
					{
						if (AuthUser::hasPermission(User::PERMISSION_ADMIN))
						{
							$disabled = ' (' . __('Disabled') . ')';
						}
						else
						{
							continue;
						}
					}



					/* $return[] = '<a href="' . self::urlByObject($obj, $type, $l->id) . '" class="lng_'
					  . $l->id . ($current_language == $l->id ? ' sel' : '') . '">' .
					  Language::formatName($l) . $disabled . '</a>'; */

					$lngObj = new stdClass();
					$lngObj->id = $l->id;
					$lngObj->url = self::urlByObject($obj, $type, $l->id);
					$lngObj->sel = ($current_language == $l->id);
					$lngObj->title = Language::formatName($l) . $disabled;

					$return[] = $lngObj;
				}
			}

			// do not generate language switch if there is only one language
			if (count($return) < 2)
			{
				self::$arr_lngObj = array();
			}
			else
			{
				self::$arr_lngObj = $return;
			}
		}

		return self::$arr_lngObj;
	}

	public static function relAlternate($obj = '', $type = 'general')
	{
		/*
		 * <link rel="alternate" hreflang="en" href="http://www.example.com/page.html" />
		  <link rel="alternate" hreflang="en-gb" href="http://en-gb.example.com/page.html" />
		  <link rel="alternate" hreflang="en-us" href="http://en-us.example.com/page.html" />
		  <link rel="alternate" hreflang="de" href="http://de.example.com/seite.html" />
		 */
		$return = array();

		$language = self::getLanguages(self::STATUS_ENABLED);
		if ($language)
		{
			foreach ($language as $l)
			{
				$return[] = '<link rel="alternate" hreflang="' . $l->id . '" href="' . self::urlByObject($obj, $type, $l->id) . '" />';
			}
		}

		if (count($return) > 1)
		{
			return implode(' ', $return);
		}

		return '';
	}

	public static function urlByObject($obj, $type, $lng_id)
	{
		switch ($type)
		{
			case 'page':
				// get page url for all languages
				$url = Page::url($obj, $lng_id);
				break;
			case 'ad':
				// get ad url for all languages
				$url = Ad::url($obj, $lng_id);
				break;
			case 'general':
			default:
				$url = Language::get_url($obj, $lng_id);
				break;
		}

		return $url;
	}

	public static function formatName($language)
	{
		return '<img src="' . Language::imageUrl($language) . '" width="16" height="11" alt="' . View::escape($language->name) . '" /> 
			<span>' . View::escape($language->name) . '</span>';
	}

	/**
	 * generate languege tabs for given language array. will not display if there is only one language defined 
	 * 
	 * @param array $languages
	 * @param string $tab_key
	 * @param string $pattern
	 * @return string 
	 */
	public static function tabs($languages, $tab_key = 'name_', $pattern = '<div class="tabs">{tabs}</div>')
	{
		$echo_tabs = '';
		if (count($languages) > 1)
		{
			foreach ($languages as $lng)
			{
				$echo_tabs .= self::tabsTab($lng, $tab_key) . ' ';
			}
			$echo_tabs = str_replace('{tabs}', $echo_tabs, $pattern);
		}

		return $echo_tabs;
	}

	/**
	 * mark default input for using unique slug generation. Detects if given language default language
	 * 
	 * @param Language $lng
	 * @return string 
	 */
	public static function tabsRelDefault($lng)
	{
		if (Language::isDefault($lng->id))
		{
			return 'rel="default_name"';
		}
		return '';
	}

	/**
	 * Format language tab as link
	 * 
	 * @param Language $language
	 * @param string $key
	 * @return string 
	 */
	public static function tabsTab($language, $key = 'name_')
	{
		return '<a href="#' . $key . View::escape($language->id) . '" 
				data-hide="' . $key . '"  
				data-show="' . $key . View::escape($language->id) . '"
				title="' . View::escape($language->name) . '">' .
				Language::formatName($language) . '</a>';
	}

	/**
	 * generate tab css class to identify current tab and language
	 * @param string $tab_key
	 * @param Language $lng
	 * @return string 
	 */
	public static function tabsTabKey($tab_key, $lng)
	{
		return $tab_key . ' ' . $tab_key . View::escape($lng->id);
	}

	/**
	 * Add lng info for multilingual form labels (ex: en, tr, ru...)
	 * @param array $labguages
	 * @param Language $lng
	 * @return string 
	 */
	public static function tabsLabelLngInfo($labguages, $lng, $pattern = ' <span class="gray_info">({name})</span>')
	{
		if (count($labguages) > 1)
		{
			return str_replace('{name}', View::escape($lng->id), $pattern);
		}
		return '';
	}

	/**
	 * get available images for language selector 
	 * 
	 * @return boolean 
	 */
	public static function getImages()
	{
		if (is_null(self::$imgs))
		{
			// get themes from directory
			self::$imgs = array();
			$theme_root = FROG_ROOT . '/public/images/lng/';

			// Files in wp-content/themes directory and one subdir down
			$themes_dir = @ opendir($theme_root);
			if (!$themes_dir)
			{
				return self::$imgs;
			}

			while (($theme_dir = readdir($themes_dir)) !== false)
			{

				if (is_file($theme_root . '/' . $theme_dir))
				{
					if ($theme_dir{0} == '.' || $theme_dir == '..' || $theme_dir == 'CVS')
					{
						continue;
					}
					self::$imgs[] = $theme_dir;
				}
			}
			if (is_dir($theme_dir))
			{
				@closedir($theme_dir);
			}
			if (!$themes_dir || !self::$imgs)
			{
				return self::$imgs;
			}
			sort(self::$imgs);
		}

		return self::$imgs;
	}

	public static function imageUrl($language)
	{
		return self::imageUrlByFile($language->img);
	}

	public static function imageUrlByFile($file = '')
	{
		return URL_ASSETS . 'images/lng/' . $file;
	}

	/**
	 * Move position of current category up or down by one
	 * @param type $id
	 * @param type $dir direction up, down
	 * @return type bool 
	 */
	public static function changePosition($id, $dir)
	{
		// get requested category 
		$language = self::findByIdFrom('Language', $id);
		if (!$language)
		{
			return false;
		}

		// get all categories in position order
		$languages = Language::findAllFrom('Language', '1=1 ORDER BY pos');

		$found = false;
		$arr = array();
		$i = 1;
		foreach ($languages as $c)
		{
			if ($id == $c->id)
			{
				// store old position
				$old_pos = $i;
				$found = true;
			}
			else
			{
				$arr[] = $c->id;
			}
			$i++;
		}

		if (!$found)
		{
			return false;
		}


		$total = count($languages);
		if ($dir == 'up')
		{
			$new_pos = $old_pos - 1;
		}
		else
		{
			$new_pos = $old_pos + 1;
		}

		if ($new_pos < 1)
		{
			$new_pos = 1;
		}

		if ($new_pos > $total)
		{
			$new_pos = $total;
		}



		// save in new order, update positions
		$i = 1;
		$updated = false;
		foreach ($arr as $v)
		{
			if ($i == $new_pos)
			{
				Language::update('Language', array('pos' => $i), 'id=?', array($id));
				$i++;
				$updated = true;
			}
			Language::update('Language', array('pos' => $i), 'id=?', array($v));
			$i++;
		}

		if (!$updated)
		{
			Language::update('Language', array('pos' => $i), 'id=?', array($id));
		}

		SimpleCache::delete('languages');

		return true;
	}

	public static function getLastPosition()
	{
		$last = self::findOneFrom('Language', '1=1 ORDER BY pos DESC', array());

		return intval($last->pos);
	}

	public static function getDefault()
	{
		return Config::option('default_language');
	}

	/**
	 * return home url for default language in cookie or settings
	 */
	public static function urlHome()
	{
		$location = Config::getDefaultLocation();
		if ($location)
		{
			return Location::url($location);
		}

		return Language::get_url();
	}

	/**
	 * return home url for default language in cookie or settings
	 */
	public static function urlHomeReset()
	{
		return Language::get_url('home/');
	}

	/**
	 * convert $_GET values again to url query 
	 * 
	 * @param array $arr
	 * @param string $return
	 * @param string $key_pattern
	 * @return string 
	 */
	public static function get2url($arr = array(), & $return = array(), $key_pattern = '{key}')
	{
		foreach ($arr as $k => $v)
		{
			$key = str_replace('{key}', $k, $key_pattern);
			if (is_array($v))
			{
				self::get2url($v, $return, $key . '[{key}]');
			}
			else
			{
				if (strlen($v))
				{
					$return[] = $key . '=' . urlencode(View::escape($v));
				}
			}
		}

		return implode('&', $return);
	}

	public static function thisUrl($override = array())
	{
		$vars = $_GET;
		unset($vars['page']);
		if ($override)
		{
			$vars = self::array_merge_recursive($vars, $override);
		}


		return self::get2url($vars);
	}

	/**
	 * merge 2 arrays recursively and remove values with empty string
	 * 
	 * @param array $arr1
	 * @param array $arr2
	 * @return array
	 */
	public static function array_merge_recursive($arr1, $arr2 = array())
	{
		$result = array();

		foreach ($arr1 as $k => $v)
		{
			if (isset($arr2[$k]))
			{
				if (is_array($v))
				{
					$result[$k] = self::array_merge_recursive($arr1[$k], $arr2[$k]);
				}
				else
				{
					$result[$k] = trim($arr2[$k]);
				}
			}
			else
			{
				if (is_array($v))
				{
					$result[$k] = self::array_merge_recursive($arr1[$k]);
				}
				else
				{
					$result[$k] = trim($arr1[$k]);
				}
			}
		}


		// now arr new values to $arr1
		if (is_array($arr2))
		{
			foreach ($arr2 as $k => $v)
			{
				if (!isset($result[$k]))
				{
					$result[$k] = $v;
				}
			}
		}

		/* DO NOT remove empty values because they are used to reset existing values */

		// remove empty values 
		/* $result_keys = array_keys($result);
		  foreach($result_keys as $k)
		  {
		  if(!is_array($result[$k]) && strlen($result[$k]) == 0)
		  {
		  unset($result[$k]);
		  }
		  } */

		return $result;
	}

	/**
	 * generates filter removing button link 
	 * 
	 * @param array $override existing values
	 * @param boolean $use_permalink use function from permlaink 
	 * @return string HTML link
	 */
	public static function thisUrlRemoveLink($override = array(), $use_permalink = false)
	{
		$url = self::thisUrlRemove($override, $use_permalink);

		//return ' <a href="' . Language::get_url('admin/items/?' . $thisurl) . '" class="button red small" title="' . __('Remove filter') . '">x</a>';

		return ' <a href="' . $url . '" class="search_filter_remove" title="' . __('Remove filter') . '">x</a>';
	}

	/**
	 * Generate filter removing url 
	 * 
	 * @param array $override
	 * @param bool $use_permalink
	 * @return string
	 */
	public static function thisUrlRemove($override = array(), $use_permalink = false)
	{
		// remove page 
		$override['page'] = '';
		if ($use_permalink)
		{
			// use function from permalink. populates link as permalink
			$url = Permalink::vars2url($_GET, $override);
		}
		else
		{
			// use thisUrl function, populates link with get variables
			$thisurl = self::thisUrl($override);
			if (strlen($thisurl))
			{
				$thisurl = '?' . $thisurl;
			}
			$url = Language::get_url(self::getCurrentUrl(true) . $thisurl);
		}
		return $url;
	}

	/**
	 * Get current url permalink, no url _GET vars
	 * @param bool $remove_locale false 
	 * @return string
	 */
	public static function getCurrentUrl($remove_locale = false)
	{
		if (self::$current_url === null)
		{
			$return = ltrim(Dispatcher::getCurrentUrl(), '/');

			// remove index/load if preset, it is used in index.php to use loaded in IndexController for variable category and locations urls
			$remove_str = 'index/load/';
			if (strpos($return, $remove_str) === 0)
			{
				$return = substr($return, strlen($remove_str));
			}

			self::$current_url = $return;
		}

		if ($remove_locale)
		{
			if (self::$current_url_no_locale === null)
			{
				// parse current url and remove any language variable from beginnig of url 
				$return = self::$current_url;
				$arr_url = explode('/', $return);
				if (self::isAvailableLocale($arr_url[0]))
				{
					// remove it 
					array_shift($arr_url);
					$return = implode('/', $arr_url);
				}
				self::$current_url_no_locale = $return;
			}

			return self::$current_url_no_locale;
		}

		return self::$current_url;
	}

	/**
	 * update language files from _backup when updating script
	 */
	public static function updateEmptyLanguageTranslations()
	{
		// get all defined languages
		$languages = self::getLanguages();
		foreach ($languages as $lng)
		{
			if ($lng->id !== 'en')
			{
				I18nBuilder::updateFromBackup($lng->id);
			}
		}
	}

}
