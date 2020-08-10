<?php
/**
 *  
 */
class PaypalStandartOld
{

	public $production = "0";
	public $production_url = "https://www.paypal.com/cgi-bin/webscr";
	public $production_main_url = "www.paypal.com";
	public $sandbox_url = "https://sandbox.paypal.com/cgi-bin/webscr";
	public $sandbox_main_url = "sandbox.paypal.com";
	public $cmd = "_xclick";
	public $amount = "";
	public $notify_url = "";
	public $business = "";
	public $currency_code = "USD";
	public $invoice = "";
	public $item_name = "";
	public $image_url = "";
	public $return = "";
	public $cancel_return = "";
	public $first_name = "";
	public $last_name = "";
	public $address1 = "";
	public $address2 = "";
	public $city = "";
	public $state = "";
	public $zip = "";
	public $country = "";
	public $email = "";
	public $no_note = "1";
	public $no_shipping = "1";
	// email address to report all ipn requests
	public $admin_ipn_email = "";
	// error messages 
	private $error_msg = array();

	/* Settings module */

	private function convert_to_url()
	{
		$url = "cmd=" . urlencode($this->cmd) . "&"
				. "amount=" . urlencode($this->amount) . "&"
				. "notify_url=" . urlencode($this->notify_url) . "&"
				. "business=" . urlencode($this->business) . "&"
				. "currency_code=" . urlencode($this->currency_code) . "&"
				. "invoice=" . urlencode($this->invoice) . "&"
				. "item_name=" . urlencode($this->item_name) . "&"
				. "image_url=" . urlencode($this->image_url) . "&"
				. "return=" . urlencode($this->return) . "&"
				. "cancel_return=" . urlencode($this->cancel_return) . "&"
				. "first_name=" . urlencode($this->first_name) . "&"
				. "last_name=" . urlencode($this->last_name) . "&"
				. "address1=" . urlencode($this->address1) . "&"
				. "address2=" . urlencode($this->address2) . "&"
				. "city=" . urlencode($this->city) . "&"
				. "state=" . urlencode($this->state) . "&"
				. "zip=" . urlencode($this->zip) . "&"
				. "country=" . urlencode($this->country) . "&"
				. "email=" . urlencode($this->email) . "&"
				. "no_note=" . urlencode($this->no_note) . "&"
				. "no_shipping=" . urlencode($this->no_shipping) . "&";

		return $url;
	}

	public function process_single()
	{
		if($this->production == "0")
		{
			$main_url = $this->sandbox_url;
		}
		else
		{
			$main_url = $this->production_url;
		}

		redirect($main_url . "?" . $this->convert_to_url());
	}

	public function notify()
	{

		if($this->production == "0")
		{
			$url = $this->sandbox_main_url;
		}
		else
		{
			$url = $this->production_main_url;
		}

		error_reporting(E_ALL ^ E_NOTICE);
		$header = "";
		// Read the post from PayPal and add 'cmd' 
		$req = 'cmd=_notify-validate';
		$get_magic_quotes_exits = false;
		if(function_exists('get_magic_quotes_gpc'))
		{
			$get_magic_quotes_exits = true;
		}

		foreach($_POST as $key => $value)
		// Handle escape characters, which depends on setting of magic quotes 
		{
			if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1)
			{
				$value = urlencode(stripslashes($value));
			}
			else
			{
				$value = urlencode($value);
			}
			$req .= "&$key=$value";
		}
		// Post back to PayPal to validate 
		$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
		$fp = fsockopen('ssl://' . $url, 443, $errno, $errstr, 30);

		// Process validation from PayPal 
		// TODO: This sample does not test the HTTP response code. All 
		// HTTP response codes must be handles or you should use an HTTP // library, such as cUrl

		if(!$fp)
		{
			// HTTP ERROR 
			$this->setError('Paypal HTTP connection error');
			return false;
		}
		else
		{
			// NO HTTP ERROR 
			fputs($fp, $header . $req);

			while(!feof($fp))
			{
				$res = fgets($fp, 1024);
				if(strcmp($res, "VERIFIED") == 0)
				{
					// TODO: 
					// Check the payment_status is Completed
					// Check that txn_id has not been previously processed
					// Check that receiver_email is your Primary PayPal email
					// Check that payment_amount/payment_currency are correct
					// 
					// Process payment 
					// If 'VERIFIED', send an email of IPN variables and values to the specified email address


					$this->mail("Live-VERIFIED IPN", $req);

					return true;
				}
				else if(strcmp($res, "INVALID") == 0)
				{
					// If 'INVALID', send an email. TODO: Log for manual investigation. 				
					$this->mail("Live-INVALID IPN", $req);

					$this->setError('Invalid Paypal IPN');
					return false;
				}
			}

			fclose($fp);
		}
		return false;
	}

	function mail($subject, $req = '')
	{
		if($this->admin_ipn_email)
		{
			$emailtext = "";
			foreach($_POST as $key => $value)
			{
				$emailtext .= $key . " = " . $value . "\n\n";
			}

			mail($this->admin_ipn_email, $subject, $emailtext . "\n\n" . $req);
		}
	}

	/**
	 * Set an error message
	 *
	 * @access  public
	 * @param   string
	 * @return  void
	 */
	function setError($msg)
	{
		$this->error_msg[] = $msg;
	}

	/**
	 * Display the error message
	 *
	 * @access  public
	 * @param   string
	 * @param   string
	 * @return  string
	 */
	function displayErrors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach($this->error_msg as $val)
		{
			$str .= $open . $val . $close;
		}

		return $str;
	}

}