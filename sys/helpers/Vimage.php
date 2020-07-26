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
/*
  include("vImage.php");
  $vImage = new vImage();

  ## code input name must be 'vImageCodP'. it is used to check validity




  ==================================
  ### Display code input in form
  ==================================

  <tr>
  <td><label for="vImageCodP">'.__('Security code').'</label></td>
  <td>

  <table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
  <td width="65" valign="top"> <input size="3" maxlength="4" type="text" name="vImageCodP" /></td>
  <td valign="top"><img src="'.get_url('login/securityImage/'.REQUEST_TIME.'/').'" /></td>
  </tr>
  </table>
  '.View::validation()->vImageCodP_error.'
  </td>
  </tr>


  ===================================
  ### Validate code using validation class
  ===================================
  $vImage->_validate_security_code()


  ===================================
  ### Manual check code validity
  ===================================
  if ($vImage->checkCode()) {
  echo "code true";
  }else{
  echo "code false";

  }


  ===================================
  ### generate code and display jpg image
  ===================================
  use_helper('Vimage');
  $vImage = new Vimage();
  $vImage->genText(4);
  $vImage->showimage();



 */


class Vimage
{

	var $numChars = 3;
	var $w;
	var $h = 20;
	var $colBG = "231 249 234";
	var $colTxt = "0 0 0";
	var $colBorder = "000 000 000";
	var $charx = 30;
	var $numCirculos = 10;
	var $securityCode = 'iuyhgsdnvlksvhnkyj1ht2387n!$%^&$*%^(*$EWRAWSDFGDXzxvxfghtfy"Ã‚Â£$%^&*(^%';
	private static $_instance = null;

	/**
	 * return instance of Vimage object 
	 * 
	 * @return Vimage 
	 */
	public static function getInstance()
	{
		if(is_null(self::$_instance))
		{
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function genText($num)
	{

		if(($num != '') && ($num > $this->numChars))
		{
			$this->numChars = $num;
		}

		$this->texto = $this->genString();

		//$_SESSION['vImageCodS'] = $this->texto;
		$this->saveCode($this->texto);
	}

	function saveCode($code)
	{
		$c['md5_code'] = $this->_bakeCode($code);
		$c['exp'] = $_SERVER['REQUEST_TIME'] + 3600;

		Flash::setCookie('vImageCodS', serialize($c), 0);
	}

	function loadCodes()
	{
		$this->postCode = $_POST['vImageCodP'];
		//$this->sessionCode = $_SESSION['vImageCodS'];

		$c = unserialize(Flash::getCookie('vImageCodS'));

		if($c['exp'] > $_SERVER['REQUEST_TIME'])
		{
			$this->sessionCode = $c['md5_code'];
		}
		else
		{
			$this->sessionCode = false;
		}
		// cod kullanildi silmek gerekiyor
		Flash::clearCookie('vImageCodS');
	}

	function checkCode()
	{
		if(!isset($this->postCode))
		{
			$this->loadCodes();
		}
		if($this->_bakeCode($this->postCode) === $this->sessionCode)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	function showCodBox($echo = false, $extra = '')
	{
		$str = '<input size="3" maxlength="4" type="text" name="vImageCodP" ' . $extra . ' /> ';

		if($echo)
		{
			echo $str;
		}
		else
		{
			return $str;
		}
	}

	function showImage($chars = 3)
	{
		$this->genImage();
		/* no cache headers */
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Cache-Control: pre-check=0, post-check=0, max-age=0');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');

		/* content type */
		header("Content-type: image/png");

		ImagePng($this->im);
	}

	function genImage()
	{
		$this->w = ($this->numChars * $this->charx) + 10;

		$this->im = imagecreatetruecolor($this->w, $this->h);

		imagefill($this->im, 0, 0, $this->getColor($this->colBorder));
		imagefilledrectangle($this->im, 1, 1, ($this->w - 2), ($this->h - 2), $this->getColor($this->colBG));


		// add circles
		for($i = 1; $i <= $this->numCirculos; $i++)
		{
			$randomcolor = imagecolorallocate($this->im, rand(100, 255), rand(100, 255), rand(100, 255));
			imageellipse($this->im, rand(0, $this->w - 10), rand(0, $this->h - 3), rand(20, 60), rand(20, 60), $randomcolor);
		}


		$ident = 20;
		for($i = 0; $i < $this->numChars; $i++)
		{
			$char = substr($this->texto, $i, 1);
			$font = 14;
			$y = round(($this->h - 15) / 2);
			$col = $this->getColor($this->colTxt);

			if(($i % 2) == 0)
			{
				imagechar($this->im, $font, $ident, $y, $char, $col);
			}
			else
			{
				imagechar($this->im, $font, $ident, $y, $char, $col);
				//imagecharup ( $this->im, $font, $ident, $y+10, $char, $col );
			}
			$ident = $ident + $this->charx;
		}
	}

	function getColor($var)
	{
		$rgb = explode(" ", $var);
		$col = imagecolorallocate($this->im, $rgb[0], $rgb[1], $rgb[2]);
		return $col;
	}

	function genString()
	{
		//rand(0,time());
		//$possible="AGHacefhjkrStVxY124579";
		$possible = "0123456789";
		while(strlen($str) < $this->numChars)
		{
			$str .= substr($possible, (rand() % (strlen($possible))), 1);
		}

		return $str;
	}

	function _validate_security_code($str)
	{
		// check security image

		if($this->checkCode())
		{
			return true;
		}
		else
		{
			$validation = Validation::getInstance();
			$validation->set_message('_validate_security_code', __('The %s is not valid'));
			return false;
		}
	}

	function _bakeCode($code)
	{
		return md5(md5($code) . $this->securityCode);
	}

}
