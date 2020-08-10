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
class StringUtf8
{

	/**
	 * converts russian to english letters
	 *
	 * @param string $string
	 * @return string
	 */
	public static function str_ru2lower_en($string)
	{
		// do not convert ru to lower ru because charachters are not recognized after
		$low = array('ё'	 => 'e', 'й'	 => 'i', 'ц'	 => 's', 'у'	 => 'u', 'к'	 => 'k', 'е'	 => 'e', 'н'	 => 'n',
			'г'	 => 'g', 'ш'	 => 'sh', 'щ'	 => 'sh', 'з'	 => 'z', 'х'	 => 'h', 'ъ'	 => '', 'ф'	 => 'f',
			'ы'	 => 'i', 'в'	 => 'v', 'а'	 => 'a', 'п'	 => 'p', 'р'	 => 'r', 'о'	 => 'o', 'л'	 => 'l',
			'д'	 => 'd', 'ж'	 => 'j', 'э'	 => 'e', 'я'	 => 'ya', 'ч'	 => 'ch', 'с'	 => 's', 'м'	 => 'm',
			'и'	 => 'i', 'т'	 => 't', 'ь'	 => '', 'б'	 => 'b', 'ю'	 => 'yu',
			'Ё'	 => 'e', 'Й'	 => 'i', 'Ц'	 => 's', 'У'	 => 'u', 'К'	 => 'k', 'Е'	 => 'e', 'Н'	 => 'n',
			'Г'	 => 'g', 'Ш'	 => 'sh', 'Щ'	 => 'sh', 'З'	 => 'z', 'Х'	 => 'h', 'Ъ'	 => '', 'Ф'	 => 'f',
			'Ы'	 => 'i', 'В'	 => 'v', 'А'	 => 'a', 'П'	 => 'p', 'Р'	 => 'r', 'О'	 => 'o', 'Л'	 => 'l',
			'Д'	 => 'd', 'Ж'	 => 'j', 'Э'	 => 'e', 'Я'	 => 'ya', 'Ч'	 => 'ch', 'С'	 => 's', 'М'	 => 'm',
			'И'	 => 'i', 'Т'	 => 't', 'Ь'	 => '', 'Б'	 => 'b', 'Ю'	 => 'yu');
		$string = strtr($string, $low);

		return strtolower(self::remove_accents($string));
	}

	/**
	 * Strtolower russian
	 *
	 * @param string $text
	 * @return string
	 */
	public static function strtolower_ru($string)
	{
		// this is not working at all. all characters are not recognized after change :((
		$alfavitupper = array('Ё', 'Й', 'Ц', 'У', 'К', 'Е', 'Н', 'Г', 'Ш', 'Щ', 'З', 'Х', 'Ъ', 'Ф', 'Ы', 'В', 'А', 'П', 'Р', 'О', 'Л', 'Д', 'Ж', 'Э', 'Я', 'Ч', 'С', 'М', 'И', 'Т', 'Ь', 'Б', 'Ю');
		$alfavitlover = array('ё', 'й', 'ц', 'у', 'к', 'е', 'н', 'г', 'ш', 'щ', 'з', 'х', 'ъ', 'ф', 'ы', 'в', 'а', 'п', 'р', 'о', 'л', 'д', 'ж', 'э', 'я', 'ч', 'с', 'м', 'и', 'т', 'ь', 'б', 'ю');

		return strtolower(strtr($string, $alfavitupper, $alfavitlover));
	}

	/**
	 * converts greek to english letters
	 *
	 * @param string $string
	 * @return string
	 */
	public static function str_el2lower_en($string)
	{
		return strtolower(self::remove_accents(self::greeklish($string)));
	}

	function greeklish($string)
	{
		$greek = array('α', 'ά', 'Ά', 'Α', 'β', 'Β', 'γ', 'Γ', 'δ', 'Δ', 'ε', 'έ', 'Ε', 'Έ', 'ζ', 'Ζ', 'η', 'ή', 'Η', 'θ', 'Θ', 'ι', 'ί', 'ϊ', 'ΐ', 'Ι', 'Ί', 'κ', 'Κ', 'λ', 'Λ', 'μ', 'Μ', 'ν', 'Ν', 'ξ', 'Ξ', 'ο', 'ό', 'Ο', 'Ό', 'π', 'Π', 'ρ', 'Ρ', 'σ', 'ς', 'Σ', 'τ', 'Τ', 'υ', 'ύ', 'Υ', 'Ύ', 'φ', 'Φ', 'χ', 'Χ', 'ψ', 'Ψ', 'ω', 'ώ', 'Ω', 'Ώ', ' ', "'", "'", ',');
		$english = array('a', 'a', 'A', 'A', 'b', 'B', 'g', 'G', 'd', 'D', 'e', 'e', 'E', 'E', 'z', 'Z', 'i', 'i', 'I', 'th', 'Th', 'i', 'i', 'i', 'i', 'I', 'I', 'k', 'K', 'l', 'L', 'm', 'M', 'n', 'N', 'x', 'X', 'o', 'o', 'O', 'O', 'p', 'P', 'r', 'R', 's', 's', 'S', 't', 'T', 'u', 'u', 'Y', 'Y', 'f', 'F', 'x', 'X', 'ps', 'Ps', 'o', 'o', 'O', 'O', ' ', '_', '_', '_');
		//$english = array('a', 'a', 'A', 'A', 'b', 'B', 'g', 'G', 'd', 'D', 'e', 'e', 'E', 'E', 'z', 'Z', 'i', 'i', 'I', 'th', 'Th', 'i', 'i', 'i', 'i', 'I', 'I', 'k', 'K', 'l', 'L', 'm', 'M', 'n', 'N', 'x', 'X', 'o', 'o', 'O', 'O', 'p', 'P', 'r', 'R', 's', 's', 'S', 't', 'T', 'u', 'u', 'Y', 'Y', 'f', 'F', 'ch', 'Ch', 'ps', 'Ps', 'o', 'o', 'O', 'O', ' ', '_', '_', '_');
		return str_replace($greek, $english, $string);
	}

	public static function strtolower_utf8($string)
	{
		if (function_exists('mb_strtolower'))
		{
			return mb_strtolower($string, "UTF-8");
		}
		else
		{
			return strtolower($string);
		}
	}

	/**
	 * strtolower turkish
	 *
	 * @param string $string
	 * @return string
	 */
	public static function strtolower_tr($string)
	{
		return self::strtolower_utf8($string);

		//$low = array("Ü" => "ü", "Ö" => "ö", "Ğ" => "ğ", "Ş" => "ş", "Ç" => "ç", "İ" => "i", "I" => "ı");
		//return strtolower(strtr($string,$low));
	}

	/**
	 * converts turkish to english letters
	 *
	 * @param string $string
	 * @return string
	 */
	public static function str_tr2lower_en($string)
	{
		$string = self::strtolower_tr($string);
		$low = array("ü" => "u", "ö" => "o", "ğ" => "g", "ş" => "s", "ç" => "c", "ı" => "i");
		return strtr($string, $low);
	}

	/**
	 * Convert to lowercase 
	 *
	 * @param string $string
	 * @param string $lng
	 * @return string
	 */
	public static function strtolower($string, $lng = 'en')
	{

		/*
		 * did not work with utf8 chars
		  switch($lng)
		  {
		  case 'tr':
		  return self::strtolower_tr($string);
		  break;
		  case 'ru':
		  return self::strtolower_ru($string);
		  break;
		  }

		  return strtolower($string);
		 */

		return self::strtolower_utf8($string);
	}

	/**
	 * convert string to lowercase eglish letters
	 *
	 * @param string $string
	 * @param string $lng from to english letters
	 * @return string
	 */
	public static function convert($string, $lng = null)
	{
		if (is_null($lng) && self::isRussian($string))
		{
			$lng = 'ru';
		}

		switch ($lng)
		{
			/* case 'tr':
			  return self::str_tr2lower_en($string);
			  break; */
			case 'ru':// russian
			case 'bg':// bulgarian
			case 'uk':// ukrainian
				$string = self::str_ru2lower_en($string);
				break;
			case 'el':// greek
				$string = self::str_el2lower_en($string);
				break;
			default:
				$string = self::strtolower(self::remove_accents($string));
		}

		return $string;
	}

	public static function makePermalink($str, $lng = null, $default = 'post')
	{
		// convert string to permalink type according to language
		$str = self::convert($str, $lng);
		$str = Inflector::slugify($str);

		if (!strlen($str) && strlen($default))
		{
			return self::makePermalink($default, $lng);
		}

		return $str;
	}

	/**
	 * Converts all accent characters to ASCII characters.
	 *
	 * If there are no accent characters, then the string given is just returned.
	 *
	 * @since 1.2.1
	 *
	 * @param string $string Text that might have accent characters
	 * @return string Filtered string with replaced "nice" characters.
	 */
	static function remove_accents($string)
	{
		if (!preg_match('/[\x80-\xff]/', $string))
			return $string;

		if (self::seems_utf8($string))
		{
			$chars = array(
				// Decompositions for Latin-1 Supplement
				chr(195) . chr(128)				 => 'A', chr(195) . chr(129)				 => 'A',
				chr(195) . chr(130)				 => 'A', chr(195) . chr(131)				 => 'A',
				chr(195) . chr(132)				 => 'A', chr(195) . chr(133)				 => 'A',
				chr(195) . chr(135)				 => 'C', chr(195) . chr(136)				 => 'E',
				chr(195) . chr(137)				 => 'E', chr(195) . chr(138)				 => 'E',
				chr(195) . chr(139)				 => 'E', chr(195) . chr(140)				 => 'I',
				chr(195) . chr(141)				 => 'I', chr(195) . chr(142)				 => 'I',
				chr(195) . chr(143)				 => 'I', chr(195) . chr(145)				 => 'N',
				chr(195) . chr(146)				 => 'O', chr(195) . chr(147)				 => 'O',
				chr(195) . chr(148)				 => 'O', chr(195) . chr(149)				 => 'O',
				chr(195) . chr(150)				 => 'O', chr(195) . chr(153)				 => 'U',
				chr(195) . chr(154)				 => 'U', chr(195) . chr(155)				 => 'U',
				chr(195) . chr(156)				 => 'U', chr(195) . chr(157)				 => 'Y',
				chr(195) . chr(159)				 => 's', chr(195) . chr(160)				 => 'a',
				chr(195) . chr(161)				 => 'a', chr(195) . chr(162)				 => 'a',
				chr(195) . chr(163)				 => 'a', chr(195) . chr(164)				 => 'a',
				chr(195) . chr(165)				 => 'a', chr(195) . chr(167)				 => 'c',
				chr(195) . chr(168)				 => 'e', chr(195) . chr(169)				 => 'e',
				chr(195) . chr(170)				 => 'e', chr(195) . chr(171)				 => 'e',
				chr(195) . chr(172)				 => 'i', chr(195) . chr(173)				 => 'i',
				chr(195) . chr(174)				 => 'i', chr(195) . chr(175)				 => 'i',
				chr(195) . chr(177)				 => 'n', chr(195) . chr(178)				 => 'o',
				chr(195) . chr(179)				 => 'o', chr(195) . chr(180)				 => 'o',
				chr(195) . chr(181)				 => 'o', chr(195) . chr(182)				 => 'o',
				chr(195) . chr(182)				 => 'o', chr(195) . chr(185)				 => 'u',
				chr(195) . chr(186)				 => 'u', chr(195) . chr(187)				 => 'u',
				chr(195) . chr(188)				 => 'u', chr(195) . chr(189)				 => 'y',
				chr(195) . chr(191)				 => 'y',
				// Decompositions for Latin Extended-A
				chr(196) . chr(128)				 => 'A', chr(196) . chr(129)				 => 'a',
				chr(196) . chr(130)				 => 'A', chr(196) . chr(131)				 => 'a',
				chr(196) . chr(132)				 => 'A', chr(196) . chr(133)				 => 'a',
				chr(196) . chr(134)				 => 'C', chr(196) . chr(135)				 => 'c',
				chr(196) . chr(136)				 => 'C', chr(196) . chr(137)				 => 'c',
				chr(196) . chr(138)				 => 'C', chr(196) . chr(139)				 => 'c',
				chr(196) . chr(140)				 => 'C', chr(196) . chr(141)				 => 'c',
				chr(196) . chr(142)				 => 'D', chr(196) . chr(143)				 => 'd',
				chr(196) . chr(144)				 => 'D', chr(196) . chr(145)				 => 'd',
				chr(196) . chr(146)				 => 'E', chr(196) . chr(147)				 => 'e',
				chr(196) . chr(148)				 => 'E', chr(196) . chr(149)				 => 'e',
				chr(196) . chr(150)				 => 'E', chr(196) . chr(151)				 => 'e',
				chr(196) . chr(152)				 => 'E', chr(196) . chr(153)				 => 'e',
				chr(196) . chr(154)				 => 'E', chr(196) . chr(155)				 => 'e',
				chr(196) . chr(156)				 => 'G', chr(196) . chr(157)				 => 'g',
				chr(196) . chr(158)				 => 'G', chr(196) . chr(159)				 => 'g',
				chr(196) . chr(160)				 => 'G', chr(196) . chr(161)				 => 'g',
				chr(196) . chr(162)				 => 'G', chr(196) . chr(163)				 => 'g',
				chr(196) . chr(164)				 => 'H', chr(196) . chr(165)				 => 'h',
				chr(196) . chr(166)				 => 'H', chr(196) . chr(167)				 => 'h',
				chr(196) . chr(168)				 => 'I', chr(196) . chr(169)				 => 'i',
				chr(196) . chr(170)				 => 'I', chr(196) . chr(171)				 => 'i',
				chr(196) . chr(172)				 => 'I', chr(196) . chr(173)				 => 'i',
				chr(196) . chr(174)				 => 'I', chr(196) . chr(175)				 => 'i',
				chr(196) . chr(176)				 => 'I', chr(196) . chr(177)				 => 'i',
				chr(196) . chr(178)				 => 'IJ', chr(196) . chr(179)				 => 'ij',
				chr(196) . chr(180)				 => 'J', chr(196) . chr(181)				 => 'j',
				chr(196) . chr(182)				 => 'K', chr(196) . chr(183)				 => 'k',
				chr(196) . chr(184)				 => 'k', chr(196) . chr(185)				 => 'L',
				chr(196) . chr(186)				 => 'l', chr(196) . chr(187)				 => 'L',
				chr(196) . chr(188)				 => 'l', chr(196) . chr(189)				 => 'L',
				chr(196) . chr(190)				 => 'l', chr(196) . chr(191)				 => 'L',
				chr(197) . chr(128)				 => 'l', chr(197) . chr(129)				 => 'L',
				chr(197) . chr(130)				 => 'l', chr(197) . chr(131)				 => 'N',
				chr(197) . chr(132)				 => 'n', chr(197) . chr(133)				 => 'N',
				chr(197) . chr(134)				 => 'n', chr(197) . chr(135)				 => 'N',
				chr(197) . chr(136)				 => 'n', chr(197) . chr(137)				 => 'N',
				chr(197) . chr(138)				 => 'n', chr(197) . chr(139)				 => 'N',
				chr(197) . chr(140)				 => 'O', chr(197) . chr(141)				 => 'o',
				chr(197) . chr(142)				 => 'O', chr(197) . chr(143)				 => 'o',
				chr(197) . chr(144)				 => 'O', chr(197) . chr(145)				 => 'o',
				chr(197) . chr(146)				 => 'OE', chr(197) . chr(147)				 => 'oe',
				chr(197) . chr(148)				 => 'R', chr(197) . chr(149)				 => 'r',
				chr(197) . chr(150)				 => 'R', chr(197) . chr(151)				 => 'r',
				chr(197) . chr(152)				 => 'R', chr(197) . chr(153)				 => 'r',
				chr(197) . chr(154)				 => 'S', chr(197) . chr(155)				 => 's',
				chr(197) . chr(156)				 => 'S', chr(197) . chr(157)				 => 's',
				chr(197) . chr(158)				 => 'S', chr(197) . chr(159)				 => 's',
				chr(197) . chr(160)				 => 'S', chr(197) . chr(161)				 => 's',
				chr(197) . chr(162)				 => 'T', chr(197) . chr(163)				 => 't',
				chr(197) . chr(164)				 => 'T', chr(197) . chr(165)				 => 't',
				chr(197) . chr(166)				 => 'T', chr(197) . chr(167)				 => 't',
				chr(197) . chr(168)				 => 'U', chr(197) . chr(169)				 => 'u',
				chr(197) . chr(170)				 => 'U', chr(197) . chr(171)				 => 'u',
				chr(197) . chr(172)				 => 'U', chr(197) . chr(173)				 => 'u',
				chr(197) . chr(174)				 => 'U', chr(197) . chr(175)				 => 'u',
				chr(197) . chr(176)				 => 'U', chr(197) . chr(177)				 => 'u',
				chr(197) . chr(178)				 => 'U', chr(197) . chr(179)				 => 'u',
				chr(197) . chr(180)				 => 'W', chr(197) . chr(181)				 => 'w',
				chr(197) . chr(182)				 => 'Y', chr(197) . chr(183)				 => 'y',
				chr(197) . chr(184)				 => 'Y', chr(197) . chr(185)				 => 'Z',
				chr(197) . chr(186)				 => 'z', chr(197) . chr(187)				 => 'Z',
				chr(197) . chr(188)				 => 'z', chr(197) . chr(189)				 => 'Z',
				chr(197) . chr(190)				 => 'z', chr(197) . chr(191)				 => 's',
				// Euro Sign
				chr(226) . chr(130) . chr(172)	 => 'E',
				// GBP (Pound) Sign
				chr(194) . chr(163)				 => '');

			$string = strtr($string, $chars);
		}
		else
		{
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = chr(128) . chr(131) . chr(138) . chr(142) . chr(154) . chr(158)
					. chr(159) . chr(162) . chr(165) . chr(181) . chr(192) . chr(193) . chr(194)
					. chr(195) . chr(196) . chr(197) . chr(199) . chr(200) . chr(201) . chr(202)
					. chr(203) . chr(204) . chr(205) . chr(206) . chr(207) . chr(209) . chr(210)
					. chr(211) . chr(212) . chr(213) . chr(214) . chr(216) . chr(217) . chr(218)
					. chr(219) . chr(220) . chr(221) . chr(224) . chr(225) . chr(226) . chr(227)
					. chr(228) . chr(229) . chr(231) . chr(232) . chr(233) . chr(234) . chr(235)
					. chr(236) . chr(237) . chr(238) . chr(239) . chr(241) . chr(242) . chr(243)
					. chr(244) . chr(245) . chr(246) . chr(248) . chr(249) . chr(250) . chr(251)
					. chr(252) . chr(253) . chr(255);

			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}

		return $string;
	}

	/**
	 * Checks to see if a string is utf8 encoded.
	 *
	 * @author bmorel at ssi dot fr
	 *
	 * @since 1.2.1
	 *
	 * @param string $Str The string to be checked
	 * @return bool True if $Str fits a UTF-8 model, false otherwise.
	 */
	public static function seems_utf8($Str)
	{ # by bmorel at ssi dot fr
		$length = strlen($Str);
		for ($i = 0; $i < $length; $i++)
		{
			if (ord($Str[$i]) < 0x80)
				continue;# 0bbbbbbb
			elseif ((ord($Str[$i]) & 0xE0) == 0xC0)
				$n = 1;# 110bbbbb
			elseif ((ord($Str[$i]) & 0xF0) == 0xE0)
				$n = 2;# 1110bbbb
			elseif ((ord($Str[$i]) & 0xF8) == 0xF0)
				$n = 3;# 11110bbb
			elseif ((ord($Str[$i]) & 0xFC) == 0xF8)
				$n = 4;# 111110bb
			elseif ((ord($Str[$i]) & 0xFE) == 0xFC)
				$n = 5;# 1111110b
			else
				return false;# Does not match any model
			for ($j = 0; $j < $n; $j++)
			{ # n bytes matching 10bbbbbb follow ?
				if (( ++$i == $length) || ((ord($Str[$i]) & 0xC0) != 0x80))
					return false;
			}
		}
		return true;
	}

	public static function isRussian($text)
	{
		return preg_match('/[А-Яа-яЁё]/u', $text);
	}

	public static function strlen($s)
	{
		//since PHP-5.3.x mb_strlen() faster then strlen(utf8_decode())
		if (function_exists('mb_strlen'))
		{
			return mb_strlen($s, 'utf-8');
		}
		/*
		  utf8_decode() converts characters that are not in ISO-8859-1 to '?', which, for the purpose of counting, is quite alright.
		  It's much faster than iconv_strlen()
		  Note: this function does not count bad UTF-8 bytes in the string - these are simply ignored
		 */
		return strlen(utf8_decode($s));
	}

	public static function substr_replace($str, $repl, $start, $length = null)
	{
		preg_match_all('/./us', $str, $ar);
		preg_match_all('/./us', $repl, $rar);
		$length = is_int($length) ? $length : self::strlen($str);
		array_splice($ar[0], $start, $length, $rar[0]);
		return implode($ar[0]);
	}

	public static function removeRepeatedChars($str)
	{
		return preg_replace('/([^\d])\\1+/us', '$1', $str);
	}

}
