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
 * class CategoryFieldRelation
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class CategoryFieldRelation extends Record
{

	const TABLE_NAME = 'category_field_relation';

	static private $arr_tree;
	static private $appended_all = false;
	private static $cols = array(
		'location_id'	 => 1,
		'category_id'	 => 1,
		'adfield_id'	 => 1,
		'group_id'		 => 1,
		'pos'			 => 1, // position
		'is_search'		 => 1, // display in search form
		'is_list'		 => 1, // display in listing
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public function __destruct()
	{
		unset($this->AdField);
		unset($this->Location);
		unset($this->Category);
		unset($this->CategoryFieldGroup);
	}

	/**
	 * get categorury custom fields 
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @param bool $append related objects
	 * @param bool $greedy search for parents
	 * @return array of CategoryFieldRelation object with adfeild_id index   
	 */
	public static function getCatfields($location_id, $category_id, $append = false, $greedy = false)
	{
		$location_id = intval($location_id);
		$category_id = intval($category_id);
		$greedy = intval($greedy);
		$append = intval($append);

		// true greedy is when location and category is not 0
		$true_greedy = $greedy && ($location_id != 0 || $category_id != 0);


		// load all catfields from DB
		self::loadAllCatfields($append);

		if ($append)
		{
			// make sure that values appended
			self::appendAll();
		}

		if (isset(self::$arr_tree[$location_id][$category_id]))
		{
			return self::_getCatfieldsReturn($location_id, $category_id);
		}

		if ($true_greedy)
		{
			// try greedy version then 

			return self::_getCatfieldsGreedy($location_id, $category_id);
		}

		return array();
	}

	/**
	 * check parent location and categories to find applied custom fields
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @return array of CategoryFieldRelation object with adfeild_id index  
	 */
	static private function _getCatfieldsGreedy($location_id, $category_id)
	{
		// not returned from direct reference then get parents 
		$whereParents = self::buildWhereParentLocationCategory($location_id, $category_id);
		foreach ($whereParents->arr_loc_id as $l_id)
		{
			foreach ($whereParents->arr_cat_id as $c_id)
			{
				//echo '(check_catf['.$l_id.']['.$c_id.'])<br/>';
				if (isset(self::$arr_tree[$l_id][$c_id]))
				{
					//return self::$arr_tree[$l_id][$c_id];
					return self::_getCatfieldsReturn($l_id, $c_id);
				}
			}
		}

		return array();
	}

	/**
	 * if $adfield_id is 0 then return empty array
	 * @param type $location_id
	 * @param type $category_id
	 * @return array
	 */
	static private function _getCatfieldsReturn($location_id, $category_id)
	{
		if (isset(self::$arr_tree[$location_id][$category_id]))
		{
			// check if it is set to not have any custom fields 
			// check if count is 1 or 0
			// check if adfield_id=0
			$return = self::$arr_tree[$location_id][$category_id];
			$cnt_ret = count($return);
			if ($cnt_ret == 1)
			{
				foreach ($return as $adfield_id => $cf)
				{
					if ($adfield_id == 0)
					{
						// it is preferred to not have any custom field for this loc cat combination 
						$return = array();
					}
				}
			}
			return $return;
		}
	}

	/**
	 * Load all catfields and populate $arr_tree for later use 
	 * 
	 */
	public static function loadAllCatfields($append = true)
	{
		if (!isset(self::$arr_tree))
		{
			$catfields = CategoryFieldRelation::findAllFrom('CategoryFieldRelation', '1=1 ORDER BY pos');
			foreach ($catfields as $cf)
			{
				self::$arr_tree[$cf->location_id][$cf->category_id][$cf->adfield_id] = $cf;
			}

			// do not delete existing array for apending 
			if ($append)
			{
				self::_appendAll($catfields);
			}
		}

		return self::$arr_tree;
	}

	public static function appendAll()
	{
		// load all and append if not loaded
		self::loadAllCatfields(true);

		// do not delete existing array for apending 
		// if loaded and not appended then append now
		if (!self::$appended_all)
		{

			$catfields = array();


			// convert array to single array 
			foreach (self::$arr_tree as $l_id => $arr_tree_c)
			{
				foreach ($arr_tree_c as $c_id => $arr_tree_af)
				{
					foreach ($arr_tree_af as $af_id => $cf)
					{
						$catfields[] = $cf;
					}
				}
			}

			self::_appendAll($catfields);
		}
	}

	private static function _appendAll($catfields)
	{

		if (!self::$appended_all)
		{
			// append all from cache as well 
			// append Location, Category, AdField, CategoryFieldGroup
			Location::appendLocation($catfields);
			Category::appendCategory($catfields);
			AdField::appendAdField($catfields, 'adfield_id');
			CategoryFieldGroup::appendWithDescription($catfields, 'group_id');

			self::$appended_all = true;
		}
	}

	/**
	 * build where query containing all parents incluting self location and cateogry. 
	 * used to get valid parent rule for PaymentPrice, CategoryFieldRelation if greedy.
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @return \stdClass $return->where, $return->where_vals, $return->arr_loc_id, $return->arr_cat_id
	 */
	public static function buildWhereParentLocationCategory($location_id, $category_id)
	{
		// return as object 
		$return = new stdClass();

		$return->arr_loc_id = array();
		$return->arr_cat_id = array();



		// check for parent category
		// check upper category first if no result then check upper location
		// define array of parent categories
		$return->arr_cat_id[$category_id] = $category_id;
		$category = Category::getCategoryFromTree($category_id);
		if ($category)
		{
			// get parents
			$arr_cat = Category::getParents($category);
			foreach ($arr_cat as $c)
			{
				$return->arr_cat_id[$c->id] = $c->id;
			}
		}
		$return->arr_cat_id[0] = 0;



		// define array of parent locations
		// check for upper location 
		$return->arr_loc_id[$location_id] = $location_id;
		$location = Location::getLocationFromTree($location_id);
		if ($location)
		{
			// get parents
			$arr_loc = Location::getParents($location);
			foreach ($arr_loc as $l)
			{
				$return->arr_loc_id[$l->id] = $l->id;
			}
		}
		$return->arr_loc_id[0] = 0;

		$arr_where_loc = implode(',', Ad::ids2quote($return->arr_loc_id));
		$arr_where_cat = implode(',', Ad::ids2quote($return->arr_cat_id));

		$return->where = "location_id IN (" . $arr_where_loc . ") AND category_id IN (" . $arr_where_cat . ")";
		$return->where_vals = array();

		return $return;
	}

	/**
	 * Deletes category custom fields
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @return bool 
	 */
	public static function deleteCatfields($location_id, $category_id)
	{
		$catfields = CategoryFieldRelation::deleteWhere('CategoryFieldRelation', "location_id=? AND category_id=?", array($location_id, $category_id));

		self::_resetInternalValues();

		return $catfields;
	}

	public static function groupResults($catfields)
	{
		// group by location_id and category_id
		$return = array();
		foreach ($catfields as $cf)
		{
			$return[$cf->location_id . '_' . $cf->category_id][] = $cf;
		}

		return $return;
	}

	/**
	 * save passed category custom fields in given order
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @param string $vals 
	 * @return array CategoryFieldRelation $group_id_$field_id_$is_search_$is_list|...
	 */
	public static function saveCatfields($location_id, $category_id, $vals)
	{
		// delete all catfields
		self::deleteCatfields($location_id, $category_id);

		// parse vals
		$arr_vals = explode('|', $vals);
		$i = 0;

		$catfields = array();

		foreach ($arr_vals as $v)
		{
			list($group_id, $field_id, $is_search, $is_list) = explode('_', $v);

			// save vals
			$cfr = new CategoryFieldRelation();
			$cfr->location_id = $location_id;
			$cfr->category_id = $category_id;
			$cfr->adfield_id = intval($field_id);
			$cfr->group_id = intval($group_id);
			$cfr->pos = $i;
			$cfr->is_search = $is_search;
			$cfr->is_list = $is_list;

			$cfr->save('new_id');

			$catfields[] = $cfr;

			$i++;
		}


		self::_clearCache();

		return $catfields;
	}

	/**
	 * Check if there is relation of adField to ads location and category, if not then create relation and return it
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @param int $adfield_id
	 * @param int $is_search
	 * @param int $is_list
	 * @return CategoryFieldRelation
	 */
	public static function checkMake($location_id, $category_id, $adfield_id, $is_search = 0, $is_list = 0)
	{
		// do not append objects 
		$related_catfields = CategoryFieldRelation::getCatfields($location_id, $category_id, false, true);

		if (count($related_catfields) < 1)
		{
			// create relation on current category and general location 
			// because fields usually do not change by location in general
			$cfr = self::_checkMake(0, $category_id, $adfield_id, $is_search, $is_list);
			$related_catfields[] = $cfr;
		}
		else
		{
			// exiist then check if this field related
			foreach ($related_catfields as $cfr_stored)
			{
				if ($cfr_stored->adfield_id == $adfield_id)
				{
					// exists
					return $cfr_stored;
				}
				$add_to_location_id = $cfr_stored->location_id;
				$add_to_category_id = $cfr_stored->category_id;
			}

			// not exists then add
			$cfr = self::_checkMake($add_to_location_id, $add_to_category_id, $adfield_id, $is_search, $is_list);
			$related_catfields[] = $cfr;
		}

		return $cfr;
	}

	/**
	 * add adfield relation to location, category. used by self::checkMake()
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @param int $adfield_id
	 * @param int $is_search
	 * @param int $is_list
	 * @return CategoryFieldRelation
	 */
	private static function _checkMake($location_id, $category_id, $adfield_id, $is_search = 0, $is_list = 0)
	{
		$cfr = new CategoryFieldRelation();
		$cfr->location_id = $location_id;
		$cfr->category_id = $category_id;
		$cfr->adfield_id = $adfield_id;
		$cfr->is_search = $is_search ? 1 : 0;
		$cfr->is_list = $is_list ? 1 : 0;

		$cfr->save('new_id');

		self::_resetInternalValues();

		return $cfr;
	}

	private static function _resetInternalValues()
	{
		// clear object static values
		// set null because it is checked with isset
		self::$arr_tree = null;
		self::$appended_all = false;

		self::_clearCache();
	}

	/**
	 * Delete cache and update json version
	 */
	private static function _clearCache()
	{
		// update json version to request updated location data
		Config::optionSet('json_version', REQUEST_TIME);
	}

}
