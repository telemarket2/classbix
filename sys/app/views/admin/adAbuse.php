<?php echo $this->validation()->messages() ?>

<h1 class="mt0"><?php echo __('Abuse reports') . ($ad ? ' <a href="' . Ad::url($ad) . '" class="h4 muted">' . View::escape(Ad::getTitle($ad)) . '</a>' : ''); ?></h1>

<?php
if ($ad)
{
	echo '<p><a href="' . Language::get_url('admin/itemAbuse/') . '" class="button">'
	. '<i class="fa fa-fw fa-arrow-left" aria-hidden="true"></i> '
	. __('View abuse reports for all ads')
	. '</a></p>';
}
?>



<?php
if ($adabuse)
{
	?>
	<ul class="list_style_admin">
		<?php
		foreach ($adabuse as $abuse)
		{
			$ad_info = '';
			if (!$ad)
			{
				$ad_info = '<a href="' . Ad::url($abuse->Ad) . '" target="_blank">' . View::escape($abuse->Ad->title) . '</a>' . Ad::labelAbused($abuse->Ad);
			}

			$button = '<a href="#delete" class="delete button red" r_id="' . $abuse->id . '">' . __('Delete') . '</a>';

			$arr_extras = array();
			if (strlen($abuse->ip) > 1)
			{
				$arr_extras[] = View::escape($abuse->ip);
			}
			$arr_extras[] = Config::dateTime($abuse->added_at);
			if ($abuse->User)
			{
				//http://localhost/simpleclassifiedsscript/admin/items/?page=2&location_id=0&email=vepa_hal%40hotmail.com&category_id=0
				$arr_extras[] = ' <a href="' . Language::get_url('admin/items/?email=' . urlencode($abuse->User->email)) . '" target="_blank">' . View::escape($abuse->User->email) . '</a>';
			}



			echo '<li class="item img-no' . ( ' r' . ($row++ % 2)) . '">'
			. '<div class="item_content">'
			. ($abuse->reason ? '<p class="abuse_message">'
					. '<span class="abuse_message_label">' . __('Message') . '</span> ' . View::escape($abuse->reason) . '</p>' : '')
			. ($ad ? '' : Ad::labelAbused($abuse->Ad) . ' <a href="' . Ad::url($abuse->Ad) . '" class="item_title">' . View::escape($abuse->Ad->title) . '</a>' )
			. '<ul class="item_extra"><li>' . implode('</li><li>', $arr_extras) . '</li></ul>'
			. '</div>'
			. '<div class="controls">
				<a class="button red delete" title="' . __('Delete report') . '" r_id="' . $abuse->id . '"><i class="fa fa-trash" aria-hidden="true"></i></a>
				</div>'
			. '</li>';
		}
		?>
	</ul>
	<?php
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No records found.') . '</p></div>';
}
?>




<?php echo $paginator; ?>


<script>
	addLoadEvent(function ()
	{
		$('.list_style_admin').on('click', '.delete', deleteAbuse);
	});

	function deleteAbuse()
	{
		var $me = $(this);
		var id = $me.attr('r_id');
		var $tr = $me.parents('li:first');

		if (confirm('<?php echo __('Do you wnat to delete this record?'); ?>'))
		{
			$.post(BASE_URL + 'admin/itemAbuseDelete/', {id: id}, function (data)
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
