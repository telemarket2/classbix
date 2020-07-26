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
 * class User
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class User extends Record
{

	const TABLE_NAME = 'user';
	const PERMISSION_ADMIN = 1;
	const PERMISSION_MODERATOR = 2;
	const PERMISSION_USER = 3;
	const PERMISSION_DEALER = 4;
	const FOLDER_LOGO = 'logo';

	private static $cols = array(
		'id'			 => 1,
		'name'			 => 1,
		'email'			 => 1,
		'password'		 => 1,
		'username'		 => 1,
		'level'			 => 1,
		'pending_level'	 => 1,
		'enabled'		 => 1,
		'ip'			 => 1,
		'activation'	 => 1,
		'web'			 => 1, // dealer website
		'info'			 => 1, // address, opening hours, phones...
		'logo'			 => 1, // dealer logo 
		'logged_at'		 => 1,
		'added_at'		 => 1,
		'added_by'		 => 1
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	function beforeInsert()
	{
		// create username if not set
		if (!strlen($this->name))
		{
			$this->name = self::getNameFromEmail($this->email);
		}

		// lowercase email for consistency 
		if (isset($this->email))
		{
			$this->email = strtolower($this->email);
		}

		// create acutivation code if not preset to 0
		if (!isset($this->activation))
		{
			$this->activation = self::genActivationCode();
		}

		$this->added_at = REQUEST_TIME;
		$this->added_by = AuthUser::$user->id;
		if (isset($this->ip))
		{
			$this->ip = Input::getInstance()->ip_address();
		}

		return true;
	}

	function beforeUpdate()
	{
		// create name if not set
		if (!strlen($this->name))
		{
			$this->name = self::getNameFromEmail($this->email);
		}

		// lowercase email for consistency 
		if (isset($this->email))
		{
			$this->email = strtolower($this->email);
		}

		if ($this->logo)
		{
			// get old logo value from db and delete it 
			$old_user = User::findByIdFrom('User', $this->id);
			if ($old_user->logo && $old_user->logo != $this->logo)
			{
				// delete old logo file
				Adpics::deleteImage($old_user->logo, User::FOLDER_LOGO);
			}
		}

		return true;
	}

	function beforeDelete()
	{
		// delete logo of dealer. image only, do not update database 	
		$this->deleteLogo();

		// delete slug 
		Permalink::deleteWhere('Permalink', 'item_id=? AND item_type=?', array($this->id, Permalink::ITEM_TYPE_USER));

		// remove relation of user to old ads. They will be related again if registered with same email
		Ad::associateAdsToUser($this, false);

		return true;
	}

	function afterDelete()
	{
		// clear cache 
		SimpleCache::delete('user');

		return true;
	}

	function deleteLogo($update_db = false)
	{
		if ($this->logo)
		{
			$return = Adpics::deleteImage($this->logo, User::FOLDER_LOGO);
			if ($update_db)
			{
				// delete record from db as well. 
				// if not set then probably user is being deleted by parent process
				User::update('User', array('logo' => ''), 'id=?', array($this->id));
			}

			return $return;
		}
		return true;
	}

	function afterInsert()
	{
		// save slug 
		if ($this->id && !Permalink::savePermalinkWithObject($this, Permalink::ITEM_TYPE_USER, $this->name, 'username'))
		{
			// error saving permalink
			return false;
		}

		return true;
	}

	function afterUpdate()
	{
		// save slug 
		if (!Permalink::savePermalinkWithObject($this, Permalink::ITEM_TYPE_USER, $this->name, 'username'))
		{
			// error saving permalink
			return false;
		}

		return true;
	}

	public static function genActivationCode($pattern = 'a{code}n')
	{
		return str_replace('{code}', dechex(rand(100000000, intval(4294967295))), $pattern);
	}

	static function gravatar($email, $size = "35", $type = 'identicon')
	{
		return 'http://www.gravatar.com/avatar/' . md5($email) . '.jpg?s=' . $size . '&amp;d=' . $type;
	}

	static function profileLink($id)
	{
		return Language::get_url('profile/' . $id . '/');
	}

	static function findBy($field, $value)
	{
		return self::findByIdFrom('User', $value, $field, MAIN_DB);
	}

	public static function checkUserPassword($pwd)
	{
		if (!strlen($pwd))
		{
			return false;
		}

		// check if user password is right
		$user = User::findOneFrom('User', 'id=?', array(AuthUser::$user->id), MAIN_DB, 'password');

		return ($user && $user->password === md5($pwd));
	}

	static function deleteUserAccount($id)
	{

		$id = (int) $id;

		$user = User::findBy('id', $id);

		if (!$user)
		{
			return false;
		}

		$user->delete();

		return true;
	}

	public static function clearNotActivated()
	{
		$days = intval(Config::option('ads_verification_days'));
		if ($days < 1)
		{
			$days = 7;
		}

		$expire_time = REQUEST_TIME - $days * 3600 * 24; //delete not activated accounts in given period
		// delete in loop to remove images as well
		$users = self::findAllFrom('User', "activation<>'0' AND added_at<? ORDER BY added_at ASC LIMIT 100", array($expire_time));
		foreach ($users as $user)
		{
			$user->delete();
		}

		return true;
	}

	function _validate_user_email($str, $user_id = 0)
	{
		// check if this email address is not used before 
		// if it is not email address of current user
		$user = self::findByIdFrom('User', $str, 'email', MAIN_DB);
		if ($user)
		{
			// check if not current users email adress
			if ($user->id != $user_id)
			{
				// this email address is not unique

				$validation = Validation::getInstance();

				// check if user pending activation
				if ($user->activation === '0')
				{
					// this email is registered and used 
					$validation->set_message('_validate_user_email', __('%s is already registered.'));
				}
				else
				{
					$validation->set_message('_validate_user_email', __('%s is already registered but not verified yet. Please check your email address for verification. If you did not receive verification email then click <a href="{url}">here</a> to receive new one.', array('{url}' => User::urlResendVerification($user))));
				}

				return false;
			}
		}


		return true;
	}

	public static function activateUser($user)
	{
		if ($user)
		{
			$user->activation = '0';
			return User::update('User', array('activation' => '0'), 'id=?', array($user->id));
		}
		return false;
	}

	public static function urlVerifyEmail($user)
	{
		return Language::get_url('login/activate/' . $user->id . '/' . $user->activation . '/');
	}

	public static function urlResendVerification($user)
	{
		return Language::get_url('login/resendActivation/' . $user->id . '/' . md5($user->email) . '/');
	}

	public static function isActivated($user)
	{
		return (isset($user->activation) && $user->activation == '0') ? true : false;
	}

	public static function getLevel($level)
	{
		$levels = self::levels();
		return $levels[$level];
	}

	public static function levels()
	{
		return array(
			1	 => __('Admin'),
			2	 => __('Moderator'),
			3	 => __('User'),
			4	 => __('Dealer')
		);
	}

	/**
	 * count activated users by level 
	 */
	public static function countByLevel($level = null)
	{
		$where = '';
		$values = array();

		if ($level)
		{
			$where = 'level=? AND ';
			$values[] = $level;
		}

		$sql = "SELECT level,COUNT(level) as level_cnt 
			FROM " . User::tableNameFromClassName('User') . "
				WHERE " . $where . " activation='0' AND enabled='1'
				GROUP BY level";

		return self::query($sql, $values);
	}

	/**
	 * Upload and save dealer logo 
	 * 
	 * @param User $user
	 * @param string $field
	 * @return boolean
	 */
	public static function uploadLogo($user, $field = 'logo')
	{
		$error = (!isset($_FILES[$field]['error'])) ? 4 : $_FILES[$field]['error'];
		if ($error != 4)
		{
			// upload logo and crop to defined size
			$img = Adpics::upload($field, User::FOLDER_LOGO, array('use_random_folder' => true));
			if ($img)
			{
				// image uploaded then save it 
				$user->logo = $img;
			}
			else
			{
				Validation::getInstance()->set_error(Adpics::getUploadErrors());
				return false;
			}
		}

		return true;
	}

	/**
	 * Resize and return logo 
	 * 
	 * @param User $user
	 * @param string $size WxHxCrop, default null will resize to user defined logo size from settings with white backfill, 
	 * 	if empty will return original image without cache or resize
	 * @param boolean|string $placeholder false|true|lazy 
	 * @return string
	 */
	public static function logo($user, $size = null, $placeholder = false)
	{
		$width = '';
		$height = '';
		$crop = 2;

		if (is_null($size))
		{
			// set default logo size 
			$size = Config::option('dealer_logo_width') . 'x' . Config::option('dealer_logo_height') . 'x2';
		}

		if (strlen($size))
		{
			list($width, $height, $crop) = explode('x', $size);
		}

		if ($user->logo)
		{
			$_img = User::FOLDER_LOGO . '/' . $user->logo;
			if ($width || $height)
			{
				if ($placeholder === 'lazy')
				{
					// prepare lazy url before checking file existence
					$options_lazy = array(
						'type'	 => Adpics::LAZY_URL_TYPE_USER_ONLY,
						'id'	 => $user->id,
						'ad_id'	 => $user->added_at,
						'width'	 => $width,
						'height' => $height,
						'crop'	 => $crop,
						'thumb'	 => 1
					);
					$lazy_url_var = Adpics::lazyUrlVar($options_lazy);

					// return lazy resize url 
					return Adpics::resizeImageLazy($_img, $width, $height, intval($crop), $lazy_url_var);
				}
				else
				{
					// return resized logo 
					return Adpics::resizeImageCache($_img, $width, $height, intval($crop), true);
				}
			}
			else
			{
				// return original logo
				return UPLOAD_URL . '/' . $_img;
			}
		}



		// resize placeholder if no image returned already
		if ($placeholder && $placeholder !== 'lazy')
		{
			return Adpics::imgPlaceholder($width, $height);
		}


		return false;
	}

	public static function nextAutoId()
	{
		$latest_cat = User::findOneFrom('User', '1=1 ORDER BY id desc', array(), MAIN_DB, 'id');
		return $latest_cat->id + 1;
	}

	public static function url($user = null, $page = null)
	{
		return Location::url(null, null, $user, $page);
	}

	/**
	 * Aappend ad count to User object(s)->countAds
	 * 
	 * @param User|array $users
	 * @param bool $type true:count listed ads only; false:count not listed ads; null:all ads
	 */
	public static function appendAdCount($users, $type = 'listed')
	{
		if (!$users)
		{
			return;
		}

		$users = Record::checkMakeArray($users);

		// append detailed counts by type 
		// later use in loop to get specific value 
		User::countAdType($users);

		// assign to ->countAds variable requested type only
		// this variable used in themes so we need to do it 
		foreach ($users as $user)
		{
			$user->countAds = User::countAdType($user, $type);
		}
	}

	/**
	 * Count user ads by type. get all counts and store in cache if requesting for single user
	 * 
	 * @param type $users
	 * @param string $type
	 * @return int
	 */
	static public function countAdType($users, $type = null)
	{
		if (!$users)
		{
			return;
		}

		$users = Record::checkMakeArray($users);
		$count_users = count($users);

		// now filter only userw with no ->countedAdType
		$arr_users_process = array();
		foreach ($users as $user)
		{
			if (!isset($user->countedAdType))
			{
				$arr_users_process[] = $user;
			}
		}

		$count_arr_users_process = count($arr_users_process);

		// current time for counting expired items. rounded for query caching
		$time = Config::roundTime();

		if ($count_arr_users_process === 1)
		{
			// single user use optimized query 
			$user = reset($users);

			$cache_key = User::keyCacheCount($user->id);
			$return = SimpleCache::get($cache_key);
			if ($return === false)
			{
				/* SELECT 
				  count(enabled='0' OR NULL) as enabled0,
				  count(enabled='1' OR NULL) as enabled1,
				  count(enabled='2' OR NULL) as enabled2,
				  count(enabled='3' OR NULL) as enabled3,
				  count(enabled='4' OR NULL) as enabled4,
				  count(enabled='5' OR NULL) as enabled5,
				  count(enabled='6' OR NULL) as enabled6,
				  count(enabled='7' OR NULL) as enabled7,
				  count(listed='0' OR NULL) as not_listed,
				  count(listed='1' OR NULL) as listed,
				  count(expireson>'123' OR NULL) as not_expired,
				  count(expireson<='123' OR NULL) as expired,
				  count(requires_posting_payment='1' OR NULL) as requires_posting_payment
				  FROM cb_ad WHERE added_by='1545';
				 */

				$sql = "SELECT 
				count(enabled='" . Ad::STATUS_PENDING_APPROVAL . "' OR NULL) as enabled" . Ad::STATUS_PENDING_APPROVAL . ",
				count(enabled='" . Ad::STATUS_ENABLED . "' OR NULL) as enabled" . Ad::STATUS_ENABLED . ",
				count(enabled='" . Ad::STATUS_PAUSED . "' OR NULL) as enabled" . Ad::STATUS_PAUSED . ",
				count(enabled='" . Ad::STATUS_COMPLETED . "' OR NULL) as enabled" . Ad::STATUS_COMPLETED . ",
				count(enabled='" . Ad::STATUS_INCOMPLETE . "' OR NULL) as enabled" . Ad::STATUS_INCOMPLETE . ",
				count(enabled='" . Ad::STATUS_DUPLICATE . "' OR NULL) as enabled" . Ad::STATUS_DUPLICATE . ",
				count(enabled='" . Ad::STATUS_BANNED . "' OR NULL) as enabled" . Ad::STATUS_BANNED . ",
				count(enabled='" . Ad::STATUS_TRASH . "' OR NULL) as enabled" . Ad::STATUS_TRASH . ",
				count(listed='0' OR NULL) as not_listed,
				count(listed='1' OR NULL) as listed,
				count(expireson>? OR NULL) as not_expired,
				count(expireson<=? OR NULL) as expired,
				count(requires_posting_payment='1' OR NULL) as requires_posting_payment
				FROM " . Ad::tableNameFromClassName('Ad') . " WHERE added_by=?";

				$return = Record::query($sql, array($time, $time, $user->id));
				$return = $return[0];

				$return->total_all = $return->listed + $return->not_listed;
				$return->total = $return->total_all - $return->{'enabled' . Ad::STATUS_TRASH};

				// save result for 10 minutes
				$return = serialize($return);
				SimpleCache::set($cache_key, $return, 600);
			}
			$user->countedAdType = @unserialize($return);
		}
		elseif ($count_arr_users_process > 0)
		{
			// get count for all given users. use group by 
			// cache is not used here because we are requesting multiple users only from admin panel by mod
			// get ids
			$ids = array();
			foreach ($arr_users_process as $user)
			{
				$ids[] = $user->id;
			}
			$_ids = Ad::quoteArray($ids);
			/* SELECT 
			  added_by,
			  count(enabled='0' OR NULL) as enabled0,
			  count(enabled='1' OR NULL) as enabled1,
			  count(enabled='2' OR NULL) as enabled2,
			  count(enabled='3' OR NULL) as enabled3,
			  count(enabled='4' OR NULL) as enabled4,
			  count(enabled='5' OR NULL) as enabled5,
			  count(enabled='6' OR NULL) as enabled6,
			  count(enabled='7' OR NULL) as enabled7,
			  count(listed='0' OR NULL) as not_listed,
			  count(listed='1' OR NULL) as listed,
			  count(expireson>'123' OR NULL) as not_expired,
			  count(expireson<='123' OR NULL) as expired,
			  count(requires_posting_payment='1' OR NULL) as requires_posting_payment
			  FROM cb_ad
			  WHERE added_by IN ('1545','10534')
			  GROUP BY added_by;
			 */

			$sql = "SELECT 
				added_by,
				count(enabled='" . Ad::STATUS_PENDING_APPROVAL . "' OR NULL) as enabled" . Ad::STATUS_PENDING_APPROVAL . ",
				count(enabled='" . Ad::STATUS_ENABLED . "' OR NULL) as enabled" . Ad::STATUS_ENABLED . ",
				count(enabled='" . Ad::STATUS_PAUSED . "' OR NULL) as enabled" . Ad::STATUS_PAUSED . ",
				count(enabled='" . Ad::STATUS_COMPLETED . "' OR NULL) as enabled" . Ad::STATUS_COMPLETED . ",
				count(enabled='" . Ad::STATUS_INCOMPLETE . "' OR NULL) as enabled" . Ad::STATUS_INCOMPLETE . ",
				count(enabled='" . Ad::STATUS_DUPLICATE . "' OR NULL) as enabled" . Ad::STATUS_DUPLICATE . ",
				count(enabled='" . Ad::STATUS_BANNED . "' OR NULL) as enabled" . Ad::STATUS_BANNED . ",
				count(enabled='" . Ad::STATUS_TRASH . "' OR NULL) as enabled" . Ad::STATUS_TRASH . ",
				count(listed='0' OR NULL) as not_listed,
				count(listed='1' OR NULL) as listed,
				count(expireson>? OR NULL) as not_expired,
				count(expireson<=? OR NULL) as expired,
				count(requires_posting_payment='1' OR NULL) as requires_posting_payment
				FROM " . Ad::tableNameFromClassName('Ad') . " 
				WHERE added_by IN (" . implode(",", $_ids) . ") 
				GROUP BY added_by";

			$return = Record::query($sql, array($time, $time));

			$return_ = array();
			foreach ($return as $r)
			{
				// calculate total
				$r->total_all = $r->listed + $r->not_listed;
				$r->total = $r->total_all - $r->{'enabled' . Ad::STATUS_TRASH};


				$return_[$r->added_by] = $r;
			}

			foreach ($arr_users_process as $user)
			{
				if (isset($return_[$user->id]))
				{
					$user->countedAdType = $return_[$user->id];
				}
				else
				{
					// set empty class
					$user->countedAdType = new stdClass();
				}
			}
		}

		// return only if one user requested
		if ($count_users === 1 && $user)
		{
			if (!is_null($type))
			{
				// return particular count
				return intval($user->countedAdType->{$type});
			}

			return $user->countedAdType;
		}
	}

	/**
	 * Generate clickable buttons with ad counts by type for given user
	 * 
	 * @param User $user
	 * @return string
	 */
	static public function countAdTypeButtons($user)
	{
		$return = '';
		$total_ads = User::countAdType($user, 'total_all');
		if ($total_ads)
		{


			$arr_counts = array(
				'all'		 => array(
					'title'	 => __('All'),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id),
					'total'	 => $total_ads
				),
				'listed'	 => array(
					'title'	 => __('Running'),
					/* 'url'	 => User::url($user), */
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=_r'),
					'total'	 => User::countAdType($user, 'listed')
				),
				'expired'	 => array(
					'title'	 => __('Expired'),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=_ex'),
					'total'	 => User::countAdType($user, 'expired')
				),
				'pending'	 => array(
					'title'	 => Ad::statusName(Ad::STATUS_PENDING_APPROVAL),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=' . Ad::STATUS_PENDING_APPROVAL),
					'total'	 => User::countAdType($user, 'enabled' . Ad::STATUS_PENDING_APPROVAL)
				),
				'payment'	 => array(
					'title'	 => __('Payment required'),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&payment=1'),
					'total'	 => User::countAdType($user, 'requires_posting_payment')
				),
				'incomplete' => array(
					'title'	 => Ad::statusName(Ad::STATUS_INCOMPLETE),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=' . Ad::STATUS_INCOMPLETE),
					'total'	 => User::countAdType($user, 'enabled' . Ad::STATUS_INCOMPLETE)
				),
				'paused'	 => array(
					'title'	 => Ad::statusName(Ad::STATUS_PAUSED),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=' . Ad::STATUS_PAUSED),
					'total'	 => User::countAdType($user, 'enabled' . Ad::STATUS_PAUSED)
				),
				'completed'	 => array(
					'title'	 => Ad::statusName(Ad::STATUS_COMPLETED),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=' . Ad::STATUS_COMPLETED),
					'total'	 => User::countAdType($user, 'enabled' . Ad::STATUS_COMPLETED)
				),
				'duplicate'	 => array(
					'title'	 => Ad::statusName(Ad::STATUS_DUPLICATE),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=' . Ad::STATUS_DUPLICATE),
					'total'	 => User::countAdType($user, 'enabled' . Ad::STATUS_DUPLICATE)
				),
				'banned'	 => array(
					'title'	 => Ad::statusName(Ad::STATUS_BANNED),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=' . Ad::STATUS_BANNED),
					'total'	 => User::countAdType($user, 'enabled' . Ad::STATUS_BANNED)
				),
				'trash'		 => array(
					'title'	 => Ad::statusName(Ad::STATUS_TRASH),
					'url'	 => Language::get_url('admin/items/?added_by=' . $user->id . '&enabled=' . Ad::STATUS_TRASH),
					'total'	 => User::countAdType($user, 'enabled' . Ad::STATUS_TRASH)
				),
			);

			$arr_echo = array();
			foreach ($arr_counts as $k => $v)
			{
				if ($v['total'])
				{
					$arr_echo[$k] = '<a href="' . $v['url'] . '" class="button small">' . $v['title'] . ' ' . $v['total'] . '</a> ';
				}
			}

			if (count($arr_echo) === 2)
			{
				// total and one field existst then do not show first total count 
				unset($arr_echo['all']);
			}
			$return = implode(' ', $arr_echo);
		}

		return $return;
	}

	/**
	 * delete cached counts 
	 * 
	 * @param int|array $user_ids
	 */
	static public function countAdTypeClearCache($user_ids)
	{
		$user_ids = Record::checkMakeArray($user_ids);
		foreach ($user_ids as $id)
		{
			SimpleCache::delete(User::keyCacheCount($id));
		}
	}

	/**
	 * delete cached counts by given ad_ids
	 * 
	 * @param int|array $user_ids
	 */
	static public function countAdTypeClearCacheByAdId($ids)
	{
		// get user ids
		$ids = Record::checkMakeArray($ids);
		$ids_ = Record::quoteArray($ids);
		$user_ids = Ad::findAllFrom('Ad', 'id IN (' . implode(',', $ids_) . ') GROUP BY added_by', array(), MAIN_DB, 'id,added_by');

		$user_ids_keys = array();
		foreach ($user_ids as $val)
		{
			if ($val->added_by)
			{
				$user_ids_keys[$val->added_by] = $val->added_by;
			}
		}
		if ($user_ids_keys)
		{
			User::countAdTypeClearCache(array_keys($user_ids_keys));
		}
	}

	/**
	 * Cache key for ad counts 
	 *  
	 * @param int $id
	 * @return string
	 */
	static public function keyCacheCount($id)
	{
		return 'ad_count_user.' . intval($id);
	}

	/**
	 *  check if given user can be upgraded to dealer account 
	 * 
	 * @param User $user
	 * @return bool
	 */
	public static function canUpgradeToDealer($user)
	{
		return ($user->level == User::PERMISSION_USER && Config::option('account_dealer_move_from_user') && Config::option('account_dealer'));
	}

	/**
	 * check if user can display dealer info on ad page 
	 * 
	 * @param User $user
	 * @return bool
	 */
	public static function canDisplayDealerInfoOnAdPage($user)
	{
		return (User::isLive($user) && $user->level == User::PERMISSION_DEALER && Config::option('account_dealer_display_info_ad_page'));
	}

	/**
	 *  check if current user can edit given level. 
	 * admin can edit everyone
	 * moderator can edit only dealer and user
	 * to check if this user moderator and not admin then pass level as User::PERMISSION_MODERATOR
	 * 
	 * @param int $level
	 * @return bool
	 */
	public static function canEditModerator($level = null)
	{
		if (AuthUser::hasPermission(User::PERMISSION_ADMIN))
		{
			return true;
		}
		else
		{
			// not admin 
			// check if moderator
			if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
			{
				// moderator cannot edit admin or other moderators
				if ($level == User::PERMISSION_ADMIN || $level == User::PERMISSION_MODERATOR)
				{
					return false;
				}
				else
				{
					// can edit dealer and users
					return true;
				}
			}
		}

		// not admin or moderator
		return false;
	}

	/**
	 * find user by email, create new activated user if not found
	 * 
	 * @param string $email
	 * @return User
	 */
	public static function checkMakeByEmail($email, $enabled = true, $activated = false, $ip = null)
	{
		$email = strtolower($email);

		$user = User::findBy('email', $email);

		if (!$user)
		{
			// no user then create activated user
			$new_password = User::genActivationCode('p{code}d');

			$user = new User();
			$user->email = $email;
			$user->password = md5($new_password);
			$user->password_raw = $new_password;
			$user->enabled = $enabled ? 1 : 0;
			if ($activated)
			{
				// make activated, verified
				// if not set then will generate activation code beforeInsert
				$user->activation = 0;
			}
			$user->ip = $ip;
			$user->level = Config::option('default_user_permission');
			$user->save();
		}

		return $user;
	}

	public static function getNameFromUserOrEmail($user = null, $email = null, $default = 'user')
	{
		if (isset($user))
		{
			if (strlen($user->name))
			{
				return $user->name;
			}
			elseif (strlen($user->email))
			{
				return self::getNameFromEmail($user->email, $default);
			}
		}

		return self::getNameFromEmail($email, $default);
	}

	public static function getNameFromEmail($email = '', $name = 'user')
	{
		if (strlen($email))
		{
			list($name, ) = explode('@', $email);
		}
		return $name;
	}

	public static function isEnabledMessage($user)
	{
		if ($user->enabled == 0)
		{
			// if not approved send message to admin 
			MailTemplate::sendPendingApproval();

			// ad needs to be enabled by admin
			return __('Account will be available after approval by site administration.');
		}
		else
		{
			return __('Please <a href="{url}">login</a> to start using your account.', array(
				'{url}' => Language::get_url('login/')
			));
		}
	}

	/**
	 * Check if user is enambled and activated 
	 * 
	 * @param User $user
	 * @return bolean
	 */
	public static function isLive($user)
	{
		return ($user->activation == 0 && $user->enabled == 1);
	}

	/**
	 * Search users name,email, username for given query 
	 * 
	 * @param string $q
	 * @param int $limit
	 * @return array of Category object
	 */
	public static function search($q, $limit = 5)
	{
		// search given query in category name and description 
		$whereA = array();
		$whereB = array();
		$users = array();

		$arr_q = Ad::searchQuery2Array($q);
		foreach ($arr_q as $_q)
		{
			$whereA[] = '(u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';
			$whereB[] = '%' . $_q . '%';
			$whereB[] = '%' . $_q . '%';
			$whereB[] = '%' . $_q . '%';
		}


		if ($whereA)
		{
			$limit = intval($limit);
			$limit_sql = '';
			if ($limit > 0)
			{
				$limit_sql = " LIMIT " . $limit;
			}

			$whereA[] = 'u.enabled=? AND u.activation=?';
			$whereB[] = '1';
			$whereB[] = '0';

			$whereA[] = 'a.listed=?';
			$whereB[] = '1';

			$sql = "SELECT u.id, u.name, u.email, u.username  
					FROM " . User::tableNameFromClassName('User') . " u 
					LEFT JOIN  " . Ad::tableNameFromClassName('Ad') . " a on u.id=a.added_by
				  WHERE {where} 
				  GROUP BY u.id
				  " . $limit_sql;

			$sql = str_replace('{where}', implode(' AND ', $whereA), $sql);

			$users = User::query($sql, $whereB);
		}

		return $users;
	}

	static public function karma($user)
	{
		/**
		 * FORMULA:
		  karma = $good * 100/ ($good + $bad)
		 */
		$listed = User::countAdType($user, 'listed');
		$banned = User::countAdType($user, "enabled" . Ad::STATUS_BANNED);
		$duplicate = User::countAdType($user, "enabled" . Ad::STATUS_DUPLICATE);
		$incomplete = User::countAdType($user, "enabled" . Ad::STATUS_INCOMPLETE);
		$completed = User::countAdType($user, "enabled" . Ad::STATUS_COMPLETED);
		$trash = User::countAdType($user, "enabled" . Ad::STATUS_TRASH);
		$pending = User::countAdType($user, "enabled" . Ad::STATUS_PENDING_APPROVAL);
		$expired = User::countAdType($user, "expired");
		$requires_posting_payment = User::countAdType($user, "requires_posting_payment");

		$good = $listed + 0.1 * ($expired + $completed);
		// if has banned then make karma lower than 50%. so bad will be minimum equal to good.
		$bad = ($banned + $duplicate) ? $good * ($banned + $duplicate) : 0;
		$bad += 0.5 * ($incomplete + $requires_posting_payment + $pending) + 0.1 * $trash;


		if ($good > 3)
		{
			$max = 100;
		}
		else
		{
			// not enaugh live adas for good karma. so cap at 50
			$max = 50;
		}

		$return = 0;
		if ($good > 0)
		{
			// return good percentage 
			$return = round(($good * $max) / ($good + $bad));
		}

		return $return;
	}

	/**
	 * Remove sensitive info, password from users
	 * remove private values from object 
	 * 
	 * @param type $users
	 */
	static public function cleanUserData($users)
	{
		if (is_array($users))
		{
			// loop and remove password
			$new_users = array();
			foreach ($users as $k => $u)
			{
				$new_users[$k] = User::cleanUserData($u);
			}
		}
		else
		{
			if (isset($users->password))
			{
				unset($users->password);
			}
			$new_users = Record::cleanObject($users);
		}

		return $new_users;
	}

	/**
	 * Check if ad_moderation_limit_posting is set and items pending moderation reached that limit for current logged in user
	 * Users should wait before posting new item until old items moderated by admin. 
	 * Admin will see few ads and user will slow down and not overload moderators. 
	 * Moderators not checked in this limit 
	 * 
	 * @return boolean
	 */
	public static function isModerationLimitReached($show_message = true)
	{
		$return = false;

		// check if current user reached pending ads limit. 
		$user_id = intval(AuthUser::$user->id);
		$ad_moderation_limit_posting = intval(Config::option('ad_moderation_limit_posting'));

		if ($user_id && $ad_moderation_limit_posting && !AuthUser::hasPermission(User::PERMISSION_MODERATOR))
		{
			// count pending items by user
			$count_pending = Ad::countFrom('Ad', "added_by=? AND enabled=?", array($user_id, Ad::STATUS_PENDING_APPROVAL));
			if ($count_pending >= $ad_moderation_limit_posting)
			{
				$return = true;
			}
		}


		// display error message 
		if ($show_message && $return)
		{
			$message = __('Please wait while your {num} items are moderated.', array(
				'{num}' => $count_pending
			));

			$message .= ' <a href="' . Language::urlHome() . '" class="button link"><i class="fa fa-arrow-left" aria-hidden="true"></i> '
					. __('Back to home page') . '</a>';

			Config::displayMessagePage($message);
		}

		return $return;
	}

}
