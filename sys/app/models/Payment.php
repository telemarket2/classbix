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
class Payment extends Record
{

	const TABLE_NAME = 'payment';
	const ITEM_TYPE_POST = 1;
	const ITEM_TYPE_FEATURED = 2;
	const ITEM_TYPE_FEATURED_REQUESTED = 3;
	const CURRENCY = 'USD';

	public static $error_msg = array();
	public static $currencies = array(
		'AUD',
		'BRL',
		'CAD',
		'CZK',
		'DKK',
		'EUR',
		'HKD',
		'HUF',
		'ILS',
		'JPY',
		'MYR',
		'MXN',
		'NOK',
		'NZD',
		'PHP',
		'PLN',
		'GBP',
		'SGD',
		'SEK',
		'CHF',
		'TWD',
		'THB',
		'TRY',
		'USD'
	);
	private static $cols = array(
		'id' => 1,
		'ad_id' => 1,
		'payment_log_id' => 1,
		'item_type' => 1,
		'amount' => 1,
		'currency' => 1,
		'added_at' => 1,
		'added_by' => 1
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

	public static function formatAmount($amount = 0, $currency = null)
	{
		if(is_null($currency))
		{
			$currency = self::getCurrency();
		}
		$amount = $amount + 0;
		$amount = number_format($amount, 2);
		return $amount . $currency;
	}

	public static function getCurrency()
	{
		$currency = Config::option('paypal_currency');
		if(!$currency)
		{
			$currency = self::CURRENCY;
		}
		return $currency;
	}

	/**
	 * check if has unfinished featured ad request
	 * @param type $ad 
	 */
	public static function hasFeaturedRequest($ad)
	{
		if(!isset($ad->price_featured_requested))
		{
			$price_featured_requested = Payment::findOneFrom('Payment', 'ad_id=? AND item_type=?', array($ad->id, Payment::ITEM_TYPE_FEATURED_REQUESTED));
			$ad->price_featured_requested = $price_featured_requested ? 1 : 0;
		}

		return $ad->price_featured_requested;
	}

	/**
	 * Save all costom field values to AdFieldRelation. delete values with empty string
	 * @param type $post
	 * @param type $catfields 
	 */
	public static function saveFeaturedRequest($ad)
	{
		// if price featured selected when adding ad then save this as request 
		if($ad->price_featured_requested)
		{
			// insert only if dont have already requested
			$price_featured_requested = Payment::findOneFrom('Payment', 'ad_id=? AND item_type=?', array($ad->id, Payment::ITEM_TYPE_FEATURED_REQUESTED));
			if(!$price_featured_requested)
			{
				$payment = new Payment();
				$payment->ad_id = $ad->id;
				$payment->item_type = Payment::ITEM_TYPE_FEATURED_REQUESTED;
				$payment->save();
			}

			// clear old requests
			self::clearOldFeaturedRequests();
		}
	}

	/**
	 * clear 7 day old featured payment request
	 * 
	 * @return bool 
	 */
	public static function clearOldFeaturedRequests()
	{
		// clear 7 day old request to keep db clean
		return Payment::deleteWhere(Payment, 'item_type=? AND added_at<?', array(Payment::ITEM_TYPE_FEATURED_REQUESTED, REQUEST_TIME - 3600 * 24 * 7));
	}

	/**
	 * create form for payemnt and send to paypal 
	 * 
	 * @param Ad $ad
	 * @param string $item_name
	 * @param string $item_number
	 * @param double $amount 
	 */
	public static function sendToPayment($ad, $item_name, $item_number, $amount)
	{
		$PAYPAL_MAIL = Config::option('paypal_email');

		$paypal = new PaypalStandart();

		$paypal->price = $amount;
		$paypal->ipn = Language::get_url('post/payment/ipn/'); //full web address to IPN script

		$paypal->enable_payment();

		if(Config::option('paypal_sandbox'))
		{
			$paypal->sandbox = true;
		}

		//change currency code
		$paypal->add('currency_code', self::getCurrency());

		//your paypal email address
		$paypal->add('business', $PAYPAL_MAIL);

		$paypal->add('item_name', $item_name);
		$paypal->add('item_number', $item_number);
		$paypal->add('quantity', 1);
		$paypal->add('return', Language::get_url('post/payment/'));
		$paypal->add('cancel_return', Language::get_url('post/payment/cancel/'));
		$paypal->output_form();
		exit();
	}

	/**
	 * check if ad has any payment required, send to payment page 
	 *
	 * @param Ad $ad 
	 */
	public static function processPayment($ad)
	{
		/**
		  - paid cateogry:
		  validate email first
		  send to payment
		  make ad listed
		  if expires then delete ad.
		  - featured ad:
		  validate email
		  publish ad
		  send to payment
		  if completed make featured from payment completion date
		 */
		$obj_item_number = self::itemNumberBuild($ad);

		// send to payment page 
		if($obj_item_number->amount > 0)
		{
			self::sendToPayment($ad, $obj_item_number->item_name, $obj_item_number->item_number, $obj_item_number->amount);
		}
	}

	/**
	 * build istem number for given ad 
	 * 
	 * @param Ad $ad
	 * @return \stdClass item_name,item_number(ad_id|payment_item_type|...), amount
	 */
	public static function itemNumberBuild($ad)
	{
		$return = new stdClass();

		PaymentPrice::appendPaymentPrice($ad);
		$amount = 0;
		$arr_item_name = array();
		$arr_item_amount = array();
		$arr_item_number = array();

		// add item id first, then which option is paid for seperated by |
		$arr_item_number[] = $ad->id;
		$arr_item_name[] = DOMAIN . ' - ';

		if($ad->PaymentPrice->price_post > 0 && $ad->requires_posting_payment)
		{
			// need to pay to post 
			$arr_item_name[] = self::itemTypeName(Payment::ITEM_TYPE_POST);
			$arr_item_amount[] = $ad->PaymentPrice->price_post;
			$arr_item_number[] = Payment::ITEM_TYPE_POST;
			$send_to_payment = true;
			$amount += $ad->PaymentPrice->price_post;
		}

		if($ad->PaymentPrice->price_featured > 0 && Payment::hasFeaturedRequest($ad))
		{
			// need to pay to post 
			$arr_item_name[] = self::itemTypeName(Payment::ITEM_TYPE_FEATURED);
			$arr_item_amount[] = $ad->PaymentPrice->price_featured;
			$arr_item_number[] = Payment::ITEM_TYPE_FEATURED;
			$send_to_payment = true;
			$amount += $ad->PaymentPrice->price_featured;
		}

		$return->item_name = implode(' ', $arr_item_name);
		$return->item_number = implode('|', $arr_item_number);
		$return->amount = $amount;

		return $return;
	}

	/**
	 * return name of item type
	 * 
	 * @param type $item_type
	 * @return string 
	 */
	public static function itemTypeName($item_type)
	{
		switch($item_type)
		{
			case self::ITEM_TYPE_POST:
				$return = __('Ad posting fee.');
				break;
			case self::ITEM_TYPE_FEATURED:
				$return = __('Featured price');
				break;
			case self::ITEM_TYPE_FEATURED_REQUESTED:
				$return = __('Featured ad request.');
				break;
			default:
				$return = '(' . View::escape($item_type) . ')';
				break;
		}

		return $return;
	}

	/**
	 * parses item number and gets ad and what is paid for
	 * 
	 * @param PaypalStandart $paypal 
	 * @return \stdClass Ad, amount, payment_post, payment_featured
	 */
	public static function itemNumberParse($paypal)
	{
		$paypal->item = new stdClass();
		$amount = 0;

		// string $item_number (ad_id|payment_item_type|...)
		$item_number = $paypal->posted_data['item_number'];

		// start parsing 
		$arr_item_number = explode('|', $item_number);
		$ad_id = array_shift($arr_item_number);

		// find ad
		$ad = Ad::findByIdFrom('Ad', $ad_id);

		// calculate amount 
		if($ad)
		{
			// ad found append price
			PaymentPrice::appendPaymentPrice($ad);

			// check price and item_type 
			foreach($arr_item_number as $item_type)
			{
				switch($item_type)
				{
					case Payment::ITEM_TYPE_POST:
						if($ad->PaymentPrice->price_post > 0)
						{
							// need to pay to p1#
							// ost 
							$amount += $ad->PaymentPrice->price_post;
							// record done payments 
							$paypal->item->payment_post = $ad->PaymentPrice->price_post;
						}
						break;
					case Payment::ITEM_TYPE_FEATURED:
						if($ad->PaymentPrice->price_featured > 0)
						{
							// payement for featured
							$amount += $ad->PaymentPrice->price_featured;
							// record done payments 
							$paypal->item->payment_featured = $ad->PaymentPrice->price_featured;
						}
						break;
				}
			}
		}

		$paypal->item->Ad = $ad;
		$paypal->item->amount = $amount;

		return $paypal->item;
	}

	public static function processIPN()
	{
		$msg = array();
		$msg[] = '[processIPN]';

		$paypal = new PaypalStandart();
		if(Config::option('paypal_sandbox'))
		{
			$paypal->sandbox = true;
		}

		// optionally enable logging
		//$paypal->log = 1;
		//$paypal->logfile = FROG_ROOT . '/user-content/paypal-ipn-log.txt';
		//if you are dealing with subscriptions this must be called first
		$paypal->ignore_type = array('subscr_signup');

		if($paypal->validate_ipn()) 
		{
			$msg[] = '[processIPN: valid]';
			// ipn valid
			if($paypal->payment_success == 1)
			{
				$msg[] = '[processIPN: payment_success==1] ';
				// payment is successfull, payment_status is Completed
				//
				// 
				// Check that txn_id has not been previously processed
				// Check that receiver_email is your Primary PayPal email
				// Check that payment_amount/payment_currency are correct
				// 
				// 
				// use the item id to identify for which product the payment was made
				$success = 0;

				// parse item number and get vals
				self::itemNumberParse($paypal);

				$ad = self::_checkItem($paypal);
				if($ad)
				{
					$msg[] = '[processIPN: ad_found]';

					// check if already completed 
					if(self::_checkTXNID($paypal))
					{
						return $paypal;
					}

					// check other criteria
					if(self::_checkCurrency($paypal) && self::_checkReceiverEmail($paypal) && self::_checkAmount($paypal, $ad))
					{
						// update db with this request 
						$success = 1;
						$msg[] = '[processIPN: success true] ';

						if($paypal->item->payment_post > 0)
						{
							$msg[] = '[processIPN: payment_post=' . $paypal->item->payment_post . '] ';
						}
						if($paypal->item->payment_featured > 0)
						{
							$msg[] = '[processIPN: payment_featured=' . $paypal->item->payment_featured . '] ';
						}


						// update payments to db 
						// record this transaction first 
						// if paid for post then publish item
						// if paid for featured make ad featured
						// record this transaction first 
						$payment_log = new PaymentLog(PaymentLog::mapPaypalStandartIpn($paypal, $success, implode(' ', $msg)));
						// print_r($payment_log);
						if($payment_log->save())
						{
							// apply paid actions 
							if($paypal->item->payment_post > 0)
							{
								self::insertPaymentPost($paypal, $payment_log);
							}
							if($paypal->item->payment_featured > 0)
							{
								// paid for featured
								self::insertPaymentFeatured($paypal, $payment_log);
							}
							return $paypal;
						}
						else
						{
							$msg[] = '[processIPN: error saving PaymentLog] ';
						}
					}
				}

				if(!$success)
				{
					$msg[] = '[processIPN: success false] ';

					// log error when validating with local data
					$payment_log = new PaymentLog(PaymentLog::mapPaypalStandartIpn($paypal, $success, implode(' ', $msg)));
					$payment_log->save();
				}
			}
			else
			{
				$msg[] = '[processIPN: payment_success!=1] ';
				//payment not successful and/or subcsription cancelled
			}
		}
		else
		{
			//not valid PIPN  log
			$msg[] = '[processIPN: not valid ipn] ';
		}

		if($paypal->error)
		{
			$msg[] = '[paypal->error:' . $paypal->error . ']';
		}

		$errors = self::displayErrors('', ' ');

		// store error in database table _log 
		Config::log(array(
			'paypal' => $paypal,
			'errors' => $errors,
			'_POST' => $_POST,
			'msg' => $msg,
			'action' => 'Payment::processIPN'
				), 'paypal_ipn');

		return false;
	}

	/**
	 * check if this txnid recorded then return false, 
	 * because each transaction has unique id and it is processed already, do not mark payments more than once.
	 * 
	 * @param PaypalStandart $paypal
	 * @return boolean 
	 */
	private static function _checkTXNID($paypal)
	{
		$txnid = $paypal->posted_data['txn_id'];
		$payment_log = PaymentLog::findByIdFrom('PaymentLog', $txnid, 'txnid');

		$is_completed = $payment_log ? true : false;

		self::setError('_checkTXNID:' . ($is_completed ? 'true' : 'false') . ':txn_id:' . View::escape($txnid));

		return $is_completed;
	}

	/**
	 * check if business email matches
	 * 
	 * @param PaypalStandart $paypal
	 * @return boolean 
	 */
	private static function _checkReceiverEmail($paypal)
	{
		$return = ($paypal->posted_data['business'] == Config::option('paypal_email'));
		self::setError('_checkReceiverEmail:' . ($return ? 'true' : 'false') . ':(' . View::escape($paypal->posted_data['business'] . ' == ' . Config::option('paypal_email')) . ')');

		return $return;
	}

	/**
	 * check if this item Ad exists in DB
	 * 
	 * @param PaypalStandart $paypal
	 * @return Ad |false
	 */
	private static function _checkItem($paypal)
	{
		if(!$paypal->item->Ad)
		{
			self::setError('_checkItem:' . ($paypal->item->Ad ? 'true' : 'false'));
		}

		return $paypal->item->Ad;
	}

	/**
	 * check if amount matches required amount
	 * 
	 * @param PaypalStandart $paypal
	 * @param Ad $ad
	 * @return boolean 
	 */
	private static function _checkAmount($paypal)
	{
		$amount_posted = $paypal->posted_data['mc_gross'];
		$ad = $paypal->item->Ad;

		if($ad)
		{
			if($amount_posted == $paypal->item->amount)
			{
				return true;
			}
			self::setError('_checkAmount:false:(' . View::escape($amount_posted . ' == ' . $paypal->item->amount) . ')');
		}
		else
		{
			self::setError('_checkAmount: ad record not found');
		}

		return false;
	}

	/**
	 * check if currency matches
	 * 
	 * @param PaypalStandart $paypal
	 * @return boolean 
	 */
	private static function _checkCurrency($paypal)
	{
		$currency = $paypal->posted_data['mc_currency'];
		$return = $currency == self::getCurrency();

		self::setError('_checkCurrency:' . ($return ? 'true' : 'false') . ':(' . View::escape($currency . ' == ' . self::getCurrency()) . ')');

		return $return;
	}

	/**
	 * Set an error message
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	private static function setError($msg)
	{
		self::$error_msg[] = $msg;
	}

	/**
	 * Display the error message
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @return  string
	 */
	public static function displayErrors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach(self::$error_msg as $val)
		{
			$str .= $open . $val . $close;
		}

		return $str;
	}

	/**
	 * insert record to Payment as posting paid
	 * 
	 * @param paypal $paypal
	 * @param PaymentLog $payment_log
	 * @return bool 
	 */
	public static function insertPaymentPost($paypal, $payment_log)
	{
		$ad = $paypal->item->Ad;

		// remove payment reqirement for ad 
		$ad->markAsPaidToPost();

		// insert payment record
		$payment = new Payment();
		$payment->ad_id = $ad->id;
		$payment->payment_log_id = $payment_log->id;
		$payment->item_type = self::ITEM_TYPE_POST;
		$payment->amount = $paypal->item->payment_post;
		$payment->currency = $paypal->posted_data['mc_currency'];

		return $payment->save();
	}

	/**
	 * insert record to Payment as posting paid
	 * 
	 * @param paypal $paypal
	 * @param PaymentLog $payment_log
	 * @return bool 
	 */
	public static function insertPaymentFeatured($paypal, $payment_log)
	{
		// make this ad featured
		Ad::makeFeaturedByIds($paypal->item->Ad->id);

		// insert payment record
		$payment = new Payment();
		$payment->ad_id = $paypal->item->Ad->id;
		$payment->payment_log_id = $payment_log->id;
		$payment->item_type = self::ITEM_TYPE_FEATURED;
		$payment->amount = $paypal->item->payment_featured;
		$payment->currency = $paypal->posted_data['mc_currency'];

		return $payment->save();
	}

}
