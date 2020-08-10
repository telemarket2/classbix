<!-- display user listings -->
<div>
	<?php
	$_dealer_logo = '';
	$_dealer_text = '';

	if ($selected_user->level == User::PERMISSION_DEALER)
	{
		if ($selected_user->logo)
		{
			$_img_placeholder_src = Adpics::imgPlaceholder();
			$_img_thumb = User::logo($selected_user, null, 'lazy');
			$_dealer_logo = '<img src="' . $_img_placeholder_src . '"'
					. ($_img_thumb ? ' class="lazy user_logo" data-src="' . $_img_thumb . '"' : ' class="user_logo"')
					. ' alt="' . View::escape($selected_user->name) . '" loading="lazy" />';
		}

		$_dealer_text = '<p>' . Config::formatText($selected_user->info) . '</p>';
		if ($selected_user->web)
		{
			$_dealer_text .= '<p>' . TextTransform::str2Link($selected_user->web) . '</p>';
		}
	}

	$_dealer_meta_arr = array();
	$_dealer_meta_arr[] = __('on site for {time}', array('{time}' => Config::timeRelative($selected_user->added_at, 1, false)));
	if ($selected_user->countAds > 1)
	{
		$_dealer_meta_arr[] = __('{num} items', array('{num}' => View::escape($selected_user->countAds)));
	}

	$_dealer_info = '<div class="dealer_page_info clearfix my1">'
			. ($_dealer_logo ? '<div class="dealer_logo">' . $_dealer_logo . '</div>' : '')
			. '<div class="dealer_content">'
			. '<h1 class="dealer_page_title">'
			. View::escape($selected_user->name) . '</h1>'
			. '<p class="dealer_page_meta">' . implode(' • ', $_dealer_meta_arr) . '</p>'
			. '</div>'
			. '</div>';

	echo $_dealer_info . $_dealer_text;
	


	// display ads
	if ($ads)
	{
		echo new View('index/_listing', $this->vars);
		echo $paginator;
	}
	else
	{
		echo '<h3>' . __('No records found.') . '</h3>';
		echo '<p>'
		. '<a href="' . Ad::urlPost($selected_location, $selected_category) . '" class="button primary" rel="nofollow">'
		. '<i class="fa fa-plus" aria-hidden="true"></i> '
		. View::escape(Config::optionElseDefault('site_button_title', __('Post ad')))
		. '</a>'
		. '<a href="' . Language::urlHome() . '" class="button link">'
		. '<i class="fa fa-arrow-left" aria-hidden="true"></i> '
		. __('Back to home page')
		. '</a>'
		. '</p>';
	}
	?>
</div>