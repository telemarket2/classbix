<!-- display categories -->
<div>
	<?php
	echo '<h1>' . View::escape($page_title) . '</h1>';
	echo '<p>' . Config::formatText($page_description) . '</p>';
	

	if($search_related->str)
	{
		echo '<p class="search_related">' . $search_related->str . '</p>';
	}

	if($search_desc_arr)
	{
		$search_desc .= '<span class="search_filter">' . implode('</span>, <span class="search_filter">', $search_desc_arr) . '</span>';
		$search_desc .= ' <a href="' . Location::url($selected_location, $selected_category) . '" class="view_all" 
		title="' . Config::buildTitle(array(Category::getName($selected_category), Location::getName($selected_location))) . '">' . __('View all') . ' &rarr;</a>';
		echo '<p class="search_desc">' . $search_desc . '</p>';
	}

// display ads
	if($ads)
	{
		echo new View('index/_listing', $this->vars);
		echo $paginator;
	}
	else
	{
		if($is_search)
		{
			echo '<p>' . __('No results for this search criteria. Please try reducing search parameters or try different values.') . '</p>';
			if($selected_category)
			{
				echo '<p><a href="' . Location::url($selected_location, $selected_category) . '">' . __('View all listings in this category.') . '</a></p>';
			}
			if(strlen(trim($_GET['q'])))
			{
				// display googl esearch link 
				echo '<p><a href="https://www.google.com/search?q=' . urlencode($_GET['q'] . ' site:' . DOMAIN) . '" target="_blank">'
				. __('Search <b>"{name}"</b> on google.com', array('{name}' => View::escape(trim($_GET['q'])))) . '</a></p>';
			}
		}
		else
		{
			echo '<p>' . __('There is no ads in this category.') . '</p>';

			if($related_pages)
			{
				$_related_pages = '';
				foreach($related_pages as $rp_url => $rp_title)
				{
					$_related_pages[] = '<a href="' . $rp_url . '">' . $rp_title . '</a>';
				}
				echo '<p>' . __('Related pages') . ': ' . implode(', ', $_related_pages) . '</p>';
			}
		}
		echo '<p><a href="' . Ad::urlPost($selected_location, $selected_category) . '" class="button big primary" rel="nofollow">' . View::escape(Config::optionElseDefault('site_button_title', __('Post ad'))) . '</a></p>';
		echo '<p><a href="' . Language::urlHome() . '">&larr; ' . __('Back to home page') . '</a></p>';
	}
	?>
</div>