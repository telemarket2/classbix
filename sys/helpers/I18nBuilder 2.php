<?php

/**
 * Updated: 09/07/2012
 * 			added form to translate each term manually and build term file. 
 */
class I18nBuilder
{

	function build($lng = 'tr')
	{
		$arr = self::buildCatalogFromFiles();


		//print_r($arr);
		//$cat = self::getCatalog($lng, 'message,bank');		
		$cat = self::getBaseCatalog();

		self::mergeMessageTable($arr, $cat);

		ksort($arr);

		echo '<p>Generated from php files and existing lng files.</p>';
		self::displayBuildTable('build', $arr);



		echo '<p>Checking current tr table as base table for given language. First update base tr language file with build values. then check if every word has translation.</p>';
		self::check($lng, 'message,bank');


		// print new message array
		echo '<p>This is new lng file to use. It is populated from frech build to use as base language table.</p>';

		echo "<pre>" . self::formatArrayView($arr) . "</pre>";


		// print new bank array


		echo '<script src="' . self::url_jquery() . '" type="text/javascript"></script>';
		echo '<script type="text/javascript" src="' . URL_ASSETS . 'js/jquery.tablesorter.min.js"></script> ';
		echo '<script>
		$(document).ready(function() { 
        	$("#myTable").tablesorter(); 
    	}); 
    	</script>';

		return true;
	}

	public static function formatArrayView($arr)
	{
		$return = "return array (\n";
		foreach ($arr as $k => $v)
		{
			$k_ = '';
			$v_ = '';
			switch ($v['type'])
			{
				case '':
				case 'not':
					// on not or correct key end fixing mode
					$fixing = false;
					// print
					$k_ = $k;
					$v_ = $v['trans'];
					break;
				case 'er':
					// start fixing mode
					$fixing = true;
					break;
				case 'rem':
					// if fixing then print this
					if ($fixing)
					{
						// print
						$k_ = $k;
						$v_ = $v['trans'];
					}
					break;
				default:
					break;
			}

			if (strlen($k_))
			{
				$return .= "'" . self::viewEscapeQuote($k_) . "'=>'" . self::viewEscapeQuote($v_) . "',\n";
			}
		}
		$return .= ");\n";

		return $return;
	}

	static function viewEscapeQuote($str)
	{
		return self::viewQuote(View::escape($str));
	}

	static function viewQuote($str)
	{
		//return addcslashes($str,"'");		
		return str_replace("'", "\'", str_replace("\'", "'", str_replace('\"', '"', $str)));
		//return str_replace("'", "\'", $str);
		//return str_replace("'", "'''", $str);
	}

	function mergeMessageTable(& $arr, $cat)
	{
		// find if text in message then do nothing add trans
		// if message in blank then add bl as type
		// if message not found then add not as type
		// if message not found on scan then add rem as type

		foreach ($arr as $k => $v)
		{
			// strings that are broken because of ' stored here. list them seperately for manuel insertion

			$trans = $cat['message'][$k];
			$type = '';
			if (!strlen($trans))
			{
				$trans = $cat['bank'][$k];
				$type = 'new';
			}
			if (!strlen($trans))
			{
				$type = 'not';
			}

			if (!isset($arr[$k]['type']))
			{
				$arr[$k]['type'] = $type;
			}

			$arr[$k]['trans'] = $trans;
		}

		// add records from message cat to arr 
		$i = 1;
		if (isset($cat['message']))
		{
			foreach ($cat['message'] as $k => $v)
			{
				if (!isset($arr[$k]))
				{
					$arr[$k]['type'] = 'rem';
					$arr[$k]['trans'] = $v;
				}

				$arr[$k]['pos'] = $i;
				$i++;
			}
		}
	}

	function displayBuildTable($title, $arr)
	{
		$echo = '';
		foreach ($arr as $k => $v)
		{
			// strings that are broken because of ' stored here. list them seperately for manuel insertion
			$trans = $v['trans'];

			switch ($v['type'])
			{
				case 'not':
					$style = 'style="background-color:#f00;"';
					break;
				case 'new':
					$style = 'style="background-color:#7DA8C4;"';
					break;
				case 'rem':
					$style = 'style="background-color:#E2E074;"';
					break;
				default:
					$style = '';
					break;
			}
			if ($v['location'])
			{
				$t = implode(" \n", $v['location']);
			}
			else
			{
				$t = '';
			}


			$echo .= '<tr>
			<td ' . $style . '>' . ($v['type'] ? $v['type'] : '&nbsp;') . '</td>
			<td ' . $style . '>' . ($v['pos'] ? $v['pos'] : 0) . '</td>
			<td title="' . $t . '">' . View::escape($k) . '</td>
					<td>' . ($trans ? View::escape($trans) : '<div style="background-color:#f00;">-</div>') . '</td></tr>';
		}
		echo '<h1>' . $title . '</h1>';
		echo '<table border="1" cellspacing="0" width="100%" style="font-family:courier;" id="myTable">
		<thead><tr><th>type</th><th>pos</th><th>key</th><th>trans</th></tr></thead> 
		' . $echo . '</table>';
	}

	private static function _readDir($dir, & $arr)
	{
		//echo "_readDir($dir)\n";
		$dh = opendir($dir);
		while (false !== ($filename = readdir($dh)))
		{
			if ($filename != '.' && $filename != '..')
			{
				$file = $dir . DIRECTORY_SEPARATOR . $filename;
				if (is_file($file))
				{
					if (strpos($filename, '.php'))
					{
						self::_readFile($file, $arr);
					}
				}
				else
				{
					self::_readDir($file, $arr);
				}
			}
		}
		closedir($dh);
	}

	/**
	 * find lines with __() function
	 * @param $file
	 * @return unknown_type
	 */
	private static function _readFile($file, & $arr)
	{
		//echo "_readFile($file)\n";
		$line_num = 1;
		$handle = @fopen($file, "r");
		if ($handle)
		{
			while (!feof($handle))
			{
				$buffer = fgets($handle);
				$location = '#' . $file . ' line ' . $line_num . "\n";



				if (self::_findStringError($buffer, $matches))
				{
					/* if(strpos($buffer, "\'") !== false)
					  {
					  echo '[_findStringError]' . $buffer . "\n";
					  print_r($matches);
					  }
					 */


					/* do not add error string
					 * foreach($matches[1] as $m)
					  {
					  $arr[$m]['location'][] = $location;
					  $arr[$m]['type'] = 'er';
					  //echo $m."\n";
					  } */

					// has slash error then get with slash 
					if (self::_findStringWithSlash($buffer, $matches))
					{
						/* if(strpos($buffer, "\'") !== false)
						  {
						  echo '[_findStringWithSlash]' . $buffer . "\n";
						  print_r($matches);
						  } */
						foreach ($matches[1] as $m)
						{
							$m = rtrim($m, "'");
							$arr[$m]['location'][] = $location;
							//echo $m."\n";
						}
					}
				}
				elseif (self::_findString($buffer, $matches))
				{
					/* if(strpos($buffer, "\'") !== false)
					  {
					  echo '[_findString]' . $buffer . "\n";
					  print_r($matches);
					  } */
					foreach ($matches[1] as $m)
					{
						$arr[$m]['location'][] = $location;
						//echo $m."\n";
					}
				}

				$line_num++;
			}
			fclose($handle);
		}
	}

	private static function _findString($str, & $matches)
	{
		//echo "_findString($str,& $matches)";
		$matches = false;
		$pattern = '/__\(\'([^\']*)[(\'\))|\',]/';
		return preg_match_all($pattern, $str, $matches);
	}

	private static function _findStringWithSlash($str, & $matches)
	{
		//echo "_findString($str,& $matches)";
		$matches = false;
		$pattern = '/__\(\'(.*)[(\'\))|\',]/';
		return preg_match_all($pattern, $str, $matches);
	}

	private static function _findStringError($str, & $matches)
	{
		//echo "_findString($str,& $matches)";
		$matches = false;
		$pattern = '/__\(\'([^\\\\\']*)(\\\\\')/';
		return preg_match_all($pattern, $str, $matches);
	}

	public static function getCatalog($lng, $catalogs = 'message')
	{
		$files = explode(',', $catalogs);

		I18n::saveLocale();
		I18n::setLocale($lng);
		foreach ($files as $f)
		{
			I18n::loadCatalog($f);
		}
		$cat = I18n::getCatalogs();
		I18n::restoreLocale();

		return $cat;
	}

	public static function getBaseCatalog($catalog = 'message')
	{
		$catalog_file = self::getFilenameBase($catalog);

		// assign returned value of catalog file
		// file return a array (source => traduction)
		if (file_exists($catalog_file))
		{
			$return[$catalog] = include $catalog_file;
			return $return;
		}
		else
		{
			return array();
		}
	}

	function check($lng, $catalog = 'message')
	{
		//$cat_tr = I18nBuilder::getCatalog('tr', $catalog);
		$cat_tr = I18nBuilder::getBaseCatalog($catalog);
		$cat = I18nBuilder::getCatalog($lng, $catalog);

		self::displayCatTable($cat_tr, $cat);
	}

	function displayCatTable($cat_tr, $cat)
	{
		echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
		<body>
		';

		foreach ($cat_tr as $key => $c)
		{
			echo '<h1>' . $key . '</h1>';
			echo '<table border="1" cellspacing="0" width="100%" style="font-family:courier;">';

			foreach ($c as $k => $v)
			{
				$trans = $cat[$key][$k];
				echo '<tr><td>' . View::escape($k) . '</td><td>' . ($trans ? View::escape($trans) : '<div style="background-color:#f00;">-</div>') . '</td></tr>';
			}
			echo '</table>';
		}
		echo '</body>';
		//print_r($r);
	}

	function page($type, $lng = 'tr')
	{
		switch ($type)
		{
			case 'build':
				self::build($lng);
				break;
			case 'translate':
				// $cat_tr = self::getCatalog('tr');
				$cat_tr = self::getBaseCatalog();
				$cat = self::getCatalog($lng);

				self::displayTranslatePage(array(
					'cat'	 => $cat,
					'cat_tr' => $cat_tr,
					'lng'	 => $lng
				));
				break;
			case 'check':
				self::check($lng);
				break;
			default:
				exit('invalid action type: build,translate,check');
		}
	}

	public static function displayTranslatePage($vars)
	{
		extract($vars);
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<?php echo self::translateJs(); ?>
			</head>
			<body>

				<p>
					<a href="#" class="translate_all">translate all</a> |
					<a href="#" class="translate_empty">translate empty ones</a> | 
					<a href="#" class="print_array">print array</a> | 
				</p>

				<pre class="array"></pre>

				<?php
				foreach ($cat_tr as $key => $c)
				{
					echo '<h1>' . $key . '</h1>';
					echo '<table border="1" cellspacing="0" width="100%">';
					foreach ($c as $k => $v)
					{
						echo '<tr><td class="k">' . View::escape($k) . '<input type="hidden" value="' . View::escape($k) . '" /></td>
					<td class="v">' . View::escape($cat[$key][$k]) . '</td>
					<td class="g">&nbsp;</td></tr>';
					}
					echo '</table>';
				}
				?>
			</body>
		</html>
		<?php
		exit;
	}

	/**
	 * load javascript used to translate automation 
	 * 
	 */
	public static function translateJs()
	{
		ob_start();
		?>
		<!--<script src="<?php echo self::url_jquery() ?>" type="text/javascript"></script>-->
		<script type="text/javascript">
			
			var table_name = 'translation_terms';
			var $tr_process = [];
			var translate_cnt = 1000; // prevent infinite loops
			
			$(function ()
			{
				$('.translate_all').click(translate_row_all);
				$('.translate_empty').click(translate_row_empty);
				$('.print_array').click(populateLanguageVar);
				$('.print_array_new').click(populateLanguageVarNew);
				$('.old_value').click(set_from_old);
				var $table = $('.' + table_name);
				$table.on('change', 'input.v', translateBtnVisible);
				translateBtnVisible();
				// check if empty fields exist
				var empty_count = $table.data('emptycount') * 1;
				console.log('empty_count:' + empty_count);
				if (empty_count === 0)
				{
					$('.display_empty').hide();
				}
				else
				{
					$('.display_empty').append('<sup>' + empty_count + '</sup>');
				}
			});
			
			function getEmptyRows()
			{
				var $table = $('.' + table_name);
				var $tr = $table.find('tr').has('input.v');
				// clear $tr_process
				var $tr_empty = [];
				$tr.each(function ()
				{
					var $me = $(this);
					var val = $me.find('input.v').val();
					if (undefined != val && !val.length)
					{
						$tr_empty.push($me);
					}
				});
				
				return $tr_empty;
			}
			
			function translateBtnVisible()
			{
				$tr_empty = getEmptyRows();
				if ($tr_empty.length)
				{
					$('.translate_empty').show();
				}
				else
				{
					$('.translate_empty').hide();
				}
			}
			
			function set_from_old()
			{
				var $me = $(this);
				var $dest = $('input.v', $me.parents('tr:first'));
				$dest.val($me.text());
				return false;
			}
			
			function translate_row_all()
			{
				// mark all rows then run translate 
				var $table = $('.' + table_name);
				translate_cnt = 1000;
				// reset $tr_process
				$tr_process = $table.find('tr').has('input.v');
				translateContinue();
				return false;
			}
			
			function translate_row_empty()
			{
				// mark empty rows then run translate 
				$tr_process = getEmptyRows();
				translate_cnt = 1000;
				translateContinue();
				return false;
			}
			
			
			
			function translateRow($me)
			{
				// key 
				var text = $('input.k', $me).val();
				// value
				var $dest = $('input.v', $me);
				
				console.log('translateRow:' + text);
				
				//text = prepateTextToTranslate(text);
				// translate if text is not empty
				if (undefined != text && text.length)
				{
					$.post('', {str: text}, function (result)
					{
						if (result)
						{
							//t = prepateTextToTranslateReverse(result);
							t = result;
							
							// set translated text 
							$dest.val(t);
							translateContinue();
							
						}
					});
				}
				else
				{
					// no source text, continue
					translateContinue();
				}
				return false;
			}
			
			function translateContinue()
			{
				if (translate_cnt < 0)
				{
					return false;
				}
				translate_cnt--;
				console.log('translateContinue');
				console.log($tr_process);
				if ($tr_process.length)
				{
					var $tr = $tr_process.shift();
					translateRow($tr);
				}
				else
				{
					translateBtnVisible();
				}
				return false;
			}
			
			
			function prepateTextToTranslate(text)
			{
				text = text.replace(/\"\{blog\}\"/g, '<hr class="blog"/>');
				text = text.replace(/\"\{tag\}\"/g, '<hr class="tag"/>');
				text = text.replace(/\"\{title\}\"/g, '<hr class="title"/>');
				
				text = text.replace(/\"\{/g, '"[');
				text = text.replace(/\}\"/g, ']"');
				text = text.replace(/{/g, '<br class="');
				text = text.replace(/}/g, '"/>');
				text = text.replace(/\]\"/g, '}"');
				text = text.replace(/\"\[/g, '"{');
				
				return text;
			}
			
			function prepateTextToTranslateReverse(t)
			{
				t = t.replace(/\<hr class=\"blog\"\/\>/g, '"{blog}"');
				t = t.replace(/\<hr class=\"tag\"\/\>/g, '"{tag}"');
				t = t.replace(/\<hr class=\"title\"\/\>/g, '"{title}"');
				t = t.replace(/\<br class=\"/g, '{');
				t = t.replace(/\"\/\>/g, '}');
				t = t.replace(/\&quot\;/g, '"');
				return t;
			}
			
			function populateLanguageVar()
			{
				var str = '';
				
				$('table').each(function (i)
				{
					var $table = $(this);
					str += "$table_" + i + " = array(\n";
					$('tr', $table).each(function (j)
					{
						var $me = $(this);
						var text = $('.k input', $me).val();
						var t = $('.g input', $me).val();
						
						if (t == undefined)
						{
							t = $('.v', $me).text();
						}
						else
						{
							t = t.replace(/'/g, '\\\'');
						}
						text = text.replace(/'/g, '\\\'');
						
						str += "'" + text + "' => '" + t + "',\n";
					});
					str += ");\n\n";
				});
				
				$('.array').text(str);
				
				return false;
			}
			
			function populateLanguageVarNew()
			{
				var str = '';
				
				$('table').each(function (i)
				{
					var $table = $(this);
					str += "$table_" + i + " = array(\n";
					$('tr', $table).each(function (j)
					{
						var $me = $(this);
						var text = $('.k input', $me).val();
						var t = $('.g input', $me).val();
						
						if (t == undefined)
						{
							t = '';
						}
						else
						{
							t = t.replace(/'/g, '\\\'');
						}
						text = text.replace(/'/g, '\\\'');
						
						if (t)
						{
							str += "'" + text + "' => '" + t + "',\n";
						}
					});
					str += ");\n\n";
				});
				
				$('.array').text(str);
				
				return false;
			}
		</script>
		<?php
		$return = ob_get_contents();
		ob_clean();
		return $return;
	}

	public static function url_jquery()
	{
		//return  'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js';

		return URL_ASSETS . 'js/jquery-1.12.4.min.js';
	}

	/**
	 * generate translate table with javascripts to autotranslate 
	 * 
	 * @param string $lng
	 * @return boolean|string
	 */
	public static function htmlTranslationForm($lng, $type, $page)
	{
		if (isset($_POST['str']))
		{
			echo self::translateStr($_POST['str'], $lng);
			exit();
		}
		$num = 100;
		$page = $page > 0 ? $page : 1;
		$st = ($page - 1) * $num;



		$catalog = 'message';

		// load language file values
		$cat = I18nBuilder::getCatalog($lng, $catalog . ',_backup');

		//print_r($cat);
		// load default variables 
		$cat_terms = I18nBuilder::getBaseCatalog($catalog);

		$return = '';
		if ($cat_terms[$catalog])
		{

			// define empty values for counting 
			$cat_terms_empty = array();
			foreach ($cat_terms[$catalog] as $k => $v)
			{
				if (!strlen(trim($cat[$catalog][$k])))
				{
					$cat_terms_empty[$k] = $v;
				}
			}
			$empty_count = count($cat_terms_empty);

			$cat_terms_ = array();
			switch ($type)
			{
				case 'empty':
					// display only empty ones
					$cat_terms_ = $cat_terms_empty;
					break;
				case 'search':
					// display matching terms
					$search = trim($_REQUEST['search']);
					if (strlen($search))
					{
						foreach ($cat_terms[$catalog] as $k => $v)
						{
							//if(preg_match('/*' . $search . '*/ui', $k . ' ' . $cat[$catalog][$k]))
							if (stripos($k . ' ' . $cat[$catalog][$k], $search) !== false)
							{
								$cat_terms_[$k] = $v;
							}
						}
					}
					else
					{
						$type = 'all';
						$cat_terms_ = $cat_terms[$catalog];
					}
					break;
				case 'all':
				default:
					$type = 'all';
					$cat_terms_ = $cat_terms[$catalog];
					break;
			}

			$total_pages = ceil(count($cat_terms_) / $num);
			$paginator = Paginator::render($page, $total_pages, Language::get_url('admin/translate/' . $lng . '/' . $type . '/{page}/' . ($search ? '?search=' . $search : '')));

			$cat_terms_ = array_slice($cat_terms_, $st, $num, true);


			$i = $st + 1;
			$arr_unique_key = array();
			foreach ($cat_terms_ as $k => $v)
			{
				if (strlen($k))
				{
					// check for duplicate
					$k_lower = trim(strtolower($k), '.');
					if (isset($arr_unique_key[$k_lower]))
					{
						// this is duplocate with different case
						$duplicate_style = ' style="color:red;font-weight:bold;"';
					}
					else
					{
						$duplicate_style = '';
					}
					$arr_unique_key[$k_lower] = 1;

					$v_ = $cat[$catalog][$k];

					$return .= '<tr class="r' . ($tr++ % 2) . '">
					<td' . $duplicate_style . ' class="muted">' . $i . ($duplicate_style ? '*' : '') . '</td>
					<td' . $duplicate_style . ' title="' . View::escape($v) . '" data-type="term"><label for="val_' . $i . '">' . View::escape($k) . '</label></td>
					<td><input type="text" name="val[]" id="val_' . $i . '" value="' . View::escape($v_) . '" class="v input input-long" />
						<input type="hidden" name="key[]" value="' . View::escape($k) . '" class="k" />
						' . (!strlen($v_) && strlen($cat['_backup'][$k]) ? '<a href="#" class="old_value" title="' . __('old value') . '">' . View::escape($cat['_backup'][$k]) . '</a>' : '') . '
					</td>
					</tr>';
					$i++;
					//. self::viewEscapeQuote($k) . "'=>'" . self::viewEscapeQuote($v_) . "',\n";
				}
			}


			return '<form method="post" id="translation_terms"><table class="grid translation_terms" data-emptycount="' . $empty_count . '">
				<tr class="xs-hide">
					<th width="5%">' . __('#') . '</th>
					<th width="45%">' . __('Term') . '</th>
					<th width="50%">' . __('Translation') . '</th>				
				</tr>
				' . $return . '
				<tr><td colspan="3">
					<input type="submit" name="submit" value="' . __('Submit') . '"/>
					<input type="hidden" name="lng" value="' . View::escape($lng) . '" />					
					</td></tr>
				</table>
				<input type="hidden" name="search" value="' . View::escape($_REQUEST['search']) . '" />
				</form>'
					. $paginator
					. self::translateJs();
		}

		return false;
	}

	/**
	 * save submitted form to language file 
	 * 
	 * @param string $filename
	 * @return boolean
	 */
	public static function htmlTranslationFormSubmit($filename, $lng)
	{
		//print_r($_POST);
		$data = array();
		if ($_POST['data'])
		{
			// sume servers will not pass more than 500 input fields with post. they will be disabled and data passed through one textarea
			parse_str($_POST['data'], $data);
		}
		else
		{
			$data = $_POST;
		}
		//print_r($data);


		if ($data['val'])
		{
			// load base catalog 
			$cat_terms = I18nBuilder::getBaseCatalog('message');
			if (!$cat_terms['message'])
			{
				$cat_terms['message'] = array();
			}

			// load saved catalog for this language
			$cat = I18nBuilder::getCatalog($lng, 'message,_backup');
			$arr_message = is_array($cat['message']) ? $cat['message'] : array();
			$arr_backup = is_array($cat['_backup']) ? $cat['_backup'] : array();



			// build file contents 
			foreach ($data['val'] as $k => $val)
			{
				$arr_message[$data['key'][$k]] = $val;
			}

			// sort by key 
			//uksort($arr_message, array('I18nBuilder', 'sortCmpLower'));
			// generate lng file contents 
			$arr_data = array();
			foreach ($cat_terms['message'] as $k => $v)
			{
				$arr_data[$k] = $arr_message[$k];
			}

			// update translation backup
			// remove empty values and update backup 
			foreach ($arr_data as $k => $v)
			{
				if (strlen($v))
				{
					$arr_backup[$k] = $v;
				}
			}

			// update backup
			self::saveFile(I18n::getFilename($lng, '_backup'), $arr_backup);

			// save new data
			$return = self::saveFile($filename, $arr_data);

			// data saved then update loaded translation 
			if ($return)
			{
				I18n::updateCatalog($lng, '_backup', $arr_backup);
				I18n::updateCatalog($lng, 'message', $arr_data);
			}

			// free memeory
			unset($arr_backup);
			unset($arr_data);
			unset($arr_message);
			unset($cat);
			unset($cat_terms);

			return $return;
		}

		return false;
	}

	/**
	 * update language file from _backup, 
	 * if term not translated in message then use tranlsation from _backup, 
	 * if term translated do not change
	 * 
	 * @param string $lng
	 * @return boolean
	 */
	public static function updateFromBackup($lng)
	{
		// load saved catalog for this language
		$cat = I18nBuilder::getCatalog($lng, 'message,_backup');
		$arr_message = is_array($cat['message']) ? $cat['message'] : array();
		$arr_backup = is_array($cat['_backup']) ? $cat['_backup'] : array();

		$cat_terms = I18nBuilder::getBaseCatalog('message');
		if (!$cat_terms['message'])
		{
			$cat_terms['message'] = array();
		}

		// save file only if file updated
		$save_file = false;

		// save backup as message, no need to go to the loop below
		if ($arr_backup && !$arr_message)
		{
			return self::saveFile(I18n::getFilename($lng), $arr_backup);
		}

		// if no backup then will do nothing 		
		foreach ($arr_backup as $k => $v)
		{
			$no_val = isset($arr_message[$k]) ? (!strlen($arr_message[$k])) : true;
			$check = strlen($v) && isset($cat_terms['message'][$k]) && $no_val;
			if ($check)
			{
				// translation is not in message then set translation from backup
				$arr_message[$k] = $v;
				// need to save file
				$save_file = true;
			}
		}

		if ($save_file)
		{
			return self::saveFile(I18n::getFilename($lng), $arr_message);
		}
		return true;
	}

	/**
	 * save given array as php file
	 * 
	 * @uses FileDir::checkMakeFile 
	 * @param string $filename
	 * @param array $array
	 * @return bool
	 */
	public static function saveFile($filename, $array)
	{
		$return = '';
		foreach ($array as $k => $v)
		{
			$return .= "'" . self::viewQuote($k) . "'=>'" . self::viewQuote($v) . "',\n";
		}

		// convert to readible array
		$return = "<?php \n return array (\n" . trim($return, ",\n") . "\n);";

		return FileDir::checkMakeFile($filename, $return);
	}

	public static function getFilenameBase($catalog = 'message')
	{
		return I18N_PATH . DIRECTORY_SEPARATOR . $catalog . '.php';
	}

	/**
	 * Scan base directory for strings like __() and return array of terms
	 */
	public static function buildCatalogFromFiles()
	{
		$arr = array();
		// scan sys folder
		self::_readDir(CORE_ROOT, $arr);

		// scan themes folder
		self::_readDir(Theme::ThemesRoot(), $arr);

		return $arr;
	}

	/**
	 * Build base catalog file with content from scanning files 
	 */
	public static function buildBaseCatalogFile($catalog = 'message')
	{
		$arr = self::buildCatalogFromFiles();

		uksort($arr, array('I18nBuilder', 'sortCmpLower'));

		//echo '<pre>';
		//print_r($arr);
		//exit;

		$arr_data = array();
		foreach ($arr as $k => $v)
		{
			$arr_data[$k] = implode(', ', $v['location']);
		}

		$filename = self::getFilenameBase($catalog);

		return self::saveFile($filename, $arr_data);
	}

	public static function sortCmpLower($a, $b)
	{
		return strcasecmp($a, $b);
	}

	/**
	 * translate given string from english to given language
	 * 
	 * @uses Curl::get()
	 * @uses Benchmark::cp()
	 * @param array $arr_str of strings
	 * @param string $lng
	 * @return array|string
	 */
	public static function translateStrArray($arr_str = array(), $lng = 'tr')
	{
		Benchmark::cp('translateStrArray');
		$return = array();

		if ($arr_str)
		{
			foreach ($arr_str as $v)
			{
				$arr_str_[] = self::_str_trans($v);
			}
			$arr_str_ = '["' . implode('","', $arr_str_) . '"]';

			$appId = 'FF791986B918F170DBE8CD5A2C42D222F783FF36';
			$request = "http://api.microsofttranslator.com/V2/Ajax.svc/TranslateArray?appId=" . $appId .
					"&texts=" . urlencode($arr_str_) . "&from=en&to=" . $lng;

			$result = Curl::get($request);

			if (!$result)
			{
				return $return;
			}

			$result_arr = explode('","TranslatedTextSentenceLengths"', $result);

			Benchmark::cp($request);
			Benchmark::cp($result);

			// {"responseData": [{"responseData":{"translatedText":"ciao a tutti"},"responseDetails":null,"responseStatus":200},{"responseData":{"translatedText":"Bonjour tout le monde"},"responseDetails":null,"responseStatus":200}], "responseDetails": null, "responseStatus": 200}
			// save new values if it is empty
			if ($result_arr && is_array($result_arr) && count($result_arr) > 1)
			{
				$i = 0;
				foreach ($arr_str as $str)
				{
					$result_arr_ = explode(',"TranslatedText":"', $result_arr[$i]);
					$text = self::_str_trans($result_arr_[1], true);

					$return[] = $text;
					$i++;
				}
			}
		}

		Benchmark::cp('translateStrArray-finished');
		return $return;
	}

	/**
	 * translate one sting to given language from english
	 * 
	 * @param string $str
	 * @param string $lng
	 * @return string
	 */
	public static function translateStr($str, $lng)
	{
		// requested to translate text
		//$str = str_replace(array('{','}'),array('[',']'),$_POST['str']);
		$str = $_POST['str'];

		// do not translate english to english 
		if ($lng == 'en')
		{
			return $str;
		}

		if (strlen($str))
		{
			$t = self::translateStrArray(array($str), $lng);
			//$return = str_replace(array('[',']'),array('{','}'),$t[0]);
			$return = $t[0];
			return $return;
		}

		return '';
	}

	/**
	 * prepares string for sending to translation api and reverts back
	 * on reverse removes slashes and converts html entity quotes &quot; to chars
	 * 
	 * @param string $str
	 * @param bool $reverse
	 * @return string
	 */
	public static function _str_trans($str, $reverse = false)
	{
		//return $str;
		$arr = array(
			'{'	 => '{__',
			'}'	 => '__}',
			'%s' => '{_|_S_|_}'
		);
		if ($reverse)
		{
			$str = stripcslashes($str);
			$str = str_replace(array_values($arr), array_keys($arr), $str);
			$str = str_replace(array('&#39;', '&quot;'), array('\'', '"'), $str);
		}
		else
		{

			$str = str_replace(array_keys($arr), array_values($arr), $str);
			$str = addcslashes($str, '"');
		}

		return $str;
	}

	/**
	 * check if given langue can be translated uwing translated
	 * 
	 * @param string $lng
	 * @return bool
	 */
	public static function isSupported($lng)
	{
		$arr_lng = array(
			'ar'	 => 'Arabic',
			'bg'	 => 'Bulgarian',
			'ca'	 => 'Catalan',
			'zh-CHS' => 'Chinese (Simplified)',
			'zh-CHT' => 'Chinese (Traditional)',
			'cs'	 => 'Czech',
			'da'	 => 'Danish',
			'nl'	 => 'Dutch',
			/* 'en'	 => 'English', */
			'et'	 => 'Estonian',
			'fa'	 => 'Persian (Farsi)',
			'fi'	 => 'Finnish',
			'fr'	 => 'French',
			'de'	 => 'German',
			'el'	 => 'Greek',
			'ht'	 => 'Haitian Creole',
			'he'	 => 'Hebrew',
			'hi'	 => 'Hindi',
			'hu'	 => 'Hungarian',
			'id'	 => 'Indonesian',
			'it'	 => 'Italian',
			'ja'	 => 'Japanese',
			'ko'	 => 'Korean',
			'lv'	 => 'Latvian',
			'lt'	 => 'Lithuanian',
			'mww'	 => 'Hmong Daw',
			'no'	 => 'Norwegian',
			'pl'	 => 'Polish',
			'pt'	 => 'Portuguese',
			'ro'	 => 'Romanian',
			'ru'	 => 'Russian',
			'sk'	 => 'Slovak',
			'sl'	 => 'Slovenian',
			'es'	 => 'Spanish',
			'sv'	 => 'Swedish',
			'th'	 => 'Thai',
			'tr'	 => 'Turkish',
			'uk'	 => 'Ukrainian',
			'vi'	 => 'Vietnamese'
		);

		return isset($arr_lng[$lng]);
	}

}

// end I18n class