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
 * class Config
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Config extends Record
{

	const TABLE_NAME = 'config';
	const MAX_MEMORY_LIMIT = '256M';
	const VERSION = '2.0.7';
	const LOAD_ALL = false;
	// TODO build site and set actual data here	
	const UPDATE_URL = 'http://classibase.com/simcls-update/latest.php';
	const SCRIPT_URL = 'http://classibase.com/';
	const SCRIPT_NAME = 'ClassiBase';

	static $conf = false;
	private static $loaded = false;
	private static $default_location = null;
	private static $_page_types = array();
	private static $_log = array();
	private static $_banned_str = array();
	private static $_url_protocol = null;
	private static $cols = array(
		'name'		 => 1,
		'val'		 => 1,
		'autoload'	 => 1
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	/**
	 * Get option from database
	 * 
	 * @param string $name
	 * @param string $lng
	 * @param bool $raw_data
	 * @return type
	 */
	public static function option($name, $lng = null, $raw_data = false)
	{
		self::loadByKey($name);


		// it should be loaded by now
		$val = self::$conf->{$name};
		if (is_array($val) && !$raw_data)
		{
			// multilingual value 
			if (is_null($lng))
			{
				$lng = I18n::getLocale();
			}
			if (isset($val[$lng]))
			{
				// value defined 
				$val = $val[$lng];
			}
			else
			{
				// value not defined then return default value 
				$lng_default = Language::getDefault();
				if (isset($val[$lng_default]))
				{
					$val = $val[$lng_default];
				}
			}
			// if none matched then will not modify val and return raw array 
		}

		return $val;
	}

	public static function optionElseDefault($name, $default = null, $lng = null, $raw_data = false)
	{

		$val = self::option($name, $lng, $raw_data);

		if (!strlen(trim($val)))
		{
			$val = $default;
		}

		return $val;
	}

	/**
	 * Set config option to databsase
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @param bool $autoload
	 * @param bool $set_if_empty used for initial setting default values
	 * @return boolean
	 */
	public static function optionSet($name, $value, $autoload = true, $set_if_empty = false)
	{
		// read object first 
		self::load();

		// read from db 
		$opt = self::findOneFrom('Config', 'name=?', array($name));
		//print_r($opt);

		if ($opt)
		{
			if ($set_if_empty)
			{
				// set only if no value set before
				// this option set before so finish here
				return true;
			}

			// update record
			$save_key = 'name';
		}
		else
		{
			// save as new record
			$opt = new Config();

			$opt->name = $name;
			$save_key = 'no_id';
		}

		// update autoload value
		$opt->autoload = $autoload ? 1 : 0;


		switch ($name)
		{
			case 'site_title':
			case 'site_description':
			case 'site_button_title':
				$_data = array();
				foreach ($value as $lng => $val)
				{
					// switch to required locale and get defaults
					I18n::saveLocale();
					I18n::setLocale($lng);
					$_data[$lng] = strlen($val) ? trim($val) : Config::preferredValue($name);
					I18n::restoreLocale();
				}
				$opt->val = serialize($_data);
				self::$conf->{$name} = $_data;
				break;
			default:
				if (is_array($value))
				{
					// always serialize array 
					// unserialize when needed before using for each variable. 
					$opt->val = serialize($value);
				}
				else
				{
					$opt->val = strlen($value) ? trim($value) : Config::preferredValue($name);
				}
				if ($opt->val == null)
				{
					$opt->val = '';
				}
				self::$conf->{$name} = $opt->val;
		}

		if ($save_key === 'no_id')
		{
			// use insert update 
			$sql = "INSERT INTO " . Config::tableNameFromClassName('Config') . " (name,val,autoload) "
					. "VALUES(?,?,?) "
					. "ON DUPLICATE KEY UPDATE val=?, autoload=?";
			$result = Config::query($sql, array($opt->name, $opt->val, $opt->autoload, $opt->val, $opt->autoload));
		}
		else
		{
			// save option 
			$result = $opt->save($save_key);
		}

		// clear cache 
		SimpleCache::delete('config');

		return $result;
	}

	public static function optionDelete($name)
	{
		// read from db 
		$result = self::deleteWhere('Config', 'name=?', array($name));

		if ($result)
		{
			// update loaded values
			unset(self::$conf->{$name});
		}

		// clear cache 
		SimpleCache::delete('config');

		return $result;
	}

	public static function optionDeleteByKey($name)
	{
		// read from db 
		$result = self::deleteWhere('Config', 'name LIKE ?', array($name . '%'));

		if ($result)
		{
			$arr_delete_keys = array();
			// delete loaded values
			foreach (self::$conf as $key => $val)
			{
				$strpos_key = strpos($key, $name);
				if ($strpos_key !== false && $strpos_key === 0)
				{
					$arr_delete_keys[] = $key;
				}
			}

			foreach ($arr_delete_keys as $key)
			{
				unset(self::$conf->{$key});
			}
		}

		/// prevent infinite loop deleting config  
		if ($name !== 'cache.config')
		{
			// clear cache 
			SimpleCache::delete('config');
		}

		return $result;
	}

	public static function load($force = false)
	{
		if (!self::$loaded || $force)
		{
			$cache_key = 'config';
			$c = SimpleCache::get($cache_key);
			if ($c === false)
			{
				if (self::LOAD_ALL)
				{
					// load all records from DB once and reduce DB traffic
					// stores all data in memory 
					$c = self::findAllFrom('Config');
				}
				else
				{
					// load only autoload value 
					// other values will be loaded by request
					// it is good if you dont want lo load big data to memeory
					// but you will get several DB queries requesting to load missing data
					$c = self::findAllFrom('Config', 'autoload=1');
				}

				SimpleCache::set($cache_key, $c);
			}

			self::$conf = new stdClass();
			foreach ($c as $c_)
			{
				self::prepareVal($c_->name, $c_->val);
			}
			self::$loaded = true;
		}

		return self::$conf;
	}

	/**
	 * Load option by key of it is not already loaded 
	 * 
	 * @param type $key
	 * @return type
	 */
	public static function loadByKey($key)
	{
		self::load();

		if (!self::LOAD_ALL)
		{
			// loaded only autoload values, now load requested if not loaded by autoload
			if (!isset(self::$conf->{$key}))
			{
				self::loadByKeyFresh($key);
			}
		}
		return self::$conf;
	}

	/**
	 * Load given option value fresh on request.
	 * This is used when updating multi value option that takes long time and can be updated by other while generating and saving new values 
	 * 
	 * @param string $key
	 * @return string
	 */
	public static function loadByKeyFresh($key)
	{
		$c = self::findByIdFrom('Config', $key, 'name');
		if ($c)
		{
			self::prepareVal($key, $c->val);
		}
		else
		{
			self::$conf->{$key} = false;
		}
		return self::$conf->{$key};
	}

	private static function prepareVal($key, $val)
	{
		switch ($key)
		{
			case 'site_title':
			case 'site_description':
			case 'site_button_title':
				// unserialize multilingual values 
				if (Theme::previewTheme() && isset(Theme::previewTheme()->data[$key]))
				{
					self::$conf->{$key} = Theme::previewTheme()->data[$key];
				}
				else
				{
					self::$conf->{$key} = unserialize($val);
				}
				break;
			case 'currency_format':
			case 'currency_decimal_num':
			case 'currency_decimal_point':
			case 'currency_thousands_seperator':
				self::$conf->{$key} = @unserialize($val);
				break;
			default:
				if (strpos($key, '_ml_') !== false)
				{
					//it is multilingual unserialize it 
					self::$conf->{$key} = @unserialize($val);
				}
				else
				{
					self::$conf->{$key} = $val;
				}
		}
	}

	/**
	 * URL to public folder of theme 
	 * use URL_ASSETS for public folder of script 
	 * 
	 * @return string
	 */
	static function urlAssets()
	{
		// check if template set then load template location 
		$theme = Theme::getTheme();
		return $theme->url() . 'public/';
	}

	/**
	 * render breadcrumb with customization if required 
	 * @param type $arr
	 * @param type $seperator
	 * @param type $custom_home
	 * @param type $display
	 * @return boolean|string
	 */
	public static function renderBreadcrumb($arr = array(), $seperator = '', $custom_home = null, $display = true)
	{
		if (!$arr)
		{
			return false;
		}

		$return = false;

		// change home text with custom string
		if ($custom_home)
		{
			$arr[0][0] = $custom_home;
		}


		$total = count($arr);
		$sep = $seperator;
		foreach ($arr as $val)
		{
			$name = $val[0];
			$url = $val[1];

			if ($custom_home && strcmp($name, $custom_home) != 0)
			{
				$name = View::escape($name);
			}


			if ($total <= 1)
			{
				$sep = '';
			}

			if ($url)
			{
				$arr_[] = '<li>'
						. '<a href="' . $url . '">'
						. $name
						. '</a>'
						. $sep
						. '</li>';
			}
			else
			{
				$arr_[] = '<li>'
						. $name
						. $sep
						. '</li>';
			}
			$total--;
		}



		if ($arr_)
		{
			//$return = '<ul id="breadcrumb" class="breadcrumb" ' . Schema::RDFA_BREADCRUMB . '>' . implode('', $arr_) . '</ul>';
			$return = '<ul id="breadcrumb" class="breadcrumb">' . implode('', $arr_) . '</ul>';
		}

		if ($return && $display)
		{
			echo $return;
		}

		return $return;
	}

	/**
	 * check posted value, if not set then set to preferred value
	 * @param type $name
	 * @return string 
	 */
	public static function preferredValue($name)
	{
		$preferred = array(
			'theme'				 => 'base',
			'site_title'		 => 'Simple Classifieds',
			'site_button_title'	 => __('Post ad')
		);

		return $preferred[$name];
	}

	public static function pageTypeRegister($arr)
	{
		if (!isset(self::$_page_types[$arr['id']]))
		{
			$obj = new stdClass();
			foreach ($arr as $k => $v)
			{
				$obj->$k = $v;
			}

			self::$_page_types[$obj->id] = $obj;
		}
	}

	public static function pageTypeRegisterDefaults()
	{

		self::pageTypeRegister(array(
			'id'			 => 'home',
			'title'			 => __('Home page'),
			'description'	 => '',
			'widgets'		 => true,
		));

		self::pageTypeRegister(array(
			'id'			 => 'category',
			'title'			 => __('Category page'),
			'description'	 => __('Page where ads in selected category listed')
		));
		self::pageTypeRegister(array(
			'id'			 => 'user',
			'title'			 => __('User page'),
			'description'	 => __('Page where ads by selected user listed')
		));
		self::pageTypeRegister(array(
			'id'			 => 'ad',
			'title'			 => __('Ad page'),
			'description'	 => __('Page where selected ad displayed.')
		));

		self::pageTypeRegister(array(
			'id'			 => 'ad_post',
			'title'			 => __('Ad posting page'),
			'description'	 => __('Page where site visitor compose new ad.')
		));

		self::pageTypeRegister(array(
			'id'			 => 'info',
			'title'			 => __('Info page'),
			'description'	 => __('Static page with text information, contact form.')
		));
	}

	public static function pageTypesGet()
	{
		self::pageTypeRegisterDefaults();

		return self::$_page_types;
	}

	/**
	 * generates token that will expire in 6 hours
	 * 
	 * @param bool $just_expired current or just expired nounce
	 * @param string $key if provided will use given key for checking purpose, else generate random key
	 * @return string
	 */
	public static function nounce($just_expired = false, $key = null)
	{
		// generate nounce token 

		$time_frame = 6; //hours
		$user_id = AuthUser::$user->id;
		// $ip = Input::getInstance()->ip_address();
		$time_token = ceil(REQUEST_TIME / (3600 * $time_frame));
		if (is_null($key))
		{
			$key = User::genActivationCode('{code}');
		}

		if ($just_expired)
		{
			$time_token = $time_token - 1;
		}

		return md5($user_id . '.' . $_SERVER['HTTP_USER_AGENT'] . '.' . $time_token . '.' . $key) . '_' . $key;
	}

	/**
	 * check if token is not expired 
	 * 
	 * @param string $key
	 * @return bool 
	 */
	public static function nounceCheck($append_error = false, $key = 'nounce')
	{
		$nounce = $_REQUEST[$key];
		$arr_nounce = explode('_', $nounce);
		$nounce_key = $arr_nounce[1];

		// check nounce for current end one previus for making sure user will pass time frame
		$return = (strcmp($nounce, self::nounce(false, $nounce_key)) === 0) || (strcmp($nounce, self::nounce(true, $nounce_key)) === 0);
		if ($append_error && !$return)
		{
			Validation::getInstance()->set_error(__('Action token expired. Please try again.'));
		}

		return $return;
	}

	/**
	 * input field with nounce value 
	 * 
	 * @param type $key
	 * @return type 
	 */
	public static function nounceInput($key = 'nounce')
	{
		return '<input type="hidden" name="' . $key . '" value="' . self::nounce() . '" />';
	}

	public static function formatText($text)
	{
		// remove script  and add new lines 
		$text = TextTransform::nl2br($text);
		if (Config::option('url_to_link'))
		{
			$text = TextTransform::email2link($text);
			$text = TextTransform::url2link($text);
		}

		return $text;
	}

	/**
	 * Format relative date 
	 * 
	 * @param int $time
	 * @param int $depth
	 * @return string
	 */
	public static function timeRelative($time, $depth = 1, $relative = true)
	{
		$units = array(
			31104000 => array(__('{num} year'), __('{num} years'), __('last year'), __('next year')),
			2592000	 => array(__('{num} month'), __('{num} months'), __('last month'), __('next month')),
			604800	 => array(__('{num} week'), __('{num} weeks'), __('last week'), __('next week')),
			86400	 => array(__('{num} day'), __('{num} days'), __('yesturday'), __('tomorrow')),
			3600	 => array(__('{num} hour'), __('{num} hours')),
			60		 => array(__('{num} minute'), __('{num} minutes')),
			1		 => array(__('{num} second'), __('{num} seconds'))
		);

		//$conjugator = " and ";
		$conjugator = $separator = ", ";
		$now = __('now');
		$empty = "";
		$pattern_ago = __('{time} ago');
		$pattern_left = __('{time} left');

		# DO NOT EDIT BELOW

		$timediff = REQUEST_TIME - $time;
		// less than 30 second is considered as now
		if ($timediff < 30 && $timediff > 0)
		{
			return $now;
		}
		if ($depth < 1)
		{
			return $empty;
		}

		$remainder = abs($timediff);
		$u = 0;
		$output = '';
		$count_depth = 0;

		foreach ($units as $value => $unit)
		{
			if ($remainder >= $value)
			{
				if ($count_depth < $depth)
				{
					$u = intval(($remainder / $value));
					$remainder %= $value;

					if ($depth == 1 && $u == 1 && $relative)
					{
						/// only one level, show yesturday or tmorrow result 
						if ($timediff > 0)
						{
							if (isset($unit[2]))
							{
								return $unit[2];
							}
						}
						else
						{
							if (isset($unit[3]))
							{
								return $unit[3];
							}
						}
						// if unit is not defined then will continue loop and display regular result 
					}


					if ($u > 0)
					{
						$pluralise = $u > 1 ? $unit[1] : $unit[0];
						$separate = $remainder == 0 || $depth == $count_depth + 1 ? $empty :
								($depth == 1 ? $conjugator : $separator);
						$output .= str_replace('{num}', $u, $pluralise) . "{$separate}";
					}
				}
				$count_depth++;
			}
		}

		if ($relative)
		{
			if ($timediff > 0)
			{
				$pattern = $pattern_ago;
			}
			else
			{
				$pattern = $pattern_left;
			}
			$output = str_replace('{time}', $output, $pattern);
		}
		return $output;
	}

	/**
	 * build title string with {name} in {name2} format
	 * 
	 * @param array $arr
	 * @return string
	 */
	public static function buildTitle($arr)
	{
		$page_title = '';
		if (is_array($arr))
		{
			foreach ($arr as $title)
			{
				if (!strlen($page_title))
				{
					$page_title = $title;
				}
				elseif (strlen($title))
				{
					$page_title = __('{name} in {name2}', array(
						'{name}'	 => $page_title,
						'{name2}'	 => $title
					));
				}
			}
		}

		return $page_title;
	}

	/**
	 * if in maintenance mode will display page with info message to moderators, only info massgae to others visitors
	 * 
	 * @param bool $redirect true
	 * @return bool 
	 */
	public static function checkMaintenance($redirect = true)
	{
		// check maintenance mode
		if ($redirect && Config::option('maintenance'))
		{
			if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
			{
				$msg = __('Website is in maintenace mode. You have moderator permissions and able to view site in maintenance mode.');
				$msg .= ' <a href="' . Language::get_url('admin/maintenance/') . '">' . __('Disable maintenance mode') . '</a>';
				Validation::getInstance()->set_info($msg);
			}
			else
			{
				Config::displayMessagePage(__('Website is in maintenace mode. Please visit us again later.'));
			}
		}

		return Config::option('maintenance') ? true : false;
	}

	/**
	 * display single message in one page 
	 * 
	 * @param string $msg
	 * @param string $type info|error|success
	 * @param bool $redirect false
	 */
	public static function displayMessagePage($msg, $type = 'info', $redirect = false)
	{
		if ($redirect)
		{
			Flash::set($type, $msg);
			redirect(Language::get_url('login/message/'));
		}

		switch ($type)
		{
			case 'error':
				Validation::getInstance()->set_error($msg);
				break;
			case 'success':
				Validation::getInstance()->set_success($msg);
				break;
			case 'info':
			default:
				Validation::getInstance()->set_info($msg);
				break;
		}
		$login_controller = new LoginController();
		return $login_controller->message();
	}

	/**
	 * log given data to db for debugging 
	 * 
	 * @param type $data
	 * @param string $type 
	 */
	public static function log($data = null, $type = '')
	{
		self::logAppend($data);
		if (count(self::$_log))
		{
			// record only if has data 
			Record::insert('Log', array('data' => serialize(self::$_log), 'type' => $type, 'added_at' => REQUEST_TIME));
		}
	}

	public static function logAppend($data)
	{
		if (is_array($data))
		{
			self::$_log += $data;
		}
	}

	/**
	 * replace or detect bad words
	 * 
	 * @param string $text to check
	 * @param bool $replace 
	 * @param string $key bad_word_filter|bad_word_block
	 * @return boolean|replaced text
	 */
	public static function hasSpamWords($text, $replace = false, $key = 'bad_word_filter')
	{
		if (!isset(self::$_banned_str[$key]))
		{
			$words = Config::option($key);
			$words = explode("\n", $words);
			$arr_banned = array();
			foreach ($words as $w)
			{
				$w = trim($w);
				if (strlen($w))
				{
					$arr_banned[] = trim($w);
				}
			}
			if ($arr_banned)
			{
				// will be string like this /word1|word2|word3/i
				self::$_banned_str[$key] = '/' . implode('|', $arr_banned) . '/i';

				// escape special characters 
				$arr_search = array('+', '*', '.');
				$arr_replace = array();
				foreach ($arr_search as $s)
				{
					$arr_replace[] = '\\' . $s;
				}
				self::$_banned_str[$key] = str_replace($arr_search, $arr_replace, self::$_banned_str[$key]);
			}
			else
			{
				self::$_banned_str[$key] = '';
			}
		}


		if (self::$_banned_str[$key])
		{
			if ($replace)
			{
				$bad_word_replacement = Config::option('bad_word_replacement');
				return preg_replace(self::$_banned_str[$key], $bad_word_replacement, $text);
			}
			else
			{
				return preg_match(self::$_banned_str[$key], $text);
			}
		}

		if ($replace)
		{
			// nothing to replace return initial string
			return $text;
		}

		return false;
	}

	public static function scriptName()
	{
		$powered_by_link = Config::option('powered_by_link');

		if (is_null($powered_by_link))
		{
			$powered_by_link = '<a href="' . self::SCRIPT_URL . '" target="_blank">' . self::SCRIPT_NAME . '</a>';
		}

		return $powered_by_link;
	}

	/**
	 * set demo info in info box 
	 */
	public static function demoInfo()
	{
		if (DEMO && !Theme::previewTheme())
		{
			// reset every 1 hour
			$min = 60 - (REQUEST_TIME / 60) % 60;
			Validation::getInstance()->set_info(__('This demo will be reset in {num} minutes.', array('{num}' => $min)));
		}
	}

	/**
	 * set demo info in info box 
	 */
	public static function demoThemeBar()
	{
		if (DEMO && !Theme::previewTheme())
		{
			// theme can be set via _GET, _COOKIE in demo mode
			Theme::setDemoTheme();

			// get all valid themes and presets of current theme 
			$themes = Theme::getThemes();

			$current_theme = Theme::getTheme();

			foreach ($themes as $theme_id)
			{
				if ($current_theme->id == $theme_id)
				{
					$selected = ' selected="selected"';
				}
				else
				{
					$selected = '';
				}
				$select_theme .= '<option value="' . View::escape($theme_id) . '"' . $selected . '>'
						. Inflector::humanize($theme_id) . '</option>';
			}

			if ($select_theme)
			{
				$select_theme = __('Theme') . ':<select name="select_theme" id="select_theme">' . $select_theme . '</select>';
			}

			// load presets of current theme 
			if (isset($current_theme->info['customize']['theme_presets']['fields']['theme_presets']['value']))
			{
				$preset_selected = false;
				$presets = $current_theme->info['customize']['theme_presets']['fields']['theme_presets']['value'];
				$current_preset = $current_theme->option('theme_presets', false, true);
				foreach ($presets as $preset_key => $preset_value)
				{
					if ($current_preset == $preset_key)
					{
						$selected = ' selected="selected"';
						$preset_selected = true;
					}
					else
					{
						$selected = '';
					}
					$select_preset .= '<option value="' . View::escape($preset_key) . '"' . $selected . '>'
							. View::escape($preset_value) . '</option>';
				}
			}

			if ($select_preset)
			{
				$select_preset = ' - ' . __('Preset') . ':<select name="select_theme_preset" id="select_theme_preset">'
						. ($preset_selected ? '' : '<option value=""></option>')
						. $select_preset . '</select>';
			}

			if ($current_theme->info['info_url'])
			{
				$info_url = $current_theme->info['info_url'];
			}
			else
			{
				$info_url = $current_theme->info['author_url'];
			}

			$theme_bar_html = '<div class="select_theme_bar">' . $select_theme . $select_preset
					. ' <a href="' . View::escape($info_url) . '">' . __('Download this theme') . '</a></div>';
			$theme_bar_js = '<script>
				addLoadEvent(setThemeSwith);				

				//var jqLoaded=self.setInterval(function(){isJqLoaded()},1000);
				function isJqLoaded()
				{
					  if (typeof jQuery != \'undefined\') {
						  setThemeSwith();
						  jqLoaded=window.clearInterval(jqLoaded);
					  }
				}

				function setThemeSwith(){
					$(function(){
						$("body:first").prepend(\'' . preg_replace('/.\n/', "", $theme_bar_html) . '\');
						$("#select_theme").change(function(){top.location="?theme="+$("#select_theme").val();});
						$("#select_theme_preset").change(function(){top.location="?theme_presets="+$("#select_theme_preset").val();});
					});
				}
				</script>';
			$theme_bar_js .= '<style>
				div.select_theme_bar{background-color:#333; color:#eee; font-size:12px; text-align:center; border-bottom:solid 2px #111; padding:3px;}
				div.select_theme_bar a{color:#ee6; margin:0 20px;}
				div.select_theme_bar select{width:100px;}
			</style>';

			/* if (typeof jQuery != 'undefined') {
			  alert("jQuery library is loaded!");
			  }else{
			  alert("jQuery library is not found!");
			  } */

			return $theme_bar_js;
		}
	}

	public static function cleanUpDB()
	{
		// clean permalinks last times to reclean them asap passively with each cron call. 
		Config::optionSet('last_permalink_cleanUnlinked_' . intval(Permalink::ITEM_TYPE_LOCATION), 0);
		Config::optionSet('last_permalink_cleanUnlinked_' . intval(Permalink::ITEM_TYPE_CATEGORY), 0);
		Config::optionSet('last_permalink_cleanUnlinked_' . intval(Permalink::ITEM_TYPE_USER), 0);

		// clean update listed values to recount on next request
		Config::optionSet('last_updateListed', 0);
		Config::optionSet('last_updateFeatured', 0);

		// recalculate counts
		Config::optionSet('last_updateCounts', 0);

		// update listed here
		/* this is slow, leave it to cron 
		 * Ad::updateFeatured(true);
		  Ad::updateListed(true);
		 */
	}

	/**
	 * set new default location to cookie
	 * 
	 * @param int $location_id
	 * @param bool $check_if_required used when setting location in category
	 * @return boolean
	 */
	public static function setDefaultLocationCookie($location_id = 0, $check_if_required = false)
	{
		//echo '[setDefaultLocationCookie:' . $location_id . ']';
		// load current default location
		self::getDefaultLocation();

		$location_id = intval($location_id);

		// check if current location is not already set
		if (self::$default_location->id != $location_id)
		{
			if ($check_if_required)
			{
				// this is performed only if category with different location loaded
				if (self::$default_location && $location_id > 0)
				{
					// has set default then 
					// check if location is not child of current location 
					$location = Location::getLocationFromTree($location_id, Location::STATUS_ENABLED);
					Location::getParents($location, Location::STATUS_ENABLED);
					foreach ($location->arr_parents as $l)
					{
						if ($l->id == self::$default_location->id)
						{
							// it is child of current location then do not set it
							return false;
						}
					}
				}
			}

			if (Config::option('location_cookie'))
			{
				// store selected location, set cookie to 1 year
				$time = REQUEST_TIME + 365 * 24 * 60 * 60;
				Flash::setCookie('default_location', $location_id, $time);
			}


			// reset default location
			self::$default_location = Location::getLocationFromTree($location_id, Location::STATUS_ENABLED);
		}

		return true;
	}

	public static function getDefaultLocation()
	{

		if (!isset(self::$default_location))
		{
			// read cookie if not set then read config
			$location_id = Flash::getCookie('default_location');

			if (!Config::option('location_cookie') && strlen($location_id))
			{
				$location_id = '';
				// remove location cookie
				Flash::clearCookie('default_location');
			}

			if (!strlen($location_id))
			{
				// not stored in cookie then read config 
				$location_id = Config::option('default_location');
			}

			self::$default_location = Location::getLocationFromTree($location_id, Location::STATUS_ENABLED);
		}
		return self::$default_location;
	}

	public static function loadNews($get = false)
	{
		$url = 'http://classibase.com/simcls-update/news.php';
		if (Config::option('display_classibase_news'))
		{
			if (Config::option('display_classibase_news_last_time') < REQUEST_TIME - 5 * 24 * 3600)
			{
				if ($get)
				{
					// get data now and update cached values
					$data = Curl::get($url);
					if (strlen($data))
					{
						Config::optionSet('display_classibase_news_data', $data, false);
					}
					Config::optionSet('display_classibase_news_last_time', REQUEST_TIME);
				}
			}
			$data = Config::option('display_classibase_news_data');
			return $data;
		}

		return '';
	}

	public static function abbreviate($str, $len = 7)
	{
		if (strlen($str) > $len)
		{
			return '<abbr title="' . $str . '">' . Inflector::utf8Substr($str, 0, $len - 1) . '.</abbr>';
		}

		return $str;
	}

	/**
	 * delete image and data cahce SimpleCache files, delete ad count from AdCategoryCount, 
	 * remove unlined data from db and recalculate reatured and expired ads
	 * 
	 * @param boolean $silent
	 * @param string $type all|data|image
	 * @return boolean
	 */
	public static function clearAllCache($silent = true, $type = 'all')
	{
		Benchmark::cp('clearAllCache:START:' . View::escape($type));

		$return = true;

		if ($type === 'all' || $type === 'data')
		{
			// delete cache 
			SimpleCache::clearAll();

			// clean up db 
			Config::cleanUpDB();

			// clear cateogry ad counter
			AdCategoryCount::clearAll();


			// update json version 
			Config::optionSet('json_version', REQUEST_TIME);
		}


		if ($type === 'all' || $type === 'image')
		{
			// clear image cache 
			$return = Adpics::clearCache();
		}
		Benchmark::cp('clearAllCache:END');


		if (!$silent)
		{
			if ($return)
			{
				Validation::getInstance()->set_info(__('Cache cleared.'));
			}
			else
			{
				Validation::getInstance()->set_error(__('Error clearing cache. You can manually delete {name} using FTP program.', array('{name}' => 'user-content/uploads/cache/')));
			}
		}

		return $return;
	}

	public static function roundTime($seconds = 600)
	{
		return floor(REQUEST_TIME / $seconds) * $seconds;
	}

	/**
	 * perform regular updates without overloading server
	 * 
	 */
	public static function cronRun()
	{
		// get start time
		$t_start = microtime(true);

		// wait 5 second between each request 
		$wait = 5;
		$max_history = 30;
		$log_slow_time = 1;
		$finished_str = '';
		$cache_key = 'last_cronRun';
		$opt = Config::option($cache_key);
		if (!$opt)
		{
			$opt = array();
		}
		else
		{
			$opt = unserialize($opt);
		}

		if ($opt['last_run'] < REQUEST_TIME - $wait)
		{
			// ok to perform passive actions 
			// save current last time to prevent simultaneous calls

			$opt['last_run'] = REQUEST_TIME;


			// run processes
			$last_key = intval($opt['last_key']);
			$key_total = 16;
			$key = ($last_key + 1) % $key_total;
			$opt['last_key'] = $key;

			// save time and key to prevent consecutve calls 
			Config::optionSet($cache_key, serialize($opt));
			switch ($key)
			{
				case 0:
					// update listed ads by reving expired
					Ad::updateListed();
					$finished_str = 'Ad::updateListed()';
					break;
				case 1:
					// update featured ads by removing expired
					Ad::updateFeatured();
					$finished_str = 'Ad::updateFeatured()';
					break;
				case 2:
					// delete data cache
					SimpleCache::clearAll(false);
					$finished_str = 'SimpleCache::clearAll()';
					break;
				case 3:
					// delete expired ads
					Ad::trashExpired();
					$finished_str = 'Ad::trashExpired()';
					break;
				case 4:
					// load updates from external source
					Update::checkForUpdates();
					$finished_str = 'Update::checkForUpdates()';
					break;
				case 5:
					// load external news
					Config::loadNews(true);
					$finished_str = 'Config::loadNews(true)';
				case 6:
					// check if fulltext indext complete
					AdFulltext::status();
					$finished_str = 'AdFulltext::status()';
					break;
				case 7:
					// count ads by category adn location 
					AdCategoryCount::updateCounts();
					$finished_str = 'AdCategoryCount::updateCounts()';
					break;
				case 8:
					// delete items from trash
					Ad::deleteTrash();
					$finished_str = 'Ad::deleteTrash()';
					break;
				case 9:
					// delete expired ads
					Ad::trashOther();
					$finished_str = 'Ad::trashOther()';
					break;
				case 10:
					// send approved items notification email
					MailTemplate::sendApprovedSend();
					$finished_str = 'MailTemplate::sendApprovedSend()';
					break;
				case 11:
					// Clean unlinked ad from afr
					AdFieldRelation::cleanUnlinked('ad');
					$finished_str = 'AdFieldRelation::cleanUnlinked(ad)';
					break;
				case 12:
					// Clean unlinked af from afr
					AdFieldRelation::cleanUnlinked('af');
					$finished_str = 'AdFieldRelation::cleanUnlinked(af)';
					break;
				case 13:
					// Clean unlinked location from permalink
					Permalink::cleanUnlinked(Permalink::ITEM_TYPE_LOCATION);
					$finished_str = 'Permalink::cleanUnlinked(location)';
					break;
				case 14:
					// Clean unlinked category from permalink
					Permalink::cleanUnlinked(Permalink::ITEM_TYPE_CATEGORY);
					$finished_str = 'Permalink::cleanUnlinked(category)';
					break;
				case 15:
					// Clean unlinked user from permalink
					Permalink::cleanUnlinked(Permalink::ITEM_TYPE_USER);
					$finished_str = 'Permalink::cleanUnlinked(user)';
					break;
			}


			// get end time
			$t_end = microtime(true);
			$total_time = abs(round($t_end - $t_start, 2));
			if (($total_time >= $log_slow_time) && strlen($finished_str))
			{
				// record only if process took more than 1 second 
				// because some processes are not run depending on their last run times
				$finished_str = 't:' . REQUEST_TIME . ',dur:' . $total_time . ',call:' . $finished_str;

				// store latest 30 calls with times 
				if (!isset($opt['calls']))
				{
					$opt['calls'] = array();
				}
				array_unshift($opt['calls'], $finished_str);
				if (count($opt['calls']) > $max_history)
				{
					$opt['calls'] = array_slice($opt['calls'], 0, $max_history);
				}

				// save option 
				Config::optionSet($cache_key, serialize($opt));
			}
		}
	}

	/**
	 * check if we should init running cron, 
	 * if yes return cron run class to be checked with js and perform ajax call to cron
	 */
	public static function cronRunInitClass()
	{
		// wait 10 minutes between each cron job. 
		// also cron is run on each ad view count  
		// we do not want to make extra call to server for each cron. 
		// so call cron if it is not called for 10 minutes to keep counts up to date.
		$wait = 600;
		$key = 'last_cronRun';
		$return = '';
		$opt = Config::option($key);
		if (!$opt)
		{
			$opt = array();
		}
		else
		{
			$opt = unserialize($opt);
		}

		if ($opt['last_run'] < REQUEST_TIME - $wait)
		{
			$return = '_cron';
		}

		return $return;
	}

	public static function getCustomHeader($meta = null, $main_links = '', $default_title = null)
	{
		if (is_null($default_title))
		{
			$default_title = Config::option('site_title');
		}

		$site_title = $meta->title ? $meta->title : $default_title;


		$site_description = $meta->description ? $meta->description : Config::option('site_description');

		if ($meta->keywords)
		{
			$site_keywords = $meta->keywords;
		}
		else
		{
			// generate keywords using title and description 
			$site_keywords = TextTransform::str2keywords($site_description . ', ' . $site_title);
		}

		// main header elements 
		$return = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
				<title>' . View::escape($site_title) . '</title>';
		if ($site_description)
		{
			$return .= '<meta name="description" content="' . View::escape(TextTransform::excerpt($site_description, 155)) . '" />';
		}
		if ($site_keywords)
		{
			$return .= '<meta name="keywords" content="' . View::escape($site_keywords) . '" />';
		}

		// main css and js if defined display first
		$return .= $main_links;

		// general javascript vars
		$return .= '<script type="text/javascript">var BASE_URL = "' . Language::get_url() . '";var URL_PUBLIC = "' . URL_PUBLIC . '"; var URL_BASE=BASE_URL;var nounce="' . Config::nounce() . '"; var addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof cbOnload!="function"){cbOnload=func;}else{var oldonload=cbOnload;cbOnload=function(){oldonload();func();}}};</script>';
		$return .= self::faviconHtml();
		// RSS general links 
		$return .= Config::formatRssLink();
		$return .= Config::formatRssLink(array('type' => 'featured'));
		$return .= ($meta->header_other ? $meta->header_other : '');
		$return .= (isset($meta->css) ? implode('', $meta->css) : '');
		$return .= Config::option('custom_header');

		// if has custom css render save in static file and add link 
		$custom_css_url = Config::getCustomCssJsUrl('css');
		if ($custom_css_url)
		{
			$return .= '<link href="' . $custom_css_url . '" rel="stylesheet" type="text/css" />';
		}

		$manifet_url = Config::getManifestUrl();
		if ($manifet_url)
		{
			$return .= '<!-- PWA header -->';
			$return .= '<link rel="manifest" href="' . $manifet_url . '">';
			$return .= '<meta name="theme-color" content="' . Config::optionElseDefault('pwa_theme_color', '#eeeeee') . '">';
			$return .= '<!-- / PWA header -->';
		}

		return $return;
	}

	/**
	 * If has custom CSS then saves to static file and returns URL 
	 * /user-content/uploads/cache/custom.(js|css)?v={timestamp}
	 * In no custom CSS or failed to save then returns false;
	 * 
	 * @param string $type js|css
	 * @return string|bool|null  url | false: error saving file | null: nothing to save, no content
	 */
	public static function getCustomCssJsUrl($type)
	{
		$json_version = Config::option('json_version');
		if (!strlen($json_version))
		{
			$json_version = REQUEST_TIME;
			Config::optionSet('json_version', $json_version);
		}
		// by default no content 
		$content = '';

		// if we have custom css then save it as file and return url with custom timestamp
		switch ($type)
		{
			case 'js':
				$content = trim(Config::option('custom_js'));
				$filename = 'cache/custom.js';
				// timestamp when content was last saved
				$time = intval(Config::option('custom_css_js_time'));
				break;
			case 'css':
				$content = trim(Config::option('custom_css'));
				$filename = 'cache/custom.css';
				$time = intval(Config::option('custom_css_js_time'));
				break;
			case 'css_theme':
				// it will be called from Theme::applyCustomStyles
				// just in case prevent generatign in preview mode 
				if (!Theme::previewTheme())
				{
					// in preview mode css will be used inline so do not save static css for it 
					$content = trim(Theme::getCustomCSS());
				}
				// seperate css for each theme in case user switches back 
				$theme_id = '_' . Inflector::slugify(Theme::ifNullDefault() . '');
				$filename = 'cache/custom' . $theme_id . '.css';
				$time = intval(Theme::getUpdatedAt());
				break;
			case 'dataJson':
				$content = '_dataJson';
				$content_status = Location::STATUS_ENABLED;
				$current_lng = Inflector::slugify(I18n::getLocale());
				$filename = 'cache/dataJson.' . $current_lng . '.js';
				$time = $json_version;
				break;
			case 'dataJsonAll':
				$content = '_dataJson';
				$content_status = Location::STATUS_ALL;
				$current_lng = Inflector::slugify(I18n::getLocale());
				$filename = 'cache/dataJsonAll.' . $current_lng . '.js';
				$time = $json_version;
				break;
		}

		$return = null;
		if (strlen($content))
		{
			// file and location
			$upload_root = UPLOAD_ROOT . '/';

			// we assume file already saved and up to date
			$return = true;

			if (!file_exists($upload_root . $filename) || ($time > filemtime($upload_root . $filename)))
			{
				// save new contents to file 
				if ($content === '_dataJson')
				{
					// populate content only when needed
					$content = trim(Config::dataJson($content_status));
				}
				$return = FileDir::checkMakeFile($upload_root . $filename, $content);
			}

			if ($return)
			{
				// file is ok, return url to it
				$return = UPLOAD_URL . '/' . $filename . '?v=' . $time;
			}
		}
		return $return;
	}

	/**
	 * If enabled PWA then generate mainefst.json and return URL to it 
	 * /manifest.json?v={timestamp}
	 * 
	 * @return string|bool|null  url | false: error saving file | null: nothing to save, no content
	 */
	public static function getManifestUrl()
	{
		$return = null;
		$pwa_enable = Config::option('pwa_enable');
		if ($pwa_enable === 'enable')
		{
			/* {
			  "short_name": "Maps",
			  "name": "Google Maps",
			  "icons": [
			  {
			  "src": "/images/icons-192.png",
			  "type": "image/png",
			  "sizes": "192x192"
			  },
			  {
			  "src": "/images/icons-512.png",
			  "type": "image/png",
			  "sizes": "512x512"
			  }
			  ],
			  "start_url": "/maps/?source=pwa",
			  "background_color": "#3367D6",
			  "display": "standalone",
			  "scope": "/maps/",
			  "theme_color": "#3367D6"
			  } */

			$lng_default = Language::getDefault();
			$arr_content = array(
				'name'				 => Config::option('site_title', $lng_default),
				'short_name'		 => Config::option('site_title', $lng_default),
				'description'		 => Config::option('site_description', $lng_default),
				'start_url'			 => Config::optionElseDefault('pwa_start_url', './?utm_source=pwa&utm_medium=pwa&utm_campaign=pwa'),
				'background_color'	 => Config::optionElseDefault('pwa_theme_color', '#eeeeee'),
				'theme_color'		 => Config::optionElseDefault('pwa_theme_color', '#eeeeee'),
				'display'			 => Config::option('pwa_display', 'minimal-ui'),
				'scope'				 => '/',
			);


			$favicon_url = self::faviconUrl();
			if ($favicon_url)
			{
				$upload_folder = 'theme';
				$favicon = Config::option('favicon');
				$favicon_path = $upload_folder . '/' . $favicon;

				$arr_content['icons'] = array(
					array(
						'src'	 => Adpics::resizeImageCache($favicon_path, 192, 192, 1),
						'type'	 => 'image/png',
						'sizes'	 => '192x192'
					),
					array(
						'src'	 => $favicon_url,
						'type'	 => 'image/png',
						'sizes'	 => '512x512'
					)
				);
			}

			//convert to json data 
			$content = TextTransform::jsonEncode($arr_content);
			$filename = 'manifest.json';

			if (strlen($content))
			{
				// timestamp when content was last saved
				$pwa_time = Config::getPWAtime();

				// file and location
				$upload_root = FROG_ROOT . '/';

				// we assume file already saved and up to date
				$return = true;

				if (!file_exists($upload_root . $filename) || ($pwa_time > filemtime($upload_root . $filename)))
				{
					// save new css contents to file 
					$return = FileDir::checkMakeFile($upload_root . $filename, $content);
				}

				if ($return)
				{
					// file is ok, return url to it
					$return = URL_PUBLIC . $filename . '?v=' . $pwa_time;
				}
			}
		}
		return $return;
	}

	/**
	 * Get last PWA update time. 
	 * Reset time if it is older than $clear_days to clear old cache and create new 
	 * 
	 * @return integer
	 */
	public static function getPWAtime()
	{
		// time to clear old cache
		$clear_days = 30;
		$return = intval(Config::option('pwa_time'));

		if ($return < REQUEST_TIME - ($clear_days * 24 * 3600))
		{
			// reset pwa time to clear old cache 
			Config::optionSet('pwa_time', REQUEST_TIME);
			$return = intval(REQUEST_TIME);
		}

		return $return;
	}

	/**
	 * If enabled PWA then generate sw.js and return URL to it 
	 * /sw.js?v={timestamp}
	 * 
	 * @return string|bool|null  url | false: error saving file | null: nothing to save, no content
	 */
	public static function getSWUrl()
	{
		$return = null;
		$pwa_enable = Config::option('pwa_enable');
		if ($pwa_enable === 'enable')
		{
			//$lng_default = Language::getDefault();
			/* // delete old cache by adding month to cache name
			 * const d = new Date();
			 * const CACHE_NAME = '" . View::escape(DOMAIN) . "-pwa-" . View::escape(Config::getPWAtime()) . "-'+d.getMonth(); */
			//convert to json data 
			$content = "//This is the service worker with the Advanced caching
const CACHE_NAME = '" . View::escape(DOMAIN) . "-pwa-" . View::escape(Config::getPWAtime()) . "';
const precacheFiles = [
	/* Add an array of files to precache for your app */
	'" . View::escape(get_url()) . "'
];

// TODO: replace the following with the correct offline fallback page i.e.: const offlineFallbackPage = 'offline.html';
const offlineFallbackPage = '" . View::escape(get_url('?error=501')) . "';

const networkFirstPaths = [
	/* Add an array of regex of paths that should go network first */
	// Example: /\/api\/.*/
];

const avoidCachingPaths = [
	/* Add an array of regex of paths that shouldn't be cached */
	// Example: /\/api\/.*/
	/\/admin/,
	/\/login/,
	/\/post/
];

function pathComparer(requestUrl, pathRegEx) {
	return requestUrl.match(new RegExp(pathRegEx));
}

function comparePaths(requestUrl, pathsArray) {
	if (requestUrl) {
		for (let index = 0; index < pathsArray.length; index++) {
			const pathRegEx = pathsArray[index];
			if (pathComparer(requestUrl, pathRegEx)) {
				return true;
			}
		}
	}

	return false;
}

self.addEventListener('install', function (event) {
	console.log('[PWA Builder] Install Event processing');

	console.log('[PWA Builder] Skip waiting on install');
	self.skipWaiting();

	event.waitUntil(
		caches.open(CACHE_NAME).then(function (cache) {
			console.log('[PWA Builder] Caching pages during install');

			return cache.addAll(precacheFiles).then(function () {				
				return cache.add(offlineFallbackPage);
			});
		}));
});

// Allow sw to control of current page
self.addEventListener('activate', function (event) {
	console.log('[PWA Builder] Claiming clients for current page');
	/*event.waitUntil(self.clients.claim());*/

	event.waitUntil(caches.keys().then(function (keyList) {
			return Promise.all(keyList.map(function (key) {
					if (key !== CACHE_NAME) {
						console.log('pwa old cache removed', key);
						return caches.delete(key);
					}
				})).then(function () {
				return self.clients.claim();
			});
		}));
	//return self.clients.claim();
});

// If any fetch fails, it will look for the request in the cache and serve it from there first
self.addEventListener('fetch', function (event) {
	/*if (event.request.method !== 'GET') {
	return;
	}*/

	if (new URL(event.request.url).origin !== location.origin) {
		console.log('pwa: URL origin is different than location origin:' + event.request.url);
		return;
	}

	//networkFirstFetch(event);
	// serve static content from cache, js serve from network to update self when needed
	const destination = event.request.destination;
	switch (destination) {
	case 'style':	
	case 'image':
	case 'font':
		cacheFirstFetch(event);
		break;
		// All `XMLHttpRequest` or `fetch()` calls where
		// `Request.destination` is the empty string default value
	case 'script':
	default:
		networkFirstFetch(event);
	}

	/*if (comparePaths(event.request.url, networkFirstPaths)) {
	networkFirstFetch(event);
	} else {
	cacheFirstFetch(event);
	}*/
});

function cacheFirstFetch(event) {
	event.respondWith(
		fromCache(event.request).then(
			function (response) {
			// The response was found in the cache so we responde with it and update the entry

			// This is where we call the server to get the newest version of the
			// file to use the next time we show view
			event.waitUntil(
				fetch(event.request).then(function (response) {
					return updateCache(event.request, response);
				}));

			return response;
		},
			function () {
			// The response was not found in the cache so we look for it on the server
			return fetch(event.request)
			.then(function (response) {
				// If request was success, add or update it in the cache
				event.waitUntil(updateCache(event.request, response.clone()));

				return response;
			})
			.catch(function (error) {
				// The following validates that the request was for a navigation to a new document
				if (event.request.destination !== 'document' || event.request.mode !== 'navigate') {
					return;
				}

				console.log('[PWA Builder] Network request failed and no cache.' + error);
				// Use the precached offline page as fallback
				return caches.open(CACHE_NAME).then(function (cache) {
					cache.match(offlineFallbackPage);
				});
			});
		}));
}

function networkFirstFetch(event) {
	event.respondWith(
		fetch(event.request)
		.then(function (response) {
			// If request was success, add or update it in the cache
			event.waitUntil(updateCache(event.request, response.clone()));
			return response;
		})
		.catch(function (error) {
			console.log('[PWA Builder] Network request Failed. Serving content from cache: ' + error);
			return fromCache(event.request).catch(function (error2) {
				console.log('[PWA Builder]cache failed, showing offline page: ' + error2);
				return fromCache(offlineFallbackPage);
			});
		}));
}

function fromCache(request) {
	// Check to see if you have it in the cache
	// Return response
	// If not in the cache, then return error page
	return caches.open(CACHE_NAME).then(function (cache) {
		return cache.match(request).then(function (matching) {
			if (!matching /* || matching.status === 404*/) {
				return Promise.reject('no-match');
			}
			const destination = request.destination;
			console.log('[PWA Builder]fromCache:'+destination);
			return matching;
		});
	});
}

function updateCache(request, response) {
	if (request.method === 'GET') {
		// cache only get requests
		if (!comparePaths(request.url, avoidCachingPaths)) {
			return caches.open(CACHE_NAME).then(function (cache) {
				return cache.put(request, response);
			});
		}
	}
	return Promise.resolve();
}";
			$filename = 'sw.js';

			if (strlen($content))
			{
				// timestamp when content was last saved
				$pwa_time = Config::getPWAtime();

				// file and location
				$upload_root = FROG_ROOT . '/';

				// we assume file already saved and up to date
				$return = true;

				if (!file_exists($upload_root . $filename) || ($pwa_time > filemtime($upload_root . $filename)))
				{
					// save new js contents to file 
					$return = FileDir::checkMakeFile($upload_root . $filename, $content);
				}

				if ($return)
				{
					// file is ok, return url to it
					$return = URL_PUBLIC . $filename . '?v=' . $pwa_time;
				}
			}
		}
		elseif ($pwa_enable === 'disable')
		{
			// delete manifest and sw.js
			$arr_filename = array('sw.js', 'manifest.json');
			$upload_root = FROG_ROOT . '/';
			foreach ($arr_filename as $f)
			{
				@unlink($upload_root . $f);
			}

			// unregister pwa send just command string to js 
			$return = 'disable';
		}
		return $return;
	}

	/**
	 * format given body classes and return 
	 *  
	 * @param type $meta
	 * @return string
	 */
	public static function getBodyClass($meta, $vars = array(), $additiolan_classes = '')
	{
		$return = $additiolan_classes;
		$return .= ' ' . (isset($meta->body_class) ? ' ' . implode(' ', $meta->body_class) : '');
		$return .= ' ' . (isset($vars['page_type']) ? 'page_type_' . $vars['page_type'] : '');
		$return .= ' lng_' . I18n::getLocale();
		// check if we need to run cron 
		$return .= ' ' . self::cronRunInitClass();

		$arr_return = explode(' ', $return);
		$arr_return = array_unique($arr_return);

		$return = trim(implode(' ', $arr_return));
		return str_replace('  ', ' ', $return);
	}

	/**
	 * Return js code to show if js or cookie disabled
	 * 
	 * @return type
	 */
	public static function cookieJsWarning()
	{
		return '<noscript id="noscript"><div class="msg-error">' . __('Javascript is required to validate form input. Please enable javascript in your browser.') . '</div></noscript>
			<script>
				addLoadEvent(function(){
					if (typeof checkCookie != "undefined" && typeof jQuery != "undefined" && !checkCookie()) {
						$("noscript#noscript").after(\'<div class="msg-error">' . View::escape(__('Cookies are required to validate form input. Please enable cookies in your browser.')) . '</div>\');
					}
				});				
			</script>';
	}

	static public function formatRssLink($settings = array())
	{

		$settings_default = array(
			'type'			 => null, // latest, featured
			'location'		 => null,
			'category'		 => null,
			'user'			 => null,
			'use_search'	 => false,
			'return_format'	 => 'header' // header, frontend
		);

		$settings = array_merge($settings_default, $settings);

		// do it manually for cmpatability with netbeans
		$type = $settings['type'];
		$location = $settings['location'];
		$category = $settings['category'];
		$user = $settings['user'];
		$return_format = $settings['return_format'];

		$title_arr = array();
		//$url_origin = Location::urlOrigin($location, $category, $user);
		if ($settings['use_search'])
		{
			// url with search variables 
			// remove some parts from rss 		
			$overwrite = array(
				'page'			 => '',
				'freshness'		 => '',
				'location_id'	 => $settings['location']->id,
				'category_id'	 => $settings['category']->id,
				'user_id'		 => $settings['user']->id
			);
			$url_origin = Permalink::vars2url($_GET, $overwrite, true);
		}
		else
		{
			// url without serach variables 
			$url_origin = Location::urlOrigin($location, $category, $user);
		}


		switch ($type)
		{
			case 'featured':
				$title = __('RSS featured ads');
				$url = Language::get_url('rss/featured/' . $url_origin);
				break;
			default:
				$title = __('RSS latest ads');
				if ($url_origin)
				{
					$url = Language::get_url('rss/latest/' . $url_origin);
				}
				else
				{
					$url = Language::get_url('rss/');
				}
		}


		if ($user)
		{
			$title_arr[] = $user->name;
		}
		if ($location)
		{
			$title_arr[] = Location::getName($location);
		}
		if ($category)
		{
			$title_arr[] = Category::getName($category);
		}

		$title_arr_str = implode(', ', $title_arr);

		if ($title_arr_str)
		{
			$title .= ': ' . $title_arr_str;
		}


		switch ($return_format)
		{
			case 'frontend':
				$return = '<a target="_blank" href="' . $url . '">' . View::escape($title) . '</a>';
				break;
			case 'header':
			default:
				$return = '<link rel="alternate" type="application/rss+xml" title="' . View::escape($title) . '" href="' . $url . '" />';
				break;
		}

		return $return;
	}

	public static function getCustomFooter($meta = null)
	{
		$return = Config::option('custom_footer');

		// if has custom js render save in static file and add link 
		$custom_js_url = Config::getCustomCssJsUrl('js');
		if ($custom_js_url)
		{
			$return .= '<script src="' . $custom_js_url . '" type="text/javascript"></script>';
		}

		// additional js files
		$return .= (isset($meta->javascript) ? implode('', $meta->javascript) : '');


		$pwa_footer = '';
		$sw_url = self::getSWUrl();
		if ($sw_url)
		{
			$pwa_footer .= '/* PWA footer */ '
					. 'var pwa_sw = {"url":"' . $sw_url . '"}; ';
		}

		// at the end call cbOnload
		$return .= '<script type="text/javascript">'
				. $pwa_footer
				. 'if(typeof cbOnload=="function"){cbOnload();}</script>';
		return $return;
	}

	public static function faviconUpload($field = 'upload_favicon')
	{
		$error = (!isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];
		if ($error != 4)
		{
			$upload_folder = 'theme';
			// upload logo and crop to defined size
			$img = Adpics::upload($field, $upload_folder);
			if ($img)
			{
				// resize dealer logo to exact size
				$img_path = $upload_path = UPLOAD_ROOT . '/' . $upload_folder . '/' . $img;
				$width = 512;
				$height = 512;

				$old_favicon = Config::option('favicon');


				$resized = SimpleImage::fromFile($img_path)->crop($width, $height)->save($img_path);
				if ($resized)
				{
					Config::optionSet('favicon', $img);
					// delete old favicon
					if (strcmp($old_favicon, $img) != 0)
					{
						@unlink(UPLOAD_ROOT . '/' . $upload_folder . '/' . $old_favicon);
					}
				}
				else
				{
					// delete image 
					@unlink($img_path);

					// display error 
					Validation::getInstance()->set_error(__('Error resizing image'));
					return false;
				}
			}
			else
			{
				Validation::getInstance()->set_error(Adpics::getUploadErrors());
				return false;
			}
		}

		return true;
	}

	public static function faviconUrl()
	{
		$upload_folder = 'theme';
		$favicon = Config::option('favicon');
		$favicon_file = UPLOAD_ROOT . '/' . $upload_folder . '/' . $favicon;
		if (strlen($favicon) && is_file($favicon_file))
		{
			return UPLOAD_URL . '/' . $upload_folder . '/' . $favicon;
		}
		return false;
	}

	public static function faviconHtml()
	{
		$return = '';
		$favicon_url = self::faviconUrl();
		if ($favicon_url)
		{
			$upload_folder = 'theme';
			$favicon = Config::option('favicon');
			$favicon_path = $upload_folder . '/' . $favicon;

			$return = '<link rel="shortcut icon" href="' . Adpics::resizeImageCache($favicon_path, 32, 32, 1) . '" />'
					. '<link rel="apple-touch-icon" sizes="192×192" href="' . Adpics::resizeImageCache($favicon_path, 192, 192, 1) . '" />'
					. '<link rel="apple-touch-icon" sizes="512×512" href="' . $favicon_url . '" />';
		}

		return $return;
	}

	public static function arr2js($arr = array())
	{
		$islist = is_array($arr) && ( empty($arr) || array_keys($arr) === range(0, count($arr) - 1) );
		$return = array();

		if ($islist)
		{
			foreach ($arr as $k => $v)
			{
				if (is_array($v))
				{
					$return[] = self::arr2js($v);
				}
				else
				{
					// replace new line and tabs and add ad one line string 
					$return[] = '"' . View::escape(self::js_remove_spaces($v)) . '"';
				}
			}
			return '[' . implode(',', $return) . ']';
		}
		else
		{
			foreach ($arr as $k => $v)
			{
				if (is_array($v))
				{
					$return[] = '"' . View::escape($k) . '":' . self::arr2js($v);
				}
				else
				{
					// replace new line and tabs and add ad one line string 
					$return[] = '"' . View::escape($k) . '":"' . View::escape(self::js_remove_spaces($v)) . '"';
				}
			}
			return '{' . implode(',', $return) . '}';
		}
	}

	public static function arr2NameVal($arr = array(), $filter = null, $add_braces = false)
	{
		$return = array();

		/**
		  [$_GET]Array
		  (
		  [all/s_1-10000-20000/] =>
		  [s] => 1
		  [cf] => Array
		  (
		  [1] => Array
		  (
		  [from] => 10000
		  [to] => 20000
		  )

		  )

		  )
		 * 	 */
		foreach ($arr as $k => $v)
		{
			if ($filter === $k || is_null($filter))
			{


				if ($add_braces)
				{
					$k = '[' . $k . ']';
				}

				if (is_array($v))
				{
					$r = self::arr2NameVal($v, null, true);
					foreach ($r as $kk => $vv)
					{
						$return[$k . $kk] = $vv;
					}
				}
				else
				{
					$return[$k] = $v;
				}
			}
		}

		return $return;
	}

	/**
	 * Remove space, tab, newline from string 
	 * use with View::escape(Config::js_remove_spaces($str)) to make string js safe
	 * 
	 * @param string $str
	 * @return string
	 */
	public static function js_remove_spaces($str)
	{
		return trim(preg_replace('/[\n\t\r]+\s+/', ' ', $str));
	}

	/**
	 * Get user defined extend_ad_days as sanetized array
	 * skip values lower than 0 and bigger than 1000 years
	 * 
	 * @return array
	 */
	public static function getExtendAdDays()
	{
		$return = array();
		$extend_ad_days = explode(',', trim(Config::option('extend_ad_days')));
		foreach ($extend_ad_days as $day)
		{
			$day = intval($day);
			// maximum 1000 years
			if ($day > 0 && $day < 366000)
			{
				$return[] = $day;
			}
		}

		return $return;
	}

	public static function markerRequired($use_marker = true)
	{
		if ($use_marker)
		{
			return ' <span class="marker">*</span>';
		}
		return '';
	}

	/**
	 * Check if databse version is lover than $requested_version
	 * 
	 * @param string $requested_version
	 * @return boolean
	 */
	public static function isDBVersionLowerThan($requested_version)
	{
		$cur_version = Config::option('site_version');
		// if cur version < requested version then return true
		return (version_compare($cur_version, $requested_version) == -1);
	}

	/**
	 * force to use http / https protocol
	 */
	public static function checkProtocol()
	{
		$website_protocol = Config::option('website_protocol');
		if ($website_protocol)
		{
			// set to use cirtain protocol
			$_force = $website_protocol . '://';
			$_current = self::getUrlProtocol();
			if ($_current !== $_force)
			{
				// redirect to correct protocol				
				$redir_url = Language::get_url(Language::getCurrentUrl(true));
				$redir_url = str_replace($_current, $_force, $redir_url);
				redirect($redir_url, false);
			}
		}
	}

	/**
	 * check current request and return protocol https:// or http://
	 * @return string
	 */
	public static function getUrlProtocol()
	{
		if (defined('URL_PROTOCOL'))
		{
			return URL_PROTOCOL;
		}

		if (self::$_url_protocol === null)
		{
			self::$_url_protocol = 'http://';
			if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443)
			{
				self::$_url_protocol = 'https://';
			}
		}

		return self::$_url_protocol;
	}

	/**
	 * perform actions after loading files and before sending to controller
	 */
	public static function init()
	{
		Config::checkProtocol();
	}

	public static function date($time = null)
	{
		if (is_null($time))
		{
			$time = REQUEST_TIME;
		}
		return date(Config::optionElseDefault('date_format', 'd/m/Y'), $time);
	}

	public static function dateTime($time = null)
	{
		if (is_null($time))
		{
			$time = REQUEST_TIME;
		}
		return date(Config::optionElseDefault('date_time_format', 'd/m/Y H:i:s'), $time);
	}

	/**
	 * generated URL for loading JSON data of categories, locations, custom fields
	 * create static js file using data 
	 * 
	 * @param type $status
	 * @return type
	 */
	public static function urlJson($status = null)
	{
		if ($status === Location::STATUS_ALL)
		{
			return Config::getCustomCssJsUrl('dataJsonAll');
		}
		return Config::getCustomCssJsUrl('dataJson');
	}

	/**
	 * perform regular cron jobs, use it to precess queued actions 
	 */
	public static function cron()
	{
		// perform cron jobs
		AdRelated::generateRelatedLazy();

		// run other passive updates 
		Config::cronRun();
	}

	public static function _sidemenu($arr_lng = null)
	{
		$menu = array();
		if (AuthUser::isLoggedIn(false))
		{
			$menu[] = self::_menuItem('<i class="fa fa-fw fa-user" aria-hidden="true"></i> ' . AuthUser::$user->email, 'admin/', 'user');
			$menu[] = self::_menuItem('<i class="fa fa-fw fa-sign-out" aria-hidden="true"></i> ' . __('Log out'), 'login/logout/', 'user');
		}
		else
		{
			$menu[] = self::_menuItem('<i class="fa fa-fw fa-user" aria-hidden="true"></i> ' . __('Log in / Register'), 'admin/', '');
		}

		if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
		{

			$menu[] = self::_menuItem(__('My items'), 'admin/itemsmy/', 'moderator', array(
						self::_menuItem(__('View'), 'admin/itemsmy/', 'moderator'),
						self::_menuItem(__('Post ad'), 'post/item/', 'moderator'),
						self::_menuItem(__('Edit account'), 'admin/editAccount/', 'moderator')
			));

			$menu[] = self::_menuItem(__('Manage'), 'admin/items/', 'moderator', array(
						self::_menuItem(__('Ads'), 'admin/items/', 'moderator'),
						self::_menuItem(Ad::statusName(Ad::STATUS_PENDING_APPROVAL), 'admin/itemsPending/', 'moderator'),
						self::_menuItem(__('Duplicates'), 'admin/duplicates/', 'moderator'),
						self::_menuItem(__('Abuse reports'), 'admin/itemAbuse/', 'moderator'),
						self::_menuItem(__('Categories'), 'admin/categories/', 'admin'),
						self::_menuItem(__('Locations'), 'admin/locations/', 'admin'),
						self::_menuItem(__('Custom fields'), 'admin/itemfield/', 'admin'),
						self::_menuItem(__('Category field groups'), 'admin/categoryFieldGroup/', 'admin'),
						self::_menuItem(__('Category custom fields'), 'admin/categoryfield/', 'admin'),
						self::_menuItem(__('Pages'), 'admin/pages/', 'admin'),
						self::_menuItem(__('Paid options'), 'admin/paymentPrice/', 'admin')
			));


			$menu[] = self::_menuItem(__('Settings'), (AuthUser::hasPermission(User::PERMISSION_ADMIN) ? 'admin/settings/' : 'admin/settingsSpam/'), 'moderator', array(
						self::_menuItem(__('General'), 'admin/settings/', 'admin'),
						self::_menuItem(__('Ads'), 'admin/settingsAds/', 'admin'),
						self::_menuItem(__('Account'), 'admin/settingsAccount/', 'admin'),
						self::_menuItem(__('Mail server'), 'admin/settingsMail/', 'admin'),
						self::_menuItem(__('Payment'), 'admin/settingsPayment/', 'admin'),
						self::_menuItem(__('Header / footer'), 'admin/settingsHeaderFooter/', 'admin'),
						self::_menuItem(__('PWA'), 'admin/settingsPWA/', 'admin'),
						self::_menuItem(__('Email templates'), 'admin/emailTemplate/', 'admin'),
						self::_menuItem(__('Language'), 'admin/language/', 'admin'),
						self::_menuItem(__('Currency'), 'admin/settingsCurrency/', 'admin'),
						self::_menuItem(__('Spam filter'), 'admin/settingsSpam/', 'moderator'),
						self::_menuItem(__('Blocked IPs'), 'admin/ipBlock/', 'moderator'),
						self::_menuItem(__('Logs'), 'admin/logs/', 'admin'),
			));

			$menu[] = self::_menuItem(__('Appearance'), 'admin/themes/', 'admin', array(
						self::_menuItem(__('Themes'), 'admin/themes/', 'admin'),
						self::_menuItem(__('Customize'), 'admin/themesCustomize/', 'admin'),
						self::_menuItem(__('Widgets'), 'admin/widgets/', 'admin'),
			));

			$menu[] = self::_menuItem(__('Users'), 'admin/users/', 'moderator', array(
						self::_menuItem(__('View'), 'admin/users/', 'moderator'),
						self::_menuItem(__('Add'), 'admin/users/edit/', 'moderator'),
						self::_menuItem(__('Pending verification'), 'admin/users/notverified/', 'moderator'),
						self::_menuItem(__('Pending approval'), 'admin/users/notenabled/', 'moderator'),
						self::_menuItem(__('Pending upgrade to dealer'), 'admin/users/upgradetodealer/', 'moderator')
			));


			$menu[] = self::_menuItem(__('Tools'), 'admin/maintenance/', 'admin', array(
						self::_menuItem(__('Maintenance'), 'admin/maintenance/', 'admin'),
						self::_menuItem(__('Import data'), 'admin/import/', 'admin'),
						//self::_menuItem(__('Unserialize tool'), 'admin/unserializeTool/', 'admin'),
						self::_menuItem(__('Check for script updates'), 'admin/updateCheck/', 'admin'),
						self::_menuItem(__('Clear cache'), 'admin/clearCache/', 'admin'),
			));

			$menu[] = self::_menuItem(__('Reports'), 'admin/itemsHit/', 'moderator', array(
						self::_menuItem(__('Most viewed items'), 'admin/itemsHit/', 'moderator'),
						self::_menuItem(__('Payment history'), 'admin/paymentHistory/', 'moderator'),
			));
		}
		else
		{
			// generate user menu 
			$menu[] = self::_menuItem('<i class="fa fa-fw fa-file-text-o" aria-hidden="true"></i> ' . __('My items'), 'admin/itemsmy/', '');
			$menu[] = self::_menuItem('<i class="fa fa-fw fa-plus" aria-hidden="true"></i> ' . __('Post ad'), 'post/item/', '');
			$menu[] = self::_menuItem('<i class="fa fa-fw fa-edit" aria-hidden="true"></i> ' . __('Edit account'), 'admin/editAccount/', 'user');
		}


		// append language to menu for all users 
		if (is_null($arr_lng))
		{
			//$arr_lng = Language::htmlLanguageBuild('admin/');
			$arr_lng = Language::htmlLanguageBuild();
		}

		if ($arr_lng)
		{
			$menu_sub = array();
			$lng_sel = '';
			foreach ($arr_lng as $lngobj)
			{

				if ($lngobj->sel)
				{
					$lng_sel = $lngobj;
				}
				else
				{
					$menu_sub[] = self::_menuItem($lngobj->title, $lngobj->url, '');
				}
			}
			$menu[] = self::_menuItem($lng_sel->title, '#', '', $menu_sub);
		}

		return $menu;
	}

	private static function _menuItem($name, $url, $permission, $submenu = array())
	{
		$obj = new stdClass();
		$obj->name = $name;
		$obj->url = $url;
		$obj->permission = $permission;
		$obj->submenu = $submenu;

		return $obj;
	}

	public static function renderMenu($settings = array())
	{
		$settings_default = array(
			'pattern_menu'		 => '<ul>{menu}</ul>',
			'pattern_submenu'	 => '<ul>{menu}</ul>',
			'pattern_item'		 => '<li{if_class}><a href="{url}">{text}</a>{submenu}</li>',
			'menu_selected'		 => '',
			'menu_items'		 => array(),
		);

		$settings = array_merge($settings_default, $settings);


		// easy of use
		$menuItems = $settings['menu_items'];
		$menu_selected = $settings['menu_selected'];

		// preset submenu settings
		$settings_sub = $settings;
		$settings_sub['pattern_menu'] = $settings_sub['pattern_submenu'];




		$return = '';
		if ($menuItems)
		{
			foreach ($menuItems as $menu)
			{
				$settings_sub['menu_items'] = $menu->submenu;

				// chek permission
				if ($menu->permission === '' || AuthUser::hasPermission($menu->permission))
				{
					$sub = self::renderMenu($settings_sub);


					$class = ($menu->url == $menu_selected ? ' class="active"' : '');
					if (!$class && strpos($sub, ' class="active"') !== false)
					{
						$class = ' class="open"';
					}


					if ($menu->url)
					{
						// check if it is formatted url for language then pass as is 
						if (strpos($menu->url, BASE_URL) !== false)
						{
							$url = $menu->url;
						}
						else
						{
							$url = Language::get_url($menu->url);
						}
					}

					$arr_search = array(
						'{if_class}' => $class,
						'{url}'		 => $url,
						'{text}'	 => $menu->name,
						'{submenu}'	 => $sub
					);

					$return .= str_replace(array_keys($arr_search), array_values($arr_search), $settings['pattern_item']);
				}
			}

			if ($return)
			{
				$return = str_replace('{menu}', $return, $settings['pattern_menu']);
			}
		}

		return $return;
	}

	static public function validatePhone($str)
	{
		$phone_regex = Config::option('phone_regex');
		if (strlen($phone_regex))
		{
			return (!preg_match("/" . $phone_regex . "/", $str)) ? FALSE : TRUE;
		}

		/// no regex then return true 
		return true;
	}

	/**
	 * generate json data to store as js file and serve for dynamic category, location, custom fields. 
	 * 
	 * @param type $status
	 * @param type $version
	 */
	static public function dataJson($status = null)
	{
		/**
		 * p: parent_id
		 * i: id
		 * n:name
		 * v:val
		 * t:type
		 * 
		 * 
		 */
		// load all json data 
		// categories, locations, custom fields for current language 
		// store in cache 
		if (is_null($status))
		{
			$status = Location::STATUS_ENABLED;
		}



		//  categories 
		$all_categories = Category::getAllCategoryNamesTree($status);
		$cat_arr = array();
		foreach ($all_categories as $parent_id => $categories)
		{
			foreach ($categories as $category)
			{
				$cat_arr['p' . $parent_id]['i' . $category->id] = $category->CategoryDescription->name;
			}
		}

		// locations
		$all_locations = Location::getAllLocationNamesTree($status);
		$loc_arr = array();
		foreach ($all_locations as $parent_id => $locations)
		{
			foreach ($locations as $location)
			{
				$loc_arr['p' . $parent_id]['i' . $location->id] = $location->LocationDescription->name;
			}
		}

		// custom fields 
		$catfields = CategoryFieldRelation::findAllFrom('CategoryFieldRelation', "1=1 ORDER BY location_id,category_id,pos");
		AdField::appendAdField($catfields, 'adfield_id');
		$catfields_grouped = CategoryFieldRelation::groupResults($catfields);

		//print_r($catfields_grouped);exit;


		$cf_arr = array();
		$af_arr = array();
		foreach ($catfields_grouped as $l_c => $cfs)
		{
			foreach ($cfs as $cf)
			{
				$cf_arr[$l_c]['i' . $cf->adfield_id] = $cf->is_search;
				if (!isset($af_arr['i' . $cf->adfield_id]) && $cf->adfield_id > 0)
				{
					$af = $cf->AdField;
					$af_arr['i' . $cf->adfield_id] = array(
						'n'	 => $af->AdFieldDescription->name,
						't'	 => $af->type
					);
					// append currency
					if (strcmp($af->type, AdField::TYPE_PRICE) == 0)
					{
						// define currency as val
						$af_arr['i' . $cf->adfield_id]['v'] = Config::option('currency');
					}
					// append unit 
					if (strlen($af->AdFieldDescription->val))
					{
						$af_arr['i' . $cf->adfield_id]['v'] = $af->AdFieldDescription->val;
					}
					// append value for dropdown, radio, checkbox fields 
					if ($af->AdFieldValue)
					{
						foreach ($af->AdFieldValue as $afv)
						{
							//$af_arr['id_' . $cf->adfield_id]['afv_' . $afv->id] = $afv->AdFieldValueDescription->name;
							$af_arr['i' . $cf->adfield_id]['afv']['i' . $afv->id] = $afv->AdFieldValueDescription->name;
						}
					}
					// append help text 
					$help_text = AdField::fieldHelpText($af->type);
					if ($help_text)
					{
						$af_arr['i' . $cf->adfield_id]['h'] = $help_text;
					}
				}
			}
		}

		// paid postings and featured listing prices 
		$payment_prices = PaymentPrice::findAllFrom('PaymentPrice', "1=1 ORDER BY location_id,category_id");
		//print_r($payment_prices);
		$pm_arr = array();
		foreach ($payment_prices as $p)
		{
			$pm_arr[$p->location_id . '_' . $p->category_id] = array(
				'f'	 => $p->price_featured,
				'p'	 => $p->price_post
			);
		}

		$return = array(
			'category'	 => $cat_arr,
			'location'	 => $loc_arr,
			'cf'		 => $cf_arr,
			'af'		 => $af_arr,
			'pm'		 => $pm_arr
		);

		//echo Benchmark::report();
		//print_r($return);
		//exit;
		//header_cache(3600 * 24 * 30);
		return Config::arr2js($return);
	}

}
