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
 * class IndexController handles all front end pages
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class IndexController extends Controller
{

	static private $num_records = 30; // number of records per page
	static public $selected_location;
	static public $selected_category;
	static public $selected_user;
	static public $selected_ad;
	static public $url2vars;
	static public $search_params = array();
	private $_layout = 'frontend';

	const PAGE_TYPE_HOME = 'home';
	const PAGE_TYPE_CATEGORY = 'category';
	const PAGE_TYPE_AD = 'ad';
	const PAGE_TYPE_USER = 'user';
	const PAGE_TYPE_INFO = 'info';

	function __construct()
	{
		IpBlock::isBlockedIp();

		AuthUser::load();

		$this->_meta = new stdClass();
		$this->setLayout($this->_layout);

		// check maintenance mode
		Config::checkMaintenance();

		// set demo message 
		Config::demoInfo();

		$demo_js = Config::demoThemeBar();
		if ($demo_js)
		{
			$this->setMeta('javascript_other', $demo_js);
		}

		// add support for jquerydropdown in theme 
		if (Theme::versionSupport(Theme::VERSION_SUPPORT_JQDROPDOWN))
		{
			$this->setMeta('body_class', 'e_jqd');
		}
	}

	function load()
	{
		// update listed ads		
		//Config::performPassiveUpdates();

		$arr_url = func_get_args();

		/*
		  // check if given url is rendered by index
		  $arr_url = Dispatcher::splitUrl($url);
		 */
		if (!count($arr_url))
		{
			// this is index page
			// FIXME check options and get default location if set. also check if default is valid and enabled location
			return $this->index();
		}



		switch ($arr_url[0])
		{
			case 'item':
				return $this->item($arr_url[1]);
				break;
			case 'rss':
				array_shift($arr_url);
				return $this->rss($arr_url);
				break;
			case 's':
				array_shift($arr_url);
				return $this->stats($arr_url);
				break;
			case 'sitemap.xml':
				return $this->sitemap();
				break;
			case 'page':
				return $this->page($arr_url[1]);
				break;
			case 'search':
				if (isset($_GET['q']))
				{
					// use normlized q in title as well. so convert here
					$_GET['q'] = TextTransform::normalizeQueryString($_GET['q']);
				}
				// get category and location  then pass to category 
				$url = Permalink::vars2url($_GET);
				redirect($url, true);
				/* exit($url);
				  self::$selected_location = Location::findByIdFrom('Location', $_GET['location_id']);
				  self::$selected_category = Category::findByIdFrom('Category', $_GET['category_id']);
				  self::$selected_user = User::findByIdFrom('User', $_GET['user_id']);
				  self::$search_params = $_GET;
				  return $this->category($_GET['page']); */
				break;
			case 'admin':
			case 'post':
				// send to admin controller by returning false
				return false;
				break;
			case 'index':
			case 'home':
				// echo '[index]';
				// reset location 
				Config::setDefaultLocationCookie(0);
				redirect(Language::get_url());
				break;
			default:
				// treat as location or category
				// get location and category from url
				$this->_loadLocationCategory($arr_url);

				// check for short ad link
				$this->_loadShortUrl($arr_url[0]);

				// check if it is string with referance ?ref=123
				if (strpos($arr_url[0], '=') !== false && strlen($arr_url[0]) >= 3)
				{
					// this is index with ref
					return $this->index();
				}
		}


		// nothing found then return false and cantune to load real controller
		//echo '[not found]';
		//return true;
		//page_not_found();
		$this->_page_not_found();
	}

	function _page_not_found($vars = array())
	{
		header_404();
		$this->setMeta('title', __('404 Page not found'));
		$this->display('index/_page_not_found', $vars);
	}

	function _loadShortUrl($val)
	{
		$id = intval($val);
		if ($id == $val && $id > 0)
		{
			$ad = Ad::findByIdFrom('Ad', $id);
			if ($ad)
			{
				// redirect to full ad location 
				redirect(Ad::url($ad), true);
			}
		}
	}

	/**
	 * No internet
	 * called by url /?error=501 from service worker if no internet connection.
	 */
	function _error501()
	{
		$message = '<div class="no-internet">'
				. '<h2>' . __('No internet connection [501]') . '</h2>'
				. '<p>'
				. '<button onclick="window.history.back();" class="button">Go back</button> '
				. '<button onclick="location.reload();" class="button">Try again</button>'
				. '</p>'
				. '</div>';
		//return Config::displayMessagePage($message);

		$file = 'index/_error501';
		$vars = array();
		if (!file_exists(Theme::file($file)))
		{
			// theme doesn't have custom 501 file. show generik 501 error
			$file = null;
			$vars['content_for_layout'] = $message;
		}

		$this->setMeta('title', __('No internet connection [501]'));
		$this->setMeta('body_class', 'error501');
		$this->display($file, $vars);
	}

	/**
	 * get parsed url as array and find location and category 
	 * possible variations
	 * /
	 * /location/category/
	 * /location/category/page-number
	 * /category/
	 * /category/page-number
	 * /user/
	 * /user/page-number
	 * 
	 * 
	 * 
	 * @param array $arr_url
	 * @return type
	 */
	function _loadLocationCategory($arr_url = array())
	{
		$arr_url = array_filter($arr_url);

		// parse url and get vars
		$url2vars = Permalink::url2vars($arr_url);

		self::$url2vars = $url2vars;
		self::$selected_location = $url2vars->selected_location;
		self::$selected_category = $url2vars->selected_category;
		self::$selected_user = $url2vars->selected_user;

		$cur_url = implode('/', $arr_url) . '/';
		//$correct_url = trim(Location::urlOrigin(self::$selected_location, self::$selected_category, self::$selected_user, self::$page_number), '/');
		$correct_url = Permalink::vars2url($_GET, array(), true);


		//print_r(array(self::$selected_location, self::$selected_category, 1, self::$selected_user));
		//echo '[' . $cur_url . '=>' . $correct_url . '=>' . $correct_url_alt . ']';
		if (strcmp($cur_url, rawurldecode($correct_url)) !== 0)
		{
			/* if(Config::option('debug_mode'))
			  {
			  echo '[$cur_url:' . $cur_url . ', $correct_url:' . $correct_url . ']';
			  echo '[$cur_url:' . $cur_url . ', rawurldecode($correct_url):' . rawurldecode($correct_url) . ']';
			  exit;
			  } */
			// redirect to correct url 
			redirect(Language::get_url($correct_url), true);
		}




		if (isset(self::$selected_category))
		{
			return $this->category($url2vars->page_number);
		}
		if (isset(self::$selected_location))
		{
			// display location home page 
			Config::setDefaultLocationCookie(self::$selected_location->id);
			return $this->index();
		}
		if (isset(self::$selected_user))
		{
			return $this->user($url2vars->page_number);
		}

		return false;
	}

	function index()
	{

		// check special error pages 
		if ($_GET['error'] . '' === '501')
		{
			return $this->_error501();
		}


		// get location if not set
		if (!self::$selected_location)
		{
			self::$selected_location = Config::getDefaultLocation();
		}

		// build breadcrumb
		$this->_buildBreadcrumb(self::$selected_location, self::$selected_category);

		// title for index
		$title = Config::option('site_title');
		if (self::$selected_location)
		{
			$title = Location::getFullName(self::$selected_location, '', ', ', true) . ' - ' . $title;
			$page_description = Location::getDescription(self::$selected_location);
			if ($page_description)
			{
				$this->setMeta('description', View::escape(TextTransform::excerpt($page_description, 155)));
			}
		}
		$this->setMeta('title', $title);

		$vars = array(
			'page_type'			 => self::PAGE_TYPE_HOME,
			'selected_location'	 => self::$selected_location,
			'selected_category'	 => self::$selected_category,
			'catfield'			 => CategoryFieldRelation::getCatfields(self::$selected_location->id, self::$selected_category->id, true, true),
			'page_description'	 => $page_description,
			'page_title'		 => ''
		);


		// load widgets to locations
		Widget::render($vars);


		// canonical link
		$url_origin = Location::urlOrigin(self::$selected_location, self::$selected_category);
		$header_other = '<link rel="canonical" href="' . Location::url(self::$selected_location, self::$selected_category) . '" />';
		$header_other .= Language::relAlternate($url_origin);

		// custom rss 
		if (strlen($url_origin))
		{
			$header_other .= Config::formatRssLink(array('location' => self::$selected_location));
			$header_other .= Config::formatRssLink(array('type' => 'featured', 'location' => self::$selected_location));
		}

		$this->setMeta('header_other', $header_other);

		// define language switch 
		Language::htmlLanguageBuild($url_origin);


		$this->display('index/index', $vars);
	}

	function display($view, $vars = array(), $exit = true)
	{
		// check if theme exists 
		Theme::checkValidThemeLoaded();

		// send no cache headers
		if (Theme::previewTheme())
		{
			header_nocache();
		}

		// add custom styles last to take effect 
		Theme::applyCustomStyles($this);

		return parent::display($view, $vars, $exit);
	}

	function category($page = 1)
	{
		// check if default location changes
		Config::setDefaultLocationCookie(self::$selected_location->id, true);

		$num_rows_featured = intval(Config::option('ads_featured_per_page'));
		$page_description = '';
		$meta_page_description = '';
		$is_search = self::$url2vars->is_search;

		$page = intval($page);
		$page = $page > 1 ? $page : 1;
		$num_rows = self::_getNumRecords();
		$pages_max = 20;
		if ($page > $pages_max)
		{
			//$page = 1;
			redirect(Permalink::vars2url($_GET, array('page' => '')));
		}

		$ads = array();
		$ads_featured = array();

		// define deault params 
		self::$url2vars->search_params['listed'] = 1;
		self::$url2vars->search_params['category_id'] = self::$selected_category->id;
		self::$url2vars->search_params['location_id'] = self::$selected_location->id;
		self::$url2vars->search_params['user_id'] = self::$selected_user->id;

		// do not use like in search 
		self::$url2vars->search_params['use_like'] = 0;

		// build custom query 
		$cq = new stdClass();
		$cq->url2vars = self::$url2vars;
		Ad::cq($cq);

		$q = $cq->url2vars->search_params['q'];
		$q_len = StringUtf8::strlen($q);
		if ($q_len > 0 && $q_len < 3)
		{
			// so not show any results show error message 
			$this->validation()->set_error(__('Search string should be more than {num} characters.', array('{num}' => 3)));
			$cq->is_search_stopped = true;
		}
		elseif ($q_len > 50)
		{
			// search is too long ask to reduce it 
			$this->validation()->set_error(__('Search string should be less than {num} characters.', array('{num}' => 50)));
			$cq->is_search_stopped = true;
		}
		else
		{
			// get ads
			//$ads = Ad::queryUsingIds($cq->query_ordered . ' LIMIT ' . $st . ',' . $num_rows, $cq->whereB);
			$ads = Ad::cqQueryPage($cq, $page, $num_rows);

			// no ads, then no results for second or other pages. redirect to firt page 
			if (!$ads && $page != 1)
			{
				redirect(Permalink::vars2url($_GET, array('page' => '')));
			}

			// no ads but has some results on base query 
			if (!$ads && Ad::cqQueryBaseHasItems($cq) && strlen($cq->url2vars->search_params['q']))
			{
				// redirect to base results 
				Flash::set('info', __('Showing all results'));
				redirect(Permalink::vars2url(array(
							'q'				 => $cq->url2vars->search_params['q'],
							'location_id'	 => 0,
							'category_id'	 => 0
				)));
			}
		}


		// get featured ads
		if (!$cq->is_search_stopped && $num_rows_featured)
		{
			$ads_featured = Ad::cqQueryFeatured($cq, $num_rows_featured);
		}

		// add 2 arrays to load all related data once for regular and featured ads
		$ads_all = array_merge($ads, $ads_featured);


		// append names
		Category::appendAll(self::$selected_category);
		Location::appendAll(self::$selected_location);


		// build breadcrumb
		$this->_buildBreadcrumb(self::$selected_location, self::$selected_category);

		// append images
		Ad::appendAdpics($ads_all);

		// append users
		if (Adpics::isUserLogoRequired())
		{
			User::appendObject($ads_all, 'added_by', 'User');
		}
		// get catfields that whould be displayed in listing
		$catfield = CategoryFieldRelation::getCatfields(self::$selected_location->id, self::$selected_category->id, true, true);

		// append all AdFieldRelation
		AdFieldRelation::appendAllFields($ads_all);


		// paginator 	
		$total_ads = Ad::cqQueryTotal($cq);
		$total_pages = ceil($total_ads / $num_rows);
		if ($total_pages > $pages_max)
		{
			$total_pages = $pages_max;
		}

		// there will not be 2 pages ever, 
		// because if 2 pages all results will be shown in one page for better performance
		if ($total_pages <= 2)
		{
			$total_pages = 1;
		}


		// define paginator pattern
		$paginator_pattern = Permalink::vars2url($_GET, array('page' => '{page}'));
		$paginator_pattern_alt = Permalink::vars2url($_GET, array('page' => ''));
		$paginator = Paginator::render($page, $total_pages, $paginator_pattern, true, $paginator_pattern_alt);


		// define $url_origin for language switch links  
		$url_origin = Permalink::vars2url($_GET, array(), true);

		// canonical link
		// keep page number in canonical links because they are not same pages 
		$header_other = '<link rel="canonical" href="' . Language::get_url($url_origin) . '" />';
		// prev next links 
		if ($page > 1)
		{
			$header_other .= '<link rel="prev" href="' . Permalink::vars2url($_GET, array('page' => $page - 1)) . '" />';
		}
		if ($page < $total_pages)
		{
			$header_other .= '<link rel="next" href="' . Permalink::vars2url($_GET, array('page' => $page + 1)) . '" />';
		}
		$header_other .= Language::relAlternate($url_origin);

		// custom rss 
		if (strlen($url_origin))
		{
			$header_other .= Config::formatRssLink(array('location' => self::$selected_location, 'category' => self::$selected_category));
			$header_other .= Config::formatRssLink(array('type' => 'featured', 'location' => self::$selected_location, 'category' => self::$selected_category));
		}

		// noindex if no content 
		if (!$ads_all)
		{
			$header_other .= '<meta name="robots" content="noindex" />';
			header_404();
		}
		elseif ($page > 1)
		{
			// if it is page > 1 then <meta name=”robots” content=”noindex, follow”>
			$header_other .= '<meta name="robots" content="noindex, follow" />';
		}


		$this->setMeta('header_other', $header_other);

		// define language switch 
		Language::htmlLanguageBuild($url_origin);


		if (self::$selected_category && self::$selected_category->id != 0)
		{
			// set page description only on first page 
			if ($page == 1)
			{
				$page_description = Category::getDescription(self::$selected_category);
				if ($page_description)
				{
					$page_description = Location::replaceVariables($page_description, self::$selected_location);
					$meta_page_description = $page_description;
				}
			}

			$arr_page_title[] = Category::getName(self::$selected_category);
		}

		$first_ad = reset($ads);
		$page_title = Ad::cqFormatTitle($cq, $first_ad);

		// set custom title 
		$search_related = false;
		if ($cq->is_search)
		{
			// set page description 
			$meta_page_description = trim(Ad::cqFormatDescription($cq) . ' ' . $meta_page_description);

			// search category names for given query 
			if (!$cq->is_search_stopped)
			{
				$search_related = Ad::searchRelated($q, 5, self::$selected_location);
			}
		}


		$search_desc_arr = Ad::cqFormatFilterRemover($cq, array(
					'pattern'	 => '{name} <a href="{url}" class="button red small" title="' . __('Remove filter') . '">x</a>',
					'join'		 => null
		));


		$this->setMeta('title', ($page_title ? $page_title . ' - ' : '') . Config::option('site_title'));
		$this->setMeta('description', View::escape(TextTransform::excerpt($meta_page_description, 155)));

		$vars = array(
			'ads'				 => $ads,
			'ads_featured'		 => $ads_featured,
			'total_ads'			 => $total_ads,
			'search_desc_arr'	 => $search_desc_arr,
			'catfield'			 => $catfield,
			'cq'				 => $cq,
			'is_search'			 => $is_search,
			'selected_location'	 => self::$selected_location,
			'selected_category'	 => self::$selected_category,
			'selected_user'		 => self::$selected_user,
			'page_type'			 => self::PAGE_TYPE_CATEGORY,
			'paginator'			 => $paginator,
			'related_pages'		 => Category::getRelatedPages(self::$selected_location, self::$selected_category),
			'page_description'	 => $page_description,
			'page_title'		 => $page_title,
			'search_related'	 => $search_related,
		);

		// load widgets to locations
		Widget::render($vars);

		$this->display('index/category', $vars);
	}

	static private function _getNumRecords()
	{
		$num_rows = Config::option('ads_listed_per_page');
		return $num_rows > 1 ? $num_rows : self::$num_records;
	}

	function user($page = 1)
	{
		if (!self::$selected_user)
		{
			$this->_page_not_found();
		}

		$num_rows = self::_getNumRecords();
		$page = $page > 1 ? $page : 1;
		$st = ($page - 1) * $num_rows;

		// list user ads
		$ads = Ad::findAllFrom('Ad', 'added_by=? AND listed=? ORDER BY featured DESC, published_at DESC LIMIT ' . $st . ',' . $num_rows, array(self::$selected_user->id, 1));

		if (!$ads && $page != 1)
		{
			// no more pages then redirect to first page 
			redirect(User::url(self::$selected_user));
		}

		// append images
		Ad::appendAdpics($ads);

		// append users
		if (Adpics::isUserLogoRequired())
		{
			// append selected user to all ads manually for not loading same user from DB again
			// User::appendObject($ads, 'added_by', 'User');
			foreach ($ads as $ad)
			{
				$ad->User = self::$selected_user;
			}
		}
		// append custom fields 
		AdFieldRelation::appendAllFields($ads);

		// get catfields that whould be displayed in listing
		$catfield = CategoryFieldRelation::getCatfields(0, 0, true, true);


		// append listed ad count 
		User::appendAdCount(self::$selected_user, 'listed');


		// paginator
		$paginator_pattern = User::url(self::$selected_user, '{page}');
		$paginator_pattern_alt = User::url(self::$selected_user);

		if (count($ads) < $num_rows && $page == 1)
		{
			$total = count($ads);
		}
		else
		{
			// set listed ad count 
			$total = User::countAdType(self::$selected_user, 'listed');
		}

		$total_pages = ceil($total / $num_rows);
		$paginator = Paginator::render($page, $total_pages, $paginator_pattern, true, $paginator_pattern_alt);

		// canonical link
		$url_origin = Location::urlOrigin(null, null, self::$selected_user, $page);
		$header_other = '<link rel="canonical" href="' . Language::get_url($url_origin) . '" />';
		// prev next links 
		if ($page > 1)
		{
			$header_other .= '<link rel="prev" href="' . Location::url(null, null, self::$selected_user, $page - 1) . '" />';
		}
		if ($page < $total_pages)
		{
			$header_other .= '<link rel="next" href="' . Location::url(null, null, self::$selected_user, $page + 1) . '" />';
		}
		$header_other .= Language::relAlternate($url_origin);

		// custom rss 
		if (strlen($url_origin))
		{
			$header_other .= Config::formatRssLink(array('user' => self::$selected_user));
			$header_other .= Config::formatRssLink(array('type' => 'featured', 'user' => self::$selected_user));
		}

		// noindex if no content 
		if (!$ads)
		{
			$header_other .= '<meta name="robots" content="noindex" />';
		}
		elseif ($page > 1)
		{
			// if it is page > 1 then <meta name=”robots” content=”noindex, follow”>
			$header_other .= '<meta name="robots" content="noindex, follow" />';
		}

		$this->setMeta('header_other', $header_other);


		// page title
		$title = View::escape(self::$selected_user->name) . ' - ' . Config::option('site_title');
		$this->setMeta('title', $title);

		// page description 
		$_dealer_meta_arr = array();
		// use regular date here
		$_dealer_meta_arr[] = __('on site for {time}', array('{time}' => Config::timeRelative($ad->User->added_at, 1, false)));
		if (self::$selected_user->countAds > 1)
		{
			$_dealer_meta_arr[] = __('{num} items', array('{num}' => View::escape(self::$selected_user->countAds)));
		}
		$this->setMeta('description', implode(', ', $_dealer_meta_arr));


		// set language switch 
		Language::htmlLanguageBuild($url_origin);

		$vars = array(
			'ads'			 => $ads,
			'total'			 => $total,
			'catfield'		 => $catfield,
			'selected_user'	 => self::$selected_user,
			'page_type'		 => self::PAGE_TYPE_USER,
			'paginator'		 => $paginator,
			'page_title'	 => self::$selected_user->name,
		);

		// load widgets to locations
		Widget::render($vars);

		$this->display('index/user', $vars);
	}

	function item($permalink)
	{
		// if not sent already then check for ad id
		$arr_ad_id = explode('-', $permalink);
		$ad_id = array_pop($arr_ad_id);
		$ad_id = intval($ad_id);
		if ($ad_id)
		{
			$ad = Ad::findByIdFrom('Ad', $ad_id);
			self::$selected_ad = $ad;
		}
		if (!$ad)
		{
			return $this->_page_not_found(array('ad_id' => $ad_id));
		}

		// if url with login request  
		$redirect_to_login = isset($_GET['login']) ? true : false;
		if ($redirect_to_login)
		{
			/**
			 * we need user to login in order to 
			 * 		- to view contact details if defined in site settings
			 * 		- view this ad as owner (not enabled and can be viewed by owner only)
			 */
			if (Config::option('view_contact_registered_only') || ($ad->enabled != Ad::STATUS_ENABLED && Ad::ownerCan($ad, 'view')))
			{
				// check and redirect to login if needed
				AuthUser::isLoggedIn();
			}
		}



		// if ad is not enabled then do not display it at all for other user
		if (!$ad->listed)
		{
			// moderator can view any ad with reason explaining why not displayed
			if (AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, false))
			{
				// display detailed reason for not being listed
				$this->validation()->set_error(Ad::unlistedReason($ad));
			}
			elseif (AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, false))
			{
				if (Ad::ownerCan($ad, 'view'))
				{
					/// owner can view this ad
					$this->validation()->set_error(Ad::unlistedReason($ad));
				}
				else
				{
					// do not show ad, send to 404 page with related ads 
					return $this->_page_not_found(array('ad_id' => $ad_id));
				}
			}
			else
			{
				// not moderator or owner
				if (Ad::isExpired($ad, true))
				{
					// only expired then show info and renew link
					$this->validation()->set_error(__('Ad is expired on {num}.', array('{num}' => Config::date($ad->expireson))));
				}
				else
				{
					// check if ad can be viewed by owner
					if (Ad::ownerCan($ad, 'view'))
					{
						$message = __('This ad is disabled and can be viewed by ad owner only.');
						if (!AuthUser::isLoggedIn(false))
						{
							// show login button 
							$message .= ' <a href="' . Ad::url($ad, null, '?login=1') . '" class="button">' . __('Log in') . '</a>';
						}
						$this->validation()->set_error($message);

						return $this->_page_not_found(array('ad_id' => $ad_id));
					}
					else
					{
						// do not show ad, send to 404 page with related ads 
						return $this->_page_not_found(array('ad_id' => $ad_id));
					}
				}
			}
		}



		// check contact form
		$contact = $this->_contactForm();

		// preset email in contact form if user logged in 
		if (!isset($contact->email) && AuthUser::isLoggedIn(false))
		{
			$contact->email = AuthUser::$user->email;
		}

		// append Category, Location, Adpics, CategoryFieldRelation, AdFieldRelation
		Ad::appendAll($ad);

		// append author 
		User::appendObject($ad, 'added_by', 'User', 'id');
		// append ad count 
		User::appendAdCount($ad->User, 'listed');

		// append prev, next ad
		Ad::appendPrevNext($ad);

		// build breadcrumb
		$breadcrumb_arr = $this->_buildBreadcrumb($ad->Location, $ad->Category);

		// add location and category to title and keywords
		$arr_keyword = array();
		foreach ($breadcrumb_arr as $br)
		{
			$arr_keyword[] = $br[0];
		}
		if ($arr_keyword)
		{
			$str_keyword = implode(', ', $arr_keyword);
		}


		// set title 
		$title = Ad::getTitle($ad);
		if (strlen($title) < 50)
		{
			// add location and site name 
			if ($ad->Location)
			{
				$title .= ' - ' . Location::getName($ad->Location);
			}
			if (strlen($title) < 50)
			{
				// add site name 
				$title .= ' - ' . Config::option('site_title');
			}
		}
		$this->setMeta('title', $title);
		// append location and category to fill short descriptions for better SEO
		//$page_description = Ad::formatCustomFieldsSimple($ad, false, 'all', ', ');
		$page_description = Ad::formatCustomFieldsSimpleOptions($ad, array(
					'type'		 => 'all',
					'seperator'	 => ', ',
					'make_link'	 => false
		));
		$page_description .= ($page_description ? ' - ' : '')
				. $ad->description . ' - '
				. Location::getName($ad->Location) . '/'
				. Category::getFullName($ad->Category, '', ', ');
		$page_description = View::escape(TextTransform::excerpt($page_description, 155));
		$this->setMeta('description', $page_description);
		$this->setMeta('keywords', $str_keyword);

		// The Open Graph protocol http://ogp.me
		$header_other = '<meta property="og:title" content="' . View::escape($title) . '" />
						<meta property="og:type" content="website" />
						<meta property="og:url" content="' . Ad::url($ad) . '" />';
		if ($ad->Adpics)
		{
			$header_other .= '<meta property="og:image" content="' . Adpics::img($ad->Adpics[0]) . '" />';
		}

		// canonical 
		$header_other .= '<link rel="canonical" href="' . Ad::url($ad) . '" />';
		if ($ad->prev_next->prev)
		{
			$header_other .= '<link rel="prev" href="' . Ad::url($ad->prev_next->prev) . '" />';
		}
		if ($ad->prev_next->next)
		{
			$header_other .= '<link rel="next" href="' . Ad::url($ad->prev_next->next) . '" />';
		}
		$header_other .= Language::relAlternate($ad, 'ad');
		$this->setMeta('header_other', $header_other);


		// add product schema to header 
		$this->_buildProductSchema($ad);


		// Add current ad to latest viewed ads 
		Ad::viewedAdsSet($ad->id);


		// set language switch 
		Language::htmlLanguageBuild($ad, 'ad');

		$vars = array(
			'ad'				 => $ad,
			'catfield'			 => $ad->CategoryFieldRelation,
			'contact'			 => $contact,
			'selected_location'	 => $ad->Location,
			'selected_category'	 => $ad->Category,
			'page_type'			 => self::PAGE_TYPE_AD,
			'page_title'		 => Ad::getTitle($ad),
		);

		// load widgets to locations
		Widget::render($vars);

		$this->display('index/ad', $vars);
	}

	function _contactForm()
	{
		$contact = new stdClass();

		if (get_request_method() == 'POST' && $_POST['action'] == 'contact_form')
		{
			$rules['id'] = 'intval|required';
			$rules['email'] = 'trim|strip_tags|xss_clean|required|valid_email';
			$rules['message'] = 'trim|strip_tags|xss_clean|required';

			$fields['id'] = __('Ad ID');
			$fields['email'] = __('Your email');
			$fields['message'] = __('Message');

			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run() && Config::nounceCheck(true) && Captcha::check())
			{
				// get ad
				$ad = Ad::findByIdFrom('Ad', $_POST['id']);
				if (!$ad)
				{
					// no ad
					$this->validation()->set_error(__('Ad is not found.'));
					return false;
				}


				// check contact ban 
				if (!IpBlock::contactLimitIsBanned())
				{
					if (MailTemplate::sendContactMessage($ad, $_POST['email'], $_POST['message']))
					{
						// increase contact count
						$log_item = new stdClass();
						$log_item->ad_id = $ad->id;
						$log_item->from_user_id = AuthUser::$user->id;
						$log_item->from_email = $_POST['email'];
						$log_item->subject = $_POST['subject'];
						$log_item->message = $_POST['message'];

						IpBlock::contactLimitCount($log_item);

						Flash::set('success', __('Your message sent successfully.'));
						redirect(Ad::url($ad));
					}
					else
					{
						$this->validation()->set_error(__('Error sending message. Please try again later.'));
					}
				}
			}


			$contact->email = $_POST['email'];
			$contact->message = $_POST['message'];
		}

		return $contact;
	}

	function _contactUsForm($redirect = '')
	{
		$contact = new stdClass();

		if (get_request_method() == 'POST' && $_POST['action'] == 'contact_us')
		{
			$rules['email'] = 'trim|strip_tags|xss_clean|required|valid_email';
			$rules['subject'] = 'trim|strip_tags|xss_clean|required';
			$rules['message'] = 'trim|strip_tags|xss_clean|required';

			$fields['email'] = __('Your email');
			$fields['subject'] = __('Subject');
			$fields['message'] = __('Message');

			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run() && Config::nounceCheck(true) && Captcha::check())
			{
				$data = $_POST;
				unset($data['vImageCodP']);

				if (!IpBlock::contactLimitIsBanned())
				{
					if (MailTemplate::sendContactUsMessage($_POST['email'], $_POST['subject'], $_POST['message'], $data))
					{
						// increase contact count 
						$log_item = new stdClass();
						$log_item->ad_id = '';
						$log_item->from_user_id = AuthUser::$user->id;
						$log_item->from_email = $_POST['email'];
						$log_item->subject = $_POST['subject'];
						$log_item->message = $_POST['message'];

						IpBlock::contactLimitCount($log_item);

						Flash::set('success', __('Your message sent successfully.'));
						if (strlen($redirect))
						{
							redirect($redirect);
						}
						else
						{
							redirect(Language::get_url());
						}
					}
					else
					{
						$this->validation()->set_error(__('Error sending message. Please try again later.'));
					}
				}
			}


			$contact->email = $_POST['email'];
			$contact->subject = $_POST['subject'];
			$contact->message = $_POST['message'];
		}

		return $contact;
	}

	function _buildBreadcrumb($location = null, $category = null)
	{
		$breadcrumb = array();

		// add location path
		if ($location)
		{
			Location::getParents($location);
			foreach ($location->arr_parents as $l)
			{
				$breadcrumb[] = array(Location::getName($l), Location::url($l));
			}

			$breadcrumb[] = array(Location::getName($location), Location::url($location));
		}

		// add category path
		if ($category)
		{
			Category::getParents($category);
			foreach ($category->arr_parents as $c)
			{
				$breadcrumb[] = array(Category::getName($c), Location::url($location, $c));
			}

			$breadcrumb[] = array(Category::getName($category), Location::url($location, $category));
		}

		if ($breadcrumb)
		{
			// reset location link
			array_unshift($breadcrumb, array(__('Home'), Language::urlHomeReset()));
		}

		$this->_setBreadcrumb($breadcrumb);

		// return for further use in script 
		return $breadcrumb;
	}

	/**
	 * assign breadcrumb to layout variable and generate rich snipped json-ld data for SEO
	 * 
	 * @param array $breadcrumb
	 */
	function _setBreadcrumb($breadcrumb)
	{
		$this->assignToLayout('breadcrumb', $breadcrumb);


		// format breadcrumb and add to header 
		if ($breadcrumb)
		{
			/*
			  <script type="application/ld+json">
			  {
			  "@context": "https://schema.org",
			  "@type": "BreadcrumbList",
			  "itemListElement": [{
			  "@type": "ListItem",
			  "position": 1,
			  "name": "Books",
			  "item": "https://example.com/books"
			  },{
			  "@type": "ListItem",
			  "position": 2,
			  "name": "Authors",
			  "item": "https://example.com/books/authors"
			  },{
			  "@type": "ListItem",
			  "position": 3,
			  "name": "Ann Leckie",
			  "item": "https://example.com/books/authors/annleckie"
			  },{
			  "@type": "ListItem",
			  "position": 4,
			  "name": "Ancillary Justice",
			  "item": "https://example.com/books/authors/ancillaryjustice"
			  }]
			  }
			  </script> */

			$arr_br = array(
				'@context'			 => "https://schema.org",
				'@type'				 => "BreadcrumbList",
				'itemListElement'	 => array()
			);
			$pos = 1;
			foreach ($breadcrumb as $val)
			{
				$name = View::escape($val[0]);
				$url = $val[1];

				$_arr = array(
					"@type"		 => "ListItem",
					"position"	 => $pos,
					"name"		 => $name,
					"item"		 => $url
				);

				if ($url)
				{
					$_arr['item'] = $url;
				}

				$arr_br['itemListElement'][] = $_arr;

				$pos++;
			}

			// now format as application/ld+json
			$this->setMeta('header_other', '<script type="application/ld+json">' . TextTransform::jsonEncode($arr_br) . '</script>');
		}
	}

	function _buildProductSchema($ad)
	{

		/*
		  <script type="application/ld+json">
		  {
		  "@context": "https://schema.org/",
		  "@type": "Product",
		  "name": "Executive Anvil",
		  "image": [
		  "https://example.com/photos/1x1/photo.jpg",
		  "https://example.com/photos/4x3/photo.jpg",
		  "https://example.com/photos/16x9/photo.jpg"
		  ],
		  "description": "Sleeker than ACME's Classic Anvil, the Executive Anvil is perfect for the business traveler looking for something to drop from a height.",
		  "sku": "0446310786",
		  "offers": {
		  "@type": "Offer",
		  "url": "https://example.com/anvil",
		  "priceCurrency": "USD",
		  "price": "119.99",
		  "priceValidUntil": "2020-11-05",
		  "itemCondition": "https://schema.org/UsedCondition",
		  "availability": "https://schema.org/InStock",
		  "seller": {
		  "@type": "Organization",
		  "name": "Executive Objects"
		  }
		  }
		  }
		  </script> */

		// format product and add to header if has price.
		// if no price then it will show error in google webmaster tool
		$price = AdFieldRelation::getPrice($ad, false);
		if ($price > 0)
		{

			$page_description = Ad::formatCustomFieldsSimpleOptions($ad, array(
						'type'		 => 'all',
						'seperator'	 => ', ',
						'make_link'	 => false
			));
			$page_description .= ($page_description ? ' - ' : '') . $ad->description;
			$page_description = View::escape(TextTransform::excerpt($page_description, 155));


			// seller name 
			$seller_name = User::getNameFromUserOrEmail($ad->User, $ad->email);

			// general info 
			$arr_pr = array(
				"@context"		 => "https://schema.org/",
				"@type"			 => "Product",
				"name"			 => Ad::getTitle($ad),
				"description"	 => $page_description,
				"sku"			 => $ad->id,
				"offers"		 => array(
					"@type"				 => "Offer",
					"url"				 => Ad::url($ad),
					"priceCurrency"		 => Config::option('currency_iso_4721'),
					"price"				 => $price,
					"priceValidUntil"	 => date("Y-m-d", $ad->expireson),
					"availability"		 => ($ad->listed ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"),
					"seller"			 => array(
						"@type"	 => "Organization",
						"name"	 => $seller_name
					)
				)
			);

			/* images */
			if ($ad->Adpics)
			{
				$arr_pr['image'] = array();
				foreach ($ad->Adpics as $adpic)
				{
					$arr_pr['image'][] = Adpics::img($adpic);
				}
			}


			// now format as application/ld+json
			$this->setMeta('header_other', '<script type="application/ld+json">' . TextTransform::jsonEncode($arr_pr) . '</script>');
		}
	}

	function rss($arr_url = array())
	{
		$arr_url = array_filter($arr_url);
		$url2vars = false;
		// use default num records
		$num_rows = self::$num_records;
		$rss_url_prefix = '';


		if (isset($arr_url[0]))
		{
			$type = $arr_url[0];
		}
		else
		{
			$type = '';
		}


		switch ($type)
		{
			case 'featured':
			case 'latest':

				// get user,location, category
				$arr_url2 = $arr_url;
				array_shift($arr_url2);
				$url2vars = Permalink::url2vars($arr_url2);

				// define deault params 
				$url2vars->search_params['listed'] = 1;


				if ($type == 'featured')
				{
					$url2vars->search_params['featured'] = 1;
					$description = __('RSS featured ads');
					$rss_url_prefix = 'featured/';
				}
				else
				{
					$description = __('RSS latest ads');
					$rss_url_prefix = 'latest/';
				}



				// rss of user or category 
				if ($url2vars->selected_user->id)
				{
					$url2vars->search_params['user_id'] = $url2vars->selected_user->id;
				}
				else
				{
					// add category and location 
					$url2vars->search_params['category_id'] = $url2vars->selected_category->id;
					$url2vars->search_params['location_id'] = $url2vars->selected_location->id;


					// build custom query 
					// reset freshness and page if set from custom query 
					unset($_GET['freshness']);
					unset($_GET['page']);
					unset($url2vars->search_params['freshness']);
				}


				$cq = new stdClass();
				$cq->url2vars = $url2vars;
				Ad::cq($cq);

				// get ads in given category and location and user
				//$ads = Ad::query($cq->query_ordered . ' LIMIT ' . $num_rows, $cq->values);
				$ads = Ad::cqQueryPage($cq, 1, $num_rows);

				// generate proper description 				
				$title_str = Ad::cqFormatTitle($cq);

				if ($title_str)
				{
					$description .= ': ' . $title_str;
				}

				break;
			default :
				$ads = Ad::findAllFrom('Ad', "listed=1 ORDER BY published_at DESC LIMIT " . $num_rows, array());
				$description = __('RSS latest ads');
				break;
		}

		// url to current rss feed
		$url_origin = Location::urlOrigin($url2vars->selected_location, $url2vars->selected_category, $url2vars->selected_user);
		$rss_url = Language::get_url('rss/' . $rss_url_prefix . $url_origin);

		// url to related page 
		$rss_link = Language::get_url($url_origin);

		// echo '[$description:' . $description . ']';
		// echo '[$rss_url:' . $rss_url . ']';
		// append images 
		Ad::appendAdpics($ads);

		header("Content-Type: application/xml; charset=UTF-8");

		$this->setLayout(false);
		$this->display('index/rss', array(
			'ads'			 => $ads,
			'description'	 => $description,
			'rss_url'		 => $rss_url,
			'rss_link'		 => $rss_link
		));
	}

	function sitemap()
	{
		// get all languages
		// get all locations
		// get all categories
		// get all pages
		// get latest 300 featured ads
		// set to default language 
		Language::setLocale(Language::getDefault());


		$cache_key = 'sitemap';

		$return = SimpleCache::get($cache_key);
		if ($return === false)
		{

			// reset homepage 
			$links[Language::get_url()] = '<changefreq>hourly</changefreq><priority>1</priority>';

			$languages = Language::getLanguages(Language::STATUS_ENABLED);
			foreach ($languages as $lng)
			{
				$url = Language::get_url('', $lng->id);
				if (!isset($links[$url]))
				{
					$links[$url] = '<changefreq>daily</changefreq><priority>0.4</priority>';
				}
			}

			// get all locations
			$locations = Location::getAllLocationNamesTree(Location::STATUS_ENABLED);
			foreach ($locations as $parent_id => $loc_arr)
			{
				foreach ($loc_arr as $loc)
				{
					$url = Location::url($loc);
					$links[$url] = '<changefreq>daily</changefreq><priority>0.8</priority>';
				}
			}

			// get all categories
			$categories = Category::getAllCategoryNamesTree(Category::STATUS_ENABLED);
			$default_location_id = Config::option('default_location');
			$default_location = Location::getLocationFromTree($default_location_id, Location::STATUS_ENABLED);
			foreach ($categories as $parent_id => $cat_arr)
			{
				foreach ($cat_arr as $cat)
				{
					$url = Category::url($cat, $default_location);
					$links[$url] = '<changefreq>daily</changefreq><priority>0.6</priority>';
				}
			}

			// get all pages
			$pages = Page::getAllPageNamesTree(Page::STATUS_ENABLED);
			foreach ($pages as $parent_id => $page_arr)
			{
				foreach ($page_arr as $page)
				{
					$url = Page::url($page);
					$links[$url] = '<changefreq>monthly</changefreq><priority>0.4</priority>';
				}
			}

			// get featured ads 300
			$num = 1000;
			$ads = Ad::findAllFrom('Ad', "listed=1 AND featured=1 ORDER BY published_at DESC LIMIT " . $num, array(), MAIN_DB, 'id,title,updated_at,published_at');
			$count_featured = count($ads);
			foreach ($ads as $ad)
			{
				$url = Ad::url($ad);
				$links[$url] = '<lastmod>' . date('Y-m-d', $ad->updated_at) . '</lastmod><changefreq>monthly</changefreq><priority>0.5</priority>';
			}

			// check if not much urls then load more to max 3000 
			$num_rest = 3000 - count($links) + $count_featured;
			if ($num_rest < 300)
			{
				// load at least 1000 ads if there are already too many links
				$num_rest = 1000;
			}
			if ($num_rest > 10)
			{
				$ads = Ad::findAllFrom('Ad', "listed=1 ORDER BY published_at DESC LIMIT " . $num_rest, array(), MAIN_DB, 'id,title,updated_at,published_at');
				foreach ($ads as $ad)
				{
					$url = Ad::url($ad);
					if (!isset($links[$url]))
					{
						$links[$url] = '<lastmod>' . date('Y-m-d', $ad->updated_at) . '</lastmod><changefreq>monthly</changefreq><priority>0.4</priority>';
					}
				}
			}




			// content
			$return = '<?xml version="1.0" encoding="UTF-8"?>
				<?xml-stylesheet type="text/xsl" href="' . URL_ASSETS . 'js/sitemap.xsl"?>
				<!-- generator="ClassiBase/' . Config::VERSION . '" -->
				<!-- sitemap-generator-url="http://classibase.com" -->
				<!-- generated-on="' . date('r') . '" -->
				<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
				';

			foreach ($links as $url => $val)
			{
				$return .= '<url><loc>' . $url . '</loc>' . $val . '</url>
				';
			}
			$return .= '</urlset>';


			// store sitemap in cache 
			SimpleCache::set($cache_key, $return, 86400); //24 hours
		}

		// page header
		header("Content-Type: application/xml; charset=UTF-8");
		echo $return;

		exit;
	}

	function page($permalink)
	{
		// if not sent already then check for ad id
		$arr_id = explode('-', $permalink);
		$page_id = array_pop($arr_id);
		$page_id = intval($page_id);
		if ($page_id)
		{
			$selected_page = Page::findByIdFrom('Page', $page_id);
		}
		if (!$selected_page)
		{
			$this->_page_not_found();
		}


		// if ad is not enabled then do not display it at all for other user
		if (!$selected_page->enabled)
		{
			$msg = __('Page is disabled and visible only to site admin');
			if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
			{
				$this->validation()->set_info($msg);
			}
			else
			{
				Config::displayMessagePage($msg, 'error', true);
			}
		}

		// check contact form
		$contact = $this->_contactUsForm(Page::url($selected_page));
		// preset email in contact form if user logged in 
		if (!isset($contact->email) && AuthUser::isLoggedIn(false))
		{
			$contact->email = AuthUser::$user->email;
		}

		// append page description 
		Page::appendAll($selected_page);


		// build breadcrumb
		Page::getParents($selected_page);
		$breadcrumb_arr = array();
		$breadcrumb_arr[] = array(__('Home'), Language::get_url());
		foreach ($selected_page->arr_parents as $l)
		{
			$breadcrumb_arr[] = array(Page::getName($l), Page::url($l));
		}
		// add self as last 
		$breadcrumb_arr[] = array(Page::getName($selected_page), '');
		$this->_setBreadcrumb($breadcrumb_arr);

		// add location and category to title and keywords
		$arr_keyword = array();
		foreach ($breadcrumb_arr as $br)
		{
			$arr_keyword[] = $br[0];
		}
		if ($arr_keyword)
		{
			$str_keyword = implode(', ', $arr_keyword);
		}

		// canonical link
		$header_other = '<link rel="canonical" href="' . Page::url($selected_page) . '" />';
		$header_other .= Language::relAlternate($selected_page, 'page');
		$this->setMeta('header_other', $header_other);

		$vars = array(
			'selected_page'	 => $selected_page,
			'contact'		 => $contact,
			'page_type'		 => self::PAGE_TYPE_INFO,
			'page_title'	 => Page::getName($selected_page),
		);

		$page_description = Page::formatDescription($selected_page, $vars, false);

		$this->setMeta('title', Page::getName($selected_page));
		$this->setMeta('description', View::escape(TextTransform::excerpt($page_description, 155)));
		$this->setMeta('keywords', $str_keyword);

		// load widgets to locations
		Widget::render($vars);

		// set language switch 
		Language::htmlLanguageBuild($selected_page, 'page');

		$this->display('index/page', $vars);
	}

}
