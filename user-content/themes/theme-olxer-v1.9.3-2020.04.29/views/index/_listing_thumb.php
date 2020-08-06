<div class="thumbs<?php echo ($thumbs_single ? ' thumbs_single' : '') . ' list_style_' . $list_style; ?>">
	<?php
	$thumb_width = $thumb_width ? $thumb_width : Config::option('ad_thumbnail_width');
	$thumb_height = $thumb_height ? $thumb_height : Config::option('ad_thumbnail_height');
	foreach($ads as $ad)
	{
		$_img_thumb = Adpics::imgThumb($ad->Adpics, $thumb_width . 'x' . $thumb_height . 'x1', $ad->User, true);
		$price = AdFieldRelation::getPrice($ad);
		$_img_title = View::escape(Ad::getTitle($ad)) . ($price ? ' - ' . $price : '');
		$thumb = '<img src="' . $_img_thumb . '" alt="' . $_img_title . '" />';
		echo '<a href="' . Ad::url($ad) . '" title="' . $_img_title . '" style="width:' . $thumb_width . 'px;">'
		. $thumb
		. '<span class="title">' . View::escape(Ad::getTitle($ad)) . '</span>'
		. ($price ? '<span class="price">' . View::escape($price) . '</span>' : '')
		. '</a> ';
	}
	?>
</div>