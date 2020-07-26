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
 * class Payment
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class PaymentPrice extends Record
{

	const TABLE_NAME = 'payment_price';

	private static $prices = array();
	private static $prices_greedy = array();
	private static $all_prices_loaded = 0; // 0: not loaded, 1: counted and loaded, 2: counted but not loaded
	private static $cols = array(
		'location_id' => 1,
		'category_id' => 1,
		'price_featured' => 1,
		'price_post' => 1,
		'added_at' => 1,
		'added_by' => 1,
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

	public static function paymentsToday()
	{
		return self::countFrom('Payment', 'TO_DAYS(added_at) = TO_DAYS(NOW())', array());
	}

	public static function paymentsTodayAmount()
	{
		$sql = "SELECT SUM(amount) as num
				FROM " . self::tableNameFromClassName('Payment') . "  
				WHERE TO_DAYS(added_at) = TO_DAYS(NOW())";
		$r = self::queryOne($sql, array());
		return $r->num;
	}

	/**
	 * get price for given category and location
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @param bool $append related objects: Location, Category
	 * @param bool $greedy search for parents
	 * @return PaymentPrice
	 */
	public static function getPrice($location_id, $category_id, $append = false, $greedy = false)
	{
		$location_id = intval($location_id);
		$category_id = intval($category_id);

		// check if not already requested 
		if(!isset(self::$prices[$location_id][$category_id]))
		{
			// check if previusly not loaded all prices 
			if(self::$all_prices_loaded != 1)
			{
				// get fields from db
				self::$prices[$location_id][$category_id] = PaymentPrice::findOneFrom('PaymentPrice', "location_id=? AND category_id=?", array($location_id, $category_id));
			}

			if(!self::$prices[$location_id][$category_id])
			{
				self::$prices[$location_id][$category_id] = array();
			}
		}

		$payment_price = self::$prices[$location_id][$category_id];

		if(!$payment_price && $greedy)
		{
			$payment_price = self::_getPriceGreedy($location_id, $category_id);
		}

		if($payment_price && $append)
		{
			// append category location and fields , groups
			Location::appendLocation($payment_price);
			Category::appendCategory($payment_price);
		}

		return $payment_price;
	}

	/**
	 * check parent location and categories to find price
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @return PaymentPrice 
	 */
	static private function _getPriceGreedy($location_id, $category_id)
	{
		if(!isset(self::$prices_greedy[$location_id][$category_id]))
		{
			// get prices for all parents
			$whereParents = CategoryFieldRelation::buildWhereParentLocationCategory($location_id, $category_id);

			// if there less than 100 prces then load them all instead of this query.
			// it is more efficeint when used in loop for checking if payment possible 			
			self::loadAllPricesIfPossible();

			if(self::$all_prices_loaded != 1)
			{
				// counted but not loaded then load selective 
				$all_parent_prices = PaymentPrice::findAllFrom('PaymentPrice', $whereParents->where, $whereParents->where_vals);

				foreach($all_parent_prices as $cf)
				{
					if(!self::$prices[$cf->location_id][$cf->category_id])
					{
						self::$prices[$cf->location_id][$cf->category_id] = $cf;
					}
				}
			}




			$arr_set_later_greedy = array();

			foreach($whereParents->arr_loc_id as $l_id)
			{
				foreach($whereParents->arr_cat_id as $c_id)
				{
					if(!isset(self::$prices_greedy[$location_id][$category_id]))
					{
						//echo '(check_price['.$l_id.']['.$c_id.'])<br/>';
						if(self::$prices[$l_id][$c_id])
						{
							self::$prices_greedy[$location_id][$category_id] = self::$prices[$l_id][$c_id];
						}
						else
						{
							$arr_set_later_greedy[$l_id][$c_id] = array();
						}
					}
				}
			}

			if(!isset(self::$prices_greedy[$location_id][$category_id]))
			{
				// not found then empty array
				self::$prices_greedy[$location_id][$category_id] = array();
			}


			// set all previus values to lastly found value 
			/* foreach($arr_set_later_greedy as $l_id => $v)
			  {
			  foreach($v as $c_id => $val)
			  {
			  self::$prices_greedy[$l_id][$c_id] = self::$prices_greedy[$location_id][$category_id];
			  }
			  } */
		}

		return self::$prices_greedy[$location_id][$category_id];
	}

	/**
	 * load all prices if less than 100 records. 
	 * this is usefull if it is requested in loop 
	 * 
	 * @return type 
	 */
	public static function loadAllPricesIfPossible()
	{
		if(self::$all_prices_loaded == 0)
		{
			// not counted then count 
			$count = PaymentPrice::countFrom('PaymentPrice');
			// set as counted
			self::$all_prices_loaded = 2;
			if($count <= 100)
			{
				// load all 
				$all_parent_prices = PaymentPrice::findAllFrom('PaymentPrice');
				// set as counted and loaded
				self::$all_prices_loaded = 1;

				foreach($all_parent_prices as $cf)
				{
					if(!self::$prices[$cf->location_id][$cf->category_id])
					{
						self::$prices[$cf->location_id][$cf->category_id] = $cf;
					}
				}
			}
		}

		return self::$all_prices_loaded;
	}

	/**
	 * Deletes price
	 * 
	 * @param int $location_id
	 * @param int $category_id
	 * @return bool 
	 */
	public static function deletePrice($location_id, $category_id)
	{
		// maybe need to check pending payment ads and inform them or publish ads		
		$payment_price = PaymentPrice::deleteWhere('PaymentPrice', "location_id=? AND category_id=?", array(intval($location_id), intval($category_id)));

		return $payment_price;
	}

	/**
	 * save new PaymentPrice
	 * 
	 * @return PaymentPrice
	 */
	public static function savePrice($data = array())
	{
		self::deletePrice($data['location_id'], $data['category_id']);
		$payment_price = new PaymentPrice($data);
		$payment_price->save('new_id');
		return $payment_price;
	}

	/**
	 * Append greedy PaymentPrice
	 *
	 * @param Ad $ad 
	 */
	public static function appendPaymentPrice($ad)
	{
		// check if payment is enabled 
		if(!isset($ad->PaymentPrice) && Config::option('enable_payment'))
		{
			$ad->PaymentPrice = PaymentPrice::getPrice($ad->location_id, $ad->category_id, false, true);
		}
	}

	/**
	 * append greedy PaymentPrice to all ads. Also add payment_available value
	 * @param array $ads Ad
	 */
	public static function appendPaymentPriceAll($ads)
	{
		// load all prices if possible to decude db query count 
		PaymentPrice::loadAllPricesIfPossible();
		foreach($ads as $ad)
		{
			self::appendPaymentPrice($ad);
			$ad->payment_available = Ad::isPaymentAvailable($ad);
		}
	}

}

