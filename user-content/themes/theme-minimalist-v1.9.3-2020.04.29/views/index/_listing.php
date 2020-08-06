<?php

// display ads

$th = array();

// define custom field columns 
if(!$catfield)
{
	$catfield = array();
}

foreach($catfield as $cf)
{
	if($cf->is_list)
	{
		$th[$cf->adfield_id] = '<th>' . AdField::getName($cf->AdField) . '</th>';
	}
}

echo '<table class="grid listing list_style_' . ($list_style ? $list_style : 'full') . '">
		';

//var_dump($ads_featured);
$vals = array(
	'selected_category' => $selected_category,
	'selected_location' => $selected_location,
	'catfield' => $catfield,
	'tr' => 0
);

if($ads_featured)
{
	echo '<tr>
		<th>' . __('Featured ads') . '</th>
		<th>' . __('Date') . '</th>
		' . implode('', $th) . '
		</tr>';
	foreach($ads_featured as $_ad)
	{
		$vals['ad'] = $_ad;
		$vals['tr']++;
		echo View::renderAsSnippet('index/_listing_row', $vals);
	}
}

if($ads)
{
	echo '<tr>
		<th>' . __('Ads') . '</th>
		<th>' . __('Date') . '</th>
		' . implode('', $th) . '
		</tr>';
	foreach($ads as $_ad)
	{
		if(!isset($ads_featured[$_ad->id]))
		{
			$vals['ad'] = $_ad;
			$vals['tr']++;
			echo View::renderAsSnippet('index/_listing_row', $vals);
		}
	}
}
echo '</table>';
