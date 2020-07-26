<?php

/**
 * Widget manages all widget requests
 *
 * @author v
 * 
 * 
 * TODO : on switching theme update inactive widgets with all existing widgets in database
 * 
 */
class Widget extends Record
{

	const TABLE_NAME = 'widget';

	// array of defined widget types
	static private $_widgets;
	static private $_widgets_saved;
	static private $_instance;
	static private $_vars;
	static private $_sidebar_widgets;
	static private $_arr_freshness;
	private static $cols = array(
		'id'				 => 1,
		'type_id'			 => 1,
		'options'			 => 1,
		'conditional_render' => 1
	);

	function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);

		$this->typeRegisterDefaults();

		parent::__construct($data, $locale_db);
	}

	function afterDelete()
	{
		self::$_widgets_saved = null;
		return true;
	}

	function afterInsert()
	{
		self::$_widgets_saved = null;
		return true;
	}

	function afterUpdate()
	{
		self::$_widgets_saved = null;
		return true;
	}

	public function _unsetializeOptions()
	{
		// serialize options for saving
		if (!isset($this->_options))
		{
			if (strlen($this->options))
			{
				$this->_options = unserialize($this->options);
			}
			else
			{
				$this->_options = array();
			}
		}
	}

	function typeRegisterDefaults()
	{
		self::typeRegister(array(
			'id'			 => 'locations',
			'name'			 => __('Locations'),
			'description'	 => __('List of defined locations'),
			'render'		 => array('Widget', 'widgetLocations'),
			'optionsForm'	 => array('Widget', 'widgetLocationsForm'),
			'options'		 => array(
				'display_mode' => 'dynamic'
			)
		));

		self::typeRegister(array(
			'id'			 => 'categories',
			'name'			 => __('Categories'),
			'description'	 => __('List of defined categories'),
			'render'		 => array('Widget', 'widgetCategories'),
			'optionsForm'	 => array('Widget', 'widgetCategoriesForm'),
			'options'		 => array(
				'display_mode' => 'dynamic'
			)
		));

		self::typeRegister(array(
			'id'			 => 'pages',
			'name'			 => __('Pages'),
			'description'	 => __('List of defined pages'),
			'render'		 => array('Widget', 'widgetPages'),
			'options'		 => array('title' => __('Info'))
		));

		self::typeRegister(array(
			'id'				 => 'search',
			'name'				 => __('Search'),
			'description'		 => __('Search form'),
			'render'			 => array('Widget', 'widgetSearch'),
			'optionsForm'		 => array('Widget', 'widgetSearchForm'),
			'optionsFormSubmit'	 => array('Widget', 'widgetSearchSubmit'),
			'options'			 => array(
				'display_mode'		 => 'advanced',
				'related_location'	 => 1,
				'related_category'	 => 1
			)
		));

		self::typeRegister(array(
			'id'				 => 'text',
			'name'				 => __('Text'),
			'description'		 => __('Plain text, HTML or javascript code'),
			'render'			 => array('Widget', 'widgetText'),
			'options'			 => array('title' => ''),
			'optionsForm'		 => array('Widget', 'widgetTextForm'),
			'optionsFormSubmit'	 => array('Widget', 'widgetTextSubmit')
		));

		self::typeRegister(array(
			'id'			 => 'seperator',
			'name'			 => __('Seperator'),
			'description'	 => __('Seperate multiple widgets with new line. Used for horizontal widget areas.'),
			'render'		 => array('Widget', 'widgetSeperator'),
			'options'		 => array('title' => ''),
			'optionsForm'	 => array('Widget', 'widgetSeperatorForm')
		));

		// used id listing instad of ads to pass adblocks
		self::typeRegister(array(
			'id'				 => 'listing',
			'name'				 => __('Ads'),
			'description'		 => __('Latest or featured ads for selected location'),
			'render'			 => array('Widget', 'widgetAds'),
			'optionsForm'		 => array('Widget', 'widgetAdsForm'),
			'optionsFormSubmit'	 => array('Widget', 'widgetAdsSubmit'),
			'options'			 => array(
				'title'			 => '',
				'list_style'	 => 'simple',
				'list_mode'		 => 'latest',
				'hit_period'	 => 'a',
				'number_of_ads'	 => 5
			)
		));


		self::typeRegister(array(
			'id'				 => 'users',
			'name'				 => __('Users'),
			'description'		 => __('Latest or top users'),
			'render'			 => array('Widget', 'widgetUsers'),
			'optionsForm'		 => array('Widget', 'widgetUsersForm'),
			'optionsFormSubmit'	 => array('Widget', 'widgetUsersSubmit'),
			'options'			 => array(
				'title'				 => '',
				'list_type'			 => 'all',
				'list_style'		 => 'simple',
				'list_mode'			 => 'most_posted',
				'number_of_users'	 => 5
			)
		));

		self::typeRegister(array(
			'id'				 => 'rss',
			'name'				 => __('RSS'),
			'description'		 => __('Display links to RSS feeds'),
			'render'			 => array('Widget', 'widgetRSS'),
			'optionsForm'		 => array('Widget', 'widgetRSSForm'),
			'optionsFormSubmit'	 => array('Widget', 'widgetRSSSubmit'),
			'options'			 => array(
				'rss_latest'			 => 1,
				'rss_featured'			 => 1,
				'rss_latest_active'		 => 1,
				'rss_featured_active'	 => 1
			)
		));
	}

	/**
	 * Create one instance of object 
	 * 
	 * @return Widget 
	 */
	public static function instance()
	{
		if (!isset(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add new widget type to available widgets
	 * 
	 * @param array $arr 
	 */
	static function typeRegister($arr)
	{
		$obj = new stdClass();
		foreach ($arr as $k => $v)
		{
			$obj->$k = $v;
		}

		// check if no restriction on hide widget set then set default hide pages
		if (!isset($obj->options['page_type_hide']))
		{
			// hide ad_post page by default
			$obj->options['page_type_hide']['ad_post'] = 1;
		}

		self::$_widgets[$obj->id] = $obj;
	}

	/**
	 * return all defined widgets. not saved widgets, defined widget types
	 * 
	 * @return array of widget types 
	 */
	public static function typesAll()
	{
		// construct object to define all widgets 
		self::instance();

		// return defined widgets
		return self::$_widgets;
	}

	/**
	 * locations widget, displays defined locations 
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetLocations($widget)
	{
		Benchmark::cp('widgetLocations(' . $widget->id . '):START');

		// get locations and display them 
		// get all locations as tree 
		$locations = Location::getAllLocationNamesTree(Location::STATUS_ENABLED);


		// related to root or current category 
		if ($widget->getOption('relative_to_current', false, true))
		{
			// display related to current 
			$selected_location = self::$_vars['selected_location'];
			$parent_id = intval($selected_location->id);
		}
		else
		{
			// related to root
			$parent_id = 0;
		}

		// init return value
		$return = self::_widgetLocations($locations, $widget, $parent_id);

		$return = self::_applyWidgetFormat($widget, $return);

		Benchmark::cp('widgetLocations(' . $widget->id . '):END');

		return $return;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetLocationsForm($widget)
	{
		$echo = '';

		// select how to render categories
		// for homepage suggested to render fixed 2 levels
		// for sidebar dynamic
		$display_mode = '<select name="display_mode" id="display_mode">
									<option value="dynamic">' . __('Dynamic') . '</option>
									<option value="dynamic_minimal">' . __('Dynamic minimal') . '</option>
									<option value="fixed_1">' . __('Fixed {num} levels', array('{num}' => 1)) . '</option>
									<option value="fixed_2">' . __('Fixed {num} levels', array('{num}' => 2)) . '</option>
									<option value="fixed_3">' . __('Fixed {num} levels', array('{num}' => 3)) . '</option>
									<option value="fixed_all">' . __('Fixed {num} levels', array('{num}' => __('All'))) . '</option>
								</select>
								<small>
									</br><b>' . __('Dynamic') . '</b>: ' . __('suggested for sidebar') . '
									</br><b>' . __('Fixed {num} levels', array('{num}' => 2)) . '</b>: ' . __('suggested for home page') . '
								</small>';

		// mark selected option 
		$sel_display_mode = $widget->getOption('display_mode', false, true);
		$display_mode = str_replace('value="' . $sel_display_mode . '">', 'value="' . $sel_display_mode . '" selected="selected">', $display_mode);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="display_mode">' . __('Display mode') . '</label>',
					'{field}'	 => $display_mode
		));


		if (!Config::option('disable_ad_counting'))
		{
			// display ad count 
			$echo .= self::_formatFormRow(array(
						'{field}' => self::_formatFormCheckbox(array(
							'{label}'	 => __('Display ad count'),
							'{name}'	 => 'display_ad_count',
							'{checked}'	 => $widget->getOption('display_ad_count', false, true)
						))
			));

			// hide empty locations
			$echo .= self::_formatFormRow(array(
						'{field}' => self::_formatFormCheckbox(array(
							'{label}'	 => __('Hide empty records'),
							'{name}'	 => 'hide_empty',
							'{checked}'	 => $widget->getOption('hide_empty', false, true)
						))
			));
		}

		// categories related to root or current category 
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Related to active location'),
						'{name}'	 => 'relative_to_current',
						'{checked}'	 => $widget->getOption('relative_to_current', false, true)
					))
		));

		return self::defaultForm($widget) . $echo;
	}

	private static function _formatFormRow($data = array())
	{
		$pattern = '<div class="clearfix form-row {class}">
						<div class="col col-12 sm-col-2 px1 form-label">{label}</div>
						<div class="col col-12 sm-col-10 px1">{field}</div>
					</div>';
		$data_default = array(
			'{label}'	 => '',
			'{field}'	 => '',
			'{class}'	 => ''
		);
		$data_process = array_merge($data_default, $data);

		return str_replace(array_keys($data_process), array_values($data_process), $pattern);
	}

	private static function _formatFormCheckbox($data = array())
	{
		$pattern = '<label class="input-checkbox">'
				. '<input type="checkbox" name="{name}" id="{name}" value="{val}" {checked} />'
				. '<span class="checkmark"></span>{label}</label> ';

		$data_default = array(
			'{label}'	 => '',
			'{name}'	 => '',
			'{val}'		 => '1',
			'{checked}'	 => false,
			'{required}' => false,
		);
		$data_process = array_merge($data_default, $data);

		$data_str = array(
			'{checked}'	 => array(
				0	 => '',
				1	 => 'checked="checked"'
			),
			'{required}' => array(
				0	 => '',
				1	 => 'required'
			)
		);

		// loop set string values for bool fields  
		foreach ($data_str as $k => $v)
		{
			if (isset($data_process[$k]))
			{
				$data_process[$k] = $v[intval($data_process[$k])];
			}
		}

		return str_replace(array_keys($data_process), array_values($data_process), $pattern);
	}

	/**
	 * 
	 * @param array $locations Location
	 * @param Widget $widget
	 * @param int $parent_id
	 * @return string
	 */
	private static function _widgetLocations($locations, $widget, $parent_id = 0)
	{
		$selected_location = self::$_vars['selected_location'];
		$selected_category = self::$_vars['selected_category'];
		$cq = self::$_vars['cq'];
		$return = '';
		$return_arr = array();

		$display_ad_count = $widget->getOption('display_ad_count', false, true);
		$hide_empty = $widget->getOption('hide_empty', false, true);
		$relative_to_current = $widget->getOption('relative_to_current', false, true);
		$disable_ad_counting = Config::option('disable_ad_counting');

		// should urls use custom query 
		$use_relative_url = ($relative_to_current && $cq->url2vars);
		// can we use cq for counting 
		$use_cq = $use_relative_url ? $cq : null;

		if ($selected_category)
		{
			$arr_page_title[0] = View::escape(Category::getName($selected_category));
		}

		// check if location has locations
		if (isset($locations[$parent_id]))
		{
			foreach ($locations[$parent_id] as $l)
			{
				if ($selected_location->id == $l->id)
				{
					$class = ' class="active"';
					$is_sel = true;
				}
				else
				{
					$class = '';
					$is_sel = false;
				}

				$sub_loc = false;


				$display_mode = $widget->getOption('display_mode', false, true);
				switch ($display_mode)
				{
					/*
					  dynamic
					  dynamic_minimal
					  fixed_1
					  fixed_2
					  fixed_3
					  fixed_all
					 */
					case 'fixed_1':
						// display only one level then no subs						
						break;
					case 'fixed_2':
						//print_r(Category::getParents($l, Category::STATUS_ENABLED));
						// display 2 levels home and sub 
						if (count(Location::getParents($l, Location::STATUS_ENABLED)) < 1)
						{
							$sub_loc = self::_widgetLocations($locations, $widget, $l->id);
						}
						break;
					case 'fixed_3':
						// display 3 levels home and sub 
						if (count(Location::getParents($l, Location::STATUS_ENABLED)) < 2)
						{
							$sub_loc = self::_widgetLocations($locations, $widget, $l->id);
						}
						break;
					case 'fixed_all':
						// display everything
						$sub_loc = self::_widgetLocations($locations, $widget, $l->id);
						break;
					case 'dynamic_minimal':
					case 'dynamic':
					default:
						// decide if need to open sublocation 
						if ($is_sel || Location::isChildOf($l, $selected_location, Location::STATUS_ENABLED))
						{
							$sub_loc = self::_widgetLocations($locations, $widget, $l->id);
						}
				}

				// display for all 
				// if minimal then display if selected or has sub. which will not display neighbors for parents
				if ($display_mode != 'dynamic_minimal' || ($sub_loc || $is_sel || !$selected_location->id || $selected_location->id == $l->parent_id))
				{
					$l_name = View::escape(Location::getName($l));
					if ($selected_category)
					{
						$arr_page_title[1] = $l_name;
						$page_title = ' title="' . Config::buildTitle($arr_page_title) . '"';
					}
					else
					{
						$page_title = '';
					}

					// display count 
					$ad_count = '';
					if ($display_ad_count)
					{
						$ad_count_ = AdCategoryCount::getCountLocation($l->id, $selected_category->id, $use_cq);
						if ($ad_count_)
						{
							$ad_count = ' <span class="item_count">' . number_format($ad_count_) . '</span>';
						}
					}


					// hide empty location links 
					$hide_record = false;
					if ($hide_empty && !$disable_ad_counting)
					{
						$ad_count_ = AdCategoryCount::getCountLocation($l->id, $selected_category->id, $use_cq);
						$hide_record = $ad_count_ > 0 ? false : true;
					}


					if (!$hide_record)
					{
						if ($use_relative_url)
						{
							// we have custom query and set to use relatie then use relative url to custom query
							$url = Permalink::vars2url($cq->url2vars->search_params, array('location_id' => $l->id));
						}
						else
						{
							$url = Location::url($l, $selected_category);
						}

						$return_arr[] = '<li><a href="' . $url . '"' . $class . $page_title . '>'
								. $l_name . '</a>'
								. $ad_count . $sub_loc . '</li>';
					}
				}
			}


			if ($hide_empty && !$disable_ad_counting && count($return_arr) < 2)
			{
				// only one record, so show only sub if exists, because $hide_empty is set
				$return = $sub_loc;
			}
			else
			{
				$return = implode('', $return_arr);
			}

			if ($return)
			{
				// display all link for dynamic_minimal mode for better navigation
				if ($parent_id == 0 && $display_mode == 'dynamic_minimal' && $selected_location)
				{
					$l_name = __('All locations');



					// display all categories link for better navigation
					if ($selected_category)
					{
						if ($use_relative_url)
						{
							// we have custom query and set to use relatie then use relative url to custom query
							$all_url = Permalink::vars2url($cq->url2vars->search_params, array('location_id' => 0));
						}
						else
						{
							$all_url = Location::url(null, $selected_category);
						}

						$arr_page_title[1] = $l_name;
						$page_title = ' title="' . Config::buildTitle($arr_page_title) . '"';
					}
					else
					{
						// reset location 
						if ($use_relative_url)
						{
							// we have custom query and set to use relatie then use relative url to custom query
							$all_url = Permalink::vars2url($cq->url2vars->search_params, array('location_id' => 0));
						}
						else
						{
							$all_url = Language::urlHomeReset();
						}
						$page_title = '';
					}

					// display count 
					$ad_count = '';
					if ($display_ad_count)
					{
						$ad_count_ = AdCategoryCount::getCountLocation(0, $selected_category->id, $use_cq);
						if ($ad_count_)
						{
							$ad_count = ' <span class="item_count">' . number_format($ad_count_) . '</span>';
						}
					}

					$return = '<li><a href="' . $all_url . '"' . $page_title . '>'
							. $l_name . '</a>'
							. $ad_count . '<ul>' . $return . '</ul></li>';
				}

				$return = '<ul>' . $return . '</ul>';
			}
		}

		return $return;
	}

	public static function widgetPages($widget)
	{
		// get locations and display them 
		// get all locations as tree 
		$pages = Page::getAllPageNamesTree(Location::STATUS_ENABLED);

		// init return value
		$return = self::_widgetPages($pages, $widget);

		return self::_applyWidgetFormat($widget, $return);
	}

	private static function _widgetPages($pages, $widget, $parent_id = 0)
	{
		$selected_page = self::$_vars['selected_page'];

		// check if location has locations
		if (isset($pages[$parent_id]))
		{
			foreach ($pages[$parent_id] as $l)
			{
				if ($selected_page->id == $l->id)
				{
					$class = ' class="active"';
					$is_sel = true;
				}
				else
				{
					$class = '';
					$is_sel = false;
				}

				// decide if need to open sublocation 
				if ($is_sel || Page::isChildOf($l, $selected_page, Page::STATUS_ENABLED))
				{
					$sub_page = self::_widgetPages($pages, $widget, $l->id);
				}
				else
				{
					$sub_page = '';
				}

				$return .= '<li><a href="' . Page::url($l) . '"' . $class . '>'
						. View::escape(Page::getName($l)) . '</a>'
						. $sub_page . '</li>';
			}

			$return = '<ul>' . $return . '</ul>';
		}

		return $return;
	}

	/**
	 * locations widget, displays defined locations 
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetCategories($widget)
	{
		Benchmark::cp('widgetCategories(' . $widget->id . '):START');

		// get categories and display them 
		// get all categories as tree 
		$categories = Category::getAllCategoryNamesTree(Category::STATUS_ENABLED);

		// related to root or current category 
		if ($widget->getOption('relative_to_current', false, true))
		{
			// display related to current 
			$selected_category = self::$_vars['selected_category'];
			$parent_id = intval($selected_category->id);
		}
		else
		{
			// related to root
			$parent_id = 0;
		}

		// init return value
		$return = self::_widgetCategories($categories, $widget, $parent_id);

		$return = self::_applyWidgetFormat($widget, $return);

		Benchmark::cp('widgetCategories(' . $widget->id . '):END');

		return $return;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetCategoriesForm($widget)
	{
		$echo = '';

		// select how to render categories
		// for homepage suggested to render fixed 2 levels
		// for sidebar dynamic
		$display_mode = '<select name="display_mode" id="display_mode">
							<option value="dynamic">' . __('Dynamic') . '</option>
							<option value="dynamic_minimal">' . __('Dynamic minimal') . '</option>
							<option value="fixed_1">' . __('Fixed {num} levels', array('{num}' => 1)) . '</option>
							<option value="fixed_2">' . __('Fixed {num} levels', array('{num}' => 2)) . '</option>
							<option value="fixed_3">' . __('Fixed {num} levels', array('{num}' => 3)) . '</option>
							<option value="fixed_all">' . __('Fixed {num} levels', array('{num}' => __('All'))) . '</option>
						</select>
						<small>
							</br><b>' . __('Dynamic') . '</b>: ' . __('suggested for sidebar') . '
							</br><b>' . __('Fixed {num} levels', array('{num}' => 2)) . '</b>: ' . __('suggested for home page') . '
						</small>';
		// mark selected option 
		$sel_display_mode = $widget->getOption('display_mode', false, true);
		$display_mode = str_replace('value="' . $sel_display_mode . '">', 'value="' . $sel_display_mode . '" selected="selected">', $display_mode);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="display_mode">' . __('Display mode') . '</label>',
					'{field}'	 => $display_mode
		));

		if (!Config::option('disable_ad_counting'))
		{

			// display ad count 
			$echo .= self::_formatFormRow(array(
						'{field}' => self::_formatFormCheckbox(array(
							'{label}'	 => __('Display ad count'),
							'{name}'	 => 'display_ad_count',
							'{checked}'	 => $widget->getOption('display_ad_count', false, true)
						))
			));

			// hide empty categories
			$echo .= self::_formatFormRow(array(
						'{field}' => self::_formatFormCheckbox(array(
							'{label}'	 => __('Hide empty records'),
							'{name}'	 => 'hide_empty',
							'{checked}'	 => $widget->getOption('hide_empty', false, true)
						))
			));
		}

		// categories related to root or current category 
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Related to active category'),
						'{name}'	 => 'relative_to_current',
						'{checked}'	 => $widget->getOption('relative_to_current', false, true)
					))
		));

		// drilldown custom_field_drilldown 
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Custom fields drill down'),
						'{name}'	 => 'custom_field_drilldown',
						'{checked}'	 => $widget->getOption('custom_field_drilldown', false, true)
					))
		));
		// category_description[en][name]
		return self::defaultForm($widget) . $echo;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetCategoriesSubmit_($widget)
	{
		return self::defaultSubmit($widget, array('rss_latest', 'rss_featured', 'rss_latest_active', 'rss_featured_active'));
	}

	/**
	 * apply regular widget title and wrap in widget div,body
	 * 
	 * @param Widget $widget
	 * @param string $body
	 * @return string 
	 */
	private static function _applyWidgetFormat($widget, $body, $sugested_title = '')
	{
		// if has body 
		if (strlen($body))
		{
			$widget_title = '';
			if (!$widget->getOption('hide_title', false, true))
			{
				$widget_title = $widget->getOption('title', null, true);
				// if no title match found and some title suggested then use suggested
				if (!strlen($widget_title) && strlen($sugested_title))
				{
					$widget_title = $sugested_title;
				}

				if (strlen($widget_title))
				{
					$widget_title = '<h3 class="widget_title">' . View::escape($widget_title) . '</h3>';
				}
			}

			return '<div class="widget widget_' . $widget->type_id . '  widget_' . $widget->id . '">
					' . $widget_title . '
					<div class="widget_body">
						' . $body . '
						<div class="clear"></div>
					</div>
				</div>';
		}

		return '';
	}

	/**
	 *
	 * @param array $categories
	 * @param Widget $widget
	 * @param int $parent_id
	 * @return string 
	 */
	private static function _widgetCategories($categories, $widget, $parent_id = 0)
	{

		Benchmark::cp('_widgetCategories($categories, $widget,' . $parent_id . '):START');

		$selected_location = self::$_vars['selected_location'];
		$selected_category = self::$_vars['selected_category'];
		$cq = self::$_vars['cq'];
		$relative_to_current = $widget->getOption('relative_to_current', false, true);
		// should urls use custom query 
		$use_relative_url = ($relative_to_current && $cq->url2vars);
		// can we use cq for counting 
		$use_cq = $use_relative_url ? $cq : null;

		$return = '';
		$return_arr = array();

		if ($selected_location)
		{
			$arr_page_title[0] = '';
			$arr_page_title[1] = View::escape(Location::getName($selected_location));
		}

		$hide_empty = $widget->getOption('hide_empty', false, true);
		$display_mode = $widget->getOption('display_mode', false, true);
		$custom_field_drilldown = $widget->getOption('custom_field_drilldown', false, true);
		$display_ad_count = $widget->getOption('display_ad_count', false, true);
		$disable_ad_counting = Config::option('disable_ad_counting');

		// check if location has locations
		if (isset($categories[$parent_id]))
		{
			foreach ($categories[$parent_id] as $l)
			{
				if ($selected_category->id == $l->id)
				{
					$class = ' class="active"';
					$is_sel = true;
				}
				else
				{
					$class = '';
					$is_sel = false;
				}

				$sub_loc = false;

				switch ($display_mode)
				{
					/*
					  dynamic
					  dynamic_minimal
					  fixed_1
					  fixed_2
					  fixed_3
					  fixed_all
					 */
					case 'fixed_1':
						// display only one level then no subs						
						break;
					case 'fixed_2':
						//print_r(Category::getParents($l, Category::STATUS_ENABLED));
						// display 2 levels home and sub 
						if (count(Category::getParents($l, Category::STATUS_ENABLED)) < 1)
						{
							$sub_loc = self::_widgetCategories($categories, $widget, $l->id);
						}
						break;
					case 'fixed_3':
						// display 3 levels home and sub 
						if (count(Category::getParents($l, Category::STATUS_ENABLED)) < 2)
						{
							$sub_loc = self::_widgetCategories($categories, $widget, $l->id);
						}
						break;
					case 'fixed_all':
						// display everything
						$sub_loc = self::_widgetCategories($categories, $widget, $l->id);
						break;
					case 'dynamic_minimal':
					case 'dynamic':
					default:
						// decide if need to open sublocation 
						if ($is_sel || Category::isChildOf($l, $selected_category, Category::STATUS_ENABLED))
						{
							$sub_loc = self::_widgetCategories($categories, $widget, $l->id);
						}
				}

				// display for all 
				// if minimal then display if selected or has sub. which will not display neighbors for parents
				if ($display_mode != 'dynamic_minimal' || ($sub_loc || $is_sel || !$selected_category->id || $selected_category->id == $l->parent_id))
				{

					$l_name = View::escape(Category::getName($l));
					if ($selected_location)
					{
						$arr_page_title[0] = $l_name;
						$page_title = ' title="' . Config::buildTitle($arr_page_title) . '"';
					}
					else
					{
						$page_title = '';
					}

					// 
					// display count 
					$ad_count = '';
					if ($display_ad_count)
					{
						$ad_count_ = AdCategoryCount::getCountCategory($selected_location->id, $l->id, $use_cq);
						if ($ad_count_)
						{
							$ad_count = ' <span class="item_count">' . number_format($ad_count_) . '</span>';
						}
					}

					// hide empty category links 
					$hide_record = false;
					if ($hide_empty && !$disable_ad_counting)
					{
						$ad_count_ = AdCategoryCount::getCountCategory($selected_location->id, $l->id, $use_cq);
						$hide_record = $ad_count_ > 0 ? false : true;
					}


					if (!$hide_record)
					{
						if ($use_relative_url)
						{
							// we have custom query and set to use relatie then use relative url to custom query
							$url = Permalink::vars2url($cq->url2vars->search_params, array('category_id' => $l->id));
						}
						else
						{
							$url = Location::url($selected_location, $l);
						}

						$return_arr[$l->id] = '<li><a href="' . $url . '"' . $class . $page_title . '>'
								. $l_name . '</a>'
								. $ad_count . $sub_loc . '</li>';

						$return_sub[$l->id] = $sub_loc;
						$last_usable_id = $l->id;
					}
				}
			}

			if ($return_arr)
			{
				if ($hide_empty && !$disable_ad_counting && count($return_arr) < 2)
				{
					// only one record, so show only sub if exists, because $hide_empty is set
					// if we have subloc then return it
					$sub_loc = $return_sub[$last_usable_id];
					if ($sub_loc !== false)
					{
						$return = $sub_loc;
					}
					else
					{
						// generate sublock and return it 
						return self::_widgetCategories($categories, $widget, $last_usable_id);
					}
				}
				else
				{
					$return = implode('', $return_arr);
				}
			}

			if ($return)
			{
				// display all link for dynamic_minimal mode for better navigation
				if ($parent_id == 0 && $display_mode == 'dynamic_minimal' && $selected_category)
				{
					$ad_count = '';
					if ($display_ad_count)
					{
						$ad_count_ = AdCategoryCount::getCountCategory($selected_location->id, 0, $use_cq);
						if ($ad_count_)
						{
							$ad_count = ' <span class="item_count">' . number_format($ad_count_) . '</span>';
						}
					}

					if ($use_relative_url)
					{
						// we have custom query and set to use relatie then use relative url to custom query
						$url = Permalink::vars2url($cq->url2vars->search_params, array('category_id' => 0));
					}
					else
					{
						$url = Location::url($selected_location);
					}

					// display all categories link for better navigation
					$return = '<li><a href="' . $url . '">'
							. __('All categories') . '</a>'
							. $ad_count . '<ul>' . $return . '</ul></li>';
				}

				$return = '<ul>' . $return . '</ul>';
			}
		}


		// check if no result then display custom field drill down with counts
		$custom_query = self::$_vars['cq']->query;
		$total_ads = self::$_vars['total_ads'];




		if ($return === '' && $custom_field_drilldown && strlen($custom_query) && $total_ads > 10)
		{
			$return = self::_widgetCategoriesCatfields($parent_id);
		}

		Benchmark::cp('_widgetCategories($categories, $widget,' . $parent_id . '):END');

		return $return;
	}

	/**
	 * generate custom field links for current query 
	 * 
	 * @return string 
	 */
	private static function _widgetCategoriesCatfields($category_id)
	{
		Benchmark::cp('_widgetCategoriesCatfields():START');

		$return = '';
		$used_estimate = false;

		$selected_location = self::$_vars['selected_location'];

		//$catfield = self::$_vars['catfield'];
		// use catfield for requested category
		$catfield = CategoryFieldRelation::getCatfields($selected_location->id, $category_id, true, true);

		// modify custom query 
		$cq = self::$_vars['cq'];
		// use cached ids or custom query itself
		if ($cq->result->is_all_ids)
		{
			$custom_query = $cq->result->ids_1k_str;
			$custom_query_vals = array();
		}
		else
		{

			// use estimate, it is faster
			// if custom query used then on page load custom search, total count and count by custom fields performs heawy DB queries. 
			// each query taking 1-2 sec. so in total will wxceed 5 sec. which is not aceptible. 
			// so estimate value using loaded ids, and total count
			$used_estimate = true;
			$custom_query = $cq->result->ids_1k_str;
			$custom_query_vals = array();

			/*
			  $custom_query = $cq->query;
			  $custom_query = str_replace('ad.*', 'ad.id', $custom_query);
			  $custom_query_vals = $cq->values;
			 */
		}


		foreach ($catfield as $cf)
		{
			if (($cf->AdField->type === AdField::TYPE_RADIO || $cf->AdField->type === AdField::TYPE_DROPDOWN) && !isset($_GET['cf'][$cf->adfield_id]))
			{
				// show as link 
				$return_long = '';
				$count_short = 0;
				$count_total = 0;
				// number of available values
				$count_all = count($cf->AdField->AdFieldValue);
				if ($count_all > 1)
				{
					$af = $cf->AdField;
					// count custom field values 
					/* SELECT count(ad_id) as num,val
					  FROM cb_ad_field_relation
					  WHERE field_id=25 AND ad_id IN (SELECT ad.id
					  FROM cb_ad ad, cb_ad_field_relation afr0 , cb_ad_field_relation afr1 , cb_ad_field_relation afr2 ,
					  cb_ad_field_relation afr3 , cb_ad_field_relation afr4
					  WHERE ad.listed=1 AND ad.category_id='31'
					  AND ad.id=afr0.ad_id AND afr0.field_id='19' AND afr0.val='42'
					  AND ad.id=afr1.ad_id AND afr1.field_id='2' AND afr1.val='17'
					  AND ad.id=afr2.ad_id AND afr2.field_id='22' AND afr2.val='60'
					  AND ad.id=afr3.ad_id AND afr3.field_id='23' AND afr3.val='58'
					  AND ad.id=afr4.ad_id AND afr4.field_id='26' AND afr4.val='69')
					  GROUP BY val; */
					/** OLD WAY uses temp table, slow 
					  $query = "SELECT count(ad_id) as num,val
					  FROM " . AdFieldRelation::tableNameFromClassName('AdFieldRelation') . "
					  WHERE field_id=? AND ad_id IN ($custom_query)
					  GROUP BY val";
					  $values = array_merge(array($af->id), $custom_query_vals); */
					/* NEW WAY */
					/* SELECT 
					  count(val='110' OR NULL) as val1,
					  count(val='111' OR NULL) as val2,
					  count(val='159' OR NULL) as val3
					  FROM cb_ad_field_relation WHERE field_id='36'
					  AND ad_id IN (SELECT ad.id FROM cb_ad ad WHERE ad.listed=1 AND ad.category_id='34');
					 */
					$query_arr_select = array();
					foreach ($cf->AdField->AdFieldValue as $afv)
					{
						$afv_id = intval($afv->id);
						$query_arr_select[] = "count(val=" . $afv_id . " OR NULL) as val_" . $afv_id;
					}
					$query = "SELECT " . implode(', ', $query_arr_select) . "
						  FROM " . AdFieldRelation::tableNameFromClassName('AdFieldRelation') . "
						  WHERE field_id=? AND ad_id IN ($custom_query)";
					$values = array_merge(array($af->id), $custom_query_vals);

					/// use cache to store counts
					$cache_key = 'ad_count.afv.e' . intval($used_estimate) . '.' . SimpleCache::uniqueKey($query, $values);
					$afv_counted = SimpleCache::get($cache_key);
					if ($afv_counted === false)
					{
						$afv_counted = Record::query($query, $values);
						if ($afv_counted === false)
						{
							$afv_counted = array();
						}

						// convert to regular array of objects, to match old format.
						if (isset($afv_counted[0]))
						{
							$afvc_ = $afv_counted[0];
							$afv_counted = array();


							// complete estimage counts 
							$multiplier = 1;
							if ($used_estimate)
							{
								if ($cq->result->total > $cq->result->count * 0.01)
								{
									// multiply count with this amount. 
									$multiplier = $cq->result->total / $cq->result->count;
								}
							}


							foreach ($cf->AdField->AdFieldValue as $afv)
							{
								$afvc = new stdClass();
								$afvc->val = $afv->id;
								$afvc->num = round($afvc_->{"val_" . $afv->id} * $multiplier);
								if ($afvc->num)
								{
									$afv_counted[] = $afvc;
								}
							}
						}

						// 30 minute cache 
						SimpleCache::set($cache_key, $afv_counted, 1800);
					}

					if (count($afv_counted) > 1)
					{
						// we have more than 1 custom fields to show
						// convert to array
						$_afv_counted = array();
						foreach ($afv_counted as $afvc)
						{
							if ($afvc->num > 0)
							{
								$_afv_counted[$afvc->val] = $afvc->num;
							}
						}

						foreach ($af->AdFieldValue as $afv)
						{
							if (isset($_afv_counted[$afv->id]))
							{

								$l_name = AdFieldValue::getName($afv);
								$count_short += Ad::makeCustomValueMeaningfullCheck($l_name) ? 1 : 0;
								$count_total += 1;
								$ad_count = ' <span class="item_count">' . number_format($_afv_counted[$afv->id]) . '</span>';

								// format short value
								$l_url = Ad::makeCustomValueFilterLink($af, $afv->id, $l_name);
								$return .= '<li>' . $l_url . $ad_count . '</li>';

								// format value with field name 
								$l_url_long = Ad::makeCustomValueFilterLink($af, $afv->id, AdField::getName($af) . ': ' . $l_name);
								$return_long .= '<li>' . $l_url_long . $ad_count . '</li>';
							}
						}
					}
				}
			}

			if ($return)
			{
				if (round($count_short * 100 / $count_total) > 50)
				{
					$return = '<ul>' . $return_long . '</ul>';
				}
				else
				{
					$return = '<ul>' . $return . '</ul>';
				}

				// finish catfield loop
				break;
			}
		}

		Benchmark::cp('_widgetCategoriesCatfields():END');

		return $return;
	}

	/**
	 * locations widget, displays defined locations 
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetSearch($widget)
	{
		// theme supports version 2
		if (Theme::versionSupport(Theme::VERSION_SUPPORT_JQDROPDOWN))
		{
			return self::_widgetSearchDynamic($widget);
		}



		$selected_category = self::$_vars['selected_category'];
		$selected_location = self::$_vars['selected_location'];
		$selected_user = self::$_vars['selected_user'];
		$catfield = self::$_vars['catfield'];
		$page_type = self::$_vars['page_type'];
		$id_prefix = 'w' . $widget->id . '_';

		// get widget options 
		$display_mode = $widget->getOption('display_mode', false, true);
		$related_location = $widget->getOption('related_location', false, true);
		$related_category = $widget->getOption('related_category', false, true);
		$related_user = $widget->getOption('related_user', false, true);
		$display_with_photo = $widget->getOption('display_with_photo', false, true);




		$return_custom = '';

		$hidden_fields = array();


		// render old search widget 
		// set category 
		// get main categories to narrow down search results and ease query
		// check if there are any categories exists
		if (Category::hasValidPostingCategories() && $related_category)
		{
			if (!$selected_category->parent_id && $display_mode != 'simple')
			{
				$return_custom .= '<p>' . Category::selectBox($selected_category->id, 'category_id', Category::STATUS_ENABLED, true, __('All categories'), 1) . '</p>';
			}
			elseif ($selected_category)
			{
				$hidden_fields[] = '<input type="hidden" name="category_id" id="category_id" value="' . $selected_category->id . '" />';
			}
		}

		switch ($display_mode)
		{
			case 'simple':
				break;
			case 'advanced':
			default:
				$display_mode = 'advanced';

				// render custom fields 
				if ($catfield)
				{
					foreach ($catfield as $cf)
					{
						if ($cf->is_search)
						{
							// display search form for this custom field
							$name = 'cf[' . $cf->adfield_id . ']';
							switch ($cf->AdField->type)
							{
								case AdField::TYPE_DROPDOWN:
									$_label = '';
									break;
								default :
									$_label = '<label for="' . $id_prefix . $name . '">' . View::escape(AdField::getName($cf->AdField)) . ':</label> ';
							}
							$return_custom .= '<p>' . $_label
									. AdField::htmlField($cf, '', true, $id_prefix) . '</p>';
						}
					}
				}
		}

		// set location
		if ($selected_location && $related_location)
		{
			$hidden_fields[] = '<input type="hidden" name="location_id" id="location_id" value="' . $selected_location->id . '" />';
		}

		// set user if it is not ad page 
		if ($selected_user && $related_user && $page_type != IndexController::PAGE_TYPE_AD)
		{
			$hidden_fields[] = '<input type="hidden" name="user_id" id="user_id" value="' . $selected_user->id . '" />';
		}


		// get freshness values 
		$arr_freshness = Widget::freshnessValues();
		if ($arr_freshness)
		{

			// freshness select box
			$freshness = intval($_GET['freshness']);
			$select_freshness = '';

			foreach ($arr_freshness as $days => $label)
			{
				$key = 'display_freshness_' . $days;
				if ($widget->getOption($key, false, true))
				{
					$select_freshness .= '<option value="' . View::escape($days) . '"'
							. ($freshness == $days ? ' selected="selected"' : '') . '>'
							. View::escape($label) . '</option>';
				}
			}

			if ($select_freshness)
			{
				// add all as default value
				$return_custom .= '<p>'
						. '<select name="freshness" id="' . $id_prefix . 'freshness">'
						. '<option value="">' . View::escape(__('Freshness')) . '</option>'
						. $select_freshness
						. '</select>'
						. '</p>';
			}
		}/* /$arr_freshness */


		// only with images
		if ($display_with_photo)
		{
			if ($version2_support)
			{
				$return_custom .= '<p>'
						. '<label class="input-checkbox">'
						. '<input type="checkbox" name="with_photo" id="with_photo" value="1" '
						. ($_GET['with_photo'] ? 'checked="checked"' : '') . ' /> '
						. '<span class="checkmark"></span>'
						. __('with photo') . '</label>'
						. '</p>';
			}
			else
			{
				$return_custom .= '<p><label for="with_photo"><input type="checkbox" name="with_photo" id="with_photo" value="1" '
						. ($_GET['with_photo'] ? 'checked="checked"' : '') . ' /> '
						. __('with photo') . '</label></p>';
			}
		}

		$return = '<form action="' . Language::get_url('search/') . '" method="get" id="search_form ' . View::escape($display_mode) . '">	
					<p><input type="text" name="q" id="q" value="' . View::escape($_GET['q']) . '" placeholder="' . __('Search') . '" /></p>'
				. $return_custom
				. implode('', $hidden_fields)
				. '<p><input type="submit" name="s" id="s" value="' . __('Search') . '" /></p>
					</form>';


		return self::_applyWidgetFormat($widget, $return);
	}

	/**
	 * Dynamic search widget. Used if supported by theme
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	private static function _widgetSearchDynamic($widget)
	{
		$selected_category = self::$_vars['selected_category'];
		$selected_location = self::$_vars['selected_location'];
		$selected_user = self::$_vars['selected_user'];
		$catfield = self::$_vars['catfield'];
		$page_type = self::$_vars['page_type'];
		$id_prefix = 'w' . $widget->id . '_';

		// get widget options 
		$display_mode = $widget->getOption('display_mode', false, true);
		$related_location = $widget->getOption('related_location', false, true);
		$related_category = $widget->getOption('related_category', false, true);
		$related_user = $widget->getOption('related_user', false, true);
		$display_with_photo = $widget->getOption('display_with_photo', false, true);


		$return_custom = '';

		$hidden_fields = array();


		// render version 2 of seearch form 
		// set location
		if (Location::hasValidPostingLocations())
		{

			if ($selected_location && $related_location)
			{
				$location_id = $selected_location->id;
			}
			else
			{
				$location_id = 0;
			}

			// show dropdown location 
			$return_custom .= '<p>'
					. '<input name="location_id" 
								value="' . View::escape($location_id) . '" 
								data-src="' . Config::urlJson() . '"
								data-key="location"
								data-selectalt="1"
								data-rootname="' . View::escape(__('All locations')) . '"
								data-currentname="' . View::escape(Location::getNameById($location_id)) . '"
								data-allpattern="' . View::escape(__('All <b>{name}</b>')) . '"
								data-allallow="1"
								class="display-none"
								>'
					. '</p>';
		}
		// check if there are any categories exists
		if (Category::hasValidPostingCategories())
		{

			if ($selected_category && $related_category)
			{
				$category_id = $selected_category->id;
			}
			else
			{
				$category_id = 0;
			}

			// show category select 
			$return_custom .= '<p>'
					. '<input name="category_id" 
								value="' . View::escape($category_id) . '" 
								data-src="' . Config::urlJson() . '"
								data-key="category"
								data-selectalt="1"
								data-rootname="' . View::escape(__('All categories')) . '"
								data-currentname="' . View::escape(Category::getNameById($category_id)) . '"
								data-allpattern="' . View::escape(__('All <b>{name}</b>')) . '"
								data-allallow="1"
								class="display-none"
								>'
					. '</p>';
		}


		$hidden_cf = '';
		// get current serch criteria 
		$name_val = Config::arr2NameVal($_GET, 'cf');
		if ($name_val)
		{
			foreach ($name_val as $k => $v)
			{
				$hidden_cf .= '<input type="hidden" name="' . $k . '" value="' . View::escape($v) . '" />';
			}
		}

		// show dynamic content 
		$return_custom .= '<!-- DYNAMIC CONTENT -->
				<div class="wrap_cf">
					<div class="diplay-none">' . $hidden_cf . '</div>
				</div>
				<script>
					// define dynamic template as var 
					// load custom fields json
					// then call rendering function stored in js 
					addLoadEvent(function () {
						if(typeof cb !=="undefined")
						{
							cb.cf.init({
								datasrc: "' . View::escape(Config::urlJson()) . '",
								template: "<p>"
										+ "<small class=\"muted block\">${label}</small>"
										+ "${input} ${help}"
										+ "</p>",
								parent: ".sf_' . $widget->id . '",
								target: ".wrap_cf",
								loc: "input[name=\"location_id\"]",
								cat: "input[name=\"category_id\"]",
								form_type:"search",
								onChange:function(){
										// reflow modal content 
										console.log("search:cf:onChange:resized");
										cb.modal.resize();
									},
								lng:{from:"' . View::escape(__('from')) . '",
									to:"' . View::escape(__('to')) . '",
									all:"' . View::escape(__('All')) . '"}
							});
						}
					});

				</script>
				<!-- DYNAMIC CONTENT ENDs -->';


		// set user if it is not ad page 
		if ($selected_user && $related_user && $page_type != IndexController::PAGE_TYPE_AD)
		{
			$hidden_fields[] = '<input type="hidden" name="user_id" id="user_id" value="' . $selected_user->id . '" />';
		}


		// get freshness values 
		$arr_freshness = Widget::freshnessValues();
		if ($arr_freshness)
		{

			// freshness select box
			$freshness = intval($_GET['freshness']);
			$select_freshness = '';

			foreach ($arr_freshness as $days => $label)
			{
				$key = 'display_freshness_' . $days;
				if ($widget->getOption($key, false, true))
				{
					$select_freshness .= '<option value="' . View::escape($days) . '"'
							. ($freshness == $days ? ' selected="selected"' : '') . '>'
							. View::escape($label) . '</option>';
				}
			}

			if ($select_freshness)
			{
				// add all as default value
				$return_custom .= '<p>'
						. '<select name="freshness" id="' . $id_prefix . 'freshness">'
						. '<option value="">' . View::escape(__('Freshness')) . '</option>'
						. $select_freshness
						. '</select>'
						. '</p>';
			}
		}/* /$arr_freshness */


		// only with images
		if ($display_with_photo)
		{
			$return_custom .= '<p>'
					. '<label class="input-checkbox">'
					. '<input type="checkbox" name="with_photo" id="with_photo" value="1" '
					. ($_GET['with_photo'] ? 'checked="checked"' : '') . ' /> '
					. '<span class="checkmark"></span>'
					. __('with photo') . '</label>'
					. '</p>';
		}


		$total_ads = Ad::countBy();
		$placeholder = __('Search {num} items', array(
			'{num}' => number_format($total_ads)
		));




		if ($display_mode === 'simple')
		{
			// simple search display minimal input with filter option 
			$return = '<form action="' . Language::get_url('search/') . '" method="get" class="search_form ' . View::escape($display_mode) . ' sf_' . $widget->id . ' clearfix">'
					. '<p>'
					. '<span class="input-group input-group-block search_form_main">'
					. '<input type="search" name="q" id="q" value="' . View::escape($_GET['q'])
					. '" placeholder="' . View::escape($placeholder) . '" class="input input-long" '
					. ' aria-label="' . View::escape($placeholder) . '" />'
					. '<button class="button white search_form_toggle" type="button" title="' . View::escape(__('Search options')) . '"><i class="fa fa-sliders" aria-hidden="true"></i></button>'
					. '<button class="button" type="submit" title="' . View::escape(__('Search')) . '"><i class="fa fa-search" aria-hidden="true"></i></button>'
					. '</span>'
					. '</p>'
					. '<div class="search_form_extra">'
					. $return_custom
					. implode('', $hidden_fields)
					. '<p>'
					. '<button type="submit" name="s" id="s2" class="button">'
					. '<i class="fa fa-search" aria-hidden="true"></i> ' . __('Search') . '</button>'
					. '<button type="button" name="s" id="s" class="button link cancel">'
					. __('Cancel') . '</button>'
					. '</p>'
					. '</div>'
					. '</form>';
		}
		else
		{
			// display expanded search form 
			$return = '<form action="' . Language::get_url('search/') . '" method="get" class="search_form ' . View::escape($display_mode) . ' sf_' . $widget->id . ' clearfix">'
					. '<p><input type="search" name="q" id="q" value="' . View::escape($_GET['q'])
					. '" placeholder="' . View::escape($placeholder) . '" class="input input-long" /></p>'
					. $return_custom
					. implode('', $hidden_fields)
					. '<p><button type="submit" name="s" id="s" class="button block">'
					. '<i class="fa fa-search" aria-hidden="true"></i> ' . __('Search') . '</button></p>'
					. '</form>';
		}


		return self::_applyWidgetFormat($widget, $return);
	}

	/**
	 * Parse display_freshness value from string to array
	 * 
	 * @return array
	 */
	static public function freshnessValues()
	{
		if (!isset(self::$_arr_freshness))
		{
			self::$_arr_freshness = array(
				1	 => __('last 24 hours'),
				3	 => __('last 3 days'),
				7	 => __('last 7 days'),
				30	 => __('last 30 days'),
				90	 => __('last 90 days'),
				365	 => __('last 1 year')
			);
		}
		return self::$_arr_freshness;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetSearchForm($widget)
	{
		// TODO : finish widgetSearchForm AND widgetSearchSubmit. display form, submit and apply to widget 
		$echo = '';

		// select how to render search form		
		$display_mode = '<select name="display_mode" id="display_mode">
							<option value="simple">' . __('Simple') . '</option>
							<option value="advanced">' . __('Advanced') . '</option>
						</select>';
		// mark selected option 
		$sel_display_mode = $widget->getOption('display_mode', false, true);
		$display_mode = str_replace('value="' . $sel_display_mode . '">', 'value="' . $sel_display_mode . '" selected="selected">', $display_mode);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="display_mode">' . __('Display mode') . '</label>',
					'{field}'	 => $display_mode
		));


		// relate search to location, category, user
		$relation_types = array(
			'related_location'	 => __('Related to active location'),
			'related_category'	 => __('Related to active category'),
			'related_user'		 => __('Related to active user')
		);

		foreach ($relation_types as $k => $v)
		{
			$echo .= self::_formatFormRow(array(
						'{field}' => self::_formatFormCheckbox(array(
							'{label}'	 => $v,
							'{name}'	 => $k,
							'{checked}'	 => $widget->getOption($k, false, true)
						))
			));
		}

		// display freshness 
		$arr_freshness = Widget::freshnessValues();
		$freshness_str = '';
		foreach ($arr_freshness as $days => $label)
		{
			$key = 'display_freshness_' . $days;
			$freshness_str .= self::_formatFormCheckbox(array(
						'{label}'	 => View::escape($label),
						'{name}'	 => $key,
						'{checked}'	 => $widget->getOption($key, false, true)
			));
		}
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label>' . __('Freshness') . '</label>',
					'{field}'	 => $freshness_str
		));

		// display ads with images only checkbox 
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Display image checkbox'),
						'{name}'	 => 'display_with_photo',
						'{checked}'	 => $widget->getOption('display_with_photo', false, true)
					))
		));

		return self::defaultForm($widget) . $echo;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetSearchSubmit($widget)
	{
		return self::defaultSubmit($widget, array('related_location', 'related_category', 'related_user', 'display_with_photo'));
	}

	/**
	 * locations widget, displays defined locations 
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetText($widget)
	{
		$return = $widget->getOption('text', null);

		// if this is html then display as it is , if not then add br on line breaks and render as text 
		$is_html = $widget->getOption('is_html');
		switch ($is_html)
		{
			case 'php':
				// evaluate php script 
				if (strlen($return))
				{
					// prevent PHP execution in DEMO mode
					if (DEMO)
					{
						Validation::getInstance()->set_info(__('PHP widgets disabled in DEMO'));
						$return = '<p>' . __('PHP widgets disabled in DEMO') . '</p>' . View::escape($return);
					}
					else
					{
						ob_start();
						eval('?>' . $return);
						$return = ob_get_clean();
					}
				}
				break;
			case 'html':
			case 1:
				// do nothing
				break;
			default:
				// plain text 
				$return = nl2br(View::escape($return));
		}

		return self::_applyWidgetFormat($widget, $return);
	}

	/**
	 *
	 * @param Widget $widget 
	 */
	public static function widgetTextSubmit($widget)
	{
		// prepre input for storing in DB, check for xss
		self::defaultSubmit($widget);

		$is_html = $widget->data['is_html'];
		switch ($is_html)
		{
			case 'php':
			case 'html':
			case 1:
				// restore text field as html, do not perform xss check
				$input = new Input();
				$input->use_xss_clean = false;
				$widget->_options['text'] = $input->_clean_input_data($widget->data['text']);
				break;
			default:
			// do nothing xss filtered already 
		}

		return true;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetTextForm($widget)
	{
		$language = Language::getLanguages();
		$echo = '';
		$tab_key = 'widget_' . $widget->id . '_';

		foreach ($language as $lng)
		{
			$lng_label = Language::tabsLabelLngInfo($language, $lng);

			//$echo_tabs .= Language::formatTab($lng, $key);
			$echo .= self::_formatFormRow(array(
						'{label}'	 => '<label for="text[' . $lng->id . ']">' . __('Text') . $lng_label . '</label>',
						'{field}'	 => '<textarea name="text[' . $lng->id . ']" id="text[' . $lng->id . ']">'
						. View::escape($widget->getOption('text', $lng->id))
						. '</textarea>',
						'{class}'	 => Language::tabsTabKey($tab_key, $lng)
			));
		}

		$is_html = $widget->getOption('is_html');
		switch ($is_html)
		{
			case 'html':
			case 1:
				$is_html_arr['html'] = ' selected="selected"';
				break;
			case 'php':
				$is_html_arr['php'] = ' selected="selected"';
				break;
			default:
				$is_html_arr['text'] = ' selected="selected"';
				break;
		}


		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="is_html">' . __('Text format') . '</label>',
					'{field}'	 => '
							<select name="is_html" id="is_html">
								<option value="text"' . $is_html_arr['text'] . '>' . __('Plain text') . '</option>
								<option value="html"' . $is_html_arr['html'] . '>' . __('HTML / javascript') . '</option>
								<option value="php"' . $is_html_arr['php'] . '>' . __('PHP') . '</option>
							</select>'
		));

		return self::defaultForm($widget) . $echo;
	}

	/**
	 * Separator widget, groups previous widgets to display side by side
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetSeperator($widget)
	{
		// check previus widgets in same location and add appropriate class. 
		// self::$_vars['widget_location'][$location][$widget->id] = $widget;
		if (isset(self::$_vars['widget_location'][$widget->location]))
		{
			$arr = array();
			foreach (self::$_vars['widget_location'][$widget->location] as $w)
			{
				if (!isset($w->grouped) && $w->type_id != 'seperator' && strlen($w->render))
				{
					$arr[] = $w;
				}
			}

			$count = count($arr);
			if ($count)
			{
				// default number of cols
				$class = '';
				$cols = 1;


				if ($count > 3)
				{
					$class = 'four_up';
					$cols = 4;
				}
				elseif ($count == 3)
				{
					$class = 'three_up';
					$cols = 3;
				}
				elseif ($count == 2)
				{
					$class = 'two_up';
					$cols = 2;
				}


				if ($cols > 1)
				{
					$i = 0;
					foreach ($arr as $w)
					{
						$i++;
						$class_current = $class;
						if ($i % $cols == 1)
						{
							$class_current = $class . ' first';
						}
						$w->render = str_replace('<div class="widget ', '<div class="' . $class_current . ' widget ', $w->render);
						$w->grouped = true;
					}
					$return = '<div class="clear"></div>';
				}
				else
				{
					foreach ($arr as $w)
					{
						$w->grouped = true;
					}
					$return = '';
				}

				// do not group itself
				$widget->grouped = true;


				return $return;
			}
			// after last add clear
		}

		return '';
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetSeperatorForm($widget)
	{
		// display empty form because seperator dont have any visible element
		return '';
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetAds($widget)
	{
		// get latest ads 
		$selected_location = self::$_vars['selected_location'];
		$selected_category = self::$_vars['selected_category'];
		$selected_ad = self::$_vars['ad'];
		$number_of_ads = $widget->getOption('number_of_ads', false, true);
		$list_style = $widget->getOption('list_style', false, true);
		$list_mode = $widget->getOption('list_mode', false, true);
		$hit_period = $widget->getOption('hit_period', false, true);
		$display_view_more = $widget->getOption('display_view_more');
		$display_with_image_only = $widget->getOption('display_with_image_only') ? true : false;
		$prefer_unique = $widget->getOption('prefer_unique', false, true);
		$blend_featured = $widget->getOption('blend_featured', false, true);
		$appendAdpics_done = false;
		$return = '';


		// define featured count: min=1, max=10, use 33%
		if ($blend_featured)
		{
			$blend_featured_num = round($number_of_ads / 3);
			if ($blend_featured_num < 1)
			{
				$blend_featured_num = 1;
			}
			if ($blend_featured_num > 10)
			{
				$blend_featured_num = 10;
			}
		}

		switch ($list_mode)
		{
			case 'featured':
				//$ads = Ad::featuredByLocation($selected_location, $number_of_ads);
				$ads = Ad::latestAds($number_of_ads, array(
							'location'		 => $selected_location,
							'category'		 => $selected_category,
							'image'			 => $display_with_image_only,
							'featured'		 => 'featured',
							'prefer_unique'	 => $prefer_unique
				));
				// suggest better title for seo 
				if ($selected_location)
				{
					$suggested_title = __('Featured ads in {name}', array('{name}' => View::escape(Location::getName($selected_location))));
				}
				else
				{
					$suggested_title = __('Featured ads');
				}
				$blend_featured = 0;
				break;
			case 'hit':
				//$ads = Ad::featuredByLocation($selected_location, $number_of_ads);
				$ads = Ad::latestAds($number_of_ads, array(
							'location'		 => $selected_location,
							'category'		 => $selected_category,
							'image'			 => $display_with_image_only,
							'featured'		 => 'hit.' . $hit_period,
							'prefer_unique'	 => $prefer_unique
				));
				if ($blend_featured_num)
				{
					$ads_blend = Ad::latestAds($blend_featured_num, array(
								'location'		 => $selected_location,
								'category'		 => $selected_category,
								'image'			 => $display_with_image_only,
								'featured'		 => 'featured',
								'prefer_unique'	 => false
					));
				}
				// suggest better title for seo 
				if ($selected_location)
				{
					$suggested_title = __('Most viewed ads in {name}', array('{name}' => View::escape(Location::getName($selected_location))));
				}
				else
				{
					$suggested_title = __('Most viewed ads');
				}
				break;
			case 'viewed':
				// display recently viewed ads
				if ($display_with_image_only)
				{
					// get all ads and filter here, remove ads without image 
					$ads = Ad::viewedAds(100, $selected_ad->id);
					$ads = Ad::filterWithImageOnly($ads);
					$ads = array_slice($ads, 0, $number_of_ads);
					$appendAdpics_done = true;
				}
				else
				{
					$ads = Ad::viewedAds($number_of_ads, $selected_ad->id);
				}
				// suggest better title for seo 
				$suggested_title = __('Recently viewed');
				break;
			case 'related':
				// Better related ads uses relation between custom fields 
				if ($selected_ad)
				{
					// do not escape title here, escaped on html later. fixed in v.1.3.4
					$suggested_title = __('{name} related listings', array('{name}' => $selected_ad->title));
					$ads = AdRelated::append($selected_ad);
					if ($display_with_image_only)
					{
						// append images and remove ads without image 
						$ads = Ad::filterWithImageOnly($ads);
						$appendAdpics_done = true;
					}

					// if unique set then move unique values to the top 
					if ($prefer_unique)
					{

						$key_pattern = '';
						if (isset($prefer_unique['user']))
						{
							$key_pattern .= 'u{added_by}.';
						}
						if (isset($prefer_unique['location']))
						{
							$key_pattern .= 'l{location_id}.';
						}
						if (isset($prefer_unique['category']))
						{
							$key_pattern .= 'c{category_id}.';
						}

						$ads_preferred = array();
						$ads_rest = array();
						foreach ($ads as $ad)
						{
							$key = str_replace(array('{added_by}', '{location_id}', '{category_id}'), array($ad->added_by, $ad->location_id, $ad->category_id), $key_pattern);
							if (!isset($ads_preferred[$key]))
							{
								$ads_preferred[$key] = $ad;
							}
							else
							{
								$ads_rest[] = $ad;
							}
						}

						$ads = array_merge($ads_preferred, $ads_rest);
					}

					if (count($ads) < $number_of_ads)
					{
						// add more items from same category 
						$ads_more = Ad::latestAds($number_of_ads, array(
									'location'		 => $selected_location,
									'category'		 => $selected_category,
									'image'			 => $display_with_image_only,
									'prefer_unique'	 => $prefer_unique
						));

						if ($appendAdpics_done)
						{
							// append images to more 
							Ad::appendAdpics($ads_more);
						}

						// set ads by id 
						$ads_result = array();
						foreach ($ads as $ad)
						{
							if (!isset($ads_result[$ad->id]) && $selected_ad->id != $ad->id)
							{
								$ads_result[$ad->id] = $ad;
							}
						}

						foreach ($ads_more as $ad)
						{
							if (!isset($ads_result[$ad->id]) && $selected_ad->id != $ad->id)
							{
								$ads_result[$ad->id] = $ad;
							}
						}

						$ads = $ads_result;
						unset($ads_result);
						unset($ads_more);
					}
				}
				else
				{
					// no ad defined then get latest ads from related category and location 
					$suggested_title = __('Related listings');
					$ads = Ad::latestAds($number_of_ads, array(
								'location'		 => $selected_location,
								'category'		 => $selected_category,
								'image'			 => $display_with_image_only,
								'prefer_unique'	 => $prefer_unique
					));
				}
				// reduce to specified count
				if (count($ads) > $number_of_ads)
				{
					$ads = array_slice($ads, 0, $number_of_ads);
				}

				if ($blend_featured_num)
				{
					$ads_blend = Ad::latestAds($blend_featured_num, array(
								'location'		 => $selected_location,
								'category'		 => $selected_category,
								'image'			 => $display_with_image_only,
								'featured'		 => 'featured',
								'prefer_unique'	 => false
					));
				}
				// 
				break;
			case 'latest':
			default:
				//$ads = Ad::latestAds($number_of_ads, $selected_location);
				$ads = Ad::latestAds($number_of_ads, array(
							'location'		 => $selected_location,
							'image'			 => $display_with_image_only,
							'prefer_unique'	 => $prefer_unique
				));
				if ($blend_featured_num)
				{
					$ads_blend = Ad::latestAds($blend_featured_num, array(
								'location'		 => $selected_location,
								'category'		 => $selected_category,
								'image'			 => $display_with_image_only,
								'featured'		 => 'featured',
								'prefer_unique'	 => false
					));
				}
				// suggest better title for seo 
				if ($selected_location)
				{
					$suggested_title = __('Latest ads in {name}', array('{name}' => View::escape(Location::getName($selected_location))));
				}
				else
				{
					$suggested_title = __('Latest ads');
				}
				break;
		}


		if ($ads_blend)
		{
			if (!$ads)
			{
				$ads = array();
				foreach ($ads_blend as $ad)
				{
					// do not add self for related items
					if (!isset($ads[$ad->id]) && !($selected_ad->id == $ad->id && $list_mode == 'related'))
					{
						$ads[$ad->id] = $ad;
					}
				}
			}
			else
			{
				// mix blended featured items 
				// now append only non already existing ads 
				// to do it add ad index to ads array
				$ads_tmp = array();
				foreach ($ads as $ad)
				{
					$ads_tmp[$ad->id] = $ad;
				}
				$ads = $ads_tmp;
				$ads_tmp = array();
				foreach ($ads_blend as $ad)
				{
					// do not add self for related items
					if (!isset($ads[$ad->id]) && !($selected_ad->id == $ad->id && $list_mode == 'related'))
					{
						$ads_tmp[$ad->id] = $ad;
					}
				}
				$ads_blend = $ads_tmp;
				unset($ads_tmp);

				if ($ads_blend)
				{
					// now reduce main array by count of $ads_blend					
					$ads = array_slice($ads, 0, $number_of_ads - count($ads_blend));

					// insert an item from $ads_blend at random position 
					foreach ($ads_blend as $ad)
					{
						array_splice($ads, intval(rand(0, count($ads))), 0, array($ad));
					}
					//print_r($ads);
				}
			}
			unset($ads_blend);
			// items blended reset adpics
			$appendAdpics_done = false;
		}

		if ($ads)
		{
			if (!$appendAdpics_done)
			{
				// append images
				Ad::appendAdpics($ads);
				$appendAdpics_done = true;
			}

			// append users
			if (Adpics::isUserLogoRequired())
			{
				User::appendObject($ads, 'added_by', 'User');
			}

			// append AdFieldRelation
			AdFieldRelation::appendAllFields($ads);

			switch ($list_style)
			{
				case 'full':
					// list in table with custom fields
					// create seperate vars for this 
					$vars = array();
					$vars['ads'] = $ads;
					$vars['selected_category'] = self::$_vars['selected_category'];
					$vars['selected_location'] = self::$_vars['selected_location'];
					$vars['list_style'] = $list_style;

					// get custom fields for current location and category
					$vars['catfield'] = CategoryFieldRelation::getCatfields($selected_location->id, $selected_category->id, true, true);

					// render ads using same format from theme
					$return = View::renderAsSnippet('index/_listing', $vars);
					break;
				case 'thumbs':
				case 'carousel':
					$thumb_width = $widget->getOption('thumb_width', false, true);
					$thumb_height = $widget->getOption('thumb_height', false, true);
					$thumb_style = $widget->getOption('thumb_style', false, true);
					$old_thumbs_single = $widget->getOption('thumbs_single', false, true);

					if ($thumb_style === 'onerow')
					{
						$old_thumbs_single = 1;
					}
					elseif ($thumb_style == '' && $old_thumbs_single)
					{
						$thumb_style = 'onerow';
					}


					// new thumb style value
					$css_class = ($list_style === 'thumbs' ? ' thumb_style_' . ($thumb_style ? $thumb_style : 'grid') : '');
					// old compatible mode, use it for old themes that use default or own template and own css 
					// we cannot change their css and templates so leave this template unchanged in future as well 
					// or they must update their themes to latest version
					$css_class .= ($old_thumbs_single ? ' thumbs_single' : '');

					// try theme file first 
					if (file_exists(Theme::file('index/_listing_thumb')))
					{
						$return = View::renderAsSnippet('index/_listing_thumb', array(
									'ads'			 => $ads,
									'thumb_width'	 => $thumb_width,
									'thumb_height'	 => $thumb_height,
									'thumb_style'	 => $thumb_style,
									'thumbs_single'	 => $old_thumbs_single,
									'css_class'		 => $css_class,
									'list_style'	 => $list_style
						));
					}
					else
					{
						$thumb_width = $thumb_width ? $thumb_width : Config::option('ad_thumbnail_width');
						$thumb_height = $thumb_height ? $thumb_height : Config::option('ad_thumbnail_height');
						$_img_placeholder_src = Adpics::imgPlaceholder($thumb_width, $thumb_height);


						$return = '<div class="thumbs list_style_' . $list_style . $css_class . '">';
						foreach ($ads as $ad)
						{
							$price = AdFieldRelation::getPrice($ad);
							$_img_title = View::escape(Ad::getTitle($ad)) . ($price ? ' - ' . $price : '');

							$_img_thumb = Adpics::imgThumb($ad->Adpics, $thumb_width . 'x' . $thumb_height . 'x1', $ad->User, 'lazy', $ad);
							$thumb = '<img src="' . $_img_placeholder_src . '"'
									. ($_img_thumb ? ' class="lazy" data-src="' . $_img_thumb . '"' : '')
									. ' alt="' . $_img_title . '" '
									. ' width="' . $thumb_width . '"'
									. ' height="' . $thumb_height . '" />';

							$return .= '<a href="' . Ad::url($ad) . '" title="' . $_img_title . '" style="width:' . $thumb_width . 'px;">'
									. $thumb
									. '<span class="title">' . View::escape(Ad::getTitle($ad)) . '</span>'
									. ($price ? '<span class="price">' . View::escape($price) . '</span>' : '')
									. '</a> ';
						}
						$return .= '</div>';
					}
					break;
				case 'simple':
				default:
					//simple list 
					if (file_exists(Theme::file('index/_listing_simple')))
					{
						$return = View::renderAsSnippet('index/_listing_simple', array('ads' => $ads));
					}
					else
					{
						$return = '<ul class="list_style_simple">';
						$thumb_width = Config::option('ad_thumbnail_width');
						$thumb_height = Config::option('ad_thumbnail_height');
						$_img_placeholder_src = Adpics::imgPlaceholder($thumb_width, $thumb_height);

						foreach ($ads as $ad)
						{
							$_img_thumb = Adpics::imgThumb($ad->Adpics, '', $ad->User, 'lazy', $ad);
							if ($_img_thumb)
							{
								$thumb = '<img src="' . $_img_placeholder_src . '"'
										. ($_img_thumb ? ' class="lazy" data-src="' . $_img_thumb . '"' : '')
										. ' alt="' . View::escape(Ad::getTitle($ad)) . '"'
										. ' width="' . $thumb_width . '"'
										. ' height="' . $thumb_height . '"  />';

								$thumb = '<a href="' . Ad::url($ad) . '" class="thumb">'
										. $thumb
										. '</a>';
							}
							else
							{
								$thumb = '';
							}

							// contact 
							$contact = '';
							if ($ad->phone)
							{
								$contact = '<p class="contact_phone"><b>' . __('Phone') . ' :</b> ' . View::escape($ad->phone) . '</p>';
							}
							switch ($ad->showemail)
							{
								case Ad::SHOWEMAIL_YES:
									$contact .= '<p class="contact_email"><b>' . __('Contact') . ' :</b> <a href="mailto:' . View::escape($ad->email) . '">' . View::escape($ad->email) . '</a></p>';
									break;
								case Ad::SHOWEMAIL_FORM:
								case Ad::SHOWEMAIL_NO:
								default:
							}

							if (Config::option('view_contact_registered_only') && !AuthUser::isLoggedIn(false))
							{
								// display login link
								$contact = '<p><a href="' . Ad::url($ad, null, '?login=1') . '">' . __('Log in to view contact details') . '</a></p>';
							}

							// price
							$price = AdFieldRelation::getPrice($ad);
							if ($price)
							{
								$price = ' <span class="price">' . $price . '</span>';
							}

							$return .= '<li>';
							$return .= '<h2><a href="' . Ad::url($ad) . '">' . View::escape(Ad::getTitle($ad)) . '</a></h2>';
							$return .= '<p>' . $thumb . View::escape(Ad::snippet($ad, 50)) . ' ' . $price . '</p>' . $contact;
							$return .= '</li>';
						}
						$return .= '</ul>';
					}
			}

			// view more link 
			if ($display_view_more)
			{
				if (!$selected_category)
				{
					$selected_category = Category::objAll();
				}

				$url = Location::url($selected_location, $selected_category);

				// try theme file first 
				if (file_exists(Theme::file('index/_view_more')))
				{
					$return .= View::renderAsSnippet('index/_view_more', array('url' => $url));
				}
				else
				{
					$return .= '<p class="view_more"><a href="' . $url . '">' . __('View more') . ' &raquo;</a></p>';
				}
			}

			// send ads for further customization in theme
			$widget->Ad = $ads;
		}

		return self::_applyWidgetFormat($widget, $return, $suggested_title);
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetAdsForm($widget)
	{
		$echo = '';


		// select how to list
		$list_style = '
			<select name="list_style" id="list_style">
				<option value="simple">' . __('Simple') . '</option>
				<option value="full">' . __('Full') . '</option>
				<option value="thumbs">' . __('Gallery') . '</option>
				<option value="carousel">' . __('Carousel') . '</option>
			</select>
			<small>
				</br><b>' . __('Simple') . '</b>: ' . __('suggested for sidebar') . '
				</br><b>' . __('Full') . '</b>: ' . __('suggested for home page') . '
			</small>';
		// mark selected option 
		$sel_list_style = $widget->getOption('list_style', false, true);
		$list_style = str_replace('value="' . $sel_list_style . '">', 'value="' . $sel_list_style . '" selected="selected">', $list_style);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="list_style">' . __('List style') . '</label>',
					'{field}'	 => $list_style
		));


		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="thumb_width">' . __('Image width') . '</label>',
					'{field}'	 => '<input type="number" name="thumb_width" id="thumb_width" value="' . $widget->getOption('thumb_width', false, true) . '" class="input input-short" />',
					'{class}'	 => 'thumb_size',
		));


		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="thumb_height">' . __('Image height') . '</label>',
					'{field}'	 => '<input type="number" name="thumb_height" id="thumb_height" value="' . $widget->getOption('thumb_height', false, true) . '" class="input input-short" />',
					'{class}'	 => 'thumb_size',
		));

		// thumb_style: none- vertical max 2 column, horizontal, vertical max one column
		/**
		 * thumb_style		[col x row]:
		 * 		grid -	  [6,4,2 x many] 
		 * 		onerow -   [many x 1] 
		 * 		onecolumn -   [1 x many]
		 * 		none -     [many x many]
		 */
		$thumb_style = '
			<select name="thumb_style" id="thumb_style">
				<option value="grid">' . __('Grid') . '</option>
				<option value="onerow">' . __('One row') . '</option>
				<option value="onecolumn">' . __('One column') . '</option>
				<option value="none">' . __('None') . '</option>
			</select>';
		// mark selected option 
		$sel_thumb_style = $widget->getOption('thumb_style', false, true);
		// get old value 
		$old_thumbs_single = $widget->getOption('thumbs_single', false, true);
		if ($sel_thumb_style == '' && $old_thumbs_single)
		{
			// convert old value to new value 
			$sel_thumb_style = 'onerow';
		}
		$thumb_style = str_replace('value="' . $sel_thumb_style . '">', 'value="' . $sel_thumb_style . '" selected="selected">', $thumb_style);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="thumb_style">' . __('Gallery style') . '</label>',
					'{field}'	 => $thumb_style,
					'{class}'	 => 'thumb_style',
		));

		// select what to list 
		$list_mode = '
			<select name="list_mode" id="list_mode">
				<option value="latest">' . __('Latest') . '</option>
				<option value="featured">' . __('Featured') . '</option>
				<option value="related">' . __('Related') . '</option>
				<option value="viewed">' . __('Recently viewed') . '</option>
				<option value="hit">' . __('Most viewed') . '</option>
			</select>';
		// mark selected option 
		$sel_list_mode = $widget->getOption('list_mode', false, true);
		$list_mode = str_replace('value="' . $sel_list_mode . '">', 'value="' . $sel_list_mode . '" selected="selected">', $list_mode);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="list_mode">' . __('List mode') . '</label>',
					'{field}'	 => $list_mode
		));


		// select most viewed (hit) period
		$hit_period = '
			<select name="hit_period" id="hit_period">
				<option value="a">' . __('All') . '</option>
				<option value="d">' . __('Day') . '</option>
				<option value="w">' . __('Week') . '</option>
				<option value="m">' . __('Month') . '</option>
				<option value="y">' . __('Year') . '</option>
			</select>';
		// mark selected option 
		$sel_hit_period = $widget->getOption('hit_period', false, true);
		$hit_period = str_replace('value="' . $sel_hit_period . '">', 'value="' . $sel_hit_period . '" selected="selected">', $hit_period);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="hit_period">' . __('Period') . '</label>',
					'{field}'	 => $hit_period,
					'{class}'	 => 'hit_period'
		));


		// number of ads to display 
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="number_of_ads">' . __('Number of ads') . '</label>',
					'{field}'	 => '<input type="number" name="number_of_ads" id="number_of_ads" value="' . $widget->getOption('number_of_ads', false, true) . '" class="input input-short" />'
		));


		// prefer unique by user, category, location
		$sel_prefer_unique = $widget->getOption('prefer_unique', false, true);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="prefer_unique_current">' . __('Prefer ads with unique') . '</label>',
					'{field}'	 => self::_formatFormCheckbox(array(
						'{label}'	 => __('User'),
						'{name}'	 => 'prefer_unique[user]',
						'{checked}'	 => isset($sel_prefer_unique['user'])
					))
					. self::_formatFormCheckbox(array(
						'{label}'	 => __('Location'),
						'{name}'	 => 'prefer_unique[location]',
						'{checked}'	 => isset($sel_prefer_unique['location'])
					))
					. self::_formatFormCheckbox(array(
						'{label}'	 => __('Category'),
						'{name}'	 => 'prefer_unique[category]',
						'{checked}'	 => isset($sel_prefer_unique['category'])
					))
		));


		// blend featured items checkbox 	
		$echo .= self::_formatFormRow(array(
					'{field}'	 => self::_formatFormCheckbox(array(
						'{label}'	 => __('Blend featured items'),
						'{name}'	 => 'blend_featured',
						'{checked}'	 => $widget->getOption('blend_featured', false, true)
					)),
					'{class}'	 => 'blend_featured'
		));

		// display ads with image only
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Display ads with image'),
						'{name}'	 => 'display_with_image_only',
						'{checked}'	 => $widget->getOption('display_with_image_only', false, true)
					))
		));

		// display view more link 
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Display "View more" link'),
						'{name}'	 => 'display_view_more',
						'{checked}'	 => $widget->getOption('display_view_more', false, true)
					))
		));


		return self::defaultForm($widget) . $echo;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetAdsSubmit($widget)
	{
		// number of ads to display set maximum as 100
		if ($widget->data['number_of_ads'] > 100)
		{
			$widget->data['number_of_ads'] = 100;
		}
		elseif ($widget->data['number_of_ads'] < 1)
		{
			// set default number
			$widget->data['number_of_ads'] = intval($widget->typeGet()->options['number_of_ads']);
		}

		// store only supported hit period. do not allow any other value 
		$arr_hit_period = array(
			'a'	 => 1,
			'd'	 => 1,
			'w'	 => 1,
			'm'	 => 1,
			'y'	 => 1
		);
		if (!isset($arr_hit_period[$widget->data['hit_period']]))
		{
			$widget->data['hit_period'] = 'a';
		}

		return self::defaultSubmit($widget);
	}

	/**
	 * Get widget option that is stored in DB, unserialize and display for given locale
	 * 
	 * @param string $name
	 * @param string $lng
	 * @param bool $apply_default
	 * @return string 
	 */
	public function getOption($name, $lng = false, $apply_default = false)
	{
		// unserialize options
		$this->_unsetializeOptions();

		$return = $this->_options[$name];


		if (is_null($lng))
		{
			$lng = I18n::getLocale();
		}

		if (false !== $lng)
		{
			// check for locale value
			$return = $return[$lng];
		}

		// if not set then apply default if requestes
		if ((!is_array($return) && !strlen($return)) && $apply_default)
		{
			// get default value from widget type 
			$return = $this->typeGet()->options[$name];

			// if is title then try name of widget 
			if (!isset($return) && $name == 'title')
			{
				$return = $this->typeGet()->name;
			}
		}

		return $return;
	}

	function setOptions($data)
	{
		// process data
		$this->data = $data;
		self::submit($this);

		// remove temporary data
		unset($this->data);

		$this->options = serialize($this->_options);
	}

	public static function getVar($name)
	{
		return self::$_vars[$name];
	}

	/**
	 * render defined widget output
	 * 
	 * @param array $vars from current page
	 */
	public static function render(& $vars)
	{
		// define vars for accessing it when rendering widgets 
		self::$_vars = $vars;

		$sidebar_widgets = Widget::sidebarWidgets();

		// get current template defined locations (to display widgets)
		$locations = Theme::locations();

		if ($locations)
		{
			// get widgets for current template locations
			//$widgets = Widget::findAllFrom('Widget', 'location IN (' . implode(',', array_fill(0, count($locations), '?')) . ') ORDER BY pos', array_keys($locations));
			// append actual widgets to variables
			Widget::appendWidgets($sidebar_widgets);

			/*
			 * $sidebar_widgets->sidebars[location_id] = array(widget_id,...)
			 * $sidebar_widgets->inactive_widgets = array(widget_id,...)
			 * $sidebar_widgets->arr_widgets[location_id][widget_id]=widget
			 */

			foreach ($sidebar_widgets->sidebars_obj as $location => $widgets)
			{
				// check if this location really defined by this theme
				if (isset($locations[$location]))
				{
					foreach ($widgets as $widget)
					{
						// for each stored widget populate data by executing widget function 
						$widget->location = $location;
						// if widget has valid definition 			
						if (self::checkRenderCriteria($widget))
						{
							// render widget output 
							$widget->render = call_user_func($widget->typeGet()->render, $widget);
							// assign this widget to vars
							self::$_vars['widget_location'][$location][$widget->id] = $widget;
						}
					}
				}
			}
		}

		//print_r($sidebar_widgets);
		// render current page widgets if page built with widgets 
		if (isset($sidebar_widgets->pages_obj[self::$_vars['page_type']]))
		{
			$widgets = $sidebar_widgets->pages_obj[self::$_vars['page_type']];
			foreach ($widgets as $widget)
			{
				// render widget output 
				$widget->render = call_user_func($widget->typeGet()->render, $widget);
				// assign this widget to vars
				self::$_vars['widgets'][$widget->id] = $widget;
			}
		}

		$vars = self::$_vars;
	}

	/**
	 * check if current widget should be rendered on this page
	 * 
	 * @param Widget $widget
	 * @return bool 
	 */
	static private function checkRenderCriteria($widget)
	{
		$current_page_type = self::$_vars['page_type'];
		$widget_page_type_hide = $widget->getOption('page_type_hide', false, true);

		return ($widget->typeGet() && !$widget_page_type_hide[$current_page_type]);
	}

	/**
	 * generate html with rendered widgets for given theme location. used in theme layouts.
	 * 
	 * @param string $location_id
	 * @param array $vars
	 * @param string $pattern
	 * @return string 
	 */
	public static function renderThemeLocation($location_id, & $vars, $pattern = '<div class="{id}" role="complementary">{content}</div>')
	{
		$return = '';
		if (self::isRendered($location_id, $vars))
		{
			foreach ($vars['widget_location'][$location_id] as $widget)
			{
				$return .= $widget->render;
			}
			if ($return)
			{
				$return = str_replace(array('{id}', '{content}'), array($location_id, $return), $pattern);
			}
		}

		return $return;
	}

	/**
	 * check if any widget rendered for given theme location 
	 * @param string $location_id
	 * @param array $vars
	 * @return bool 
	 */
	static function isRendered($location_id, & $vars)
	{
		return isset($vars['widget_location'][$location_id]);
	}

	/**
	 * get widget type for given widget type. it will have actions to execute while rendering.
	 *
	 * @return $this->type 
	 */
	public function typeGet()
	{
		if (!isset($this->type))
		{
			self::instance();
			if (isset(self::$_widgets[$this->type_id]))
			{
				$this->type = self::$_widgets[$this->type_id];
			}
		}
		return $this->type;
	}

	/**
	 * check if this type is valid type 
	 * @param type $type_id
	 * @return type 
	 */
	public static function typeIsValid($type_id)
	{
		self::instance();
		return isset(self::$_widgets[$type_id]);
	}

	/**
	 * Display form with options for given widget. used when editing widget options in admin area.
	 * 
	 * @param type $widget_type
	 * @param Widget $widget
	 * @return boolean|string 
	 */
	public static function optionsForm($widget_type, $widget = null)
	{
		if (!$widget_type)
		{
			// widget not defined do not show anything.
			return false;
		}

		$title = View::escape($widget_type->name);

		if ($widget)
		{
			$widget_title = $widget->getOption('title', null);
			if (strlen($widget_title))
			{
				$title .= ' <small class="muted">' . $widget_title . '</small>';
			}
			$is_default = ' custom';
		}
		else
		{
			// define new widget with current widget type
			$widget = new Widget(array('type_id' => $widget_type->id));

			$is_default = ' default';
		}

		$r = '<div class="defined_widget' . $is_default . '" data-type_id="' . $widget_type->id . '" '
				. ($widget->id ? 'id="' . $widget->id . '"' : '') . '>			
			<div class="widget_wrap">
				<div class="title">
					<a href="#" class="edit right button link" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit" aria-hidden="true"></i></a>
					<a href="#" class="move right button link" title="' . View::escape(__('Move')) . '" data-jq-dropdown="#jq-dropdown-widget-move"><i class="fa fa-arrows" aria-hidden="true"></i></a>
					<h4>' . $title . '</h4>
				</div>
				' . self::form($widget) . '
			</div>
			<p class="description">' . View::escape($widget_type->description) . '</p>
		</div>';

		return $r;
	}

	/**
	 * Display grouped widgets by location or available and incative group. Used in admin area.
	 * 
	 * @param type $id
	 * @param type $title
	 * @param type $description
	 * @param type $content
	 * @return string 
	 */
	public static function optionsBox($id, $title, $description, $content, $location_id = '')
	{
		$add_button = '';
		$location_id_str = '';
		if ($location_id)
		{
			// can add new widget to this location 
			$add_button = '<a href="#add" class="button primary block" data-jq-dropdown="#jq-dropdown-widget-add"><i class="fa fa-plus" aria-hidden="true"></i> ' . __('Add widget') . '</a>';
			$location_id_str = 'data-location="' . $location_id . '"';
		}


		$r = '<!-- ' . $id . ' -->
			<div class="panel box ' . $id . ' display-none" id="' . $id . '" ' . $location_id_str . '>
				<div class="panel_header title">'
				. '<h3>'
				. $title
				. (strlen($description) ? ' <small class="muted">' . $description . '</small>' : '')
				. '</h3>					
				</div>
				<!-- list widgets -->
				<div class="body items">					
					' . $content . '						
				</div>
				<!-- list widgets END -->
				' . $add_button . '
			</div>	
			<!-- ' . $id . ' END -->';
		return $r;
	}

	/**
	 * Render widget options form. used in admin for widget edit 
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function form($widget)
	{

		// check if widget has custom form action then execute it
		if (isset($widget->typeGet()->optionsForm))
		{
			$return = call_user_func($widget->typeGet()->optionsForm, $widget);
		}
		else
		{
			$return = self::defaultForm($widget);
		}


		// Select page type
		$page_types = Config::pageTypesGet();
		$selected_page_type = $widget->getOption('page_type_hide', false, true);
		$page_type_options = '';
		foreach ($page_types as $page_type)
		{
			// if none set then display on all pages 
			$name = 'page_type_hide[' . View::escape($page_type->id) . ']';
			/* $page_type_options .= '<input type="checkbox" name="' . $name . '" id="' . $name . '" value="1"'
			  . ($selected_page_type[$page_type->id] ? ' checked="checked"' : '') . ' />
			  <label for="' . $name . '" title="' . View::escape($page_type->description) . '">' . View::escape($page_type->title) . '</label> '; */

			$page_type_options .= '<label class="input-checkbox" title="' . View::escape($page_type->description) . '">'
					. '<input type="checkbox" name="' . $name . '" id="' . $name . '" value="1"'
					. ($selected_page_type[$page_type->id] ? ' checked="checked"' : '') . ' />'
					. '<span class="checkmark"></span>'
					. View::escape($page_type->title)
					. '</label> ';
		}

		$return .= self::_formatFormRow(array(
					'{label}'	 => '<label for="page_type_hide">' . __('Hide on following page types') . '</label>',
					'{field}'	 => $page_type_options
		));

		// submit button
		$return .= '<p>'
				. '<button type="submit" name="submit" class="submit button primary"><i class="fa fa-save" aria-hidden="true"></i> ' . __('Save') . '</button>	'
				. '<a href="#cancel" class="cancel button link">' . __('Cancel') . '</a> '
				. '<a href="#remove" class="remove button red"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('Remove') . '</a> '
				. '<input type="hidden" name="id" id="id" value="' . View::escape($widget->id) . '" />
						<input type="hidden" name="type_id" id="type_id" value="' . View::escape($widget->type_id) . '" />
					</p>';



		return '<div class="widget_options"><form>'
				. '<h3>' . View::escape($widget->typeGet()->name) . '</h3>'
				. $return
				. '</form></div>';
	}

	/**
	 * Process widget options form data before saving  
	 * 
	 * @param Widget $widget
	 * @return bool 
	 */
	public static function submit($widget)
	{
		// check if widget has custom form action then execute it
		if (isset($widget->typeGet()->optionsFormSubmit))
		{
			$return = call_user_func($widget->typeGet()->optionsFormSubmit, $widget);
		}
		else
		{
			$return = self::defaultSubmit($widget);
		}


		return $return;
	}

	/**
	 * render default form for widget options. it usually just title for widget 
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function defaultForm($widget)
	{
		// render default form with widget title only 
		// show multilingual text first

		$language = Language::getLanguages();
		$echo = '';

		$tab_key = 'widget_' . $widget->id . '_';
		foreach ($language as $lng)
		{
			$echo .= self::_formatFormRow(array(
						'{label}'	 => '<label for="title[' . $lng->id . ']">' . __('Title') . Language::tabsLabelLngInfo($language, $lng) . '</label>',
						'{field}'	 => '<input name="title[' . $lng->id . ']" type="text" 
							id="title[' . $lng->id . ']" class="input input-long"
							value="' . View::escape($widget->getOption('title', $lng->id)) . '" />',
						'{class}'	 => Language::tabsTabKey($tab_key, $lng)
			));
		}

		// hide title
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Hide title'),
						'{name}'	 => 'hide_title',
						'{checked}'	 => $widget->getOption('hide_title', false, true)
					))
		));


		if (count($language) > 3)
		{
			// compact language tabs
			$tabs_compact = ' tabs_compact';
		}
		else
		{
			$tabs_compact = '';
		}

		//$tabs_pattern = '<div class="tabs' . $tabs_compact . '" data-container="body">{tabs}</div>';
		$tabs_pattern = '<div class="clearfix form-row">
							<div class="col col-12 px1 tabs' . $tabs_compact . '" data-container="widget_options">{tabs}</div>
						</div>';
		return Language::tabs($language, $tab_key, $tabs_pattern)
				. $echo;
	}

	/**
	 * process submitted form options before saving
	 *  
	 * @param Widget $widget 
	 */
	public static function defaultSubmit($widget, $checkboxes = array())
	{
		// update page types 
		if (!is_array($widget->data['page_type_hide']))
		{
			$widget->data['page_type_hide'] = array();
		}

		// set 0 value for checkboxes
		foreach ($checkboxes as $chk)
		{
			if (!isset($widget->data[$chk]))
			{
				$widget->data[$chk] = 0;
			}
		}


		// clean data,  check for xss 
		$input = new Input();
		$input->use_xss_clean = true;
		$widget->_options = $input->_clean_input_data($widget->data);

		return true;
	}

	/**
	 * get stored sidebar_widgets  
	 */
	public static function sidebarWidgets()
	{
		if (!isset(self::$_sidebar_widgets))
		{
			$sidebar_widgets = Config::option('sidebar_widgets');
			if (strlen($sidebar_widgets))
			{
				self::$_sidebar_widgets = unserialize($sidebar_widgets);
			}
			else
			{
				self::$_sidebar_widgets = new stdClass();
			}
			self::_fixSidebarWidgets(self::$_sidebar_widgets);
		}

		return self::$_sidebar_widgets;
	}

	/**
	 * set provided object as sidebar widgets. 
	 * @param stdClass $sidebar_widgets loaded from theme options
	 */
	public static function sidebarWidgetsSetDemo($sidebar_widgets = null)
	{
		if (is_object($sidebar_widgets))
		{
			self::_fixSidebarWidgets($sidebar_widgets);
			self::$_sidebar_widgets = $sidebar_widgets;
		}
	}

	private static function _fixSidebarWidgets($sidebar_widgets)
	{
		if (!is_array($sidebar_widgets->sidebars))
		{
			$sidebar_widgets->sidebars = array();
		}
		if (!is_array($sidebar_widgets->pages))
		{
			$sidebar_widgets->pages = array();
		}
		if (!is_array($sidebar_widgets->inactive_widgets))
		{
			$sidebar_widgets->inactive_widgets = array();
		}
	}

	public static function sidebarWidgetsSave($sidebar_widgets, $key = 'sidebar_widgets')
	{
		return Config::optionSet($key, serialize($sidebar_widgets));
	}

	public static function sidebarWidgetsSaveFromPost()
	{
		$sidebar_widgets = new stdClass();

		$sidebars_arr = $_POST['sidebars'];
		if ($sidebars_arr)
		{
			foreach ($sidebars_arr as $sb_key => $sb)
			{
				$sidebars[$sb_key] = self::str2arr($sb);
			}
			$sidebar_widgets->sidebars = $sidebars;
		}

		$pages_arr = $_POST['pages'];
		if ($pages_arr)
		{
			foreach ($pages_arr as $sb_key => $sb)
			{
				$pages[$sb_key] = self::str2arr($sb);
			}
			$sidebar_widgets->pages = $pages;
		}

		$sidebar_widgets->inactive_widgets = self::str2arr($_POST['inactive_widgets']);

		return Widget::sidebarWidgetsSave($sidebar_widgets);
	}

	public static function str2arr($srt)
	{
		return explode(',', trim($srt, ','));
	}

	/**
	 * get array of $sidebar_widgets->sidebars[location] and for each id get widget from db and append to 
	 * $sidebar_widgets->arr_widgets[location]
	 * 
	 * @param object $sidebar_widgets 
	 */
	public static function appendWidgets($sidebar_widgets)
	{
		if (!isset($sidebar_widgets->sidebars_obj))
		{
			$sidebar_widgets->sidebars_obj = array();
			$sidebar_widgets->pages_obj = array();
			$sidebar_widgets->inactive_widgets_obj = array();

			$_widgets = self::findAll();

			// append widgets to new array sidebars
			foreach ($sidebar_widgets->sidebars as $location => $arr_ids)
			{
				foreach ($arr_ids as $w_id)
				{
					if (isset($_widgets[$w_id]))
					{
						$sidebar_widgets->sidebars_obj[$location][$w_id] = $_widgets[$w_id];
						unset($_widgets[$w_id]);
					}
				}
			}

			// append widgets to new array pages
			foreach ($sidebar_widgets->pages as $location => $arr_ids)
			{
				foreach ($arr_ids as $w_id)
				{
					if (isset($_widgets[$w_id]))
					{
						$sidebar_widgets->pages_obj[$location][$w_id] = $_widgets[$w_id];
						unset($_widgets[$w_id]);
					}
				}
			}

			// all other widgets are inactive widgets
			$sidebar_widgets->inactive_widgets = array();
			foreach ($_widgets as $w_id => $w)
			{
				$sidebar_widgets->inactive_widgets[] = $w_id;
				$sidebar_widgets->inactive_widgets_obj[$w_id] = $w;
			}
		}
	}

	public static function findAll()
	{
		// get all widget objects from db and append them 
		if (is_null(self::$_widgets_saved))
		{
			$widgets = Widget::findAllFrom('Widget');
			self::$_widgets_saved = array();
			foreach ($widgets as $w)
			{
				self::$_widgets_saved[$w->id] = $w;
			}
			unset($widgets);
		}

		return self::$_widgets_saved;
	}

	public static function changeLanguageId($lng_old, $lng_new)
	{
		// search existing widgets for locale options and update matching language ids
		$widgets = Widget::findAllFrom('Widget');
		foreach ($widgets as $widget)
		{
			$save_this = false;

			$widget->_unsetializeOptions();
			foreach ($widget->_options as $name => $val)
			{
				if (is_array($val))
				{
					foreach ($val as $lng => $lng_val)
					{
						if ($lng == $lng_old)
						{
							$save_this = true;
							$widget->_options[$name][$lng_new] = $lng_val;
							unset($widget->_options[$name][$lng_old]);
							break;
						}
					}
				}
			}

			if ($save_this)
			{
				$widget->setOptions($widget->_options);
				$widget->save('id');
			}
		}
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetUsers($widget)
	{
		// get users
		$selected_location = self::$_vars['selected_location'];
		$number_of_users = $widget->getOption('number_of_users', false, true);
		$display_ad_count = $widget->getOption('display_ad_count', false, true);
		// all,users,dealers
		$list_type = $widget->getOption('list_type', false, true);
		// latest, latest_posted, most_posted
		$list_mode = $widget->getOption('list_mode', false, true);
		// simple, thumbs, carousel
		$list_style = $widget->getOption('list_style', false, true);

		$display_with_image_only = $widget->getOption('display_with_image_only') ? true : false;
		$return = '';


		if ($selected_location)
		{
			$suggested_title = __('Users in {name}', array('{name}' => View::escape(Location::getName($selected_location))));
		}
		else
		{
			$suggested_title = __('Users');
		}


		$cache_key = 'user'
				. '.' . $list_type
				. '.' . $list_mode
				. '.n' . $number_of_users
				. '.l' . intval($selected_location->id)
				. ($display_with_image_only ? '.img' : '');

		$users = SimpleCache::get($cache_key);
		if ($users === false)
		{

			$where_user = 'us.enabled=' . User::quote(1) . ' AND us.activation=' . User::quote(0);
			//$whereA = array('us.enabled=? AND us.activation=?');
			//$whereB = array('1', '0');



			switch ($list_type)
			{
				case 'users':
					$where_user .= ' AND us.level=' . User::quote(User::PERMISSION_USER);
					//$whereA[] = 'us.level=?';
					//$whereB[] = User::PERMISSION_USER;
					break;
				case 'dealers':
					$where_user .= ' AND us.level=' . User::quote(User::PERMISSION_DEALER);
					//$whereA[] = 'us.level=?';
					//$whereB[] = User::PERMISSION_DEALER;
					break;
			}

			if ($display_with_image_only)
			{
				$where_user .= ' AND us.level=' . User::quote('');
				//$whereA[] = 'us.logo!=?';
				//$whereB[] = '';
			}


			$whereA = array('ad.listed=?');
			$whereB = array('1');
			// add location to query
			Ad::buildLocationQuery($selected_location, $whereA, $whereB);


			$fields = 'id,name,email,username,logo';

			switch ($list_mode)
			{
				case 'most_posted':
					// most posted users by location 

					$sql_user_exists = "SELECT 1 FROM cb_user us WHERE us.id=ad.added_by AND " . $where_user;
					$sql_ad_count = "SELECT count(ad.added_by) as num_ads ,ad.added_by as ad_added_by
						FROM cb_ad ad
						WHERE " . implode(' AND ', $whereA) . " AND EXISTS (" . $sql_user_exists . ")
						GROUP BY ad.added_by ORDER BY num_ads DESC  LIMIT " . $number_of_users;

					$sql = "SELECT " . $fields . ", ad1.num_ads
						FROM cb_user us1,(" . $sql_ad_count . ") ad1
						WHERE us1.id=ad1.ad_added_by";

					//$order_by = " ORDER BY num_ads DESC ";

					break;
				case 'latest_posted':
					// latest users posted an ad by location
					//$order_by = " ORDER BY ad.id DESC ";
					$sql_user_exists = "SELECT 1 FROM cb_user us WHERE us.id=ad.added_by AND " . $where_user;
					$sql_ad_distinct = "SELECT DESTINCT ad.added_by as ad_added_by,ad.id as ad_id
						FROM cb_ad ad
						WHERE " . implode(' AND ', $whereA) . " AND EXISTS (" . $sql_ad_distinct . ")
						ORDER BY ad.id DESC LIMIT " . $number_of_users;

					$sql = "SELECT " . $fields . "
						FROM cb_user us1,(" . $sql_ad_count . ") ad1
						WHERE us1.id=ad1.ad_added_by
						ORDER BY ad1.ad_id DESC";
					break;
				case 'latest':
				default:
					// latest users by location 
					//$order_by = " ORDER BY us.id DESC ";
					$sql_ad_exists = "SELECT 1
						FROM cb_ad ad
						WHERE ad.added_by=us.id AND " . implode(' AND ', $whereA);
					$sql = "SELECT " . $fields . "
						FROM cb_user us 
						WHERE " . $where_user . " AND EXISTS (" . $sql_ad_exists . ")
						ORDER BY us.id DESC LIMIT " . $number_of_users;

					break;
			}



			/* OLD VERSION SLOW 1.5s
			  $sql = "SELECT us.*, count(ad.id) as num_ads "
			  . " FROM " . User::tableNameFromClassName('User') . " us "
			  . " LEFT JOIN " . Ad::tableNameFromClassName('Ad') . " ad ON us.id=ad.added_by "
			  . " WHERE " . implode(' AND ', $whereA)
			  . " GROUP BY us.id "
			  . $order_by
			  . " LIMIT " . $number_of_users; */

			/* this is faster 1.5 vs .5 sec. 
			  SELECT *
			  FROM cb_user us1,(
			  SELECT count(ad.added_by) as num_ads ,ad.added_by
			  FROM cb_ad ad
			  WHERE ad.listed='1' AND EXISTS (
			  SELECT 1 FROM cb_user us WHERE us.id=ad.added_by AND us.enabled='1' AND us.activation='0'
			  )
			  GROUP BY ad.added_by  ORDER BY num_ads DESC  LIMIT 5
			  ) ad1
			  WHERE us1.id=ad1.added_by; */


			$users = User::query($sql, $whereB);

			// remove passwords in case it is stored in object 
			$users = User::cleanUserData($users);

			// cache results 
			SimpleCache::set($cache_key, $users);
		}


		if ($users)
		{
			switch ($list_style)
			{
				case 'thumbs':
				case 'carousel':
					$thumb_width = $widget->getOption('thumb_width', false, true);
					$thumb_height = $widget->getOption('thumb_height', false, true);

					$thumb_style = $widget->getOption('thumb_style', false, true);
					$old_thumbs_single = $widget->getOption('thumbs_single', false, true);

					if ($thumb_style === 'onerow')
					{
						$old_thumbs_single = 1;
					}
					elseif ($thumb_style == '' && $old_thumbs_single)
					{
						$thumb_style = 'onerow';
					}

					// new thumb style value
					$css_class = ($list_style === 'thumbs' ? ' thumb_style_' . ($thumb_style ? $thumb_style : 'grid') : '');
					// old compatible mode, use it for old themes that use default or own template and own css 
					// we cannot change their css and templates so leave this template unchanged in future as well 
					// or they must update their themes to latest version
					$css_class .= ($old_thumbs_single ? ' thumbs_single' : '');

					// try theme file first 
					if (file_exists(Theme::file('index/_users_thumb')))
					{
						$return = View::renderAsSnippet('index/_users_thumb', array(
									'users'				 => $users,
									'thumb_width'		 => $thumb_width,
									'thumb_height'		 => $thumb_height,
									'thumb_style'		 => $thumb_style,
									'thumbs_single'		 => $old_thumbs_single,
									'list_style'		 => $list_style,
									'css_class'			 => $css_class,
									'display_ad_count'	 => $display_ad_count
						));
					}
					else
					{
						$thumb_width = $thumb_width ? $thumb_width : Config::option('dealer_logo_width');
						$thumb_height = $thumb_height ? $thumb_height : Config::option('dealer_logo_height');
						$_img_placeholder_src = Adpics::imgPlaceholder($thumb_width, $thumb_height);

						$return = '<div class="thumbs list_style_' . $list_style . $css_class . '">';
						foreach ($users as $user)
						{
							$_img_title = View::escape($user->name);

							$_img_thumb = User::logo($user, $thumb_width . 'x' . $thumb_height . 'x2', 'lazy');
							$thumb = '<img src="' . $_img_placeholder_src . '"'
									. ($_img_thumb ? ' class="lazy" data-src="' . $_img_thumb . '"' : '')
									. ' alt="' . $_img_title . '" '
									. ' width="' . $thumb_width . '"'
									. ' height="' . $thumb_height . '" />';

							$return .= '<a href="' . User::url($user) . '" title="' . $_img_title . '" style="width:' . $thumb_width . 'px;">'
									. $thumb
									. '<span class="title">' . $_img_title . '</span>'
									. ($display_ad_count ? '<span class="item_count">' . number_format($user->num_ads) . '</span>' : '')
									. '</a> ';
						}
						$return .= '</div>';
					}
					break;
				case 'simple':
				default:

					//simple list 
					if (file_exists(Theme::file('index/_users_simple')))
					{
						$return = View::renderAsSnippet('index/_users_simple', array('users' => $users));
					}
					else
					{
						$return = '<ul class="list_style_simple">';
						foreach ($users as $user)
						{
							$return .= '<li><a href="' . User::url($user) . '">' . View::escape($user->name) . '</a>'
									. ($display_ad_count ? '<span class="item_count">' . number_format($user->num_ads) . '</span>' : '')
									. '</li>';
						}
						$return .= '</ul>';
					}
			}

			// send users for further customization in theme
			$widget->User = $users;
		}

		return self::_applyWidgetFormat($widget, $return, $suggested_title);
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetUsersForm($widget)
	{
		$echo = '';

		// select who to list 
		$list_type = '
			<select name="list_type" id="list_type">
				<option value="all">' . __('All') . '</option>
				<option value="users">' . __('Users') . '</option>
				<option value="dealers">' . __('Dealers') . '</option>
			</select>';
		// mark selected option 
		$sel_list_type = $widget->getOption('list_type', false, true);
		$list_type = str_replace('value="' . $sel_list_type . '">', 'value="' . $sel_list_type . '" selected="selected">', $list_type);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="list_type">' . __('List type') . '</label>',
					'{field}'	 => $list_type
		));


		// select what to list 
		$list_mode = '
			<select name="list_mode" id="list_mode">
				<option value="latest">' . __('Latest registered') . '</option>
				<option value="latest_posted">' . __('Latest posted') . '</option>
				<option value="most_posted">' . __('Most posted') . '</option>
			</select>';
		// mark selected option 
		$sel_list_mode = $widget->getOption('list_mode', false, true);
		$list_mode = str_replace('value="' . $sel_list_mode . '">', 'value="' . $sel_list_mode . '" selected="selected">', $list_mode);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="list_mode">' . __('List mode') . '</label>',
					'{field}'	 => $list_mode
		));


		// select how to list
		$list_style = '
			<select name="list_style" id="list_style">
				<option value="simple">' . __('Simple') . '</option>				
				<option value="thumbs">' . __('Gallery') . '</option>
				<option value="carousel">' . __('Carousel') . '</option>
			</select>';
		// mark selected option 
		$sel_list_style = $widget->getOption('list_style', false, true);
		$list_style = str_replace('value="' . $sel_list_style . '">', 'value="' . $sel_list_style . '" selected="selected">', $list_style);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="list_style">' . __('List style') . '</label>',
					'{field}'	 => $list_style
		));

		//thumb_width
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="thumb_width">' . __('Image width') . '</label>',
					'{field}'	 => '<input type="number" name="thumb_width" id="thumb_width" value="' . $widget->getOption('thumb_width', false, true) . '" class="input input-short" />',
					'{class}'	 => 'thumb_size'
		));

		//thumb_height
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="thumb_height">' . __('Image height') . '</label>',
					'{field}'	 => '<input type="number" name="thumb_height" id="thumb_height" value="' . $widget->getOption('thumb_height', false, true) . '" class="input input-short" />',
					'{class}'	 => 'thumb_size'
		));

		// thumb_style: none- vertical max 2 column, horizontal, vertical max one column
		/**
		 * thumb_style		[col x row]:
		 * 		grid -	  [6,4,2 x many] 
		 * 		onerow -   [many x 1] 
		 * 		onecolumn -   [1 x many]
		 * 		none -     [many x many]
		 */
		$thumb_style = '
			<select name="thumb_style" id="thumb_style">
				<option value="grid">' . __('Grid') . '</option>
				<option value="onerow">' . __('One row') . '</option>
				<option value="onecolumn">' . __('One column') . '</option>
				<option value="none">' . __('None') . '</option>
			</select>';
		// mark selected option 
		$sel_thumb_style = $widget->getOption('thumb_style', false, true);
		// get old value 
		$old_thumbs_single = $widget->getOption('thumbs_single', false, true);
		if ($sel_thumb_style == '' && $old_thumbs_single)
		{
			// convert old value to new value 
			$sel_thumb_style = 'onerow';
		}
		$thumb_style = str_replace('value="' . $sel_thumb_style . '">', 'value="' . $sel_thumb_style . '" selected="selected">', $thumb_style);
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="thumb_style">' . __('Gallery style') . '</label>',
					'{field}'	 => $thumb_style,
					'{class}'	 => 'thumb_style',
		));


		// number of users to display 
		$echo .= self::_formatFormRow(array(
					'{label}'	 => '<label for="number_of_users">' . __('Number of users') . '</label>',
					'{field}'	 => '<input type="number" name="number_of_users" id="number_of_users" value="' . $widget->getOption('number_of_users', false, true) . '" class="input input-short" />'
		));


		// display users with image only
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Display users with image'),
						'{name}'	 => 'display_with_image_only',
						'{checked}'	 => $widget->getOption('display_with_image_only', false, true)
					))
		));


		// display ad count 
		$echo .= self::_formatFormRow(array(
					'{field}' => self::_formatFormCheckbox(array(
						'{label}'	 => __('Display ad count'),
						'{name}'	 => 'display_ad_count',
						'{checked}'	 => $widget->getOption('display_ad_count', false, true)
					))
		));

		return self::defaultForm($widget) . $echo;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetUsersSubmit($widget)
	{
		// number of users to display set maximum as 100
		if ($widget->data['number_of_users'] > 100)
		{
			$widget->data['number_of_users'] = 100;
		}
		elseif ($widget->data['number_of_users'] < 1)
		{
			// set default number
			$widget->data['number_of_users'] = intval($widget->typeGet()->options['number_of_users']);
		}

		return self::defaultSubmit($widget);
	}

	/**
	 * display RSS links for current page
	 * 
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetRSS($widget)
	{
		$links_array = array();

		// check shich RSS links to display 
		if ($widget->getOption('rss_latest', false, true))
		{
			$links_array[] = Config::formatRssLink(array(
						'return_format' => 'frontend'
			));
		}
		if ($widget->getOption('rss_featured', false, true))
		{
			$links_array[] = Config::formatRssLink(array(
						'type'			 => 'featured',
						'return_format'	 => 'frontend'
			));
		}

		// check if have to display custom RSS feeds
		$has_active = self::$_vars['selected_location'] || self::$_vars['selected_category'] || self::$_vars['selected_user'];

		if ($widget->getOption('rss_latest_active', false, true) && $has_active)
		{
			// latest rss for active  user, location, category
			$links_array[] = Config::formatRssLink(array(
						'location'		 => self::$_vars['selected_location'],
						'category'		 => self::$_vars['selected_category'],
						'user'			 => self::$_vars['selected_user'],
						'use_search'	 => true,
						'return_format'	 => 'frontend'
			));
		}
		if ($widget->getOption('rss_featured_active', false, true) && $has_active)
		{
			$links_array[] = Config::formatRssLink(array(
						'type'			 => 'featured',
						'location'		 => self::$_vars['selected_location'],
						'category'		 => self::$_vars['selected_category'],
						'user'			 => self::$_vars['selected_user'],
						'use_search'	 => true,
						'return_format'	 => 'frontend'
			));
		}

		if ($links_array)
		{
			$return = '<ul><li>' . implode('</li><li>', $links_array) . '</li></ul>';
		}

		return self::_applyWidgetFormat($widget, $return);
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetRSSForm($widget)
	{
		$echo = '';

		// RSS to display 
		$feed_types = array(
			'rss_latest'			 => __('RSS latest ads'),
			'rss_featured'			 => __('RSS featured ads'),
			'rss_latest_active'		 => __('RSS latest ads related to current page'),
			'rss_featured_active'	 => __('RSS featured ads related to current page')
		);

		foreach ($feed_types as $k => $v)
		{
			$echo .= self::_formatFormRow(array(
						'{field}' => self::_formatFormCheckbox(array(
							'{label}'	 => $v,
							'{name}'	 => $k,
							'{checked}'	 => $widget->getOption($k, false, true)
						))
			));
		}

		return self::defaultForm($widget) . $echo;
	}

	/**
	 *
	 * @param Widget $widget
	 * @return string 
	 */
	public static function widgetRSSSubmit($widget)
	{
		return self::defaultSubmit($widget, array('rss_latest', 'rss_featured', 'rss_latest_active', 'rss_featured_active'));
	}

}
