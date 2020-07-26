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
 * class AdFieldValue stores multiple values for adfields with type dropdown, checkbox, radio. 
 * Value names are stored in AdFieldValueDescription with language id
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdCategoryCount extends Record
{

	const TABLE_NAME = 'ad_category_count';
	const UPDATE_PERIOD = 3600; // 60 minutes sufficient for up to 100K ads per month site

	static $arr_ads_count = array();
	static $arr_ads_sum = array();
	private static $cols = array(
		'category_id'		 => 1,
		'location_id'		 => 1,
		'user_id'			 => 1,
		'search_id'			 => 1,
		'count_listed'		 => 1,
		'count_not_listed'	 => 1,
		'added_at'			 => 1
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public static function updateCounts($force = null)
	{
		if (Config::option('disable_ad_counting'))
		{
			return false;
		}

		if (is_null($force))
		{
			// update if expired 
			$last_updateCounts = Config::option('last_updateCounts');
			$force = (REQUEST_TIME - self::UPDATE_PERIOD) > $last_updateCounts;
		}
		elseif ($force === false)
		{
			// check if records exist
			// because if no count saved then loc category widgets will not show.
			$last_updateCounts = Config::option('last_updateCounts');
			if (!$last_updateCounts)
			{
				$total_records = AdCategoryCount::countFrom('AdCategoryCount');
				if (!$total_records)
				{
					$force = true;
				}
			}
		}

		if ($force)
		{
			// clear all records
			self::clearAll();

			Config::optionSet('last_updateCounts', REQUEST_TIME);

			// update values 
			$sql = "INSERT INTO " . AdCategoryCount::tableNameFromClassName('AdCategoryCount') . " 
				(category_id,location_id,count_listed,count_not_listed,added_at) 
					(SELECT 
					category_id,
					location_id,
					SUM(listed) as count_listed,
					COUNT(id)-SUM(listed) as count_not_listed,
					" . REQUEST_TIME . " 
					FROM " . Ad::tableNameFromClassName('Ad') . " 
					GROUP BY location_id,category_id)
				ON DUPLICATE KEY UPDATE 
					count_listed = VALUES(count_listed),
					count_not_listed=VALUES(count_not_listed),
					added_at=" . REQUEST_TIME;
			self::query($sql);

			// delete old values 
			// AdCategoryCount::deleteWhere('AdCategoryCount', 'added_at!=?', array(REQUEST_TIME));
		}
	}

	public static function getCountCategory($location_id, $category_id, $cq = null)
	{
		// try cq first 
		$return = self::cqSumCategory($cq, $category_id);
		if ($return !== false)
		{
			return $return;
		}


		// fixed location, all categories 
		if (Config::option('disable_ad_counting'))
		{
			return false;
		}


		self::updateCounts(false);

		// get count with fixed location and all categories
		$location_id = intval($location_id);
		$category_id = intval($category_id);

		//echo '[getCountCategory(' . $location_id . ', ' . $category_id . ')]';

		if (!isset(self::$arr_ads_sum[$location_id][$category_id]))
		{
			//exit('[getCountCategory]');
			// get all counts for given location 
			$where = "search_id=0 AND user_id=0 AND count_listed!=0";
			$values = array();
			$arr_location_id = Location::getSublocationIds($location_id);

			// if set location_id then get only required locations
			if ($location_id)
			{
				$arr_location_id_ = Ad::ids2quote($arr_location_id);
				$where .= " AND location_id IN(" . implode(',', $arr_location_id_) . ")";
			}

			$records = AdCategoryCount::findAllFrom('AdCategoryCount', $where, $values);
			$arr_cat_id = array();
			foreach ($records as $r)
			{
				if ($r->count_listed > 0)
				{
					self::$arr_ads_count[$r->location_id][$r->category_id] = $r->count_listed;
				}
				$arr_cat_id[$r->category_id] = true;
			}

			foreach ($arr_cat_id as $cat_id => $bool)
			{
				// sum required location first 
				self::sumCountLocation($location_id, $cat_id);
			}

			foreach ($arr_location_id as $loc_id)
			{
				self::sumCountCategory($loc_id, 0);
			}

			/* echo '[self::$arr_ads_sum]';
			  print_r(self::$arr_ads_sum);
			  echo '[self::$arr_ads_count]';
			  print_r(self::$arr_ads_count); */
		}

		// self::sumCountCategory($location_id, $category_id);

		return self::$arr_ads_sum[$location_id][$category_id];
	}

	public static function getCountLocation($location_id, $category_id, $cq = null)
	{
		// try cq first 
		$return = self::cqSumLocation($cq, $location_id);
		if ($return !== false)
		{
			return $return;
		}


		// all location, fixed categories 
		if (Config::option('disable_ad_counting'))
		{
			return false;
		}


		self::updateCounts(false);

		// get count with fixed location and all categories
		$location_id = intval($location_id);
		$category_id = intval($category_id);

		//echo '[getCountLocation(' . $location_id . ', ' . $category_id . ')]';

		if (!isset(self::$arr_ads_sum[$location_id][$category_id]))
		{
			$where = "search_id=0 AND user_id=0 AND count_listed!=0";
			$values = array();
			$arr_category_id = Category::getSubcategoryIds($category_id);

			// if set category_id then get only required categories
			if ($category_id)
			{
				$arr_category_id_ = Ad::ids2quote($arr_category_id);
				$where .= " AND category_id IN(" . implode(',', $arr_category_id_) . ")";
			}
			// get all counts for given location 
			$records = AdCategoryCount::findAllFrom('AdCategoryCount', $where, $values);
			$arr_loc_id = array();
			foreach ($records as $r)
			{
				if ($r->count_listed)
				{
					self::$arr_ads_count[$r->location_id][$r->category_id] = $r->count_listed;
				}
				$arr_loc_id[$r->location_id] = true;
			}

			foreach ($arr_loc_id as $loc_id => $bool)
			{
				// sum required location first 
				self::sumCountCategory($loc_id, $category_id);
			}

			foreach ($arr_category_id as $cat_id)
			{
				self::sumCountLocation(0, $cat_id);
			}


			//echo '[self::$arr_ads_sum:'.count(self::$arr_ads_sum).']';
			//print_r(self::$arr_ads_sum);
			//echo '[self::$arr_ads_count:'.count(self::$arr_ads_count).']';
			//print_r(self::$arr_ads_count);
		}

		//self::sumCountLocation($location_id, $category_id);


		return self::$arr_ads_sum[$location_id][$category_id];
	}

	private static function sumCountCategory($location_id, $category_id)
	{

		//echo '[sumCountCategory(' . $location_id . ', ' . $category_id . ')]';
		// fixed location, all categories 
		if (!isset(self::$arr_ads_sum[$location_id][$category_id]))
		{
			// add self count

			$return = (isset(self::$arr_ads_count[$location_id][$category_id]) ? self::$arr_ads_count[$location_id][$category_id] : 0);

			// add child sums
			$category_tree = Category::getAllCategoryNamesTree();
			if (isset($category_tree[$category_id]))
			{
				foreach ($category_tree[$category_id] as &$cat)
				{
					$return += self::sumCountCategory($location_id, $cat->id);
				}
				unset($cat);
			}
			self::$arr_ads_sum[$location_id][$category_id] = intval($return);
		}

		return self::$arr_ads_sum[$location_id][$category_id];
	}

	private static function sumCountLocation($location_id, $category_id)
	{

		//echo '[sumCountLocation(' . $location_id . ', ' . $category_id . ')]';
		// all locations, fixed categories 
		if (!isset(self::$arr_ads_sum[$location_id][$category_id]))
		{
			// add self count
			$return = isset(self::$arr_ads_count[$location_id][$category_id]) ? self::$arr_ads_count[$location_id][$category_id] : 0;

			// add child sums
			$location_tree = Location::getAllLocationNamesTree();
			if (isset($location_tree[$location_id]))
			{
				foreach ($location_tree[$location_id] as &$loc)
				{
					$return += self::sumCountLocation($loc->id, $category_id);
				}
				unset($loc);
			}
			self::$arr_ads_sum[$location_id][$category_id] = intval($return);
		}

		return self::$arr_ads_sum[$location_id][$category_id];
	}

	public static function clearAll()
	{
		// empty summary table
		$sql = "TRUNCATE TABLE " . AdCategoryCount::tableNameFromClassName('AdCategoryCount');
		self::query($sql);

		// reset autocount timer
		Config::optionSet('last_updateCounts', 0);

		return true;
	}

	/**
	 * check if locations X categories > 50K disable ad counting automatically
	 */
	public static function autoDisbaleAdCount()
	{
		// get number of categories X locations
		$num_categories = Category::countFrom('Category');
		$num_locations = Location::countFrom('Location');

		if (!$num_categories)
		{
			$num_categories = 1;
		}

		if (!$num_locations)
		{
			$num_locations = 1;
		}

		if (!Config::option('disable_ad_counting') && $num_categories * $num_locations > 50000)
		{
			Config::optionSet('disable_ad_counting', 1);
		}
	}

	static public function cqCount($cq)
	{

		/// perform count using cq if given and all ids loaded
		if (is_null($cq) || !$cq->result->is_all_ids)
		{
			return false;
		}

		if (isset($cq->result->countLC))
		{
			return true;
		}

		// first check if all ads loaded 
		if ($cq->result->ads_all)
		{
			// use already loaded data 
			$arr_count = array();
			foreach ($cq->result->ads_all as $ad)
			{
				$l = intval($ad->location_id);
				$c = intval($ad->category_id);
				if (!isset($arr_count[$l][$c]))
				{
					$arr_count[$l][$c] = 0;
				}
				$arr_count[$l][$c] ++;
			}

			$cq->result->countLC = $arr_count;

			return true;
		}

		// count using ids_1k
		if ($cq->result->is_all_ids)
		{

			if ($cq->result->count == 0)
			{
				// no result so send empty array, and return true
				$cq->result->countLC = array();
				return true;
			}


			$sql = "SELECT count(id) as num,location_id,category_id "
					. "FROM " . Ad::tableNameFromClassName('Ad') . " "
					. "WHERE id IN (" . $cq->result->ids_1k_str . ") "
					. "GROUP BY location_id,category_id";

			$cache_key = 'ad_cqCount.' . SimpleCache::uniqueKey($sql);
			$arr_count = SimpleCache::get($cache_key);
			if ($arr_count === false)
			{
				$arr_count = array();
				// count using ids sql
				//echo '[cqCount:$sql:' . $sql . ']';
				$ids_count = Ad::query($sql, array());
				foreach ($ids_count as $cnt)
				{
					$l = intval($cnt->location_id);
					$c = intval($cnt->category_id);
					$arr_count[$l][$c] = intval($cnt->num);
				}

				SimpleCache::set($cache_key, $arr_count, 1800); // 30 minute cache counts 
			}

			$cq->result->countLC = $arr_count;
			return true;
		}


		return false;
	}

	static public function cqSumLocation($cq, $location_id)
	{
		if (self::cqCount($cq))
		{
			// we use $cq for counting
			if (!$cq->result->countLC)
			{
				// no results then sum is 0;
				return 0;
			}

			// all locations, fixed categories 
			if (!isset($cq->result->countSumLocation[$location_id]))
			{
				// add self count
				$return = 0;
				if (isset($cq->result->countLC[$location_id]))
				{
					foreach ($cq->result->countLC[$location_id] as $cat_id => $num)
					{
						$return += $num;
					}
				}


				// add child sums
				$location_tree = Location::getAllLocationNamesTree();
				if (isset($location_tree[$location_id]))
				{
					foreach ($location_tree[$location_id] as &$loc)
					{
						$return += self::cqSumLocation($cq, $loc->id);
					}
					unset($loc);
				}
				$cq->result->countSumLocation[$location_id] = intval($return);
			}

			return $cq->result->countSumLocation[$location_id];
		}

		// not counted. this is diferent than 0 count 
		return false;
	}

	static public function cqSumCategory($cq, $category_id)
	{
		if (self::cqCount($cq))
		{
			// we use $cq for counting

			if (!$cq->result->countLC)
			{
				// no results then sum is 0;
				return 0;
			}
			// all categories, fixed location 
			if (!isset($cq->result->countSumCategory[$category_id]))
			{
				// add self count
				$return = 0;
				foreach ($cq->result->countLC as $loc_id => $cat_arr)
				{
					if (isset($cat_arr[$category_id]))
					{
						$return += $cat_arr[$category_id];
					}
				}

				// add child sums
				$category_tree = Category::getAllCategoryNamesTree();
				if (isset($category_tree[$category_id]))
				{
					foreach ($category_tree[$category_id] as &$cat)
					{
						$return += self::cqSumCategory($cq, $cat->id);
					}
					unset($cat);
				}
				$cq->result->countSumCategory[$category_id] = intval($return);
			}

			return $cq->result->countSumCategory[$category_id];
		}

		// not counted. this is diferent than 0 count 
		return false;
	}

}
