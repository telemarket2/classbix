<?php echo $this->validation()->messages() ?>
<?php
$arr_tabs = array(
	IpBlock::TYPE_ACCESS	 => array(
		'title_button'	 => ' <a href="#" class="button blue add_new_button" title="' . View::escape(__('Add')) . '"><i class="fa fa-plus" aria-hidden="true"></i></a>',
		'url'			 => Language::get_url('admin/ipBlock/' . IpBlock::TYPE_ACCESS . '/'),
		'title'			 => __('Manual block'),
		'exp_minutes'	 => 0
	),
	IpBlock::TYPE_LOGIN		 => array(
		'title_button'	 => ' <a href="' . Language::get_url('admin/settingsSpam/#grp_ipblock_login_attempt') . '" class="button">' . __('Settings') . '</a>',
		'url'			 => Language::get_url('admin/ipBlock/' . IpBlock::TYPE_LOGIN . '/'),
		'title'			 => __('Invalid login attempts'),
		'exp_minutes'	 => Config::option('ipblock_login_ban_period')
	),
	IpBlock::TYPE_CONTACT	 => array(
		'title_button'	 => ' <a href="' . Language::get_url('admin/settingsSpam/#grp_ipblock_contact_limit') . '" class="button">' . __('Settings') . '</a>',
		'url'			 => Language::get_url('admin/ipBlock/' . IpBlock::TYPE_CONTACT . '/'),
		'title'			 => __('Contact form spam'),
		'exp_minutes'	 => Config::option('ipblock_contact_ban_period')
	)
);
?>
<h1 class="mt0"><?php echo __('Blocked IPs') . $arr_tabs[$type]['title_button']; ?></h1>

<?php
foreach ($arr_tabs as $key => $val)
{
	if ($key == $type)
	{
		$arr_uc[] = '<span class="active">' . $val['title'] . '<sup class="muted">' . $count[$key] . '</sup>' . '</span>';
	}
	else
	{
		if ($count[$key] > 0)
		{
			$arr_uc[] = '<a href="' . $val['url'] . '">' . $val['title'] . '<sup class="muted">' . $count[$key] . '</sup>' . '</a>';
		}
	}
}
if (count($arr_uc) > 1)
{
	// show tabs if more than 1 tab 
	echo '<p class="tabs_static">' . implode(' ', $arr_uc) . '</p>';
}


if ($type == IpBlock::TYPE_ACCESS)
{
	?>
	<form action="" method="post" class="add_new display-none">
		<h3><?php echo __('Add new records') ?></h3>

		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label">
				<label for="ips"><?php echo __('IPs seperated by new line.'); ?></label>
			</div>
			<div class="col col-12 sm-col-10 px1">
				<textarea name="ips" id="ips"></textarea>
				<p>	<?php echo __('Example') ?>: <br/>
					192.168.0.1<br/>
					192.168.10.100 - 192.168.10.255
				</p>
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"></div>
			<div class="col col-12 sm-col-10 px1">
				<input type="submit" name="submit" id="submit" class="button" value="<?php echo __('Submit'); ?>" />
				<a href="#" class="button link cancel add_new_cancel"><?php echo __('Cancel') ?></a>
			</div>
		</div>
	</form>
	<?php
}// type 0



if ($ipblocks)
{
	echo '<table class="grid">
			<tr>
				<th>' . __('Blocked IPs') . ' <sup class="muted">' . __('Hits') . '<sup></th>
				' . ($type != IpBlock::TYPE_ACCESS ? '<th>' . __('Time') . '</th><th>' . __('Expires') . '</th>' : '') . '
				<th></th>		
			</tr>';

	foreach ($ipblocks as $ipblock)
	{
		echo '<tr class="r' . ($tr++ % 2) . '">';
		echo '<td>' . IpBlock::str2ip($ipblock->ip)
		. (strlen($ipblock->ip_end) ? '-' . IpBlock::str2ip($ipblock->ip_end) : '')
		. ($ipblock->num > 0 ? '<sup class="muted">' . View::escape($ipblock->num) . '</sup>' : '')
		. '</td>';
		if ($type != IpBlock::TYPE_ACCESS)
		{
			echo '<td data-title="' . View::escape(__('Time')) . '">' . Config::dateTime($ipblock->added_at) . '</td>'
			. '<td data-title="' . View::escape(__('Expires')) . '">' . Config::timeRelative($ipblock->added_at + $arr_tabs[$type]['exp_minutes'] * 60, 2) . '</td>';
		}
		echo '<td class="right-align"><a href="#delete" class="delete button red" r_id="' . $ipblock->id . '" title="'
		. View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a></td>';
		echo '</tr>';
	}
	echo '</table>';

	echo $paginator;
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No records found.') . '</p></div>';
}
?>
<script>
	$(function ()
	{
		$('.delete').click(deleteIpBlock);
		$('.add_new').hide().removeClass('display-none');
		$('.add_new_button,.add_new_cancel').click(function ()
		{
			$('.add_new').slideToggle();
			return false;
		});
	});

	function deleteIpBlock()
	{
		var $me = $(this);
		var id = $me.attr('r_id');
		var $tr = $me.parents('tr:first');

		if (confirm('<?php echo __('Do you wnat to delete this record?'); ?>'))
		{
			$.post(BASE_URL + 'admin/ipBlockDelete/', {id: id}, function (data)
			{
				if (data == 'ok')
				{
					$tr.remove();
				}
				else
				{
					alert('Error: ' + data);
				}
			});
		}
		return false;
	}
</script>