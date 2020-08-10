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
 * class PostController handles posting ads
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 * 
 */
class PostController extends Controller
{

	private $_layout = 'frontend';

	function __construct()
	{
		IpBlock::isBlockedIp();

		AuthUser::load();

		$this->_meta = new stdClass();
		$this->setLayout($this->_layout);

		// noindex meta header
		$header_other = '<meta name="robots" content="noindex, nofollow" />';
		$this->setMeta('header_other', $header_other);

		// add support for jquerydropdown in theme 
		if (Theme::versionSupport(Theme::VERSION_SUPPORT_JQDROPDOWN))
		{
			$this->setMeta('body_class', 'e_jqd');
		}
	}

	function index($snoose_errors = false)
	{
		// check if can post ads without registration 
		if (!Config::option('ad_posting_without_registration') && !AuthUser::isLoggedIn(false))
		{
			// no permission to post ads without registration 
			Flash::set('error', __('Please Log in or Register to post ad to this site.'));
			// send to login page with redirect link back to this page 
			AuthUser::isLoggedIn();
		}

		// check if posting limited 
		if (!AuthUser::hasPermission(User::PERMISSION_MODERATOR))
		{
			// show message if post throttling
			IpBlock::postLimitIsBanned();

			// check if moderation limit reached, users should wait until old items moderated by admin.
			User::isModerationLimitReached();
		}


		// check if it is image upload 
		switch ($_REQUEST['action'])
		{
			case 'img':

				// upload image to temp
				$upload = Adpics::uploadToTmp($_REQUEST['image_token']);
				if ($upload)
				{
					echo $upload;
				}
				else
				{
					header_404();
					echo Adpics::getUploadErrors();
				}
				exit;
				break;
			case 'img_remove':
				//remove uploaded image 
				if (Adpics::uploadToTmpRemove($_POST['image_token'], $_POST['file']))
				{
					echo 'ok';
				}
				exit;
				break;
		}

		if (Theme::versionSupport(Theme::VERSION_SUPPORT_JQDROPDOWN))
		{
			// new post page. Single page dynamic custom fields 
			$location = Location::getLocationFromTree($_REQUEST['location_id'], Location::STATUS_ENABLED);
			$category = Category::getCategoryFromTree($_REQUEST['category_id'], Category::STATUS_ENABLED_NOTLOCKED);
			if (get_request_method() == 'POST')
			{
				// if data posted then submit it 
				$_POST['step'] = 2;
			}

			$ad = $this->_postData($location, $category, false);

			// reset location and category if they are 0 this is needed to properly work in js dropdown
			if ($ad->location_id == 0)
			{
				unset($ad->location_id);
			}
			if ($ad->category_id == 0)
			{
				unset($ad->category_id);
			}
		}
		else
		{
			if ($_REQUEST['location_id'])
			{
				$location = Location::findByIdFrom('Location', $_REQUEST['location_id']);
				Location::appendName($location);
			}

			if ($_REQUEST['category_id'])
			{
				$category = Category::findByIdFrom('Category', $_REQUEST['category_id']);
				Category::appendName($category);
			}

			if ($_REQUEST['submit'])
			{
				$next_step = true;

				// check if location fonund and enabled and not locked
				if (Location::hasValidPostingLocations())
				{
					if (!Location::isPostingAvailable($location))
					{
						$this->validation()->set_error(__('Please select valid location.'));
						$next_step = false;
					}
				}
				else
				{
					// no location enabled then cant post to any location. post as general ad with no lcation.
					$location = null;
				}

				// check if category fonund and enabled
				if (Category::hasValidPostingCategories())
				{
					if (!Category::isPostingAvailable($category))
					{
						$this->validation()->set_error(__('Please select valid category.'));
						$next_step = false;
					}
				}
				else
				{
					// no category enabled then cant post to any category. post as general ad with no category.
					$category = null;
				}

				if ($next_step)
				{
					return $this->_postData($location, $category);
				}
			}

			if (!Location::hasValidPostingLocations() && !Category::hasValidPostingCategories())
			{
				// no location or category defined then just post ad
				return $this->_postData($location, $category);
			}
		}



		// define language switch 
		Language::htmlLanguageBuild('post/item/' . intval($location->id) . '/' . intval($category->id) . '/');

		$vars = array(
			'selected_location'	 => $location,
			'selected_category'	 => $category,
			'ad'				 => $ad,
			'page_type'			 => 'ad_post',
			'page_title'		 => __('Post ad'),
			'step'				 => 1,
			'contact_options'	 => Ad::getContactOptions()
		);

		// load widgets to locations
		Widget::render($vars);

		if ($snoose_errors && !Theme::versionSupport(Theme::VERSION_SUPPORT_JQDROPDOWN))
		{
			$this->validation()->clear_errors();
		}

		$this->display('post/index', $vars);
	}

	function item($location_id = 0, $category_id = 0, $change = 0)
	{
		// post to given location and category 
		if (!isset($_POST['location_id']))
		{
			$_REQUEST['location_id'] = $location_id;
		}
		if (!isset($_POST['category_id']))
		{
			$_REQUEST['category_id'] = $category_id;
		}
		if (!$change)
		{
			$_REQUEST['submit'] = 'submit';
		}

		return $this->index(true);
	}

	private function _postData($location, $category, $display = true)
	{
		// set default location 
		Config::setDefaultLocationCookie($location->id, true);

		// get custom fields for given location and category 
		$catfields = CategoryFieldRelation::getCatfields($location->id, $category->id, true, true);

		$ad = $this->_postDataSubmit($location, $category, $catfields);

		// add not submitted then append additional default fields for ad
		if (!isset($ad->showemail))
		{
			// set default option 
			$ad->showemail = Ad::defaultContactOption();
		}
		if (AuthUser::isLoggedIn(false) && !isset($ad->email))
		{
			$ad->email = AuthUser::$user->email;
		}


		// display ad with custom fields addig form for old themes 
		if ($display)
		{
			// check if has featured ads or paid post 
			PaymentPrice::appendPaymentPrice($ad);


			Language::htmlLanguageBuild('post/?location_id=' . intval($location->id) . '&category_id=' . intval($category->id) . '&submit=Continue');

			$vars = array(
				'selected_location'	 => $location,
				'selected_category'	 => $category,
				'location'			 => $location,
				'category'			 => $category,
				'catfields'			 => $catfields,
				'ad'				 => $ad,
				'page_type'			 => 'ad_post',
				'page_title'		 => __('Post ad'),
				'contact_options'	 => Ad::getContactOptions()
			);

			// load widgets to locations
			Widget::render($vars);

			$this->display('post/postdata', $vars);
		}
		else
		{
			// return ad for new themes
			return $ad;
		}
	}

	private function _postDataSubmit($location, $category, $catfields)
	{
		// if data submitted
		if (get_request_method() == 'POST' && $_POST['step'] == 2 && Config::nounceCheck(true))
		{

			$rules = array();
			$fields = array();

			$rules['title'] = 'trim|strip_tags|callback_TextTransform::removeSpacesNewlines|xss_clean';
			$rules['description'] = 'trim|required|callback_TextTransform::removeSpacesMaxNewlines[2]|xss_clean';
			$rules['email'] = 'trim|required|strip_tags|strtolower|xss_clean|valid_email';
			$rules['showemail'] = 'trim|intval';
			$rules['location_id'] = 'trim|intval|callback_Location::isPostingAvailableById';
			$rules['category_id'] = 'trim|intval|callback_Category::isPostingAvailableById';
			$rules['othercontactok'] = 'trim|intval';
			$rules['image_token'] = 'trim|alpha_dash';
			$rules['phone'] = 'trim|strip_tags'
					. '|callback_TextTransform::removeSpacesNewlines'
					. '|callback_Config::validatePhone'
					. '|xss_clean';
			if (Config::option('required_phone'))
			{
				$rules['phone'] .= '|required';
			}

			if (!Config::option('hide_agree'))
			{
				$rules['agree'] = 'trim|required|intval';
				$fields['agree'] = __('Agreement to terms and conditions');
			}

			$fields['title'] = __('Title');
			$fields['description'] = __('Description');
			$fields['email'] = __('Email');
			$fields['phone'] = __('Phone');
			$fields['location_id'] = __('Location');
			$fields['category_id'] = __('Category');



			// fix showemail value, if not set then set default value
			if (!isset($_POST['showemail']))
			{
				$_POST['showemail'] = Ad::defaultContactOption();
			}

			if ($_POST['showemail'] < 1)
			{
				// make sure phone is required if hide imail set
				$rules['phone'] .= '|required';
			}


			// add custom field validation
			AdFieldRelation::defineValidationRules($catfields, $rules, $fields);


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);
			$this->validation()->set_message('Config::validatePhone', __('%s format is not valid.'));
			$this->validation()->set_message('Location::isPostingAvailableById', __('%s is not valid.'));
			$this->validation()->set_message('Category::isPostingAvailableById', __('%s is not valid.'));


			$ad_fields = 'title,description,email,showemail,location_id,category_id,othercontactok,phone,cf,price_featured_requested';
			if ($this->validation()->run() && Config::nounceCheck(true) && Captcha::check())
			{
				$data = Record::filterCols($_POST, $ad_fields);
				$ad = new Ad($data);
				$ad->prepareCustomFields($_POST, $catfields);


				// check if available location and category 
				if (!Category::hasValidPostingCategories())
				{
					$ad->category_id = 0;
				}
				if (!Location::hasValidPostingLocations())
				{
					$ad->location_id = 0;
				}

				// check if it is double etnry, need to exact match with no difference
				$double_entry = Ad::findDoubleEntry($ad, array('diff' => 0, 'matches' => 1), true);
				if ($double_entry)
				{
					if (AuthUser::hasPermission(User::PERMISSION_USER, $double_entry->ad2->added_by, false))
					{
						$edit_link = ' <a href="' . Ad::urlEdit($double_entry->ad2) . '" class="button link">' . __('Edit') . '</a>';
					}
					else
					{
						$edit_link = '';
					}

					Flash::set('success', __('This item is already posted.') . $edit_link);

					Ad::redirectToAd($double_entry->ad2, '?item_posted=2');
				}

				// set if approved or not automatically
				$ad->enabled = Ad::autoApprove($ad);


				// save new item
				if ($ad->save())
				{
					// save custom fields
					$ad->saveCustomFields($_POST, $catfields);

					// append images 
					$uploads = Adpics::uploadToAd($ad->id, 'adpic_', $_POST['image_token']);
					$msg_images = '';
					// check if files submitted
					if ($uploads['num_files'])
					{
						// if uploaded
						if ($uploads['num_uploaded'] > 0)
						{
							$msg_images = __('{num} images attached to listing.', array('{num}' => $uploads['num_uploaded']));
						}
						else
						{
							// error on image uploads. what to do??? 
							$this->validation()->set_error(__('Error attaching images to your ad. Please try again.'));

							// delete this ad
							$ad->delete();

							return $ad;
						}
					}
					elseif (Config::option('required_image') && Config::option('ad_image_num'))
					{
						// no image uploaded then display error and prompt upload at least one image 
						$this->validation()->set_error(__('Please upload at least one image.'));

						// delete this ad
						$ad->delete();
						return $ad;
					}

					// ad posted process to activation 
					$this->_processActivation($ad);
				}
				else
				{
					// error
					$this->validation()->set_error(__('Error posting your ad.'));
					return $ad;
				}
			}
		}


		$ad = new Ad($_REQUEST);

		$ad->prepareCustomFields($_REQUEST, $catfields);

		return $ad;
	}

	private function _processActivation($ad)
	{
		// clear not activated ads
		Ad::clearNotActivated();

		// if logged in user and ad posted to this email address then no activation required.
		if (Ad::isVerificationRequired($ad))
		{
			// send activation email
			if (MailTemplate::sendAdVerificationEmail($ad))
			{
				// activation email sent
				Config::displayMessagePage(__('Ad posted successfully and will be published after you verify email address. Please verify email address by following instructions sent to {email} email address.', array('{email}' => $ad->email)), 'success', true);
			}
			else
			{
				// error sending activation email				
				Config::displayMessagePage(__('Ad posted but system failed to send verification email to {email}. Please contact site administrator to inform this issue.', array('{email}' => $ad->email)), 'error', true);
			}
		}
		else
		{
			// check if payment required then redirect to payment 
			Payment::processPayment($ad);

			// activation not required			
			Flash::set('success', __('Ad posted successfully.') . ' ' . Ad::isEnabledMessage($ad));

			// redirect with token to identify with google analytics
			Ad::redirectToAd($ad, '?item_posted=1');
		}
	}

	function activate($id, $code)
	{
		// get ad
		$ad = Ad::findByIdFrom('Ad', $id);
		if (!$ad)
		{
			Config::displayMessagePage(__('Ad not found. It might be deleted if you did not confirm within provided time frame. Please post your ad again.'), 'error', true);
		}

		if (Ad::isVerified($ad))
		{
			// already activated
			Flash::set('success', __('Ad is already verified.') . ' ' . Ad::isEnabledMessage($ad));
			Ad::redirectToAd($ad);
		}


		if (strcmp($ad->verified, $code) != 0)
		{
			// code is not valid 
			Config::displayMessagePage(__('Incorrect verification link. Please make sure that url is exactly the same.'), 'error', true);
		}

		// activate ad make listed if not expired
		Ad::verify($ad);


		// find user by id
		if ($ad->added_by)
		{
			$user = User::findByIdFrom('User', $ad->added_by);
		}

		// if no user then ad posted by not logged in user. Find user by email
		if (!$user)
		{
			$user = User::findByIdFrom('User', $ad->email, 'email');

			// this ad is not associated with this user using added_by field. associate them
			Ad::associateAdsToUser($user);
		}

		if ($user)
		{
			if (!User::isActivated($user))
			{
				User::activateUser($user);
				Flash::set('info', __('Your pending user registration is also verified. You can now login to manage your account.'));
			}
		}
		else
		{
			// no user then create activated user
			$user = User::checkMakeByEmail($ad->email, true, true);

			// send new password by email to this user
			MailTemplate::sendNewUserPassword($user, $user->password_raw);

			// associate all related ads to this new user.
			Ad::associateAdsToUser($user);
			Flash::set('info', __('We have created an account for you to manage your ads. Login details to your account is sent to you by email.'));
		}

		Flash::set('success', __('Ad posting verified.') . ' ' . Ad::isEnabledMessage($ad));


		// redirect with token to identify with google analytics
		Ad::redirectToAd($ad, '?item_posted=1');
	}

	function login()
	{
		// check if user logged in 
		if (AuthUser::isLoggedIn())
		{
			redirect(Language::get_url('post/'));
		}
	}

	public function display($view, $vars = array(), $exit = true)
	{
		// check if theme exists 
		Theme::checkValidThemeLoaded();

		// add custom styles last to take effect 
		Theme::applyCustomStyles($this);

		return parent::display($view, $vars, $exit);
	}

	function payment($step = null)
	{
		//print_r($_REQUEST);
		$link_account = ' <a href="' . Language::get_url('admin/') . '">' . __('Return to your account') . '</a>';

		// echo Benchmark::report();
		switch ($step)
		{
			case 'cancel':
				// payemnt canceled on paypal page by user click
				Config::displayMessagePage(__('Payment process canceled.') . $link_account);
				//echo __('Payment process canceled.');
				break;
			case 'ipn':
				// ipn notification of payment 
				// Payment::processIPN();
				echo __('Payment process ipn.');
				// process ipn 
				$paypal = Payment::processIPN();
				break;
			case 'return':
			default:
				// process ipn 
				$paypal = Payment::processIPN();
				if ($paypal)
				{
					// completed 
					$link = ' <a href="' . Ad::url($paypal->item->Ad) . '">' . __('View your ad') . '</a>';
					Config::displayMessagePage(__('Payment process completed.') . $link);
				}
				else
				{
					// pending 
					Config::displayMessagePage(__('We are processing your payment, if we did not finish in a few seconds, please contact us') . $link_account);
				}
		}


		exit();
	}

	function report()
	{
		$id = $_POST['id'];
		$reason = $_POST['reason'];

		if (AdAbuse::addReport($id, $reason))
		{
			$msg = __('Thank you for reporting this ad as inappropriate');
		}
		else
		{
			$msg = AdAbuse::getErrors();
			if (!$msg)
			{
				$msg = __('Error reporting abuse, please try again later.');
			}
		}

		exit($msg);
	}

	/**
	 * Count ad views. Require $_POST['item_id']. this is called from ajax
	 */
	function cntItem()
	{
		// count item
		$ad_id = intval($_POST['id']);
		if ($ad_id)
		{
			if (Config::nounceCheck(true))
			{

				// increase hits only on listed ads
				Ad::increaseWhere('Ad', array('hits' => "+1"), "id=? AND listed=?", array($ad_id, 1));

				// read total hits and show it 
				$ad = Ad::findByIdFrom('Ad', $ad_id, 'id', MAIN_DB, 'id,hits');
				echo 'ok:' . $ad->hits;
			}
			else
			{
				echo 'Error:' . strip_tags(Validation::getInstance()->messages_dump());
			}
		}

		// perform other lazy cron jobs 
		Config::cron();


		//echo Benchmark::report();
		exit;
	}

	/**
	 * lazy resize image $lazy_url_var = "type x id x ad_id x width x height x crop x thumb" 
	 * 
	 * @param string $lazy_url_var
	 */
	function lazy($lazy_url_var)
	{
		return Adpics::imgResizeLazy($lazy_url_var);
	}

}
