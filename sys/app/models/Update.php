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
class Update
{

	static $available_updates = null;

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public static function updateDB()
	{
		// upgrade to new version
		// upgrade from version 1.1 to 1.2
		$cur_version = Config::option('site_version');
		$new_version = Config::VERSION; //1.2, 1.2.2
		$return = false;

		if (!self::isDBLatest())
		{
			// change if old version is 1.0
			if (version_compare($cur_version, '1.0') == 0)
			{
				// index changed for ad table after version 1.0 
				$sql = 'ALTER TABLE ' . Ad::tableNameFromClassName('Ad') . '
						DROP INDEX category_id,	ADD INDEX category_id (category_id),
						ADD INDEX expireson (expireson),
						ADD INDEX abused (abused),
						ADD INDEX published_at (published_at)';
				Ad::query($sql);
			}

			// changes for all versions prior 1.2			
			if (Config::isDBVersionLowerThan('1.2'))
			{
				// changed default value to 0 for finding related ads by user. for ad table before version 1.2
				$sql = "ALTER TABLE " . Ad::tableNameFromClassName('Ad') . "
						CHANGE COLUMN `phone` `phone` VARCHAR(50) NOT NULL DEFAULT '' AFTER `showemail`
						CHANGE COLUMN `expireson` `expireson` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `featured_expireson`,
						CHANGE COLUMN `updated_at` `updated_at` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `expireson`,
						CHANGE COLUMN `updated_by` `updated_by` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `updated_at`,
						CHANGE COLUMN `added_at` `added_at` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `updated_by`,
						CHANGE COLUMN `added_by` `added_by` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `added_at`,
						CHANGE COLUMN `published_at` `published_at` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `added_by`";
				Ad::query($sql);


				$sql = "ALTER TABLE " . Category::tableNameFromClassName('Category') . "
						CHANGE COLUMN `slug` `slug` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'used in url' AFTER `parent_id`,
						CHANGE COLUMN `pos` `pos` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '9999' AFTER `slug`,
						CHANGE COLUMN `added_at` `added_at` INT(11) NOT NULL DEFAULT '0' AFTER `enabled`,
						CHANGE COLUMN `added_by` `added_by` INT(11) NOT NULL DEFAULT '0' AFTER `added_at`";
				Category::query($sql);


				$sql = "ALTER TABLE " . Location::tableNameFromClassName('Location') . "						
						CHANGE COLUMN `slug` `slug` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'used in url' AFTER `parent_id`,
						CHANGE COLUMN `pos` `pos` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '9999' AFTER `slug`,
						CHANGE COLUMN `added_at` `added_at` INT(11) NOT NULL DEFAULT '0' AFTER `enabled`,
						CHANGE COLUMN `added_by` `added_by` INT(11) NOT NULL DEFAULT '0' AFTER `added_at`";
				Location::query($sql);


				$sql = "ALTER TABLE " . MailTemplate::tableNameFromClassName('MailTemplate') . "						
						CHANGE COLUMN `id` `id` VARCHAR(50) NOT NULL DEFAULT '' FIRST,
						CHANGE COLUMN `language_id` `language_id` VARCHAR(10) NOT NULL DEFAULT '' AFTER `id`,
						CHANGE COLUMN `subject` `subject` VARCHAR(150) NOT NULL DEFAULT '' AFTER `language_id`,
						CHANGE COLUMN `body` `body` TEXT NOT NULL DEFAULT '' AFTER `subject`";
				MailTemplate::query($sql);


				$sql = "ALTER TABLE " . Page::tableNameFromClassName('Page') . "						
						CHANGE COLUMN `pos` `pos` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '9999' AFTER `parent_id`,
						CHANGE COLUMN `added_at` `added_at` INT(11) NOT NULL DEFAULT '0' AFTER `enabled`,
						CHANGE COLUMN `added_by` `added_by` INT(11) NOT NULL DEFAULT '0' AFTER `added_at`";
				Page::query($sql);

				$sql = "ALTER TABLE " . Payment::tableNameFromClassName('Payment') . "						
						CHANGE COLUMN `ad_id` `ad_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`,
						CHANGE COLUMN `payment_log_id` `payment_log_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `ad_id`,
						CHANGE COLUMN `item_type` `item_type` TINYINT(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '1:post, 2:premium, 3:premium_request' AFTER `payment_log_id`,
						CHANGE COLUMN `amount` `amount` DOUBLE(10,2) NOT NULL DEFAULT '0.00' AFTER `item_type`,
						CHANGE COLUMN `currency` `currency` VARCHAR(5) NOT NULL DEFAULT '' AFTER `amount`,
						CHANGE COLUMN `added_at` `added_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `currency`,
						CHANGE COLUMN `added_by` `added_by` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `added_at`";
				Payment::query($sql);

				$sql = "ALTER TABLE " . PaymentLog::tableNameFromClassName('PaymentLog') . "						
						CHANGE COLUMN `ad_id` `ad_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `txnid`,
						CHANGE COLUMN `itemname` `itemname` VARCHAR(250) NOT NULL DEFAULT '' AFTER `ad_id`,
						CHANGE COLUMN `itemnumber` `itemnumber` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'adid|item_type_post|item_type_premium|...' AFTER `itemname`,
						CHANGE COLUMN `added_at` `added_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `message`,
						CHANGE COLUMN `added_by` `added_by` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `added_at`";
				PaymentLog::query($sql);



				$sql = "ALTER TABLE " . PaymentPrice::tableNameFromClassName('PaymentPrice') . "						
						CHANGE COLUMN `price_featured` `price_featured` DOUBLE(10,2) UNSIGNED NOT NULL DEFAULT '0' AFTER `category_id`,
						CHANGE COLUMN `price_post` `price_post` DOUBLE(10,2) UNSIGNED NOT NULL DEFAULT '0' AFTER `price_featured`,
						CHANGE COLUMN `added_at` `added_at` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `price_post`,
						CHANGE COLUMN `added_by` `added_by` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `added_at`";
				PaymentPrice::query($sql);

				$sql = "ALTER TABLE " . Permalink::tableNameFromClassName('Permalink') . "						
						CHANGE COLUMN `updated_at` `updated_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `is_old`,
						CHANGE COLUMN `added_at` `added_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `updated_at`,
						CHANGE COLUMN `added_by` `added_by` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `added_at`";
				Permalink::query($sql);

				$sql = "ALTER TABLE " . User::tableNameFromClassName('User') . "						
						CHANGE COLUMN `pending_level` `pending_level` TINYINT(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'if user pending to be dealer, or suspended by admin from being user or dealer mark here' AFTER `level`,
						CHANGE COLUMN `enabled` `enabled` TINYINT(2) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0:disabled, 1: enabled user' AFTER `pending_level`,
						CHANGE COLUMN `logged_at` `logged_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'last login time' AFTER `logo`,
						CHANGE COLUMN `added_at` `added_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'registration time' AFTER `logged_at`,
						CHANGE COLUMN `added_by` `added_by` INT(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `added_at`";
				User::query($sql);

				// update option 
				Config::optionSet('ad_posting_without_registration', Config::option('ad_posting_wihout_registration'));
				Config::optionDelete('ad_posting_wihout_registration');
			}

			// changes for all versions prior 1.3
			if (Config::isDBVersionLowerThan('1.3'))
			{
				$sql = "DROP TABLE IF EXISTS " . Record::tableNameFromClassName('AdCategoryCount');
				Record::query($sql);

				$sql = "CREATE TABLE " . Record::tableNameFromClassName('AdCategoryCount') . " (
							`category_id` INT(10) UNSIGNED NOT NULL,
							`location_id` INT(10) UNSIGNED NOT NULL,
							`user_id` INT(10) UNSIGNED NOT NULL,
							`search_id` BIGINT(20) UNSIGNED NOT NULL,
							`count_listed` BIGINT(20) UNSIGNED NOT NULL,
							`count_not_listed` BIGINT(20) UNSIGNED NOT NULL,
							`added_at` INT(10) UNSIGNED NOT NULL,
							PRIMARY KEY (`category_id`, `location_id`, `user_id`, `search_id`),
							INDEX `added_at` (`added_at`)
						)ENGINE=InnoDB DEFAULT CHARSET=utf8";
				Record::query($sql);

				// reset counts
				AdCategoryCount::clearAll();
			}

			// changes for all versions prior 1.3.3
			if (Config::isDBVersionLowerThan('1.3.3'))
			{
				$sql = "ALTER TABLE " . Record::tableNameFromClassName('Permalink') . "	DROP INDEX `slug`,	ADD UNIQUE INDEX `slug` (`slug`)";
				Record::query($sql);
			}

			// changes for all versions prior 1.3.4
			if (Config::isDBVersionLowerThan('1.3.4'))
			{
				/// check if type existst
				$sql = "SHOW COLUMNS FROM " . IpBlock::tableNameFromClassName('IpBlock') . " LIKE 'type'";
				$exists = Record::query($sql, array());

				if (!$exists)
				{
					$sql = "ALTER TABLE " . IpBlock::tableNameFromClassName('IpBlock') . "
							ADD COLUMN `type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `num`,
							DROP INDEX `ip`,
							ADD UNIQUE INDEX `ip` (`ip`, `ip_end`, `type`),
							ADD INDEX `type` (`type`)";
					Record::query($sql);

					// set initial settings 
					if (!IpBlock::contactLimitIsEnabled())
					{
						Config::optionSet('ipblock_contact_limit_count', 5, true, true);
						Config::optionSet('ipblock_contact_limit_period', 60, true, true);
						Config::optionSet('ipblock_contact_ban_period', 60, true, true);
					}
					if (!IpBlock::loginAttemptIsEnabled())
					{
						Config::optionSet('ipblock_login_attempt_count', 5, true, true);
						Config::optionSet('ipblock_login_attempt_period', 60, true, true);
						Config::optionSet('ipblock_login_ban_period', 30, true, true);
					}
				}
			}

			// changes for all versions prior 1.4
			if (Config::isDBVersionLowerThan('1.4'))
			{
				// default values for map
				Config::optionSet('map_enabled', 1, true, true);
				Config::optionSet('map_append_to_description', 1, true, true);
				Config::optionSet('map_zoom_level', 14, true, true);

				// set default capthca to simple
				Config::optionSet('use_captcha', 'simple', true, true);
			}

			// changes for all versions prior 1.4.2
			if (Config::isDBVersionLowerThan('1.4.2'))
			{
				// default contact options
				Config::optionSet('default_contact_option', 'showemail_2', true, true);
				Config::optionSet('showemail_0', 1, true, true);
				Config::optionSet('showemail_1', 1, true, true);
				Config::optionSet('showemail_2', 1, true, true);
			}


			// changes for all versions prior 1.4.4
			if (Config::isDBVersionLowerThan('1.4.4'))
			{
				// default dealer info displays
				Config::optionSet('account_dealer_display_info_ad_page', 1, true, true);
				Config::optionSet('account_dealer_display_logo_listing', 'no_ad_image', true, true);

				// send notification about pending approval actions
				Config::optionSet('notify_admin_pending_approval', 1, true, true);
			}


			// changes for all versions prior 1.4.5
			if (Config::isDBVersionLowerThan('1.4.5'))
			{
				// no need to set currency formating as it will work without setting 				
				Config::optionSet('delete_after_days', '-1', true, true);
			}

			// changes for all versions prior 1.6
			if (Config::isDBVersionLowerThan('1.6'))
			{
				// no need to set currency formating as it will work without setting 				
				Config::optionSet('currency_iso_4721', 'USD', true, true);
				Config::optionSet('extend_ad_days', '10,30,100,365', true, true);
			}


			// changes for all versions prior 1.7
			if (Config::isDBVersionLowerThan('1.7'))
			{
				// create table AdRelated
				$sql = "DROP TABLE IF EXISTS " . Record::tableNameFromClassName('AdRelated');
				Record::query($sql);

				$sql = "CREATE TABLE " . Record::tableNameFromClassName('AdRelated') . " (
						`ad_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
						`related` TEXT NOT NULL,
						`related_at` INT(10) UNSIGNED NOT NULL DEFAULT '0',
						PRIMARY KEY (`ad_id`)
					)
					ENGINE=InnoDB DEFAULT CHARSET=utf8";
				Record::query($sql);

				// changeging columns takes long so make sure that those columns are not itn(10)
				$change_cat_col = true;
				$sql = "SHOW COLUMNS FROM " . Ad::tableNameFromClassName('Ad');
				$columns = Record::query($sql, array());
				foreach ($columns as $col)
				{
					if (strtolower($col->Field) == 'category_id' && strpos(strtolower($col->Type), 'int(10)') !== false)
					{
						// it is already int(10) no need to change them 
						$change_cat_col = false;
					}
				}
				if ($change_cat_col)
				{
					// change location_id and category_id column type to int(10). old value was smallint(5)
					$sql = "ALTER TABLE " . Ad::tableNameFromClassName('Ad') . "
					CHANGE COLUMN `location_id` `location_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`,
					CHANGE COLUMN `category_id` `category_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `location_id`";
					Record::query($sql);
				}
			}


			// changes for all versions prior 1.7.6
			if (Config::isDBVersionLowerThan('1.7.6'))
			{
				// set image size settings
				Config::optionSet('ad_image_max_width', '1200', true, true);
				Config::optionSet('ad_image_max_height', '1200', true, true);
				Config::optionSet('ad_image_max_filesize', '2000', true, true);
			}

			// changes for all versions prior 1.7.8
			if (Config::isDBVersionLowerThan('1.7.8'))
			{
				// fix empty image names 
				Adpics::fixStoredEmptyImageNames();
			}


			// changes for all versions prior 1.8 			
			if (Config::isDBVersionLowerThan('1.8'))
			{
				// set default values for not defined rows
				$sql = "ALTER TABLE " . AdCategoryCount::tableNameFromClassName('AdCategoryCount') . "
						CHANGE COLUMN `category_id` `category_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
						CHANGE COLUMN `location_id` `location_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
						CHANGE COLUMN `user_id` `user_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',
						CHANGE COLUMN `search_id` `search_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
						CHANGE COLUMN `count_listed` `count_listed` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
						CHANGE COLUMN `count_not_listed` `count_not_listed` BIGINT(20) UNSIGNED NOT NULL DEFAULT '0',
						CHANGE COLUMN `added_at` `added_at`INT(10) UNSIGNED NOT NULL DEFAULT '0'
					";
				Record::query($sql);

				$sql = "ALTER TABLE " . Language::tableNameFromClassName('Language') . "
						CHANGE COLUMN `name` `name` varchar(50) NOT NULL DEFAULT ''
					";
				Record::query($sql);

				$sql = "ALTER TABLE " . PaymentLog::tableNameFromClassName('PaymentLog') . "
						CHANGE COLUMN `txnid` `txnid` varchar(50) NOT NULL DEFAULT ''
					";
				Record::query($sql);

				$sql = "ALTER TABLE " . User::tableNameFromClassName('User') . "
						CHANGE COLUMN `name` `name` varchar(100) NOT NULL DEFAULT '',
						CHANGE COLUMN `username` `username` varchar(100) NOT NULL DEFAULT '' COMMENT 'slug for permalink',
						CHANGE COLUMN `email` `email` varchar(100) NOT NULL DEFAULT '',
						CHANGE COLUMN `password` `password` varchar(50) NOT NULL DEFAULT '',
						CHANGE COLUMN `ip` `ip` varchar(32) NOT NULL DEFAULT '',
						CHANGE COLUMN `web` `web` varchar(100) NOT NULL DEFAULT '' COMMENT 'user website address',
						CHANGE COLUMN `info` `info` text NULL COMMENT 'contact address, working hours, telephone and other info',
						CHANGE COLUMN `logo` `logo` varchar(100) NOT NULL DEFAULT '' COMMENT 'logo of dealer'
					";
				Record::query($sql);

				$sql = "ALTER TABLE " . Widget::tableNameFromClassName('Widget') . "
						CHANGE COLUMN `type_id` `type_id` varchar(50) NOT NULL DEFAULT '' COMMENT 'id that is defined in script, widget types: location, category, search, menu ...'
					";
				Record::query($sql);


				// add hits as key 				
				if (!self::_indexExists(Ad::tableNameFromClassName('Ad'), 'hits'))
				{
					// add index
					$sql = "ALTER TABLE " . Ad::tableNameFromClassName('Ad') . " ADD INDEX `hits` (`hits`)";
					Record::query($sql);
				}


				// update config file 
				$config_php = FROG_ROOT . '/sys/config.php';
				$configFile = @file_get_contents($config_php);
				if (strlen($configFile) > 10)
				{
					// config file read correctly
					$str_search = 'define(\'URL_PUBLIC\', \'http://\' . DOMAIN . \'/\');';
					$str_replace = '$_url_protocol = \'http://\';
if ((!empty($_SERVER[\'HTTPS\']) && $_SERVER[\'HTTPS\'] !== \'off\') || $_SERVER[\'SERVER_PORT\'] == 443)
{	
	$_url_protocol = \'https://\';
}
define(\'URL_PROTOCOL\', $_url_protocol);
define(\'URL_PUBLIC\', URL_PROTOCOL . DOMAIN . \'/\');';

					if (strpos($configFile, $str_search) !== false)
					{
						// old value found update and save it 
						$configFile = str_replace($str_search, $str_replace, $configFile);

						// put file contents back 
						if (!file_put_contents($config_php, $configFile))
						{
							// error saving config file. send to instruction page 
							$update_config_msg = __('Error adding HTTPS support to config file. Read instructions here {url}', array(
								'{url}' => 'http://classibase.com/classibase-version-1-8-https-recaptcha2-ad-view-counter/'
							));

							Validation::getInstance()->set_info($update_config_msg);
						}
					}
				}
			}

			// changes for all versions prior 1.9.2
			if (Config::isDBVersionLowerThan('1.9.2'))
			{
				$sql = "ALTER TABLE " . Config::tableNameFromClassName('Config') . "
						CHANGE COLUMN `val` `val` MEDIUMTEXT NULL
					";
				Record::query($sql);
			}

			// changes for all versions prior 2
			if (Config::isDBVersionLowerThan('2'))
			{
				// set flag to convert old items to fulltext 
				Config::optionSet('fulltext_status', '');
				AdFulltext::enableLazyConvertionFlag();


				//  set defaults to new settings 
				$auto_move_to_trash_after = array(
					'days'	 => 30,
					'status' => array(
						Ad::STATUS_BANNED,
						Ad::STATUS_DUPLICATE,
						Ad::STATUS_INCOMPLETE
					)
				);
				Config::optionSet('auto_move_to_trash_after', $auto_move_to_trash_after, true, true);
				Config::optionSet('auto_delete_from_trash_days', 180, true, true);


				// create fulltext index table 
				$sql = "DROP TABLE IF EXISTS " . Record::tableNameFromClassName('AdFulltext');
				Record::query($sql);

				$sql = "CREATE TABLE IF NOT EXISTS " . Record::tableNameFromClassName('AdFulltext') . " (
							`id` bigint(20) unsigned NOT NULL,
							`title` text NOT NULL,
							`description` longtext NOT NULL,
							PRIMARY KEY (`id`),
							FULLTEXT KEY `title` (`title`),
							FULLTEXT KEY `description` (`description`)
						  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='fulltext index of ad title and description normolized using metaphone and soundex'";
				Record::query($sql);
			}

			if (Config::isDBVersionLowerThan('2.0.4'))
			{
				if (!self::_indexExists(Record::tableNameFromClassName('Config'), 'autoload'))
				{
					$sql = "ALTER TABLE " . Record::tableNameFromClassName('Config') . " ADD INDEX `autoload` (`autoload`)";
					Record::query($sql);
				}
			}
			
			if (Config::isDBVersionLowerThan('2.0.5'))
			{
				// ads_separate=0 default
				Config::optionSet('ads_separate', '0', true, true);
			}
			
			
			// FUTURE NEW VESRION make sure val field in config fixed to mediumtext format. 
			if (Config::isDBVersionLowerThan('2.0.7'))
			{
				$sql = "ALTER TABLE " . Config::tableNameFromClassName('Config') . "
						CHANGE COLUMN `val` `val` MEDIUMTEXT NULL
					";
				Record::query($sql);
			}


			// clear cache
			SimpleCache::clearAll();

			// update version number
			Config::optionSet('site_version', $new_version);


			// Successfully upgraded to version {version}. $new_version
			$return = true;
		}
		else
		{
			// Already using latets version {version}. $cur_version
			$return = true;
		}

		return $return;
	}

	public static function isDBLatest()
	{
		$cur_version = Config::option('site_version');
		$new_version = Config::VERSION; //1.2, 1.2.2

		return (version_compare($new_version, $cur_version) < 1);
	}

	public static function isCoreLatest()
	{
		// presume always as latest 
		$return = true;
		$update_data = self::availableUpdates();

		if ($update_data->version && strlen($update_data->download))
		{
			// check for version
			$cur_version = Config::option('site_version');
			$return = (version_compare($update_data->version, $cur_version) < 1);
		}

		return $return;
	}

	public static function updateNotice()
	{
		if (!self::isDBLatest())
		{
			$new_version = Config::VERSION; //1.2, 1.2.2
			Validation::getInstance()->set_info(__('New version {num} of script installed. Some changes need to be done to your database. Click here and <a href="{url}">update database tables</a>.', array(
				'{num}'	 => $new_version,
				'{url}'	 => Language::get_url('admin/updateDB/')
			)));
		}
		else
		{

			// display available update info
			$update_data = self::availableUpdates();
			if ($update_data->version)
			{
				// check for verison 
				$cur_version = Config::option('site_version');
				if (version_compare($update_data->version, $cur_version) > 0)
				{
					// has available updates 
					Validation::getInstance()->set_info(__('New version of script available. Installed version {num}, available version {num2}. <a href="{url}">Update automatically</a> or <a href="{download}">download manually</a>.', array(
						'{num}'		 => $cur_version,
						'{num2}'	 => $update_data->version,
						'{url}'		 => Language::get_url('admin/update/'),
						'{download}' => $update_data->info_url
					)));
				}
			}

			// display available theme updates
			$available_theme_updates = Update::availableThemeUpdates();
			foreach ($available_theme_updates->themes_update as $theme_id => $update_data)
			{
				$theme = Theme::getTheme($theme_id);
				if ($theme->isUpdateAvailable())
				{
					// has available updates 
					Validation::getInstance()->set_info(__('Theme updates available, <a href="{url}">view updates</a>.', array(
						'{url}' => Language::get_url('admin/themes/')
					)));
					// just need one update to display you have theme updates info 
					break;
				}
			}
		}
	}

	public static function availableUpdates()
	{
		if (!isset(self::$available_updates))
		{
			// check for updates passive
			self::checkForUpdates(false);

			// read update data and return 
			$update_data = Config::option('update_data');
			if (strlen($update_data))
			{
				$update_data = @unserialize($update_data);
			}

			if (!is_object($update_data))
			{
				$update_data = new stdClass();
			}

			self::$available_updates = $update_data;
		}


		return self::$available_updates;
	}

	public static function checkForUpdates($force = null, $silent = true)
	{
		$check_period_days = 1;
		$return = true;

		if (is_null($force))
		{
			$force = Config::option('last_checkForUpdates') < REQUEST_TIME - 3600 * 24 * $check_period_days;
		}

		if ($force)
		{
			// get latest available version for core files
			// get latest available version for installed themes
			$update_str = Curl::get(Config::UPDATE_URL);


			/* @var $update_data stdClass 
			 * 
			 * $update_data->version
			 * $update_data->info_url
			 * $update_data->download
			 * $update_data->download_file_name
			 * 
			 */

			if (strlen($update_str))
			{
				$update_data = @unserialize($update_str);

				// check if valid response
				if ($update_data && isset($update_data->version))
				{
					$update_data->download_file_name = basename($update_data->download);

					// save update data
					Config::optionSet('update_data', serialize($update_data), 0);
				}
				else
				{
					if (!$silent)
					{
						Validation::getInstance()->set_error(__('Update data is not complete'));
					}
					$return = false;
				}
			}
			else
			{
				if (!$silent)
				{
					Validation::getInstance()->set_error(__('Error checking for updates'));
				}
				$return = false;
			}

			Config::optionSet('last_checkForUpdates', REQUEST_TIME);
		}

		return $return;
	}

	public static function unzip($file, $destination)
	{
		// Unzip can use a lot of memory, but not this much hopefully
		@ini_set('memory_limit', Config::MAX_MEMORY_LIMIT);

		if (!is_file($file))
		{
			// file not exists return false
			$error = 'Source file not exists: ' . View::escape($file);
			Validation::getInstance()->set_error($error);
			Benchmark::cp($error);

			return false;
		}

		// delete files on destination if exists
		if (!FileDir::rmdirr($destination))
		{
			// file not exists return false
			$error = 'Error destination folder is not empty: ' . View::escape($destination);
			Validation::getInstance()->set_error($error);
			Benchmark::cp($error);

			return false;
		}

		// create destination folder
		if (!FileDir::checkMakeDir($destination))
		{
			// cannot create destination folder
			$error = 'Error cannot create destination folder: ' . View::escape($destination);
			Validation::getInstance()->set_error($error);
			Benchmark::cp($error);

			return false;
		}

		// check if has 
		if (class_exists('ZipArchive', false))
		{
			// use ZipArchive
			Benchmark::cp('unizp using ZipArchive');
			$zip = new ZipArchive;
			$res = $zip->open($file);
			if ($res === TRUE)
			{
				$zip->extractTo($destination);
				$zip->close();
				Benchmark::cp('unizp OK');
				$return = true;
			}
			else
			{
				$error = 'Error unzip failed (ZipArchive->open()), code:' . $res;
				Validation::getInstance()->set_error($error);
				Benchmark::cp($error);
				$return = false;
			}
		}
		else
		{
			// use PclZip
			Benchmark::cp('unizp using PclZip');

			use_file('PclZip.php');
			$archive = new PclZip($file);
			if ($archive->extract(PCLZIP_OPT_PATH, $destination) == 0)
			{
				$error = 'Error unzip failed (PclZip->extract()):' . $archive->errorInfo(true);
				Validation::getInstance()->set_error($error);
				Benchmark::cp($error);
				$return = false;
			}
			else
			{
				Benchmark::cp('unizp OK');
				$return = true;
			}
		}

		return $return;
	}

	public static function updateCoreStep($step = null)
	{
		$update_data = self::availableUpdates();
		if (self::isCoreLatest())
		{
			// site is up to date
			Validation::getInstance()->set_info(__('Your script is up to date.'));
			return false;
		}
		elseif (!$update_data)
		{
			// no update
			Validation::getInstance()->set_info(__('Update is not available. Visit {name} to find out about latest updates.', array('{name}' => Config::scriptName())));
			return false;
		}

		// as some file, save to upload dir
		$upload_dir = self::dir() . $update_data->download_file_name;
		$extract_dir = self::dir() . 'dir-' . $update_data->download_file_name . '/';


		if (is_null($step))
		{
			// continue from last complete step
			$step = Config::option('update_next_step');
			if (!$step)
			{
				$step = 'init';
			}
		}


		switch ($step)
		{
			case 'init':
				// reset update first
				self::reset();

				Validation::getInstance()->set_info(__('Downloading installation file {name}', array('{name}' => View::escape(basename($update_data->download)))));
				Config::optionSet('update_next_step', 'download');
				return true;
				break;
			case 'download':
				// download file
				$zip_file = Curl::get($update_data->download);
				if (strlen($zip_file))
				{
					FileDir::rmdirr($extract_dir);

					// save file as zip
					if (FileDir::checkMakeFile($upload_dir, $zip_file))
					{
						// saved donloaded zip
						Validation::getInstance()->set_info(__('File downloaded'));
						Config::optionSet('update_next_step', 'extract');
						return true;
					}
					else
					{
						Validation::getInstance()->set_error(__('Error saving downloaded file'));
						return false;
					}
				}// get zip file 
				else
				{
					Validation::getInstance()->set_error(__('Error downloading file'));
					return false;
				}
				break;
			case 'extract':
				// extract files
				if (Update::unzip($upload_dir, $extract_dir))
				{
					// unzipped, now move files to main location 
					// check if sys exists 
					if (is_dir($extract_dir . 'sys/'))
					{
						Validation::getInstance()->set_info(__('Files extracted'));
						Config::optionSet('update_next_step', 'replace');
						return true;
					}
					else
					{
						Validation::getInstance()->set_error(__('Extracted file is not correct'));
						return false;
					}
				}// unzip
				else
				{
					Validation::getInstance()->set_error(__('Error extracting file'));
					return false;
				}
				break;
			case 'replace':

				// delete themes folder first to not override existing one
				$user_content_dir = $extract_dir . 'user-content/';
				FileDir::rmdirr($user_content_dir);

				// delete language files
				$languages = Language::getLanguages();
				foreach ($languages as $lng)
				{
					if ($lng->id !== 'en')
					{
						// remove defined non english language files
						FileDir::rmdirr($extract_dir . 'sys/app/i18n/' . $lng->id . '-message.php');
					}
				}

				// delete defailt ico 
				FileDir::rmdirr($extract_dir . 'favicon.ico');

				// delete config file if exists in update files by mistake. because it should not be in update nor in fresh installs
				FileDir::rmdirr($extract_dir . 'sys/config.php');


				// check if files overwritable
				// did not work if there are other files and folders not related to script
				/* $is_writable_root = FileDir::isWritable(FROG_ROOT);
				  if(is_array($is_writable_root))
				  {
				  // root folder is not writable give error
				  Validation::getInstance()->set_info(__('Following files and folders are not writable. Please change permission to 777 for these files.'));
				  Validation::getInstance()->set_info(implode('<br>', $is_writable_root));

				  return false;
				  } */

				// ok exists then move folder and override existing one
				if (FileDir::dirmv($extract_dir, FROG_ROOT, true))
				{
					// moved 
					Validation::getInstance()->set_info(__('Replaced system files'));

					// update language files
					if (Language::updateEmptyLanguageTranslations())
					{
						Validation::getInstance()->set_info(__('Updated language files'));
					}

					Config::optionSet('update_next_step', 'update_db');
					return true;
				}
				else
				{
					Validation::getInstance()->set_error(__('Error installing updates'));
					return false;
				}
				break;
			case 'update_db':
				// update database to latest version 
				if (self::updateDB())
				{
					Validation::getInstance()->set_info(__('Database updated'));

					// complete update 
					self::reset();
					Validation::getInstance()->set_info(__('Update completed'));

					return true;
				}
				else
				{
					Validation::getInstance()->set_error(__('Error updating database'));
					return false;
				}
				break;
			default:
				Validation::getInstance()->set_error(__('Incorrect update step: {name}', array('{name}' => View::escape($step))));
				return false;
		}
	}

	public static function updateTheme($theme_id)
	{
		// get theme info 
		$theme = Theme::getTheme($theme_id);

		// load update data 
		$available_updates = Update::availableUpdates();
		$update_data = $available_updates->themes[$theme_id];


		if (isset($update_data))
		{
			if (!$theme)
			{
				// installing new theme continue 
			}
			elseif (!$theme->isUpdateAvailable())
			{
				// compare versions
				Validation::getInstance()->set_info(__('Theme {name} is up to date.', array('{name}' => View::escape($theme_id))));
				return false;
			}
		}
		else
		{
			// no update for $theme_id theme
			Validation::getInstance()->set_error(__('{name} theme source is not found.', array('{name}' => View::escape($theme_id))));
			return false;
		}

		// check minimum version 
		if (version_compare(Config::option('site_version'), $update_data->version_required) < 0)
		{
			Validation::getInstance()->set_error(__('{name} theme requires script version {num} or later.', array(
				'{name}' => View::escape($theme_id),
				'{num}'	 => View::escape($update_data->version_required)
			)));
			return false;
		}

		// define folders 
		$update_data->download_file_name = basename($update_data->download);
		$upload_dir = self::dir() . $update_data->download_file_name;
		$extract_dir = self::dir() . 'dir-' . $update_data->download_file_name . '/';
		$theme_dir = Theme::ThemesRoot() . $theme_id . '/';
		$theme_dir_backup = Theme::ThemesBackupRoot() . $theme_id . '-version' . $theme->info['version'] . '/';



		/**
		 * download file
		 */
		$zip_file = Curl::get($update_data->download);
		if (strlen($zip_file))
		{
			FileDir::rmdirr($extract_dir);

			// save file as zip
			if (FileDir::checkMakeFile($upload_dir, $zip_file))
			{
				// saved downloaded zip
				Validation::getInstance()->set_info(__('File downloaded'));
				Config::optionSet('update_next_step', 'extract');
			}
			else
			{
				Validation::getInstance()->set_error(__('Error saving downloaded file'));
				return false;
			}
		}// get zip file 
		else
		{
			Validation::getInstance()->set_error(__('Error downloading file'));
			return false;
		}


		/**
		 * extract files
		 */
		if (Update::unzip($upload_dir, $extract_dir))
		{
			Validation::getInstance()->set_info(__('Files extracted'));
		}// unzip
		else
		{
			Validation::getInstance()->set_error(__('Error extracting file'));
			return false;
		}


		/**
		 * move existing theme to backup folder
		 */
		if (is_dir($theme_dir))
		{
			// move it to backup 
			if (FileDir::dirmv($theme_dir, $theme_dir_backup, true))
			{
				// moved 
				Validation::getInstance()->set_info(__('Old theme saved to {name}', array('{name}' => $theme_dir_backup)));
			}
			else
			{
				Validation::getInstance()->set_error(__('Error saving old theme to {name}', array('{name}' => $theme_dir_backup)));
				return false;
			}
		}

		/**
		 * move new theme to themes directory, override existing one
		 */
		if (FileDir::dirmv($extract_dir, $theme_dir, true))
		{
			// moved 
			Validation::getInstance()->set_info(__('Installed theme: {name}', array('{name}' => '<a href="' . Language::get_url('admin/themes/#' . View::escape($theme_id)) . '">' . View::escape($theme_id) . '</a>')));
		}
		else
		{
			Validation::getInstance()->set_error(__('Error installing theme'));
			return false;
		}

		return true;
	}

	public static function dir()
	{
		return UPLOAD_ROOT . '/update/';
	}

	public static function reset()
	{
		// delete eveything in update folder
		$return = FileDir::rmdirr(self::dir());

		Config::optionDelete('update_next_step');

		return $return;
	}

	public static function updateCoreAll()
	{
		$steps = array(
			'init',
			'download',
			'extract',
			'replace',
			'update_db'
		);

		foreach ($steps as $step)
		{
			if (!self::updateCoreStep($step))
			{
				// break on first error
				return false;
			}
		}

		return true;
	}

	/**
	 * load updates and installed thems, then seperate themes array from update object to 2 seperate arrays themes_update,themes_new
	 * 
	 * @return update object downloaded from remote server
	 */
	public static function availableThemeUpdates()
	{
		// get available updates obj. and add new arrays to it
		$available_updates = Update::availableUpdates();

		// add only if it is not done before
		if (!isset($available_updates->themes_new))
		{
			$available_updates->themes_new = array();
			$available_updates->themes_update = array();

			// get installed themes
			$themes = Theme::getThemes();

			// set themes_update if theme installed
			foreach ($themes as $theme_id)
			{
				if ($available_updates->themes[$theme_id])
				{
					$available_updates->themes_update[$theme_id] = $available_updates->themes[$theme_id];
				}
			}

			// all other themes that are not in update are themes_new 
			if ($available_updates->themes)
			{
				foreach ($available_updates->themes as $theme_id => $update_info)
				{
					if (!isset($available_updates->themes_update[$theme_id]))
					{
						$available_updates->themes_new[$theme_id] = $update_info;
					}
				}
			}
		}

		return $available_updates;
	}

	/**
	 * Check if index for given table exists 
	 * 
	 * @param string $table
	 * @param string $index
	 * @return boolean
	 */
	static private function _indexExists($table, $index)
	{
		// add hits as key 
		$sql = "SELECT COUNT(1) index_is_there FROM INFORMATION_SCHEMA.STATISTICS
						WHERE table_schema=DATABASE() AND table_name='" . $table . "' AND index_name='" . $index . "'";
		$result = Record::query($sql, array());
		return ($result[0]->index_is_there == 1);
	}

}
