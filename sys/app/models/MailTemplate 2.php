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
 * class MailTemplate
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class MailTemplate extends Record
{

	const TABLE_NAME = 'mail_template';
	const PERIOD_PENDING_APPROVAL = 18000; // 5hr

	static private $_default = null;
	static private $mt = null;
	private static $cols = array(
		'id'			 => 1,
		'language_id'	 => 1,
		'subject'		 => 1,
		'body'			 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	function beforeInsert()
	{
		// delete old value 
		MailTemplate::deleteWhere('MailTemplate', 'id=? AND language_id=?', array($this->id, $this->language_id));

		return true;
	}

	public static function getTemplate($id)
	{
		if (!isset(self::$mt[$id]))
		{
			self::$mt[$id] = self::findOneFrom('MailTemplate', 'id=? AND language_id=?', array($id, I18n::getLocale()));
			if (!self::$mt[$id])
			{
				self::$mt[$id] = self::getDefault($id);
			}
		}
		return self::$mt[$id];
	}

	public static function getFomatted($id, $field = 'body', $values = array())
	{
		$mail_template = self::getTemplate($id);
		$value = $mail_template->{$field};

		// if empty use default
		if (!strlen(trim($value)))
		{
			$mail_template_default = self::getDefault($id);
			$value = $mail_template_default->{$field};
		}

		return str_replace(array_keys($values), array_values($values), $value);
	}

	public static function getAllIndexed()
	{
		$return = array();
		$mail_template = MailTemplate::findAllFrom('MailTemplate');

		foreach ($mail_template as $mt)
		{
			$return[$mt->id][$mt->language_id] = $mt;
		}

		return $return;
	}

	public static function getTitle($id)
	{
		self::initDefault();
		return self::$_default[$id]->title;
	}

	public static function initDefault()
	{
		if (!isset(self::$_default))
		{

			/* general */
			self::$_default['general'] = new stdClass();
			self::$_default['general']->id = 'general';
			self::$_default['general']->title = __('General');
			self::$_default['general']->description = __('Used for general notification emails.');
			self::$_default['general']->subject = '{@SUBJECT}';
			self::$_default['general']->body = '{@MESSAGE}
------------------------------------------------------
{@SITENAME}';

			self::$_default['general']->vals = array(
				'{@SITENAME}'	 => __('Site name'),
				'{@SUBJECT}'	 => __('Subject'),
				'{@MESSAGE}'	 => __('Message')
			);



			/* new post */
			self::$_default['new_post'] = new stdClass();
			self::$_default['new_post']->id = 'new_post';
			self::$_default['new_post']->title = __('New post');
			self::$_default['new_post']->description = __('Sent when new ad posted and email verification required. Email verification required only if user posted without logging in to existing account.');
			self::$_default['new_post']->subject = 'Verify your posting to {@SITENAME}';
			self::$_default['new_post']->body = 'Thank you for posting an ad to {@SITENAME} site. Please confirm your posting by clicking on activation link. If link is not working copy and paste link into address bar in your web browser.

Activation link
{@VERIFICATIONLINK}

NOTE: All posts not activated in {@EXPIREAFTER} days are deleted.
------------------------------------------------------
{@SITENAME}';

			self::$_default['new_post']->vals = array(
				'{@SITENAME}'			 => __('Site name'),
				'{@ADTITLE}'			 => __('Ad title'),
				'{@VERIFICATIONLINK}'	 => __('Verification link'),
				'{@VIEWADLINK}'			 => __('View ad link'),
				'{@EDITADLINK}'			 => __('Edit ad link'),
				'{@EXPIREAFTER}'		 => __('Expire after (days)')
			);


			/* new account */
			self::$_default['new_account'] = new stdClass();
			self::$_default['new_account']->id = 'new_account';
			self::$_default['new_account']->title = __('New account');
			self::$_default['new_account']->description = __('Sent when new account created by verifying email address of ad posting.');
			self::$_default['new_account']->subject = 'Your account on {@SITENAME}';
			self::$_default['new_account']->body = 'We have created a user account for managing your ads.

Username is this email address.
Password: {@PASSWORD}

You can ligon to your account visiting {@ACCOUNTLINK}

Note: Please change your password on login
------------------------------------------------------
{@SITENAME}';
			self::$_default['new_account']->vals = array(
				'{@SITENAME}'	 => __('Site name'),
				'{@ACCOUNTLINK}' => __('Account link'),
				'{@PASSWORD}'	 => __('Password')
			);


			/* new_account_activate */
			self::$_default['new_account_activate'] = new stdClass();
			self::$_default['new_account_activate']->id = 'new_account_activate';
			self::$_default['new_account_activate']->title = __('Account verification mail');
			self::$_default['new_account_activate']->description = __('Sent when new user registered using Registration Form');
			self::$_default['new_account_activate']->subject = 'Account verification mail';
			self::$_default['new_account_activate']->body = 'Thank you for registering with {@SITENAME}. Please activate your account by clicking on verification link. If link is not working copy and paste link into address bar in your web browser.

Verification link: 
{@VERIFICATIONLINK}
    
NOTE: All accounts not verified in {@EXPIREAFTER} days are deleted.
-----------------------------------
{@SITENAME}';
			self::$_default['new_account_activate']->vals = array(
				'{@SITENAME}'			 => __('Site name'),
				'{@VERIFICATIONLINK}'	 => __('Verification link'),
				'{@EXPIREAFTER}'		 => __('Expire after (days)')
			);


			/* contact user */
			self::$_default['contact_user'] = new stdClass();
			self::$_default['contact_user']->id = 'contact_user';
			self::$_default['contact_user']->title = __('Contact User');
			self::$_default['contact_user']->description = __('Sent when visitor contacts ad author using online form.');
			self::$_default['contact_user']->subject = 'New message from {@SITENAME} for listing ({@ADID})';
			self::$_default['contact_user']->body = 'You have a new message for ad posted on {@SITENAME}

Listing: {@ADTITLE}
Url: {@VIEWADLINK}
Contacter email: {@FROM}
-----------------------------------
{@MESSAGE}
 ----------------------------------

NOTE: If you do not want to receive contact messages for this ad click here and change contact method. {@DISABLECONTACTLINK}
If you want to remove this listing from website click here and delete. {@REMOVELISTING}
Manage all your listings. {@ACCOUNTLINK}
-----------------------------------
{@SITENAME}';
			self::$_default['contact_user']->vals = array(
				'{@SITENAME}'			 => __('Site name'),
				'{@ADID}'				 => __('Ad ID'),
				'{@ADTITLE}'			 => __('Ad title'),
				'{@ACCOUNTLINK}'		 => __('Account link'),
				'{@FROM}'				 => __('From email'),
				'{@VIEWADLINK}'			 => __('View ad link'),
				'{@DISABLECONTACTLINK}'	 => __('Disable contact form link'),
				'{@REMOVELISTING}'		 => __('Remove listing link'),
				'{@MESSAGE}'			 => __('User message')
			);




			/* password_reminder */
			self::$_default['password_reminder'] = new stdClass();
			self::$_default['password_reminder']->id = 'password_reminder';
			self::$_default['password_reminder']->title = __('Password reminder');
			self::$_default['password_reminder']->description = __('Send when user requests to resend new password using Forgot Password form.');
			self::$_default['password_reminder']->subject = 'Password reminder';
			self::$_default['password_reminder']->body = 'New password:
{@PASSWORD}

Note: Please change your password on login
-----------------------------------
{@SITENAME}';
			self::$_default['password_reminder']->vals = array(
				'{@SITENAME}'	 => __('Site name'),
				'{@ACCOUNTLINK}' => __('Account link'),
				'{@PASSWORD}'	 => __('Password')
			);




			/* password_reminder */
			self::$_default['contact_us'] = new stdClass();
			self::$_default['contact_us']->id = 'contact_us';
			self::$_default['contact_us']->title = __('Contact us');
			self::$_default['contact_us']->description = __('Message sent to site admins using contact us form.');
			self::$_default['contact_us']->subject = 'Contact us from {@SITENAME}: {@SUBJECT}';
			self::$_default['contact_us']->body = 'You have a new contact us message from {@SITENAME}

Contacter email: {@FROM}
Subject: {@SUBJECT}
-----------------------------------
{@MESSAGE}

';
			self::$_default['contact_us']->vals = array(
				'{@SITENAME}'	 => __('Site name'),
				'{@SUBJECT}'	 => __('Subject'),
				'{@FROM}'		 => __('From')
			);
		}
	}

	public static function getDefault($id)
	{
		self::initDefault();
		return self::$_default[$id];
	}

	public static function getDefaultAll()
	{
		self::initDefault();
		return self::$_default;
	}

	/**
	 * send verification email to author
	 * @param Ad $ad
	 * @return boolean
	 */
	public static function sendAdVerificationEmail($ad)
	{
		if ($ad)
		{
			$arr_replace = array(
				'{@SITENAME}'			 => DOMAIN,
				'{@ACCOUNTLINK}'		 => Language::get_url('admin/'),
				'{@ADTITLE}'			 => View::escape(Ad::getTitle($ad)),
				'{@POSTTITLE}'			 => View::escape(Ad::getTitle($ad)),
				'{@VERIFICATIONLINK}'	 => Ad::urlActivate($ad),
				'{@VIEWADLINK}'			 => Ad::url($ad),
				'{@EDITADLINK}'			 => Ad::urlEdit($ad),
				'{@EXPIREAFTER}'		 => Config::option('ads_verification_days')
			);


			$from = MailTemplate::emailFrom();
			$to = $ad->email;
			$subject = MailTemplate::getFomatted('new_post', 'subject', $arr_replace);
			$message = MailTemplate::getFomatted('new_post', 'body', $arr_replace);

			return MailTemplate::sendMail($from, $to, $subject, $message);
		}
		return false;
	}

	public static function sendUserVerificationEmail($user)
	{
		if ($user)
		{
			$arr_replace = array(
				'{@SITENAME}'			 => DOMAIN,
				'{@VERIFICATIONLINK}'	 => User::urlVerifyEmail($user),
				'{@EXPIREAFTER}'		 => Config::option('ads_verification_days')
			);

			$from = MailTemplate::emailFrom();
			$to = $user->email;
			$subject = MailTemplate::getFomatted('new_account_activate', 'subject', $arr_replace);
			$message = MailTemplate::getFomatted('new_account_activate', 'body', $arr_replace);

			return MailTemplate::sendMail($from, $to, $subject, $message);
		}

		return false;
	}

	/**
	 * send verification email to author
	 * @param Ad $ad
	 * @return boolean
	 */
	public static function sendGeneralEmail($subject, $message, $email_to)
	{
		Benchmark::cp('sendGeneralEmail');
		if ($message)
		{
			$arr_replace = array(
				'{@SITENAME}'	 => DOMAIN,
				'{@SUBJECT}'	 => View::escape($subject),
				'{@MESSAGE}'	 => $message
			);

			$from = MailTemplate::emailFrom();
			$subject = MailTemplate::getFomatted('general', 'subject', $arr_replace);
			$message = MailTemplate::getFomatted('general', 'body', $arr_replace);

			return MailTemplate::sendMail($from, $email_to, $subject, $message);
		}
		return false;
	}

	public static function sendNewUserPassword($user, $password)
	{
		if ($user && $password)
		{
			$arr_replace = array(
				'{@SITENAME}'	 => DOMAIN,
				'{@ACCOUNTLINK}' => Language::get_url('admin/'),
				'{@PASSWORD}'	 => $password
			);

			$from = MailTemplate::emailFrom();
			$to = $user->email;
			$subject = MailTemplate::getFomatted('new_account', 'subject', $arr_replace);
			$message = MailTemplate::getFomatted('new_account', 'body', $arr_replace);

			return MailTemplate::sendMail($from, $to, $subject, $message);
		}
		return false;
	}

	public static function sendContactMessage($ad, $from_email, $message)
	{
		// send message
		// FIXME add links to disable messages, remove listing, manage all listing links for this ad.
		/* $this->setLayout(false);
		  $Body = $this->render('index/mail_ad_contact_form', array(
		  'ad' => $ad,
		  'message' => View::escape($_POST['message']),
		  'title' => View::escape(Ad::getTitle($ad)),
		  'permalink' => Ad::permalink($ad),
		  'site' => URL_PUBLIC
		  ));
		  $this->setLayout($this->_layout); */

		/**
		 * for compatability with versions prior 1.2 add {@POSTID} and {@POSTTITLE}
		 */
		$arr_replace = array(
			'{@SITENAME}'			 => DOMAIN,
			'{@ACCOUNTLINK}'		 => Language::get_url('admin/'),
			'{@ADID}'				 => $ad->id,
			'{@POSTID}'				 => $ad->id,
			'{@ADTITLE}'			 => View::escape(Ad::getTitle($ad)),
			'{@POSTTITLE}'			 => View::escape(Ad::getTitle($ad)),
			'{@VIEWADLINK}'			 => Ad::url($ad),
			'{@FROM}'				 => View::escape($from_email),
			'{@DISABLECONTACTLINK}'	 => Ad::urlEdit($ad),
			'{@REMOVELISTING}'		 => Ad::urlEdit($ad),
			'{@MESSAGE}'			 => View::escape($message)
		);

		$from = MailTemplate::emailFrom();
		$to = $ad->email;
		$subject = MailTemplate::getFomatted('contact_user', 'subject', $arr_replace);
		$message = MailTemplate::getFomatted('contact_user', 'body', $arr_replace);

		$return = MailTemplate::sendMail($from, $to, $subject, $message, array(), $from_email);

		// count sent message 


		return $return;
	}

	public static function emailFrom()
	{
		return array(self::emailFromAddress(), self::emailFromName());
	}

	public static function emailFromAddress()
	{
		$email_from_address = Config::option('email_from_address');
		if (!strlen($email_from_address))
		{
			$arr_url = parse_url(BASE_URL);
			$email_from_address = 'support@' . $arr_url['host'];
		}

		return $email_from_address;
	}

	public static function emailFromName()
	{
		$email_from_name = Config::option('email_from_name');
		if (!strlen($email_from_name))
		{
			$arr_url = parse_url(BASE_URL);
			$email_from_name = $arr_url['host'];
		}
		return $email_from_name;
	}

	public static function sendContactUsMessage($from_email, $subject, $message, $data = null)
	{
		// send message
		// FIXME add links to disable messages, remove listing, manage all listing links for this ad.
		/* $this->setLayout(false);
		  $Body = $this->render('index/mail_ad_contact_form', array(
		  'ad' => $ad,
		  'message' => View::escape($_POST['message']),
		  'title' => View::escape(Ad::getTitle($ad)),
		  'permalink' => Ad::permalink($ad),
		  'site' => URL_PUBLIC
		  ));
		  $this->setLayout($this->_layout); */

		if ($data)
		{
			ob_start();
			print_r($data);
			$raw_data = ob_get_contents();
			ob_end_clean();
		}


		$arr_replace = array(
			'{@SITENAME}'	 => DOMAIN,
			'{@FROM}'		 => View::escape($from_email),
			'{@SUBJECT}'	 => View::escape($subject),
			'{@MESSAGE}'	 => View::escape($message),
			'{@RAWDATA}'	 => $raw_data
		);

		$from = $from_email;
		$subject = MailTemplate::getFomatted('contact_us', 'subject', $arr_replace);
		$message = MailTemplate::getFomatted('contact_us', 'body', $arr_replace);


		// send message to all admins 
		$admins = User::findAllFrom('User', 'level=?', array(User::PERMISSION_ADMIN));
		$return = false;

		foreach ($admins as $admin)
		{
			$to = $admin->email;
			$return = MailTemplate::sendMail($from, $to, $subject, $message, array(), $from_email) || $return;
		}

		return $return;
	}

	/**
	 * set protocol defined in site settings 
	 * 
	 * @param Email $email 
	 * @param array $test_settings for testing given email settings
	 */
	static function setEmailProtocol($email, $test_settings = array())
	{
		$config = array();
		if ($test_settings)
		{
			$mail_protocol = strtolower($test_settings['mail_protocol']);
			$config['smtp_host'] = $test_settings['smtp_host'];
			$config['smtp_encryption'] = $test_settings['smtp_encryption'];
			$config['smtp_user'] = $test_settings['smtp_user'];
			$config['smtp_pass'] = $test_settings['smtp_password'];
			$config['smtp_port'] = $test_settings['smtp_port'];
			$config['smtp_timeout'] = $test_settings['smtp_timeout'];
		}
		else
		{
			$mail_protocol = strtolower(Config::option('mail_protocol'));
			$config['smtp_host'] = Config::option('smtp_host');
			$config['smtp_encryption'] = Config::option('smtp_encryption');
			$config['smtp_user'] = Config::option('smtp_user');
			$config['smtp_pass'] = Config::option('smtp_password');
			$config['smtp_port'] = Config::option('smtp_port');
			$config['smtp_timeout'] = Config::option('smtp_timeout');
		}

		if ($mail_protocol == 'smtp')
		{
			// change protocol to smtp
			$email->setProtocol('smtp');
			$email->initialize($config);
		}
		else
		{
			// reset to default protocol
			$email->setProtocol();
		}
	}

	/**
	 * initialize email according to defined server settings
	 * deprecated since version 1.3.6
	 * 
	 * @param array $test_settings for testing given email settings
	 * @return Email 
	 */
	public static function email($test_settings = array())
	{
		use_helper('Email');

		$email = new Email();
		self::setEmailProtocol($email, $test_settings);
		return $email;
	}

	/**
	 * send email to specified address 
	 * 
	 * control if in demo dont send mail to @test.com 
	 * this is used as wrap for mail sending 
	 * 
	 * @param string $from
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param array $settings
	 * @return bool
	 */
	public static function sendMail($from, $to, $subject, $message, $settings = array(), $reply_to = null)
	{

		if (DEMO && strpos($to, '@test.com') !== false)
		{
			// do not send email because @test.com and demo mode
			$error = __('Sending email to @test.com restricted in demo mode.');
			Validation::getInstance()->set_error($error);

			return false;
		}

		// use phpmailer
		use_file('PHPMailer/class.phpmailer.php');


		$phpmailer = new PHPMailer();
		$phpmailer->CharSet = 'utf-8';

		if ($settings)
		{
			$mail_protocol = strtolower($settings['mail_protocol']);

			// enable debug for testing smtp settings only
			$phpmailer->SMTPDebug = 2; // server and client debug
			// generate as html message 
			$phpmailer->Debugoutput = 'html';
		}
		else
		{
			$mail_protocol = strtolower(Config::option('mail_protocol'));
			$settings['smtp_host'] = Config::option('smtp_host');
			$settings['smtp_encryption'] = Config::option('smtp_encryption');
			$settings['smtp_user'] = Config::option('smtp_user');
			$settings['smtp_password'] = Config::option('smtp_password');
			$settings['smtp_port'] = Config::option('smtp_port');
			$settings['smtp_timeout'] = Config::option('smtp_timeout');
		}

		if ($mail_protocol == 'smtp')
		{
			//date_default_timezone_set('Etc/UTC');
			$phpmailer->IsSMTP();  // Set mailer to use SMTP
			$phpmailer->Host = $settings['smtp_host'];  // Specify main and backup server 'smtp1.example.com;smtp2.example.com'
			$phpmailer->SMTPSecure = $settings['smtp_encryption']; // tls,ssl
			$phpmailer->SMTPAuth = true; // Enable SMTP authentication
			$phpmailer->Username = $settings['smtp_user']; // SMTP username
			$phpmailer->Password = $settings['smtp_password'];   // SMTP password
			$phpmailer->Port = intval($settings['smtp_port']); // 465,
			$phpmailer->Timeout = $settings['smtp_timeout'];
		}

		$_reply_to_set = false;
		if (is_array($reply_to))
		{
			$phpmailer->AddReplyTo($from[0], $from[1]);
			$_reply_to_set = true;
		}
		elseif ($reply_to)
		{
			$phpmailer->AddReplyTo($reply_to);
			$_reply_to_set = true;
		}


		if (is_array($from))
		{
			$phpmailer->SetFrom($from[0], $from[1]);
			if (!$_reply_to_set)
			{
				$phpmailer->AddReplyTo($from[0], $from[1]);
			}
		}
		else
		{
			$phpmailer->SetFrom($from);
			if (!$_reply_to_set)
			{
				$phpmailer->AddReplyTo($from);
			}
		}


		$phpmailer->AddAddress($to); // Add a recipient
		$phpmailer->Subject = $subject;
		$phpmailer->Body = $message;

		//$phpmailer->SetLanguage('fr');

		$return = $phpmailer->Send();

		if (!$return)
		{
			Validation::getInstance()->set_error($phpmailer->ErrorInfo);
		}

		return $return;
	}

	/**
	 * send mail if there are any pending approval ads or users
	 * 
	 * @return boolean
	 */
	public static function sendPendingApproval()
	{
		// check if notification enabled 
		if (!Config::option('notify_admin_pending_approval'))
		{
			return false;
		}

		// check last notified time for not overloading admin with messages 
		$last_sendPendingApproval = Config::option('last_sendPendingApproval');

		if (REQUEST_TIME - self::PERIOD_PENDING_APPROVAL > $last_sendPendingApproval)
		{
			// switch to default locale. send admin notification in default language
			I18n::saveLocale();
			I18n::setLocale(Language::getDefault());

			$subject = array();
			$message = array();

			// get number of ads pending approval
			$pending_aproval_ads = Ad::countByClass('Ad', 'enabled=? AND verified IN (?,?)', array('0', '0', '1'));
			$pending_approval_users = User::countFrom('User', "enabled='0' AND activation='0'");
			$pending_approval_dealers = User::countFrom('User', "pending_level='" . User::PERMISSION_DEALER . "'");

			if ($pending_aproval_ads)
			{
				$message[] = $pending_aproval_ads . ' - ' . __('Ads pending approval') . ' - ' . Language::get_url('admin/items/?enabled=' . Ad::STATUS_PENDING_APPROVAL);
				$subject[] = $pending_aproval_ads . ' ' . __('Ads');
			}
			if ($pending_approval_users)
			{
				$message[] = $pending_approval_users . ' - ' . __('Users pending approval') . ' - ' . Language::get_url('admin/users/notenabled/');
			}
			if ($pending_approval_dealers)
			{
				$message[] = $pending_approval_dealers . ' - ' . __('Pending upgrade to dealer') . ' - ' . Language::get_url('admin/users/upgradetodealer/');
			}

			if ($pending_approval_users || $pending_approval_dealers)
			{
				$subject[] = ($pending_approval_users + $pending_approval_dealers) . ' ' . __('Users');
			}


			if ($subject)
			{
				$str_message = implode("\n", $message);
				$str_message .= "\n\n" . __('Disable these alerts from {url}', array(
							'{url}' => Language::get_url('admin/settingsAccount/#notify_admin_pending_approval')
				));


				$str_subject = implode(", ", $subject) . ' ' . __('pending approval');
				$from = MailTemplate::emailFrom();

				// send message to all admins 
				$admins = User::findAllFrom('User', 'level=?', array(User::PERMISSION_ADMIN));
				$return = false;

				foreach ($admins as $admin)
				{
					$return = MailTemplate::sendMail($from, $admin->email, $str_subject, $str_message) || $return;
				}
			}

			Config::optionSet('last_sendPendingApproval', REQUEST_TIME);

			// restore to current locale
			I18n::restoreLocale();

			return $return;
		}

		return false;
	}

	/**
	 * Adds these ids to list of newly approved, sends in cron later
	 * 
	 * @param array|int $ids
	 * @return bool 
	 */
	static public function sendApprovedDelayed($ids)
	{
		// add ids to approved list with current time for delayed sending
		$key = '_sendApprovedOptions';
		$opt = MailTemplate::sendApprovedOptions();

		// reduce list 
		$approved_limit = 500;
		if (count($opt['list']['approved']) > $approved_limit)
		{
			$opt['list']['approved'] = array_slice($opt['list']['approved'], 0, $approved_limit);
		}

		// add to list with current time 
		$ids = Record::checkMakeArray($ids);
		foreach ($ids as $id)
		{
			$opt['list']['approved'][$id] = REQUEST_TIME;
		}

		return Config::optionSet($key, serialize($opt), 0);
	}

	/**
	 * Check stored send approved ids list to send message to owner. 
	 * Group by user and send multiple records in one message when possible. 
	 * 		checks for wait after added time
	 * 		checks for wait after last sent
	 * 		checks for list overflow, and sends if needed
	 * 
	 */
	static public function sendApprovedSend($force = false)
	{
		Benchmark::cp('sendApprovedSend');

		// add ids to approved list with current time for delayed sending
		$key = '_sendApprovedOptions';
		// wait 10 minutes after record added
		$wait_added = 600;
		// wait 10 seconds after last sent
		$wait_sent = 10;
		// if more than this limit then do not wait for $wait_added
		$approved_limit = 100;

		// keep current locale and use default locale
		I18n::saveLocale();
		I18n::setLocale(Language::getDefault());


		$opt = MailTemplate::sendApprovedOptions();


		// get oldest id by time 
		$oldest = array(
			'time'	 => REQUEST_TIME,
			'id'	 => 0
		);

		foreach ($opt['list']['approved'] as $id => $time)
		{
			if ($oldest['time'] > $time)
			{
				$oldest = array(
					'time'	 => $time,
					'id'	 => $id
				);
			}
		}

		// we have oldest check if oldest older than wait time 
		$last_sent_time = $opt['time']['sent'];
		$oldest_added_time = $oldest['time'];

		// check if we have ids to send and time is ok 
		$id_ok = $oldest['id'] > 0;



		Benchmark::cp('sendApprovedSend:NOW:' . REQUEST_TIME . ',$last_sent_time:' . $last_sent_time . ',$oldest_added_time' . $oldest_added_time);

		if ($id_ok && !$force)
		{
			Benchmark::cp('sendApprovedSend:id_ok');

			// wait for some time after record added in case items from same user will be added shortly
			$time_wait_added_ok = $oldest_added_time < (REQUEST_TIME - $wait_added);

			Benchmark::cp('sendApprovedSend:wait:' . ($time_wait_added_ok ? 'OK' : 'NOT'));

			// if too much records and wait added is not met then send to reduce records 
			$time_wait_added_ok = $time_wait_added_ok || count($opt['list']['approved']) > $approved_limit;
			Benchmark::cp('sendApprovedSend:lmitReached:' . ($time_wait_added_ok ? 'OK' : 'NOT'));

			// wait before sending next recrods
			$force = $time_wait_added_ok && $last_sent_time < (REQUEST_TIME - $wait_sent);
		}

		if ($force)
		{
			Benchmark::cp('sendApprovedSend:time_ok');
			$arr_msg = array();

			// remove this id from approved list 
			$arr_remove = array($oldest['id'] => true);

			// we can send akg to this item owner 
			// get all approver ad ids for this owner 
			$ad = Ad::findByIdFrom('Ad', $oldest['id']);
			Benchmark::cp('sendApprovedSend:$ad:' . $ad->id . ',user:' . $ad->added_by);
			// make sure it is listed 
			if ($ad && $ad->added_by)
			{
				$ids = array_keys($opt['list']['approved']);
				if (count($ids) > 1)
				{
					$ids_ = Ad::quoteArray($ids);
					// get ads from same user, we will filter listed items below in loop
					$ads = Ad::findAllFrom('Ad', 'added_by=? AND id IN (' . implode(',', $ids_) . ')', array($ad->added_by));
				}
				else
				{
					// we have only one id then it is only 1 ad
					$ads = array($ad);
				}

				// generate message and send 
				// get user 
				$user = User::findByIdFrom('User', $ad->added_by);
				Benchmark::cp('sendApprovedSend:$user:' . $user->id);

				if ($user)
				{
					$max = 5;
					$arr_msg['email'] = $user->email;
					$arr_msg['subject'] = __('Ad listing approved');
					foreach ($ads as $_ad)
					{
						$arr_remove[$_ad->id] = true;
						if ($_ad->listed == 1)
						{
							$arr_listed[$_ad->id] = true;

							if ($max > 0)
							{
								// group message body
								$arr_msg['body'][] = __('Ad listing "{name}" approved and can be viewed at {url} . Please consider sharing your listing with friends for boosting interest.', array(
									'{name}' => View::escape(Ad::getTitle($_ad)),
									'{url}'	 => Ad::url($_ad),
								));
								$max--;
							}
						}
					}

					$total_listed = count($arr_listed);

					if ($total_listed > 5)
					{
						$arr_msg['body'][] = __('Total {num} items approved.', array(
							'{num}' => $total_listed
						));
					}
				}
			}


			// remove ids before sending because sending takes time 
			$arr_approved = array();
			foreach ($opt['list']['approved'] as $id => $time)
			{
				if (!isset($arr_remove[$id]))
				{
					// keep this record
					$arr_approved[$id] = $time;
				}
			}
			$opt['list']['approved'] = $arr_approved;
			$opt['time']['sent'] = REQUEST_TIME;

			// save removing processes records
			Config::optionSet($key, serialize($opt), 0);


			if (isset($arr_msg['body']))
			{
				// send one email
				$sent = MailTemplate::sendGeneralEmail($arr_msg['subject'], implode("\n\n", $arr_msg['body']), $arr_msg['email']);

				// after sending save stats 
				$opt = MailTemplate::sendApprovedOptions($fresh);
				$dur = round(time() - $opt['time']['sent'], 2);
				$opt['time']['duration'] = $dur;

				$arr_sent = $opt['list']['sent'];
				$log = 'a:' . intval($oldest['id'])
						. ',u:' . $ad->added_by
						. ',cnt:' . count($ads)
						. ',d:' . $dur
						. ',ok:' . ($sent ? 1 : 0)
						. ',t:' . time();
				array_unshift($arr_sent, $log);
				// max  10 records 
				$arr_sent = array_slice($arr_sent, 0, 10);
				$opt['list']['sent'] = $arr_sent;

				// save last sent duration info for stats
				Config::optionSet($key, serialize($opt), 0);

				Benchmark::cp('sendApprovedSend:LOG:' . $log);
			}
		}
		else
		{
			Benchmark::cp('sendApprovedSend:time_ok:NOT');


			if (!$oldest['id'])
			{
				// nothing to send then update last sent time to bit future 
				// wait at least for new added items to be able to expire
				$opt['time']['sent'] = REQUEST_TIME + $wait_added;
				Config::optionSet($key, serialize($opt), 0);
			}
			else
			{
				// check if old time is in future then make it now
				if ($last_sent_time > REQUEST_TIME)
				{
					$opt['time']['sent'] = REQUEST_TIME;
					Config::optionSet($key, serialize($opt), 0);
				}
			}
		}

		// restore to current locale
		I18n::restoreLocale();

		Benchmark::cp('sendApprovedSend:END');
	}

	/**
	 * Get current list from db 
	 * 
	 * @param type $fresh
	 * @return array
	 */
	static public function sendApprovedOptions($fresh = true)
	{
		// get lates opt from DB
		$key = '_sendApprovedOptions';
		$opt_default = array(
			'time'	 => array(
				'sent'		 => 0,
				'duration'	 => 0,
			),
			'list'	 => array(
				'approved'	 => array(),
				'sent'		 => array()
			)
		);
		// check if waiting list empty then generate related and update last generation time to now. 

		if ($fresh)
		{
			$opt = Config::loadByKeyFresh($key);
		}
		else
		{
			$opt = Config::option($key);
		}

		if (strlen($opt))
		{
			$opt = unserialize($opt);
			$opt = Language::array_merge_recursive($opt_default, $opt);
		}
		else
		{
			// create object 
			$opt = $opt_default;
		}
		return $opt;
	}

}

// end MailTemplate class