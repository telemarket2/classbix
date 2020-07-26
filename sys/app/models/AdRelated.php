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
 * class AdRelated
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdRelated extends Record
{

	const TABLE_NAME = 'ad_related';
	const RELATED_LIFE = 2592000; // 7 days 604800, 30 days 2592000
	const TIME_NEXT_START = 30; // time to wait after last generation started
	const TIME_WAIT = 5; // time to wait after last generation finished 
	const MAX_QUEUE = 30; // number of maximum items in queue 

	private static $cols = array(
		'ad_id'		 => 1,
		'related'	 => 1,
		'related_at' => 1
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	/**
	 * Append related ads to given $ad->related
	 * 
	 * @param Ad $ad
	 * @param boolean $readonly do not generate or add to queue just read what is already stored in DB
	 * @return array of Ad
	 */
	public static function append($ad, $readonly = false)
	{
		if (!isset($ad->related))
		{
			// store defaut empty response 
			$ad->related = array();

			if (!self::isDatabaseReady())
			{
				return $ad->related;
			}


			AdRelated::appendObject($ad, 'id', 'AdRelated', 'ad_id');


			// if no results then generate related and add to DB
			if (!$ad->AdRelated)
			{
				if (!$readonly)
				{
					// generate if possibe, new item
					$adRelated = self::generateRelatedLazy($ad);
				}

				if ($adRelated)
				{
					$ad->AdRelated = $adRelated;
				}
				else
				{
					// not generated return default empty 
					return $ad->related;
				}
			}
			elseif ($ad->AdRelated && ($ad->AdRelated->related_at < (REQUEST_TIME - self::RELATED_LIFE)))
			{
				if ($ad->AdRelated->related_at == 0)
				{
					if (!$readonly)
					{
						// forced expire, generate if possible, because user migth want to see changes	
						$adRelated = self::generateRelatedLazy($ad);
					}
					if ($adRelated)
					{
						$ad->AdRelated = $adRelated;
					}
				}
				else
				{
					if (!$readonly)
					{
						// natural expire add to queue. 
						// this request might be initiated by crawlers. so do not bother and add to queue
						self::_addToQueue($ad);
					}
				}
			}

			// append ads using ids
			if ($ad->AdRelated && strlen($ad->AdRelated->related))
			{
				$ids = explode(',', $ad->AdRelated->related);
				if (count($ids))
				{
					$ids_ = Ad::ids2quote($ids);
					$str_ids = implode(',', $ids_);
					$ad->related = Ad::findAllFrom('Ad', "id IN (" . $str_ids . ") AND listed=? ORDER BY FIELD(id," . $str_ids . ")", array(1));
				}
			}
		}

		return $ad->related;
	}

	/**
	 * Find related ads and store their ids in database. reutrn stored value object
	 * This will update existing related values not checking if they are fresh or stale
	 * 
	 * @param Ad $ad
	 * @return AdRelated
	 */
	private static function _generateRelated($ad, $opt = null)
	{
		//store maximum 30 records. 
		$num = 30;
		$rel_extra = array();
		$ids = array();
		$current = $opt['current'];
		if (!$current)
		{
			$current = 'cron';
		}

		// if related stale then store temporary record to disabel double generation because it is slow query 3sec.
		self::_insertUpdate($ad->id);

		// generation started, set time to prevent double process
		if (is_null($opt))
		{
			$opt = self::_getOpt();
		}
		$time = time();
		$opt['time']['start'] = $time;
		// set end time to 0 for preventing other processes
		$opt['time']['end'] = 0;
		// remove ad from queue and update db with new time
		self::_removeFromQueue($ad->id, $opt, true);


		// return generated record 
		/* BAD TIME 3.6s
		  # optimal version is
		  #explain
		  SELECT COUNT(ad.id) as num,ad.id
		  FROM
		  cb_ad ad,
		  cb_ad_field_relation af,
		  cb_ad_field_relation af2
		  WHERE af2.ad_id=16367
		  AND ad.location_id=81
		  AND ad.category_id=89
		  AND ad.listed=1
		  AND ad.id!=16367
		  AND ad.id=af.ad_id
		  AND af.field_id=af2.field_id
		  AND af.val=af2.val
		  GROUP BY ad.id
		  ORDER BY num DESC, ad.published_at DESC
		  LIMIT 30;
		 * 
		 * 
		 * "SELECT COUNT(ad.id) as num,ad.id
		  FROM
		  " . Ad::tableNameFromClassName('Ad') . " ad,
		  " . AdFieldRelation::tableNameFromClassName('AdFieldRelation') . " af,
		  " . AdFieldRelation::tableNameFromClassName('AdFieldRelation') . " af2
		  WHERE af2.ad_id=?
		  AND ad.location_id=?
		  AND ad.category_id=?
		  AND ad.listed=1
		  AND ad.id!=?
		  AND ad.id=af.ad_id
		  AND af.field_id=af2.field_id
		  AND af.val=af2.val
		  GROUP BY ad.id
		  ORDER BY num DESC, " . $sql_order_with_image . " ad_num.id DESC
		  LIMIT " . $num;
		 * 
		 */


		/* BETTER TIME 2.5s
		  SELECT * FROM (
		  SELECT COUNT(ad1.id) as num,ad1.id
		  FROM cb_ad ad1, cb_ad_field_relation af
		  WHERE ad1.location_id='80' AND ad1.category_id='34' AND ad1.listed='1' AND ad1.id=af.ad_id
		  AND (af.field_id, af.val) IN (('1','27000'),
		  ('3','2'),
		  ('11','35'),
		  ('34','12'),
		  ('35','106'),
		  ('36','110'),
		  ('37','113'),
		  ('38','116'))
		  GROUP BY ad1.id
		  ORDER BY num DESC,ad1.id DESC
		  LIMIT 30
		  ) ad
		  ORDER BY ad.num DESC,(EXISTS (select ap.ad_id FROM cb_adpics ap WHERE ap.ad_id=ad.id)) DESC, ad.id DESC;
		 */

		$sql_order_with_image = "(EXISTS (select ap.ad_id FROM " . Adpics::tableNameFromClassName('Adpics') . " ap WHERE ap.ad_id=ad.id)) DESC";

		// get field val first as separate query 
		$afv = array();
		$rel = array();
		$af = AdFieldRelation::findAllFrom('AdFieldRelation', 'ad_id=?', array($ad->id));
		foreach ($af as $_af)
		{
			$afv[$_af->field_id . '.' . $_af->val] = "('" . $_af->field_id . "','" . $_af->val . "')";
		}

		if ($afv)
		{
			// calculate related using custom fields. This is slow 3 sec for 30k items.
			$where_af = ' AND (af.field_id, af.val) IN (' . implode(', ', $afv) . ') ';

			$sql = "			
			SELECT * FROM (
				SELECT COUNT(ad1.id) as num,ad1.id
				FROM " . Ad::tableNameFromClassName('Ad') . " ad1, " . AdFieldRelation::tableNameFromClassName('AdFieldRelation') . " af
				WHERE ad1.location_id=? AND ad1.category_id=? AND ad1.listed=? AND ad1.id=af.ad_id " . $where_af . "
				GROUP BY ad1.id
				ORDER BY num DESC,ad1.id DESC
				LIMIT " . $num . "
			) ad
			ORDER BY ad.num DESC, " . $sql_order_with_image . ", ad.id DESC";

			$rel = AdFieldRelation::query($sql, array($ad->location_id, $ad->category_id, 1));
		}



		// check if returned value less than needed then add ads from same location and category
		if (count($rel) < $num)
		{
			/* $sql = "SELECT ad.id
			  FROM " . Ad::tableNameFromClassName('Ad') . " ad
			  WHERE ad.location_id=?
			  AND ad.category_id=?
			  AND ad.listed=?
			  ORDER BY " . $sql_order_with_image . " ad.published_at DESC
			  LIMIT " . $num;
			 */
			// optimized 
			$sql = "SELECT * FROM (
						SELECT ad1.id FROM " . Ad::tableNameFromClassName('Ad') . " ad1 
						WHERE ad1.location_id=? AND ad1.category_id=? AND ad1.listed=? 
						ORDER BY ad1.published_at DESC LIMIT " . $num . "
					) ad
					ORDER BY " . $sql_order_with_image;

			$rel_extra = Ad::query($sql, array($ad->location_id, $ad->category_id, 1));

			//$rel_extra = Ad::findAllFrom('Ad', 'location_id=? AND category_id=? AND listed=1 ORDER BY id DESC LIMIT ' . $num, array($ad->location_id, $ad->category_id), MAIN_DB, 'id');
		}


		foreach ($rel as $ad_rel)
		{
			$ids[$ad_rel->id] = $ad_rel->id;
		}
		foreach ($rel_extra as $ad_rel)
		{
			$ids[$ad_rel->id] = $ad_rel->id;
		}

		// echo '[rel:' . count($rel) . ',$rel_extra:' . count($rel_extra) . ']';
		// exclude self id
		unset($ids[$ad->id]);


		// crop array to required length 
		$ids = array_slice($ids, 0, $num);


		// convert ids to comma seperated scring and store in db 
		$ids_str = implode(',', $ids);

		// uset not used data here before query
		unset($ids);
		unset($rel);
		unset($rel_extra);


		// store related ad ids in table
		self::_insertUpdate($ad->id, $ids_str);


		// set generation end time 
		$opt = self::_getOpt();
		$time_end = time();
		$time_duration = $time_end - $time;
		$opt['time']['end'] = $time_end;
		$opt['time']['duration'] = $time_duration;
		// store last processed ads for seeing
		$finished_str = $ad->id . ':' . $current . ',end:' . $time_end . ',dur:' . $time_duration;
		array_unshift($opt['list']['finished'], $finished_str);
		$opt['list']['finished'] = array_slice($opt['list']['finished'], 0, self::MAX_QUEUE);

		// remove ad from queue and update db with new time
		self::_removeFromQueue($ad->id, $opt, true);


		// prepare AdRelated object to return 
		$adRelated = new AdRelated();
		$adRelated->ad_id = $ad->id;
		$adRelated->related = $ids_str;
		$adRelated->related_at = REQUEST_TIME;

		return $adRelated;
	}

	private static function _insertUpdate($ad_id, $related_str = '')
	{
		if (strlen($related_str))
		{
			$sql = "INSERT INTO " . AdRelated::tableNameFromClassName('AdRelated') . "
					(ad_id,related,related_at) 
					VALUES (?,?,?)
					ON DUPLICATE KEY UPDATE related=?, related_at=?";
			$values = array($ad_id, $related_str, REQUEST_TIME, $related_str, REQUEST_TIME);
		}
		else
		{
			// empty string do not update existing record with empty string, just update time of it
			$sql = "INSERT INTO " . AdRelated::tableNameFromClassName('AdRelated') . "
					(ad_id,related,related_at) 
					VALUES (?,?,?)
					ON DUPLICATE KEY UPDATE related_at=?";
			$values = array($ad_id, $related_str, REQUEST_TIME, REQUEST_TIME);
		}

		return AdRelated::query($sql, $values);
	}

	private static function _getOpt($fresh = true)
	{
		// get lates opt from DB
		$opt_default = array(
			'time'	 => array(
				'start'		 => 0,
				'end'		 => 0,
				'duration'	 => 0,
			),
			'list'	 => array(
				'new'		 => array(),
				'existing'	 => array(),
				'finished'	 => array()
			)
		);
		// check if waiting list empty then generate related and update last generation time to now. 

		if ($fresh)
		{
			$opt = Config::loadByKeyFresh('_generateRelatedLazy');
		}
		else
		{
			$opt = Config::option('_generateRelatedLazy');
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

	/**
	 * Update database with given opt
	 * 
	 * @param array $opt
	 */
	private static function _setOpt($opt)
	{
		Config::optionSet('_generateRelatedLazy', serialize($opt));
	}

	/**
	 * 
	 * @param type $ad
	 * @return boolean
	 */
	public static function generateRelatedLazy($ad = null)
	{
		// check if waiting list empty then generate related and update last generation time to now. 
		$opt = self::_getOpt(false);

		// check if time is ok to process 
		$time_ok = false;
		$time = time();

		// check if ok by last run times
		if (($opt['time']['start'] + self::TIME_NEXT_START < $time) ||
				($opt['time']['end'] > 0 && ($opt['time']['end'] + self::TIME_WAIT < $time)))
		{
			$time_ok = true;
		}

		// if time not ok and ad given then add it to list 
		if (!$time_ok)
		{
			if ($ad)
			{

				self::_addToQueue($ad, $opt);
			}
			return false;
		}

		// check if last generated records took more than 1 second and $ad given then do not generate, 
		// if ad set then it is requested for current page.
		$opt['current'] = 'cron';
		if ($ad)
		{
			$max_duration = 1;
			$arr_finished = $opt['list']['finished'];
			foreach ($arr_finished as $f)
			{
				// $f = "115388,end:1563177040,dur:2"
				$f_arr = explode(',', $f);
				$f_duration = explode(':', $f_arr[2]);
				if ($f_duration[1] >= $max_duration)
				{
					// generation is slow so ad to queue and leave
					self::_addToQueue($ad, $opt);
					return false;
				}
			}
			$opt['current'] = 'live';
		}



		// time ok, continue 
		if (is_null($ad))
		{
			// no ad set then get from queue. prefer new id
			// keep it simple just get first id 
			$ad_id = 0;
			if (count($opt['list']['new']))
			{
				$ad_id = array_shift($opt['list']['new']);
			}
			elseif (count($opt['list']['existing']))
			{
				$ad_id = array_shift($opt['list']['existing']);
			}

			// update option in db we reduced queue
			self::_removeFromQueue($ad_id, $opt, true);

			if ($ad_id)
			{
				$ad = Ad::findByIdFrom('Ad', $ad_id);
			}
		}

		if ($ad)
		{
			// check if data is expired 
			if (!isset($ad->AdRelated))
			{
				AdRelated::appendObject($ad, 'id', 'AdRelated', 'ad_id');
			}

			// check if related fresh then return related 
			if ($ad->AdRelated->related_at > (REQUEST_TIME - self::RELATED_LIFE))
			{
				// remove ad from queue
				self::_removeFromQueue($ad->id, $opt);
				return $ad->AdRelated;
			}

			// generate record 
			return self::_generateRelated($ad, $opt);
		}

		return false;
	}

	/**
	 * Add this ad to queue for generation later 
	 * 
	 * @param Ad $ad
	 * @param array $opt pass only if already loaded
	 */
	public static function _addToQueue($ad, $opt = null)
	{
		if ($ad)
		{
			if (is_null($opt))
			{
				// using stale data when adding to queue is ok 
				$opt = self::_getOpt();
			}

			// check if it is not already in list

			if (array_search($ad->id, $opt['list']['existing']) === false && array_search($ad->id, $opt['list']['new']) === false)
			{
				if ($ad->AdRelated)
				{
					// has existing records add to to exisitng list
					$opt['list']['existing'][] = $ad->id;
				}
				else
				{
					// add to new list 
					$opt['list']['new'][] = $ad->id;
				}

				// check if queue size ok 
				if (count($opt['list']['existing']) <= self::MAX_QUEUE && count($opt['list']['new']) <= self::MAX_QUEUE)
				{
					// save records and exit 
					self::_setOpt($opt);
				}
			}
		}
	}

	/**
	 * remove given id from list and update DB if $update_db=true or id removed from array
	 * 
	 * @param int $ad_id
	 * @param array $opt
	 * @param bool $update_db
	 */
	public static function _removeFromQueue($ad_id, $opt = null, $update_db = false)
	{
		if (is_null($opt))
		{
			$opt = self::_getOpt();
		}

		$arr = array();
		foreach ($opt['list']['existing'] as $id)
		{
			if ($id == $ad_id)
			{
				// id found, skip it
				$update_db = true;
			}
			else
			{
				$arr[] = $id;
			}
		}
		$opt['list']['existing'] = $arr;


		$arr = array();
		foreach ($opt['list']['new'] as $id)
		{
			if ($id == $ad_id)
			{
				// id found, skip it
				$update_db = true;
			}
			else
			{
				$arr[] = $id;
			}
		}
		$opt['list']['new'] = $arr;

		// if update required or requested
		if ($update_db)
		{
			// update option 
			self::_setOpt($opt);
		}
	}

	/**
	 * use this when deleting, it checks if database is right version before using any action
	 * 
	 * @param int $ad_id
	 * @return boolean
	 */
	public static function deleteById($ad_id)
	{
		if (!self::isDatabaseReady())
		{
			return false;
		}
		return AdRelated::deleteWhere('AdRelated', 'ad_id=?', array($ad_id));
	}

	/**
	 * set ad relation as expired so new record will be generated 
	 * 
	 * @param int $ad_id
	 * @return boolean
	 */
	public static function expire($ad_id)
	{
		if (!self::isDatabaseReady())
		{
			return false;
		}
		return AdRelated::update('AdRelated', array('related_at' => 0), 'ad_id=?', array($ad_id));
	}

	/**
	 * Checks if database has table that is added in version 1.7
	 * 
	 * @return boolean
	 */
	public static function isDatabaseReady()
	{
		// AdRelated object database table is added in version 1.7. 
		// if current DB version is lower than this then DB is not ready
		return Config::isDBVersionLowerThan('1.7') ? false : true;
	}

	public static function checkDuplicates($ad, $ads = null)
	{

		if (is_null($ads))
		{
			// check against related ads 
			// load related  ads and check for duplicates 
			self::append($ad);
			$ads = $ad->related;
		}


		if ($ads)
		{
			$arr_similar = array();
			// check current ad to related ads similarity 
			foreach ($ads as $r)
			{
				$r->similarity = TextTransform::text_similarity($ad->title . ' ' . $ad->description, $r->title . ' ' . $r->description);
				$r->similarity = round($r->similarity * 100);
				if ($r->similarity >= 90)
				{
					// 90% match mark as duplicate 
					$arr_similar[$r->id] = $r->similarity;
				}
			}
		}
	}

}
