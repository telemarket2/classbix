<!-- display user listings -->
<div>
<?php
echo '<h1>' . View::escape($selected_user->name) . '</h1>';


if($selected_user->level == User::PERMISSION_DEALER)
{
	if($selected_user->logo)
	{
		$logo = '<img src="' . User::logo($selected_user) . '" class="user_logo" />';
	}
	else
	{
		$logo = '';
	}
	echo '<p>' . $logo . Config::formatText($selected_user->info) . '</p>';
	if($selected_user->web)
	{
		echo '<p><a href="' . $selected_user->web . '" rel="nofollow" target="_blank">' . View::escape($selected_user->web) . '</a></p>';
	}
}

// display ads
if($ads)
{
	echo new View('index/_listing', $this->vars);
	echo $paginator;
}
else
{
	echo '<p>' . __('No records found.') . '</p>';
	echo '<p><a href="' . Ad::urlPost($selected_location, $selected_category) . '" class="button big primary" rel="nofollow">' . View::escape(Config::optionElseDefault('site_button_title', __('Post ad')))  . '</a></p>';
	echo '<p><a href="' . Language::urlHome() . '">' . __('Back to home page') . '</a></p>';
}
?>
</div>