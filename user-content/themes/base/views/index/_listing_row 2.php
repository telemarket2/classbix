<?php

// get picture url
$_img_thumb = Adpics::imgThumb($ad->Adpics, '', $ad->User, 'lazy', $ad);
if ($_img_thumb)
{
	$thumb = '<span class="item_thumb lazy" data-src="' . $_img_thumb . '"></span>';
}
else
{
	$thumb = '';
}


if ($ad->featured)
{
	$featured = ' <span class="label_text label_featured green small">' . __('Featured') . '</span>';
}
else
{
	$featured = '';
}


/* format extra values */
$arr_extra = array();
$arr_extra[] = Ad::formatDate($ad);
// location
$location_name = Location::getNameById($ad->location_id);
if (strlen($location_name) > 0)
{
	$arr_extra[] = $location_name;
}
// category
$category_name = Category::getNameById($ad->category_id);
if (strlen($category_name) > 0)
{
	$arr_extra[] = $category_name;
}

// format custom fields as simple string 
$custom_fields_simple = Ad::formatCustomFieldsSimpleOptions($ad, array(
			'catfields_exclude_type' => array(AdField::TYPE_VIDEO_URL => true, AdField::TYPE_PRICE => true),
			'seperator'				 => ' | ',
			'make_link'				 => false
		));
if ($custom_fields_simple)
{
	$arr_extra[] = $custom_fields_simple;
}

$price = AdFieldRelation::getPrice($ad);
if ($price)
{
	$price = ' <span class="item_price">' . $price . '</span>';
}

$arr_replace = array();
$arr_replace['{class_img}'] = ($thumb ? 'img-yes' : 'img-no') . ($ad->featured ? ' featured' : '');
$arr_replace['{id}'] = $ad->id;
$arr_replace['{url}'] = Ad::url($ad);
$arr_replace['{img}'] = $thumb;
$arr_replace['{title}'] = View::escape(Ad::getTitle($ad));
$arr_replace['{labels}'] = $featured;
$arr_replace['{content}'] = View::escape(Ad::snippet($ad));
$arr_replace['{price}'] = $price;
$arr_replace['{extras}'] = '<small class="item_extra">'
		. strip_tags(implode(' | ', $arr_extra))
		. '</small>';


$pattern = '<li class="item {class_img}" data-item="{id}">
				<a href="{url}" class="item_link clearfix">
					{img}
					<span class="item_content">
						{price}
						<span class="item_title clearfix">{title}{labels}</span>
						<span class="item_description clearfix">{content}</span>
						{extras}
					</span>
				</a>
			</li>';

echo str_replace(array_keys($arr_replace), array_values($arr_replace), $pattern);
