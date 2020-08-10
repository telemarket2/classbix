<!-- display categories -->
<div>
	<?php
	echo Ad::cqFormatFilterRemover($cq, array('icon' => '<i class="fa fa-times" aria-hidden="true"></i>'));

	if ($page_title)
	{
		echo '<h1>'
		. View::escape($page_title)
		. ($total_ads ? ' <sup class="item_count">' . number_format($total_ads) . '</sup>' : '')
		. '</h1>';
	}
	if ($page_description)
	{
		echo '<p>' . Config::formatText($page_description) . '</p>';
	}
	

	if ($search_related->str)
	{
		echo '<p class="search_related">' . $search_related->str . '</p>';
	}

	// display ads
	if ($ads)
	{
		echo new View('index/_listing', $this->vars);
		echo $paginator;
	}
	else
	{
		if ($is_search)
		{
			echo '<p>' . __('No results for this search criteria. Please try reducing search parameters or try different values.') . '</p>';
			if ($selected_category)
			{
				echo '<p><a href="' . Location::url($selected_location, $selected_category) . '">' . __('View all listings in this category.') . '</a></p>';
			}
			if (strlen(trim($_GET['q'])))
			{
				// display googl esearch link 
				echo '<p><a href="https://www.google.com/search?q=' . urlencode($_GET['q'] . ' site:' . DOMAIN) . '" target="_blank">'
				. __('Search <b>"{name}"</b> on google.com', array('{name}' => View::escape(trim($_GET['q'])))) . '</a></p>';
			}
		}
		else
		{
			echo '<p>' . __('There is no ads in this category.') . '</p>';

			if ($related_pages)
			{
				$_related_pages = array();
				foreach ($related_pages as $rp_url => $rp_title)
				{
					$_related_pages[] = '<a href="' . $rp_url . '">' . $rp_title . '</a>';
				}
				echo '<p>' . __('Related pages') . ': ' . implode(', ', $_related_pages) . '</p>';
			}
		}
		echo '<p><a href="' . Ad::urlPost($selected_location, $selected_category) . '" class="button big primary" rel="nofollow"><i class="fa fa-plus" aria-hidden="true"></i> ' . View::escape(Config::optionElseDefault('site_button_title', __('Post ad'))) . '</a> '
				. '<a href="' . Language::urlHome() . '" class="button link"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . __('Back to home page') . '</a></p>';
	}
	?>
</div>