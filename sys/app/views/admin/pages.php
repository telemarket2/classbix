<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Pages') ?> <a href="<?php echo Language::get_url('admin/pagesEdit/0/' . $parent_page->id . '/') ?>" class="button blue" title="<?php echo View::escape(__('Add')); ?>"><i class="fa fa-plus" aria-hidden="true"></i></a></h1>
<?php
if ($pages)
{
	echo '<p>' . __('Click on page name to view and edit subpages.') . '</p>';
	if ($parent_page->id)
	{
		$back_title = '<a href="' . Language::get_url('admin/pages/' . intval($parent_page->parent_id) . '/') . '"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . __('Name') . '</a>';
	}
	else
	{
		$back_title = __('Name');
	}
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php echo $back_title; ?></th>
			<th><?php echo __('ID') ?></th>
			<th><?php echo __('Enabled') ?></th>
			<th><?php echo __('View') ?></th>
			<th><?php echo __('Position') ?></th>
			<th></th>
		</tr>
		<?php
		foreach ($pages as $c)
		{
			$count_subs = Page::countSubs($c);
			if ($count_subs)
			{
				$item_title = '<a href="' . Language::get_url('admin/pages/' . $c->id . '/') . '">' . View::escape(Page::getName($c)) . '</a>';
			}
			else
			{
				$item_title = View::escape(Page::getName($c));
			}

			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . $item_title . '</td>';
			echo '<td data-title="' . View::escape(__('ID')) . '">' . View::escape($c->id) . '</td>';
			echo '<td>'
			. '<label class="input-switch" data-id="' . $c->id . '" data-switch="enabled">'
			. '<input type="checkbox" value="1" ' . ($c->enabled ? 'checked="checked"' : '') . ' />'
			. '<span class="checkmark"></span>'
			. '</label>'
			. '</td>';
			echo '<td><a href="' . Page::url($c) . '" class="button" target="_blank" title="' . View::escape(__('View')) . '">'
			. '<i class="fa fa-eye" aria-hidden="true"></i>'
			. '</a></td>';
			echo '<td>'
			. '<div class="button-group">'
			. '<a href="' . Language::get_url('admin/pagesOrder/' . $c->id . '/up/') . '" class="button" title="' . __('up') . '">&uarr;</a>'
			. '<a href="' . Language::get_url('admin/pagesOrder/' . $c->id . '/down/') . '" class="button" title="' . __('down') . '">&darr;</a>'
			. '</div>'
			. '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/pagesEdit/' . $c->id . '/') . '" class="button" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/pagesDelete/' . $c->id . '/') . '" class="button red" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
			. '</td>';
			echo '</tr>';
		}
		?>
	</table>
	<?php
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No pages found. You can add pages here.') . '</p></div>';
}
?>

<script>
	addLoadEvent(function () {
		//$('.pagesAction').click(pagesAction);
		cb.buttonSwitch('[data-switch]', {url: 'admin/pagesAction/'});
	});
</script>