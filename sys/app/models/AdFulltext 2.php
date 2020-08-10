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
 * class AdFulltext
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdFulltext extends Record
{

	const TABLE_NAME = 'ad_fulltext';

	private static $cols = array(
		'id'			 => 1,
		'title'			 => 1,
		'description'	 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	/**
	 * process given ad and add it to db 
	 * 
	 * @param Ad $ad
	 */
	public static function process($ad)
	{
		if (!self::isDatabaseReady())
		{
			return false;
		}

		$title = TextTransform::text_normalize($ad->title, 'all');
		$description = TextTransform::text_normalize($ad->title . ' ' . $ad->description, 'all');

		$sql = "INSERT INTO " . AdFulltext::tableNameFromClassName('AdFulltext') . " "
				. "(id,title,description) "
				. "VALUES (?,?,?) ON DUPLICATE KEY UPDATE title=?, description=?";

		return AdFulltext::query($sql, array($ad->id, $title, $description, $title, $description));
	}

	/**
	 * Process not converted ads. this is used when AdFulltext used first time, for converting old existing items.
	 */
	public static function cronProcess($forece = false)
	{
		if (!self::isDatabaseReady())
		{
			return false;
		}

		$fulltext_convert = Config::option('fulltext_convert');
		$fulltext_convert_last_time = Config::option('fulltext_convert_last_time');
		/// seconds to wait after last cron process
		$wait = 10;
		Benchmark::cp('$fulltext_convert:' . $fulltext_convert . ','
				. '$fulltext_convert_last_time:' . $fulltext_convert_last_time);
		if ($forece || ($fulltext_convert && $fulltext_convert_last_time < REQUEST_TIME - $wait))
		{
			Benchmark::cp('cronProcess:find');

			// get not processed ad ids
			$num = 100;
			$sql = "SELECT ad.id FROM " . Ad::tableNameFromClassName('Ad') . " ad "
					. "WHERE NOT EXISTS "
					. "(SELECT 1 FROM " . AdFulltext::tableNameFromClassName('AdFulltext') . " aft WHERE aft.id=ad.id) "
					. "ORDER BY ad.id DESC "
					. "LIMIT " . $num;

			$ad_ids = Ad::query($sql, array());
			if ($ad_ids)
			{
				Benchmark::cp('cronProcess:total:' . count($ad_ids));

				// mark as processed
				Config::optionSet('fulltext_convert_last_time', REQUEST_TIME);
				// append ads
				Ad::appendObject($ad_ids, 'id', 'Ad');
				foreach ($ad_ids as $ad_id)
				{
					$ad = $ad_id->Ad;
					self::process($ad);
				}
			}
			else
			{
				Benchmark::cp('cronProcess:$ad_ids:' . $ad_ids);

				//finished converting, remove convertion flag from options 
				Config::optionDelete('fulltext_convert');
				Config::optionDelete('fulltext_convert_last_time');
			}
		}
	}

	/**
	 * Checks if database has table that is added in version 2
	 * 
	 * @return boolean
	 */
	public static function isDatabaseReady()
	{
		// AdFulltext object database table is added in version 2 
		// if current DB version is lower than this then DB is not ready
		return Config::isDBVersionLowerThan('2') ? false : true;
	}

	/**
	 * get ad count not converted to fulltext index
	 * @param bool $force
	 * @return int
	 */
	public static function status($force = false)
	{
		if (!self::isDatabaseReady())
		{
			return false;
		}


		$fulltext_status = Config::option('fulltext_status');
		list($fulltext_status_last_time, $fulltext_status_count) = explode(':', $fulltext_status);

		/// time to wait before checking fulltext status. 1 day
		$wait = 3600 * 24;

		if ($force || $fulltext_status_last_time < REQUEST_TIME - $wait)
		{

			// get not converted item count 
			$not_converted_num = 0;
			$min_num = 100;
			$sql = "SELECT count(ad.id) as num "
					. "FROM " . Ad::tableNameFromClassName('Ad') . " ad "
					. "WHERE NOT EXISTS "
					. "(SELECT 1 FROM " . AdFulltext::tableNameFromClassName('AdFulltext') . " aft WHERE aft.id=ad.id) ";

			$num_obj = Ad::query($sql, array());
			if ($num_obj)
			{
				$not_converted_num = $num_obj[0]->num;
			}
			if ($not_converted_num > 0)
			{
				// convert now one batch
				self::cronProcess(true);

				if ($not_converted_num > $min_num)
				{
					// enable lazy conversion 
					self::enableLazyConvertionFlag();
				}
				else
				{
					// set to 0 because we completed one batch, and there were less items to process than one batch
					$not_converted_num = 0;
				}
			}

			if ($not_converted_num == 0)
			{
				// remove lazy convertion flag
				Config::optionDelete('fulltext_convert');
				Config::optionDelete('fulltext_convert_last_time');
			}

			// update status data 
			$fulltext_status_count = $not_converted_num;
			$fulltext_status = REQUEST_TIME . ':' . $fulltext_status_count;
			Config::optionSet('fulltext_status', $fulltext_status);
		}

		return $fulltext_status_count;
	}

	/**
	 * Enable fulltext lazy conversion for old items flags
	 */
	public static function enableLazyConvertionFlag()
	{
		Config::optionSet('fulltext_convert', '1', true);
	}

	/**
	 * Show notice if need to convert some data to fullindex search index 
	 */
	public static function notice()
	{
		if (AdFulltext::status() > 0)
		{
			$fulltext_message = __('Old data needs to be converted to fulltext search format. {button}', array(
				'{button}' => '<button class="button fulltext_batch_convert" data-target=".fulltext_batch_convert" data-toggle="cb_batch" data-url="admin/batchFulltext/">' . __('Convert {num} items', array(
					'{num}' => AdFulltext::status()
				)) . '</button>'
			));

			// show notice with batch process button
			Validation::getInstance()->set_info($fulltext_message);
		}
	}

}
