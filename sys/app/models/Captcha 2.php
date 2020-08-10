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
 * class Page
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 * 
 * ------------------------------
 * RELATED CONFIG OPTIONS:
 * 		use_captcha : none|simple|recaptcha
 * 		logged_user_disable_captcha
 * 		recaptcha_public_key
 * 		recaptcha_private_key
 * ------------------------------
 * 
 */
class Captcha extends Record
{

	const TYPE_NONE = 'none';
	const TYPE_SIMPLE = 'simple';
	const TYPE_RECAPTCHA_1 = 'recaptcha';
	const TYPE_RECAPTCHA_2 = 'recaptcha2';
	const TYPE_RECAPTCHA_INVISIBLE = 'recaptcha_invisible';

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public static function renderSecurityImage()
	{
		// check if recaptcha is not used

		use_helper('Vimage');

		$vImage = new Vimage();
		$vImage->genText(4);
		$vImage->showimage();
		exit;
	}

	public static function check($type = null)
	{

		// check if captcha disabled for logged in users
		if (Config::option('logged_user_disable_captcha') && AuthUser::isLoggedIn(false))
		{
			return true;
		}

		$msg_extra = '';

		if (is_null($type))
		{
			$type = Config::option('use_captcha');
		}

		switch ($type)
		{
			case self::TYPE_NONE:
				return true;
				break;
			case self::TYPE_RECAPTCHA_1:
				//require_once('recaptchalib.php');
				use_file('recaptchalib.php');

				$privatekey = Config::option('recaptcha_private_key');
				$resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

				$return = $resp->is_valid;
				$field = 'recaptcha_response_field';
				$msg_extra = $resp->error;
				break;
			case self::TYPE_RECAPTCHA_2:
			case self::TYPE_RECAPTCHA_INVISIBLE:

				// check if curl and json_decode available 
				if (!self::isAvailable($type))
				{
					// fall back to simple captcha 
					return self::check(self::TYPE_SIMPLE);
				}

				$url = 'https://www.google.com/recaptcha/api/siteverify';
				$secret = Config::option('recaptcha_private_key');
				$response = $_POST['g-recaptcha-response'];

				$resp = Curl::getCurl($url, array('secret' => $secret, 'response' => $response));

				$resp = json_decode($resp);
				/*
				  {
				  "success": true|false,
				  "challenge_ts": timestamp,  // timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
				  "hostname": string,         // the hostname of the site where the reCAPTCHA was solved
				  "error-codes": [...]        // optional
				  }
				 */

				$return = $resp->success;
				$field = 'g-recaptcha-response';
				$err_codes_key = "error-codes";
				$err_codes = $resp->{$err_codes_key};
				if (is_array($err_codes))
				{
					$msg_extra = implode(', ', $err_codes);
				}
				break;
			case self::TYPE_SIMPLE:
			default:
				$return = Vimage::getInstance()->checkCode();
				$field = 'vImageCodP';
				break;
		}


		if (!$return)
		{
			Validation::getInstance()->set_error(__('Security code is not valid. {msg}', array('{msg}' => $msg_extra)), $field);
		}

		return $return;
	}

	public static function render($pettern = '<p><label for="{name}">{label}: </label> {input}</p>', $type = null)
	{
		// check if captcha disabled for logged in users
		if (Config::option('logged_user_disable_captcha') && AuthUser::isLoggedIn(false))
		{
			return '';
		}

		if (is_null($type))
		{
			$type = Config::option('use_captcha');
		}

		switch ($type)
		{
			case self::TYPE_NONE:
				return '';
				break;
			case self::TYPE_RECAPTCHA_1:
				//require_once('recaptchalib.php');
				$publickey = Config::option('recaptcha_public_key');
				$name = 'recaptcha_response_field';
				$label = __('Security code');

				if (Config::option('recaptcha_ajax'))
				{
					$input = self::recaptchaAjax($publickey) . Validation::getInstance()->{$name . '_error'};
				}
				else
				{
					use_file('recaptchalib.php');
					$use_ssl = (Config::getUrlProtocol() === 'https://');
					$input = recaptcha_get_html($publickey, null, $use_ssl) . Validation::getInstance()->{$name . '_error'};
				}
				break;
			case self::TYPE_RECAPTCHA_INVISIBLE:

				if (!self::isAvailable($type))
				{
					return self::render($pettern, self::TYPE_SIMPLE);
				}

				// site_key
				$publickey = Config::option('recaptcha_public_key');
				$name = 'g-recaptcha-response';
				$label = __('Security code');
				$input = '<span class="recaptcha_invisible">' . __('Invisible') . '</span>
							<script type="text/javascript">
							if(typeof recaptcha_onloadCallback == "undefined")
							{								
								var recaptcha_onloadCallback = function() {
									$(".recaptcha_invisible").each(function(){
										var $me = $(this);
										var $form = $me.parents("form:first");
										var submitted = false;
										var $submit = $("[type=\'submit\']",$form);
										var holderId = grecaptcha.render($me.get(0),{
											  "sitekey": "' . View::escape($publickey) . '",
											  "size": "invisible",
											  "badge" : "bottomright", // possible values: bottomright, bottomleft, inline
											  "callback" : function (recaptchaToken) {												
												submitted=true;
												if($submit.length){console.log("recaptcha_invisible finished btn click");$submit.click();}
												else{console.log("recaptcha_invisible finished form submit");$form.submit();}
											  }
											});
											
										$form.submit(function(evt){
											if(!submitted)
											{
												evt.preventDefault();
												grecaptcha.execute(holderId);
												console.log("recaptcha_invisible $form.submit called");												
											}											
										});
										
									});															
									console.log("recaptcha_invisible onloadCallback");
								};

								addLoadEvent(function(){
									$.getScript("https://www.google.com/recaptcha/api.js?onload=recaptcha_onloadCallback&render=explicit");
								});
							}
						</script>'
						. Validation::getInstance()->{$name . '_error'};

				/* . '<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>' */

				break;
			case self::TYPE_RECAPTCHA_2:

				if (!self::isAvailable($type))
				{
					return self::render($pettern, self::TYPE_SIMPLE);
				}

				// site_key
				$publickey = Config::option('recaptcha_public_key');
				$name = 'g-recaptcha-response';
				$label = __('Security code');
				$input = '<script src="https://www.google.com/recaptcha/api.js"></script>'
						. '<div class="g-recaptcha" data-sitekey="' . View::escape($publickey) . '"></div>'
						. Validation::getInstance()->{$name . '_error'};

				break;
			case self::TYPE_SIMPLE:
			default:
				$name = 'vImageCodP';
				$label = __('Security code');
				$input = '<input size="3" maxlength="4" type="text" name="' . $name . '" id="' . $name . '" class="short input input-short" autocomplete="' . $name . 'off"  required />
						<img src="' . Language::get_url('login/securityImage/' . REQUEST_TIME . '/') . '" style="vertical-align: middle;" /> '
						. Validation::getInstance()->{$name . '_error'};
				break;
		}

		return str_replace(array('{name}', '{label}', '{input}', '{marker}'), array($name, $label, $input, Config::markerRequired()), $pettern);
	}

	public static function recaptchaAjax($publickey)
	{

		return '<span id="recaptcha_wrap"></span>
			<script type="text/javascript">
			addLoadEvent(function(){
				$.getScript("https://www.google.com/recaptcha/api/js/recaptcha_ajax.js",function(){
					function showRecaptcha(element) {
						Recaptcha.create("' . $publickey . '", element, {theme: "red"});
					}
					showRecaptcha("recaptcha_wrap");
				});
			});
			</script>';
	}

	public static function isAvailable($type)
	{
		switch ($type)
		{
			case self::TYPE_RECAPTCHA_2:
			case self::TYPE_RECAPTCHA_INVISIBLE:
				$return = Curl::isAvailable() && function_exists('json_decode');
				break;
			default:
				$return = true;
		}

		return $return;
	}

	/**
	 * Check if old recaptcha v1 is active on this site
	 * @return type
	 */
	public static function isOldRecaptcha()
	{
		return strcmp(Config::option('use_captcha'), Captcha::TYPE_RECAPTCHA_1) == 0;
	}

}
