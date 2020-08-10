<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Locations') ?> <a href="<?php echo Language::get_url('admin/locationsEdit/0/' . $parent_location->id . '/') ?>" class="button blue" title="<?php echo View::escape(__('Add')); ?>"><i class="fa fa-plus" aria-hidden="true"></i></a></h1>
<?php
if ($locations)
{
	echo '<p>' . __('Click on location name to view and edit sublocations.') . '</p>';
	if ($parent_location->id)
	{
		$back_title = '<a href="' . Language::get_url('admin/locations/' . intval($parent_location->parent_id) . '/') . '"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . __('Name') . '</a>';
	}
	else
	{
		$back_title = __('Name');
	}
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php echo $back_title . ' <span class="button small green circle">' . __('Ads') . '<span>' ?></th>
			<th title="<?php echo __('Sublocations'); ?>"><?php echo __('Sub') ?></th>
			<th><?php echo __('Enabled') ?></th>
			<th><?php echo __('Position') ?></th>
			<th></th>
		</tr>
		<?php
		foreach ($locations as $c)
		{
			if ($c->countAds)
			{
				$cnt = '<a href="' . Language::get_url('admin/items/?location_id=' . $c->id . '&status=2') . '" class="button green circle small">' . $c->countAds . '</a>';
			}
			else
			{
				$cnt = '';
			}

			$count_subs = Location::countSubs($c);

			if ($count_subs)
			{
				$item_title = '<a href="' . Language::get_url('admin/locations/' . $c->id . '/') . '">' . View::escape(Location::getName($c)) . '</a>';
			}
			else
			{
				$item_title = View::escape(Location::getName($c));
			}

			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . $item_title . ' ' . $cnt . '</td>';
			echo '<td data-title="' . View::escape(__('Sublocations')) . '">' . ($count_subs ? $count_subs : '') . '</td>';
			echo '<td>'
			. '<label class="input-switch" data-id="' . $c->id . '" data-switch="enabled">'
			. '<input type="checkbox" value="1" ' . ($c->enabled ? 'checked="checked"' : '') . ' />'
			. '<span class="checkmark"></span>'
			. '</label>'
			. '</td>';
			echo '<td>'
			. '<div class="button-group">'
			. '<a href="' . Language::get_url('admin/locationsOrder/' . $c->id . '/up/') . '" class="button" title="' . __('up') . '">&uarr;</a>'
			. '<a href="' . Language::get_url('admin/locationsOrder/' . $c->id . '/down/') . '" class="button" title="' . __('down') . '">&darr;</a>'
			. '</div>'
			. '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/locationsEdit/' . $c->id . '/') . '" class="button" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/locationsDelete/' . $c->id . '/') . '" class="button red" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
			. '</td>';
			echo '</tr>';
		}
		?>
	</table>
	<?php
	
	echo $paginator;
	
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No locations found. You can add locations here.') . '</p></div>';
}
?>

<script>
	addLoadEvent(function ()
	{
		cb.buttonSwitch('[data-switch]', {url: 'admin/locationsAction/'});
	});
</script>