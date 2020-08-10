<?php

//simple list 
$return = '<ul class="list_style_simple">';
foreach ($ads as $ad)
{
	// price
	$price = AdFieldRelation::getPrice($ad);
	if ($price)
	{
		$price = '<span class="price">' . $price . '</span>';
	}

	$return .= '<li>'
			. '<a href="' . Ad::url($ad) . '" class="link clearfix">'
			. $price
			. ($ad->Adpics ? '<i class="fa fa-image" title="' . View::escape(__('item with image')) . '"></i> ' : '')
			. '<span class="title">' . View::escape(Ad::getTitle($ad)) . '</span>'
			. '<span class="description">' . View::escape(Ad::snippet($ad, 50)) . '</span>'
			. '</a>'
			. '</li>';
}
$return .= '</ul>';

echo $return;
