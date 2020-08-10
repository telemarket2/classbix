<?php
/**
 * ErrorObj pass message with boolean value for displaying related error message
 *
 * @author vp
 */
class ErrorObj
{

	//put your code here
	public $messages = array();

	public static function isError($obj)
	{
		return $obj instanceof ErrorObj;
	}

	public function add($message, $type = 'error')
	{
		$this->messages[] = array($type => $message);
	}

	public function getMessages($type = null, $pattern = '<p class="msg-{type}">{message}</p>')
	{
		if(!strlen($pattern))
		{
			// pattern not set then return messages as one string
			$pattern = '{message} ';
		}

		$return = '';

		// get all messages
		foreach($this->messages as $m)
		{
			foreach($m as $k => $v)
			{
				if(is_null($type) || $k == $type)
				{
					$return.=str_replace(array('{type}', '{message}'), array($k, $v), $pattern);
				}
			}
		}

		return $return;
	}

}
