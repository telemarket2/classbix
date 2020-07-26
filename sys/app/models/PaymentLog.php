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
 * class PaymentLog
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class PaymentLog extends Record
{

	const TABLE_NAME = 'payment_log';

	private static $cols = array(
		'id' => 1,
		'txnid' => 1,
		'ad_id' => 1,
		'itemname' => 1,
		'itemnumber' => 1,
		'amount' => 1,
		'fee' => 1,
		'currency' => 1,
		'paidtoemail' => 1,
		'payeremail' => 1,
		'paymenttype' => 1,
		'success' => 1,
		'paymentstatus' => 1,
		'pendingreason' => 1,
		'ipndata' => 1,
		'message' => 1,
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
		if(!$this->added_by)
		{
			$this->added_by = AuthUser::$user->id;
		}
		$this->added_at = REQUEST_TIME;
		
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

	public static function mapPaypalStandartIpn($paypal, $success = 0, $message = '')
	{
		// save log using post data 
		// convert post data fields to db fields

		return array(
			'txnid' => $paypal->posted_data['txn_id'],
			'ad_id' => $paypal->item->Ad->id,
			'itemname' => $paypal->posted_data['item_name'],
			'itemnumber' => $paypal->posted_data['item_number'],
			'amount' => $paypal->posted_data['mc_gross'],
			'fee' => $paypal->posted_data['mc_fee'],
			'currency' => $paypal->posted_data['mc_currency'],
			'paidtoemail' => $paypal->posted_data['business'],
			'payeremail' => $paypal->posted_data['payer_email'],
			'paymenttype' => $paypal->posted_data['payment_type'],
			'success' => intval($success),
			'paymentstatus' => $paypal->posted_data['payment_status'],
			'pendingreason' => $paypal->posted_data['pending_reason'],
			'ipndata' => serialize($paypal->posted_data),
			'message' => $message,
			'added_by' => $paypal->item->Ad->added_by,
		);
	}

}

