<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Custom fields') ?> <a href="<?php echo Language::get_url('admin/itemfieldEdit/') ?>" class="button blue" title="<?php echo View::escape(__('Add custom field')); ?>"><i class="fa fa-plus" aria-hidden="true"></i></a></h1>
<?php
echo '<p>' . __('Define fields that can be used to associate with ad listings.') . '</p>';

if ($adfields)
{
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php echo __('Name') ?></th>
			<th><?php echo __('Type') ?></th>
			<th width="50%"><?php echo __('Values') ?></th>
			<th></th>
		</tr>
		<?php
		foreach ($adfields as $af)
		{
			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . View::escape(AdField::getName($af)) . '</td>';
			echo '<td data-title="' . View::escape(__('Type')) . '">' . View::escape($af->type) . '</td>';
			echo '<td>' . View::escape(AdField::formatPredefinedValue($af)) . '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/itemfieldEdit/' . $af->id . '/') . '" class="button" title="' . __('Edit') . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/itemfieldDelete/' . $af->id . '/') . '" class="button red" title="' . __('Delete') . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
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
	. '<p class="h3">' . __('No custom field found. You can add custom field here.') . '</p></div>';
}