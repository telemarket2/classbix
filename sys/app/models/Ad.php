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
 * class Ad
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Ad extends Record
{

	const TABLE_NAME = 'ad';
	const SHOWEMAIL_NO = 0;
	const SHOWEMAIL_YES = 1;
	const SHOWEMAIL_FORM = 2;
	// ad status. used to determin user karma
	const STATUS_PENDING_APPROVAL = '0';
	// approved items
	const STATUS_ENABLED = '1';
	// only approved and live items can be paused. paused items are not seen in front. 404
	const STATUS_PAUSED = '2';
	// can edit, cannot contact, convert to deleteted by time 
	const STATUS_COMPLETED = '3';
	// incomplete some fields are not filled or image not accepted
	const STATUS_INCOMPLETE = '4';
	// duplicate items cannot be edited, delete them by time if set so
	const STATUS_DUPLICATE = '5';
	// banned cannot edit, delete by time
	const STATUS_BANNED = '6';
	// delete by time, by admin
	const STATUS_TRASH = '7';

	/**
	 * V: viewed publicly
	 * VC: view contact details
	 * VS: view by owner
	 * SEA: status after edit
	 * p: STATUS_PENDING_APPROVAL
	 * 
	 * 								
	  -							V	VC	VS	Edit	SAE	PER
	  -----------------------------------------------------
	  STATUS_PENDING_APPROVAL	-	-	Y	Y		p	M
	  STATUS_ENABLED			Y	Y	Y	Y		p	M
	  STATUS_PAUSED				-	-	Y	Y		p	U
	  STATUS_INCOMPLETE			-	-	Y	Y		p	M
	  STATUS_DUPLICATE			-	-	Y	-		-	M
	  STATUS_BANNED				-	-	Y	-		-	M
	  STATUS_TRASH				-	-	Y	-		-	M
	  STATUS_COMPLETED			Y	-	Y	Y		p	U
	 * 
	 */
	private static $cols = array(
		'id'						 => 1,
		'location_id'				 => 1,
		'category_id'				 => 1,
		'title'						 => 1,
		'description'				 => 1,
		'email'						 => 1,
		'showemail'					 => 1, // 0:hide, 1: show, 2:display form
		'othercontactok'			 => 1,
		'phone'						 => 1,
		'hits'						 => 1,
		'ip'						 => 1,
		'abused'					 => 1,
		'featured'					 => 1, // 0:regular, 1: featured
		'featured_expireson'		 => 1, // time when featured option expires
		'verified'					 => 1, // 1:verified by user email, 0:verified by admin, CODE123544 not verified
		'enabled'					 => 1, // 0:pending approval by admin, 1: approved, 2: paused by user
		'requires_posting_payment'	 => 1, // requires payment for posting 0|1
		'expireson'					 => 1, // expire time
		'listed'					 => 1, // display this ad in listings. 1: running,0: not running
		'updated_at'				 => 1,
		'updated_by'				 => 1,
		'added_at'					 => 1,
		'added_by'					 => 1,
		'published_at'				 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public function __destruct()
	{
		unset($this->AdFieldRelation);
		unset($this->Location);
		unset($this->Category);
		unset($this->User);
		unset($this->Adpics);
		unset($this->CategoryFieldRelation);
		unset($this->related);
		unset($this->prev_next->prev);
		unset($this->prev_next->next);
		unset($this->prev_next);
	}

	function beforeInsert()
	{
		if (!isset($this->added_at))
		{
			$this->published_at = REQUEST_TIME;
			$this->added_at = REQUEST_TIME;
			$this->updated_at = REQUEST_TIME;
		}
		else
		{
			$this->published_at = $this->added_at;
			$this->updated_at = $this->added_at;
		}


		// lowercase email for consistency 
		if (isset($this->email))
		{
			$this->email = strtolower($this->email);
		}

		// set user_id $this->added_by
		self::assignUserId($this);

		// set same user to updated as well
		$this->updated_by = $this->added_by;


		if (!isset($this->ip))
		{
			$this->ip = Input::getInstance()->ip_address();
		}
		$this->othercontactok = intval($this->othercontactok);

		Ad::fixText($this);
		Ad::fixTitle($this);

		// create activation code if not preset to 0
		if (!isset($this->verified))
		{
			if (self::isVerificationRequired($this))
			{
				$this->verified = User::genActivationCode('v{code}');
			}
			else
			{
				$this->verified = 1; // verified by logged in user
			}
		}

		$this->expireson = $this->added_at + Ad::getExpireDays();

		// check if requires payemnt for posting 
		$this->setRequiresPostingPaymentValue();


		if ($this->isListedByValues())
		{
			// check if enabled and not expired then make listed 1
			$this->listed = 1;
		}

		// display at least one contact method 
		$this->fixContactMethod();

		return true;
	}

	/**
	 * Find user id and assign to $ad->added_by field if it is not already set.
	 * 
	 * @param type $ad
	 */
	static public function assignUserId($ad)
	{
		// check if not set user id 
		if (!isset($ad->added_by))
		{
			// check if user logged in, if not then assign to user with same email
			$user_id = null;
			if (AuthUser::$user->id)
			{
				// set logged in user id 
				$user_id = AuthUser::$user->id;
			}
			else
			{
				// find user with same email address
				$user = User::findBy('email', $ad->email);
				if ($user)
				{
					$user_id = $user->id;
				}
			}
			$ad->added_by = $user_id;
		}
	}

	/**
	 * get first match with given percentage
	 * 
	 * @param Ad $ad
	 * @param int $options
	 * @return Ad
	 */
	public static function findDoubleEntry($ad, $options = array(), $renew_when_found = false)
	{
		$similar_item = null;


		// fix user_id if not set 
		Ad::assignUserId($ad);

		// fix title  before using 
		Ad::fixText($ad);
		Ad::fixTitle($ad);

		// get latest 100 items of user 
		$num = 100;


		// define detection ptions 
		$duplicatesObj = new stdClass();
		$duplicatesObj->check_ad = $ad;
		foreach ($options as $k => $v)
		{
			$duplicatesObj->{$k} = $v;
		}
		// get first matching ad according to given criteria
		$duplicatesObj->return_type = 'first';

		// first get latest 100 items from same owner 
		if (!isset($ad->_latest_same_owner))
		{

			$arr_enabled = array(
				Ad::STATUS_PENDING_APPROVAL,
				Ad::STATUS_ENABLED,
				Ad::STATUS_PAUSED,
				Ad::STATUS_COMPLETED
			);
			$arr_enabled_ = Ad::quoteArray($arr_enabled);

			$and_where = " AND enabled IN (" . implode(',', $arr_enabled_) . ") ORDER BY id DESC LIMIT " . $num;

			if ($ad->added_by)
			{
				$where = "added_by=? " . $and_where;
				$values = array($ad->added_by);
			}
			else
			{
				// check by email 
				$where = "email=? " . $and_where;
				$values = array($ad->email);
			}

			$ad->_latest_same_owner = Ad::findAllFrom('Ad', $where, $values);
		}

		if ($ad->_latest_same_owner)
		{
			//  now check text similarity if difference is 0 then it is same text 
			$duplicatesObj->check_against = $ad->_latest_same_owner;
			$similar_item = Ad::checkDuplicates($duplicatesObj, 'one_to_many');
		}



		// if not found and there are more items to check by this user then try them 
		if (!$similar_item && count($ad->_latest_same_owner) >= $num)
		{

			if (!isset($ad->_similar_same_owner))
			{

				// exclude existing ids
				$arr_exclude = array($ad->id);
				foreach ($ad->_latest_same_owner as $latest)
				{
					$arr_exclude[] = $latest->id;
				}

				$ad->_similar_same_owner = Ad::findSimilar($ad, array(
							'same_user'		 => true,
							'exclude_ids'	 => $arr_exclude,
							'limit'			 => $num
				));
			}

			if ($ad->_similar_same_owner)
			{
				//  now check text similarity if difference is 0 then it is same text 
				$duplicatesObj->check_against = $ad->_similar_same_owner;
				$similar_item = Ad::checkDuplicates($duplicatesObj, 'one_to_many');
			}
		}

		if ($similar_item && $ad->added_by && $renew_when_found)
		{
			// check if it is expired renew it 
			$user = User::findByIdFrom('User', $ad->added_by);
			Ad::statusRenewByIds(array($similar_item->id), $user);
		}

		return $similar_item;
	}

	/**
	 * Check for duplicates given $duplicatesObj
	 * Ad			$duplicatesObj->check_ad
	 * Ad			$duplicatesObj->check_ad2
	 * array of Ads $duplicatesObj->check_against
	 * boolean		$duplicatesObj->owner
	 * integer		$duplicatesObj->max_check do not check more than this loops, to prevent long page loads
	 * string		$duplicatesObj->return_type first|none
	 *
	 * Match parameters:
	 * 	$duplicatesObj->perc
	 * 	$duplicatesObj->perc_deviation
	 * 	$duplicatesObj->matches
	 * 	$duplicatesObj->matches_max
	 * 	$duplicatesObj->diff
	 * 
	 * 
	 * @param Object $duplicatesObj
	 * @param string $type single(check_ad2)|one_to_many(check_against) |array(check_against)
	 * @return boolean
	 */
	static public function checkDuplicates($duplicatesObj, $type = 'single')
	{

		// set defaults if not already set
		if (!isset($duplicatesObj->all_set))
		{
			$duplicatesObj->all_set = true;

			$arr_defaults = array(
				'perc'				 => 90,
				'perc_deviation'	 => 50,
				'matches'			 => 5,
				'matches_max'		 => 0,
				'diff'				 => 50,
				'return_type'		 => 'none',
				'owner'				 => false,
				'max_check'			 => 1000,
				'check_against'		 => array(),
				'ads_checked_bool'	 => array()
			);

			foreach ($arr_defaults as $k => $v)
			{
				if (!isset($duplicatesObj->{$k}))
				{
					$duplicatesObj->{$k} = $v;
				}
			}
		}


		switch ($type)
		{
			case 'single':
				// check one item to another
				$ad = $duplicatesObj->check_ad;
				$ad_d = $duplicatesObj->check_ad2;

				$key = min($ad->id, $ad_d->id) . '|' . max($ad->id, $ad_d->id);

				$check = $ad_d && $ad->id != $ad_d->id;
				$check = $check && !isset($duplicatesObj->ads_checked_bool[$key]);
				if ($duplicatesObj->owner)
				{
					// must be same owner 
					$check = $check && $ad->added_by == $ad_d->added_by;
				}

				if ($check)
				{
					$duplicatesObj->ads_checked_bool[$key] = true;

					// normalize if needed
					if (!isset($ad->normlized))
					{
						$ad->normlized = TextTransform::text_normalize($ad->title . ' ' . $ad->description);
					}
					if (!isset($ad_d->normlized))
					{
						$ad_d->normlized = TextTransform::text_normalize($ad_d->title . ' ' . $ad_d->description);
					}

					// calculate similarity 
					$similarity = TextTransform::text_similarity($ad->normlized, $ad_d->normlized, true);
					if ($similarity->similarity >= $duplicatesObj->perc &&
							$similarity->len_same >= $duplicatesObj->matches &&
							($similarity->len_same <= $duplicatesObj->matches_max || $duplicatesObj->matches_max == 0) &&
							($duplicatesObj->perc_deviation >= abs($similarity->similarity_max - $similarity->similarity_min)) &&
							$similarity->len_diff <= $duplicatesObj->diff)
					{
						if (!isset($ad->arr_similarity))
						{
							$ad->arr_similarity = array();
						}
						if (!isset($ad->duplicates))
						{
							$ad->duplicates = array();
						}

						$ad->arr_similarity[$ad_d->id] = $similarity;
						$ad->duplicates[$ad_d->id] = $ad_d;
					}
				}

				if ($duplicatesObj->return_type === 'first')
				{
					// return first found record, and escaper further loop
					if (isset($ad->duplicates) && count($ad->duplicates))
					{
						// has value return first one with similarity score
						$return = new stdClass();
						$return->ad = $ad;
						$return->ad2 = reset($ad->duplicates);
						$return->similarity = reset($ad->arr_similarity);

						return $return;
					}
				}


				break;
			case 'one_to_many':
				// check one ad to array of ads
				Benchmark::cp('Ad::checkDuplicates:one_to_many:' . count($duplicatesObj->check_against));
				foreach ($duplicatesObj->check_against as $ad_d)
				{
					$duplicatesObj->check_ad2 = $ad_d;
					$return = Ad::checkDuplicates($duplicatesObj, 'single');

					// return or continue
					if ($duplicatesObj->return_type === 'first' && $return)
					{
						// found record then end function here
						return $return;
					}
				}
				break;
			case 'array':
				if (count($duplicatesObj->check_against) < 2)
				{
					// array is single item. cant check it
					return false;
				}
				Benchmark::cp('Ad::checkDuplicates:array:' . count($duplicatesObj->check_against));
				// check items inside array to self 
				foreach ($duplicatesObj->check_against as $ad)
				{
					foreach ($duplicatesObj->check_against as $ad2)
					{
						if ($ad->id < $ad2->id)
						{
							// keep track of max loop if set
							if (count($duplicatesObj->ads_checked_bool) > $duplicatesObj->max_check)
							{
								Benchmark::cp('Ad::checkDuplicates:array:reaqchedMax:' . count($duplicatesObj->ads_checked_bool));
								return false;
							}
							$duplicatesObj->check_ad = $ad;
							$duplicatesObj->check_ad2 = $ad2;

							$return = Ad::checkDuplicates($duplicatesObj, 'single');

							// return or continue
							if ($duplicatesObj->return_type === 'first' && $return)
							{
								// found record then end function here
								return $return;
							}
						}
					}
				}
				break;
		}

		return false;
	}

	/**
	 * Search DB for similar items using title and description 
	 * 
	 * @param type $ad
	 * @param type $options
	 * @return type
	 */
	public static function findSimilar($ad, $options = array())
	{
		$options_default = array(
			'same_user'		 => false,
			'same_category'	 => false,
			'same_location'	 => false,
			'exclude_ids'	 => $ad->id ? array($ad->id) : array(),
			'arr_enabled'	 => array(
				Ad::STATUS_PENDING_APPROVAL,
				Ad::STATUS_ENABLED,
				Ad::STATUS_PAUSED,
				Ad::STATUS_COMPLETED
			),
			'limit'			 => 100
		);

		$options = array_merge($options_default, $options);

		$return = array();


		// search 
		// loaded not all ads for this user, try loading ads with boolean search
		$q_n = TextTransform::text_normalize($ad->title . ' ' . $ad->description, 'search');

		// use simplified version 
		$q_n = TextTransform::text_normalize_simplify_search($q_n);

		// check if not already loaded and has $q_n value
		if (strlen($q_n))
		{
			// build settings array 
			$arr_where = array();
			$arr_values = array();

			if ($options['same_user'])
			{
				$arr_where[] = 'ad.added_by=?';
				$arr_values[] = $ad->added_by;
			}

			if ($options['same_category'])
			{
				$arr_where[] = 'ad.category_id=?';
				$arr_values[] = $ad->category_id;
			}

			if ($options['same_location'])
			{
				$arr_where[] = 'ad.location_id=?';
				$arr_values[] = $ad->location_id;
			}

			if ($options['arr_enabled'])
			{
				$arr_enabled = Ad::quoteArray($options['arr_enabled']);
				$arr_where[] = "enabled IN (" . implode(',', $arr_enabled) . ")";
			}

			if ($options['exclude_ids'])
			{

				if (count($options['exclude_ids']) == 1)
				{
					// single id
					$arr_where[] = 'ad.id!=?';
					$arr_values[] = intval($options['exclude_ids'][0]);
				}
				else
				{
					// multiple ids 
					$ids = Ad::quoteArray($options['exclude_ids']);
					$arr_where[] = 'ad.id NOT IN (' . implode(', ', $ids) . ')';
				}
			}


			if ($options['limit'] < 1)
			{
				$options['limit'] = 100;
			}


			if ($arr_where)
			{
				$str_where = implode(' AND ', $arr_where) . ' AND ';
			}
			else
			{
				$str_where = '';
			}

			$arr_values[] = $q_n;


			$sql = "SELECT ad.*,"
					. " MATCH (aft.title) AGAINST (" . Record::escape($q_n) . " IN BOOLEAN MODE)*10 AS score1, "
					. " MATCH (aft.description) AGAINST (" . Record::escape($q_n) . " IN BOOLEAN MODE) AS score2 "
					. " FROM " . Ad::tableNameFromClassName('Ad') . " ad, "
					. " " . AdFulltext::tableNameFromClassName('AdFulltext') . " aft "
					. " WHERE " . $str_where . " ad.id=aft.id AND MATCH (aft.description) AGAINST (?  IN BOOLEAN MODE) "
					. " ORDER BY (score1)+(score2) DESC "
					. " LIMIT " . intval($options['limit']);



			$return = Ad::queryUsingIds($sql, $arr_values);
		}

		return $return;
	}

	/**
	 * Get time in seconds after which ed will expire to store in database
	 * add this to current time, 
	 * returns in seconds 
	 * 
	 * @return int
	 */
	public static function getExpireDays()
	{
		// FIXME do something for not expiring ads
		// calculate expire time
		$expire_days = intval(Config::option('expire_days')) * 3600 * 24;
		if (!$expire_days)
		{
			$expire_days = 9000000000;
		}

		return $expire_days;
	}

	static public function fixText($ad)
	{
		// remove tags 
		if (isset($ad->title))
		{
			$ad->title = strip_tags($ad->title);
		}


		if (isset($ad->description))
		{
			$ad->description = strip_tags($ad->description);
		}
	}

	function setRequiresPostingPaymentValue()
	{
		PaymentPrice::appendPaymentPrice($this);
		$this->requires_posting_payment = $this->PaymentPrice->price_post > 0 ? 1 : 0;
	}

	function afterInsert()
	{
		// add payment request if requested 
		Payment::saveFeaturedRequest($this);

		AdAbuse::checkSpam($this);

		// convert to fulltext index
		AdFulltext::process($this);

		// clear cache 
		SimpleCache::delete('ad');

		return true;
	}

	function afterUpdate()
	{
		AdAbuse::checkSpam($this);

		// convert to fulltext index
		AdFulltext::process($this);

		// clear cache 
		SimpleCache::delete('ad');

		return true;
	}

	/**
	 * Check id text changed and update approved value or ad.
	 * uses $ad->title_old , $ad->description_old
	 * 
	 * @param Ad $ad
	 */
	static public function checkTextChange($ad)
	{
		if (isset($ad->title_old) || isset($ad->description_old))
		{
			$is_changed = TextTransform::text_is_changed($ad->title . ' ' . $ad->description, $ad->title_old . ' ' . $ad->description_old);
			if ($is_changed)
			{
				// send for moderation if required by settings
				Ad::autoApproveChanged($ad);
			}
		}
	}

	function afterDelete()
	{
		// clear cache 
		SimpleCache::delete('ad');

		return true;
	}

	function beforeUpdate()
	{
		Ad::fixText($this);
		Ad::fixTitle($this);

		$this->updated_at = REQUEST_TIME;
		$this->updated_by = AuthUser::$user->id;

		// lowercase email for consistency 
		if (isset($this->email))
		{
			$this->email = strtolower($this->email);
		}

		// lowercase email for consistency 
		if (isset($this->old_email))
		{
			$this->old_email = strtolower($this->old_email);
		}

		// if email is changed then generate new verification code to verify ad
		if (strlen($this->old_email) && strcmp($this->old_email, $this->email) != 0)
		{
			if (self::isVerificationRequired($this))
			{
				$this->verified = User::genActivationCode('v{code}');
			}
			else
			{
				$this->verified = 1; // verified by logged in user
			}
		}


		// renew published at date if applicable
		if (Config::option('renew_ad') && isset($this->published_at))
		{
			// check if it is own ad
			// check if minimum renew period passed
			$renew_if_older_time = Ad::getMinRenewDate();
			if (AuthUser::$user->id == $this->added_by && $this->published_at < $renew_if_older_time)
			{
				// set new date 
				$this->published_at = REQUEST_TIME;

				// check if expireson can be extended
				if (isset($this->expireson) && Ad::isExtendable($this))
				{
					$expireson = REQUEST_TIME + Ad::getExpireDays();
					if ($this->expireson < $expireson)
					{
						// extend expire date 
						$this->expireson = $expireson;
					}
				}
			}
		}


		// update listed value if all of this exist
		if (isset($this->expireson) && isset($this->enabled) && isset($this->verified))
		{
			if ($this->isListedByValues())
			{
				// check if enabled and not expired then make listed 1
				$this->listed = 1;
			}
		}

		$this->fixContactMethod();

		// check if category changed then update payment requirement 
		if ((isset($this->category_id_old) || isset($this->location_id_old)) && ($this->category_id_old != $this->category_id || $this->location_id_old != $this->location_id))
		{
			//update payment requiremrnt 
			$this->setRequiresPostingPaymentValue();
		}


		return true;
	}

	/**
	 * if no phot set and email hidden swith email to use contact form  
	 */
	function fixContactMethod()
	{
		// check if some contact info visible 
		if (!strlen($this->phone) && $this->showemail == 0)
		{
			// phone not set and email is hidden then change email to display contact form 
			$this->showemail = 2;
		}
	}

	/**
	 * prepare and save all costom field values to AdFieldRelation. delete values with empty string
	 * 
	 * @param array $post posted data by ad submit form. will prepare this values and save.
	 * @param CategoryFieldRelation $catfields 
	 */
	function saveCustomFields($post, $catfields)
	{
		// convert posted value to array of objects 
		$this->prepareCustomFields($post, $catfields, true);

		$this->saveCustomFieldsPrepared();
	}

	/**
	 * Save all costom field values to AdFieldRelation. delete values with empty string
	 */
	function saveCustomFieldsPrepared()
	{
		if ($this->AdFieldRelation)
		{
			foreach ($this->AdFieldRelation as $afr)
			{
				AdFieldRelation::saveVal($this->id, $afr->field_id, $afr->val);
			}
		}
	}

	/**
	 * Covert posted values to array of AdFieldRelation object
	 * 
	 * @param array $post posted data by ad submit form
	 * @param array $catfields CategoryFieldRelation
	 * @return AdFieldRelation 
	 */
	function prepareCustomFields($post, $catfields, $convert = true)
	{
		unset($this->AdFieldRelation);

		// if set then prepare custom fields to save or edit in form
		if ($catfields)
		{
			foreach ($catfields as $cf)
			{
				AdFieldRelation::prepareVal($cf->AdField, $this, $post['cf'][$cf->AdField->id], false, $convert);
			}
		}

		return $this->AdFieldRelation;
	}

	public function isListedByValues()
	{
		return (self::isVerified($this) && $this->expireson > REQUEST_TIME && $this->enabled == 1 && $this->requires_posting_payment == 0);
	}

	/**
	 * checks if ad title not set then will create title from description by using first 100 chars
	 */
	public static function fixTitle($ad)
	{
		if (strlen($ad->description) && !strlen($ad->title))
		{
			$ad->title = TextTransform::excerpt($ad->description, 65, '');
		}

		// remove phone number from title
		if (Config::option('hide_phone_title'))
		{
			$ad->title = TextTransform::removePhoneNumber($ad->title);
		}

		// remove spaces 
		$ad->title = TextTransform::removeSpacesNewlines($ad->title);

		if (!strlen($ad->title))
		{
			$ad->title = __('[no title]');
		}
	}

	public static function statusApproveByIds($ids, $send_ack_mail = false)
	{
		// val= 1: approved, 0: pending approval, 2: paused 
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			if ($send_ack_mail)
			{
				// get pending approval ad ids for later use
				// not listed pending approval ads only
				$not_listed = Ad::findAllFrom('Ad', 'listed=0 AND enabled=? AND id IN (' . implode(',', $ids_) . ')', array(Ad::STATUS_PENDING_APPROVAL), MAIN_DB, 'id');
				foreach ($not_listed as $nl)
				{
					$not_listed_ids[] = $nl->id;
				}
			}

			// approve only pending approval ads 
			$return = self::changeEnabled($ids, array(
						'enabled'		 => Ad::STATUS_ENABLED,
						'published_at'	 => REQUEST_TIME
							), Ad::STATUS_PENDING_APPROVAL);

			// approve banned, deleted, duplicate, ads. this is for converting them back to enabled state by admin
			// instead of unban, undelete, unduplicate use one funciton approve
			$return = self::changeEnabled($ids, Ad::STATUS_ENABLED, array(
						Ad::STATUS_BANNED,
						Ad::STATUS_TRASH,
						Ad::STATUS_DUPLICATE,
						Ad::STATUS_INCOMPLETE,
						Ad::STATUS_COMPLETED
			));


			if ($send_ack_mail && $not_listed_ids)
			{
				MailTemplate::sendApprovedDelayed($not_listed_ids);
			}

			return $return;
		}
		return false;
	}

	/**
	 * Unapprove previously approved ads. this is different from pausing. 
	 * paused ads can be deleted, or unpaused then unapproved
	 * 
	 * @param type $ids
	 * @return boolean
	 */
	public static function statusUnapproveByIds($ids)
	{
		return self::changeEnabled($ids, Ad::STATUS_PENDING_APPROVAL, Ad::STATUS_ENABLED);
	}

	/**
	 * Pause previously approved ads 
	 * Pause approved ads by given user
	 * 
	 * @param array $ids
	 * @return boolean
	 */
	public static function statusPauseByIds($ids, $user = null)
	{
		return self::changeEnabled($ids, Ad::STATUS_PAUSED, Ad::STATUS_ENABLED, $user);
	}

	/**
	 * unPause previously paused ads 
	 * unPause previously paused ads by given user. 
	 * Send to moderation or enable depending on (settings, ad + current user)
	 * Also update published at time
	 * 
	 * @param array $ids
	 * @param User $ids if null then run by moderator
	 * @return boolean
	 */
	public static function statusUnpauseByIds($ids, $user = null)
	{
		// if paused ad edited then updated_at will be igger than published_at
		// we should send to moderation if needed.
		// this is done becuase paused ad can be edited then unpaused. edited ads should go to moderation if moderation enabled in settings.
		$ads = Ad::findManyByIdFrom('Ad', $ids);
		$arr_mod = array();
		$arr_enable = array();
		foreach ($ads as $ad)
		{
			// check if user matches: no user set or user matches added_by
			$user_match = (!$user) || ($ad->added_by == $user->id);
			if ($user_match && $ad->enabled == Ad::STATUS_PAUSED)
			{
				// process this ad, updated after approval send to mod if set in settings
				if ($ad->updated_at > $ad->published_at)
				{
					// return to auto moderate state
					$enabled = Ad::autoApprove($ad);
					if ($enabled)
					{
						$arr_enable[] = $ad->id;
					}
					else
					{
						$arr_mod[] = $ad->id;
					}
				}
				else
				{
					// return to enabled state
					$arr_enable[] = $ad->id;
				}
			}
		}

		// make enabled not modified items
		$return1 = Ad::changeEnabled($arr_enable, Ad::STATUS_ENABLED, Ad::STATUS_PAUSED, $user);
		// make pending modified items
		$return2 = Ad::changeEnabled($arr_mod, Ad::STATUS_PENDING_APPROVAL, Ad::STATUS_PAUSED, $user);

		$return = $return1 || $return2;

		$renew_ad = Config::option('renew_ad');
		// IMPORTANT always check before renewing on unpause
		if ($renew_ad && $arr_enable)
		{
			// renewing enabled . 
			// if it is not enabled then do not renew when unpasused. 
			// ads unpaused by user or mod, renew it
			Ad::statusRenewByIds($arr_enable, $user);
		}
		return $return;
	}

	public static function changeEnabled($ids, $new_val, $old_val = null, $user = null)
	{
		// val= 1: approved, 0: pending approval, 2: paused 
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			$arr_where = array();
			if (!is_null($old_val))
			{
				if (is_array($old_val))
				{
					$arr_where[] = "enabled IN ('" . implode("','", $old_val) . "')";
				}
				else
				{
					$arr_where[] = 'enabled=' . intval($old_val);
				}
			}
			if (!is_null($user))
			{
				$arr_where[] = 'added_by=' . intval($user->id);

				// delete cached user ad counts 
				User::countAdTypeClearCache($user->id);
			}

			$arr_where[] = 'id IN (' . implode(',', $ids_) . ')';

			$where = implode(' AND ', $arr_where);

			if (!is_array($new_val))
			{
				$new_val = array('enabled' => $new_val);
			}

			// set updated at time for auto deleting later this ads 
			$new_val['updated_at'] = REQUEST_TIME;

			$return = self::update('Ad', $new_val, $where);

			// update listed values 
			Ad::updateListed(true, $ids);

			// delete cached ad coutns for users 
			User::countAdTypeClearCacheByAdId($ids);


			return $return;
		}
		return false;
	}

	public static function statusCompletedByIds($ids, $user = null)
	{
		// only enabled ads can be completed
		$return = Ad::changeEnabled($ids, Ad::STATUS_COMPLETED, array(Ad::STATUS_ENABLED, Ad::STATUS_PAUSED), $user);
		// also set expired date to now
		return $return;
	}

	public static function statusTrashByIds($ids)
	{
		//  Any ad can be moved to trash by moderator
		$return = Ad::changeEnabled($ids, Ad::STATUS_TRASH);
		return $return;
	}

	public static function statusBanByIds($ids)
	{
		// any ad can be banned by moderator
		return Ad::changeEnabled($ids, Ad::STATUS_BANNED);
	}

	public static function statusUnBanByIds($ids)
	{
		// unban ad and make enabled 
		return Ad::changeEnabled($ids, Ad::STATUS_ENABLED, Ad::STATUS_BANNED);
	}

	public static function statusDuplicateByIds($ids)
	{
		// set 
		// any ad can be marked as duplicate by moderator
		return Ad::changeEnabled($ids, Ad::STATUS_DUPLICATE);
	}

	public static function statusIncompleteByIds($ids)
	{
		// items has wrong description, wrong custom fields, wrong or not appropriate image 
		// pending, or enabled ads can be incomplete 
		// when they edited change status to pending 
		// need to mark them for knowing that it was incomplete
		return Ad::changeEnabled($ids, Ad::STATUS_INCOMPLETE);
	}

	public static function verifyByIds($ids, $val = 1, $user = null)
	{
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			$where = '';
			$values = array();
			if ($user)
			{
				// verify only user ads with matching email address
				$where = "added_by=? AND email=? AND ";
				$values[] = $user->id;
				$values[] = $user->email;
			}

			$where .= "id IN (" . implode(',', $ids_) . ") AND verified NOT IN ('0','1')";
			$return = self::update('Ad', array('verified' => ($val ? 1 : 0), 'published_at' => REQUEST_TIME), $where, $values);

			// associate ads to users
			Ad::associateVerifiedAdsToUsers($ids);

			// update listed ads
			Ad::updateListed(true, $ids);

			return $return;
		}
		return false;
	}

	public static function extendByIds($ids, $days = 10)
	{
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			// convert days to seconds
			$expend_time = $days * 24 * 3600;

			// extend not expired ads, add to old expiry time
			$sql = "UPDATE " . Ad::tableNameFromClassName('Ad') . "
				SET expireson=expireson+" . intval($expend_time) . " 
				WHERE id IN (" . implode(',', $ids_) . ") AND expireson>" . REQUEST_TIME;
			$return = Ad::query($sql);

			// extend expired ads, add to current time
			$sql = "UPDATE " . Ad::tableNameFromClassName('Ad') . "
				SET expireson=" . intval(REQUEST_TIME + $expend_time) . " 
				WHERE id IN (" . implode(',', $ids_) . ") AND expireson>0 AND expireson<" . REQUEST_TIME;
			Ad::query($sql);

			// update listed ads
			Ad::updateListed(true, $ids);

			return $return;
		}
		return false;
	}

	/**
	 * Update given ad as verified 
	 * 
	 * @param Ad $ad
	 * @param integer $val 1: verified by email, 0: verified by admin
	 * @return boolean
	 */
	static public function verify($ad, $val = 1)
	{
		// 1: verified by email, 0: verified by admin

		$ad->verified = $val;
		$ad->listed = $ad->isListedByValues() ? 1 : 0;


		$arr_update = array(
			'verified'	 => $ad->verified,
			'listed'	 => $ad->listed
		);

		if ($ad->listed)
		{
			$ad->published_at = REQUEST_TIME;
			$arr_update['published_at'] = $ad->published_at;
		}

		// update db 
		// use this instead updating all fields with $ad->save();
		$return = Ad::update('Ad', $arr_update, 'id=?', array($ad->id));

		// update listed ads
		Ad::updateListed();

		return $return;
	}

	public function markAsPaidToPost()
	{
		// remove payment requirement for listing
		$this->requires_posting_payment = 0;

		$this->listed = $this->isListedByValues() ? 1 : 0;
		$return = $this->save();

		// update listed ads
		Ad::updateListed();

		return $return;
	}

	/**
	 * reset abuse report count to 0 for given ad ids
	 * @param array $ids
	 * @return boolean 
	 */
	public static function resetAbuseByIds($ids)
	{
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			$where = 'id IN (' . implode(',', $ids_) . ')';
			$return1 = self::update('Ad', array('abused' => 0), $where);

			// also delete reports from AdAbuse
			$where = 'ad_id IN (' . implode(',', $ids_) . ')';
			$return2 = AdAbuse::deleteWhere('AdAbuse', $where);

			return $return1 && $return2;
		}
		return false;
	}

	/**
	 * block ips related to given ad ids
	 * @param array $ids
	 * @return boolean|int number of blocked ips
	 */
	public static function blockIpByIds($ids)
	{
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			$ips = array();
			$where = 'id IN (' . implode(',', $ids_) . ')';
			$ads = Ad::findAllFrom('Ad', $where, array(), MAIN_DB, 'id,ip');
			foreach ($ads as $ad)
			{
				$ips[$ad->ip] = $ad->ip;
			}

			return IpBlock::blockIps($ips);
		}
		return false;
	}

	/**
	 * remove requires_posting_payment for given ad ids
	 * 
	 * @param array $ids
	 * @return boolean 
	 */
	public static function markAsPaidByIds($ids)
	{
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			$where = 'id IN (' . implode(',', $ids_) . ')';
			$return = self::update('Ad', array('requires_posting_payment' => 0), $where);

			// update listed ads
			Ad::updateListed(true, $ids);

			return $return;
		}
		return false;
	}

	/**
	 * remove requires_posting_payment for given ad ids
	 * 
	 * @param array $ids
	 * @return boolean 
	 */
	public static function makeFeaturedByIds($ids)
	{
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			// featured expire time 
			$featured_expireson = REQUEST_TIME + Config::option('featured_days') * 24 * 3600;

			// set featured
			$where = 'id IN (' . implode(',', $ids_) . ') AND featured=0';
			$return = self::update('Ad', array('featured' => 1, 'featured_expireson' => $featured_expireson, 'published_at' => REQUEST_TIME), $where);

			// extend expired ads if required 
			self::update('Ad', array('expireson' => $featured_expireson, 'published_at' => REQUEST_TIME), 'id IN (' . implode(',', $ids_) . ') AND expireson<featured_expireson');

			// remove featured payment request, because it is featured now
			Payment::deleteWhere('Payment', 'ad_id IN (' . implode(',', $ids_) . ') AND item_type=?', array(Payment::ITEM_TYPE_FEATURED_REQUESTED));

			// update listed ads
			Ad::updateListed(true, $ids);

			// update featured, remove if expired 
			Ad::updateFeatured(true, $ids);

			return $return;
		}
		return false;
	}

	/**
	 * renew ads by updating published_at time 
	 * update expireson time if it is smaller than regular expire time for new ads. 
	 * 
	 * @param array $ids
	 * @param string $where_renew if not moderator then extra query to renew ads passed minimum renew days 
	 * @return boolean 
	 */
	public static function statusRenewByIds($ids, $this_user_only = null)
	{
		$renew_ad = Config::option('renew_ad');
		$permission_moderator = AuthUser::hasPermission(User::PERMISSION_MODERATOR);
		if ($permission_moderator || $renew_ad)
		{
			// renew if moderator or renewing enabled in settings
			// add quoted values
			$ids_ = self::ids2quote($ids);
			if ($ids_)
			{
				// mods can renew any ad
				$where_renew = '';
				$where_extend = '';
				$values_extend = array();
				$expireson = REQUEST_TIME + Ad::getExpireDays();
				if (!$this_user_only && !$permission_moderator)
				{
					// not mod, and user not set then set user to current logged in user 
					$this_user_only = AuthUser::$user;
				}
				if ($this_user_only)
				{
					// user can renew items with status enabled,completed,paused
					$where_status = " AND enabled IN ('" . Ad::STATUS_ENABLED . "','" . Ad::STATUS_COMPLETED . "','" . Ad::STATUS_PAUSED . "')";
					// user set then renew explicitly this user ads
					// it is used when mod unpauses many ads, ads of other users should not be renewed when unpaused! 
					// user can renew only own ads if they are older than minimum renew period
					$where_renew = $where_status . ' AND added_by=' . intval($this_user_only->id)
							. ' AND published_at<' . Ad::getMinRenewDate();

					$where_extend = $where_status . ' AND added_by=' . intval($this_user_only->id)
							. ' AND expireson<?';
					$values_extend = array($expireson);
				}

				// set published at time  to now
				$where_ids = 'id IN (' . implode(',', $ids_) . ') ';
				$return = self::update('Ad', array('published_at' => REQUEST_TIME), $where_ids . $where_renew);

				// update expires at time if ad expired or expires in less than 'expire_days' 
				self::update('Ad', array('expireson' => $expireson), $where_ids . $where_extend, $values_extend);


				// move completed and paused listings to enabled 
				$arr_old_val = array(
					Ad::STATUS_COMPLETED,
					Ad::STATUS_PAUSED
				);

				Ad::changeEnabled($ids, Ad::STATUS_ENABLED, $arr_old_val, $this_user_only);


				//echo '[expireson:' . $expireson . ']';
				//echo Benchmark::report();
				//exit;
				// update listed ads
				Ad::updateListed(true, $ids);

				// update featured, remove if expired 
				Ad::updateFeatured(true, $ids);

				return $return;
			}
		}
		return false;
	}

	/**
	 * mark selected ads as not featured
	 * 
	 * @param array $ids
	 * @return boolean 
	 */
	public static function disableFeaturedByIds($ids)
	{
		// add quoted values
		$ids_ = self::ids2quote($ids);
		if ($ids_)
		{
			// remove featured bit
			$where = 'id IN (' . implode(',', $ids_) . ') AND featured=1';
			$return = self::update('Ad', array('featured' => 0, 'featured_expireson' => 0), $where);

			// remove featured payment request, because it is featured disabled
			Payment::deleteWhere('Payment', 'ad_id IN (' . implode(',', $ids_) . ') AND item_type=?', array(Payment::ITEM_TYPE_FEATURED_REQUESTED));

			// update featured, remove if expired 
			Ad::updateFeatured(true, $ids);

			return $return;
		}
		return false;
	}

	public static function deleteByIds($ids)
	{
		$ads = array();
		$deleted = 0;
		$ids_ = self::ids2quote($ids);

		if ($ids_)
		{
			$where = 'id IN (' . implode(',', $ids_) . ')';
			$ads = self::findAllFrom('Ad', $where);
		}
		foreach ($ads as $ad)
		{
			if ($ad->delete())
			{
				$deleted++;
			}
		}

		if ($deleted == count($ads))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function deleteByIdsUser($ids, $user)
	{
		$ads = array();
		$deleted = 0;
		$ids_ = self::ids2quote($ids);

		if ($ids_)
		{
			// get ads adde by this user
			$where = 'added_by=' . intval($user->id) . ' AND id IN (' . implode(',', $ids_) . ')';
			$ads = self::findAllFrom('Ad', $where);
		}
		foreach ($ads as $ad)
		{
			if ($ad->delete())
			{
				$deleted++;
			}
		}

		if ($deleted == count($ads))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function ids2quote($ids)
	{
		$ids = Record::checkMakeArray($ids);

		return Record::quoteArray($ids);
	}

	function beforeDelete()
	{
		$return = true;
		// delete images
		$adpics = Adpics::findAllFrom('Adpics', 'ad_id=?', array($this->id));
		foreach ($adpics as $adpic)
		{
			Adpics::deleteImage($adpic->img);
		}
		if (!Adpics::deleteWhere('Adpics', 'ad_id=?', array($this->id)))
		{
			$return = false;
		}

		// delete custom fields
		AdFieldRelation::deleteWhere('AdFieldRelation', 'ad_id=?', array($this->id));

		// delete abuse reports
		AdAbuse::deleteWhere('AdAbuse', 'ad_id=?', array($this->id));

		// delete related ads record
		AdRelated::deleteById($this->id);

		// delete fulltext search records 
		AdFulltext::deleteWhere('AdFulltext', 'id=?', array($this->id));


		return $return;
	}

	/**
	 * Get full url for given ad 
	 * 
	 * @param Ad $ad
	 * @param string $lng 2 letter locale id ex: en,tr,ru
	 * @param string $query append after ad url ex: ?login=1
	 * @return string
	 */
	public static function url($ad, $lng = null, $query = '')
	{
		$slug = StringUtf8::makePermalink(Ad::getTitle($ad));
		$slug = ($slug ? $slug . '-' : '') . $ad->id;

		//pemalink as: title-text-a12354
		// where last part is ad id
		return Language::get_url('item/' . $slug . '.html' . $query, $lng);
	}

	/**
	 * check if ad is visible to user redirect to ad, if not then redirect to message page
	 * 
	 * @param Ad $ad
	 * @param string $append in case new ad posted and verified then this will have success token to track in analytics goals, suggested value ?item_posted=1
	 */
	public static function redirectToAd($ad, $append = '')
	{
		// check if ad visible to this user. then redirect to ad, else redirect to message page
		if ($ad->listed || AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, false))
		{
			redirect(Ad::url($ad) . $append);
		}

		// not visible to this user then redirect to message page
		redirect(Language::get_url('login/message/'));
	}

	public static function permalinkShort($ad)
	{
		//pemalink as: /ad_id
		return self::permalinkShortById($ad->id);
	}

	public static function permalinkShortById($ad_id)
	{
		//pemalink as: /ad_id
		if ($ad_id)
		{
			$ad_id = intval($ad_id);
		}
		else
		{
			$ad_id = '';
		}
		return Language::get_url($ad_id);
	}

	/**
	 * crop ad description and return small text snippet.
	 * 
	 * @param type $ad
	 * @return string 
	 */
	public static function snippet($ad, $preview_chars = 150)
	{
		if ($preview_chars)
		{
			$snippet = TextTransform::excerpt($ad->description, $preview_chars);
		}
		else
		{
			$snippet = $ad->description;
		}

		// remove phone number from snippets
		if (Config::option('hide_phone_title'))
		{
			$snippet = TextTransform::removePhoneNumber($snippet);
		}


		return $snippet;
	}

	/**
	 * Get title,  if no title then try description snippet, else send no title text with id.
	 * Hide phone number if set in options
	 * 
	 * @param type $ad
	 * @return string 
	 */
	public static function getTitle($ad, $default = null, $hide_phone_title = null)
	{
		$try_array = array('title', 'description');
		if (is_null($hide_phone_title))
		{
			$hide_phone_title = Config::option('hide_phone_title');
		}
		foreach ($try_array as $try)
		{
			switch ($try)
			{
				case 'title':
					$title = trim($ad->title);
					break;
				case 'description';
					$title = Ad::snippet($ad, 20);
					break;
			}

			if ($hide_phone_title)
			{
				$title = TextTransform::removePhoneNumber($title);
			}

			if (strlen($title))
			{
				break;
			}
		}

		// no title display default no title text with id
		if (!strlen($title))
		{
			if (is_null($default))
			{
				$default = __('[no title:{num}]');
			}

			$title = str_replace('{num}', $ad->id, $default);
		}

		return $title;
	}

	/**
	 * check if given ad expired
	 * 
	 * @param Ad $ad
	 * @param boolean $only_expired check if it is not listed only because expired
	 * @return boolean 
	 */
	public static function isExpired($ad, $only_expired = false)
	{
		if ($only_expired)
		{
			// verfied, enabled, not requres payment, not abused but expired only
			return (self::isVerified($ad) && $ad->expireson < REQUEST_TIME && $ad->enabled == Ad::STATUS_ENABLED && $ad->requires_posting_payment == 0 && !self::isAbused($ad));
		}

		// check if current ad expired or not.
		return ($ad->expireson < REQUEST_TIME);
	}

	/**
	 * check if ad has reached abuse minimum 
	 * 
	 * @param Ad $ad
	 * @return boolean
	 */
	public static function isAbused($ad)
	{
		$abuse_minimum = AdAbuse::getMinimum();
		return ($ad->abused >= $abuse_minimum);
	}

	public static function isEnabledMessage($ad)
	{
		if ($ad->enabled == Ad::STATUS_PENDING_APPROVAL)
		{
			// if not approved send message to admin 
			MailTemplate::sendPendingApproval();

			// ad needs to be enabled by admin
			return __('Ad will be listed after approval by site administration.');
		}
		else
		{
			return __('Share this ad with your friends by email and social networks.');
		}
	}

	public static function buildLocationQuery($selected_location, & $whereA, & $whereB)
	{
		return self::buildLocationQueryById($selected_location->id, $whereA, $whereB);
	}

	public static function buildLocationQueryById($loc_id, & $whereA, & $whereB)
	{
		if ($loc_id)
		{
			// get sub location ids
			$location_ids = Location::getSublocationIds($loc_id);
			if ($location_ids)
			{
				if (count($location_ids) == 1)
				{
					$whereA[] = 'ad.location_id=?';
					$whereB[] = array_pop($location_ids);
				}
				else
				{
					$location_ids_ = Ad::ids2quote($location_ids);
					$whereA[] = 'ad.location_id IN (' . implode(',', $location_ids_) . ')';
				}
			}
		}
	}

	public static function buildCategoryQuery($selected_category, & $whereA, & $whereB)
	{
		return self::buildCategoryQueryById($selected_category->id, $whereA, $whereB);
	}

	public static function buildCategoryQueryById($cat_id, & $whereA, & $whereB)
	{
		if ($cat_id)
		{
			// get sub location ids
			$category_ids = Category::getSubcategoryIds($cat_id);
			if ($category_ids)
			{
				if (count($category_ids) == 1)
				{
					$whereA[] = 'ad.category_id=?';
					$whereB[] = array_pop($category_ids);
				}
				else
				{
					$category_ids_ = Ad::ids2quote($category_ids);
					$whereA[] = 'ad.category_id IN (' . implode(',', $category_ids_) . ')';
				}
			}
		}
	}

	public static function cq($cq)
	{
		$cq->query = '';
		$cq->is_search = false;

		// all query params here 
		$cq->params = array();


		// shorten var name 
		$sp = $cq->url2vars->search_params;


		// process default values first 
		if ($sp['listed'])
		{
			$cq->params['listed'] = array(
				'where'	 => 'ad.listed=?',
				'values' => $sp['listed']
			);

			//$cq->whereA[] = 'ad.listed=?';
			//$cq->whereB[] = $sp['listed'];
		}

		if ($sp['featured'])
		{
			$cq->params['featured'] = array(
				'where'	 => 'ad.featured=?',
				'values' => $sp['featured']
			);

			//$cq->whereA[] = 'ad.featured=?';
			//$cq->whereB[] = $sp['featured'];
		}


		// add location 
		if ($sp['location_id'])
		{
			$cq->params['location_id'] = array(
				'where'	 => array(),
				'values' => array()
			);

			Ad::buildLocationQueryById($sp['location_id'],
							  $cq->params['location_id']['where'],
							  $cq->params['location_id']['values']);

			//Ad::buildLocationQueryById($sp['location_id'], $cq->whereA, $cq->whereB);
			// generate remove  links 
			$cq->params['location_id']['description'] = array();
			$cq->params['location_id']['url_remove'] = array();
			$_sel = Location::getLocationFromTree($sp['location_id']);
			while ($_sel->parent_id > 0)
			{
				array_unshift($cq->params['location_id']['description'], Location::getName($_sel));
				array_unshift($cq->params['location_id']['url_remove'], Language::thisUrlRemove(array('location_id' => $_sel->parent_id), true));
				$_sel = Location::getLocationFromTree($_sel->parent_id);
			}
			array_unshift($cq->params['location_id']['description'], Location::getName($_sel));
			array_unshift($cq->params['location_id']['url_remove'], Language::thisUrlRemove(array('location_id' => ''), true));
		}

		// add category 
		if ($sp['category_id'])
		{

			$cq->params['category_id'] = array(
				'where'	 => array(),
				'values' => array()
			);

			Ad::buildCategoryQueryById($sp['category_id'],
							  $cq->params['category_id']['where'],
							  $cq->params['category_id']['values']);

			//Ad::buildCategoryQueryById($sp['category_id'], $cq->whereA, $cq->whereB);
			// generate remove  links 
			$cq->params['category_id']['description'] = array();
			$cq->params['category_id']['url_remove'] = array();
			$_sel = Category::getCategoryFromTree($sp['category_id']);
			while ($_sel->parent_id > 0)
			{
				array_unshift($cq->params['category_id']['description'], Category::getName($_sel));
				array_unshift($cq->params['category_id']['url_remove'], Language::thisUrlRemove(array('category_id' => $_sel->parent_id), true));
				$_sel = Category::getCategoryFromTree($_sel->parent_id);
			}
			array_unshift($cq->params['category_id']['description'], Category::getName($_sel));
			array_unshift($cq->params['category_id']['url_remove'], Language::thisUrlRemove(array('category_id' => ''), true));
		}



		// filter by user if set 		
		if ($sp['user_id'])
		{
			$_user = User::findByIdFrom('User', intval($sp['user_id']));
			if ($_user)
			{
				$cq->params['user_id'] = array(
					'where'			 => 'ad.added_by=?',
					'values'		 => $_user->id,
					'description'	 => View::escape($_user->name),
					'url_remove'	 => Language::thisUrlRemove(array('user_id' => ''), true)
				);
			}
		}

		// check if it is search 
		if ($sp['s'])
		{
			$cq->is_search = true;
			// if search field has value then search ad title
			$q = TextTransform::normalizeQueryString($sp['q']);
			$cq->has_q = strlen($q) > 0;

			// use it to generate then add to $cq->params['q']=$cq_params_arr;
			$cq_params_arr = array();

			if (strlen($q))
			{
				if ($sp['use_like'])
				{
					// use line query in main table
					// old way
					$arr_q = Ad::searchQuery2Array($q);
					$cq_params_arr['values'] = array();
					foreach ($arr_q as $_q)
					{
						$arr_variation = array();
						$arr_variation_sql = array();

						$arr_variation[] = $_q;

						foreach ($arr_variation as $v)
						{
							$arr_variation_2 = array();
							$arr_variation_2[0] = StringUtf8::strtolower($v);
							// convert to latin characters
							$arr_variation_2[1] = StringUtf8::convert($v);
							// remove duplicate characters 
							$arr_variation_2[2] = StringUtf8::removeRepeatedChars($arr_variation_2[0]);
							$arr_variation_2[3] = StringUtf8::removeRepeatedChars($arr_variation_2[1]);

							$arr_variation_2 = array_unique($arr_variation_2);

							foreach ($arr_variation_2 as $v2)
							{
								//$whereA[] = '(ad.title LIKE ? OR ad.description LIKE ?)';
								$arr_variation_sql[] = 'ad.title LIKE ? OR ad.description LIKE ?';
								$cq_params_arr['values'][] = '%' . $v2 . '%';
								$cq_params_arr['values'][] = '%' . $v2 . '%';
								//$cq->whereB[] = '%' . $v2 . '%';
								//$cq->whereB[] = '%' . $v2 . '%';
							}
						}
						$cq_params_arr['where'] = array();
						if ($arr_variation_sql)
						{
							$cq_params_arr['where'][] = '(' . implode(' OR ', $arr_variation_sql) . ')';
							//$cq->whereA[] = '(' . implode(' OR ', $arr_variation_sql) . ')';
						}
					}
				}
				else
				{
					/* BOOLEAN MODE */
					$q_n = TextTransform::text_normalize($q, 'search');


					if (strlen($q_n))
					{
						// convert to simple match query without parentheses, because it is faster
						$q_n_value = TextTransform::text_normalize_simplify_search($q_n);

						// generate base query params to use for getting broader search result based on query only
						$query_base = new stdClass();
						$query_base->from = AdFulltext::tableNameFromClassName('AdFulltext') . ' aft';

						$query_base->select_field = array();
						$query_base->select_field[] = "MATCH (aft.title) AGAINST (" . Record::escape($q_n) . "  IN BOOLEAN MODE)*10 AS score1";
						$query_base->select_field[] = "MATCH (aft.description) AGAINST (" . Record::escape($q_n) . " IN BOOLEAN MODE) AS score2";

						$query_base->where = "ad.id=aft.id AND MATCH (aft.description) AGAINST (? IN BOOLEAN MODE)";
						$query_base->values = array($q_n_value);
						$query_base->order_by_str = '(score1)+(score2) DESC, ad.published_at DESC';


						// set same values for current query 

						$cq_params_arr['from'] = $query_base->from;
						$cq_params_arr['select_field'] = array();


						//$cq->from[] = $query_base->from;
						foreach ($query_base->select_field as $qb_val)
						{
							$cq_params_arr['select_field'][] = $qb_val;
							//$cq->select_field[] = $qb_val;
						}
						$cq_params_arr['where'] = $query_base->where;
						//$cq->whereA[] = $query_base->where;
						$cq_params_arr['values'] = $q_n_value;
						//$cq->whereB[] = $q_n;
						$cq_params_arr['order_by'] = $query_base->order_by_str;
						//$cq->order_by[] = $query_base->order_by_str;

						$cq_using_ids = array();
						//////////////////////////////////////////////////////////////////////
						// IMPORTANT 
						// Generate alternate params for using ids generated by query base
						// it is used to get ids for query_base then filter result to match 
						// this main query
						// examle: $q search returns 500 items, then user clicks to fillter down by category, location, customfield, change order. We use cached ids (if they are less than 1k) and perform filtering. this is much faster than using match for filtering small results.
						//////////////////////////////////////////////////////////////////////
						$cq_using_ids['where'] = 'ad.id IN ({ids_1k})';
						$cq_using_ids['order_by'] = "FIELD (ad.id, {ids_1k})";
						$cq_params_arr['cq_using_ids'] = $cq_using_ids;


						// generate and store query_base for this $q
						$cq->query_base = new stdClass();
						$cq->query_base->query = "SELECT ad.id, " . implode(', ', $query_base->select_field)
								. " FROM " . Ad::tableNameFromClassName('Ad') . " ad, " . $query_base->from
								. " WHERE ad.listed=1 AND " . $query_base->where
								. " ORDER BY " . $query_base->order_by_str;

						$cq->query_base->query_count = "SELECT count(ad.id) as num "
								. " FROM " . Ad::tableNameFromClassName('Ad') . " ad, " . $query_base->from
								. " WHERE  ad.listed=1 AND " . $query_base->where;

						$cq->query_base->values = $query_base->values;
					}
				}

				if (count($cq_params_arr))
				{
					// if has set some search params then use it 

					$cq_params_arr['description'] = View::escape($q);
					$cq_params_arr['url_remove'] = Language::thisUrlRemove(array('q' => ''), true);
					$cq_params_arr['title']['start'] = $q;

					$cq->params['q'] = $cq_params_arr;
				}
			}




			// sanetize custom fields. pass only if has valid value
			$ids = array();
			if (isset($sp['cf']))
			{
				foreach ($sp['cf'] as $af_id => $val)
				{
					if (is_array($val))
					{
						foreach ($val as $k => $v)
						{
							$v = trim($v);
							if (strlen($v))
							{
								$ids[$af_id] = $val;
								break;
							}
						}
					}
					else
					{
						if (strlen($val))
						{
							$ids[$af_id] = $val;
						}
					}
				}
			}

			// iterateor for ad_field_relation table
			$i = 0;

			// get adfields to have reference about field type
			//$adfield = AdField::findAllFrom('AdField', "id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")", array_keys($ids));
			// get all adfields using ids 
			foreach ($ids as $af_id => $afr_val)
			{
				$af = AdField::getAdFieldFromTree($af_id);
				if ($af)
				{
					$cq_params_arr = array();

					switch ($af->type)
					{
						case AdField::TYPE_NUMBER:
						case AdField::TYPE_PRICE:
							// use like statement
							if (isset($ids[$af->id]['from']) || isset($ids[$af->id]['to']))
							{
								// 2 values set then use range 
								$num_from = intval($ids[$af->id]['from']);
								$num_to = intval($ids[$af->id]['to']);
							}
							else
							{
								// one value set then use equal 
								$num_from = intval($ids[$af->id]);
								$num_to = $num_from;
							}

							if ($af->type == AdField::TYPE_PRICE)
							{
								$num_from = AdField::stringToFloat($ids[$af->id]['from']);
								$num_to = AdField::stringToFloat($ids[$af->id]['to']);
							}


							$search_desc_arr_remove_url = Language::thisUrlRemove(array(
										'cf' => array(
											$af->id => array(
												'from'	 => '',
												'to'	 => '',
											)
										)
											), true);

							if ($num_from > 0 && $num_to > 0)
							{
								// check if from smaller than to 
								if ($num_from > $num_to)
								{
									// swap them
									list($num_to, $num_from) = array($num_from, $num_to);
									$sp['cf'][$af->id]['from'] = $num_from;
									$sp['cf'][$af->id]['to'] = $num_to;
								}

								$cq_params_arr['from'] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								//$cq->from[] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;

								if ($num_from == $num_to)
								{
									// from equeal to to then use one value with equeal 
									// can be used to display 
									$cq_params_arr['where'] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND ABS(afr{$i}.val) = ?";
									//$cq->whereA[] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND ABS(afr{$i}.val) = ?";
									$cq_params_arr['values'] = array($af->id, $num_from);
									//$cq->whereB[] = $af->id;
									//$cq->whereB[] = $num_from;
									$str_val = Ad::formatCustomValue($af, $num_from, array('escape' => false));
								}
								else
								{
									$cq_params_arr['where'] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND ABS(afr{$i}.val) >= ? AND ABS(afr{$i}.val) <= ?";
									//$cq->whereA[] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND ABS(afr{$i}.val) >= ? AND ABS(afr{$i}.val) <= ?";
									$cq_params_arr['values'] = array($af->id, $num_from, $num_to);
									//$cq->whereB[] = $af->id;
									//$cq->whereB[] = $num_from;
									//$cq->whereB[] = $num_to;
									$str_val = Ad::formatCustomValue($af, $num_from, array('escape' => false)) . '-' . Ad::formatCustomValue($af, $num_to, array('escape' => false));
								}
							}
							elseif ($num_from > 0)
							{
								$cq_params_arr['from'] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								//$cq->from[] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								$cq_params_arr['where'] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND ABS(afr{$i}.val) >= ? ";
								//$cq->whereA[] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND ABS(afr{$i}.val) >= ? ";
								$cq_params_arr['values'] = array($af->id, $num_from);
								//$cq->whereB[] = $af->id;
								//$cq->whereB[] = $num_from;
								$str_val = Ad::formatCustomValue($af, $num_from, array('escape' => false)) . '-...';
							}
							elseif ($num_to > 0)
							{
								$cq_params_arr['from'] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								$cq_params_arr['where'] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND ABS(afr{$i}.val) <= ? ";
								$cq_params_arr['values'] = array($af->id, $num_to);
								$str_val = '0-' . Ad::formatCustomValue($af, $num_to, array('escape' => false));
							}
							$cq_params_arr['description'] = View::escape(AdField::getName($af) . ':' . $str_val);
							$cq_params_arr['url_remove'] = $search_desc_arr_remove_url;
							$cq_params_arr['title']['cf'][$af->id] = Ad::makeCustomValueMeaningfull($str_val, AdField::getName($af));
							break;
						case AdField::TYPE_RADIO:
						case AdField::TYPE_DROPDOWN:
							$val = trim($ids[$af->id]);
							// radio and dropdown should have at least one value that exists in definition
							if (strlen($val) && isset($af->AdFieldValue[$val]))
							{
								$cq_params_arr['from'] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								//$cq->from[] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								$cq_params_arr['where'] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND afr{$i}.val=?";
								//$cq->whereA[] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND afr{$i}.val=?";
								$cq_params_arr['values'] = array($af->id, $val);
								//$cq->whereB[] = $af->id;
								//$cq->whereB[] = $val;
								$str_val = Ad::formatCustomValue($af, $val, array('escape' => false));
								// removed name to make control text shorter
								// /*AdField::getName($af) . ':' .*/
								$str_val_meaningul = Ad::makeCustomValueMeaningfull($str_val, AdField::getName($af));
								$cq_params_arr['description'] = View::escape($str_val_meaningul);
								$cq_params_arr['url_remove'] = Language::thisUrlRemove(array('cf' => array($af->id => '')), true);
								$cq_params_arr['title']['cf'][$af->id] = $str_val_meaningul;
							}
							break;
						case AdField::TYPE_CHECKBOX:
							// radio and dropdown should have at least one value that exists in definition
							$vals = $ids[$af->id];
							$selected_vals = array();
							foreach ($vals as $v)
							{
								$v = trim($v);

								$cq_params_arr['description'] = array();
								$cq_params_arr['url_remove'] = array();
								// checkbox can have none, one or many values 
								if (isset($af->AdFieldValue[$v]))
								{
									$selected_vals[$v] = $v;
									$str_val = Ad::formatCustomValue($af, $v, array('escape' => false, 'display_all_checkboxes' => false, 'checkbox_pattern' => ''));


									// remove field name to make control text shorter
									/* AdField::getName($af) . ':' . */
									$cq_params_arr['description'][] = View::escape($str_val);
									$cq_params_arr['url_remove'][] = Language::thisUrlRemove(array('cf' => array($af->id => array($v => ''))), true);
									// title NOT USED will only last value of chechboxes if multiple selected 
								}
							}

							if ($selected_vals)
							{
								$cq_params_arr['from'] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								//$cq->from[] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								$cq_params_arr['where'] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? " . implode('', array_fill(0, count($selected_vals), " AND afr{$i}.val LIKE ? "));
								//$cq->whereA[] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? " . implode('', array_fill(0, count($selected_vals), " AND afr{$i}.val LIKE ? "));
								$cq_params_arr['values'] = array($af->id);
								//$cq->whereB[] = $af->id;
								foreach ($selected_vals as $v)
								{
									$cq_params_arr['values'][] = '%;' . $v . ';%';
									//$cq->whereB[] = '%;' . $v . ';%';
								}
							}

							// make title using all values 
							$str_val = Ad::formatCustomValue($af, $v, array('escape' => false, 'display_all_checkboxes' => false, 'checkbox_pattern' => '', 'checkbox_seperator' => ', '));

							$cq_params_arr['title']['cf'][$af->id] = Ad::makeCustomValueMeaningfull($str_val, AdField::getName($af));
							//$cq->search_title_arr['cf'][$af->id] = Ad::makeCustomValueMeaningfull($str_val, AdField::getName($af));
							break;
						case AdField::TYPE_VIDEO_URL:
							// no need to search by youtube video							
							break;
						case AdField::TYPE_TEXT:
						case AdField::TYPE_ADDRESS:
						case AdField::TYPE_URL:
						case AdField::TYPE_EMAIL:
						default:
							// regular text, use like statement
							$val = trim($ids[$af->id]);
							if (strlen($val))
							{
								$cq_params_arr['from'] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								//$cq->from[] = AdFieldRelation::tableNameFromClassName('AdFieldRelation') . ' afr' . $i;
								$cq_params_arr['where'] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND afr{$i}.val LIKE ?";
								//$cq->whereA[] = "ad.id=afr{$i}.ad_id AND afr{$i}.field_id=? AND afr{$i}.val LIKE ?";
								$cq_params_arr['values'] = array($af->id, '%' . $val . '%');
								//$cq->whereB[] = $af->id;
								//$cq->whereB[] = '%' . $val . '%';
								$str_val = $val;
								// removed field text 
								/* AdField::getName($af) . ':' . */
								$cq_params_arr['description'] = View::escape($str_val);
								$cq_params_arr['url_remove'] = Language::thisUrlRemove(array('cf' => array($af->id => '')), true);
								$cq_params_arr['title']['cf'][$af->id] = Ad::makeCustomValueMeaningfull($str_val, AdField::getName($af));
							}
							break;
					}// swith type
					$i++;
					$cq->params['cf_' . $af->id] = $cq_params_arr;
				}// if $af
			}// foreach $ids


			/* check if freshness set */
			$freshness = trim($sp['freshness']);
			if ($freshness)
			{
				// get freshness values 
				$arr_freshness = Widget::freshnessValues();

				// check if current freshness value exists	
				if (isset($arr_freshness[$freshness]))
				{
					// it is day 
					$time = intval($freshness) * 24 * 3600;
					// floor to 1 hour
					$time_floor = floor(REQUEST_TIME / 3600) * 3600 - $time;

					$cq->params['freshness'] = array(
						'where'			 => 'ad.published_at>?',
						'values'		 => $time_floor,
						'description'	 => __('Freshness') . ':' . View::escape($arr_freshness[$freshness]),
						'url_remove'	 => Language::thisUrlRemove(array('freshness' => ''), true),
						'title'			 => array('end' => View::escape($arr_freshness[$freshness])),
					);
				}
			}


			// check if with photo checked 
			$with_photo = trim($sp['with_photo']);
			if ($with_photo)
			{

				$cq->params['with_photo'] = array(
					'where'			 => 'EXISTS (SELECT 1 FROM ' . Adpics::tableNameFromClassName('Adpics') . ' adp WHERE adp.ad_id=ad.id)',
					'description'	 => __('with photo'),
					'url_remove'	 => Language::thisUrlRemove(array('with_photo' => ''), true),
					'title'			 => array('end' => __('with photo')),
				);

				/* Optimization a bit slow than original */
				// 'where' => 'EXISTS (SELECT 1 FROM ' . Adpics::tableNameFromClassName('Adpics') . ' adp WHERE adp.ad_id=ad.id)',

				/* NO GROUP BY , EXISTS IS FASTER 
				  'where' => 'adp.ad_id=ad.id',
				  'from' => Adpics::tableNameFromClassName('Adpics') . ' adp',
				  'group_by' => " GROUP BY ad.id",
				 */
			}
		}

		/* query value populated now build query */
		Ad::cqQueryBuild($cq);

		return $cq->query;
	}

	static public function cqQueryVars($cq, $cq_using_ids = false)
	{
		// convert params to variables 
		$vars = new stdClass();
		$vars->from = array();
		$vars->where = array();
		$vars->select_field = array();
		$vars->order_by = array();
		$vars->group_by = array();
		$vars->values = array();

		$var_names = array(
			'from',
			'where',
			'select_field',
			'order_by',
			'group_by',
			'values'
		);

		$has_cq_using_ids = false;

		foreach ($cq->params as $key => $val)
		{
			foreach ($var_names as $var_key)
			{
				if (isset($val['cq_using_ids']))
				{
					// we have cq_using_ids defined use it now or mark to append to vars after
					if ($cq_using_ids)
					{
						// use alternate query vars to use ids instead of "match against"
						$val = $val['cq_using_ids'];
					}
					else
					{
						// mark to append to vars
						$has_cq_using_ids = true;
					}
				}

				if (isset($val[$var_key]))
				{
					if (is_array($val[$var_key]))
					{
						foreach ($val[$var_key] as $vv)
						{
							$vars->{$var_key}[] = $vv;
						}
					}
					else
					{
						$vars->{$var_key}[] = $val[$var_key];
					}
				}
			}
		}

		if ($has_cq_using_ids)
		{
			// this query has alternate vars, using ids  generate it as well 
			$vars->cq_using_ids = self::cqQueryVars($cq, true);
		}

		return $vars;
	}

	/**
	 * generate defined query using values in $cq
	 * regular query with order by published_at
	 * favorite 
	 * 
	 * 
	 * @param type $cq
	 * @return string
	 */
	static public function cqQueryBuild($cq)
	{
		/* query value populated now build query */

		$vars = self::cqQueryVars($cq);

		//var_dump($vars);

		if ($vars->from)
		{
			// TODO : test comples query with custom fields how it will work??
			/* 			 
			  explain SELECT ad.*
			  FROM cb_ad ad, cb_ad_field_relation afr0 , cb_ad_field_relation afr1
			  WHERE ad.listed=1 AND ad.category_id IN (31) AND ad.title LIKE '%ti%'
			  AND ad.id=afr0.ad_id AND afr0.field_id='1' AND afr0.val >= '100'
			  AND ad.id=afr1.ad_id AND afr1.field_id='4' AND afr1.val >= '2000' AND afr1.val <= '2010'
			  GROUP BY ad.id ORDER BY ad.published_at DESC LIMIT 0,30;
			 * 
			  explain SELECT ad.*
			  FROM cb_ad ad, cb_ad_field_relation afr0 , cb_ad_field_relation afr1
			  WHERE ad.listed='1' AND ad.category_id IN ('31') AND ad.title LIKE '%ti%'
			  AND ad.id=afr0.ad_id AND afr0.field_id='4' AND afr0.val >= '2000' AND afr0.val <= '2010'
			  AND ad.id=afr1.ad_id AND afr1.field_id='14' AND afr1.val LIKE '%;check val 1;%' AND afr1.val LIKE '%;check val 4;%'
			  GROUP BY ad.id ORDER BY ad.published_at DESC LIMIT 0,30;
			 */

			$select_field_str = $vars->select_field ? ' , ' . implode(' , ', $vars->select_field) : '';

			// for regular query add order by published_at for 
			$order_by_str = $vars->order_by ? ' ORDER BY ' . implode(' , ', $vars->order_by) : ' ORDER BY ad.published_at DESC ';

			$group_by_str = $vars->group_by ? ' GROUP BY ' . implode(',', $vars->group_by) . ' ' : '';

			// no scoring, no ordering, used for counting custom vields later 
			$query = "SELECT ad.* 
						FROM " . Ad::tableNameFromClassName('Ad') . " ad,
							" . implode(' , ', $vars->from) . " 
						WHERE {where} "
					. $group_by_str;

			// includes match score if set and ordering
			$query_ordered = "SELECT ad.* " . $select_field_str . "
						FROM " . Ad::tableNameFromClassName('Ad') . " ad,
							" . implode(' , ', $vars->from) . " 
						WHERE {where} "
					. $group_by_str
					. $order_by_str;

			if ($vars->cq_using_ids)
			{
				// define $query_ordered_using_ids
				$qui_from_str = ($vars->cq_using_ids->from ? ', ' . implode(' , ', $vars->cq_using_ids->from) : '');
				$qui_group_by_str = ($vars->cq_using_ids->group_by ? ' GROUP BY ' . implode(' , ', $vars->cq_using_ids->group_by) : '');
				$qui_order_by_str = ($vars->cq_using_ids->order_by ? ' ORDER BY ' . implode(' , ', $vars->cq_using_ids->order_by) : '');
				$qui_where_str = ($vars->cq_using_ids->where ? ' WHERE ' . implode(' AND ', $vars->cq_using_ids->where) : '');

				$query_ordered_using_ids = "SELECT ad.* "
						. " FROM " . Ad::tableNameFromClassName('Ad') . " ad "
						. $qui_from_str
						. $qui_where_str
						. $qui_group_by_str
						. $qui_order_by_str;

				$cq->query_ordered_using_ids = $query_ordered_using_ids;
				$cq->query_ordered_using_ids_values = $vars->cq_using_ids->values;
			}
			else
			{
				$query_ordered_using_ids = '';
			}



			// featured use orderby rand() , add featured=1 to where, 
			$query_featured = "SELECT ad.* 
						FROM " . Ad::tableNameFromClassName('Ad') . " ad,
							" . implode(' , ', $vars->from) . " 
						WHERE {where} AND ad.featured=1 "
					. $group_by_str
					. " ORDER BY rand()";


			// count 
			if ($vars->group_by)
			{
				$query_count_by = "distinct ad.id";
			}
			else
			{
				$query_count_by = "ad.id";
			}
			$query_count = "SELECT count(" . $query_count_by . ") as num 
						FROM " . Ad::tableNameFromClassName('Ad') . " ad,
							" . implode(' , ', $vars->from) . " 
						WHERE {where} ";
		}


		// if no custom query then build simple query with ads table only
		if (!$query)
		{
			$query = "SELECT ad.* 
						FROM " . Ad::tableNameFromClassName('Ad') . " ad
						WHERE {where}";

			$query_ordered = $query . ' ORDER BY ad.published_at DESC';
			$query_featured = $query . ' AND ad.featured=1 ORDER BY rand()';
			$query_count = str_replace('SELECT ad.*', 'SELECT count(ad.id) as num', $query);
		}

		// where str
		$where_str = implode(' AND ', $vars->where);

		$query = str_replace('{where}', $where_str, $query);
		$query_ordered = str_replace('{where}', $where_str, $query_ordered);
		$query_featured = str_replace('{where}', $where_str, $query_featured);
		$query_count = str_replace('{where}', $where_str, $query_count);

		$cq->query = $query;
		$cq->query_ordered = $query_ordered;
		$cq->query_featured = $query_featured;
		$cq->query_count = $query_count;
		$cq->values = $vars->values;
	}

	/**
	 * check if query should be cached, also check if query is complex. 
	 * do not cache if 
	 * 		no afv table used
	 * 		no q used
	 * 		no adpic table used
	 * 
	 * @param type $cq
	 * @return boolean
	 */
	static public function cqQueryIsUseCache($cq)
	{
		$use_cache = false;
		// check for table names in query
		$arr_check_query = array(
			Adpics::tableNameFromClassName('Adpics'),
			AdFieldRelation::tableNameFromClassName('AdFieldRelation')
		);

		foreach ($arr_check_query as $check)
		{
			if (strpos($cq->query_ordered, $check) !== false)
			{
				// we have extra table joins then use cache
				$use_cache = true;
				break;
			}
		}

		// check if search query used 
		if (!$use_cache && $cq->has_q)
		{
			$use_cache = true;
		}

		Benchmark::cp('cqQueryIsUseCache:' . intval($use_cache));
		return $use_cache;
	}

	/**
	 * Check if cq is same as cq->query_base in terms of results 
	 * 
	 * @param type $cq
	 * @return boolean
	 */
	static public function cqQueryIsSameAsBase($cq)
	{
		$return = false;
		self::cqQueryInitBase($cq);
		if (isset($cq->query_base->result->ids_1k))
		{
			if (isset($cq->params['q']) && isset($cq->params['listed']) && count($cq->params) === 2)
			{
				// we have only q and listed which is same for base query so result ids_1k is same 
				$return = true;
			}
		}

		return $return;
	}

	/**
	 * populate ad ids array, limit 2 items per user in each round to show ads from different users close to top
	 * 
	 * @param array $ad_ids
	 * @param boolean $unique_user
	 * @return array
	 */
	static private function cqIdsOnly($ad_ids, $unique_user = true)
	{
		$ids_1k = array();
		$ad_ids_count = count($ad_ids);

		// get from settings if enabled 
		$ads_separate = Config::option('ads_separate');

		$unique_user = $unique_user && $ads_separate;

		if ($unique_user)
		{
			// count added items per user
			$arr_user = array();
			// ads for next round.
			$ads_next = array();
			// users left for next round
			$ads_next_user = array();
			// pre-process next round in case only one user left for next round
			$ids_1k_next = array();
			// how many items pass per user for each round, max 2 items from same user
			$max_pass = 2;
			foreach ($ad_ids as $ad)
			{
				$user_id = $ad->added_by * 1;
				if (isset($ad->added_by) && $user_id > 0)
				{
					// check if ad by this user is already added 
					if (!isset($arr_user[$user_id]))
					{
						$arr_user[$user_id] = 0;
					}

					if ($arr_user[$user_id] < $max_pass)
					{
						$ids_1k[] = $ad->id;
						$arr_user[$user_id] += 1;
					}
					else
					{
						// prepare for next round
						$ads_next[] = $ad;
						$ads_next_user[$user_id] = true;
						$ids_1k_next[] = $ad->id;
					}
				}
				else
				{
					// no id set, pass in given order
					$ids_1k[] = $ad->id;
				}
			}

			// unset to free memory before recursive call 
			unset($ad_ids);

			// check need for next round 
			if (count($ads_next_user) > 3)
			{
				// we need next round 
				$ids_1k_next = Ad::cqIdsOnly($ads_next, $unique_user);
			}

			// unset other arrays
			unset($ads_next);
			unset($ads_next_user);

			// check if we need merge
			if ($ids_1k_next)
			{
				$ids_1k = array_merge($ids_1k, $ids_1k_next);
			}
		}
		else
		{
			foreach ($ad_ids as $ad)
			{
				// just convert to array of ids
				$ids_1k[] = $ad->id;
			}
		}

		Benchmark::cp('Ad::cqIdsOnly($ad_ids(' . $ad_ids_count . '),'
				. ($unique_user ? 'true' : 'false')
				. (isset($arr_user) ? '(' . count($arr_user) . ')' : '')
				. ')');

		return $ids_1k;
	}

	/**
	 * perform main, featured and count queries as needed
	 * use caching when possible 
	 * 
	 * @param type $cq
	 */
	static public function cqQueryInit($cq, $force_use_cache = null)
	{
		if (!isset($cq->result))
		{
			// get 1k ids for main query 
			$cq->result = new stdClass();
			$cq->result->max_limit = 1000;
			// for query with joins 30 min (1800)
			$cache_life_long = 1800;
			// for single table query 3 minut (180)
			$cache_life_short = 180;

			if (is_null($force_use_cache))
			{
				// decide short or long query to use
				$cq->use_cache = self::cqQueryIsUseCache($cq) ? $cache_life_long : $cache_life_short;
			}
			else
			{
				$cq->use_cache = $force_use_cache;
			}

			if (intval($cq->use_cache) === 1)
			{
				// set to long cache period
				$cq->use_cache = $cache_life_long;
			}

			if ($cq->use_cache)
			{

				// check for base query set then use it if possible 
				self::cqQueryInitBase($cq);
				if (self::cqQueryIsSameAsBase($cq))
				{
					// we have same query as base, 
					// regardless if base loaded all ids or not we can use base as is 
					$cq->result->ids_1k = $cq->query_base->result->ids_1k;
					$cq->result->count = $cq->query_base->result->count;
					$cq->result->total = Ad::cqQueryBaseGetTotal($cq);
				}
				elseif ($cq->query_base_is_usable)
				{
					// we have usable base (with results or with 0 results)
					if ($cq->query_base->result->count)
					{
						// all ids loaded in base, base is not same as cq
						// use ids to filter down
						$_sql = str_replace('ad.*', 'ad.id', $cq->query_ordered_using_ids) . ' LIMIT ' . $cq->result->max_limit;
						$_sql = str_replace('{ids_1k}', $cq->query_base->result->ids_1k_str, $_sql);

						$ad_ids = Ad::query($_sql, $cq->query_ordered_using_ids_values);

						// convert to array, do not seperate by unique because base query ids already prepared.
						$cq->result->ids_1k = Ad::cqIdsOnly($ad_ids, false);
						unset($ad_ids);

						// set count of ids_1k 
						$cq->result->count = count($cq->result->ids_1k);
						// we have total count as well set it 
						$cq->result->total = $cq->result->count;
					}
					else
					{
						// base didnt found any results use zero result as response
						$cq->result->ids_1k = $cq->query_base->result->ids_1k;
						$cq->result->count = $cq->query_base->result->count;
						$cq->result->total = Ad::cqQueryBaseGetTotal($cq);
					}
				}
				else
				{
					// use regular cache 
					// build ordered query with max limit to get ids, and added_by to reorder by users					
					$_sql = str_replace('ad.*', 'ad.id, ad.added_by', $cq->query_ordered) . ' LIMIT ' . $cq->result->max_limit;

					// check if we seperate by user
					$ads_separate = Config::option('ads_separate');

					// if has query then do not seperate users to keep order of relevant results
					if ($cq->has_q)
					{
						$ads_separate = false;
					}
					$ads_separate_str = $ads_separate ? 's_' : '';

					$cache_key = 'ad_cqQueryInit.' . $ads_separate_str . SimpleCache::uniqueKey($_sql, $cq->values);
					$cache_data = SimpleCache::get($cache_key);
					if ($cache_data === false)
					{
						// get ids						
						$ad_ids = Ad::query($_sql, $cq->values);

						// convert to array and reorder by user
						$cq->result->ids_1k = Ad::cqIdsOnly($ad_ids, $ads_separate);
						unset($ad_ids);

						$ids_str = implode(',', $cq->result->ids_1k);

						// set count of ids_1k 
						$cq->result->count = count($cq->result->ids_1k);

						// we have 1k ids 
						// set total
						if ($cq->result->count < $cq->result->max_limit)
						{
							// we have total count as well set it 
							$cq->result->total = $cq->result->count;
						}
						else
						{
							// total is not calculated, query to get total
							$cq->result->total = Ad::countByCustom($cq->query_count, $cq->values, false);
						}

						$cache_data = array();
						$cache_data['total'] = $cq->result->total;

						// set 2nd parameter as query string, because that is visible in cache db for better analyzing 
						if ($cq->url2vars->search_params['q'])
						{
							$cache_data['q'] = $cq->url2vars->search_params['q'];
						}

						$cache_data['ids'] = $ids_str;

						SimpleCache::set($cache_key, $cache_data, $cq->use_cache); // 30 minute cache counts 
					}
					else
					{
						if (strlen($cache_data['ids']))
						{
							$cq->result->ids_1k = explode(',', $cache_data['ids']);
						}
						else
						{
							$cq->result->ids_1k = array();
						}
						$cq->result->total = $cache_data['total'];
						$cq->result->count = count($cq->result->ids_1k);
					}
				}


				// store quoted ids_1k as string
				$cq->result->ids_1k_str = implode(', ', Ad::quoteArray($cq->result->ids_1k));

				if (!$cq->result->ids_1k)
				{
					// no results so we can set this to empty array
					$cq->result->ads_all = array();
				}

				// we have all ad ids loaded
				$cq->result->is_all_ids = $cq->result->total <= $cq->result->count;
			}
			else
			{
				// dont use cache because it is simple query 
				// get total count, total is cached seperately inside Ad::countByCustom
				$cq->result->total = Ad::countByCustom($cq->query_count, $cq->values);
				// set count to toal becase it is used in pagination
				$cq->result->count = $cq->result->total;
			}
		}
	}

	/**
	 * check if base query set then get ids and total of base query 
	 * Base query is query using search variable $q with match in boolean mode
	 * 
	 * @param type $cq
	 */
	static public function cqQueryInitBase($cq)
	{
		// check for base query set then use it if possible 
		if ($cq->query_base && !isset($cq->query_base->result))
		{
			// use simple var 
			$base = $cq->query_base;
			$base->result = new stdClass();
			$result = $base->result;

			$result->max_limit = $cq->result->max_limit;
			$qb_sql = $base->query . ' LIMIT ' . $result->max_limit;

			$cache_key = 'ad_cqQueryBase.' . SimpleCache::uniqueKey($qb_sql, $base->values);
			$cache_query_base = SimpleCache::get($cache_key);
			if ($cache_query_base === false)
			{
				// run query base 
				// get ids 				
				$ad_ids = Ad::query($qb_sql, $base->values);
				// set ids, do not order by user because base query is using fulltext search and ordered by relevance.
				$result->ids_1k = Ad::cqIdsOnly($ad_ids, false);
				unset($ad_ids);


				$ids_str = implode(',', $result->ids_1k);

				// set count of ids_1k 
				$result->count = count($result->ids_1k);

				// we have 1k ids 
				// set total
				if ($result->count < $result->max_limit)
				{
					// we have total count as well set it 
					$result->total = $result->count;
				}
				else
				{
					// total is not calculated, query to get total, donot cache becase we use single cache for ids and total
					// DO NOT COUNT HERE it is slow, count only when needed for base query. 
					// $result->total = Ad::countByCustom($base->query_count, $base->values, false);
					$result->total = 'calculate';
				}

				$cache_query_base = array();
				$cache_query_base['total'] = $result->total;

				// set 2nd parameter as query string, because that is visible in cache db for better analyzing 
				if ($cq->url2vars->search_params['q'])
				{
					$cache_query_base['q'] = $cq->url2vars->search_params['q'];
				}
				$cache_query_base['ids'] = $ids_str;

				SimpleCache::set($cache_key, $cache_query_base, 1800); // 30 minute cache counts 
			}
			else
			{
				if (strlen($cache_query_base['ids']))
				{
					$result->ids_1k = explode(',', $cache_query_base['ids']);
				}
				else
				{
					$result->ids_1k = array();
				}
				$result->total = $cache_query_base['total'];
				$result->count = count($result->ids_1k);
			}

			// we have all ids in base query 
			$result->is_all_ids = ($result->total !== 'calculate') && ($result->total <= $result->count);

			if ($result->ids_1k)
			{
				$result->ids_1k_str = implode(', ', Ad::quoteArray($result->ids_1k));
			}
		}

		$cq->query_base_is_usable = ($cq->query_base && $cq->query_base->result->is_all_ids);

		return $cq->query_base_is_usable;
	}

	/**
	 * check if base has some results. used to redirect to base results if has no results in narrowed search.
	 * 
	 * @param CustomQuery $cq
	 * @return boolean
	 */
	static public function cqQueryBaseHasItems($cq)
	{
		self::cqQueryInitBase($cq);

		return $cq->query_base->result->count > 0;
	}

	/**
	 * Get total for base query, calculate with query it is needed now.
	 * 
	 * @param CustomQuery $cq
	 * @return integer
	 */
	static public function cqQueryBaseGetTotal($cq)
	{
		self::cqQueryInitBase($cq);

		if ($cq->query_base->result->total === 'calculate')
		{
			if ($cq->query_base->result->count > 0)
			{
				// $result->total = Ad::countByCustom($base->query_count, $base->values, false);
				$cq->query_base->result->total = Ad::countByCustom($cq->query_base->query_count, $cq->query_base->values);
				Benchmark::cp('Ad::cqQueryBaseGetTotal');
			}
			else
			{
				$cq->query_base->result->total = $cq->query_base->result->count;
			}
		}

		return $cq->query_base->result->total;
	}

	static public function cqQueryTotal($cq)
	{
		if ($cq->is_search_stopped)
		{
			// search stopped because search string length is small or bigger than allowed
			$return = 0;
		}
		else
		{
			// perform init 
			self::cqQueryInit($cq);
			$return = $cq->result->total;
		}

		return $return;
	}

	static public function cqQueryPage($cq, $page = 1, $num = 50)
	{
		// perform init 
		self::cqQueryInit($cq);
		$empty_result = array();

		$page = intval($page);
		// generate $st and $num accordingly
		if ($page < 1)
		{
			$page = 1;
		}

		$num = intval($num);
		if ($num < 1)
		{
			// num not set correctly, set it to default 
			$num = 50;
		}

		$st = ($page - 1) * $num;

		// out of result range 
		if ($st > $cq->result->count || $cq->result->count < 1)
		{
			// no results
			return $empty_result;
		}


		// check if we have loaded all results return parsing it 
		if (isset($cq->result->ads_all))
		{
			// split result to requested size 
			if (($page === 1 || $page === 2) && $cq->result->count <= $num * 2)
			{
				// return all records without limit 
				return $cq->result->ads_all;
			}
			else
			{
				return array_slice($cq->result->ads_all, $st, $num);
			}
		}



		// store result in self 
		if (!isset($cq->result->_cqQueryPage))
		{
			$cq->result->_cqQueryPage = array();
		}
		$key = $page . ':' . $num;


		if (!isset($cq->result->_cqQueryPage[$key]))
		{
			$load_all = false;

			// check if on first page and count is less than 2 pages return all records 
			if (($page === 1 || $page === 2) && $cq->result->count <= $num * 2)
			{
				// return all records without limit 
				$limit = '';
				$load_all = true;
			}
			else
			{
				$limit = " LIMIT " . $st . "," . $num;
			}

			if ($cq->use_cache)
			{

				// quote ids_1k array
				$order_by_field = "ORDER BY FIELD (id, " . $cq->result->ids_1k_str . ")";

				// get ads in given range with additional check and preserve order 
				// store result in self 
				$ads = Ad::findAllFrom('Ad', "id IN (" . $cq->result->ids_1k_str . ") AND listed=1 " . $order_by_field . $limit, array());
			}
			else
			{
				// get ads without using cache 
				//$ads = Ad::findAllFromUseIds('Ad', $cq->query_ordered . $limit, $cq->values, MAIN_DB, '*', 'id');
				$ads = Ad::queryUsingIds($cq->query_ordered . $limit, $cq->values);
			}

			if ($load_all)
			{
				// loaded all 
				$cq->result->ads_all = $ads;
			}
			else
			{
				// loaded only one page 
				$cq->result->_cqQueryPage[$key] = $ads;
			}
		}
		else
		{
			$ads = $cq->result->_cqQueryPage[$key];
		}

		return $ads;
	}

	static public function cqQueryFeatured($cq, $num_rows_featured = 10)
	{


		if ($num_rows_featured < 1)
		{
			// num is smaller than 1, return empty result 
			return array();
		}

		// perform init 
		self::cqQueryInit($cq);


		if (!isset($cq->result->ads_featured))
		{
			// check if we have any featured at all 
			$total_featured = self::countFeatrued(true);
			if ($total_featured < 1)
			{
				// set empty array to not continue searching for reatured 
				$cq->result->ads_featured = array();
			}
		}

		if (!isset($cq->result->ads_featured))
		{
			// first check if we have loaded all possible ads for this query 
			if (isset($cq->result->ads_all))
			{
				// create array to know that we have already checked all possible featured ads for current query
				$cq->result->ads_featured = array();
				// store featured ads 
				foreach ($cq->result->ads_all as $ad)
				{
					if ($ad->featured)
					{
						$cq->result->ads_featured[] = $ad;
					}
				}
			}
		}


		// analyze if query simple then use it if complex try using cached ids
		// this is a bit different from Ad::cqQueryIsUseCache($cq)
		$is_simple = true;
		// afr5: has 5 custom field; 
		// >,<: has range;
		// match: has search
		// like: has search 
		$arr_complex = array(
			'afr5',
			'>',
			'<',
			'match',
			'like',
		);
		// query to check 
		$str_check = StringUtf8::strtolower($cq->query_featured);

		// skip case if published_at>123 in query, so replace it with custom placeholder. 
		// do not use $str_check for actual query
		// this is to show all featured items for last 30,90 days
		// because in last 1k results you may get only last 1-5 day data in general
		$str_check = str_replace('published_at>', 'published_at=', $str_check);
		foreach ($arr_complex as $check)
		{
			if (strpos($str_check, $check) !== false)
			{
				// query is not simple
				$is_simple = false;
				break;
			}
		}


		// if $cq->result->ads_featured not initialized then we need to find featured ads for this query
		if (!isset($cq->result->ads_featured) && !$is_simple)
		{
			// complex query use existing values 
			if ($cq->result->ids_1k)
			{
				// we have some ids to work with 
				// if $cq is cachable then it is slow query. we can use ids for finding featured 
				$num = $num_rows_featured;
				if ($num < 300)
				{
					$num = 300;
				}
				$cq->result->ads_featured = Ad::findAllFrom('Ad', "listed=1 AND featured=1 AND id IN (" . $cq->result->ids_1k_str . ") LIMIT " . $num, array());
			}
		}


		if (!isset($cq->result->ads_featured))
		{
			// we still do not have any featured 
			// use given query
			// need to get from DB	using custom query. 
			// this is very slow, avoid using it if possible.
			$cq->result->ads_featured = Ad::featuredCustom($cq->query_featured, $cq->values, $num_rows_featured);
		}

		// use stored ads in current variable from previous calls
		$ads_featured = $cq->result->ads_featured;

		// return shuffled and limitd result 
		if ($ads_featured)
		{
			shuffle($ads_featured);
			if (count($ads_featured) > $num_rows_featured)
			{
				$ads_featured = array_slice($ads_featured, 0, $num_rows_featured);
			}
		}

		return $ads_featured;
	}

	/**
	 * get current number of featured ads
	 * 
	 * @staticvar int $total_featured
	 * @param bool $use_cache
	 * @return int
	 */
	static public function countFeatrued($use_cache = true)
	{
		static $total_featured = null;
		if (is_null($total_featured))
		{
			$total_featured = Ad::countByClass('Ad', 'listed=? AND featured=?', array(1, 1), $use_cache);
		}

		return intval($total_featured);
	}

	/**
	 * convert custom query description array to plain text and remove link tags, 
	 * keep usable, remove formatting 
	 * 
	 * @param type $cq
	 */
	static public function cqFormatDescription($cq)
	{
		$arr_desc = array();
		foreach ($cq->params as $k => $v)
		{
			if (isset($v['description']))
			{
				if (is_array($v['description']))
				{
					foreach ($v['description'] as $d)
					{
						$arr_desc[] = $d;
					}
				}
				else
				{
					$arr_desc[] = $v['description'];
				}
			}
		}

		$return = implode(', ', $arr_desc);

		//echo '[cqFormatDescription:' . $return . ']';
		return $return;
	}

	/**
	 * Generate and format filter removing array or string.
	 * if $options['join'] given then will return string, else array
	 * 
	 * @param type $cq
	 * @param array $options
	 * @return string|array
	 */
	static public function cqFormatFilterRemover($cq, $options = array())
	{
		////////////////////////
		// FOR OLD THEMES RETURNED ARRAY in IndexController then formated in theme category.php view
		// to support old themes we still need to pass this array in old fasion. and use new way in new themes.
		////////////////////////
		/*
		  $search_desc_arr = Ad::cqFormatFilterRemover($cq,array(
		  'pattern'	=> '{name} <a href="{url}" class="button red small" title="' . __('Remove filter') . '">x</a>',
		  'join'	=> null
		  ));
		 * 
		 * 		 
		  $search_desc .= '<span class="search_filter">' . implode('</span>, <span class="search_filter">', $search_desc_arr) . '</span>';
		  $search_desc .= ' <a href="' . Location::url($selected_location, $selected_category) . '">' . __('View all') . '</a>';
		  echo '<p class="search_desc">' . $search_desc . '</p>';
		 */

		// suggested values 
		$options_default = array(
			'pattern'	 => '<span class="search_filter">{name} <a href="{url}" class="search_filter_remove" title="' . __('Remove filter') . '">{icon}</a></span>',
			'join'		 => ' ',
			'wrap'		 => '<p class="search_filters"><a href="' . Language::get_url() . '" class="search_filter_clear" title="' . __('Clear') . '">{icon}</a> {items}</p>',
			'icon'		 => '&times;'
		);

		$options = array_merge($options_default, $options);
		// remove nulls 
		$options = array_filter($options);

		$return = array();
		if (isset($cq->params))
		{
			foreach ($cq->params as $k => $v)
			{
				if (isset($v['description']))
				{
					if (is_array($v['description']))
					{
						foreach ($v['description'] as $d_k => $d)
						{

							$return[] = str_replace(array(
								'{name}',
								'{url}',
								'{icon}'
									),
							   array(
										$d,
										$v['url_remove'][$d_k],
										$options['icon']
									),
							   $options['pattern']);
						}
					}
					else
					{
						$return[] = str_replace(array(
							'{name}',
							'{url}',
							'{icon}'
								),
							  array(
									$v['description'],
									$v['url_remove'],
									$options['icon']
								),
							  $options['pattern']);
					}
				}
			}
		}


		// join to string and wrap if set 
		if (isset($options['join']))
		{
			if ($return)
			{
				// join and wrap result 
				$return = implode($options['join'], $return);
				if (isset($options['wrap']))
				{
					$return = str_replace(array('{items}', '{icon}'), array($return, $options['icon']), $options['wrap']);
				}
			}
			else
			{
				// because $options['join'] set we expect return value to be string. 
				// so return empty string
				$return = '';
			}
		}


		return $return;
	}

	static public function cqFormatTitle($cq, $first_ad = null)
	{
		$sp = $cq->url2vars->search_params;
		if ($sp['category_id'] != 0)
		{
			$arr_page_title[] = Category::getNameById($sp['category_id']);
		}

		if ($sp['location_id'])
		{
			$arr_page_title[] = Location::getNameById($sp['location_id']);
		}

		if ($cq->url2vars->selected_user)
		{
			$arr_page_title[] = $cq->url2vars->selected_user->name;
		}

		$page_title = Config::buildTitle($arr_page_title);

		// generate search_title_arr
		$search_title_arr = array();
		foreach ($cq->params as $k => $v)
		{
			if (isset($v['title']))
			{
				if (is_array($v['title']))
				{
					foreach ($v['title'] as $t_key => $t_val)
					{
						if (!isset($search_title_arr[$t_key]))
						{
							$search_title_arr[$t_key] = array();
						}
						if (is_array($t_val))
						{
							foreach ($t_val as $tt_key => $tt_val)
							{
								$search_title_arr[$t_key][$tt_key] = $tt_val;
							}
						}
						else
						{
							$search_title_arr[$t_key][] = $t_val;
						}
					}
				}
				else
				{
					$search_title_arr[] = $v['title'];
				}
			}
		}


		// set custom title 
		if ($cq->is_search && $search_title_arr)
		{
			// page title pattern 
			// {start} {cf} $page_title {end}
			if (isset($search_title_arr['start']))
			{
				$_page_title[] = implode(', ', $search_title_arr['start']);
			}
			if (isset($search_title_arr['cf']))
			{
				// fix order of cf
				// check order from first add
				//$first_ad = reset($ads);
				if ($first_ad)
				{
					Ad::appendCategoryFieldRelation($first_ad);
					if ($first_ad->CategoryFieldRelation)
					{
						$search_title_arr['cf_ordered'] = array();
						foreach ($first_ad->CategoryFieldRelation as $cf)
						{
							if (isset($search_title_arr['cf'][$cf->adfield_id]))
							{
								$search_title_arr['cf_ordered'][$cf->adfield_id] = $search_title_arr['cf'][$cf->adfield_id];
							}
						}
						$search_title_arr['cf'] = $search_title_arr['cf_ordered'];
					}
				}

				// build title 
				$_page_title[] = implode(', ', $search_title_arr['cf']);
			}
			if (strlen($page_title))
			{
				$_page_title[] = $page_title;
			}
			elseif (!$_page_title)
			{
				$_page_title[] = __('All');
			}
			if (isset($search_title_arr['end']))
			{
				$_page_title[] = implode(', ', $search_title_arr['end']);
			}

			$page_title = implode(', ', $_page_title);
			unset($_page_title);
		}

		return $page_title;
	}

	public static function clearNotActivated()
	{
		$days = intval(Config::option('ads_verification_days'));
		if ($days < 1)
		{
			$days = 7;
		}

		$expire_time = REQUEST_TIME - $days * 24 * 3600; //delete not verified ads in given period
		// get not activated ads and remove them . limit 100 no not overload server
		$ads = self::findAllFrom('Ad', "verified NOT IN ('0','1') AND updated_at<? ORDER BY updated_at ASC LIMIT 100", array($expire_time));
		foreach ($ads as $ad)
		{
			$ad->delete();
		}

		// clear not activated users as well
		return User::clearNotActivated();
	}

	/**
	 * check if ad posting activation required by user id and email. if email matched to logged in user then no need for activation.
	 * 
	 * @param Ad $ad
	 * @return boolean 
	 */
	public static function isVerificationRequired($ad)
	{
		// if user logged in and posted ad to same email address then no verification required
		if ($ad->added_by && AuthUser::$user->id == $ad->added_by && AuthUser::$user->email == $ad->email)
		{
			return false;
		}
		return true;
	}

	/**
	 * on ad update if email changed and not verified then resend verification email
	 * 
	 * @param Ad $ad
	 * @return boolean 
	 */
	public static function isVerificationRequiredAgain($ad)
	{
		return (self::isVerificationRequired($ad) && !self::isVerified($ad));
	}

	/**
	 * if ad verified by user or admin then returns true
	 * @param Ad $ad
	 * @return bool 
	 */
	public static function isVerified($ad)
	{
		return in_array($ad->verified, array('0', '1'));
	}

	/**
	 * if ad approved returns true $ad->enabled == Ad::STATUS_ENABLED
	 * 
	 * @param Ad $ad
	 * @return bool 
	 */
	public static function isApproved($ad)
	{
		return self::isStatus($ad, Ad::STATUS_ENABLED);
		;
	}

	/**
	 * if ad paused returns true $ad->enabled == Ad::STATUS_PAUSED
	 * 
	 * @param type $ad
	 * @return bool
	 */
	public static function isPaused($ad)
	{
		return self::isStatus($ad, Ad::STATUS_PAUSED);
	}

	public static function isStatus($ad, $status)
	{
		return $ad->enabled === $status;
	}

	/**
	 * if featured returns true $ad->featured
	 * @param type $ad
	 * @return bool
	 */
	public static function isFeatured($ad)
	{
		return $ad->featured ? true : false;
	}

	/**
	 * after user registration all previously posted ads with this email is associated to this user. 
	 * added_by value will be set to this user id in Ad table
	 * 
	 * @param User $user
	 * @return bool 
	 */
	public static function associateAdsToUser($user, $associate = true)
	{
		if ($user)
		{
			if ($associate)
			{
				// associate ads
				return Ad::update('Ad', array('added_by' => $user->id), 'added_by=? AND email=?', array(0, $user->email));
			}
			else
			{
				// disassociate ads 
				// move all ads to trash and make not running
				// make added by 0, they will be associated if user registers again
				$data = array(
					'enabled'	 => Ad::STATUS_TRASH,
					'listed'	 => 0,
					'updated_at' => REQUEST_TIME,
					'added_by'	 => 0
				);

				return Ad::update('Ad', $data, "added_by=?", array($user->id));
			}
		}
		return false;
	}

	/**
	 * Associate ads verified by admin to users if they are not related to any user
	 * It helps usrs to manage their ads even if it is verified by admin
	 * 
	 * @param array $ad_ids 
	 * @return boolean
	 */
	public static function associateVerifiedAdsToUsers($ad_ids = array())
	{
		$return = false;
		if ($ad_ids)
		{
			$ids_ = self::ids2quote($ad_ids);
			$where = "added_by=? AND verified=? AND id IN (" . implode(',', $ids_) . ") ";
			$values = array(0, 0);
			// search for ads verified but don thave associatted user. 
			$ads = Ad::findAllFrom('Ad', $where . ' GROUP BY email', $values);

			if ($ads)
			{
				//now append users by email
				User::appendObject($ads, 'email', 'User', 'email');
				foreach ($ads as $ad)
				{
					if (isset($ad->User))
					{
						// append all user ads to this user
						$return = Ad::associateAdsToUser($ad->User) || $return;
					}
				}
			}
		}


		return $return;
	}

	/**
	 * Append previous and next ad in same category and location 
	 * 
	 * @param Ad $ad
	 */
	public static function appendPrevNext($ad)
	{
		//return false;
		if (!isset($ad->prev_next))
		{
			$where_values = array($ad->id, 1, $ad->category_id, $ad->location_id);
			$prev = Ad::findOneFrom('Ad', 'id<? AND listed=? AND category_id=? AND location_id=? ORDER BY id DESC', $where_values, MAIN_DB, 'id');
			$next = Ad::findOneFrom('Ad', 'id>? AND listed=? AND category_id=? AND location_id=? ORDER BY id ASC', $where_values, MAIN_DB, 'id');

			$ads = array();
			if ($prev)
			{
				$ads[] = $prev;
			}
			if ($next)
			{
				$ads[] = $next;
			}

			Ad::appendObject($ads, 'id', 'Ad', 'id');

			$ad->prev_next = new stdClass();
			$ad->prev_next->prev = $prev->Ad;
			$ad->prev_next->next = $next->Ad;
		}
	}

	/**
	 * Append related Adpics object to each ad
	 *  
	 * @param array $ads Ad
	 * @param bool $all false, if true append all Adpics, if false appends only one
	 */
	public static function appendAdpics($ads, $all = false)
	{
		if ($all)
		{
			// append all images related to ad
			Ad::appendObject($ads, 'id', 'Adpics', 'ad_id', '', MAIN_DB, '*', false, true);
		}
		else
		{
			// append only one image
			Ad::appendObject($ads, 'id', 'Adpics', 'ad_id');
		}
	}

	/**
	 * append custom field data to ad. used for displaying custom fields when ad viewed.	 * 
	 * @param Ad $ad 
	 */
	public static function appendCategoryFieldRelation($ad)
	{
		if (!isset($ad->CategoryFieldRelation))
		{
			$ad->CategoryFieldRelation = CategoryFieldRelation::getCatfields($ad->location_id, $ad->category_id, true, true);
		}
	}

	/**
	 * Update all listed ads every 24 hours. 
	 * 
	 * @param bool $force update imedaitely
	 * @param array $ids 
	 * @return bool
	 */
	public static function updateListed($force = false, $ids = array())
	{
		$return1 = $return2 = false;
		// time to wait between each run
		$wait = 3600 * 24;
		/// time to wait to coplete this run
		$wait_finish = 300;


		if ($force || $ids || Config::option('last_updateListed') < REQUEST_TIME - $wait)
		{

			// set 5 minute rest time to not overload server with update listed
			if (!$ids)
			{
				Config::optionSet('last_updateListed', REQUEST_TIME - $wait + $wait_finish);
			}


			$vals = array();
			$where = '';
			if ($ids)
			{
				if (is_array($ids))
				{
					$ids_ = Ad::ids2quote($ids);
					$where = "id IN (" . implode(',', $ids_) . ") AND ";
				}
				else
				{
					$where = "id=? AND ";
					$vals[] = $ids;
				}
			}
			$vals[] = AdAbuse::getMinimum();
			$vals[] = REQUEST_TIME;

			// make listed: verified, enabled, not expired, no payment required ads
			$return1 = self::update('Ad', array('listed' => 1), $where . "listed=0 AND "
							. "enabled=" . Ad::STATUS_ENABLED . " AND "
							. "verified IN('0','1') AND "
							. "requires_posting_payment=0 AND "
							. "(abused<? OR featured=1) AND "
							. "expireson>?", $vals);

			// make unlisted: not verified or not enabled or expired or payment required ads
			$return2 = self::update('Ad', array('listed' => 0), $where . 'listed=1 AND '
							. '(enabled!=' . Ad::STATUS_ENABLED . ' OR '
							. "verified NOT IN('0','1') OR "
							. 'requires_posting_payment=1 OR '
							. '(abused>=? AND featured=0) OR '
							. 'expireson<?)', $vals);

			if (!$ids)
			{
				// if not set per limited id values then set last update time 
				Config::optionSet('last_updateListed', REQUEST_TIME);
			}

			// delete cache 
			SimpleCache::delete('ad');
		}

		return ($return1 || $return2);
	}

	/**
	 * Moves expired && enabled items to trash after set days in settings 'delete_after_days'
	 * value must be !(-1 || '') 
	 * 	not -1
	 *  not empty string 
	 * 
	 * @param boolean $force
	 * @return boolean
	 */
	public static function trashExpired($force = false)
	{
		$wait = 3600 * 24;
		// empty string or -1 is disabling this option
		$days = trim(Config::option('delete_after_days'));
		if ($days == '')
		{
			$days = -1;
		}
		else
		{
			$days = intval($days);
		}


		if (!$force)
		{
			$force = Config::option('last_trashExpired') < (REQUEST_TIME - $wait + 125);
		}

		$return = false;

		// make reset time 10 min different from updateListed to not load them both at once.
		if ($days > -1 && $force)
		{
			// save this run time 
			Config::optionSet('last_trashExpired', REQUEST_TIME);

			// delete items expired older than this
			$time = REQUEST_TIME - 3600 * 24 * $days;

			// set updated_at to use when auto deleting from trash
			$data = array(
				'enabled'	 => Ad::STATUS_TRASH,
				'updated_at' => REQUEST_TIME
			);
			$where = "enabled=? AND expireson<?";
			$values = array(Ad::STATUS_ENABLED, $time);

			$return = Ad::update('Ad', $data, $where, $values);
		}

		return $return;
	}

	/**
	 * Moves other selected items to trash after set days in settings 'auto_move_to_trash_after'
	 * value must be !(-1 || '') 
	 * 	not -1
	 *  not empty string 
	 * 
	 * @param boolean $force
	 * @return boolean
	 */
	public static function trashOther($force = false)
	{
		$wait = 3600 * 24;
		$auto_move_to_trash_after = @unserialize(Config::option('auto_move_to_trash_after'));
		$days = trim($auto_move_to_trash_after['days']);
		if ($days == '')
		{
			$days = -1;
		}
		else
		{
			$days = intval($days);
		}

		if (isset($auto_move_to_trash_after['status']))
		{
			$statuses_ = Ad::quoteArray($auto_move_to_trash_after['status']);
		}
		else
		{
			$statuses_ = array();
		}


		if (!$force)
		{
			// make time a bit different from updateListed to not load them both at once.
			$force = Config::option('last_trashOther') < (REQUEST_TIME - $wait + 71);
		}

		$return = false;

		if ($days > -1 && $statuses_ && $force)
		{
			// save this run time 
			Config::optionSet('last_trashOther', REQUEST_TIME);

			// delete items expired older than this
			$time = REQUEST_TIME - 3600 * 24 * $days;

			// set updated_at to use when auto deleting from trash
			$data = array(
				'enabled'	 => Ad::STATUS_TRASH,
				'updated_at' => REQUEST_TIME
			);

			$where = "enabled IN (" . implode(',', $statuses_) . ") AND updated_at<?";
			$values = array($time);

			$return = Ad::update('Ad', $data, $where, $values);
		}

		return $return;
	}

	/**
	 * Deletes items from trash periodically every hour 
	 * deletes only if set in site settings 'auto_delete_from_trash_days' = !(-1 | '')  
	 * 
	 * @param boolean $force
	 * @return boolean
	 */
	public static function deleteTrash($force = false)
	{

		// empty string or -1 is disabling this option
		$days = trim(Config::option('auto_delete_from_trash_days'));
		if ($days == '')
		{
			$days = -1;
		}
		$days = intval($days);

		// maximum number of ads to delete per request
		// delete in batches 
		$max_num = 100;
		// delete every 10 minutes 
		$wait = 600;

		if (!$force)
		{
			// make reset time 33 sec different from updateListed to not load them both at once.
			$force = Config::option('last_deleteTrash') < (REQUEST_TIME - $wait);
		}


		if ($days > -1 && $force)
		{
			// save this before running delete to not run twice by seperate process
			// deleting can be slow process becaouse deleting in many tables and image files
			Config::optionSet('last_deleteTrash', REQUEST_TIME);

			$vals = array();

			// items in trash only deleted from system 
			$vals[] = Ad::STATUS_TRASH;
			// exp + days < today
			$vals[] = REQUEST_TIME - 3600 * 24 * $days;

			// delete ads after cirtain days passed after expiring
			$ads = Ad::findAllFrom('Ad', 'enabled=? AND updated_at<? LIMIT 0,' . $max_num, $vals);
			foreach ($ads as $ad)
			{
				$ad->delete();
			}
		}

		return true;
	}

	/**
	 * Update featred ads every 24 hours. check if fetured_expireson passed
	 * 
	 * @param bool $force update imedaitely
	 * @return bool
	 */
	public static function updateFeatured($force = false, $ids = array())
	{
		$return1 = $return2 = false;
		// make reset time 10 min different from updateListed to not load them both at once.
		if ($force || $ids || Config::option('last_updateFeatured') < REQUEST_TIME - 3600 * 24 + 600)
		{

			$vals = array();
			$where = '';
			if ($ids)
			{
				if (is_array($ids))
				{
					$ids_ = Ad::ids2quote($ids);
					$where = "id IN (" . implode(',', $ids_) . ") AND ";
				}
				else
				{
					$where = "id=? AND ";
					$vals[] = $ids;
				}
			}
			$vals[] = REQUEST_TIME;

			// make listed: verified, enabled, not expired, no payment required ads
			$return1 = self::update('Ad', array('featured' => 1), $where . "featured=0 AND featured_expireson>?", $vals);

			// make unlisted: not verified or not enabled or expired or payment required ads
			$return2 = self::update('Ad', array('featured' => 0), $where . 'featured=1 AND featured_expireson<?', $vals);


			if (!$ids)
			{
				// if not set per limited id values then set last update time 
				// updated all records so reset last update time 
				Config::optionSet('last_updateFeatured', REQUEST_TIME);
			}

			// delete cache 
			SimpleCache::delete('ad');
		}

		return ($return1 || $return2);
	}

	/**
	 * generate ad posting url for given category and location 
	 * 
	 * @param type $location
	 * @param type $category
	 * @return type 
	 */
	public static function urlPost($location, $category, $change = false)
	{
		return Language::get_url('post/item/' . ($location->id * 1) . '/' . ($category->id * 1) . '/' . ($change ? '1/' : ''));
	}

	public static function urlActivate($ad)
	{
		return Language::get_url('post/activate/' . $ad->id . '/' . $ad->verified . '/');
	}

	public static function urlPromote($ad)
	{
		return Language::get_url('admin/promote/' . $ad->id . '/');
	}

	/**
	 * Generate QR code image url 
	 * Uses google service 
	 * 
	 * @param Ad $ad
	 * @return string url
	 */
	public static function urlQR($ad)
	{
		/*
		  https://developers.google.com/chart/infographics/docs/qr_codes?hl=de-de
		  http://chart.apis.google.com/chart?chs=200x200&cht=qr&chld=L|0&chl=13.06.2014
		 * 
		 * Formatting
		 * https://www.nttdocomo.co.jp/english/service/developer/make/content/barcode/function/application/addressbook/
		 * http://blog.thenetimpact.com/2011/07/decoding-qr-codes-how-to-format-data-for-qr-code-generators/
		 */

		// get info for ad
		$contact_details = '';
		$price = AdFieldRelation::getPrice($ad);
		$nl = "\r\n";

		// title 
		$info = View::escape(Ad::getTitle($ad)) . (strlen($price) ? ' - ' . $price : '') . $nl;

		// link 
		$info .= Ad::permalinkShort($ad) . $nl;

		// address
		$address = AdFieldRelation::getByType($ad, AdField::TYPE_ADDRESS, false);
		if ($address)
		{
			$info .= View::escape($address) . $nl;
		}

		// phone
		if ($ad->phone)
		{
			$contact_details .= __('Phone') . ': ' . View::escape($ad->phone) . $nl;
		}

		// email
		switch ($ad->showemail)
		{
			case Ad::SHOWEMAIL_YES:
				$contact_details .= __('Email') . ': ' . View::escape($ad->email) . $nl;
				break;
		}

		if (Config::option('view_contact_registered_only') && !AuthUser::isLoggedIn(false))
		{
			// display login link
			$contact_details = '';
		}

		$info .= $contact_details;
		$info .= __('Date') . ': ' . Config::date($ad->published_at);

		return '//chart.apis.google.com/chart?chs=300x300&cht=qr&chld=L|2&chl=' . urlencode($info);
	}

	public static function urlEdit($ad, $returl = '')
	{
		$_returl = '';
		if (strlen($returl))
		{
			$_returl = '?returl=' . rawurlencode($returl);
		}
		return Language::get_url('admin/itemedit/' . $ad->id . '/' . $_returl);
	}

	public static function urlAbuseReports($ad)
	{
		return Language::get_url('admin/itemAbuse/' . $ad->id . '/');
	}

	public static function urlEditList($ad)
	{
		return Language::get_url('admin/items/?search=' . $ad->id);
	}

	public static function urlReturn($default = 'admin/items/')
	{
		$redirect_url = (isset($_GET['returl']) ? $_GET['returl'] : $default);
		return Language::get_url($redirect_url);
	}

	public static function formatDate($ad, $format = null)
	{
		if (is_null($format))
		{
			// display relative date only if smaller than 1 year
			$time_diff = $ad->published_at - REQUEST_TIME;
			if ($time_diff < (3600 * 24 * 365))
			{
				return Config::timeRelative($ad->published_at, 1);
			}

			return Config::date($ad->published_at);
		}

		// $format == 'c'  ISO 8601 date for schema.org/Product
		return date($format, $ad->published_at);
	}

	/**
	 * Format previous / next ad navigation links 
	 * 
	 * @param Ad $ad
	 * @param array $pattern
	 * @return string
	 */
	public static function formatPrevNext($ad, $pattern = array())
	{
		// define default pattern
		$pattern_default = array(
			'wrap'			 => '<p class="item_prev_next">{BUTTON_PREV} {BUTTON_NEXT}</p>',
			'button_prev'	 => '<span class="item_prev"><a href="{URL_PREV}"> &larr; {TITLE_PREV}</a></span>',
			'button_next'	 => '<span class="item_next"><a href="{URL_NEXT}">{TITLE_NEXT} &rarr;</a></span>'
		);

		// substitute missing parts from default pattern
		$pattern = array_merge($pattern_default, $pattern);

		// previous next ads 
		$retrun = '';
		if ($ad->prev_next->prev || $ad->prev_next->next)
		{
			if ($ad->prev_next->prev)
			{
				$button_prev = str_replace(array(
					'{URL_PREV}',
					'{TITLE_PREV}'
						), array(
					Ad::url($ad->prev_next->prev),
					View::escape(Ad::getTitle($ad->prev_next->prev))
						), $pattern['button_prev']);
			}
			else
			{
				$button_prev = '';
			}

			if ($ad->prev_next->next)
			{
				$button_next = str_replace(array(
					'{URL_NEXT}',
					'{TITLE_NEXT}'
						), array(
					Ad::url($ad->prev_next->next),
					View::escape(Ad::getTitle($ad->prev_next->next))
						), $pattern['button_next']);
			}
			else
			{
				$button_next = '';
			}

			$retrun = str_replace(array('{BUTTON_PREV}', '{BUTTON_NEXT}'), array($button_prev, $button_next), $pattern['wrap']);
		}
		return $retrun;
	}

	/**
	 * Format location and category link for given ad
	 * 
	 * @param Ad $ad
	 * @param string $seperator
	 * @param string $pattern
	 * @return string
	 */
	public static function formatLocationCategoryLink($ad, $seperator = ', ', $full = false, $pattern = '<a href="{url}">{name}</a>')
	{
		$loc_cat = '';

		if ($full)
		{
			// get full name
			$loc = Location::getFullNameById($ad->location_id, '', $seperator);
			$cat = Category::getFullNameById($ad->category_id, '', $seperator);
		}
		else
		{
			// get simple name
			$loc = Location::getNameById($ad->location_id);
			$cat = Category::getNameById($ad->category_id);
		}

		if ($loc || $cat)
		{
			$arr_loc_cat = array();
			if ($cat)
			{
				$arr_loc_cat[] = $cat;
			}
			if ($loc)
			{
				$arr_loc_cat[] = $loc;
			}

			$name = implode($arr_loc_cat, $seperator);
			$url = Location::urlById($ad->location_id, $ad->category_id);
			$loc_cat = str_replace(array('{name}', '{url}'), array($name, $url), $pattern);
		}

		return $loc_cat;
	}

	/**
	 * format given value as html value. used to display custom fields in ad page and in listing
	 * 
	 * $settings_default = array(
	  'display_all_checkboxes' => false,
	  'checkbox_pattern' => '<span class="{class}">{val}</span>',
	  'checkbox_seperator' => ' ',
	  'make_link' => false,
	  'escape' => true
	  );
	 * 
	 * @param AdField $af
	 * @param string $val
	 * @param array $settings
	 * @return string 
	 */
	public static function formatCustomValue($af, $val, $settings = array())
	{

		$settings_default = array(
			'display_all_checkboxes' => false,
			'checkbox_pattern'		 => '<span class="{class}">{val}</span>',
			'checkbox_seperator'	 => ' ',
			'make_link'				 => false,
			'escape'				 => true
		);

		// merge settings
		$settings = array_merge($settings_default, $settings);

		$display_all_checkboxes = $settings['display_all_checkboxes'];
		$checkbox_pattern = $settings['checkbox_pattern'];
		$checkbox_seperator = $settings['checkbox_seperator'];
		$make_link = $settings['make_link'];
		$escape = $settings['escape'];


		// if make link set then must escape 
		if ($make_link)
		{
			$escape = true;
		}

		switch ($af->type)
		{
			case AdField::TYPE_CHECKBOX:
				// format checkbox values
				$selected_vals = AdField::parseVals($val, true);
				$str_val = array();
				foreach ($af->AdFieldValue as $afv_id => $afv)
				{
					$selected = isset($selected_vals[$afv_id]);
					if ($selected || $display_all_checkboxes)
					{
						$_val = AdFieldValue::getName($afv);
						if ($escape || $checkbox_pattern)
						{
							$_val = View::escape($_val);
						}

						// convert to link 
						if ($make_link && $selected)
						{
							$_val = Ad::makeCustomValueFilterLink($af, $afv_id, $_val);
						}

						// format checkboxes different
						if ($checkbox_pattern)
						{
							$class = ($selected ? 'custom_field_selected' : 'custom_field');
							$str_val[] = str_replace(array('{class}', '{val}'), array($class, $_val), $checkbox_pattern);
						}
						elseif ($selected)
						{
							// if no pattern then display only selected checkboxes. Because it is plain text.
							$str_val[] = $_val;
						}
					}
				}

				$return = implode($checkbox_seperator, $str_val);
				break;
			case AdField::TYPE_RADIO:
			case AdField::TYPE_DROPDOWN:
				$afv = $af->AdFieldValue[$val];
				$return = AdFieldValue::getName($afv);

				if ($escape)
				{
					$return = View::escape($return);
				}

				// convert to link 
				if ($make_link)
				{
					$return = Ad::makeCustomValueFilterLink($af, $val, $return);
				}
				break;
			case AdField::TYPE_NUMBER:
				// format regular value
				if (strlen($val) && $val > 0)
				{
					$return = $val . AdField::getName($af, 'val');
					if ($escape)
					{
						$return = View::escape($return);
					}
				}
				break;
			case AdField::TYPE_PRICE:
				// format regular value
				if ($val > 0)
				{
					$return = self::formatCurrency($val);
					if ($escape)
					{
						$return = View::escape($return);
					}
				}
				break;
			case AdField::TYPE_URL:
				if (TextTransform::str2Url($val))
				{
					$return = TextTransform::str2Link($val);
				}
				else
				{
					$return = $val;
					if ($escape)
					{
						$return = View::escape($return);
					}
				}
				break;
			case AdField::TYPE_EMAIL:
				if (Validation::getInstance()->valid_email($val))
				{
					$return = View::escape($val);
					$return = '<a href="mailto:' . $return . '">' . $return . '</a>';
				}
				else
				{
					$return = $val;
					if ($escape)
					{
						$return = View::escape($return);
					}
				}
				break;
			case AdField::TYPE_TEXT:
			case AdField::TYPE_ADDRESS:
			case AdField::TYPE_VIDEO_URL:
			default:
				// format regular value
				$return = $val;
				if ($escape)
				{
					$return = View::escape($return);
				}
		}

		return $return;
	}

	/**
	 * Wraps given string with link to filter results in curresponding cateogry 
	 * 
	 * @param AdField $adfield
	 * @param string $val
	 * @param string $str_val
	 * @return string plain text or formatted link <a>
	 */
	public static function makeCustomValueFilterLink($adfield, $val, $str_val)
	{
		switch ($adfield->type)
		{
			case AdField::TYPE_RADIO:
			case AdField::TYPE_DROPDOWN:
			case AdField::TYPE_CHECKBOX:
				// convert to link 
				if (!isset($_GET['cf'][$adfield->id]) || $_GET['cf'][$adfield->id] != $val)
				{
					$arr_vars2url = array();
					if (isset(IndexController::$selected_ad))
					{
						$arr_vars2url['location_id'] = IndexController::$selected_ad->location_id;
						$arr_vars2url['category_id'] = IndexController::$selected_ad->category_id;
					}
					if (isset(IndexController::$selected_user))
					{
						$arr_vars2url['user_id'] = '';
					}

					// assing get to customize later 
					$custom_get = $_GET;
					if ($adfield->type == AdField::TYPE_CHECKBOX)
					{
						// remove all other checkboxes and add only this one 
						unset($custom_get['cf'][$adfield->id]);

						// add this checkbox value
						$arr_vars2url['cf'][$adfield->id][$val] = $val;
					}
					else
					{
						$arr_vars2url['cf'][$adfield->id] = $val;
					}

					// remove pagination info 
					$custom_get['page'] = '';

					// generate url using new values 
					$_url = Permalink::vars2url($custom_get, $arr_vars2url);

					$str_val = '<a href="' . $_url . '">' . $str_val . '</a>';
				}
				break;
		}

		return $str_val;
	}

	/**
	 * Format amount with currency 
	 * use AdField::stringToFloat($val) to reverse convert to float 
	 * 
	 * @param int $number
	 * @return string
	 */
	public static function formatCurrency($number)
	{
		$currency = Config::option('currency');
		$currency_format = Config::option('currency_format');
		$currency_decimal_num = Config::option('currency_decimal_num');
		$currency_decimal_point = Config::option('currency_decimal_point');
		$currency_thousands_seperator = Config::option('currency_thousands_seperator');

		if (!$currency_decimal_num)
		{
			$currency_decimal_num = 0;
		}
		if (strlen($currency_decimal_point) == 0)
		{
			$currency_decimal_point = '';
		}
		if (strlen($currency_thousands_seperator) == 0)
		{
			$currency_thousands_seperator = '';
		}

		$amount = number_format($number, $currency_decimal_num, $currency_decimal_point, $currency_thousands_seperator);

		if (strpos($currency_format, '{NUMBER}') === false || strpos($currency_format, '{CURRENCY}') === false)
		{
			$currency_format = '{NUMBER}{CURRENCY}';
		}

		return str_replace(array('{NUMBER}', '{CURRENCY}'), array($amount, $currency), $currency_format);
	}

	/**
	 * 
	 * @param Location $location
	 * @param Category $category
	 * @param User $user
	 * @param type $listed true:listed, false:not listed, null:all - default true
	 * @return int
	 */
	public static function countBy($location = null, $category = null, $user = null, $listed = true)
	{
		$whereA = $whereB = array();
		self::buildCategoryQuery($category, $whereA, $whereB);
		self::buildLocationQuery($location, $whereA, $whereB);

		if (isset($listed))
		{
			$whereA[] = 'listed=?';
			$whereB[] = intval($listed);
		}

		if (isset($user))
		{
			$whereA[] = 'added_by=?';
			$whereB[] = $user->id;
		}

		if ($whereA)
		{
			$where = str_replace('ad.', '', implode(' AND ', $whereA));
			return self::countByClass('Ad', $where, $whereB);
		}
		else
		{
			return self::countByClass('Ad');
		}
	}

	public static function countByCustom($query, $values = array(), $cache = true)
	{
		$cache_key = 'ad_count.countByCustom.' . SimpleCache::uniqueKey($query, $values);
		if ($cache)
		{
			$return = SimpleCache::get($cache_key);
			if ($return === false)
			{
				$total = Ad::query($query, $values);
				$return = intval($total[0]->num);

				SimpleCache::set($cache_key, $return);
			}
		}
		else
		{
			if (is_null($cache))
			{
				// delete cached value
				SimpleCache::delete($cache_key);
			}

			$total = Ad::query($query, $values);
			$return = intval($total[0]->num);
		}

		return $return;
	}

	/**
	 * count ads with cache 
	 * 
	 * @param string $class
	 * @param string $where
	 * @param array $values
	 * @return int
	 */
	public static function countByClass($class, $where = false, $values = array(), $cache = true)
	{
		$cache_key = 'ad_count.' . SimpleCache::uniqueKey($class, $where, $values);
		if ($cache)
		{
			$return = SimpleCache::get($cache_key);
			if ($return === false)
			{
				$return = Ad::countFrom($class, $where, $values);

				SimpleCache::set($cache_key, $return, 600); // 10 minute cache counts 
			}
		}
		else
		{
			if (is_null($cache))
			{
				// clear cached value
				SimpleCache::delete($cache_key);
			}
			$return = Ad::countFrom($class, $where, $values);
		}

		return $return;
	}

	/**
	 * append category, location, all adpics, custom fields and values to ad
	 * @param Ad $ad 
	 */
	public static function appendAll($ad)
	{
		// append category, location, adpics
		Category::appendCategory($ad, 'category_id');
		Location::appendLocation($ad, 'location_id');
		Ad::appendAdpics($ad, true);

		// get custom category fields
		Ad::appendCategoryFieldRelation($ad);

		// append AdFieldRelation
		AdFieldRelation::appendAllFields($ad);
	}

	/**
	 * get latest ads in given location
	 * 
	 * @param Location $location
	 * @param int $num
	 * @return array Ad 
	 */
	public static function latestAds($num = 20, $settings = array())
	{
		$settings_default = array(
			'featured'		 => 'latest',
			'location'		 => null,
			'category'		 => null,
			'image'			 => false,
			'force'			 => false,
			'prefer_unique'	 => array()
		);

		// available featured values 
		// featured, latest, hit.[d,w,m,y,a]

		$settings = array_merge($settings_default, $settings);




		$location = $settings['location'];
		$category = $settings['category'];
		$num = intval($num);

		// set limit number. 
		$limit_num = $num;
		if ($settings['featured'] === 'featured')
		{
			// get more items for featured and later filter result, for better caching
			$limit_num = $num * 2;
			if ($limit_num < 100)
			{
				$limit_num = 100;
			}
		}


		// TODO: store only ids, and check if stored equal to requested. if not equal then get fresh data without cache. 
		// when user deletes ad we should check it and update cache, 
		// this is only way to detect deleted item and do not delete cache on every new item posted. 
		// also it iwll reduce space used to store cache, by storing only ids instead of full ad data
		$cache_prefix = 'ad.';
		if (strpos($settings['featured'], 'hit') !== false || $settings['prefer_unique'])
		{
			// hit queryes are slower when they have unique field. so dont delete them on each new ad post. 
			// that is why use seperate cache_prefix
			$cache_prefix = 'ad_';
		}

		$cache_key = $cache_prefix
				. $settings['featured']
				. '_l' . intval($location->id)
				. '_c' . intval($category->id)
				. '_n' . $limit_num
				. ($settings['image'] ? '_img' : '')
				. (isset($settings['prefer_unique']['user']) ? '_uU' : '')
				. (isset($settings['prefer_unique']['location']) ? '_uL' : '')
				. (isset($settings['prefer_unique']['category']) ? '_uC' : '')
				. '_id';


		$arr_ids = SimpleCache::get($cache_key);
		if ($arr_ids === false || $settings['force'])
		{

			$ad_ids = array();

			$whereA = array('ad.listed=?');
			$whereB = array(1);
			$order = ' ORDER BY ad.published_at DESC';
			$max_key = 'published_at';

			$from_extra = '';
			$group_by = '';

			switch ($settings['featured'])
			{
				case 'featured':
					// display featured only
					$whereA[] = 'ad.featured=?';
					$whereB[] = 1;
					$order = ' ORDER BY rand() ';
					break;
				case 'hit.d':
				case 'hit.w':
				case 'hit.m':
				case 'hit.y':
				case 'hit.a':
					list($hit, $hit_period) = explode('.', $settings['featured']);
					switch ($hit_period)
					{
						case 'd':
							$whereA[] = 'ad.added_at>?';
							$whereB[] = REQUEST_TIME - 24 * 3600;
							break;
						case 'w':
							$whereA[] = 'ad.added_at>?';
							$whereB[] = REQUEST_TIME - 24 * 3600 * 7;
							break;
						case 'm':
							$whereA[] = 'ad.added_at>?';
							$whereB[] = REQUEST_TIME - 24 * 3600 * 30;
							break;
						case 'y':
							$whereA[] = 'ad.added_at>?';
							$whereB[] = REQUEST_TIME - 24 * 3600 * 365;
							break;
					}
					$order = ' ORDER BY hits DESC';
					$max_key = 'hits';
					break;
			}


			// display ads with image only
			if ($settings['image'])
			{

				$whereA[] = "EXISTS (SELECT 1 FROM " . Adpics::tableNameFromClassName('Adpics') . " adp WHERE adp.ad_id=ad.id)";

				/* if ($settings['prefer_unique'])
				  {
				  // use subquery for getting unique ids
				  $whereA[] = "ad.id IN (SELECT ad_id FROM " . Adpics::tableNameFromClassName('Adpics') . ")";
				  }
				  else
				  {
				  $whereA[] = 'adp.ad_id=ad.id';
				  $from_extra = ", " . Adpics::tableNameFromClassName('Adpics') . " adp ";
				  $group_by = " GROUP BY ad.id";
				  } */
			}



			Ad::buildLocationQuery($location, $whereA, $whereB);
			Ad::buildCategoryQuery($category, $whereA, $whereB);

			//$where = str_replace('ad.', '', implode(' AND ', $whereA));
			$where = implode(' AND ', $whereA);
			$sql = "SELECT ad.id "
					. "FROM " . Ad::tableNameFromClassName('Ad') . " ad " . $from_extra
					. " WHERE " . $where
					. $group_by
					. $order
					. " LIMIT " . $limit_num;


			if ($settings['prefer_unique'])
			{
				$group_by2 = array();
				foreach ($settings['prefer_unique'] as $k => $v)
				{
					switch ($k)
					{
						case 'user':
							$group_by2[] = 'added_by';
							break;
						case 'category':
							$group_by2[] = 'category_id';
							break;
						case 'location':
							$group_by2[] = 'location_id';
							break;
					}
				}
				// if it is featured then use simple group by query 
				if ($settings['featured'] === 'featured')
				{
					// wrap query to group by user, category, location 
					/*
					  SELECT * FROM (
					  SELECT id,title,hits,added_by, location_id,category_id
					  FROM cb_ad WHERE listed=1 AND added_at>1534602023
					  ORDER BY hits DESC
					  ) as tmp_table group by added_by, category_id,location_id ORDER BY hits DESC LIMIT 50;
					 */
					// override default sql
					$sql = "SELECT ad.id "
							. "FROM " . Ad::tableNameFromClassName('Ad') . " ad " . $from_extra
							. " WHERE " . $where
							. $group_by
							. $order;

					$sql = " SELECT * FROM (" . $sql . ") as tmp_table "
							. "GROUP BY  tmp_table." . implode(', tmp_table.', $group_by2)
							. str_replace('ad.', 'tmp_table.', $order)
							. " LIMIT " . $limit_num;
				}
				else
				{
					// get max value in each group first . then in separate query get ads by max values
					/*
					  SELECT test_id, MAX(request_id), request_id
					  FROM testresults
					  GROUP BY test_id DESC;
					 */
					$group_by2_sql = "ad." . implode(', ad.', $group_by2);
					$sql_max = "SELECT "
							. " ad." . implode(', ad.', $group_by2) . ", MAX(ad." . $max_key . ") as max_" . $max_key . ", ad." . $max_key
							. " FROM " . Ad::tableNameFromClassName('Ad') . " ad " . $from_extra
							. " WHERE " . $where
							. ($group_by ? $group_by . ", " . $group_by2_sql : " GROUP BY " . $group_by2_sql)
							. " ORDER BY max_" . $max_key . " DESC "
							. " LIMIT " . $limit_num;

					$max_values = Ad::query($sql_max, $whereB);


					// now get ads by max_values
					if ($max_values)
					{

						if ($max_key === 'hits')
						{
							$max_operator = '>=';
						}
						else
						{
							$max_operator = '=';
						}

						$where2_vals = $whereB;
						foreach ($max_values as $max_val)
						{
							$where2_arr[] = "(ad." . $max_key . $max_operator . "? AND ad." . implode('=? AND ad.', $group_by2) . "=?)";
							$where2_vals[] = $max_val->{"max_" . $max_key};
							foreach ($group_by2 as $_group_by2)
							{
								$where2_vals[] = $max_val->{$_group_by2};
							}
						}
						$sql2 = "SELECT ad.id "
								. "FROM " . Ad::tableNameFromClassName('Ad') . " ad " . $from_extra
								. " WHERE " . $where . " AND (" . implode(' OR ', $where2_arr) . ")"
								. " GROUP BY " . $group_by2_sql
								. $order;
						$ad_ids = Ad::query($sql2, $where2_vals);
					}
				}
			}

			if (!$ad_ids)
			{
				// perform default query if no ads found for custom sqls
				$ad_ids = Ad::query($sql, $whereB);
			}

			//convert to ids array 
			$arr_ids = array();
			foreach ($ad_ids as $ad)
			{
				$arr_ids[] = $ad->id;
			}

			unset($ad_ids);

			// default ttl 3 hour
			$ttl = null;
			// set ttl 30 min for latest and featured ads if cache is persistent.
			if ($cache_prefix === 'ad_' && ($settings['featured'] === 'featured' || $settings['featured'] === 'latest'))
			{
				// 30 minutes
				$ttl = 1800;
			}

			SimpleCache::set($cache_key, $arr_ids, $ttl);
		}

		// set initial values
		$ads = array();

		// we have $arr_ids get listed ads 
		if ($arr_ids)
		{
			// quote $arr_ids array
			$arr_ids_q = Ad::quoteArray($arr_ids);
			$str_ids = implode(',', $arr_ids_q);

			// get ads in given range with additional check and preserve order 
			// store result in self 
			$ads = Ad::findAllFrom('Ad', "id IN (" . $str_ids . ") AND listed=1 ORDER BY FIELD (id, " . $str_ids . ")", array());

			// check if count is not matching and more persistent cache (ad_) used then force regenerate cache
			if (count($arr_ids) != count($ads) && $cache_prefix === 'ad_' && !$settings['force'])
			{
				// force to load fresh data because some cached records deleted
				$settings['force'] = true;
				return Ad::latestAds($num, $settings);
			}
		}


		// cache filled broad array seperately, with new call to self 
		$ads_fill = array();
		// if it is featured and not enought ads returned then append fetured from same location 
		if (count($ads) < $num)
		{
			if ($settings['featured'] === 'featured' && $category)
			{
				// get fetured by location only
				$settings_broad = $settings;
				$settings_broad['category'] = null;
				// remove unique to get more results  in one query for featured ads. Because no need to unique for additional reatured  list 
				$settings_broad['prefer_unique'] = array();

				// request same number of ads in order to store in cache same requests
				$ads_fill = self::latestAds($num, $settings_broad);
			}
			elseif ($settings['prefer_unique'])
			{
				// not enaught ads add not unique values to the list 
				$settings_broad = $settings;
				$settings_broad['prefer_unique'] = array();

				// request same number of ads in order to store in cache same requests
				$ads_fill = self::latestAds($num, $settings_broad);
			}

			if ($ads_fill)
			{
				// now append only non already existing ads 
				// to do it add ad index to ads array
				$ads_combined = array();
				foreach ($ads as $ad)
				{
					$ads_combined[$ad->id] = $ad;
				}
				foreach ($ads_fill as $ad)
				{
					$ads_combined[$ad->id] = $ad;
				}
				$ads = $ads_combined;

				// reduce array to exact number
				$ads = array_slice($ads, 0, $num);
			}
		}

		// do not cache shuffled array 
		if ($settings['featured'] === 'featured' && $ads)
		{
			// shuffle result o display random ads
			shuffle($ads);
			$ads = array_slice($ads, 0, $num);
		}

		return $ads;
	}

	/**
	 * Get latest viewed ads from cookie and DB
	 *   
	 * @param int $limit
	 * @param int $exlude_id in case you do not want to view currently viewing id in latest viewed
	 * @return array Ad
	 */
	public static function viewedAds($limit = 20, $exlude_id = 0)
	{
		$ads = array();

		// read from cookie 
		$viewedAds = Flash::getCookie('viewedAds');
		$ad_ids = explode(',', $viewedAds);

		if ($ad_ids && $ad_ids[0] > 0)
		{
			// remove exlude value 
			if ($exlude_id)
			{
				// remove 
				if (($key = array_search($exlude_id, $ad_ids)) !== false)
				{
					unset($ad_ids[$key]);
				}
			}

			// limit requested ads max to 100
			$ad_ids = array_slice($ad_ids, 0, 100);

			$ads = Ad::getByIds($ad_ids);

			// limit result to requested value
			$ads = array_slice($ads, 0, $limit);
		}

		return $ads;
	}

	/**
	 * Add given id to latest viewed ads list, store in cookie
	 * 
	 * @param int $ad_id
	 */
	public static function viewedAdsSet($ad_id)
	{
		// read from cookie 
		$viewedAds = Flash::getCookie('viewedAds');
		$ad_ids = explode(',', $viewedAds);

		// add given id to to beginning of list
		array_unshift($ad_ids, $ad_id);

		// remove duplicates
		$ad_ids = array_unique($ad_ids);

		// limit max to 100 records
		$ad_ids = array_slice($ad_ids, 0, 100);

		$str_ids = implode(',', $ad_ids);

		// store in cookie for 3 month
		Flash::setCookie('viewedAds', $str_ids, REQUEST_TIME + 3600 * 24 * 30 * 3);
	}

	/**
	 * get featured ads using custom query
	 * 
	 * @param string $query
	 * @param array $values
	 * @param int $limit
	 * @return array Ad 
	 */
	public static function featuredCustom($query, $values, $limit = 10)
	{
		// get max available records as possible then randomise in result
		$num = intval(max(array($limit * 10, 500)));

		// modify query to add limit param and get ids only
		$query = str_replace('ad.*', 'ad.id', $query);
		$query = $query . ' LIMIT ' . $num;


		$cache_key = 'ad.featuredCustom.n' . $num . '.' . SimpleCache::uniqueKey($query, $values);
		$ads = SimpleCache::get($cache_key);
		if ($ads === false)
		{
			$ads = Ad::query($query, $values);
			if ($ads)
			{
				$_ads_featured = array();
				foreach ($ads as $ad)
				{
					$_ads_featured[] = $ad->id;
				}
				$ads = $_ads_featured;
			}

			SimpleCache::set($cache_key, $ads);
		}

		if ($ads)
		{
			// now randomise and slice array 
			shuffle($ads);
			// get double limit in case some ads removed after cached
			$ads = array_slice($ads, 0, $limit * 2);
			// get items 
			$ads = ad::getByIds($ads);
			// make sure item is still listed and featured 
			$ads_ = array();
			foreach ($ads as $ad)
			{
				if (Ad::isFeatured($ad) && $ad->listed == 1)
				{
					$ads_[] = $ad;
				}
			}

			// reduce result to required num
			$ads = array_slice($ads_, 0, $limit);
		}
		else
		{
			$ads = array();
		}

		return $ads;
	}

	/**
	 * Auto approve value for given ad if it is defined in settings. 
	 * 
	 * @param Ad $ad
	 * @return int 0|1
	 */
	public static function autoApprove($ad)
	{
		// check auto approve permission and define if ad will be approved or wait for manual approval
		$auto_approve = Config::option('ads_auto_approve');
		/* 	0: None - approve ads manually,
		  1: Auto approve all ads by user and dealer,
		  2: Auto approve ads by user,
		  3: Auto approve ads by dealer,
		  4: Auto approve ads posted by previously approved users, */
		// default value 
		$enabled = Ad::STATUS_PENDING_APPROVAL;
		switch ($auto_approve)
		{
			case 0:
				//0: None - approve ads manually,
				break;
			case 1:
				// 1: Auto approve all ads by user and dealer,
				$enabled = Ad::STATUS_ENABLED;
				break;
			case 2:
				// 2: Auto approve ads by user,
				if (AuthUser::$user->level == User::PERMISSION_USER || AuthUser::hasPermission(User::PERMISSION_MODERATOR))
				{
					$enabled = Ad::STATUS_ENABLED;
				}
				break;
			case 3:
				// 3: Auto approve ads by dealer,
				if (AuthUser::$user->level == User::PERMISSION_DEALER || AuthUser::hasPermission(User::PERMISSION_MODERATOR))
				{
					$enabled = Ad::STATUS_ENABLED;
				}
				break;
			case 4:
				// 4: Auto approve ads posted by previously approved users,
				// moderators auto approve always 
				if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
				{
					$enabled = Ad::STATUS_ENABLED;
				}
				else
				{
					// verified and approved ads by current user
					$karma = User::karma(AuthUser::$user);
					if ($karma > 50)
					{
						$enabled = Ad::STATUS_ENABLED;
					}

					// if enabled then check if has pending ads
					if ($enabled)
					{
						// fix user_id if not set, we need it to check other pending items
						Ad::assignUserId($ad);

						// because if user has other pending ads this also should be moderated, 
						// as it can be duplicate or tooo many ads from same user without being moderated.
						$has_pending = Ad::findAllFrom('Ad', "added_by=? AND enabled=?", array($ad->added_by, Ad::STATUS_PENDING_APPROVAL), MAIN_DB, 'id');
						if ($has_pending)
						{
							$enabled = Ad::STATUS_PENDING_APPROVAL;
						}
					}

					// if enabled then check if this post matches previous posts 90%
					$ad_moderation_similarity_min = intval(Config::option('ad_moderation_similarity_min'));
					if ($enabled && $ad_moderation_similarity_min > 0)
					{
						// check if found similar item with given percentage 
						$similar_item = Ad::findDoubleEntry($ad, array('perc' => $ad_moderation_similarity_min));
						if ($similar_item)
						{
							// found similar item then hold for moderation
							$enabled = Ad::STATUS_PENDING_APPROVAL;
						}
					}
				}
				break;
		}


		return $enabled;
	}

	/**
	 * formats description text with new lines
	 * 
	 * @param Ad $ad
	 * @return string 
	 */
	public static function formatDescription($ad, $hide_phone = null)
	{
		if (Map::isAppendToDescription())
		{
			$map = Map::showAddress($ad);
		}
		else
		{
			$map = '';
		}

		$description = $ad->description;

		// hide before converting to html with links
		if ($hide_phone)
		{
			$description = TextTransform::removePhoneNumber($description);
		}

		// convert urls and email to link if defined in settings
		$description = Config::formatText($description);

		// add tracking adid to urls 
		if (Config::option('url_to_link') && strpos($description, 'rel="nofollow"'))
		{
			// add ad_id for tracking clicks 
			$description = str_replace('rel="nofollow"', 'rel="nofollow" data-adid="' . $ad->id . '"', $description);
		}

		return $description . $map;
	}

	/**
	 * Generates image gallery in html format
	 * 
	 * @param Ad $ad
	 * @return string 
	 */
	public static function formatGallery($ad, $med_size = '', $thumb_size = '')
	{
		$return = '';
		$thumbs = '';
		$first = true;
		$title = View::escape(Ad::getTitle($ad));

		$_img_placeholder_src = Adpics::imgPlaceholder();

		// format images
		if ($ad->Adpics)
		{
			foreach ($ad->Adpics as $adpic)
			{
				if ($first)
				{
					$img_med = Adpics::imgMed($adpic, $med_size, $ad->id);
					// Adpics::imgMed($adpic, $med_size)
					if ($img_med)
					{
						$return .= '<div class="med">'
								. '<a href="' . Adpics::img($adpic) . '" rel="group' . $ad->id . '">'
								. '<img src="' . $_img_placeholder_src . '" alt="' . $title . '" '
								. 'data-src="' . $img_med . '" class="lazy" loading="lazy" />'
								. '</a>'
								. '</div>';
						$first = false;
					}
				}
				else
				{
					$img_th = Adpics::imgThumb($adpic, $thumb_size, null, 'lazy', $ad);
					if ($img_th)
					{
						$thumbs .= '<a href="' . Adpics::img($adpic) . '" rel="group' . $ad->id . '">'
								. '<img src="' . $_img_placeholder_src . '" alt="' . $title . '" '
								. 'data-src="' . $img_th . '" class="lazy" loading="lazy" />'
								. '</a>';
					}
				}
			}
		}


		// add videos from custom fields
		if ($ad->CategoryFieldRelation)
		{
			// loop throught custom fields
			foreach ($ad->CategoryFieldRelation as $cf)
			{
				$af_id = $cf->adfield_id;
				$val = $ad->AdFieldRelation[$af_id]->val;
				//$name = AdField::getName($cf->AdField);

				if (strlen($val))
				{
					$str_val = Ad::formatCustomValue($cf->AdField, $val);
					if (strlen($str_val))
					{
						switch ($cf->AdField->type)
						{
							case AdField::TYPE_VIDEO_URL:
								// if video url is valid then skip it. Video will be used in gallery.
								// if not then display as regular text
								$video_url = new VideoUrl($str_val);
								if ($video_url->is_valid)
								{
									if ($first)
									{
										// display video frame
										$return .= '<div class="med">' . $video_url->html . '</div>';
										// add hidden link to video for navigation in colorbox
										$return .= '<a class="iframe ' . $video_url->provider_name . '" style="display:none" href="' . $video_url->iframe . '" rel="group' . $ad->id . '">' . $title . '</a>';
										$first = false;
									}
									else
									{
										// add video url to thumb 
										$thumbs .= '<a class="iframe ' . $video_url->provider_name . '" href="' . $video_url->iframe . '" rel="group' . $ad->id . '"><img src="' . VideoUrl::imgThumb($thumb_size) . '" alt="' . $title . '" /></a>';
									}
								}
								break;
						}
					}
				}
			}
		}

		// wrap return
		if ($return)
		{
			if ($thumbs)
			{
				$return .= '<div class="thumb">' . $thumbs . '</div>';
			}
			$return = '<div class="gallery">' . $return . '</div>';
		}

		return $return;
	}

	/**
	 * Generates image gallery in html format
	 * 
	 * @param Ad $ad
	 * @return string 
	 */
	public static function formatGallerySlider($ad, $med_size = '', $thumb_size = '')
	{
		$return = array();
		$return_str = '';
		$title = View::escape(Ad::getTitle($ad));
		$lazy = true;

		if ($lazy)
		{
			$_img_placeholder_src = Adpics::imgPlaceholder();
		}

		// format images
		if ($ad->Adpics)
		{
			foreach ($ad->Adpics as $adpic)
			{
				$img = '';
				if ($lazy)
				{
					$img_med = Adpics::imgMed($adpic, $med_size, $ad->id);
					if ($img_med)
					{
						$img = '<img src="' . $_img_placeholder_src . '" data-src="' . $img_med . '" alt="' . $title . '" class="lazy" loading="lazy" />';
					}
				}
				else
				{
					$img_med = Adpics::imgMed($adpic, $med_size);
					if ($img_med)
					{
						$img = '<img src="' . Adpics::imgMed($adpic, $med_size) . '" alt="' . $title . '" />';
					}
				}
				if ($img)
				{
					// get all image medium size
					$return[] = '<a href="' . Adpics::img($adpic) . '" rel="group' . $ad->id . '">'
							. $img
							. '</a>';
				}
			}
		}


		// add videos from custom fields
		if ($ad->CategoryFieldRelation)
		{
			// loop throught custom fields
			foreach ($ad->CategoryFieldRelation as $cf)
			{
				$af_id = $cf->adfield_id;
				$val = $ad->AdFieldRelation[$af_id]->val;
				//$name = AdField::getName($cf->AdField);

				if (strlen($val))
				{
					$str_val = Ad::formatCustomValue($cf->AdField, $val);
					if (strlen($str_val))
					{
						switch ($cf->AdField->type)
						{
							case AdField::TYPE_VIDEO_URL:
								// if video url is valid then skip it. Video will be used in gallery.
								// if not then display as regular text
								$video_url = new VideoUrl($str_val);
								if ($video_url->is_valid)
								{
									$return[] = '<div class="gallery_video">' . $video_url->html . '</div>';
								}
								break;
						}
					}
				}
			}
		}

		// wrap return
		if ($return)
		{
			$return_str = '<div class="gallery_slider' . (count($return) === 1 ? ' gallery_slider_single' : ' gallery_slider_multi') . '">'
					. implode('', $return)
					. '</div>';
		}

		return $return_str;
	}

	public static function formatCustomFields($ad, $format = array())
	{
		if ($ad->CategoryFieldRelation)
		{
			$format_default = new stdClass();
			$format_default->html_group_open = '<li class="post_custom_group"><h4>{name}</h4></li>';
			$format_default->html_group_close = '';
			$format_default->html_checkbox = '<li{schema_item_scope}><span class="label">{name}</span><span{schema_item_prop} class="type_{type}">{value}</span></li>';
			$format_default->html_checkbox_wrap = '<ul class="post_custom_fields_big">{html}</ul>';
			$format_default->html_name_value = '<li{schema_item_scope}><span class="label">{name}</span><span{schema_item_prop} class="type_{type} {long_text}">{value}</span>{schema_item_extra}</li>';
			$format_default->html_name_value_wrap = '<ul class="post_custom_fields">{html}</ul>';
			$format_default->skip_by_type = array();
			$format_default->custom_value_options = array();

			// apply supplied formatting
			foreach ($format as $key => $val)
			{
				$format_default->{$key} = $val;
			}


			// define custom value options 
			$custom_value_options = array(
				'make_link' => true
			);

			if ($format_default->custom_value_options)
			{
				/*
				  'checkbox_pattern'		 => '<span class="{class}">{val}</span>',
				  'checkbox_seperator'	 => ' ',
				 */
				foreach ($format_default->custom_value_options as $k => $v)
				{
					$custom_value_options[$k] = $v;
				}
			}



			// return html formatted custom fields
			$ad->html_custom_fields = '';
			$ad->html_custom_fields_checkbox = '';
			$ad->html_custom_fields_all = '';


			// loop throught custom fields
			foreach ($ad->CategoryFieldRelation as $cf)
			{

				if (in_array($cf->AdField->type, $format_default->skip_by_type))
				{
					// skip this type of custom field in general list
					// used in olxer theme to display address in seperate place
					continue;
				}


				$af_id = $cf->adfield_id;
				$val = $ad->AdFieldRelation[$af_id]->val;

				if (strlen($val) || Config::option('custom_fields_display_empty_values'))
				{
					$group_str = CategoryFieldGroup::htmlGroupOpen($cf->CategoryFieldGroup, $format_default->html_group_open, $format_default->html_group_close);

					$ad->html_custom_fields .= $group_str;
					$ad->html_custom_fields_all .= $group_str;

					// format value 
					$str_val = Ad::formatCustomValue($cf->AdField, $val, $custom_value_options);
					if (strlen($str_val))
					{
						$str_val_formatted = '';
						switch ($cf->AdField->type)
						{
							case AdField::TYPE_CHECKBOX:
								// checkboxes display seperately because it may have too many values 							
								$str_val_formatted = self::formatCustomField($str_val, $cf->AdField, $ad, $format_default->html_checkbox);
								$ad->html_custom_fields_checkbox .= $str_val_formatted;
								$ad->html_custom_fields_all .= $str_val_formatted;
								break;
							case AdField::TYPE_VIDEO_URL:
								// if video url is valid then skip it. Video will be used in gallery.
								// if not then display as regular text
								$video_url = new VideoUrl($str_val);
								if (!$video_url->is_valid)
								{
									$str_val_formatted = self::formatCustomField($str_val, $cf->AdField, $ad, $format_default->html_name_value);
									$ad->html_custom_fields .= $str_val_formatted;
									$ad->html_custom_fields_all .= $str_val_formatted;
								}
								break;
							default:
								// display name: value pair of custom fileds
								// add http://schema.org/Offer for item price
								$str_val_formatted = self::formatCustomField($str_val, $cf->AdField, $ad, $format_default->html_name_value, $val);
								$ad->html_custom_fields .= $str_val_formatted;
								$ad->html_custom_fields_all .= $str_val_formatted;
						}
					}
				}
			}

			// close custom filed group
			$group_str .= CategoryFieldGroup::htmlGroupClose();
			$ad->html_custom_fields .= $group_str;
			$ad->html_custom_fields_all .= $group_str;

			// wrap custom fields 
			if ($ad->html_custom_fields && $format_default->html_name_value_wrap)
			{
				$ad->html_custom_fields = str_replace('{html}', $ad->html_custom_fields, $format_default->html_name_value_wrap);
			}

			if ($ad->html_custom_fields_checkbox && $format_default->html_checkbox_wrap)
			{
				$ad->html_custom_fields_checkbox = str_replace('{html}', $ad->html_custom_fields_checkbox, $format_default->html_checkbox_wrap);
			}
		}
	}

	/**
	 * format one value with use of schema
	 * 
	 * @param string $str_val value converted to string using Ad::formatCustomValue
	 * @param AdField $adField
	 * @param Ad $ad
	 * @param string $pattern
	 * @param intiger $val used to show integer value as price without formatting
	 * @return string
	 */
	public static function formatCustomField($str_val, $adField, $ad, $pattern = '<span><span class="label">{name}</span><span class="type_{type} {long_text}">{value}</span></span>', $val = null)
	{
		/* old pattern, keep it to provide ampty values for not used schema values */
		// $pattern = '<span{schema_item_scope}><span class="label">{name}</span><span{schema_item_prop} class="type_{type} {long_text}">{value}</span>{schema_item_extra}</span>'
		$name = AdField::getName($adField);

		$arr_search = array(
			'{schema_item_scope}'	 => '',
			'{schema_item_prop}'	 => '',
			'{schema_item_extra}'	 => '',
			'{name}'				 => View::escape($name),
			'{value}'				 => $str_val,
			'{long_text}'			 => strlen(trim(strip_tags($str_val))) > 30 ? 'long_text' : '',
			'{type}'				 => $adField->type
		);


		return str_replace(array_keys($arr_search), array_values($arr_search), $pattern);
	}

	/**
	 * format one value with use of schema using AdFieldRelation value appended to Ad
	 * 
	 * @param AdField $adField provided by CategoryFieldRelation
	 * @param Ad $ad
	 * @param string $pattern
	 * @return string
	 */
	public static function formatCustomFieldByAFR($adField, $ad, $pattern = '<span{schema_item_scope}><span class="label">{name}</span><span{schema_item_prop} class="type_{type} {long_text}">{value}</span>{schema_item_extra}</span>')
	{
		// get value 
		$val = $ad->AdFieldRelation[$adField->id]->val;

		// format value as string		
		$str_val = Ad::formatCustomValue($adField, $val, array('display_all_checkboxes' => false, 'checkbox_pattern' => '', 'checkbox_seperator' => ', ', 'make_link' => true));

		// format result using given pattern
		return Ad::formatCustomField($str_val, $adField, $ad, $pattern, $val);
	}

	/**
	 * format custom fields as plain text string. 
	 * 
	 * @param Ad $ad
	 * @param array $catfields_exclude CategoryFieldRelation to explude from listing. Used when they are displayed in separate table column
	 * @param string $type all|list
	 * @param string $seperator between values
	 * @return string
	 */
	public static function formatCustomFieldsSimple($ad, $catfields_exclude = array(), $type = 'list', $seperator = ' | ')
	{
		return self::formatCustomFieldsSimpleOptions($ad, array(
					'catfields_exclude'	 => is_array($catfields_exclude) ? $catfields_exclude : array(),
					'type'				 => $type,
					'seperator'			 => $seperator,
					'make_link'			 => true,
					'return_format'		 => 'string'
		));
	}

	/**
	 * Format custom fields as plain text. 
	 * 
	 * @param Ad $ad
	 * @param array $options 
	 * @return string|array
	 */
	public static function formatCustomFieldsSimpleOptions($ad, $options = array())
	{
		$default_options = array(
			'catfields_exclude'		 => array(),
			'catfields_exclude_type' => array(AdField::TYPE_VIDEO_URL => true),
			'type'					 => 'list',
			'seperator'				 => ' | ',
			'make_link'				 => true,
			'return_format'			 => 'string'
		);

		$options = array_merge($default_options, $options);

		// return value 
		$arr_simple = array();

		// add catfields if not already added
		Ad::appendCategoryFieldRelation($ad);

		// alter $_GET to use related location ac category links
		// FIXME find more elegant way without using global variables for generating links 
		if ($options['make_link'])
		{
			// store default for setting them back later 
			$_default_location_id = $_GET['location_id'];
			$_default_category_id = $_GET['category_id'];
			$_GET['location_id'] = $ad->location_id;
			$_GET['category_id'] = $ad->category_id;
		}

		foreach ($ad->CategoryFieldRelation as $cf)
		{
			// if not exluded by adfield_id
			$show_it = !isset($options['catfields_exclude'][$cf->adfield_id]);
			// if not excluded by type
			$show_it = $show_it && !isset($options['catfields_exclude_type'][$cf->AdField->type]);
			// if listed or show all value used 
			$show_it = $show_it && (($options['type'] === 'list' && $cf->is_list) || $options['type'] === 'all');
			if ($show_it)
			{
				// process this custom field
				$val = $ad->AdFieldRelation[$cf->adfield_id]->val;

				// if we have some value to format 
				if (strlen($val))
				{
					//$str_val = Ad::formatCustomValue($cf->AdField, $val, false, '', ', ');
					$str_val = Ad::formatCustomValue($cf->AdField, $val, array(
								'display_all_checkboxes' => false,
								'checkbox_pattern'		 => '',
								'checkbox_seperator'	 => ', ',
								'make_link'				 => $options['make_link']
					));

					// if value is not empty
					if (strlen($str_val))
					{
						$str_val = Ad::makeCustomValueMeaningfull($str_val, AdField::getName($cf->AdField));
						$arr_simple[] = $str_val;
					}
				}
			}
		}

		// revert altered $_GET variables 
		if ($options['make_link'])
		{

			if (is_null($_default_location_id))
			{
				unset($_GET['location_id']);
			}
			else
			{
				$_GET['location_id'] = $_default_location_id;
			}
			if (is_null($_default_category_id))
			{
				unset($_GET['category_id']);
			}
			else
			{
				$_GET['category_id'] = $_default_category_id;
			}
		}



		if ($options['return_format'] === 'string')
		{
			// convert to string 
			if ($arr_simple)
			{
				// return seperated string
				return implode($options['seperator'], $arr_simple);
			}
			else
			{
				return '';
			}
		}

		// return array
		return $arr_simple;
	}

	/**
	 * Add name if value alone is meaningless.
	 * by default if value smaller than 4 characters or numeric then name added
	 * 
	 * @param string $str_val
	 * @param string $name
	 * @param string $type all|numeric|small
	 * @return string
	 */
	public static function makeCustomValueMeaningfull($str_val, $name, $type = 'all')
	{
		// if value is only number then add field name for better understanding
		if (self::makeCustomValueMeaningfullCheck($str_val, $type))
		{
			$str_val = $name . ': ' . $str_val;
		}

		return $str_val;
	}

	/**
	 * Check if value is meaningless, type=all: smaller than 4 characters or is numeric. 
	 * 
	 * @param string $str_val
	 * @param string $type all|numeric|small
	 * @return bool
	 */
	public static function makeCustomValueMeaningfullCheck($str_val, $type = 'all')
	{
		// if value is only number then add field name for better understanding
		$str_val_text = strip_tags($str_val);
		$is_num = is_numeric($str_val_text);
		$is_small = strlen($str_val_text) < 4;
		$return = false;
		switch ($type)
		{
			case 'numeric':
				$return = $is_num;
				break;
			case 'small':
				$return = $is_small;
				break;
			default:
				$return = $is_num || $is_small;
		}

		return $return;
	}

	/**
	 * append sum of all payments for given ads 
	 * 
	 * @param array $ads Ad
	 */
	public static function appendPaymentAmounts($ads)
	{
		/* explain SELECT SUM(amount) as amount,currency 
		  FROM cb_payment
		  WHERE ad_id IN (56,21,50,51,49) AND item_type IN('1','2')
		  GROUP BY currency; */

		$ads = Record::checkMakeArray($ads);

		$arr_ids = array();
		foreach ($ads as $ad)
		{
			$arr_ids[$ad->id] = $ad;
			$ad->Payment = array();
		}

		if ($arr_ids)
		{
			// display total paid amount 
			$arr_ids_ = Ad::ids2quote(array_keys($arr_ids));
			$amount_sql = "SELECT ad_id, currency, SUM(amount) as amount 
				FROM " . Payment::tableNameFromClassName('Payment') . " 
				WHERE ad_id IN (" . implode(',', $arr_ids_) . ") 
				AND item_type IN(?,?) 
				GROUP BY ad_id,currency";
			$where = array(Payment::ITEM_TYPE_POST, Payment::ITEM_TYPE_FEATURED);

			$records = Payment::query($amount_sql, $where);


			foreach ($records as $r)
			{
				$arr_ids[$r->ad_id]->Payment[] = $r;
			}
		}
	}

	/**
	 * return true if payment option for this ad exists. 
	 * 
	 * @param Ad $ad 
	 */
	public static function isPaymentAvailable($ad)
	{
		$required = false;

		// appaned payment price 
		if (!isset($ad->PaymentPrice))
		{
			PaymentPrice::appendPaymentPrice($ad);
		}

		// check if has payment price, then payment is enabled 
		if ($ad->requires_posting_payment)
		{
			if ($ad->PaymentPrice->price_post > 0)
			{
				// have to pay for posting 
				$required = true;
			}
		}

		// check if has avalable promoted price 
		if (!$ad->featured && $ad->PaymentPrice->price_featured > 0)
		{
			$required = true;
		}

		return $required;
	}

	/**
	 * check if category and location can be changed 
	 * 
	 * @param Ad $ad
	 * @return bool 
	 */
	public static function canChangeLocationCategory($ad)
	{
		// load payment if not loaded
		if (!isset($ad->Payment))
		{
			Ad::appendPaymentAmounts($ad);
		}

		$enable_payment = Config::option('enable_payment');

		// check if can change location and category 
		// + if not paid then can change
		// + if payment is not enabled at all then can change location and category 
		// - if same price and regardless paid or not then can change , 
		// - but have to load only available location and categories

		if ($enable_payment)
		{
			if ($ad->Payment)
			{
				// payment done then nobody can change location and category
				return false;
			}
			else
			{
				// payment not done then only moderator can change location and cateogry 
				return AuthUser::hasPermission(User::PERMISSION_MODERATOR);
			}
		}
		else
		{
			// payment not enabled then enybody can change location and category 
			return true;
		}
	}

	public static function labelFeatured($ad)
	{
		$featured = '';
		if ($ad->featured)
		{
			$featured = ' <span class="label_text green small">' . __('Featured') . '</span>';
		}
		return $featured;
	}

	public static function labelExpired($ad)
	{
		$expired = '';
		if (Ad::isExpired($ad))
		{
			$expired = ' <span class="label_text black small">' . Config::abbreviate(__('Expired')) . '</span>';
		}
		return $expired;
	}

	public static function labelEnabled($ad)
	{
		$str_enabled = '';
		// default label color for status 
		$color = 'red';

		$arr_color = array(
			Ad::STATUS_COMPLETED		 => 'black',
			Ad::STATUS_PAUSED			 => 'orange',
			Ad::STATUS_PENDING_APPROVAL	 => 'blue'
		);

		if ($ad->enabled != Ad::STATUS_ENABLED)
		{
			// not enabled then show label.
			// no need to show label for enabled ads in admin 
			if (isset($arr_color[$ad->enabled]))
			{
				$color = $arr_color[$ad->enabled];
			}
			$str_enabled = '<span class="label_text small ' . $color . '" '
					. 'title="' . View::escape(self::statusEnabledText($ad, 'long')) . '">'
					. self::statusEnabledText($ad) . '</span>';
		}

		return $str_enabled;
	}

	public static function labelVerified($ad)
	{
		$str_verified = '';
		if (!Ad::isVerified($ad))
		{
			$str_verified = '<span class="label_text red small" title="' . __('Email is not verified by user') . '">' . __('Pending verification') . '</span>';
		}
		return $str_verified;
	}

	public static function labelPayment($ad, $button = false)
	{
		if ($ad->requires_posting_payment)
		{

			if ($button && $ad->payment_available)
			{
				$str_payment = '<a href="' . Ad::urlPromote($ad) . '" class="label_text blue small">' . __('Pay now') . '</a>';
			}
			else
			{
				$str_payment = '<span class="label_text red small" title="' . __('Requires payment') . '">' . __('Requires payment') . '</span>';
			}
		}
		else
		{
			// display total paid amount 
			$arr_amount = array();
			$str_payment = '';
			if (isset($ad->Payment))
			{
				foreach ($ad->Payment as $pym)
				{
					$arr_amount[] = Payment::formatAmount($pym->amount, $pym->currency);
				}

				if ($arr_amount)
				{
					$str_amount = implode(' ', $arr_amount);
					$str_payment = '<a href="' . Language::get_url('admin/paymentHistory/' . $ad->id . '/') . '" 
						class="label_text green small" 
						title="' . __('Paid') . ' ' . $str_amount . '">'
							. $str_amount . '</a>';
				}
			}

			if ($button && $ad->payment_available)
			{
				$str_payment .= ' <a href="' . Ad::urlPromote($ad) . '" class="label_text blue small">' . __('Promote') . '</a>';
			}
		}

		return $str_payment;
	}

	public static function labelAbused($ad)
	{
		$abused = '';
		if ($ad->abused > 0)
		{
			$abuse_minimum = AdAbuse::getMinimum();
			$abused_title = __('{num} abuse reports', array('{num}' => $ad->abused));
			$abused_class = ($ad->abused >= $abuse_minimum ? 'red' : 'white');
			if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
			{
				$abused = ' <a href="' . Ad::urlAbuseReports($ad) . '" class="label_text ' . $abused_class . ' small">' . $abused_title . '</a>';
			}
			else
			{
				$abused = ' <span class="label_text ' . $abused_class . ' small">' . $abused_title . '</span>';
			}
		}

		return $abused;
	}

	/**
	 * Explain current status of item defined in $ad->enabled value
	 * 
	 * @param Ad $ad
	 * @param string $style short|long
	 * @return string
	 */
	public static function statusEnabledText($ad, $style = 'short')
	{
		$arr_status = array(
			'long' => array(
				Ad::STATUS_PENDING_APPROVAL	 => __('Pending approval by admin'),
				Ad::STATUS_PAUSED			 => __('Paused by user')
			)
		);

		// get short name 
		$str_enabled = Ad::statusName($ad->enabled);

		// check for long name 
		if (isset($arr_status[$style][$ad->enabled]))
		{
			$str_enabled = $arr_status[$style][$ad->enabled];
		}

		return $str_enabled;
	}

	/**
	 * generate string explnation why ad is not listed 
	 * 
	 * @param Ad $ad
	 * @return array
	 */
	public static function unlistedReason($ad)
	{

		$return = '';
		if ($ad->listed == 0)
		{
			$reason = array();
			if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
			{
				$link_edit_list = ' <a href="' . Ad::urlEditList($ad) . '">' . __('Edit') . '</a>';
				$link_view_abuse = ' <a href="' . Ad::urlAbuseReports($ad) . '">' . __('View abuse reports') . '</a>';
			}
			else
			{
				$link_edit_list = '';
				$link_view_abuse = '';
			}

			$link_payment = ' <a href="' . Ad::urlPromote($ad) . '">' . __('Complete payment') . '</a>';

			if ($ad->enabled != Ad::STATUS_ENABLED)
			{
				$reason[] = self::statusEnabledText($ad, 'long');
			}

			if (self::isExpired($ad))
			{
				$reason[] = __('Ad is expired.');
			}

			if (self::isAbused($ad))
			{
				$reason[] = __('Ad has too many abuse reports.') . $link_view_abuse;
			}

			if ($ad->requires_posting_payment)
			{
				$reason[] = __('Ad is pending for payment completion.') . $link_payment;
			}

			//verified IN('0','1')
			if (!Ad::isVerified($ad))
			{
				$reason[] = __('Ad is pending for email verification. ');
			}

			if ($reason)
			{
				$return = __('This ad is not listed because of following reasons.')
						. $link_edit_list . ':<br/>'
						. implode('<br/>', $reason);
			}
		}

		return $return;
	}

	/**
	 * use 2 queries, first to get ids, second to get objects. this is 10 times faster on mysql-5. useful with 'limit 1800,20' type queries
	 * 
	 * @param string $sql
	 * @param array $values
	 * @return array
	 */
	public static function queryUsingIds($sql, $values = array())
	{
		// get ids first then append object. it is faster 
		// search for ad.* replace with ad.id
		$_sql = str_replace('ad.*', 'ad.id', $sql);
		$ad_ids = Ad::query($_sql, $values);

		return self::getByIds($ad_ids);
	}

	/**
	 * get ads in given id order
	 * 
	 * @param array $ids 
	 * @return array Ad
	 */
	public static function getByIds($ids)
	{
		$return = array();
		$ad_ids = array();
		if (is_array($ids) && $ids)
		{
			if (!is_object($ids[0]))
			{
				// convert integer array to object array
				foreach ($ids as $id)
				{
					$ad_ids[] = new Ad(array('id' => $id));
				}
			}
			else
			{
				$ad_ids = $ids;
			}

			// now add object to ids 
			Ad::appendObject($ad_ids, 'id', 'Ad', 'id');

			// build Ads array from appended object
			foreach ($ad_ids as $r)
			{
				if ($r->Ad)
				{
					$return[] = $r->Ad;
				}
			}
		}

		return $return;
	}

	/**
	 * Fix if default contact option setting is not in available contact options setting
	 */
	public static function fixContactOptionsSetting()
	{
		// if default contact option is not selected as aailable then make default available as well
		$default_contact_option = Config::option('default_contact_option');
		$available_contact_option = Config::option($default_contact_option);
		if (!$available_contact_option)
		{
			Config::optionSet($default_contact_option, 1);
		}
	}

	/**
	 * return int value of default contact option sotred on config
	 * @return int
	 */
	public static function defaultContactOption()
	{
		$default_contact_option = Config::option('default_contact_option');
		return intval(str_replace('showemail_', '', $default_contact_option));
	}

	/**
	 * get acailable contact options defined in settings and related label and messages 
	 * 
	 * @return array
	 */
	public static function getContactOptions()
	{
		// fix contact options
		Ad::fixContactOptionsSetting();
		$contact_options = array();

		// display permitted options
		if (Config::option('showemail_0'))
		{
			$contact_options['showemail_0'] = array(
				'value'				 => 0,
				'label'				 => __('Do not show my email address, contact by phone only.'),
				'message'			 => __('Make sure that you posted phone number.'),
				'message_selected'	 => __('Email address will not be shown, contact by phone will be available.')
			);
		}

		if (Config::option('showemail_2'))
		{
			$contact_options['showemail_2'] = array(
				'value'				 => 2,
				'label'				 => __('Do not show my email address but allow to send me email using contact form.'),
				'message_selected'	 => __('Email address will not be shown, contact form will be used.')
			);
		}

		if (Config::option('showemail_1'))
		{
			$contact_options['showemail_1'] = array(
				'value'				 => 1,
				'label'				 => __('Show my email address to everyone.'),
				'message_selected'	 => __('Email address will be displayed for contacting.')
			);
		}

		return $contact_options;
	}

	/**
	 * filter given array by leaving ads with image only 
	 * 
	 * @param array $ads Ad
	 * @return array of Ad
	 */
	public static function filterWithImageOnly($ads)
	{
		// append adpics
		Ad::appendAdpics($ads);
		$return = array();

		foreach ($ads as $ad)
		{
			// leave only ads with image
			if ($ad->Adpics)
			{
				$return[] = $ad;
			}
		}
		return $return;
	}

	/**
	 * Check if given ad ids can be extended. return array of extendible ids 
	 * - Paid ads cannot be extended 
	 * - If set in options 'disable_extending_ads' ads cannot be extended
	 * 
	 * @param type $ids
	 * @return array
	 */
	public static function filterExtendibleIds($ids)
	{
		$disable_extending_ads = intval(Config::option('disable_extending_ads'));
		$extend_ids = $ids;
		switch ($disable_extending_ads)
		{
			case 0:
				// can extend any ad, do nothing, continue with $extend_ids						
				break;
			case 1:
				// can extend only free ads
				// display info that users can extend only free ads
				//$this->validation()->set_info(__('Users can extend only free ads'));
				// load all ads to check if they are not currently in paid ads group
				$ids_ = Ad::ids2quote($extend_ids);
				$where = "id IN (" . implode(',', $ids_) . ")";
				$ads = Ad::findAllFrom('Ad', $where);

				// reset ids
				$extend_ids = array();

				// pass only free ads 
				PaymentPrice::appendPaymentPriceAll($ads);
				foreach ($ads as $ad)
				{
					if ($ad->PaymentPrice->price_post <= 0)
					{
						// extend only free ads 
						$extend_ids[] = $ad->id;
					}
				}

				break;
			case 2:
				// cannot extend any ads
				$extend_ids = array();
				break;
		}

		return $extend_ids;
	}

	/**
	 * check if ad can be extended 
	 * 
	 * @param Ad $ad
	 * @return boolean
	 */
	static public function isExtendable($ad)
	{
		$disable_extending_ads = intval(Config::option('disable_extending_ads'));
		$return = false;
		switch ($disable_extending_ads)
		{
			case 0:
				// can extend any ad, do nothing, continue with $extend_ids		
				$return = true;
				break;
			case 1:
				// can extend only free ads
				PaymentPrice::appendPaymentPrice($ad);

				if ($ad->PaymentPrice->price_post <= 0)
				{
					$return = true;
				}
				break;
			case 2:
				// cannot extend any ads
				$return = false;
				break;
		}

		return $return;
	}

	/**
	 * Search category, location and users for given term. Use to display in search results page. Cache search results.
	 * 
	 * @param string $q
	 * @param int $limit
	 * @return object
	 */
	public static function searchRelated($q, $limit = 5, $location = null)
	{
		// clean query, use only searchable terms, remove spaces
		$q = implode(' ', Ad::searchQuery2Array($q));

		if (strlen($q))
		{
			$limit = intval($limit);
			$cache_key = 'search.related.' . $limit . '.' . SimpleCache::uniqueKey($q);
			$return = SimpleCache::get($cache_key);
			if ($return === false)
			{
				$return = new stdClass();
				$return->Category = Category::search($q, $limit);
				$return->Location = Location::search($q, $limit);
				$return->User = User::search($q, $limit);

				SimpleCache::set($cache_key, $return);
			}

			// format results for basic use. Do not cache this string. populate it each time
			$str = '';
			if ($return->Category)
			{
				$str .= __('Related categories') . ': ';
				$arr_str = array();
				foreach ($return->Category as $c)
				{
					$arr_str[] = '<a href="' . Category::url($c, $location) . '">' . Category::getFullName($c) . '</a>';
				}

				$str .= implode(', ', $arr_str) . '. ';
			}

			if ($return->Location)
			{
				$str .= __('Related locations') . ': ';
				$arr_str = array();
				foreach ($return->Location as $l)
				{
					$arr_str[] = '<a href="' . Location::url($l) . '">' . Location::getFullName($l) . '</a>';
				}

				$str .= implode(', ', $arr_str) . '. ';
			}

			if ($return->User)
			{
				$str .= __('Related users') . ': ';
				$arr_str = array();
				foreach ($return->User as $u)
				{
					$arr_str[] = '<a href="' . User::url($u) . '">' . User::getNameFromUserOrEmail($u) . '</a>';
				}

				$str .= implode(', ', $arr_str) . '. ';
			}

			$return->str = $str;
		}
		else
		{
			$return = false;
		}

		return $return;
	}

	/**
	 * convert search terms to array with usable terms, remove empty spaces and repeated terms
	 * @param string $q
	 * @return array
	 */
	public static function searchQuery2Array($q)
	{
		$return = array();
		$q = trim($q);
		if (strlen($q))
		{
			$arr_q = explode(' ', StringUtf8::strtolower($q));
			$arr_q = array_unique($arr_q);
			foreach ($arr_q as $_q)
			{
				if (strlen($_q))
				{
					$return[] = $_q;
				}
			}
		}
		Benchmark::cp('searchQuery2Array(' . $q . '):' . implode(',', $return));
		return $return;
	}

	/**
	 * get time older which ads can be renewed. 
	 * it is used to prevent site from being hijacked by frequently renewed ads being shown on home page 
	 * 
	 * @return int
	 */
	static public function getMinRenewDate()
	{
		return REQUEST_TIME - (Config::option('renew_ad_days') * 3600 * 24);
	}

	/**
	 * check if ads should be moderated. then mark for moderation and save
	 * called if words (not numbers) in text changed or new image added (not deleted old images). 
	 * 
	 * @param Ad $ad
	 */
	static public function autoApproveChanged($ad)
	{
		$enabled = Ad::autoApprove($ad);
		if ($enabled == Ad::STATUS_PENDING_APPROVAL && $ad->enabled == Ad::STATUS_ENABLED)
		{
			$ad->enabled = Ad::STATUS_PENDING_APPROVAL;
			$ad->listed = 0;

			// update db 
			// ad should be sent to moderation 
			Ad::update('Ad', array(
				'enabled'	 => $ad->enabled,
				'listed'	 => $ad->listed
					), 'id=?', array($ad->id));
		}
	}

	/**
	 * check if current user has permission to view ad
	 * 
	 * @param Ad $ad
	 * @param string $action view|edit
	 */
	static public function ownerCan($ad, $action = 'view')
	{
		//permit everything to owner firist
		$return = true;

		// define restrictions
		$arr_can = array(
			'view'	 => array(
				Ad::STATUS_BANNED	 => false,
				Ad::STATUS_DUPLICATE => false,
				Ad::STATUS_TRASH	 => false
			),
			'edit'	 => array(
				Ad::STATUS_BANNED	 => false,
				Ad::STATUS_DUPLICATE => false,
				Ad::STATUS_TRASH	 => false
			),
			'know'	 => array(
				Ad::STATUS_TRASH => false
			),
		);

		if (isset($arr_can[$action][$ad->enabled]))
		{
			$return = $arr_can[$action][$ad->enabled];
		}

		return $return;
	}

	static public function statusName($status)
	{
		$arr_return = array(
			Ad::STATUS_PENDING_APPROVAL	 => __('Pending approval'),
			Ad::STATUS_ENABLED			 => __('Approved'),
			Ad::STATUS_PAUSED			 => __('Paused'),
			Ad::STATUS_COMPLETED		 => __('Completed'),
			Ad::STATUS_INCOMPLETE		 => __('Incomplete'),
			Ad::STATUS_DUPLICATE		 => __('Duplicate'),
			Ad::STATUS_BANNED			 => __('Banned'),
			Ad::STATUS_TRASH			 => __('Trash'),
		);

		return $arr_return[$status];
	}

}
