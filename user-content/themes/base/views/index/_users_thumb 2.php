<ul class="thumbs<?php echo ' list_style_' . $list_style . $css_class; ?>">
	<?php
	$thumb_width = $thumb_width ? $thumb_width : Config::option('ad_thumbnail_width');
	$thumb_height = $thumb_height ? $thumb_height : Config::option('ad_thumbnail_height');
	$_img_placeholder_src = Adpics::imgPlaceholder($thumb_width, $thumb_height);

	foreach ($users as $user)
	{
		$_img_title = View::escape($user->name);
		$_img_thumb = User::logo($user, $thumb_width . 'x' . $thumb_height . 'x2', 'lazy');
		$thumb = '<img src="' . $_img_placeholder_src . '"'
				. ($_img_thumb ? ' class="lazy" data-src="' . $_img_thumb . '"' : '')
				. ' alt="' . $_img_title . '" '
				. ' width="' . $thumb_width . '"'
				. ' height="' . $thumb_height . '" />';


		echo '<li class="thumb_item">'
		. '<a href="' . User::url($user) . '" title="' . $_img_title . '" style="width:' . $thumb_width . 'px;" class="thumb_link">'
		. $thumb
		. '<span class="title">' . $_img_title . '</span>'
		. ($display_ad_count ? '<span class="item_count">' . number_format($user->num_ads) . '</span>' : '')
		. '</a>'
		. '</li>';
	}
	?>
</ul>