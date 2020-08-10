<?php

$vals = array(
	'selected_category'	 => $selected_category,
	'selected_location'	 => $selected_location
);
$ad_shown = array();
echo '<ul class="list_style_' . ($list_style ? $list_style : 'full') . '">';
if ($ads_featured)
{
	foreach ($ads_featured as $_ad)
	{
		if (!isset($ad_shown[$_ad->id]))
		{
			$ad_shown[$_ad->id] = true;
			$vals['ad'] = $_ad;
			echo View::renderAsSnippet('index/_listing_row', $vals);
		}
	}
}

if ($ads)
{
	foreach ($ads as $_ad)
	{
		if (!isset($ad_shown[$_ad->id]))
		{
			$ad_shown[$_ad->id] = true;
			$vals['ad'] = $_ad;
			echo View::renderAsSnippet('index/_listing_row', $vals);
		}
	}
}
echo '</ul>';

// image placeholder 
$_img_placeholder_src = Adpics::imgPlaceholder();
echo '<style>.list_style_full .item_thumb{background-image:url(' . $_img_placeholder_src . ');}</style>';
