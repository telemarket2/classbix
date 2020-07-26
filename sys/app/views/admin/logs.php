<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo View::escape($title) . ($url_settings ? ' <a href="' . $url_settings . '" class="button">' . __('Settings') . '</a>' : '') ?></h1>
<?php
// display links to other logs 
$arr_uc = array();
$arr_other = array(
	IpBlock::TYPE_LOGIN		 => array(
		'url'	 => Language::get_url('admin/logs/' . IpBlock::TYPE_LOGIN . '/'),
		'title'	 => __('Invalid login attempts')
	),
	IpBlock::TYPE_CONTACT	 => array(
		'url'	 => Language::get_url('admin/logs/' . IpBlock::TYPE_CONTACT . '/'),
		'title'	 => __('Contact form spam')
	),
	IpBlock::TYPE_POST		 => array(
		'url'	 => Language::get_url('admin/logs/' . IpBlock::TYPE_POST . '/'),
		'title'	 => __('Throttled postings')
	)
);
foreach ($arr_other as $key => $val)
{
	$val_title = $val['title'] . ( $count[$key] ? '<sup class="muted">' . $count[$key] . '</sup>' : '');
	if ($key == $type)
	{
		$arr_uc[] = '<span class="active">' . $val_title . '</span>';
	}
	else
	{
		if ($count[$key] > 0)
		{
			$arr_uc[] = '<a href="' . $val['url'] . '">' . $val_title . '</a>';
		}
	}
}
if (count($arr_uc) > 1)
{
	echo '<p class="tabs_static">' . implode(' ', $arr_uc) . '</p>';
}



// display data
if ($data)
{
	// display grouping options
	if ($group_by)
	{
		$arr_uc = array();
		if (!$group)
		{
			$arr_uc[] = '<span class="active">' . __('none') . '</span>';
		}
		else
		{
			$arr_uc[] = '<a href="' . Language::get_url('admin/logs/' . $type . '/') . '">' . __('none') . '</a>';
		}

		foreach ($group_by as $val)
		{
			if ($val == $group)
			{
				$arr_uc[] = '<span class="active">' . View::escape($val) . '</span>';
			}
			else
			{
				$arr_uc[] = '<a href="' . Language::get_url('admin/logs/' . $type . '/' . View::escape($val) . '/') . '">' . View::escape($val) . '</a>';
			}
		}

		$echo_group = '<p class="tabs_static">' . __('Group by') . ': ' . implode(' ', $arr_uc) . '</p>';
		echo $echo_group;
	}



	$row_num = 0;
	$arr_num_row = array();
	$th = '';
	$pattern_num = '<small class="muted">{num} - </small> ';
	$first_col = true;
	foreach ($cols as $col)
	{
		$th .= '<th>' . ($first_col ? str_replace('{num}', '#', $pattern_num) : '') . View::escape($col) . '</th>';
		$first_col = false;
	}
	$th = '<tr>' . $th . '</tr>';

	if ($group)
	{
		foreach ($data as $val)
		{
			//$key = $val->{$group};
			$key = IpBlock::formatValue($val, $group);
			if (!isset($arr_num_row[$key]))
			{
				$arr_num_row[$key] = 0;
				$echo[$key] = '';
			}
			$echo[$key] .= '<tr class="r' . ($tr++ % 2) . '">';
			$first_col = true;
			foreach ($cols as $col)
			{
				if ($col != $group)
				{
					$echo[$key] .= '<td>'
							. ($first_col ? str_replace('{num}', ( ++$arr_num_row[$key]), $pattern_num) : '')
							. IpBlock::formatValue($val, $col)
							. '</td>';
					$first_col = false;
				}
			}
			$echo[$key] .= '</tr>';
		}

		echo '<table class="grid tblmin">';
		foreach ($echo as $key => $_echo)
		{
			echo '<tr><th colspan="' . (count($cols) - 1) . '">' . ($group . ': ' . $key) . '</th></tr>'
			. $_echo;
		}
		echo '</table>';
	}
	else
	{
		echo '<table class="grid tblmin">' . $th;
		foreach ($data as $val)
		{
			echo '<tr class="r' . ($tr++ % 2) . '">';
			$first_col = true;
			foreach ($cols as $col)
			{
				echo '<td>'
				. ($first_col ? str_replace('{num}', ( ++$row_num), $pattern_num) : '')
				. IpBlock::formatValue($val, $col)
				. '</td>';
				$first_col = false;
			}
			echo '</tr>';
		}
		echo '</table>';
	}
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No records found.') . '</p></div>';
}