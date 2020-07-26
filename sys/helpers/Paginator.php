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
class Paginator
{

	public static function render($page, $total_pages, $extralink, $pattern = true, $extralink_alt = "")
	{
		//echo "make_links_array($st,$total_rows,$numrows,$extralink,$pattern)";
		/*
		  $st : kayit baslama satiri. sayfada 20 yazi varsa 2 sayfanin linki 20 diye basliyor.
		  $total_rows : toplam yazi zayisi
		  $numrows : her sayfada listelenen sayfa sayisi
		  $extralink : ornek/link/sayfa/{st}   . burada {st} nin yerine sayfa numarasini koyuyor. pattern true olmasi lazim. yoksa gelen linkin sonuna duruma gore ?st=2 veya &st=2 ekliyor
		  $pattern : true ise gelen linki pattenr olarak kullanir, yoksa arkasina link ekler

		  <sayfalar>
		  html...
		  </sayfalar>
		 */
		//echo '[render('.$page.', '.$total_pages.', '.$extralink.', '.$pattern.', '.$extralink_alt.')]';
		// on each side
		$links_count = 2;

		if($total_pages > 1)
		{

			// define page number
			if($page < 1)
			{
				$page = 1;
			}

			if($page > $total_pages)
			{
				$page = $total_pages;
			}

			// define max and minimum page numbers
			$p_min = $page - $links_count;
			if($p_min < 1)
			{
				$p_min = 1;

				$p_max = $p_min + $links_count * 2;
				if($p_max > $total_pages)
				{
					$p_max = $total_pages;
				}
			}
			else
			{
				$p_max = $page + $links_count;
				if($p_max > $total_pages)
				{
					$p_max = $total_pages;

					$p_min = $p_max - $links_count * 2;
					if($p_min < 1)
					{
						$p_min = 1;
					}
				}
			}



			// create links
			$arr_l = array();

			if($page > 1)
			{
				$arr_l[] = array("st" => ($page - 1) . "", "label" => "&laquo;");
			}
			if($p_min > 1)
			{
				$arr_l[] = array("st" => "1", "label" => "1");
				$arr_l[] = array("st" => "space", "label" => "...");
			}

			for($i = $p_min; $i <= $p_max; $i++)
			{
				if($i == $page)
				{
					$arr_l[] = array("st" => "span", "label" => $i);
				}
				else
				{
					$arr_l[] = array("st" => $i, "label" => $i);
				}
			}
			if($p_max < $total_pages)
			{
				$arr_l[] = array("st" => "space", "label" => "...");
				$arr_l[] = array("st" => "$total_pages", "label" => "$total_pages");
			}
			if($page < $total_pages)
			{
				$arr_l[] = array("st" => ($page + 1), "label" => "&raquo;");
			}


			// create links from array
			foreach($arr_l as $val)
			{
				switch($val['st'])
				{
					case "space":
						$links .= $val['label'];
						break;
					case "span":
						$links .= " <span class=\"sel_page\">" . $val['label'] . "</span> ";
						break;
					default:
						if($pattern)
						{
							if($extralink_alt && $val['st'] == 1)
							{
								$val_st = $extralink_alt;
							}
							else
							{
								$val_st = str_replace("{page}", $val['st'], $extralink);
							}
						}
						else
						{
							$val_st = "?page=" . $val['st'] . $extralink;
						}
						$links .= " <a href=\"$val_st\">" . $val['label'] . "</a> ";
				}
			}//foreach


			if($links)
			{
				$links = '<div class="paginator">' . $links . '</div>';
			}
		}//have any more pages

		return $links;
	}

	public static function display($page, $total_pages, $extralink, $pattern = false, $extralink_alt = "")
	{
		echo self::render($page, $total_pages, $extralink, $pattern, $extralink_alt);
	}

	/**
	 * render simple paginator with prev / next only 
	 * 
	 * @param int $page
	 * @param int $total_pages
	 * @param string $pattern
	 * @return string
	 */
	public static function renderSimple($page, $total_pages, $pattern = array())
	{
		if($total_pages > 1)
		{
			$pattern_default = array(
				'url' => '{NUM}',
				'url_alt' => '{NUM}',
				'wrap' => '<p class="">{PAGE_PREV} {PAGE_NEXT}</p>',
				'page_prev' => '<a href="{URL_PREV}">&loquo;</a>',
				'page_next' => '<a href="{URL_NEXT}">&raquo;</a>',
			);

			$page_prev = '';
			$page_next = '';
			$return = '';

			$pattern = array_merge($pattern_default, $pattern);

			// define page number
			if($page < 1)
			{
				$page = 1;
			}

			if($page > $total_pages)
			{
				$page = $total_pages;
			}

			$page_num_prev = $page - 1;
			if($page_num_prev < 1)
			{
				$page_num_prev = false;
			}
			elseif($page_num_prev == 1 && $pattern['url_alt'])
			{
				$pattern_url = $pattern['url_alt'];
			}
			else
			{
				$pattern_url = $pattern['url'];
			}

			$url_prev = str_replace("{NUM}", $page_num_prev, $pattern_url);
			if($url_prev)
			{
				$page_prev = str_replace("{URL_PREV}", $url_prev, $pattern['page_prev']);
			}


			$page_num_next = $page + 1;
			if($page_num_next > $total_pages)
			{
				$page_num_next = false;
			}
			elseif($page_num_next == 1 && $pattern['url_alt'])
			{
				$pattern_url = $pattern['url_alt'];
			}
			else
			{
				$pattern_url = $pattern['url'];
			}

			$url_next = str_replace("{NUM}", $page_num_next, $pattern_url);
			if($url_next)
			{
				$page_next = str_replace("{URL_PREV}", $url_next, $pattern['page_next']);
			}

			if($page_prev || $page_next)
			{
				$return = str_replace(array(
					'{URL_PREV}',
					'{URL_NEXT}',
					'{PAGE_CUR}',
					'{PAGE_TOTAL}'
						), array(
					$page_prev,
					$page_next,
					$page,
					$total_pages
						), $pattern['wrap']);
			}
		}//have any more pages

		return $return;
	}

}
