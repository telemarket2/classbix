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
 * class Page
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Page extends Record
{

	const TABLE_NAME = 'page';
	const STATUS_ENABLED = 'enabled';
	const STATUS_ALL = 'all';

	static private $arr_tree = null;
	static private $arr_tree_reverse = null;
	private static $cols = array(
		'id'		 => 1,
		'parent_id'	 => 1,
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

	function beforeInsert()
	{
		// set initial position 
		$this->pos = self::getLastPosition($this->parent_id) + 1;

		$this->added_at = REQUEST_TIME;
		$this->added_by = AuthUser::$user->id;

		// check if parent is resolvable to root. if not then make parent 0
		if ($this->parent_id > 0)
		{
			$this->parent_id = self::checkPossibleParent($this->id, $this->parent_id);
		}

		return true;
	}

	function beforeUpdate()
	{
		// check if parent is resolvable to root. if not then make parent 0
		if ($this->parent_id > 0)
		{
			$this->parent_id = self::checkPossibleParent($this->id, $this->parent_id);
		}
		return true;
	}

	function afterInsert()
	{
		return $this->updateDescription();
	}

	function afterUpdate()
	{
		return $this->updateDescription();
	}

	function beforeDelete()
	{
		// delete subpages		
		// get all subpages
		$pages_tree = self::getAllPageNamesTree();
		if (isset($pages_tree[$this->id]))
		{
			foreach ($pages_tree[$this->id] as $c)
			{
				$c->delete('id');
			}
		}

		// delete descriptions
		PageDescription::deleteWhere('PageDescription', 'page_id=?', array($this->id));


		return true;
	}

	/**
	 * get pages 
	 * @param string $status 
	 * @return array of object
	 */
	public static function getPages($status = 'enabled')
	{
		return self::getAllPageNamesTree($status, true);
	}

	/**
	 * Load page from loaded tree
	 * 
	 * @param int $id
	 * @param string $status
	 * @return Page
	 */
	public static function getPageFromTree($id, $status = 'all')
	{
		self::getAllPageNamesTree($status);
		return self::$arr_tree_reverse[$status][$id];
	}

	/**
	 * Move position of current page up or down by one
	 * @param type $id
	 * @param type $dir direction up, down
	 * @return type bool 
	 */
	public static function changePosition($id, $dir)
	{
		$id = intval($id);

		// get requested page 
		$page = self::findByIdFrom('Page', $id);
		if (!$page)
		{
			return false;
		}

		// get all pages in position order
		$pages = self::findAllFrom('Page', 'parent_id=? ORDER BY pos', array($page->parent_id));

		$found = false;
		$arr = array();
		$i = 1;
		foreach ($pages as $c)
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


		$total = count($pages);
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
				self::update('Page', array('pos' => $i), 'id=?', array($id));
				$i++;
				$updated = true;
			}
			self::update('Page', array('pos' => $i), 'id=?', array($v));
			$i++;
		}

		if (!$updated)
		{
			self::update('Page', array('pos' => $i), 'id=?', array($id));
		}

		return intval($page->parent_id);
	}

	public static function selectBox($selected_id = 0, $name = 'parent_id', $status = 'all', $display_root = true, $root_title = '', $max_level = 0)
	{
		// get all pages
		$pages = self::getAllPageNamesTree($status);

		$options = self::_selectBoxLvl($pages, 0, 0, $max_level);
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
	 * Get all page names and parent_id for tree view
	 * 
	 * @return type 
	 */
	public static function getAllPageNamesTree($status = 'all', $reverse = false)
	{
		if (!isset(self::$arr_tree))
		{
			// geal all page names and parent id for tree view
			$pages = self::findAllFrom('Page', '1=1 ORDER BY parent_id,pos,id', array(), MAIN_DB, 'id,parent_id,enabled');

			self::appendName($pages);

			self::$arr_tree = self::$arr_tree_reverse = array();
			foreach ($pages as $c)
			{
				self::$arr_tree[Page::STATUS_ALL][$c->parent_id][] = $c;
				self::$arr_tree_reverse[Page::STATUS_ALL][$c->id] = $c;

				if ($c->enabled)
				{
					self::$arr_tree[Page::STATUS_ENABLED][$c->parent_id][] = $c;
					self::$arr_tree_reverse[Page::STATUS_ENABLED][$c->id] = $c;
				}
			}
		}

		if ($reverse)
		{
			return self::$arr_tree_reverse[$status];
		}
		return self::$arr_tree[$status];
	}

	public static function appendName($pages)
	{
		//PageDescription::appendObject($pages, 'id', 'PageDescription', 'page_id', '', MAIN_DB, 'page_id,language_id,name', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
		PageDescription::appendObject($pages, 'id', 'PageDescription', 'page_id', '', MAIN_DB, 'page_id,language_id,name', false, 'language_id');
	}

	public static function appendAll($pages)
	{
		//PageDescription::appendObject($pages, 'id', 'PageDescription', 'page_id', '', MAIN_DB, '*', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
		PageDescription::appendObject($pages, 'id', 'PageDescription', 'page_id', '', MAIN_DB, '*', false, 'language_id');
	}

	/**
	 * render a ul list displaying pages as tree
	 * 
	 * @param array $pages
	 * @param int $parent_id
	 * @param string $pattern
	 * @param string $wrap_pattern
	 * @return string html
	 */
	public static function htmlPageTree(& $pages, $parent_id, $pattern = '<li>{name}{sub}</li>', $wrap_pattern = '<ul>{tree}</ul>')
	{
		$arr_search = array('{url}', '{name}', '{sub}');
		if (isset($pages[$parent_id]))
		{
			foreach ($pages[$parent_id] as $c)
			{
				$arr_replace = array(
					self::url($c),
					self::getName($c),
					self::htmlPageTree($pages, $c->id, $pattern, $wrap_pattern)
				);

				$tree .= str_replace($arr_search, $arr_replace, $pattern);
			}
			$tree = str_replace('{tree}', $tree, $wrap_pattern);
		}

		return $tree;
	}

	private static function _selectBoxLvl(& $arr, $parent_id = 0, $level = 0, $max_level = 0)
	{
		$return = '';
		if (isset($arr[$parent_id]) && ($max_level == 0 || $level < $max_level))
		{
			$level_str = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
			foreach ($arr[$parent_id] as $c)
			{
				$return .= '<option value="' . $c->id . '">' . $level_str . View::escape(self::getName($c)) . '</option>'
						. self::_selectBoxLvl($arr, $c->id, $level + 1, $max_level);
			}
		}
		return $return;
	}

	/**
	 * get all parents till root and return in array. use this for breadcrumbs
	 * @return type 
	 */
	public static function getParents($page, $status = 'all')
	{
		if (!$page)
		{
			return array();
		}

		if (!isset($page->arr_parents))
		{
			// get parents
			// to prevent infinite loop use max levels
			$max_levels = 100;
			$parent = $page;
			$arr_parents = array();

			self::getAllPageNamesTree($status);

			$page->arr_parents = array();
			while ($parent && $parent->parent_id && $max_levels)
			{
				//Category::appendObject($parent, 'parent_id', 'Category', 'id', 'parentCategory', 'master', 'id,name,parent_id');
				//$parent = $parent->parentCategory;
				$parent = self::$arr_tree_reverse[$status][$parent->parent_id];
				$arr_parents[] = $parent;
				$max_levels--;
			}

			$page->arr_parents = array_reverse($arr_parents);
		}

		return $page->arr_parents;
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
		$parent = self::getPageFromTree($parent_id);
		if (!$parent)
		{
			// parent not found return root id 0
			return 0;
		}

		// check if parent is in self children
		if ($current_id > 0)
		{
			$current = self::getPageFromTree($current_id);
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
	 * count all subs recursively 
	 * 
	 * @param Page $page
	 * @param string $status
	 * @return int 
	 */
	public static function countSubs($page, $status = 'all')
	{
		// load all categories 		
		self::getAllPageNamesTree($status);
		$return = 0;
		if (isset(self::$arr_tree[$status][$page->id]))
		{
			$return = count(self::$arr_tree[$status][$page->id]);
			foreach (self::$arr_tree[$status][$page->id] as $c)
			{
				$return += self::countSubs($c, $status);
			}
		}

		return $return;
	}

	/**
	 * check if page is child of parent_page
	 * 
	 * @param Page $parent_page
	 * @param Page $page
	 * @param type $status
	 * @return boolean 
	 */
	public static function isChildOf($parent_page, $page, $status = 'all')
	{
		self::getParents($page, $status);
		if ($page->arr_parents)
		{
			foreach ($page->arr_parents as $l)
			{
				if ($parent_page->id == $l->id)
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get full name for page with parents 
	 * @param object $page
	 * @param string $empty_value
	 * @param string $seperator
	 * @return string 
	 */
	public static function getFullName($page = 0, $empty_value = '', $seperator = ' &raquo; ')
	{
		// get full name to page with parents
		if (!$page)
		{
			return $empty_value;
		}

		self::getParents($page);

		$return = array();
		foreach ($page->arr_parents as $p)
		{
			$return[] = View::escape(self::getName($p));
		}

		$return[] = View::escape(self::getName($page));

		return implode($seperator, $return);
	}

	function updateDescription()
	{
		// save description if set
		if ($this->PageDescription)
		{
			foreach ($this->PageDescription as $cd)
			{
				$cd->page_id = $this->id;
				// delete description first
				PageDescription::deleteWhere('PageDescription', 'page_id=? AND language_id=?', array($cd->page_id, $cd->language_id));
				$cd->save('new_id');
			}
		}

		return true;
	}

	function setFromData($data)
	{
		parent::setFromData($data);

		unset($this->PageDescription);

		if ($this->page_description)
		{
			foreach ($this->page_description as $lng => $cd)
			{
				// add new description 
				$cd['page_id'] = $this->id;
				$cd['language_id'] = $lng;
				$this->PageDescription[$lng] = new PageDescription($cd);
			}
		}

		unset($this->page_description);
	}

	/**
	 * shortcut to get page name if it is loaded as object. it will not load PageDescription if it is not loaded
	 *
	 * @param Page $page
	 * @return type 
	 */
	public static function getName($page, $lng = null)
	{
		if (!isset($page->PageDescription))
		{
			$pages = self::getAllPageNamesTree('all', true);
			if (isset($pages[$page->id]->PageDescription))
			{
				$page->PageDescription = $pages[$page->id]->PageDescription;
			}
			else
			{
				self::appendAll($page);
			}
		}

		if (!strlen($lng))
		{
			$lng = I18n::getLocale();
		}

		return $page->PageDescription[$lng]->name;
	}

	/**
	 * shortcut to get page description if it is loaded as object. it will not load PageDescription if it is not loaded
	 * 
	 * @param Page $page
	 * @return type 
	 */
	public static function getDescription($page, $lng = null)
	{
		if (!strlen($lng))
		{
			$lng = I18n::getLocale();
		}

		if (!isset($page->PageDescription[$lng]->description))
		{
			self::appendAll($page);
		}

		return $page->PageDescription[$lng]->description;
	}

	/**
	 * format description with new lines and add contact form if required 
	 * 
	 * @param Page $page
	 * @param array $vars
	 * @return string 
	 */
	public static function formatDescription($page, & $vars = null, $fill = true)
	{
		// populate variable values 
		$description = Page::getDescription($page);
		$description = Config::formatText($description);
		$arr_replace = array();
		if ($fill)
		{

			if (strpos($description, '{@CONTACTUSFORM}') !== false)
			{
				// render contact us form 
				$arr_replace['{@CONTACTUSFORM}'] = new View('index/_contact_us_form', $vars);
			}
			if (strpos($description, '{@LOCATIONS}') !== false)
			{
				// render contact us form 
				$arr_replace['{@LOCATIONS}'] = '<div class="all_locations">'
						. Location::htmlLocationTree(Location::getAllLocationNamesTree(Location::STATUS_ENABLED), 0, '<li><a href="{url}">{name}</a>{sub}</li> ', '<ul>{tree}</ul>')
						. '</div>';
			}
			if (strpos($description, '{@CATEGORIES}') !== false)
			{
				// render contact us form 
				$arr_replace['{@CATEGORIES}'] = '<div class="all_categories">'
						. Category::htmlCategoryTree(Category::getAllCategoryNamesTree(Category::STATUS_ENABLED), 0, '<li><a href="{url}">{name}</a>{sub}</li> ', '<ul>{tree}</ul>')
						. '</div>';
			}
		}
		else
		{
			$arr_replace = array(
				'{@CONTACTUSFORM}'	 => '',
				'{@LOCATIONS}'		 => '',
				'{@CATEGORIES}'		 => ''
			);
		}

		if ($arr_replace)
		{
			$description = str_replace(array_keys($arr_replace), array_values($arr_replace), $description);
		}

		return $description;
	}

	public static function getLastPosition($parent_id = 0)
	{
		$last = self::findOneFrom('Page', 'parent_id=? ORDER BY pos DESC', array(intval($parent_id)));

		return intval($last->pos);
	}

	/**
	 * generate permalink for page 
	 * 
	 * @param Page $page
	 * @return string 
	 */
	public static function url($page, $lng = null)
	{
		return Language::get_url('page/' . StringUtf8::makePermalink(self::getName($page, $lng), $lng, 'page') . '-' . $page->id . '.html', $lng);
	}

	/**
	 * return string or page link if it is set in settings
	 * 
	 * @param string $key page_id_terms|page_id_payment
	 * @param string $title
	 * @return string
	 */
	public static function pageLink($key, $title = null, $blank = true)
	{
		switch ($key)
		{
			case 'page_id_terms':
				if (is_null($title))
				{
					$title = __('Terms and conditions');
				}
				$page_id = Config::option('page_id_terms');
				$page = Page::findByIdFrom('Page', $page_id);
				break;
			case 'page_id_payment':
				if (is_null($title))
				{
					$title = __('Paid options');
				}
				$page_id = Config::option('page_id_payment');
				$page = Page::findByIdFrom('Page', $page_id);
				break;
			case 'page_id_contactus':
				if (is_null($title))
				{
					$title = __('Contact us');
				}
				$page_id = Config::option('page_id_contactus');
				$page = Page::findByIdFrom('Page', $page_id);
				break;
		}

		if ($page)
		{
			$url = Page::url($page);
			$return = '<a href="' . $url . '" ' . ($blank ? 'target="_blank"' : '') . '>' . $title . '</a>';
		}
		else
		{
			$return = $title;
		}

		return $return;
	}

}
