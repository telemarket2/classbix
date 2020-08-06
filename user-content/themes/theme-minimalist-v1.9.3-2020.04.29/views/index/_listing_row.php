<?php

$_img_thumb = Adpics::imgThumb($ad->Adpics, '', $ad->User);

if($_img_thumb)
{
	$thumb = '<a href="' . Ad::url($ad) . '" class="thumb"><img src="'
			. $_img_thumb . '" width="'
			. Config::option('ad_thumbnail_width') . '"  height="'
			. Config::option('ad_thumbnail_height') . '" alt="'
			. View::escape(Ad::getTitle($ad)) . '" /></a>';
}
else
{
	$thumb = '';
}

if($ad->featured)
{
	$featured = ' <span class="label_text green small">' . __('Featured') . '</span>';
}
else
{
	$featured = '';
}


// location and category name with links
$loc_cat = Ad::formatLocationCategoryLink($ad, ', ');

// format custom fields as simple string 
$custom_fields_simple = Ad::formatCustomFieldsSimple($ad, $catfield);
if($custom_fields_simple)
{
	$loc_cat .= ' | ' . $custom_fields_simple;
}

if($loc_cat)
{
	$loc_cat = '<p class="extra_text">' .$loc_cat . '</p>';
}





echo '<tr class="r' . ($tr++ % 2) . ($ad->featured ? ' featured' : '') . '">
			<td>' . $thumb . '
			<h2><a href="' . Ad::url($ad) . '">' . View::escape(Ad::getTitle($ad)) . '</a>' . $featured . '</h2>
			<p>' . View::escape(Ad::snippet($ad)) . '</p>
				' . $loc_cat . '				
			</td>
			<td>'
  . Ad::formatDate($ad) . '</td>';
foreach($catfield as $cf)
{
	if($cf->is_list)
	{
		echo Ad::formatCustomFieldByAFR($cf->AdField, $ad, '<td{schema_item_scope}><span{schema_item_prop}>{value}</span>{schema_item_extra}</td>');
	}
}
echo '</tr>';
