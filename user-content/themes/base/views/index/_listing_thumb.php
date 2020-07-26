<ul class="thumbs<?php echo ' list_style_' . $list_style . $css_class; ?>">
	<?php
	$thumb_width = $thumb_width ? $thumb_width : Config::option('ad_thumbnail_width');
	$thumb_height = $thumb_height ? $thumb_height : Config::option('ad_thumbnail_height');
	$_img_placeholder_src = Adpics::imgPlaceholder($thumb_width, $thumb_height);
	foreach ($ads as $ad)
	{
		$_img_thumb = Adpics::imgThumb($ad->Adpics, $thumb_width . 'x' . $thumb_height . 'x1', $ad->User, 'lazy', $ad);
		$price = AdFieldRelation::getPrice($ad);
		if ($ad->featured)
		{
			$featured = ' <span class="label_text label_featured green small">' . __('Featured') . '</span>';
		}
		else
		{
			$featured = '';
		}
		$_img_title = View::escape(Ad::getTitle($ad)) . ($price ? ' - ' . $price : '');
		$thumb = '<img src="' . $_img_placeholder_src . '"'
				. ($_img_thumb ? ' class="lazy" data-src="' . $_img_thumb . '"' : '')
				. ' alt="' . $_img_title . '" '
				. ' width="' . $thumb_width . '"'
				. ' height="' . $thumb_height . '" />';

		echo '<li class="thumb_item' . ($ad->featured ? ' featured' : '') . '">'
		. '<a href="' . Ad::url($ad) . '" title="' . $_img_title . '" style="width:' . $thumb_width . 'px;" class="thumb_link">'
		. $thumb
		. '<span class="title">' . View::escape(Ad::getTitle($ad)) . '</span>'
		. ($price ? '<span class="price">' . View::escape($price) . '</span>' : '')
		. $featured
		. '</a>'
		. '</li>';
	}
	?>
</ul>