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
class AdminController extends Controller
{

	private static $num_records = 20; // records per page

	public function __construct()
	{
		IpBlock::isBlockedIp();

		// layout
		$this->setLayout('backend');

		// check if user is logged in 
		AuthUser::isLoggedIn(true);

		// build language menu to link to admin 
		$this->assignToLayout('sidemenu', Config::_sidemenu(Language::htmlLanguageBuild('admin/')));

		// set demo message 
		Config::demoInfo();

		// noindex meta header
		$header_other = '<meta name="robots" content="noindex, nofollow" />';
		$this->setMeta('header_other', $header_other);

		//Config::performPassiveUpdates();		
	}

	function index()
	{
		// only moderators can view overview
		if (!AuthUser::hasPermission(User::PERMISSION_MODERATOR))
		{
			return $this->itemsmy();
		}

		$overview = array();

		//$overview->payments_today = Payments::paymentsToday();
		//$overview->payments_today_amount = Payments::paymentsTodayAmount();
		//ads_unapproved
		$ov = new stdClass();
		$ov->id = 'ads_unapproved';
		$ov->val = Ad::countByClass('Ad', 'enabled=?', array(Ad::STATUS_PENDING_APPROVAL), false);
		$ov->title = __('Ads pending approval');
		$ov->url = Language::get_url('admin/itemsPending/');
		$overview[$ov->id] = $ov;

		//ads_unverified
		$ov = new stdClass();
		$ov->id = 'ads_unverified';
		$ov->val = Ad::countByClass('Ad', 'verified NOT IN (?,?) AND enabled NOT IN (?,?,?)', array('0', '1', Ad::STATUS_TRASH, Ad::STATUS_BANNED, Ad::STATUS_DUPLICATE), false);
		$ov->title = __('Ads pending verification');
		$ov->url = Language::get_url('admin/items/?verified=0&enabled=_-tbd');
		$overview[$ov->id] = $ov;

		//ads_abused
		$ov = new stdClass();
		$ov->id = 'ads_abused';
		$ov->val = Ad::countByClass('Ad', 'abused!=? AND enabled NOT IN (?,?,?) ', array(0, Ad::STATUS_TRASH, Ad::STATUS_BANNED, Ad::STATUS_DUPLICATE), false);
		$ov->title = __('Ads with abuse report');
		$ov->url = Language::get_url('admin/items/?abused=1&enabled=_-tbd');
		$overview[$ov->id] = $ov;

		//ads_running
		$ov = new stdClass();
		$ov->id = 'ads_running';
		$ov->val = Ad::countByClass('Ad', 'listed=?', array(1), false);
		$ov->title = __('Ads running');
		$ov->url = Language::get_url('admin/items/?enabled=_r');
		$overview[$ov->id] = $ov;

		//ads_not_running
		$ov = new stdClass();
		$ov->id = 'ads_not_running';
		$ov->val = Ad::countByClass('Ad', 'listed=?', array(0), false);
		$ov->title = __('Ads not running');
		$ov->url = Language::get_url('admin/items/?enabled=_rn');
		$overview[$ov->id] = $ov;

		//ads_expired
		$ov = new stdClass();
		$ov->id = 'ads_expired';
		$ov->val = Ad::countByClass('Ad', 'expireson<=?', array(Config::roundTime()), false);
		$ov->title = __('Ads expired');
		$ov->url = Language::get_url('admin/items/?enabled=_ex');
		$overview[$ov->id] = $ov;

		//ads_requiring_payment
		$ov = new stdClass();
		$ov->id = 'ads_requiring_payment';
		$ov->val = Ad::countByClass('Ad', 'requires_posting_payment=?', array(1), false);
		$ov->title = __('Ads requiring payment');
		$ov->url = Language::get_url('admin/items/?payment=1');
		$overview[$ov->id] = $ov;

		//location_count
		$ov = new stdClass();
		$ov->id = 'location_count';
		$ov->val = Location::countFrom('Location');
		$ov->title = __('Locations');
		$ov->url = Language::get_url('admin/locations/');
		$overview[$ov->id] = $ov;

		//category_count
		$ov = new stdClass();
		$ov->id = 'category_count';
		$ov->val = Category::countFrom('Category');
		$ov->title = __('Categories');
		$ov->url = Language::get_url('admin/categories/');
		$overview[$ov->id] = $ov;

		//user_count
		$ov = new stdClass();
		$ov->id = 'user_count';
		$ov->val = User::countFrom('User');
		$ov->title = __('Users');
		$ov->url = Language::get_url('admin/users/');
		$overview[$ov->id] = $ov;

		//user_pending_verification
		$ov = new stdClass();
		$ov->id = 'user_pending_verification';
		$ov->val = User::countFrom('User', "activation<>'0'");
		$ov->title = __('Users pending verification');
		$ov->url = Language::get_url('admin/users/notverified/');
		if ($ov->val)
		{
			$overview[$ov->id] = $ov;
		}

		//user_pending_approval
		$ov = new stdClass();
		$ov->id = 'user_pending_approval';
		$ov->val = User::countFrom('User', "enabled='0'");
		$ov->title = __('Users pending approval');
		$ov->url = Language::get_url('admin/users/notenabled/');
		if ($ov->val)
		{
			$overview[$ov->id] = $ov;
		}

		//user_pending_dealer
		$ov = new stdClass();
		$ov->id = 'user_pending_dealer';
		$ov->val = User::countFrom('User', "pending_level=?", array(User::PERMISSION_DEALER));
		$ov->title = __('Pending upgrade to dealer');
		$ov->url = Language::get_url('admin/users/upgradetodealer/');
		if ($ov->val)
		{
			$overview[$ov->id] = $ov;
		}


		// load classibase news 
		$classibase_news = Config::loadNews();


		// check version changes 
		Update::updateNotice();


		// check for fulltext status 
		AdFulltext::notice();


		$this->display('admin/index', array(
			'overview'			 => $overview,
			'classibase_news'	 => $classibase_news,
			'menu_selected'		 => 'admin/',
		));
	}

	function items()
	{
		if (!AuthUser::hasPermission(User::PERMISSION_MODERATOR))
		{
			// redirect to itemsmy. this is because when editing ad it may return to this url by default
			return $this->itemsmy();
		}

		// perform actions
		$this->_ads();

		// search for ad id first 
		$id = trim($_GET['search']);
		// if ad_id given then get ad by id
		if (intval($id) . '' === $id)
		{
			$id = intval($id);
			$ads = Ad::findAllFrom('Ad', 'id=?', array($id));
			$search_desc_arr[] = __('with ID <b>{search}</b>', array('{search}' => $id)) . Language::thisUrlRemoveLink(array('search' => ''));
		}


		if ($ads)
		{
			$total = 1;
		}
		else
		{
			// if no ad found by ad then search by other options
			// build search
			$from = array();
			$fields = array();
			$order = array();
			$whereA = array();
			$whereB = array();
			$search_desc_arr = array();

			// Default values for ads
			$category_id = intval($_GET['category_id']);
			$location_id = intval($_GET['location_id']);

			Input::getInstance()->get();

			$array_trim = array('search', 'email', 'phone');
			foreach ($array_trim as $trim)
			{
				if (isset($_GET[$trim]))
				{
					$_GET[$trim] = trim($_GET[$trim]);
				}
			}

			if (strlen($_GET['search']))
			{
				if ($_GET['search_exact'])
				{
					$arr_q = Ad::searchQuery2Array($_GET['search']);
					foreach ($arr_q as $_q)
					{
						$arr_variation = array();
						$arr_variation_sql = array();

						$arr_variation[] = $_q;

						foreach ($arr_variation as $v)
						{
							$arr_variation_2 = array();
							$arr_variation_2[0] = StringUtf8::strtolower($v);
							// convert to latin characters
							$arr_variation_2[1] = StringUtf8::convert($v);
							// remove duplicate characters 
							$arr_variation_2[2] = StringUtf8::removeRepeatedChars($arr_variation_2[0]);
							$arr_variation_2[3] = StringUtf8::removeRepeatedChars($arr_variation_2[1]);

							$arr_variation_2 = array_unique($arr_variation_2);

							foreach ($arr_variation_2 as $v2)
							{
								//$whereA[] = '(ad.title LIKE ? OR ad.description LIKE ?)';
								$arr_variation_sql[] = 'ad.title LIKE ? OR ad.description LIKE ?';
								$whereB[] = '%' . $v2 . '%';
								$whereB[] = '%' . $v2 . '%';
							}
						}
						//$cq_params_arr['where'] = array();
						if ($arr_variation_sql)
						{
							$whereA[] = '(' . implode(' OR ', $arr_variation_sql) . ')';
							//$cq->whereA[] = '(' . implode(' OR ', $arr_variation_sql) . ')';
						}
					}
				}
				else
				{
					// use fuzy search fulltextindex 
					$q_n = TextTransform::text_normalize($_GET['search'], 'search');

					$from[] = AdFulltext::tableNameFromClassName('AdFulltext') . ' aft';
					$fields[] = "MATCH (aft.title) AGAINST (" . Record::escape($q_n) . "  IN BOOLEAN MODE)*10 AS score1";
					$fields[] = "MATCH (aft.description) AGAINST (" . Record::escape($q_n) . "  IN BOOLEAN MODE) AS score2";
					$whereA[] = "ad.id=aft.id AND MATCH (aft.description) AGAINST (?  IN BOOLEAN MODE)";
					$whereB[] = TextTransform::text_normalize_simplify_search($q_n);
					$order[] = '(score1)+(score2) DESC';
				}

				$search_desc_arr[] = View::escape($_GET['search'])
						. Language::thisUrlRemoveLink(array('search' => ''));
			}
			if (strlen($_GET['email']))
			{
				$whereA[] = "email LIKE ?";
				$whereB[] = "%$_GET[email]%";
				$search_desc_arr[] = __('email containing <b>{search}</b>', array('{search}' => $_GET['email']))
						. Language::thisUrlRemoveLink(array('email' => ''));
			}
			if (strlen($_GET['phone']))
			{
				$whereA[] = "phone LIKE ?";
				$whereB[] = "%$_GET[phone]%";
				$search_desc_arr[] = __('phone containing <b>{search}</b>', array('{search}' => $_GET['phone']))
						. Language::thisUrlRemoveLink(array('phone' => ''));
			}
			if ($_GET['added_by'])
			{
				$added_by = intval($_GET['added_by']);
				$whereA[] = "added_by=?";
				$whereB[] = $added_by;

				//  get user email and link to edit user
				$user = User::findByIdFrom('User', $added_by);
				if ($user)
				{
					$added_by = '<a href="' . Language::get_url('admin/users/edit/' . $user->id . '/') . '">' . View::escape($user->email) . '</a>';
				}

				$search_desc_arr[] = __('added by <b>{search}</b>', array('{search}' => $added_by))
						. Language::thisUrlRemoveLink(array('added_by' => ''));
			}


			if ($location_id)
			{
				// get all sub locations 
				$location = Location::findByIdFrom('Location', $location_id);
				if ($location)
				{
					Ad::buildLocationQuery($location, $whereA, $whereB);

					// add search filter remover link
					$_sel = $location;
					while ($_sel->parent_id > 0)
					{
						$custom_filter = Location::getName($_sel) . Language::thisUrlRemoveLink(array('location_id' => $_sel->parent_id));
						array_unshift($search_desc_arr, $custom_filter);
						$_sel = Location::getLocationFromTree($_sel->parent_id);
					}
					$custom_filter = Location::getName($_sel) . Language::thisUrlRemoveLink(array('location_id' => ''));
					array_unshift($search_desc_arr, $custom_filter);

					//$search_desc_arr[] = Location::getFullName($location) . Language::thisUrlRemoveLink(array('location_id' => ''));
				}
			}
			if ($category_id)
			{
				// get all sub locations 
				$category = Category::findByIdFrom('Category', $category_id);
				if ($category)
				{
					Ad::buildCategoryQuery($category, $whereA, $whereB);

					// add search filter remover link
					$_sel = $category;
					while ($_sel->parent_id > 0)
					{
						$custom_filter = Category::getName($_sel) . Language::thisUrlRemoveLink(array('category_id' => $_sel->parent_id));
						array_unshift($search_desc_arr, $custom_filter);
						$_sel = Category::getCategoryFromTree($_sel->parent_id);
					}
					$custom_filter = Category::getName($_sel) . Language::thisUrlRemoveLink(array('category_id' => ''));
					array_unshift($search_desc_arr, $custom_filter);

					/* $search_desc_arr[] = __('in <b>{name}</b>', array(
					  '{name}' => Category::getFullName($category)
					  ))
					  . Language::thisUrlRemoveLink(array('category_id' => '')); */
				}
			}

			if (isset($_GET['verified']) && $_GET['verified'] !== "")
			{
				if ($_GET['verified'])
				{
					$whereA[] = "verified IN ('0','1')";
				}
				else
				{
					$whereA[] = "verified NOT IN ('0','1')";
				}
				$search_desc_arr[] = ($_GET['verified'] ? __('Verified') : __('not verified'))
						. Language::thisUrlRemoveLink(array('verified' => ''));
			}
			$arr_enabled = array(
				Ad::STATUS_PENDING_APPROVAL	 => Ad::statusName(Ad::STATUS_PENDING_APPROVAL),
				Ad::STATUS_ENABLED			 => Ad::statusName(Ad::STATUS_ENABLED),
				Ad::STATUS_PAUSED			 => Ad::statusName(Ad::STATUS_PAUSED),
				Ad::STATUS_COMPLETED		 => Ad::statusName(Ad::STATUS_COMPLETED),
				Ad::STATUS_INCOMPLETE		 => Ad::statusName(Ad::STATUS_INCOMPLETE),
				Ad::STATUS_DUPLICATE		 => Ad::statusName(Ad::STATUS_DUPLICATE),
				Ad::STATUS_BANNED			 => Ad::statusName(Ad::STATUS_BANNED),
				Ad::STATUS_TRASH			 => Ad::statusName(Ad::STATUS_TRASH),
				'_ex'						 => __('Expired'),
				'_en'						 => __('Not expired'),
				'_r'						 => __('Running'),
				'_rn'						 => __('Not running'),
				'_f'						 => __('Featured'),
				'_-tbd'						 => __('Not ({name})', array(
					'{name}' => Ad::statusName(Ad::STATUS_TRASH)
					. ', ' . Ad::statusName(Ad::STATUS_BANNED)
					. ', ' . Ad::statusName(Ad::STATUS_DUPLICATE)
				))
			);
			if (isset($_GET['enabled']) && $_GET['enabled'] !== "" && isset($arr_enabled[$_GET['enabled']]))
			{
				// check if it is custom status 
				if (strpos($_GET['enabled'], '_') === 0)
				{
					// custom status starts with _
					$arr_enabled_where = array(
						'_ex'	 => array(
							'where'	 => 'expireson < ?',
							'values' => Config::roundTime()
						),
						'_en'	 => array(
							'where'	 => 'expireson >= ?',
							'values' => Config::roundTime()
						),
						'_r'	 => array(
							'where'	 => 'listed = ?',
							'values' => 1
						),
						'_rn'	 => array(
							'where'	 => 'listed = ?',
							'values' => 0
						),
						'_f'	 => array(
							'where'	 => 'featured = ?',
							'values' => 1
						),
						'_-tbd'	 => array(
							'where'	 => 'enabled NOT IN (?,?,?)',
							'values' => array(Ad::STATUS_TRASH, Ad::STATUS_BANNED, Ad::STATUS_DUPLICATE)
						),
					);

					$whereA[] = $arr_enabled_where[$_GET['enabled']]['where'];
					if (is_array($arr_enabled_where[$_GET['enabled']]['values']))
					{
						foreach ($arr_enabled_where[$_GET['enabled']]['values'] as $val)
						{
							$whereB[] = $val;
						}
					}
					else
					{
						$whereB[] = $arr_enabled_where[$_GET['enabled']]['values'];
					}
				}
				else
				{
					// it is real enabled value 
					$whereA[] = "enabled = ?";
					$whereB[] = intval($_GET['enabled']);
				}
				$search_desc_arr[] = $arr_enabled[$_GET['enabled']] . Language::thisUrlRemoveLink(array('enabled' => ''));
			}

			if (isset($_GET['abused']) && $_GET['abused'] !== "")
			{
				$whereA[] = "abused " . ($_GET['abused'] ? "> 0" : "= 0");
				$order[] = "abused DESC";
				$search_desc_arr[] = ($_GET['abused'] ? __('with abuse reports') : __('without abuse reports'))
						. Language::thisUrlRemoveLink(array('abused' => ''));
			}


			if (isset($_GET['payment']) && $_GET['payment'] !== "")
			{
				switch ($_GET['payment'])
				{
					case 0:
						// not requires_posting_payment
						$whereA[] = "requires_posting_payment=?";
						$whereB[] = 0;
						$search_desc_arr[] = __('payment not required') . Language::thisUrlRemoveLink(array('payment' => ''));
						break;
					case 1:
						// requires_posting_payment
						$whereA[] = "requires_posting_payment = ?";
						$whereB[] = 1;
						$search_desc_arr[] = __('payment required') . Language::thisUrlRemoveLink(array('payment' => ''));
						break;
				}
			}

			// search query
			$where = implode(" AND ", $whereA);



			if (!$where)
			{
				$where = "1=1";
			}

			// remove ad. prefixed added in location and category queries
			$where_ = str_replace('ad.', '', $where);


			$order[] = "added_at DESC";
			$order_by_str = "ORDER BY " . implode(', ', $order);


			if ($from)
			{
				// query with joins 
				$sql_total = "SELECT count(ad.id) as num "
						. " FROM " . Ad::tableNameFromClassName('Ad') . " ad, " . implode(', ', $from)
						. " WHERE " . $where;
				$total = Ad::countByCustom($sql_total, $whereB);
			}
			else
			{
				// simple query 
				$total = Ad::countByClass('Ad', $where_, $whereB);
			}

			// get ads
			$page = intval($_REQUEST['page']);
			if (!$page)
			{
				$page = 1;
			}
			$perpage = intval($_REQUEST['perpage']);
			if ($perpage < self::$num_records)
			{
				$perpage = self::$num_records;
			}
			$st = ($page - 1) * $perpage;
			// if no ads for given page then go to first page 
			if ($st > 0 && $st >= $total && $page > 1)
			{
				$page = 1;
				$st = 0;
			}


			$sql_limit = " LIMIT $st," . $perpage;

			if ($from)
			{
				// query with joins  
				/* $from = array();
				  $fields = array();
				  $order = array();
				 */
				$select_field_str = $fields ? ' , ' . implode(' , ', $fields) : '';
				$sql_ads = "SELECT ad.* " . $select_field_str
						. " FROM " . Ad::tableNameFromClassName('Ad') . " ad, " . implode(' , ', $from)
						. " WHERE " . $where . " "
						. $order_by_str;
				$ads = Ad::queryUsingIds($sql_ads . $sql_limit, $whereB);
			}
			else
			{
				// simple query 
				$sql_ads = " $where_ " . $order_by_str;
				$ads = Ad::findAllFromUseIds('Ad', $sql_ads . $sql_limit, $whereB, MAIN_DB, '*', 'id');
			}


			// check if no ads then redirect to last available page 
			if (!$ads && $page > 1)
			{
				// recalculate total from fresh 
				if ($from)
				{
					$total = Ad::countByCustom($sql_total, $whereB, null);
				}
				else
				{
					// simple query 
					$total = Ad::countByClass('Ad', $where_, $whereB, null);
				}
				$last_page = ceil($total / $perpage);
				if ($last_page > 0 && $last_page < $page)
				{
					$page = $last_page;
					$st = ($page - 1) * $perpage;
					$sql_limit = " LIMIT $st," . $perpage;
					// repeat ads query with new $st
					if ($from)
					{
						$ads = Ad::queryUsingIds($sql_ads . $sql_limit, $whereB);
					}
					else
					{
						// simple query 						
						$ads = Ad::findAllFromUseIds('Ad', $sql_ads . $sql_limit, $whereB, MAIN_DB, '*', 'id');
					}
				}
			}

			// populate paginator
			$total_pages = ceil($total / $perpage);
			$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/items/?page={page}&' . Language::thisUrl()));
		}


		// append images 
		Ad::appendAdpics($ads, true);
		//User::appendObject($ads, 'added_by', 'User');
		// append payment sums
		Ad::appendPaymentAmounts($ads);



		// description for search
		//$search_desc = __('Total {num} ads found', array('{num}' => $total));
		$search_desc = '';
		if ($search_desc_arr)
		{
			$search_desc .= '<span class="search_filter">' . implode('</span> <span class="search_filter">', $search_desc_arr) . '</span>';

			//$this->setMeta('title', strip_tags(implode(', ', str_replace('>x<', '><', $search_desc_arr))));
			$this->setMeta('title', strip_tags(TextTransform::strip_tags_content(implode(', ', $search_desc_arr), '<b>')));
		}


		// FIXME: add activate controls for no javascript users
		// breadcrumb
		/*
		  $breadcrumb = array(
		  array(__('Home'), Language::get_url('admin/')),
		  array(__('Ads'), Language::get_url('admin/items/'))
		  );
		  $this->assignToLayout('breadcrumb', $breadcrumb);
		 */

		// set title and subtitle 
		if ($total)
		{
			$this->setMeta('title_sub', __('{num}', array('{num}' => number_format($total))));
		}
		$this->display('admin/ads', array(
			'ads'			 => $ads,
			'menu_selected'	 => 'admin/items/',
			'total'			 => $total,
			'paginator'		 => $paginator,
			'search_desc'	 => $search_desc,
			'returl'		 => Language::getCurrentUrl(true) . '?' . Language::thisUrl(array('page' => intval($page)))
		));
	}

	/**
	 * Items pending approval grouped by user 
	 * 
	 * @return type
	 */
	function itemsPending($added_by = null)
	{
		if (!AuthUser::hasPermission(User::PERMISSION_MODERATOR))
		{
			// redirect to itemsmy. this is because when editing ad it may return to this url by default
			return $this->itemsmy();
		}

		// perform actions
		$this->_ads();


		// initial total
		$total_pending = 0;

		// search for pending moderation items 
		// get oldest pending moderation item 
		if (!is_null($added_by))
		{
			// try finding pending items by user 
			$ad = Ad::findOneFrom('Ad', "enabled=? AND added_by=?", array(Ad::STATUS_PENDING_APPROVAL, intval($added_by)));
		}
		if (!$ad)
		{
			$ad = Ad::findOneFrom('Ad', "enabled=? ORDER BY id", array(Ad::STATUS_PENDING_APPROVAL));
		}
		if ($ad)
		{
			$ads_other = array();
			$total_pending = Ad::countFrom('Ad', "enabled=?", array(Ad::STATUS_PENDING_APPROVAL));
			// get user 
			if ($ad->added_by)
			{
				$user = User::findByIdFrom('User', $ad->added_by);
			}

			// total pending itms by user
			$total = 1;
			$total_other = 0;

			if ($user)
			{
				// we have user 
				$total = User::countAdType($user, 'enabled' . Ad::STATUS_PENDING_APPROVAL);
				$total_all = User::countAdType($user, 'total_all');
				$total_other = $total_all - $total;

				if ($total > 1)
				{
					// get pending moderation items by same owner 
					$ads = Ad::findAllFrom('Ad', "added_by=? AND enabled=? ORDER BY id LIMIT " . self::$num_records, array($ad->added_by, Ad::STATUS_PENDING_APPROVAL));
				}

				if ($total_other > 0)
				{
					$other_limit = 20;
					// get other latest items from same owner
					$ads_other = Ad::findAllFrom('Ad', "added_by=? AND enabled!=? ORDER BY published_at DESC LIMIT " . $other_limit, array($ad->added_by, Ad::STATUS_PENDING_APPROVAL));
				}
			}

			if (!$ads)
			{
				// no more pending ads from same user so use this ad as pending
				$ads = array($ad);
			}

			$ads_all = array_merge($ads, $ads_other);
			// now append images to all items
			// append images 
			Ad::appendAdpics($ads_all, true);
			// append payment sums
			Ad::appendPaymentAmounts($ads_all);

			// add prev and next pending ad users 
			$ad->next = Ad::findOneFrom('Ad', "enabled=? AND added_by>? ORDER BY added_by", array(Ad::STATUS_PENDING_APPROVAL, $ad->added_by));

			$ad->prev = Ad::findOneFrom('Ad', "enabled=? AND added_by<? ORDER BY added_by DESC", array(Ad::STATUS_PENDING_APPROVAL, $ad->added_by));

			//var_dump($ad);
			if ($ad->next->id === $ad->prev->id)
			{
				unset($ad->prev);
			}

			//var_dump($ad);
		}

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Ads'), Language::get_url('admin/items/')),
			array(Ad::statusName(Ad::STATUS_PENDING_APPROVAL), Language::get_url('admin/itemsPending/')),
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);


		// set title and subtitle 
		if ($total_pending)
		{
			$this->setMeta('title_sub', __('{num}', array('{num}' => number_format($total_pending))));
		}

		$this->display('admin/itemsPending', array(
			'ads'			 => $ads,
			'ad'			 => $ad,
			'ads_other'		 => $ads_other,
			'user'			 => $user,
			'total'			 => $total,
			'total_pending'	 => $total_pending,
			'total_other'	 => $total_other,
			'menu_selected'	 => 'admin/itemsPending/',
			'returl'		 => Language::getCurrentUrl(true)
		));
	}

	/**
	 * perform actions with selected ads. delete, approve, verify...
	 * 
	 * @return boolean
	 */
	private function _ads($returl = null)
	{
		/* @var $permission_moderator bool */
		$permission_moderator = AuthUser::hasPermission(User::PERMISSION_MODERATOR);
		$count_ads = isset($_POST['ad']) ? count($_POST['ad']) : 0;
		$ad_ids = $_POST['ad'];
		$bulk_actions = $_POST['bulk_actions'];
		$msg_success = '';
		$msg_error = '';

		// if not post then return empty 
		if (get_request_method() !== 'POST')
		{
			return false;
		}

		if (is_null($returl))
		{
			$query_params = Language::thisUrl(array('page' => $_REQUEST['page']));
			$returl = Language::getCurrentUrl(true) . ($query_params ? '?' . $query_params : '');
		}

		// convert to complete url 
		$returl = Language::get_url($returl);

		// check nounce 
		if (!Config::nounceCheck(true))
		{
			//return false;
			redirect($returl);
		}

		// no items passed then return 
		if (!$count_ads)
		{
			//return false;
			redirect($returl);
		}

		// bulk actions 
		if (strlen($bulk_actions) && $ad_ids)
		{
			switch ($bulk_actions)
			{
				case 'approve':
					if ($permission_moderator)
					{
						// moderator can approve any ad not paused by user
						$approve = Ad::statusApproveByIds($ad_ids, true);
					}

					if ($approve)
					{
						$msg_success = __('{num} ads approved', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error approving ads');
					}
					break;
				case 'verify':
					if ($permission_moderator)
					{
						// moderator can verify any ad
						$verify = Ad::verifyByIds($ad_ids, 0);
					}
					else
					{
						// user can verify only own ads
						$verify = Ad::verifyByIds($ad_ids, 1, AuthUser::$user);
					}
					// verify by admin, set 0
					if ($verify)
					{
						$msg_success = __('{num} ads verified', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error verifying ads');
					}
					break;
				case 'del':
					// only moderator can delete any ad 
					if ($permission_moderator)
					{
						// moderator can delete any ad
						$delete = Ad::deleteByIds($ad_ids);
						if ($delete)
						{
							$msg_success = __('{num} ads deleted', array('{num}' => $count_ads));
						}
						else
						{
							$msg_error = __('Error deleting ads');
						}
					}
					else
					{
						$msg_error = __('Only moderator can completely delete ads');
					}
					break;
				case 'unapprove':
					if ($permission_moderator)
					{
						// moderator can pause any approved ad.
						$status_updated = Ad::statusUnapproveByIds($ad_ids);
					}
					// moderator can unapprove previous;ly approved ads
					if ($status_updated)
					{
						$msg_success = __('{num} ads unapproved', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error unapproving ads');
					}
					break;
				case 'pause':
					if ($permission_moderator)
					{
						// moderator can pause any approved ad.
						$status_updated = Ad::statusPauseByIds($ad_ids);
					}
					else
					{
						// user pausing previously approved own ads
						$status_updated = Ad::statusPauseByIds($ad_ids, AuthUser::$user);
					}

					if ($status_updated)
					{
						$msg_success = __('{num} items paused', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error pausing items');
					}
					break;
				case 'unpause':
					if ($permission_moderator)
					{
						// moderator can unpause any paused ad.
						$status_updated = Ad::statusUnpauseByIds($ad_ids);
					}
					else
					{
						// user unpausing previously paused own ads
						$status_updated = Ad::statusUnpauseByIds($ad_ids, AuthUser::$user);
					}

					if ($status_updated)
					{
						$msg_success = __('{num} items unpaused', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error unpausing items');
					}
					break;
				case 'completed':
					if ($permission_moderator)
					{
						// moderator can pause any approved ad.
						$status_updated = Ad::statusCompletedByIds($ad_ids);
					}
					else
					{
						// user pausing previously approved own ads
						$status_updated = Ad::statusCompletedByIds($ad_ids, AuthUser::$user);
					}

					if ($status_updated)
					{
						$msg_success = __('{num} items completed', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error completing items');
					}
					break;
				case 'ban':
					if ($permission_moderator)
					{
						// moderator can pause any approved ad.
						$status_updated = Ad::statusBanByIds($ad_ids);
					}
					if ($status_updated)
					{
						$msg_success = __('{num} items banned', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error banning items');
					}
					break;
				case 'unban':
					if ($permission_moderator)
					{
						// moderator can pause any approved ad.
						$status_updated = Ad::statusUnBanByIds($ad_ids);
					}
					if ($status_updated)
					{
						$msg_success = __('{num} items banned', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error banning items');
					}
					break;
				case 'incomplete':
					if ($permission_moderator)
					{
						// moderator can pause any approved ad.
						$status_updated = Ad::statusIncompleteByIds($ad_ids);
					}
					if ($status_updated)
					{
						$msg_success = __('{num} items marked as incomplete', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error marking as incomlete items');
					}
					break;
				case 'duplicate':
					if ($permission_moderator)
					{
						// moderator can pause any approved ad.
						$status_updated = Ad::statusDuplicateByIds($ad_ids);
					}
					if ($status_updated)
					{
						$msg_success = __('{num} items marked as duplicate', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error marking as duplicate items');
					}
					break;
				case 'trash':
					if ($permission_moderator)
					{
						// moderator can trash any ad
						$status_updated = Ad::statusTrashByIds($ad_ids);
					}
					if ($status_updated)
					{
						$msg_success = __('{num} items moved to trash', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error moving items to trash');
					}
					break;
				case 'mark_as_paid':
					// mark as paid 
					if ($permission_moderator)
					{
						if (Ad::markAsPaidByIds($ad_ids))
						{
							$msg_success = __('{num} ads marked as paid', array('{num}' => $count_ads));
						}
						else
						{
							$msg_error = __('Error updating ads');
						}
					}
					break;
				case 'make_featured':
					if ($permission_moderator)
					{
						// verify by admin, set 0
						if (Ad::makeFeaturedByIds($ad_ids))
						{
							$msg_success = __('{num} ads marked as featured', array('{num}' => $count_ads));
						}
						else
						{
							$msg_error = __('Error updating ads');
						}
					}

					break;
				case 'renew_item':
					// renew if moderator or renewing enabled in settings
					if ($permission_moderator || Config::option('renew_ad'))
					{
						if (Ad::statusRenewByIds($ad_ids))
						{
							$msg_success = __('{num} ads renewed', array('{num}' => $count_ads));
						}
						else
						{
							$msg_error = __('Error renewing ads');
						}
					}
					break;
				case 'disable_featured':
					if ($permission_moderator)
					{
						// verify by admin, set 0
						if (Ad::disableFeaturedByIds($ad_ids))
						{
							$msg_success = __('{num} ads marked as not featured', array('{num}' => $count_ads));
						}
						else
						{
							$msg_error = __('Error updating ads');
						}
					}

					break;
				case 'resetabuse':
					// reset abuse
					if ($permission_moderator)
					{
						if (Ad::resetAbuseByIds($ad_ids))
						{
							$msg_success = __('{num} ads reset abuse reports', array('{num}' => $count_ads));
						}
						else
						{
							$msg_error = __('Error resetting abuse reports');
						}
					}

					break;
				case 'ip_block':
					if ($permission_moderator)
					{
						$num = Ad::blockIpByIds($ad_ids);
						if ($num !== false)
						{
							$msg_success = __('{num} IPs blocked', array('{num}' => $num));
						}
						else
						{
							$msg_error = __('Error blocking IPs');
						}
					}
			}// switch bulk_actions
			// extend ads if set 

			if (strpos($bulk_actions, 'extend_') === 0)
			{
				$day = str_replace('extend_', '', $bulk_actions);
				$extend_ids = array();

				// check if days is defined in settings
				$extend_ad_days = Config::getExtendAdDays();
				if (!in_array($day, $extend_ad_days))
				{
					// day is not valid. continue extending 
					$msg_error = __('Extend ads value is not valid');
				}
				else
				{
					// set ad ids
					$extend_ids = $ad_ids;
					if (!$permission_moderator)
					{
						$extend_ids_filtered = Ad::filterExtendibleIds($extend_ids);
						if ($extend_ids_filtered && count($extend_ids_filtered) == count($extend_ids))
						{
							// some ads filtered show notification
							$this->validation()->set_info(__('Users can extend only free ads'));
						}
						$extend_ids = $extend_ids_filtered;
					}// !$permission_moderator
				}

				// Extend expiry date
				if ($extend_ids)
				{
					if (Ad::extendByIds($extend_ids, $day))
					{
						$msg_success = __('{num} ads extended expiry date', array('{num}' => $count_ads));
					}
					else
					{
						$msg_error = __('Error extending expiry date');
					}
				}// $extend_ids
			}// extend_
		}// bulk_apply

		$completed = (strlen($msg_success) + strlen($msg_error)) > 0;



		// set messages and redirect 
		if (strlen($msg_success) > 0)
		{
			Flash::set('success', $msg_success);
		}

		if (strlen($msg_error) > 0)
		{
			Flash::set('error', $msg_error);
		}

		if (strlen($returl))
		{
			redirect($returl);
		}
	}

	private function _adeditImage($ad)
	{
		// check if it is image upload 
		switch ($_REQUEST['action'])
		{
			case 'img':

				// upload image to temp
				$upload = Adpics::uploadToTmp($_REQUEST['image_token']);
				if ($upload)
				{
					echo $upload;
				}
				else
				{
					header_404();
					echo Adpics::getUploadErrors();
				}
				exit;
				break;
			case 'img_remove':
				if ($_POST['id'])
				{
					// delete image attached to ad
					Adpics::deleteImagesByIds($_POST['id'], $ad->id);
					echo 'ok';
				}
				else
				{
					//remove uploaded image to tmp
					if (Adpics::uploadToTmpRemove($_POST['image_token'], $_POST['file']))
					{
						echo 'ok';
					}
				}
				exit;
				break;
		}
	}

	/**
	 * process posted data and save Ad
	 * 
	 * @param Ad $ad
	 * @return boolean
	 */
	private function _adedit($ad)
	{
		// perform image actions if set
		$this->_adeditImage($ad);


		// if data submitted
		if (get_request_method() == 'POST')
		{
			if (Config::nounceCheck(true))
			{
				$redirect_url = Ad::urlReturn();
				$catfields = CategoryFieldRelation::getCatfields($_POST['location_id'], $_POST['category_id'], true, true);

				if ($_POST['completed'])
				{

					$user = AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, false) ? null : AuthUser::$user;
					// delete this ad
					// $ad->delete()
					if (Ad::statusCompletedByIds($ad->id, $user))
					{
						Flash::set('success', __('Item completed'));
						redirect($redirect_url);
					}
					else
					{
						// error deleting ad
						$this->validation()->set_error('Error making item completed');
					}
				}
				else
				{
					// edit record
					$rules = array();
					$fields = array();

					$rules['title'] = 'trim|strip_tags|callback_TextTransform::removeSpacesNewlines|xss_clean';
					//$rules['description'] = 'trim|required|strip_tags|callback_TextTransform::removeSpacesMaxNewlines[2]|xss_clean';
					$rules['description'] = 'trim|required|strip_tags|callback_TextTransform::removeSpacesMaxNewlines[2]|xss_clean';
					$rules['email'] = 'trim|strip_tags|xss_clean|required|valid_email';
					$rules['showemail'] = 'trim|intval';
					$rules['location_id'] = 'trim|intval|callback_Location::isPostingAvailableById';
					$rules['category_id'] = 'trim|intval|callback_Category::isPostingAvailableById';
					$rules['othercontactok'] = 'trim|intval';
					$rules['phone'] = 'trim|strip_tags'
							. '|callback_TextTransform::removeSpacesNewlines'
							. '|callback_Config::validatePhone'
							. '|xss_clean';
					if (Config::option('required_phone'))
					{
						$rules['phone'] .= '|required';
					}

					$fields['title'] = __('Title');
					$fields['description'] = __('Description');
					$fields['email'] = __('Email');
					$fields['phone'] = __('Phone');
					$fields['location_id'] = __('Location');
					$fields['category_id'] = __('Category');

					// fix showemail value, if not set then set default value
					if (!isset($_POST['showemail']))
					{
						$_POST['showemail'] = Ad::defaultContactOption();
					}

					if ($_POST['showemail'] < 1)
					{
						// make sure phone is required if hide imail set
						$rules['phone'] .= '|required';
					}


					// add custom field validation
					AdFieldRelation::defineValidationRules($catfields, $rules, $fields);

					$this->validation()->set_rules($rules);
					$this->validation()->set_fields($fields);
					$this->validation()->set_message('Config::validatePhone', __('%s format is not valid.'));
					$this->validation()->set_message('Location::isPostingAvailableById', __('%s is not valid.'));
					$this->validation()->set_message('Category::isPostingAvailableById', __('%s is not valid.'));

					$ad_fields = 'title,description,email,showemail,location_id,category_id,othercontactok,phone,cf';
					if ($this->validation()->run())
					{
						// store old email address
						$ad->old_email = $ad->email;

						// update checkbox value 
						$_POST['othercontactok'] = intval($_POST['othercontactok']);

						// populate with new data
						$data = Record::filterCols($_POST, $ad_fields);

						if (!Ad::canChangeLocationCategory($ad))
						{
							// do not change location and category because it is paid already 
							unset($data['location_id']);
							unset($data['category_id']);
						}

						if (!Category::hasValidPostingCategories())
						{
							unset($data['category_id']);
						}

						if (!Location::hasValidPostingLocations())
						{
							unset($data['location_id']);
						}


						// save old values 
						$ad->location_id_old = $ad->location_id;
						$ad->category_id_old = $ad->category_id;
						// if title, description changed send to moderation if moderate manually chosen
						// store for checking after save
						$ad->title_old = $ad->title;
						$ad->description_old = $ad->description;

						$ad->setFromData($data);
						$ad->prepareCustomFields($_POST, $catfields);

						// check if location available
						if (isset($data['location_id']))
						{
							if (!Location::isPostingAvailableById($data['location_id']))
							{
								// error
								$this->validation()->set_error(__('Please select valid location.'));
								return false;
							}
						}

						// check if location available
						if (isset($data['category_id']))
						{
							if (!Category::isPostingAvailableById($data['category_id']))
							{
								// error
								$this->validation()->set_error(__('Please select valid category.'));
								return false;
							}
						}


						if ($ad->save())
						{
							// save custom fields
							$ad->saveCustomFields($_POST, $catfields);

							// check if title and description text chanegd, uses $ad->title_old , $ad->description_old
							Ad::checkTextChange($ad);


							// delete selected images
							if ($_POST['adpic_delete'])
							{
								Adpics::deleteImagesByIds($_POST['adpic_delete'], $ad->id);
							}

							// append images 
							$uploads = Adpics::uploadToAd($ad->id, 'adpic_', $_POST['image_token']);
							$count_existing_images = Adpics::countFrom('Adpics', 'ad_id=?', array($ad->id));
							$ad_image_num = Config::option('ad_image_num');

							$msg_images = '';
							if ($uploads['num_files'])
							{
								if ($uploads['num_uploaded'] > 0)
								{
									$msg_images = __('{num} images attached to listing.', array('{num}' => $uploads['num_uploaded']));
									// new images added so send to moderation if set in settings
									// we alredy saved ad so send for moderation if defined in settings
									Ad::autoApproveChanged($ad);
								}
								else
								{
									// error on image uploads. what to do??? 
									$this->validation()->set_error(__('Error attaching images to your ad. Please try again.'));
								}
							}
							elseif ($count_existing_images < 1 && Config::option('required_image') && $ad_image_num > 0)
							{
								// no image uploaded then display error and prompt upload at least one image 
								$this->validation()->set_error(__('Please upload at least one image.'));

								return $ad;
							}

							// clear not activated ads
							Ad::clearNotActivated();


							// expire related ads because location, category, custom fields may be changed on update
							AdRelated::expire($ad->id);


							// if logged in user and ad posted to this email address then no activation required.
							if (Ad::isVerificationRequiredAgain($ad))
							{
								// send activation email
								if (MailTemplate::sendAdVerificationEmail($ad))
								{
									// activation email sent
									Flash::set('success', __('Ad posted successfully and will be published after you verify email address. Please verify email address by following instructions sent to {email} email address.', array('{email}' => $ad->email)));
									redirect($redirect_url);
								}
								else
								{
									// error sending activation email
									Flash::set('error', __('Ad posted but system failed to send verification email to {email}. Please contact site administrator to inform this issue.', array('{email}' => $ad->email)));
									redirect($redirect_url);
								}
							}
							else
							{
								// activation email sent
								Flash::set('success', __('Ad posted successfully.') . ' '
										. Ad::isEnabledMessage($ad)
										. ' <a href="' . Ad::url($ad) . '">' . __('View') . '</a>');
								redirect($redirect_url);
							}
						}
						else
						{
							// error
							$this->validation()->set_error(__('Error posting your ad.'));
							return false;
						}
					}
				}// $_POST['delete']
			}// Config::nounceCheck();
			// not passed validation 
			$ad->setFromData($_REQUEST);
			$ad->prepareCustomFields($_REQUEST, $catfields);
		}


		// nothing posted then do not change ad object
		return false;
	}

	function itemedit($id = 0)
	{
		if ($id)
		{
			$ad = Ad::findByIdFrom('Ad', $id);
		}

		if (!$ad)
		{
			Flash::set('error', __('Record not found'));
			redirect(Language::get_url('admin/'));
		}

		// check if current user has permission to edit this ad
		if (!AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, false))
		{
			// redirect to user panel with permission message
			Flash::set('error', __('Sorry you do not have permission to access this page.'));
			redirect(Language::get_url('admin/'));
		}

		// check if user can edit this ad
		if (!AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, false) && !Ad::ownerCan($ad, 'edit'))
		{
			// not moderator and owner cant edit ad 
			Flash::set('error', __('Record not found'));
			redirect(Language::get_url('admin/'));
		}


		// update ad 
		$this->_adedit($ad);


		// append everything to this ad
		Ad::appendAll($ad);


		// if admin then append user
		if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
		{
			User::appendObject($ad, 'added_by', 'User', 'id');

			// append number of ads for this user 
			User::appendAdCount($ad->User, 'total_all');
		}

		$url_return = Ad::urlReturn();
		// possible values
		$arr_menu_selected = array(
			'admin/items/'		 => __('Ads'),
			'admin/itemsHit/'	 => __('Most viewed items'),
			'admin/itemsmy/'	 => __('My items')
		);
		// default 
		$menu_selected = 'admin/itemsmy/';
		$return_title = __('My items');

		foreach ($arr_menu_selected as $k => $v)
		{
			if (strpos($url_return, $k) !== false)
			{
				$menu_selected = $k;
				$return_title = $v;
				break;
			}
		}

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array($return_title, $url_return),
			array(Ad::getTitle($ad), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/adedit', array(
			'ad'				 => $ad,
			'menu_selected'		 => $menu_selected,
			'catfields'			 => $ad->CategoryFieldRelation,
			'contact_options'	 => Ad::getContactOptions()
		));
	}

	/**
	 * Ads added by current user 
	 * 
	 * @param int $page
	 * @param string $type
	 * @param string $order
	 */
	function itemsmy($page = 1, $type = '', $order = '')
	{
		// perform actions
		$this->_ads();

		if ($page < 1)
		{
			$page = 1;
		}

		$types = array(
			'all'		 => array(
				'title'	 => __('All'),
				'url'	 => 'all/',
				'where'	 => 'added_by=? AND enabled!=?',
				'vals'	 => array(AuthUser::$user->id, Ad::STATUS_TRASH),
				'total'	 => User::countAdType(AuthUser::$user, 'total')
			),
			'listed'	 => array(
				'title'	 => __('Running'),
				'url'	 => 'listed/',
				'where'	 => 'added_by=? AND listed=?',
				'vals'	 => array(AuthUser::$user->id, 1),
				'total'	 => User::countAdType(AuthUser::$user, 'listed')
			),
			'expired'	 => array(
				'title'	 => __('Expired'),
				'url'	 => 'expired/',
				'where'	 => 'added_by=? AND expireson<?',
				'vals'	 => array(AuthUser::$user->id, Config::roundTime()),
				'total'	 => User::countAdType(AuthUser::$user, 'expired')
			),
			'pending'	 => array(
				'title'	 => Ad::statusName(Ad::STATUS_PENDING_APPROVAL),
				'url'	 => 'pending/',
				'where'	 => 'added_by=? AND enabled=?',
				'vals'	 => array(AuthUser::$user->id, Ad::STATUS_PENDING_APPROVAL),
				'total'	 => User::countAdType(AuthUser::$user, 'enabled' . Ad::STATUS_PENDING_APPROVAL)
			),
			'payment'	 => array(
				'title'	 => __('Payment required'),
				'url'	 => 'payment/',
				'where'	 => 'added_by=? AND requires_posting_payment=?',
				'vals'	 => array(AuthUser::$user->id, 1),
				'total'	 => User::countAdType(AuthUser::$user, 'requires_posting_payment')
			),
			'incomplete' => array(
				'title'	 => Ad::statusName(Ad::STATUS_INCOMPLETE),
				'url'	 => 'incomplete/',
				'where'	 => 'added_by=? AND enabled=?',
				'vals'	 => array(AuthUser::$user->id, Ad::STATUS_INCOMPLETE),
				'total'	 => User::countAdType(AuthUser::$user, 'enabled' . Ad::STATUS_INCOMPLETE)
			),
			'paused'	 => array(
				'title'	 => Ad::statusName(Ad::STATUS_PAUSED),
				'url'	 => 'paused/',
				'where'	 => 'added_by=? AND enabled=?',
				'vals'	 => array(AuthUser::$user->id, Ad::STATUS_PAUSED),
				'total'	 => User::countAdType(AuthUser::$user, 'enabled' . Ad::STATUS_PAUSED)
			),
			'completed'	 => array(
				'title'	 => Ad::statusName(Ad::STATUS_COMPLETED),
				'url'	 => 'completed/',
				'where'	 => 'added_by=? AND enabled=?',
				'vals'	 => array(AuthUser::$user->id, Ad::STATUS_COMPLETED),
				'total'	 => User::countAdType(AuthUser::$user, 'enabled' . Ad::STATUS_COMPLETED)
			),
			'duplicate'	 => array(
				'title'	 => Ad::statusName(Ad::STATUS_DUPLICATE),
				'url'	 => 'duplicate/',
				'where'	 => 'added_by=? AND enabled=?',
				'vals'	 => array(AuthUser::$user->id, Ad::STATUS_DUPLICATE),
				'total'	 => User::countAdType(AuthUser::$user, 'enabled' . Ad::STATUS_DUPLICATE)
			),
			'banned'	 => array(
				'title'	 => Ad::statusName(Ad::STATUS_BANNED),
				'url'	 => 'banned/',
				'where'	 => 'added_by=? AND enabled=?',
				'vals'	 => array(AuthUser::$user->id, Ad::STATUS_BANNED),
				'total'	 => User::countAdType(AuthUser::$user, 'enabled' . Ad::STATUS_BANNED)
			),
		);

		if (!isset($types[$type]))
		{
			$type = 'all';
		}

		$orders = array(
			'date'	 => array(
				'title'	 => __('Date'),
				'sql'	 => ' ORDER BY published_at DESC',
				'url'	 => '',
			),
			'hit'	 => array(
				'title'	 => __('Views'),
				'sql'	 => ' ORDER BY hits DESC',
				'url'	 => 'hit/',
			)
		);
		if (!isset($orders[$order]))
		{
			$order = 'date';
		}


		// count all types 
		foreach ($types as $k => $v)
		{
			if (!isset($types[$k]['total']))
			{
				$types[$k]['total'] = Ad::countByClass('Ad', $v['where'], $v['vals']);
			}
		}

		// populate paginator
		$total_pages = ceil($types[$type]['total'] / self::$num_records);
		if ($page > $total_pages)
		{
			if ($total_pages > 0)
			{
				// show first page 
				$page = 1;
			}
			else
			{
				// no pages then switch type to all
				if ($type !== 'all')
				{
					$type = 'all';
					$total_pages = ceil($types[$type]['total'] / self::$num_records);
				}
				$page = 1;
			}
		}
		$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/itemsmy/{page}/' . $types[$type]['url'] . $orders[$order]['url']));

		$st = ($page - 1) * self::$num_records;
		$ads = Ad::findAllFrom('Ad', $types[$type]['where'] . $orders[$order]['sql'] . ' LIMIT ' . $st . ',' . self::$num_records, $types[$type]['vals']);


		// append images 
		Ad::appendAdpics($ads, true);

		// apend payments made 
		Ad::appendPaymentAmounts($ads);

		// load all prices because this ads will be called in loop to check if payment available 
		PaymentPrice::appendPaymentPriceAll($ads);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('My items'), Language::get_url('admin/itemsmy/'))
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);
		$this->setMeta('title', __('My listings'));

		$this->display('admin/myads', array(
			'ads'			 => $ads,
			'type'			 => $type,
			'types'			 => $types,
			'order'			 => $order,
			'orders'		 => $orders,
			'paginator'		 => $paginator,
			'menu_selected'	 => 'admin/itemsmy/',
			'returl'		 => Language::getCurrentUrl(true)
		));
	}

	/**
	 * Show live ads by number of hits for given period
	 * 
	 * @param string $period
	 */
	function itemsHit($period = '')
	{
		// perform actions
		$this->_ads();

		$limit = 100;

		$periods = array(
			'a'	 => array(
				'title'	 => __('All'),
				'url'	 => '',
				'where'	 => 'listed=?',
				'vals'	 => array(1)
			),
			'd'	 => array(
				'title'	 => __('Day'),
				'url'	 => 'd/',
				'where'	 => 'listed=? AND added_at>?',
				'vals'	 => array(1, REQUEST_TIME - 24 * 3600)
			),
			'w'	 => array(
				'title'	 => __('Week'),
				'url'	 => 'w/',
				'where'	 => 'listed=? AND added_at>?',
				'vals'	 => array(1, REQUEST_TIME - 24 * 3600 * 7)
			),
			'm'	 => array(
				'title'	 => __('Month'),
				'url'	 => 'm/',
				'where'	 => 'listed=? AND added_at>?',
				'vals'	 => array(1, REQUEST_TIME - 24 * 3600 * 30)
			),
			'y'	 => array(
				'title'	 => __('Year'),
				'url'	 => 'y/',
				'where'	 => 'listed=? AND added_at>?',
				'vals'	 => array(1, REQUEST_TIME - 24 * 3600 * 365)
			),
		);

		if (!isset($periods[$period]))
		{
			$period = 'a';
		}

		$ads = Ad::findAllFrom('Ad', $periods[$period]['where'] . ' ORDER BY hits DESC LIMIT ' . $limit, $periods[$period]['vals']);

		// append images 
		Ad::appendAdpics($ads, true);

		// apend payments made 
		Ad::appendPaymentAmounts($ads);

		// load all prices because this ads will be called in loop to check if payment available 
		PaymentPrice::appendPaymentPriceAll($ads);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Most viewed items'), Language::get_url('admin/itemsHit/'))
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->setMeta('title', __('Most viewed items'));

		$this->display('admin/adsHit', array(
			'ads'			 => $ads,
			'period'		 => $period,
			'periods'		 => $periods,
			'menu_selected'	 => 'admin/itemsHit/',
			'returl'		 => Language::getCurrentUrl(true)
		));
	}

	function promote($id = null)
	{
		if ($id)
		{
			$ad = Ad::findByIdFrom('Ad', $id);
		}

		if (!$ad)
		{
			// ad not found 
			Flash::set('error', __('Record not found'));
			Language::get_url('admin/');
		}

		// check if current user has permission to promote this ad
		AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, true);

		// append price
		PaymentPrice::appendPaymentPrice($ad);

		// check if requires any payment 
		if (!Ad::isPaymentAvailable($ad))
		{
			if (!Config::option('enable_payment'))
			{
				$this->validation()->set_error(__('Payment processing currently disabled. If you want to complete payment, please contact site administrator and mention this message.'));
			}
			else
			{
				$this->validation()->set_error(__('No payment available for this ad'));
			}
		}
		else
		{
			// send to paypal for payment using 
			if (get_request_method() == 'POST')
			{
				if ($_POST['price_featured_requested'])
				{
					$ad->price_featured_requested = intval($_POST['price_featured_requested']);
					Payment::saveFeaturedRequest($ad);
				}

				// process to payment 
				Payment::processPayment($ad);
			}
		}


		$this->display('admin/promote', array(
			'ad'			 => $ad,
			'menu_selected'	 => 'admin/itemsmy/',
		));
	}

	private function _hasPermissionAjax($permission, $user_id = false, $redirect = false)
	{
		$result = AuthUser::hasPermission($permission, $user_id, false);
		if (!$result && $redirect)
		{
			exit(__('No permission to perform this action.'));
		}

		return $result;
	}

	function categories($parent_id = 0, $page = 1)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// FIXME: add activate controls for no javascript users
		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Categories'), Language::get_url('admin/categories/'))
		);

		if ($parent_id)
		{
			$parent_category = Category::findByIdFrom('Category', $parent_id);
			if (!$parent_category)
			{
				// no such parent category then redirect to root of categories
				Flash::set('error', __('No parent category with {num} key', array('{num}' => $parent_id)));
				redirect(Language::get_url('admin/categories/'));
			}
			// appen name
			Category::appendName($parent_category);

			$this->_appendParentCategoriesBreadcrumb($breadcrumb, $parent_category);
			$breadcrumb[] = array(Category::getName($parent_category), '');
		}

		$this->assignToLayout('breadcrumb', $breadcrumb);


		// check no parent categories 
		Category::checkNotFoundParents();


		// get locations with pagination 
		if (!$page)
		{
			$page = 1;
		}
		// show no more than 100 items per page 
		$perpage = 300;
		$total = Category::countFrom('Category', 'parent_id=?', array($parent_id));
		$st = ($page - 1) * $perpage;
		$total_pages = ceil($total / $perpage);
		// if no ads for given page then go to first page 
		if ($st > 0 && $st >= $total && $page > 1)
		{
			$page = 1;
			$st = 0;
		}
		$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/categories/' . $parent_id . '/{page}/'));
		// get categories
		$categories = Category::findAllFrom('Category', 'parent_id=? ORDER BY pos,id LIMIT ' . $st . ',' . $perpage, array($parent_id));

		// appen name
		Category::appendName($categories);

		// append custom fields
		CategoryFieldRelation::appendObject($categories, 'id', 'CategoryFieldRelation', 'category_id', '', MAIN_DB, '*', true, false, "location_id=0 AND ");

		// append ad counts
		Category::appendAdCount($categories);

		$this->display('admin/categories', array(
			'categories'		 => $categories,
			'menu_selected'		 => 'admin/categories/',
			'paginator'			 => $paginator,
			'parent_category'	 => $parent_category,
		));
	}

	function categoriesEdit($id = 0, $parent_id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($id)
		{
			$category = Category::findByIdFrom('Category', $id, 'id');
			if (!$category)
			{
				redirect(Language::get_url('admin/categories/'));
			}
			// append all descriptions
			CategoryDescription::appendObject($category, 'id', 'CategoryDescription', 'category_id', '', MAIN_DB, '*', false, 'language_id');

			$title = __('Edit category');
			$add = false;
		}
		else
		{
			$category = new Category();
			$category->locked = 0;
			$category->enabled = 1;
			$category->parent_id = $parent_id;
			$title = __('Add category');
			$add = true;
		}

		// save changes if submitted
		$this->_categoriesEdit($category);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Categories'), Language::get_url('admin/categories/'))
		);
		$this->_appendParentCategoriesBreadcrumb($breadcrumb, $category);
		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/categoriesEdit', array(
			'category'		 => $category,
			'menu_selected'	 => 'admin/categories/',
			'title'			 => $title,
			'language'		 => Language::getLanguages(),
			'add'			 => $add,
		));
	}

	private function _appendParentCategoriesBreadcrumb(& $breadcrumb, $category)
	{
		// populate parents
		Category::getParents($category);

		foreach ($category->arr_parents as $p)
		{
			$breadcrumb[] = array(Category::getName($p), Language::get_url('admin/categories/' . $p->id . '/'));
		}
	}

	function categoriesDelete($id = 0)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$category = Category::findByIdFrom('Category', $id);
		if (!$category)
		{
			Flash::set('error', __('Category not found'));
			redirect(Language::get_url('admin/categories/'));
		}

		// delete category
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if ($category->delete('id'))
			{
				Flash::set('success', __('Record deleted'));
				redirect(Language::get_url('admin/categories/' . $category->parent_id . '/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}


		// append name
		Category::appendName($category);


		// get subcategories tree
		$categories_tree = Category::getAllCategoryNamesTree();
		$subcategory_tree = Category::htmlCategoryTreeTruncated($categories_tree, $category->id);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Categories'), Language::get_url('admin/categories/'))
		);
		$this->_appendParentCategoriesBreadcrumb($breadcrumb, $category);
		$breadcrumb[] = array(__('Delete category'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/categoriesDelete', array(
			'category'			 => $category,
			'menu_selected'		 => 'admin/categories/',
			'subcategory_tree'	 => $subcategory_tree,
		));
	}

	/**
	 *
	 * @param Category $category 
	 */
	private function _categoriesEdit($category)
	{
		if (get_request_method() == 'POST')
		{
			$language = Language::getLanguages();

			foreach ($language as $lng)
			{
				$rules['category_description[' . $lng->id . '][name]'] = 'trim|strip_tags|xss_clean|callback_TextTransform::removeSpacesNewlines|required';
				$rules['category_description[' . $lng->id . '][description]'] = 'trim|xss_clean';

				$fields['category_description[' . $lng->id . '][name]'] = __('Name');
				$fields['category_description[' . $lng->id . '][description]'] = __('Description');
			}

			$rules['parent_id'] = 'trim|intval';
			$rules['locked'] = 'trim|intval';
			$rules['enabled'] = 'trim|intval';

			$fields['parent_id'] = __('Parent category');
			$fields['locked'] = __('Locked');
			$fields['enabled'] = __('Enabled');


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				$_POST['enabled'] = intval($_POST['enabled']);
				$_POST['locked'] = intval($_POST['locked']);

				$category->setFromData($_POST);
				if ($category->save())
				{
					Flash::set('success', __('Category saved.'));
					redirect(Language::get_url('admin/categories/' . $category->parent_id . '/'));
				}
				else
				{
					$this->validation()->set_error(__('Error saving category'));
				}
			}

			// not saved then just update object with passed values 
			$category->setFromData($_POST);
		}
	}

	function categoriesAction()
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$id = intval($_POST['id']);
		$action = $_POST['action'];
		if ($id)
		{
			$category = Category::findByIdFrom('Category', $id);
		}
		if (!$category)
		{
			exit(__('Category not found'));
		}


		switch ($action)
		{
			case 'enabled':
				return $this->_categoriesEnabled($category);
				break;
			case 'locked':
				return $this->_categoriesLocked($category);
				break;
			default:
				exit(__('No action specified.'));
		}
	}

	/**
	 *
	 * @param Category $category 
	 */
	function _categoriesEnabled($category)
	{
		// 
		if ($category->enabled)
		{
			$category->enabled = 0;
			$category->save('id');
			//exit('{"class":"white","text":"' . __('Disabled') . '"}');
		}
		else
		{
			$category->enabled = 1;
			$category->save('id');
			//exit('{"class":"green","text":"' . __('Enabled') . '"}');
		}

		exit(intval($category->enabled) . '');
	}

	/**
	 *
	 * @param Category $category 
	 */
	function _categoriesLocked($category)
	{
		// 
		if ($category->locked)
		{
			$category->locked = 0;
			$category->save('id');
			//exit('{"class":"green","text":"' . __('not locked') . '"}');
		}
		else
		{
			$category->locked = 1;
			$category->save('id');
			//exit('{"class":"white","text":"' . __('Locked') . '"}');
		}
		exit(intval($category->locked) . '');
	}

	function categoriesOrder($id = 0, $dir = 'up')
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$parent_id = Category::changePosition($id, $dir);

		if ($parent_id)
		{
			redirect(Language::get_url('admin/categories/' . $parent_id . '/'));
		}

		redirect(Language::get_url('admin/categories/'));
	}

	function categoriesSlug()
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$rules['name'] = 'trim|strip_tags|xss_clean';
		$rules['slug'] = 'trim|strip_tags|xss_clean';
		$rules['id'] = 'trim|intval';

		$this->validation()->set_rules($rules);

		if ($this->validation()->run())
		{
			$slug = Permalink::generateSlug($_POST['slug'], $_POST['name'], $_POST['id'], Permalink::ITEM_TYPE_CATEGORY);
			exit('{"slug":"' . View::escape($slug) . '"}');
		}
		else
		{
			exit;
		}
	}

	/**
	 * Show locations for given parent_id
	 * 
	 * @param int $parent_id
	 * @param int $page
	 */
	function locations($parent_id = 0, $page = 1)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// FIXME: add activate controls for no javascript users
		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Locations'), Language::get_url('admin/locations/'))
		);

		if ($parent_id)
		{
			$parent_location = Location::findByIdFrom('Location', $parent_id);
			if (!$parent_location)
			{
				// no such parent location then redirect to root of locations
				Flash::set('error', __('No parent location with {num} key', array('{num}' => $parent_id)));
				redirect(Language::get_url('admin/locations/'));
			}

			// appen name
			Location::appendName($parent_location);

			$this->_appendParentLocationsBreadcrumb($breadcrumb, $parent_location);
			$breadcrumb[] = array(Location::getName($parent_location), '');
		}

		$this->assignToLayout('breadcrumb', $breadcrumb);


		// check no parent locations 
		Location::checkNotFoundParents();



		// get locations with pagination 
		if (!$page)
		{
			$page = 1;
		}
		// show no more than 100 items per page 
		$perpage = 300;
		$total = Location::countFrom('Location', 'parent_id=?', array($parent_id));
		$st = ($page - 1) * $perpage;
		$total_pages = ceil($total / $perpage);
		// if no ads for given page then go to first page 
		if ($st > 0 && $st >= $total && $page > 1)
		{
			$page = 1;
			$st = 0;
		}
		$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/locations/' . $parent_id . '/{page}/'));

		$locations = Location::findAllFrom('Location', 'parent_id=? ORDER BY pos,id LIMIT ' . $st . ',' . $perpage, array($parent_id));

		// append name
		Location::appendName($locations);

		// append ad counts
		Location::appendAdCount($locations);

		$this->display('admin/locations', array(
			'locations'			 => $locations,
			'parent_location'	 => $parent_location,
			'paginator'			 => $paginator,
			'menu_selected'		 => 'admin/locations/',
		));
	}

	function locationsEdit($id = 0, $parent_id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($id)
		{
			$location = Location::findByIdFrom('Location', $id, 'id');
			if (!$location)
			{
				redirect(Language::get_url('admin/locations/'));
			}
			// append all descriptions
			LocationDescription::appendObject($location, 'id', 'LocationDescription', 'location_id', '', MAIN_DB, '*', false, 'language_id');

			$title = __('Edit location');
			$add = false;
		}
		else
		{
			$location = new Location();
			$location->enabled = 1;
			$location->parent_id = $parent_id;
			$title = __('Add location');
			$add = true;
		}


		// save changes if submitted
		$this->_locationsEdit($location);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Locations'), Language::get_url('admin/locations/'))
		);
		$this->_appendParentLocationsBreadcrumb($breadcrumb, $location);
		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/locationsEdit', array(
			'location'		 => $location,
			'title'			 => $title,
			'language'		 => Language::getLanguages(),
			'add'			 => $add,
			'menu_selected'	 => 'admin/locations/',
		));
	}

	private function _appendParentLocationsBreadcrumb(& $breadcrumb, $location)
	{
		// populate parents
		Location::getParents($location);
		foreach ($location->arr_parents as $p)
		{
			$breadcrumb[] = array(Location::getName($p), Language::get_url('admin/locations/' . $p->id . '/'));
		}
	}

	function locationsDelete($id = 0)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$location = Location::findByIdFrom('Location', $id);
		if (!$location)
		{
			Flash::set('error', __('Location not found'));
			redirect(Language::get_url('admin/locations/'));
		}

		// delete location
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if ($location->delete('id'))
			{
				Flash::set('success', __('Record deleted'));
				redirect(Language::get_url('admin/locations/' . $location->parent_id . '/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}


		// append name
		Location::appendName($location);

		// get sub locations tree
		$locations_tree = Location::getAllLocationNamesTree();
		$sublocation_tree = Location::htmlLocationTreeTruncated($locations_tree, $location->id);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Locations'), Language::get_url('admin/locations/'))
		);
		$this->_appendParentLocationsBreadcrumb($breadcrumb, $location);
		$breadcrumb[] = array(__('Delete location'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/locationsDelete', array(
			'location'			 => $location,
			'sublocation_tree'	 => $sublocation_tree,
			'menu_selected'		 => 'admin/locations/',
		));
	}

	private function _locationsEdit($location)
	{
		if (get_request_method() == 'POST')
		{

			$language = Language::getLanguages();

			foreach ($language as $lng)
			{
				$rules['location_description[' . $lng->id . '][name]'] = 'trim|strip_tags|xss_clean|callback_TextTransform::removeSpacesNewlines|required';
				$rules['location_description[' . $lng->id . '][description]'] = 'trim|xss_clean';

				$fields['location_description[' . $lng->id . '][name]'] = __('Name');
				$fields['location_description[' . $lng->id . '][description]'] = __('Description');
			}

			$rules['parent_id'] = 'trim|intval';
			$rules['locked'] = 'trim|intval';
			$rules['enabled'] = 'trim|intval';


			$fields['parent_id'] = __('Parent location');
			$fields['locked'] = __('Locked');
			$fields['enabled'] = __('Enabled');


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				$_POST['enabled'] = intval($_POST['enabled']);
				$_POST['locked'] = intval($_POST['locked']);


				$location->setFromData($_POST);
				if ($location->save())
				{
					Flash::set('success', __('Location saved.'));
					redirect(Language::get_url('admin/locations/' . $location->parent_id . '/'));
				}
				else
				{
					$this->validation()->set_error(__('Error saving location'));
				}
			}

			// not saved then just update object with passed values 
			$location->setFromData($_POST);
		}
	}

	function locationsAction()
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$id = intval($_POST['id']);
		$action = $_POST['action'];
		if ($id)
		{
			$location = Location::findByIdFrom('Location', $id);
		}
		if (!$location)
		{
			exit(__('Location not found'));
		}


		switch ($action)
		{
			case 'enabled':
				return $this->_locationsEnabled($location);
				break;
			default:
				exit(__('No action specified.'));
		}
	}

	function _locationsEnabled($location)
	{
		// 
		if ($location->enabled)
		{
			$location->enabled = 0;
			$location->save('id');
			//exit('{"class":"white","text":"' . __('Disabled') . '"}');
		}
		else
		{
			$location->enabled = 1;
			$location->save('id');
			//exit('{"class":"green","text":"' . __('Enabled') . '"}');
		}
		exit(intval($location->enabled) . '');
	}

	function locationsOrder($id = 0, $dir = 'up')
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$parent_id = Location::changePosition($id, $dir);

		if ($parent_id)
		{
			redirect(Language::get_url('admin/locations/' . $parent_id . '/'));
		}

		redirect(Language::get_url('admin/locations/'));
	}

	function locationsSlug()
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$rules['name'] = 'trim|strip_tags|xss_clean';
		$rules['slug'] = 'trim|strip_tags|xss_clean';
		$rules['id'] = 'trim|intval';

		$this->validation()->set_rules($rules);

		if ($this->validation()->run())
		{
			$slug = Permalink::generateSlug($_POST['slug'], $_POST['name'], $_POST['id'], Permalink::ITEM_TYPE_LOCATION);
			exit('{"slug":"' . View::escape($slug) . '"}');
		}
		else
		{
			exit;
		}
	}

	function users()
	{
		// check user permission. only admin and moderator can view all users
		AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, true);

		$args = func_get_args();

		$mode = $args[0];
		$page = $args[1];

		$vals = array();
		$menu_selected = 'admin/users/';
		switch ($mode)
		{
			case 'add':
			case 'edit':
			case 'delete':
			case 'verify':
				return $this->{'users' . ucfirst($mode)}($args[1]);
				break;
			case 'notverified':
				// page number
				$paginator_pattern = 'admin/users/notverified/{page}/';
				// list only not verified users
				$where = "activation <> '0'";
				$title = __('Pending verification');
				$menu_selected = 'admin/users/notverified/';
				break;
			case 'notenabled':
				// page number
				$paginator_pattern = 'admin/users/notenabled/{page}/';
				// list only not verified users
				$where = "enabled='0'";
				$title = __('Pending approval');
				$menu_selected = 'admin/users/notenabled/';
				break;
			case 'upgradetodealer':
				// page number
				$paginator_pattern = 'admin/users/upgradetodealer/{page}/';
				// list only not verified users
				$where = "pending_level='" . User::PERMISSION_DEALER . "'";
				$title = __('Pending upgrade to dealer');
				$menu_selected = 'admin/users/upgradetodealer/';
				break;
			case User::PERMISSION_ADMIN:
			case User::PERMISSION_MODERATOR:
			case User::PERMISSION_USER:
			case User::PERMISSION_DEALER:
				$paginator_pattern = 'admin/users/' . $mode . '/{page}/';
				// list only active users in given level
				$where = "activation='0' AND enabled='1' AND level=?";
				$vals[] = $mode;
				// count by level		
				$user_count = User::countByLevel();
				break;
			case 'search':
				$search = trim($_GET['search']);
				if (!strlen($search))
				{
					redirect(Language::get_url('admin/users/'));
				}
				$paginator_pattern = 'admin/users/' . $mode . '/{page}/?search=' . urlencode($search);
				// list only active users in given level
				$where = "email LIKE ?";
				$vals[] = '%' . $search . '%';
				// count by level		
				$user_count = User::countByLevel();
				break;
			case 'all':
			default:
				$mode = 'all';
				$paginator_pattern = 'admin/users/all/{page}/';
				// list all activated users
				$where = "activation='0' AND enabled='1'";
				// count by level		
				$user_count = User::countByLevel();
				break;
		}


		// set page number to 1 if not set
		if (!$page)
		{
			$page = 1;
		}

		// get users
		$st = ($page - 1) * self::$num_records;
		$users = User::findAllFrom('User', $where . ' ORDER BY username ASC LIMIT ' . $st . ',' . self::$num_records, $vals);

		// append ad count
		User::appendAdCount($users, 'total_all');

		// populate paginator
		$total_users = User::countFrom('User', $where, $vals);
		$total_pages = ceil($total_users / self::$num_records);
		$paginator = Paginator::render($page, $total_pages, Language::get_url($paginator_pattern));


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Users'), Language::get_url('admin/users/'))
		);
		if (strlen($title))
		{
			$breadcrumb[] = array($title, '');
		}
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/users', array(
			'users'			 => $users,
			'total_users'	 => $total_users,
			'menu_selected'	 => $menu_selected,
			'paginator'		 => $paginator,
			'title'			 => $title,
			'user_count'	 => $user_count,
			'mode'			 => $mode
		));
	}

	function usersVerify()
	{
		$this->_hasPermissionAjax(User::PERMISSION_MODERATOR, false, true);

		$id = $_POST['id'];
		$action = $_POST['action'];


		if ($id)
		{
			$user = User::findByIdFrom('User', $id, 'id');
		}
		if (!$user)
		{
			exit(__('User not found'));
		}

		switch ($action)
		{
			case 'approve':
				$user->enabled = 1;
				// send message that account is approved
				$_subject = __('Account approved');
				$_message = __('Your account on {sitename} approved. Please login using following link. {url}', array(
					'{sitename}' => DOMAIN,
					'{url}'		 => Language::get_url('login/')
				));
				break;
			case 'activate':
				$user->activation = 0;
				break;
			case 'upgrade':
				$user->level = User::PERMISSION_DEALER;
				$user->pending_level = 0;
				// send message that account is upgraded to dealer
				$_subject = __('Account upgraded');
				$_message = __('Your account on {sitename} upgraded to dealer account. {url}', array(
					'{sitename}' => DOMAIN,
					'{url}'		 => Language::get_url()
				));
				break;
			case 'upgrade_deny':
				$user->pending_level = 0;
				$_subject = __('Account upgrade denied');
				$_message = __('Your account on {sitename} is denied for upgraded to dealer account. {url}', array(
					'{sitename}' => DOMAIN,
					'{url}'		 => Language::get_url()
				));
				break;
		}

		$user->save('id');

		if ($_subject)
		{
			MailTemplate::sendGeneralEmail($_subject, $_message, $user->email);
		}

		exit('ok');
	}

	function usersRemoveLogo()
	{
		$id = $_POST['id'];

		if ($id)
		{
			$user = User::findByIdFrom('User', $id, 'id');
		}
		if (!$user)
		{
			exit(__('User not found'));
		}

		$this->_hasPermissionAjax(User::PERMISSION_USER, $user->id, true);

		$user->deleteLogo(true);
		exit('ok');
	}

	function usersDelete()
	{
		$this->_hasPermissionAjax(User::PERMISSION_MODERATOR, false, true);
		// moderator cannot delete admins or other moderators

		if (DEMO)
		{
			// cannot delete users in demo 
			exit(__('Some actions restricted in demo mode.'));
		}

		$id = $_POST['id'];
		if ($id)
		{
			$user = User::findByIdFrom('User', $id, 'id');
		}
		if (!$user)
		{
			exit(__('User not found'));
		}


		// moderator cannot edit admin or moderator
		if (!User::canEditModerator($user->level))
		{
			exit(__('You do not have permission to edit/delete admin or moderator'));
		}


		// admin deleting 
		// connot delete last admin
		// cannot delete self admin 
		if ($user->level == User::PERMISSION_ADMIN && User::isActivated($user))
		{
			// check if there are other admins left if not then cannot delete last admin, al
			if ($user->id == AuthUser::$user->id)
			{
				exit(__('Admin cannot delete own account.'));
			}
			else
			{

				$admins_count = User::countByLevel(User::PERMISSION_ADMIN);
				if ($admins_count->level_cnt < 2)
				{
					// only one admin then cannot delete last admin 
					exit(__('Admin cannot delete last admin in system.'));
				}
			}
		}

		// delete user 
		$user->delete('id');

		exit('ok');
	}

	function usersEdit($id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, true);

		// save changes if submitted
		// return submitted user object
		$user = $this->_usersEdit();
		$id = intval($id);
		if ($id)
		{
			// if no user data submitted then get user from db
			if (!$user)
			{
				$user = User::findByIdFrom('User', $id, 'id');
				if (!$user)
				{
					Flash::set('error', __('User with id {num} not found.', array('{num}' => $id)));
					redirect(Language::get_url('admin/users/'));
				}

				// moderator cannot edit admin or moderator
				if (!User::canEditModerator($user->level))
				{
					Flash::set('error', __('You do not have permission to edit/delete admin or moderator'));
					redirect(Language::get_url('admin/users/'));
				}
			}
			$title = __('Edit user');
			$add = false;
			$menu_selected = 'admin/users/';
		}
		else
		{
			$title = __('Add user');
			$add = true;
			$menu_selected = 'admin/users/edit/';
		}

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Users'), Language::get_url('admin/users/'))
		);
		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/usersEdit', array(
			'user'			 => $user,
			'menu_selected'	 => $menu_selected,
			'title'			 => $title,
			'add'			 => $add,
			'levels'		 => User::levels(),
		));
	}

	function usersSlug()
	{
		$this->_hasPermissionAjax(User::PERMISSION_USER, $_POST['id'], true);

		$rules['name'] = 'trim|strip_tags|xss_clean';
		$rules['slug'] = 'trim|strip_tags|xss_clean';
		$rules['id'] = 'trim|intval';

		$this->validation()->set_rules($rules);

		if ($this->validation()->run())
		{
			$slug = Permalink::generateSlug($_POST['slug'], $_POST['name'], $_POST['id'], Permalink::ITEM_TYPE_USER);
			exit('{"slug":"' . View::escape($slug) . '"}');
		}
		else
		{
			exit;
		}
	}

	function upgradetodealer()
	{
		// upgrade user account to dealer account 
		$user = User::findByIdFrom('User', AuthUser::$user->id);

		if (!$user)
		{
			redirect(Language::get_url('admin/'));
		}

		if (User::canUpgradeToDealer($user))
		{
			if (Config::option('account_dealer_move_from_user_auto_approve'))
			{
				// just chnage user permission to dealer permission 
				$user->level = User::PERMISSION_DEALER;
				$user->$user->pending_level = 0;
				$user->save();

				Flash::set('success', __('Your account is upgraded to dealer account.'));
			}
			else
			{
				// need manual approval 
				$user->pending_level = User::PERMISSION_DEALER;
				$user->save();

				// dealer is not approved, send mail to admin 
				MailTemplate::sendPendingApproval();

				Flash::set('success', __('Your account upgrade to dealer is marked for approval by administrator.'));
			}
		}

		redirect(Language::get_url('admin/editAccount/'));
	}

	function editAccount()
	{
		$user = User::findByIdFrom('User', AuthUser::$user->id);

		if (!$user)
		{
			redirect(Language::get_url('admin/'));
		}

		// save changes if submitted
		// return submitted user object
		$this->_editAccount($user);


		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Edit account'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/editAccount', array(
			'user'			 => $user,
			'title'			 => __('Edit account'),
			'menu_selected'	 => 'admin/editAccount/',
		));
	}

	/**
	 * Add new user or update existing user
	 * @return User object|redirect
	 */
	private function _editAccount($user)
	{
		if (get_request_method() == 'POST')
		{
			if ($user->level == User::PERMISSION_DEALER)
			{
				$rules['web'] = 'trim|prep_url|xss_clean';
				$rules['info'] = 'trim|max_length[1000]|xss_clean';
				$str_fields = 'web,info,';
			}

			$rules['name'] = 'trim|strip_tags|xss_clean|callback_TextTransform::removeSpacesNewlines|required';
			$rules['username'] = 'trim|strip_tags|xss_clean|callback_TextTransform::removeSpacesNewlines|required';


			if (strlen($_POST['password']))
			{
				$rules['password'] = 'min_length[4]|max_length[32]|matches[password_repeat]';
			}

			$fields['name'] = __('Name');
			$fields['username'] = __('Permalink');
			$fields['web'] = __('Website');
			$fields['info'] = __('Info');
			$fields['password'] = __('Password');
			$fields['password_repeat'] = __('Repeat password');

			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				//print_r($post);

				$str_fields .= 'name,username';

				$user->setFromData(User::filterCols($_POST, $str_fields));

				if (strlen($_POST['password']))
				{
					$user->password = md5($_POST['password']);
				}

				// if dealer then upload logo 
				if ($user->level == User::PERMISSION_DEALER)
				{
					// upload logo if set 
					if (User::uploadLogo($user, 'logo') === false)
					{
						// if error on uploading logo
						return $user;
					}
				}

				if ($user->save('id'))
				{
					// update info 
					AuthUser::setInfos($user);

					Flash::set('success', __('User saved.'));
					redirect(Language::get_url('admin/editAccount/'));
				}
				else
				{
					$this->validation()->set_error(__('Error saving user.'));
				}
			}
		}
	}

	function settings()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (Captcha::isOldRecaptcha())
		{
			$this->validation()->set_error(__('Version 1.0 of the reCAPTCHA API is no longer supported, please <a href="{url}">upgrade to Version 2.0.</a>', array('{url}' => Language::get_url('admin/settingsSpam/#grp_captcha'))));
		}

		if (get_request_method() == 'POST')
		{
			// reset PWA version on each save 
			$_POST['pwa_time'] = REQUEST_TIME;

			$arr_chekboxes = array(
				'display_classibase_news'
			);

			// save settings if posted 
			$this->_settings(Language::get_url('admin/settings/'), $arr_chekboxes);
		}

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settings', array(
			'menu_selected' => 'admin/settings/',
		));
	}

	function testMailServer()
	{

		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$test_settings = array();

		parse_str($_POST['data'], $test_settings);

		$from = MailTemplate::emailFrom();
		$to = AuthUser::$user->email;
		$subject = DOMAIN . ' email server test';
		$message = DOMAIN . ' email server test. If you receive this email then your mail server settings working properly.';

		if (MailTemplate::sendMail($from, $to, $subject, $message, $test_settings))
		{
			exit(__('Email sent to {name}, mail server details correct.', array('{name}' => AuthUser::$user->email)));
		}
		else
		{
			//print_r($email);
			echo __('Error sending email. Check your mail server settings and try again.') . ' ' . strip_tags(Validation::getInstance()->messages_dump());
			exit;
		}
	}

	private function _settings_old()
	{
		if (get_request_method() == 'POST')
		{
			$settings = Config::findAllFrom('Config', 'is_editable!=0 ORDER BY grp,name', array());

			// by default every field is 0;
			foreach ($settings as $s)
			{
				Config::optionSet($s->name, $_POST[$s->name]);
			}

			/* foreach($_POST as $k => $v)
			  {
			  $data['val'] = $v;
			  Config::update('Config', $data, 'name=?', array($k));
			  } */

			Flash::set('success', __('Settings updated.'));
			redirect(Language::get_url('admin/settings/'));
		}
	}

	private function _settings($redir_url = null, $arr_chekboxes = array())
	{

		if (get_request_method() == 'POST')
		{

			// init checkbox values 
			foreach ($arr_chekboxes as $v)
			{
				if (!isset($_POST[$v]))
				{
					$_POST[$v] = 0;
				}
			}

			// remove common button
			unset($_POST['submit']);

			// save posted values
			foreach ($_POST as $k => $v)
			{
				Config::optionSet($k, $v);
			}

			// upload favicon
			Config::faviconUpload('upload_favicon');


			Flash::set('success', __('Settings updated.'));
			if (is_null($redir_url))
			{
				$redir_url = Language::get_url('admin/settings/');
			}
			redirect($redir_url);
		}
	}

	function settingsAds()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$arr_chekboxes = array(
			'ads_separate',
			'location_cookie',
			'url_to_link',
			'disable_ad_counting',
			'hide_phone_title',
			'showemail_0',
			'showemail_1',
			'showemail_2',
			'required_image',
			'required_phone',
			'map_enabled',
			'map_append_to_description',
			'hide_othercontactok',
			'hide_agree',
			'renew_ad',
			'notify_admin_pending_approval',
			'view_contact_registered_only'
		);

		// add default option to show email if not selected
		if (isset($_POST['default_contact_option']) && in_array($_POST['default_contact_option'], $arr_chekboxes))
		{
			$_POST[$_POST['default_contact_option']] = 1;
		}

		// convert number feilds to integer before saving
		$arr_number = array(
			'ad_image_num',
			'ad_image_width',
			'ad_image_height',
			'ad_thumbnail_width',
			'ad_thumbnail_height',
			'ad_image_max_width',
			'ad_image_max_height',
			'ad_image_max_filesize',
			'renew_ad_days'
		);
		foreach ($arr_number as $name)
		{
			if (isset($_POST[$name]))
			{
				$_POST[$name] = intval($_POST[$name]);
			}
		}

		// save settings if posted 
		$this->_settings(Language::get_url('admin/settingsAds/'), $arr_chekboxes);

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Ads'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsAds', array(
			'menu_selected' => 'admin/settingsAds/',
		));
	}

	function settingsCurrency()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (get_request_method() == 'POST')
		{
			// update json_version time in case currency sybmol chages
			Config::optionSet('json_version', REQUEST_TIME);
		}

		// save settings if posted 
		$this->_settings(Language::get_url('admin/settingsCurrency/'));

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Currency'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsCurrency', array(
			'menu_selected' => 'admin/settingsCurrency/',
		));
	}

	function settingsAccount()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$arr_chekboxes = array(
			'account_dealer_can_register',
			'account_dealer_auto_approve_registration',
			'account_dealer_move_from_user',
			'account_dealer_move_from_user_auto_approve',
			'account_dealer_display_info_ad_page',
			'account_user_can_register',
			'account_user_auto_approve_registration',
			'ad_posting_without_registration'
		);

		// save settings if posted 
		$this->_settings(Language::get_url('admin/settingsAccount/'), $arr_chekboxes);

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Account'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsAccount', array(
			'menu_selected' => 'admin/settingsAccount/',
		));
	}

	function settingsMail()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// save settings if posted 
		$this->_settings(Language::get_url('admin/settingsMail/'));

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Mail server'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsMail', array(
			'menu_selected' => 'admin/settingsMail/',
		));
	}

	function settingsPayment()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);


		if (DEMO)
		{
			Validation::getInstance()->set_info(__('Payment cannot be enabled in demo mode'));

			// cannot enable payment in demo mode
			if ($_POST['enable_payment'] > 0)
			{
				$_POST['enable_payment'] = 0;
			}
		}

		// fix featured days
		if (isset($_POST['featured_days']))
		{
			if ($_POST['featured_days'] < 1)
			{
				$_POST['featured_days'] = 7;
			}
		}


		$arr_chekboxes = array(
			'enable_payment',
			'paypal_sandbox'
		);

		// save settings if posted 
		$this->_settings(Language::get_url('admin/settingsPayment/'), $arr_chekboxes);

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Payment'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsPayment', array(
			'menu_selected'	 => 'admin/settingsPayment/',
			'currencies'	 => Payment::$currencies
		));
	}

	function settingsSpam()
	{
		AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, true);

		// alert if old recaptcha used to switch to v2
		if (Captcha::isOldRecaptcha())
		{
			$this->validation()->set_error(__('Version 1.0 of the reCAPTCHA API is no longer supported, please <a href="{url}">upgrade to Version 2.0.</a>', array('{url}' => Language::get_url('admin/settingsSpam/#grp_captcha'))));
		}

		// alert if selected recaptcha is not availanble 
		if (!Captcha::isAvailable(Config::option('use_captcha')))
		{
			// show what functions are not available 
			if (!function_exists('json_decode'))
			{
				$arr_not_avail_func[] = '"json_decode"';
			}
			if (!function_exists('curl_exec'))
			{
				$arr_not_avail_func[] = '"curl_exec"';
			}

			$this->validation()->set_error(__('Selected captcha "{name}" is not available on your server, it requires {str}. Switched to Simple captcha.', array(
				'{name}' => View::escape(Config::option('use_captcha')),
				'{str}'	 => implode(', ', $arr_not_avail_func)
			)));
		}



		if (get_request_method() == 'POST')
		{
			// intval number fields
			$arr_intval = array(
				'ipblock_contact_limit_period',
				'ipblock_contact_limit_count',
				'ipblock_contact_ban_period',
				'ipblock_login_ban_period',
				'ipblock_login_attempt_count',
				'ipblock_login_attempt_period'
			);

			foreach ($arr_intval as $val)
			{
				$_POST[$val] = intval($_POST[$val]);
			}

			$arr_chekboxes = array(
				'logged_user_disable_captcha',
				'recaptcha_ajax'
			);

			// save settings if posted 
			$this->_settings(Language::get_url('admin/settingsSpam/'), $arr_chekboxes);
		}


		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Spam filter'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsSpam', array(
			'menu_selected' => 'admin/settingsSpam/',
		));
	}

	/**
	 * called after saving custom header and footer to check if static files  are writable in cache folder
	 */
	function settingsHeaderFooterAfter()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// add js_css timestamp for updating urls and contents
		Config::optionSet('custom_css_js_time', REQUEST_TIME);

		// try generating static files. if error saving then show it here 
		$custom_css_saved = Config::getCustomCssJsUrl('css');
		if ($custom_css_saved === false)
		{
			// false: error saving css file (null:no content to save)
			$this->validation()->set_error(__('Error saving custom {type} file to {name} folder', array(
				'{type}' => 'css',
				'{name}' => 'cache'
			)));
		}

		$custom_js_saved = Config::getCustomCssJsUrl('js');
		if ($custom_js_saved === false)
		{
			// false: error saving css file (null:no content to save)
			$this->validation()->set_error(__('Error saving custom {type} file to {name} folder', array(
				'{type}' => 'javascript',
				'{name}' => 'cache'
			)));
		}

		return $this->settingsHeaderFooter();
	}

	function settingsHeaderFooter()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (get_request_method() == 'POST')
		{
			$arr_chekboxes = array(
				'powered_by_hide_front'
			);
			// save settings if posted 
			$this->_settings(Language::get_url('admin/settingsHeaderFooterAfter/'), $arr_chekboxes);
		}

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Header / footer'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsHeaderFooter', array(
			'menu_selected' => 'admin/settingsHeaderFooter/',
		));
	}

	function settingsPWA()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (get_request_method() == 'POST')
		{
			// reset PWA version on each save 
			$_POST['pwa_time'] = REQUEST_TIME;

			// save settings if posted 
			$this->_settings(Language::get_url('admin/settingsPWA/'), $arr_chekboxes);
		}

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('PWA'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/settingsPWA', array(
			'menu_selected' => 'admin/settingsPWA/',
		));
	}

	/**
	 * Add new user or update existing user
	 * @return User object|redirect
	 */
	private function _usersEdit()
	{
		if (get_request_method() == 'POST')
		{
			if (Config::nounceCheck(true))
			{

				$id = intval($_POST['id']);
				$level = $_POST['level'] = intval($_POST['level']);
				$_POST['enabled'] = intval($_POST['enabled']);


				// moderator cannot edit admin or moderator
				if (!User::canEditModerator($level))
				{
					$this->validation()->set_error(__('You do not have permission to edit/delete admin or moderator'));
					return new User($_POST);
				}

				// check if deleting user 
				if (isset($_POST['delete']))
				{
					// delete user and make all ads not related
					$user = User::findByIdFrom('User', $id);
					// prevent deleting self moderators/admins
					if ($user->id == AuthUser::$user->id)
					{
						// error deleting self 
						$this->validation()->set_error('Error deleting record');
					}
					else
					{
						if ($user->delete())
						{
							Flash::set('success', __('Record deleted'));
							redirect(Ad::urlReturn('admin/users/'));
						}
						else
						{
							// error deleting user
							$this->validation()->set_error('Error deleting record');
						}
					}
				}
				elseif (isset($_POST['delete_with_ads']))
				{
					// delete user and delete all related ads
					$user = User::findByIdFrom('User', $id);

					// delete user
					if ($user->delete())
					{
						Flash::set('success', __('Record deleted'));
						redirect(Ad::urlReturn('admin/users/'));
					}

					// error deleting user
					$this->validation()->set_error('Error deleting record');
				}
				else
				{
					// edit record

					if ($level == User::PERMISSION_DEALER)
					{
						$rules['web'] = 'trim|prep_url|xss_clean';
						$rules['info'] = 'trim|max_length[1000]|xss_clean';
						$str_fields = 'web,info,';
					}

					$rules['name'] = 'trim|strip_tags|xss_clean|callback_TextTransform::removeSpacesNewlines|required';
					$rules['email'] = 'trim|required|xss_clean|valid_email|callback__validate_user_email[' . $id . ']';
					$rules['web'] = 'trim|strip_tags|xss_clean';


					if (strlen($_POST['password']))
					{
						$rules['password'] = 'min_length[4]|max_length[32]|matches[password_repeat]';
					}
					elseif (!$id)
					{
						// adding new user ask for password
						$rules['password'] = 'required|min_length[4]|max_length[32]|matches[password_repeat]';
					}

					$fields['name'] = __('Name');
					$fields['email'] = __('Email');
					$fields['web'] = __('Website');
					$fields['info'] = __('Info');
					$fields['password'] = __('Password');
					$fields['password_repeat'] = __('Repeat password');

					$user_class = new User();
					$this->validation()->set_controller($user_class);
					$this->validation()->set_rules($rules);
					$this->validation()->set_fields($fields);

					if ($this->validation()->run())
					{
						//print_r($post);

						$str_fields .= 'id,name,username,email,web,level,enabled';
						$user = new User(User::filterCols($_POST, $str_fields));

						if (strlen($_POST['password']))
						{
							$user->password = md5($_POST['password']);
						}

						// if dealer then upload logo 
						if ($user->level == User::PERMISSION_DEALER)
						{
							// upload logo if set 
							if (User::uploadLogo($user, 'logo') === false)
							{
								// if error on uploading logo
								return $user;
							}
						}

						if ($user->id)
						{
							// send activation mail after saving
							$send_activation_after_saving = false;
						}
						else
						{
							$send_activation_after_saving = true;
						}

						if ($user->save('id'))
						{
							// update user if self 
							if ($user->id == AuthUser::$user->id)
							{
								AuthUser::setInfos($user);
							}

							if ($send_activation_after_saving)
							{
								Flash::set('success', __('New user added, but not verified. <a href="{url}">View users pending verification.</a>', array(
									'{url}' => Language::get_url('admin/users/notverified/')
								)));

								if (MailTemplate::sendUserVerificationEmail($user))
								{
									Flash::set('success', __('User verification email sent to {email}.', array('{email}' => View::escape($user->email))));
								}
								else
								{
									Flash::set('error', __('Failed to send user verification email to {email}. <a href="{url}">Resend verification email.</a>', array(
										'{email}'	 => View::escape($user->email),
										'{url}'		 => User::urlResendVerification($user)
									)));
								}
							}
							else
							{
								Flash::set('success', __('User saved.'));
							}

							redirect(Language::get_url('admin/users/'));
						}
						else
						{
							$this->validation()->set_error(__('Error saving user.'));
						}
					}// run 
				}// edit record
			}// Config::nounceCheck

			return new User($_POST);
		}
	}

	function itemfield()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Custom fields'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);

		//$args = func_get_args();
		// get categories
		$adfields = AdField::findAllFrom('AdField', '1=1 ORDER BY type');

		// appen name
		AdField::appendNameValue($adfields);

		$this->display('admin/adfield', array(
			'adfields'		 => $adfields,
			'menu_selected'	 => 'admin/itemfield/',
		));
	}

	function itemfieldEdit($id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($id)
		{
			$adfield = AdField::findByIdFrom('AdField', $id);
			if (!$adfield)
			{
				redirect(Language::get_url('admin/itemfield/'));
			}

			// append all descriptions
			AdFieldDescription::appendObject($adfield, 'id', 'AdFieldDescription', 'af_id', '', MAIN_DB, '*', false, 'language_id');

			// append value 
			AdFieldValue::appendObject($adfield, 'id', 'AdFieldValue', 'af_id', '', MAIN_DB, '*', false, 'id');

			// append value all descriptions
			if ($adfield->AdFieldValue)
			{
				AdFieldValueDescription::appendObject($adfield->AdFieldValue, 'id', 'AdFieldValueDescription', 'afv_id', '', MAIN_DB, '*', false, 'language_id');
			}
			$title = __('Edit custom field');
			$add = false;
		}
		else
		{
			$adfield = new AdField();
			$title = __('Add custom field');
			$add = true;
		}

		// save changes if submitted
		$this->_adfieldEdit($adfield);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Custom fields'), Language::get_url('admin/itemfield/'))
		);
		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/adfieldEdit', array(
			'adfield'		 => $adfield,
			'menu_selected'	 => 'admin/itemfield/',
			'title'			 => $title,
			'language'		 => Language::getLanguages(),
			'add'			 => $add,
		));
	}

	function itemfieldDelete($id = 0)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$adfield = AdField::findByIdFrom('AdField', $id);
		if (!$adfield)
		{
			Flash::set('error', __('Custom field not found'));
			redirect(Language::get_url('admin/itemfield/'));
		}


		// delete location
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if ($adfield->delete('id'))
			{
				Flash::set('success', __('Record deleted'));
				redirect(Language::get_url('admin/itemfield/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}


		// append name
		AdField::appendAll($adfield);

		// get all related category names
		AdField::appendRelatedCategories($adfield);
		$adfield->countRelatedAds = AdField::countRelatedAds($adfield->id);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Custom fields'), Language::get_url('admin/itemfield/'))
		);
		$breadcrumb[] = array(__('Delete custom field'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/adfieldDelete', array(
			'adfield'		 => $adfield,
			'menu_selected'	 => 'admin/itemfield/',
		));
	}

	private function _adfieldEdit($adfield)
	{
		//echo '[_adfieldEdit]';

		if (get_request_method() == 'POST')
		{
			$language = Language::getLanguages();

			foreach ($language as $lng)
			{
				$rules['af_description[' . $lng->id . '][name]'] = 'trim|strip_tags|xss_clean|required';

				$fields['af_description[' . $lng->id . '][name]'] = __('Name');
			}

			$rules['type'] = 'trim|strip_tags|xss_clean|required';
			$rules['val'] = 'trim|strip_tags|xss_clean';

			$fields['type'] = __('Type');
			$fields['val'] = __('Value');


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				$adfield->setFromData($_POST);
				// check if all languages has value 
				// check iv val is valid for given type
				if ($adfield->_validate_val($adfield))
				{
					if ($adfield->save())
					{
						Flash::set('success', __('Custom field saved.'));
						redirect(Language::get_url('admin/itemfield/'));
					}
					else
					{
						$this->validation()->set_error(__('Error saving custom field'));
					}
				}
			}
			else
			{
				// not saved then just update object with passed values 
				$adfield->setFromData($_POST);
			}
		}
	}

	function categoryFieldGroup()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Category field groups'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);

		//$args = func_get_args();
		// get categories
		$catfieldgroups = CategoryFieldGroup::findAllFrom('CategoryFieldGroup');

		// appen name
		CategoryFieldGroup::appendName($catfieldgroups);

		$this->display('admin/categoryFieldGroup', array(
			'catfieldgroups' => $catfieldgroups,
			'menu_selected'	 => 'admin/categoryFieldGroup/',
		));
	}

	function categoryFieldGroupEdit($id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($id)
		{
			$catfieldgroup = CategoryFieldGroup::findByIdFrom('CategoryFieldGroup', $id);
			if (!$catfieldgroup)
			{
				redirect(Language::get_url('admin/categoryFieldGroup/'));
			}

			// append all descriptions
			CategoryFieldGroupDescription::appendObject($catfieldgroup, 'id', 'CategoryFieldGroupDescription', 'cfg_id', '', MAIN_DB, '*', false, 'language_id');

			$title = __('Edit category field group');
			$add = false;
		}
		else
		{
			$catfieldgroup = new CategoryFieldGroup();
			$title = __('Add category field group');
			$add = true;
		}


		// save changes if submitted
		$this->_categoryFieldGroupEdit($catfieldgroup);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Category field groups'), Language::get_url('admin/categoryFieldGroup/'))
		);
		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/categoryFieldGroupEdit', array(
			'catfieldgroup'	 => $catfieldgroup,
			'menu_selected'	 => 'admin/categoryFieldGroup/',
			'title'			 => $title,
			'language'		 => Language::getLanguages(),
			'add'			 => $add,
		));
	}

	function categoryFieldGroupDelete($id = 0)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$catfieldgroup = CategoryFieldGroup::findByIdFrom('CategoryFieldGroup', $id);
		if (!$catfieldgroup)
		{
			Flash::set('error', __('Catgory field group not found'));
			redirect(Language::get_url('admin/categoryFieldGroup/'));
		}


		// delete location
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if ($catfieldgroup->delete('id'))
			{
				Flash::set('success', __('Record deleted'));
				redirect(Language::get_url('admin/categoryFieldGroup/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}

		// append name
		CategoryFieldGroup::appendName($catfieldgroup);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Category field groups'), Language::get_url('admin/categoryFieldGroup/'))
		);
		$breadcrumb[] = array(__('Delete category field group'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/categoryFieldGroupDelete', array(
			'catfieldgroup'	 => $catfieldgroup,
			'menu_selected'	 => 'admin/categoryFieldGroup/',
		));
	}

	private function _categoryFieldGroupEdit($catfieldgroup)
	{
		if (get_request_method() == 'POST')
		{
			$language = Language::getLanguages();

			foreach ($language as $lng)
			{
				$rules['cfg_description[' . $lng->id . '][name]'] = 'trim|strip_tags|xss_clean|required';

				$fields['cfg_description[' . $lng->id . '][name]'] = __('Name');
			}

			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			// convert empty string to int to prevent type error when inserting to db
			$_POST['space'] = intval($_POST['space']);

			if ($this->validation()->run())
			{

				$catfieldgroup->setFromData($_POST);
				if ($catfieldgroup->save())
				{
					Flash::set('success', __('Category field group saved.'));
					redirect(Language::get_url('admin/categoryFieldGroup/'));
				}
				else
				{
					$this->validation()->set_error(__('Error saving category field group'));
				}
			}

			// not saved then just update object with passed values 
			$catfieldgroup->setFromData($_POST);
		}
	}

	function categoryfield()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Category custom fields'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);

		$catfields = CategoryFieldRelation::findAllFrom('CategoryFieldRelation', "1=1 ORDER BY location_id,category_id,pos");

		Location::appendLocation($catfields);
		Category::appendCategory($catfields);
		AdField::appendAdField($catfields, 'adfield_id');

		$catfields_grouped = CategoryFieldRelation::groupResults($catfields);


		$this->display('admin/categoryfield', array(
			'catfields_grouped'	 => $catfields_grouped,
			'menu_selected'		 => 'admin/categoryfield/',
		));
	}

	function categoryfieldEdit()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$category_id = (isset($_REQUEST['category_id']) ? intval($_REQUEST['category_id']) : null);
		$location_id = (isset($_REQUEST['location_id']) ? intval($_REQUEST['location_id']) : null);


		$title = __('Add category custom fields');
		$add = true;

		if (is_null($category_id) || is_null($location_id))
		{

			// breadcrumb
			$breadcrumb = array(
				array(__('Home'), Language::get_url('admin/')),
				array(__('Category custom fields'), Language::get_url('admin/categoryfield/')),
			);

			$breadcrumb[] = array($title, '');
			$this->assignToLayout('breadcrumb', $breadcrumb);

			// select location and category
			$this->display('admin/categoryfieldEditNew', array(
				'category_id'	 => $category_id,
				'location_id'	 => $location_id,
				'title'			 => $title,
				'menu_selected'	 => 'admin/categoryfield/',
			));
		}





		if ($_POST['step'] != 1)
		{
			// save submitted feilds
			$this->_categoryfieldEdit();
		}


		// get catfields from db
		$catfields = CategoryFieldRelation::getCatfields($location_id, $category_id, true);


		if ($catfields)
		{
			$title = __('Edit category custom fields');
			$add = false;
		}


		$location = Location::findByIdFrom('Location', $location_id);
		$category = Category::findByIdFrom('Category', $category_id);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Category custom fields'), Language::get_url('admin/categoryfield/')),
			array(Location::getFullName($location, __('All locations'), ' :: ') . ' / ' . Category::getFullName($category, __('All categories'), ' :: '), '')
		);

		//$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$categoryfieldgroups = CategoryFieldGroup::findAllFrom('CategoryFieldGroup');
		CategoryFieldGroup::appendName($categoryfieldgroups);

		$adfields = AdField::findAllFrom('AdField');
		AdField::appendAll($adfields);

		$this->display('admin/categoryfieldEdit', array(
			'location_id'			 => $location_id,
			'category_id'			 => $category_id,
			'catfields'				 => $catfields,
			'adfields'				 => $adfields,
			'categoryfieldgroups'	 => $categoryfieldgroups,
			'title'					 => $title,
			'add'					 => $add,
			'menu_selected'			 => 'admin/categoryfield/',
		));
	}

	function categoryfieldDelete($location_id = null, $category_id = null)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);


		if (is_null($category_id) || is_null($location_id))
		{
			// no location or category defined
			Flash::set('error', __('Record not found'));
			redirect(Language::get_url('admin/categoryfield/'));
		}

		// we need raw catfields because CategoryFieldRelation::getCatfields can return empty value which is not usave for deleting that record.
		// $catfields = CategoryFieldRelation::getCatfields($location_id, $category_id, true);
		$all_catfields = CategoryFieldRelation::loadAllCatfields(true);
		if (isset($all_catfields[$location_id][$category_id]))
		{
			$catfields = $all_catfields[$location_id][$category_id];
		}
		else
		{
			// no catfields found 
			Flash::set('error', __('Record not found'));
			redirect(Language::get_url('admin/categoryfield/'));
		}


		// delete location
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if (CategoryFieldRelation::deleteCatfields($location_id, $category_id))
			{
				Flash::set('success', __('Record deleted'));
				redirect(Language::get_url('admin/categoryfield/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Category custom fields'), Language::get_url('admin/categoryfield/'))
		);
		$breadcrumb[] = array(__('Delete category custom fields'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/categoryfieldDelete', array(
			'catfields'		 => $catfields,
			'menu_selected'	 => 'admin/categoryfield/',
		));
	}

	private function _categoryfieldEdit()
	{
		if (get_request_method() == 'POST')
		{
			$rules['location_id'] = 'trim|strip_tags|xss_clean|required';
			$rules['category_id'] = 'trim|strip_tags|xss_clean|required';
			$rules['groups_fields'] = 'trim|strip_tags|xss_clean';

			$fields['location_id'] = __('Location');
			$fields['category_id'] = __('Category');
			$fields['groups_fields'] = __('Value');


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				// delete previous fields
				CategoryFieldRelation::saveCatfields($_POST['location_id'], $_POST['category_id'], $_POST['groups_fields']);

				Flash::set('success', __('Category custom fields saved'));
				redirect(Language::get_url('admin/categoryfield/'));
			}
		}
	}

	function language()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Languages'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);

		//$args = func_get_args();
		// get categories
		$language = Language::getLanguages();

		$this->display('admin/language', array(
			'language'		 => $language,
			'menu_selected'	 => 'admin/language/',
		));
	}

	function languageEdit($id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($id)
		{
			$language = Language::findByIdFrom('Language', $id);
			if (!$language)
			{
				redirect(Language::get_url('admin/language/'));
			}
			$title = __('Edit');
			$add = false;
		}
		else
		{
			$language = new Language();
			$language->img = 'gb.gif';
			$title = __('Add');
			$add = true;
		}

		// save changes if submitted
		$this->_languageEdit($language);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Language'), Language::get_url('admin/language/'))
		);
		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/languageEdit', array(
			'language'		 => $language,
			'title'			 => $title,
			'add'			 => $add,
			'menu_selected'	 => 'admin/language/',
		));
	}

	function languageDelete($id = 0)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		if ($id)
		{
			$language = Language::findByIdFrom('Language', $id);
		}
		if (!$language)
		{
			Flash::set('error', __('Record not found'));
			redirect(Language::get_url('admin/language/'));
		}


		// if total languages less than 1 then cannot delete any language
		if (!Language::canDelete())
		{
			// you cannot delete last available language
			Flash::set('error', __('Cannot delete last language'));
			redirect(Language::get_url('admin/language/'));
		}


		// delete location
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if ($language->delete('id'))
			{
				Flash::set('success', __('Record deleted'));

				redirect(Language::get_url('admin/language/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Language'), Language::get_url('admin/language/'))
		);
		$breadcrumb[] = array(__('Delete'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/languageDelete', array(
			'language'		 => $language,
			'menu_selected'	 => 'admin/language/',
		));
	}

	/**
	 *
	 * @param Language $language
	 * @return boolean 
	 */
	private function _languageEdit($language)
	{
		if (get_request_method() == 'POST')
		{
			$rules['id'] = 'trim|required|strip_tags|alpha_dash|xss_clean';
			$rules['name'] = 'trim|strip_tags|xss_clean|required';

			$fields['id'] = __('ID');
			$fields['name'] = __('Name');

			$_POST['enabled'] = intval($_POST['enabled']);
			$_POST['default'] = intval($_POST['default']);


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				$old_id = $language->id;

				$language->setFromData($_POST);

				// if this is old record then update
				$check_unique_id = true;
				if ($old_id)
				{
					$language->old_id = $old_id;
					$save_key = 'id';

					if ($language->old_id == $language->id)
					{
						$check_unique_id = false;
					}
				}
				else
				{
					// this is new record
					$save_key = 'new_id';
				}


				if ($check_unique_id)
				{
					// check if id is unique 
					$existing_lng = Language::findByIdFrom('Language', $language->id);
					if ($existing_lng)
					{
						$this->validation()->set_error(__('Language {name} already exists.', array('{name}' => $language->id)));
						return false;
					}
				}


				if ($language->save($save_key))
				{
					Flash::set('success', __('Language saved.'));
					redirect(Language::get_url('admin/language/'));
				}
				else
				{
					$this->validation()->set_error(__('Error saving language'));
				}
			}
			else
			{
				$language->setFromData($_POST);
			}
		}
	}

	function languageAction()
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$id = trim($_POST['id']);
		$action = trim($_POST['action']);
		if ($id)
		{
			$language = Language::findByIdFrom('Language', $id);
		}
		if (!$language)
		{
			exit(__('Language not found.'));
		}


		switch ($action)
		{
			case 'enabled':
				if ($language->enabled)
				{
					$language->enabled = 0;
					$language->save('id');
					//exit('{"class":"white","text":"' . __('Disabled') . '"}');
				}
				else
				{
					$language->enabled = 1;
					$language->save('id');
					//exit('{"class":"green","text":"' . __('Enabled') . '"}');
				}
				exit(intval($language->enabled) . '');
				break;
			default:
				exit(__('No action specified.'));
		}
	}

	function languageOrder($id = 0, $dir = 'up')
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		Language::changePosition($id, $dir);

		redirect(Language::get_url('admin/language/'));
	}

	function gTranslate($type = '', $lng = 'tr')
	{
		echo '<p>
		<a href="' . Language::get_url('admin/gTranslate/build/' . $lng . '/') . '">' . Build . '</a>
		| <a href="' . Language::get_url('admin/gTranslate/translate/' . $lng . '/') . '">' . Translate . '</a>
		| <a href="' . Language::get_url('admin/gTranslate/check/' . $lng . '/') . '">' . Check . '</a>
		</p>';

		I18nBuilder::page($type, $lng);
	}

	function translate($lng = null, $type = 'all', $page = 1)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (is_null($lng))
		{
			redirect(Language::get_url('admin/language/'));
		}
		elseif ($lng == 'en' && false)
		{
			Flash::set('error', __('English is base language for this script. No translation needed for it.'));
			redirect(Language::get_url('admin/language/'));
		}

		// translate if posted string
		if (isset($_POST['str']))
		{
			echo I18nBuilder::translateStr($_POST['str'], $lng);
			exit();
		}


		// load desired langugae translation file
		$filename = I18n::getFilename($lng);

		// check file exists and writeble
		FileDir::checkMakeFile($filename);

		// ok load file
		if (is_writable($filename))
		{
			// save previously submitted values 
			if (get_request_method() == 'POST')
			{
				if (I18nBuilder::htmlTranslationFormSubmit($filename, $lng))
				{
					//Flash::set('success', __('Language translation saved.'));
					$this->validation()->set_success(__('Language translation saved.'));
				}
				else
				{
					// Flash::set('error', __('Error saving translation.'));
					$this->validation()->set_error(__('Error saving translation.'));
				}

				// use redirect to prevent include of lng files caching by php object cache
				// redirect(Language::get_url('admin/translate/' . $lng . '/' . $type . '/' . $page));
			}

			$form = I18nBuilder::htmlTranslationForm($lng, $type, $page);
			if (!$form)
			{
				// build base catalog first 
				$this->validation()->set_error(__('No translation terms found. Build translation terms by clicking <a href="{url}">here</a>.', array('{url}' => Language::get_url('admin/translateBuild/' . $lng . '/'))));
			}
		}
		else
		{
			// file is not writable change permission to file 
			$this->validation()->set_error(__('Language file {name} is not writable. change permission of file to 777 using ftp client.', array('{name}' => $filename)));
		}


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Languages'), Language::get_url('admin/language/')),
			array(__('Translate') . ':' . $lng, '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);



		$this->display('admin/translate', array(
			'form'			 => $form,
			'lng'			 => $lng,
			'menu_selected'	 => 'admin/language/',
		));
	}

	function translateBuild($lng)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// build message fiel first scanning existing files
		if (I18nBuilder::buildBaseCatalogFile())
		{
			Flash::set('success', __('Base file with translation terms built.'));

			// fill available fields from backup 			
			Language::updateEmptyLanguageTranslations();
		}
		else
		{
			Flash::set('success', __('Error building base file with translation terms.'));
		}

		redirect(Language::get_url('admin/translate/' . $lng . '/'));
	}

	function emailTemplate($current = 'general')
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Email templates'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);

		//$args = func_get_args();
		// get categories
		$mail_template = MailTemplate::getAllIndexed();
		$mail_template_defaults = MailTemplate::getDefaultAll();

		$this->_emailTemplateEdit($mail_template);

		if (!isset($mail_template_defaults[$current]))
		{
			$current = 'general';
		}

		$this->display('admin/emailTemplate', array(
			'mail_template'			 => $mail_template,
			'mail_template_defaults' => $mail_template_defaults,
			'current'				 => $current,
			'language'				 => Language::getLanguages(),
			'menu_selected'			 => 'admin/emailTemplate/',
		));
	}

	private function _emailTemplateEdit($mail_template)
	{
		if (get_request_method() == 'POST')
		{
			$language = Language::getLanguages();

			foreach ($language as $lng)
			{
				$rules['mt[' . $lng->id . '][subject]'] = 'trim|strip_tags|xss_clean|required';
				$rules['mt[' . $lng->id . '][body]'] = 'trim|strip_tags|xss_clean|required';

				$fields['mt[' . $lng->id . '][subject]'] = __('Subject') . '(' . $lng->id . ')';
				$fields['mt[' . $lng->id . '][body]'] = __('Body') . '(' . $lng->id . ')';
			}


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				$id = $_POST['id'];
				foreach ($language as $lng)
				{
					if (!isset($mail_template[$id][$lng->id]))
					{
						$mail_template[$id][$lng->id] = new MailTemplate();
						$mail_template[$id][$lng->id]->id = $id;
						$mail_template[$id][$lng->id]->language_id = $lng->id;
					}
					$mail_template[$id][$lng->id]->subject = $_POST['mt'][$lng->id]['subject'];
					$mail_template[$id][$lng->id]->body = $_POST['mt'][$lng->id]['body'];

					$mail_template[$id][$lng->id]->save('new_id');
				}

				Flash::set('success', __('Mail template saved.'));
				redirect(Language::get_url('admin/emailTemplate/' . $id . '/'));
			}
			else
			{
				$id = $_POST['id'];
				foreach ($language as $lng)
				{
					$mail_template[$id][$lng->id]->subject = $_POST['mt'][$lng->id]['subject'];
					$mail_template[$id][$lng->id]->body = $_POST['mt'][$lng->id]['body'];
				}
			}
		}
	}

	function widgets()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// get stored widget sidebar positions
		$sidebar_widgets = Widget::sidebarWidgets();

		// append actual widgets to variables
		Widget::appendWidgets($sidebar_widgets);

		$widget_types = Widget::typesAll();
		$theme_locations = Theme::locations();
		$page_types = Config::pageTypesGet();

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Widgets'), ''),
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/widgets', array(
			'menu_selected'		 => 'admin/widgets/',
			'widget_types'		 => $widget_types,
			'sidebar_widgets'	 => $sidebar_widgets,
			'theme_locations'	 => $theme_locations,
			'theme'				 => Theme::getTheme(),
			'page_types'		 => $page_types,
		));
	}

	function widgetsOld()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// get stored widget sidebar positions
		$sidebar_widgets = Widget::sidebarWidgets();

		// append actual widgets to variables
		Widget::appendWidgets($sidebar_widgets);

		$widget_types = Widget::typesAll();
		$theme_locations = Theme::locations();
		$page_types = Config::pageTypesGet();

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Widgets'), ''),
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/widgets_old', array(
			'menu_selected'		 => 'admin/widgets/',
			'widget_types'		 => $widget_types,
			'sidebar_widgets'	 => $sidebar_widgets,
			'theme_locations'	 => $theme_locations,
			'theme'				 => Theme::getTheme(),
			'page_types'		 => $page_types,
		));
	}

	function widgetsSave()
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		if (!Config::nounceCheck(true))
		{
			// no nounce then possible xss
			exit('Error:' . strip_tags(Validation::getInstance()->messages_dump()));
		}

		switch ($_POST['action'])
		{
			case 'widgets-order-save':
				// save new order of widgets
				if (Widget::sidebarWidgetsSaveFromPost())
				{
					exit('ok');
				}
				else
				{
					exit(__('Error saving widgets'));
				}
				break;
			case 'widget-save':
				// parse data
				$data = array();
				parse_str($_POST['data'], $data);

				$id = intval($data['id']);
				$type_id = trim($data['type_id']);

				unset($data['id']);
				unset($data['type_id']);


				// get widget if exists
				$widget = Widget::findByIdFrom('Widget', $id);
				if (!$widget)
				{
					if (!strlen($type_id) || !Widget::typeIsValid($type_id))
					{
						exit(__('Widget type is not valid'));
					}

					// add new widget
					$widget = new Widget();
					$widget->type_id = $type_id;
				}

				// process data prior saving
				$widget->setOptions($data);
				$widget->save('id');


				echo 'ok{SEP}';
				echo Widget::form($widget);
				exit();
				break;
			case 'widget-delete':
				$arr_ids = explode(',', $_POST['id']);
				// get widget if exists
				$widget = Widget::findManyByIdFrom('Widget', $arr_ids);
				if ($widget)
				{
					$ok = false;
				}
				else
				{
					$ok = true;
				}
				foreach ($widget as $w)
				{
					$ok = $ok || $w->delete();
				}

				if ($ok)
				{
					exit('ok');
				}
				else
				{
					exit(__('Error deleting widget. Please refresh page and try again.'));
				}
				break;
		}

		/*
		 * this will get array of widgets in each locationa and inactive widgets.
		 * check for position changes
		 * location changes
		 * removed widgets
		 * inactive widgets that should be used if theme with that location exists
		 * 
		 * check how wordpress works with widgets 
		 * 
		 * 
		 * 
		 */
		exit('action is not defined');
	}

	function themes()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// get installed themes
		$themes = Theme::getThemes();
		$themes_backup = Theme::getThemes(true);

		$available_theme_updates = Update::availableThemeUpdates();

		// print_r($available_theme_updates);
		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Themes'), ''),
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/themes', array(
			'themes'					 => $themes,
			'themes_backup'				 => $themes_backup,
			'available_theme_updates'	 => $available_theme_updates,
			'menu_selected'				 => 'admin/themes/',
		));
	}

	function themesInstall()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);


		$available_theme_updates = Update::availableThemeUpdates();

		// print_r($available_theme_updates);
		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Themes'), Language::get_url('admin/themes/')),
			array(__('Install Themes'), ''),
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/themesInstall', array(
			'available_theme_updates'	 => $available_theme_updates,
			'menu_selected'				 => 'admin/themes/',
		));
	}

	function themesActivate($theme_id = null)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (is_null($theme_id))
		{
			redirect(Language::get_url('admin/themes/'));
		}

		// backup current theme
		Theme::backupThemeOptions();

		// load new theme
		$theme = Theme::restoreThemeOptions($theme_id);


		Flash::set('success', __('{name} theme activated', array('{name}' => View::escape($theme->info['name']))));
		redirect(Language::get_url('admin/themes/'));
	}

	function themesDelete($theme_id = null)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		if (!$theme_id)
		{
			Flash::set('error', __('Theme not found'));
			redirect(Language::get_url('admin/themes/'));
		}

		if ($theme_id == Theme::currentThemeId())
		{
			Flash::set('error', __('Cannot delete currently active theme'));
			redirect(Language::get_url('admin/themes/'));
		}

		// delete theme
		if ($_POST['confirm_delete'])
		{
			$theme = Theme::getTheme($theme_id);
			if ($theme->deleteTheme())
			{
				Flash::set('success', __('Theme deleted'));
				redirect(Language::get_url('admin/themes/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting theme. Check {name} directory permission', array('{name}' => $theme->themeDir())));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}



		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Themes'), Language::get_url('admin/themes/')),
			array(__('Delete theme'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/themesDelete', array(
			'theme'			 => Theme::getTheme($theme_id),
			'menu_selected'	 => 'admin/themes/',
		));
	}

	function themesDeleteBackup()
	{
		if (!Config::nounceCheck(true))
		{
			// no nounce then possible xss
			exit('Error:' . strip_tags(Validation::getInstance()->messages_dump()));
		}


		switch ($_POST['action'])
		{
			case 'delete_all':
				// delete all themes
				if (Theme::deleteThemeBackupAll())
				{
					exit('ok');
				}
				else
				{
					exit('Error:' . strip_tags(Validation::getInstance()->messages_dump()));
				}
				break;
			case 'delete_theme':
				// delete given theme
				if (strlen($_POST['theme_id']))
				{
					if (Theme::deleteThemeBackup($_POST['theme_id']))
					{
						exit('ok');
					}
					else
					{
						exit('Error:' . strip_tags(Validation::getInstance()->messages_dump()));
					}
				}
				break;
			default:
				exit('Error:' . strip_tags(Validation::getInstance()->messages_dump()));
		}
	}

	function themesCustomize($theme_id = null)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$theme = Theme::getTheme($theme_id);

		$this->setLayout(false);
		$this->display('admin/themesCustomize', array(
			'theme_id'	 => $theme->id,
			'title'		 => __('Customize') . ': ' . View::escape($theme->info['name'])
		));
	}

	function themesCustomizeControls($theme_id = null)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$theme = Theme::getTheme($theme_id);

		if (get_request_method() == 'POST')
		{
			// save preview data 
			$data = $theme->optionFixCheckboxData($_POST);
			$theme->optionSaveAllFromData($data);

			// activate this theme and redirect to themes
			return $this->themesActivate($theme->id);
		}

		$this->setLayout(false);
		$this->display('admin/themesCustomizeControls', array(
			'title'	 => __('Customize') . ': ' . View::escape($theme->info['name']),
			'theme'	 => $theme
		));
	}

	function themesCustomizeAjax($theme_id = null)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$theme = Theme::getTheme($theme_id);

		foreach ($_FILES as $fieldname => $v)
		{
			$field_options = $theme->customizeFieldOptions($fieldname);

			if ($field_options)
			{
				// field found then perform resize action and return image name
				switch ($field_options['type'])
				{
					case 'image':
						// rezie image 
						$width_max = intval($field_options['width_max']);
						$height_max = intval($field_options['height_max']);

						// upload image to theme dir
						$img = Adpics::upload($fieldname, $theme->uploadDirRelative());
						if (!$img)
						{
							exit(Adpics::getUploadErrors());
						}

						// get absolute image location
						$img_src = $theme->uploadDir($img);

						if ($width_max || $height_max)
						{
							if (!SimpleImage::fromFile($img_src)->maxarea($width_max, $height_max)->save($img_src))
							{
								// error resizing delete image 
								$theme->deleteFile($img);
								exit(__('Error resizing image'));
							}
						}

						// add image to uploaded images for this theme options 
						$old_images = $theme->option('_' . $fieldname);
						if (!$old_images)
						{
							$old_images = array();
						}
						array_unshift($old_images, $img);
						$theme->optionSet('_' . $fieldname, $old_images);

						// return current image 
						exit('ok{SEP}' . $img);

						break;
					default :
						exit(__('action is not valid'));
				}
			}
		}

		// perform custom actions 
		switch ($_POST['custom_action'])
		{
			case 'remove_image':
				// remove image from hidden value array 
				$field = $_POST['field'];
				$img = $_POST['img'];

				// delete file 
				$theme->deleteFile($img);

				// update option 
				$old_images = $theme->option('_' . $field);
				if ($old_images)
				{
					$_old_images = array();
					foreach ($old_images as $old_img)
					{
						if ($old_img != $img)
						{
							$_old_images[] = $old_img;
						}
					}


					// update with new value 
					$theme->optionSet('_' . $field, $_old_images);
				}

				exit('ok');
				break;
		}

		exit(__('action is not valid'));
	}

	/*	 * *********************************************************************
	 *       PAGES
	 * ******************************************************************** */

	function pages($parent_id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// FIXME: add activate controls for no javascript users
		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Pages'), Language::get_url('admin/pages/'))
		);

		if ($parent_id)
		{
			$parent_page = Page::findByIdFrom('Page', $parent_id);
			if (!$parent_page)
			{
				// no such parent category then redirect to root of categories
				Flash::set('error', __('No parent page with {num} key', array('{num}' => $parent_id)));
				redirect(Language::get_url('admin/pages/'));
			}
			// appen name
			Page::appendName($parent_page);

			$this->_appendParentPagesBreadcrumb($breadcrumb, $parent_page);
			$breadcrumb[] = array(Page::getName($parent_page), '');
		}

		$this->assignToLayout('breadcrumb', $breadcrumb);

		// $args = func_get_args();
		// get categories
		$pages = Page::findAllFrom('Page', 'parent_id=? ORDER BY pos', array($parent_id));

		// append name
		Page::appendName($pages);

		$this->display('admin/pages', array(
			'pages'			 => $pages,
			'menu_selected'	 => 'admin/pages/',
			'parent_page'	 => $parent_page,
		));
	}

	function pagesEdit($id = 0, $parent_id = 0)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($id)
		{
			$page = Page::findByIdFrom('Page', $id, 'id');
			if (!$page)
			{
				redirect(Language::get_url('admin/pages/'));
			}
			// append all descriptions
			PageDescription::appendObject($page, 'id', 'PageDescription', 'page_id', '', MAIN_DB, '*', false, 'language_id');

			$title = __('Edit page');
			$add = false;
		}
		else
		{
			$page = new Page();
			$page->enabled = 1;
			$page->parent_id = $parent_id;
			$title = __('Add page');
			$add = true;
		}

		// save changes if submitted
		$this->_pagesEdit($page);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Pages'), Language::get_url('admin/pages/'))
		);
		$this->_appendParentPagesBreadcrumb($breadcrumb, $page);
		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/pagesEdit', array(
			'page'			 => $page,
			'menu_selected'	 => 'admin/pages/',
			'title'			 => $title,
			'language'		 => Language::getLanguages(),
			'add'			 => $add,
		));
	}

	private function _appendParentPagesBreadcrumb(& $breadcrumb, $page)
	{
		// populate parents
		Page::getParents($page);

		foreach ($page->arr_parents as $p)
		{
			$breadcrumb[] = array(Page::getName($p), Language::get_url('admin/pages/' . $p->id . '/'));
		}
	}

	function pagesDelete($id = 0)
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$page = Page::findByIdFrom('Page', $id);
		if (!$page)
		{
			Flash::set('error', __('Page not found'));
			redirect(Language::get_url('admin/pages/'));
		}

		// delete category
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if ($page->delete('id'))
			{
				Flash::set('success', __('Record deleted'));
				redirect(Language::get_url('admin/pages/' . $page->parent_id . '/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}

		// append name
		Page::appendName($page);


		// get subpages tree
		$pages_tree = Page::getAllPageNamesTree();
		$subpage_tree = Page::htmlPageTree($pages_tree, $page->id);


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Pages'), Language::get_url('admin/pages/'))
		);
		$this->_appendParentPagesBreadcrumb($breadcrumb, $page);
		$breadcrumb[] = array(__('Delete page'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/pagesDelete', array(
			'page'			 => $page,
			'menu_selected'	 => 'admin/pages/',
			'subpage_tree'	 => $subpage_tree,
		));
	}

	/**
	 *
	 * @param Page $page 
	 */
	private function _pagesEdit($page)
	{
		if (get_request_method() == 'POST')
		{
			$language = Language::getLanguages();

			foreach ($language as $lng)
			{
				$rules['page_description[' . $lng->id . '][name]'] = 'trim|strip_tags|xss_clean|callback_TextTransform::removeSpacesNewlines|required';
				$rules['page_description[' . $lng->id . '][description]'] = 'trim|xss_clean';

				$fields['page_description[' . $lng->id . '][name]'] = __('Name');
				$fields['page_description[' . $lng->id . '][description]'] = __('Description');
			}

			$rules['parent_id'] = 'trim|intval';
			$rules['enabled'] = 'trim|intval';

			$fields['parent_id'] = __('Parent category');
			$fields['enabled'] = __('Enabled');


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			// set checkbox to 0 if not set
			$_POST['enabled'] = intval($_POST['enabled']);

			if ($this->validation()->run())
			{

				$page->setFromData($_POST);
				if ($page->save())
				{
					Flash::set('success', __('Page saved.'));
					redirect(Language::get_url('admin/pages/' . $page->parent_id . '/'));
				}
				else
				{
					$this->validation()->set_error(__('Error saving page'));
				}
			}

			// not saved then just update object with passed values 
			$page->setFromData($_POST);
		}
	}

	function pagesAction()
	{
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		$id = intval($_POST['id']);
		$action = $_POST['action'];
		if ($id)
		{
			$page = Page::findByIdFrom('Page', $id);
		}
		if (!$page)
		{
			exit(__('Page not found'));
		}


		switch ($action)
		{
			case 'enabled':
				return $this->_pagesEnabled($page);
				break;
			default:
				exit(__('No action specified.'));
		}
	}

	function _pagesEnabled($page)
	{
		// 
		if ($page->enabled)
		{
			$page->enabled = 0;
			$page->save('id');
			//exit('{"style_class":"white","text":"' . __('Disabled') . '"}');
		}
		else
		{
			$page->enabled = 1;
			$page->save('id');
			//exit('{"style_class":"green","text":"' . __('Enabled') . '"}');
		}
		exit(intval($page->enabled) . '');
	}

	function pagesOrder($id = 0, $dir = 'up')
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$parent_id = Page::changePosition($id, $dir);

		if ($parent_id)
		{
			redirect(Language::get_url('admin/pages/' . $parent_id . '/'));
		}

		redirect(Language::get_url('admin/pages/'));
	}

	function maintenance()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// update value 
		if (get_request_method() == 'POST')
		{
			if (isset($_POST['maintenance']))
			{
				Config::optionSet('maintenance', intval($_POST['maintenance']));
				Flash::set('success', __('Maintenance mode updated'));
				redirect(Language::get_url('admin/maintenance/'));
			}

			if (isset($_POST['debug_mode']))
			{
				Config::optionSet('debug_mode', intval($_POST['debug_mode']));
				Flash::set('success', __('Debug mode updated'));
				redirect(Language::get_url('admin/maintenance/'));
			}
		}

		$maintenance_mode = Config::option('maintenance');
		$debug_mode = Config::option('debug_mode');

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Maintenance'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/maintenance', array(
			'maintenance_mode'	 => $maintenance_mode,
			'debug_mode'		 => $debug_mode,
			'menu_selected'		 => 'admin/maintenance/',
		));
	}

	function paymentHistory($ad_id = 0, $page = 1)
	{
		AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, true);


		$ad_id = intval($ad_id);
		$page = intval($page);

		// list all reports 
		if (!$ad_id)
		{
			AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, true);

			// list all reports 
			$where = "item_type!=?";
			$vals = array(Payment::ITEM_TYPE_FEATURED_REQUESTED);
		}
		else
		{
			$where = "ad_id=? AND item_type!=?";
			$vals = array($ad_id, Payment::ITEM_TYPE_FEATURED_REQUESTED);

			/* @var $ad Ad */
			$ad = Ad::findByIdFrom('Ad', $ad_id);

			AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, true);
		}

		if (isset($_POST['page']))
		{
			$page = $_POST['page'];
		}

		if (!$page)
		{
			$page = 1;
		}

		// get payments
		$st = ($page - 1) * self::$num_records;

		$payments = Payment::findAllFrom('Payment', $where . " ORDER BY id DESC LIMIT " . $st . ',' . self::$num_records, $vals);

		// populate paginator
		$total_records = Payment::countFrom('Payment', $where, $vals);
		$total_pages = ceil($total_records / self::$num_records);
		$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/paymentHistory/{page}/'));

		// append ads
		Ad::appendObject($payments, 'ad_id', 'Ad');

		// append payment log
		PaymentLog::appendObject($payments, 'payment_log_id', 'PaymentLog');


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Payment'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/paymentHistory', array(
			'payments'		 => $payments,
			'paginator'		 => $paginator,
			'ad'			 => $ad,
			'menu_selected'	 => 'admin/paymentHistory/',
		));
	}

	function paymentPrice()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Paid options'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);

		$payment_prices = PaymentPrice::findAllFrom('PaymentPrice', "1=1 ORDER BY location_id,category_id");

		Location::appendLocation($payment_prices);
		Category::appendCategory($payment_prices);


		$this->display('admin/paymentPrice', array(
			'payment_prices' => $payment_prices,
			'menu_selected'	 => 'admin/paymentPrice/',
		));
	}

	function paymentPriceEdit($location_id = null, $category_id = null)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (!is_null($category_id) && !is_null($location_id))
		{
			$payment_price = PaymentPrice::getPrice($location_id, $category_id);
		}

		if (!$payment_price)
		{
			$payment_price = new PaymentPrice();
			$payment_price->location_id = $location_id;
			$payment_price->category_id = $category_id;
			$title = __('Add price');
		}
		else
		{
			$title = __('Edit price');
		}

		// save submitted feilds		
		$payment_price = $this->_paymentPriceEdit($payment_price);

		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Paid options'), Language::get_url('admin/paymentPrice/')),
		);

		$breadcrumb[] = array($title, '');
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/paymentPriceEdit', array(
			'payment_price'	 => $payment_price,
			'title'			 => $title,
			'menu_selected'	 => 'admin/paymentPrice/',
		));
	}

	function paymentPriceDelete($location_id = null, $category_id = null)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (!is_null($category_id) && !is_null($location_id))
		{
			$payment_price = PaymentPrice::getPrice($location_id, $category_id, true);
		}

		if (!$payment_price)
		{
			// no location or category defined
			Flash::set('error', __('Record not found'));
			redirect(Language::get_url('admin/paymentPrice/'));
		}


		// delete location
		if ($_POST['confirm_delete'])
		{
			// delete form db and image files
			if (PaymentPrice::deletePrice($location_id, $category_id))
			{
				Flash::set('success', __('Record deleted'));
				redirect(Language::get_url('admin/paymentPrice/'));
			}
			else
			{
				$this->validation()->set_error(__('Error deleting record'));
			}
		}
		elseif ($_POST['submit'])
		{
			$this->validation()->set_error(__('Please select confirm delete checkbox.'), 'confirm_delete');
		}


		// breadcrumb
		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Paid options'), Language::get_url('admin/paymentPrice/'))
		);
		$breadcrumb[] = array(__('Delete price'), '');
		$this->assignToLayout('breadcrumb', $breadcrumb);


		$this->display('admin/paymentPriceDelete', array(
			'payment_price'	 => $payment_price,
			'menu_selected'	 => 'admin/paymentPrice/',
		));
	}

	private function _paymentPriceEdit($payment_price)
	{
		if (get_request_method() == 'POST')
		{
			$rules['location_id'] = 'trim|intval|required';
			$rules['category_id'] = 'trim|intval|required';
			$rules['price_featured'] = 'trim|floatval';
			$rules['price_post'] = 'trim|floatval';

			$fields['location_id'] = __('Location');
			$fields['category_id'] = __('Category');
			$fields['price_featured'] = __('Featured price');
			$fields['price_post'] = __('Posting price');


			$this->validation()->set_rules($rules);
			$this->validation()->set_fields($fields);

			if ($this->validation()->run())
			{
				$return = PaymentPrice::savePrice($_POST);

				Flash::set('success', __('Price saved'));
				redirect(Language::get_url('admin/paymentPrice/'));
			}
			$payment_price = new PaymentPrice($_POST);
		}

		return $payment_price;
	}

	public function unserializeTool()
	{
		$data_u = unserialize($_POST['data']);

		if (is_array($data_u))
		{
			foreach ($data_u as $k => $v)
			{
				$return .= '<div><b>' . View::escape($k) . '</b></div><pre>' . var_export($v, true) . '</pre>';
			}
		}
		else
		{

			$return = '<pre>' . var_export($data_u, true) . '</pre>';
		}

		$this->display('admin/unserializeTool', array(
			'data'			 => $_POST['data'],
			'menu_selected'	 => 'admin/unserializeTool/',
			'return'		 => $return
		));
	}

	/**
	 * display abuse reports by ad or all
	 * 
	 * @param int $ad_id
	 * @param int $page 
	 */
	function itemAbuse($ad_id = 0, $page = 1)
	{
		AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, true);

		$ad_id = intval($ad_id);
		$page = intval($page);

		// list all reports 
		if (!$ad_id)
		{
			// list all reports 
			$where = "1=1";
			$vals = array();
		}
		else
		{
			$where = "ad_id=?";
			$vals = array($ad_id);
			$ad = Ad::findByIdFrom('Ad', $ad_id);
		}

		if ($page < 1)
		{
			$page = 1;
		}



		// get users
		$st = ($page - 1) * self::$num_records;
		$adabuse = AdAbuse::findAllFrom('AdAbuse', $where . ' ORDER BY id DESC LIMIT ' . $st . ',' . self::$num_records, $vals);

		// populate paginator
		$total_adabuse = AdAbuse::countFrom('AdAbuse', $where, $vals);
		$total_pages = ceil($total_adabuse / self::$num_records);
		$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/itemAbuse/' . $ad_id . '/{page}/'));

		Ad::appendObject($adabuse, 'ad_id', 'Ad');
		User::appendObject($adabuse, 'added_by', 'User');

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Abuse reports'), Language::get_url('admin/itemAbuse/'))
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/adAbuse', array(
			'ad'			 => $ad,
			'adabuse'		 => $adabuse,
			'paginator'		 => $paginator,
			'menu_selected'	 => 'admin/itemAbuse/',
		));
	}

	/**
	 *
	 * @param type $ad_id 
	 */
	function itemAbuseDelete()
	{
		// check permission 
		$this->_hasPermissionAjax(User::PERMISSION_MODERATOR, false, true);

		$id = intval($_POST['id']);
		if ($id)
		{
			$ad_abuse = AdAbuse::findByIdFrom('AdAbuse', $id);
		}

		if ($ad_abuse)
		{
			$ad_id = $ad_abuse->ad_id;
			if (!$ad_abuse->delete())
			{
				exit(__('Error deleting record'));
			}

			// update abuse count and status 
			AdAbuse::updateCount($ad_id);
		}

		exit('ok');
	}

	/**
	 * display abuse reports by ad or all
	 * 
	 * @param int $ad_id
	 * @param int $page 
	 */
	function ipBlock($type = 0, $page = 1)
	{
		AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, true);

		$this->_ipBlock();

		// clear all expired logs before displaying 
		IpBlock::clearExpired();

		$type = intval($type);
		$page = intval($page);
		if ($page < 1)
		{
			$page = 1;
		}

		// get users
		$st = ($page - 1) * self::$num_records;

		$sql = array(
			IpBlock::TYPE_ACCESS	 => array(
				'sql'	 => "type=?",
				'vals'	 => array(IpBlock::TYPE_ACCESS)),
			IpBlock::TYPE_LOGIN		 => array(
				'sql'	 => "type=? AND num>=?",
				'vals'	 => array(IpBlock::TYPE_LOGIN, Config::option('ipblock_login_attempt_count'))),
			IpBlock::TYPE_CONTACT	 => array(
				'sql'	 => "type=? AND num>=?",
				'vals'	 => array(IpBlock::TYPE_CONTACT, Config::option('ipblock_contact_limit_count'))),
		);
		$ipblocks = IpBlock::findAllFrom('IpBlock', $sql[$type]['sql'] . ' ORDER BY ip,added_at LIMIT ' . $st . ',' . self::$num_records, $sql[$type]['vals']);


		// count currently blocked ips by type 
		$sql_count = "SELECT count(*) as row_num,type "
				. "FROM " . IpBlock::tableNameFromClassName('IpBlock') . " "
				. "WHERE type=? OR (type=? AND num>=?) OR (type=? AND num>=?) "
				. "GROUP BY type";
		$sql_count_vals = array(
			IpBlock::TYPE_ACCESS,
			IpBlock::TYPE_LOGIN,
			Config::option('ipblock_login_attempt_count'),
			IpBlock::TYPE_CONTACT,
			Config::option('ipblock_contact_limit_count')
		);
		$ipblock_count = IpBlock::query($sql_count, $sql_count_vals);
		$count = array();
		foreach ($ipblock_count as $val)
		{
			$count[$val->type] = $val->row_num;
		}
		unset($ipblock_count);


		// populate paginator
		$total_records = $count[$type];
		$total_pages = ceil($total_records / self::$num_records);
		$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/ipBlock/' . $type . '/{page}/'));

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Blocked IPs'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/ipBlock', array(
			'ipblocks'		 => $ipblocks,
			'count'			 => $count,
			'type'			 => $type,
			'paginator'		 => $paginator,
			'menu_selected'	 => 'admin/ipBlock/',
		));
	}

	function _ipBlock()
	{
		if (get_request_method() == 'POST')
		{
			$ips = explode("\n", $_POST['ips']);
			$num = IpBlock::blockIps($ips);
			$this->validation()->set_success(__('{num} records added', array('{num}' => $num)));
		}
	}

	/**
	 *
	 * @param type $ad_id 
	 */
	function ipBlockDelete()
	{
		// check permission 
		$this->_hasPermissionAjax(User::PERMISSION_MODERATOR, false, true);

		$id = intval($_POST['id']);
		if ($id)
		{
			$ipblock = IpBlock::findByIdFrom('IpBlock', $id);
		}

		if ($ipblock)
		{
			if (!$ipblock->delete())
			{
				exit(__('Error deleting record'));
			}
		}

		exit('ok');
	}

	function import($action = '')
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$this->_import();


		// check if ignoring old not complete imports
		switch ($action)
		{
			case '1':
				SimpleCache::delete('bulk_import_locations_left');
				SimpleCache::delete('bulk_import_categories_left');
				break;
			default:
				// check for not copleted imports
				$import_button = '';
				$bulk_import_locations_left = SimpleCache::get('bulk_import_locations_left');
				if ($bulk_import_locations_left !== false)
				{
					$count = substr_count($bulk_import_locations_left, "\n") + 1;
					$import_button .= '<button class="button import_batch_loc" data-target=".import_batch_loc" data-toggle="cb_batch" data-url="admin/importContinue/locations/">' . __('Import {num} locations', array(
								'{num}' => $count
							)) . '</button> ';
				}

				$bulk_import_categories_left = SimpleCache::get('bulk_import_categories_left');
				if ($bulk_import_categories_left !== false)
				{
					$count = substr_count($bulk_import_categories_left, "\n") + 1;
					$import_button .= '<button class="button import_batch_cat" data-target=".import_batch_cat" data-toggle="cb_batch" data-url="admin/importContinue/categories/">' . __('Import {num} categories', array(
								'{num}' => $count
							)) . '</button> ';
				}

				if ($import_button)
				{
					// ignore button 
					$import_button .= ' <a class="button" href="' . Language::get_url('admin/import/1/') . '">' . __('Ignore') . '</a>';
					
					$message = __('Items not completed from previous import. {button}', array(
						'{button}' => $import_button
					));

					// show notice with batch process button
					Validation::getInstance()->set_info($message);
				}
		}

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Import data'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		$this->display('admin/import', array(
			'menu_selected' => 'admin/import/'
		));
	}

	/**
	 * Perform import action using uploaded file
	 * Break if execution exceeds 10 seconds.
	 * Add left items to cache, then cache used to show message with button to continue or ignore left items.
	 * 
	 * @return boolean
	 */
	function _import()
	{
		if (get_request_method() == 'POST' && $_FILES['file']['tmp_name'])
		{
			if (!Config::nounceCheck(true))
			{
				return false;
			}

			$imported = 0;
			if ($_POST['type'] == "category")
			{
				$imported = Category::importString(file_get_contents($_FILES['file']['tmp_name']));
				
				Flash::set('success', __('Imported {num} records', array('{num}' => intval($imported['count']))));

				// check if we have any import string left 
				if ($imported['string_left'])
				{
					// store it in cache 
					SimpleCache::set('bulk_import_categories_left', $imported['string_left']);
					redirect(Language::get_url('admin/import/'));
				}
				else
				{
					redirect(Language::get_url('admin/categories/'));
				}
			}
			elseif ($_POST['type'] == "location")
			{
				$imported = Location::importString(file_get_contents($_FILES['file']['tmp_name']));

				Flash::set('success', __('Imported {num} records', array('{num}' => intval($imported['count']))));

				// check if we have any import string left 
				if ($imported['string_left'])
				{
					// store it in cache 
					SimpleCache::set('bulk_import_locations_left', $imported['string_left']);
					redirect(Language::get_url('admin/import/'));
				}
				else
				{
					redirect(Language::get_url('admin/locations/'));
				}
			}
		}
	}

	/**
	 * Continue importing locations or categories that didn't finish in 10 seconds. 
	 * Importing in batches of 10 second. 
	 * Stops when completed or on error.
	 * Requested from ajax
	 * 
	 * @param string $action  locations|categories
	 */
	function importContinue($action)
	{

		// check if admin 
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);
		$left_count = 0;
		$imported = false;
		$cache_key = '';

		if (Config::nounceCheck())
		{
			switch ($action)
			{
				case 'locations':
					// this is ajax call 
					$cache_key = 'bulk_import_locations_left';
					$str = SimpleCache::get($cache_key);
					if ($str !== false)
					{
						$imported = Location::importString($str);
					}
					break;
				case 'categories':
					// this is ajax call 
					$cache_key = 'bulk_import_categories_left';
					$str = SimpleCache::get($cache_key);
					if ($str !== false)
					{
						$imported = Category::importString($str);
					}
					break;
			}
		}
		else
		{
			exit();
		}

		if ($imported && strlen($imported['string_left']) && strlen($cache_key))
		{
			// store it in cache 
			SimpleCache::set($cache_key, $imported['string_left']);
			$left_count = substr_count($imported['string_left'], "\n") + 1;

			// continue importing in next batch 
			$return = array(
				'text'		 => __('Importing ({num} records left)', array(
					'{num}' => $left_count
				)),
				'continue'	 => 1
			);
		}
		elseif (strlen($cache_key))
		{
			// delete from cache it is completed 
			SimpleCache::delete($cache_key);

			// not found or completed. finish batch
			$return = array(
				'text'		 => __('Completed'),
				'continue'	 => 0
			);
		}

		$this->displayJSON(Config::arr2js($return));
		exit;
	}

	function importAds()
	{

		if (!Config::nounceCheck(true))
		{
			// no nounce then possible xss
			exit('Error:' . strip_tags(Validation::getInstance()->messages_dump()));
		}

		// import from url 
		Import::importFromUrlXml($_POST['url'], $_POST['page']);
		exit;
	}

	function updateDB()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		Update::updateDB();

		Flash::set('success', __('Database updated'));
		redirect(Language::get_url('admin/'));
	}

	function updateTheme($theme_id)
	{
		Update::updateTheme($theme_id);

		$messages = Validation::getInstance()->messages_dump();

		Validation::getInstance()->clear_all();

		$this->display('admin/update', array(
			'menu_selected'	 => 'admin/themes/',
			'messages'		 => $messages,
		));
	}

	function update()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($_POST['next_step'] == 1)
		{
			if ($_POST['restart'])
			{
				// reset update first
				Config::optionDelete('update_next_step');
			}

			if (Update::updateCoreStep())
			{
				if (!Update::isCoreLatest())
				{
					// not completed yet
					echo 'ok{SEP}';
				}
				else
				{
					echo 'completed{SEP}';
				}
			}
			echo strip_tags($this->validation()->messages_dump()) . '<br/>';
			exit;
		}

		$themes = Theme::getThemes();

		// reset update first
		Config::optionDelete('update_next_step');

		$this->display('admin/update', array(
			'update_core'	 => 1,
			'themes'		 => $themes,
		));
	}

	function updateCheck()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (Update::checkForUpdates(true, false))
		{
			if (Update::isCoreLatest())
			{
				// if latest version then say it
				Flash::set('success', __('Your script is up to date.'));
			}
		}
		else
		{
			$error_msg = strip_tags(Validation::getInstance()->messages_dump());
			if ($error_msg)
			{
				Flash::set('error', $error_msg);
			}
		}

		// if notlatest then appropriate message will be shown on index page anyway
		redirect(Language::get_url('admin/'));
	}

	function clearCache()
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if ($_POST['clear'])
		{
			Config::clearAllCache(false);
		}
		elseif ($_POST['clear_data'])
		{
			Config::clearAllCache(false, 'data');
		}
		elseif ($_POST['clear_image'])
		{
			Config::clearAllCache(false, 'image');
		}

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Clear cache'), '')
		);
		$this->assignToLayout('breadcrumb', $breadcrumb);

		// if notlatest then appropriate message will be shown on index page anyway
		$this->display('admin/clearCache', array(
			'menu_selected' => 'admin/clearCache/'
		));
	}

	function logs($type = 0, $group = null)
	{
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		$type = intval($type);
		$data = $cols = array();

		// load both data and count 
		$data_all[IpBlock::TYPE_LOGIN] = IpBlock::logRead(IpBlock::TYPE_LOGIN);
		$data_all[IpBlock::TYPE_CONTACT] = IpBlock::logRead(IpBlock::TYPE_CONTACT);
		$data_all[IpBlock::TYPE_POST] = IpBlock::logRead(IpBlock::TYPE_POST);
		$count[IpBlock::TYPE_LOGIN] = count($data_all[IpBlock::TYPE_LOGIN]);
		$count[IpBlock::TYPE_CONTACT] = count($data_all[IpBlock::TYPE_CONTACT]);
		$count[IpBlock::TYPE_POST] = count($data_all[IpBlock::TYPE_POST]);

		// type not set, use not empty tab else firts tab
		if ($type == 0)
		{
			$type = $count[IpBlock::TYPE_CONTACT] > 0 ? IpBlock::TYPE_CONTACT : IpBlock::TYPE_LOGIN;
		}

		switch ($type)
		{
			case IpBlock::TYPE_LOGIN:
				$title = __('Blocked login attempts');
				$url_settings = Language::get_url('admin/settingsSpam/#grp_ipblock_login_attempt');

				$group_by = array('ip', 'username');
				/**
				 * $data = array( array(ip=>'',username=>'',time=>''),...);
				 */
				$data = $data_all[IpBlock::TYPE_LOGIN];

				// get columns, first raw defines columns
				if (isset($data[0]))
				{
					//$cols = array_keys($data[0]);
					$cols = array_keys(get_object_vars($data[0]));
				}

				// group data if required
				if (!$group || !in_array($group, $group_by) || !in_array($group, $cols))
				{
					$group = null;
				}

				// reset counts
				IpBlock::clearExpired($type);
				// get currently blocked ips
				/*
				  $values = array($type, Config::option('ipblock_login_attempt_count'));
				  $ipblocks = IpBlock::findAllFrom('IpBlock', 'type=? AND num>=? LIMIT 500', $values);
				  $total = IpBlock::countFrom('IpBlock', 'type=? AND num>=?', $values); */
				break;
			case IpBlock::TYPE_CONTACT:
				$title = __('Blocked contact form usage');
				$url_settings = Language::get_url('admin/settingsSpam/#grp_ipblock_contact_limit');

				$group_by = array('ip', 'from_email', 'message', 'ad_id', 'from_user_id');

				/**
				 * $data = array( array(ip=>'',email=>'',message=>'',time=>''),...);
				 */
				$data = $data_all[IpBlock::TYPE_CONTACT];

				// get columns, first raw defines columns
				if (isset($data[0]))
				{
					$cols = array_keys(get_object_vars($data[0]));
				}

				// append users
				User::appendObject($data, 'from_user_id', 'User', 'id', 'from_User');

				// append ads
				Ad::appendObject($data, 'ad_id', 'Ad');

				if (!$group || !in_array($group, $group_by) || !in_array($group, $cols))
				{
					$group = null;
				}

				// reset counts
				IpBlock::clearExpired($type);
				// get currently blocked ips
				/*
				  $values = array($type, Config::option('ipblock_contact_limit_count'));
				  $ipblocks = IpBlock::findAllFrom('IpBlock', 'type=? AND num>=? LIMIT 500', $values);
				  $total = IpBlock::countFrom('IpBlock', 'type=? AND num>=?', $values);
				 */
				break;

			case IpBlock::TYPE_POST:
				$title = __('Throttled postings');
				$url_settings = Language::get_url('admin/settingsAds/#grp_ipblock_post_limit');

				$group_by = array('user_id', 'ip');

				/**
				 * $data = array( array(user_id=>'',ip=>'',time=>''),...);
				 */
				$data = $data_all[IpBlock::TYPE_POST];

				// get columns, first raw defines columns
				if (isset($data[0]))
				{
					$cols = array_keys(get_object_vars($data[0]));
				}

				// append users
				User::appendObject($data, 'user_id', 'User', 'id');


				if (!$group || !in_array($group, $group_by) || !in_array($group, $cols))
				{
					$group = null;
				}
				// reset counts
				IpBlock::clearExpired($type);

				break;
			default:
				// display list of logs 
				$title = __('Logs');
		}

		unset($data_all);

		$breadcrumb = array(
			array(__('Home'), Language::get_url('admin/')),
			array(__('Settings'), Language::get_url('admin/settings/')),
			array(__('Logs'), '')
		);

		$this->assignToLayout('breadcrumb', $breadcrumb);


		// if notlatest then appropriate message will be shown on index page anyway
		$this->display('admin/logs', array(
			'data'			 => $data,
			'count'			 => $count,
			'type'			 => $type,
			'cols'			 => $cols,
			'group_by'		 => $group_by,
			'group'			 => $group,
			'title'			 => $title,
			'url_settings'	 => $url_settings,
			'ipblocks'		 => $ipblocks,
			'total'			 => $total,
			'menu_selected'	 => 'admin/logs/',
		));
	}

	function duplicates()
	{
		// perform actions
		//$this->_ads();
		AuthUser::hasPermission(User::PERMISSION_ADMIN, false, true);

		if (strlen($_POST['del_ajax']))
		{
			// duplicate selected ads with ajax 
			$ids = explode(',', $_POST['del_ajax']);
			$return = Ad::statusDuplicateByIds($ids);
			echo $return ? 'ok' : 'error deleting ads:' . View::escape(implode(',', $ids));
			exit;
		}

		if (isset($_POST['last_id']))
		{
			// load ads and calculate duplicates 
			$matches = strlen($_POST['matches']) ? intval($_POST['matches']) : 5;
			$matches_max = strlen($_POST['matches_max']) ? intval($_POST['matches_max']) : 0;
			$perc = strlen($_POST['perc']) ? intval($_POST['perc']) : 90;
			$perc_deviation = strlen($_POST['perc_deviation']) ? intval($_POST['perc_deviation']) : 10;
			$diff = strlen($_POST['diff']) ? intval($_POST['diff']) : 10;
			$id = strlen($_POST['id']) ? intval($_POST['id']) : 0;
			$pending = intval($_POST['pending']);
			$owner = intval($_POST['owner']);
			$last_id = intval($_POST['last_id']);


			// load 30 ad per page 
			$num = 20;
			$check_same_user = true;

			// maximum number of checks in general per page load 
			$max_check = 5000;
			$limit_user = 100;
			if ($id || $pending)
			{
				// 1000 is too much when normalizing big text
				// $limit_user = 1000;
				$num = 10;
			}

			if ($matches < 5)
			{
				$matches = 5;
			}

			if ($matches_max < $matches)
			{
				$matches_max = 0;
			}

			$arr_enabled_keys = array(
				Ad::STATUS_PENDING_APPROVAL	 => true,
				Ad::STATUS_ENABLED			 => true,
				Ad::STATUS_PAUSED			 => true,
				Ad::STATUS_COMPLETED		 => true
			);
			$arr_enabled = Ad::quoteArray(array_keys($arr_enabled_keys));
			$str_enabled = "enabled IN (" . implode(',', $arr_enabled) . ")";

			$duplicatesObj = new stdClass();
			$duplicatesObj->matches = $matches;
			$duplicatesObj->matches_max = $matches_max;
			$duplicatesObj->perc = $perc;
			$duplicatesObj->perc_deviation = $perc_deviation;
			$duplicatesObj->diff = $diff;
			$duplicatesObj->num = $num;
			$duplicatesObj->owner = $owner;
			$duplicatesObj->max_check = $max_check;
			// keep log of performed checks 
			$duplicatesObj->ads_checked_bool = array();


			if ($id)
			{
				// check only given ad
				$ads = Ad::findAllFrom('Ad', "id=?", array($id));
			}
			else
			{
				$where = array();
				// get latest ads 
				if ($last_id)
				{
					$where[] = "id<" . $last_id;
				}

				if ($pending)
				{
					// pending approval
					$where[] = "enabled=" . Ad::STATUS_PENDING_APPROVAL;
				}
				else
				{
					// pending, enabled, paused, completed
					$where[] = $str_enabled;
				}

				if (!$where)
				{
					$where[] = "1=1";
				}

				// latest ads
				$ads = Ad::findAllFromUseIds('Ad', implode(' AND ', $where) . " ORDER BY id DESC LIMIT " . $num, array(), MAIN_DB, '*', 'id');
			}



			if (!$ads)
			{
				// finished loading all pages 
				echo 'END';
				exit;
			}


			// add sample set for each duplicate 
			$user_ads = array();
			// load user and title combination, used for users with more ads
			$user_ads_title_bool = array();

			$all_ads = array();
			$next_last_id = $last_id;
			foreach ($ads as $ad)
			{
				// get related ads
				AdRelated::append($ad, true);

				// get latest 100 ads from same user
				if ($check_same_user)
				{
					// load latest ads from same user
					if (!isset($user_ads[$ad->added_by]))
					{
						$user_ads_ = Ad::findAllFrom('Ad', 'added_by=? AND ' . $str_enabled . ' ORDER BY id DESC LIMIT ' . $limit_user, array($ad->added_by));
						$user_ads[$ad->added_by] = count($user_ads_);

						// set user ads to all 
						foreach ($user_ads_ as $ad_u)
						{
							if (!isset($all_ads[$ad_u->id]))
							{
								$all_ads[$ad_u->id] = $ad_u;
							}
						}
					}


					// load ads with booblean search title and description  for same user if has more ads to load for this user
					if ($user_ads[$ad->added_by] >= $limit_user)
					{
						/* this is used only for searching once as key here. */
						// loaded not all ads for this user, try loading ads with boolean search
						$q_n = TextTransform::text_normalize($ad->title . ' ' . $ad->description, 'search');
						// use simplified version 
						$q_n = TextTransform::text_normalize_simplify_search($q_n);

						// check if not already loaded and has $q_n value
						if (strlen($q_n) && !isset($user_ads_title_bool[$ad->added_by][$q_n]))
						{
							$user_ads_title_bool[$ad->added_by][$q_n] = true;

							$user_ads_ = Ad::findSimilar($ad, array(
										'same_user' => true
							));

							// set user ads to all
							foreach ($user_ads_ as $ad_u)
							{
								if (!isset($all_ads[$ad_u->id]))
								{
									$all_ads[$ad_u->id] = $ad_u;
								}
							}
						}
					}
				}
				if ($next_last_id > $ad->id || $next_last_id == 0)
				{
					$next_last_id = $ad->id;
				}

				// set related to all ads 
				$arr_rel_indexed = array();
				foreach ($ad->related as $ad_rel)
				{
					if (!isset($all_ads[$ad_rel->id]))
					{
						$all_ads[$ad_rel->id] = $ad_rel;
					}
					$arr_rel_indexed[$ad_rel->id] = true;
				}
				unset($ad->related);
				$ad->related_index = $arr_rel_indexed;


				if (isset($all_ads[$ad->id]))
				{
					// keep related index
					$all_ads[$ad->id]->related_index = $ad->related_index;
				}
				else
				{
					$all_ads[$ad->id] = $ad;
				}
				unset($arr_rel_indexed);
				unset($user_ads_);
			}
			// will set it later
			unset($user_ads);
			unset($user_ads_title_bool);

			Benchmark::cp();
			$loop_count = 0;
			$arr_unset = array();
			$total_words = 0;
			// done loading now normalize text for each ad
			foreach ($all_ads as $ad)
			{
				if (isset($arr_enabled_keys[$ad->enabled]))
				{
					$loop_count++;

					$ad->normlized = TextTransform::text_normalize($ad->title . ' ' . $ad->description);
					// check for minimum and unset ubject if len is smaller than required 
					$arr = explode(' ', $ad->normlized);
					$arr_cnt = count($arr);
					$total_words += $arr_cnt;
					if ($arr_cnt < $matches)
					{
						// content is too small to be cheked. remove it 
						$arr_unset[$ad->id] = true;
					}
				}
				else
				{
					// should not be checked status not (enabled, paused, completed, or pending) 
					$arr_unset[$ad->id] = true;
				}
			}
			Benchmark::cp('Normalize loop:' . $loop_count);
			Benchmark::cp('$arr_unset:' . count($arr_unset));
			Benchmark::cp('$total_words:' . $total_words);

			// iterate and remove skipped records
			foreach ($arr_unset as $ad_id => $val)
			{
				unset($all_ads[$ad_id]);
			}
			unset($arr_unset);

			Benchmark::cp('$all_ads:' . count($all_ads));

			// create user_ads array 
			$user_ads = array();
			foreach ($all_ads as $ad)
			{
				$user_ads[$ad->added_by][$ad->id] = $ad;
			}

			// recreate initial ads array 
			$new_ads = array();
			foreach ($ads as $ad)
			{
				if (isset($all_ads[$ad->id]))
				{
					// set initial ad from all ads so it uses only one ad reference eveywhere 
					$new_ads[$ad->id] = $all_ads[$ad->id];
				}
			}
			$ads = $new_ads;
			unset($new_ads);
			//unset($all_ads);


			Benchmark::cp();

			// now check similarity 
			foreach ($ads as $ad)
			{
				// check similarity 
				$ads_check = array();
				// check with related
				if ($ad->related_index)
				{
					foreach ($ad->related_index as $ad_id => $val)
					{
						if (isset($all_ads[$ad_id]))
						{
							$ads_check[$ad_id] = $all_ads[$ad_id];
						}
					}
				}
				// check with same user
				if (isset($user_ads[$ad->added_by]))
				{
					$ads_check = array_merge($ads_check, $user_ads[$ad->added_by]);
				}

				$duplicatesObj->check_ad = $ad;
				$duplicatesObj->check_against = $ads_check;
				// perform check 
				Ad::checkDuplicates($duplicatesObj, 'one_to_many');

				// free up some meory 
				//unset($ad->related);
				unset($ads_check);
			}
			//unset($user_ads);
			Benchmark::cp('Main similarity check loop:' . count($duplicatesObj->ads_checked_bool));


			if (count($duplicatesObj->ads_checked_bool) < $duplicatesObj->max_check && !$pending)
			{
				// check related items for each other similarity 
				Benchmark::cp();
				foreach ($ads as $ad)
				{
					if (count($duplicatesObj->ads_checked_bool) > $duplicatesObj->max_check)
					{
						break;
					}

					$duplicatesObj->check_against = array();
					if ($ad->related_index)
					{
						foreach ($ad->related_index as $ad_id => $val)
						{
							if (isset($all_ads[$ad_id]))
							{
								$duplicatesObj->check_against[$ad_id] = $all_ads[$ad_id];
							}
						}
					}
					Ad::checkDuplicates($duplicatesObj, 'array');
				}
				Benchmark::cp('Related loop:' . count($duplicatesObj->ads_checked_bool));


				// if we still have some loops left then check for same owner ads similarity 
				if (count($duplicatesObj->ads_checked_bool) < $duplicatesObj->max_check)
				{
					foreach ($user_ads as $key => $arr_ads)
					{
						if (count($duplicatesObj->ads_checked_bool) > $duplicatesObj->max_check)
						{
							break;
						}
						$duplicatesObj->check_against = $arr_ads;
						Ad::checkDuplicates($duplicatesObj, 'array');
					}
					Benchmark::cp('User loop:' . count($duplicatesObj->ads_checked_bool));

					// if we still have some loops left then check for all ads
					if (count($duplicatesObj->ads_checked_bool) < $duplicatesObj->max_check)
					{
						$duplicatesObj->check_against = $all_ads;
						Ad::checkDuplicates($duplicatesObj, 'array');
						Benchmark::cp('ALL loop:' . count($duplicatesObj->ads_checked_bool));
					}
				}
			}

			// show checked ids			
			//Benchmark::cp('ads_checked_bool:' . implode(',', array_keys($duplicatesObj->ads_checked_bool)));
			// check all ads now for found duplicates
			$ads = array();
			foreach ($all_ads as $ad)
			{
				if (isset($ad->duplicates))
				{
					$ad->num = count($ad->duplicates);
					$ads[$ad->id] = $ad;
				}
				unset($ad->related);
			}

			unset($duplicatesObj);
			unset($all_ads);
			unset($user_ads);

			// add to append images
			$all_ads = $ads;
			foreach ($ads as $ad)
			{
				$all_ads = array_merge($all_ads, $ad->duplicates);
			}

			Ad::appendAdpics($all_ads, true);

			// return rendered result 
			$this->setLayout(false);
			$this->display('admin/duplicates', array(
				'ads'			 => $ads,
				'next_last_id'	 => $next_last_id,
				'response_type'	 => 'ajax'
			));
			exit;
		}

		$this->display('admin/duplicates', array(
			'menu_selected' => 'admin/duplicates/',
		));
	}

	function testFulltext()
	{
		AdFulltext::cronProcess();
		$this->validation()->set_success('testFulltext:done');
		return $this->index();
	}

	/**
	 * return json data={text:"10 items left|completed",continue:0|1}
	 */
	function batchFulltext()
	{
		// check if admin 
		$this->_hasPermissionAjax(User::PERMISSION_ADMIN, false, true);

		// convert and update status
		$left_count = AdFulltext::status(true);
		if ($left_count == 0)
		{
			// completed 
			$return = array(
				'text'		 => __('Completed'),
				'continue'	 => 0
			);
		}
		else
		{
			$return = array(
				'text'		 => __('Converting ({num} records left)', array(
					'{num}' => $left_count
				)),
				'continue'	 => 1
			);
		}

		$this->displayJSON(Config::arr2js($return));
		exit;
	}

}
