<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Category custom fields') ?> <a href="<?php echo Language::get_url('admin/categoryfieldEdit/') ?>" class="button blue" title="<?php echo View::escape(__('Add')); ?>"><i class="fa fa-plus" aria-hidden="true"></i></a></h1>

<?php
echo '<p>' . __('Define fields that will be displayed when submitting an ad.') . '</p>';

if ($catfields_grouped)
{
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php
				echo __('Location')
				. ' <i class="fa fa-arrows-h" aria-hidden="true"></i> '
				. __('Category')
				?></th>
			<th><?php echo __('Custom fields') ?></th>
			<th></th>
		</tr>
		<?php
		foreach ($catfields_grouped as $cfg)
		{
			$arr_adfield = array();
			foreach ($cfg as $cf)
			{

				$field_extra = null;
				if ($cf->is_search)
				{
					$field_extra[] = '<abbr title="' . __('Searchable') . '">S</abbr>';
				}
				if ($cf->is_list)
				{
					$field_extra[] = '<abbr title="' . __('Visible in listing') . '">L</abbr>';
				}
				if ($field_extra)
				{
					$field_extra = ' <span class="small">(' . implode(',', $field_extra) . ')</span>';
				}

				$location = $cf->Location;
				$category = $cf->Category;
				$arr_adfield[] = View::escape(AdField::getName($cf->AdField)) . $field_extra;
			}
			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>'
			. ($location ? Location::getFullName($location, __('All locations')) . ' <i class="fa fa-arrows-h" aria-hidden="true"></i> ' : '')
			. Category::getFullName($category, __('All categories'))
			. '</td>';
			echo '<td>' . implode(', ', $arr_adfield) . '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/categoryfieldEdit/?location_id=' . $cf->location_id . '&category_id=' . $cf->category_id) . '' . '" class="button" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit fa-fw" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/categoryfieldDelete/' . $cf->location_id . '/' . $cf->category_id . '/') . '" class="button red" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash fa-fw" aria-hidden="true"></i></a>'
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
	. '<p class="h3">' . __('No category custom fields found. You can add category custom fields here.') . '</p></div>';
}