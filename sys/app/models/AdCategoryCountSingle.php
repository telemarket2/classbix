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
class AdCategoryCountSingle extends Record
{

	const TABLE_NAME = 'ad_category_count';
	const UPDATE_PERIOD = 3600; // 60 minutes sufficient for up to 100K ads per month site

	static $arr_ads_count = array();
	static $arr_ads_sum = array();
	private static $cols = array(
		'category_id' => 1,
		'location_id' => 1,
		'user_id' => 1,
		'search_id' => 1,
		'count_listed' => 1,
		'count_not_listed' => 1,
		'added_at' => 1
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public static function updateCounts($force = false)
	{
		$last_updateCounts = Config::option('last_updateCounts');

		if(REQUEST_TIME - self::UPDATE_PERIOD > $last_updateCounts || $force)
		{
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
			AdCategoryCount::deleteWhere('AdCategoryCount', 'added_at!=?', array(REQUEST_TIME));
		}
	}

	public static function getCountCategory($location_id, $category_id)
	{
		// fixed location, all categories 

		self::updateCounts();

		// get count with fixed location and all categories
		$location_id = intval($location_id);
		$category_id = intval($category_id);

		//echo '[getCountCategory(' . $location_id . ', ' . $category_id . ')]';

		if(is_null(self::array1dGet(self::$arr_ads_sum, $location_id, $category_id)))
		{
			//exit('[getCountCategory]');
			// get all counts for given location 
			$where = "search_id=0 AND user_id=0 AND count_listed!=0";
			$values = array();
			$arr_location_id = Location::getSublocationIds($location_id);

			// if set location_id then get only required locations
			if($location_id)
			{
				$where .= " AND location_id IN(" . implode(',', array_fill(0, count($arr_location_id), '?')) . ")";
				$values = array_values($arr_location_id);
			}

			$records = AdCategoryCount::findAllFrom('AdCategoryCount', $where, $values);
			$arr_cat_id = array();
			foreach($records as $r)
			{
				if($r->count_listed > 0)
				{
					self::array1dSet(self::$arr_ads_count, $r->location_id, $r->category_id, $r->count_listed);
				}
				$arr_cat_id[$r->category_id] = $r->category_id;
			}

			foreach($arr_cat_id as $cat_id)
			{
				// sum required location first 
				self::sumCountLocation($location_id, $cat_id);
			}

			foreach($arr_location_id as $loc_id)
			{
				self::sumCountCategory($loc_id, 0);
			}

			/* echo '[self::$arr_ads_sum]';
			  print_r(self::$arr_ads_sum);
			  echo '[self::$arr_ads_count]';
			  print_r(self::$arr_ads_count); */
		}

		// self::sumCountCategory($location_id, $category_id);

		
		return self::array1dGet(self::$arr_ads_sum, $location_id, $category_id);
	}

	public static function getCountLocation($location_id, $category_id)
	{
		// all location, fixed categories 

		self::updateCounts();

		// get count with fixed location and all categories
		$location_id = intval($location_id);
		$category_id = intval($category_id);

		//echo '[getCountLocation(' . $location_id . ', ' . $category_id . ')]';

		if(is_null(self::array1dGet(self::$arr_ads_sum, $location_id, $category_id)))
		{
			$where = "search_id=0 AND user_id=0 AND count_listed!=0";
			$values = array();
			$arr_category_id = Category::getSubcategoryIds($category_id);

			// if set category_id then get only required categories
			if($category_id)
			{
				$where .= " AND category_id IN(" . implode(',', array_fill(0, count($arr_category_id), '?')) . ")";
				$values = array_values($arr_category_id);
			}
			// get all counts for given location 
			$records = AdCategoryCount::findAllFrom('AdCategoryCount', $where, $values);
			$arr_loc_id = array();
			foreach($records as $r)
			{
				if($r->count_listed)
				{
					self::array1dSet(self::$arr_ads_count, $r->location_id, $r->category_id, $r->count_listed);
				}
				$arr_loc_id[$r->location_id] = $r->location_id;
			}

			foreach($arr_loc_id as $loc_id)
			{
				// sum required location first 
				self::sumCountCategory($loc_id, $category_id);
			}

			foreach($arr_category_id as $cat_id)
			{
				self::sumCountLocation(0, $cat_id);
			}


			//echo '[self::$arr_ads_sum:'.count(self::$arr_ads_sum).']';
			//print_r(self::$arr_ads_sum);
			//echo '[self::$arr_ads_count:'.count(self::$arr_ads_count).']';
			//print_r(self::$arr_ads_count);
		}

		//self::sumCountLocation($location_id, $category_id);


		return self::array1dGet(self::$arr_ads_sum, $location_id, $category_id);
	}

	private static function sumCountCategory($location_id, $category_id)
	{

		//echo '[sumCountCategory(' . $location_id . ', ' . $category_id . ')]';
		// fixed location, all categories 
		if(is_null(self::array1dGet(self::$arr_ads_sum, $location_id, $category_id)))
		{
			// add self count

			$return = intval(self::array1dGet(self::$arr_ads_count, $location_id, $category_id));

			// add child sums
			$category_tree = Category::getAllCategoryNamesTree();
			if(isset($category_tree[$category_id]))
			{
				foreach($category_tree[$category_id] as &$cat)
				{
					$return += self::sumCountCategory($location_id, $cat->id);
				}
				unset($cat);
			}
			self::array1dSet(self::$arr_ads_sum, $location_id, $category_id, intval($return));
		}

		return self::array1dGet(self::$arr_ads_sum, $location_id, $category_id);
	}

	private static function sumCountLocation($location_id, $category_id)
	{

		//echo '[sumCountLocation(' . $location_id . ', ' . $category_id . ')]';
		// all locations, fixed categories 
		if(is_null(self::array1dGet(self::$arr_ads_sum, $location_id, $category_id)))
		{
			// add self count
			$return = intval(self::array1dGet(self::$arr_ads_count, $location_id, $category_id));

			// add child sums
			$location_tree = Location::getAllLocationNamesTree();
			if(isset($location_tree[$location_id]))
			{
				foreach($location_tree[$location_id] as &$loc)
				{
					$return += self::sumCountLocation($loc->id, $category_id);
				}
				unset($loc);
			}
			self::array1dSet(self::$arr_ads_sum, $location_id, $category_id, intval($return));
		}

		return self::array1dGet(self::$arr_ads_sum, $location_id, $category_id);
	}

	public static function clearAll()
	{
		// empty summary table
		$sql = "TRUNCATE TABLE " . AdCategoryCount::tableNameFromClassName('AdCategoryCount');
		self::query($sql);

		// reset autocount timer
		Config::optionDelete('last_updateCounts');

		return true;
	}

	public static function array1dSet(&$arr, $x, $y, $val)
	{
		$arr[$x . sprintf("[%010s]\n", $y)] = $val;
	}

	public static function array1dGet(&$arr, $x, $y)
	{
		return $arr[$x . sprintf("[%010s]\n", $y)];
	}

}