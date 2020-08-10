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
class Permalink extends Record
{

	const TABLE_NAME = 'permalink';
	const ITEM_TYPE_LOCATION = 0;
	const ITEM_TYPE_CATEGORY = 1;
	const ITEM_TYPE_USER = 2;
	const ITEM_TYPE_PREFIX_AD = 3;
	const ITEM_TYPE_PREFIX_POST = 4;

	static $reserved = array('admin', 'user', 'dealer', 'post', 'page', 'ad', 'item', 'sys', 'public', 'user-content', 'index', 'login', 'all', 'home');
	private static $cols = array(
		'id'		 => 1,
		'slug'		 => 1,
		'item_id'	 => 1,
		'item_type'	 => 1,
		'is_old'	 => 1,
		'updated_at' => 1,
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
		$this->added_at = REQUEST_TIME;
		$this->added_by = AuthUser::$user->id;

		return true;
	}

	function beforeUpdate()
	{
		$this->updated_at = REQUEST_TIME;

		return true;
	}

	public static function isAvailable($slug, $verbose = false, $self_id = 0, $self_type = null)
	{
		// check if it is too short 
		$min_length = 4;
		if (strlen($slug) < $min_length)
		{
			if ($verbose)
			{
				Validation::getInstance()->set_error(__('{name} should be minimum {num} characters', array('{name}' => View::escape($slug), '{num}' => $min_length)));
			}
			return false;
		}

		// check system reserved values 
		if (in_array($slug, self::$reserved))
		{
			if ($verbose)
			{
				Validation::getInstance()->set_error(__('{name} is reserved for system use', array('{name}' => View::escape($slug))));
			}
			return false;
		}

		// check if it is integer, integers reserved for ad shortlinks
		if (strcmp(intval($slug), $slug) == 0)
		{
			if ($verbose)
			{
				Validation::getInstance()->set_error(__('{name} is reserved for system use', array('{name}' => View::escape($slug))));
			}
			return false;
		}

		// check spam filters
		if (Config::hasSpamWords($slug, false, 'bad_word_filter') || Config::hasSpamWords($slug, false, 'bad_word_block'))
		{
			if ($verbose)
			{
				Validation::getInstance()->set_error(__('{name} detected as spam by spam word filter', array('{name}' => View::escape($slug))));
			}
			return false;
		}


		// check database 
		$permalink = Permalink::findByIdFrom('Permalink', $slug, 'slug');

		// if self set then check if matches self
		if (!is_null($self_type))
		{
			$is_self = ($permalink->item_id == $self_id || $permalink->item_id == 0) && $permalink->item_type == $self_type;
			$is_empty = $permalink->item_id == 0 && in_array($permalink->item_type, array(self::ITEM_TYPE_LOCATION, self::ITEM_TYPE_CATEGORY, self::ITEM_TYPE_USER));
			if ($is_self || $is_empty)
			{
				// this is already saved permalink and mark available for given id and type only.
				return true;
			}
		}

		if ($permalink && $verbose)
		{
			switch ($permalink->item_type)
			{
				case self::ITEM_TYPE_LOCATION:
					$item_type_str = '<a href="' . Language::get_url('admin/locationsEdit/' . $permalink->item_id . '/') . '">'
							. __('Location') . ': ' . View::escape($permalink->slug) . '</a>';
					break;
				case self::ITEM_TYPE_CATEGORY:
					$item_type_str = '<a href="' . Language::get_url('admin/categoriesEdit/' . $permalink->item_id . '/') . '">'
							. __('Category') . ': ' . View::escape($permalink->slug) . '</a>';
					break;
				case self::ITEM_TYPE_USER:
					$item_type_str = '<a href="' . Language::get_url('admin/usersEdit/' . $permalink->item_id . '/') . '">'
							. __('User') . ': ' . View::escape($permalink->slug) . '</a>';
					break;
				case self::ITEM_TYPE_PREFIX_AD:
				case self::ITEM_TYPE_PREFIX_POST:
					$item_type_str = '<a href="' . Language::get_url('admin/settingsPermalink/') . '">'
							. __('custom prefix') . '</a>';
					break;
				default:
					$item_type_str = __('other use');
					break;
			}

			Validation::getInstance()->set_error(__('{name} is reserved for {url}', array(
				'{name}' => View::escape($slug),
				'{url}'	 => $item_type_str
			)));
		}

		return $permalink ? false : true;
	}

	public static function generateSlug($slug, $name, $id = 0, $item_type = 0)
	{
		if (!strlen($slug))
		{
			$slug = $name;
		}
		$cat_id = $loc_id = 0;
		switch (strtolower($item_type))
		{
			case self::ITEM_TYPE_CATEGORY:
				$cat_id = $id;
				if (!$cat_id)
				{
					// get latest available id
					$cat_id = Category::nextAutoId();
				}
				$slug_default = 'c' . $cat_id;
				break;
			case self::ITEM_TYPE_USER:
				$cat_id = $id;
				if (!$cat_id)
				{
					// get latest available id
					$cat_id = User::nextAutoId();
				}
				$slug_default = 'u' . $cat_id;
				break;
			case self::ITEM_TYPE_LOCATION:
			default:
				$loc_id = $id;
				if (!$loc_id)
				{
					// get latest available id
					$loc_id = Location::nextAutoId();
				}
				$slug_default = 'l' . $loc_id;
				break;
		}

		$slug = StringUtf8::makePermalink($slug, null, $slug_default);
		if (strcmp($slug, $slug_default) == 0)
		{
			// used default then reset default
			$slug_default = '';
		}

		// check if this slug is unique 
		$exists = true;
		$max_loop = 0;
		$_slug = $slug;
		while ($exists && $max_loop < 100)
		{
			$exists = !self::isAvailable($_slug, false, $id, $item_type);
			if ($exists)
			{
				$_slug = $slug . ($slug_default ? '-' . $slug_default : '') . ($max_loop ? '-' . $max_loop : '');
			}

			$max_loop++;
		}

		if ($exists)
		{
			// generate unique id  from timestamp
			$_slug = $slug . ($slug_default ? '-' . $slug_default : '') . '-' . User::genActivationCode('p{code}');

			// try once again
			$exists = !self::isAvailable($_slug, false, $id, $item_type);
			if ($exists)
			{
				$_slug = $slug . ($slug_default ? '-' . $slug_default : '') . '-' . User::genActivationCode('p{code}');
			}
		}

		return $_slug;
	}

	/**
	 * Generate slug using slug and name, then saves it
	 * 
	 * @param string $slug
	 * @param string $name
	 * @param int $id
	 * @param int $item_type
	 * @return boolean|\Permalink
	 */
	public static function savePermalink($slug, $name, $id, $item_type)
	{
		$slug = self::generateSlug($slug, $name, $id, $item_type);

		// find existing permalink for this item
		$permalink = Permalink::findOneFrom('Permalink', 'item_id=? AND item_type=? AND is_old=?', array($id, $item_type, 0));
		if (!$permalink)
		{
			// this is new item, add it 
			$permalink = new Permalink();
			$permalink->item_type = $item_type;
			$permalink->item_id = $id;
			$permalink->is_old = 0;
			$permalink->slug = $slug;

			if (!$permalink->save())
			{
				return false;
			}
		}
		else
		{
			// updating old item check if slug changed
			if (strcmp($permalink->slug, $slug) != 0)
			{
				// slug is different then make current slug old and add new slug
				$permalink->is_old = 1;
				if (!$permalink->save())
				{
					return false;
				}

				// delete any old permalink with this slug 
				Permalink::deleteWhere('Permalink', 'slug=? AND is_old=?', array($slug, 1));

				// add new slug as new permalink
				$new_permalink = new Permalink();
				$new_permalink->slug = $slug;
				$new_permalink->item_type = $permalink->item_type;
				$new_permalink->item_id = $permalink->item_id;
				$new_permalink->is_old = 0;
				if (!$new_permalink->save())
				{
					return false;
				}

				// return new permalink
				$permalink = $new_permalink;
			}
		}

		return $permalink;
	}

	public static function savePermalinkWithObject($obj, $item_type, $name = '', $slug_key = 'slug')
	{
		$permalink = self::savePermalink($obj->{$slug_key}, $name, $obj->id, $item_type);

		if ($permalink)
		{
			if (strcmp($obj->{$slug_key}, $permalink->slug) != 0)
			{
				// update object slug
				switch ($item_type)
				{
					case self::ITEM_TYPE_CATEGORY:
						Category::update('Category', array($slug_key => $permalink->slug), 'id=?', array($obj->id));
						break;
					case self::ITEM_TYPE_LOCATION:
						Location::update('Location', array($slug_key => $permalink->slug), 'id=?', array($obj->id));
						break;
					case self::ITEM_TYPE_USER:
						User::update('User', array($slug_key => $permalink->slug), 'id=?', array($obj->id));
						break;
				}
			}
		}

		return $permalink;
	}

	/**
	 * read all category, location and users and add their slugs to permalinktable. used when migrating to central permalink management
	 */
	public static function populateFromSource()
	{
		// get all categories 
		$cats = Category::getCategories();
		foreach ($cats as $cat)
		{
			Permalink::savePermalinkWithObject($cat, Permalink::ITEM_TYPE_CATEGORY, Category::getName($cat));
		}

		// get all locations 
		$locs = Location::getLocations();
		foreach ($locs as $loc)
		{
			Permalink::savePermalinkWithObject($loc, Permalink::ITEM_TYPE_LOCATION, Location::getName($loc));
		}

		// get all users 
		$users = User::findAllFrom('User');
		foreach ($users as $user)
		{
			Permalink::savePermalinkWithObject($user, Permalink::ITEM_TYPE_USER, $user->name, 'username');
		}
	}

	/**
	 * Clean unlinked records from permalink table
	 * 
	 * @param integer $type
	 */
	public static function cleanUnlinked($type = null)
	{
		// perform once per month. not much important just to clean up db. 
		// it can be slow query if site has too many users,locations, categories 
		$wait = 3600 * 24 * 30;
		$key = 'last_permalink_cleanUnlinked_' . intval($type);

		// set default type
		if (is_null($type))
		{
			$type = self::ITEM_TYPE_LOCATION;
		}

		if (Config::option($key) < REQUEST_TIME - $wait + intval($type) * 7)
		{
			// save current call time 
			Config::optionSet($key, REQUEST_TIME);

			switch ($type)
			{
				case self::ITEM_TYPE_LOCATION:
					// delete Permalink for non existing Locaiton
					$sql = "DELETE pl 
						FROM " . Permalink::tableNameFromClassName('Permalink') . " pl
						LEFT JOIN " . Location::tableNameFromClassName('Location') . " loc ON(loc.id=pl.item_id)
						WHERE pl.item_type=?  AND loc.id is NULL";
					Record::query($sql, array(self::ITEM_TYPE_LOCATION));
					break;
				case self::ITEM_TYPE_CATEGORY:
					// delete Permalink for non existing Category
					$sql = "DELETE pl 
						FROM " . Permalink::tableNameFromClassName('Permalink') . " pl
						LEFT JOIN " . Category::tableNameFromClassName('Category') . " ct ON(ct.id=pl.item_id)
						WHERE pl.item_type=?  AND ct.id is NULL";
					Record::query($sql, array(self::ITEM_TYPE_CATEGORY));
					break;
				case self::ITEM_TYPE_USER:
					// delete Permalink for non existing user
					$sql = "DELETE pl 
						FROM " . Permalink::tableNameFromClassName('Permalink') . " pl
						LEFT JOIN " . User::tableNameFromClassName('User') . " u ON(u.id=pl.item_id)
						WHERE pl.item_type=?  AND u.id is NULL";
					Record::query($sql, array(self::ITEM_TYPE_USER));
					break;
			}
		}
	}

	/**
	 * define user, location, category, page_number processing url vars
	 * reverse of Permalink::vars2url()
	 * 
	 * @param array $arr_url
	 * @return Object 
	 */
	static public function url2vars($arr_url = array())
	{
		// return values in object 
		$return = new stdClass();
		$return->page_number = 1;
		$return->search_params = array();
		$return->is_search = false;


		// remove empty values 
		$arr_url = array_filter($arr_url);

		// nothing set then return empty object 
		if (!count($arr_url))
		{
			return $return;
		}


		// detect if it isi search action by checking array for s
		$_arr_url = array();
		$after_s = false;
		$cf_processed = false;

		// consider everything after s as search params
		foreach ($arr_url as $val)
		{
			// check if it is beginning of search 
			if (strpos($val, 's_') === 0)
			{
				$after_s = true;
			}

			// convert values after s to search_params
			if ($after_s)
			{
				// process links once. this will ignore eveything after s_
				if (!$cf_processed)
				{
					$cf_processed = true;
					$cf_vars = explode('_', $val);
					//print_r($cf_vars);

					foreach ($cf_vars as $cf_val)
					{
						// convert string to search variable 
						$arr_key_val = explode('-', $cf_val);
						$key = array_shift($arr_key_val);
						$search_value = implode('-', $arr_key_val);
						switch ($key)
						{
							case 's':
								// first parameter defining that after this query search starts
								// do nothing
								break;
							case 'i':
								// with image 
								$return->search_params['with_photo'] = 1;
								break;
							case 'f':
								// freshness
								$return->search_params['freshness'] = intval($search_value);
								break;
							case 'q':
								// query
								$return->search_params['q'] = trim(rawurldecode($search_value));
								$return->search_params['q'] = TextTransform::normalizeQueryString($return->search_params['q']);
								break;
							default:
								// key is adfield_id
								// $search_value is 
								$af = AdField::getAdFieldFromTree($key);
								if ($af)
								{
									switch ($af->type)
									{
										case AdField::TYPE_NUMBER:
										case AdField::TYPE_PRICE:
											// range value from-to
											$values = self::seperateMultivalue($search_value);
											if (count($values) < 2)
											{
												// from = to
												$return->search_params['cf'][$af->id]['from'] = $search_value;
												$return->search_params['cf'][$af->id]['to'] = $search_value;
											}
											else
											{
												list($from, $to) = $values;

												if ($from)
												{
													$return->search_params['cf'][$af->id]['from'] = $from;
												}
												if ($to)
												{
													$return->search_params['cf'][$af->id]['to'] = $to;
												}
											}
											break;
										case AdField::TYPE_CHECKBOX:
											// comma separeted multivalue 
											$values = self::seperateMultivalue($search_value);
											foreach ($values as $check_val)
											{
												// check if such value exists 
												if (isset($af->AdFieldValue[$check_val]))
												{
													$return->search_params['cf'][$af->id][$check_val] = $check_val;
												}
											}
											break;
										case AdField::TYPE_RADIO:
										case AdField::TYPE_DROPDOWN:
											// comma separeted multivalue 
											// single value 
											if ($search_value)
											{
												// check if such value exists 
												if (isset($af->AdFieldValue[$search_value]))
												{
													$return->search_params['cf'][$af->id] = $search_value;
												}
											}
											break;
										default :
											// it is string value 
											$return->search_params['cf'][$af->id] = trim(rawurldecode($search_value));
											break;
									}
								}// $af
								break;
						}
					}// $foreach $cf_val
				}
			}
			else
			{
				// values before 's' search values use to detect location, category, user
				$_arr_url[] = $val;
			}// $after_s
		}// foreach $arr_url
		//$permalink = Permalink::findAllFrom('Permalink', 'slug IN (' . implode(',', array_fill(0, count($_arr_url), '?')) . ')', $_arr_url);

		$permalink = Permalink::findAllFrom('Permalink', 'slug IN (' . implode(',', Ad::ids2quote($_arr_url)) . ')');

		// add all if preset
		if (in_array('all', $_arr_url))
		{
			// check if no category found
			foreach ($permalink as $p)
			{
				if ($p->item_type == Permalink::ITEM_TYPE_CATEGORY)
				{
					$cat_found = true;
				}
			}

			if (!$cat_found)
			{
				$return->selected_category = Category::objAll();
			}
		}

		/**
		 * location / category
		 * category
		 * user
		 * 
		 */
		//print_r($arr_p);
		foreach ($permalink as $p)
		{
			switch ($p->item_type)
			{
				case Permalink::ITEM_TYPE_LOCATION:
					$return->selected_location = Location::getLocationFromTree($p->item_id, Location::STATUS_ENABLED);
					break;
				case Permalink::ITEM_TYPE_CATEGORY:
					$return->selected_category = Category::getCategoryFromTree($p->item_id, Category::STATUS_ENABLED);
					break;
				case Permalink::ITEM_TYPE_USER:
					$return->selected_user = User::findByIdFrom('User', $p->item_id);
					break;
			}
		}

		// get page number stored as last element in array 
		$page_number = end($arr_url) . '';

		//echo '[$page_number:' . $page_number . ']';
		//print_r($arr_slug);
		if (!self::isPageNumber($page_number))
		{
			// it is not page number, then set page as 1 
			$page_number = 1;
		}

		$return->page_number = intval($page_number);

		// merge current serch params to global $_GET for populating search form
		if ($return->search_params)
		{
			$_GET['s'] = 1;
			$return->search_params['s'] = 1;
			$_GET = Language::array_merge_recursive($_GET, $return->search_params);
			$return->is_search = true;
		}

		// define all params to properly calculate custom query
		// set search params from defined values 
		/* $return->search_params['page'] = $page_number;
		  if($return->selected_location)
		  {
		  $return->search_params['location_id'] = $return->selected_location->id;
		  }
		  if($return->selected_category)
		  {
		  $return->search_params['category_id'] = $return->selected_category->id;
		  }
		  if($return->selected_user)
		  {
		  $return->search_params['user_id'] = $return->selected_user->id;
		  }
		 */

		return $return;
	}

	/**
	 * Seperate multivalue with , or -. Use this function for redirecting to new url structure from , to -
	 * 
	 * @param string $value
	 * @return array
	 */
	public static function seperateMultivalue($value)
	{
		// try old seperator ,
		// then try new seperator -
		$value_seperator = '-';
		// check old value seperator 
		if (strpos($value, ',') !== false)
		{
			$value_seperator = ',';
		}


		return explode($value_seperator, $value);
	}

	/**
	 * check if given string is integer then it is page number 
	 * 
	 * @param type $str
	 * @return type
	 */
	public static function isPageNumber($str)
	{
		return self::isNumber($str);
	}

	/**
	 * check if given string is integer then it is page number 
	 * 
	 * @param type $str
	 * @return type
	 */
	public static function isNumber($str)
	{
		return strcmp(intval($str) . '', $str) == 0;
	}

	/**
	 * build url using given values 
	 * Also need to reverse convert them 
	 * reverse of Permalink::url2vars()
	 * 
	 * @param type $vars
	 */
	public static function vars2url($vars = array(), $overwrite = array(), $origin = false)
	{
		$location = IndexController::$selected_location;
		$category = IndexController::$selected_category;
		$user = IndexController::$selected_user;
		$page = intval(IndexController::$url2vars->page_number);
		$arr_url = array();
		$max_keywords_in_url = 2;
		$arr_keyword = array();
		$value_seperator = '-';

		if (!$page)
		{
			$page = 1;
		}

		$vars = Language::array_merge_recursive($vars, $overwrite);

		foreach ($vars as $k => $v)
		{
			switch ($k)
			{
				case 'location_id':
					if (!$v)
					{
						$location = null;
					}
					elseif ($location->id != $v)
					{
						$location = Location::getLocationFromTree($v);
					}
					break;
				case 'category_id':
					if (!$v)
					{
						$category = null;
					}
					elseif ($category->id != $v)
					{
						$category = Category::getCategoryFromTree($v);
					}
					break;
				case 'user_id':
					if (!$v)
					{
						$user = null;
					}
					elseif ($user->id != $v)
					{
						$user = User::findByIdFrom('User', $v);
					}
					break;
				case 'q':
				case 'freshness':
				case 'with_photo':
					// skip this and append at the end
					break;
				case 'cf':
					// other all fields are considered as custom field
					/* http://localhost/classibase/search/
					 * ?q=
					 * &cf%5B2%5D=17
					 * &cf%5B4%5D%5Bfrom%5D=2000
					 * &cf%5B4%5D%5Bto%5D=2001
					 * &cf%5B18%5D%5Bfrom%5D=10
					 * &cf%5B18%5D%5Bto%5D=11
					 * &cf%5B1%5D%5Bfrom%5D=2000
					 * &cf%5B1%5D%5Bto%5D=20000
					 * &freshness=3
					 * &with_photo=1
					 * &category_id=31
					 * &location_id=75
					 * &s=Search
					 */

					// sort by keys here to make url consistent
					ksort($v);

					foreach ($v as $af_id => $af_val)
					{
						$str_url_val = '';
						$af = AdField::getAdFieldFromTree($af_id);
						if ($af)
						{
							// ad field exists continue
							if (is_array($af_val))
							{
								if (isset($af_val['from']) || isset($af_val['to']))
								{
									// range field
									$from = AdField::stringToFloat($af_val['from']);
									$to = AdField::stringToFloat($af_val['to']);
									if ($from > 0 && $to > 0 && $from > $to)
									{
										// swap values 
										list($from, $to) = array($to, $from);
									}

									// it is range delimeter
									if ($from > 0 || $to > 0)
									{
										if ($from == $to)
										{
											// use single value 
											$str_url_val .= $from;
										}
										else
										{
											if ($from > 0)
											{
												$str_url_val .= $from;
											}
											$str_url_val .= $value_seperator;
											if ($to > 0)
											{
												$str_url_val .= $to;
											}
										}
									}
								}
								else
								{
									// it is multivalue checkboxes 
									$af_val = array_filter($af_val);
									$str_url_val = implode($value_seperator, $af_val);
								}
							}
							else
							{
								// it is single value 
								// remove / from search query becuase it gives 404 error in apache
								$q = TextTransform::normalizeQueryString($af_val);
								$str_url_val = rawurlencode($q);
							}

							// has custom value for searching
							if (strlen($str_url_val))
							{
								// add keywords to url for SEO
								if ($max_keywords_in_url > 0)
								{
									$keyword = AdField::slug($af, $af_val);
									if (strlen($keyword))
									{
										$arr_keyword[] = $keyword;
										$max_keywords_in_url--;
									}
								}
								$arr_url[] = $af->id . '-' . $str_url_val;
							}
						}
					}
					break;
			}
		}


		// sort $arr_url for consistency 
		// do not sort because title changes order of words
		sort($arr_url);

		// add special values last 
		if (isset($vars['q']))
		{
			// normalize search string, 
			$q = TextTransform::normalizeQueryString($vars['q']);
			if (strlen($q) > 0)
			{
				// replace space with - 
				$q = str_replace(' ', '-', $q);
				$arr_url[] = 'q-' . rawurlencode($q);
			}
		}
		if (isset($vars['freshness']) && intval($vars['freshness']) > 0)
		{
			$arr_url[] = 'f-' . intval($vars['freshness']);
		}
		if (isset($vars['with_photo']) && intval($vars['with_photo']) > 0)
		{
			$arr_url[] = 'i';
		}

		// convert array to url 
		//$extra = implode('', $arr_url);
		$extra = implode('_', $arr_url);

		// if it is search url then add extra values 
		if (strlen($extra))
		{
			// add s to beginning for defining search 
			$extra = 's_' . $extra . '/';

			// add keywords 
			if ($arr_keyword)
			{
				$arr_keyword_unique = array();
				$extra_test = Location::urlOrigin($location, $category, $user, 1, $extra);
				//$extra_test = $extra;
				// make sure that each keyword is unique 
				foreach ($arr_keyword as $keyword)
				{
					if (strpos($extra_test, $keyword) === false)
					{
						$arr_keyword_unique[] = $keyword;
						$extra_test .= '-' . $keyword;
					}
				}
				if ($arr_keyword_unique)
				{
					$extra .= implode('-', $arr_keyword_unique) . '/';
				}
				//$extra .= implode('-', $arr_keyword) . '/';
			}

			// this is search so check if has valid category, if not then add all 
			if (!$category)
			{
				$category = Category::objAll();
			}
		}

		// define page 
		if (isset($vars['page']))
		{
			if (intval($vars['page']) > 1)
			{
				$page = intval($vars['page']);
			}
			elseif ($vars['page'] == '{page}')
			{
				$page = '{page}';
			}
			else
			{
				$page = 1;
			}
		}

		$url_origin = Location::urlOrigin($location, $category, $user, $page, $extra);
		if ($origin)
		{
			return $url_origin;
		}
		else
		{
			return Language::get_url($url_origin);
		}
	}

}
