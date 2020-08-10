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
Class TextTransform
{

	private $_str;
	private static $metaphone_soundex_arr = array();
	private static $double_metaphone_arr = array();
	private static $text_normalize_arr = array();

	//const SMILEYS_URL = '/dev/public/images/ifadeler/';

	function __construct($str, $transform = '')
	{

		$this->_str = $str;

		$arr_t = explode('|', $transform);
		foreach ($arr_t as $t)
		{
			if (method_exists($this, $t))
			{
				$this->_str = $this->$t($this->_str);
			}
			elseif (function_exists($t))
			{
				$this->_str = $t($this->_str);
			}
		}

		return $this->_str;
	}

	function __toString()
	{
		return $this->_str . '';
	}

	static function bbcode($message)
	{
		$preg = array(
			'/(?<!\\\\)\[color(?::\w+)?=(.*?)\](.*?)\[\/color(?::\w+)?\]/si'													 => "<span style=\"color:\\1\">\\2</span>",
			'/(?<!\\\\)\[size(?::\w+)?=(.*?)\](.*?)\[\/size(?::\w+)?\]/si'														 => "<span style=\"font-size:\\1\">\\2</span>",
			'/(?<!\\\\)\[font(?::\w+)?=(.*?)\](.*?)\[\/font(?::\w+)?\]/si'														 => "<span style=\"font-family:\\1\">\\2</span>",
			'/(?<!\\\\)\[align(?::\w+)?=(.*?)\](.*?)\[\/align(?::\w+)?\]/si'													 => "<div style=\"text-align:\\1\">\\2</div>",
			'/(?<!\\\\)\[b(?::\w+)?\](.*?)\[\/b(?::\w+)?\]/si'																	 => "<span style=\"font-weight:bold\">\\1</span>",
			'/(?<!\\\\)\[i(?::\w+)?\](.*?)\[\/i(?::\w+)?\]/si'																	 => "<span style=\"font-style:italic\">\\1</span>",
			'/(?<!\\\\)\[u(?::\w+)?\](.*?)\[\/u(?::\w+)?\]/si'																	 => "<span style=\"text-decoration:underline\">\\1</span>",
			'/(?<!\\\\)\[center(?::\w+)?\](.*?)\[\/center(?::\w+)?\]/si'														 => "<div style=\"text-align:center\">\\1</div>",
			// [code] & [php]
			'/(?<!\\\\)\[code(?::\w+)?\](.*?)\[\/code(?::\w+)?\]/si'															 => "<div class=\"bb-code\">\\1</div>",
			'/(?<!\\\\)\[php(?::\w+)?\](.*?)\[\/php(?::\w+)?\]/si'																 => "<div class=\"bb-php\">\\1</div>",
			// [email]
			'/(?<!\\\\)\[email(?::\w+)?\](.*?)\[\/email(?::\w+)?\]/si'															 => "<a href=\"mailto:\\1\" class=\"bb-email\">\\1</a>",
			'/(?<!\\\\)\[email(?::\w+)?=(.*?)\](.*?)\[\/email(?::\w+)?\]/si'													 => "<a href=\"mailto:\\1\" class=\"bb-email\">\\2</a>",
			// [url]
			'/(?<!\\\\)\[url(?::\w+)?\]www\.(.*?)\[\/url(?::\w+)?\]/si'															 => "<a href=\"http://www.\\1\" target=\"_blank\" class=\"bb-url\">\\1</a>",
			'/(?<!\\\\)\[url(?::\w+)?\](.*?)\[\/url(?::\w+)?\]/si'																 => "<a href=\"\\1\" target=\"_blank\" class=\"bb-url\">\\1</a>",
			'/(?<!\\\\)\[url(?::\w+)?=(.*?)?\](.*?)\[\/url(?::\w+)?\]/si'														 => "<a href=\"\\1\" target=\"_blank\" class=\"bb-url\">\\2</a>",
			// [img]
			'/(?<!\\\\)\[img(?::\w+)?\](.*?)\[\/img(?::\w+)?\]/si'																 => "<img src=\"\\1\" alt=\"\\1\" class=\"bb-image\" />",
			'/(?<!\\\\)\[img(?::\w+)?=(.*?)x(.*?)\](.*?)\[\/img(?::\w+)?\]/si'													 => "<img width=\"\\1\" height=\"\\2\" src=\"\\3\" alt=\"\\3\" class=\"bb-image\" />",
			// [quote]
			'/(?<!\\\\)\[quote(?::\w+)?\](.*?)\[\/quote(?::\w+)?\]/si'															 => "<div>ALINTI:<div class=\"bb-quote\">\\1</div></div>",
			'/(?<!\\\\)\[quote(?::\w+)?=(?:&quot;|"|\')?(.*?)["\']?(?:&quot;|"|\')?\](.*?)\[\/quote\]/si'						 => "<div>Quote \\1:<div class=\"bb-quote\">\\2</div></div>",
			// [list]
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[\*(?::\w+)?\](.*?)(?=(?:\s*<br\s*\/?>\s*)?\[\*|(?:\s*<br\s*\/?>\s*)?\[\/?list)/si' => "\n<li class=\"bb-listitem\">\\1</li>",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[\/list(:(?!u|o)\w+)?\](?:<br\s*\/?>)?/si'											 => "\n</ul>",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[\/list:u(:\w+)?\](?:<br\s*\/?>)?/si'												 => "\n</ul>",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[\/list:o(:\w+)?\](?:<br\s*\/?>)?/si'												 => "\n</ol>",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list(:(?!u|o)\w+)?\]\s*(?:<br\s*\/?>)?/si'										 => "\n<ul class=\"bb-list-unordered\">",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list:u(:\w+)?\]\s*(?:<br\s*\/?>)?/si'												 => "\n<ul class=\"bb-list-unordered\">",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list:o(:\w+)?\]\s*(?:<br\s*\/?>)?/si'												 => "\n<ol class=\"bb-list-ordered\">",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list(?::o)?(:\w+)?=1\]\s*(?:<br\s*\/?>)?/si'										 => "\n<ol class=\"bb-list-ordered,bb-list-ordered-d\">",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list(?::o)?(:\w+)?=i\]\s*(?:<br\s*\/?>)?/s'										 => "\n<ol class=\"bb-list-ordered,bb-list-ordered-lr\">",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list(?::o)?(:\w+)?=I\]\s*(?:<br\s*\/?>)?/s'										 => "\n<ol class=\"bb-list-ordered,bb-list-ordered-ur\">",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list(?::o)?(:\w+)?=a\]\s*(?:<br\s*\/?>)?/s'										 => "\n<ol class=\"bb-list-ordered,bb-list-ordered-la\">",
			'/(?<!\\\\)(?:\s*<br\s*\/?>\s*)?\[list(?::o)?(:\w+)?=A\]\s*(?:<br\s*\/?>)?/s'										 => "\n<ol class=\"bb-list-ordered,bb-list-ordered-ua\">",
			// escaped tags like \[b], \[color], \[url], ...
			'/\\\\(\[\/?\w+(?::\w+)*\])/'																						 => "\\1"
		);
		$message = preg_replace(array_keys($preg), array_values($preg), $message);
		return $message;
	}

	// KullanÃƒÂ½mÃƒÂ½ : {$mystring|B2Smilies}


	static function smileys($message)
	{
		$b2smiliestrans = array(
			':D'			 => 'icon_biggrin.gif',
			':-D'			 => 'icon_biggrin.gif',
			':grin:'		 => 'icon_biggrin.gif',
			':)'			 => 'icon_smile.gif',
			':-)'			 => 'icon_smile.gif',
			':smile:'		 => 'icon_smile.gif',
			':('			 => 'icon_sad.gif',
			':-('			 => 'icon_sad.gif',
			':sad:'			 => 'icon_sad.gif',
			':o'			 => 'icon_surprised.gif',
			':-o'			 => 'icon_surprised.gif',
			':eek:'			 => 'icon_surprised.gif',
			'8O'			 => 'icon_eek.gif',
			'8-O'			 => 'icon_eek.gif',
			':shock:'		 => 'icon_eek.gif',
			':?'			 => 'icon_confused.gif',
			':-?'			 => 'icon_confused.gif',
			':???:'			 => 'icon_confused.gif',
			':s'			 => 'icon_confused.gif',
			':S'			 => 'icon_confused.gif',
			'8-)'			 => 'icon_cool.gif',
			':cool:'		 => 'icon_cool.gif',
			':lol:'			 => 'icon_lol.gif',
			':x'			 => 'icon_mad.gif',
			':-x'			 => 'icon_mad.gif',
			':mad:'			 => 'icon_mad.gif',
			':P'			 => 'icon_razz.gif',
			':-P'			 => 'icon_razz.gif',
			':razz:'		 => 'icon_razz.gif',
			':oops:'		 => 'icon_redface.gif',
			':cry:'			 => 'icon_cry.gif',
			':\'('			 => 'icon_cry.gif',
			':evil:'		 => 'icon_evil.gif',
			':twisted:'		 => 'icon_twisted.gif',
			':roll:'		 => 'icon_rolleyes.gif',
			':wink:'		 => 'icon_wink.gif',
			';)'			 => 'icon_wink.gif',
			';-)'			 => 'icon_wink.gif',
			':!:'			 => 'icon_exclaim.gif',
			':?:'			 => 'icon_question.gif',
			':idea:'		 => 'icon_idea.gif',
			':arrow:'		 => 'icon_arrow.gif',
			':|'			 => 'icon_neutral.gif',
			':-|'			 => 'icon_neutral.gif',
			':neutral:'		 => 'icon_neutral.gif',
			':mrgreen:'		 => 'icon_mrgreen.gif',
			':angel:'		 => 'icon_angel.gif',
			'0:)'			 => 'icon_angel.gif',
			':-*'			 => 'icon_kiss.gif',
			':kiss:'		 => 'icon_kiss.gif',
			':sealed:'		 => 'icon_sealed.gif',
			':moneymouth:'	 => 'icon_money_mouth.gif',
		);


		//uksort($b2smiliestrans, 'smiliescmp');
		uksort($b2smiliestrans, array(self, "smiliescmp"));

		foreach ($b2smiliestrans as $smiley => $img)
		{
			$b2_smiliessearch[] = $smiley;
			$smiley_masked = '';
			for ($i = 0; $i < strlen($smiley); $i = $i + 1)
			{
				$smiley_masked .= substr($smiley, $i, 1) . chr(160);
			}
			$b2_smiliesreplace[] = "<img src='" . self::getSmiley($img) . "' />";
		}

		return str_replace($b2_smiliessearch, $b2_smiliesreplace, $message);
	}

	static function cdatasafe($message)
	{
		$search_str = ']]>';
		$replace_str = ']]&gt;';

		if (strpos($message, $search_str) !== false)
		{
			return str_replace($search_str, $replace_str, $message);
		}

		return $message;
	}

	static function getSmiley($img)
	{
		return URL_ASSETS . 'images/ifadeler/' . $img;
	}

	static function smiliescmp($a, $b)
	{
		if (strlen($a) == strlen($b))
		{
			return strcmp($a, $b);
		}
		return (strlen($a) > strlen($b)) ? -1 : 1;
	}

	//calculate years of age (input string: YYYY-MM-DD)
	static function age($birthday)
	{
		list($year, $month, $day) = explode("-", $birthday);
		$year_diff = date("Y") - $year;
		$month_diff = date("m") - $month;
		$day_diff = date("d") - $day;
		if ($day_diff < 0 || $month_diff < 0)
			$year_diff--;
		return $year_diff;
	}

	static function nl2br($text)
	{
		if (strpos($text, '<br />') === false)
		{
			$text = nl2br($text);
		}
		return $text;
	}

	/**
	 * convert urls to clickable links
	 *
	 * @param string $text
	 * @return string
	 */
	static function url2link($text)
	{
		//if (strpos($text, '</a>') === false)
		//{
		/*
		  //$text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '<a href="\\1" rel="nofollow" target="_blank">\\1</a>', $text);
		  //$text = eregi_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '\\1<a href="http://\\2" rel="nofollow" target="_blank">\\2</a>', $text);
		 */

		// switchet to broader match on 02.02.2018
		//$text = preg_replace('$(https?://[a-z0-9_./?=&#-@]+)(?![^<>]*>)$i', ' <a href="$1" rel="nofollow" target="_blank">$1</a> ', $text . " ");
		//$text = preg_replace('$(www\.[a-z0-9_./?=&#-@]+)(?![^<>]*>)$i', '<a href="http://$1" rel="nofollow" target="_blank">$1</a> ', $text . " ");
		//==== v2 ==============			
		// urls with http
		//$match_href = '$((https?://|www\.)([\d\w\.-]+\.[\w\.]{2,6})[^\s\<\>]*/?)$i';
		//$replace_url = ' <a href="http://$1" rel="nofollow" target="_blank">$1</a> ';
		//$text = preg_replace($match_href, $replace_url, $text);
		//$text = str_replace(array('http://http://', 'http://https://'), array('http://', 'https://'), $text);
		//==== v3 ==============	
		//Allow any character even non latin characters in url
		// urls with http
		$text = ' ' . $text;
		$match_href = '$(^|[\n ])(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\<\>]*/?)$i';
		$replace_url = ' <a href="$2" rel="nofollow" target="_blank">$2</a> ';
		$text = preg_replace($match_href, $replace_url, $text);
		// urls without http
		$match_href = '$(^|[\n ])(www\.([\d\w\.-]+\.[\w\.]{2,6})[^\s\<\>]*/?)$i';
		$replace_url = ' <a href="http://$2" rel="nofollow" target="_blank">$2</a> ';
		$text = preg_replace($match_href, $replace_url, $text);
		$text = substr($text, 1);
		//}
		return $text;
	}

	/**
	 * convert email address to clickable links
	 *
	 * @param string $text
	 * @return string
	 */
	static function email2link($text)
	{
		if (strpos($text, '</a>') === false)
		{
			//$text = eregi_replace('([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})', '<a href="mailto:\\1">\\1</a>', $text);
			$text = preg_replace('|([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})|i', '<a href="mailto:$1">$1</a>', $text);
		}
		return $text;
	}

	public static function firstImage($text)
	{
		// get first image file url from html
		$pattern = "/src=[\"'](http:\/\/[^\"']*\.(png|jpg|gif))[\"']/i";
		preg_match_all($pattern, $text, $images);

		if (strlen($images[1][0]))
		{
			$return = strip_tags($images[1][0]);
		}
		return $return;
	}

	/**
	 * strip tags, remove new line, tab and reduce spaces. crop text if it is longer than required.
	 * 
	 * @param string $text
	 * @param int $length
	 * @param string $suffix
	 * @return string
	 */
	public static function excerpt($text, $length = 250, $suffix = '...')
	{
		$text = strip_tags($text);

		if (StringUtf8::strlen($text) > $length)
		{
			$text = Inflector::utf8Substr($text, 0, $length + 100);
		}
		else
		{
			$suffix = '';
		}

		$text = self::removeSpacesNewlines($text);

		if (StringUtf8::strlen($text) > $length)
		{
			$text = Inflector::utf8Substr(trim($text), 0, $length);
		}
		else
		{
			$suffix = '';
		}

		return $text . $suffix;
	}

	/**
	 * remove extra spaces & all (tab, newline, return) from string
	 * 
	 * @param type $text
	 * @return type
	 */
	public static function removeSpacesNewlines($text)
	{
		// $text = str_replace(array("\n", "\t", "\r"), " ", $text);
		// remove ecess spaces, tabs, newlines also removed with \s+ but we removed them before as well for being sure.
		$text = preg_replace("/\s+/u", " ", $text);

		return trim($text);
	}

	/**
	 * remove extra spaces, newlines are kept
	 * 
	 * @param type $text
	 * @return type
	 */
	public static function removeSpaces($text)
	{
		// remove ecess regular spaces and tabs to single space
		$text = preg_replace("/[ \t]+/u", " ", $text);

		return trim($text);
	}

	/**
	 * remove extra spaces and reduce empty newlines to max number
	 * 
	 * @param type $text
	 * @return type
	 */
	public static function removeSpacesMaxNewlines($text, $max_newlines = 1)
	{
		$max_newlines = intval($max_newlines);
		if ($max_newlines < 1)
		{
			$max_newlines = 1;
		}

		$replace_str = "\n";
		if ($max_newlines > 1)
		{
			$replace_str = str_repeat($replace_str, $max_newlines);
		}

		// convert to newline only format 
		$text = preg_replace("/\r/u", "", $text);
		// remove empty space in new line
		$text = preg_replace("/^\s+$/mu", "", $text);
		// reduce excess newlines to max possible 
		$text = preg_replace("/\n{" . $max_newlines . ",}/u", $replace_str, $text);

		return self::removeSpaces($text);
	}

	public static function removePhoneNumber($text, $replace = '')
	{
		$text = preg_replace('/\+?[0-9][0-9()\-\s+]{4,20}[0-9]/', $replace, $text);
		return trim($text);
	}

	/**
	 * Remove extra space, some special characters, newline, tab, return chars, convert to lowercase
	 * 
	 * @param string $str
	 * @return string
	 */
	static public function normalizeQueryString($str)
	{
		// remove this chars from search query because they give error and useless
		/* $arr_skip_chars = array('/', '&', '%', '_', '.', ',', ':', ';');
		  $str = str_replace($arr_skip_chars, ' ', $str); */

		// remove non aplpha numerics 
		$str = preg_replace("/[^[:alnum:][:space:]]/u", ' ', $str);

		// convert to lowercase
		$str = StringUtf8::strtolower($str);

		// remove extra spaces, tabs, newline, return
		$str = TextTransform::removeSpacesNewlines($str);

		return trim($str);
	}

	/**
	 * Checks if given string a url. adds http:// if it is missing. If string is not url then returns empty string. Also validates url with Validator
	 * 
	 * @param string $str
	 * @return string url or empty string if not valid url
	 */
	public static function str2Url($str)
	{
		if (strlen($str) > 4 && strpos($str, 'http') !== 0)
		{
			$return_url = 'http://' . $str;
		}
		else
		{
			$return_url = $str;
		}


		if ($return_url && Validation::getInstance()->valid_url($return_url))
		{
			return $return_url;
		}

		return '';
	}

	/**
	 * checks if current string is url then formats it with wigen pattern. Returns initial string or html formatted link
	 * 
	 * @param string $str
	 * @param string $pattern
	 * @return string
	 */
	public static function str2Link($str, $pattern = '<a href="{URL}" rel="nofollow" target="_blank">{TITLE}</a>')
	{
		$return = '';
		if (strlen($str) > 0)
		{
			$return = View::escape($str);
			$return_url = self::str2Url($str);
			if ($return_url)
			{
				$return = str_replace(array('{URL}', '{TITLE}'), array(View::escape($return_url), $return), $pattern);
			}
		}

		return $return;
	}

	/**
	 * get youtube video ID from URL
	 *
	 * example: echo youtube_id_from_url('http://youtu.be/NLqAF9hrVbY'); # NLqAF9hrVbY
	 * 
	 * @param string $url
	 * @return string Youtube video id or FALSE if none found. 
	 */
	public static function youtube_id_from_url($url)
	{
		$pattern = '%^# Match any youtube URL
        (?:https?://)?  # Optional scheme. Either http or https
        (?:www\.)?      # Optional www subdomain
        (?:             # Group host alternatives
          youtu\.be/    # Either youtu.be,
        | youtube\.com  # or youtube.com
          (?:           # Group path alternatives
            /embed/     # Either /embed/
          | /v/         # or /v/
          | /watch\?v=  # or /watch\?v=
          )             # End path alternatives.
        )               # End host alternatives.
        ([\w-]{10,12})  # Allow 10-12 for 11 char youtube id.
        $%x'
		;
		$result = preg_match($pattern, $url, $matches);
		if (false !== $result)
		{
			return $matches[1];
		}
		return false;
	}

	/**
	 * parse string by spaces and return most used keywords
	 * 
	 * @param string $str text or html to parse
	 * @return string comma seperated string 
	 */
	public static function str2keywords($str)
	{
		$min_keyword_len = 3;
		$max_keyword_count = 10;
		$punctuations = array('&quot;', '&copy;', '&gt;', '&lt;', '&amp;', '&raquo;',
			',', ')', '(', '.', "'", '"',
			'<', '>', ';', '!', '?', '/', '-',
			'_', '[', ']', ':', '+', '=', '#', '$', '|',
			chr(10), chr(13), chr(9));
		$arr_keywords2 = array();


		// remove tags 
		$str = strip_tags($str);

		// replace unused characters 
		$str = str_replace($punctuations, ' ', $str);

		// lowercase
		$str = StringUtf8::strtolower($str);

		// split with space
		$arr_keywords = explode(' ', $str);

		foreach ($arr_keywords as $key)
		{
			$key = trim($key);
			if (strlen($key) >= $min_keyword_len)
			{
				if (isset($arr_keywords2[$key]))
				{
					$arr_keywords2[$key]++;
				}
				else
				{
					$arr_keywords2[$key] = 1;
				}
			}
		}

		// sort by number of occurences 
		arsort($arr_keywords2);

		$arr_keywords2 = array_keys($arr_keywords2);

		// return with limit
		$arr_keywords2 = array_slice($arr_keywords2, 0, $max_keyword_count);

		return implode(',', $arr_keywords2);
	}

	/**
	 * use native function if exists 
	 * 
	 * @param array $data_to_encode
	 * @return string
	 */
	public static function jsonEncode($data_to_encode)
	{
		if (function_exists('json_encode'))
		{
			$phpVersion = substr(phpversion(), 0, 3) * 1;
			if ($phpVersion >= 5.4)
			{
				// keep unicode strings 
				return json_encode($data_to_encode, JSON_UNESCAPED_UNICODE);
			}

			return json_encode($data_to_encode);
		}
		else
		{
			return TextTransform::json_encode($data_to_encode);
		}
	}

	/**
	 * json_encode function for php version < 5.2
	 * 
	 * @param type $data
	 * @return json
	 */
	public static function json_encode($data)
	{
		if (is_array($data) || is_object($data))
		{
			$islist = is_array($data) && ( empty($data) || array_keys($data) === range(0, count($data) - 1) );

			if ($islist)
			{
				$json = '[' . implode(',', array_map(array('TextTransform', 'json_encode'), $data)) . ']';
			}
			else
			{
				$items = Array();
				foreach ($data as $key => $value)
				{
					$items[] = TextTransform::json_encode("$key") . ':' . TextTransform::json_encode($value);
				}
				$json = '{' . implode(',', $items) . '}';
			}
		}
		elseif (is_string($data))
		{
			# Escape non-printable or Non-ASCII characters. 
			# I also put the \\ character first, as suggested in comments on the 'addclashes' page. 
			$string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
			$json = '';
			$len = strlen($string);
			# Convert UTF-8 to Hexadecimal Codepoints. 
			for ($i = 0; $i < $len; $i++)
			{

				$char = $string[$i];
				$c1 = ord($char);

				# Single byte; 
				if ($c1 < 128)
				{
					$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
					continue;
				}

				# Double byte 
				$c2 = ord($string[++$i]);
				if (($c1 & 32) === 0)
				{
					$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
					continue;
				}

				# Triple 
				$c3 = ord($string[++$i]);
				if (($c1 & 16) === 0)
				{
					$json .= sprintf("\\u%04x", (($c1 - 224) << 12) + (($c2 - 128) << 6) + ($c3 - 128));
					continue;
				}

				# Quadruple 
				$c4 = ord($string[++$i]);
				if (($c1 & 8 ) === 0)
				{
					$u = (($c1 & 15) << 2) + (($c2 >> 4) & 3) - 1;

					$w1 = (54 << 10) + ($u << 6) + (($c2 & 15) << 2) + (($c3 >> 4) & 3);
					$w2 = (55 << 10) + (($c3 & 15) << 6) + ($c4 - 128);
					$json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
				}
			}
		}
		else
		{
			# int, floats, bools, null 
			$json = strtolower(var_export($data, true));
		}
		return $json;
	}

	/**
	 * Strip tags and their content 
	 * 
	 * @param str $text
	 * @param str $tags
	 * @param bool $invert
	 * @return str
	 */
	static public function strip_tags_content($text, $tags = '', $invert = FALSE)
	{

		preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
		$tags = array_unique($tags[1]);

		if (is_array($tags) AND count($tags) > 0)
		{
			if ($invert == FALSE)
			{
				return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
			}
			else
			{
				return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
			}
		}
		elseif ($invert == FALSE)
		{
			return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
		}
		return $text;
	}

	/**
	 * Check if text changed. do not check number. 
	 * 
	 * @param string $str1
	 * @param string $str2
	 * @return boolean 
	 */
	static public function text_is_changed($str1, $str2)
	{
		// check if given text changed 
		// remove numbers 
		// normalize strings
		$str1_arr = TextTransform::text_normalize($str1, 'metaphone_soundex', 'array');
		$str2_arr = TextTransform::text_normalize($str2, 'metaphone_soundex', 'array');

		$str1_arr_ = array();
		$str2_arr_ = array();

		// remove numbers 
		foreach ($str1_arr as $val)
		{
			// numbers will have x prefixed
			if (strpos($val, 'x') !== 0)
			{
				// this is word
				$str1_arr_[] = $val;
			}
		}
		$str1 = implode(' ', $str1_arr_);
		unset($str1_arr);
		unset($str1_arr_);


		foreach ($str2_arr as $val)
		{
			// numbers will have x prefixed
			if (strpos($val, 'x') !== 0)
			{
				// this is word
				$str2_arr_[] = $val;
			}
		}
		$str2 = implode(' ', $str2_arr_);
		unset($str2_arr);
		unset($str2_arr_);

		// now convert to string and check similarity 
		$sim = TextTransform::text_similarity($str1, $str2, true);

		// different words found
		return $sim->len_diff > 0 ? true : false;
	}

	static public function text_similarity($str1, $str2, $already_normalized = false)
	{
		$return = new stdClass();
		$return->similarity = 0;
		$return->similarity_min = 0;
		$return->similarity_max = 0;
		$return->similarity_avg = 0;
		$return->len_same = 0;
		$return->len_diff = 0;

		if (strlen($str1) == 0 || strlen($str2) == 0)
		{
			return $return;
		}

		if ($already_normalized !== true)
		{
			if ($already_normalized === false)
			{
				// use default normalization method
				$str1 = self::text_normalize($str1);
				$str2 = self::text_normalize($str2);
			}
			else
			{
				// normalize using given method 
				$str1 = self::text_normalize($str1, $already_normalized);
				$str2 = self::text_normalize($str2, $already_normalized);
			}

			if (strlen($str1) == 0 || strlen($str2) == 0)
			{
				return $return;
			}
		}

		// comvert array to keys 
		$arr1 = array();
		parse_str('&' . str_replace(' ', '=1&', $str1) . '=1', $arr1);
		$arr2 = array();
		parse_str('&' . str_replace(' ', '=1&', $str2) . '=1', $arr2);

		$len1 = count($arr1);
		$len2 = count($arr2);

		$len_min = min($len1, $len2);
		$len_max = max($len1, $len2);
		$len_avg = ($len1 + $len2) / 2;

		if ($len_min == 0 || $len_max == 0 || $len_avg == 0)
		{
			return $return;
		}

		// find exact matches
		$arr_same = array();
		foreach ($arr1 as $k => $v)
		{
			if (isset($arr2[$k]))
			{
				$arr_same[$k] = $v;
			}
		}


		$return->len_same = count($arr_same);
		$return->len_diff = $len1 + $len2 - $return->len_same * 2;

		$return->similarity_min = round($return->len_same * 100 / $len_min);
		$return->similarity_max = round($return->len_same * 100 / $len_max);
		$return->similarity_avg = round($return->len_same * 100 / $len_avg);
		$return->similarity = $return->similarity_min;

		return $return;
	}

	/**
	 * Normalize and convert current text to metaphone-soundex string
	 * 
	 * @param string $str
	 * @param string $method metaphone_soundex|double_metaphone|all|search|plain|
	 * @param string $return_format null|array
	 * @return sring
	 */
	static public function text_normalize($str, $method = 'metaphone_soundex', $return_format = null)
	{
		//Benchmark::cp('text_normalize:'.$str);
		// 
		// PREPARE FOR SPEED 
		// 
		// remove non alpanumerics to space
		$str = preg_replace("/[^[:alnum:][:space:]]/u", ' ', $str);
		$str = preg_replace("/\s+/", ' ', $str);
		//Benchmark::cp('[0-preremove]');
		// convert to lovercase
		$str = StringUtf8::strtolower($str);
		//Benchmark::cp('[0-strtolower]');
		// unique 
		$arr_str = explode(' ', $str);
		$arr_str = array_unique($arr_str);
		$arr_str = array_filter($arr_str, 'strlen');
		$str = implode(' ', $arr_str);
		//Benchmark::cp('[0-unique]');
		//
		//
		// THIS TAKES LONG 
		// 
		// convert string to latin letters 
		$str = StringUtf8::convert($str);
		//Benchmark::cp('[1-convert]');
		//
		// REST IS FAST
		//
		// remove non alpanumerics to space
		$str = preg_replace("/[^[:alnum:][:space:]]/u", ' ', $str);
		$str = preg_replace("/\s+/", ' ', $str);
		//Benchmark::cp('[2-alnum]');


		$str_word = preg_replace("/\d/u", ' ', $str);
		$str_num = preg_replace("/\D/u", ' ', $str);
		//Benchmark::cp('[3-sep_word_num]');

		$str_word = preg_replace("/\s+/", ' ', $str_word);
		$str_num = preg_replace("/\s+/", ' ', $str_num);
		//Benchmark::cp('[4-rem-space]');


		$arr_word = explode(' ', $str_word);
		$arr_num = explode(' ', $str_num);
		$arr_word = array_unique($arr_word);
		$arr_num = array_unique($arr_num);

		$arr_word = array_filter($arr_word, 'strlen');
		$arr_num = array_filter($arr_num, 'strlen');
		//Benchmark::cp('[5-arr]');


		$result = array();
		$num_convert = array(
			'normalize_method'	 => 0,
			'int'				 => 0,
			'all'				 => 0
		);
		foreach ($arr_word as $a)
		{
			// use it to prevent excess pregmatches
			if (!isset(self::$text_normalize_arr[$method][$a]))
			{
				$num_convert['normalize_method'] += 1;
				self::$text_normalize_arr[$method][$a] = self::normalize_method($a, $method) . '';
			}
			// store with type 'str' to use later to reduce on search
			$result[self::$text_normalize_arr[$method][$a]] = 'str';
			$num_convert['all'] += 1;
		}

		foreach ($arr_num as $a)
		{
			// use it to prevent excess pregmatches
			if (!isset(self::$text_normalize_arr['int'][$a]))
			{
				$num_convert['int'] += 1;
				self::$text_normalize_arr['int'][$a] = sprintf("%'x4s", $a) . '';
			}
			// store with type 'int' to use later to reduce on search
			$result[self::$text_normalize_arr['int'][$a]] = 'int';
			$num_convert['all'] += 1;
		}

		//Benchmark::cp('[text_normalize:$num_convert:' . implode('|', $num_convert) . ']');




		if (isset($result['']))
		{
			unset($result['']);
		}

		$arr = array_keys($result);

		asort($arr);


		if ($return_format === 'array')
		{
			// return array
			$return = $arr;
		}
		else
		{
			if ($method === 'search')
			{

				// use result array here, because it has type 
				// convert to match boolean search string 
				$return = '';
				// if less than 5 words then use regular search with precise priority braces. if more then use simple version without braces
				$count_max_precise_search_terms = 5;
				// if terms more than 15 then remove number terms, and plain terms 
				$count_max_all_search_terms = 15;
				// in any case do not return more than 20 terms
				$count_max_return_search_terms = 20;
				// if less than 5 meta terms then append plain terms
				$count_min_meta_terms = 5;

				$count_arr = count($result);
				$use_braces = $count_arr > $count_max_precise_search_terms ? false : true;
				$arr_term_use = array();
				foreach ($result as $term => $type)
				{
					$term_use = '';
					if (strpos($term, ' ') !== false)
					{
						// has multivalue
						if ($use_braces)
						{
							// braces allowed for few word searches
							$term_use = '(' . $term . ')';
						}
						else
						{
							// use only first metaphone value
							$arr_term = explode(' ', $term);

							foreach ($arr_term as $term_)
							{
								$term_use = $term_;
								// metaphone marked with 9
								if (strpos($term_, '9') !== false)
								{
									break;
								}
							}
						}
					}
					else
					{
						$term_use = $term;
					}

					if (strlen($term_use))
					{
						$arr_term_use[$term_use] = $type;
					}
				}


				if ($arr_term_use)
				{
					if ($count_arr > $count_max_all_search_terms)
					{
						// more than 10 words then seperate exact words, meta words, numbers by type 
						$arr_by_type = array(
							'double_metaphone'	 => array(),
							'plain'				 => array(),
							'number'			 => array()
						);
						foreach ($arr_term_use as $term => $type)
						{
							if ($type === 'int')
							{
								$arr_by_type['number'][$term] = true;
							}
							else
							{
								if (strpos($term, '9') !== false)
								{
									// it is double metaphone
									$arr_by_type['double_metaphone'][$term] = true;
								}
								else
								{
									// it is plain text 
									$arr_by_type['plain'][$term] = true;
								}
							}
						}

						// use double 
						$arr = array_keys($arr_by_type['double_metaphone']);

						// now check if it is enough 
						if (count($arr) <= $count_min_meta_terms)
						{
							// append plain 
							$arr = array_merge($arr, array_keys($arr_by_type['plain']));
						}

						// reduce array to maximum 20 terms 
						if (count($arr) > $count_max_return_search_terms)
						{
							$arr = array_slice($arr, 0, $count_max_return_search_terms);
						}
					}
					else
					{
						// use braces or reduced terms
						$arr = array_keys($arr_term_use);
					}

					$arr = array_unique($arr);
					asort($arr);
					$return = implode(' +', $arr);
					$return = '+' . trim($return, ' +');
				}
			}
			else
			{
				// return string 
				$return = implode(' ', $arr);
				$arr = explode(' ', $return);
				$arr = array_unique($arr);
				asort($arr);
				$return = implode(' ', $arr);
			}
		}
		//Benchmark::cp('text_normalize:END:'.$return);
		//echo '[$return:'.$return.']';
		//exit;

		return $return;
	}

	/**
	 * Remove parentheses because it is slow for match queries. use it only to order by most matched values 
	 * convert search query string with parentheses to without parentheses version 
	 * ex: '+(7mir 99mr) +xxx7' -> '+99mr +xxx7'
	 * 
	 * @param string $str
	 * @return string
	 */
	static public function text_normalize_simplify_search($str)
	{
		$return = $str;

		if (strpos($str, '('))
		{
			// has paranteses, parse by + sign and remove paranteses
			$arr = explode('+', $str);
			$arr_result = array();
			foreach ($arr as $a)
			{
				// check if has parantheses
				if (strpos($a, '(') !== false)
				{
					$term = str_replace(array('(', ')'), '', $a);

					// use only first metaphone value
					$arr_term = explode(' ', $term);

					foreach ($arr_term as $term_)
					{
						$term_use = $term_;
						// metaphone marked with 9
						if (strpos($term_, '9') !== false)
						{
							break;
						}
					}

					$arr_result[$term_use] = true;
				}
				else
				{
					$arr_result[$a] = true;
				}
			}

			// convert back to string 
			$return = implode(' +', array_keys($arr_result));
			$return = '+' . trim($return, ' +');
		}

		Benchmark::cp('text_normalize_simplify_search(' . $str . '):' . $return);

		return $return;
	}

	/**
	 * 
	 * @param type $str word
	 * @param type $method metaphone_soundex|double_metaphone|all|search|plain|
	 * @return type
	 */
	static private function normalize_method($str, $method = 'metaphone_soundex')
	{
		$return = '';
		switch ($method)
		{
			case 'metaphone_soundex':
				$return = self::metaphone_soundex($str);
				break;
			case 'double_metaphone':
				$return = self::double_metaphone($str);
				break;
			case 'all':
				$return_arr = array();
				$result_plain = self::normalize_method($str, 'plain');
				$return_arr[] = $result_plain;

				$result_metaphone = self::normalize_method($str, 'double_metaphone');
				if (strpos($result_metaphone, '999') === false)
				{
					// do not include one letter matches 
					$return_arr[] = $result_metaphone;
				}

				$result_soundex = self::normalize_method($str, 'metaphone_soundex');
				if (strpos($result_soundex, '000') === false)
				{
					// do not include one letter matches 
					$return_arr[] = $result_soundex;
				}

				$return_arr = array_filter($return_arr, 'strlen');
				array_unique($return_arr);

				$return = implode(' ', $return_arr);
				break;
			case 'search':
				// use plain + meta for searching, do not use soundex 
				$return_arr = array();
				$result_plain = self::normalize_method($str, 'plain');
				$return_arr[] = $result_plain;

				$result_metaphone = self::normalize_method($str, 'double_metaphone');
				if (strpos($result_metaphone, '999') === false)
				{
					// do not include one letter matches 
					$return_arr[] = $result_metaphone;
				}

				/* $result_soundex = self::normalize_method($str, 'metaphone_soundex');
				  if (strpos($result_soundex, '000') === false)
				  {
				  // do not include one letter matches
				  $return_arr[] = $result_soundex;
				  } */

				$return_arr = array_filter($return_arr, 'strlen');
				array_unique($return_arr);

				$return = implode(' ', $return_arr);
				break;
			case 'plain':
			default:
				$return = sprintf("%'74s", '7' . $str);
		}

		// plain formatted
		return $return;
	}

	/**
	 * generate metaphone then soundex of string 
	 * no need to prefix because it is string with number and length 4 
	 * @param type $str
	 * @return string
	 */
	static public function metaphone_soundex($str)
	{
		if (isset(self::$metaphone_soundex_arr[$str]))
		{
			$return = self::$metaphone_soundex_arr[$str];
		}
		else
		{
			$meta = metaphone($str);
			$return = '';
			if (strlen($meta))
			{
				// valid metaphone, markit with token
				$return = soundex($meta);
			}

			self::$metaphone_soundex_arr[$str] = $return;
		}

		return $return;
	}

	/**
	 * generate metaphone then soundex of string 
	 * prefix 9
	 * 
	 * @param type $str
	 * @return string
	 */
	static public function double_metaphone($str)
	{
		if (isset(self::$double_metaphone_arr[$str]))
		{
			$return = self::$double_metaphone_arr[$str];
		}
		else
		{
			use_helper('DoubleMetaphone');
			$dm = new DoubleMetaphone($str);

			$dm1 = $dm->primary;
			$dm2 = $dm->secondary;
			$meta = '';
			if (strlen($dm1))
			{
				if (strcmp($dm1, $dm2) != 0)
				{
					$dm1 = sprintf("%'94s", '9' . $dm1);
					$dm2 = sprintf("%'94s", '9' . $dm2);

					$meta = $dm1 . ' ' . $dm2;
				}
				else
				{
					$dm1 = sprintf("%'94s", '9' . $dm1);
					$meta = $dm1;
				}
			}

			$return = strtolower($meta);
			self::$double_metaphone_arr[$str] = $return;
		}

		return $return;
	}

	/**
	 * Home made method to do array_diff ~10x faster that PHP built-in.
	 *
	 * @param The array to compare from
	 * @param An array to compare against
	 *
	 * @return an array containing all the entries from array1 that are not present in array2.
	 */
	static public function array_diff($array1, $array2)
	{
		$diff = array();

		// we don't care about keys anyway + avoids dupes
		foreach ($array1 as $value)
		{
			$diff[$value] = 1;
		}

		// unset common values
		foreach ($array2 as $value)
		{
			unset($diff[$value]);
		}

		return array_keys($diff);
	}

	/**
	 * Home mande method to do array_intersect ~10x faster that PHP built-in.
	 *
	 * @param The array to compare from
	 * @param An array to compare against
	 *
	 * @return an array containing all the entries from array1 that are present in array2.
	 */
	static public function array_intersect($array1, $array2)
	{
		$a1 = $a2 = array();

		// we don't care about keys anyway + avoids dupes
		foreach ($array1 as $value)
		{
			$a1[$value] = $value;
		}
		foreach ($array2 as $value)
		{
			$a2[$value] = 1;
		}

		// unset different values values
		foreach ($a1 as $value)
		{
			if (!isset($a2[$value]))
			{
				unset($a1[$value]);
			}
		}

		return array_keys($a1);
	}

}
