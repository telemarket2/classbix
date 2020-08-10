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
 * class Location
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Location extends Record
{

	const TABLE_NAME = 'location';
	const STATUS_ENABLED = 'enabled';
	const STATUS_ALL = 'all';

	static private $arr_tree;
	static private $arr_tree_reverse = array();
	static private $arr_locs_by_name = array();
	static private $bulk_insert = false;
	private static $cols = array(
		'id'		 => 1,
		'parent_id'	 => 1,
		'slug'		 => 1,
		'pos'		 => 1,
		'enabled'	 => 1,
		'added_at'	 => 1,
		'added_by'	 => 1
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public function __destruct()
	{
		unset($this->LocationDescription);
		unset($this->arr_parents);
	}

	function beforeInsert()
	{
		// fix slug
		if (!is_null(self::getNameByLng($this)))
		{
			$this->slug = Permalink::generateSlug($this->slug, self::getNameByLng($this), $this->id, Permalink::ITEM_TYPE_LOCATION);
		}

		// set initial position 
		$this->pos = self::getLastPosition($this->parent_id) + 1;

		// check if parent is resolvable to root. if not then make parent 0
		if ($this->parent_id > 0)
		{
			$this->parent_id = self::checkPossibleParent($this->id, $this->parent_id);
		}

		// set other defaults if not set
		if (!isset($this->enabled))
		{
			$this->enabled = 1;
		}

		$this->added_at = REQUEST_TIME;
		$this->added_by = AuthUser::$user->id;
		return true;
	}

	function beforeUpdate()
	{
		// fix slug
		if (isset($this->slug) && !is_null(self::getNameByLng($this)))
		{
			$this->slug = Permalink::generateSlug($this->slug, self::getNameByLng($this), $this->id, Permalink::ITEM_TYPE_LOCATION);
		}

		// check if parent is resolvable to root. if not then make parent 0
		if ($this->parent_id > 0)
		{
			$this->parent_id = self::checkPossibleParent($this->id, $this->parent_id);
		}

		return true;
	}

	function afterInsert()
	{
		// save slug 
		if (!Permalink::savePermalinkWithObject($this, Permalink::ITEM_TYPE_LOCATION, self::getNameByLng($this)))
		{
			// error saving permalink
			return false;
		}

		$return = $this->updateDescription();


		// on bulk insert disable these. 
		// they should be called at the end of bulk insert
		if (!self::$bulk_insert)
		{
			self::_clearCache();
			AdCategoryCount::autoDisbaleAdCount();
		}

		return $return;
	}

	function afterUpdate()
	{
		// save slug 
		if (!Permalink::savePermalinkWithObject($this, Permalink::ITEM_TYPE_LOCATION, self::getNameByLng($this)))
		{
			// error saving permalink
			return false;
		}

		$return = $this->updateDescription();

		self::_clearCache();

		AdCategoryCount::autoDisbaleAdCount();

		return $return;
	}

	function afterDelete()
	{
		self::_clearCache();

		return true;
	}

	function beforeDelete()
	{
		// delete locations
		// get all sub locations from db. 
		// Do not use cache because in cache it is object not location class but cleaned object with stdclass
		$sub = Location::findAllFrom('Location', 'parent_id=?', array($this->id));
		foreach ($sub as $c)
		{
			$c->delete('id');
		}

		// delete descriptions
		LocationDescription::deleteWhere('LocationDescription', 'location_id=?', array($this->id));

		// delete custom field relation 
		CategoryFieldRelation::deleteWhere('CategoryFieldRelation', 'location_id=?', array($this->id));

		// delete slug 
		Permalink::deleteWhere('Permalink', 'item_id=? AND item_type=?', array($this->id, Permalink::ITEM_TYPE_LOCATION));

		// delete payment price
		PaymentPrice::deleteWhere('PaymentPrice', 'location_id=?', array($this->id));

		return true;
	}

	/**
	 * get locations 
	 * @param styring $status
	 * @return array of object
	 */
	public static function getLocations($status = 'enabled')
	{
		return self::getAllLocationNamesTree($status, true);
	}

	/**
	 * Move position of current location up or down by one
	 * @param type $id
	 * @param type $dir direction up, down
	 * @return type bool 
	 */
	public static function changePosition($id, $dir)
	{
		$id = intval($id);

		// get requested location 
		$location = self::findByIdFrom('Location', $id);
		if (!$location)
		{
			return false;
		}

		// get all locations in position order
		$locations = Location::findAllFrom('Location', 'parent_id=? ORDER BY pos', array($location->parent_id));

		$found = false;
		$arr = array();
		$i = 1;
		foreach ($locations as $c)
		{
			if ($id == $c->id)
			{
				// store old position
				$old_pos = $i;
				$found = true;
			}
			else
			{
				$arr[] = $c->id;
			}
			$i++;
		}

		if (!$found)
		{
			return false;
		}


		$total = count($locations);
		if ($dir == 'up')
		{
			$new_pos = $old_pos - 1;
		}
		else
		{
			$new_pos = $old_pos + 1;
		}

		if ($new_pos < 1)
		{
			$new_pos = 1;
		}

		if ($new_pos > $total)
		{
			$new_pos = $total;
		}

		// save in new order, update positions
		$i = 1;
		$updated = false;
		foreach ($arr as $v)
		{
			if ($i == $new_pos)
			{
				Location::update('Location', array('pos' => $i), 'id=?', array($id));
				$i++;
				$updated = true;
			}
			Location::update('Location', array('pos' => $i), 'id=?', array($v));
			$i++;
		}

		if (!$updated)
		{
			Location::update('Location', array('pos' => $i), 'id=?', array($id));
		}

		self::_clearCache();

		return intval($location->parent_id);
	}

	public static function selectBox($selected_id = 0, $name = 'parent_id', $status = 'all', $display_root = true, $root_title = '', $max_level = 0, $posting = false)
	{
		// get all locations
		$locations = self::getAllLocationNamesTree($status);

		if ($posting)
		{
			$options = self::_selectBoxLvlPosting($locations);
		}
		else
		{
			$options = self::_selectBoxLvl($locations, 0, 0, $max_level);
		}
		$options = str_replace('value="' . $selected_id . '"', 'value="' . $selected_id . '" selected="selected"', $options);

		if ($display_root)
		{
			if (!$root_title)
			{
				$root_title = __('No parent');
			}
			$options = '<option value="0">' . $root_title . '</option>' . $options;
		}
		return '<select name="' . $name . '" id="' . $name . '">' . $options . '</select>';
	}

	/**
	 * Get all locations as javascript object for later generating chain select
	 * required Javascript functions: chainSelect.init, chainSelect.display, chainSelect.displayLoop
	 * 
	 * @param int $selected_id
	 * @param string $name
	 * @param string $status
	 * @param string $root_title
	 * @return string javascript
	 */
	public static function selectBoxChain($selected_id = 0, $name = 'parent_id', $status = 'all', $root_title = '')
	{
		// get all locations for javascript
		$all_locations = Location::getAllLocationNamesTree($status);

		// display chain if more than one level
		if (count($all_locations) > 1)
		{
			$return_arr = array();
			$total_items = 0;
			foreach ($all_locations as $parent_id => $locations)
			{
				foreach ($locations as $location)
				{
					$return_arr['parent_' . $parent_id]['id_' . $location->id] = $location->LocationDescription->name;
					$total_items++;
				}
			}

			// display chain if more than 10 items
			if ($total_items > 10)
			{
				return '<script>'
						. 'var chain_' . $name . '={name:"' . $name . '",selected_id:"' . intval($selected_id) . '",root_title:"' . View::escape($root_title) . '",arr:' . Config::arr2js($return_arr) . '};'
						. '</script>';
			}
		}
		return '';
	}

	/**
	 * Get all locations as json
	 * 
	 * @param string $status
	 * @return string javascript
	 */
	public static function ____locationJson_DELETE($status = 'all')
	{
		// get all locations for javascript
		$all_locations = Location::getAllLocationNamesTree($status);

		// display chain if more than one level
		if (count($all_locations) > 1)
		{
			$return_arr = array();
			$total_items = 0;
			foreach ($all_locations as $parent_id => $locations)
			{
				foreach ($locations as $location)
				{
					$return_arr['parent_' . $parent_id]['id_' . $location->id] = $location->LocationDescription->name;
					$total_items++;
				}
			}
			return Config::arr2js($return_arr);
		}
		return '';
	}

	/**
	 * check if has enabled root location for posting 
	 * @return type 
	 */
	public static function hasValidPostingLocations()
	{
		// get all locations
		$locations = self::getAllLocationNamesTree(Location::STATUS_ENABLED);
		return count($locations[0]);
	}

	/**
	 * Get all location names and parent_id for tree view
	 * 
	 * @return type 
	 */
	public static function getAllLocationNamesTree($status = 'all', $reverse = false)
	{
		if (!isset(self::$arr_tree))
		{

			// set tree only here 
			self::$arr_tree = array();
			self::$arr_tree[self::STATUS_ALL] = array();
			self::$arr_tree[self::STATUS_ENABLED] = array();
			self::$arr_tree_reverse = array();
			self::$arr_tree_reverse[self::STATUS_ALL] = array();
			self::$arr_tree_reverse[self::STATUS_ENABLED] = array();


			$cache_key = 'locations.' . I18n::getLocale();
			$locations = SimpleCache::get($cache_key);
			if ($locations === false)
			{
				// geal all locations names and parent id for tree view
				$locations = Location::findAllFrom('Location', '1=1 ORDER BY parent_id,pos,id', array(), 'master', 'id,parent_id,slug,enabled');

				// old way use appending function
				self::appendName($locations);

				// store data in cache 
				SimpleCache::set($cache_key, $locations, 86400); //24 hours
			}

			foreach ($locations as $c)
			{
				self::getAllLocationNamesTreeUpdate($c);
			}
			unset($locations);
		}

		if ($reverse)
		{
			return self::$arr_tree_reverse[$status];
		}

		return self::$arr_tree[$status];
	}

	/**
	 * Add location to memory. 
	 * Added when read from database or cache
	 * Added when added new location in batch insert
	 * 
	 * 
	 * 
	 * @param stdClass $record is stdClass:if from cache, Location if from DB or New Location
	 */
	private static function getAllLocationNamesTreeUpdate($record)
	{
		// add only if record id >0
		if ($record->id > 0)
		{
			// tree not loaded
			if (!isset(self::$arr_tree))
			{
				// load tree first 
				self::getAllLocationNamesTree();
			}

			if (!isset(self::$arr_tree[self::STATUS_ALL][$record->parent_id]))
			{
				self::$arr_tree[self::STATUS_ALL][$record->parent_id] = array();
			}

			self::$arr_tree[self::STATUS_ALL][$record->parent_id][] = $record;
			self::$arr_tree_reverse[self::STATUS_ALL][$record->id] = $record;

			if ($record->enabled)
			{
				if (!isset(self::$arr_tree[self::STATUS_ENABLED][$record->parent_id]))
				{
					self::$arr_tree[self::STATUS_ENABLED][$record->parent_id] = array();
				}
				self::$arr_tree[self::STATUS_ENABLED][$record->parent_id][] = $record;
				self::$arr_tree_reverse[self::STATUS_ENABLED][$record->id] = $record;
			}
		}
	}

	/**
	 * Check if some pain parent are not linked to root and not visible in script.
	 * This happen if delete or bulk insert operation not finished and halted by error 
	 * 
	 */
	static public function checkNotFoundParents()
	{
		// check if we have records with non existing parent_record then create undefined parent in disabled mode and add them there 
		// generate location tree
		Location::getAllLocationNamesTree();

		$arr_no_parent = array();
		foreach (self::$arr_tree_reverse[self::STATUS_ALL] as $record)
		{
			// parent not 0 and not found then add to $arr_no_parent
			if ($record->parent_id != 0 && !isset(self::$arr_tree_reverse[self::STATUS_ALL][$record->parent_id]))
			{
				$arr_no_parent[$record->parent_id] = true;
			}
		}

		if ($arr_no_parent)
		{
			// check make undefined parent location and make sure it is disabled 
			$undefined = Location::checkMakeByName('undefined');

			// make sure it is disabled 
			if ($undefined->enabled || !isset($undefined->enabled))
			{
				// enabled 
				$undefined->enabled = 0;
				Location::update('Location', array('enabled' => 0), 'id=?', array($undefined->id));
			}

			$arr_no_parent_ = Record::quoteArray(array_keys($arr_no_parent));

			// move all locations with no parent to $location_undefined 
			Location::update('Location', array('parent_id' => $undefined->id), 'parent_id IN (' . implode(',', $arr_no_parent_) . ')', array());

			// clear cache 
			Location::_clearCache();
		}
	}

	public static function appendName($locations)
	{
		LocationDescription::appendObject($locations, 'id', 'LocationDescription', 'location_id', '', MAIN_DB, 'location_id,language_id,name', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
	}

	public static function appendAll($locations)
	{
		LocationDescription::appendObject($locations, 'id', 'LocationDescription', 'location_id', '', MAIN_DB, '*', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
	}

	/**
	 * render a ul list displaying locations as tree
	 * 
	 * @param array $locations
	 * @param int $parent_id
	 * @param string $pattern
	 * @param string $wrap_pattern
	 * @return string html
	 */
	public static function htmlLocationTree(& $locations, $parent_id = 0, $pattern = '<li>{name}{sub}</li>', $wrap_pattern = '<ul>{tree}</ul>', $selected_category = null)
	{
		$tree = '';
		$arr_search = array('{url}', '{name}', '{sub}');
		if (isset($locations[$parent_id]))
		{
			foreach ($locations[$parent_id] as $c)
			{
				$arr_replace = array(
					self::url($c, $selected_category),
					self::getName($c),
					self::htmlLocationTree($locations, $c->id, $pattern, $wrap_pattern, $selected_category)
				);
				$tree .= str_replace($arr_search, $arr_replace, $pattern);
			}
			$tree = str_replace('{tree}', $tree, $wrap_pattern);
		}

		return $tree;
	}

	/**
	 * generate location tree for info only, used in admin only
	 * Used when deleting location with sublocations.
	 * If location has too many sub (1000+) then delete page will load very slow. 
	 * Show only 100 first degree subs, 10 from other degrees. 
	 * Add (+54 more) for truncated values 
	 * 
	 * @param array $locations
	 * @param int $parent_id
	 * @return string
	 */
	public static function htmlLocationTreeTruncated($locations, $parent_id = 0)
	{
		// scan given locations array and reduce subs
		$locations_truncated = array();
		$tolerate = 5;

		foreach ($locations as $key => $arr)
		{
			if ($key == $parent_id)
			{
				$num = 100;
			}
			else
			{
				$num = 10;
			}
			$cnt = count($arr);
			if ($cnt > $num + $tolerate)
			{
				// truncate it and update last item with more text 
				$arr = array_slice($arr, 0, $num);
				$last = end($arr);
				$last->LocationDescription->name .= ' (' . __('{num} more', array('{num}' => '+' . ($cnt - $num))) . ')';
			}
			$locations_truncated[$key] = $arr;
		}

		return Location::htmlLocationTree($locations_truncated, $parent_id);
	}

	/**
	 * generate options for selectbox 
	 * 
	 * @param array $arr Location tree
	 * @param int $parent_id
	 * @param int $level currenct level
	 * @param int $max_level maximum depth, used for listing only 1,2 levels on front page 
	 * @return string 
	 */
	private static function _selectBoxLvl(& $arr, $parent_id = 0, $level = 0, $max_level = 0)
	{
		$return = '';
		if (isset($arr[$parent_id]) && ($max_level == 0 || $level < $max_level))
		{
			$level_str = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
			foreach ($arr[$parent_id] as $c)
			{
				$return .= '<option value="' . $c->id . '">' . $level_str . View::escape(Location::getName($c)) . '</option>'
						. self::_selectBoxLvl($arr, $c->id, $level + 1);
			}
		}
		return $return;
	}

	private static function _selectBoxLvlPosting(& $arr, $parent_id = 0)
	{
		$return = '';
		if (isset($arr[$parent_id]))
		{
			foreach ($arr[$parent_id] as $c)
			{
				if (self::isPostingAvailable($c))
				{
					$return .= '<option value="' . $c->id . '">' . Location::getFullName($c) . '</option>';
				}
				$return .= self::_selectBoxLvlPosting($arr, $c->id);
			}
		}
		return $return;
	}

	/**
	 * get all oparents till root and return in array. use this for breadcrumbs
	 * @return type 
	 */
	public static function getParents($location, $status = 'all')
	{
		if (!$location)
		{
			return array();
		}

		if (!isset($location->arr_parents))
		{
			// get parents
			// to prevent infinite loop use max levels
			$max_levels = 100;
			$parent = $location;
			$arr_parents = array();
			self::getAllLocationNamesTree($status);
			while ($parent && $parent->parent_id && $max_levels)
			{
				//Location::appendObject($parent, 'parent_id', 'Location', 'id', 'parentLocation', MAIN_DB, 'id,name,parent_id');
				//$parent = $parent->parentLocation;
				$parent = self::$arr_tree_reverse[$status][$parent->parent_id];
				$arr_parents[] = $parent;
				$max_levels--;
			}

			$location->arr_parents = array_reverse($arr_parents);
		}
		return $location->arr_parents;
	}

	/**
	 * check if parent is not creating closed loop to self and exists 
	 * 
	 * @param int $current_id
	 * @param int $parent_id
	 * @return int
	 */
	public static function checkPossibleParent($current_id, $parent_id)
	{
		$current_id = intval($current_id);

		if ($current_id == $parent_id)
		{
			// cannot be parent of self 
			Benchmark::cp('checkPossibleParent:cannot be parent of self:c' . $current_id . ':p' . $parent_id . ':r0');
			return 0;
		}

		// check if parent exists in DB
		$parent = self::getLocationFromTree($parent_id);
		if (!$parent)
		{
			// parent not found return root id 0
			Benchmark::cp('checkPossibleParent:parent not found return root id 0:c' . $current_id . ':p' . $parent_id . ':r0');
			return 0;
		}

		// check if parent is in self children
		if ($current_id > 0)
		{
			$current = self::getLocationFromTree($current_id);
			// is proposed parent actually child of current
			if (self::isChildOf($current, $parent))
			{
				// closed loop detected  return rooot id 0
				Benchmark::cp('checkPossibleParent:closed loop detected  return rooot id 0:c' . $current_id . ':p' . $parent_id . ':r0');
				return 0;
			}
		}

		// parent id possible return it.
		return $parent_id;
	}

	/**
	 * check if location is child of parent_location
	 * 
	 * @param Location $parent_location
	 * @param Location $location
	 * @param type $status
	 * @return boolean 
	 */
	public static function isChildOf($parent_location, $location, $status = 'all')
	{
		self::getParents($location, $status);
		if ($location->arr_parents)
		{
			foreach ($location->arr_parents as $l)
			{
				if ($parent_location->id == $l->id)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get full name for location with parents 
	 * @param Location $location
	 * @param string $empty_value
	 * @param string $seperator
	 * @return string 
	 */
	public static function getFullName($location = 0, $empty_value = '', $seperator = ' &raquo; ', $reverse = false)
	{
		// get full name to category with parents
		if (!$location)
		{
			return $empty_value;
		}

		self::getParents($location);

		foreach ($location->arr_parents as $p)
		{
			$return[] = View::escape(Location::getName($p));
		}

		$return[] = View::escape(Location::getName($location));

		if ($reverse)
		{
			$return = array_reverse($return);
		}

		return implode($seperator, $return);
	}

	public static function getFullNameById($location_id = 0, $empty_value = '', $seperator = ' &raquo; ', $reverse = false)
	{
		$location = Location::getLocationFromTree($location_id);

		return self::getFullName($location, $empty_value, $seperator, $reverse);
	}

	/**
	 * get sublocation ids. used for listing all ads related to given location
	 *
	 * @param type $location_id 
	 */
	public static function getSublocationIds($location_id, & $return = array())
	{
		$locations_tree = self::getAllLocationNamesTree();

		// add self id
		$return[$location_id] = $location_id;

		if (isset($locations_tree[$location_id]))
		{
			// add sub id
			foreach ($locations_tree[$location_id] as $loc)
			{
				self::getSublocationIds($loc->id, $return);
			}
		}
		return $return;
	}

	public static function urlById($location_id = null, $category_id = null, $user = null, $page = null)
	{
		$location = Location::getLocationFromTree($location_id);
		$category = Category::getCategoryFromTree($category_id);

		return self::url($location, $category, $user, $page);
	}

	public static function url($location = null, $category = null, $user = null, $page = null, $extra = '')
	{
		return Language::get_url(self::urlOrigin($location, $category, $user, $page, $extra));
	}

	public static function urlOrigin($location = null, $category = null, $user = null, $page = null, $extra = '')
	{
		$url = '';
		$url_loc = '';
		$url_cat = '';

		// get url for given user 
		if (isset($user->username))
		{
			$url .= $user->username . '/';
		}

		// get url for given location 
		if (isset($location->slug))
		{
			$url_loc = $location->slug . '/';
		}

		// get url for given category
		if (isset($category->slug))
		{
			$url_cat = $category->slug . '/';
		}

		// decide if permalinks location/category or category/location
		$listing_permalinks = Config::option('listing_permalinks');
		switch ($listing_permalinks)
		{
			case 'cat_loc':
				$url .= $url_cat . $url_loc;
				break;
			case 'loc_cat':
			default:
				// this is default structure for versions prior and including 1.3.2
				$url .= $url_loc . $url_cat;
		}

		$url .= $extra;

		// add page 
		if (isset($page) && strlen($page) && $page != 1 && $page !== 0)
		{
			$url .= View::escape($page) . '/';
		}

		return $url;
	}

	/**
	 * Load location from already loaded location tree to reduce DB queries
	 * 
	 * @param int $id
	 * @param string $status
	 * @return Location
	 */
	public static function getLocationFromTree($id, $status = 'all')
	{
		self::getAllLocationNamesTree($status);

		return self::$arr_tree_reverse[$status][$id];
	}

	function updateDescription()
	{
		// save description if set
		if ($this->LocationDescription)
		{

			// optimize to insert in one query 
			/*
			  INSERT INTO cb_location_description (location_id, language_id, name, description)
			  VALUES
			  ('916', 'en', 'cat1.26', ''),
			  ('916', 'ru', 'cat1.26', ''),
			  ('916', 'tr', 'cat1.26', '')
			  ON DUPLICATE KEY UPDATE
			  name = VALUES(name),
			  description = VALUES(description)
			  ;

			 */

			$arr_where = array();
			$values = array();

			foreach ($this->LocationDescription as $cd)
			{
				// fix name and description 
				LocationDescription::fixNameDescription($cd);
				$cd->location_id = $this->id;

				$arr_where[] = "(?, ?, ?, ?)";
				// location_id
				$values[] = $cd->location_id;
				// language_id
				$values[] = $cd->language_id;
				$values[] = $cd->name;
				$values[] = $cd->description;


				// delete description first
				//LocationDescription::deleteWhere('LocationDescription', 'location_id=? AND language_id=?', array($cd->location_id, $cd->language_id));
				//$cd->save('new_id');
			}

			// set new values 
			$sql = "INSERT INTO " . LocationDescription::tableNameFromClassName('LocationDescription') . " 
						(location_id, language_id, name, description)
					VALUES " . implode(', ', $arr_where) . "  
					ON DUPLICATE KEY UPDATE
						name = VALUES(name),
						description = VALUES(description)";
			LocationDescription::query($sql, $values);
		}

		return true;
	}

	function setFromData($data)
	{
		parent::setFromData($data);

		unset($this->LocationDescription);

		if ($this->location_description)
		{
			foreach ($this->location_description as $lng => $cd)
			{
				// add new description 
				$cd['location_id'] = $this->id;
				$cd['language_id'] = $lng;
				$this->LocationDescription[$lng] = new LocationDescription($cd);
			}
		}

		unset($this->location_description);
	}

	public static function getName($location)
	{
		if (!isset($location->LocationDescription))
		{
			$locations = self::getAllLocationNamesTree('all', true);
			if (isset($locations[$location->id]->LocationDescription))
			{
				$location->LocationDescription = $locations[$location->id]->LocationDescription;
			}
			else
			{
				self::appendAll($location);
			}
		}
		return $location->LocationDescription->name;
	}

	public static function getNameById($id)
	{
		return self::getName(self::getLocationFromTree($id));
	}

	public static function getDescription($location)
	{
		if ($location && !isset($location->LocationDescription->description))
		{
			self::appendAll($location);
		}
		return $location->LocationDescription->description;
	}

	public static function getNameByLng($location, $lng = '')
	{
		if (!strlen($lng))
		{
			$lng = Language::getDefault();
		}

		return $location->LocationDescription[$lng]->name;
	}

	public static function getDescriptionByLng($location, $lng = '')
	{
		if (!strlen($lng))
		{
			$lng = Language::getDefault();
		}
		return $location->LocationDescription[$lng]->description;
	}

	public static function appendLocation($records, $field = 'location_id')
	{
		$records = Record::checkMakeArray($records);

		// append location from tree
		foreach ($records as $r)
		{
			$r->Location = Location::getLocationFromTree($r->{$field});
		}
	}

	public static function appendWithDescription($records, $field = 'location_id')
	{
		$records = Record::checkMakeArray($records);

		self::appendLocation($records, $field);

		// appedn names
		$arr_rec = array();
		foreach ($records as $r)
		{
			$arr_rec[] = $r->Location;
		}

		// append Location description
		Location::appendAll($arr_rec);
	}

	/**
	 * get last positin in parent. 
	 * used to add item to the end of list.
	 * 
	 * @param int $parent_id
	 * @return int
	 */
	public static function getLastPosition($parent_id = 0)
	{
		$parent_id = intval($parent_id);

		// check if location tree loaded use it
		if (isset(self::$arr_tree[self::STATUS_ALL][$parent_id]))
		{
			$last = end(self::$arr_tree[self::STATUS_ALL][$parent_id]);
		}
		else
		{
			$last = self::findOneFrom('Location', 'parent_id=? ORDER BY pos DESC', array($parent_id));
		}

		return intval($last->pos);
	}

	public static function appendAdCount($locations, $category = null)
	{
		if ($locations)
		{
			foreach ($locations as $l)
			{
				$l->countAds = AdCategoryCount::getCountLocation($l->id, $category->id);
			}
		}
	}

	/**
	 * Check if has children with given status 
	 * 
	 * @param Category $location
	 * @param string $status
	 * @return bool 
	 */
	public static function hasChildren($location, $status = 'all')
	{
		$arr_tree = self::getAllLocationNamesTree($status);
		return isset($arr_tree[$location->id]);
	}

	/**
	 * check if posting to this location available.
	 * 
	 * @param Location $location
	 * @return bool 
	 */
	public static function isPostingAvailable($location)
	{
		$return = self::hasChildren($location, Location::STATUS_ENABLED) ? false : true;

		return $location->enabled && $return;
	}

	/**
	 * check if posting to this location available.
	 * 
	 * @param int $location_id
	 * @return bool 
	 */
	public static function isPostingAvailableById($location_id)
	{
		if ($location_id == 0)
		{
			return self::hasValidPostingLocations() ? false : true;
		}

		$location = Location::getLocationFromTree($location_id);

		if ($location)
		{
			return self::isPostingAvailable($location);
		}
		return false;
	}

	/**
	 * count all subs recursively 
	 * 
	 * @param Location $location
	 * @param string $status
	 * @return int 
	 */
	public static function countSubs($location, $status = 'all')
	{
		// load all categories 
		self::getLocations($status);
		$return = 0;
		if (isset(self::$arr_tree[$status][$location->id]))
		{
			$return = count(self::$arr_tree[$status][$location->id]);
			foreach (self::$arr_tree[$status][$location->id] as $c)
			{
				$return += self::countSubs($c, $status);
			}
		}

		return $return;
	}

	/**
	 * find location by name 
	 * 
	 * @param string $name
	 * @param int $parent_id
	 * @param string $lng
	 * @return Location 
	 */
	public static function findByName($name, $parent_id = null, $lng = null)
	{
		// find category by title
		if (!$lng)
		{
			$lng = Language::getDefault();
		}

		if (is_null($parent_id))
		{
			$where = "cd.name=? AND cd.language_id=?";
			$vals = array($name, $lng);
		}
		else
		{
			$where = "cd.name=? AND cd.language_id=? AND c.parent_id=?";
			$vals = array($name, $lng, intval($parent_id));
		}

		$sql = "SELECT c.* 
			FROM " . Location::tableNameFromClassName('Location') . " c 
			LEFT JOIN " . LocationDescription::tableNameFromClassName('LocationDescription') . " cd ON(c.id=cd.location_id)
				WHERE " . $where;

		$locations = Location::query($sql, $vals);
		if ($locations)
		{
			return $locations[0];
		}

		return false;
	}

	/**
	 * 
	 * Import items from given string 
	 * Separator new line and |
	 * Break inserting if execution time is more than max_time, return 'string_left'=>$str_left
	 * 
	 * @param string $str
	 * @return array
	 */
	public static function importString($str)
	{
		$return = array();

		/** $str :
		 * Category1
		  Category1|Sub1
		  Category1|Sub2
		  Category1|Sub2|Subsub1
		  Category2
		  ...
		 */
		// set as bulk insert to prevent location cache clearing
		// clear first and last 
		self::_clearCache();
		self::$bulk_insert = true;

		// run for 10 s. if no tfinished then import ni batches of 10 seconds 
		$max_run_time = 10;


		$lines = explode("\n", $str);
		$count = 0;
		foreach ($lines as $key => $line)
		{
			$line = trim($line);
			if (!strlen($line))
			{
				// empty line
				continue;
			}

			$location_path = explode("|", $line);

			// insert location if not exists
			$location = self::checkMakeByName($location_path);
			if ($location)
			{
				$count++;
			}

			if (Benchmark::totalTime() > $max_run_time)
			{
				// return left lines 
				$lines = array_slice($lines, $key);
				if (count($lines) > 1)
				{
					$return['string_left'] = implode("\n", $lines);
				}
				// break here 
				break;
			}
		}

		unset($lines);

		// clear cache after bulk insert as well 
		self::$bulk_insert = false;
		self::_clearCache();

		// perform other skipped actions because of bulk upload
		AdCategoryCount::autoDisbaleAdCount();

		$return['count'] = $count;

		return $return;
	}

	public static function nextAutoId()
	{
		// use tree when possible 
		if (isset(self::$arr_tree_reverse[self::STATUS_ALL]) && count(self::$arr_tree_reverse[self::STATUS_ALL]))
		{
			$max = max(array_keys(self::$arr_tree_reverse[self::STATUS_ALL]));
		}
		else
		{
			$latest_cat = Location::findOneFrom('Location', '1=1 ORDER BY id desc', array(), MAIN_DB, 'id');
			$max = $latest_cat->id;
		}

		return $max + 1;
	}

	/**
	 * search for category with given name. if not found then create new category under given parent
	 * 
	 * @param string $location_path string or array('category','sub','subsub',... )  like breadcrumb
	 * @param int $parent_id
	 * @return Location | false
	 */
	public static function checkMakeByName($location_path, $parent_id = 0)
	{
		$location_path = Record::checkMakeArray($location_path);

		$count = 0;
		$location = false;

		$languages = Language::getLanguages();

		foreach ($location_path as $name)
		{
			$name = trim($name);
			if (strlen($name) < 1)
			{
				// skip empty values 
				continue;
			}

			if (!isset(self::$arr_locs_by_name[$parent_id][$name]))
			{
				$location = Location::findByName($name, $parent_id);

				if (!$location)
				{
					// add category 
					$location = new Location();
					$location->parent_id = $parent_id;

					foreach ($languages as $lng)
					{
						$cd = new LocationDescription();
						$cd->language_id = $lng->id;
						$cd->name = $name;

						$location->LocationDescription[$lng->id] = $cd;
					}
					$location->save();

					// location saved add to memory location with current lng name
					// set same variables used in self::appendName($locations) -> location_id,language_id,name
					// $ld_arr = $location->LocationDescription;
					$location->LocationDescription = new LocationDescription();
					$location->LocationDescription->location_id = $location->id;
					$location->LocationDescription->language_id = I18n::getLocale();
					$location->LocationDescription->name = $name;
					self::getAllLocationNamesTreeUpdate($location);

					// increase added record count 
					$count++;
				}
				self::$arr_locs_by_name[$parent_id][$name] = $location;
			}
			$location = self::$arr_locs_by_name[$parent_id][$name];

			$parent_id = $location->id;
		}

		return $location;
	}

	public static function replaceVariables($str, $location = null)
	{
		$var = '{@LOCATION_OR_SITETITLE}';
		if (strpos($str, $var) !== false)
		{
			if ($location)
			{
				$values[$var] = View::escape(Location::getName($location));
			}
			else
			{
				$values[$var] = View::escape(Config::option('site_title'));
			}
			$str = str_replace(array_keys($values), array_values($values), $str);
		}

		return $str;
	}

	/**
	 * Search location name and description for given query 
	 * 
	 * @param string $q
	 * @param int $limit
	 * @return array of Category object
	 */
	public static function search($q, $limit = 5)
	{
		// search given query in category name and description 


		$whereA = array();
		$whereB = array();
		$cat = array();

		$arr_q = Ad::searchQuery2Array($q);

		foreach ($arr_q as $_q)
		{
			$whereA[] = '(cd.name LIKE ? OR cd.description LIKE ? OR c.slug LIKE ?)';
			$whereB[] = '%' . $_q . '%';
			$whereB[] = '%' . $_q . '%';
			$whereB[] = '%' . $_q . '%';
		}


		if ($whereA)
		{

			$limit = intval($limit);
			$limit_sql = '';
			if ($limit > 0)
			{
				$limit_sql = " LIMIT " . $limit;
			}

			/*
			  SELECT * FROM cb_category c LEFT JOIN  cb_category_description cd on c.id=cd.category_id
			  WHERE cd.name LIKE '%kvar%' OR cd.description LIKE '%kvar%' OR c.slug LIKE '%kvar%'
			  GROUP BY c.id; */
			$sql = "SELECT c.* 
					FROM " . Location::tableNameFromClassName('Location') . " c 
					LEFT JOIN  " . LocationDescription::tableNameFromClassName('LocationDescription') . " cd on c.id=cd.location_id
				  WHERE c.enabled = 1 AND {where} 
				  GROUP BY c.id
				  " . $limit_sql;

			$sql = str_replace('{where}', implode(' AND ', $whereA), $sql);

			$cat = Location::query($sql, $whereB);
		}

		return $cat;
	}

	/**
	 * Delete cache and update json version
	 */
	private static function _clearCache()
	{
		// prevent clearing if it is bulk insert 
		if (self::$bulk_insert)
		{
			return false;
		}

		// delete category cache
		SimpleCache::delete('locations');

		// clear local location trees 
		self::$arr_tree = null;
		self::$arr_tree_reverse = array();

		// update json version to request updated location data
		$json_version = Config::option('json_version');
		if ($json_version != REQUEST_TIME)
		{
			Config::optionSet('json_version', REQUEST_TIME);
		}

		return true;
	}

}
