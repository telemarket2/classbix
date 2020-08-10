<?php

// display widgets 
if(isset($widgets))
{
	//print_r($widgets);

	foreach($widgets as $widget)
	{
		echo $widget->render;
	}
}

if($page_description)
{
	echo '<h3>' . View::escape($meta->title) . '</h3>';
	echo '<p>' . Config::formatText($page_description) . '</p>';
}
