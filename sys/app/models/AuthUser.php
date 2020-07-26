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
 * Class AuthUser
 *
 * All informations of the logged in user, plug all method for login, login
 * and permissions
 */
class AuthUser
{

	const SESSION_KEY = 'veppa_scs_auth_user';
	const COOKIE_KEY = 'cb_u';
	const ALLOW_LOGIN_WITH_USERNAME = false;
	// const COOKIE_LIFE = 1209600; // 2 weeks	
	const COOKIE_LIFE = 86400; // 1 day
	const DELAY_ON_INVALID_LOGIN = true;

	static protected $is_logged_in = false;
	static protected $loaded = false;
	static public $user = false;

	public static function load()
	{
		if (!self::$loaded)
		{
			self::$loaded = true;

			if (isset($_COOKIE[self::COOKIE_KEY]))
			{
				$cookie = Flash::getCookie(self::COOKIE_KEY);
				$user = self::challengeCookie($cookie);
			}
			else
			{
				return false;
			}

			if (!$user)
			{
				return self::logout();
			}

			self::setInfos($user);
		}
	}

	public static function setInfos(Record $user)
	{
		self::$user = $user;
		self::$is_logged_in = true;
	}

	public static function isLoggedIn($redirect = true)
	{

		self::load();

		if ($redirect && !self::$is_logged_in)
		{
			//exit('self::$is_logged_in:'.self::$is_logged_in);
			$rd = self::getRdUrl();
			redirect(Language::get_url('login/' . $rd));
		}
		return self::$is_logged_in;
	}

	static function getRdUrl()
	{
		$current_url = Language::getCurrentUrl(true);
		$url_vars = Language::get2url($_GET);
		if ($url_vars)
		{
			$url_vars = '?' . $url_vars;
		}
		$rd = '?rd=' . urlencode($current_url . $url_vars);

		//$rd = '?rd=' . urlencode(URL_PROTOCOL . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);

		/*
		  if($_SERVER['SERVER_PORT']!='80')
		  {
		  $port = ':'.$_SERVER['SERVER_PORT'];
		  }

		  $rd = ($_SERVER['QUERY_STRING']==NULL) ?
		  '?rd='.urlencode('http://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['SCRIPT_NAME']) :
		  '?rd='.urlencode('http://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING']);
		 */
		return $rd;
	}

	/**
	 * Checks if user has (one of) the required permissions.
	 *
	 * @param string $permission Can contain a single permission or comma seperated list of permissions.
	 * @param int $user_id false
	 * @param bool $redirect false
	 * @return boolean
	 */
	public static function hasPermission($permission, $user_id = false, $redirect = false)
	{
		self::load();
		// 
		$return = false;

		if (/* self::isLoggedIn($redirect) */true)
		{
			switch ($permission)
			{
				case User::PERMISSION_ADMIN:
				case 'admin':
					$return = (self::$user->level == User::PERMISSION_ADMIN);
					break;
				case User::PERMISSION_MODERATOR:
				case 'moderator':
					// admin or moderator
					$return = (self::$user->level == User::PERMISSION_MODERATOR);


					// check if it is admin then grant permission
					if (!$return)
					{
						return self::hasPermission(User::PERMISSION_ADMIN, $user_id, $redirect);
					}
					break;
				case User::PERMISSION_USER:
				case User::PERMISSION_DEALER:
				case 'dealer':
				case 'user':
					// moderator and admin can be a user
					$return = (self::$user->level == User::PERMISSION_USER || self::$user->level == User::PERMISSION_DEALER);

					if ($return)
					{
						if ($user_id !== false)
						{
							// check if current user is correct if user_id is passed
							// if passed user_id=0 then self::$user->id will never be 0 so it is safe to use this condition
							$return = (self::$user->id == $user_id);
						}
					}

					// check if it is moderator, then grant permission
					if (!$return)
					{
						$return = self::hasPermission(User::PERMISSION_MODERATOR, $user_id, $redirect);
					}
					break;
			}
		}

		if (!$return && $redirect)
		{
			redirect(Language::get_url('login/noPermission/'));
		}

		return $return;
	}

	public static function login($username, $password, $set_cookie = false)
	{
		self::logout();
		// always use cookie 
		$set_cookie = true;

		$user = User::findBy('email', $username);

		if (!$user instanceof User && self::ALLOW_LOGIN_WITH_USERNAME)
		{
			$user = User::findBy('username', $username);
		}

		if ($user instanceof User && $user->password == md5($password))
		{
			$user->logged_at = REQUEST_TIME;
			$user->ip = Input::getInstance()->ip_address();
			$user->save();

			if ($set_cookie)
			{
				self::setSession($user);
			}

			self::setInfos($user);

			// user logged in regenerate session id 
			// session_regenerate_id();

			return true;
		}
		else
		{
			if (self::DELAY_ON_INVALID_LOGIN)
			{
				$invalid_login_key = '_il';
				$invalid_login_count = intval(Flash::getCookie($invalid_login_key));

				if ($invalid_login_count < 1)
				{
					$invalid_login_count = 1;
				}
				else
				{
					$invalid_login_count++;
				}
				Flash::setCookie($invalid_login_key, $invalid_login_count);

				sleep(max(0, min($invalid_login_count, (ini_get('max_execution_time') - 1))));
			}
			return false;
		}
	}

	private static function setSession($user)
	{
		$time = REQUEST_TIME + self::COOKIE_LIFE;
		Flash::setCookie(self::COOKIE_KEY, self::bakeUserCookie($time, $user), $time);
	}

	/**
	 * destroy logged in user data 
	 */
	public static function logout()
	{
		//$_SESSION[self::SESSION_KEY] = null;
		//unset($_SESSION[self::SESSION_KEY]);

		self::eatCookie();
		self::$user = false;
		self::$is_logged_in = false;
	}

	static protected function challengeCookie($cookie)
	{
		$params = self::explodeCookie($cookie);
		if (isset($params['exp'], $params['id'], $params['digest']))
		{
			if (!$user = Record::findByIdFrom('User', $params['id']))
			{
				return false;
			}
			if (self::bakeUserCookie($params['exp'], $user) === $cookie && $params['exp'] > REQUEST_TIME)
			{
				// user and cookie is valid// check if time less than half of session time 
				if ($params['exp'] < REQUEST_TIME + (self::COOKIE_LIFE / 2))
				{
					// session time is halfed then update session cookie
					self::setSession($user);
				}

				return $user;
			}
		}
		return false;
	}

	static protected function explodeCookie($cookie)
	{
		$pieces = explode('&', $cookie);

		if (count($pieces) < 2)
		{
			return array();
		}

		foreach ($pieces as $piece)
		{
			list($key, $value) = explode('=', $piece);
			$params[$key] = $value;
		}
		return $params;
	}

	static protected function eatCookie()
	{
		if (isset($_COOKIE[self::COOKIE_KEY]))
		{
			Flash::clearCookie(self::COOKIE_KEY);
		}
	}

	static protected function bakeUserCookie($time, $user)
	{
		// $user_ip = Input::getInstance()->ip_address();
		// dont use current ip: because user may switch from wifi to mobile etc. on same device on the go
		// dont use logged in ip to allow login from multiple devices 
		$user_ip = 'dont-use-user-ip';
		$salt = crc32($time . $user->id . $user_ip . $_SERVER['HTTP_USER_AGENT']) % 1234;
		$digest = md5($user->id . $time . $salt . $user->email . $user_ip . $_SERVER['HTTP_USER_AGENT']);
		return 'exp=' . $time . '&id=' . $user->id . '&digest=' . $digest;
	}

}

// end AuthUser class