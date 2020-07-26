<?php echo $this->validation()->messages() ?>
<h2 class="mt0">
	<?php
	echo Ad::statusName(Ad::STATUS_PENDING_APPROVAL);
	if ($ad->prev)
	{
		echo ' <a href="' . Language::get_url('admin/itemsPending/' . intval($ad->prev->added_by) . '/')
		. '" class="button" title="' . View::escape(__('Previous')) . '"><i class="fa fa-arrow-left" aria-hidden="true"></i></a> ';
	}
	if ($ad->next)
	{
		echo ' <a href="' . Language::get_url('admin/itemsPending/' . intval($ad->next->added_by) . '/')
		. '" class="button" title="' . View::escape(__('Next')) . '"><i class="fa fa-arrow-right" aria-hidden="true"></i></a> ';
	}
	?>
</h2>
<?php
if ($ads)
{
	// approve all button 
	$total_current = count($ads);
	if ($total_current < $total)
	{
		// has more than one page pending approval items. so show as fraction 
		$str_count = $total_current . '/' . $total;
	}
	else
	{
		$str_count = $total_current;
	}
	$approve_all = '<button class="approve_all button primary">' . __('Approve all') . '<sup class="muted">' . $str_count . '</sup></button>';


	// display user info 
	if ($user)
	{
		$count_buttons = User::countAdTypeButtons($user);
		$karma = User::karma($user);
		$karma_badge = ' <span class="label_text small ' . ($karma > 50 ? '' : 'red') . '">' . $karma . '%</span>';

		echo '<h3><a href="' . Language::get_url('admin/users/edit/' . $user->id . '/') . '">'
		. View::escape($user->name) . $karma_badge . '</a> '
		. $approve_all
		. '</h3>'
		. '<p>'
		. __('on site for {time}', array('{time}' => Config::timeRelative($user->added_at, 1, false))) . ' '
		. $count_buttons
		. '</p>';
	}
	else
	{
		echo '<p>' . $approve_all . '</p>';
	}




	// use snippet because this listing used in many places 
	$vals = array(
		'ads'	 => $ads,
		'returl' => $returl,
	);
	echo '<div class="items_pending">' . View::renderAsSnippet('admin/_listing', $vals) . '</div>';

	if ($ads_other)
	{
		// other total 
		echo '<h3>Other items <sup>' . $total_other . '</sup></h3>';
		// other items by same user
		$vals = array(
			'ads'	 => $ads_other,
			'returl' => $returl,
		);
		echo View::renderAsSnippet('admin/_listing', $vals);
	}

	$total_all = $total_other + $total;
	if ($total_all > (count($ads_other) + count($ads)))
	{
		// show view all ads by user button 
		echo '<a href="' . Language::get_url('admin/items/?added_by=' . $ad->added_by) . '" class="button block">' . __('View all') . ' ' . intval($total_all) . '</a>';
	}
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No ads found.') . '</p></div>';
}
?>

<script language="javascript">
	addLoadEvent(function ()
	{
		$(document).on('click', '.approve_all', approveAll);
	});

	function approveAll()
	{
		// approve all pending items
		var $form = $('.items_pending form');

		// select all checkboxes in pending approval
		$form.find(':checkbox[name="ad[]"]').prop('checked', true);
		// set command
		$form.find('#bulk_actions').val('approve');
		// submit form 
		$form.submit();
	}
</script>