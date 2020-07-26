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
 * class LoginController
 *
 * Log a use in and out and send a mail with something on
 * if the user doesn't remember is password !!!
 *
 * @author Philippe Archambault <philippe.archambault@gmail.com>
 * @since  0.1
 */
class LoginController extends Controller
{

	CONST URL_ADMIN = 'admin/';

	private $_layout = 'login';

	function __construct()
	{
		AuthUser::load();

		$this->_meta = new stdClass();
		$this->setLayout($this->_layout);

		// noindex meta header
		$header_other = '<meta name="robots" content="noindex, nofollow" />';
		$this->setMeta('header_other', $header_other);

		// load in login and in cron. 
		// in login for being sure that this processes are being run.
		Config::cronRun();
	}

	function index()
	{
		return $this->login();
	}

	private function _loggedin_redirect()
	{
		// already log in ?
		if (AuthUser::isLoggedIn(false))
		{
			redirect(Language::get_url(self::URL_ADMIN));
		}
	}

	private function _login()
	{
		if (get_request_method() == 'POST')
		{
			$data = isset($_POST['login']) ? $_POST['login'] : array('username' => '', 'password' => '');
			//Flash::set('username', $data['username']);

			$data['username'] = trim($data['username']);

			// check if login count not exceeded
			if (IpBlock::loginAttemptIsBanned())
			{
				return false;
			}

			//$use_cookie = isset($data['remember']);
			if (AuthUser::login($data['username'], $data['password']))
			{
				// reset unsuccessful logins
				IpBlock::loginAttemptReset();

				// check if user is not activated account
				if (AuthUser::$user->activation !== '0')
				{
					// generate verification email then logout 
					$message = __('Your account is not verified. Please check your email to verify your account. If you did not receive verification email click <a href="{url}">here</a> to receive new one.', array('{url}' => User::urlResendVerification(AuthUser::$user)));

					// user account is not activated. display message to resend activation code and logout
					AuthUser::logout();

					Config::displayMessagePage($message, 'error', true);
				}
				else if (!AuthUser::$user->enabled)
				{
					// user account is not enabled. display message 
					AuthUser::logout();

					// TODO send confirmation email once account approved
					$message = __('Your account is not approved yet. You will receive confirmation email once your account approved.');
					Config::displayMessagePage($message, 'error', true);
				}

				// redirect to last page
				$this->_resume_redirect();
			}
			else
			{
				$this->validation()->set_error(__('Login failed. Please check your login data and try again.'));

				// increas falied login count 
				IpBlock::loginAttemptCount($data['username']);
			}
		}
	}

	function login()
	{
		IpBlock::isBlockedIp();

		// already log in ?
		$this->_loggedin_redirect();

		// login 
		$this->_login();

		// store redirect link in cookie 
		if (strlen($_GET['rd']))
		{
			Flash::setCookie('rd', $_GET['rd']);
		}

		// show it!
		$this->display('login/login', array(
			'username' => trim($_POST['username'])
		));
	}

	function logout()
	{
		AuthUser::logout();
		redirect(Language::get_url());
	}

	function forgot()
	{
		IpBlock::isBlockedIp();

		if (get_request_method() == 'POST')
		{
			$rules['email'] = 'trim|required|valid_email';
			$fields['email'] = __('Email');

			$validation = $this->validation();
			$validation->set_rules($rules);
			$validation->set_fields($fields);

			if ($validation->run())
			{
				// check if user exists
				$user = User::findBy('email', $_POST['email']);
				if ($user)
				{
					$this->_sendPasswordTo($user);
					$this->display('login/login');
				}
				else
				{
					$validation->set_error(__('User not found'));
				}
			}
		}

		//return $this->_sendPasswordTo($_POST['forgot']['email']);

		$this->display('login/forgot');
	}

	function securityImage()
	{
		return Captcha::renderSecurityImage();
	}

	private function _sendPasswordTo($user)
	{
		if ($user)
		{
			$new_pass = '12' . dechex(rand(100000000, 4294967295)) . 'K';

			$arr_replace = array(
				'{@SITENAME}'	 => DOMAIN,
				'{@ACCOUNTLINK}' => Language::get_url('admin/'),
				'{@PASSWORD}'	 => $new_pass
			);

			$from = MailTemplate::emailFrom();
			$to = $user->email;
			$subject = MailTemplate::getFomatted('password_reminder', 'subject', $arr_replace);
			$message = MailTemplate::getFomatted('password_reminder', 'body', $arr_replace);


			if (MailTemplate::sendMail($from, $to, $subject, $message))
			{
				$user->password = md5($new_pass);
				$user->save();

				$this->validation()->set_success(__('An email has been send with your new password!'));
			}
			else
			{
				$this->validation()->set_error(__('Error accured while sending your new password. Please try again later.'));
			}

			return true;
		}

		return false;
	}

	private function _sendActivationEmail($user)
	{
		return MailTemplate::sendUserVerificationEmail($user);
	}

	function register($dealer = 0)
	{

		IpBlock::isBlockedIp();


		// already log in ?
		$this->_loggedin_redirect();

		// check maintenance mode
		Config::checkMaintenance();


		// check regitration permission as user and dealer
		if ($dealer && (!Config::option('account_dealer_can_register') || !Config::option('account_dealer')))
		{
			Config::displayMessagePage(__('Dealer registration for this website is not allowed.'), 'error', true);
		}
		elseif (!$dealer && (!Config::option('account_user_can_register') || !Config::option('account_user')))
		{
			Config::displayMessagePage(__('User registration for this website is not allowed.'), 'error', true);
		}

		if (get_request_method() == 'POST')
		{
			$user = $this->_register($dealer);
		}

		$this->display('login/register', array(
			'user'	 => $user,
			'dealer' => $dealer,
		));
	}

	private function _register($dealer = 0)
	{
		// general rules		
		$rules['email'] = 'trim|required|strtolower|xss_clean|valid_email|callback__validate_user_email';

		// specific rules
		$rules['password'] = 'required|min_length[4]|max_length[32]|matches[password_repeat]';
		$rules['read'] = 'required';


		$str_fields = '';
		if ($dealer)
		{
			$rules['web'] = 'trim|prep_url|xss_clean';
			$rules['info'] = 'trim|max_length[1000]|xss_clean';
			$str_fields = 'web,info,';
		}


		$fields['email'] = __('Email');
		$fields['web'] = __('Website');
		$fields['info'] = __('Info');
		$fields['password'] = __('Password');
		$fields['password_repeat'] = __('Repeat password');
		$fields['read'] = __('Terms of service');

		$user = new User();
		$this->validation()->set_controller($user);

		$this->validation()->set_rules($rules);
		$this->validation()->set_fields($fields);


		// check if user details are correct
		if ($this->validation()->run() && Config::nounceCheck(true) && Captcha::check())
		{
			$str_fields .= 'email,password';

			// code is correct create user and send activation link
			$data = Record::filterCols($_POST, $str_fields);

			$data['password'] = md5($data['password']);

			$user = new User($data);
			if ($dealer)
			{
				// set user as dealer
				$user->level = User::PERMISSION_DEALER;

				// check if can auto approve dealer
				if (Config::option('account_dealer_auto_approve_registration'))
				{
					// set user as dealer
					$user->enabled = 1;
				}
				else
				{
					// set user as pending to be approved as dealer
					$user->enabled = 0;
				}

				// upload image if set 
				if (User::uploadLogo($user, 'logo') === false)
				{
					// if error on uploading logo
					return $user;
				}
			}
			else
			{
				$user->level = User::PERMISSION_USER;
				// check registration options for users
				if (Config::option('account_user_auto_approve_registration'))
				{
					// set as user, auto approve 
					$user->enabled = 1;
				}
				else
				{
					// set user as pending to be approved as user
					$user->enabled = 0;
				}
			}

			if ($user->save('id', MAIN_DB))
			{
				// send activation email
				$this->_sendActivationEmail($user);

				// clear not activated users
				// User::clearNotActivated();
				Ad::clearNotActivated();

				// user created activation code sent
				Config::displayMessagePage(__('Your account is created. We sent verification email to you. Please check your email.'), 'success', true);
			}
			else
			{
				$this->validation()->set_error(__('Error accured while creating your account. Please try again later.'));
			}
		}

		return new User($_POST);
	}

	function message()
	{
		$this->display('login/message');
	}

	function noPermission()
	{
		$this->display('login/no_permission');
	}

	function activate($user_id = '', $activation_code = '')
	{

		IpBlock::isBlockedIp();

		if (!$user_id)
		{
			$this->_incorrectActivation();
		}

		if (!strlen($activation_code))
		{
			$this->_incorrectActivation();
		}

		// get user from database
		$user = User::findBy('id', $user_id);
		if (!$user)
		{
			$message = __('User is not found. Your registration may be deleted if it was not activated within {num} days. Click <a href="{url}">here</a> to start new registration.', array(
				'{url}'	 => Language::get_url('login/register/'),
				'{num}'	 => intval(Config::option('ads_verification_days'))
			));
			//Flash::set('error', $message);
			//redirect(Language::get_url('login/message/'));
			Config::displayMessagePage($message, 'error', true);
		}

		// check if user not already activated
		if ($user->activation === $activation_code)
		{
			// update database
			User::activateUser($user);
		}

		if ($user->activation === '0')
		{
			// associate all related ads to this user.
			Ad::associateAdsToUser($user);

			// display message 
			$message = __('Congratulations! Your registration is verified.') . ' ' . User::isEnabledMessage($user);
			Config::displayMessagePage($message, 'success', true);
		}

		$this->_incorrectActivation();
	}

	private function _incorrectActivation()
	{
		Config::displayMessagePage(__('Incorrect verification link. Please make sure that url is exactly the same.'), 'error', true);
	}

	/*
	 * TODO use capcha to accept only user requests. 
	 */

	function resendActivation($user_id, $email_md5)
	{
		IpBlock::isBlockedIp();


		// check maintenance mode
		Config::checkMaintenance();

		$user = User::findBy('id', $user_id);
		if ($user && md5($user->email) === $email_md5)
		{
			if ($user->activation != '0')
			{
				if ($this->_sendActivationEmail($user))
				{
					$message = __('Verification email is sent to your email address. Please check your email.');
					$message_type = 'success';
				}
				else
				{
					$message = __('Error accured while sending email, please try again later.');
					$message_type = 'error';
				}
			}
			else
			{
				$message = __('You already verified your email.');
				$message_type = 'success';
			}
		}
		else
		{
			$message = __('Your account is not found.');
			$message_type = 'error';
		}

		// use this for not caching email address in url
		Config::displayMessagePage($message, $message_type, true);
	}

	private function _resume_redirect()
	{
		// $this->_checkVersion();
		// redirect to defaut controller and action
		if (strlen($_GET['rd']))
		{
			$url = $_GET['rd'];
		}
		elseif (strlen($_POST['rd']))
		{
			$url = $_POST['rd'];
		}
		elseif (strlen(Flash::getCookie('rd')))
		{
			$url = Flash::getCookie('rd');
			Flash::clearCookie('rd');
		}
		else
		{
			$url = self::URL_ADMIN;
		}

		redirect(Language::get_url($url));
	}

}

// end LoginController class
