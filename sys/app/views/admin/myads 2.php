<?php echo $this->validation()->messages() ?>

<?php

if ($ads)
{
	$arr_echo = array();
	foreach ($types as $k => $v)
	{
		if ($v['total'] > 0)
		{
			$arr_echo[] = '<a href="' . Language::get_url('admin/itemsmy/1/' . $v['url'] . $orders[$order]['url']) . '"'
					. ($k === $type ? ' class="active"' : '') . '>'
					. View::escape($v['title']) . ' <sup class="muted">' . $v['total'] . '</sup></a>';
		}
	}
	$arr_echo_order = array();
	if ($types[$type]['total'] > 5)
	{
		foreach ($orders as $k => $v)
		{
			$arr_echo_order[] = '<a href="' . Language::get_url('admin/itemsmy/1/' . $types[$type]['url'] . $v['url']) . '"'
					. ($k === $order ? ' class="active"' : '') . '>'
					. View::escape($v['title']) . '</a>';
		}
	}

	$arr_echo_all = array();
	if (count($arr_echo) > 1)
	{
		$arr_echo_all[] = implode(' ', $arr_echo);
	}

	if ($arr_echo_order)
	{
		$arr_echo_all[] = __('Order by') . ' ' . implode(' ', $arr_echo_order);
	}

	if ($arr_echo_all)
	{
		// show order option 		
		echo '<p class="tabs_static">' . implode(' | ', $arr_echo_all) . '</p>';
	}


	// use snippet because this listing used in many places 
	$vals = array(
		'ads'		 => $ads,
		'returl'	 => $returl,
		'paginator'	 => $paginator
	);
	echo View::renderAsSnippet('admin/_listing', $vals);
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No ads found.')
	. ' <a href="' . Language::get_url('post/item/') . '" class="button primary">'
	. View::escape(Config::optionElseDefault('site_button_title', __('Post ad')))
	. '</a>'
	. '</p>'
	. '</div>';
}
	