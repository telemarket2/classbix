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
 * class Category
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Category extends Record
{

	const TABLE_NAME = 'category';
	const STATUS_ENABLED = 'enabled';
	const STATUS_ENABLED_NOTLOCKED = 'enabled_notlocked';
	const STATUS_ALL = 'all';

	static private $arr_tree;
	static private $arr_tree_reverse = array();
	static private $arr_cats_by_name = array();
	static private $bulk_insert = false;
	static private $obj_all;
	static private $cols = array(
		'id'		 => 1,
		'parent_id'	 => 1,
		'slug'		 => 1,
		'pos'		 => 1,
		'locked'	 => 1,
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
		unset($this->CategoryDescription);
		unset($this->arr_parents);
	}

	function beforeInsert()
	{
		// fix slug
		if (!is_null(self::getNameByLng($this)))
		{
			$this->slug = Permalink::generateSlug($this->slug, self::getNameByLng($this), $this->id, Permalink::ITEM_TYPE_CATEGORY);
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
			$this->locked = 0;
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
			$this->slug = Permalink::generateSlug($this->slug, self::getNameByLng($this), $this->id, Permalink::ITEM_TYPE_CATEGORY);
		}

		// check if it cant be locked then ulock it
		if (!self::canBeLocked($this))
		{
			$this->locked = 0;
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
		if (!Permalink::savePermalinkWithObject($this, Permalink::ITEM_TYPE_CATEGORY, self::getNameByLng($this)))
		{
			// error saving permalink
			return false;
		}

		// check parent if this is only child and parent is not locked lock it
		if ($this->parent_id)
		{

			// use tree when possible
			if (isset(self::$arr_tree[self::STATUS_ALL][$this->parent_id]))
			{
				$count_parent = count(self::$arr_tree[self::STATUS_ALL][$this->parent_id]);
			}
			else
			{
				$count_parent = Category::countFrom('Category', 'parent_id=?', array($this->parent_id));
			}

			// lock only when detected first child of parent 
			// to prevent locking continusly and changing manually not locked categories 
			if ($count_parent == 1)
			{
				$locked = false;
				if (isset(self::$arr_tree_reverse[self::STATUS_ALL][$this->parent_id]) && self::$arr_tree_reverse[self::STATUS_ALL][$this->parent_id]->locked != 1)
				{
					if (self::$arr_tree_reverse[self::STATUS_ALL][$this->parent_id]->locked != 1)
					{
						// lock in memory
						self::$arr_tree_reverse[self::STATUS_ALL][$this->parent_id]->locked = 1;
					}
					else
					{
						$locked = false;
					}
				}

				if (!$locked)
				{
					// lock parent category automaticly 
					Category::update('Category', array('locked' => 1), 'id=?', array($this->parent_id));
				}
			}
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
		if (!Permalink::savePermalinkWithObject($this, Permalink::ITEM_TYPE_CATEGORY, self::getNameByLng($this)))
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
		// delete subcategories
		// get all subcategories. 
		// Use DB query, do not use cahce because in cache cleaned stdCasls object instead of categories calss
		$sub = Category::findAllFrom('Category', 'parent_id=?', array($this->id));
		foreach ($sub as $c)
		{
			$c->delete('id');
		}

		// delete descriptions
		CategoryDescription::deleteWhere('CategoryDescription', 'category_id=?', array($this->id));

		// delete custom field relation 
		CategoryFieldRelation::deleteWhere('CategoryFieldRelation', 'category_id=?', array($this->id));

		// delete slug 
		Permalink::deleteWhere('Permalink', 'item_id=? AND item_type=?', array($this->id, Permalink::ITEM_TYPE_CATEGORY));

		// delete payment price
		PaymentPrice::deleteWhere('PaymentPrice', 'category_id=?', array($this->id));

		return true;
	}

	/**
	 * get categories 
	 * @param string $status 
	 * @return array of object
	 */
	public static function getCategories($status = 'enabled')
	{
		return self::getAllCategoryNamesTree($status, true);
	}

	/**
	 * Move position of current category up or down by one
	 * @param type $id
	 * @param type $dir direction up, down
	 * @return type bool 
	 */
	public static function changePosition($id, $dir)
	{
		$id = intval($id);

		// get requested category 
		$category = self::findByIdFrom('Category', $id);
		if (!$category)
		{
			return false;
		}

		// get all categories in position order
		$categories = Category::findAllFrom('Category', 'parent_id=? ORDER BY pos', array($category->parent_id));

		$found = false;
		$arr = array();
		$i = 1;
		foreach ($categories as $c)
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


		$total = count($categories);
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
				Category::update('Category', array('pos' => $i), 'id=?', array($id));
				$i++;
				$updated = true;
			}
			Category::update('Category', array('pos' => $i), 'id=?', array($v));
			$i++;
		}

		if (!$updated)
		{
			Category::update('Category', array('pos' => $i), 'id=?', array($id));
		}

		self::_clearCache();
		;

		return intval($category->parent_id);
	}

	public static function selectBox($selected_id = 0, $name = 'parent_id', $status = 'all', $display_root = true, $root_title = '', $max_level = 0, $posting = false)
	{
		// get all categories
		$categories = self::getAllCategoryNamesTree($status);

		if ($posting)
		{
			$options = self::_selectBoxLvlPosting($categories);
		}
		else
		{
			$options = self::_selectBoxLvl($categories, 0, 0, $max_level);
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
	 * Get all categories as javascript object for later generating chain select
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
		$all_categories = Category::getAllCategoryNamesTree($status);

		// display chain if more than one level
		if (count($all_categories) > 1)
		{
			$return_arr = array();
			$total_items = 0;
			foreach ($all_categories as $parent_id => $categories)
			{
				foreach ($categories as $category)
				{
					$return_arr['parent_' . $parent_id]['id_' . $category->id] = $category->CategoryDescription->name;
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
	 * Get all categories as json.
	 * 
	 * @param string $status
	 * @return string javascript
	 */
	public static function ____categoriesJson_DELETE($status = 'all')
	{
		// get all locations for javascript
		$all_categories = Category::getAllCategoryNamesTree($status);

		// display chain if more than one level
		if (count($all_categories) > 1)
		{
			$return_arr = array();
			$total_items = 0;
			foreach ($all_categories as $parent_id => $categories)
			{
				foreach ($categories as $category)
				{
					$return_arr['parent_' . $parent_id]['id_' . $category->id] = $category->CategoryDescription->name;
					$total_items++;
				}
			}
			return Config::arr2js($return_arr);
		}
		return '';
	}

	/**
	 * 
	 * Custom order function to order categories by name. 
	 * 
	 * Usage: usort($categories, array('Category', 'cmpName'));
	 * 
	 * @param Category $a
	 * @param Category $b
	 * @return int
	 */
	public static function cmpName($a, $b)
	{
		return strcmp($a->CategoryDescription->name, $b->CategoryDescription->name);
	}

	/**
	 * Get all category names and parent_id for tree view
	 * 
	 * @return type 
	 */
	public static function getAllCategoryNamesTree($status = 'all', $reverse = false)
	{
		if (!isset(self::$arr_tree))
		{
			// create empty values if no categories found
			self::$arr_tree = array();
			self::$arr_tree[self::STATUS_ALL] = array();
			self::$arr_tree[self::STATUS_ENABLED] = array();
			self::$arr_tree[self::STATUS_ENABLED_NOTLOCKED] = array();
			self::$arr_tree_reverse = array();
			self::$arr_tree_reverse[self::STATUS_ALL] = array();
			self::$arr_tree_reverse[self::STATUS_ENABLED] = array();
			self::$arr_tree_reverse[self::STATUS_ENABLED_NOTLOCKED] = array();

			$cache_key = 'categories.' . I18n::getLocale();

			$categories = SimpleCache::get($cache_key);
			if ($categories === false)
			{
				// geal all category names and parent id for tree view
				$categories = Category::findAllFrom('Category', '1=1 ORDER BY parent_id,pos,id', array(), MAIN_DB, 'id,parent_id,slug,enabled,locked');
				self::appendName($categories);

				SimpleCache::set($cache_key, $categories, 86400); //24 hours
			}

			foreach ($categories as $c)
			{
				self::getAllCategoryNamesTreeUpdate($c);
			}
			unset($categories);
		}


		if ($reverse)
		{
			return self::$arr_tree_reverse[$status];
		}

		return self::$arr_tree[$status];
	}

	/**
	 * Add category to memory. 
	 * Added when read from database or cache
	 * Added when added new category in batch insert
	 * 
	 * @param stdClass $record is stdClass:if from cache, category if from DB or New category
	 */
	private static function getAllCategoryNamesTreeUpdate($record)
	{
		// add only if record id >0
		if ($record->id > 0)
		{
			// tree not loaded
			if (!isset(self::$arr_tree))
			{
				// load tree first 
				self::getAllCategoryNamesTree();
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

				if ($record->locked == 0)
				{
					if (!isset(self::$arr_tree[self::STATUS_ENABLED_NOTLOCKED][$record->parent_id]))
					{
						self::$arr_tree[self::STATUS_ENABLED_NOTLOCKED][$record->parent_id] = array();
					}
					self::$arr_tree[self::STATUS_ENABLED_NOTLOCKED][$record->parent_id][] = $record;
					self::$arr_tree_reverse[self::STATUS_ENABLED_NOTLOCKED][$record->id] = $record;
				}
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
		// generate tree
		Category::getAllCategoryNamesTree();

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
			$undefined = Category::checkMakeByName('undefined');

			// make sure it is disabled 
			if ($undefined->enabled || !isset($undefined->enabled))
			{
				// enabled 
				$undefined->enabled = 0;
				Category::update('Category', array('enabled' => 0), 'id=?', array($undefined->id));
			}

			$arr_no_parent_ = Record::quoteArray(array_keys($arr_no_parent));

			// move all records with no parent to $undefined 
			Category::update('Category', array('parent_id' => $undefined->id), 'parent_id IN (' . implode(',', $arr_no_parent_) . ')', array());

			// clear cache 
			Category::_clearCache();
		}
	}

	public static function appendName($categories)
	{
		CategoryDescription::appendObject($categories, 'id', 'CategoryDescription', 'category_id', '', MAIN_DB, 'category_id,language_id,name', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
	}

	public static function appendAll($categories)
	{
		CategoryDescription::appendObject($categories, 'id', 'CategoryDescription', 'category_id', '', MAIN_DB, '*', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
	}

	/**
	 * render a ul list displaying categories as tree
	 * 
	 * @param array $categories
	 * @param int $parent_id
	 * @param string $pattern
	 * @param string $wrap_pattern
	 * @return string html
	 */
	public static function htmlCategoryTree(& $categories, $parent_id = 0, $pattern = '<li>{name}{sub}</li>', $wrap_pattern = '<ul>{tree}</ul>', $selected_location = null)
	{
		$tree = '';
		$arr_search = array('{url}', '{name}', '{sub}');
		if (isset($categories[$parent_id]))
		{
			foreach ($categories[$parent_id] as $c)
			{
				$arr_replace = array(
					self::url($c, $selected_location),
					self::getName($c),
					self::htmlCategoryTree($categories, $c->id, $pattern, $wrap_pattern, $selected_location)
				);
				$tree .= str_replace($arr_search, $arr_replace, $pattern);
			}
			$tree = str_replace('{tree}', $tree, $wrap_pattern);
		}

		return $tree;
	}

	/**
	 * generate category tree for info only, used in admin only
	 * Used when deleting category with sub.
	 * If category has too many sub (1000+) then delete page will load very slow. 
	 * Show only 100 first degree subs, 10 from other degrees. 
	 * Add (+54 more) for truncated values 
	 * 
	 * @param array $categories
	 * @param int $parent_id
	 * @return string
	 */
	public static function htmlCategoryTreeTruncated($categories, $parent_id = 0)
	{
		// scan given locations array and reduce subs
		$categories_truncated = array();
		$tolerate = 5;

		foreach ($categories as $key => $arr)
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
				$last->CategoryDescription->name .= ' (' . __('{num} more', array('{num}' => '+' . ($cnt - $num))) . ')';
			}
			$categories_truncated[$key] = $arr;
		}

		return Category::htmlCategoryTree($categories_truncated, $parent_id);
	}

	private static function _selectBoxLvl(& $arr, $parent_id = 0, $level = 0, $max_level = 0)
	{
		$return = '';
		if (isset($arr[$parent_id]) && ($max_level == 0 || $level < $max_level))
		{
			$level_str = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
			foreach ($arr[$parent_id] as $c)
			{
				$return .= '<option value="' . $c->id . '">' . $level_str . View::escape(Category::getName($c)) . '</option>'
						. self::_selectBoxLvl($arr, $c->id, $level + 1, $max_level);
			}
		}
		return $return;
	}

	/**
	 * Populate select box options, display full name without leveling
	 * 
	 * @param type $arr
	 * @param int $parent_id
	 * @return string
	 */
	private static function _selectBoxLvlPosting(& $arr, $parent_id = 0)
	{
		$return = '';
		if (isset($arr[$parent_id]))
		{
			foreach ($arr[$parent_id] as $c)
			{
				if (self::isPostingAvailable($c))
				{
					$return .= '<option value="' . $c->id . '">' . Category::getFullName($c) . '</option>';
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
	public static function getParents($category, $status = 'all')
	{
		if (!$category)
		{
			return array();
		}

		if (!isset($category->arr_parents))
		{
			// get parents
			// to prevent infinite loop use max levels
			$max_levels = 100;
			$parent = $category;
			$arr_parents = array();

			self::getAllCategoryNamesTree($status);

			$category->arr_parents = array();
			while ($parent && $parent->parent_id && $max_levels)
			{
				//Category::appendObject($parent, 'parent_id', 'Category', 'id', 'parentCategory', 'master', 'id,name,parent_id');
				//$parent = $parent->parentCategory;
				$parent = self::$arr_tree_reverse[$status][$parent->parent_id];
				$arr_parents[] = $parent;
				$max_levels--;
			}

			$category->arr_parents = array_reverse($arr_parents);
		}

		return $category->arr_parents;
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
			return 0;
		}

		// check if parent exists in DB
		$parent = self::getCategoryFromTree($parent_id);
		if (!$parent)
		{
			// parent not found return root id 0
			return 0;
		}

		// check if parent is in self children
		if ($current_id > 0)
		{
			$current = self::getCategoryFromTree($current_id);
			// is proposed parent actually child of current
			if (self::isChildOf($current, $parent))
			{
				// closed loop detected  return rooot id 0
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
	public static function isChildOf($parent_category, $category, $status = 'all')
	{
		self::getParents($category, $status);
		if ($category->arr_parents)
		{
			foreach ($category->arr_parents as $l)
			{
				if ($parent_category->id == $l->id)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if has children with given status 
	 * 
	 * @param Category $category
	 * @param string $status
	 * @return bool 
	 */
	public static function hasChildren($category, $status = 'all')
	{
		$arr_tree = self::getAllCategoryNamesTree($status);
		return isset($arr_tree[$category->id]);
	}

	/**
	 * Get full name for category with parents 
	 * @param Category $category
	 * @param string $empty_value
	 * @param string $seperator
	 * @return string 
	 */
	public static function getFullName($category = 0, $empty_value = '', $seperator = ' &raquo; ', $reverse = false)
	{
		// get full name to category with parents
		if (!$category)
		{
			return $empty_value;
		}

		self::getParents($category);

		foreach ($category->arr_parents as $p)
		{
			$return[] = View::escape(Category::getName($p));
		}

		$return[] = View::escape(Category::getName($category));

		if ($reverse)
		{
			$return = array_reverse($return);
		}

		return implode($seperator, $return);
	}

	public static function getFullNameById($category_id = 0, $empty_value = '', $seperator = ' &raquo; ', $reverse = false)
	{
		$category = Category::getCategoryFromTree($category_id);

		return self::getFullName($category, $empty_value, $seperator, $reverse);
	}

	/**
	 * get subcategory ids. used for listing all ads related to given category
	 *
	 * @param type $category_id 
	 */
	public static function getSubcategoryIds($category_id, & $return = array())
	{
		$category_tree = self::getAllCategoryNamesTree();

		$return[$category_id] = $category_id;

		if (isset($category_tree[$category_id]))
		{
			foreach ($category_tree[$category_id] as $loc)
			{
				self::getSubcategoryIds($loc->id, $return);
			}
		}
		return $return;
	}

	public static function getCategoryFromTree($id, $status = 'all')
	{
		self::getAllCategoryNamesTree($status);

		return self::$arr_tree_reverse[$status][$id];
	}

	function updateDescription()
	{
		// save description if set
		if ($this->CategoryDescription)
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

			foreach ($this->CategoryDescription as $cd)
			{
				// fix name and description 
				CategoryDescription::fixNameDescription($cd);
				$cd->category_id = $this->id;

				$arr_where[] = "(?, ?, ?, ?)";
				// location_id
				$values[] = $cd->category_id;
				// language_id
				$values[] = $cd->language_id;
				$values[] = $cd->name;
				$values[] = $cd->description;


				// delete description first
				// LocationDescription::deleteWhere('LocationDescription', 'location_id=? AND language_id=?', array($cd->location_id, $cd->language_id));
				// $cd->save('new_id');
			}

			// set new values 
			$sql = "INSERT INTO " . CategoryDescription::tableNameFromClassName('CategoryDescription') . " 
						(category_id, language_id, name, description)
					VALUES " . implode(', ', $arr_where) . "  
					ON DUPLICATE KEY UPDATE
						name = VALUES(name),
						description = VALUES(description)";
			CategoryDescription::query($sql, $values);
		}

		return true;
	}

	function setFromData($data)
	{
		parent::setFromData($data);

		unset($this->CategoryDescription);

		if ($this->category_description)
		{
			foreach ($this->category_description as $lng => $cd)
			{
				// add new description 
				$cd['category_id'] = $this->id;
				$cd['language_id'] = $lng;
				$this->CategoryDescription[$lng] = new CategoryDescription($cd);
			}
		}

		unset($this->category_description);
	}

	public static function getName($category)
	{
		if (!isset($category->CategoryDescription))
		{
			$categories = self::getAllCategoryNamesTree('all', true);
			if (isset($categories[$category->id]->CategoryDescription))
			{
				$category->CategoryDescription = $categories[$category->id]->CategoryDescription;
			}
			else
			{
				self::appendAll($category);
			}
		}

		return $category->CategoryDescription->name;
	}

	public static function getNameById($id)
	{
		return self::getName(self::getCategoryFromTree($id));
	}

	public static function getDescription($category)
	{
		if ($category && !isset($category->CategoryDescription->description))
		{
			self::appendAll($category);
		}
		return $category->CategoryDescription->description;
	}

	public static function getNameByLng($category, $lng = '')
	{
		if (!strlen($lng))
		{
			$lng = Language::getDefault();
		}

		return $category->CategoryDescription[$lng]->name;
	}

	public static function getDescriptionByLng($category, $lng = '')
	{
		if (!strlen($lng))
		{
			$lng = Language::getDefault();
		}
		return $category->CategoryDescription[$lng]->description;
	}

	public static function appendCategory($records, $field = 'category_id')
	{
		$records = Record::checkMakeArray($records);

		// append location from tree
		foreach ($records as $r)
		{
			$r->Category = Category::getCategoryFromTree($r->{$field});
		}
	}

	public static function appendWithDescription($records, $field = 'category_id')
	{
		self::appendCategory($records, $field);

		// append names
		$arr_rec = array();
		foreach ($records as $r)
		{
			$arr_rec[] = $r->Category;
		}

		// append category description
		Category::appendAll($arr_rec);
	}

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
			$last = self::findOneFrom('Category', 'parent_id=? ORDER BY pos DESC', array($parent_id));
		}


		return intval($last->pos);
	}

	public static function appendAdCount($categories, $location = null)
	{
		if ($categories)
		{
			foreach ($categories as $c)
			{
				$c->countAds = AdCategoryCount::getCountCategory($location->id, $c->id);
			}
		}
	}

	public static function url($category = null, $location = null)
	{
		return Location::url($location, $category);
	}

	/**
	 * check if dont have children this cateogry cannot be locked
	 * 
	 * @param Category $category
	 * @return boolean 
	 */
	public static function canBeLocked($category)
	{
		// check if dont have children then no
		$categories_tree = self::getAllCategoryNamesTree(self::STATUS_ENABLED);

		if (isset($categories_tree[$category->id]) && count($categories_tree[$category->id]) > 0)
		{
			$return = true;
		}
		else
		{
			$return = false;
		}

		return $return;
	}

	/**
	 * if dont have any children then category cannot be locked. unlock it and update db.
	 * 
	 * @param Category $category 
	 */
	public static function checkUnlock($category)
	{
		if ($category->locked && !self::canBeLocked($category))
		{
			$category->locked = 0;

			// save new setting
			$c = new Category();
			$c->locked = 0;
			$c->id = $category->id;
			$c->slug = $category->slug;
			$c->save();
		}
	}

	/**
	 * count all subs recursively 
	 * 
	 * @param Category $category
	 * @param string $status
	 * @return int 
	 */
	public static function countSubs($category, $status = 'all')
	{
		// load all categories 
		self::getCategories($status);
		$return = 0;
		if (isset(self::$arr_tree[$status][$category->id]))
		{
			$return = count(self::$arr_tree[$status][$category->id]);
			foreach (self::$arr_tree[$status][$category->id] as $c)
			{
				$return += self::countSubs($c, $status);
			}
		}

		return $return;
	}

	/**
	 * check if has enabled root category for posting 
	 * @return int 
	 */
	public static function hasValidPostingCategories()
	{
		// get all locations
		$categories = self::getAllCategoryNamesTree(Category::STATUS_ENABLED);
		return count($categories[0]);
	}

	/**
	 * check if posting to this location available.
	 * 
	 * @param Location $category
	 * @return bool 
	 */
	public static function isPostingAvailable($category)
	{
		//$return = self::hasChildren($category, Location::STATUS_ENABLED) ? false : true;
		// check if category truely locked
		self::checkUnlock($category);

		return $category->enabled && !$category->locked;
	}

	/**
	 * check if posting to this location available.
	 * 
	 * @param int $category_id
	 * @return bool 
	 */
	public static function isPostingAvailableById($category_id)
	{
		if ($category_id == 0)
		{
			return self::hasValidPostingCategories() ? false : true;
		}

		$category = Category::getCategoryFromTree($category_id);

		if ($category)
		{
			return self::isPostingAvailable($category);
		}
		return false;
	}

	/**
	 * find category by name
	 * 
	 * @param string $name
	 * @param int $parent_id
	 * @param string $lng
	 * @return Category 
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
			FROM " . Category::tableNameFromClassName('Category') . " c 
			LEFT JOIN " . CategoryDescription::tableNameFromClassName('CategoryDescription') . " cd ON(c.id=cd.category_id)
				WHERE " . $where;

		$categories = Category::query($sql, $vals);
		if ($categories)
		{
			return $categories[0];
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

			$category_path = explode("|", $line);

			// insert category if not exists 
			$category = self::checkMakeByName($category_path);
			if ($category)
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
			$latest_cat = Category::findOneFrom('Category', '1=1 ORDER BY id desc', array(), MAIN_DB, 'id');
			$max = $latest_cat->id;
		}

		return $max + 1;
	}

	/**
	 * search for category with given name. if not found then create new category under given parent
	 * 
	 * @param string $name $category_path or array('category','sub','subsub',... )  like breadcrumb
	 * @param int $parent_id
	 * @return Category | false
	 */
	public static function checkMakeByName($category_path, $parent_id = 0)
	{
		$category_path = Record::checkMakeArray($category_path);

		$count = 0;
		$category = false;

		$languages = Language::getLanguages();

		foreach ($category_path as $name)
		{
			$name = trim($name);
			if (strlen($name) < 1)
			{
				// skip empty values 
				continue;
			}

			if (!isset(self::$arr_cats_by_name[$parent_id][$name]))
			{
				$category = Category::findByName($name, $parent_id);

				if (!$category)
				{
					// add category 
					$category = new Category();
					$category->parent_id = $parent_id;

					foreach ($languages as $lng)
					{
						$cd = new CategoryDescription();
						$cd->language_id = $lng->id;
						$cd->name = $name;

						$category->CategoryDescription[$lng->id] = $cd;
					}
					$category->save();


					// location saved add to memory location with current lng name
					// set same variables used in self::appendName($locations) -> location_id,language_id,name
					// $ld_arr = $location->LocationDescription;
					$category->CategoryDescription = new CategoryDescription();
					$category->CategoryDescription->location_id = $category->id;
					$category->CategoryDescription->language_id = I18n::getLocale();
					$category->CategoryDescription->name = $name;
					self::getAllCategoryNamesTreeUpdate($category);

					// increase added record count 
					$count++;
				}
				self::$arr_cats_by_name[$parent_id][$name] = $category;
			}
			$category = self::$arr_cats_by_name[$parent_id][$name];

			$parent_id = $category->id;
		}

		return $category;
	}

	/**
	 * ger links to parent location and categories. used for empty categories to navigate users to existing ads
	 * 
	 * @param Location $location
	 * @param Category $category
	 * @return array
	 */
	public static function getRelatedPages($location = null, $category = null)
	{
		$parent_links = array();
		if ($category)
		{
			if ($location->arr_parents)
			{
				foreach ($location->arr_parents as $parent_loc)
				{
					$parent_links[Location::url($parent_loc, $category)] = View::escape(Location::getName($parent_loc) . ' - ' . Category::getName($category));
				}
			}

			// display categories
			if ($location)
			{
				$parent_links[Category::url($category)] = View::escape(Category::getName($category));
			}
			if ($category->arr_parents)
			{
				foreach ($category->arr_parents as $parent_cat)
				{
					$parent_links[Category::url($parent_cat)] = View::escape(Category::getName($parent_cat));
				}
			}
		}

		return $parent_links;
	}

	/**
	 * Empty category object for listing all ads regardless category 
	 * 
	 * @return Category
	 */
	public static function objAll()
	{
		if (!isset(self::$obj_all))
		{
			self::$obj_all = new Category();
			self::$obj_all->id = 0;
			self::$obj_all->slug = 'all';
			self::$obj_all->CategoryDescription = new CategoryDescription();
			self::$obj_all->CategoryDescription->name = __('All');
		}

		return self::$obj_all;
	}

	/**
	 * Search category name and description for given query 
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
					FROM " . Category::tableNameFromClassName('Category') . " c 
					LEFT JOIN  " . CategoryDescription::tableNameFromClassName('CategoryDescription') . " cd on c.id=cd.category_id
				  WHERE c.enabled = 1 AND {where} 
				  GROUP BY c.id
				  " . $limit_sql;

			$sql = str_replace('{where}', implode(' AND ', $whereA), $sql);

			$cat = Category::query($sql, $whereB);
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
		SimpleCache::delete('categories');

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
