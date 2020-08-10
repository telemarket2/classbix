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
 * class IpBlock
 * These are fields that can be attached to all ads by default or by category and location
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class IpBlock extends Record
{

	const TABLE_NAME = 'ip_block';
	const TYPE_ACCESS = 0;
	const TYPE_LOGIN = 1;
	const TYPE_CONTACT = 2;
	const TYPE_POST = 3;

	private static $cols = array(
		'id'		 => 1,
		'ip'		 => 1,
		'ip_end'	 => 1,
		'num'		 => 1,
		'type'		 => 1,
		'added_at'	 => 1,
		'added_by'	 => 1,
	);

	// type added in version 1.3.4
	// info added in version 1.3.4

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	function beforeInsert()
	{
		$this->added_at = REQUEST_TIME;
		$this->added_by = AuthUser::$user->id;

		return true;
	}

	public static function isBlockedIp($halt = true)
	{
		// check current user ip 
		$return = false;
		$ip_str = self::myIpStr();


		$ipblock_requested = false;
		$site_version = Config::option('site_version');
		// db older than 1.3.4
		if (version_compare($site_version, '1.3.4') == -1)
		{
			// prior to 1.3.4 version there was no type, so error will accure on every page load, this will prevent it
			$ipblock = IpBlock::findOneFrom('IpBlock', 'ip=? OR (ip<=? AND ip_end>=?)', array($ip_str, $ip_str, $ip_str));
			if (intval($ipblock->type) == IpBlock::TYPE_ACCESS)
			{
				// ipblock prior to 1.3.4 dont have type, if it has not type then we have correct ipblock
				$ipblock_requested = true;
			}
		}

		if (!$ipblock_requested)
		{
			// request ipblock with type
			$ipblock = IpBlock::findOneFrom('IpBlock', 'type=? AND (ip=? OR (ip<=? AND ip_end>=?))', array(self::TYPE_ACCESS, $ip_str, $ip_str, $ip_str));
		}

		if ($ipblock)
		{
			// increase count
			$ipblock->num = $ipblock->num + 1;
			$ipblock->save();

			$return = true;
		}

		if ($halt && $return)
		{
			// display error message 
			Validation::getInstance()->set_error(__('Your ip address has access restriction to this site.'));
			$login_controller = new LoginController();
			$login_controller->message();
			exit;
		}

		return $return;
	}

	/**
	 * convert 001.2.03.040  to 1.2.3.40
	 * 
	 * @param type $ip
	 * @return type
	 */
	public static function str2ip($ip)
	{
		$arr_ip = explode('.', $ip);
		$return = array();
		foreach ($arr_ip as $ip_val)
		{
			$return[] = intval($ip_val);
		}

		while (count($return) < 4)
		{
			$return[] = 0;
		}

		if (count($return) > 4)
		{
			$return = array_slice($return, 0, 4);
		}

		return implode('.', $return);
	}

	/**
	 * convert 1.2.3.4 to 001.002.003.004 for storing in DB and comparing to range
	 * 
	 * @param string $ip
	 * @return string
	 */
	public static function ip2str($ip)
	{
		// first fix ip 
		$ip = self::str2ip($ip);

		$arr_ip = explode('.', $ip);
		$return = array();
		foreach ($arr_ip as $ip_val)
		{
			$return[] = sprintf("%03s", $ip_val);
		}
		return implode('.', $return);
	}

	/**
	 * add given ips to block list 
	 * 
	 * @param array $ips
	 * @return int number of inserted records
	 */
	public static function blockIps($ips)
	{
		$ips = Record::checkMakeArray($ips);

		$my_ip = Input::getInstance()->ip_address();
		$my_ip_str = IpBlock::ip2str($my_ip);

		$cnt = 0;
		foreach ($ips as $ip_range)
		{
			list($ip, $ip_end, ) = explode('-', $ip_range);
			$ip = trim($ip);
			$ip_end = trim($ip_end);
			if (strlen($ip))
			{
				// fix ip address
				$ip_str = IpBlock::ip2str($ip);
				if (strlen($ip_end))
				{
					$ip_end_str = IpBlock::ip2str($ip_end);
					// check if my ip between ip-ip_end
					if (strcmp($my_ip_str, $ip_str) > -1 && strcmp($my_ip_str, $ip_end_str) < 1)
					{
						//skip own ip 
						Validation::getInstance()->set_error(__('Cannot block own ip {name} between {name2}', array('{name}' => $my_ip, '{name2}' => $ip . '-' . $ip_end)));
						continue;
					}
				}
				else
				{
					$ip_end_str = '';
					if (strcmp($my_ip_str, $ip_str) == 0)
					{
						//skip own ip 
						Validation::getInstance()->set_error(__('Cannot block own ip {name}', array('{name}' => $my_ip)));
						continue;
					}
				}


				$sql = "INSERT IGNORE INTO " . IpBlock::tableNameFromClassName('IpBlock')
						. "(ip,ip_end,type,added_at,added_by) 
							VALUES (?,?,?,?,?)";
				IpBlock::query($sql, array($ip_str, $ip_end_str, self::TYPE_ACCESS, REQUEST_TIME, AuthUser::$user->id));

				$cnt++;
			}
		}

		return $cnt;
	}

	/**
	 * Check if login attemp should be limited for current user 
	 * 
	 * @param boolean $show_message
	 * @return boolean
	 */
	public static function loginAttemptIsBanned($show_message = true)
	{
		if (!self::loginAttemptIsEnabled())
		{
			return false;
		}

		// check current user ip 
		$return = false;

		// clear expired attempts
		self::clearExpired(self::TYPE_LOGIN);

		// get settings
		$ipblock_login_attempt_count = Config::option('ipblock_login_attempt_count');
		$ipblock_login_ban_period = Config::option('ipblock_login_ban_period');


		// my ip
		$ipblock = IpBlock::findOneFrom('IpBlock', 'type=? AND ip=?', array(self::TYPE_LOGIN, self::myIpStr()));

		if ($ipblock)
		{
			if ($ipblock->num >= $ipblock_login_attempt_count)
			{
				// baned for now
				$return = $ipblock;
			}
		}

		if ($show_message && $return)
		{
			// display error message 
			Validation::getInstance()->set_error(__('You have tried too many login attempts please try again after {num} minutes.', array('{num}' => $ipblock_login_ban_period)));
		}

		return $return;
	}

	/**
	 * increase login attempt for current ip 
	 */
	public static function loginAttemptCount($username = '')
	{
		// TODO log to seperate file last 500 records, store log in not accessibel form for direct requests (add exit at beginning )
		if (!self::loginAttemptIsEnabled())
		{
			return false;
		}

		$ipblock_login_attempt_count = Config::option('ipblock_login_attempt_count');
		$ip = Input::getInstance()->ip_address();
		$ip_str = self::myIpStr();

		// get login attempt count 
		$ipblock = IpBlock::findOneFrom('IpBlock', 'ip=? AND type=?', array($ip_str, self::TYPE_LOGIN));
		if ($ipblock)
		{
			$ipblock->num++;
			$ipblock->added_at = REQUEST_TIME;
			if ($ipblock->num >= $ipblock_login_attempt_count)
			{
				// log that login ban reached
				$log_item = new stdClass();
				$log_item->ip = $ip;
				$log_item->username = $username;
				$log_item->time = REQUEST_TIME;

				self::logRecord(self::TYPE_LOGIN, $log_item);
			}

			return $ipblock->save();
		}
		else
		{
			$sql = "INSERT INTO " . IpBlock::tableNameFromClassName('IpBlock')
					. "(ip,num,type,added_at,added_by) 
					VALUES (?,?,?,?,?)
					ON DUPLICATE KEY UPDATE num=num+1, added_at=?";

			return IpBlock::query($sql, array($ip_str, 1, self::TYPE_LOGIN, REQUEST_TIME, intval(AuthUser::$user->id), REQUEST_TIME));
		}
	}

	// TODO finish logging
	private static function logRecord($type, $item)
	{
		$data = self::logRead($type);
		$max_logs = 500;

		// append latest log 
		array_unshift($data, $item);

		// trim at max records
		if (count($data) > $max_logs)
		{
			$data = array_slice($data, 0, $max_logs);
		}

		return self::logWrite($type, $data);
	}

	public static function logRead($type)
	{
		$return = array();
		$file = UPLOAD_ROOT . '/data/ipblock_' . intval($type) . '.php';

		// read log file contents if exists
		if (is_file($file))
		{
			$logs = file_get_contents($file);
			list($exit, $data, ) = explode('<?php exit();?>', $logs);
			if (strlen($data))
			{
				$return = unserialize($data);
			}
		}

		return $return;
	}

	public static function logWrite($type, $data)
	{
		$file = UPLOAD_ROOT . '/data/ipblock_' . intval($type) . '.php';

		return FileDir::checkMakeFile($file, '<?php exit();?>' . serialize($data));
	}

	private static function myIpStr()
	{
		return IpBlock::ip2str(Input::getInstance()->ip_address());
	}

	public static function loginAttemptIsEnabled()
	{
		// get ban period from settings in minutes
		$ipblock_login_ban_period = Config::option('ipblock_login_ban_period');
		$ipblock_login_attempt_count = Config::option('ipblock_login_attempt_count');
		$ipblock_login_attempt_period = Config::option('ipblock_login_attempt_period');

		return ($ipblock_login_attempt_count > 0 && $ipblock_login_ban_period > 0 && $ipblock_login_attempt_period > 0);
	}

	/**
	 * On successful login delete any unsuccessful records
	 */
	public static function loginAttemptReset()
	{
		if (!self::loginAttemptIsEnabled())
		{
			return false;
		}

		return IpBlock::deleteWhere('IpBlock', 'ip=? AND type=?', array(self::myIpStr(), self::TYPE_LOGIN));
	}

	/**
	 * clear expired counts from db 
	 * 
	 * @param int $type
	 */
	public static function clearExpired($type = 'all')
	{
		if ($type === 'all')
		{
			self::clearExpired(self::TYPE_CONTACT);
			self::clearExpired(self::TYPE_LOGIN);
			return true;
		}

		switch ($type)
		{
			case self::TYPE_LOGIN:
				// get ban period from settings in minutes
				$ipblock_login_attempt_period = Config::option('ipblock_login_attempt_period');
				$ipblock_login_attempt_count = Config::option('ipblock_login_attempt_count');
				$ipblock_login_ban_period = Config::option('ipblock_login_ban_period');
				$values = array(
					self::TYPE_LOGIN,
					REQUEST_TIME - $ipblock_login_attempt_period * 60,
					$ipblock_login_attempt_count,
					REQUEST_TIME - $ipblock_login_ban_period * 60
				);
				return IpBlock::deleteWhere('IpBlock', 'type=? AND (added_at<? OR (num>=? AND added_at<?))', $values);
				break;
			case self::TYPE_CONTACT:
				// get ban period from settings in minutes
				$ipblock_contact_limit_period = Config::option('ipblock_contact_limit_period');
				$ipblock_contact_limit_count = Config::option('ipblock_contact_limit_count');
				$ipblock_contact_ban_period = Config::option('ipblock_contact_ban_period');
				$values = array(
					self::TYPE_CONTACT,
					REQUEST_TIME - $ipblock_contact_limit_period * 60,
					$ipblock_contact_limit_count,
					REQUEST_TIME - $ipblock_contact_ban_period * 60,
				);
				return IpBlock::deleteWhere('IpBlock', 'type=? AND (added_at<? OR (num>=? AND added_at<?))', $values);
				break;
			case self::TYPE_ACCESS:
				// access ban never expires, it is created and deleted by admin				
				break;
		}

		return false;
	}

	/**
	 * Check if post limiting / throttling enabled by values 
	 * 
	 * @return boolean
	 */
	public static function postLimitIsEnabled()
	{
		// get ban period from settings in minutes
		$ipblock_post_limit_period = Config::option('ipblock_post_limit_period');
		$ipblock_post_limit_count = Config::option('ipblock_post_limit_count');
		$ipblock_post_ban_period = Config::option('ipblock_post_ban_period');

		return ($ipblock_post_limit_period > 0 && $ipblock_post_limit_count > 0 && $ipblock_post_ban_period > 0);
	}

	public static function postLimitIsBanned($show_message = true)
	{
		if (!self::postLimitIsEnabled())
		{
			return false;
		}

		$return = false;

		$ipblock_post_limit_count = Config::option('ipblock_post_limit_count');
		// in minutes * 60 (convert to seconds)
		$ipblock_post_limit_period = Config::option('ipblock_post_limit_period') * 60;
		$ipblock_post_ban_period = Config::option('ipblock_post_ban_period') * 60;

		$ip = Input::getInstance()->ip_address();

		// check if current user logged in 
		if (AuthUser::isLoggedIn(false))
		{
			// use user id to check latest posted items
			$count = Ad::countFrom('Ad', 'added_by=? AND added_at>?', array(AuthUser::$user->id, REQUEST_TIME - $ipblock_post_limit_period));
			$where_last = "added_by=? ORDER BY added_at DESC";
			$values_last = array(AuthUser::$user->id);
		}
		else
		{
			// use ip to check latest posted items

			$count = Ad::countFrom('Ad', 'ip=? AND added_at>?', array($ip, REQUEST_TIME - $ipblock_post_limit_period));
			$where_last = "ip=? ORDER BY added_at DESC";
			$values_last = array($ip);
		}

		// check if reached limit
		$return = ($count >= $ipblock_post_limit_count);


		// write log if required
		if ($return)
		{
			$arr_log = self::logRead(self::TYPE_POST);
			$is_logged = false;

			// check if log with same user or ip exists and ban time not passed then do nothing
			if (AuthUser::isLoggedIn(false))
			{
				// check by user id 
				foreach ($arr_log as $log)
				{
					if ($log->user_id == AuthUser::$user->id && $log->time > REQUEST_TIME - $ipblock_post_ban_period)
					{
						// we already have logged this record. 
						$is_logged = true;
						break;
					}
				}
			}
			else
			{
				// use ip for checking 
				foreach ($arr_log as $log)
				{
					if ($log->user_id == 0 && $log->ip == $ip && $log->time > REQUEST_TIME - $ipblock_post_ban_period)
					{
						// we already have logged this record. 
						$is_logged = true;
						break;
					}
				}
			}


			if (!$is_logged)
			{
				// create new log for this post limiting
				$log_item = new stdClass();
				$log_item->user_id = AuthUser::isLoggedIn(false) ? AuthUser::$user->id : 0;
				$log_item->ip = $ip;
				$log_item->time = REQUEST_TIME;

				self::logRecord(self::TYPE_POST, $log_item);
			}
		}



		// display error message 
		if ($show_message && $return)
		{
			// get last post time 
			$last_item = Ad::findOneFrom('Ad', $where_last, $values_last, MAIN_DB, 'added_at');

			$can_post_time = $last_item->added_at + $ipblock_post_ban_period;
			$time_left = Config::timeRelative($can_post_time, 1, true);
			$period_time = Config::timeRelative(REQUEST_TIME + $ipblock_post_limit_period, 1, false);

			$message = __('You have posted {num} items in {time}. {time_left} till you can post new item again.', array(
				'{num}'			 => $count,
				'{time}'		 => $period_time,
				'{time_left}'	 => $time_left,
			));

			$message .= ' <a href="' . Language::urlHome() . '" class="button link"><i class="fa fa-arrow-left" aria-hidden="true"></i> '
					. __('Back to home page') . '</a>';

			Config::displayMessagePage($message);
		}

		return $return;
	}

	public static function contactLimitIsEnabled()
	{
		// get ban period from settings in minutes
		$ipblock_contact_limit_period = Config::option('ipblock_contact_limit_period');
		$ipblock_contact_limit_count = Config::option('ipblock_contact_limit_count');
		$ipblock_contact_ban_period = Config::option('ipblock_contact_ban_period');

		return ($ipblock_contact_limit_period > 0 && $ipblock_contact_limit_count > 0 && $ipblock_contact_ban_period > 0);
	}

	public static function contactLimitCount($log_item)
	{
		if (!self::contactLimitIsEnabled())
		{
			return false;
		}

		$ipblock_contact_limit_count = Config::option('ipblock_contact_limit_count');

		$ip = Input::getInstance()->ip_address();
		$ip_str = self::myIpStr();

		$ipblock = IpBlock::findOneFrom('IpBlock', 'ip=? AND type=?', array($ip_str, self::TYPE_CONTACT));


		if ($ipblock)
		{
			$ipblock->num++;
			$ipblock->added_at = REQUEST_TIME;
			if ($ipblock->num >= $ipblock_contact_limit_count)
			{
				$log_item->ip = $ip;
				$log_item->time = REQUEST_TIME;

				self::logRecord(self::TYPE_CONTACT, $log_item);
			}

			return $ipblock->save();
		}
		else
		{
			$sql = "INSERT INTO " . IpBlock::tableNameFromClassName('IpBlock')
					. "(ip,num,type,added_at,added_by) 
				VALUES (?,?,?,?,?)
				ON DUPLICATE KEY UPDATE num=num+1, added_at=?";

			return IpBlock::query($sql, array(
						$ip_str,
						1,
						self::TYPE_CONTACT,
						REQUEST_TIME,
						intval(AuthUser::$user->id),
						REQUEST_TIME
			));
		}
	}

	// TODO: do similar functions for coontat forms
	public static function contactLimitIsBanned($show_message = true)
	{
		if (!self::contactLimitIsEnabled())
		{
			return false;
		}

		// check current user ip 
		$return = false;

		// clear expired attempts
		self::clearExpired(self::TYPE_CONTACT);

		// get settings
		$ipblock_contact_limit_count = Config::option('ipblock_contact_limit_count');

		// my ip
		$ipblock = IpBlock::findOneFrom('IpBlock', 'type=? AND ip=?', array(self::TYPE_CONTACT, self::myIpStr()));

		if ($ipblock)
		{
			if ($ipblock->num >= $ipblock_contact_limit_count)
			{
				// baned for now
				$return = $ipblock;
			}
		}

		if ($show_message && $return)
		{
			// display error message 
			Validation::getInstance()->set_error(__('You have contacted too many times. Please slow down on contacting people.'));
		}

		return $return;
	}

	/**
	 * format custom value types in logs, add links if appliceble
	 * 
	 * @param array $item
	 * @param string $key
	 * @return string
	 */
	public static function formatValue($item, $key)
	{
		$value = View::escape($item->{$key});
		switch ($key)
		{
			case 'time':
				$value = Config::dateTime($value);
				break;
			case 'from_user_id':
				if (isset($item->from_User))
				{
					$value = '<a href="' . Language::get_url('admin/users/edit/' . $item->from_User->id . '/') . '" target="_blank">' . $value . '</a>';
				}
				break;
			case 'user_id':
				if (isset($item->User))
				{
					$value = '<a href="' . Language::get_url('admin/users/edit/' . $item->User->id . '/') . '" target="_blank">'
							. $value
							. ' <small class="muted">(' . User::getNameFromUserOrEmail($item->User) . ')</small>'
							. '</a>';
				}
				break;
			case 'ad_id':
				if (isset($item->Ad))
				{
					$value = '<a href="' . Ad::url($item->Ad) . '" target="_blank">' . $value . '</a>';
				}
				elseif (intval($value) < 1)
				{
					$value = __('contact us form');
				}
				break;
		}

		return $value;
	}

}
