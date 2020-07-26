<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Categories') ?> <a href="<?php echo Language::get_url('admin/categoriesEdit/0/' . $parent_category->id . '/') ?>" class="button blue" title="<?php echo View::escape(__('Add category')); ?>"><i class="fa fa-plus" aria-hidden="true"></i></a></h1>
<?php
if ($categories)
{
	echo '<p>' . __('Click on category name to view and edit subcategories.') . '</p>';

	if ($parent_category->id)
	{
		$back_title = '<a href="' . Language::get_url('admin/categories/' . intval($parent_category->parent_id) . '/') . '"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . __('Name') . '</a>';
	}
	else
	{
		$back_title = __('Name');
	}
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php echo $back_title . ' <span class="button small green circle">' . __('Ads') . '<span>' ?></th>
			<th title="<?php echo __('Subcategories'); ?>"><?php echo __('Sub') ?></th>
			<th><?php echo __('Locked') ?></th>
			<th><?php echo __('Enabled') ?></th>
			<th><?php echo __('Position') ?></th>
			<th><?php echo Config::abbreviate(__('Custom fields')) ?></th>
			<th></th>
		</tr>
		<?php
		foreach ($categories as $c)
		{
			if (Category::canBeLocked($c))
			{
				if ($c->locked)
				{
					$locked_txt = __('Locked');
					$locked_class = 'white';
				}
				else
				{
					$locked_txt = __('not locked');
					$locked_class = 'green';
				}
				$lock_action = '<a data-id="' . $c->id . '" data-switch="locked" href="#" class="button ' . $locked_class . '">' . $locked_txt . '</a>';
			}
			else
			{
				$lock_action = '';
			}


			if ($c->CategoryFieldRelation)
			{
				$catfield_class = 'blue';
				$catfield_txt = __('Edit custom fields');
			}
			else
			{
				$catfield_class = 'white';
				$catfield_txt = __('Add custom fields');
			}

			if ($c->countAds)
			{
				$cnt = '<a href="' . Language::get_url('admin/items/?category_id=' . $c->id . '&status=2') . '" class="button green circle small">' . $c->countAds . '</a>';
			}
			else
			{
				$cnt = '';
			}

			$count_subs = Category::countSubs($c);

			if ($count_subs)
			{
				$item_title = '<a href="' . Language::get_url('admin/categories/' . $c->id . '/') . '">' . View::escape(Category::getName($c)) . '</a>';
			}
			else
			{
				$item_title = View::escape(Category::getName($c));
			}

			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . $item_title . ' ' . $cnt . '</td>';
			echo '<td data-title="' . View::escape(__('Subcategories')) . '">' . ($count_subs ? $count_subs : '') . '</td>';
			echo '<td>' . $lock_action . '</td>';
			echo '<td>'
			. '<label class="input-switch" data-id="' . $c->id . '" data-switch="enabled">'
			. '<input type="checkbox" value="1" ' . ($c->enabled ? 'checked="checked"' : '') . ' />'
			. '<span class="checkmark"></span>'
			. '</label>'
			. '</td>';
			echo '<td>'
			. '<div class="button-group">'
			. '<a href="' . Language::get_url('admin/categoriesOrder/' . $c->id . '/up/') . '" class="button" title="' . __('up') . '">&uarr;</a>'
			. '<a href="' . Language::get_url('admin/categoriesOrder/' . $c->id . '/down/') . '" class="button" title="' . __('down') . '">&darr;</a>'
			. '</div>'
			. '</td>';
			echo '<td><a href="' . Language::get_url('admin/categoryfieldEdit/?location_id=0&category_id=' . $c->id) . '" class="button ' . $catfield_class . '" title="' . $catfield_txt . '"><i class="fa fa-sliders" aria-hidden="true"></i></a></td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/categoriesEdit/' . $c->id . '/') . '" class="button" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/categoriesDelete/' . $c->id . '/') . '" class="button red" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
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
	. '<p class="h3">' . __('No categories found. You can add categories here.') . '</p></div>';
}
?>
<script>
	addLoadEvent(function ()
	{
		cb.buttonSwitch('[data-switch]', {
			url: 'admin/categoriesAction/',
			values: {
				'enabled': {
					'0': {title: "<?php echo View::escape(__('Disabled')); ?>", cssClass: 'white'},
					'1': {title: "<?php echo View::escape(__('Enabled')); ?>", cssClass: 'green'}
				},
				'locked': {
					'0': {title: "<?php echo View::escape(__('not locked')); ?>", cssClass: 'green'},
					'1': {title: "<?php echo View::escape(__('Locked')); ?>", cssClass: 'white'}
				}
			}
		});
	});
</script>