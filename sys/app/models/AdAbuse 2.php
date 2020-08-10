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
 * class AdField
 * These are fields that can be attached to all ads by default or by category and location
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdAbuse extends Record
{

	const TABLE_NAME = 'ad_abuse';
	const SOURCE_HUMAN = 0;
	const SOURCE_BAD_WORD_FILTER = 1;
	const SOURCE_BAD_WORD_BLOCK = 2;

	private static $_errors = array();
	private static $cols = array(
		'id'		 => 1,
		'ad_id'		 => 1,
		'ip'		 => 1, // ip or source
		'reason'	 => 1,
		'num'		 => 1,
		'added_at'	 => 1,
		'added_by'	 => 1,
	);

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

	/**
	 * add report if it is unique ip per ad and update abuse count in Ad, Update ad listed value.
	 * @param int $ad_id
	 * @param string $reason
	 * @return boolean 
	 */
	public static function addReport($ad_id, $reason = '')
	{
		$ip = Input::getInstance()->ip_address();
		$ad_id = intval($ad_id);

		if ($ad_id)
		{
			// check if ad exists 
			$ad = Ad::findByIdFrom('Ad', $ad_id);
		}

		if (!$ad)
		{
			self::setError(__('Record not found'));
			return false;
		}


		// fo rsecurity reason allow certain number of reports from same ip in given time frame 
		$min_time = 3600; // 1 hour
		$max_reports = 10; // maximum number of reports can be recorder by ip in min_time
		$count = AdAbuse::countFrom('AdAbuse', "ip=? AND added_at<?", array($ip, REQUEST_TIME - $min_time));

		if ($count > $max_reports)
		{
			// reached maximum number of reports for given time. too much reporting is suspiciuos.
			self::setError(__('Reached maximum number of abuse reports per user.'));
			return false;
		}


		// check if it is unique 
		$ad_abuse = AdAbuse::findAllFrom('AdAbuse', 'ad_id=? AND ip=?', array($ad_id, $ip));
		if (!$ad_abuse)
		{
			// add report
			$ad_abuse = new AdAbuse();
			$ad_abuse->ip = $ip;
			$ad_abuse->ad_id = $ad_id;
			$ad_abuse->reason = $reason;
			if ($ad_abuse->save())
			{
				// count or increase count by one 
				self::updateCount($ad_id);

				return true;
			}
		}

		self::setError(__('Ad abuse is already reported by you.'));
		return false;
	}

	/**
	 * add report if it is unique ip per ad and update abuse count in Ad, Update ad listed value.
	 * @param int $ad_id
	 * @param string $reason
	 * @return boolean 
	 */
	public static function addReportAuto($ad_id, $reason, $source)
	{
		$ad_id = intval($ad_id);

		if ($ad_id)
		{
			// check if ad exists 
			$ad = Ad::findByIdFrom('Ad', $ad_id);
		}

		if (!$ad)
		{
			return false;
		}

		// check if it is unique 
		$ad_abuse = AdAbuse::findAllFrom('AdAbuse', 'ad_id=? AND ip=?', array($ad_id, $source));
		if (!$ad_abuse)
		{
			// add report
			$ad_abuse = new AdAbuse();
			$ad_abuse->ad_id = $ad_id;
			$ad_abuse->ip = $source;
			$ad_abuse->reason = $reason;

			if ($source == self::SOURCE_BAD_WORD_BLOCK)
			{
				$ad_abuse->num = Config::option('abuse_minimum');
			}

			if ($ad_abuse->save())
			{
				// count or increase count by one 
				self::updateCount($ad_id);

				return true;
			}
		}

		return false;
	}

	public static function updateCount($ad_id)
	{
		$sql = "SELECT SUM(num) as num 
			FROM " . AdAbuse::tableNameFromClassName('AdAbuse') . "
				WHERE ad_id=?";
		$count = AdAbuse::query($sql, array($ad_id));
		$count = intval($count[0]->num);

		$return = Ad::update('Ad', array('abused' => $count), 'id=?', array($ad_id));

		Ad::updateListed(true, $ad_id);

		return $return;
	}

	/**
	 * get abuse_minimum from settings. used to display count in red and unlist ads.
	 * 
	 * @return int 
	 */
	public static function getMinimum()
	{
		return intval(Config::option('abuse_minimum'));
	}

	public static function getErrors()
	{
		return implode(' ', self::$_errors);
	}

	public static function setError($message)
	{
		self::$_errors[] = $message;
	}

	/**
	 * check for spam word and add to report if it about it
	 * 
	 * @param type $ad
	 * @return type
	 */
	public static function checkSpam($ad)
	{
		// check if has bad_word_block
		$bad_word_block_count = Config::hasSpamWords($ad->title . ' ' . $ad->description . ' ' . $ad->email, false, 'bad_word_block');
		if ($bad_word_block_count)
		{
			// update listed and enabled values 
			$ad->enabled = Ad::STATUS_PENDING_APPROVAL;
			$ad->listed = 0;

			if ($ad->id)
			{
				$arr_update = array(
					'enabled'	 => Ad::STATUS_PENDING_APPROVAL,
					'listed'	 => 0
				);

				Ad::update('Ad', $arr_update, 'id=?', array($ad->id));

				AdAbuse::addReportAuto($ad->id, 'bad_word_block_count: ' . $bad_word_block_count, self::SOURCE_BAD_WORD_BLOCK);
			}
		}


		// check if has bad_word_filter
		$bad_word_filter_count = Config::hasSpamWords($ad->title . ' ' . $ad->description . ' ' . $ad->email, false, 'bad_word_filter');
		if ($bad_word_filter_count)
		{
			$data = array();
			if (isset($ad->title))
			{
				$ad->title = Config::hasSpamWords($ad->title, true, 'bad_word_filter');
				$data['title'] = $ad->title;
			}
			if (isset($ad->description))
			{
				$ad->description = Config::hasSpamWords($ad->description, true, 'bad_word_filter');
				$data['description'] = $ad->description;
			}
			if ($ad->id)
			{
				if ($data)
				{
					Ad::update('Ad', $data, 'id=?', array($ad->id));
				}
				AdAbuse::addReportAuto($ad->id, 'bad_word_filter_count: ' . $bad_word_filter_count, self::SOURCE_BAD_WORD_FILTER);
			}
		}

		return $bad_word_block_count || $bad_word_filter_count;
	}

}
