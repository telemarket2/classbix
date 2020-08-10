<?php

/**
 * Description of Theme
 *
 * @author v
 */
class Theme
{

	static private $_info = array();
	static private $_themes;
	static private $_previewTheme;
	static private $_current_theme;
	public $themeConfig;

	const VERSION_SUPPORT_JQDROPDOWN = '2';
	const VERSION_SUPPORT_HTML5INPUT = '2';

	/* static public $info = array(
	  'name' => '-',
	  'version' => '-',
	  'description' => '-',
	  'author_name' => '-',
	  'author_url' => '-',
	  'locations' => array()
	  ); */

	/**
	 *
	 * @param string $theme_id
	 * @return Theme 
	 */
	public static function getTheme($theme_id = null, $force = false)
	{
		$theme_id = self::ifNullDefault($theme_id);

		if (!isset(self::$_info[$theme_id]) || $force)
		{
			$theme = new Theme();
			$theme->id = $theme_id;

			// read info 
			$inf_file = $theme->themeDir() . 'info.php';
			if (file_exists($inf_file))
			{
				// $info is defined in theme info.php file
				include $inf_file;
				if (is_array($info))
				{
					$theme->info = $info;
				}
			}

			self::$_info[$theme_id] = $theme;
		}
		return self::$_info[$theme_id];
	}
	
	/**
	* get current theme version 
	*/
	public static function getVersion()
	{
		$theme = self::getTheme();
		return $theme->info['version'];
	}

	public static function setDemoTheme()
	{
		if (DEMO && !Theme::previewTheme())
		{
			$theme_set = false;
			$theme_preset_set = false;
			// set theme from get
			if (isset($_GET['theme']))
			{
				$theme_id = trim($_GET['theme']);
				if (strlen($theme_id) && self::hasTheme($theme_id))
				{
					// theme exists and loaded use it 
					Flash::setCookie('theme', $theme_id, 0);
					Flash::clearCookie('theme_presets');
					self::$_current_theme = $theme_id;
					$theme_set = true;
					$theme_preset_set = true;
				}
			}

			// not get then set theme from cookie 
			if (!$theme_set)
			{
				$theme_id = Flash::getCookie('theme');
				if (strlen($theme_id) && self::hasTheme($theme_id))
				{
					// theme exists and loaded use it 
					self::$_current_theme = $theme_id;
					$theme_set = true;
				}
				else
				{
					// no such theme delete cookie value 
					Flash::clearCookie('theme');
					Flash::clearCookie('theme_presets');
				}
			}

			// set preset if exists
			$theme = Theme::getTheme();
			$theme->optionLoadAll();

			if ($theme->id !== Config::option('theme'))
			{
				// this theme is not current theme
				// load sidebars for this theme fro backups
				Widget::sidebarWidgetsSetDemo($theme->themeConfig->sidebar_widgets);
			}

			//print_r($theme->themeConfig);

			$presets = $theme->info['customize']['theme_presets']['fields']['theme_presets']['value'];
			// set from get 
			if (!$theme_preset_set && isset($_GET['theme_presets']))
			{
				$theme_preset = trim($_GET['theme_presets']);
				if (isset($presets[$theme_preset]))
				{
					$theme->themeConfig->options['theme_presets'] = $theme_preset;
					Flash::setCookie('theme_presets', $theme_preset, 0);
					$theme_preset_set = true;
				}
			}
			// if not set with get then set from cookie 
			if (!$theme_preset_set)
			{
				$theme_preset = Flash::getCookie('theme_presets');
				if (isset($presets[$theme_preset]))
				{
					$theme->themeConfig->options['theme_presets'] = $theme_preset;
					$theme_preset_set = true;
				}
				else
				{
					Flash::clearCookie('theme_presets');
				}
			}
		}
	}

	public static function setAutoloader()
	{
		self::setDemoTheme();
		$theme = self::getTheme();

		AutoLoader::addFolder(array($theme->themeDir() . DIRECTORY_SEPARATOR . 'models',
			$theme->themeDir() . DIRECTORY_SEPARATOR . 'controllers'));

		// also execute theme index.php if existst
		$index_file = $theme->themeDir() . DIRECTORY_SEPARATOR . 'index.php';
		if (file_exists($index_file))
		{
			include($index_file);
		}
	}

	/**
	 * check if given theme has info file, if yes then valid theme 
	 * 
	 * @param string $theme_id
	 * @return bool
	 */
	public static function hasTheme($theme_id)
	{
		$theme = self::getTheme($theme_id);
		return isset($theme->info);
	}

	function optionLoadAll()
	{
		// load all options for this theme 
		if (!isset($this->themeConfig))
		{
			// load from db 
			$this->themeConfig = Config::option($this->optionsKey());

			if ($this->themeConfig)
			{
				$this->themeConfig = unserialize($this->themeConfig);
			}
			else
			{
				$this->themeConfig = new stdClass();
				$this->themeConfig->id = $this->id;
			}
		}
	}

	/**
	 * 
	 * @param string $name 
	 * @param false|string $lng
	 * @param bool $apply_default false
	 * @return type 
	 */
	function option($name, $lng = false, $apply_default = false)
	{
		// unserialize options
		$this->optionLoadAll();

		if (Theme::previewTheme() && isset(Theme::previewTheme()->data[$name]))
		{
			$return = Theme::previewTheme()->data[$name];
		}
		else
		{
			$return = $this->themeConfig->options[$name];
		}

		if (is_null($lng))
		{
			$lng = I18n::getLocale();
		}

		if (false !== $lng)
		{
			// check for locale value
			$return = $return[$lng];
		}

		// if not set then apply default if requestes
		if ((!is_array($return) && !strlen($return)) && $apply_default)
		{
			// get default value from widget type 
			$return = $this->info['default_options'][$name];
		}

		return $return;
	}

	function optionSaveAll()
	{
		if (isset($this->themeConfig))
		{
			return Config::optionSet($this->optionsKey(), serialize($this->themeConfig));
		}
		return false;
	}

	function optionSaveAllFromData($data = array())
	{
		// process data
		$data = self::processOptionsData($data);


		// load theme options first 
		$this->optionLoadAll();
		// set new data
		if (!$this->themeConfig->options)
		{
			$this->themeConfig->options = array();
		}

		// fix checkboxes, remove value if not selected
		// $data = $this->optionFixCheckboxData($data);

		$this->themeConfig->options = array_merge($this->themeConfig->options, $data);

		// save site title and description if set
		if (isset($this->themeConfig->options['site_title']))
		{
			Config::optionSet('site_title', $this->themeConfig->options['site_title']);
		}
		if (isset($this->themeConfig->options['site_description']))
		{
			Config::optionSet('site_description', $this->themeConfig->options['site_description']);
		}
		if (isset($this->themeConfig->options['site_button_title']))
		{
			Config::optionSet('site_button_title', $this->themeConfig->options['site_button_title']);
		}

		unset($this->themeConfig->options['site_title']);
		unset($this->themeConfig->options['site_description']);
		unset($this->themeConfig->options['site_button_title']);
		unset($this->themeConfig->options['submit']);

		// update last saved css time on theme customization save
		$this->themeConfig->updated_at = REQUEST_TIME;

		return $this->optionSaveAll();
	}

	public function optionFixCheckboxData($data = array())
	{
		$language = Language::getLanguages();

		foreach ($this->info['customize'] as $group_id => $v)
		{
			foreach ($v['fields'] as $field_id => $feild)
			{
				switch ($feild['type'])
				{
					case 'checkbox':
						// if checkbox not selected then remove its data from theme options
						if ($feild['multilingual'])
						{
							foreach ($language as $lng)
							{
								if (!isset($data[$field_id][$lng->id]))
								{
									$data[$field_id][$lng->id] = 0;
								}
							}
						}
						else
						{
							if (!isset($data[$field_id]))
							{
								$data[$field_id] = 0;
							}
						}
						break;
				}
			}
		}

		// clean input data 
		$input = new Input();

		// do not xss php 
		$input->use_xss_clean = false;
		$custom_styles = $input->_clean_input_data($data['custom_styles']);

		$input->use_xss_clean = true;
		$data = $input->_clean_input_data($data);

		// put php data back
		$data['custom_styles'] = $custom_styles;

		return $data;
	}

	function optionSet($name, $value)
	{
		$this->optionLoadAll();

		// get option first 
		$this->themeConfig->options[$name] = $value;

		return $this->optionSaveAll();
	}

	/**
	 * Backup current theme sidebar_widgets and options 
	 * 
	 * @return type 
	 */
	public static function backupThemeOptions()
	{
		// get current theme, backup only current theme because sidebar widgets active for current theme 		
		$theme = self::getTheme();

		// load current options
		$theme->optionLoadAll();

		// current sidebar widgets 
		$theme->themeConfig->sidebar_widgets = Widget::sidebarWidgets();

		return $theme->optionSaveAll();
	}

	/**
	 * generate theme specific option key for using in Config::option
	 * 
	 * @return string 
	 */
	function optionsKey()
	{
		return 'theme_options_' . $this->id;
	}

	/**
	 * Restore sidebar_widgets and activate theme
	 * 
	 * @param string $theme_id
	 * @return Theme 
	 */
	public static function restoreThemeOptions($theme_id)
	{
		// restore new theme from backup
		// load sidebars if exists for new theme

		$theme = self::getTheme($theme_id);

		$theme->optionLoadAll();

		if (isset($theme->themeConfig->sidebar_widgets))
		{
			// restore saved sidebars fr given theme
			Widget::sidebarWidgetsSave($theme->themeConfig->sidebar_widgets);
		}

		// set current theme
		Config::optionSet('theme', $theme->id);

		return $theme;
	}

	public function screenshot()
	{
		return $this->url() . '/screenshot.jpg';
	}

	public static function ifNullDefault($theme_id = null)
	{
		if (is_null($theme_id))
		{
			// check if in preview mode then set preview theme 
			if (self::previewTheme())
			{
				$theme_id = self::previewTheme()->theme_id;
			}
			else
			{
				$theme_id = self::currentThemeId();
			}
		}

		return $theme_id;
	}

	/**
	 * check if valid user, token and theme preview set. return posted theme options 
	 * 
	 * @return Object | bool:false 
	 */
	public static function previewTheme()
	{
		if (!isset(self::$_previewTheme))
		{
			if (strlen($_POST['preview_theme']) && AuthUser::hasPermission(User::PERMISSION_MODERATOR) && Config::nounceCheck())
			{
				// echo '[previewTheme:true]';
				// check if theme installed
				if (self::hasTheme($_POST['preview_theme']))
				{
					self::$_previewTheme = new stdClass();

					Input::getInstance()->get_post();

					$data = array();
					parse_str($_POST['data'], $data);

					// replace selected data 				
					$data = self::processOptionsData($data);

					self::$_previewTheme->data = $data;
					self::$_previewTheme->theme_id = $_POST['preview_theme'];

					// fix checkbox values
					$theme = Theme::getTheme();
					self::$_previewTheme->data = $theme->optionFixCheckboxData(self::$_previewTheme->data);
				}
			}

			// if not set then set to false
			if (!self::$_previewTheme)
			{
				self::$_previewTheme = false;
			}
		}

		return self::$_previewTheme;
	}

	/**
	 * process theme options data before saving or using
	 * replace image field value with selected image 
	 * 
	 */
	static function processOptionsData($data)
	{
		// replace selected data 
		foreach ($data as $k => $v)
		{
			if (preg_match('/_(.*)_selected/', $k, $matches))
			{
				$data[$matches[1]] = $v;
			}
		}

		return $data;
	}

	public static function currentThemeId()
	{
		if (!isset(self::$_current_theme))
		{
			self::$_current_theme = Config::option('theme');
		}

		return self::$_current_theme;
	}

	public static function file($file, $theme_id = null)
	{
		$theme_id = self::ifNullDefault($theme_id);

		if ($theme_id)
		{
			$file = self::ThemesRoot() . $theme_id . '/views/' . ltrim($file, '/') . '.php';
			return $file;
		}
		return false;
	}

	public static function ThemesRoot()
	{
		return FROG_ROOT . '/user-content/themes/';
	}

	public static function ThemesBackupRoot()
	{
		return FROG_ROOT . '/user-content/themes-backup/';
	}

	public function url()
	{
		return URL_PUBLIC . 'user-content/themes/' . $this->id . '/';
	}

	public static function locations($theme_id = null)
	{
		// get current theme locations
		$theme = Theme::getTheme($theme_id);

		return $theme->info['locations'];
	}

	public function author($with_link = true)
	{
		if ($with_link && $this->info['author_url'])
		{
			return '<a href="' . View::escape($this->info['author_url']) . '" target="_blank">' . View::escape($this->info['author_name']) . '</a>';
		}
		else
		{
			return View::escape($this->info['author_name']);
		}
	}

	/**
	 * Retrieve list of themes with theme data in theme directory.
	 * 	 
	 * @return array Theme list with theme data.
	 */
	public static function getThemes($backup = false)
	{
		$backup = intval($backup);

		if (is_null(self::$_themes[$backup]))
		{
			// get themes from directory
			self::$_themes[$backup] = array();
			if ($backup)
			{
				$theme_root = self::ThemesBackupRoot();
			}
			else
			{
				$theme_root = self::ThemesRoot();
			}

			// Files in wp-content/themes directory and one subdir down
			$themes_dir = @ opendir($theme_root);
			if ($themes_dir)
			{
				while (($theme_dir = readdir($themes_dir)) !== false)
				{
					if (is_dir($theme_root . '/' . $theme_dir) && is_readable($theme_root . '/' . $theme_dir))
					{
						if ($theme_dir{0} == '.' || $theme_dir == '..' || $theme_dir == 'CVS')
						{
							continue;
						}
						self::$_themes[$backup][] = $theme_dir;
					}
				}
				if (is_dir($theme_dir))
				{
					@closedir($theme_dir);
				}

				if (!$themes_dir || !self::$_themes[$backup])
				{
					return self::$_themes[$backup];
				}

				sort(self::$_themes[$backup]);
			}
		}

		return self::$_themes[$backup];
	}

	public static function checkValidThemeLoaded()
	{
		$theme = Theme::getTheme();
		if (!$theme->info)
		{
			//var_dump($theme);
			// theme is not found then display error message 
			echo __('Theme is not found. Please login to <a href="{url}">admin</a> and define theme.', array('{url}' => Language::get_url('admin/themes/')));
			exit;
		}
	}

	/**
	 * return field options stored in theme info for given field name
	 * @param string $fieldname
	 * @return array|boolean if found return field values array
	 */
	public function customizeFieldOptions($fieldname)
	{
		// search in theme info for field name and return all values
		$this->optionLoadAll();
		if ($this->info['customize'])
		{
			foreach ($this->info['customize'] as $group_id => $v)
			{
				foreach ($v['fields'] as $field_id => $feild)
				{
					if ($field_id == $fieldname)
					{
						return $feild;
					}
				}
			}
		}

		return false;
	}

	/**
	 * absolute location for uploaded file
	 * @param string $filename
	 * @return string 
	 */
	function uploadDir($filename = '')
	{
		return UPLOAD_ROOT . '/' . $this->uploadDirRelative() . '/' . $filename;
	}

	/**
	 * return relative path for theme uploads
	 * @return string 
	 */
	function uploadDirRelative()
	{
		return 'theme/' . $this->id;
	}

	/**
	 * absolute url for uploaded theme file
	 * @param string $filename
	 * @return string 
	 */
	function uploadUrl($filename = '')
	{
		return View::escape(UPLOAD_URL . '/' . $this->uploadDirRelative() . '/' . $filename);
	}

	/**
	 * absolute location for theme files
	 * @return string 
	 */
	function themeDir()
	{
		return Theme::ThemesRoot() . $this->id . '/';
	}

	/**
	 * delete theme files, options, uploded files from system
	 * 
	 * @return boolean 
	 */
	function deleteTheme()
	{
		$theme_dir = $this->themeDir();

		// delete theme files from server
		if (FileDir::rmdirr($theme_dir))
		{
			// delete theme options from DB
			Config::optionDelete($this->optionsKey());

			// delete uploaded files 
			FileDir::rmdirr($this->uploadDir());

			return true;
		}

		return false;
	}

	/**
	 * delete given backup theme
	 * 
	 * @param string $theme_id folder name of backup
	 * @return boolean
	 */
	public static function deleteThemeBackup($theme_id)
	{
		$theme_id = basename($theme_id);
		if (strpos($theme_id, '..') || $theme_id == '.' || $theme_id == '..')
		{
			return false;
		}

		$theme_dir = self::ThemesBackupRoot() . $theme_id . '/';
		if (is_dir($theme_dir))
		{
			// delete theme files from server
			return FileDir::rmdirr($theme_dir);
		}
		return true;
	}

	/**
	 * delete all theme backups
	 * 
	 * @return boolean
	 */
	public static function deleteThemeBackupAll()
	{
		// delete all theme backups 
		return FileDir::rmdirr(self::ThemesBackupRoot());
	}

	/**
	 * delete file from theme upload location 
	 * 
	 * @param string $filename
	 * @return boolean 
	 */
	function deleteFile($filename)
	{
		if (strlen($filename))
		{
			return FileDir::rmdirr($this->uploadDir($filename));
		}
		return false;
	}

	/**
	 * check if available later version for this theme
	 * 
	 * @return boolean
	 */
	public function isUpdateAvailable()
	{
		$available_updates = Update::availableThemeUpdates();
		$update_data = $available_updates->themes[$this->id];

		if ($update_data && version_compare($this->info['version'], $update_data->version) < 0)
		{
			$this->update_data = $update_data;
			return true;
		}

		return false;
	}

	/**
	 * Add custom styles related to theme to css array, will be edded to layout header
	 * for preview themes css will be added inline
	 * for saved themes css will not be added here. it will be added as seperate file in Config::getCustomCssJsUrl();
	 * 
	 * @param Controller $controller
	 */
	public static function applyCustomStyles($controller)
	{
		if (Theme::previewTheme())
		{
			// load css inline for preview themes
			$custom_styles = Theme::getCustomCSS();
			if (strlen($custom_styles))
			{
				$controller->setMeta('css_other', '<style type="text/css">' . $custom_styles . '</style>');
			}
		}
		else
		{
			// save as seperate file and add to header
			$css_file = Config::getCustomCssJsUrl('css_theme');
			if (strlen($css_file))
			{
				$controller->setMeta('css', $css_file);
			}
		}
	}

	/**
	 * Get custom CSS data saved for given theme
	 * 
	 * @param type $theme_id
	 * @return string
	 */
	public static function getCustomCSS()
	{
		// set custom styles 
		$theme = Theme::getTheme();
		$custom_styles = $theme->option('custom_styles');

		// if has php then eval it
		if (strpos($custom_styles, '<?php') !== false)
		{

			ob_start();
			eval('?>' . $custom_styles);
			$custom_styles = ob_get_clean();
		}

		return trim($custom_styles);
	}

	/**
	 * Get last time when theme customizations in admin was saved
	 * 
	 * @return int
	 */
	public static function getUpdatedAt()
	{
		// set custom styles 
		$theme = Theme::getTheme();
		$theme->optionLoadAll();
		return intval($theme->themeConfig->updated_at);
	}

	/**
	 * Check if theme->version_required >= site|given version
	 * if not some script functions will use old styles 
	 *  
	 * @param string $version
	 * @param Theme $theme
	 * @return bool true if theme required version bigger or equal to given version. 
	 */
	public static function versionSupport($version = null, $theme = null)
	{
		// check if given theme or default theme supports given version or current version 
		if (is_null($version))
		{
			$version = Config::option('site_version');
		}

		if (is_null($theme))
		{
			$theme = self::getTheme();
		}

		// if theme supports given version features return true
		return (version_compare($version, $theme->info['version_required']) <= 0);
	}

}
